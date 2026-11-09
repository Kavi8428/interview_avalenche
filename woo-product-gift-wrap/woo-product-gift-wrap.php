<?php


/**
 * Plugin Name: WooCommerce Product Gift Wrap
 * Description: Optional LKR 250 gift wrap and gift message on selected products.
 * Version: 1.0.0
 * Requires Plugins: woocommerce
 * Text Domain: woo-product-gift-wrap
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Declare HPOS (High-Performance Order Storage) Compatibility
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

class WC_Product_Gift_Wrap {

    const FEE_AMOUNT = 250; // LKR 250 per gift-wrapped item

    public function __construct() {
        // 1. Admin: Product settings (mark product as giftable)
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_admin_giftable_checkbox' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_admin_giftable_checkbox' ] );

        // 2. Frontend: Display gift wrap option on single product page
        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'render_product_gift_wrap_fields' ], 25 );

        // 3. Validation: Validate message length (max 100 characters)
        add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_gift_wrap_input' ], 10, 3 );

        // 4. Cart: Attach gift wrap data to cart line item
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_gift_wrap_data' ], 10, 3 );

        // 5. Cart/Checkout Display: Show gift wrap details under cart item title
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_cart_item_gift_wrap_data' ], 10, 2 );

        // 6. Fee: Add LKR 250 fee to cart and checkout totals
        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'calculate_gift_wrap_fees' ], 20, 1 );

        // 7. Order: Save gift wrap metadata to order line item
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_order_line_item_gift_wrap_data' ], 10, 4 );

        // 8. Admin & Packing Display: Show highlighted packing badge directly in Admin Order screen
        add_action( 'woocommerce_after_order_itemmeta', [ $this, 'render_admin_order_item_gift_badge' ], 10, 3 );

        // 9. Optional Stretch: Add packing instructions into Order Notes automatically (Classic + Block Checkout)
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'add_gift_wrap_order_notes' ], 10, 3 );
        add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'add_gift_wrap_order_notes' ], 10, 1 );

        // 10. Admin UI: Prevent table meta labels from breaking onto two lines
        add_action( 'admin_head', [ $this, 'add_admin_order_item_meta_css' ] );
    }

    /**
     * Check if a product is marked as giftable.
     *
     * @param int|WC_Product $product
     * @return bool
     */
    public static function is_giftable( $product ) {
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }
        if ( ! $product instanceof WC_Product ) {
            return false;
        }
        return 'yes' === $product->get_meta( '_is_giftable' );
    }

    /**
     * 1. Add "Giftable Product" checkbox to Admin Product Edit > General Tab
     */
    public function add_admin_giftable_checkbox() {
        echo '<div class="options_group show_if_simple show_if_variable">';
        woocommerce_wp_checkbox( [
            'id'          => '_is_giftable',
            'label'       => __( 'Gift Wrap Eligible', 'woo-product-gift-wrap' ),
            'description' => __( 'Allow customers to add gift wrapping (LKR 250) and a custom note to this product.', 'woo-product-gift-wrap' ),
            'desc_tip'    => true,
        ] );
        echo '</div>';
    }

    /**
     * Save "Giftable Product" checkbox state in Admin
     *
     * @param int $post_id
     */
    public function save_admin_giftable_checkbox( $post_id ) {
        $product = wc_get_product( $post_id );
        if ( ! $product ) {
            return;
        }
        $is_giftable = isset( $_POST['_is_giftable'] ) ? 'yes' : 'no';
        $product->update_meta_data( '_is_giftable', $is_giftable );
        $product->save();
    }

    /**
     * 2. Render Gift Wrap fields on eligible product pages
     */
    public function render_product_gift_wrap_fields() {
        global $product;
        if ( ! $product || ! self::is_giftable( $product ) ) {
            return;
        }
        ?>
        <div class="wc-gift-wrap-container" style="margin: 15px 0; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 6px; background-color: #f8fafc;">
            <p style="margin: 0 0 8px; font-weight: 600;">
                <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="wc_add_gift_wrap" id="wc_add_gift_wrap" value="yes" style="cursor: pointer; width: 16px; height: 16px;" />
                    <span><?php esc_html_e( 'Add Gift Wrapping (+LKR 250)', 'woo-product-gift-wrap' ); ?></span>
                </label>
            </p>
            <div id="wc-gift-message-wrapper" style="display: none; margin-top: 10px;">
                <label for="wc_gift_message" style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px;">
                    <?php esc_html_e( 'Gift Message (Optional, max 100 characters):', 'woo-product-gift-wrap' ); ?>
                </label>
                <textarea 
                    name="wc_gift_message" 
                    id="wc_gift_message" 
                    rows="3" 
                    maxlength="100" 
                    style="width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px;" 
                    placeholder="<?php esc_attr_e( 'Write your gift message here...', 'woo-product-gift-wrap' ); ?>"></textarea>
                <div style="text-align: right; font-size: 12px; color: #64748b; margin-top: 2px;">
                    <span id="wc-gift-char-counter">0</span> / 100 characters
                </div>
            </div>
        </div>

        <script>
            (function() {
                var checkbox = document.getElementById('wc_add_gift_wrap');
                var wrapper = document.getElementById('wc-gift-message-wrapper');
                var textarea = document.getElementById('wc_gift_message');
                var counter = document.getElementById('wc-gift-char-counter');

                if (!checkbox || !wrapper) return;

                checkbox.addEventListener('change', function() {
                    wrapper.style.display = this.checked ? 'block' : 'none';
                    if (!this.checked && textarea) {
                        textarea.value = '';
                        if (counter) counter.textContent = '0';
                    }
                });

                if (textarea && counter) {
                    textarea.addEventListener('input', function() {
                        counter.textContent = this.value.length;
                    });
                }
            })();
        </script>
        <?php
    }

    /**
     * 3. Validate gift wrap input upon add-to-cart
     *
     * @param bool $passed
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function validate_gift_wrap_input( $passed, $product_id, $quantity ) {
        if ( isset( $_POST['wc_add_gift_wrap'] ) && 'yes' === $_POST['wc_add_gift_wrap'] ) {
            // Server-side guard: reject gift wrap for non-giftable products
            if ( ! self::is_giftable( $product_id ) ) {
                wc_add_notice( __( 'Gift wrapping is not available for this item.', 'woo-product-gift-wrap' ), 'error' );
                return false;
            }

            if ( ! empty( $_POST['wc_gift_message'] ) ) {
                $raw_message = wp_unslash( $_POST['wc_gift_message'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                if ( mb_strlen( trim( $raw_message ) ) > 100 ) {
                    wc_add_notice( __( 'The gift message cannot exceed 100 characters.', 'woo-product-gift-wrap' ), 'error' );
                    return false;
                }
            }
        }
        return $passed;
    }

    /**
     * 4. Add gift wrap selection and message to Cart Item Data
     *
     * @param array $cart_item_data
     * @param int $product_id
     * @param int $variation_id
     * @return array
     */
    public function add_cart_item_gift_wrap_data( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $_POST['wc_add_gift_wrap'] ) && 'yes' === $_POST['wc_add_gift_wrap'] ) {
            // Server-side guard: do not attach gift wrap data to non-giftable products
            if ( ! self::is_giftable( $product_id ) ) {
                return $cart_item_data;
            }

            $cart_item_data['gift_wrap'] = 'yes';
            
            $message = '';
            if ( ! empty( $_POST['wc_gift_message'] ) ) {
                $message = sanitize_textarea_field( wp_unslash( $_POST['wc_gift_message'] ) );
                if ( mb_strlen( $message ) > 100 ) {
                    $message = mb_substr( $message, 0, 100 );
                }
            }
            $cart_item_data['gift_message'] = $message;
        }
        return $cart_item_data;
    }

    /**
     * 5. Display gift wrap information in Cart & Checkout item lines
     *
     * @param array $item_data
     * @param array $cart_item
     * @return array
     */
    public function display_cart_item_gift_wrap_data( $item_data, $cart_item ) {
        if ( ! empty( $cart_item['gift_wrap'] ) && 'yes' === $cart_item['gift_wrap'] ) {
            $item_data[] = [
                'key'   => __( 'Gift Wrapping', 'woo-product-gift-wrap' ),
                'value' => __( 'Yes (+LKR 250)', 'woo-product-gift-wrap' ),
            ];

            if ( ! empty( $cart_item['gift_message'] ) ) {
                $item_data[] = [
                    'key'   => __( 'Gift Message', 'woo-product-gift-wrap' ),
                    'value' => esc_html( $cart_item['gift_message'] ),
                ];
            } else {
                $item_data[] = [
                    'key'   => __( 'Gift Message', 'woo-product-gift-wrap' ),
                    'value' => '<em>' . __( 'No custom message', 'woo-product-gift-wrap' ) . '</em>',
                ];
            }
        }
        return $item_data;
    }

    /**
     * 6. Calculate and add Gift Wrapping Fee in Cart and Checkout
     *
     * @param WC_Cart $cart
     */
    public function calculate_gift_wrap_fees( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        $gift_wrapped_count = 0;

        foreach ( $cart->get_cart() as $cart_item ) {
            if ( ! empty( $cart_item['gift_wrap'] ) && 'yes' === $cart_item['gift_wrap'] ) {
                // Server-side guard: verify product is eligible for gift wrapping
                if ( ! empty( $cart_item['product_id'] ) && ! self::is_giftable( $cart_item['product_id'] ) ) {
                    continue;
                }
                $gift_wrapped_count += (int) $cart_item['quantity'];
            }
        }

        if ( $gift_wrapped_count > 0 ) {
            $total_fee = $gift_wrapped_count * self::FEE_AMOUNT;
            $fee_label = sprintf(
                /* translators: %d: number of wrapped items */
                __( 'Gift Wrapping Fee (%d item%s)', 'woo-product-gift-wrap' ),
                $gift_wrapped_count,
                $gift_wrapped_count > 1 ? 's' : ''
            );
            $cart->add_fee( $fee_label, $total_fee, false ); // false = non-taxable
        }
    }

    /**
     * 7. Save gift wrap details to Order Line Item Meta on checkout
     *
     * @param WC_Order_Item_Product $item
     * @param string $cart_item_key
     * @param array $values
     * @param WC_Order $order
     */
    public function save_order_line_item_gift_wrap_data( $item, $cart_item_key, $values, $order ) {
        if ( ! empty( $values['gift_wrap'] ) && 'yes' === $values['gift_wrap'] ) {
            $item->add_meta_data( __( 'Gift Wrapping', 'woo-product-gift-wrap' ), __( 'Yes', 'woo-product-gift-wrap' ), true );

            if ( ! empty( $values['gift_message'] ) ) {
                $item->add_meta_data( __( 'Gift Message', 'woo-product-gift-wrap' ), $values['gift_message'], true );
            }
        }
    }

    /**
     * 8. Display a highlighted, prominent packing badge in WooCommerce Admin Order screen
     *
     * @param int $item_id
     * @param WC_Order_Item_Product $item
     * @param WC_Product $product
     */
    public function render_admin_order_item_gift_badge( $item_id, $item, $product ) {
        if ( ! is_admin() || ! is_callable( [ $item, 'get_meta' ] ) ) {
            return;
        }

        $is_gift = $item->get_meta( 'Gift Wrapping' );
        if ( ! $is_gift ) {
            return;
        }

        $message = $item->get_meta( 'Gift Message' );

        echo '<div style="margin: 8px 0; padding: 10px 14px; background: #f0fdf4; border: 1px solid #86efac; border-left: 4px solid #16a34a; border-radius: 4px; font-size: 13px; line-height: 1.5; color: #166534; max-width: 480px;">';
        echo '<div style="font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 6px;">🎁 <span>' . esc_html__( 'Gift Wrapping Requested (+LKR 250)', 'woo-product-gift-wrap' ) . '</span></div>';
        if ( ! empty( $message ) ) {
            echo '<div style="margin-top: 6px; font-size: 13px;"><strong>' . esc_html__( 'Gift Note to Pack:', 'woo-product-gift-wrap' ) . '</strong> &ldquo;<em>' . esc_html( $message ) . '</em>&rdquo;</div>';
        } else {
            echo '<div style="margin-top: 4px; color: #65a30d;"><em>' . esc_html__( 'No custom note requested.', 'woo-product-gift-wrap' ) . '</em></div>';
        }
        echo '</div>';
    }

    /**
     * 9. Optional Stretch: Automatically add gift wrap packing instructions to Order Notes timeline
     * Supports both Classic Checkout and Block Checkout (Store API).
     *
     * @param int|WC_Order $order_or_id
     * @param array $posted_data
     * @param WC_Order|null $order
     */
    public function add_gift_wrap_order_notes( $order_or_id, $posted_data = [], $order = null ) {
        if ( $order_or_id instanceof WC_Order ) {
            $order = $order_or_id;
        } elseif ( ! $order && is_numeric( $order_or_id ) ) {
            $order = wc_get_order( $order_or_id );
        }

        if ( ! $order instanceof WC_Order ) {
            return;
        }

        // Prevent duplicate order note creation
        if ( 'yes' === $order->get_meta( '_gift_wrap_note_added' ) ) {
            return;
        }

        $gift_items = [];
        foreach ( $order->get_items() as $item ) {
            if ( $item->get_meta( 'Gift Wrapping' ) ) {
                $msg = $item->get_meta( 'Gift Message' );
                $gift_items[] = sprintf(
                    '• %s (x%d)%s',
                    $item->get_name(),
                    $item->get_quantity(),
                    ! empty( $msg ) ? ' - Note: "' . $msg . '"' : ' - (No custom note)'
                );
            }
        }

        if ( ! empty( $gift_items ) ) {
            $note = "🎁 PACKING INSTRUCTIONS (Gift Wrap):\n" . implode( "\n", $gift_items );
            $order->add_order_note( $note, false, false ); // Private note for fulfillment team
            $order->update_meta_data( '_gift_wrap_note_added', 'yes' );
            $order->save_meta_data();
        }
    }

    /**
     * 10. Admin UI CSS: Ensure order item meta table keys do not wrap awkwardly
     */
    public function add_admin_order_item_meta_css() {
        echo '<style>
            .woocommerce_order_items .display_meta th {
                white-space: nowrap !important;
                padding-right: 14px !important;
                color: #334155 !important;
            }
        </style>';
    }
}

// Initialize Plugin
new WC_Product_Gift_Wrap();

