<?php
/**
 * Uninstall cleanup for BB Mega Menu.
 *
 * @package BB_Mega_Menu
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bb_mega_menu_settings' );
