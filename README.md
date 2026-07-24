# Pahatud

Pahatud is a multi-vendor delivery and voucher marketplace built on Laravel. It combines
on-demand restaurant food delivery, a flower store marketplace, coupon/voucher deals, and a
courier/errand booking service — with a customer web app, merchant dashboard, admin back office,
and dedicated mobile APIs for customers, merchants, and delivery riders.

## Features

### Customer-facing (web)

- **Restaurant marketplace** — browse restaurants, view a restaurant's page and menu, search by
  name/tag/sector, order food for delivery.
- **Flower store marketplace** — browse flower shops and order online.
- **Coupons & vouchers** — merchant-issued vouchers/deals surfaced across the marketplace.
- **Errand/booking requests** — request a courier-style booking (pickup/drop-off), track and
  cancel bookings.
- **Cart & checkout** — add items to cart, apply coupon codes, choose a delivery address, pick a
  payment method, SMS verification at checkout, order confirmation.
- **Order & booking history** — view past orders and bookings, view a statement of account (SOA).
- **Categories & partner directory** — browse partners by category, partner detail pages, a
  "become a partner" info page.
- **Location-based delivery** — Google Maps-based distance/place lookups to enforce delivery
  radius and calculate delivery distance.
- **Accounts** — registration, login, password reset, email verification, Facebook social login.
- **Static/info pages** — about us, contact us, privacy policy, terms of use, fraud prevention,
  payment methods.
- **Newsletter signup.**

### Merchant dashboard

- Merchant self-registration, login, and password reset, with an admin verification step before
  going live.
- Manage products, product add-ons, and product variants.
- Manage categories and branch/store locations.
- Manage vouchers/deals.
- Manage incoming and previous orders.
- Sales reports (today's sales, sales report, statement of account).
- Store profile management, including logo/banner upload.

### Admin back office

- Manage orders, bookings, riders, customers, and merchants from a single dashboard.
- Verify merchants, toggle merchant online status, set commission rate, configure pre-order
  settings, and trigger merchant password resets.
- Create and assign new bookings, assign riders to orders/bookings.
- Reporting across orders, bookings, and riders.
- Admin can log in *as* a merchant for support/troubleshooting.

### Mobile APIs

Dedicated, token-authenticated JSON APIs power three companion apps:

- **Customer app** — home feed, restaurant browsing/search, cart, checkout (with SMS
  verification), order history and cancellation, address management, errand booking.
- **Rider app** — login, push-notification token registration, view/accept delivery jobs and
  bookings (including by date).
- **Merchant/store app** — login, push-notification token registration, view/accept bookings.

## Tech stack

- **Backend**: PHP 8.2, Laravel 12, MySQL, Laravel Sanctum, Spatie Laravel Permission
  (roles/permissions), Intervention Image.
- **Frontend**: Vue 2 component islands (mounted into Blade views, not a full SPA) bundled with
  Vite, Bootstrap 5, Tailwind CSS, SCSS.

## Getting started

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure your database connection in `.env`. This app's schema (partners, products, coupons,
bookings, orders, etc.) is not fully represented in `database/migrations/` — most tables are
expected to already exist in your database rather than being created by `php artisan migrate`.
See `CLAUDE.md` for the details.

```bash
composer run dev   # runs the app server, queue listener, log tailer, and Vite together
```

or, individually:

```bash
php artisan serve
npm run dev        # Vite dev server with HMR
```

For a production asset build:

```bash
npm run build
```

## Testing

```bash
composer test
# or
php artisan test
```

See `CLAUDE.md` for known gaps in the current test suite.

## Learn more

For contributor-facing conventions, directory layout notes, and known environment quirks, see
[`CLAUDE.md`](./CLAUDE.md).
