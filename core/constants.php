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
//the array directs from language code to - Native name, flag
$languages = array("en" => "English,us",
					"sq" => "Shqip,al",
					"ar" => "العربية,sa",
					"bg" => "Български,bg",
					"ca" => "Català,catalonia",
					"zh" => "中文(简体),cn",
					"zh-tw" => "中文(漢字),tw",
					"hr" => "Hrvatski,hr",
					"cs" => "čeština,cz",
					"da" => "dansk,dk",
					"nl" => "Nederlands,nl",
					"et" => "Eesti keel,ee",
					"fi" => "Suomi,fi",
					"fr" => "Français,fr",
					"gl" => "Galego,galicia",
					"de" => "Deutsch,de",
					"el" => "Ελληνικά,gr",
					"he" => "עברית,il",
					"hi" => "हिन्दी; हिंदी,in",
					"hu" => "magyar,hu",
					"id" => "Bahasa Indonesia,id",
					"it" => "Italiano,it",
					"ja" => "日本語 (にほんご／にっぽんご),jp",
					"ko" => "우리말,kr",
					"lv" => "latviešu valoda,lv",
					"lt" => "lietuvių kalba,lt",
					"mt" => "Malti,mt",
					"no" => "Norsk,no",
					"pl" => "Polski,pl",
					"pt" => "Português,pt",
					"ro" => "Română,ro",
					"ru" => "Русский,ru",
					"sr" => "српски језик,rs",
					"sk" => "slovenčina,sk",
					"sl" => "slovenščina,sl",
					"es" => "Español,es",
					"sv" => "svenska,se",
					"tl" => "Tagalog,ph",
					"th" => "ภาษาไทย,th",
					"tr" => "Türkçe,tr",
					"uk" => "Українська,ua",
					"vi" => "Tiếng Việt,vn");

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

//Option defining the default language
define("DEFAULT_LANG", "transposh_default_language");

//Option defining transposh widget appearance
define("WIDGET_TRANSPOSH", "transposh_widget");

//Define segment id prefix, will be included in span tag. also used as class identifier
define("SPAN_PREFIX", "tr_");

//Define segment id prefix, will be included in img tag.
define("IMG_PREFIX", "tr_img_");

?>