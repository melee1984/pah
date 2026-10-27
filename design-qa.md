# Merchant Dashboard Design QA

- Source visual truth: `/Users/larryparba/Desktop/Screenshot 2026-08-28 at 10.38.40 AM.png`
- Implementation route: `http://127.0.0.1:8000/merchant/dashboard`
- Source pixels: 3414 × 1876
- Intended CSS viewport: approximately 1707 × 938 at 2× density
- Implementation pixels: unavailable
- State: signed-in merchant dashboard, empty-order state
- Density normalization: source interpreted as a 2× desktop capture; implementation normalization could not be completed

## Full-view comparison evidence

The source screenshot was opened and inspected. The implementation route was opened in the in-app browser, but redirected to `/merchant/dashboard/login` because that browser does not have the merchant session. The connected Chrome browser was unavailable, so an authenticated implementation screenshot could not be captured.

## Focused region comparison evidence

Blocked for the same authentication reason. The intended focused checks are the merchant shell/sidebar, the four primary KPI cards, the seven-day chart and fulfilment card, the revenue strip, the store-status control, and the live order table.

## Findings

- [Blocked] Authenticated rendered implementation is unavailable for comparison.
  - Impact: Typography, spacing, responsive layout, data states, and console behavior cannot be visually certified against the reference and the main dashboard design system.
  - Resolution: Sign in to the merchant account in the in-app browser and repeat the browser capture and comparison at the intended viewport.

## Comparison history

- Pass 1: source inspected; implementation redirected to merchant login; no visual fixes could be evidence-tested.

## Implementation checklist

- Capture the authenticated merchant dashboard at the source viewport.
- Check the browser console and merchant summary/order API requests.
- Compare full view and focused dashboard regions.
- Fix any P0/P1/P2 differences and repeat the comparison.

final result: blocked
