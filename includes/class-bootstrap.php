<?php
namespace RamkaMega;

defined( 'ABSPATH' ) || exit;

final class Bootstrap {
	private static bool $widget_callback_seen = false;
	private static bool $widget_registered    = false;
	private static bool $category_registered  = false;
	private static bool $category_callback_seen = false;

	public static function init(): void {
		load_plugin_textdomain( 'ramka-elementor-product-mega-menu', false, dirname( plugin_basename( RAMKA_MEGA_FILE ) ) . '/languages' );

		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		require_once RAMKA_MEGA_PATH . 'includes/class-icons.php';
		require_once RAMKA_MEGA_PATH . 'includes/class-categories.php';
		require_once RAMKA_MEGA_PATH . 'includes/class-term-fields.php';
		Term_Fields::init();
		Categories::init();

		/*
		 * Register listeners unconditionally. Adding an Elementor hook does not
		 * require Elementor classes to be loaded. The widget class itself is only
		 * required inside widget(), when Elementor passes its Widgets_Manager.
		 * This avoids losing registration when Elementor finishes booting later.
		 */
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ), 20 );
		add_action( 'elementor/frontend/after_register_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( __CLASS__, 'assets' ) );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'widget' ) );

		/*
		 * Theme Builder frontend bridge. This never runs in the Elementor editor.
		 * It only touches published templates whose Elementor template type is
		 * `header`, leaving pages, posts and all editor data unchanged.
		 */
		add_filter( 'elementor/frontend/builder_content_data', array( __CLASS__, 'header_frontend_bridge' ), 20, 2 );

		// Safety net for unusual loaders that initialize Elementor before this plugin.
		add_action( 'init', array( __CLASS__, 'late_registration_check' ), 99 );
	}

	private static function elementor_available(): bool {
		return defined( 'ELEMENTOR_VERSION' ) || did_action( 'elementor/loaded' ) || class_exists( '\\Elementor\\Plugin' );
	}

	private static function woocommerce_available(): bool {
		return defined( 'WC_VERSION' ) || class_exists( '\\WooCommerce' ) || function_exists( 'WC' );
	}

	public static function notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$missing = array();
		if ( ! self::woocommerce_available() ) {
			$missing[] = 'WooCommerce';
		}
		if ( ! self::elementor_available() ) {
			$missing[] = 'Elementor';
		}
		if ( ! $missing ) {
			return;
		}

		$message = sprintf(
			/* translators: %s: comma-separated plugin names. */
			__( 'Ramka Mega Menu could not detect these active dependencies: %s.', 'ramka-elementor-product-mega-menu' ),
			implode( ', ', $missing )
		);
		$diagnostics_url = admin_url( 'tools.php?page=ramka-mega-diagnostics' );

		printf(
			'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html( $message ),
			esc_url( $diagnostics_url ),
			esc_html__( 'Open Ramka diagnostics', 'ramka-elementor-product-mega-menu' )
		);
	}

	public static function assets(): void {
		wp_register_style( 'ramka-mega-menu', RAMKA_MEGA_URL . 'assets/css/mega-menu.css', array(), RAMKA_MEGA_VERSION );
		wp_register_script( 'ramka-mega-menu', RAMKA_MEGA_URL . 'assets/js/mega-menu.js', array(), RAMKA_MEGA_VERSION, true );
	}

	/**
	 * Frontend-only assets. Header templates can be rendered after Elementor has
	 * already collected widget dependencies, so the runtime is enqueued early on
	 * the public site instead of relying only on get_*_depends().
	 */
	public static function frontend_assets(): void {
		if ( is_admin() ) {
			return;
		}

		self::assets();
		wp_enqueue_style( 'ramka-mega-menu' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'ramka-mega-menu' );
	}

	/**
	 * Keep the native Ramka widget in Elementor, but make the published Theme
	 * Builder header deterministic by handing Elementor its built-in HTML widget
	 * with the already-rendered Ramka markup. The element ID and saved settings
	 * stay in place, so existing generated selectors continue to match.
	 *
	 * @param array $data Elementor document element data.
	 * @param int   $post_id Elementor document post ID.
	 * @return array
	 */
	public static function header_frontend_bridge( $data, $post_id ): array {
		if ( ! is_array( $data ) || is_admin() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return is_array( $data ) ? $data : array();
		}

		if ( class_exists( '\\Elementor\\Plugin' ) && ! empty( \Elementor\Plugin::$instance ) && isset( \Elementor\Plugin::$instance->editor ) ) {
			try {
				if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					return $data;
				}
			} catch ( \Throwable $e ) {
				// Continue on the published frontend if edit-mode probing is unavailable.
			}
		}

		$post_id = absint( $post_id );
		if ( ! $post_id || 'header' !== (string) get_post_meta( $post_id, '_elementor_template_type', true ) ) {
			return $data;
		}

		if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
			return $data;
		}

		require_once RAMKA_MEGA_PATH . 'includes/class-widget.php';

		$walk = static function ( array $elements ) use ( &$walk ): array {
			foreach ( $elements as $index => $element ) {
				if ( ! is_array( $element ) ) {
					continue;
				}

				if ( 'widget' === ( $element['elType'] ?? '' ) && 'ramka_product_mega_menu' === ( $element['widgetType'] ?? '' ) ) {
					try {
						$widget = new Widget( $element );
						$html = $widget->render_runtime_markup();

						if ( '' !== trim( $html ) ) {
							$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
							$settings['html'] = '<!-- Ramka Theme Builder Header Frontend Bridge ' . esc_html( RAMKA_MEGA_VERSION ) . ' -->' . $html;
							$element['widgetType'] = 'html';
							$element['settings'] = $settings;
						}
					} catch ( \Throwable $error ) {
						error_log( 'Ramka header frontend bridge error: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
				}

				if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
					$element['elements'] = $walk( $element['elements'] );
				}

				$elements[ $index ] = $element;
			}

			return $elements;
		};

		return $walk( $data );
	}

	public static function widget( $manager ): void {
		self::$widget_callback_seen = true;

		if ( ! is_object( $manager ) || ! method_exists( $manager, 'register' ) ) {
			self::$widget_registered = false;
			return;
		}

		if ( method_exists( $manager, 'get_widget_types' ) && $manager->get_widget_types( 'ramka_product_mega_menu' ) ) {
			self::$widget_registered = true;
			return;
		}

		if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
			self::$widget_registered = false;
			return;
		}

		require_once RAMKA_MEGA_PATH . 'includes/class-widget.php';
		$result = $manager->register( new Widget() );
		self::$widget_registered = false !== $result;
	}

	public static function category( $elements_manager ): void {
		self::$category_callback_seen = true;

		if ( self::$category_registered || ! is_object( $elements_manager ) || ! method_exists( $elements_manager, 'add_category' ) ) {
			return;
		}

		$elements_manager->add_category(
			'ramka-elements',
			array(
				'title' => __( 'Ramka', 'ramka-elementor-product-mega-menu' ),
				'icon'  => 'eicon-menu-bar',
			)
		);
		self::$category_registered = true;
	}

	public static function late_registration_check(): void {
		if ( ! self::elementor_available() || ! class_exists( '\\Elementor\\Plugin' ) || empty( \Elementor\Plugin::$instance ) ) {
			return;
		}

		$plugin = \Elementor\Plugin::$instance;

		if ( did_action( 'elementor/elements/categories_registered' ) && ! self::$category_registered && isset( $plugin->elements_manager ) ) {
			self::category( $plugin->elements_manager );
		}

		if ( did_action( 'elementor/widgets/register' ) && ! self::widget_is_registered() && isset( $plugin->widgets_manager ) ) {
			self::widget( $plugin->widgets_manager );
		}
	}

	private static function widget_is_registered(): bool {
		if ( ! class_exists( '\\Elementor\\Plugin' ) || empty( \Elementor\Plugin::$instance ) || ! isset( \Elementor\Plugin::$instance->widgets_manager ) ) {
			return self::$widget_registered;
		}

		$manager = \Elementor\Plugin::$instance->widgets_manager;
		if ( ! method_exists( $manager, 'get_widget_types' ) ) {
			return self::$widget_registered;
		}

		$registered = (bool) $manager->get_widget_types( 'ramka_product_mega_menu' );
		self::$widget_registered = $registered;
		return $registered;
	}

	public static function admin_menu(): void {
		add_management_page(
			__( 'Ramka Mega Diagnostics', 'ramka-elementor-product-mega-menu' ),
			__( 'Ramka Mega Diagnostics', 'ramka-elementor-product-mega-menu' ),
			'activate_plugins',
			'ramka-mega-diagnostics',
			array( __CLASS__, 'diagnostics_page' )
		);
	}

	public static function diagnostics_page(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You are not allowed to view these diagnostics.', 'ramka-elementor-product-mega-menu' ) );
		}

		$widget_registered = self::widget_is_registered();
		$widget            = null;
		$category_exists   = false;
		$design_permission = 'unknown';
		$widget_filter     = 'unknown';
		$config_readable   = 'no';
		$config_error      = '';
		$config_categories = '';
		$config_show       = '';
		$config_hide       = '';
		$controls_status   = 'not checked';
		$widget_title      = '';
		$widget_class      = '';
		$safe_mode_option  = get_option( 'elementor_safe_mode', false );
		$safe_mode_status  = empty( $safe_mode_option ) ? 'disabled' : 'enabled (' . ( is_scalar( $safe_mode_option ) ? (string) $safe_mode_option : gettype( $safe_mode_option ) ) . ')';

		if ( class_exists( '\\Elementor\\Plugin' ) && ! empty( \Elementor\Plugin::$instance ) ) {
			$plugin = \Elementor\Plugin::$instance;

			if ( isset( $plugin->widgets_manager ) && method_exists( $plugin->widgets_manager, 'get_widget_types' ) ) {
				$widget = $plugin->widgets_manager->get_widget_types( 'ramka_product_mega_menu' );
			}

			if ( isset( $plugin->elements_manager ) && method_exists( $plugin->elements_manager, 'get_categories' ) ) {
				try {
					$registered_categories = $plugin->elements_manager->get_categories();
					$category_exists = isset( $registered_categories['ramka-elements'] );
				} catch ( \Throwable $e ) {
					$category_exists = false;
				}
			}

			if ( isset( $plugin->role_manager ) && method_exists( $plugin->role_manager, 'user_can' ) ) {
				try {
					$design_permission = $plugin->role_manager->user_can( 'design' ) ? 'yes' : 'no';
				} catch ( \Throwable $e ) {
					$design_permission = 'error';
				}
			}
		}

		if ( is_object( $widget ) ) {
			$widget_title = method_exists( $widget, 'get_title' ) ? (string) $widget->get_title() : '';
			$widget_class = get_class( $widget );

			try {
				$widget_filter = apply_filters( 'elementor/widgets/is_widget_enabled', true, $widget ) ? 'yes' : 'no';
			} catch ( \Throwable $e ) {
				$widget_filter = 'error';
			}

			try {
				$config = $widget->get_config();
				$config_readable = 'yes';
				$config_categories = isset( $config['categories'] ) && is_array( $config['categories'] ) ? implode( ', ', $config['categories'] ) : '';
				$config_show = ! empty( $config['show_in_panel'] ) ? 'yes' : 'no';
				$config_hide = ! empty( $config['hide_on_search'] ) ? 'yes' : 'no';
			} catch ( \Throwable $e ) {
				$config_error = get_class( $e ) . ': ' . $e->getMessage();
			}

			try {
				$stack = $widget->get_stack( false );
				$count = isset( $stack['controls'] ) && is_array( $stack['controls'] ) ? count( $stack['controls'] ) : 0;
				$controls_status = 'yes (' . $count . ' controls)';
			} catch ( \Throwable $e ) {
				$controls_status = 'error: ' . get_class( $e ) . ': ' . $e->getMessage();
			}
		}

		$rows = array(
			__( 'Plugin version', 'ramka-elementor-product-mega-menu' ) => RAMKA_MEGA_VERSION,
			__( 'PHP version', 'ramka-elementor-product-mega-menu' ) => PHP_VERSION,
			__( 'ELEMENTOR_VERSION defined', 'ramka-elementor-product-mega-menu' ) => defined( 'ELEMENTOR_VERSION' ) ? 'yes (' . ELEMENTOR_VERSION . ')' : 'no',
			__( 'elementor/loaded action count', 'ramka-elementor-product-mega-menu' ) => (string) did_action( 'elementor/loaded' ),
			__( 'WC_VERSION defined', 'ramka-elementor-product-mega-menu' ) => defined( 'WC_VERSION' ) ? 'yes (' . WC_VERSION . ')' : 'no',
			__( 'WooCommerce class exists', 'ramka-elementor-product-mega-menu' ) => class_exists( '\\WooCommerce' ) ? 'yes' : 'no',
			__( 'elementor/widgets/register action count', 'ramka-elementor-product-mega-menu' ) => (string) did_action( 'elementor/widgets/register' ),
			__( 'Ramka widget callback seen', 'ramka-elementor-product-mega-menu' ) => self::$widget_callback_seen ? 'yes' : 'no',
			__( 'Ramka widget registered in Widget Manager', 'ramka-elementor-product-mega-menu' ) => $widget_registered ? 'yes' : 'no',
			__( 'Ramka widget class', 'ramka-elementor-product-mega-menu' ) => $widget_class,
			__( 'Ramka widget title', 'ramka-elementor-product-mega-menu' ) => $widget_title,
			__( 'Elementor widget enable filter', 'ramka-elementor-product-mega-menu' ) => $widget_filter,
			__( 'elementor/elements/categories_registered action count', 'ramka-elementor-product-mega-menu' ) => (string) did_action( 'elementor/elements/categories_registered' ),
			__( 'Ramka category callback seen', 'ramka-elementor-product-mega-menu' ) => self::$category_callback_seen ? 'yes' : 'no',
			__( 'Ramka category exists in Elementor', 'ramka-elementor-product-mega-menu' ) => $category_exists ? 'yes' : 'no',
			__( 'Elementor design permission for current user', 'ramka-elementor-product-mega-menu' ) => $design_permission,
			__( 'Elementor Safe Mode option', 'ramka-elementor-product-mega-menu' ) => $safe_mode_status,
			__( 'Widget config readable', 'ramka-elementor-product-mega-menu' ) => $config_readable,
			__( 'Widget config categories', 'ramka-elementor-product-mega-menu' ) => $config_categories,
			__( 'Widget config show_in_panel', 'ramka-elementor-product-mega-menu' ) => $config_show,
			__( 'Widget config hide_on_search', 'ramka-elementor-product-mega-menu' ) => $config_hide,
			__( 'Widget controls stack readable', 'ramka-elementor-product-mega-menu' ) => $controls_status,
		);

		if ( $config_error ) {
			$rows[ __( 'Widget config error', 'ramka-elementor-product-mega-menu' ) ] = $config_error;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Ramka Mega Diagnostics', 'ramka-elementor-product-mega-menu' ); ?></h1>
			<p><?php echo esc_html__( 'This page is visible only to administrators who can activate plugins. It is read-only and can be removed after troubleshooting.', 'ramka-elementor-product-mega-menu' ); ?></p>
			<table class="widefat striped" style="max-width:1100px">
				<tbody>
				<?php foreach ( $rows as $label => $value ) : ?>
					<tr>
						<th scope="row" style="width:46%"><?php echo esc_html( $label ); ?></th>
						<td><code><?php echo esc_html( (string) $value ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><?php echo esc_html__( 'For Elementor 4.x the expected widget config is: categories include ramka-elements and general, show_in_panel is yes, hide_on_search is no, and the controls stack is readable.', 'ramka-elementor-product-mega-menu' ); ?></p>
			<p><strong><?php echo esc_html__( 'Also verify that Elementor Safe Mode is disabled and that the Ramka widget is allowed for your role in Elementor Element Manager.', 'ramka-elementor-product-mega-menu' ); ?></strong></p>
		</div>
		<?php
	}
}
