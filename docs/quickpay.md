# QuickPay POS setup

QuickPay supports direct QR Ph, GCash, and Maya payments and hosted checkout at the cashier POS. Each restaurant uses the key assigned to its Pahatud partner ID.

1. Run `php artisan migrate` as part of deployment. The migration adds Maya, GCash, QR Ph, and Transfer to bank in **Dashboard → Payment Gateways**.
2. Set each method’s **QuickPay channel** in the dashboard. Enable **Active** and **QR ordering** to show it in cashier POS and guest QR checkout for merchants with configured credentials. QuickPay methods are excluded from Application checkout.
3. Set the merchant API keys by Pahatud partner ID. Each key needs `checkouts` and `read` scopes. Use test keys in non-production environments and live keys in production.
4. Run the Laravel scheduler to verify outstanding payments even after a cashier or guest leaves the checkout screen.

```dotenv
PAYMENT_GATEWAY=quickpay
QUICKPAY_MERCHANT_KEYS='{"17":"qp_test_replace_me"}'
```

Keep keys in server-side deployment secrets. Allow the app server to connect to `https://quickpayph.com`.

`PAYMENT_GATEWAY` selects the online payment driver and defaults to `quickpay`. When another
gateway integration is added to `config/payment.php`, deployments can select it by changing this
value and rebuilding Laravel's configuration cache (`php artisan config:cache`).

## Checkout behavior

The POS and guest QR app use the channel configured on the selected payment method. QuickPay must offer that channel on the merchant account. Hosted checkout can also be configured in the dashboard.

- **POS.** Select QuickPay and generate a payment code for the customer to scan. In GCash or Maya mode, customers scan the code in the corresponding app. In hosted mode, the code opens QuickPay in the customer’s browser. The cashier screen checks the gateway status automatically and can check on demand. Once verified, the payment appears on the bill and receipt.
- **Guest QR ordering.** Customers review the total, choose a payment option, and open QuickPay to pay. Pahatud submits a prepaid order only after the gateway confirms payment.
- **Amounts.** QuickPay accepts whole-peso amounts and applies the merchant account's checkout limits. For a bill with centavos, the cashier can confirm a discount equal to those centavos to charge the next lower whole peso. The discount is recorded on the receipt after QuickPay verifies payment.
- **Pending, expired and rejected.** A pending checkout blocks edits or another tender against the same bill. Cancel returns to payment selection, hides the issued code, and stops automatic status checks. Use Check payment status to verify expiry before taking another payment. If a network request fails, status checks retry the same checkout request to avoid duplicate charges.
- **Refunds.** The supplied API contract does not include an automated refund operation. Handle paid-order refunds through the merchant's payment operations.

Use QuickPay test mode to confirm QR display, receipts, and payment reconciliation before enabling live keys.

## Mobile delivery payments

The customer app and assigned rider share one QuickPay checkout for an existing cash-on-delivery order. Payment is available once the rider reaches the customer. The customer opens the hosted link, or the rider displays the QR code for the customer to scan. This flow charges the full, whole-peso COD amount already recorded on the delivery. Orders with centavos continue through the cash flow.

### API routes

| Operation | Customer | Rider |
| --- | --- | --- |
| Read current payment and available methods | `GET /api/v1/user/orders/{orderId}/payment` | `GET /api/v1/rider/deliveries/{deliveryId}/payment` |
| Start payment | `POST /api/v1/user/orders/{orderId}/payment` | `POST /api/v1/rider/deliveries/{deliveryId}/payment` |
| Verify with QuickPay | `POST /api/v1/user/orders/{orderId}/payment/status` | `POST /api/v1/rider/deliveries/{deliveryId}/payment/status` |

Send the usual API request header, bearer token, and `Accept: application/json`. Use the customer token for the order owner or the v1 rider token for the approved rider assigned to the delivery. The rider route accepts the delivery UUID or the order ID.

Start payment with a method ID from `payment_methods`, the displayed `cod_centavos`, and a client-generated UUID. Reuse that UUID when retrying the same request after an interruption. Use a new UUID only for an intentional new attempt after expiry or rejection.

```json
{
  "payment_method_id": 8,
  "expected_total_centavos": 12000,
  "idempotency_key": "d71f25e5-7fc1-4f54-8afb-1d6f9f15943c"
}
```

- **No checkout yet.** The response has `payment: null`. Show online payment only when `payment_methods` contains an option.
- **Pending.** Start and status requests return HTTP 202. Open `payment.checkout_url` or display `payment.qr_image` or `payment.qr_payload` when supplied. Verify through the status endpoint after the payer returns and at most every five seconds while the payment screen is open. Disable cash confirmation while `cash_collection_blocked` is true. A browser redirect does not confirm payment.
- **Paid.** The verified response has `payment.status: "paid"` and `cod_centavos: 0`. The rider can proceed with the usual proof and delivery steps.
- **Expired or rejected.** The rider can take cash through the usual COD endpoint or either app can start a new online attempt. A 409 indicates a conflict such as an active checkout or changed amount. Read the current payment before retrying. After a timeout or 502/503, check status or retry the original request with the same UUID.

### Enablement

Run the migration, keep the QuickPay reconciliation scheduler running, and set `MOBILE_DELIVERY_QUICKPAY_ENABLED=true` to enable new checkouts. The API uses active QuickPay methods enabled for QR ordering and the configured merchant key for the order's partner. Existing attempts remain verifiable when the flag is off. Continue using the existing rider wallet top-up and remittance endpoints for those operations.

The full charge settles through the merchant's QuickPay account, including any delivery fee in the COD amount. Confirm how merchants will settle the rider's share and handle refunds before enabling live payments. The API records the QuickPay payment and clears cash due at drop-off.
