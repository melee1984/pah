# QR ordering API

QR ordering is public and does not require customer authentication. A QR code UUID is an opaque
credential tied to a restaurant branch and, optionally, a dining table. The client must generate a
new UUID for each cart and keep it until checkout completes.

Each dining table automatically receives a QR UUID when it is created. The merchant table API also
returns an `ordering_url` built from `QR_ORDERING_URL`; it includes `qr_uuid`, `partner_id`,
`branch_id`, and `table_id` query parameters for the customer-facing QR ordering app. The UUID's
database association remains the trusted source for the restaurant, branch, and table.

```dotenv
QR_ORDERING_URL=https://qr.pahatud.com/order
```

All customer routes share this prefix:

```text
/api/qr/{qr_uuid}/branches/{branch_id}
```

## Menu

```http
GET /menu
```

Returns the restaurant, branch, table, categories, active menu items and variants, and active
payment methods.

## Add an item

```http
POST /carts/{cart_session_uuid}/items
Content-Type: application/json

{
  "product_id": 15,
  "quantity": 2,
  "instruction": "No onions",
  "is_not_available": "remove_item",
  "variants": [
    {"variant_id": 43, "product_detail_id": 31}
  ]
}
```

The server derives the restaurant, branch, table, prices, commissions, and totals. IDs or prices
from another restaurant are rejected.

## Cart summary

```http
GET /carts/{cart_session_uuid}/summary
```

An unknown cart UUID returns an empty summary for the QR context.

## Change quantity or remove an item

```http
PATCH /carts/{cart_session_uuid}/items/{cart_item_id}
Content-Type: application/json

{"quantity": 3}
```

Set `quantity` to `0` to remove the item.

## Guest checkout

```http
POST /carts/{cart_session_uuid}/checkout
Content-Type: application/json

{
  "full_name": "Guest Customer",
  "mobile": "09171234567",
  "email": "guest@example.com",
  "payment_method_id": 3
}
```

Checkout is idempotent for an already-submitted cart. It creates a dine-in order with no user
account, attaches the branch/table from the QR code, and notifies the merchant through the existing
order notification flow.

## Merchant QR provisioning

These routes use the existing merchant session/API authentication checks:

```text
GET   /api/merchant/location/{branch}/qr-ordering-codes
POST  /api/merchant/location/{branch}/qr-ordering-codes
PATCH /api/merchant/location/{branch}/qr-ordering-codes/{qr_code}
```

Create or regenerate a table QR code:

```json
{
  "dining_table_id": 7,
  "label": "Patio table 7",
  "expires_at": null
}
```

Omit `dining_table_id` to create a branch-level QR code. Re-posting for the same branch/table
rotates the UUID and invalidates the previous QR code.

Run the migration before using these endpoints:

```bash
php artisan migrate
```
