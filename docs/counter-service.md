# In-store counter service and serving history

This update supersedes the earlier serve-before-payment POS behavior.

## Payment and kitchen state

Cashiers can record payment immediately after placement or later from an open bill or Order history. The existing `DiningRoom::pay()` transaction, table/bill/order locks, reviewed total, unique payment request UUID, and unique payment-per-bill constraint are reused. A retry with the same inputs returns the original payment; a second payment request for an already-paid bill is rejected. Payment preference alone never means Paid.

Payment changes the bill/payment ledger only. Kitchen transitions continue to update the order and its audit trail. Serving never rewrites payment status. Shared table bills still include all unpaid rounds, with their original item/price snapshots. A new round after an early payment gets a new unpaid bill, while the earlier paid kitchen work remains active at the same table.

Cashier screens and kitchen tickets display both states, such as **Paid · Preparing** and **Unpaid · Served**. The live board includes paid orders until they are served and includes served orders while their bill has an outstanding balance. Served-and-paid records are omitted from the live response and defensively filtered in the UI; they remain in history. Current-day legacy channel orders without a bill/payment record keep their existing visibility. An older order with no payment evidence is not automatically declared paid; old open bills remain live until settled.

Delivery/pickup ordering and merchant/rider actions remain available through their existing channels. The cashier POS and its quote/create endpoints accept only dine-in/in-store orders. Existing legacy delivery bills retain their pre-handoff COD protection; this update does not change rider completion or infer settlement from a selected payment method.

## Touchscreen POS

The catalog and order panel remain adjacent on desktop and landscape tablets. Main actions are dark high-contrast buttons with at least 54–58px height, including **Place Order**, **Pay Now**, and **Confirm Payment**. Quantity controls and options have larger touch targets. Payment preferences and collection use selectable cards with Cash, GCash mobile-wallet and credit-card icons, matching the table selection highlight.

Tables use the existing branch `partner_location_tables` records: a tappable grid shows name, status, balance state and selection. Inactive/paused tables are disabled. After selection the picker collapses to the selected table, leaving room for the basket; **Change table** reopens the highlighted grid. A paid order still in preparation keeps its table active. Small screens retain the collapsible order drawer, and POS fullscreen remains supported.

## Timestamp/schema review

Inspected local MySQL definitions for `order`, `dining_payments`, and `order_process`, plus the QR/mobile/merchant order controllers and DiningRoom serving/payment actions. No additional columns or migration are needed for this update:

| Event | Existing source | Meaning |
| --- | --- | --- |
| Placed | `order.submitted_at` | Actual order submission; not cart creation |
| Payment recorded successfully | `dining_payments.created_at` | The committed payment record; `dining_bills.closed_at` remains settlement time |
| Staff served dine-in/pickup | `order.delivered_at` | Existing staff serving/completion action, corroborated by status 9 or an audited status-9 event |
| Service audit | `order_process` status 9 | Existing actor and status timestamp; serving retries do not overwrite the serving event |

Database statuses are unchanged: 9 is still the stored staff completion status, now labelled **Served** in the workspace. Delivery's `delivered_at` means rider delivery to the customer, so it is not used as kitchen serving time.

Time to serve is the nonnegative difference between actual placement and staff serving timestamps, in seconds. Missing timestamps produce a dash/null, never `now`, payment time or `updated_at`. A populated legacy delivery field on an order that was never served does not invent a service event. For history filtering only, an order with missing placement can be found by its creation date, but its displayed placement and duration remain unknown. No historical serving times were backfilled.

## History and manager ranking

**Order history** is available in the shared sidebar. It includes items/modifiers/item notes, separate whole-order notes, independent payment/kitchen states, placement/payment/serving timestamps, and time to serve. Cashiers can collect an outstanding bill directly from history. Records are paginated, 30 per page.

Owners/branch managers can choose **Fastest served first** and a placement date range for the selected authorized branch. The ranking includes only orders with valid recorded staff service times; unknown/invalid times and rider delivery times are excluded. Branch switching preserves the dates and sort. Staff are pinned to their active assignment even if they supply a different `branch_id`; fastest-ranking access is restricted to manager abilities. Kitchen responses continue to omit item prices, bill totals, and payment amounts.

Endpoint: `GET /merchant/staff/history?branch_id=…&from=YYYY-MM-DD&to=YYYY-MM-DD&sort=newest|fastest&page=…`.

## Validation

- 85 focused feature tests passed (594 assertions), including pay-first/serve-first, duplicate taps/retries, independent timestamps/statuses, paid preparatory work with later unpaid rounds, live-board removal/history retention, in-store-only POS validation, all incoming channels, branch/role restrictions, fastest ordering/date filters, missing/invalid legacy timestamps, and existing QR/mobile/merchant workflows.
- Production Vite build and focused PHP formatting checks passed. Existing Sass deprecation warning remains.
- Browser QA used only `/tmp/pah-counter-qa.sqlite`: actual cashier placement/early cash collection, kitchen preparing/ready/serve actions, paid order removal, unpaid served retention, later cashier collection from history/removal, recorded serving times, manager fastest sorting and branch switching, landscape 1024px grid/panel, and mobile 390px table selection without horizontal overflow.
- Existing MySQL records and schema were not changed. The earlier additive POS migration remains required where it has not yet been applied. A pre-existing AdminLTE dashboard `knob` plugin error was observed only on the unrelated owner overview, not the POS/staff views.

### Receipt printing

The paid POS button opens a receipt preview with a centered print icon and label. Select 80 mm (default) or 58 mm thermal paper, then Print receipt to open the browser/system print dialog. Choose the installed receipt printer and the matching roll width; disable browser headers/footers and use 100% scale. The printer's driver determines the roll length/cutting behavior. This uses browser printing, not a direct USB/Bluetooth or silent ESC/POS connection.

Preview and print share one component, including branch/table, order number and placement date, item quantities and unit/line prices, options, item instructions, separate order notes, totals, payment reference/date, and cash tendered/change. Only the receipt is printed, including when the POS is fullscreen. New paid snapshots retain order notes, order type and placement time in the existing receipt JSON; older receipts use available stored order metadata. No schema changes or historical backfill are required. A physical test with the actual printer is still required.
