<?php
/**
 * Plugin Name: Dev Chouchou Box
 * Description: Custom flavor selector for WooCommerce products.
 * Author: Noman Dev
 * Version: 1.2
 */

if (!defined('ABSPATH')) exit;

class Dev_WooCommerce_Init {

    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', [$this, 'render_admin_fields']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_admin_fields']);
        add_filter('woocommerce_loop_add_to_cart_link', [$this, 'override_add_to_cart_button'], 10, 2);
    }

    /** Admin fields */
    public function render_admin_fields() {
        echo '<div class="options_group">';

        // Enable custom link
        woocommerce_wp_checkbox([
            'id'    => '_custom_add_to_cart_enable',
            'label' => __('Use Custom Add to Cart Link?', 'your-textdomain'),
        ]);

        // Custom URL field
        woocommerce_wp_text_input([
            'id'          => '_custom_add_to_cart_url',
            'label'       => __('Custom Add to Cart URL', 'your-textdomain'),
            'placeholder' => 'https://example.com/custom-link',
            'desc_tip'    => true,
            'description' => __('Enter a custom URL to override Add to Cart button.', 'your-textdomain'),
        ]);

        // Open in new tab
        woocommerce_wp_checkbox([
            'id'    => '_custom_add_to_cart_new_tab',
            'label' => __('Open link in new tab?', 'your-textdomain'),
        ]);

        echo '</div>';

        // JS toggle
        add_action('admin_footer', [$this, 'admin_footer_script']);
    }

    /** Save fields */
    public function save_admin_fields($product) {
        $enable = isset($_POST['_custom_add_to_cart_enable']) ? 'yes' : 'no';
        $url = isset($_POST['_custom_add_to_cart_url']) ? esc_url_raw($_POST['_custom_add_to_cart_url']) : '';
        $new_tab = isset($_POST['_custom_add_to_cart_new_tab']) ? 'yes' : 'no';

        $product->update_meta_data('_custom_add_to_cart_enable', $enable);
        $product->update_meta_data('_custom_add_to_cart_url', $url);
        $product->update_meta_data('_custom_add_to_cart_new_tab', $new_tab);
    }

    /** Replace Add to Cart button HTML on shop/product pages */
    public function override_add_to_cart_button($button_html, $product) {
        $enable = $product->get_meta('_custom_add_to_cart_enable');
        $custom_url = $product->get_meta('_custom_add_to_cart_url');
        $new_tab = $product->get_meta('_custom_add_to_cart_new_tab');

        if ($enable === 'yes' && !empty($custom_url)) {
            $target = ($new_tab === 'yes') ? ' target="_blank"' : '';
            $label = esc_html__('View Product', 'your-textdomain'); // you can change this text

            return sprintf(
                '<a href="%s" class="button custom-add-to-cart-link" %s>%s</a>',
                esc_url($custom_url),
                $target,
                $label
            );
        }

        return $button_html;
    }

    /** Admin JS to toggle URL input */
    public function admin_footer_script() {
        ?>
        <script>
        jQuery(function($){
            const $checkbox = $('#_custom_add_to_cart_enable');
            const $urlField = $('#_custom_add_to_cart_url').closest('.form-field');
            const $newTab = $('#_custom_add_to_cart_new_tab').closest('.form-field');

            function toggleFields() {
                if ($checkbox.is(':checked')) {
                    $urlField.show();
                    $newTab.show();
                } else {
                    $urlField.hide();
                    $newTab.hide();
                }
            }

            toggleFields();
            $checkbox.on('change', toggleFields);
        });
        </script>
        <?php
    }
}

new Dev_WooCommerce_Init();
