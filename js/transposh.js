/*  Copyright © 2009 Transposh Team (website : http://transposh.org)
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*function display_dialog(caption, content)
{        
overlib(content,
		MODAL,
		MODALCOLOR, 	'#4488dd',
		MODALOPACITY, 20,
		MODALSCROLL,
		CAPTION, caption,
		CGCLASS, 'olraisedBlue',
		CLOSETEXT, 'Close',
		CLOSECLICK,
		CLOSETITLE,'Close',
		CAPTIONPADDING,4,
		TEXTPADDING,14,
		BGCLASS,'olbgD',
		CAPTIONFONTCLASS,'olcapD',
		FGCLASS,'olfgD',
		TEXTFONTCLASS,'oltxtD',
		SHADOW, SHADOWCOLOR, '#113377', SHADOWOPACITY, 20,
		WRAP, STICKY, SCROLL, MIDX,0, MIDY,0);
}
*/

//Show tooltip over a translated text
/*function hint(original)
{
    overlib('<bdo dir="ltr">'+ original +'</bdo>',
    		FGCLASS,'olfgD',
    		TEXTFONTCLASS,'oltxtD',
    		AUTOSTATUS,WRAP);
}*/

// fetch translation from google translate...
function getgt()
{
	google.language.translate(jQuery("#tr_original").val(), "", transposh_params['lang'], function(result) {
		  if (!result.error) {
		    jQuery("#tr_translation").val(jQuery("<div>"+result.translation+"</div>").text());
		  } 
		});
}

//Ajax translation
function ajax_translate(original,translation,source,segment_id) {
    jQuery.ajax({  
        type: "POST",
        url: transposh_params['post_url'],
        data: {token: jQuery("#tr_" + segment_id).attr('token'),
				translation: translation,
				lang: transposh_params['lang'],
				source: source,
				translation_posted: "1"},
        success: function(req) {
        	var pre_translated = jQuery("#tr_" + segment_id).html();
        	var new_text = translation;
        	//reset to the original content - the unescaped version if translation is empty
            if(jQuery.trim(translation).length == 0) {
            	new_text = original;
            }
            // rewrite text for all matching items at once
        	jQuery(".tr_t,.tr_u").filter(function() {return jQuery(this).html() == pre_translated;}).html(new_text)
        		.each(function (i) { // handle the image changes
        			var img_segment_id = jQuery(this).attr('id').substr(jQuery(this).attr('id').lastIndexOf('_')+1);
                    //current img 
                    var img = jQuery("#tr_img_" + img_segment_id).attr('src');
                    if (img != undefined) {
                    	//rewrite onclick function - in case of re-edit
                    	jQuery("#tr_img_" + img_segment_id).click(function () {
                    		translate_dialog(original, translation, img_segment_id);
                    	});
                    	img = img.substr(0,img.lastIndexOf("/")) + "/";
                    	// handle image
                    	if(jQuery.trim(translation).length == 0) {
                        //switch to the edit img
                    		img += "translate.png";
                    	} else {
                    		if (source == 1) {
                    			//switch to the auto img
                    			img += "translate_auto.png";                		
                    		} else {
                    		//	switch to the fix img
                    			img += "translate_fix.png";
                    		}
                    	}
                    	//	rewrite image
                    	jQuery("#tr_img_" + img_segment_id).attr('src', img);
                    };
        			
        		});
                
            //close dialog
        	if (typeof cClick == 'function' && source == 0) {
        		cClick();
        	}
    	},
                
        error: function(req) {
    		if (source == 0) {
    			alert("Error !!! failed to translate.\n\nServer's message: " + req.statusText);
    		}
    	}
    });
}

//function for auto translation

function do_auto_translate() {
	jQuery(".tr_u").each(function (i) {
		var translated_id = jQuery(this).attr('id');
		google.language.translate(jQuery(this).text(), "", transposh_params['lang'], function(result) {
			if (!result.error) {
				var segment_id = translated_id.substr(translated_id.lastIndexOf('_')+1);
		        ajax_translate(jQuery("#"+translated_id).text(),jQuery("<div>"+result.translation+"</div>").text(),1,segment_id);
		        jQuery("#"+translated_id).addClass("tr_t").removeClass("tr_u");
			} 
		});
	});
}

//to run at start
jQuery.noConflict();
//read parameters
var transposh_params = new Array(); 
jQuery("script[src*='transposh.js']").each(function (i) {
	var query_string = unescape(this.src.substring(this.src.indexOf('?')+1));
	var parms = query_string.split('&');
	for (var i=0; i<parms.length; i++) {
		var pos = parms[i].indexOf('=');
		if (pos > 0) {
			var key = parms[i].substring(0,pos);
			var val = parms[i].substring(pos+1);
			transposh_params[key] = val;	
		}
	}
});

//Open translation dialog 
/*function translate_dialog(original, trans, segment_id)
{
caption='Edit Translation';
//alert (this.id);
var dialog = ''+
    ('<form id="tr_form" name="transposh_edit_form" method="post" action="' + transposh_params['post_url'] + '"><div>') +
     '<p dir="ltr">Original text<br \/><textarea id="tr_original_unescaped" cols="60" rows="3" readonly="readyonly">' +
       original + '</textarea> <\/p>' +
    '<p>Translate to<br \/><input class="olinput" type="text" id="tr_translation" name="translation" size="80" value="'+ trans +
    '"' + 'onfocus="OLmEdit=1;" onblur="OLmEdit=0;"<\/p>' +
    '<input type="hidden" name="translation_posted" value= "1">' +
    '<p><input class="olinput" onclick="getgt()" type="button" value="Get Suggestion!"/>&nbsp;<input class="olinput" type="submit" value="Translate"/><\/p>' +
    ('<\/div><\/form>');

	display_dialog(caption, dialog);

	// attach handler to form's submit event 
	jQuery('#tr_form').submit(function() { 
        var translation = jQuery('#tr_translation').val();
                        
        ajax_translate(original,translation,0,segment_id);
        
        // return false to prevent normal browser submit and page navigation 
        return false;
        
    });
}*/

function translate_dialog(original, trans, segment_id) {
	jQuery("#tabs").remove();
	jQuery('<div id="tabs" title="Edit Translation"/>').appendTo("body");
	jQuery("#tabs").append('<ul/>').tabs({ cache: true });
	jQuery("#tabs").tabs('add','#tabs-1','Translate');
	jQuery("#tabs").tabs('add','index.php?tr_token_hist='+jQuery("#tr_" + segment_id).attr('token')+'&lang='+transposh_params['lang'],'History');
	jQuery("#tabs-1").append(
			'<form>' +	
			'<fieldset>' +
			'<label for="original">Original Text</label>' +
			'<input type="text" name="original" id="tr_original" class="text ui-widget-content ui-corner-all" readonly="y"/>' +
			'<label for="translation">Translate To</label>' +
			'<input type="text" name="translation" id="tr_translation" value="" class="text ui-widget-content ui-corner-all"/>' +
			'</fieldset>' +
			'</form>');
	jQuery("#tr_original").val(original);
	jQuery("#tr_translation").val(trans);
	jQuery("#tabs").css("text-align","left");
	jQuery("#tabs-1 label").css("display","block");
	jQuery("#tabs-1 input.text").css({'margin-bottom':'12px', 'width' : '95%', 'padding' : '.4em'});
	jQuery("#tabs").bind('tabsload', function(event, ui) {
		//TODO, formatting here, not server side
		jQuery("table",ui.panel).addClass("ui-widget ui-widget-content").css({'width' : '95%', 'padding' : '0'});
		jQuery("table thead tr",ui.panel).addClass("ui-widget-header");
	});
	jQuery("#tabs").tabs().dialog({
		bgiframe: true,
		modal: true,
		width: 460,
		buttons: {
			Suggest: function() {
				getgt();
			},
			Ok: function() {
				var translation = jQuery('#tr_translation').val();
				ajax_translate(original,translation,0,segment_id);
				jQuery(this).dialog('close');
			}
		}
	}).css("padding",0).dialog('open');
}

google.load("language", "1");
jQuery(document).ready(
	function() {
		do_auto_translate();
	}
);

