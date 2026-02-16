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
		sourcePath() {
			const rawSource = typeof this.source === 'string' ? this.source.trim() : ''
			let sourcePath = ''

			if (rawSource !== '') {
				if (/^https?:\/\//i.test(rawSource)) {
					try {
						const parsed = new URL(rawSource)
						const path = parsed.pathname || ''
						const publicDavMatch = path.match(/\/public\.php\/dav\/files\/[^/]+\/(.+)$/)
						const remoteDavMatch = path.match(/\/remote\.php\/dav\/files\/[^/]+\/(.+)$/)

						if (publicDavMatch && publicDavMatch[1]) {
							sourcePath = decodeURIComponent(publicDavMatch[1])
						} else if (remoteDavMatch && remoteDavMatch[1]) {
							sourcePath = decodeURIComponent(remoteDavMatch[1])
						} else {
							sourcePath = decodeURIComponent(path.split('/').pop() || '')
						}
					} catch (e) {
						sourcePath = ''
					}
				} else {
					try {
						sourcePath = decodeURIComponent(rawSource)
					} catch (e) {
						sourcePath = rawSource
					}
				}
			}

			sourcePath = sourcePath.replace(/^\/+/, '')
			return sourcePath === '/' ? '' : sourcePath
		},

		iframeSrc() {
			const publicMatch = window.location.pathname.match(/\/s\/([^/]+)/)
			if (publicMatch && publicMatch[1]) {
				const token = publicMatch[1]
				const file = (this.filename || this.sourcePath || this.basename || '') === '/'
					? ''
					: (this.filename || this.sourcePath || this.basename || '')
				const publicUrl = generateUrl('/apps/ownpad/public/{token}', { token })
				return file ? `${publicUrl}?file=${encodeURIComponent(file)}` : publicUrl
			}

			// For authenticated/internal opens we must prefer filename to keep directory context.
			const file = (this.filename || this.sourcePath || this.basename || '/') === '/'
				? '/'
				: (this.filename || this.sourcePath || this.basename || '/')
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
