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

function display_dialog(caption, content)
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

//Show tooltip over a translated text
function hint(original)
{
    overlib('<bdo dir="ltr">'+ original +'</bdo>',
    		FGCLASS,'olfgD',
    		TEXTFONTCLASS,'oltxtD',
    		AUTOSTATUS,WRAP);
}

// fetch translation from google translate...
function getgt()
{
	google.language.translate(jQuery("#tr_original_unescaped").text(), "", transposh_target_lang, function(result) {
		  if (!result.error) {
		    jQuery("#tr_translation").val(result.translation);
		  } 
		});
}

//Ajax translation
function ajax_translate(original,translation,source,segment_id) {
	var token = jQuery("#tr_" + segment_id).attr('token');
	var query = 'token=' +  token +
    '&translation=' + translation +
    '&lang=' + transposh_target_lang +
    '&source=' + source +
    '&translation_posted=1';
	
    //jQuery("span:contains("+translation+")").css("text-decoration", "underline");
    jQuery.ajax({  
        type: "POST",
        url: transposh_post_url,
        data: query,  
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

                    //rewrite onclick function - in case of re-edit
                    jQuery("#tr_img_" + img_segment_id).click(function () {
                    	translate_dialog(original, translation, img_segment_id);
                    });

                    // handle image
                    if(jQuery.trim(translation).length == 0) {
                        //switch to the edit img
                        img = img.replace(/translate_fix.png/, "translate.png");
                        img = img.replace(/translate_auto.png/, "translate.png");
                    } else {
                    	if (source == 1) {
                    		//switch to the auto img
                    		img = img.replace(/translate.png/, "translate_auto.png");                		
                    	} else {
                    		//switch to the fix img
                    		img = img.replace(/translate.png/, "translate_fix.png");
                    		img = img.replace(/translate_auto.png/, "translate_fix.png");
                    	}
                    }
                    //rewrite image
                    jQuery("#tr_img_" + img_segment_id).attr('src', img);
        			
        		});
                
            //close dialog
            cClick();
    	},
                
        error: function(req) {
    		if (source == 0) {
    			alert("Error !!! failed to translate.\n\nServer's message: " + req.statusText);
    		}
    	}
    });

}

//Open translation dialog 
function translate_dialog(original, trans, segment_id)
{
caption='Edit Translation';
//alert (this.id);
var dialog = ''+
    ('<form id="tr_form" name="transposh_edit_form" method="post" action="' + transposh_post_url + '"><div>') +
     '<p dir="ltr">Original text<br \/><textarea id="tr_original_unescaped" cols="60" rows="3" readonly="readyonly">' +
       original + '</textarea> <\/p>' +
    '<p>Translate to<br \/><input type="text" id="tr_translation" name="translation" size="80" value="'+ trans +
    '"' + 'onfocus="OLmEdit=1;" onblur="OLmEdit=0;"<\/p>' +
    '<input type="hidden" name="translation_posted" value= "1">' +
    '<p><input onclick="getgt()" type="button" value="Get Suggestion!"/>&nbsp;<input type="submit" value="Translate"/><\/p>' +
    ('<\/div><\/form>');

	display_dialog(caption, dialog);

	// attach handler to form's submit event 
	jQuery('#tr_form').submit(function() { 
        var translation = jQuery('#tr_translation').val();
                        
        ajax_translate(original,translation,0,segment_id);
        
        // return false to prevent normal browser submit and page navigation 
        return false;
        
    });

}
//function for auto translation

function do_auto_translate() {
	jQuery(".tr_u").each(function (i) {
		var translated_id = jQuery(this).attr('id');
		google.language.translate(jQuery(this).text(), "", transposh_target_lang, function(result) {
			if (!result.error) {
				var segment_id = translated_id.substr(translated_id.lastIndexOf('_')+1);
		        ajax_translate(jQuery("#"+translated_id).text(),result.translation,1,segment_id);
		        jQuery("#"+translated_id).addClass("tr_t").removeClass("tr_u");
			} 
		});
	});
}

//to run at start
jQuery.noConflict();
var transposh_post_url,transposh_target_lang; 
jQuery("script[src*='transposh']").each(function (i) {
	transposh_post_url = this.src.match('post_url=(.*?)&')[1];
	transposh_target_lang = this.src.match('lang=(.*?)&')[1];
});
google.load("language", "1");
jQuery(document).ready(
	function() {
		do_auto_translate();
	}
);

