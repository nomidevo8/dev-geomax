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

    /**
     * Enqueue frontend JavaScript
     */
    add_action( 'wp_enqueue_scripts', 'dev_geomax_enqueue_scripts' );

    function dev_geomax_enqueue_scripts() {
        // Register script
        wp_enqueue_script(
            'dev-geomax-script', // Handle name
            plugin_dir_url( __FILE__ ) . 'assets/js/script.js', // Path to your JS file
            array('jquery'), // Dependencies
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/script.js' ), // Version (auto-updates)
            true // Load in footer
        );

        // Optional: localize variables if needed
        wp_localize_script( 'dev-geomax-script', 'devGeomaxData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'siteUrl' => site_url(),
        ) );
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
