/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2017
 */

(function(OCA) {

	const FilesOwnpadMenu = function() {
		this.initialize()
	}

	FilesOwnpadMenu.prototype = {

		_etherpadEnabled: false,
		_etherpadPublicEnabled: false,
		_etherpadAPIEnabled: false,
		_ethercalcEnabled: false,

		initialize() {
			const self = this

			if (OC.getCurrentUser().uid !== null) {
				$.ajax({
					url: OC.generateUrl('/apps/ownpad/ajax/v1.0/getconfig'),
				}).done(function(result) {
					self._etherpadEnabled = result.data.ownpad_etherpad_enable === 'yes'
					self._etherpadPublicEnabled = result.data.ownpad_etherpad_public_enable === 'yes'
					self._etherpadAPIEnabled = result.data.ownpad_etherpad_useapi === 'yes'
					self._ethercalcEnabled = result.data.ownpad_ethercalc_enable === 'yes'
					OC.Plugins.register('OCA.Files.NewFileMenu', self)
				})
			}
		},

		attach(newFileMenu) {
			const self = this

			if (self._etherpadEnabled === true) {
				if (self._etherpadPublicEnabled === true || self._etherpadAPIEnabled === false) {
					newFileMenu.addMenuEntry({
						id: 'etherpad',
						displayName: t('ownpad', 'Pad'),
						templateName: t('ownpad', 'New pad.pad'),
						iconClass: 'icon-filetype-etherpad',
						fileType: 'etherpad',
						actionHandler(filename) {
							self._createPad('etherpad', filename)
						},
					})
				}

				if (self._etherpadAPIEnabled === true) {
					const displayName = self._etherpadPublicEnabled === true ? 'Protected Pad' : 'Pad'
					const templateName = self._etherpadPublicEnabled === true ? 'New protected pad.pad' : 'New pad.pad'
					newFileMenu.addMenuEntry({
						id: 'etherpad-api',
						displayName: t('ownpad', displayName),
						templateName: t('ownpad', templateName),
						iconClass: 'icon-filetype-etherpad',
						fileType: 'etherpad',
						actionHandler(filename) {
							self._createPad('etherpad', filename, true)
						},
					})
				}
			}

			if (self._ethercalcEnabled === true) {
				newFileMenu.addMenuEntry({
					id: 'ethercalc',
					displayName: t('ownpad', 'Calc'),
					templateName: t('ownpad', 'New calc.calc'),
					iconClass: 'icon-filetype-ethercalc',
					fileType: 'ethercalc',
					actionHandler(filename) {
						self._createPad('ethercalc', filename)
					},
				})
			}
		},

		_createPad(type, filename, is_protected) {
			// Default value for `is_protected`.
			var is_protected = typeof is_protected !== 'undefined' ? is_protected : false

			const self = this

			OCA.Files.Files.isFileNameValid(filename)
			filename = FileList.getUniqueName(filename)

			$.post(
				OC.generateUrl('/apps/ownpad/ajax/v1.0/newpad'), {
					dir: OCA.Files.App.currentFileList.getCurrentDirectory(),
					padname: filename,
					type,
					protected: is_protected,
				},
				function(result) {
					if (result.status == 'success') {
						FileList.add(result.data, { animate: true, scrollTo: true })
					} else {
						OC.dialogs.alert(result.data.message, t('core', 'Could not create file'))
					}
				}
			)
		},
	}

	// Only initialize the Ownpad menu when user is logged in and
	// using the “files” app.
	$(document).ready(function() {
		if ($('#filesApp').val()) {
			OCA.FilesOwnpadMenu = new FilesOwnpadMenu()
		}
	})

})(OCA)
