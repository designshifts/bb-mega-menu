=== BB Mega Menu ===
Contributors: coffeemugger
Tags: menu, mega menu, navigation, blocks
Requires at least: 6.0
Requires PHP: 8.0
Tested up to: 6.9
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mega menu CPT and frontend behavior for classic menus and the Navigation block.

== Description ==
BB Mega Menu makes building complex, flexible mega menus feel just like working with WordPress content.

The plugin adds a Mega Menu custom post type. Each Mega Menu is built using the block editor, giving you full access to any WordPress block: Columns, Groups, Images, Buttons, Lists, Media & Text, Covers, and more. There are no layout restrictions and no proprietary UI to learn.

To activate a Mega Menu, simply create a Mega Menu post and give it the same title as a menu item in your site’s classic menu or Navigation block. When the labels match, BB Mega Menu automatically converts that menu item into a button and injects the Mega Menu’s block content directly into the menu markup on the frontend.

This approach keeps menus content-driven, reusable, and easy to maintain, while letting designers and editors use familiar WordPress tools to create rich navigation experiences tailored to their customers.

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

= Does this work with classic menus? =
Yes. Classic menu support targets the `primary` theme location and works with the default WordPress menu walker. If a theme uses a custom walker that bypasses the `walker_nav_menu_start_el` filter, the mega menu injection won’t run and the theme may need a small adjustment.

= Where do I configure the settings? =
Go to Appearance → BB Mega Menu.

= What settings are available? =
Header / Nav Height Offset, Panel Padding, Z-index, Enable Default Styling, Panel Background, Panel Shadow, and Transition Speed (ms).

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

= 1.0.1 =
Added missing deploy file

= 1.0.2 =
Added the correct banner size

= 1.0.3 =
* Improved plugin description and documentation clarity.
* Updated plugin banner

= 1.0.4 =
* redesigned plugin banners and icon