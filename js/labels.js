/*
 *  Document   : labels.js
 *  Author     : Guido Gybels
 *  Description: Generic JS support for Dymo Labelwriters
 *
 */
 
var dymo_supported = false;
var dymo_printers;

function LoadPrintersBtn (btn) {
	var ul = $(btn).nextAll('ul:first');
	
	if (dymo_supported)
	{
		ul.empty();
		for(var i = 0; i < dymo_printers.length; i++)
		{
			ul.append('<li><a href="#">'+dymo_printers[i].name+'</a></li>');
		}
		$(btn).show();
	}
	else
	{
		$(btn).hide();
	}
}

$(function(){
	if (typeof dymo === 'object')
	{
		dymo_printers = dymo.label.framework.getPrinters();
		if(dymo_printers.length > 0)
		{
			dymo_supported = true;
		}
	}
});