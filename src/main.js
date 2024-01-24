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
import { addNewFileMenuEntry, Permission, File } from '@nextcloud/files'
import { getUniqueName } from './utils/fileUtils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { logger } from './logger.js'
import { emit } from '@nextcloud/event-bus'

let etherpadEnabled = false
let etherpadPublicEnabled = false
let etherpadAPIEnabled = false
let ethercalcEnabled = false

if (getCurrentUser().uid !== null) {
	const result = await axios.get(generateUrl('/apps/ownpad/ajax/v1.0/getconfig'))
	const data = result.data.data
	etherpadEnabled = data.ownpad_etherpad_enable === 'yes'
	ethercalcEnabled = data.ownpad_ethercalc_enable === 'yes'
	etherpadPublicEnabled = data.ownpad_etherpad_public_enable === 'yes'
	etherpadAPIEnabled = data.ownpad_etherpad_useapi === 'yes'
}

const createNewOwnpadDocument = async (root, filename, type, isProtected) => {
	const source = root.source + '/' + filename
	const response = await axios.post(generateUrl('/apps/ownpad/ajax/v1.0/newpad'), {
		dir: root.path,
		padname: filename,
		type,
		protected: isProtected,
	})

	return {
		fileid: parseInt(response.data.data.id),
		source,
	}
}

const addNewOwnpadDocumentHandler = async (context, content, defaultName, type, isProtected) => {
	const contentNames = content.map((node) => node.basename)
	const name = getUniqueName(defaultName, contentNames)

	try {
		const { fileid, source } = await createNewOwnpadDocument(context, name, type, isProtected)

		let mimeType
		if (type === 'ethercalc') {
			mimeType = 'application/x-ownpad-calc'
		} else {
			mimeType = 'application/x-ownpad'
		}

		const file = new File({
			source,
			id: fileid,
			mime: mimeType,
			mtime: new Date(),
			owner: getCurrentUser()?.uid || null,
			permissions: Permission.ALL,
			root: context?.root || '/files/' + getCurrentUser()?.uid,
		})

		showSuccess(t('ownpad', 'Created new Etherpad document “{name}”', { name }))
		logger.debug('Created new Etherpad document', { file, source })
		emit('files:node:created', file)
		emit('files:node:rename', file)
	} catch (error) {
		showError(t('ownpad', 'Error: {error}', { error: error.response.data.data.message }))
	}
}

addNewFileMenuEntry({
	id: 'etherpad',
	displayName: t('ownpad', 'Pad'),
	iconClass: 'icon-filetype-etherpad',
	order: 97,
	enabled: () => (etherpadEnabled && (etherpadPublicEnabled || !etherpadAPIEnabled)),
	async handler(context, content) {
		addNewOwnpadDocumentHandler(context, content, t('ownpad', 'New pad.pad'), 'etherpad', false)
	},
})

addNewFileMenuEntry({
	id: 'etherpad-api',
	displayName: t('ownpad', 'Protected Pad'),
	iconClass: 'icon-filetype-etherpad',
	order: 98,
	enabled: () => (etherpadEnabled && etherpadAPIEnabled),
	async handler(context, content) {
		addNewOwnpadDocumentHandler(context, content, t('ownpad', 'New protected pad.pad'), 'etherpad', true)
	},
})

addNewFileMenuEntry({
	id: 'ethercalc',
	displayName: t('ownpad', 'Calc'),
	iconClass: 'icon-filetype-ethercalc',
	order: 99,
	enabled: () => ethercalcEnabled,
	async handler(context, content) {
		addNewOwnpadDocumentHandler(context, content, t('ownpad', 'New calc.calc'), 'ethercalc', false)
	},
})
