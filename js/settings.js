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
            OC.AppConfig.setValue('ownpad', $(this).attr('name'), value);
            saved();
        });
    });

}(window, document, jQuery));
