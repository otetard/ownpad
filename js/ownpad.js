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


function createPadEvent(type, li) {
    console.log(li);
    
    var checkExists = setInterval(function() {
        var form = $(li).find('form');

        if(form.length) {
            clearInterval(checkExists);

            form.unbind('submit');
            form.on('submit', function(event) {
                event.stopPropagation();
		event.preventDefault();
                
                var padname = $("#input-" + type).val();
		$.post(OC.filePath('ownpad', 'ajax', 'newpad.php'),
                       {
			   dir: $('#dir').val(),
			   padname: padname,
                           type: type,
		       },
		       function(result) {
		    	   if (result.status == 'success') {
			       FileList.add(result.data, {
                                   updateSummary: false,
			           silent: true
                               });
			       FileList.reload();
                           }
		    	   else {
			       OC.dialogs.alert(result.data.message, t('core', 'Could not create file'));
			   }
                       });
                
                var li = form.parent();
		form.remove();
		/* workaround for IE 9&10 click event trap, 2 lines: */
		$('input').first().focus();
		$('#content').focus();
		li.append('<p>' + li.data('text') + '</p>');
		$('#new>a').click();
            });
        }
    }, 100);
}

$(document).ready(function() {
    if($('#new > ul > li').length > 0) {
        html = '<li class="icon-filetype-etherpad svg" data-newname="Etherpad" data-type="etherpad">';
        html += '<p>' + t('ownpad', 'Pad') + '</p>';
        html += '</li>';
        $(html).appendTo('#new > ul');

        html = '<li class="icon-filetype-ethercalc svg" data-newname="Ethercalc" data-type="ethercalc">';
        html += '<p>' + t('ownpad', 'Calc') + '</p>';
        html += '</li>';
        $(html).appendTo('#new > ul');

        var etherpadTarget = $('li[data-type="etherpad"]');
        etherpadTarget.on('click', function(evt) {
            createPadEvent("etherpad", etherpadTarget);
        });

        var ethercalcTarget = $('li[data-type="ethercalc"]');
        ethercalcTarget.on('click', function(evt) {
            createPadEvent("ethercalc", ethercalcTarget);
        });
    }
});
