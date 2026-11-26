# QuickPay POS setup

QuickPay supports direct QR Ph, GCash, and Maya payments and hosted checkout at the cashier POS. Each restaurant uses the key assigned to its Pahatud partner ID.

1. Run `php artisan migrate` as part of deployment. The migration adds Maya, GCash, QR Ph, and Transfer to bank in **Dashboard → Payment Gateways**.
2. Set each method’s **QuickPay channel** in the dashboard. Enable **Active** and **QR ordering** to show it in cashier POS and guest QR checkout for merchants with configured credentials. QuickPay methods are excluded from Application checkout.
3. Set the merchant API keys by Pahatud partner ID. Each key needs `checkouts` and `read` scopes. Use test keys in non-production environments and live keys in production.
4. Run the Laravel scheduler to verify outstanding payments even after a cashier or guest leaves the checkout screen.

```dotenv
QUICKPAY_MERCHANT_KEYS='{"17":"qp_test_replace_me"}'
```

Keep keys in server-side deployment secrets. Allow the app server to connect to `https://quickpayph.com`.

## Checkout behavior

The POS and guest QR app use the channel configured on the selected payment method. QuickPay must offer that channel on the merchant account. Hosted checkout can also be configured in the dashboard.

- **POS.** Select QuickPay and generate a payment code for the customer to scan. In GCash or Maya mode, customers scan the code in the corresponding app. In hosted mode, the code opens QuickPay in the customer’s browser. The cashier screen checks the gateway status automatically and can check on demand. Once verified, the payment appears on the bill and receipt.
- **Guest QR ordering.** Customers review the total, choose a payment option, and open QuickPay to pay. Pahatud submits a prepaid order only after the gateway confirms payment.
- **Amounts.** QuickPay accepts whole-peso amounts and applies the merchant account's checkout limits. For a bill with centavos, the cashier can confirm a discount equal to those centavos to charge the next lower whole peso. The discount is recorded on the receipt after QuickPay verifies payment.
- **Pending, expired and rejected.** A pending checkout blocks edits or another tender against the same bill. Cancel returns to payment selection, hides the issued code, and stops automatic status checks. Use Check payment status to verify expiry before taking another payment. If a network request fails, status checks retry the same checkout request to avoid duplicate charges.
- **Refunds.** The supplied API contract does not include an automated refund operation. Handle paid-order refunds through the merchant's payment operations.

Use QuickPay test mode to confirm QR display, receipts, and payment reconciliation before enabling live keys.
