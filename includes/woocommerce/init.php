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
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'custom_single_add_to_cart_text']);
        add_filter( 'woocommerce_product_add_to_cart_text', [$this, 'custom_single_add_to_cart_text'], 10, 2 ); 
        add_filter('woocommerce_product_add_to_cart_url', [$this, 'custom_single_add_to_cart_url'], 10, 2);
        add_filter('woocommerce_is_purchasable', [$this, 'custom_is_purchasable'], 10, 2);
        add_action('woocommerce_single_product_summary', [$this, 'custom_add_to_cart_button'], 31);
        add_action('wp_footer', [$this, 'dev_footer_script']);

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
            'description' => __('Enter a custom URL to override Add to Cart button. You can pass product info like this: <code>?product_value=whatever</code>. If not provided, the product name will be added automatically.', 'your-textdomain'),
        ]);

        // Custom Button Text
        woocommerce_wp_text_input([
            'id'          => '_custom_add_to_cart_text',
            'label'       => __('Custom Button Text', 'dev-geomax'),
            'placeholder' => __('Send Request', 'dev-geomax'),
            'desc_tip'    => true,
            'description' => __('Enter custom text for the Add to Cart button. Leave empty to use the default "Send Request".', 'dev-geomax'),
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
        $button_text = isset($_POST['_custom_add_to_cart_text']) ? sanitize_text_field($_POST['_custom_add_to_cart_text']) : '';

        $product->update_meta_data('_custom_add_to_cart_enable', $enable);
        $product->update_meta_data('_custom_add_to_cart_url', $url);
        $product->update_meta_data('_custom_add_to_cart_new_tab', $new_tab);
        $product->update_meta_data('_custom_add_to_cart_text', $button_text);

    }

    /** Replace Add to Cart button HTML on shop/product pages */
    public function override_add_to_cart_button($button_html, $product) {
        $enable = $product->get_meta('_custom_add_to_cart_enable');
        $custom_url = $product->get_meta('_custom_add_to_cart_url');
        $new_tab = $product->get_meta('_custom_add_to_cart_new_tab');

        if ($enable === 'yes' && !empty($custom_url)) {
            $target = ($new_tab === 'yes') ? ' target="_blank"' : '';
            // Parse the URL
            $parsed_url = esc_url($custom_url);

            // If URL does not contain 'product_value', add it automatically
            if (strpos($parsed_url, 'product_value=') === false) {
                $separator = (strpos($parsed_url, '?') !== false) ? '&' : '?';
                $parsed_url .= $separator . 'product_value=' . rawurlencode($product->get_name());
            }

            $label = esc_html__('View Product', 'your-textdomain'); // you can change this text

            return sprintf(
                '<a href="%s" class="button custom-add-to-cart-link" %s>%s</a>',
                esc_url($parsed_url),
                $target,
                $label
            );
        }

        return $button_html;
    }


    /** Change button text on single product page */
    public function custom_single_add_to_cart_text($text) {
        global $product;

        if (!$product instanceof WC_Product) {
            return $text;
        }

        $enable = $product->get_meta('_custom_add_to_cart_enable');

        if ($enable === 'yes') {
          
            $custom_text = $product->get_meta('_custom_add_to_cart_text');
            if (!empty($custom_text)) {
                return esc_html($custom_text);
            } else {
                return __('Send Request', 'your-textdomain'); 
            }
        }

        return $text;
    }



    /** Change the Add to Cart button URL on single product page */
    public function custom_single_add_to_cart_url($url, $product) {
        $enable = $product->get_meta('_custom_add_to_cart_enable');
        $custom_url = $product->get_meta('_custom_add_to_cart_url');
        $new_tab = $product->get_meta('_custom_add_to_cart_new_tab');

        if ($enable === 'yes' && !empty($custom_url)) {
            $parsed_url = esc_url($custom_url);

            // Add product_value if missing
            if (strpos($parsed_url, 'product_value=') === false) {
                $separator = (strpos($parsed_url, '?') !== false) ? '&' : '?';
                $parsed_url .= $separator . 'product_value=' . rawurlencode($product->get_name());
            }

            return esc_url($parsed_url);
        }

        return $url;
    }

    /** Disable add-to-cart form and replace with custom link */
    public function custom_add_to_cart_button() {
        global $product;

        if (!$product instanceof WC_Product) return;

        $enable = $product->get_meta('_custom_add_to_cart_enable');
        if ($enable !== 'yes') return;

        $url = $product->get_meta('_custom_add_to_cart_url');
        $text = $product->get_meta('_custom_add_to_cart_text') ?: __('Send Request', 'your-textdomain');
        $new_tab = $product->get_meta('_custom_add_to_cart_new_tab');

        $target = ($new_tab === 'yes') ? ' target="_blank"' : '';
        if (strpos($url, 'product_value=') === false) {
            $separator = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $separator . 'product_value=' . rawurlencode($product->get_name());
        }

        echo '<a href="' . esc_url($url) . '" class="single_add_to_cart_button button alt custom-add-to-cart-link"' . $target . '>' . esc_html($text) . '</a>';
    }

    public function custom_is_purchasable($purchasable, $product) {
        if ($product->get_meta('_custom_add_to_cart_enable') === 'yes') {
            return false;
        }
        return $purchasable;
    }

    public function dev_footer_script() {
        if (!is_product()) return;

        global $product;

        if (!$product instanceof WC_Product) return;

        $enable = $product->get_meta('_custom_add_to_cart_enable');
        if ($enable !== 'yes') return;

        $custom_text = $product->get_meta('_custom_add_to_cart_text') ?: __('Send Request', 'your-textdomain');
        $custom_url  = $product->get_meta('_custom_add_to_cart_url') ?: get_permalink($product->get_id());
        $new_tab     = $product->get_meta('_custom_add_to_cart_new_tab') === 'yes' ? 'true' : 'false';

        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            // Elementor Add to Cart button
            const btnWrapper = document.querySelector('.elementor-widget-wc-add-to-cart .elementor-button-wrapper a');

            if(btnWrapper){
                btnWrapper.querySelector('.elementor-button-text').textContent = <?php echo json_encode($custom_text); ?>;
                btnWrapper.href = <?php echo json_encode($custom_url); ?>;
                if(<?php echo $new_tab === 'true' ? 'true' : 'false'; ?>){
                    btnWrapper.setAttribute('target', '_blank');
                } else {
                    btnWrapper.removeAttribute('target');
                }

                // Remove WooCommerce default form if any
                const wcForm = document.querySelector('.single_add_to_cart_button');
                if(wcForm) wcForm.remove();
            }
        });
        </script>
        <?php
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
