=== Plugin Name ===
Contributors: mklacroix, davidanderson
Tags: metaslider,addon,hide slides,schedule slides
Requires at least: 4.0
Tested up to: 5.3
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schedule date and time show / hide for your meta slider slides

== Description ==

**This plugin is an addon and requires [MetaSlider](https://wordpress.org/plugins/ml-slider)**

**Features**

* Adds a "Schedule" tab to each slide, with start / end date and time
* Adds a "Hide slide" checkbox, to easily prevent a slide from being displayed, without having to delete it.
* [NEW] Adds a Admin Title field, for an easier organization/quick review of the slides. 

**Translations**

* English
* French

== Installation ==

The easy way:

1. Go to the Plugins Menu in WordPress
1. Search for "MetaSlider Schedule Slides"
1. Click "Install"

The not so easy way:

1. Upload the `meta-slider-schedule-slides` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Manage your slideshows using the 'MetaSlider' menu option

== Frequently Asked Questions ==

== Screenshots ==

1. Adds a 'hide slide' checkbox (1) and a new tab (2) to schedule the slide
2. The 'Schedule' tab contains the start and end date as well as a checkbox to activate / deactivate the schedule.

== Changelog ==

= 1.0.4 [10/09/18] =

* TWEAK: Improved compatibility with MetaSlider Pro

= 1.0.3 [15/06/17] =

* Added an Admin title field for a better slides organization.

= 1.0.2 [17/03/17] =

* Fixed date issue. Used ’date()’ which returns UTC date  instead of wp own ’current_time()’ that returns the correct timezone.  

= 1.0.1 [27/12/16] =

* Fixed initial bug in query, causing not to display any slide when user had not saved slider at least once after installing plugin. 

= 1.0 [26/12/16] =

* Initial version
