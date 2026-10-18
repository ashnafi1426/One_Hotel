# WebSocket Kitchen Order Updates - Implementation Plan

## Overview
The kitchen order pages (Pending, Preparing, Food Orders) are polling/refreshing instead of receiving real-time updates via WebSocket. Five critical issues prevent WebSocket delivery: duplicate function definition, channel/event name mismatches, missing WebSocket initialization, broken unsubscribe logic, and incorrect data mapping.

---

## Issue Analysis & Design Decisions

### ISSUE 1: Duplicate `receiveOrderUpdate` Function in kitchenStore.ts
**Location:** `src/stores/kitchenStore.ts` lines ~100-130 and ~133-160
**Problem:** TypeScript silently drops the first definition. The second definition (with `data.order || data` unwrapping) is more complete but the first is still referenced, creating unpredictable behavior.
**Decision:** Remove the first definition entirely; keep the second one which handles partial data better. This simplifies maintenance and eliminates confusion.

### ISSUE 2: Channel/Event Name Mismatch Between Frontend & Backend
**Frontend (echoService.ts):**
- Listens on public channel: `kitchen.orders.update` (line ~76)
- Listens for event: `order.status.changed` (line ~77)
- Does NOT capture return value for unsubscribe

**Backend (OrderStatusUpdated.php):**
- Broadcasts to PRIVATE channels: `hotel.kitchen` and `hotel.{hotelId}.kitchen` (lines ~46-50)
- Broadcasts as event: `OrderStatusUpdated` (line ~60)
- Data sent: flat fields (`order_id`, `status`, `previous_status`, etc.) NOT nested under `order` object

**Channel Type Mismatch:**
- `channels.php` defines auth for `hotel.kitchen` and `hotel.{hotelId}.kitchen` as private channels (lines ~64-66)
- Frontend uses `.channel()` which is for PUBLIC channels
- Frontend should use `.private()` for private channels

**Decision:** 
- Change backend to broadcast on PUBLIC channel `kitchen.orders` (no auth needed, simpler for staff coordination)
- Update frontend to use `.private()` if keeping private channels, OR switch both to public
- We choose PUBLIC: simplest approach for kitchen staff broadcast. Kitchen staff are authenticated in Laravel middleware, so Pusher/Reverb auth is overkill for read-only real-time updates. Change backend from `new PrivateChannel()` to `new Channel()`.
- Update event name to match: backend already says `OrderStatusUpdated`, so frontend should listen for `OrderStatusUpdated`

### ISSUE 3: No WebSocket Subscription in Kitchen Views
**Problem:** All three kitchen views (PendingOrdersView.vue, PreparingOrdersView.vue, FoodOrdersView.vue) call `fetchDashboard()` in `onMounted` but NEVER call `subscribeToUpdates()`. The views receive initial data but never listen for updates.
**Decision:** Add `subscribeToUpdates()` call after `fetchDashboard()` in each view's `onMounted`. Add `unsubscribeFromUpdates()` in `onUnmounted` cleanup.

### ISSUE 4: Broken Unsubscribe Logic
**Problem:** 
- `subscribeToUpdates()` in kitchenStore.ts calls `echoService.subscribeToKitchenOrderUpdates()` but IGNORES the return value (line ~143)
- `echoService.subscribeToKitchenOrderUpdates()` does NOT return an unsubscribe function (line ~76)
- `unsubscribeFromUpdates()` tries to call `unsubscribe()` but it was never populated (line ~156)
- Result: Echo channel is never left, memory leaks, and duplicate subscriptions on re-mount

**Decision:** 
- Make `echoService.subscribeToKitchenOrderUpdates()` return an unsubscribe function (matching the pattern in `subscribeToManagerOrderUpdates()` at line ~48)
- Capture the return value in `kitchenStore.subscribeToUpdates()` and store it in the `unsubscribe` ref
- Call `unsubscribe()` in `unsubscribeFromUpdates()` to properly leave the channel

### ISSUE 5: Data Mapping Mismatch
**Problem:** Backend sends `OrderStatusUpdated` event with flat fields: `order_id`, `status`, `previous_status`, etc. (broadcastWith at line ~81 in OrderStatusUpdated.php)
Frontend tries to extract `data.order` (line ~135 in kitchenStore.ts), but the data is NOT nested under an `order` key—it's flat.
**Decision:** Rather than manually reconstruct the KitchenOrder from partial broadcast data (error-prone, missing `items`, `guest`, `room`, `table` objects), trigger a full `fetchDashboard()` refresh when WebSocket fires. This is more reliable and ensures the order list stays consistent with the server.

---

## Implementation Plan

### Item 1: Fix Backend Broadcasting - Switch to Public Channel
**What to do:** Change OrderStatusUpdated.php to broadcast on public channels (`new Channel()` instead of `new PrivateChannel()`). This simplifies frontend subscription and removes the need for Pusher auth credentials on the frontend. Kitchen staff are already authenticated via Laravel middleware, so no security loss.

**Files:** 
- `Restaurant_system2/Server/app/Events/OrderStatusUpdated.php`

**Changes:**
- Replace all `new PrivateChannel()` with `new Channel()` in the `broadcastOn()` method
- Simplify channel names: use `kitchen.orders` (broadcast to all kitchen) and `hotel.{hotelId}.orders` (broadcast to hotel-specific orders)

**Verify:** 
```bash
php -l app/Events/OrderStatusUpdated.php
./vendor/bin/phpunit tests/ -k OrderStatusUpdated
```

---

### Item 2: Fix Frontend Echo Service - Correct Channel Subscription & Return Unsubscribe
**What to do:** Update `echoService.ts` to:
- Use `.channel()` for public channels (since backend now uses `new Channel()`)
- Listen on correct channel name: `kitchen.orders` (to match backend broadcastOn)
- Listen for correct event name: `OrderStatusUpdated` (to match backend broadcastAs)
- Return an unsubscribe function so kitchenStore can properly clean up

**Files:**
- `Restaurant_system2/Client/src/services/echoService.ts`

**Changes:**
- Update `subscribeToKitchenOrderUpdates()` function (around line 76):
  - Change `.channel('kitchen.orders.update')` to `.channel('kitchen.orders')`
  - Change `.listen('order.status.changed')` to `.listen('OrderStatusUpdated')`
  - Return the unsubscribe function (echo.leave callback)

**Verify:**
```bash
npm run test:unit
```

---

### Item 3: Fix kitchenStore - Remove Duplicate, Capture Unsubscribe, Trigger Refresh
**What to do:**
- Remove the first (incomplete) `receiveOrderUpdate()` function definition (keep only the second)
- Update `receiveOrderUpdate()` to trigger a `fetchDashboard()` refresh instead of trying to reconstruct the order
- Update `subscribeToUpdates()` to capture the unsubscribe function returned from echoService
- Update `unsubscribeFromUpdates()` to properly call the captured unsubscribe function

**Files:**
- `Restaurant_system2/Client/src/stores/kitchenStore.ts`

**Changes:**
- Delete lines ~100-130 (first receiveOrderUpdate definition)
- Replace the second `receiveOrderUpdate()` to trigger fetchDashboard
- Update `subscribeToUpdates()` to capture unsubscribe function
- Update `unsubscribeFromUpdates()` to call the unsubscribe function

**Verify:**
```bash
npm run test:unit
```

---

### Item 4: Add WebSocket Subscription to PendingOrdersView.vue
**What to do:** Initialize WebSocket subscription in `onMounted` after `fetchDashboard()`, and clean up in `onUnmounted`.

**Files:**
- `Restaurant_system2/Client/src/views/kitchen/PendingOrdersView.vue`

**Changes:**
- Add `kitchenStore.subscribeToUpdates()` after `fetchDashboard()` in onMounted
- Add new `onUnmounted()` hook that calls `kitchenStore.unsubscribeFromUpdates()`

**Verify:**
```bash
npm run dev
# Navigate to Pending Orders view
# Check console for: [Echo] Subscribed to kitchen order updates
# Trigger order status change - verify update without refresh
```

---

### Item 5: Add WebSocket Subscription to PreparingOrdersView.vue
**What to do:** Same as Item 4—add subscription to `onMounted` and cleanup to `onUnmounted`.

**Files:**
- `Restaurant_system2/Client/src/views/kitchen/PreparingOrdersView.vue`

**Changes:**
- Add `kitchenStore.subscribeToUpdates()` after `fetchDashboard()` in onMounted
- Add new `onUnmounted()` hook that calls `kitchenStore.unsubscribeFromUpdates()`

**Verify:** Same as Item 4 for this view.

---

### Item 6: Add WebSocket Subscription to FoodOrdersView.vue
**What to do:** Same as Item 4 & 5—add subscription to `onMounted` and cleanup to `onUnmounted`.

**Files:**
- `Restaurant_system2/Client/src/views/kitchen/FoodOrdersView.vue`

**Changes:**
- Add `kitchenStore.subscribeToUpdates()` after `fetchDashboard()` in onMounted
- Add new `onUnmounted()` hook that calls `kitchenStore.unsubscribeFromUpdates()`

**Verify:** Same as Item 4 for this view.

---

## End-to-End Verification

After all items are complete:

1. **Start backend & Reverb:**
   ```bash
   php artisan reverb:start
   php artisan serve
   ```

2. **Start frontend:**
   ```bash
   npm run dev
   ```

3. **Test real-time updates:**
   - Navigate to `/kitchen/pending-orders`
   - Create a new order via API
   - Confirm order appears WITHOUT page refresh
   - Verify console shows WebSocket events

4. **Test status transitions:**
   - Change order from pending → preparing → ready → served
   - Confirm order moves between views WITHOUT refresh

5. **Test cleanup:**
   - Navigate away from kitchen pages
   - Navigate back
   - Verify new subscription created (no duplicate)

---

## Notes

- **Email/Resend Issues:** Not addressed in this plan (separate from WebSocket)
- **Manager Waiters API 500:** Not addressed (separate backend API issue)
- **Cashier Photo 404:** Not addressed (separate from kitchen WebSocket)
- **Loading State:** No spinner on WebSocket updates (only on initial mount) - desired UX
- **Race Conditions:** Multiple concurrent fetches are safe; store handles gracefully

