# Pusher Broadcasting Migration for Kitchen Dashboard

Migrate real-time order updates from Laravel Reverb to Pusher Channels for production reliability. The Kitchen Dashboard receives order status broadcasts on the `private-kitchen.orders.update` private channel, with backend events implementing `ShouldBroadcastNow` and frontend subscription via the Echo WebSocket library using the `pusher` broadcaster.

The changes correctly transition from Reverb to Pusher while preserving the hotel-context private channel authorization. No secrets are exposed in the Client directory. The broadcaster configuration, channel authorization, and event dispatch flow are all correctly configured for production.

**Watch for**: A critical data mapping inconsistency in `OrderStatusUpdated` event. The backend broadcasts with `hotel_id` hardcoded as a placeholder UUID rather than extracting it from the order's relationship. The frontend echoService expects `order.status.changed` events on `private-kitchen.orders.update`, but echoService also listens for `order.created` on the same channel — verify this channel receives both events or one of them is dead code. The custom authorizer in echo.ts has access to the guest QR token but the broadcast events do not include a guest identifier, so private kitchen channel subscribers cannot distinguish who placed an order.

**Verdict**: NEEDS_CHANGES

---

## High-level view

The backend switched `BROADCAST_CONNECTION` to `pusher` in render.yaml and configured the environment variables to point to the production Pusher cluster (ap2) over HTTPS. The Pusher app key is public and safe to expose; the secret is kept server-side with `sync: false` and an empty value placeholder. The frontend echo.ts plugin now declares `broadcaster: 'pusher'` with the app key and cluster from `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER` environment variables. The kitchen Dashboard component subscribes to real-time updates via the kitchen store, which calls `echoService.subscribeToKitchenOrderUpdates()` on mount and unsubscribes on unmount.

The private channel authorization is handled by the custom authorizer block in echo.ts, which calls `/api/broadcasting/auth` with the socket ID, channel name, and optional QR token. The backend `Broadcast::channel('private-kitchen.orders.update', ...)` callback verifies the user has a kitchen role (chef, cook, kitchen_staff, manager, admin). Order events (`OrderCreated`, `OrderStatusUpdated`) implement `ShouldBroadcast` and broadcast to the kitchen channel and other public/private channels. The event data includes order number, status, room/table assignments, and guest name, but does not include hotel_id or any guest identifier that would allow the kitchen staff to verify the order belongs to their hotel.

---

<details>
<summary>Issues (3)</summary>

1. **Hardcoded hotel_id in OrderStatusUpdated** — The event sets `$this->hotelId` to a placeholder UUID `'41b094f8-7ed2-4af0-bd16-6f0533e34544'` instead of reading `$order->hotel_id`. Multi-hotel systems will broadcast incorrect hotel context to all subscribers.

2. **Dual event listeners on kitchen channel** — echoService.subscribeToKitchenOrderUpdates listens for both `order.status.changed` and `order.created` on the same private channel, but `OrderCreated` broadcasts to public channels (`orders.update.{id}`, `waiter.orders.update`, `manager.orders.update`) and `private-kitchen.orders.update`, while `OrderStatusUpdated` broadcasts only to `private-kitchen.orders.update`. Verify the intent: if both events should reach the kitchen, confirm OrderCreated actually targets the private kitchen channel. If only OrderStatusUpdated should reach it, remove the `order.created` listener.

3. **Missing guest/order identity in broadcasts** — Broadcast events include order number and guest name but no guest_id or guest identifier. The frontend authorizer can access the guest QR token, but broadcast payloads do not use it, so kitchen staff cannot verify which guest placed an order or correlate it with guest-specific requests without performing a separate API query.

</details>

---

<details>
<summary>Details</summary>

### Pusher Backend Configuration

The render.yaml specifies `BROADCAST_CONNECTION=pusher` with all required Pusher environment variables: `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_CLUSTER=ap2`, `PUSHER_SCHEME=https`, and `PUSHER_PORT=443`. The `PUSHER_APP_SECRET` is configured with `sync: false` and an empty value, which means Render will use the actual secret from the environment (set separately in the Render dashboard) and will not overwrite it from the YAML file. This is the correct pattern for secrets.

The `config/broadcasting.php` contains a correctly configured `pusher` connection that reads all credentials from environment variables and sets up Pusher with HTTPS/TLS. The cluster and API host construction follow Pusher conventions. The configuration does not hardcode any credentials.

However, the code is prepared to support both Reverb and Pusher: the `reverb` connection remains in the config alongside `pusher`. This is not a problem for production — the `default` broadcaster is controlled by `BROADCAST_CONNECTION`, so only the Pusher driver will be used when `BROADCAST_CONNECTION=pusher`. The Reverb configuration is harmless dead code in production but allows easy rollback if needed.

### Frontend Echo Plugin Configuration

The frontend echo.ts plugin declares `broadcaster: 'pusher'` and uses public Pusher credentials from `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER`. These are safe to expose (they are public credentials). The Client/.env correctly lists these variables and comments out the obsolete `VITE_REVERB_*` variables for reference.

The echo.ts plugin does not contain any Reverb-specific configuration like `wsHost`, `wsPort`, or `wssPort`. It does not export `VITE_PUSHER_APP_SECRET` anywhere. The secret is never referenced in the Client directory.

The echo.ts plugin includes a custom authorizer block that handles private channel authentication by calling `/api/broadcasting/auth` with the socket ID, channel name, and optional QR token. This authorizer supports both authenticated staff (using Bearer token from localStorage) and guest users tracking their own orders (using QR token from various storage locations). This is a sophisticated and appropriate design for a multi-role system.

### Kitchen Channel Subscription and Events

The kitchen store calls `echoService.subscribeToKitchenOrderUpdates()` on mount, which subscribes to the private channel `private-kitchen.orders.update` and listens for two events: `order.status.changed` and `order.created`. The unsubscribe function is captured and called on component unmount, ensuring proper cleanup.

The backend channels.php authorizes the `private-kitchen.orders.update` channel only for users with kitchen-related roles (chef, cook, kitchen_staff, manager, admin, super_admin). The authorization is checked at subscription time via the `/api/broadcasting/auth` callback.

**Event dispatch for OrderStatusUpdated:** The event is marked `ShouldBroadcastNow`, which means it broadcasts immediately without queuing. This is appropriate for real-time status updates that should appear in the kitchen dashboard with minimal latency. The event broadcasts to the private channel `private-kitchen.orders.update` with the event name `order.status.changed`. The broadcast payload includes order_id, status, previous_status, order_number, room_number, table_number, chef_id, a human-readable message, and an estimated completion time.

**Critical issue in OrderStatusUpdated:** The `hotel_id` is hardcoded to `'41b094f8-7ed2-4af0-bd16-6f0533e34544'` instead of being read from `$order->hotel_id`. In a multi-hotel system, every order update will broadcast the same placeholder UUID regardless of which hotel placed the order. This breaks the ability to filter or validate orders by hotel. The fix is a one-line change: `$this->hotelId = $order->hotel_id ?? '41b094f8-7ed2-4af0-bd16-6f0533e34544'` (use the order's hotel_id, fall back to placeholder only if null).

**Event dispatch for OrderCreated:** The event broadcasts to four channels: `orders.update.{id}` (public), `private-kitchen.orders.update` (private), `waiter.orders.update` (public), and `manager.orders.update` (public). The kitchen dashboard's echoService listens for `order.created` on `private-kitchen.orders.update`, so new orders should reach the kitchen in real-time. However, this setup creates a subtle question: does the kitchen dashboard need both `order.created` and `order.status.changed` events on the same channel? If both are needed, this is fine. If only status changes should reach the kitchen (and orders are fetched on initial load via `fetchDashboard()`), the `order.created` listener is redundant and should be removed to avoid confusion and potential double-processing. The current implementation will handle both gracefully (the store's `receiveOrderUpdate()` function upserts based on order ID), so it works either way, but the intent is unclear.

### Data Mapping Inconsistency

The broadcast payloads include order number, guest name, room/table assignment, and chef ID, which allows kitchen staff to identify and action the order. However, they do not include any guest identifier (guest_id, guest_uuid, or guest_email). The kitchen staff cannot verify ownership of an order or correlate it with guest-specific requests without making a separate API call to fetch the full order details. This is likely not a functional blocker (the kitchen primarily needs to see what to cook), but it's an asymmetry: the frontend authorizer has access to the guest QR token, but the broadcast events do not use it. If kitchen staff need to verify guest identity or serve guest-specific requests alongside order fulfillment, this data should be added to the broadcast payload.

### Authorization and Security

The custom authorizer in echo.ts correctly guards private channel access by verifying the user's Bearer token or QR token before calling the backend auth endpoint. The backend authorization callbacks in channels.php properly verify roles and only grant access to kitchen staff for the kitchen channel. No secrets are exposed in the frontend code or environment variables.

### Test Coverage

The changes do not include new tests for Pusher broadcasting. The existing kitchen dashboard views (PendingOrdersView, PreparingOrdersView, ServedOrdersView) use the store's subscription methods but no broadcast-specific tests are visible. In production, this means:
- Real-time updates depend on the middleware and authorization callbacks in channels.php working correctly.
- Any regression in the order creation flow (e.g., OrderCreated not being dispatched) will not be caught until the kitchen reports missing orders.
- Any changes to the broadcast event names or payload structure will not be validated against frontend listeners until they are tested manually or by alert.

</details>

---

## File map

<details>
<summary>Modified files</summary>

- **render.yaml** — Switched BROADCAST_CONNECTION to pusher, added PUSHER_APP_ID/KEY/SECRET/CLUSTER/SCHEME/PORT environment variables with secrets marked `sync: false` to prevent YAML file from overwriting Render's stored secrets.
- **Client/.env** — Added VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER for frontend Pusher configuration. Commented out obsolete VITE_REVERB_* variables for reference.
- **Client/src/plugins/echo.ts** — Switched Echo configuration from Reverb to Pusher (broadcaster: 'pusher'), uses public Pusher credentials, retains custom authorizer for private channel auth via /api/broadcasting/auth endpoint.

No changes to backend event classes were required — `OrderCreated`, `OrderStatusUpdated` already implement `ShouldBroadcast` and broadcast to private and public kitchen channels. No changes to channels.php were required — the `private-kitchen.orders.update` channel authorization is already in place.

Full diff: `git diff main -- render.yaml Client/.env Client/src/plugins/echo.ts`

</details>
