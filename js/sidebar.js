/*
 *  Document   : sidebar.js
 *  Author     : Guido Gybels
 *  Description: Sidebar Routines
 *
 */

jQuery(function($) {
//	sbLoadAlerts(0);
//	setInterval(function() {
//		sbLoadAlerts(750);        
//	}, 300000); //Refresh the alerts every 5 minutes
//    validateChangeMyPassword();
    $("#frmChangeMyPassword\\:OldPassword, #frmChangeMyPassword\\:NewPassword1, #frmChangeMyPassword\\:NewPassword2").change(validateChangeMyPassword);
    $("#frmChangeMyPassword\\:OldPassword, #frmChangeMyPassword\\:NewPassword1, #frmChangeMyPassword\\:NewPassword2").keyup(validateChangeMyPassword);
    $("#frmChangeMyPassword\\:btnChangeMyPassword").on("click", function( event ) {
        var params = $.param( { fname: 'changepw', script: 'set.php' } );
        var url = 'https://'+window.location.host.concat('/syscall.php?do=marvin&', params);
        var postdata = { password: $("#frmChangeMyPassword\\:NewPassword1").val() };
        var jqxhr = $.post( url, postdata, function() {
            
        })
        .done(function( data ) {
            var response = jQuery.parseJSON( data );
            bootbox.dialog({ message: response.message,
                             title: (response.changed ? '<span class="text-success"><i class="fa fa-check-circle"></i> <strong>Password changed!</strong></span>' : '<span class="text-danger"><i class="fa fa-times-circle"></i> <strong>Error</strong></span>'),
                             buttons: { main: { label: "OK",
                                                className: (response.changed ? "btn-success" : "btn-danger") ,
                                                callback: function( ) {  }
                                        }
                             }
            });
        })
        .fail(function( data ) {
            if( jqxhr.status == 403 ) {
                //response was access denied; reload the page;
                window.location.reload(true);
            } else {
                bootbox.dialog({ message: 'Failed! An error occurred while saving your data: '+jqxhr.statusText,
                                 title: '<span class="text-danger"><i class="fa fa-times-circle"></i> <strong>Error</strong></span>',
                                 buttons: { main: { label: "OK",
                                                    className: "btn-danger",
                                                    callback: function( ) {  }
                                            }
                                 }
                });
            }
        })
        .always(function() {
            $(event.target).closest('div.modal').modal('hide');
        });
    });
});

function validateChangeMyPassword() {
    if( ($("#frmChangeMyPassword\\:NewPassword1").val().length > 0)
            &&
        ($("#frmChangeMyPassword\\:NewPassword1").val() === $("#frmChangeMyPassword\\:NewPassword2").val())
    ) {
        $("#frmChangeMyPassword\\:btnChangeMyPassword").prop("disabled", false);
    }
    else {
        $("#frmChangeMyPassword\\:btnChangeMyPassword").prop("disabled", true);
    }
}

function sbDeleteAlert( notificationid ) {
    var url = 'https://'+window.location.host.concat('/syscall.php?do=deletenotification', '&', $.param( { notificationid: notificationid } ));
    var jqxhr = $.get( url, function() {
    })
    .always(function( ) {
        LoadNotifications( true );
    });
}
