# Admin Bookings

## Purpose

`/data/dashboard/bookings` is the dedicated delivery-booking operations page for job orders that are separate from marketplace restaurant orders.

## Features

- Automatically refreshed booking queue
- Booking details and delivery information
- Rider assignment
- Booking-status updates
- Clear empty and loading states

## Data source

The Vue booking component requests `GET /api/dashboard/booking/list`. Rider and status changes use the authenticated booking update endpoints under `/api/data/dashboard/booking/update/{booking}`.

## Primary actions

- **Overview** returns to the business dashboard.
- **New booking** opens the manual booking form.
- Assign riders and update delivery progress from the queue.

## Main files

- `resources/views/dashboard/pages/bookings/index.blade.php`
- `resources/js/components/dashboard/pages/report/BookingComponent.vue`
- `app/Http/Controllers/Api/Admin/BookingController.php`
