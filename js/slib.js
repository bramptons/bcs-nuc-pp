/*
 *  Document   : slib.js
 *  Author     : Guido Gybels
 *  Description: Javascript function library shared between different projects, independent of any specific UI
 *
 */

 function strRepeat( str, times ) {
    return (new Array(times)).join(str);
}
 
function getUrlVars() {
	var vars = [], hash;
	var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	for(var i = 0; i < hashes.length; i++) {
		hash = hashes[i].split('=');
//		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
	}
	return vars;
}

function getUrlVarObj() {
	var vars = {}, hash;
	var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	for(var i = 0; i < hashes.length; i++) {
		hash = hashes[i].split('=');
		vars[hash[0]] = hash[1];
	}
	return vars;
}

var getCookies = function(){
  var pairs = document.cookie.split(";");
  var cookies = {};
  for (var i=0; i<pairs.length; i++){
    var pair = pairs[i].split("=");
    cookies[pair[0]] = unescape(pair[1]);
  }
  return cookies;
}

function toObject( arr ) {
  var aObj = {};
  for (var i = 0; i < arr.length; ++i)
    if (arr[i] !== undefined) aObj[i] = arr[i];
  return aObj;
}

function inputValueToScaledInteger( source, scale ) {
    var inputElement;
    var multiplier;
    if( typeof source == 'object' ) {
        inputElement = source;
    } else if ( typeof source == 'string' ) {
        inputElement = $("[name='"+source+"']");
    }
    if( typeof scale == 'undefined' ) {
        multiplier = 100;
    } else {
        multiplier = scale;
    }
    var amount = inputElement.val().replace(/[^\d.-]/g, '');
    return amount*multiplier;
}

function SinPlu(quantity, spoptions)
{
    var options = spoptions;
    if( typeof spoptions == 'undefined' ) {
        options = { noun: 'item' };
    } else {
        options = spoptions;
        if( typeof options.noun == 'undefined' ) {
            options.noun = 'item';
        }
    }
    var Result = '';
    var verbBefore = ( options.verbafter ? false : true);
    if( typeof options.verb == 'string' && verbBefore) {
        switch( options.verb ){
            case 'has':
            case 'have':
                Result = Result.concat((quantity == 1 ? 'has ' : 'have '));
                break;
            case 'is':
            case 'be':
                Result = Result.concat((quantity == 1 ? 'is ' : 'are '));
                break;
        }
    }
    if (( typeof options.omit !== 'object' ) || !(quantity.toString in options.omit )) {
        Result = Result+quantity.toLocaleString({ style: 'decimal',  });
    }
    if( options.noun.length > 0 ) {
        Result = Result+(Result.length > 0 ? ' ' : '');
        if( quantity == 1) {
            Result = Result+options.noun;
        } else {
            var lastLetter = options.noun.substring( options.noun.length-1 );
            if( options.noun.substring( options.noun.length-2, options.noun.length ) == 'ix' ) {
                Result = Result.concat(options.noun.substring( 0, options.noun.length-1 ), 'ces');
            } else if( lastLetter == 'y' ) {
                var vowels = 'aeiou';
                if (vowels.indexOf(options.noun.substring( options.noun.length-2, options.noun.length-1 )) !== -1 ) {
                    Result = Result.concat(options.noun, 's');
                } else {
                    Result = Result.concat(options.noun.substring(0, options.noun.length-1), 'ies');
                }
            } else if(( lastLetter == 's' ) || ( lastLetter == 'x' )) {
                Result = Result.concat(options.noun, 'es');
            } else {
                Result = Result.concat(options.noun, 's');
            }
        }
    }
    if( typeof options.verb == 'string' && !verbBefore) {
        switch( options.verb ){
            case 'has':
            case 'have':
                Result = Result.concat((quantity == 1 ? ' has' : ' have'));
                break;
            case 'is':
            case 'be':
                Result = Result.concat((quantity == 1 ? ' is' : ' are'));
                break;
        }
    }    
    return Result;
}

function readCookie(name) {
	var lookFor = name + "=";
	var cookies = document.cookie.split(';');
	for(var i=0; i < cookies.length; i++) {
		var thiscookie = cookies[i];
		//Remove whitespace at the start
		while (thiscookie.charAt(0)==' ') thiscookie = thiscookie.substring(1);
		if (thiscookie.indexOf(lookFor) == 0) return thiscookie.substring(lookFor.length);
	}
	return null;
}

function Notification() {
	var snd = new Audio("audio/chime.mp3");
	snd.play();
}

function FirstItem( obj ) {
    for (var key in obj) break;
    return obj[key];
}

function ucfirst( string ) {
    return ( typeof string !== "undefined" ? string.charAt(0).toUpperCase() + string.slice(1) : '' );
}

function UTCDateToLocalDate(date) {
    var newDate = new Date(date.getTime()+date.getTimezoneOffset()*60*1000);
    var offset = date.getTimezoneOffset() / 60;
    var hours = date.getHours();
    newDate.setMinutes(date.getMinutes() - date.getTimezoneOffset())     
    return newDate;   
}

function DateToYYYYMMDD(date, separator) {
    if ( typeof separator == 'undefined' ) {
        separator = '';
    }
    var yyyy = '0000'+date.getFullYear();
    var mm = '0'+(date.getMonth()+1);
    var dd = '0'+date.getDate();
    return yyyy.substr(-4)+separator+mm.substr(-2)+separator+dd.substr(-2);
}

function RefreshDataTable(table) {
    var aSelector;
    if( table === Array ) {
        $.each( table, function(index, atable) {
            if (typeof atable !== 'undefined')  {
                atable.fnDraw();
                if(typeof aSelector === 'undefined') {
                    aSelector = $(atable.selector).parent().closest('div');
                }
            }
        });
    } else {
        if (typeof table !== 'undefined') {
            table.fnDraw();
            aSelector = $(table.selector).parent().closest('div');
        }
    }
//	table.fnDraw();
//	$(table.selector).parent().closest('div').focus();
    if(typeof aSelector !== 'undefined') {
        aSelector.focus();
    }
}

eventCancel = function (e) {
    if (!e)
    if (window.event) e = window.event;
    else return;
    if (e.cancelBubble != null) e.cancelBubble = true;
    if (e.stopPropagation) e.stopPropagation();
    if (e.preventDefault) e.preventDefault();
    if (window.event) e.returnValue = false;
    if (e.cancel != null) e.cancel = true;
}

function AgeText( date, ago ) {
    var result = '&hellip;';
    if( date.isValid() ) {
        var now = moment();
        if( now.format('YYYYMMDD') == date.format('YYYYMMDD') ) {
            result = 'Today';
        } else {
            var daysdiff = -now.diff(date, 'days', true);
            if( daysdiff < 0 ) {
                //In the past
                daysdiff = Math.abs(daysdiff)-1;
                if ( daysdiff <= 1 ) {
                    result = 'Yesterday';
                } else {
                    monthsdiff = Math.abs(now.diff(date, 'months', true));
                    yearsdiff = Math.abs(now.diff(date, 'years', true));
                    if ( monthsdiff < 1) {
                        result = Math.round(daysdiff).toString()+' Days'+( ago ? ' ago': '');
                    } else if(yearsdiff < 1) {
                        monthsdiff = Math.floor(monthsdiff);
                        daysdiff = Math.round(daysdiff % 30.416666);
                        result = (monthsdiff >= 1 ? monthsdiff.toString()+' Month'+(monthsdiff > 1 ? 's' : '') : '')+', '+daysdiff.toString()+' Day'+(daysdiff == 1 ? '': 's')+( ago ? ' ago': '');
                    } else {
                        yearsdiff = Math.floor(yearsdiff);
                        monthsdiff = Math.round(monthsdiff % 12);
                        if(monthsdiff == 12) {
                            yearsdiff++;
                            monthsdiff = 0;
                        }
                        result = yearsdiff.toString()+' Year'+(yearsdiff > 1 ? 's' : '')+(monthsdiff >= 1 ? ', '+monthsdiff.toString()+' Month'+(monthsdiff == 1 ? '': 's') : '')+( ago ? ' ago': '');
                    }
                }
            } else {
                //In the future
                daysdiff = Math.abs(daysdiff+1);
                if ( daysdiff <= 1 ) {
                    result = 'Tomorrow';
                } else {
                    monthsdiff = Math.abs(now.diff(date, 'months', true));
                    yearsdiff = Math.abs(now.diff(date, 'years', true));
                    if (monthsdiff < 1) {
                        daysdiff = Math.round(daysdiff);
                        if( daysdiff == 1 ){
                            result = 'Tomorrow';
                        } else {
                            result = 'in '+daysdiff.toString()+' Day'+(daysdiff > 1 ? 's' : '');
                        }
                    } else if(yearsdiff < 1) {
                        monthsdiff = Math.floor(monthsdiff);
                        daysdiff = Math.round(daysdiff % 30.416666);
                        result = 'in '+monthsdiff.toString()+' Month'+(monthsdiff > 1 ? 's' : '')+', '+daysdiff.toString()+' Day'+(daysdiff == 1 ? '': 's');
                    } else {
                        yearsdiff = Math.floor(yearsdiff);
                        monthsdiff = Math.round(monthsdiff % 12);
                        if(monthsdiff == 12) {
                            yearsdiff++;
                            monthsdiff = 0;
                        }
                        result = 'in '+yearsdiff.toString()+' Year'+(yearsdiff > 1 ? 's' : '')+', '+monthsdiff.toString()+' Month'+(monthsdiff == 1 ? '': 's');
                    }
                }
            }
        }
    }
    return result;
}

function SetHiddenFieldValue( field, newValue, onDone  ) {
    field.val(newValue);
    if( typeof onDone == 'function' ) {
        onDone( field, newValue );
    } else if( typeof onDone == 'string' ) {
        window[onDone]( field, newValue );
    }
}

