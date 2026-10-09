# PahatudFood Support Center

The customer widget is mounted in the shared customer Blade layouts. Signed-in customers can create tickets, attach screenshots, browse their requests, and reply. Guests are directed to sign in. Admins use **Support Tickets** in the dashboard menu (`/data/dashboard/support`). The existing `admin` role determines staff access and assignment eligibility.

## Deployment

Apply the isolated migration, then build assets:

```sh
php artisan migrate --path=database/migrations/2026_10_02_000000_create_support_tables.php --force
npm ci
npm run build
```

The migration creates only `support_tickets`, `support_messages`, `support_attachments`, and `support_alerts`. It does not change legacy users or orders. The real auth schema must already exist; never rebuild the marketplace database with `migrate:fresh`.

Screenshots are stored as base64 in the database and served only through authenticated, ownership-checked downloads. They are limited to three JPEG/PNG/WebP files of 2 MB each per message. No storage symlink is needed. Include the support tables in database backups. PHP/web-server upload limits should accommodate at least 8 MB requests and 2 MB files.

## Workflow and notifications

- Ticket numbers use the `PF-` prefix and a unique ULID.
- Categories: Orders & Delivery, Billing & Payments, Refunds & Cancellations, Account Issues, General Inquiries.
- Statuses: Open, In Progress, Waiting for Customer, Resolved, Closed.
- Priorities: Low, Normal, High, Urgent.
- Customer replies reopen Waiting for Customer or Resolved tickets. Closed tickets require a new request.
- Staff may add internal notes even on closed tickets. Internal text and attachments never appear in customer responses or downloads.
- Notifications are persistent **in-app alerts**, refreshed every 30 seconds while the page is visible. They are not email, SMS, or device push notifications. No queue worker or external notification provider is required.
- All current admins receive new-request and customer-reply alerts; newly assigned staff receive assignment alerts. Customers receive staff-reply and ticket-update alerts. Internal notes do not notify customers.
- Opening a ticket marks only the viewer's alerts for that audience as read. Conversations can be refreshed explicitly; unread badges indicate newly arriving activity without replacing a draft reply.
- Filters and ticket lists are paginated. Related orders are loaded in pages and always scoped to the current customer.

The widget reserves mobile bottom-navigation space and moves above existing `.footer-sticky`, `.fixed-bottom`, `.mobile-bottom-nav`, `.bottom-nav`, and scroll-to-top controls. New fixed checkout/navigation elements should use one of these selectors or be added to `SupportCenter.vue`'s positioning logic.

## Verification

```sh
php artisan test --compact tests/Feature/SupportCenterTest.php
npm run build
```

The support tests use disposable in-memory SQLite fixtures and the real `App\User` auth model. They exercise permissions, spoofed customer/order fields, internal notes and downloads, upload validation, status changes, assignment restrictions, filtering, and unread notifications without touching the local marketplace schema.

## Customer dashboard and shared sign-in

Customers use `/dashboard` for the account overview, `/profile/orders` for their order history, `/profile` to update first name, last name, email, and mobile number, and `/profile/support` for the full support inbox. All use Laravel's existing `web` guard and `App\User` account. The same name dropdown is available in public and account headers on desktop and mobile.

The website login form (`/login/submit`), standard `/login` POST, and website modal (`/api/login/submit`) share the customer sign-in handler. Successful login rotates the session, preserves the guest basket, and returns to an intended protected page or defaults to the dashboard. Entering support while signed out sets the support inbox as the intended page. The separate mobile API account endpoints are unchanged.

Profile updates are limited to the current user and explicitly allowed fields. Order details check both cart and order ownership before loading addresses, items, or payments. Log Out is a CSRF-protected POST that clears the website session for both support and customer pages. `/account` remains an alias to `/dashboard`; booking history remains at `/profile/bookings`.

Additional verification: `php artisan test --compact tests/Feature/CustomerAccountTest.php tests/Feature/SupportCenterTest.php`.
