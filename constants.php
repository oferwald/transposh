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
$languages = array("en" => "English,us",
                   "ar" => "العربية,sa",
                   "bg" => "Български,bg",
                   "zh" => "汉字,cn",
                   "hr" => "Hrvatski,hr",
                   "cs" => "čeština,cz",
                   "nl" => "Nederlands,nl",
                   "fi" => "Suomi,fi",
                   "fr" => "Français,fr",
                   "de" => "Deutsch,de",
                   "el" => "Ελληνικά,gr",
                   "he" => "עברית,il",
                   "hu" => "magyar,hu",
                   "it" => "Italiano,it",
                   "ko" => "우리말,kr",
                   "pl" => "Polski,pl",
                   "pt" => "Português,pt",
                   "ro" => "Română,ro",
                   "ru" => "Русский,ru",
                   "es" => "Español,es",
                   "sv" => "svenska,se",
                   "th" => "ภาษาไทย,th",
                   "tr" => "Türkçe,tr");


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

//Option defining the default language
define("DEFAULT_LANG", "transposh_default_language");

//Define segment id prefix, will be included in span tag.
define("SPAN_PREFIX", "tr_");

//Define segment id prefix, will be included in img tag.
define("IMG_PREFIX", "tr_img_");

//Option defining transposh widget appearance
define("WIDGET_TRANSPOSH", "widget_transposh");

?>
