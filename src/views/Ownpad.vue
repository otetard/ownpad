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
         iframeSrc() {
             return generateUrl('/apps/ownpad/?file={file}', {file: this.filename});
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
