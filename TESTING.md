# Part D: Self-Test Report

Environment: WordPress sandbox (WordPress Playground), WooCommerce plugin, custom plugin `WooCommerce Product Gift Wrap` v1.0.0.
WooCommerce version: ______ (fill in from WooCommerce > Status)
Checkout type used: ______ (classic / block)

Only cases I actually ran are marked Pass or Fail. Cases I did not run are marked **Not tested** and are listed honestly in the limitations section.

| # | Test case | Steps | Expected | Actual | Pass/Fail |
|---|-----------|-------|----------|--------|-----------|
| 1 | Non-giftable product | Open a product where "Gift Wrap Eligible" is unchecked | No gift wrap option shown | No gift wrap option shown | Pass |
| 2 | Giftable product, toggle | Open a giftable product, tick and untick the gift wrap checkbox | Checkbox visible. Message field appears when ticked | Checkbox visible and message field toggled as expected | Pass |
| 3 | Happy path with message | Tick gift wrap, enter a message, add to cart, view cart and checkout | Gift wrap and message shown. LKR 250 fee added | Gift wrap and message shown, fee applied | Pass |
| 4 | Empty message | Tick gift wrap, leave the message empty, add to cart | Added successfully with the LKR 250 fee | Worked with an empty message | Pass |
| 5 | Exactly 100 characters | Enter a 100-character message and add to cart | Accepted | Accepted | Pass |
| 6 | More than 100 characters (browser) | Try to type more than 100 characters in the message box | Cannot enter more than 100 | The text box does not allow more than 100 characters (`maxlength`) | Pass (browser limit only) |
| 7 | Quantity 3 | Wrap with quantity 3, add to cart | Fee LKR 750 | Fee LKR 750 | Pass |
| 8 | Item removed or quantity changed | Remove the wrapped item or change its quantity in the cart | Fee updates or disappears | Fee updated | Pass |
| 9 | Two different messages, same product | Add the same product twice with different messages | Two cart lines, one message each, fee LKR 500 | Only a single message per product could be entered. Two separate lines were not confirmed | Inconclusive, retest needed |
| 10 | Coupon applied | Apply a coupon with a wrapped item in the cart | Fee unaffected | Not tested | Not tested |
| 11 | Admin order view | Place an order and open it in WooCommerce > Orders | Gift wrap and message on the line item, packing badge, fee row, correct total | Gift Wrapping: Yes, Gift Message shown, green packing badge shown, "Gift Wrapping Fee (1 item)" LKR 250, order total LKR 2,750 (screenshot) | Pass |
| 12 | Private packing order note | Open the order notes after checkout | Private note listing the wrapped items | Not tested | Not tested |

## Summary
- Cases run: 10 of 12 (cases 1 to 9 and case 11)
- Not tested: 7 (cases 10, 12,)

## Known limitations and gaps
- The 101-character rejection was checked only through the browser `maxlength`. The server-side rejection message was not tested.
- Gift options appear only on the single product page, not on shop-page quick-add buttons.
- The fee assumes the store currency is LKR.
