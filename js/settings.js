/**
 * ownCloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2015
 */

(function (window, document, $) {
    'use strict';

    $(document).ready(function() {
        var savedMessage = $('#ownpad-saved-message');

        var saved = function () {
            if (savedMessage.is(':visible')) {
                savedMessage.hide();
            }

            savedMessage.fadeIn(function () {
                setTimeout(function () {
                    savedMessage.fadeOut();
                }, 5000);
            });
        };

        $('#ownpad_settings input').change(function() {
            var value = $(this).val();

	    if($(this).attr('type') === 'checkbox') {
		if (this.checked) {
		    value = 'yes';
		} else {
		    value = 'no';
		}
	    }

            OC.AppConfig.setValue('ownpad', $(this).attr('name'), value);
            saved();
        });

        $('#ownpad_etherpad_enable').change(function() {
            $("#ownpad_etherpad_settings").toggleClass('hidden', !this.checked);
        });


        $('#ownpad_ethercalc_enable').change(function() {
            $("#ownpad_ethercalc_settings").toggleClass('hidden', !this.checked);
        });
    });

}(window, document, jQuery));
