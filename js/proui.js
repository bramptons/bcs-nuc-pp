/*
 *  Document   : proui.js
 *  Author     : Guido Gybels
 *  Description: Javascript function library for projects using the PROUI UI
 *
 */


function EnableAgeText( addon ) {
    var input = $(addon).prev('input');
/*    input.datepicker().on('changeDate', function( e ) {
        console.log('dp changeDate');
        var aoText = AgeText( moment(e.date), true ) ;
        $(this).next('.agetext').html( '<small>'+aoText+'</small>' );
    });*/
    input.on('change', function( ) {
        var aoText = AgeText( moment($(this).val()), true ) ;
        $(this).next('.agetext').html( '<small>'+aoText+'</small>' );
    });
    return true;
}

function EnableTimerInput( addon ) {
    var input = addon.closest('.input-group').find('input:first');
    var btn = addon.find('button.input-timer');
    //console.log( addon, input.attr('id') );
    input.data("stopwatch", {
        input: input,
        state: 'stopped',
        interval: null,
        begin: new Date(),
        setState: function( state ) {
            switch( state ) {
                case 'start':
                    this.begin = new Date();
                case 'continue':
                    this.state = 'started';
                    this.interval = setInterval(this.updateDisplay, 75, this);
                    break;                
                case 'stop':
                    this.state = 'stopped';
                    clearInterval( this.interval );
                    this.updateDisplay( this );
                    break;
                case 'reset':
                    this.begin = new Date();
                    break;
            }
        },
        toggle: function() {
            if( this.state === 'started' ) {
                this.setState('stop');
            } else {
                this.setState('start');
            }
        },
        updateDisplay: function( obj ) {
            if( obj.state === 'started' ) {
                var current = new Date();
                var diff = current.getTime()-obj.begin.getTime();
                obj.input.val(obj.msToTime(diff));
            }
        },
        msToTime: function (duration) {
            var milliseconds = parseInt((duration%1000)/100);
            var seconds = parseInt((duration/1000)%60);
            var minutes = parseInt((duration/(1000*60))%60);
            var hours = parseInt((duration/(1000*60*60))%24);
            hours = (hours < 10) ? "0" + hours : hours;
            minutes = (minutes < 10) ? "0" + minutes : minutes;
            seconds = (seconds < 10) ? "0" + seconds : seconds;
            return hours + ":" + minutes + ":" + seconds + "." + milliseconds;
        },
    });
    btn.on('click', function(){
        $('.tooltip').not(this).hide();
        //console.log( $(this), $(this).closest('.input-group').find('input:first').attr('id') );
        var stopwatch = $(this).closest('.input-group').find('input:first').data('stopwatch');
        stopwatch.toggle();
        $(this).html( stopwatch.state == 'started' ? 'Stop <i class="gi gi-stopwatch"></i>' : 'Start <i class="gi gi-stopwatch"></i>');
    });
}

//options
//  class
//  id
//  sender (object)
function AnimRefreshDataTable(table, options) {
    if( typeof options === 'object' ) {
        if( typeof options.class == 'string' ) {
            $('.'+options.class+' i').addClass('fa-spin');
        }
        if( typeof options.id == 'string' ) {
            $('#'+options.id+' i').addClass('fa-spin');
        }
        if( typeof options.sender == 'object' ) {
            options.sender.addClass('fa-spin');
        }
    }
    RefreshDataTable(table);
}

function CancelSpinAnimation( options ) {
    if( typeof options === 'object' ) {
        if( typeof options.class == 'string' ) {
            $('.'+options.class+' i').removeClass('fa-spin');
        }
        if( typeof options.id == 'string' ) {
            $('#'+options.id+' i').removeClass('fa-spin');
        }
        if( typeof options.sender == 'object' ) {
            options.sender.removeClass('fa-spin');
        }
    }
    $('.tooltip').not(this).hide();
}

function InitWordCompletion( container ) {
    //console.log( $(container) );
    //AdjustDragDropSizes(container);
    var maxWidth = 0;
	var maxHeight = 0;
    $(container).find('.wc-draggable').each(function(i){
        var thisWidth, thisHeight;
        var item = $(this);
        if(item.is(":visible")) {
            thisWidth = $(this).width();
            thisHeight = $(this).height();
        } else {
            var $wrap = $("<div />").appendTo($("body"));
            $wrap.css({
                "position":   "absolute !important",
                "visibility": "hidden !important",
                "display":    "block !important"
            });
            $clone = $(this).clone().appendTo($wrap);
            thisWidth = $clone.width();
            thisHeight = $clone.height();
            $wrap.remove();
        }
        if(thisWidth > maxWidth) {
            maxWidth = thisWidth;
        }
        if(thisHeight > maxHeight) {
            maxHeight = thisHeight;
        }
    });
    //console.log( maxWidth, maxHeight );
	$(container).find('.wc-dragtarget').each(function(i){
        $(this).width( maxWidth*1.15 );
        $(this).height( maxHeight*1.25 );
    });
    $(container).find('.wc-draggable').draggable({ containment: $(container), cursor: 'move', snap: '.wc-dragtarget' });
	$(container).find('input:hidden:first').val($(container).find('.wc-target').text());
    $(container).find('.wc-dragtarget').droppable({ 
        drop: function( event, ui ){
            var droppedWord = ui.draggable;
			$(event.target).text( '['+droppedWord.text()+']' );
            $(container).find('input:hidden:first').val($(container).find('.wc-target').text());
            $(this).droppable('option', 'accept', droppedWord);
			return true;
		},
        out: function( event, ui) {
            $(event.target).text( '[null]' );
            //console.log( event.target );
            $(container).find('input:hidden:first').val($(container).find('.wc-target').text());
            $(this).droppable('option', 'accept', '.wc-draggable');
            return true;
        },
	});    
}

function AddonBtnAutoEnable( button, validate ) {
    var input = $(button).parent().prev('input');
//    console.log(button);
//    console.log(input);
    input.on('change keyup paste cut', function( ) {
        if ( $(this).val().length > 0 ) {
            if( validate ) {
                if ( $(this).valid() ) {
                    SetDisabled( button, true );
//                    $(button).removeClass('disabled');
//                    $(button).prop('disabled', false);
                } else {
                    SetDisabled( button, false );
//                    $(button).addClass('disabled');
//                    $(button).prop('disabled', true);
                }
            } else {
                SetDisabled( button, false );
//                $(button).removeClass('disabled')
//                $(button).prop('disabled', false);
            }
        } else {
            SetDisabled( button, true );
//            $(button).addClass('disabled');
//            $(button).prop('disabled', true);
        }
    }).change();
    return true;
}

function ExecToggleButton( button, divid ) {
    var btn = $(button);
    var icon = btn.find('i');
    var targetDiv = $('#'+divid);
    targetDiv.toggle();
    
    if(targetDiv.is(":visible")) {
        icon.removeClass('hi-chevron-down');
        icon.addClass('hi-chevron-up');
    } else {
        icon.removeClass('hi-chevron-up');
        icon.addClass('hi-chevron-down');
    }
    return true;
}

function SetDisabled( button, state ) {
    if( state ) {
        $(button).addClass('disabled');
        $(button).prop('disabled', true);
    } else {
        $(button).removeClass('disabled');
        $(button).prop('disabled', false);
    }
}

function SetDataAttribute( parent, attr, newvalue, hinttext ) {
    var parentElement, targetElement;
    if ( typeof parent == 'string') {
        parentElement = $( '#'+parent );
    } else if( typeof parent == 'object' ) {
        parentElement = parent;
    }
    if( parentElement ) {
        targetElement = parentElement.find("[data-"+attr+"]:first");
        if( targetElement ) {
            targetElement.attr('data-'+attr, newvalue);
            targetElement.parent().siblings('span.help-block').text(hinttext);
        }
    }
}

function dtDefDrawCallBack( oSettings, cboptions )
{
    $('.dataTables_wrapper [data-toggle=\"tooltip\"], .enable-tooltip').tooltip({container: 'body', animation: false});
    $('.dataTables_wrapper [data-toggle=\"popover\"], .enable-popover').popover({container: 'body', animation: true});
    //Ajax loaded popovers
    $('*[data-poload]').popover({
        html: true,
        trigger: 'manual',
        content: function() {
            var item = $(this);
            var div_id = "tmp-id-" + $.now();
            return loadPopover( item, div_id );
        }
    }).click(function() {
        $(this).popover('toggle');
    });
    if( typeof cboptions == 'object' ) {
        if( typeof cboptions.callback == 'function' ) {
            cboptions.callback( oSettings );
        } else if( typeof cboptions.callback == 'string' ) {
            window[options.cbSuccess]( oSettings );
        }
    }
//    console.log( oSettings );
}

function loadPopover( item, div_id ) {
    //console.log( item.attr('data-polines') );
    var breakCount = 0;
    if ( item.attr('data-polines') ) {
        breakCount = Math.max(parseInt(item.data('polines')), 0);
    }
    //console.log(breakCount);
    $.ajax({
        url: item.data('poload'),
        success: function( response ) {
            $('#'+div_id).html( response );
            if( item.attr('data-pohideafter') ) {
                setTimeout(function(){ item.popover('hide') }, item.data('pohideafter'));
            }
        }
    });
    //console.log('<div id="'+div_id+'">Loading...'+strRepeat( "<br>", breakCount+1 )+'</div>');
    return '<div id="'+div_id+'">Loading...'+strRepeat( "<br>", breakCount+1 )+'</div>';
}

function ToggleDatepicker( addon ) {
    var dpInput = $(addon).parent().siblings('input');
    if( dpInput.data('datepicker').picker.is(':visible') ) { 
        dpInput.datepicker('hide');
    } else {
        dpInput.datepicker('show');
    }
    return true;
}

function clearForm( form )
{
    var formItem;
    if ( typeof form == 'string') {
        formItem = $( '#'+form );
    } else if( typeof form == 'object' ) {
        formItem = form;
    }
    if( formItem ) {
        formItem.find(':input').not(':button, :submit, :reset, :hidden, :checkbox, :radio').val('');
        formItem.find('.textarea-editor').each( function(){ $(this).data("wysihtml5").editor.clear() } );
        formItem.find(':checkbox, :radio').prop('checked', false);
    }
}

function ToggleSelect( control, onchange ) {
    $('.tooltip').not(this).hide();
    var currentState;
    currentState = (control.attr('data-selecteditem') == 'true');
    var iconElement = $(control).find('i:first');
    if( currentState ) {
        iconElement.removeClass('gi-check');
        iconElement.addClass('gi-unchecked');
    } else {
        iconElement.removeClass('gi-unchecked');
        iconElement.addClass('gi-check');
    }
    control.attr('data-selecteditem', (currentState ? 'false' : 'true'));
    var newState = (currentState ? false : true);
    if( typeof onchange == 'function' ) {
        onchange( newState );
    } else if( typeof options.cbSuccess == 'string' ) {
        window[onchange]( newState );
    }
}

function GetSelectedItemCount( parent ) {
    var searchFrom;
    if( typeof parent == 'undefined' ) {
        searchFrom = document;
    } else if( typeof parent == 'object' ) {
        searchFrom = parent;
    } else {
        searchFrom = $( '#'+parent.toString );
    }
    return $(searchFrom).find('[data-selecteditem=true]').length;
}

function SelectAllItems( parent ) {
    
}

function GetAllSelectedItems( parent ) {
    var searchFrom;
    if( typeof parent == 'undefined' ) {
        searchFrom = document;
    } else if( typeof parent == 'object' ) {
        searchFrom = parent;
    } else {
        searchFrom = $( '#'+parent.toString );
    }
    return $(searchFrom).find('[data-selecteditem=true]');
}

//call with (parent, key) or (key) as arguments
function GetDataFromAllSelectedItems( ) {
    var aData = [];
    var aParent;
    var aKey;
    if(arguments.length > 1) {
        aParent = arguments[0];
        aKey = arguments[1];
    } else {
        aKey = arguments[0];
    }
    forAllSelectedItems( aParent, 
        function( button ){
            var value = $(button).data(aKey);
            if( typeof value != 'undefined' ) {
                aData.push( value );
            }
        } 
    )
    return aData;
}

//call with (parent, callee) or ?(callee) as arguments
function forAllSelectedItems( ) {
   var searchFrom;
   var tocall;
   if(arguments.length > 1) {
        if( typeof arguments[0] == 'undefined' ) {
            searchFrom = document;
        } else if( typeof arguments[0] == 'object' ) {
            searchFrom = arguments[0];
        } else {
            searchFrom = $( '#'+arguments[0].toString );
        }
        tocall = arguments[1];
   } else {
       searchFrom = document;
       tocall = arguments[0];
   }
    $(searchFrom).find('[data-selecteditem=true]').each( function() {
        if( typeof tocall == 'function' ) {
            tocall( this );
        } else if( typeof options.cbSuccess == 'string' ) {
            window[tocall]( this );
        }
    });
}

function SelectTab( params ) {
    var tabname;
    if( typeof params == 'object' ) {
        if( params.tab ) {
            tabname = params.tab;
        }
    } else {
        var getParams;
        getParams = getUrlVars();
        if( getParams.tab ) {
            tabname = getParams.tab;
        }
    }
    if( tabname ) {
        $('.nav-tabs a[href="#tab-'+tabname+'"]').tab('show');
    }
}
