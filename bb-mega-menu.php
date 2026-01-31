<?php
/**
 * Plugin Name: BB Mega Menu
 * Plugin URI: https://betterbuilds.app
 * Description: Mega menu post type and front-end behavior for main menu dropdowns.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Anderson
 * Author URI: https://www.linkedin.com/in/chrisandersondesigns/
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

$bb_mega_menu_instance = new BB_Mega_Menu();

add_action( 'bb_core_register', 'bb_mega_menu_register_core_panel' );

/**
 * Get the plugin instance for integration callbacks.
 *
 * @return BB_Mega_Menu|null
 */
function bb_mega_menu_get_instance() {
	global $bb_mega_menu_instance;
	return ( $bb_mega_menu_instance instanceof BB_Mega_Menu ) ? $bb_mega_menu_instance : null;
}

/**
 * Register the plugin in Better Builds Core.
 *
 * @return void
 */
function bb_mega_menu_register_core_panel(): void {
	if ( ! function_exists( 'bb_core_register_plugin' ) ) {
		return;
	}

	$instance = bb_mega_menu_get_instance();
	if ( ! $instance ) {
		return;
	}

	bb_core_register_plugin(
		array(
			'slug'  => 'bb-mega-menu',
			'label' => __( 'Mega Menu', 'bb-mega-menu' ),
			'icon'  => 'menu',
			'pages' => array(
				'settings' => array( $instance, 'render_settings_page' ),
			),
		)
	);
}
