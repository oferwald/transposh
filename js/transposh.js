
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
function translate_dialog(original, trans, lang, post_url)
{
caption='Edit Translation';

//TODO accept the action url as a parameter
var dialog = ''+
    ('<form name="transposh_edit_form" method="post" action="' + post_url + '"><div>') +
     '<p dir="ltr">Original text<br \/><textarea cols="60" rows="3" readonly="readyonly">' +
       original + '</textarea> <\/p>' +
    '<p>Translate to<br \/><input type="text" name="translation" size="80" value="'+ trans + '"' + 'onfocus="OLmEdit=1;" onblur="OLmEdit=0;"<\/p>' +
    '<input type="hidden" name="original" value="'+escape(original)+'">' +
    '<input type="hidden" name="lang" value="'+lang+'">' +
    '<input type="hidden" name="translation_posted" value= "1">' +
    '<p><input type="submit" value="Translate"><\/p>' +
    ('<\/div><\/form>');

display_dialog(caption, dialog);

}
