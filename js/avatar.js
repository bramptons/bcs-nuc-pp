/*
 *  Document   : avatar.js
 *  Author     : Guido Gybels
 *  Description: BCS CRM functionality for avatar management (requires cropbox.js)
 *
 */
 
jQuery(function($) {
    //Create the avatar controls
    var options =
    {
        thumbBox: '.thumbBox',
        spinner: '.avatarspinner',
        imgSrc: '',
    }; 
    var cropper = $('.imageBox').cropbox(options);
    $('.avatarspinner').hide();
    $('#avatar-file').on('change', function(){
        var reader = new FileReader();
        reader.onload = function(e) {
            options.imgSrc = e.target.result;
            cropper = $('.imageBox').cropbox(options);
        }
        reader.readAsDataURL(this.files[0]);
        this.files = [];
    });
    $('#btnZoomIn').on('click', function(){
        cropper.zoomIn();
    });
    $('#btnZoomOut').on('click', function(){
        cropper.zoomOut();
    });
    $('#btnCrop').on('click', function(){
        var fd = new FormData();
        fd.append('PersonID', $('#frmPersonPersonal\\:PersonID').val());
        fd.append('imgData', cropper.getDataURL());
        $.ajax({
            type: 'POST',
            url: '/syscall.php?do=setavatar',
            data: fd,
            processData: false,
            contentType: false
        }).done(function(data) {
            var response = jQuery.parseJSON( data );
            var date = new Date();
            //$("#userAvatar").attr("src", "/img/avatar/"+response.filename+"?"+date.getTime());
            $("#previewAvatar").attr("src", "/img/avatar/"+response.filename+"?"+date.getTime());
        })
        .fail(function( jqxhr ) {
            bootbox.dialog({ message: 'Unable to complete. An error occurred while changing the avatar: '+jqxhr.statusText,
                             title: '<span class="text-danger"><i class="fa fa-times-circle"></i> <strong>Error</strong></span>',
                             buttons: { main: { label: "OK",
                                                className: "btn-primary",
                                                callback: function( ) { LoadContent($('#tab-media'), '/load.php?do=tab_person&tabid=tab-media', { cbSuccess: function( tabdiv ) { $('#tab-media').data('loaded', true); } } ); }
                                        }
                             }
            });
        });        
    });
});

function SelectFile()
{
    $("#avatar-file").trigger('click');
    return false;
}

function ClearAvatar()
{
    execSyscall(
        "/syscall.php?do=clearavatar",
        {
            postparams: { PersonID: $('#frmPersonPersonal\\:PersonID').val() },
            cbSuccess: function( jsonresponse ) { 
                var date = new Date();
                $("#previewAvatar").attr("src", "/img/avatar/"+jsonresponse.filename+"?"+date.getTime());
            },
            cbError: function( jqxhr ) {    
                bootbox.dialog({ message: 'Unable to complete. An error occurred while changing the avatar: '+jqxhr.statusText,
                                 title: '<span class="text-danger"><i class="fa fa-times-circle"></i> <strong>Error</strong></span>',
                                 buttons: { main: { label: "OK",
                                                    className: "btn-primary",
                                                    callback: function( ) { LoadContent($('#tab-media'), '/load.php?do=tab_person&tabid=tab-media', { cbSuccess: function( tabdiv ) { $('#tab-media').data('loaded', true); } } ); }
                                            }
                                 }
                });
            },
        } 
    );
}