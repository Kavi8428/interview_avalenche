# WooCommerce & Shopify Developer Assessment Submission

This repository contains the complete assessment solution for the **WooCommerce Developer Track**.

---

## 🎥 Video Demonstration (Loom)
- **Loom Walkthrough**: [Watch the 3-minute demo](https://www.loom.com/share/16e1c8714ec24f5796f1d13dd219d0b2)
- **Total Time Spent**: ~2.5 hours

---

## 📁 Repository Contents

| File | Purpose |
|------|---------|
| **[`PART_A.md`](PART_A.md)** | Debug & Fix report with one-line breakdown for all 5 bugs + corrected code. |
| **[`REQUIREMENTS.md`](REQUIREMENTS.md)** | Step 1 requirements documentation: 6 clarifying questions, assumptions, and scope definition. |
| **[`woo-product-gift-wrap/`](woo-product-gift-wrap/)** | Complete custom WooCommerce plugin source code. |
| **[`woo-product-gift-wrap.zip`](woo-product-gift-wrap.zip)** | Packaged plugin ready for 1-click installation. |
| **[`AI_LOG.md`](AI_LOG.md)** | Part C research log documenting AI prompts, responses, and evaluation (including a corrected AI error). |
| **[`TESTING.md`](TESTING.md)** | Part D self-test report with an 11-point test matrix covering happy paths and edge cases. |

---

## 🚀 Plugin Features (Gift Wrap Option)

1. **Admin Product Management**:
   - "Gift Wrap Eligible" checkbox in Product Data (General tab).
   - Saved cleanly to post meta `_is_giftable`.
2. **Dynamic Storefront UI**:
   - Checkbox to add gift wrapping (+LKR 250).
   - Expandable message box with real-time character countdown (max 100 characters).
3. **Validation & Sanitization**:
   - Strict server-side verification ensuring only gift-eligible products can receive gift wrap options or fees.
   - Strict length validation via `woocommerce_add_to_cart_validation` rejecting >100 characters.
   - Input sanitization using `sanitize_textarea_field()` and `wp_unslash()`.
   - Empty messages allowed as per client requirements.
4. **Dynamic Cart & Checkout Fee**:
   - LKR 250 fee calculated dynamically based on gift-wrapped item quantity (`LKR 250 × quantity`).
   - Leverages native WooCommerce cart item hashing so different messages create separate line items while identical messages merge cleanly.
5. **Order Fulfillment & Warehouse Visibility**:
   - Gift wrap status and custom note displayed under cart and checkout line items.
   - Highlighted green packing badge displayed directly on the Admin Order item row.
   - **Optional Stretch Feature**: Automatic private note added to the **Order Notes** timeline for fulfillment personnel (supports both Classic Checkout and Block Checkout Store API).
6. **Modern Standards**:
   - Fully declared compatible with **WooCommerce High-Performance Order Storage (HPOS)**.

