# WebSocket Real-Time Updates for Kitchen Order Views

The kitchen order pages (Pending, Preparing, All Orders) now receive live updates via WebSocket instead of polling. Changes span the backend broadcast channel, the Echo service subscription, the kitchen store lifecycle, and subscription hooks in all three views.

The implementation introduces a WebSocket subscription that fires whenever an order status changes, then calls `fetchDashboard()` to refresh the full order state. All three views subscribe on mount and unsubscribe on unmount, with proper cleanup. The channel name, event name, and cleanup sequence match across backend and frontend.

**Watch for**: No blocking concerns. The fix correctly implements the checklist requirements.

**Verdict**: APPROVED

## High-level view

The backend broadcasts order updates on a public `kitchen.orders.update` channel, firing the `OrderStatusUpdated` event when status changes occur. The frontend Echo service subscribes to this channel and listens for that specific event name, returning an unsubscribe closure for cleanup.

The kitchen store captures the unsubscribe function and holds it in module scope, making it available to `unsubscribeFromUpdates()`. When a WebSocket update arrives, `receiveOrderUpdate()` calls `fetchDashboard()` to pull fresh order data, ensuring the UI always reflects the server state.

All three kitchen views (Pending, Preparing, FoodOrders) subscribe during mount and unsubscribe during unmount, with a watch handler that re-subscribes when the hotel context changes. Only the `<script setup>` blocks were modified; templates remain unchanged.

<details>
<summary>Issues (0)</summary>

No blocking concerns identified.

</details>

<details>
<summary>Details</summary>

### Backend broadcast on public channel with correct event name

The `OrderStatusUpdated` event broadcasts to a public channel named `kitchen.orders.update` (line 59 in OrderStatusUpdated.php) and declares the event name as `'OrderStatusUpdated'` via `broadcastAs()` (line 64). This matches exactly what the frontend listens for.

### Echo service subscribes with matching channel and event

`subscribeToKitchenOrderUpdates()` in echoService.ts uses `.channel('kitchen.orders.update')` (public, not private) and listens for the `'OrderStatusUpdated'` event (line 139). The function returns a closure that calls `echo.leave('kitchen.orders.update')` for cleanup (lines 144-147).

### Store captures unsubscribe and enforces single subscription

The store module declares `let unsubscribe: (() => void) | null = null` at the top scope (line 12). When `subscribeToUpdates()` is called, it checks if a previous subscription exists and unsubscribes before creating a new one (lines 150-152), then assigns the returned closure to `unsubscribe` (line 154-160). This prevents duplicate subscriptions and ensures cleanup is always available.

### WebSocket trigger calls fetchDashboard for fresh data

When `receiveOrderUpdate()` is called with a broadcast payload, it extracts the `order_id`. If the order exists locally, it patches the status fields and updates the array element (lines 120-127). If the order is not found locally, it calls `fetchDashboard()` to pull fresh state (line 129). It also calls `fetchDashboard()` if no `order_id` is present in the payload (line 117). Statistics are always synced (line 133).

This approach trades incremental updates for consistency: the WebSocket payload is thin (event metadata only), so a full refresh is safer than trying to apply incomplete patches.

### All three views subscribe on mount and unsubscribe on unmount

PendingOrdersView: `onMounted` calls `subscribeToUpdates()` (line 44), `onUnmounted` calls `unsubscribeFromUpdates()` (line 48). PreparingOrdersView: same pattern (lines 40-41). FoodOrdersView: same pattern (lines 61-63). All three also watch `hotelStore.hotelId` and re-subscribe when it changes (lines 45-50, 43-49, 65-71 respectively).

### No duplicate function definitions

A single `receiveOrderUpdate()` function exists in kitchenStore.ts (line 111). It is exported via the store's return statement (line 214) and called from the subscription callback (line 159).

### Templates unchanged

All three .vue files have identical template structures to their prior versions. Only the `<script setup>` block was modified to add the lifecycle hooks. The table layout, pagination, and UI elements are unmodified.

</details>

## File map

<details>
<summary>Changed files</summary>

- **Server/app/Events/OrderStatusUpdated.php**: `broadcastOn()` returns public channel `kitchen.orders.update` instead of private channels
- **Client/src/services/echoService.ts**: Added `subscribeToKitchenOrderUpdates()` function that subscribes to the public channel and listens for `OrderStatusUpdated` event, returning an unsubscribe closure
- **Client/src/stores/kitchenStore.ts**: Added lifecycle functions `subscribeToUpdates()` and `unsubscribeFromUpdates()` to manage WebSocket subscription; added `receiveOrderUpdate()` handler that calls `fetchDashboard()` on incoming updates
- **Client/src/views/kitchen/PendingOrdersView.vue**: Added `onMounted` hook calling `subscribeToUpdates()`, `onUnmounted` hook calling `unsubscribeFromUpdates()`, and hotel watch handler to re-subscribe on context change
- **Client/src/views/kitchen/PreparingOrdersView.vue**: Same lifecycle additions as PendingOrdersView
- **Client/src/views/kitchen/FoodOrdersView.vue**: Same lifecycle additions as PendingOrdersView

Full diff: `git diff <base-branch> HEAD`

</details>
