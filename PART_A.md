# Part A — Debug & Fix Report

## 1. WooCommerce Track — Loyalty Discount

### Client Bug Report
> *"The discount doesn't show for most customers. One customer saw a critical error at checkout. When it did show, the total went up instead of down."*

### Original Provided Code
```php
add_action( 'woocommerce_cart_calculate_fees', 'apply_loyalty_discount' );

function apply_loyalty_discount( $cart ) {
    if ( is_admin() ) return;
    if ( ! is_user_logged_in ) return;

    $total = $cart->get_cart_contents_total;

    if ( $total > 5000 ) {
        $discount = $total * 10 / 100;
        $cart->add_fee( 'Loyalty Discount', $discount );
    }
}
```

### Bug List (One-Line Analysis)
1. **Critical checkout error (Undefined constant)**: `! is_user_logged_in` was missing function call parentheses `()`. In PHP 8+, this throws a fatal `Error: Undefined constant "is_user_logged_in"`. Fixed by calling the WordPress function properly: `! is_user_logged_in()`.
2. **Discount not showing (Property access on method)**: `$cart->get_cart_contents_total` was missing method parentheses `()`, reading an undefined property returning `null` so `$total > 5000` was never true. Fixed by calling `$cart->get_subtotal()` or `$cart->get_cart_contents_total()`.
3. **Total went up instead of down (Fee polarity)**: `$cart->add_fee()` treats positive values as surcharges, thereby adding to the total. Fixed by passing a negative amount `-$discount` to subtract from the cart total.
4. **Discount stacking with coupons (Missing business logic)**: The requirement explicitly states *"The discount should not stack with coupons"*, but the code lacked any check for applied coupons. Fixed by adding `if ( ! empty( $cart->get_applied_coupons() ) ) return;`.
5. **Admin & AJAX context guard**: While native WooCommerce checkout AJAX uses `?wc-ajax=` endpoints (where `is_admin()` is `false`), third-party mini-carts, checkout extensions, and plugins often trigger cart recalculations via `admin-ajax.php`, where `is_admin()` returns `true`. Terminating prematurely on bare `is_admin()` breaks fee calculation in these environments. Fixed by updating the guard to the WooCommerce standard pattern: `if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;`.

### Corrected Code
```php
add_action( 'woocommerce_cart_calculate_fees', 'apply_loyalty_discount' );

/**
 * Applies a 10% loyalty discount for logged-in users on cart subtotal > LKR 5,000.
 * Does not stack with coupons.
 *
 * @param WC_Cart $cart
 */
function apply_loyalty_discount( $cart ) {
    // Prevent execution in WP Admin unless running an AJAX request (e.g. checkout update)
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    // Must be a logged-in customer
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Must not stack with coupons
    if ( ! empty( $cart->get_applied_coupons() ) ) {
        return;
    }

    // Calculate eligible cart subtotal
    $subtotal = (float) $cart->get_subtotal();

    if ( $subtotal > 5000 ) {
        $discount = $subtotal * 0.10; // 10% discount
        // Negative fee decreases the total
        $cart->add_fee( __( 'Loyalty Discount (10%)', 'woocommerce' ), -$discount );
    }
}
```