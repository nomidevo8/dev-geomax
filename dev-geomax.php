<?php
/**
 * Plugin Name: Woo Custom Add to Cart Link
 * Description: Adds a custom "Add to Cart" link for specific WooCommerce products.
 * Version: 1.0.0
 * Author: SERVETECH
 * Text Domain: dev-geomax
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Initialize plugin after all plugins are loaded
 */
add_action( 'plugins_loaded', 'dev_geomax_plugin_init' );

function dev_geomax_plugin_init() {

    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'dev_geomax_missing_woocommerce_notice' );
        return;
    }

    // Check if Elementor is active (optional)
    if ( ! defined( 'ELEMENTOR_PATH' ) ) {
        add_action( 'admin_notices', 'dev_geomax_missing_elementor_notice' );
        return;
    }

    // Load main WooCommerce functionality
    require_once __DIR__ . '/includes/woocommerce/init.php';
}

/**
 * Admin notice: WooCommerce missing
 */
function dev_geomax_missing_woocommerce_notice() {
    echo '<div class="notice notice-error"><p><strong>Woo Custom Add to Cart Link</strong> requires <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> to be installed and activated.</p></div>';
}

/**
 * Admin notice: Elementor missing
 */
function dev_geomax_missing_elementor_notice() {
    echo '<div class="notice notice-warning"><p><strong>Woo Custom Add to Cart Link</strong> requires <a href="https://wordpress.org/plugins/elementor/" target="_blank">Elementor</a> to be installed and activated.</p></div>';
}
