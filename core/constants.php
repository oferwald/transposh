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
define('EDIT_PARAM', 'edit');

//Enable in memory cache usage, APC, xcache
define('TP_ENABLE_CACHE', TRUE);
//What is the cache items TTL
define('TP_CACHE_TTL', 3600);

//Class marking a section not be translated.
define('NO_TRANSLATE_CLASS', 'no_translate');
define('NO_TRANSLATE_CLASS_GOOGLE', 'notranslate');
define('ONLY_THISLANGUAGE_CLASS', 'only_thislanguage');

//Get text breakers
define('TP_GTXT_BRK', chr(1)); // Gettext breaker
define('TP_GTXT_IBRK', chr(2)); // Gettext inner breaker (around %s)
define('TP_GTXT_BRK_CLOSER', chr(3)); // Gettext breaker closer
define('TP_GTXT_IBRK_CLOSER', chr(4)); // Gettext inner breaker closer

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
        'eu' => 'Basque,Euskara,basque,',
        'be' => 'Belarusian,Беларуская,by,',
        'bg' => 'Bulgarian,Български,bg,bg_BG',
        'ca' => 'Catalan,Català,catalonia,',
        'zh' => 'Chinese (Simplified),中文(简体),cn,zh_CN',
        'zh-tw' => 'Chinese (Traditional),中文(漢字),tw,zh_TW',
        'hr' => 'Croatian,Hrvatski,hr,',
        'cs' => 'Czech,Čeština,cz,cs_CZ',
        'da' => 'Danish,Dansk,dk,da_DK',
        'nl' => 'Dutch,Nederlands,nl,',
        'eo' => 'Esperanto,Esperanto,esperanto,',
        'et' => 'Estonian,Eesti keel,ee,',
        'fi' => 'Finnish,Suomi,fi,',
        'fr' => 'French,Français,fr,fr_FR',
        'gl' => 'Galician,Galego,galicia,gl_ES',
        'ka' => 'Georgian,ქართული,ge,ka_GE',
        'de' => 'German,Deutsch,de,de_DE',
        'el' => 'Greek,Ελληνικά,gr,',
        'ht' => 'Haitian,Kreyòl ayisyen,ht,',
        'he' => 'Hebrew,עברית,il,he_IL',
        'hi' => 'Hindi,हिन्दी; हिंदी,in,hi_IN',
        'hu' => 'Hungarian,Magyar,hu,hu_HU',
        'is' => 'Icelandic,Íslenska,is,',
        'id' => 'Indonesian,Bahasa Indonesia,id,id_ID',
        'ga' => 'Irish,Gaeilge,ie,',
        'it' => 'Italian,Italiano,it,it_IT',
        'ja' => 'Japanese,日本語,jp,',
        'ko' => 'Korean,우리말,kr,ko_KR',
        'la' => 'Latin,Latīna,va,',
        'lv' => 'Latvian,Latviešu valoda,lv,',
        'lt' => 'Lithuanian,Lietuvių kalba,lt,',
        'mk' => 'Macedonian,македонски јазик,mk,mk_MK',
        'ms' => 'Malay,Bahasa Melayu,my,ms_MY',
        'mt' => 'Maltese,Malti,mt,',
        'no' => 'Norwegian,Norsk,no,nb_NO',
        'fa' => 'Persian,فارسی,ir,fa_IR',
        'pl' => 'Polish,Polski,pl,pl_PL',
        'pt' => 'Portuguese,Português,pt,pt_PT',
        'ro' => 'Romanian,Română,ro,ro_RO',
        'ru' => 'Russian,Русский,ru,ru_RU',
        'sr' => 'Serbian,Cрпски језик,rs,sr_RS',
        'sk' => 'Slovak,Slovenčina,sk,sk_SK',
        'sl' => 'Slovene,Slovenščina,si,sl_SI', //slovenian
        'es' => 'Spanish,Español,es,es_ES',
        'sw' => 'Swahili,Kiswahili,ke,',
        'sv' => 'Swedish,Svenska,se,sv_SE',
        'tl' => 'Tagalog,Tagalog,ph,', // fhilipino
        'th' => 'Thai,ภาษาไทย,th,',
        'tr' => 'Turkish,Türkçe,tr,tr_TR',
        'uk' => 'Ukrainian,Українська,ua,',
        'ur' => 'Urdu,اردو,pk,',
        'vi' => 'Vietnamese,Tiếng Việt,vn,',
        'cy' => 'Welsh,Cymraeg,wales,',
        'yi' => 'Yiddish,ייִדיש,europeanunion,'
    );
    // Language which are read from right to left (rtl)
    public static $rtl_languages = array('ar', 'he', 'fa', 'ur', 'yi');
    // Google supported languages
    // (got using - var langs =''; jQuery.each(google.language.Languages,function(){if (google.language.isTranslatable(this)) {langs += this +'|'}}); console.log(langs); - fixed for our codes)
    // @updated 2010-Oct-01 (hy,az,eu,ka,la,ur)
    // $google_languages = array('en', 'af', 'sq', 'ar', 'hy', 'az', 'eu', 'be', 'bg', 'ca', 'zh', 'zh-tw', 'hr', 'cs', 'da', 'nl', 'et', 'fi', 'fr', 'gl', 'ka', 'de', 'el', 'ht', 'he', 'hi', 'hu', 'id', 'it', 'is', 'ga', 'ja', 'ko', 'lv', 'lt', 'mk', 'ms', 'mt', 'no', 'fa', 'pl', 'pt', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'sw', 'sv', 'tl', 'th', 'tr', 'uk', 'ur', 'vi', 'cy', 'yi');
    public static $google_languages = array('en', 'af', 'sq', 'ar', 'be', 'bg', 'ca', 'zh', 'zh-tw', 'hr', 'cs', 'da', 'nl', 'et', 'fi', 'fr', 'gl', 'de', 'el', 'ht', 'he', 'hi', 'hu', 'id', 'it', 'is', 'ga', 'ja', 'ko', 'lv', 'lt', 'mk', 'ms', 'mt', 'no', 'fa', 'pl', 'pt', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'sw', 'sv', 'tl', 'th', 'tr', 'uk', 'vi', 'cy', 'yi');
    public static $google_proxied_languages = array('hy', 'az', 'eu', 'ka', 'la', 'ur');
    // Bing supported languages
    // (got this using Microsoft.Translator.GetLanguages() - fixed to match our codes)
    // @updated 2010-Jun-30
    public static $bing_languages = array('en', 'ar', 'bg', 'zh', 'zh-tw', 'cs', 'da', 'nl', 'et', 'fi', 'fr', 'de', 'el', 'ht', 'he', 'hu', 'id', 'it', 'ja', 'ko', 'lv', 'lt', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'es', 'sv', 'th', 'tr', 'uk', 'vi');
    // Apertium supported languages
    // a bit tricky, but we'll see - starting with just esperanto
    public static $apertium_languages = array('eo');

// Array for holding po domains we have problems with
    public static $ignored_po_domains = array('MailPress');

}

//Define the new capability that will be assigned to roles - translator
define('TRANSLATOR', 'translator');

//Define for transposh plugin version
define('TRANSPOSH_PLUGIN_VER', '%VERSION%');

//Define segment id prefix, will be included in span tag. also used as class identifier
define('SPAN_PREFIX', 'tr_');

//The name of our admin page
define('TRANSPOSH_ADMIN_PAGE_NAME', 'transposh');

//Our text domain
define('TRANSPOSH_TEXT_DOMAIN', 'transposh');

//0.3.5 - Storing all options in this config option
define('TRANSPOSH_OPTIONS', 'transposh_options');

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

/* List of unused wordpress locales (27-Aug-2010)
  # bn_BD/ Bengali
  # bs_BA/ Bosnian
  # ckb/ Kurdish
  # cpp/ ??
  # el/
  # eo/ esperanto
  # es_CL/
  # es_PE/
  # es_VE/
  # fo/ foroese
  # fr_BE/
  # fy/
  # jv_ID/
  # /
  # kea/ ??
  # kn/
  # /
  # ky_KY/
  # ml_IN/
  # mn/
  # my_MM/
  # nb_NO/ ? good Question, popped into Norway
  # ne_NP/
  # nn_NO/ ? same question
  # pa_IN/
  # pt_BR/
  # ru_UA/
  # sd_PK/
  # si_LK/
  # su_ID/
  # ta_IN/
  # ug_CN/
  # uz_UZ/
  # zh_HK/
 */
?>