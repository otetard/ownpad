/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2017
 */

import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

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

			if (getCurrentUser().uid !== null) {
			    axios.get(generateUrl('/apps/ownpad/ajax/v1.0/getconfig')).then(function(result) {
					const data = result.data.data
					self._etherpadEnabled = data.ownpad_etherpad_enable === 'yes'
					self._etherpadPublicEnabled = data.ownpad_etherpad_public_enable === 'yes'
					self._etherpadAPIEnabled = data.ownpad_etherpad_useapi === 'yes'
					self._ethercalcEnabled = data.ownpad_ethercalc_enable === 'yes'
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

		_createPad(type, filename, isProtected) {
			// Default value for `isProtected`.
			isProtected = typeof isProtected !== 'undefined' ? isProtected : false

			OCA.Files.Files.isFileNameValid(filename)
			filename = FileList.getUniqueName(filename)

		    axios.post(generateUrl('/apps/ownpad/ajax/v1.0/newpad'), {
				dir: OCA.Files.App.currentFileList.getCurrentDirectory(),
				padname: filename,
				type,
				protected: isProtected,
		    }).then(
				function(result) {
					const data = result.data.data
					if (result.status === 200) {
						FileList.add(data, { animate: true, scrollTo: true })
					} else {
						OC.dialogs.alert(data.message, t('core', 'Could not create file'))
					}
				}
			)
		},
	}

	// Only initialize the Ownpad menu when user is logged in and
	// using the “files” app.
	document.addEventListener('DOMContentLoaded', function() {
		if (document.getElementById('filesApp').value) {
			OCA.FilesOwnpadMenu = new FilesOwnpadMenu()
		}
	})

})(OCA)
