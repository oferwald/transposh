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

//** FULL VERSION
define('FULL_VERSION', true);
//** FULLSTOP
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
    private static $languages = [
        'en' => [
            'name' => 'English',
            'orig' => 'English',
            'flag' => 'us',
            'locale' => 'en_US',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
                'a' => 'y',
            ],
            'adsense' => 'y',
        ],
        'af' => [
            'name' => 'Afrikaans',
            'orig' => 'Afrikaans',
            'flag' => 'za',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'afr',
                'y' => 'y',
            ],
        ],
        'sq' => [
            'name' => 'Albanian',
            'orig' => 'Shqip',
            'flag' => 'al',
            'engines' => [
                'g' => 'y',
                'u' => 'alb',
                'y' => 'y',
            ],
        ],
        'am' => [
            'name' => 'Amharic',
            'orig' => 'አማርኛ',
            'flag' => 'et',
            'engines' => [
                'g' => 'y',
                'u' => 'amh',
                'y' => 'y',
            ],
        ],
        'ar' => [
            'name' => 'Arabic',
            'orig' => 'العربية',
            'flag' => 'sa',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'ara',
                'y' => 'y',
            ],
            'rtl' => 'y',
            'adsense' => 'y',
        ],
        'hy' => [
            'name' => 'Armenian',
            'orig' => 'Հայերեն',
            'flag' => 'am',
            'engines' => [
                'g' => 'y',
                'u' => 'arm',
                'y' => 'y',
            ],
        ],
        'az' => [
            'name' => 'Azerbaijani',
            'orig' => 'azərbaycan dili',
            'flag' => 'az',
            'engines' => [
                'g' => 'y',
                'u' => 'aze',
                'y' => 'y',
            ],
        ],
        'eu' => [
            'name' => 'Basque',
            'orig' => 'Euskara',
            'flag' => 'es-ba',
            'engines' => [
                'g' => 'y',
                'u' => 'baq',
                'y' => 'y',
            ],
        ],
        'ba' => [
            'name' => 'Bashkir',
            'orig' => 'башҡорт теле',
            'flag' => 'ru-ba',
            'engines' => [
                'y' => 'y',
            ],
        ],
        'be' => [
            'name' => 'Belarusian',
            'orig' => 'Беларуская',
            'flag' => 'by',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'bel',
                'y' => 'y',
            ],
        ],
        'bn' => [
            'name' => 'Bengali',
            'orig' => 'বাংলা',
            'flag' => 'bd',
            'locale' => 'bn_BD',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'ben',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'bs' => [
            'name' => 'Bosnian',
            'orig' => 'bosanski jezik',
            'flag' => 'ba',
            'locale' => 'bs_BA',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'bos',
                'y' => 'y',
            ],
        ],
        'bg' => [
            'name' => 'Bulgarian',
            'orig' => 'Български',
            'flag' => 'bg',
            'locale' => 'bg_BG',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'bul',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'my' => [
            'name' => 'Burmese',
            'orig' => 'မြန်မာစာ',
            'flag' => 'mm',
            'locale' => 'my_MM',
            'engines' => [
                'g' => 'y',
                'u' => 'bur',
                'y' => 'y',
            ],
        ],
        'ca' => [
            'name' => 'Catalan',
            'orig' => 'Català',
            'flag' => 'es-ca',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'cat',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'yue' => [
            'name' => 'Cantonese',
            'orig' => '粤语',
            'flag' => 'hk',
            'locale' => 'zh_HK',
            'engines' => [
                'b' => 'y',
            ],
        ],
        'ceb' => [
            'name' => 'Cebuano',
            'orig' => 'Binisaya',
            'flag' => 'ph',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
        ],
        'ny' => [
            'name' => 'Chichewa',
            'orig' => 'Chinyanja',
            'flag' => 'mw',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'zh' => [
            'name' => 'Chinese (Simplified)',
            'orig' => '中文(简体)',
            'flag' => 'cn',
            'locale' => 'zh_CN',
            'engines' => [
                'b' => 'zh-chs',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'zh-tw' => [
            'name' => 'Chinese (Traditional)',
            'orig' => '中文(漢字)',
            'flag' => 'tw',
            'locale' => 'zh_TW',
            'engines' => [
                'b' => 'zh-cht',
                'g' => 'y',
                'u' => 'cht',
            ],
            'adsense' => 'y',
        ],
        'co' => [
            'name' => 'Corsican',
            'orig' => 'Corsu',
            'flag' => 'fr',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
            ],
        ],
        'hr' => [
            'name' => 'Croatian',
            'orig' => 'Hrvatski',
            'flag' => 'hr',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'hrv',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'cs' => [
            'name' => 'Czech',
            'orig' => 'Čeština',
            'flag' => 'cz',
            'locale' => 'cs_CZ',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'da' => [
            'name' => 'Danish',
            'orig' => 'Dansk',
            'flag' => 'dk',
            'locale' => 'da_DK',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'dan',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'nl' => [
            'name' => 'Dutch',
            'orig' => 'Nederlands',
            'flag' => 'nl',
            'locale' => 'nl_NL',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'eo' => [
            'name' => 'Esperanto',
            'orig' => 'Esperanto',
            'flag' => 'esperanto',
            'engines' => [
                'b' => 'epo',
                'g' => 'y',
                'u' => 'epo',
                'y' => 'y',
                'a' => 'y',
            ],
        ],
        'et' => [
            'name' => 'Estonian',
            'orig' => 'Eesti keel',
            'flag' => 'ee',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'est',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'fj' => [
            'name' => 'Fijian',
            'orig' => 'vosa Vakaviti',
            'flag' => 'fj',
            'engines' => [
                'b' => 'y',
            ],
        ],
        'fil' => [
            'name' => 'Filipino',
            'orig' => 'Wikang Filipino',
            'flag' => 'ph',
            'engines' => [
                'b' => 'y',
            ],
            'adsense' => 'y',
        ],
        'fi' => [
            'name' => 'Finnish',
            'orig' => 'Suomi',
            'flag' => 'fi',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'fin',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'fr' => [
            'name' => 'French',
            'orig' => 'Français',
            'flag' => 'fr',
            'locale' => 'fr_FR',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'fra',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'fy' => [
            'name' => 'Frisian',
            'orig' => 'Frysk',
            'flag' => 'nl',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
            ],
        ],
        'gl' => [
            'name' => 'Galician',
            'orig' => 'Galego',
            'flag' => 'es-ga',
            'locale' => 'gl_ES',
            'engines' => [
                'g' => 'y',
                'u' => 'glg',
                'y' => 'y',
            ],
        ],
        'ka' => [
            'name' => 'Georgian',
            'orig' => 'ქართული',
            'flag' => 'ge',
            'locale' => 'ka_GE',
            'engines' => [
                'g' => 'y',
                'u' => 'geo',
                'y' => 'y',
            ],
        ],
        'de' => [
            'name' => 'German',
            'orig' => 'Deutsch',
            'flag' => 'de',
            'locale' => 'de_DE',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'el' => [
            'name' => 'Greek',
            'orig' => 'Ελληνικά',
            'flag' => 'gr',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'gu' => [
            'name' => 'Gujarati',
            'orig' => 'ગુજરાતી',
            'flag' => 'in',
            'engines' => [
                'g' => 'y',
                'u' => 'guj',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ht' => [
            'name' => 'Haitian',
            'orig' => 'Kreyòl ayisyen',
            'flag' => 'ht',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
        ],
        'ha' => [
            'name' => 'Hausa',
            'orig' => 'Harshen Hausa',
            'flag' => 'ng',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'haw' => [
            'name' => 'Hawaiian',
            'orig' => 'ʻŌlelo Hawaiʻi',
            'flag' => 'us-ha',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'hmn' => [
            'name' => 'Hmong',
            'orig' => 'Hmoob',
            'flag' => 'la',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'mw' => [
            'name' => 'Hmong Daw',
            'orig' => 'Hmoob Daw',
            'flag' => 'la',
            'engines' => [
                'b' => 'mww',
            ],
        ],
        'he' => [
            'name' => 'Hebrew',
            'orig' => 'עברית',
            'flag' => 'il',
            'locale' => 'he_IL',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'heb',
                'y' => 'y',
            ],
            'rtl' => 'y',
            'adsense' => 'y',
        ],
        'mrj' => [
            'name' => 'Hill Mari',
            'orig' => 'Мары йӹлмӹ',
            'flag' => 'ru',
            'engines' => [
                'y' => 'y',
            ],
        ],
        'hi' => [
            'name' => 'Hindi',
            'orig' => 'हिन्दी; हिंदी',
            'flag' => 'in',
            'locale' => 'hi_IN',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'hu' => [
            'name' => 'Hungarian',
            'orig' => 'Magyar',
            'flag' => 'hu',
            'locale' => 'hu_HU',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'is' => [
            'name' => 'Icelandic',
            'orig' => 'Íslenska',
            'flag' => 'is',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'ice',
                'y' => 'y',
            ],
        ],
        'ig' => [
            'name' => 'Igbo',
            'orig' => 'Asụsụ Igbo',
            'flag' => 'ng',
            'engines' => [
                'g' => 'y',
                'u' => 'ibo',
            ],
        ],
        'id' => [
            'name' => 'Indonesian',
            'orig' => 'Bahasa Indonesia',
            'flag' => 'id',
            'locale' => 'id_ID',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ga' => [
            'name' => 'Irish',
            'orig' => 'Gaeilge',
            'flag' => 'ie',
            'engines' => [
                'g' => 'y',
                'u' => 'gle',
                'y' => 'y',
            ],
        ],
        'it' => [
            'name' => 'Italian',
            'orig' => 'Italiano',
            'flag' => 'it',
            'locale' => 'it_IT',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ja' => [
            'name' => 'Japanese',
            'orig' => '日本語',
            'flag' => 'jp',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'jp',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'jw' => [
            'name' => 'Javanese',
            'orig' => 'basa Jawa',
            'flag' => 'id',
            'locale' => 'jv_ID',
            'engines' => [
                'b' => 'jav',
                'g' => 'y',
                'y' => 'jv',
            ],
        ],
        'kn' => [
            'name' => 'Kannada',
            'orig' => 'ಕನ್ನಡ',
            'flag' => 'in',
            'engines' => [
                'g' => 'y',
                'u' => 'kan',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'kk' => [
            'name' => 'Kazakh',
            'orig' => 'Қазақ тілі',
            'flag' => 'kz',
            'engines' => [
                'g' => 'y',
                'u' => 'kaz',
                'y' => 'y',
            ],
        ],
        'km' => [
            'name' => 'Khmer',
            'orig' => 'ភាសាខ្មែរ',
            'flag' => 'kh',
            'engines' => [
                'g' => 'y',
                'u' => 'hkm',
                'y' => 'y',
            ],
        ],
        'ky' => [
            'name' => 'Kirghiz',
            'orig' => 'кыргыз тили',
            'flag' => 'kg',
            'locale' => 'ky_KY',
            'engines' => [
                'g' => 'y',
                'u' => 'kir',
                'y' => 'y',
            ],
        ],
        'ko' => [
            'name' => 'Korean',
            'orig' => '한국어',
            'flag' => 'kr',
            'locale' => 'ko_KR',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'kor',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ku' => [
            'name' => 'Kurdish (Kurmanji)',
            'orig' => 'Kurdî',
            'flag' => 'tr',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'lo' => [
            'name' => 'Lao',
            'orig' => 'ພາສາລາວ',
            'flag' => 'la',
            'engines' => [
                'g' => 'y',
                'u' => 'lao',
                'y' => 'y',
            ],
        ],
        'la' => [
            'name' => 'Latin',
            'orig' => 'Latīna',
            'flag' => 'va',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'lat',
                'y' => 'y',
            ],
        ],
        'lv' => [
            'name' => 'Latvian',
            'orig' => 'Latviešu valoda',
            'flag' => 'lv',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'lav',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'lt' => [
            'name' => 'Lithuanian',
            'orig' => 'Lietuvių kalba',
            'flag' => 'lt',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'lit',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'lb' => [
            'name' => 'Luxembourgish',
            'orig' => 'Lëtzebuergesch',
            'flag' => 'lu',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'ltz',
                'y' => 'y',
            ],
        ],
        'mk' => [
            'name' => 'Macedonian',
            'orig' => 'македонски јазик',
            'flag' => 'mk',
            'locale' => 'mk_MK',
            'engines' => [
                'g' => 'y',
                'u' => 'mac',
                'y' => 'y',
            ],
        ],
        'mg' => [
            'name' => 'Malagasy',
            'orig' => 'Malagasy fiteny',
            'flag' => 'mg',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
        ],
        'ms' => [
            'name' => 'Malay',
            'orig' => 'Bahasa Melayu',
            'flag' => 'my',
            'locale' => 'ms_MY',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'may',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ml' => [
            'name' => 'Malayalam',
            'orig' => 'മലയാളം',
            'flag' => 'in',
            'engines' => [
                'g' => 'y',
                'u' => 'mal',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'mt' => [
            'name' => 'Maltese',
            'orig' => 'Malti',
            'flag' => 'mt',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'mlt',
                'y' => 'y',
            ],
        ],
        'mi' => [
            'name' => 'Maori',
            'orig' => 'Te Reo Māori',
            'flag' => 'nz',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'y' => 'y',
            ],
        ],
        'mr' => [
            'name' => 'Marathi',
            'orig' => 'मराठी',
            'flag' => 'in',
            'engines' => [
                'g' => 'y',
                'u' => 'mar',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'mhr' => [
            'name' => 'Mari',
            'orig' => 'марий йылме',
            'flag' => 'ru',
            'engines' => [
                'y' => 'y',
            ],
        ],
        'mn' => [
            'name' => 'Mongolian',
            'orig' => 'Монгол',
            'flag' => 'mn',
            'engines' => [
                'g' => 'y',
                'u' => 'mon',
                'y' => 'y',
            ],
        ],
        'ne' => [
            'name' => 'Nepali',
            'orig' => 'नेपाली',
            'flag' => 'np',
            'locale' => 'ne_NP',
            'engines' => [
                'g' => 'y',
                'u' => 'nep',
                'y' => 'y',
            ],
        ],
        'no' => [
            'name' => 'Norwegian',
            'orig' => 'Norsk',
            'flag' => 'no',
            'locale' => 'nb_NO',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'nor',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'otq' => [
            'name' => 'Otomi',
            'orig' => 'Querétaro Otomi',
            'flag' => 'mx',
            'engines' => [
                'b' => 'y',
            ],
        ],
        'pap' => [
            'name' => 'Papiamento',
            'orig' => 'Papiamentu',
            'flag' => 'aw',
            'engines' => [
                'b' => 'y',
                'y' => 'y',
            ],
        ],
        'fa' => [
            'name' => 'Persian',
            'orig' => 'پارسی',
            'flag' => 'ir',
            'locale' => 'fa_IR',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'per',
                'y' => 'y',
            ],
            'rtl' => 'y',
        ],
        'pl' => [
            'name' => 'Polish',
            'orig' => 'Polski',
            'flag' => 'pl',
            'locale' => 'pl_PL',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'pt' => [
            'name' => 'Portuguese',
            'orig' => 'Português',
            'flag' => 'pt',
            'locale' => 'pt_PT',
            'engines' => [
                'b' => 'pt-PT',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'pt-br' => [
            'name' => 'Brazilian Portuguese',
            'orig' => 'Português do Brasil',
            'flag' => 'br',
            'locale' => 'pt_BR',
            'engines' => [
                'b' => 'pt',
            ],
            'adsense' => 'y',
        ],
        'pa' => [
            'name' => 'Punjabi',
            'orig' => 'ਪੰਜਾਬੀ',
            'flag' => 'pk',
            'locale' => 'pa_IN',
            'engines' => [
                'g' => 'y',
                'u' => 'pan',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ro' => [
            'name' => 'Romanian',
            'orig' => 'Română',
            'flag' => 'ro',
            'locale' => 'ro_RO',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'rom',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ru' => [
            'name' => 'Russian',
            'orig' => 'Русский',
            'flag' => 'ru',
            'locale' => 'ru_RU',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'sm' => [
            'name' => 'Samoan',
            'orig' => 'gagana fa\'a Samoa',
            'flag' => 'ws',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
            ],
        ],
        'gd' => [
            'name' => 'Scots Gaelic',
            'orig' => 'Gàidhlig',
            'flag' => 'gb-sc',
            'engines' => [
                'g' => 'y',
                'u' => 'gla',
                'y' => 'y',
            ],
        ],
        'sr' => [
            'name' => 'Serbian',
            'orig' => 'Cрпски језик',
            'flag' => 'rs',
            'locale' => 'sr_RS',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'srp',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'st' => [
            'name' => 'Sesotho',
            'orig' => 'Sesotho',
            'flag' => 'ls',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'sn' => [
            'name' => 'Shona',
            'orig' => 'chiShona',
            'flag' => 'zw',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'sd' => [
            'name' => 'Sindhi',
            'orig' => 'سنڌي',
            'flag' => 'pk',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'si' => [
            'name' => 'Sinhala',
            'orig' => 'සිංහල',
            'flag' => 'lk',
            'locale' => 'si_LK',
            'engines' => [
                'g' => 'y',
                'u' => 'sin',
                'y' => 'y',
            ],
        ],
        'sk' => [
            'name' => 'Slovak',
            'orig' => 'Slovenčina',
            'flag' => 'sk',
            'locale' => 'sk_SK',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'sl' => [
            'name' => 'Slovene',
            'orig' => 'Slovenščina',
            'flag' => 'si',
            'locale' => 'sl_SI',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'slo',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'so' => [
            'name' => 'Somali',
            'orig' => 'Af-Soomaali',
            'flag' => 'so',
            'engines' => [
                'g' => 'y',
            ],
        ],
        'es' => [
            'name' => 'Spanish',
            'orig' => 'Español',
            'flag' => 'es',
            'locale' => 'es_ES',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'spa',
                'y' => 'y',
                'a' => 'y',
            ],
            'adsense' => 'y',
        ],
        'su' => [
            'name' => 'Sundanese',
            'orig' => 'Basa Sunda',
            'flag' => 'id',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'sun',
                'y' => 'y',
            ],
        ],
        'sw' => [
            'name' => 'Swahili',
            'orig' => 'Kiswahili',
            'flag' => 'tz',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'swa',
                'y' => 'y',
            ],
        ],
        'sv' => [
            'name' => 'Swedish',
            'orig' => 'Svenska',
            'flag' => 'se',
            'locale' => 'sv_SE',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'swe',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'tl' => [
            'name' => 'Tagalog',
            'orig' => 'Tagalog',
            'flag' => 'ph',
            'engines' => [
                'g' => 'y',
                'u' => 'tgl',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ty' => [
            'name' => 'Tahitian',
            'orig' => 'Reo Mā`ohi\'',
            'flag' => 'pf',
            'engines' => [
                'b' => 'y',
            ],
        ],
        'tg' => [
            'name' => 'Tajik',
            'orig' => 'Тоҷикӣ',
            'flag' => 'tj',
            'engines' => [
                'g' => 'y',
                'u' => 'tgk',
                'y' => 'y',
            ],
        ],
        'ta' => [
            'name' => 'Tamil',
            'orig' => 'தமிழ்',
            'flag' => 'in',
            'locale' => 'ta_IN',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'tam',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'tt' => [
            'name' => 'Tatar',
            'orig' => 'татарча',
            'flag' => 'ru-ta',
            'engines' => [
                'u' => 'tat',
                'y' => 'y',
            ],
        ],
        'te' => [
            'name' => 'Telugu',
            'orig' => 'తెలుగు',
            'flag' => 'in',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'tel',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'th' => [
            'name' => 'Thai',
            'orig' => 'ภาษาไทย',
            'flag' => 'th',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'to' => [
            'name' => 'Tonga',
            'orig' => 'faka Tonga',
            'flag' => 'to',
            'engines' => [
                'b' => 'y',
            ],
        ],
        'tr' => [
            'name' => 'Turkish',
            'orig' => 'Türkçe',
            'flag' => 'tr',
            'locale' => 'tr_TR',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'y',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'udm' => [
            'name' => 'Udmurt',
            'orig' => 'удмурт кыл',
            'flag' => 'ru',
            'engines' => [
                'y' => 'y',
            ],
        ],
        'uk' => [
            'name' => 'Ukrainian',
            'orig' => 'Українська',
            'flag' => 'ua',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'ukr',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'ur' => [
            'name' => 'Urdu',
            'orig' => 'اردو',
            'flag' => 'pk',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'urd',
                'y' => 'y',
            ],
            'rtl' => 'y',
            'adsense' => 'y',
        ],
        'uz' => [
            'name' => 'Uzbek',
            'orig' => 'Oʻzbek tili',
            'flag' => 'uz',
            'locale' => 'uz_UZ',
            'engines' => [
                'g' => 'y',
                'u' => 'uzb',
                'y' => 'y',
            ],
        ],
        'vi' => [
            'name' => 'Vietnamese',
            'orig' => 'Tiếng Việt',
            'flag' => 'vn',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'vie',
                'y' => 'y',
            ],
            'adsense' => 'y',
        ],
        'cy' => [
            'name' => 'Welsh',
            'orig' => 'Cymraeg',
            'flag' => 'gb-wa',
            'engines' => [
                'b' => 'y',
                'g' => 'y',
                'u' => 'wel',
                'y' => 'y',
            ],
        ],
        'xh' => [
            'name' => 'Xhosa',
            'orig' => 'isiXhosa',
            'flag' => 'za',
            'engines' => [
                'g' => 'y',
                'u' => 'xho',
                'y' => 'y',
            ],
        ],
        'yi' => [
            'name' => 'Yiddish',
            'orig' => 'ייִדיש',
            'flag' => 'europeanunion',
            'engines' => [
                'b' => 'ydd',
                'g' => 'y',
                'u' => 'yid',
                'y' => 'y',
            ],
            'rtl' => 'y',
        ],
        'yo' => [
            'name' => 'Yoruba',
            'orig' => 'èdè Yorùbá',
            'flag' => 'ng',
            'engines' => [
                'g' => 'y',
                'u' => 'yor',
            ],
        ],
        'yua' => [
            'name' => 'Yucatec Maya',
            'orig' => 'Màaya T\'àan',
            'flag' => 'mx',
            'engines' => [
                'b' => 'y',
            ],
        ],
        'zu' => [
            'name' => 'Zulu',
            'orig' => 'isiZulu',
            'flag' => 'za',
            'engines' => [
                'g' => 'y',
                'u' => 'zul',
                'y' => 'y',
            ],
        ],
    ];

    /*
     * Upstream source: https://wiki.openstreetmap.org/wiki/Nominatim/Country_Codes
     */
    private static $countryToLanguageMapping = [
        'ad' => 'ca', 
        'ae' => 'ar',
        'af' => 'fa,ps',
        'ag' => 'en',
        'ai' => 'en',
        'al' => 'sq',
        'am' => 'hy',
        'an' => 'nl,en',
        'ao' => 'pt',
        // 'aq'=> '',
        'ar' => 'es',
        'as' => 'en,sm',
        'at' => 'de',
        'au' => 'en',
        'aw' => 'nl,pap',
        'ax' => 'sv',
        'ba' => 'bs,hr,sr',
        'bb' => 'en',
        'bd' => 'bn',
        'be' => 'nl,fr,de',
        'bf' => 'fr',
        'bh' => 'ar',
        'bi' => 'fr',
        'bj' => 'fr',
        'bl' => 'fr',
        'bm' => 'en',
        'bn' => 'ms',
        'bo' => 'es,qu,ay',
        'br' => 'pt-br',
        'bs' => 'en',
        'bt' => 'dz',
        'bv' => 'no',
        'bw' => 'en,tn',
        'by' => 'be,ru',
        'bz' => 'en',
        'ca' => 'en,fr',
        'cc' => 'en',
        'cd' => 'fr',
        'cf' => 'fr',
        'cg' => 'fr',
        'ch' => 'de,fr,it,rm',
        'ci' => 'fr',
        'ck' => 'en,rar',
        'cl' => 'es',
        'cm' => 'fr,en',
        'cn' => 'zh',
        'co' => 'es',
        'cr' => 'es',
        'cu' => 'es',
        'cv' => 'pt',
        'cx' => 'en',
        'cy' => 'el,tr',
        'cz' => 'cs',
        '// de' => 'de',
        'dj' => 'fr,ar,so',
        'dk' => 'da',
        'dm' => 'en',
        'do' => 'es',
        'dz' => 'ar',
        'ec' => 'es',
        'ee' => 'et',
        'eg' => 'ar',
        'eh' => 'ar,es,fr',
        'er' => 'ti,ar,en',
        'es' => 'ast,ca,es,eu,gl',
        'et' => 'am,om',
        'fi' => 'fi,sv,se',
        'fj' => 'en',
        'fk' => 'en',
        'fm' => 'en',
        // 'fo'=> 'fo',
        // 'fr'=> 'fr',
        'ga' => 'fr',
        'gb' => 'en,ga,cy,gd,kw',
        'gd' => 'en',
        'ge' => 'ka',
        'gf' => 'fr',
        'gg' => 'en',
        'gh' => 'en',
        'gi' => 'en',
        'gl' => 'kl,da',
        'gm' => 'en',
        'gn' => 'fr',
        'gp' => 'fr',
        'gq' => 'es,fr,pt',
        'gr' => 'el',
        'gs' => 'en',
        'gt' => 'es',
        'gu' => 'en,ch',
        'gw' => 'pt',
        'gy' => 'en',
        'hk' => 'zh,en',
        'hm' => 'en',
        'hn' => 'es',
        // 'hr'=> 'hr',
        'ht' => 'fr,ht',
        // 'hu'=> 'hu',
        // 'id'=> 'id',
        'ie' => 'en,ga',
        'il' => 'he',
        'im' => 'en',
        'in' => 'hi,en',
        'io' => 'en',
        'iq' => 'ar,ku',
        'ir' => 'fa',
        // 'is'=> 'is',
        'it' => 'it,de,fr',
        'je' => 'en',
        'jm' => 'en',
        'jo' => 'ar',
        'jp' => 'ja',
        'ke' => 'sw,en',
        'kg' => 'ky,ru',
        'kh' => 'km',
        'ki' => 'en',
        'km' => 'ar,fr',
        'kn' => 'en',
        'kp' => 'ko',
        'kr' => 'ko,en',
        'kw' => 'ar',
        'ky' => 'en',
        'kz' => 'kk,ru',
        'la' => 'lo',
        'lb' => 'ar,fr',
        'lc' => 'en',
        'li' => 'de',
        'lk' => 'si,ta',
        'lr' => 'en',
        'ls' => 'en,st',
        // 'lt'=> 'lt',
        'lu' => 'lb,fr,de',
        // 'lv'=> 'lv',
        'ly' => 'ar',
        'ma' => 'ar',
        'mc' => 'fr',
        'md' => 'ru,uk,ro',
        'me' => 'srp,sq,bs,hr,sr',
        'mf' => 'fr',
        'mg' => 'mg,fr',
        'mh' => 'en,mh',
        // 'mk'=> 'mk',
        'ml' => 'fr',
        'mm' => 'my',
        // 'mn'=> 'mn',
        'mo' => 'zh,pt',
        'mp' => 'ch',
        'mq' => 'fr',
        'mr' => 'ar,fr',
        'ms' => 'en',
        'mt' => 'mt,en',
        'mu' => 'mfe,fr,en',
        'mv' => 'dv',
        'mw' => 'en,ny',
        'mx' => 'es',
        'my' => 'ms',
        'mz' => 'pt',
        'na' => 'en,sf,de',
        'nc' => 'fr',
        'ne' => 'fr',
        'nf' => 'en,pih',
        'ng' => 'en',
        'ni' => 'es',
        // 'nl'=> 'nl',
        'no' => 'nb,nn,no,se',
        'np' => 'ne',
        'nr' => 'na,en',
        'nu' => 'niu,en',
        'nz' => 'mi,en',
        'om' => 'ar',
        'pa' => 'es',
        'pe' => 'es',
        'pf' => 'fr',
        'pg' => 'en,tpi,ho',
        'ph' => 'en,tl',
        'pk' => 'en,ur',
        // 'pl'=> 'pl',
        'pm' => 'fr',
        'pn' => 'en,pih',
        'pr' => 'es,en',
        'ps' => 'ar,he',
        // 'pt'=> 'pt',
        'pw' => 'en,pau,ja,sov,tox',
        'py' => 'es,gn',
        'qa' => 'ar',
        're' => 'fr',
        // 'ro'=> 'ro',
        'rs' => 'sr',
        // 'ru'=> 'ru',
        'rw' => 'rw,fr,en',
        'sa' => 'ar',
        'sb' => 'en',
        'sc' => 'fr,en,crs',
        'sd' => 'ar,en',
        'se' => 'sv',
        'sg' => 'en,ms,zh,ta',
        'sh' => 'en',
        'si' => 'sl',
        'sj' => 'no',
        // 'sk'=> 'sk',
        'sl' => 'en',
        'sm' => 'it',
        'sn' => 'fr',
        'so' => 'so,ar',
        'sr' => 'nl',
        'st' => 'pt',
        'ss' => 'en',
        'sv' => 'es',
        'sy' => 'ar',
        'sz' => 'en,ss',
        'tc' => 'en',
        'td' => 'fr,ar',
        'tf' => 'fr',
        'tg' => 'fr',
        // 'th'=> 'th',
        'tj' => 'tg,ru',
        'tk' => 'tkl,en,sm',
        'tl' => 'pt,tet',
        'tm' => 'tk',
        'tn' => 'ar',
        'to' => 'en',
        // 'tr'=> 'tr',
        'tt' => 'en',
        'tv' => 'en',
        'tw' => 'zh-tw,zh',
        'tz' => 'sw,en',
        'ua' => 'uk',
        'ug' => 'en,sw',
        'um' => 'en',
        'us' => 'en',
        'uy' => 'es',
        'uz' => 'uz,kaa',
        'va' => 'it',
        'vc' => 'en',
        've' => 'es',
        'vg' => 'en',
        'vi' => 'en',
        'vn' => 'vi',
        'vu' => 'bi,en,fr',
        'wf' => 'fr',
        'ws' => 'sm,en',
        'ye' => 'ar',
        'yt' => 'fr',
        'za' => 'zu,xh,af,st,tn,en',
        'zm' => 'en',
        'zw' => 'en,sn,nd',
    ];

    // new var to hold translation engines information
    private static $engines = array(
        'b' => array(
            'name' => 'Bing',
            'icon' => 'bingicon.png',
        ),
        'g' => array(
            'name' => 'Google',
            'icon' => 'googleicon.png',
        ),
        'u' => array(
            'name' => 'Baidu',
            'icon' => 'baiduicon.png',
        ),
        'y' => array(
            'name' => 'Yandex',
            'icon' => 'yandexicon.png',
        ),
        'a' => array(
            'name' => 'Apertium',
            'icon' => 'apertiumicon.png',
        ),

    );

    public static function get_language_name($lang) {
        $langname = self::$languages[$lang]['name'];
        $langname_r = apply_filters("tp_language_name", $langname);
        return $langname_r;
    }

    public static function get_language_orig_name($lang) {
        $langorigname = self::$languages[$lang]['orig'];
        $langorigname_r = apply_filters("tp_language_origname", $langorigname);
        return $langorigname_r;
    }

    public static function get_language_flag($lang) {
        $flag = self::$languages[$lang]['flag'];
        $flag_r = apply_filters("tp_language_flag", $flag);
        return $flag_r;
    }

    public static function get_language_locale($lang)
    {
        if (isset(self::$languages[$lang]['locale'])) {
            $locale = self::$languages[$lang]['locale'];
        } else {
            $locale = $lang;
        }
        $locale_r = apply_filters("tp_language_locale", $locale);
        return $locale_r;
    }

    /**
     * @param string $lang - language tested
     * @return bool - do we have it?
     */
    public static function is_supported_language($lang) {
        return isset(self::$languages[$lang]);
    }

    /**
     * @param string $lang - language tested
     * @return bool - is it rtl?
     */
    public static function is_language_rtl($lang) {
        return isset(self::$languages[$lang]['rtl']);
    }

    /**
     * @param string $lang - language tested
     * @return bool - is it adsense language?
     */
    public static function is_language_adsense($lang) {
        return isset(self::$languages[$lang]['adsense']);
    }

    /**
     * @param string $lang - language tested
     * @param string $engine - engine tested
     * @return bool - is language supported by engine?
     */
    public static function is_supported_engine($lang, $engine) {
        return isset(self::$languages[$lang]['engines'][$engine]);
    }

    /**
     * @param string $lang - language tested
     * @param string $engine - engine tested
     * @return string - conversion of this engine?
     */
    public static function get_engine_lang_code($lang, $engine) {
        if (isset(self::$languages[$lang]['engines'][$engine])) {
            if (self::$languages[$lang]['engines'][$engine] == 'y') {
                return $lang;
            } else {
                return self::$languages[$lang]['engines'][$engine];
            }
        }
        return '';
    }

    /**
     * @param string $lang - language tested
     * @return array - all languages supported by engine
     */
    public static function get_engine_lang_codes($engine) {
        $langs = [];
        foreach (self::$languages as $lang => $langrec) {
            if (isset($langrec['engines'][$engine])) {
                $langs[$lang] = $lang;
            }
        }
        return $langs;
    }

    public static function get_engines() {
        return self::$engines;
    }

    /**
     * @return array
     */
    public static function get_country_mapping() {
        return self::$countryToLanguageMapping;
    }

    /**
     * @return int[]|string[]
     */
    public static function get_langauge_keys() {
        return array_keys(self::$languages);
    }

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
define('JQUERYUI_VER', '1.14.1');

//Define segment id prefix, will be included in span tag. also used as class identifier
define('SPAN_PREFIX', 'tr_');

//Our text domain
define('TRANSPOSH_TEXT_DOMAIN', 'transposh');

//0.3.5 - Storing all options in this config option
define('TRANSPOSH_OPTIONS', 'transposh_options');
//0.9.6 - Making sure Google works
define('TRANSPOSH_OPTIONS_YANDEXPROXY', 'transposh_options_yandexproxy');
define('TRANSPOSH_YANDEXPROXY_DELAY', 3600); // give it an hour
//0.9.6 - Making sure Google works
define('TRANSPOSH_OPTIONS_GOOGLEPROXY', 'transposh_options_googleproxy');
define('TRANSPOSH_GOOGLEPROXY_DELAY', 86400); // give it a day
//0.5.6 new definitions
//Defintions for directories used in the plugin
define('TRANSPOSH_DIR_CSS', 'css');
define('TRANSPOSH_DIR_IMG', 'img');
define('TRANSPOSH_DIR_JS', 'js');
define('TRANSPOSH_DIR_WIDGETS', 'widgets');
define('TRANSPOSH_DIR_UPLOAD', 'transposh'); //1.0.1