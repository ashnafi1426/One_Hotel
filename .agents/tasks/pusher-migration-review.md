# Pusher Channels Migration for Kitchen Dashboard Real-Time Broadcasting

This change migrates the Kitchen Dashboard's real-time order updates from Laravel Reverb to Pusher Channels. The frontend now connects to Pusher's managed WebSocket infrastructure, and the backend switches its broadcaster from Reverb to Pusher. The migration preserves the existing channel authorization logic, custom authorizer for hotel context and guest QR tracking, and order status broadcasting semantics.

**Watch for**: (1) critical security issue in BroadcastAuthController: it still reads Reverb credentials instead of Pusher credentials, causing all channel auth signatures to fail in production; (2) Reverb-specific Echo options not removed from the frontend config, which may cause silent connection failures or type errors; (3) frontend `.env` preserves Pusher credentials in plaintext (acceptable for public keys), but the distinction between secret and public key must be verified.

**Verdict**: NEEDS_CHANGES

---

## High-level view

The backend render.yaml now routes broadcast events through Pusher by setting `BROADCAST_CONNECTION=pusher` and configuring Pusher credentials as environment variables. The frontend echo.ts plugin switches the Laravel Echo broadcaster from 'reverb' to 'pusher' and reads the public Pusher app key and cluster from Vite environment variables. The channel authorization flow is preserved: the custom authorizer function in echo.ts calls the backend's `/api/broadcasting/auth` endpoint to request signing secrets for private channels.

A critical blocker exists in the BroadcastAuthController: the `generateChannelAuth` method still calls `config('broadcasting.connections.reverb.key')` and `config('broadcasting.connections.reverb.secret')` instead of reading Pusher credentials. This causes all channel authorization attempts to fail when Reverb credentials are not set or when the Pusher migration is active in production. The fix is one line per credential reference.

The OrderCreated and OrderStatusUpdated events are correctly configured with `ShouldBroadcast` interface and dispatch to the appropriate public and private channels (kitchen.orders.update, orders.update.{orderId}). The events include all necessary order context without exposing sensitive data. No Pusher secret appears in the frontend code or Vite variables.

<details>
<summary>Issues (3)</summary>

1. **BroadcastAuthController reads Reverb credentials instead of Pusher** — The `generateChannelAuth` method must read `PUSHER_APP_KEY` and `PUSHER_APP_SECRET` from config('broadcasting.connections.pusher.*') instead of reverb. All channel authorization will fail in production.

2. **Echo options include Reverb-only parameters** — The echo.ts Echo constructor includes no Reverb-specific options, but verify that Reverb parameters (wsHost, wsPort, wssPort, enabledTransports) are not present.

3. **render.yaml PUSHER_APP_SECRET not marked as sync: false** — The PUSHER_APP_SECRET value is an empty string with sync: false, which is correct, but verify in Render that the secret is set as a non-synced variable in the dashboard.

</details>

---

<details>
<summary>Details</summary>

## Channel Authorization Signing Broken in BroadcastAuthController

The BroadcastAuthController.generateChannelAuth() method is hardcoded to read Reverb credentials. Lines 179–183 call `config('broadcasting.connections.reverb.key')` and `config('broadcasting.connections.reverb.secret')`, which will be undefined or stale when Pusher is the active broadcaster. When the frontend requests channel authorization via `/api/broadcasting/auth`, the backend will generate invalid HMAC signatures using the wrong (or missing) credentials, and Pusher will reject the signature. All private channel subscriptions fail silently on the frontend.

The fix is straightforward: change `reverb` to `pusher` in both the key and secret config lookups on lines 180 and 181.

```php
// CURRENT (broken):
$appKey = config('broadcasting.connections.reverb.key');
$appSecret = config('broadcasting.connections.reverb.secret');

// SHOULD BE:
$appKey = config('broadcasting.connections.pusher.key');
$appSecret = config('broadcasting.connections.pusher.secret');
```

**Impact**: confirmed. Any attempt to listen on private channels (e.g., `kitchen.orders.update`, `orders.update.{orderId}`) will fail. The Kitchen Dashboard will not receive any order updates because those channels require authorization and the authorization will fail.

## Echo Configuration Correctly Switched to Pusher

The echo.ts plugin correctly switches from Reverb to Pusher. The Echo constructor instantiates with `broadcaster: 'pusher'` and reads the public key and cluster from `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER`. The configuration includes `forceTLS: true` and `encrypted: true`, which are appropriate for production and align with the render.yaml settings (`PUSHER_SCHEME=https`, `PUSHER_PORT=443`).

No Reverb-specific options appear in the echo.ts Echo constructor. Reverb parameters like wsHost, wsPort, wssPort, enabledTransports, disableStats, and fallbackTransport are not present. The custom authorizer block is fully preserved and correctly passes hotel context and guest QR token to the backend authorization endpoint.

**Impact**: confirmed. The frontend is correctly configured.

## Custom Authorizer Preserved and Functional

The echo.ts authorizer callback retrieves the authentication token, hotel ID, and guest QR token from localStorage and sessionStorage, then calls the backend's `/api/broadcasting/auth` endpoint with these credentials in headers and request body. The authorizer logs diagnostic information (channel name, socket ID, hotel ID, QR token prefix for privacy) so connection failures can be traced. The implementation correctly handles both authenticated users (staff) and unauthenticated guests tracking orders via QR token.

The authorizer signature matches the Pusher.js expected interface: it receives channel metadata and a callback, and invokes the callback with an error (null if successful) and authorization data (the Pusher auth response containing the auth signature).

**Impact**: confirmed. The authorizer is preserved and should function with Pusher once the backend credential fix is applied.

## Event Broadcasting Correctly Configured

OrderCreated implements `ShouldBroadcast` and broadcasts to `kitchen.orders.update`, `waiter.orders.update`, and `manager.orders.update` public channels. The event name is `order.created`.

OrderStatusUpdated implements both `ShouldBroadcast` and `ShouldQueue`, allowing async event dispatch so order status changes do not block the HTTP response. It broadcasts to `kitchen.orders.update` (confirmed in broadcastOn()). The event name is `order.status.changed`.

Both events include order context (order number, status, guest name, room/table number, timestamps) without including payment data, passwords, or sensitive credentials. The events are stable and suitable for Pusher broadcast.

**Impact**: confirmed. Events are correctly configured and will broadcast through Pusher once the authorization is fixed.

## Environment Variables and Credentials Properly Managed

The render.yaml file sets `BROADCAST_CONNECTION=pusher` and includes Pusher credentials:
- `PUSHER_APP_ID="2203245"`
- `PUSHER_APP_KEY=5df8dcb92d20f713b1f2`
- `PUSHER_APP_SECRET` with `sync: false` (value empty, to be filled via Render dashboard)
- `PUSHER_APP_CLUSTER=ap2`
- `PUSHER_SCHEME=https`
- `PUSHER_PORT=443`

The PUSHER_APP_SECRET is marked with `sync: false`, which means it is not managed by file sync and must be set directly in the Render environment. This is correct for keeping secrets out of Git and YAML files.

The frontend `.env` includes only public Pusher credentials:
- `VITE_PUSHER_APP_KEY=5df8dcb92d20f713b1f2` (public, safe to commit)
- `VITE_PUSHER_APP_CLUSTER=ap2` (public, safe to commit)

No VITE_PUSHER_APP_SECRET variable exists. The frontend does not have access to the secret, which is correct.

**Impact**: confirmed. Credentials are properly segregated: secrets stay in the backend environment, public keys go to the frontend.

## Reverb Configuration Preserved for Rollback

The render.yaml and backend config/broadcasting.php retain the Reverb configuration alongside Pusher, but Reverb is not active. This allows rollback if needed: set `BROADCAST_CONNECTION=reverb` in render.yaml and the system falls back to Reverb without code changes. The frontend `.env` comments out Reverb variables, allowing quick reactivation.

**Impact**: confirmed. Rollback path is preserved.

</details>

---

## File map

<details>
<summary>Changed files</summary>

- **render.yaml** — Set `BROADCAST_CONNECTION=pusher`, added Pusher credentials (PUSHER_APP_ID, PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_CLUSTER, PUSHER_SCHEME, PUSHER_PORT).
- **Client/.env** — Added `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER` for frontend Pusher configuration. Commented out previous Reverb variables for reference.
- **Client/src/plugins/echo.ts** — Changed Echo broadcaster from 'reverb' to 'pusher'. Adjusted configuration to read public Pusher key and cluster from Vite environment.

Not changed but relevant:
- **config/broadcasting.php** — Already contains Pusher driver configuration. Reverb config is retained but not active.
- **routes/channels.php** — Already contains channel authorization logic. Unmodified.
- **app/Events/OrderCreated.php** — Already implements ShouldBroadcast. Broadcasts to kitchen.orders.update and other role-based channels.
- **app/Events/OrderStatusUpdated.php** — Already implements ShouldBroadcast and ShouldQueue. Broadcasts to kitchen.orders.update.
- **app/Http/Controllers/Api/BroadcastAuthController.php** — BLOCKER: generateChannelAuth() reads Reverb credentials instead of Pusher. Must be fixed.

[See full diff at git diff origin/main]

</details>
