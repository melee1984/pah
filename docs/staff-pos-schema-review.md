# Staff POS and kitchen integration

## Schema review (19 November 2026)

Reviewed the local MySQL definitions, Eloquent relationships, migrations, QR/mobile checkout, merchant acceptance/ready/completion, rider dispatch/COD, and dining settlement before changing the flow. The current repository/database has substantially more migrations than the older AGENTS.md inventory.

| Existing storage | Use |
| --- | --- |
| `cart` | Customer details, order number, merchant, branch (`partner_location_address_id`), fulfillment type, table, payment preference, source, delivery fee |
| `cart_details` | One item per line; quantity, price/commission, serialized modifiers/add-ons, item `instruction` |
| `order` | Submitted order, merchant, kitchen/delivery statuses, acceptance and completion timestamps |
| `order_process` | Status history and acting staff user |
| `partner_location`, `merchant_branch_users` | Branch ownership and active staff assignment |
| `partner_location_tables` | Branch-specific table name, capacity, active/availability flags |
| `product_location_availability`, checkout options | Branch menu and enabled fulfillment types |
| `cart_user_address` | Delivery address, phone and coordinates |
| `payment_method` | Payment preference/method; choosing one does not prove payment |
| `dining_bills`, `dining_bill_orders`, `dining_payments` | Existing settlement flow, centavo totals, immutable item/price snapshots, audit actor, reference, tendered cash/change, unique payment request key |
| `rider_api_deliveries` | Rider handoff state and COD collection amount |

`App\Model\Orders\OrderTransaction` is an unused scaffold with no `order_transaction` table. No parallel POS order or payment ledger was introduced.

## Additive migration

`2026_11_19_000000_support_staff_pos_orders.php` adds nullable `cart.notes` for whole-order instructions, nullable `cart.pos_request_hash` for matching retried POS submissions, and makes `dining_bills.dining_table_id` nullable for pickup/delivery POS bills. Existing rows and identifiers are preserved. Rollback deliberately retains the fields and table-less bills to avoid data loss. Fresh test fixtures explicitly provision marketplace tables; migrations alone still cannot reconstruct the legacy marketplace schema.

The migration was tested with disposable SQLite data, **not applied to the local MySQL database**. Before using the new POS against an existing application database:

```sh
php artisan migrate --force
PATH=/opt/homebrew/bin:$PATH npm run build
```

Do not use `migrate:fresh` on `pahatud`.

## Behavior

- Cashier POS lives in the staff workspace and supports branch-enabled delivery, pickup, and dine-in. Dine-in requires an active, available table in the authorized branch. Delivery requires an address, phone, latitude/longitude and an entered delivery fee. Coordinates feed the existing rider dispatcher; automatic geocoding/delivery quoting is not included.
- Products, prices, required/multiple modifier validation and totals come from the existing menu/checkout logic. The QR and POS flows share modifier validation. Client-supplied prices and totals are not used to create orders.
- Dine-in continues to append rounds to the table's open bill. Pickup/delivery POS orders each receive their own bill using the existing bill/round/payment tables. Ordinary customer delivery/pickup orders retain their established settlement flow.
- POS creation is atomic and retries reuse the same request key. A changed submission using the same key is rejected. The initial placed status is audited against the cashier.
- Kitchen receives submitted orders of every source and fulfillment type in its assigned branch, including ordinary customer/QR orders and POS orders. Each line shows quantity/name, modifiers/add-ons and item instructions; whole-order notes are separately labelled. Payment never hides an order still requiring kitchen work. Orders already with a rider are not restarted as new kitchen work.
- Dine-in preparation uses `DiningRoom`. Pickup/delivery preparation delegates to existing merchant actions, preserving dispatch and customer acceptance notifications. Only the rider flow can complete delivery.
- Cashier payment preserves the existing serve-before-settlement requirement for dine-in/pickup. Delivery POS payment is permitted when ready and before rider handoff; it clears COD on an existing rider delivery. The dispatcher also checks paid bills if a delivery is created later. After handoff, collection stays with the rider flow. Card/GCash recording acknowledges a payment received elsewhere; it does not charge a gateway.
- Shared Blade sidebar/menu are used by the merchant dashboard and all staff workspace views. Kitchen users receive the kitchen link; cashiers receive cashier/kitchen links. Owner administration links remain available to owners. Staff cannot change their branch through request parameters. Owner sidebar workspace links preserve the selected branch.
- Kitchen native fullscreen remains available where supported; immersive fallback and Escape restore the shared sidebar. Five-second polling retains filters and selections.

## Validation

- 74 focused tests pass (537 assertions), including all ten new POS tests plus existing dining, QR ordering, merchant acceptance, mobile fulfillment and merchant push coverage.
- Production Vite build passes with ARM Node; the existing Sass legacy API warning remains.
- Browser checks used only `/tmp/pah-pos-qa.sqlite`: cashier POS submission with add-on/item instruction/order note; kitchen login with complete delivery/pickup/dine-in tickets; pickup preparing → ready → completed; cash payment, change, payment history and receipt; immersive fullscreen/Escape sidebar restoration; 390px mobile POS/kitchen layout without horizontal overflow.
- A wider run exposed unchanged test issues: the branch-management workspace fixture omits the QR-enabled flag required by the existing controller, and one rider-arrival assertion expects `Delivered Order` while unchanged `Orders::getRiderAction()` returns `Add delivery proof`. These unrelated assertions were not rewritten.
