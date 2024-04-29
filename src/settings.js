/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2017
 */

import Vue from 'vue'
import OwnpadSettingsForm from './components/OwnpadSettingsForm.vue'

Vue.prototype.t = t

const View = Vue.extend(OwnpadSettingsForm)
new View().$mount('#ownpad-settings')
