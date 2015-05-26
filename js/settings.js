/**
 * ownCloud - OwnPad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2015
 */

$(document).ready(function() {
    $('#ownpad_settings input').change(function() {
        value = $(this).val();
        OC.AppConfig.setValue('ownpad', $(this).attr('name'), value);

        console.log($(this).attr('name'));
    });
});
