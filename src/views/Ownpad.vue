<!--
   - Nextcloud - Ownpad
   -
   - This file is licensed under the Affero General Public License
   - version 3 or later. See the COPYING file.
   -
   - @author Olivier Tétard <olivier.tetard@miskin.fr>
   - @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2023
-->

<template>
	<iframe :src="iframeSrc" />
</template>

<script>
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Ownpad',

	computed: {
		resolvedFile() {
			const rawSource = typeof this.source === 'string' ? this.source.trim() : ''
			let sourceName = ''

			if (rawSource !== '') {
				if (/^https?:\/\//i.test(rawSource)) {
					try {
						const parsed = new URL(rawSource)
						sourceName = decodeURIComponent(parsed.pathname.split('/').pop() || '')
					} catch (e) {
						sourceName = ''
					}
				} else {
					sourceName = decodeURIComponent(rawSource.split('/').pop() || '')
				}
			}

			const selected = this.basename || this.filename || sourceName || ''
			return selected === '/' ? '' : selected
		},

		iframeSrc() {
			const publicMatch = window.location.pathname.match(/\/s\/([^/]+)/)
			if (publicMatch && publicMatch[1]) {
				const token = publicMatch[1]
				const file = this.resolvedFile
				const publicUrl = generateUrl('/apps/ownpad/public/{token}', { token })
				return file ? `${publicUrl}?file=${encodeURIComponent(file)}` : publicUrl
			}

			return generateUrl('/apps/ownpad/?file={file}', {
				file: this.resolvedFile || '/',
			})
		},
	},

	async mounted() {
		this.doneLoading()
		this.$nextTick(function() {
			this.$el.focus()
		})
	},
}
</script>

<style lang="scss" scoped>
iframe {
	position: absolute;
	top: 0;
	width: 100%;
	height: calc(100vh - var(--header-height));
}
</style>
