# Pahatud

Pahatud is a multi-vendor delivery and voucher marketplace built on Laravel. It combines
on-demand restaurant food delivery, a flower store marketplace, coupon/voucher deals, and a
courier/errand booking service — with a customer web app, merchant dashboard, admin back office,
and dedicated mobile APIs for customers, merchants, and delivery riders.
The same application also contains a separately authenticated **Pahatud Agent Portal** for
restaurant acquisition agents, enrollment attribution, and order-based commission reporting.

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

### Agent portal

- Separate agent sign-in at `/agent/login` and dashboard at `/agent/dashboard`.
- Agent-owned restaurant directory and restaurant enrollment.
- Dashboard totals for restaurants, generated orders, completed order value, lifetime
  commission, current-month commission, and recent transactions.
- Immutable commission snapshots for qualifying orders, including the rate used at completion.
- Date-range and status-filtered commission reporting.
- Automatic reversal when a previously commissioned order is cancelled.

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

Use PHP 8.2+ and Node.js 18+ (Vite 5 does not support Node.js 16).

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

Deploy the generated `public/build/manifest.json` and `public/build/assets/` directory together
with the application. If an older production manifest does not yet contain the Agent Portal CSS
entry, the Agent Portal views safely inline `resources/css/agent.css` as a temporary fallback
instead of returning a Vite manifest exception. A fresh production build is still recommended for
cacheable, fingerprinted assets.

## Agent Portal

### Overview

The Agent Portal is part of the main Laravel deployment and database, but uses a separate
authentication guard and a separate `agents` table. An agent never signs in through the customer
or merchant login. All portal queries begin from the authenticated agent, so one agent cannot
view another agent's restaurants, commissions, dashboard totals, or reports.

Portal URLs:

| Page | URL | Access |
|---|---|---|
| Login | `/agent/login` | Guest agents |
| Dashboard | `/agent/dashboard` | Authenticated agent |
| Restaurant list | `/agent/restaurants` | Authenticated agent |
| Enroll restaurant | `/agent/restaurants/enroll` | Authenticated agent |
| Commission report | `/agent/reports` | Authenticated agent |

The frontend is rendered with Blade and the dedicated `resources/css/agent.css` Vite entry. It
uses Pahatud's current red (`#ef3b35`), white, warm-neutral background, typography, and existing
logo assets. It is responsive and uses a bottom navigation bar on smaller screens.

### Installation and setup

Install the application normally, configure `.env`, and run the migrations and asset build:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

The Agent Portal migration is
`database/migrations/2026_08_27_000000_create_agent_portal_tables.php`. It creates the two new
portal-owned tables and adds `agent_id` to `partners`. The migration checks that the legacy
`partners` table exists before altering it, which is important because the full marketplace
schema is not reproduced by this repository's migrations. On an existing Pahatud environment,
confirm that the real marketplace schema has been imported before running this migration.

If migrations cannot be run on the hosting environment, the equivalent paste-ready MySQL script
is available at `database/sql/agent_portal.sql`. Select the live Pahatud database in phpMyAdmin or
your database client, paste the complete script, and execute it once. The script safely skips
tables and the `partners.agent_id` column when they already exist.

For local development:

```bash
composer run dev
```

The local MySQL database used by the established project is named `pahatud`. A representative
configuration is:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pahatud
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=database
AGENT_COMMISSION_PERCENTAGE=30
```

Do not run `migrate:fresh` against a real Pahatud database. Most legacy marketplace tables,
including `partners`, `order`, `cart`, and `cart_details`, exist only in that database and are not
fully defined by this repository's migrations.

### Agent authentication and account creation

The `agent` session guard in `config/auth.php` uses the `App\Agent` model and the `agents`
provider. Login is protected by CSRF validation, session regeneration, a five-attempt-per-minute
rate limit, active-account enforcement, and Laravel's password hashing. Logout is POST-only and
invalidates the session.

Create an agent interactively so the password is not written into source code or a shell command:

```bash
php artisan agent:create "Juan Dela Cruz" juan@example.com --mobile=09171234567 --commission=30
```

The command securely prompts for a password. An inactive agent cannot sign in even with a valid
password. Accounts may be deactivated by setting `agents.active` to `0` through an authorized
administrative workflow.

### Agent, restaurant, and order relationship

The application's existing restaurant entity is `App\Partners` and the physical table is
`partners`. The ownership chain is:

```text
Agent (agents.id)
└── Restaurants (partners.agent_id)
    └── Orders (order.partner_id)
        └── Agent Commission (agent_commissions.order_id)
```

Relationships are defined on the models as `Agent::restaurants()`, `Partners::agent()`,
`Partners::orders()`, `Partners::agentCommissions()`, and `Orders::agentCommission()`.
Controllers never accept an arbitrary agent ID from a form. Enrollment is always created through
the authenticated agent's `restaurants()` relationship, which supplies `partners.agent_id`
server-side.

### Restaurant enrollment

Agents enter the restaurant name, business email, mobile/telephone, city, complete address, and
an optional description, together with the merchant contact's first and last name. The server
validates the request and, in one database transaction, creates the merchant `users` record,
generates a unique restaurant slug, fills `search_string`, attaches both `partners.user_id` and
the logged-in `partners.agent_id`, and creates the restaurant as inactive.

The initial user password is a random, unusable value. A one-time invitation containing a private
setup link is sent to the registered business email address. Only the SHA-256 hash of the token is
stored in `restaurant_invitations`; the link expires after 72 hours by default and becomes invalid
immediately after use. The restaurant contact uses it to confirm their name and mobile number and
choose their own password. Following setup, the contact is signed into the existing Pahatud
merchant flow.

New enrollments are shown as **Under review** until the existing Pahatud admin process activates
and verifies the partner. The invitation creates login credentials but does not bypass restaurant
review or activation.

Configure invitation lifetime with `RESTAURANT_INVITATION_EXPIRE_HOURS` in `.env`. Mail delivery
uses Laravel's configured `MAIL_MAILER`; production must have valid SMTP or another supported mail
transport configured.

### Order and commission relationship

`App\Observers\OrderObserver` watches Eloquent saves on the legacy `App\Model\Orders\Orders`
model and delegates to `App\Services\AgentCommissionService`. A commission is created when:

1. The order is delivered (`order.order_status_id = 6`) or has `delivered_at` populated.
2. The order's `partner_id` resolves to a restaurant with `partners.agent_id`.
3. No `agent_commissions` entry already exists for that order.

`agent_commissions.order_id` is unique. Re-saving or replaying a delivered-order event therefore
does not pay an agent twice. The service computes the same final customer order value used by the
cart: item subtotal plus delivery fee, less cart-level and item-level discounts.

The normal flow is:

```text
Customer submits order
        ↓
Restaurant receives and fulfills order
        ↓
Order becomes delivered/successful
        ↓
System reads order.partner_id → partners.agent_id
        ↓
System snapshots the agent's current commission percentage
        ↓
Commission amount is calculated and stored as pending
        ↓
Agent dashboard and report read the new ledger transaction
```

If a commissioned order later moves to the current legacy cancelled status
(`order_status_id = 7`), the existing transaction is retained and marked `reversed`, with a
timestamp and reason. This provides an audit trail. Failed or refunded workflows should map the
order to the cancelled/non-qualifying state and save the Eloquent order so the same reversal path
runs. Code paths that bypass Eloquent with direct SQL must explicitly invoke
`AgentCommissionService::sync($order)` after changing the order state.

### Commission formula and examples

```text
commission_amount = round(order_amount × (commission_percentage ÷ 100), 2)
```

For Inasal with a ₱100.00 completed order and a 30% rate:

```text
₱100.00 × (30 ÷ 100) = ₱30.00 agent commission
```

For a ₱1,250.50 completed order and a 12.5% rate:

```text
₱1,250.50 × (12.5 ÷ 100) = ₱156.31 agent commission
```

Money and percentages are stored as fixed-precision decimal values. Each transaction stores both
`order_amount` and `commission_percentage`; reports never recalculate an old commission from the
agent's current rate.

### Configuring commission percentages

`AGENT_COMMISSION_PERCENTAGE` is the default rate suggested for newly created agent accounts. It
does not rewrite existing agents or historical transactions. Set it in `.env`, then clear cached
configuration when it changes:

```bash
php artisan config:clear
```

Set an individual agent's rate for future qualifying orders with:

```bash
php artisan agent:set-rate juan@example.com 25
```

Changing the rate only updates `agents.commission_percentage`. Existing rows in
`agent_commissions` keep their original percentage and amount. A restaurant enrollment does not
carry its own rate in the current implementation; the owning agent's rate is read at the moment
the order qualifies.

### Commission statuses

| Status | Meaning | Included in earned dashboard totals? |
|---|---|---|
| `pending` | Automatically created; awaiting operations review or payout | Yes |
| `approved` | Validated and approved for payout | Yes |
| `paid` | Paid to the agent | Yes |
| `reversed` | Cancelled, refunded, or failed after commission creation | No |

Status changes from pending to approved or paid are operations/admin actions. Agents have
read-only access to status in their dashboard and reports.

### Reporting mechanics

The report defaults to the current calendar month in the application timezone and accepts
inclusive `from` and `to` dates plus an optional status. Each row shows the restaurant, source
order, one qualifying order, snapshotted order amount, snapshotted rate, commission amount,
qualification date, and status. Results are paginated at 25 transactions.

Totals are calculated over the complete filtered result, not only the visible page:

- **Total Orders** counts commission ledger rows.
- **Total Order Value** sums the snapshotted order values, including reversed rows so the report
  reconciles to the displayed ledger.
- **Total Agent Commission** excludes reversed rows.

### Database structure

#### `agents`

| Column | Purpose |
|---|---|
| `id` | Agent primary key |
| `name`, `email`, `mobile` | Agent identity and contact data |
| `password`, `remember_token` | Laravel session authentication |
| `commission_percentage` | Rate used by the next qualifying order |
| `active` | Login eligibility |
| `last_login_at` | Last successful portal login |
| `created_at`, `updated_at` | Audit timestamps |

#### `partners` addition

| Column | Purpose |
|---|---|
| `agent_id` | Nullable reference to the agent who enrolled the restaurant |

It remains nullable so existing Pahatud restaurants can continue without agent attribution.

#### `agent_commissions`

| Column | Purpose |
|---|---|
| `order_id` | Unique source order; prevents duplicate commission creation |
| `restaurant_id` | Restaurant snapshot reference (`partners.id`) |
| `agent_id` | Earning agent reference (`agents.id`) |
| `order_amount` | Qualifying order value at completion |
| `commission_percentage` | Agent rate snapshotted at completion |
| `commission_amount` | Rounded peso amount earned |
| `status` | `pending`, `approved`, `paid`, or `reversed` |
| `qualified_at` | When the order qualified |
| `reversed_at`, `reversal_reason` | Reversal audit data |
| `created_at`, `updated_at` | Ledger timestamps |

Numeric references to the legacy marketplace tables are indexed but intentionally do not add
database foreign-key constraints. This matches the project's live-schema constraints and avoids
making a fresh Laravel migration run pretend it can reproduce the legacy `partners` and `order`
tables.

#### `restaurant_invitations`

| Column | Purpose |
|---|---|
| `user_id` | Newly created partner user |
| `restaurant_id` | Enrolled restaurant; unique per invitation |
| `email` | Invitation recipient |
| `token_hash` | SHA-256 hash of the one-time token; the plain token is never stored |
| `expires_at` | Invitation expiry time |
| `accepted_at` | When account setup was completed |
| `created_at`, `updated_at` | Audit timestamps |

### Connection to the main Pahatud system

The portal is not a second service or a copied order database. It runs in the same Laravel
application and reads the same `partners`, `order`, `cart`, and `cart_details` records used by the
customer, merchant, admin, and rider surfaces. This gives immediate dashboard/report updates after
the main order model is saved as delivered while keeping agent login sessions isolated from
customer and merchant authentication.

Before deployment, apply the migration, create agent accounts, and ensure every commission-
eligible restaurant has the correct `partners.agent_id`. Orders completed before attribution do
not receive a commission automatically; only qualifying saves after attribution create ledger
entries.

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
