<?php
/**
 * Plugin Name: BB Mega Menu
 * Plugin URI: https://betterbuilds.app
 * Description: Mega menu post type and front-end behavior for main menu dropdowns.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Better Builds
 * Author URI: https://betterbuilds.app
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bb-mega-menu
 * Domain Path: /languages
 *
 * @package BB_Mega_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-bb-mega-menu.php';

new BB_Mega_Menu();
