# CLAUDE.md

Guidance for working in this repo. It's a Laravel marketplace/voucher platform for Pahatud
(partners/merchants, coupons & vouchers, bookings, a flower store, restaurant ordering, cart &
checkout, delivery riders).

## Stack

- **Backend**: PHP 8.2, Laravel 12, MySQL, Spatie Laravel Permission (roles/permissions),
  Laravel Sanctum, Intervention Image.
- **Frontend**: Vue 2 + Vite (`laravel-vite-plugin`, `@vitejs/plugin-vue2`), Bootstrap 5,
  Tailwind, SCSS. This is **not** an Inertia SPA — `inertiajs/inertia-laravel` is installed and
  `HandleInertiaRequests` middleware runs, but nothing actually renders Inertia pages. The real
  frontend is classic Vue 2 component islands mounted into Blade views via three entry points:
  `resources/js/app.js`, `dashboard.js`, `merchant.js` (see `vite.config.js`).

## Common commands

```bash
composer install && npm install   # install dependencies
php artisan key:generate          # required once — .env ships with APP_KEY blank
composer run dev                  # serve + queue:listen + pail + vite, all concurrently
npm run build                     # production asset build
composer test                     # alias for `php artisan test` (PHPUnit)
vendor/bin/pint --test            # style check, dry run (see "Known gaps" — not clean today)
```

## Database — read this before touching schema

Only **6** Laravel migrations exist (`users`, `cache`, `jobs`, permission tables,
`personal_access_tokens`). The real application schema — `partners`, `products`, `coupon`,
`category`, `sector`, `bookings`, `cart`, `order`, `rider`, etc. (~49 tables) — lives directly in
the local MySQL database and is **not** reproducible via `php artisan migrate` on a blank
database. Local dev connects to a MySQL database named `pahatud` (root, no password, see `.env`).

Treat schema changes to the marketplace tables as changes to that live database, not something
`migrate:fresh` can rebuild. Do not assume a clean environment has the full schema.

**The local `pahatud` copy is missing a `users` table entirely.** Two different Eloquent models
map to it by convention:
- `App\User` (legacy, `app/User.php`) — the actual auth-guard model (`config/auth.php` →
  `providers.users.model`), used by `Auth::routes()` in `routes/web.php`. Its custom
  `RegisterController` requires `firstname`, `lastname`, `mobile` (not `name`).
- `App\Models\User` (`app/Models/User.php`) — the stock Laravel Breeze scaffold model, used only
  by the generated `tests/Feature/Auth/*` and `ProfileTest` suite.

Because `pahatud` has no `users` table at all, the real login/registration flow (`App\User`)
cannot currently be exercised against the local DB. If you need to test real auth locally, you'll
need the actual production `users` schema (firstname/lastname/mobile/account_type_id/etc. —
see `app/User.php` `$fillable`) — don't guess at it and create one blind.

## Directory conventions

- Domain models live directly under `app/*.php` (`app/Partners.php`, `app/Coupon.php`,
  `app/Category.php`, ...) rather than `app/Models/` — only `User.php` is under `app/Models/`.
  `app/Model/` (singular) holds a separate set of classes (`Cart`, `Bookings`, `Orders`, `Rider`).
- Controllers are organized by domain under `app/Http/Controllers/{Admin,Merchant,Api,Booking,
  Checkout,Flower,Restaurant,Auth}`.
- Auth/middleware: standard `Auth::routes()` (laravel/ui-based, not Breeze's) plus custom
  middleware groups `admin`, `merchant`, `logged`, `isRequest` registered in `bootstrap/app.php`.

## Known gaps (found during verification, 2026-07-24)

- **Test suite is stale relative to the app.** After fixing real infra bugs (see below), the
  suite went from 24 failed/1 passed to 14 failed/11 passed. The remaining 14 failures are not
  bugs in the app — they're `laravel/breeze`-generated scaffolding that never got updated to
  match this app's customization:
  - Several tests (`AuthenticationTest`, `Email/PasswordConfirmation/PasswordUpdate/ResetTest`)
    redirect-assert against a named route `dashboard`, which doesn't exist in this app.
  - `RegistrationTest` posts `name`/`email`/`password`, but the real `RegisterController`
    requires `firstname`/`lastname`/`mobile` (see above) — validation fails.
  - `ExampleTest` hits `/`, which is the real marketplace homepage here (not Laravel's default
    welcome page) and queries the `sector` table — not in migrations, so it 404s/errors against
    a fresh test DB.
  - Fixing these properly means rewriting the Auth test suite around this app's actual
    registration fields and routes, or deleting the irrelevant ones — a judgment call, not done
    here.
- **`vendor/bin/pint --test` reports style issues in ~122 files.** Pint (Laravel's formatter) is
  installed but has never been run/enforced on this codebase. Running `vendor/bin/pint` would
  reformat most of `app/`, `bootstrap/`, `database/` — a large, unrelated diff, so left alone.
- **`composer.json`'s `post-update-cmd` force-republishes vendor assets** (`vendor:publish
  --tag=laravel-assets --force`) on every `composer update`. This is what produced a duplicate,
  never-applied `personal_access_tokens` migration (fixed — see below) and will do so again for
  any package whose vendor migration gets force-republished. Worth revisiting if it recurs.

## Fixes applied during this verification pass

- `composer.lock` was out of sync with `composer.json` (`laravel/ui` and `knuckleswtf/scribe`
  were required but not installed in `vendor/`) — ran `composer update knuckleswtf/scribe` to
  resync; this also pulled in current framework/dependency versions.
- Deleted `database/migrations/2025_11_14_130617_create_personal_access_tokens_table.php` — an
  unapplied, near-duplicate of the already-applied `2025_06_09_052923_...` migration, which broke
  100% of DB-touching tests with `table "personal_access_tokens" already exists`.
- Restored `Schema::create('users', ...)` in
  `database/migrations/0001_01_01_000000_create_users_table.php` (was commented out, wrapped in
  `if (!Schema::hasTable('users'))` for safety) — without it, every fresh/test database has no
  `users` table at all, so any test touching auth/profile failed with `no such table: users`.
  This only affects fresh databases; it's a no-op against `pahatud` or production since the
  migration is already recorded as applied there.

## Verified working (2026-07-24)

- `php artisan route:list` — all 258 routes resolve, no controller errors.
- `npm run build` — Vite build succeeds cleanly.
- Booted `php artisan serve` against the real `pahatud` DB and confirmed `/`, `/login`,
  `/register` all return HTTP 200 with real rendered content (no errors in `storage/logs/laravel.log`).
