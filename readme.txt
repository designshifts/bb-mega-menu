=== BB Mega Menu ===
Contributors: betterbuilds
Tags: menu, mega menu, navigation, blocks
Requires at least: 6.0
Requires PHP: 8.0
Tested up to: 6.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mega menu CPT and frontend behavior for classic menus and the Navigation block.

== Description ==
BB Mega Menu adds a "Mega Menu" custom post type and injects its content into classic menus and the Navigation block when a menu label matches a Mega Menu post title.

The plugin does not make external API calls and does not send data off-site. Settings are stored locally in the WordPress options table. On uninstall, the settings option is removed.

== Installation ==
1. Upload the `bb-mega-menu` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to Appearance → BB Mega Menu to adjust settings.
4. Create a Mega Menu post whose title matches a navigation item label.

== Frequently Asked Questions ==
= How do I connect a Mega Menu to a nav item? =
Create a Mega Menu post and make sure its title exactly matches a navigation item label. The plugin replaces that label’s link with a button and injects the Mega Menu content.

= Does this work with the Navigation block? =
Yes. The plugin targets `core/navigation-link` blocks and injects the Mega Menu content.

= What data does the plugin collect? =
None. No tracking or external requests are made. The only stored data is the local settings option.

== Screenshots ==
1. Settings screen under Appearance → BB Mega Menu.
2. Mega Menu panel opened on the frontend.

== Changelog ==
= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 1.0.0 =
Initial release.
