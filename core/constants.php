<?php
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

//Language indicator in URL. i.e. lang=en
define("LANG_PARAM", "lang");

//Edit mode indicator in URL. i.e. lang=en&edit=true
define("EDIT_PARAM", "edit");

//Enable apc usage
define("ENABLE_APC", TRUE);

//Class marking a section not be translated.
define("NO_TRANSLATE_CLASS", "no_translate");

//Supported languages, new languages can be added here
//the array directs from language code to - Native name, flag, auto-translatable
$languages = array(
    "en" => "English,us,1",
    "sq" => "Shqip,al,1",
    "ar" => "العربية,sa,1",
    "bg" => "Български,bg,1",
    "ca" => "Català,catalonia,1",
    "zh" => "中文(简体),cn,1",
    "zh-tw" => "中文(漢字),tw,1",
    "hr" => "Hrvatski,hr,1",
    "cs" => "čeština,cz,1",
    "da" => "dansk,dk,1",
    "nl" => "Nederlands,nl,1",
    "et" => "Eesti keel,ee,1",
    "fi" => "Suomi,fi,1",
    "fr" => "Français,fr,1",
    "gl" => "Galego,galicia,1",
    "de" => "Deutsch,de,1",
    "el" => "Ελληνικά,gr,1",
    "he" => "עברית,il,1",
    "hi" => "हिन्दी; हिंदी,in,1",
    "hu" => "magyar,hu,1",
    "id" => "Bahasa Indonesia,id,1",
    "it" => "Italiano,it,1",
    "is" => "íslenska,is,0",
    "ja" => "日本語,jp,1",
    "ko" => "우리말,kr,1",
    "lv" => "latviešu valoda,lv,1",
    "lt" => "lietuvių kalba,lt,1",
    "mt" => "Malti,mt,1",
    "no" => "Norsk,no,1",
    "pl" => "Polski,pl,1",
    "pt" => "Português,pt,1",
    "ro" => "Română,ro,1",
    "ru" => "Русский,ru,1",
    "sr" => "српски језик,rs,1",
    "sk" => "slovenčina,sk,1",
    "sl" => "slovenščina,sl,1",
    "es" => "Español,es,1",
    "sv" => "svenska,se,1",
    "tl" => "Tagalog,ph,1",
    "th" => "ภาษาไทย,th,1",
    "tr" => "Türkçe,tr,1",
    "uk" => "Українська,ua,1",
    "vi" => "Tiếng Việt,vn,1");

//Language which are read from right to left (rtl)
$rtl_languages =  array("ar", "he");

//Define the new capability that will be assigned to roles - translator
define("TRANLSLATOR", 'translator');

//Option defining whether anonymous translation is allowed.
define("ANONYMOUS_TRANSLATION", "transposh_allow_anonymous_translation");

//Option defining the list of currentlly viewable languages
define("VIEWABLE_LANGS", "transposh_viewable_languages");

//Option defining the list of currentlly editable languages
define("EDITABLE_LANGS", "transposh_editable_languages");

//Option to enable/disable rewrite of permalinks
define("ENABLE_AUTO_TRANSLATE", "transposh_enable_autotranslate");

//Option to enable/disable rewrite of permalinks
define("ENABLE_PERMALINKS_REWRITE", "transposh_enable_permalinks");

//Option to enable/disable default language translation
define("ENABLE_DEFAULT_TRANSLATE", "transposh_enable_default_translate");

//Option to enable/disable footer scripts (2.8 and up)
define("ENABLE_FOOTER_SCRIPTS", "transposh_enable_footer_scripts");

//Option defining the default language
define("DEFAULT_LANG", "transposh_default_language");

//Option defining transposh widget appearance
define("WIDGET_TRANSPOSH", "transposh_widget");

//Define segment id prefix, will be included in span tag. also used as class identifier
define("SPAN_PREFIX", "tr_");

//The name of our admin page
define('TRANSPOSH_ADMIN_PAGE_NAME', 'transposh');

?>