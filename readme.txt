=== S2J Media Library Date Corrector ===
Contributors: stein2nd
Tags: media, library, date, attachment
Requires at least: 6.3
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 1.0.13
License: GPL v3 (or later)
License URI: https: //www.gnu.org/licenses/gpl-3.0.html

Correct attachment post_date values from the uploads パス year/month on the media library list screen.

== Description ==

After bulk media registration, files may live under uploads/yyyy/mm while post_date stays at the upload time. This plugin reads `_wp_attached_file`, compares パス year/month to post_date, and lets you correct mismatches from the media library list view.

== Installation ==

1. Upload the plugin zip via Plugins > Add New > Upload Plugin, or copy the folder into wp-content/plugins/.
2. Activate the plugin through the Plugins menu.
3. Open Media > Library (list view) to use Date Correct actions.

== Changelog ==

See CHANGELOG.md in the plugin repository.
