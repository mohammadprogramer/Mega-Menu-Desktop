<?php
/**
 * Plugin Name: Ramka Elementor Product Mega Menu
 * Description: Accessible WooCommerce product category mega menu widget for Elementor.
 * Version: 1.0.21
 * Requires PHP: 8.1
 * Text Domain: ramka-elementor-product-mega-menu
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'RAMKA_MEGA_VERSION', '1.0.21' );
define( 'RAMKA_MEGA_FILE', __FILE__ );
define( 'RAMKA_MEGA_PATH', plugin_dir_path( __FILE__ ) );
define( 'RAMKA_MEGA_URL', plugin_dir_url( __FILE__ ) );

require_once RAMKA_MEGA_PATH . 'includes/class-bootstrap.php';
// Run after all active plugin files have been included.
add_action( 'plugins_loaded', array( 'RamkaMega\\Bootstrap', 'init' ), 20 );
