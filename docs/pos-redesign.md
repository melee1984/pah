# Cashier POS redesign

The existing `/merchant/staff?view=pos` now uses a product catalog beside a single order panel. The merchant sidebar and Pahatud theme remain shared with the staff workspace.

## Catalog and order panel

- Products use existing branch menu data, category names, descriptions, prices and the existing image endpoint. Missing or failed photos show a local placeholder without a network dependency. Search matches product name, category and description; category filters combine with search.
- Tapping a card opens item details, quantity controls, radio options for single variants, checkboxes for multiple add-ons, and item instructions. Required groups are validated before adding. Items can be edited, removed or adjusted in the basket.
- Whole-order notes remain separate from item instructions. Dine-in shows a branch table selector. Pickup hides it. Delivery retains customer/address/coordinate and delivery-fee fields for the existing dispatcher.
- All pricing is calculated by the existing `Cart::cartItemAmounts()` logic through a read-only quote endpoint. Quote and submission share the same item builder and modifier validation, so client prices are never trusted. Quoting creates no cart, order or bill records. Stale quote responses are ignored; failed quotes have a retry action.
- On desktop and landscape tablets the catalog and panel remain side by side. Below 900px the panel becomes a collapsible drawer with an order summary bar, focus management, Escape dismissal and large touch controls. The panel scrolls independently while its action footer stays visible.

## Fullscreen

The POS catalog header includes Full screen / Exit full screen. It uses the shared staff Fullscreen API with immersive fallback, hides the sidebar/header/navigation, expands the order panel, and preserves the basket and item customization. Exit or Escape restores navigation. Browser QA verified fallback entry, button exit, Escape exit and basket preservation; native fullscreen depends on browser support.

## Checkout stays in POS

Submission no longer navigates to Orders. The panel shows the saved order number, its bill, every bill round, live kitchen status, service controls, payment method, cash tender/change or reference, confirmation, and receipt access. Another order can be started while leaving the submitted bill open.

The existing payment rules are preserved: dine-in/pickup must be served/completed, and delivery must be ready before rider handoff. Payment recording does not charge a gateway. Cashiers can mark a ready dine-in order served or complete a ready pickup from the POS and then record payment in the same panel. Preparation remains with kitchen staff. No schema change was needed for this redesign beyond the earlier POS migration documented in `staff-pos-schema-review.md`.

## Validation

- 77 focused feature tests pass (571 assertions), covering existing POS/dining/QR/merchant order behavior plus quote accuracy, zero draft writes, branch and role checks, required modifiers and menu category/photo metadata.
- Browser QA used only `/tmp/pah-pos-redesign.sqlite`: search/category filtering; existing photo and placeholder rendering; customization with quantity 2, add-on and item note; dine-in table and whole-order note; submission without navigation; kitchen preparation simulated through the existing DiningRoom transitions; cashier service completion, cash payment/change and receipt while URL remained `view=pos`; kitchen ticket with complete item/notes; mobile drawer opening, closing and order summary; landscape tablet columns; 390px layout without horizontal overflow.
- Existing MySQL records were not changed. The earlier additive POS migration remains a deployment prerequisite if it has not yet been applied.
