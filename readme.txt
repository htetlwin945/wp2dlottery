=== Custom 2-Digit Lottery ===
Contributors: Jules
Donate link: https://example.com/
Tags: lottery, 2-digit
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 1.6.0
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

= 1.6.0 =
* Feature: Implement a Payout Request system for agents.
* Feature: Agents can request a payout once their balance exceeds a configurable threshold.
* Feature: Admins can set a global payout threshold and override it for individual agents.
* Feature: Admins can view and manage payout requests from a new "Payout Requests" page.

= 1.5.1 =
* Feature: Enhanced the Agent Payout system. Admins can now specify a payout method (Cash, Bank Transfer, etc.) and upload proof of transfer when making a payout.
* Feature: Added a new "My Commission" page for agents to view their commission history with date filtering.
* Feature: Enhanced the Agent Portal. Agents can now see the payout method and a link to the proof of transfer for their own payout transactions, improving transparency.

= 1.5.0 =
* Feature: Implement an advanced "Time-Based Entry Restriction" feature. Administrators can now set custom entry session times (opening and closing) for each individual Commission Agent. If no custom times are set for an agent, the system uses the default session times from the main plugin settings as a fallback.

= 1.4.0 =
* Refactor: Reworked the "Request Modification" feature into a direct edit proposal system. Agents can now propose specific changes to an entry's number and amount, which an admin can approve or reject. Upon approval, the original entry is automatically updated.
* Fix: Corrected a major bug where JavaScript was not loading on the Commission Agent portal pages, which prevented the "Lottery Entry" and "My Entries" pages from functioning correctly.
* Fix: Scoped the customer search AJAX handler to only return customers belonging to the logged-in agent, preventing data leakage.
* Security: Added role capability checks to all relevant AJAX handlers to improve security.

= 1.3.3 =
* Feature: The "Clear Data by Date" tool in the Tools page now also clears the winning numbers for the selected date, ensuring a more complete data reset.
* Fix: Made the winning number fetch cron job more reliable by switching from a live API endpoint to a historical one. This prevents a race condition where the cron job could run before the winning number was available on the live feed.

= 1.3.2 =
* Feature: Enhanced the "Financial Report" to include profit/loss calculation. The report now shows the actual payout and net profit/loss for a session if the winning number has been drawn.

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