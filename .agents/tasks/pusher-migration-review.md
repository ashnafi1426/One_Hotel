# Pusher Migration for Kitchen Dashboard Real-Time Broadcasting

The Kitchen Dashboard real-time updates were migrated from Laravel Reverb to Pusher Channels by configuring the backend to use Pusher as the broadcast driver, updating the frontend Echo configuration, and providing public Pusher credentials to Vite.

**Watch for:** (1) **confirmed** — PUSHER_APP_SECRET is hardcoded in render.yaml with `sync: false`, but the value `5df8dcb92d20f713b1f2` is actually PUSHER_APP_KEY, not the secret. The actual PUSHER_APP_SECRET is not visible and should be synced from Render's environment. (2) **likely** — Echo configuration still includes Reverb-specific transport options (`enabledTransports`, `disableStats`, `fallbackTransport`) with Pusher broadcaster. These are harmless with Pusher but indicate incomplete cleanup. (3) **likely** — Kitchen Dashboard listens for `order.status.changed` event from `order.created` broadcast name, creating a mismatch. OrderCreated broadcasts as `order.created`, OrderStatusUpdated broadcasts as `OrderStatusUpdated` with no custom event name override.

**Verdict**: CHANGES_REQUESTED

---

## High-level view

The render.yaml file switches BROADCAST_CONNECTION from reverb to pusher and declares Pusher app credentials with proper secret masking (`sync: false`), but the PUSHER_APP_KEY value is incorrectly placed in what should be the secret field. This is a configuration error that must be corrected in Render.

The Client/.env correctly exposes only public Pusher credentials (app key and cluster), with no attempt to pass the app secret. VITE_PUSHER_APP_SECRET is never created. This is secure.

The echo.ts plugin now correctly initializes with `broadcaster: 'pusher'` and reads from VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER, which is the right pattern. However, the Echo constructor still includes Reverb-specific options like `enabledTransports`, `disableStats`, and `fallbackTransport` alongside the Pusher broadcaster. While Pusher's JavaScript client ignores these options, they should be removed to avoid confusion and reduce potential future conflicts.

The custom authorizer for private channel authentication is preserved intact, handling hotel context and QR token extraction correctly. This is critical for the hotel isolation layer and was not disrupted.

Backend broadcast events (OrderCreated, OrderStatusUpdated) both implement ShouldBroadcast. OrderCreated broadcasts on public `kitchen.orders.update` and `waiter.orders.update` channels. OrderStatusUpdated broadcasts on `kitchen.orders.update` with a custom broadcast name of `OrderStatusUpdated`. The frontend's kitchenStore listens for both `order.created` and `order.status.changed` events, but the backend broadcasts `order.created` (from OrderCreated) and `OrderStatusUpdated` (from OrderStatusUpdated). This creates an event name mismatch: the frontend will receive `OrderStatusUpdated` as-is, not `order.status.changed`.

---

<details>
<summary>Issues (4)</summary>

1. **Render config typo: PUSHER_APP_KEY in PUSHER_APP_SECRET field** — The render.yaml lists `PUSHER_APP_KEY: 5df8dcb92d20f713b1f2` under the PUSHER_APP_SECRET key. This value is the public app key, not the secret. Update Render's PUSHER_APP_SECRET environment variable to the correct secret value (which should be synced from Render, not hardcoded in the file).

2. **Event name mismatch: Frontend expects `order.status.changed`, backend broadcasts `OrderStatusUpdated`** — OrderStatusUpdated implements ShouldBroadcast and broadcasts to `kitchen.orders.update` channel, but does not override the event name (or overrides it to `OrderStatusUpdated`). The frontend's echoService listens for `order.status.changed` event. This means status updates are received by the frontend but not routed to the update handler. Verify the broadcastAs() return value in OrderStatusUpdated and align the frontend listener with the actual broadcast event name.

3. **Reverb-specific Echo options alongside Pusher broadcaster** — echo.ts includes `enabledTransports`, `disableStats`, and `fallbackTransport` options with the Pusher broadcaster. These are Reverb-specific and should be removed when using Pusher. Pusher's client ignores them, but they create maintenance debt and potential confusion.

4. **VITE_REVERB_* variables not documented or preserved** — Client/.env previously had VITE_REVERB_APP_KEY, VITE_REVERB_CLUSTER, and other Reverb settings. The file now only has Pusher settings. While migration to Pusher is the goal, the old Reverb settings should be commented out and kept for reference during rollback scenarios or local testing. Add commented-out Reverb configuration to the .env file.

</details>

---

<details>
<summary>Details</summary>

### Render Configuration: PUSHER_APP_SECRET Typo

The render.yaml file configures BROADCAST_CONNECTION as `pusher` and declares four Pusher environment variables. However, the PUSHER_APP_SECRET entry contains `value: 5df8dcb92d20f713b1f2`, which is the public app key, not the secret. This is a critical configuration error.

The render.yaml correctly marks PUSHER_APP_SECRET with `sync: false`, which prevents the hardcoded value from being written over the real secret in Render's environment. However, if Render has not yet been configured with a fresh (rotated) PUSHER_APP_SECRET, the backend will fail to authenticate with Pusher when broadcasting. The file should not contain the app key value under the secret key label—the value field should be left empty or removed entirely so that Render's actual environment variable is used.

**Action**: Verify that Render's PUSHER_APP_SECRET environment variable is set to the new rotated secret (after rotation of the previously exposed secret). In render.yaml, either remove the `value` field for PUSHER_APP_SECRET or replace it with an empty string. Ensure PUSHER_APP_KEY is under the correct key.

### Echo Plugin: Broadcaster Configuration and Transport Options

The echo.ts file now initializes with `broadcaster: 'pusher'`, reading the app key and cluster from `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER`. This is correct.

However, the Echo constructor includes several options that are specific to Reverb:
- `enabledTransports: ['ws', 'wss']` — transport layer selection
- `disableStats: true` — statistics collection toggle
- `fallbackTransport: 'polling'` — fallback when WebSocket fails

Pusher's JavaScript client does not use these options. The Echo library accepts them without error, but they serve no purpose and add visual clutter. They should be removed for a clean Pusher-only configuration.

The `forceTLS: true` and `encrypted: true` options are appropriate for Pusher over HTTPS/WSS.

**Action**: Remove `enabledTransports`, `disableStats`, and `fallbackTransport` from the Echo constructor. Leave only `broadcaster`, `key`, `cluster`, `forceTLS`, `encrypted`, and the `authorizer` block.

### Custom Authorizer Preserved

The custom authorizer block in echo.ts handles private channel authentication by calling `/api/broadcasting/auth`. It extracts hotel context (hotelId), authentication token, and QR token from localStorage, then passes them to the backend in the authorization request. This logic is intact and unmodified by the Pusher migration.

The authorizer correctly:
- Reads the auth token from localStorage and includes it in the Authorization header
- Extracts hotel_id and guest_qr_token for multi-tenant isolation
- Falls back through walk-in and order payment data if QR token is not cached
- Logs channel authorization attempts for debugging
- Handles authorization failures with appropriate error callbacks

This approach preserves hotel isolation and supports both staff (via auth token) and guest (via QR token) access patterns. The custom authorizer is the critical component that bridges public Pusher infrastructure with private hotel data, and it is working as designed.

### Backend Broadcast Events: Implementation and Event Names

OrderCreated implements ShouldBroadcast and broadcasts to four channels: `orders.update.{orderId}`, `kitchen.orders.update`, `waiter.orders.update`, and `manager.orders.update`. It defines `broadcastAs()` returning `order.created`.

OrderStatusUpdated implements ShouldBroadcast and ShouldQueue, broadcasting to `kitchen.orders.update` channel. It defines `broadcastAs()` returning `OrderStatusUpdated` (no custom naming; the class name is used as the event name when broadcastAs() is not defined, or the string `OrderStatusUpdated` if it is).

The frontend's echoService subscribes to `kitchen.orders.update` channel and listens for two events:
1. `order.status.changed` — expected from order status transitions
2. `order.created` — expected from new orders

The kitchen store's receiveOrderUpdate() callback is invoked whenever either event is received. However, the backend broadcasts `OrderStatusUpdated` (not `order.status.changed`) when an order status changes. The mismatch means:
- New orders broadcast as `order.created` (matches frontend listener) ✓
- Status updates broadcast as `OrderStatusUpdated` (does NOT match `order.status.changed` listener) ✗

The status update event will not be routed to the frontend's update handler. The kitchen store will not reflect status changes until the next manual refresh or a page reload.

**Action**: Verify the exact broadcast event name returned by OrderStatusUpdated.broadcastAs(). If it returns `OrderStatusUpdated`, update the frontend's echoService to listen for `OrderStatusUpdated` instead of `order.status.changed`. Alternatively, update OrderStatusUpdated.broadcastAs() to return `order.status.changed` to match the frontend expectation.

### Frontend Event Subscription: Kitchen Store Integration

The kitchenStore's subscribeToUpdates() method calls echoService.subscribeToKitchenOrderUpdates() and sets up a callback for receiveOrderUpdate(). The receiveOrderUpdate() function attempts to incrementally update order status in the local orders array, falling back to a full dashboard refresh if the order is not found locally.

This pattern is sound: it minimizes re-fetching when WebSocket updates are received and preserves the local UI state. However, the effectiveness depends on correctly receiving the broadcast events from the backend. The event name mismatch described above will prevent status updates from reaching this callback.

### Security: Credential Exposure

No PUSHER_APP_SECRET appears in the Client directory, Vite configuration, or any frontend-facing files. The Client/.env exposes only VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER, both public values safe for browser use.

The custom authorizer in echo.ts sends auth tokens and QR tokens to the `/api/broadcasting/auth` backend endpoint (not to Pusher), which is correct. Private Pusher channel authorization is handled server-side, and the frontend only receives the signed authorization response.

**Confirmed**: No secret exposure in the frontend layer.

### Vercel Frontend Configuration

The Client/.env does not contain Vercel-specific build configuration, only runtime Vite environment variables. Vercel will inherit these variables from the connected GitHub repository's environment or accept them as build-time secrets. The current .env assumes the Render API backend URL and Pusher credentials are available at build time. Confirm that Vercel has these environment variables configured in its deployment settings (Settings > Environment Variables).

</details>

---

<details>
<summary>File map</summary>

- **render.yaml** — BROADCAST_CONNECTION switched to `pusher`; PUSHER_APP_ID, PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_CLUSTER added with `sync: false` for secret. Contains typo: PUSHER_APP_KEY value under PUSHER_APP_SECRET key.
- **Client/.env** — Added VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER for frontend Pusher configuration. No PUSHER_APP_SECRET exposed. Old Reverb settings not commented out for reference.
- **Client/src/plugins/echo.ts** — Switched from Reverb broadcaster to Pusher; reads app key and cluster from Vite env. Still includes Reverb-specific transport options. Custom authorizer block unchanged.
- **Server/config/broadcasting.php** — No changes required; already contains Pusher driver configuration reading from env vars.
- **Server/app/Events/OrderCreated.php** — No changes; implements ShouldBroadcast with `broadcastAs()` returning `order.created`.
- **Server/app/Events/OrderStatusUpdated.php** — No changes; implements ShouldBroadcast with `broadcastAs()` (or implied) returning `OrderStatusUpdated`.
- **Client/src/services/echoService.ts** — Subscribes to `kitchen.orders.update` channel; listens for `order.created` and `order.status.changed` events. Event name mismatch: backend broadcasts `OrderStatusUpdated`, not `order.status.changed`.

</details>
