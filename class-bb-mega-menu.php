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
	private const SETTINGS_KEY  = 'bb_mega_menu_settings';
	private const SETTINGS_PAGE = 'bb-mega-menu-settings';
	private const SETTINGS_PARENT = 'themes.php';

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
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'wp_nav_menu_args', array( $this, 'limit_menu_depth' ) );
		add_filter( 'nav_menu_css_class', array( $this, 'menu_item_classes' ), 10, 4 );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'display_mega_menus' ), 10, 4 );
		add_filter( 'render_block', array( $this, 'inject_navigation_block_mega_menu' ), 10, 2 );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
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
		$base_css_path  = plugin_dir_path( __FILE__ ) . 'assets/css/mega-menu-base.css';
		$theme_css_path = plugin_dir_path( __FILE__ ) . 'assets/css/mega-menu-theme.css';
		$js_path  = plugin_dir_path( __FILE__ ) . 'assets/js/mega-menu.js';
		$settings = $this->get_settings();

		wp_enqueue_style(
			'bb-mega-menu-base',
			plugin_dir_url( __FILE__ ) . 'assets/css/mega-menu-base.css',
			array(),
			file_exists( $base_css_path ) ? filemtime( $base_css_path ) : '1.0.0'
		);

		if ( ! empty( $settings['use_default_styling'] ) ) {
			wp_enqueue_style(
				'bb-mega-menu-theme',
				plugin_dir_url( __FILE__ ) . 'assets/css/mega-menu-theme.css',
				array( 'bb-mega-menu-base' ),
				file_exists( $theme_css_path ) ? filemtime( $theme_css_path ) : '1.0.0'
			);
		}

		$inline_css = sprintf(
			':root{--bb-mm-header-offset:%1$s;--bb-mm-z:%2$d;--bb-mm-max-width:%3$s;--bb-mm-panel-padding:%4$s;--bb-mm-panel-bg:%5$s;--bb-mm-panel-shadow:%6$s;--bb-mm-transition:%7$dms;}',
			esc_attr( $settings['header_offset'] ),
			(int) $settings['z_index'],
			'100%',
			esc_attr( $settings['panel_padding'] ),
			esc_attr( $settings['panel_bg'] ),
			esc_attr( $this->get_shadow_value( $settings['panel_shadow'] ) ),
			(int) $settings['transition_ms']
		);

		wp_add_inline_style( 'bb-mega-menu-base', $inline_css );

		wp_enqueue_script(
			'bb-mega-menu',
			plugin_dir_url( __FILE__ ) . 'assets/js/mega-menu.js',
			array(),
			file_exists( $js_path ) ? filemtime( $js_path ) : '1.0.0',
			true
		);
	}

	/**
	 * Add body class for styling toggle.
	 *
	 * @param array $classes Existing classes.
	 * @return array
	 */
	public function add_body_class( array $classes ): array {
		$settings = $this->get_settings();
		if ( ! empty( $settings['use_default_styling'] ) ) {
			$classes[] = 'bb-mm-default-styling-on';
		} else {
			$classes[] = 'bb-mm-default-styling-off';
		}
		return $classes;
	}

	/**
	 * Register settings page under Appearance.
	 *
	 * @return void
	 */
	public function register_settings_page(): void {
		add_submenu_page(
			self::SETTINGS_PARENT,
			__( 'BB Mega Menu', 'bb-mega-menu' ),
			__( 'BB Mega Menu', 'bb-mega-menu' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'bb_mega_menu_settings_group',
			self::SETTINGS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);

		add_settings_section(
			'bb_mega_menu_settings_layout',
			__( 'Layout', 'bb-mega-menu' ),
			'__return_false',
			self::SETTINGS_PAGE
		);

		$this->add_setting_field( 'header_offset', __( 'Header / Nav Height Offset', 'bb-mega-menu' ), 'text', 'bb_mega_menu_settings_layout' );
		$this->add_setting_field( 'panel_padding', __( 'Panel Padding', 'bb-mega-menu' ), 'text', 'bb_mega_menu_settings_layout' );
		$this->add_setting_field( 'z_index', __( 'Z-index', 'bb-mega-menu' ), 'number', 'bb_mega_menu_settings_layout' );

		add_settings_section(
			'bb_mega_menu_settings_appearance',
			__( 'Appearance', 'bb-mega-menu' ),
			'__return_false',
			self::SETTINGS_PAGE
		);

		$this->add_setting_field( 'use_default_styling', __( 'Enable Default Styling', 'bb-mega-menu' ), 'checkbox', 'bb_mega_menu_settings_appearance' );
		$this->add_setting_field( 'panel_bg', __( 'Panel Background', 'bb-mega-menu' ), 'text', 'bb_mega_menu_settings_appearance' );
		$this->add_setting_field( 'panel_shadow', __( 'Panel Shadow', 'bb-mega-menu' ), 'select', 'bb_mega_menu_settings_appearance', $this->get_shadow_options() );

		add_settings_section(
			'bb_mega_menu_settings_behavior',
			__( 'Behavior', 'bb-mega-menu' ),
			'__return_false',
			self::SETTINGS_PAGE
		);

		$this->add_setting_field( 'transition_ms', __( 'Transition Speed (ms)', 'bb-mega-menu' ), 'number', 'bb_mega_menu_settings_behavior' );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'BB Mega Menu Settings', 'bb-mega-menu' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'bb_mega_menu_settings_group' );
				do_settings_sections( self::SETTINGS_PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a settings field.
	 *
	 * @param string $key Option key.
	 * @param string $label Field label.
	 * @param string $type Field type.
	 * @param string $section Section id.
	 * @param array  $options Select options.
	 * @return void
	 */
	private function add_setting_field( string $key, string $label, string $type, string $section, array $options = array() ): void {
		$descriptions = array(
			'header_offset'       => __( 'Top offset for the mega menu panel (e.g. 60px or 4rem).', 'bb-mega-menu' ),
			'panel_padding'       => __( 'Inner padding for the mega menu panel (e.g. 24px).', 'bb-mega-menu' ),
			'z_index'             => __( 'Stacking order for the mega menu panel.', 'bb-mega-menu' ),
			'use_default_styling' => __( 'Enable simple default styling (caret + padding).', 'bb-mega-menu' ),
			'panel_bg'            => __( 'Background color for the mega menu panel.', 'bb-mega-menu' ),
			'panel_shadow'        => __( 'Optional shadow preset for the panel.', 'bb-mega-menu' ),
			'transition_ms'       => __( 'Transition speed in milliseconds.', 'bb-mega-menu' ),
		);
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $type, $options, $descriptions ) {
				$settings = $this->get_settings();
				$value    = $settings[ $key ] ?? '';

				if ( 'checkbox' === $type ) {
					printf(
						'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /></label>',
						esc_attr( self::SETTINGS_KEY ),
						esc_attr( $key ),
						checked( (bool) $value, true, false )
					);
					if ( isset( $descriptions[ $key ] ) ) {
						printf( '<p class="description">%s</p>', esc_html( $descriptions[ $key ] ) );
					}
					return;
				}

				if ( 'select' === $type ) {
					echo '<select name="' . esc_attr( self::SETTINGS_KEY ) . '[' . esc_attr( $key ) . ']">';
					foreach ( $options as $option_value => $option_label ) {
						printf(
							'<option value="%1$s" %2$s>%3$s</option>',
							esc_attr( $option_value ),
							selected( $value, $option_value, false ),
							esc_html( $option_label )
						);
					}
					echo '</select>';
					if ( isset( $descriptions[ $key ] ) ) {
						printf( '<p class="description">%s</p>', esc_html( $descriptions[ $key ] ) );
					}
					return;
				}

				printf(
					'<input type="%1$s" class="regular-text" name="%2$s[%3$s]" value="%4$s" />',
					esc_attr( $type ),
					esc_attr( self::SETTINGS_KEY ),
					esc_attr( $key ),
					esc_attr( $value )
				);
				if ( isset( $descriptions[ $key ] ) ) {
					printf( '<p class="description">%s</p>', esc_html( $descriptions[ $key ] ) );
				}
			},
			self::SETTINGS_PAGE,
			$section
		);
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param array $input Raw settings.
	 * @return array
	 */
	public function sanitize_settings( array $input ): array {
		$defaults = $this->get_default_settings();
		$output   = $defaults;

		$output['header_offset'] = $this->sanitize_css_size( $input['header_offset'] ?? $defaults['header_offset'], $defaults['header_offset'] );
		$output['max_width']     = $defaults['max_width'];
		$output['panel_padding'] = $this->sanitize_css_size( $input['panel_padding'] ?? $defaults['panel_padding'], $defaults['panel_padding'] );
		$output['panel_bg']      = $this->sanitize_color( $input['panel_bg'] ?? $defaults['panel_bg'] );
		$output['panel_shadow']  = $this->sanitize_shadow( $input['panel_shadow'] ?? $defaults['panel_shadow'] );
		$output['transition_ms'] = $this->sanitize_int_range( $input['transition_ms'] ?? $defaults['transition_ms'], 0, 2000 );
		$output['z_index']       = $this->sanitize_int_range( $input['z_index'] ?? $defaults['z_index'], 1, 999999 );
		$output['use_default_styling'] = ! empty( $input['use_default_styling'] ) ? 1 : 0;

		return $output;
	}

	/**
	 * Get merged settings with defaults.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		$defaults = $this->get_default_settings();
		$settings = get_option( self::SETTINGS_KEY, array() );
		return array_merge( $defaults, is_array( $settings ) ? $settings : array() );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function get_default_settings(): array {
		return array(
			'header_offset'       => '0px',
			'z_index'             => 9999,
			'max_width'           => '100%',
			'panel_padding'       => '24px',
			'panel_bg'            => '#ffffff',
			'panel_shadow'        => 'none',
			'transition_ms'       => 200,
			'use_default_styling' => 1,
		);
	}

	/**
	 * Allow limited CSS size values.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function sanitize_css_size( string $value, string $fallback ): string {
		$value = trim( $value );
		if ( preg_match( '/^\d+(\.\d+)?(px|rem|em|vh|vw|%)$/', $value ) ) {
			return $value;
		}
		return $fallback;
	}

	/**
	 * Sanitize color values.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function sanitize_color( string $value ): string {
		$value = trim( $value );
		if ( 'transparent' === $value ) {
			return 'transparent';
		}
		return sanitize_hex_color( $value ) ?: '#ffffff';
	}

	/**
	 * Sanitize shadow selection.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function sanitize_shadow( string $value ): string {
		$options = $this->get_shadow_options();
		return isset( $options[ $value ] ) ? $value : 'none';
	}

	/**
	 * Shadow options.
	 *
	 * @return array
	 */
	private function get_shadow_options(): array {
		return array(
			'none'   => __( 'None', 'bb-mega-menu' ),
			'subtle' => __( 'Subtle', 'bb-mega-menu' ),
			'medium' => __( 'Medium', 'bb-mega-menu' ),
		);
	}

	/**
	 * Map shadow preset to CSS value.
	 *
	 * @param string $preset Shadow preset key.
	 * @return string
	 */
	private function get_shadow_value( string $preset ): string {
		switch ( $preset ) {
			case 'medium':
				return '0 1.25rem 1.5rem rgba(0, 0, 0, 0.18)';
			case 'subtle':
				return '0 0.75rem 1rem rgba(0, 0, 0, 0.12)';
			default:
				return 'none';
		}
	}

	/**
	 * Sanitize int ranges.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $min Minimum.
	 * @param int   $max Maximum.
	 * @return int
	 */
	private function sanitize_int_range( $value, int $min, int $max ): int {
		$value = (int) $value;
		if ( $value < $min ) {
			return $min;
		}
		if ( $value > $max ) {
			return $max;
		}
		return $value;
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
