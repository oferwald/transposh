=== Transposh - translation filter for wordpress ===
Contributors: oferwald, amirperlman
Donate link: http://transposh.org/
Tags: translation, widget, filter, bilingual, multilingual, transposh, language, RTL, Hebrew, Spanish, French, Russian, English, Arabic, crowdsourcing, context, wiki
Requires at least: 2.7
Tested up to: 2.7.1
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

== Screenshots ==

1. This is a screen shot of a site using Transposh widget on the sidebar
2. This is the same site, translated to Hebrew
3. A look at the translation interface
4. Management of languages in the settings page
5. Widget style selection

== Release notes ==

* 2009/03/24 - 0.1.1
 * Fixed compatability issues with other scripts (thanks [Eike](http://spotterblog.de/))
 * Fixed minor issues with encoding some strings
 * Verify UTF charset and collation upon database creation
 * Some CSS improvments
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