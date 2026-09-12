# Admin Orders

## Purpose

`/data/dashboard/orders` is the dedicated marketplace order operations page. It separates actionable order management from the business overview.

## Features

- Automatically refreshed order queue
- Separate active, completed, and cancelled tabs
- Incoming orders without riders are visually highlighted
- Order detail view with merchant, delivery, customer, item, and payment information
- Rider assignment
- Order-status updates
- Pagination for long order lists

## Data source

The Vue order component requests `GET /api/dashboard/order/list`. Updates use the existing authenticated rider and status endpoints under `/api/data/dashboard/update/{order}`.

## Primary actions

- **Overview** returns to the business dashboard.
- **Sales report** opens detailed revenue reporting.
- Select a rider or update an order status directly from the queue.

## Main files

- `resources/views/dashboard/pages/orders/index.blade.php`
- `resources/js/components/dashboard/pages/orders/OrdersComponents.vue`
- `app/Http/Controllers/Api/Admin/OrderController.php`
