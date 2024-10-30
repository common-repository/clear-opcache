=== Clear OPcache ===
Tags: opcache, wincache, flush, clear
Requires at least: 5.1
Tested up to: 5.2
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Flush PHP OPcache and WinCache with the click of a button and automatically before WordPress updates.

== Description ==

Clear OPcache is a plugin that makes managing OPcache simple. When you install Clear OPcache, it puts a button in the top admin bar named 'Flush OPcache'. When that button is clicked, it clears OPcache and WinCache if they are enabled. When the plugin is installed, it also clears the OPcache before WordPress is updated. This plugin is useful for saving space and speeding up your site.

When installed, the plugin also creates an entry in the Settings menu named OPcache where you can view useful stats.

== Changelog ==

= 0.5 =

* Added error notice that displays upon plugin activation if OPcache isn't enabled
* Fixed bug where error would display if opcache_get_status function didn't exist

= 0.4 =

* Added notice that displays upon plugin activation

= 0.3 =

* Added OPcache page in Settings menu showing OPcache details
* Added popup saying OPcache was cleared or if OPcache is disabled
