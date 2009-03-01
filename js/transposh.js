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
 
//Open translation dialog 
function translate_dialog(original, trans, lang, post_url, segment_id)
{
caption='Edit Translation';

var dialog = ''+
    ('<form id="tr_form" name="transposh_edit_form" method="post" action="' + post_url + '"><div>') +
     '<p dir="ltr">Original text<br \/><textarea id="tr_original_unescaped" cols="60" rows="3" readonly="readyonly">' +
       original + '</textarea> <\/p>' +
    '<p>Translate to<br \/><input type="text" id="tr_translation" name="translation" size="80" value="'+ trans +
    '"' + 'onfocus="OLmEdit=1;" onblur="OLmEdit=0;"<\/p>' +
    '<input type="hidden" id="tr_original" name="original" value="' + escape(original) +'">' +
    '<input type="hidden" id="tr_lang" name="lang" value="'+lang+'">' +
    '<input type="hidden" name="translation_posted" value= "1">' +
    '<p><input type="submit" value="Translate"><\/p>' +
    ('<\/div><\/form>');

display_dialog(caption, dialog);

// attach handler to form's submit event 
$('#tr_form').submit(function() { 
        var translation = $('#tr_translation').val();
        var query = 'original=' +  escape(original) +
                    '&translation=' + translation +
                    '&lang=' + $('#tr_lang').val() +
                    '&translation_posted=1';
        
                
        $.ajax({  
            type: "POST",
            url: post_url,
            data: query,  
            success: function(req) {
                    //rewrite onclick function - in case of re-edit
                    $("#tr_img_" + segment_id).click(function () {
                            translate_dialog(original, translation, lang, post_url, segment_id);
                        });

                    //current img 
                    var img = $("#tr_img_" + segment_id).attr('src');
                    var text_rewrite = translation;
                    
                    if(jQuery.trim(translation).length == 0) {
                        //reset to the original content - the not escaped version
                        text_rewrite = original;

                        //switch to the edit img
                        img = img.replace(/translate_fix.png/, "translate.png");
                    }
                    else {
                        //switch to the fix img
                        img = img.replace(/translate.png/, "translate_fix.png");
                    }
                    
                    
                    //rewrite text
                    $("#tr_" + segment_id).html(text_rewrite);

                    //rewrite image
                    $("#tr_img_" + segment_id).attr('src', img);

                    //close dialog
                    cClick();
                    },
                    
            error: function(req) {
                    alert("Error !!! failed to translate.\n\nServer's message: " + req.statusText);
                   }
                
                    
        });
        
        // return false to prevent normal browser submit and page navigation 
        return false;
        
    });
}
