<?php

/*
 * Transposh v%VERSION%
 * http://transposh.org/
 *
 * Copyright %YEAR%, Team Transposh
 * Licensed under the GPL Version 2 or higher.
 * http://transposh.org/license
 *
 * Date: %DATE%
 */

//Language indicator in URL. i.e. lang=en
define('LANG_PARAM', 'lang');

//Edit mode indicator in URL. i.e. lang=en&edit=true
define('EDIT_PARAM', 'tpedit');

//Enable in memory cache usage, APC, xcache
define('TP_ENABLE_CACHE', TRUE);
//What is the cache items TTL
define('TP_CACHE_TTL', 3600 * 24);
//Constants for memcached
define('TP_MEMCACHED_SRV', '127.0.0.1');
define('TP_MEMCACHED_PORT', 11211);

//Class marking a section not be translated.
define('NO_TRANSLATE_CLASS', 'no_translate');
define('NO_TRANSLATE_CLASS_GOOGLE', 'notranslate');
define('ONLY_THISLANGUAGE_CLASS', 'only_thislanguage');

//Get text breakers
define('TP_GTXT_BRK', chr(1)); // Gettext breaker
define('TP_GTXT_IBRK', chr(2)); // Gettext inner breaker (around %s)
define('TP_GTXT_BRK_CLOSER', chr(3)); // Gettext breaker closer
define('TP_GTXT_IBRK_CLOSER', chr(4)); // Gettext inner breaker closer
//External services
define('TRANSPOSH_BACKUP_SERVICE_URL', 'http://svc.transposh.org/backup');
define('TRANSPOSH_RESTORE_SERVICE_URL', 'http://svc.transposh.org/restore');
define('TRANSPOSH_UPDATE_SERVICE_URL', 'http://svc.transposh.org/update-check');

/**
 * Holds our arrays staticly to reduce chance of namespace collision
 */
class transposh_consts {

//Supported languages, new languages can be added here
//the array directs from language code to - English Name, Native name, flag
    public static $languages = array(
        'en' => 'English,English,us,en_US',
        'af' => 'Afrikaans,Afrikaans,za,',
        'sq' => 'Albanian,Shqip,al,',
        'ar' => 'Arabic,العربية,sa,',
        'hy' => 'Armenian,Հայերեն,am,',
        'az' => 'Azerbaijani,azərbaycan dili,az,',
        'eu' => 'Basque,Euskara,es-ba,',
        'ba' => 'Bashkir,башҡорт теле,ru-ba',
        'be' => 'Belarusian,Беларуская,by,',
        'bn' => 'Bengali,বাংলা,bd,bn_BD',
        'bs' => 'Bosnian,bosanski jezik,ba,bs_BA',
        'bg' => 'Bulgarian,Български,bg,bg_BG',
        'ca' => 'Catalan,Català,es-ca,',
        'yue' => 'Cantonese,粤语,hk,zh_HK',
        'ceb' => 'Cebuano,Binisaya,ph,',
        'ny' => 'Chichewa,Chinyanja,mw',
        'zh' => 'Chinese (Simplified),中文(简体),cn,zh_CN',
        'zh-tw' => 'Chinese (Traditional),中文(漢字),tw,zh_TW',
        'hr' => 'Croatian,Hrvatski,hr,',
        'cs' => 'Czech,Čeština,cz,cs_CZ',
        'da' => 'Danish,Dansk,dk,da_DK',
        'nl' => 'Dutch,Nederlands,nl,nl_NL',
        'eo' => 'Esperanto,Esperanto,esperanto,',
        'et' => 'Estonian,Eesti keel,ee,',
        'fi' => 'Finnish,Suomi,fi,',
        'fr' => 'French,Français,fr,fr_FR',
        'gl' => 'Galician,Galego,es-ga,gl_ES',
        'ka' => 'Georgian,ქართული,ge,ka_GE',
        'de' => 'German,Deutsch,de,de_DE',
        'el' => 'Greek,Ελληνικά,gr,',
        'gu' => 'Gujarati,ગુજરાતી,in,',
        'ht' => 'Haitian,Kreyòl ayisyen,ht,',
        'ha' => 'Hausa,Harshen Hausa,ng,',
        'hmn' => 'Hmong,Hmoob,la,',
        'mw' => 'Hmong Daw,Hmoob Daw,la,',
        'he' => 'Hebrew,עברית,il,he_IL',
        'hi' => 'Hindi,हिन्दी; हिंदी,in,hi_IN',
        'hu' => 'Hungarian,Magyar,hu,hu_HU',
        'is' => 'Icelandic,Íslenska,is,',
        'ig' => 'Igbo,Asụsụ Igbo,ng,',
        'id' => 'Indonesian,Bahasa Indonesia,id,id_ID',
        'ga' => 'Irish,Gaeilge,ie,',
        'it' => 'Italian,Italiano,it,it_IT',
        'ja' => 'Japanese,日本語,jp,',
        'jw' => 'Javanese,basa Jawa,id,jv_ID',
        'kn' => 'Kannada,ಕನ್ನಡ,in,',
        'kk' => 'Kazakh,Қазақ тілі,kz',
        'km' => 'Khmer,ភាសាខ្មែរ,kh,',
        'ko' => 'Korean,한국어,kr,ko_KR',
        'ky' => 'Kirghiz,кыргыз тили,kg,ky_KY',
        'lo' => 'Lao,ພາສາລາວ,la,',
        'la' => 'Latin,Latīna,va,',
        'lv' => 'Latvian,Latviešu valoda,lv,',
        'lt' => 'Lithuanian,Lietuvių kalba,lt,',
        'mk' => 'Macedonian,македонски јазик,mk,mk_MK',
        'mg' => 'Malagasy,Malagasy fiteny,mg',
        'ms' => 'Malay,Bahasa Melayu,my,ms_MY',
        'ml' => 'Malayalam,മലയാളം,in',
        'mt' => 'Maltese,Malti,mt,',
        'mi' => 'Maori,Te Reo Māori,nz,',
        'mr' => 'Marathi,मराठी,in,',
        'mn' => 'Mongolian,Монгол,mn,',
        'my' => 'Burmese,မြန်မာစာ,mm,my_MM', // PROBLEM - OLD flag
        'ne' => 'Nepali,नेपाली,np,ne_NP',
        'no' => 'Norwegian,Norsk,no,nb_NO',
        'fa' => 'Persian,پارسی,ir,fa_IR',
        'pl' => 'Polish,Polski,pl,pl_PL',
        'pt' => 'Portuguese,Português,pt,pt_PT',
        'pa' => 'Punjabi,ਪੰਜਾਬੀ,pk,pa_IN',
        'ro' => 'Romanian,Română,ro,ro_RO',
        'ru' => 'Russian,Русский,ru,ru_RU',
        'sr' => 'Serbian,Cрпски језик,rs,sr_RS',
        'st' => 'Sesotho,Sesotho,ls', // PROBLEM - OLD flag
        'si' => 'Sinhala,සිංහල,lk,si_LK',
        'sk' => 'Slovak,Slovenčina,sk,sk_SK',
        'sl' => 'Slovene,Slovenščina,si,sl_SI', //slovenian
        'so' => 'Somali,Af-Soomaali,so,',
        'es' => 'Spanish,Español,es,es_ES',
        'su' => 'Sundanese,Basa Sunda,id',
        'sw' => 'Swahili,Kiswahili,tz,',
        'sv' => 'Swedish,Svenska,se,sv_SE',
        'tl' => 'Tagalog,Tagalog,ph,', // fhilipino
        'tg' => 'Tajik,Тоҷикӣ,tj',
        'ta' => 'Tamil,தமிழ்,in,ta_LK', // apparently more in India
        'tt' => 'Tatar,татарча,ru-ta',
        'te' => 'Telugu,తెలుగు,in,',
        'th' => 'Thai,ภาษาไทย,th,',
        'tr' => 'Turkish,Türkçe,tr,tr_TR',
        'uk' => 'Ukrainian,Українська,ua,',
        'ur' => 'Urdu,اردو,pk,',
        'uz' => 'Uzbek,Oʻzbek tili,uz,uz_UZ',
        'vi' => 'Vietnamese,Tiếng Việt,vn,',
        'cy' => 'Welsh,Cymraeg,gb-wa,',
        'yi' => 'Yiddish,ייִדיש,europeanunion,',
        'yo' => 'Yoruba,èdè Yorùbá,ng,',
        'zu' => 'Zulu,isiZulu,za,'
    );
    // new var to hold translation engines information
    public static $engines = array(
        'a' => array(
            'name' => 'Apertium',
            'icon' => 'apertiumicon.png',
            'langs' => array('eo'),
        ),
        'b' => array(
            'name' => 'Bing',
            'icon' => 'bingicon.png',
            // (got this using Microsoft.Translator.GetLanguages() - fixed to match our codes)
            // @updated 2012-Feb-14 (mww)
            // @updated 2013-Feb-21 (ms, ur)
            // @updated 2014-Feb-21 (cy)
            // @updated 2015-Apr-19 (bs, hr, sr)
            // @updated 2015-Oct-23 (sw)
            'langs' => array('en', 'ar', 'bg', 'ca', 'zh', 'zh-tw', 'cs', 'da', 'nl', 'et', 'fi', 'fr', 'de', 'el', 'ht', 'he', 'hi', 'hu', 'id', 'it', 'ja', 'ko', 'lv',
                'lt', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'es', 'sv', 'th', 'tr', 'uk', 'vi', 'mw', 'ms', 'ur', 'cy', 'bs', 'hr', 'sr', 'sw'),
            'langconv' => array('zh' => 'zh-chs', 'zh-tw' => 'zh-cht', 'mw' => 'mww')
        ),
        'g' => array(
            'name' => 'Google',
            'icon' => 'googleicon.png',
            // (got using - var langs =''; jQuery.each(google.language.Languages,function(){if (google.language.isTranslatable(this)) {langs += this +'|'}}); console.log(langs); - fixed for our codes)
            // @updated 2010-Oct-01 (hy,az,eu,ka,la,ur)
            // @updated 2011-Nov-04
            // @updated 2012-Feb-24 (eo)
            // @updated 2012-Sep-17 (la)
            // @updated 2013-Apr-19 (km)
            // @updated 2013-May-09 (bs,ceb,hmn,jw,mr)
            // @updated 2013-Dec-24 (ha,ig,mi,mn,ni,pa,so,yo,zu)
            // @updated 2014-Dec-15 (kk,mg,ml,my,ny,si,st,su,tg,uz)
            'langs' => array('en', 'af', 'sq', 'ar', 'be', 'bg', 'ca', 'zh', 'zh-tw', 'hr', 'cs', 'da', 'nl', 'et', 'fi', 'fr', 'gl', 'de', 'el', 'ht', 'he', 'hi', 'hu',
                'id', 'it', 'is', 'ga', 'ja', 'ko', 'lo', 'lv', 'lt', 'mk', 'ms', 'mt', 'no', 'fa', 'pl', 'pt', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'sw', 'sv', 'tl', 'th', 'tr', 'uk', 'vi',
                'cy', 'yi', 'hy', 'az', 'eu', 'ka', 'la', 'ur', 'ta', 'te', 'kn', 'bn', 'gu', 'eo', 'km', 'bs', 'ceb', 'hmn', 'jw', 'mr', 'ha', 'ig', 'mi', 'mn', 'ne', 'pa', 'so', 'yo', 'zu',
                'kk', 'mg', 'ml', 'my', 'ny', 'si', 'st', 'su', 'tg', 'uz'),
        ),
        'u' => array(
            'name' => 'Baidu',
            'icon' => 'baiduicon.png',
            // @updated 2015-Nov-03
            'langs' => array('ar', 'et', 'bg', 'pl', 'da', 'de', 'ru', 'fr', 'fi', 'ko', 'nl', 'cs', 'ro', 'pt', 'jp', 'sv', 'sl', 'th', 'es', 'el', 'hu', 'zh', 'en', 'it', 'yue', 'zh-tw'), //wyw - old chinese
            'langconv' => array('ar' => 'ara', 'et' => 'est', 'bg' => 'bul', 'da' => 'dan', 'fr' => 'fra', 'fi' => 'fin', 'ko' => 'kor', 'ro' => 'rom', 'sv' => 'swe', 'sl' => 'slo', 'es' => 'spa', 'zh-tw' => 'cht')
        ),
        //<li><a href="###" class="data-lang" value="wyw">文言文</a></li>
        'y' => array(
            'name' => 'Yandex',
            'icon' => 'yandexicon.png',
            // @updated 2015-Aug-12 initial list
            // @updated 2015-Oct-25 (ba)
            'langs' => array('sq', 'en', 'ar', 'hy', 'az', 'af', 'eu', 'be', 'bg', 'bs', 'cy', 'vi', 'hu', 'ht', 'gl', 'nl', 'el', 'ka',
                'da', 'he', 'id', 'ga', 'it', 'is', 'es', 'ca', 'kk', 'ky', 'zh', 'ko', 'la', 'lv', 'lt', 'mg', 'ms', 'mt', 'mk', 'mn', 'de', 'no', 'fa', 'pl', 'pt',
                'ro', 'ru', 'sr', 'sk', 'sl', 'sw', 'tg', 'th', 'tl', 'tt', 'tr', 'uz', 'uk', 'fi', 'fr', 'hr', 'cs', 'sv', 'et', 'ja', 'ba'),
        ),
    );

    public static function get_language_name($lang) {
        list ($langname) = explode(",", transposh_consts::$languages[$lang]);
        return $langname;
    }

    public static function get_language_orig_name($lang) {
        list (, $langorigname) = explode(",", transposh_consts::$languages[$lang]);
        return $langorigname;
    }

    public static function get_language_flag($lang) {
        list (,, $flag) = explode(",", transposh_consts::$languages[$lang]);
        return $flag;
    }

    public static function get_language_locale($lang) {
        @list (,,, $locale) = explode(",", transposh_consts::$languages[$lang]);
        if ($locale) {
            return $locale;
        }
        return $lang;
    }

    // Language which are read from right to left (rtl)
    public static $rtl_languages = array('ar', 'he', 'fa', 'ur', 'yi');
    // todo - more languages in OHT
    //Chinese Cantonese	zh-cn-yue -- check
    //Chinese Mandarin-Simplified	zh-cn-cmn-s
    //Chinese Mandarin-Traditional	zh-cn-cmn-t
    //Dari	fa-af
    //Kazakh	kk-kz
    //Pashto	ps
    //Uzbek	uz-uz
    public static $oht_languages = array('en', 'sq', 'ar', 'bn', 'bg', 'ca', 'zh', 'zh-tw', 'hr', 'cs', 'da', 'nl', 'et', 'fa', 'fi', 'fr', 'ka', 'de', 'el', 'he', 'hi', 'hu', 'id',
        'it', 'ja', 'ko', 'lo', 'lv', 'lt', 'mk', 'ms', 'no', 'pl', 'pt', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'sv', 'tl', 'ta', 'th', 'tr', 'uk', 'ur', 'vi', 'km', 'bs');
    // Array for holding po domains we have problems with
    public static $ignored_po_domains = array('MailPress');
    // Array holding list of jQueryUI themes
    public static $jqueryui_themes = array('black-tie', 'blitzer', 'cupertino', 'dark-hive', 'dot-luv', 'eggplant', 'excite-bike', 'flick',
        'hot-sneaks', 'humanity', 'le-frog', 'mint-choc', 'overcast', 'pepper-grinder', 'redmond', 'smoothness', 'south-street',
        'start', 'sunny', 'swanky-purse', 'trontastic', 'ui-darkness', 'ui-lightness', 'vader');

}

//Define the new capability that will be assigned to roles - translator
define('TRANSLATOR', 'translator');

//Define for transposh plugin version
define('TRANSPOSH_PLUGIN_VER', '%VERSION%');

//Current jQuery UI
define('JQUERYUI_VER', '1.10.4');

//Define segment id prefix, will be included in span tag. also used as class identifier
define('SPAN_PREFIX', 'tr_');

//Our text domain
define('TRANSPOSH_TEXT_DOMAIN', 'transposh');

//0.3.5 - Storing all options in this config option
define('TRANSPOSH_OPTIONS', 'transposh_options');

//0.8.4 - Storing oht project
define('TRANSPOSH_OPTIONS_OHT', 'transposh_options_oht');
define('TRANSPOSH_OPTIONS_OHT_PROJECTS', 'transposh_options_oht_projects');
define('TRANSPOSH_OHT_DELAY', 600);

//0.9.6 - Making sure Google works
define('TRANSPOSH_OPTIONS_GOOGLEPROXY', 'transposh_options_googleproxy');
define('TRANSPOSH_GOOGLEPROXY_DELAY', 86400); // give it a day
//0.5.6 new definitions
//Defintions for directories used in the plugin
define('TRANSPOSH_DIR_CSS', 'css');
define('TRANSPOSH_DIR_IMG', 'img');
define('TRANSPOSH_DIR_JS', 'js');
define('TRANSPOSH_DIR_WIDGETS', 'widgets');

/* Full language list according to ISO
  ISO 639-1	Language name	Native name
  aa	Afar	Afaraf
  ab	Abkhazian	Аҧсуа
  ae	Avestan	avesta
  af	Afrikaans	Afrikaans
  ak	Akan	Akan
  am	Amharic	አማርኛ
  an	Aragonese	Aragonés
  ar	Arabic	العربية
  as	Assamese	অসমীয়া
  av	Avaric	авар мацӀ, магӀарул мацӀ
  ay	Aymara	aymar aru
  az	Azerbaijani	azərbaycan dili
  ba	Bashkir	башҡорт теле
  be	Belarusian	Беларуская
  bg	Bulgarian	български език
  bh	Bihari	भोजपुरी
  bi	Bislama	Bislama
  bm	Bambara	bamanankan
  bn	Bengali	বাংলা
  bo	Tibetan	བོད་ཡིག
  br	Breton	brezhoneg
  bs	Bosnian	bosanski jezik
  ca	Catalan, Valencian	Català
  ce	Chechen	нохчийн мотт
  ch	Chamorro	Chamoru
  co	Corsican	corsu, lingua corsa
  cr	Cree	ᓀᐦᐃᔭᐍᐏᐣ
  cs	Czech	česky, čeština
  cu	Church Slavic, Old Slavonic, Church Slavonic, Old Bulgarian, Old Church Slavonic	ѩзыкъ словѣньскъ
  cv	Chuvash	чӑваш чӗлхи
  cy	Welsh	Cymraeg
  da	Danish	dansk
  de	German	Deutsch
  dv	Divehi, Dhivehi, Maldivian	ދިވެހި
  dz	Dzongkha	རྫོང་ཁ
  ee	Ewe	Eʋegbe
  el	Modern Greek	Ελληνικά
  en	English	English
  eo	Esperanto	Esperanto
  es	Spanish, Castilian	español, castellano
  et	Estonian	eesti, eesti keel
  eu	Basque	euskara, euskera
  fa	Persian	فارسی
  ff	Fulah	Fulfulde, Pulaar, Pular
  fi	Finnish	suomi, suomen kieli
  fj	Fijian	vosa Vakaviti
  fo	Faroese	føroyskt
  fr	French	français, langue française
  fy	Western Frisian	Frysk
  ga	Irish	Gaeilge
  gd	Gaelic, Scottish Gaelic	Gàidhlig
  gl	Galician	Galego
  gn	Guaraní	Avañe'ẽ
  gu	Gujarati	ગુજરાતી
  gv	Manx	Gaelg, Gailck
  ha	Hausa	Hausa, هَوُسَ
  he	Modern Hebrew	עברית
  hi	Hindi	हिन्दी, हिंदी
  ho	Hiri Motu	Hiri Motu
  hr	Croatian	hrvatski
  ht	Haitian, Haitian Creole	Kreyòl ayisyen
  hu	Hungarian	Magyar
  hy	Armenian	Հայերեն
  hz	Herero	Otjiherero
  ia	Interlingua (International Auxiliary Language Association)	Interlingua
  id	Indonesian	Bahasa Indonesia
  ie	Interlingue, Occidental	Interlingue
  ig	Igbo	Igbo
  ii	Sichuan Yi, Nuosu	ꆇꉙ
  ik	Inupiaq	Iñupiaq, Iñupiatun
  io	Ido	Ido
  is	Icelandic	Íslenska
  it	Italian	Italiano
  iu	Inuktitut	ᐃᓄᒃᑎᑐᑦ
  ja	Japanese	日本語 (にほんご／にっぽんご)
  jv	Javanese	basa Jawa
  ka	Georgian	ქართული
  kg	Kongo	KiKongo
  ki	Kikuyu, Gikuyu	Gĩkũyũ
  kj	Kwanyama, Kuanyama	Kuanyama
  kk	Kazakh	Қазақ тілі
  kl	Kalaallisut, Greenlandic	kalaallisut, kalaallit oqaasii
  km	Central Khmer	ភាសាខ្មែរ
  kn	Kannada	ಕನ್ನಡ
  ko	Korean	한국어 (韓國語), 조선말 (朝鮮語)
  kr	Kanuri	Kanuri
  ks	Kashmiri	कश्मीरी, كشميري‎
  ku	Kurdish	Kurdî, كوردی‎
  kv	Komi	коми кыв
  kw	Cornish	Kernewek
  ky	Kirghiz, Kyrgyz	кыргыз тили
  la	Latin	latine, lingua latina
  lb	Luxembourgish, Letzeburgesch	Lëtzebuergesch
  lg	Ganda	Luganda
  li	Limburgish, Limburgan, Limburger	Limburgs
  ln	Lingala	Lingála
  lo	Lao	ພາສາລາວ
  lt	Lithuanian	lietuvių kalba
  lu	Luba-Katanga
  lv	Latvian	latviešu valoda
  mg	Malagasy	Malagasy fiteny
  mh	Marshallese	Kajin M̧ajeļ
  mi	Māori	te reo Māori
  mk	Macedonian	македонски јазик
  ml	Malayalam	മലയാളം
  mn	Mongolian	Монгол
  mr	Marathi	मराठी
  ms	Malay	bahasa Melayu, بهاس ملايو‎
  mt	Maltese	Malti
  my	Burmese	ဗမာစာ
  na	Nauru	Ekakairũ Naoero
  nb	Norwegian Bokmål	Norsk bokmål
  nd	North Ndebele	isiNdebele
  ne	Nepali	नेपाली
  ng	Ndonga	Owambo
  nl	Dutch, Flemish	Nederlands, Vlaams
  nn	Norwegian Nynorsk	Norsk nynorsk
  no	Norwegian	Norsk
  nr	South Ndebele	isiNdebele
  nv	Navajo, Navaho	Diné bizaad, Dinékʼehǰí
  ny	Chichewa, Chewa, Nyanja	chiCheŵa, chinyanja
  oc	Occitan (after 1500)	Occitan
  oj	Ojibwa	ᐊᓂᔑᓈᐯᒧᐎᓐ
  om	Oromo	Afaan Oromoo
  or	Oriya	ଓଡ଼ିଆ
  os	Ossetian, Ossetic	Ирон æвзаг
  pa	Panjabi, Punjabi	ਪੰਜਾਬੀ, پنجابی‎
  pi	Pāli	पाऴि
  pl	Polish	polski
  ps	Pashto, Pushto	پښتو
  pt	Portuguese	Português
  qu	Quechua	Runa Simi, Kichwa
  rm	Romansh	rumantsch grischun
  rn	Rundi	kiRundi
  ro	Romanian, Moldavian, Moldovan	română
  ru	Russian	Русский язык
  rw	Kinyarwanda	Ikinyarwanda
  sa	Sanskrit	संस्कृतम्
  sc	Sardinian	sardu
  sd	Sindhi	सिन्धी, سنڌي، سندھی‎
  se	Northern Sami	Davvisámegiella
  sg	Sango	yângâ tî sängö
  si	Sinhala, Sinhalese	සිංහල
  sk	Slovak	slovenčina
  sl	Slovene	slovenščina
  sm	Samoan	gagana fa'a Samoa
  sn	Shona	chiShona
  so	Somali	Soomaaliga, af Soomaali
  sq	Albanian	Shqip
  sr	Serbian	српски језик
  ss	Swati	SiSwati
  st	Southern Sotho	Sesotho
  su	Sundanese	Basa Sunda
  sv	Swedish	svenska
  sw	Swahili	Kiswahili
  ta	Tamil	தமிழ்
  te	Telugu	తెలుగు
  tg	Tajik	тоҷикӣ, toğikī, تاجیکی‎
  th	Thai	ไทย
  ti	Tigrinya	ትግርኛ
  tk	Turkmen	Türkmen, Түркмен
  tl	Tagalog	Wikang Tagalog, ᜏᜒᜃᜅ᜔ ᜆᜄᜎᜓᜄ᜔
  tn	Tswana	Setswana
  to	Tonga (Tonga Islands)	faka Tonga
  tr	Turkish	Türkçe
  ts	Tsonga	Xitsonga
  tt	Tatar	татарча, tatarça, تاتارچا‎
  tw	Twi	Twi
  ty	Tahitian	Reo Mā`ohi
  ug	Uighur, Uyghur	Uyƣurqə, ئۇيغۇرچە‎
  uk	Ukrainian	Українська
  ur	Urdu	اردو
  uz	Uzbek	O'zbek, Ўзбек, أۇزبېك‎
  ve	Venda	Tshivenḓa
  vi	Vietnamese	Tiếng Việt
  vo	Volapük	Volapük
  wa	Walloon	Walon
  wo	Wolof	Wollof
  xh	Xhosa	isiXhosa
  yi	Yiddish	ייִדיש
  yo	Yoruba	Yorùbá
  za	Zhuang, Chuang	Saɯ cueŋƅ, Saw cuengh
  zh	Chinese	中文 (Zhōngwén), 汉语, 漢語
  zu	Zulu	isiZulu

 */

/* List of unused wordpress locales (22-Jun-2011)
  # ckb/ Kurdish
  # cpp/ ??
  # el/
  # es_CL/
  # es_PE/
  # es_VE/
  # fo/ foroese
  # fr_BE/
  # fy/
  # /
  # kea/ ??
  # /
  # ml_IN/
  # nb_NO/ ? good Question, popped into Norway
  # nn_NO/ ? same question
  # pt_BR/
  # ru_UA/
  # sd_PK/
  # su_ID/
  # ta_IN/
  # ug_CN/
  # zh_HK/
 */
?>