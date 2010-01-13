=== Transposh - translation filter for wordpress ===
Contributors: oferwald, amirperlman
Donate link: http://transposh.org/donate/
Tags: translation, widget, filter, bilingual, multilingual, transposh, language, crowdsourcing, context, wiki, RTL, Hebrew, Spanish, French, Russian, English, Arabic, Portuguese, translate
Requires at least: 2.7
Tested up to: 2.9.1
Stable tag: <%VERSION%>

Transposh filter allows in context quick translation of websites, it allows you to crowd-source the translation to your users

== Description ==
Transposh translation filter for WordPress offers a unique approach to blog translation. It allows your blog to combine automatic translation with human translation aided by your users with an easy to use in-context interface.

***Transposh includes the following features:***

* Support for any language - including RTL/LTR layouts
* Unique drag/drop interface for choosing viewable/translatable languages
* Multiple selection for widget appearances
* Translation of external plugins without a need for .po/.mo files
* Automatic translation mode for all content (including comments!)
* Automatic translation can be triggered on demand by the readers or on the server side
* RSS feeds are translated too
* Takes care of hidden elements, link tags and titles
* Translated languages are searchable
* Fine grained control for advanced users

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

= My requested language does not appear on the list =

You can edit the constants.php and add a line to the languages array, or just contact us to add your language

= I am using the flag interface and would like to change the flag used for some language =

In the languages array in the constants.php file change the two letter ISO code used for the flag

= I have a feature to suggest =

The correct place for that would be our [development site](http://trac.transposh.org "ticket system")

= The interface looks messed up =

Another .css file might intervene with our in ways we didn't predict yet, either fix that .css alone or contact us

= What do the colors of the translation icons mean? =

There are currently three colors used

* Red - no translation was done
* Yellow - translation was done by a robot
* Green - translation was done by human (or approved by one)

= What are the colors in the administrative interface? =

Three background colors are used
* Green - this language is active and all users will see it
* Yellow - only users with translation capability can see this language (this is disabled once anonymous translation is available)
* Blank - language won't appear on widgets

= What is the dragging of languages for? =

You may drag the languages in order to set the order in which they appear in the widget, you can use the sorting links below
which will sort the languages and put the default language first. Dragging also is used to select the default language.

= Why should I allow anonymous translation? =

Wiki has proven itself quite a valid model on the Internet. More people have good intentions than bad intentions and that can be
harnessed to get your message out to more people. Future versions will give more focus on preventing spammers from defacing sites

= I have installed the plugin - nothing happens =

By default, automatic translation is on and it should be kicking in. If its off, and you don’t have enough privileges to translate, nothing will happen.

Please be reminded of the following “rules of thumb”

1. A language marked as viewable will have its flag shown inside the widget.
1. A language marked for editing will allow a translator (anyone marked in the ‘who can translate’ section) to manually edit the page. i.e. the translate check-box will appear in the widget.
1. Enabling automatic translation will automatically translate a page (without requiring entering edit mode) for EVERYONE viewing the page regardless of the their role. However it will only take place for languages marked as editable.

Also - please take extra care to validate your html, adding extra tags that are unclosed in the template may lead to our parser breaking. Use the w3c validator service for more details. If everything is setup correctly and still nothing happens, please contact us.

= I have installed the plugin - nothing happens - themes related =

The plugin works on one theme yet seems to fail on another. This might be caused by themes which don't include the wp_head and/or wp_foot
functions so the transposh.js file is not being included, try to include it manually by modifying your theme

= How can I add the plugin interface without using the sidebar widget? =

Just add the following line to your template:

`<?php if(function_exists("transposh_widget")) { transposh_widget(); }?>`

= Plugin support: php speedy (http://aciddrop.com/php-speedy/) =

Users of php speedy will have to deactivate it, add “transposh.js” in the ignore list, click on “Test configuration” then reactivate it.

= Plugin support: Google-Sitemaps-XML =

Currently the plugin is able to add the multilingual urls to the sitemap, and you need to add the following line at the sitemap-core.php, add-url function (line 1509 at version 3.2.2)

`do_action('sm_addurl', &$page);`

We hope that future versions will include this by default, and for now you can get the patched file from our site.
After a change of languages used, you are welcomed to trigger a new sitemap buildup.

= Plugin support: WP-Super-Cache =

The support for wp-super-cache includes the invalidation of cached pages after a translation is made, which should reduce the issue with incorrect pages being displayed and
redundant calls to the machine translation agent. After a change in the widget layout or the language list you are still expected to invalidate your cache.

= I am getting weird errors =

Please make sure you are using PHP5 and up, PHP4 is not supported

= I want my own css image with less flags =

This is on our todo list

= css flags have issues on IE6 for my users =

First, there's always the ability to use another option for the plugin which is more compatible, such as the selection box. Second, you can
change the .css from transparent background to your page background color. And last - we urge anyone using IE6 to upgrade...

= How can I prevent certain text from being translated? =

You can wrap the element with the "no_translate" class, or add a span similar to `<span class="no_translate">`

= Can I make different images appear in different languages in my themes? =

Yes, although a bit tricky - you can use the `$my_transposh_plugin->target_language` as part of the image descriptor, this will load different
images based on the current language

== Screenshots ==

1. This is a screen shot of Transposh home page with the flagged widget on the right sidebar
2. This is the same site, translated to Hebrew, take note that automatic RTL kicked in
3. A look at the translation interface, in Spanish, viewable is the editor window and the icons used to trigger it in the background
4. The settings page, including management of active languages and various other settings
5. Widget style selection box, with three basic appearances, flags below (in Hebrew), language selection on the top right and language list on the bottom right.

== Upgrade Notice ==
= 0.4.0 =
This version provides integration with google-sitemaps-xml and wp-super-cache
= 0.3.9 =
This version allows sorting of languages within the widget

== Changelog ==
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
 * Fixed wrong handling of multy-byte chars as terminators (middle dots) which caused a regression bug
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