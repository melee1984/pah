# Merchant dining workspace

The existing merchant dashboard links to **Cashier workspace** and **Kitchen display** under Dining service. Both use the existing merchant web login and Vue 2 merchant entry point. Kitchen/Cashier accounts land directly in their assigned branch; owners can switch among their own branches.

## Workflow

- Submitted table orders become rounds on the table's open bill. The order observer attaches QR and ordinary checkout orders through the same service.
- Kitchen tickets move through New → Preparing → Ready to Serve → Served. Readiness no longer completes a dine-in order. Delivery and pickup actions keep their previous behavior.
- Served orders remain on the bill until a cashier records payment. Kitchen preparation does not mark a bill paid.
- Payment requires all non-cancelled rounds to be served and the reviewed total to match. Table/bill/order locks serialize settlement against incoming rounds. A unique request key and one-payment-per-bill constraint prevent duplicate records.
- Payment stores the acting user, method, reference, amount in centavos, immutable totals, and receipt item snapshots. The next submitted order at that table starts a new bill with round 1.
- A fully cancelled bill can be released without recording payment. Active or missing orders prevent this action.
- Cashier table controls pause/resume ordering using the existing `is_available` flag. QR checkout and new item additions respect the flag; reading a menu remains possible. Table setup, capacities, and QR code administration remain in the existing merchant table management page.

The workspace only handles dine-in orders. Existing delivery/pickup lists and reports remain available to merchant operators. Existing sales/reward/commission behavior continues to use order completion; **Collected today** uses actual dining payment records instead.

Payment recording confirms money already received. It does not invoke a card/GCash processor. Settlement is for the full bill; split payments and refunds are outside this change.

## Access

| Role | Tickets | Prepare / ready | Serve | Tables / bills / receipts | Record payments |
|---|---|---|---|---|---|
| Kitchen | Assigned branch | Yes | Yes | Ticket table information only | No |
| Cashier | Assigned branch | No | Yes | Assigned branch | Yes |
| Merchant owner | Selected owned branch | Yes | Yes | Selected owned branch | Yes |

Existing branch operators retain their role and can use the workspace within their assignment. New staff accounts cannot use the legacy owner-wide merchant APIs. Role/branch changes revoke their API token; every web request checks the current assignment and permissions. Price and payment data are omitted from kitchen responses.

## Deployment

Run the new migrations against the existing application database; do not use `migrate:fresh`:

```sh
php artisan migrate --force
php artisan dining:sync-open-orders
npm run build
```

`dining:sync-open-orders` is repeatable and links only pending/accepted/preparing/ready table orders. It deliberately does not assume historical completed orders are unpaid. Review those separately if importing historical open bills.

This Mac has ARM-native frontend dependencies but an Intel Node first in the shell path. The successful local build command was:

```sh
PATH=/opt/homebrew/bin:$PATH npm run build
```

The new tables are `dining_bills`, `dining_bill_orders`, and `dining_payments`. Existing marketplace tables are reused without destructive rebuilds. The role and bill migrations were applied to the local `pahatud` database; no open historical table orders required linking.

## Screens and design

The supplied staff HTML/CSS is the visual reference. The workspace retains its floor grid, stats, bill sidebar, kitchen columns, colors, spacing, and receipt flow. It uses shared database data instead of browser-local demo orders.

Demo-order/reset controls are removed. Table filtering replaces the demo's preparation-station filter because the current menu has no station assignments. The sidebar's service panel shows real ready orders rather than fabricated service requests.

Orders refresh every five seconds without resetting selection, filters, modal contents, or full-screen state. Failed refreshes retain the last successful view with a visible connection warning; expired or revoked access signs the session out. Native fullscreen uses the Fullscreen API. Browsers without that capability get an immersive display mode with an Escape exit. Receipts have an on-screen preview before invoking the browser's print dialog.

## Validation

- Production Vite build passed; only the existing Sass legacy API deprecation warning remains.
- 120 relevant tests passed (734 assertions), covering role/branch restrictions, QR checkout and bill attachment, kitchen transitions, payment validation/idempotency, new rounds/seatings, cancellation, receipt snapshots, table availability, existing owner order APIs, merchant login, table setup, delivery/pickup checkout, rewards, and merchant push notifications.
- Corrected the existing mobile dine-in test fixture to send `diningTableId`, matching the controller's established request contract.
- Browser checks used a separate disposable SQLite database at `/tmp/pah-dining-qa.sqlite`, served at `http://127.0.0.1:8100`. Preview users/orders/payments were not added to `pahatud`.
- Browser-verified: existing owner dashboard and order totals, Branch Users role form, navigation into dining, owner branch switching, kitchen preparing/ready/served flow, automatic incoming-order refresh, table search, table pause/resume, unserved-payment block, payment recording, table release after settlement, payment history, and receipt preview.
- Desktop and 390px mobile layouts were inspected; neither mobile screen overflowed horizontally. Immersive fullscreen and Escape were exercised in the in-app browser. Native fullscreen and physical printer output depend on the user's browser/hardware and were not verified in this environment.

The full unrelated legacy test suite was not used as a release gate; the repository documents existing stale scaffold tests in AGENTS.md.

## Role-specific headers

Kitchen Display and Cashier Dashboard now have explicit workspace/page titles and browser titles. Their header toolbars no longer contain cross-dashboard switches. Kitchen retains table filtering, sound alerts, refresh, and a text-labelled fullscreen/exit button; Cashier retains refresh and its existing table/bill/payment controls. Owner branch selection remains available where authorized, with cross-workspace navigation kept in the sidebar.

Header validation: the production build and 25 relevant tests (155 assertions) passed. Browser checks covered desktop and 390px layouts, fullscreen entry/exit, Cashier table pause/resume, test payment recording, payment history, and receipt preview. The checks used only the isolated QA database. No browser console errors were observed.
