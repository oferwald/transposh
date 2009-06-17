=== Transposh - translation filter for wordpress ===
Contributors: oferwald, amirperlman
Donate link: http://transposh.org/
Tags: translation, widget, filter, bilingual, multilingual, transposh, language, crowdsourcing, context, wiki, RTL, Hebrew, Spanish, French, Russian, English, Arabic, Portuguese
Requires at least: 2.7
Tested up to: 2.8
Stable tag: <%VERSION%>

Transposh filter allows in context quick translation of websites, it allows you to crowd-source the translation to your users

== Description ==
Transposh translation filter for WordPress offers a unique approach to blog translation. It allows your blog to be translated by your readers in-context.

***The following features are supported:***

* Support for any language - including RTL/LTR
* Unique interface for choosing viewable/translatable languages
* Multiple selection for widget appearances
* Translation of external plugins with no changes
* Automatic translation mode for all content (including comments!)
* Fine grained control for advanced users

***We are focused on:***

* **Performance** - very fast - using APC cache if available
* **Support** - you want it - we'll implement it, visit our trac site http://trac.transposh.org
* **Security** - we have externally audited the plugin for improved security
* **Ease of Use** - making translation as fun and as easy as possible
* **SEO Optimization** - your site content will be available in all languages via search engines

Technology has been tested on a large dynamic site with millions of monthly page views. Feel free to visit [ColNect](http://colnect.com "website for collectors").

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add the widget to your side bar, decide which interface you prefer
1. On the settings page - define which languages you would like viewable, translatable and which language is currently used (default) 
1. You may also elect to disable/enable the automatic translation mode  
1. You are good to go

== Frequently Asked Questions ==

= My requested language does not appear on the list =

You can edit the constants.php and add a line to the languages array, or just contact us to add your language

= I am using the flag interface and would like to change the flag used for some language =

In the languages array in the constants.php file change the two letter ISO code used for the flag

= I have a feature to suggest =

The correct place for that would be our [development site](http://trac.transposh.com "ticket system")

= The interface looks messed up =

Another .css file might intervene with our in ways we didn't predict yet, either fix that .css alone or contact us

= What do the colors of the translation icons mean =

There are currently three colors used

* Red - no translation was done
* Yellow - translation was done by a robot
* Green - translation was done by human (or approved by one)

= Why should I allow anonymous translation =

Wiki has proven itself quite a valid model on the Internet. More people have good intentions than bad intentions and that can be
harnessed to get your message out to more people. Future versions will give more focus on preventing spammers from defacing sites

= I have installed the plugin - nothing happens =

By default, automatic translation is on and it should be kicking in. If its off, and you don’t have enough privileges to translate, nothing will happen.

Please be reminded of the following “rules”

1. A language marked as viewable will have its flag shown inside the widget.
1. A language marked for editing will allow a translator (anyone marked in the ‘who can translate’ section) to manually edit the page. i.e. the translate check-box will appear in the widget.
1. Enabling automatic translation will automatically translate a page (without requiring entering edit mode) for EVERYONE viewing the page regardless of the their role. However it will only take place for languages marked as editable.

Also - please take extra care to validate your html, adding extra tags that are unclosed in the template may lead to our parser breaking. Use the w3c validator service for more details. If everything is setup correctly and still nothing happens, please contact us.

= How can I add the plugin interface without using the sidebar widget? =

Just add the following line to your template:

&lt;?php if(function_exists("transposh_widget")) { transposh_widget(array()); } ?&gt;

== Screenshots ==

1. This is a screen shot of a site using Transposh widget on the sidebar
2. This is the same site, translated to Hebrew
3. A look at the translation interface
4. Management of languages in the settings page
5. Widget style selection

== Release notes ==
* 2009/06/17 - 0.2.6
 * Fixed regressions in urls reported by ([Mike](http://www.nostate.com/)) and ([Julian](http://julianmegson.com/blog/about/))
 * Will not push jQuery to the bottom, as it might conflict with other plugins
* 2009/06/16 - 0.2.5
 * Fixed url rewrite bug reported by ([Mike](http://www.nostate.com/))
 * Allow translation of even more hidden elements such as keywords, descriptions and stuff inside select tags
 * Updated to jQueryUI 1.7.2
* 2009/06/09 - 0.2.4
 * Fixed bugs with database prefixes (thanks again [Mike](http://www.nostate.com/))
 * Translation of keywords and description meta tags (thanks again [Mike](http://www.nostate.com/))
 * Fix for RSS feeds provided in other languages
 * Fixed compatability to show support for wordpress 2.8
 * Support footer insertion of scripts in wordpress 2.8
 * Fixed issues of html entities breaking when they should not (thanks [Karl](http://www.wp-plugin-archive.de/))
 * Lang is now set in the headers for real
 * Fixed compatability with themes using annoying query_posts with no consideration (thanks [Karl](http://www.wp-plugin-archive.de/))
* 2009/06/03 - 0.2.3
 * Revamped plugin setting page to a more useful one (code adapted from [code-styling.de](http://www.code-styling.de/))
 * Widget settings may be changed from settings page (thanks [Db0](http://dbzer0.com/))
 * Allow default language to be translated (for multilingual blogs) (thanks [Db0](http://dbzer0.com/))
 * Setting page shows database statistics
 * Fixed IE8 hover quirk in flags widget
 * Avoid translating admin pages (even if we can)
* 2009/05/25 - 0.2.2
 * Fixed wrong handling of multy-byte chars as terminators (middle dots) which caused a regression bug
* 2009/05/21 - 0.2.1
 * Fixed unique breaking case in parser (style used within script and not properly terminated) (thanks again Fernanda)
 * Added language list mode to widget
 * Prevent translation of url that is self wrapped
 * Added &lt;code&gt; tag to list of ignored tags (thanks again [Mike](http://www.nostate.com/))
 * Middle dot is now a seperator
* 2009/05/18 - 0.2.0
 * Faster parser - 50% faster parsing than previous engine (thanks [Simple Html DOM](http://simplehtmldom.sourceforge.net/))
 * Hidden elements translation (mainly tooltips specified by title attribute)
 * Make sure viewable languages are translateable
 * Simplify setting page
 * Fixed various bugs (thanks [Mike](http://www.nostate.com/))
* 2009/05/07 - 0.1.7
 * Fix issues with IIS/Windows/Non standard installations (thanks [Fabrizio](http://www.sulmare.it/))
 * Fixed namespace conflict with more plugins (For example - Lazyest Gallery)
* 2009/05/05 - 0.1.6
 * Fix a problem with translating Traditional Chinese and Portuguese  (thanks Fernanda)
 * Fixed several issues with html comment tags (thanks [ekerem](http://www.top100freesoftware.com/))
* 2009/05/03 - 0.1.5
 * Improved end-user experience by switching order of posts and page changes
* 2009/04/30 - 0.1.4
 * Moved to jQuery UI instead of overlibmws
 * Reduced code generated for faster page loading
 * History is now visible for translated phrases
 * An optional progress bar shows advancing auto-translation
 * Script is now minified by default
 * Better support for not-auto-translatable languages, added islandic
* 2009/04/02 - 0.1.3
 * Fix for mysql 4.1 (thanks [Amit](http://landscaping-blog.com/))
* 2009/03/31 - 0.1.2
 * Made sure our code passes w3c validation
 * Added missing flags for two languages
 * Auto translation should always work if set (even to non translators)
* 2009/03/24 - 0.1.1
 * Fixed compatibility issues with other scripts (thanks [Eike](http://spotterblog.de/))
 * Fixed minor issues with encoding some strings
 * Verify UTF charset and collation upon database creation
 * Some CSS improvements
* 2009/03/22 - 0.1.0
 * Enabled automatic translation for site readers
 * Added many languages to the default list
 * Upgrade database for supporting translation "sources"
 * Fixed installation bug
* 2009/03/07 - 0.0.3
 * Added ability to get suggestions from Google Translate
 * Improved support for RSS feeds translation 
* 2009/03/01 - 0.0.2
 * Fixed bug with hard coded plugin path (thanks [Atomboy](http://politicalnewsblogs.com/))
 * Support for AJAX replacement of content using jQuery 
* 2009/02/28 - 0.0.1
 * Initial release