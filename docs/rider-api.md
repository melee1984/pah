# Pahatud Rider API

The versioned rider API uses this base URL:

```text
/api/v1/rider
```

All requests currently retain the Flutter compatibility header:

```http
X-Admin-Request: apiRequestHandle001
Accept: application/json
```

Authenticated rider endpoints also require:

```http
Authorization: Bearer {access_token}
```

Application routes accept the application bearer token returned by
`POST /auth/register`. If a rider leaves an application in draft status, they
can sign in with `POST /auth/login` and use that response's `access_token` for
the application routes, including document uploads. The draft login response
also includes `application_id` for the application URL. Approved rider accounts
use the access token returned by `POST /auth/login`.

For a draft rider, `GET /me` includes `application_id` and an `application`
object with the saved personal, emergency contact, vehicle, payout, document,
and progress details. The `application` object uses the same shape as
`GET /applications/current`; document file paths and full payout account numbers
are not returned.

## Authentication and applications

```text
POST   /auth/login
POST   /auth/refresh
POST   /auth/logout
POST   /auth/logout-all
POST   /auth/otp/send
POST   /auth/otp/verify
POST   /auth/password/forgot
POST   /auth/password/reset
GET    /me
GET    /devices
POST   /devices
PATCH  /devices/{deviceId}
DELETE /devices/{deviceId}

POST   /applications
GET    /applications/current
GET    /applications/{id}
PATCH  /applications/{id}/personal
PATCH  /applications/{id}/emergency-contact
PATCH  /applications/{id}/vehicle
PATCH  /applications/{id}/payout-account
POST   /applications/{id}/documents
DELETE /applications/{id}/documents/{documentId}
POST   /applications/{id}/submit
POST   /applications/{id}/resubmit
GET    /applications/{id}/status
POST   /applications/{id}/activation/send
POST   /applications/{id}/activation/confirm
```

The legacy Flutter aliases remain:

```text
POST /api/rider/account/login
POST /api/rider/account/register
POST /api/rider/login/submit
POST /api/rider/register/submit
```

## Operations and location

```text
GET  /dashboard
GET  /availability
PUT  /availability
POST /availability/heartbeat
GET  /availability/schedule
PUT  /availability/schedule
GET  /zones
PUT  /zones/preferences
GET  /alerts
POST /location
POST /location/batch
GET  /location/config
```

Availability states are `offline`, `available`, `searching`, `on_break`, and
`active_delivery`. Approval middleware prevents unapproved, suspended, and
expired-document riders from accessing operational routes.

Exact rider coordinates are stored without ordinary application logging.

## Offers, deliveries, proof, and orders

```text
GET  /offers/current
GET  /offers
GET  /offers/{offerId}
POST /offers/{offerId}/accept
POST /offers/{offerId}/decline

GET  /deliveries/active
GET  /deliveries/{deliveryId}
GET  /deliveries/{deliveryId}/route
POST /deliveries/{deliveryId}/events
POST /deliveries/{deliveryId}/pickup/verify
POST /deliveries/{deliveryId}/customer/verify
POST /deliveries/{deliveryId}/cod/confirm
POST /deliveries/{deliveryId}/proof/uploads
POST /deliveries/{deliveryId}/proof
GET  /deliveries/{deliveryId}/proof/status
POST /deliveries/{deliveryId}/issues
POST /deliveries/{deliveryId}/calls
POST /deliveries/{deliveryId}/share-trip

GET /orders
GET /orders/{orderId}
POST /orders/{orderId}/decline
```

Declining an available order hides it from that rider's dashboard and order
list without removing it for other riders. Repeating the decline request is
safe and does not create duplicate decline or activity records. The action is
available from `GET /activity-logs?type=booking_declined`, including its
`order_id`.

Delivery event requests require a client-generated UUID. Replayed UUIDs are
idempotent. Illegal or out-of-order transitions return `409 Conflict` with the
current state and allowed events.

Offer responses contain only approximate drop-off information. Full customer
and address information is returned only after the offer is accepted.

### Two-order batching

When batching is enabled, a rider with one active delivery can receive a second
offer only when all of these conditions remain true at dispatch and acceptance
time:

- the latest rider location is no older than the configured location age;
- the rider has fewer than two active deliveries;
- the second pickup is within the configured detour from the remaining route;
- the total added distance and delay to the existing delivery are within the
  configured limits.

The route calculation preserves pickup-before-drop-off ordering for every
delivery and selects the shortest valid stop sequence. It currently uses
straight-line distances and a configurable average speed. A road-routing
provider can replace that calculation later without changing the app response.

An eligible offer includes:

```json
{
  "offer_type": "add_on",
  "route_compatible": true,
  "route_impact": {
    "is_add_on": true,
    "active_order_count": 1,
    "resulting_order_count": 2,
    "pickup_detour_meters": 350,
    "added_distance_meters": 2100,
    "added_eta_seconds": 302,
    "existing_order_delay_seconds": 180
  }
}
```

Exact coordinates, addresses, and the ordered stop list are withheld until the
offer is accepted. If the rider moves and the route is no longer eligible,
`route_compatible` becomes `false` and acceptance returns `409 Conflict`.

`GET /deliveries/active` retains the legacy singular `delivery` field and now
also returns `deliveries` and `route`. The rider app should render the
`route.stops` array in order, highlight `route.stops[0]` as the next action,
and show the add-on's extra time, distance, and earnings before acceptance.

`GET /deliveries/{deliveryId}/route` now returns the same ordered `stops`
array plus the backward-compatible `destination` field for the next stop.
Completing one order keeps the rider in `active_delivery` while another order
in the batch remains active.

The delivery fee and its snapshotted Pahatud commission are returned as
`earnings_centavos`, `commission_percentage`, and `commission_centavos`. A
rider can accept only when their prepaid wallet can cover that commission. An
insufficient balance returns `409 Conflict` with the amount required. When the
delivery is completed, the commission is deducted once and recorded as a
`pahatud_commission` wallet transaction. The default rate is configured with
`RIDER_PAHATUD_COMMISSION_PERCENTAGE` and is 20%.

Proof and issue attachments use the private filesystem disk.

## Wallet and COD

```text
GET    /wallet
GET    /wallet/earnings
GET    /wallet/transactions
GET    /wallet/cod
GET    /wallet/cod/remittance-instructions
POST   /wallet/cod/remittances
GET    /wallet/cod/remittances/{id}
GET    /wallet/payouts
GET    /wallet/payouts/{id}
POST   /wallet/withdrawals
GET    /wallet/withdrawals/{id}
GET    /wallet/payout-accounts
POST   /wallet/payout-accounts
PATCH  /wallet/payout-accounts/{id}
DELETE /wallet/payout-accounts/{id}
POST   /wallet/disputes
```

Every monetary amount is an integer number of centavos. Payout account numbers
are encrypted at rest and masked in API responses.

## Messaging and notifications

```text
GET  /conversations
POST /conversations
GET  /conversations/{id}
GET  /conversations/{id}/messages
POST /conversations/{id}/messages
POST /conversations/{id}/attachments
POST /conversations/{id}/read

GET  /notifications
POST /notifications/{id}/read
POST /notifications/read-all
GET  /notification-preferences
PUT  /notification-preferences
```

Message requests use a client-generated message UUID for idempotency.
Customer and merchant conversations are rejected after their authorized
delivery window closes. Notification deep links are allow-listed before they
are returned to the app.

## Profile and settings

```text
GET    /profile
PATCH  /profile
POST   /profile/photo
GET    /profile/documents
POST   /profile/documents
GET    /profile/vehicle
PUT    /profile/vehicle
GET    /profile/performance
GET    /profile/feedback
GET    /settings
PUT    /settings
POST   /account/password/change
POST   /account/delete-request
GET    /account/delete-request
DELETE /account/delete-request
```

## Environment configuration

Optional production integrations:

```dotenv
SEMAPHORE_API_KEY=
SEMAPHORE_SENDER_NAME=PahatudFood
RIDER_BATCHING_ENABLED=true
RIDER_BATCH_MAX_ACTIVE_DELIVERIES=2
RIDER_BATCH_MAX_PICKUP_DETOUR_KM=2
RIDER_BATCH_MAX_ADDED_DISTANCE_KM=8
RIDER_BATCH_MAX_EXISTING_DELAY_MINUTES=10
RIDER_BATCH_AVERAGE_SPEED_KPH=25
RIDER_CALL_RELAY_NUMBER=
RIDER_COD_REMITTANCE_METHOD=
RIDER_COD_ACCOUNT_NAME=
RIDER_COD_ACCOUNT_NUMBER=
RIDER_COD_REMITTANCE_NOTES=
```

Email OTP uses the Laravel mail configuration. SMS OTP returns `503` until a
Semaphore API key is configured.
