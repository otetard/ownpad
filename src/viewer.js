/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2023
 */

import Ownpad from './views/Ownpad.vue'

OCA.Viewer.registerHandler({
    id: 'ownpad',

    mimes: [
        'application/x-ownpad',
        'application/x-ownpad-calc',
    ],

    component: Ownpad,
});
