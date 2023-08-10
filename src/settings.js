/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2017
 */

import * as $ from 'jquery';

(function(window, document, $) {
	'use strict'

	$(document).ready(function() {
		const savedMessage = $('#ownpad-saved-message')

		const saved = function() {
			if (savedMessage.is(':visible')) {
				savedMessage.hide()
			}

			savedMessage.fadeIn(function() {
				setTimeout(function() {
					savedMessage.fadeOut()
				}, 5000)
			})
		}

		$('#ownpad_settings input').change(function() {
			let value = $(this).val()

			if ($(this).attr('type') === 'checkbox') {
				if (this.checked) {
					value = 'yes'
				} else {
					value = 'no'
				}
			}

			OC.AppConfig.setValue('ownpad', $(this).attr('name'), value)
			saved()
		})

		$('#ownpad_etherpad_enable').change(function() {
			$('#ownpad_etherpad_settings').toggleClass('hidden', !this.checked)

			if (this.checked && $('#ownpad_etherpad_useapi').is(':checked')) {
				$('#ownpad_etherpad_useapi_settings').removeClass('hidden')
			} else {
				$('#ownpad_etherpad_useapi_settings').addClass('hidden')
			}

		})

		$('#ownpad_etherpad_useapi').change(function() {
			$('#ownpad_etherpad_useapi_settings').toggleClass('hidden', !this.checked)
		})

		$('#ownpad_ethercalc_enable').change(function() {
			$('#ownpad_ethercalc_settings').toggleClass('hidden', !this.checked)
		})
	})

}(window, document, $))
