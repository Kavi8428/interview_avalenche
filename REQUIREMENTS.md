# Feature Requirements & Architecture: Gift Wrap Option

## Overview
**Client Brief**:
> *"We want customers to be able to add gift wrapping for LKR 250 on some products, and write a short gift message. We need to see it when we pack the order."*

This document outlines the clarifying questions, working assumptions, and defined scope for implementing the Gift Wrap Option feature.

---

## 1. Questions for the Client & Working Assumptions

| # | Question for the Client | Working Assumption Made | Rationale |
|---|--------------------------|-------------------------|-----------|
| **1** | **Fee Calculation**: Is the LKR 250 fee charged per gift-wrapped item quantity, or as a flat one-time fee per order? | **Per gift-wrapped item quantity** (e.g., 2 units wrapped = LKR 500). | In physical fulfillment, wrapping materials and labor scale directly with the number of units packed. |
| **2** | **Gift Message Scope**: Can a customer provide a distinct gift message for each line item, or should the message apply to the entire order? | **Per line item**. | Multiple giftable products may be intended for different recipients or different gifts within the same order. |
| **3** | **Product Eligibility**: How should the store administrator designate which products are eligible for gift wrapping? | **Per-product toggle in the Admin Dashboard** (Admin Product Edit screen). | Allows granular control so fragile, oversized, or clearance items can be excluded easily. |
| **4** | **Message Validation**: Is a gift message mandatory if "Gift wrap" is selected, and what is the character limit? | **Optional message, strictly capped at 100 characters**. | Some customers want gift wrap without a custom note; 100 characters fits standard packing slip label sizes. |
| **5** | **Cart Edge Cases**: What happens to the fee if a gift-wrapped product is removed or its quantity changes in the cart? | **Fee dynamically updates or is removed automatically**. | Customers should never be charged a gift wrap fee for products no longer present in their cart. |
| **6** | **Taxability & Refunds**: Is the LKR 250 fee taxable, and how are refunds handled if the item is returned? | **Non-taxable add-on fee; refunds follow the store's standard policy for packaging service fees.** | Assumed as a fixed-rate handling service fee unless merchant tax settings explicitly define packaging tax classes. |

---

## 2. Scope Definition

### In Scope
1. **Admin Management**:
   - A dedicated checkbox in the Product Edit screen ("Giftable Product") to flag eligible items.
   - Standard meta storage (`_is_giftable`).
2. **Product Page Experience**:
   - Conditioned display: Gift wrap options appear *only* on products marked as giftable.
   - Checkbox: "Add Gift Wrapping (+LKR 250)".
   - Dynamic message textarea: Visible when the checkbox is checked, with a live countdown counter showing remaining characters up to 100.
3. **Cart & Checkout Integration**:
   - Input sanitization (`sanitize_textarea_field`) and strict length validation (max 100 characters).
   - Display of the selected gift wrap status and custom message on the cart item line.
   - Distinct fee row (`Gift Wrapping Fee: LKR 250`) added to cart subtotal and checkout totals.
4. **Order Management & Fulfillment**:
   - Preservation of gift wrap metadata and customer message in the order line items.
   - Prominent display on the WooCommerce Admin Order Edit screen (for the warehouse/packing team).
   - Display on order confirmation emails (customer and store administrator).

### Out of Scope (Deliberately Left Out)
1. **Multiple Wrapping Paper Selection**: No design/paper pattern picker (standard premium neutral wrap assumed).
2. **Greeting Card Physical Add-ons**: No upsell for separate physical cards or card dimensions.
3. **Multi-currency Auto-Conversion**: Fixed price at LKR 250 (store base currency).
4. **Post-Order Modification**: Customers cannot add or alter gift wrap after an order has reached `processing` or `completed` status without merchant intervention.

