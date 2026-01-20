<?php
/**
 * Mega menu behavior for classic menus and navigation blocks.
 *
 * @package BB_Mega_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles mega menu CPT registration and front-end rendering.
 */
final class BB_Mega_Menu {
	/**
	 * Theme menu location to target for classic menus.
	 */
	private const MENU_LOCATION = 'primary';

	/**
	 * Cached mega menu IDs keyed by title.
	 *
	 * @var array<string,int>
	 */
	private $mega_menu_cache = array();

	/**
	 * Wire up hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ), 20 );
		add_action( 'wp_loaded', array( $this, 'build_mega_menu_cache' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'wp_nav_menu_args', array( $this, 'limit_menu_depth' ) );
		add_filter( 'nav_menu_css_class', array( $this, 'menu_item_classes' ), 10, 4 );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'display_mega_menus' ), 10, 4 );
		add_filter( 'render_block', array( $this, 'inject_navigation_block_mega_menu' ), 10, 2 );
	}

	/**
	 * Register Mega Menu CPT.
	 *
	 * @return void
	 */
	public function register_cpt(): void {
		$labels = array(
			'name'          => 'Mega Menus',
			'singular_name' => 'Mega Menu',
			'add_new'       => 'Add New',
			'add_new_item'  => 'Add New Mega Menu',
			'edit_item'     => 'Edit Mega Menu',
			'new_item'      => 'New Mega Menu',
			'view_item'     => 'View Mega Menu',
			'not_found'     => 'No Mega Menus found',
			'menu_name'     => 'Mega Menus',
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'custom-fields', 'revisions' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'themes.php',
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-editor-table',
		);

		register_post_type( 'megamenu', apply_filters( 'bb_mega_menu_post_type_args', $args ) );
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$css_path = plugin_dir_path( __FILE__ ) . 'assets/css/mega-menu.css';
		$js_path  = plugin_dir_path( __FILE__ ) . 'assets/js/mega-menu.js';

		wp_enqueue_style(
			'bb-mega-menu',
			plugin_dir_url( __FILE__ ) . 'assets/css/mega-menu.css',
			array(),
			file_exists( $css_path ) ? filemtime( $css_path ) : '1.0.0'
		);

		wp_enqueue_script(
			'bb-mega-menu',
			plugin_dir_url( __FILE__ ) . 'assets/js/mega-menu.js',
			array(),
			file_exists( $js_path ) ? filemtime( $js_path ) : '1.0.0',
			true
		);
	}

	/**
	 * Limit menu depth for the primary location.
	 *
	 * @param array $args Menu arguments.
	 * @return array
	 */
	public function limit_menu_depth( $args ) {
		if ( self::MENU_LOCATION === $args['theme_location'] ) {
			$args['depth'] = 1;
		}
		return $args;
	}

	/**
	 * Build the mega menu cache.
	 *
	 * @return void
	 */
	public function build_mega_menu_cache(): void {
		$menus = get_posts(
			array(
				'post_type'      => 'megamenu',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		foreach ( $menus as $menu ) {
			if ( function_exists( 'icl_object_id' ) ) {
				$translation = icl_object_id( $menu->ID, 'megamenu', false );
				if ( $translation ) {
					$menu = get_post( intval( $translation ) );
				}
			}

			$this->mega_menu_cache[ $menu->post_title ] = $menu->ID;
		}
	}

	/**
	 * Add mega menu classes to classic menu items.
	 *
	 * @param array   $classes Existing classes.
	 * @param object  $item Menu item.
	 * @param object  $args Menu args.
	 * @param integer $depth Menu depth.
	 * @return array
	 */
	public function menu_item_classes( $classes, $item, $args, $depth ) {
		unset( $depth );

		if ( self::MENU_LOCATION !== $args->theme_location ) {
			return $classes;
		}

		if ( in_array( 'menu-item-has-children', $classes, true ) ) {
			$classes = array_diff( $classes, array( 'menu-item-has-children' ) );
		}

		if ( isset( $this->mega_menu_cache[ $item->title ] ) ) {
			$menu_id   = $this->mega_menu_cache[ $item->title ];
			$classes[] = 'menu-item-has-children';
			$classes[] = 'has-mega-menu';
			$classes[] = 'megamenu-' . $menu_id;
		}

		return $classes;
	}

	/**
	 * Inject mega menu content into classic menu item output.
	 *
	 * @param string $item_output Menu item output.
	 * @param object $item Menu item object.
	 * @param int    $depth Menu depth.
	 * @param object $args Menu args.
	 * @return string
	 */
	public function display_mega_menus( $item_output, $item, $depth, $args ) {
		if ( self::MENU_LOCATION !== $args->theme_location || 0 !== $depth ) {
			return $item_output;
		}

		$submenu_object = $this->get_cached_mega_menu_object( $item );
		if ( ! $submenu_object || is_wp_error( $submenu_object ) ) {
			return $item_output;
		}

		$menu_id = 'mega-menu-' . $item->ID;

		$item_output = $this->replace_link_with_button( $item_output, $menu_id );

		$submenu = sprintf(
			'<div class="mega-menu" id="%1$s" role="region" aria-label="%2$s Mega Menu" hidden><div class="wrap">%3$s</div></div>',
			esc_attr( $menu_id ),
			esc_html( $item->title ),
			apply_filters( 'the_content', $submenu_object->post_content )
		);

		return preg_replace( '/(<\/button>|<\/a>)/', '$1' . $submenu, $item_output, 1 );
	}

	/**
	 * Inject mega menu content into navigation blocks.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block Block data.
	 * @return string
	 */
	public function inject_navigation_block_mega_menu( string $block_content, array $block ): string {
		if ( empty( $block['blockName'] ) || 'core/navigation-link' !== $block['blockName'] ) {
			return $block_content;
		}

		$label = $this->get_navigation_label_from_block( $block, $block_content );
		if ( '' === $label ) {
			return $block_content;
		}

		$submenu_object = $this->get_cached_mega_menu_by_label( $label );
		if ( ! $submenu_object || is_wp_error( $submenu_object ) ) {
			return $block_content;
		}

		$menu_id       = wp_unique_id( 'mega-menu-' . $submenu_object->ID . '-' );
		$block_content = $this->add_has_mega_menu_class( $block_content );
		$block_content = $this->replace_link_with_button( $block_content, $menu_id );

		$submenu = sprintf(
			'<div class="mega-menu" id="%1$s" role="region" aria-label="%2$s Mega Menu" hidden><div class="wrap">%3$s</div></div>',
			esc_attr( $menu_id ),
			esc_html( $label ),
			apply_filters( 'the_content', $submenu_object->post_content )
		);

		return preg_replace( '/(<\/button>|<\/a>)/', '$1' . $submenu, $block_content, 1 );
	}

	/**
	 * Extract a navigation label from block data or rendered HTML.
	 *
	 * @param array  $block Block data.
	 * @param string $block_content Rendered block HTML.
	 * @return string
	 */
	private function get_navigation_label_from_block( array $block, string $block_content ): string {
		if ( isset( $block['attrs']['label'] ) && is_string( $block['attrs']['label'] ) ) {
			return trim( wp_strip_all_tags( $block['attrs']['label'] ) );
		}

		if ( preg_match( '/<a[^>]*>(.*?)<\/a>/s', $block_content, $matches ) ) {
			return trim( wp_strip_all_tags( $matches[1] ) );
		}

		if ( preg_match( '/<button[^>]*>(.*?)<\/button>/s', $block_content, $matches ) ) {
			return trim( wp_strip_all_tags( $matches[1] ) );
		}

		return '';
	}

	/**
	 * Retrieve mega menu object by label.
	 *
	 * @param string $label Navigation label.
	 * @return WP_Post|false
	 */
	private function get_cached_mega_menu_by_label( string $label ) {
		if ( isset( $this->mega_menu_cache[ $label ] ) ) {
			return get_post( intval( $this->mega_menu_cache[ $label ] ) );
		}
		return false;
	}

	/**
	 * Add has-mega-menu class to navigation list item.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @return string
	 */
	private function add_has_mega_menu_class( string $block_content ): string {
		if ( false !== strpos( $block_content, 'class=' ) ) {
			return preg_replace(
				'/class=("|\')(.*?)\1/',
				'class=$1$2 has-mega-menu$1',
				$block_content,
				1
			);
		}

		return preg_replace( '/<li(\s|>)/', '<li class="has-mega-menu"$1', $block_content, 1 );
	}

	/**
	 * Convert an anchor to a button with aria attributes.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param string $menu_id Mega menu id.
	 * @return string
	 */
	private function replace_link_with_button( string $block_content, string $menu_id ): string {
		if ( preg_match( '/<a([^>]*)>(.*?)<\/a>/s', $block_content, $matches ) ) {
			$anchor_attrs   = $matches[1];
			$anchor_content = $matches[2];
			$attributes     = array();

			if ( preg_match_all( '/(\w+)=["\']([^"\']*)["\']/', $anchor_attrs, $attr_matches, PREG_SET_ORDER ) ) {
				foreach ( $attr_matches as $attr_match ) {
					$attr_name  = $attr_match[1];
					$attr_value = $attr_match[2];
					if ( 'href' === $attr_name ) {
						continue;
					}
					$attributes[ $attr_name ] = $attr_value;
				}
			}

			$button_attrs = '';
			foreach ( $attributes as $name => $value ) {
				$button_attrs .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}

			$button_attrs .= ' type="button"';
			$button_attrs .= ' aria-haspopup="true"';
			$button_attrs .= ' aria-expanded="false"';
			$button_attrs .= ' aria-controls="' . esc_attr( $menu_id ) . '"';

			$button_output = '<button' . $button_attrs . '>' . $anchor_content . '</button>';
			return preg_replace( '/<a[^>]*>.*?<\/a>/s', $button_output, $block_content, 1 );
		}

		return $block_content;
	}

	/**
	 * Retrieve Mega Menu object from cache.
	 *
	 * @param object $item Menu item.
	 * @return WP_Post|false
	 */
	private function get_cached_mega_menu_object( $item ) {
		if ( isset( $this->mega_menu_cache[ $item->title ] ) ) {
			return get_post( intval( $this->mega_menu_cache[ $item->title ] ) );
		}
		return false;
	}
}
