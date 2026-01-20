<?php
/**
 * Plugin Name: BB Mega Menu
 * Description: Mega menu post type and front-end behavior for main menu dropdowns.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package BB_Mega_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-bb-mega-menu.php';

new BB_Mega_Menu();
