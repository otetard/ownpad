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
		sourceName() {
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
					try {
						sourceName = decodeURIComponent(rawSource.split('/').pop() || '')
					} catch (e) {
						sourceName = rawSource.split('/').pop() || ''
					}
				}
			}

			return sourceName === '/' ? '' : sourceName
		},

		iframeSrc() {
			const publicMatch = window.location.pathname.match(/\/s\/([^/]+)/)
			if (publicMatch && publicMatch[1]) {
				const token = publicMatch[1]
				const file = (this.basename || this.filename || this.sourceName || '') === '/'
					? ''
					: (this.basename || this.filename || this.sourceName || '')
				const publicUrl = generateUrl('/apps/ownpad/public/{token}', { token })
				return file ? `${publicUrl}?file=${encodeURIComponent(file)}` : publicUrl
			}

			// For authenticated/internal opens we must prefer filename to keep directory context.
			const file = (this.filename || this.basename || this.sourceName || '/') === '/'
				? '/'
				: (this.filename || this.basename || this.sourceName || '/')
			return generateUrl('/apps/ownpad/?file={file}', {
				file,
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
