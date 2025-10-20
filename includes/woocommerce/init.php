<?php

if (!defined('ABSPATH'))
    exit;

class Dev_WooCommerce_Init
{

    public function __construct()
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'render_admin_fields']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_admin_fields']);
        add_filter('woocommerce_loop_add_to_cart_link', [$this, 'override_add_to_cart_button'], 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'custom_single_add_to_cart_text']);
        add_filter('woocommerce_product_add_to_cart_text', [$this, 'custom_single_add_to_cart_text'], 10, 2);
        add_filter('woocommerce_product_add_to_cart_url', [$this, 'custom_single_add_to_cart_url'], 10, 2);
        add_filter('woocommerce_is_purchasable', [$this, 'custom_is_purchasable'], 10, 2);
        add_action('woocommerce_single_product_summary', [$this, 'custom_add_to_cart_button'], 31);
        add_action('wp_footer', [$this, 'dev_footer_script']);

    }

    /** Admin fields */
    public function render_admin_fields()
    {
        echo '<div class="options_group">';

        // Enable custom link
        woocommerce_wp_checkbox([
            'id' => '_custom_add_to_cart_enable',
            'label' => __('Use Custom Add to Cart Link?', 'your-textdomain'),
        ]);

        // Custom URL field
        woocommerce_wp_text_input([
            'id' => '_custom_add_to_cart_url',
            'label' => __('Custom Add to Cart URL', 'your-textdomain'),
            'placeholder' => 'https://example.com/custom-link',
            'desc_tip' => true,
            'description' => __('Enter a custom URL to override Add to Cart button. You can pass product info like this: <code>?product_value=whatever</code>. If not provided, the product name will be added automatically.', 'your-textdomain'),
        ]);

        // Custom Button Text (Single Product)
        woocommerce_wp_text_input([
            'id' => '_custom_add_to_cart_text',
            'label' => __('Custom Button Text (Single Product)', 'dev-geomax'),
            'placeholder' => __('Send Request', 'dev-geomax'),
            'desc_tip' => true,
            'description' => __('Enter custom text for the Add to Cart button on single product pages. Leave empty to use the default "Send Request".', 'dev-geomax'),
        ]);

        // 🆕 Custom Button Text (Shop / Archive Page)
        woocommerce_wp_text_input([
            'id' => '_custom_add_to_cart_text_shop',
            'label' => __('Custom Button Text (Shop Page)', 'dev-geomax'),
            'placeholder' => __('Anfragen', 'dev-geomax'),
            'desc_tip' => true,
            'description' => __('Enter custom text for the Add to Cart button on the Shop/Archive page.', 'dev-geomax'),
        ]);

        // Open in new tab
        woocommerce_wp_checkbox([
            'id' => '_custom_add_to_cart_new_tab',
            'label' => __('Open link in new tab?', 'your-textdomain'),
        ]);

        echo '</div>';

        // JS toggle
        add_action('admin_footer', [$this, 'admin_footer_script']);
    }

    /** Save fields */
    public function save_admin_fields($product)
    {
        $enable = isset($_POST['_custom_add_to_cart_enable']) ? 'yes' : 'no';
        $url = isset($_POST['_custom_add_to_cart_url']) ? esc_url_raw($_POST['_custom_add_to_cart_url']) : '';
        $new_tab = isset($_POST['_custom_add_to_cart_new_tab']) ? 'yes' : 'no';
        $button_text = isset($_POST['_custom_add_to_cart_text']) ? sanitize_text_field($_POST['_custom_add_to_cart_text']) : '';
        $button_text_shop = isset($_POST['_custom_add_to_cart_text_shop']) ? sanitize_text_field($_POST['_custom_add_to_cart_text_shop']) : '';

        $product->update_meta_data('_custom_add_to_cart_enable', $enable);
        $product->update_meta_data('_custom_add_to_cart_url', $url);
        $product->update_meta_data('_custom_add_to_cart_new_tab', $new_tab);
        $product->update_meta_data('_custom_add_to_cart_text', $button_text);
        $product->update_meta_data('_custom_add_to_cart_text_shop', $button_text_shop);
    }


    /** Replace Add to Cart button HTML on shop/product pages */
    public function override_add_to_cart_button($button_html, $product)
    {
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
    public function custom_single_add_to_cart_text($text)
    {
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
    public function custom_single_add_to_cart_url($url, $product)
    {
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
    public function custom_add_to_cart_button()
    {
        global $product;

        if (!$product instanceof WC_Product)
            return;

        $enable = $product->get_meta('_custom_add_to_cart_enable');
        if ($enable !== 'yes')
            return;

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

    public function custom_is_purchasable($purchasable, $product)
    {
        if ($product->get_meta('_custom_add_to_cart_enable') === 'yes') {
            return false;
        }
        return $purchasable;
    }

    public function dev_footer_script()
    {
        $this->dev_shop_page_script();

        if (!is_product())
            return;

        global $product;

        if (!$product instanceof WC_Product)
            return;

        $enable = $product->get_meta('_custom_add_to_cart_enable');
        $custom_text = $product->get_meta('_custom_add_to_cart_text') ?: __('Send Request', 'dev-geomax');
        $custom_url = $product->get_meta('_custom_add_to_cart_url');
        $new_tab = $product->get_meta('_custom_add_to_cart_new_tab') === 'yes' ? 'true' : 'false';
        $fallback_url = get_permalink($product->get_id());

        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Elementor Add to Cart button
                const btnWrapper = document.querySelector('.elementor-widget-wc-add-to-cart a');
                if (btnWrapper) {
                    const textSpan = btnWrapper.querySelector('.elementor-button-text');
                    let finalURL;
                    <?php if ($enable === 'yes' && !empty($custom_url)): ?>
                        if (textSpan) textSpan.textContent = <?php echo json_encode($custom_text); ?>;
                        // ✅ Custom URL enabled
                        finalURL = <?php echo json_encode($custom_url); ?>;
                        if (finalURL.indexOf('product_value=') === -1) {
                            const sep = finalURL.includes('?') ? '&' : '?';
                            finalURL += sep + 'product_value=' + encodeURIComponent('<?php echo $product->get_name(); ?>');
                        }
                    <?php else: ?>
                        // 🚫 Custom disabled → fallback to same page URL
                        finalURL = <?php echo json_encode($fallback_url); ?>;
                    <?php endif; ?>

                    btnWrapper.href = finalURL;
                    <?php if ($enable === 'yes' && !empty($custom_url)): ?>
                        // Disable WooCommerce add-to-cart behavior
                        btnWrapper.addEventListener('click', function (e) {
                            e.stopImmediatePropagation(); // stop WooCommerce's click handlers
                            e.preventDefault(); // prevent add to cart
                            window.open(finalURL, <?php echo $new_tab === 'true' ? "'_blank'" : "'_self'"; ?>);
                        });
                    <?php endif; ?>

                    // Handle target behavior
                    if (<?php echo $new_tab === 'true' ? 'true' : 'false'; ?>) {
                        btnWrapper.setAttribute('target', '_blank');
                    } else {
                        btnWrapper.removeAttribute('target');
                    }

                    // Remove WooCommerce default form if any
                    const wcForm = document.querySelector('.single_add_to_cart_button');
                    if (wcForm) wcForm.remove();
                }
            });
        </script>
        <?php
    }

    public function dev_shop_page_script()
    {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return; // only run on archive/shop pages
        }

        // Get all products that have custom links enabled
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_custom_add_to_cart_enable',
                    'value' => 'yes'
                ]
            ]
        ];

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            return;
        }

        // Prepare product data for JS
        $products = [];
        foreach ($query->posts as $product_post) {
            $pid = $product_post->ID;
            $custom_text = get_post_meta($pid, '_custom_add_to_cart_text_shop', true) ?: 'Anfragen';
            $custom_url = get_post_meta($pid, '_custom_add_to_cart_url', true);
            $new_tab = get_post_meta($pid, '_custom_add_to_cart_new_tab', true) === 'yes' ? '_blank' : '_self';

            if (!empty($custom_url)) {
                $products[$pid] = [
                    'text' => $custom_text ?: __('Anfragen', 'dev-geomax'),
                    'url' => $custom_url,
                    'new_tab' => $new_tab
                ];
            }
        }

        if (empty($products)) {
            return;
        }

        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const productData = <?php echo wp_json_encode($products); ?>;
                console.log("productData:", productData);
                document.querySelectorAll('.shopengine-archive-mode-grid li.product').forEach(function (productEl) {
                    const pid = productEl.className.match(/post-(\d+)/);
                    if (!pid || !productData[pid[1]]) return;

                    const product = productData[pid[1]];
                    const btn = productEl.querySelector('a.button.custom-add-to-cart-link');

                    if (btn) {
                        // Change text
                        btn.textContent = product.text;

                        // Update URL
                        let finalURL = product.url;
                        if (finalURL.indexOf('product_value=') === -1) {
                            const productTitle = productEl.querySelector('.woocommerce-loop-product__title')?.innerText.trim() || '';
                            const sep = finalURL.includes('?') ? '&' : '?';
                            finalURL += sep + 'product_value=' + encodeURIComponent(productTitle);
                        }
                        btn.href = finalURL;

                        // Open in new tab if needed
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            window.open(finalURL, product.new_tab);
                        });
                    }
                });
            });
        </script>
        <?php
    }

    /** Admin JS to toggle URL input */
    public function admin_footer_script()
    {
        ?>
        <script>
            jQuery(function ($) {
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
