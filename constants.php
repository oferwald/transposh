<?php

//Language indicator in URL. i.e. lang=en
define("LANG_PARAM", "lang");

//Edit mode indicator in URL. i.e. lang=en&edit=true
define("EDIT_PARAM", "edit");

//Enable apc usage
define("ENABLE_APC", TRUE);


//Class marking a section not be translated.
define("NO_TRANSLATE_CLASS", "no_translate");

//Supported languages
$languages = array("en" => "English",
                   "ar" => "العربية",
                   "bg" => "Български",
                   "zh" => "汉字",
                   "hr" => "Hrvatski",
                   "cs" => "čeština",
                   "nl" => "Nederlands",
                   "fi" => "Suomi",
                   "fr" => "Français",
                   "de" => "Deutsch",
                   "el" => "Ελληνικά",
                   "he" => "עברית",
                   "hu" => "magyar",
                   "it" => "Italiano",
                   "ko" => "우리말",
                   "pl" => "język polski",
                   "pt" => "Português",
                   "ro" => "Română",
                   "ru" => "Русский",
                   "es" => "Español",
                   "sv" => "svenska",
                   "th" => "ภาษาไทย",
                   "tr" => "Türkçe");


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

?>