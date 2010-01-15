<?php
/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
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
//the array directs from language code to - English Name, Native name, flag
$languages = array(
        "en" => "English,English,us",
        "af" => "Afrikaans,Afrikaans,za",
        "sq" => "Albanian,Shqip,al",
        "ar" => "Arabic,العربية,sa",
        "be" => "Belarusian,Беларуская,by",
        "bg" => "Bulgarian,Български,bg",
        "ca" => "Catalan,Català,catalonia",
        "zh" => "Chinese (Simplified),中文(简体),cn",
        "zh-tw" => "Chinese (Traditional),中文(漢字),tw",
        "hr" => "Croatian,Hrvatski,hr",
        "cs" => "Czech,čeština,cz",
        "da" => "Danish,dansk,dk",
        "nl" => "Dutch,Nederlands,nl",
        "et" => "Estonian,Eesti keel,ee",
        "fi" => "Finnish,Suomi,fi",
        "fr" => "French,Français,fr",
        "gl" => "Galician,Galego,galicia",
        "de" => "German,Deutsch,de",
        "el" => "Greek,Ελληνικά,gr",
        "he" => "Hebrew,עברית,il",
        "hi" => "Hindi,हिन्दी; हिंदी,in",
        "hu" => "Hungarian,magyar,hu",
        "id" => "Indonesian,Bahasa Indonesia,id",
        "it" => "Italian,Italiano,it",
        "is" => "Icelandic,íslenska,is",
        "ga" => "Irish,Gaeilge,ie",
        "ja" => "Japanese,日本語,jp",
        "ko" => "Korean,우리말,kr",
        "lv" => "Latvian,latviešu valoda,lv",
        "lt" => "Lithuanian,lietuvių kalba,lt",
        "mk" => "Macedonian,македонски јазик,mk",
        "ms" => "Malay,bahasa Melayu,my",
        "mt" => "Maltese,Malti,mt",
        "no" => "Norwegian,Norsk,no",
        "fa" => "Persian,فارسی,ir",
        "pl" => "Polish,Polski,pl",
        "pt" => "Portuguese,Português,pt",
        "ro" => "Romanian,Română,ro",
        "ru" => "Russian,Русский,ru",
        "sr" => "Serbian,српски језик,rs",
        "sk" => "Slovak,slovenčina,sk",
        "sl" => "Slovene,slovenščina,sl",
        "es" => "Spanish,Español,es",
        "sw" => "Swahili,Kiswahili,ke",
        "sv" => "Swedish,svenska,se",
        "tl" => "Tagalog,Tagalog,ph",
        "th" => "Thai,ภาษาไทย,th",
        "tr" => "Turkish,Türkçe,tr",
        "uk" => "Ukrainian,Українська,ua",
        "vi" => "Vietnamese,Tiếng Việt,vn",
        "cy" => "Welsh,Cymraeg,wales",
        "yi" => "Yiddish,ייִדיש,europeanunion"
);

//Language which are read from right to left (rtl)
$rtl_languages = array("ar", "he", "fa", "yi");

//Google supported languages @updated 2009-Dec-21
$google_languages = array("en", "af", "sq", "ar", "be", "bg", "ca", "zh", "zh-tw", "hr", "cs", "da", "nl", "et", "fi", "fr", "gl", "de", "el", "he", "hi", "hu", "id", "it", "is", "ga", "ja", "ko", "lv", "lt", "mk", "ms", "mt", "no", "fa", "pl", "pt", "ro", "ru", "sr", "sk", "sl", "es", "sw", "sv", "tl", "th", "tr", "uk", "vi", "cy", "yi");
//Bing supported languages @updated 2009-Dec-21
$bing_languages = array("en", "ar", "bg", "zh", "zh-tw", "cs", "da", "nl", "fi", "fr", "de", "gr", "he", "it", "ja", "ko", "pl", "pt", "ru", "es", "sv", "th");

//Define the new capability that will be assigned to roles - translator
define("TRANSLATOR", 'translator');

define("TRANSPOSH_PLUGIN_VER",'%VERSION%');

//Define segment id prefix, will be included in span tag. also used as class identifier
define("SPAN_PREFIX", "tr_");

//The name of our admin page
define('TRANSPOSH_ADMIN_PAGE_NAME', 'transposh');

//0.3.5 - Storing all options in this config option
define("TRANSPOSH_OPTIONS", "transposh_options");

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
?>