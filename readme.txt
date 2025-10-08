=== Custom 2-Digit Lottery ===
Contributors: Jules
Donate link: https://example.com/
Tags: lottery, 2-digit
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A custom plugin to manage a 2-digit lottery system in WordPress.

== Description ==

This plugin provides a complete system for managing a 2-digit lottery within the WordPress admin dashboard. It includes features for lottery entry, financial reporting, payout management, customer management, and automatic number limiting.

== Installation ==

1. Upload the `custom-lottery-plugin` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the plugin by going to the "Lottery" > "Settings" page.

== Frequently Asked Questions ==

= How do I use the plugin? =

Once activated, you can access all features through the "Lottery" menu in the WordPress admin dashboard.

== Screenshots ==

1. The main dashboard view.
2. The lottery entry form.
3. The settings page.

== Changelog ==

= 1.3.1 =
* Fix: Ensured that winning entries are correctly flagged in the database before being displayed on the Payouts page. This resolves an issue where the "Mark as Paid" button would not work correctly.

= 1.3.0 =
* Feature: Added a new "Settings" page under the "Lottery" menu.
* Feature: Added a setting to configure the payout rate.
* Feature: Added settings to configure the live and historical API URLs.
* Feature: Added settings to configure the default session times.
* Feature: Changed the automatic number blocking logic to use a configurable limit instead of a dynamic calculation.
* Fix: Corrected an issue where the settings page was not appearing in the admin menu.

= 1.2.0 =
* Initial release.

== Upgrade Notice ==

= 1.3.0 =
This version introduces a new settings page and makes several key features configurable. It is recommended to review the new settings under "Lottery" > "Settings" after updating.