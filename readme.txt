=== Open Hours and Holiday Closures ===
Contributors: shoaibzain
Tags: business hours, open now, store hours, holiday hours, office hours
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show open or closed status, weekly business hours, holiday overrides, and temporary closure notices. Works with shortcode, block, and Elementor.

== Description ==

**Open Hours and Holiday Closures** helps local businesses show accurate business hours without installing a booking system.

Features:
* Live open or closed status with next opening time
* Weekly business hours with up to two time ranges per day
* Holiday and special-date overrides
* Temporary closure notices
* Dynamic Gutenberg block
* Shortcode: `[ohhc_open_hours]`
* Optional Elementor widget when Elementor is active

== Installation ==

1. Upload the `open-hours-and-holiday-closures` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Go to `Settings > Open Hours` to configure your schedule.

== Frequently Asked Questions ==

= Do I need Elementor? =
No. Elementor support is optional.

= Is this a booking plugin? =
No. This plugin only focuses on business hours and closure messaging.

== Changelog ==

= 0.1.1 =
* Escaped rendered output variables at final echo points.
* Renamed shortcode to a prefixed tag: `[ohhc_open_hours]`.
* Renamed public helper function to a prefixed name: `ohhc_render_open_hours()`.

= 0.1.0 =
* Initial release.
