/**
 * ownCloud - OwnPad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2015
 */

(function(OCA) {
    OCA.FilesEtherpad = {
        attach: function(fileList) {
            this._extendFileActions(fileList.fileActions);
        },

        hide: function() {
            $('#etherpad').remove();
            FileList.setViewerMode(false);

	    // replace the controls with our own
	    $('#app-content #controls').removeClass('hidden');
        },

        show: function(fileName, dirName) {
            var self = this;
            var $iframe;

            var viewer = OC.generateUrl('/apps/ownpad/?file={file}&dir={dir}', {file: fileName, dir: dirName});

            $iframe = $('<iframe id="etherpad" style="width:100%;height:100%;display:block;position:absolute;top:0;" src="'+viewer+'"/>');

            FileList.setViewerMode(true);

            $('#app-content').append($iframe);
            $("#pageWidthOption").attr("selected","selected");
	    $('#app-content #controls').addClass('hidden');

            $('#etherpad').load(function(){
		var iframe = $('#etherpad').contents();
		if ($('#fileList').length) {
		    iframe.find('#filetopad_close').click(function() {
			self.hide();
		    });
		} else {
		    iframe.find("#filetopad_close").addClass('hidden');
		}
	    });
        },

        _extendFileActions: function(fileActions) {
            var self = this;
            fileActions.registerAction({
                name: 'view',
                displayName: 'Etherpad',
                mime: 'application/x-ownpad',
                permissions: OC.PERMISSION_READ,
                actionHandler: function(fileName, context) {
                    self.show(fileName, context.dir);
                }
            });
            fileActions.setDefault('application/x-ownpad', 'view');
        }
    };
})(OCA);

OC.Plugins.register('OCA.Files.FileList', OCA.FilesEtherpad);

(function(OCA) {
    OCA.FilesEtherpadMenu = {
        attach: function(newFileMenu) {
            var self = this;

            newFileMenu.addMenuEntry({
                id: 'etherpad',
                displayName: t('ownpad', 'Pad'),
                templateName: t('ownpad', 'New pad.pad'),
                iconClass: 'icon-filetype-etherpad',
                fileType: 'etherpad',
                actionHandler: function(filename) {
                    self._createPad("etherpad", filename);
                }
            });

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
        },
        _createPad: function(type, filename) {
            var self = this;

            OCA.Files.Files.isFileNameValid(filename);
            filename = FileList.getUniqueName(filename);

            $.post(
                OC.generateUrl('/apps/ownpad/ajax/newpad.php'), {
                    dir: $('#dir').val(),
                    padname: filename,
                    type: type,
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
    }
})(OCA);

OC.Plugins.register('OCA.Files.NewFileMenu', OCA.FilesEtherpadMenu);
