# Admin Overview

## Purpose

`/data/dashboard` is the system-level business overview. It is intended for quick monitoring rather than day-to-day order editing.

## Information shown

- Today's, weekly, and monthly completed-order sales
- Total and incoming order counts
- Seven-day completed-sales trend
- Current order-status distribution
- Lifetime revenue, platform commission, estimated merchant payout, and average order value
- Top merchants ranked by completed-order revenue
- Recent order activity
- Completion rate, cancellation rate, active merchants, and active agents

## Data rules

Sales and revenue widgets use completed orders. If `delivered_at` is unavailable, `submitted_at` is used as the completion-date fallback. Financial totals are calculated using the existing cart summary logic so they remain consistent with order reporting.

## Primary actions

- **Incoming orders** opens the dedicated Orders page.
- **View reports** opens the sales report.
- The bottom navigation cards open Orders and Bookings.

## Main files

- `app/Http/Controllers/Admin/DashboardController.php`
- `resources/views/dashboard/pages/main.blade.php`
- `public/css/admin-portal.css`
