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

    var FilesOwnpadMenu = function() {
        this.initialize();
    }

    FilesOwnpadMenu.prototype = {

        _etherpadEnabled: false,
        _etherpadPublicEnabled: false,
        _etherpadAPIEnabled: false,
        _ethercalcEnabled: false,

        initialize: function() {
            var self = this;

            if(OC.getCurrentUser().uid !== null) {
                $.ajax({
                    url: OC.generateUrl('/apps/ownpad/ajax/v1.0/getconfig')
                }).done(function(result) {
                    self._etherpadEnabled = result.data.ownpad_etherpad_enable === "yes";
                    self._etherpadPublicEnabled = result.data.ownpad_etherpad_public_enable === "yes";
                    self._etherpadAPIEnabled = result.data.ownpad_etherpad_useapi === "yes";
                    self._ethercalcEnabled = result.data.ownpad_ethercalc_enable === "yes";
                    OC.Plugins.register('OCA.Files.NewFileMenu', self);
                });
            }
        },


        attach: function(newFileMenu) {
            var self = this;

            if(self._etherpadEnabled === true) {
                if (self._etherpadPublicEnabled === true || self._etherpadAPIEnabled === false) {
                    newFileMenu.addMenuEntry({
                        id: 'etherpad',
                        displayName: t('ownpad', 'Pad'),
                        templateName: t('ownpad', 'New pad.pad'),
                        iconClass: 'icon-filetype-etherpad',
                        fileType: 'etherpad',
                        actionHandler: function (filename) {
                            self._createPad("etherpad", filename);
                        }
                    });
                }

                if(self._etherpadAPIEnabled === true) {
                    var displayName = self._etherpadPublicEnabled === true ? 'Protected Pad' : 'Pad';
                    var templateName = self._etherpadPublicEnabled === true ? 'New protected pad.pad' : 'New pad.pad';
                    newFileMenu.addMenuEntry({
                        id: 'etherpad-api',
                        displayName: t('ownpad', displayName),
                        templateName: t('ownpad', templateName),
                        iconClass: 'icon-filetype-etherpad',
                        fileType: 'etherpad',
                        actionHandler: function(filename) {
                            self._createPad("etherpad", filename, true);
                        }
                    });
                }
            }

            if(self._ethercalcEnabled === true) {
                newFileMenu.addMenuEntry({
                    id: 'ethercalc',
                    displayName: t('ownpad', 'Calc'),
                    templateName: t('ownpad', 'New calc.calc'),
                    iconClass: 'icon-filetype-ethercalc',
                    fileType: 'ethercalc',
                    actionHandler: function(filename) {
                        self._createPad("ethercalc", filename);
                    }
                });
            }
        },

        _createPad: function(type, filename, is_protected) {
            // Default value for `is_protected`.
            var is_protected = typeof is_protected !== 'undefined' ? is_protected : false;

            var self = this;

            OCA.Files.Files.isFileNameValid(filename);
            filename = FileList.getUniqueName(filename);

            $.post(
                OC.generateUrl('/apps/ownpad/ajax/v1.0/newpad'), {
                    dir: OCA.Files.App.currentFileList.getCurrentDirectory(),
                    padname: filename,
                    type: type,
                    protected: is_protected
                },
                function(result) {
                    if(result.status == 'success') {
                        FileList.add(result.data, {animate: true, scrollTo: true});
                    }
                    else {
                        OC.dialogs.alert(result.data.message, t('core', 'Could not create file'));
                    }
                }
            );
        }
    };

    // Only initialize the Ownpad menu when user is logged in and
    // using the “files” app.
    $(document).ready(function() {
        if($('#filesApp').val()) {
            OCA.FilesOwnpadMenu = new FilesOwnpadMenu();
        }
    });

})(OCA);
