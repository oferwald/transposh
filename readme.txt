=== Transposh WordPress Translation ===
Contributors: oferwald
Donate link: http://transposh.org/donate/
Tags: translation, translate, i18n, widget, filter, buddypress, bilingual, multilingual, transposh, language, crowdsourcing, google translate, bing translate, context, wiki, RTL, Hebrew, Spanish, French, Russian, English, Arabic, Portuguese
Requires at least: 3.3
Tested up to: 3.5
Stable tag: %VERSION%

Transposh adds best of breed translation support to wordpress, 66 languages are automatically translated and can be manually corrected with ease.

== Description ==
Transposh translation filter for WordPress offers a unique approach to blog translation. It allows your blog to combine automatic translation with human translation aided by your users with an easy to use in-context interface.

[youtube http://www.youtube.com/watch?v=hN0WppbhoFQ]

You can watch the video above, made by Fabrice Meuwissen of obviousidea.com which describes basic usage of Transposh, more videos can be found in the changelog

***Transposh includes the following features:***

* Support for any language - including RTL/LTR layouts
* Unique drag/drop interface for choosing viewable/translatable languages
* Multiple options for widget appearances - with pluggable widgets and multiple instances
* Translation of external plugins without a need for .po/.mo files
* Automatic translation mode for all content (including comments!)
* Professional translation bt [One Hour Translation](http://transposh.org/redir/oht)
* Use either Google Translate,MS Translate or Apertium backends - 67 languages supported!
* Automatic translation can be triggered on demand by the readers or on the server side
* RSS feeds are translated too
* Takes care of hidden elements, link tags, meta contents and titles
* Translated languages are searchable
* Buddypress integration

***Our goals:***

* **Performance** - very fast - using APC cache if available
* **Support** - you want it - we'll implement it, just visit our [development site](http://trac.transposh.org "ticket system")
* **Security** - we have externally audited the plugin for improved security
* **Ease of Use** - making translation as fun and as easy as possible
* **Flexibility** - allowing you to take control of the user experience
* **SEO** - search engines exposure increase

Technology has been thoroughly tested on a large dynamic site with millions of monthly page views. Feel free to visit [Colnect](http://colnect.com "website for collectors"), the best site for collectors.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add the widget to your side bar, decide which interface you prefer
1. On the settings page - define which languages you would like viewable, translatable and which language is currently used (default) by clicking and dragging
1. You may also elect to disable/enable the automatic translation mode  
1. You are good to go

== Frequently Asked Questions ==

= How come this plugin is so awesome? =

We are working really hard to make this the best possible wordpress translation plugin available, feedback from the community is what made the plugin so great

= Where are the real FAQs =

You can find them on our site [here](http://transposh.org/faq)

== Screenshots ==

1. This is a screen shot of Transposh home page with the flagged widget on the right sidebar
2. This is the same site, translated to Hebrew, take note that automatic RTL kicked in
3. A look at the translation interface, in Spanish, viewable is the editor window and the icons used to trigger it in the background
4. The settings page, including management of active languages and various other settings
5. Widget style selection box, with three basic appearances, flags below (in Hebrew), language selection on the top right and language list on the bottom right.

== Upgrade Notice ==
= 0.8.0 =
The widgets can have multiple instances, you need to change the current instance
If you want to use a google/msn API key, it is now possible
= 0.7.0 =
New edit interface, tons of other fixes
= 0.6.6 =
Fixed two XSS vulnerabilities
= 0.6.3 =
Support .po/.mo files
= 0.6.0 =
Much improved translation interface engine
= 0.5.7 =
Fix for critical bug in 0.5.6
= 0.5.6 =
Support pluggable widgets
= 0.5.5 =
Support for buddypress URLs
= 0.5.3 =
Support URL translation
= 0.5.2 =
Improved lang attribute support, changed default language translation option
= 0.5.1 =
Improved speed and database structure
= 0.5.0 =
Ability to translate all content, backup service for human translations
= 0.4.3 =
Minor bug fixes, more compatability
= 0.4.2 =
This version provides Haitian support, auto translate with bing support
= 0.4.0 =
This version provides integration with google-sitemaps-xml and wp-super-cache
= 0.3.9 =
This version allows sorting of languages within the widget

== Credits ==
= Translation credits =
 * Dutch translation by [Roland Nieuwendijk](http://blog.imagitect.nl/)
 * Russian translation by Romans Matusevics
 * Hebrew translation by [Amir](http://colnect.com/he)
 * Frontend Spanish translation by [Ignacio](http://colnect.com/es/collectors/collector/iflvico)
 * Italian translation by Marco Rossi
 * Persian translation by [Sushyant Zavarzadeh](http://zavarzadeh.org)
 * Spanish translation by [Angel Torregrosa](http://wordp.relatividad.org)
 * German translation by [Jenny Beelens](http://www.professionaltranslation.com)
 * Turkish translation by [Semih Yeşilyurt](http://kingdroid.net)
 * Portuguese (Brazil) translation by [Amilton Junior](http://www.dicasemgeral.com)

== Changelog ==
= 2012/12/12 - 0.9.0 =
 * Major rewrite for the administrative interface and settings, should simplify working with transposh
 * Parsing rules can now be changed in the advanced tab, use with care
 * Added the options to include debug and remote debug outputs
 * A new language selection widget based on select2
 * Added ctrl keys for quick navigation of prev/next blocks
 * A new option that allows to reset the configuration file to the recommanded defaults
 * Css fixes for twenty twelve theme
 * Avoid loading the subwidgets in the admin pages
 * Removed distinction between editable and viewable languages, now a language can only be active or disabled
 * Updating jQueriUI to 1.9.2 (jQuery should now be 1.6+) 
 * Fixes the z-index for the old style dropdown (patch by chemaz)
 * Fixes the bug with the coupling of Chinese simple and traditional
 * Fix bug preventing upgrade from very old versions
 * Suppress notices when widgets are created directly with our function 
 * Avoid rewriting urls in the default language, mainly effected canonicals
 * Our script is needed when the widget allows setting of default language 
 * Finally solved the problem with MSN translate and CR/LF 
= 2012/09/15 - 0.8.5 =
 * Support for Lao (Thanks to Google Translate)
= 2012/09/03 - 0.8.4 =
 * Integration with [One Hour Translation professional](http://transposh.org/redir/oht) translation service
 * Fixed flag of Swahili to Tanzania as noted by Ed Jordan
 * Live backup now includes a daily backup
 * Fixes to backup, seems there was a big problem with data compactation
 * Fix for a parser bug when having translate in default language following a select element
 * Fixed XSS reported by [Infern0_](​http://www.seqrity.pl)
 * Added global function to return the current language "transposh_get_current_language()"
 * Seems like Lybia has a new flag
 * Fixed widget IDs containing / so that we'll pass w3c validation
 * Updated jQueryUI to 1.8.23 to avoid conflict with jQuery 1.8 used by some themes
 * Portuguese (Brazil) translation by [Amilton Junior](http://www.dicasemgeral.com)
= 2012/05/28 - 0.8.3 =
 * Fix break in feeds with params noticed by Marco Raaphorst 
 * Maintanance button now attempts to create database tables 
 * Attempt to reduce log warnings 
 * Add support for &scaron; and other latin-1 extended chars 
 * Support inserting widgets into post as shortcode 
= 2012/03/01 - 0.8.2 =
 * Fix an error where MSN is the only engine available but is not the default engine
 * Added support for Esparanto for Google and Hmong Daw for Bing
 * Fix the z-index issue with Twenty-Twelve 
= 2011/12/12 - 0.8.1 =
 * Allow setting comment lanaguage meta data from the admin interface
 * Enable live human translations backup by default
 * Lists of languages used in Javascript are fixed and more readable
 * Improved loading for backend javascripts
 * Support the ， symbol in parsing
 * Reintroduce CORS support in our AJAX (Cross Origin Ajax)
 * Try to make sure lazyloader loads in the correct context
 * Fix calculation of batch translation size to avoid translations too large for Google
 * Fix when two jQueryUI versions are included, mainly for wordpress 3.2.x
 * Fixed widget to remove [Language] which was buggy as noted by Philip Trauring
 * Fix broken sites for users using the widget function directly
= 2011/11/28 - 0.8.0 =
 * Attack of the killer "give us your money" APIs by both Google and Bing
 * Improved Google Proxy to support working with all langugaes without key
 * Added the ability to use your own API keys (take precedence over the proxy) (Thanks [Ryan Wood](spywarehelpcenter.com) for help with Google API key)
 * Use temporary bing key if needed
 * Added Catalan and Hindi support for Bing
 * Improved translate all code and speed
 * Improved widgets platform
 * Allow multiple widget instances - each with different appearance
 * Allow setting of widget title
 * Widgets no longer post to change language, but use javascript directly
 * Dropdown widget improved css
 * Improved code reuse in javascript, better on-demand loading of required elements
 * Support for Memcached
 * Better 404 page handling (don't create new links to non-existing pages)
 * Fix caching on rackspace cloudsites
 * Many more minor fixes
 * Turkish Translation by [Semih Yeşilyurt](http://kingdroid.net)
 * Help us more (We will get ~1 promile of your adsense income, thanks!)
 * Ajax is now performed through the wp-admin ajax interface
= 2011/08/02 - 0.7.6 =
 * Added some improvements to the simple html dom from a new upstream release
 * Allow setting of a post/page language with a simple select box
 * Warn about some conditions that we can't fix and a use should probably be aware of
 * Allow collecting of anonymized statisics upon user consent
 * Fixed some minor warning notices reported by users
= 2011/06/22 - 0.7.5 =
 * Added support to 5 new indic languages - Bengali, Gujarati, Kannada, Tamil and Telugu, thanks to the support provided by Google translate
 * Added the option not to override the default locale with Transposh's default language
 * Translation interface improvements, next and prev now save changes, and the dialog will not move on the page
= 2011/06/03 - 0.7.4 =
 * Allow default language of Transposh to override the one set in WP_LANG, this allows a Wordpress MU installation in which every site can be managed in a different language
 * Fixed bug with using the only="y" parameter of the tp shortcode which made incorrect detections of source language further in parsing
 * Added German translation by [Jenny Beelens](http://www.professionaltranslation.com)
 * Added a new parameter for shortcode which provides the current language and can be used in image selection
 * Iframes should be properly corrected to the current target language (thanks deepbevel)
 * Added constants at the begining of the parser which allows some basic changes to parser behaviour wrt to numbers and punctuations
= 2011/03/24 - 0.7.3 =
 [youtube http://www.youtube.com/watch?v=X0CGgYeBiHg]

 * Shortcode support - see http://trac.transposh.org/wiki/ShortCodes
 * Make bots redirect away from edit pages
 * Avoid creation of session files for bots
 * Fix for languages that are only visible to translators
 * Fix another possible jQueryUI conflict
= 2011/03/01 - 0.7.2 =
 * Added Italian translation by Marco Rossi
 * Added Persian translation by [Sushyant Zavarzadeh](http://zavarzadeh.org)
 * Added Spanish translation by [Angel Torregrosa](http://wordp.relatividad.org)
 * Fixed some url rewriting bugs reported by [Angel Torregrosa](http://wordp.relatividad.org)
 * The two years anniversary release
= 2011/01/30 - 0.7.1 =
 * Fix excerpt for tp_language marked posts
 * Added Dutch translation by [Roland Nieuwendijk](http://blog.imagitect.nl/)
 * Added Russian translation by Romans Matusevics
 * Dramatically reduce number of database queries on translatable urls
 * Fix auto translate with no anonymous translation support for non google engines
 * Fix buddypress (and hopefully other) redirections on single activities
 * Fix regression with after post translation and translate all
= 2011/01/11 - 0.7.0 =
 [youtube http://www.youtube.com/watch?v=ktGtPb6SB34]

 * Revamped edit interface
 * Allow simple localization of interface
 * Go to previous/next translation item
 * Focus on phrase being translated
 * Allow viewing original in other language if it was translated previously by a human (thanks [Hanan](http://www.galgalyarok.org/))
 * Allow approval of auto translation
 * Support for virtual keyboard from http://www.greywyvern.com/code/javascript/keyboard
 * Support theming of interface using [themeroller](http://ui-dev.jquery.com/themeroller/) themes
 * Bad translations may be deleted using the history log dialog
 * Cleaned close confirmation dialog
 * First plugin to date to support Esperanto using Apertium (from sites in Spanish or English)
 * Allow translations of meta tags
 * Database cleanup function added
 * Changed collation of database to UTF8_bin in order to support different translations for different capitalizations (hello -> hola, Hello -> Hola)
 * Hebrew translation of front end thanks [Amir](http://colnect.com/he)
 * Spanish translation of front end thanks [Ignacio](http://colnect.com/es/collectors/collector/iflvico)
= 2010/12/17 - 0.6.7 =
 * Allow Google to attempt a retranslation if it is unable to detect source language at first attempt
 * Direct links to static files have a good chance of being answered now (thanks krizzz)
 * Ignore not viewable languages entered directly into urls
 * Fixed typo in readme.txt, we did support 59 languages already
= 2010/11/11 - 0.6.6 =
 * Added support for some more breaker html entities such as &rsquo; (thanks archon810)
 * Fixed XSS vulnerability on IE<8 (Thanks [Joshua Hansen and Scott Caveza](http://www.godaddy.com/security/website-security.aspx))
 * Integration with Google Sitemaps XML v4 beta
= 2010/10/25 - 0.6.5 =
 * Fixed Slovenian flag bug reported by anphicle
 * Added support for rel alternate in the headers - see http://googlewebmastercentral.blogspot.com/2010/09/unifying-content-under-multilingual.html
 * Fixed bug with canonical redirects and url rewritings as reported by Marco
 * Fixed a bug with translate all and after post translation which hindered their ability to work - (thanks nightsurfer [#122](http://trac.transposh.org/ticket/122))
 * Fixed json translation for buddypress stream issue - (thanks Inocima [#121](http://trac.transposh.org/ticket/121))
= 2010/10/13 - 0.6.4 =
 * Support for translation of our interface and admin pages
 * Hebrew translation for transposh
 * Add the option to disable the gettext interface
 * Fix problem with gettext collision with mailpress
 * Add support for Latin
 * Three new languages on bing translate Indonesian, Ukrainian and Vietnamese
 * This version is dedicated to Sgula, 15, 15, 15
= 2010/09/01 - 0.6.3 =
 * Support for gettext files (.po/.mo) files - see http://trac.transposh.org/wiki/UsingGetText
 * Support backend memory caching with xcache and eaccelarator in addition to apc
 * Improved caching to save resources (43% on APC) better overall performance with negative caching that is actually working
 * Tags from the tag cloud will now be translated with mass translate
 * Fix for the sneaky "not a valid plugin header" issue
 * Fix for MS translate tendency to add an extra space
 * Fixed bug with list with flags css widget preventing the view of flags
= 2010/08/09 - 0.6.2 =
 * Allow marking of complete posts in different languages (see FAQ)
 * Fixed typo in buddypress stream (thanks revonorway)
 * Allow parser processing of nested lang tags
 * Treat the noscript tag as hidden, fixes bug with buddypress (thanks [Terence](http://virtualcrowds.org))
 * Fixes to translate with non latin chars (thanks [Martin](http://www.maskaran.com))
 * Fixes to mass translate with bing translator for Chinese and Taiwanese
 * Moved functions and constants to static classes to reduce collisions (Such as with "WordPress MU Domain Mapping" plugin)
 * Added functions to remove automated translations from the database, either all or those older than 14 days
 * Shortened copyright notice in source files, and made it a bit more informative
= 2010/08/01 - 0.6.1 =
 * Makes themes that support RTL actually use that support
 * Deeper integration with buddypress, support activity stream
 * Fix for ms translate and non latin characters
 * Fix rewrite urls and url translation issue when using custom structure (eg. when suffixed with .html) (thanks [claudio](http://www.kurageart.eu))
 * Auto translation will not work in edit mode if auto translation is set to off
= 2010/07/29 - 0.6.0 =
 * Support batch translate which makes translations faster
 * No longer needs to load extra scripts for translations resulting in faster page loads
 * MSN (bing) translator no longer requires a key, just enable this at will
 * Transposh Google Proxy is now included to enable translation for Alpha level languages from Google (5 new languages supported)
 * Allow removing of Transposh logo and backlink according to [terms](http://transposh.org/logoterms)
 * Translate all now uses batch interface for faster operation, and may use both backends
 * Translate all will not try to handle non-translatable languages (such as ones added manually)
 * Fixed typo in settings page (thanks Rogelio)
 * Fixed comment posting bounce to original language (thanks Marko)
= 2010/07/11 - 0.5.7 =
 * Fix for critical bug in widget inclusion (thanks [dgrut](http://www.buyacomputer.info/))
= 2010/07/11 - 0.5.6 =
 * Pluggable widgets - read all about them on http://trac.transposh.org/wiki/WidgetWritingGuide
 * Avoid translation of trashed and draft post on translate all
 * Fix MSN as default translator and add two new languages to the list of supported languages
 * Fix bug with problematic !@require
 * Code cleanups
= 2010/06/18 - 0.5.5 =
 * Add support for buddypress URLs
 * Fix UI issues when jQuery tools were used on the page
= 2010/06/06 - 0.5.4 =
 * Fix some issues with the widget regarding url translation
 * Fix some inclusion issue with transposh_ajax.php file
= 2010/05/30 - 0.5.3 =
 * Support translation of URLs
 * Mark language used to comment
 * Improved wp-super-cache integration
 * Fixed issues with widget generating urls containing default language
 * Upgrade jQueryUI to 1.7.3
= 2010/04/11 - 0.5.2 =
 * support the google notranslate class notation (as an addition to no_translate)
 * support for lang tagging on paragraphs
 * changed translation of default language to just translate paragraphs explicitly marked with a different language
 * support the only_thislanguage class to make sure a paragraph is only displayed in a given language
= 2010/04/11 - 0.5.1 =
 * Improved database structure to support long translations
 * Improved speed by pre-fetching contents with a single mysql query (over 70% faster in some cases)
 * Fix for textarea tag bug - (thanks [timo](http://www.herbaldepecona.com/))
= 2010/03/24 - 0.5.0 =
 * Ability to backup human translation to a remote database (hosted on google appengine)
 * Ability to translate all existing content with a single click from the administration page
 * 7 more languages added to MSN translator
= 2010/02/28 - 0.4.3 =
 * Shrink even more with pre-calculating supported languages
 * Allow cross-domain posting
 * Fixed name of flags file
 * Canned remote jQuery, Async loading and footer scripts in sake of compatability, simplicity
 * Change internal variables to fit html5
 * Better mixed number support in parser, esp when $ and % is involved
= 2010/01/26 - 0.4.2 =
 * Script split, reduced to 1.5k gzipped in common scenarios
 * Haitian translation thanks to Bing translator
 * Ability to choose preferred translator, auto translate now in own section in settings page
 * Revert to new post format for translations, remove old format code
 * Fix bug with admin side translation on slow connections
= 2010/01/13 - 0.4.1 =
 * Fixed a few redirection bugs (administrative pages, and referred pages)
 * Fixed documentation bug (regarding google-sitemap-generator patch requirement)
 * Support on demand and async loading of transposh script
 * Using google closure compiler to reduce script size (now only 3k when gzipped)
 * Script now passes jslint.com (almost)
 * Css optimizations and reduction in number of file requests in most scenarios
= 2010/01/01 - 0.4.0 =
 * Solve activation/deactivation bug
 * Parser provides statistics in meta tag
 * Integration with google-sitemaps-xml plugin (3.2.2)
 * Integration with wp-super-cache (0.9.8)
= 2009/12/26 - 0.3.9 =
 * New languages interface, users can now sort languages on their widget
 * anonymous translation is now on by default (for new installations)
 * Changed the post option so it would just work (no need for the alternate settings)
 * updated screenshots and FAQ
= 2009/12/20 - 0.3.8 =
 * Add language detection and default language settings
 * Fix wrong inclusions of css and js (thanks [Kevin Hart](http://gainesvillecomputer.com/))
 * Fix RSS subscription links (thanks [Kevin Hart](http://gainesvillecomputer.com/))
 * Fix rel=canonical just in time for 2.9.0 (thanks [Kevin Hart](http://gainesvillecomputer.com/))
= 2009/12/06 - 0.3.7 =
 * Fix feed parsing
 * Fix issue with parsing numbers before sentence breakers
 * Change language tag in the feed (thanks [Kevin Hart](http://gainesvillecomputer.com/))
 * Fix bug with search when not using permalinks
 * Allow wrapping widget with an unordered list (thanks [Kevin Hart](http://gainesvillecomputer.com/))
 * Fix clash with other plugins using JSON_Services
= 2009/12/02 - 0.3.6 =
 * Translated language posts are now searchable with the default wordpress search box
 * Rewrite urls inside feeds so translated feeds become a much more valid option (thanks [Kevin Hart](http://gainesvillecomputer.com/))
 * Fixed transposh_widget global bug
= 2009/11/26 - 0.3.5 =
 * Enabled auto-translation to all editable languages on the admin side
 * Alternate posting methods (thanks Andre)
 * Fix documentation display regarding widgetless themes (thanks [Hosein-mec](http://linuxshare.org/))
 * Make sure simple_html_dom is not loaded twice (if we can...)
 * Large scale code refactoring
 * Migrated css flags to the widget settings
= 2009/11/05 - 0.3.4 =
 * Fix for nextgen gallery issue
 * Force LTR for wordpress blogs originating in RTL
 * Avoid loading Bing Translate javascript when it is not needed
= 2009/09/06 - 0.3.3 =
 * 9 More languages supported by google translate
 * Further compressed images with punypng (808 bytes saved!)
= 2009/08/03 - 0.3.2 =
 * Fixed issue with plugin that made login unavailable at some situations
 * Fixed issue with static first page
 * Manual translate will not make progress bar move
= 2009/07/27 - 0.3.1 =
 * Much faster caching of auto translation results on server with reduced server load
 * Mark active language in the widget for css usage
 * Fixed url code with parameters and subdirectories
= 2009/07/23 - 0.3.0 =
 * Support Bing (MSN) translator as a hinting facilitator
= 2009/07/21 - 0.2.9 =
 * Supress warning on parse_url (thanks [Mike](http://www.nostate.com/))
 * Fix the urls generated for the widget with subdir blogs (thanks [Peter](http://www.algarve-abc.de/ferienhaus-westalgarve))
 * Fix issue when object->tostring didn't work correctly (thanks [Anthony](http://gratiswork.com/))
= 2009/07/19 - 0.2.8 =
 * Don't touch XML RPC
 * Allow usage of CSS sprites when available
 * Removed use of local blank image (thanks [Marek](http://marenkuv.blogspot.com/))
 * Fixed unicode in Transposh RSS feed (Admin panel)
 * Smarter inclusion of .css files (only when needed)
 * Fixed bug with url_cleanup which prevented return to original language when blog was installed in a subdir
= 2009/06/20 - 0.2.7 =
 * Added Persian (Farsi) support to auto translation (thanks to google and Iranian "elections")
= 2009/06/17 - 0.2.6 =
 * Fixed regressions in urls reported by ([Mike](http://www.nostate.com/)) and ([Julian](http://julianmegson.com/blog/about/))
 * Will not push jQuery to the bottom, as it might conflict with other plugins
= 2009/06/16 - 0.2.5 =
 * Fixed url rewrite bug reported by ([Mike](http://www.nostate.com/))
 * Allow translation of even more hidden elements such as keywords, descriptions and stuff inside select tags
 * Updated to jQueryUI 1.7.2
= 2009/06/09 - 0.2.4 =
 * Fixed bugs with database prefixes (thanks again [Mike](http://www.nostate.com/))
 * Translation of keywords and description meta tags (thanks again [Mike](http://www.nostate.com/))
 * Fix for RSS feeds provided in other languages
 * Fixed compatibility to show support for wordpress 2.8
 * Support footer insertion of scripts in wordpress 2.8
 * Fixed issues of html entities breaking when they should not (thanks [Karl](http://www.wp-plugin-archive.de/))
 * Lang is now set in the headers for real
 * Fixed compatibility with themes using annoying query_posts with no consideration (thanks [Karl](http://www.wp-plugin-archive.de/))
= 2009/06/03 - 0.2.3 =
 * Revamped plugin setting page to a more useful one (code adapted from [code-styling.de](http://www.code-styling.de/))
 * Widget settings may be changed from settings page (thanks [Db0](http://dbzer0.com/))
 * Allow default language to be translated (for multilingual blogs) (thanks [Db0](http://dbzer0.com/))
 * Setting page shows database statistics
 * Fixed IE8 hover quirk in flags widget
 * Avoid translating admin pages (even if we can)
= 2009/05/25 - 0.2.2 =
 * Fixed wrong handling of multi-byte chars as terminators (middle dots) which caused a regression bug
= 2009/05/21 - 0.2.1 =
 * Fixed unique breaking case in parser (style used within script and not properly terminated) (thanks again Fernanda)
 * Added language list mode to widget
 * Prevent translation of url that is self wrapped
 * Added &lt;code&gt; tag to list of ignored tags (thanks again [Mike](http://www.nostate.com/))
 * Middle dot is now a separator
= 2009/05/18 - 0.2.0 =
 * Faster parser - 50% faster parsing than previous engine (thanks [Simple Html DOM](http://simplehtmldom.sourceforge.net/))
 * Hidden elements translation (mainly tooltips specified by title attribute)
 * Make sure viewable languages are translateable
 * Simplify setting page
 * Fixed various bugs (thanks [Mike](http://www.nostate.com/))
= 2009/05/07 - 0.1.7 =
 * Fix issues with IIS/Windows/Non standard installations (thanks [Fabrizio](http://www.sulmare.it/))
 * Fixed namespace conflict with more plugins (For example - Lazyest Gallery)
= 2009/05/05 - 0.1.6 =
 * Fix a problem with translating Traditional Chinese and Portuguese  (thanks Fernanda)
 * Fixed several issues with html comment tags (thanks [ekerem](http://www.top100freesoftware.com/))
= 2009/05/03 - 0.1.5 =
 * Improved end-user experience by switching order of posts and page changes
= 2009/04/30 - 0.1.4 =
 * Moved to jQuery UI instead of overlibmws
 * Reduced code generated for faster page loading
 * History is now visible for translated phrases
 * An optional progress bar shows advancing auto-translation
 * Script is now minified by default
 * Better support for not-auto-translatable languages, added islandic
= 2009/04/02 - 0.1.3 =
 * Fix for mysql 4.1 (thanks [Amit](http://landscaping-blog.com/))
= 2009/03/31 - 0.1.2 =
 * Made sure our code passes w3c validation
 * Added missing flags for two languages
 * Auto translation should always work if set (even to non translators)
= 2009/03/24 - 0.1.1 =
 * Fixed compatibility issues with other scripts (thanks [Eike](http://spotterblog.de/))
 * Fixed minor issues with encoding some strings
 * Verify UTF charset and collation upon database creation
 * Some CSS improvements
= 2009/03/22 - 0.1.0 =
 * Enabled automatic translation for site readers
 * Added many languages to the default list
 * Upgrade database for supporting translation "sources"
 * Fixed installation bug
= 2009/03/07 - 0.0.3 =
 * Added ability to get suggestions from Google Translate
 * Improved support for RSS feeds translation 
= 2009/03/01 - 0.0.2 =
 * Fixed bug with hard coded plugin path (thanks [Atomboy](http://politicalnewsblogs.com/))
 * Support for AJAX replacement of content using jQuery 
= 2009/02/28 - 0.0.1 =
 * Initial release