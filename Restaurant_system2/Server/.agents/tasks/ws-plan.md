# Implementation Plan: Replace Order Polling with Reverb WebSocket Real-Time Updates

This plan will replace inefficient polling of order statuses with real-time WebSocket broadcasts using Laravel Reverb, fix two HTTP errors, and ensure instant UI updates without page refreshes.

## Overview

**Current State:**
- Frontend polls orders every 15-30 seconds via setInterval (KitchenDashboard, WaiterDashboard, CashierDashboard)
- No server-side event broadcasting for order status changes
- /api/manager/waiters returns HTTP 500 (undefined `$hotelId` variable)
- /api/cashier/profile/photo returns HTTP 404 (route mismatch or missing endpoint)
- Events directory does not exist

**Solution:**
1. Create Event classes for order broadcasts
2. Modify OrderController::changeStatus to fire events
3. Update channels.php to add order update channels
4. Replace frontend polling with Echo listeners
5. Fix backend HTTP errors
6. Add WebSocket initialization to frontend

---

## Backend Implementation

### 1. Create Order Event Classes

Create `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Server\app\Events\OrderStatusChanged.php`:

This event fires whenever an order status is updated. It broadcasts to the `orders.{orderId}` channel so guests and kitchen staff see updates instantly.

**Verify:** Ensure the file exists and contains the OrderStatusChanged class with $broadcastOn() returning the correct channel.

---

### 2. Create OrderCreated Event

Create `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Server\app\Events\OrderCreated.php`:

Broadcasts new orders to the kitchen and waiter channels so staff see incoming orders immediately.

**Verify:** Ensure the file exists and broadcasts to `hotel.kitchen` and `hotel.waiters` channels.

---

### 3. Modify OrderService to Fire Events

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Server\app\Services\orderService.php`:

- In `create()` method: dispatch OrderCreated event after creating an order
- In `changeStatus()` method: dispatch OrderStatusChanged event after status update

Add `use App\Events\OrderStatusChanged; use App\Events\OrderCreated;` at the top.

**Verify:** Check that the file compiles without errors. Service fires events when methods are called.

---

### 4. Update OrderModel with Broadcast Trait (Optional but Recommended)

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Server\app\Models\Order.php`:

Add `use Illuminate\Database\Eloquent\BroadcastsEvents;` and add the trait to the Order class. This enables automatic model broadcasts. However, since we need custom channels, the service approach is more suitable. Verify the service approach works first, then add this trait if needed.

**Verify:** Model file compiles; trait is registered.

---

### 5. Extend channels.php with Order Update Channels

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Server\routes\channels.php`:

Add channels for real-time order tracking:
- `orders.update.{orderId}` — personal order tracking (guests, specific staff)
- `kitchen.orders.update` — broadcast all order changes to kitchen
- `waiter.orders.update` — broadcast all order changes to waiters

Define authorization for each channel using the same pattern as existing `orders.{orderId}` channel.

**Verify:** channels.php file compiles; all channel functions return bool; no syntax errors.

---

### 6. Fix WaiterManagementController HTTP 500 Error

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Server\app\Http\Controllers\Api\Manager\WaiterManagementController.php`, line 40:

Change:
```php
'floorAssignments' => function ($q) use ($hotelId) {
```

To:
```php
'floorAssignments' => function ($q) {
```

Remove the `use ($hotelId)` clause since `$hotelId` is undefined. The query does not require hotel filtering in this context.

**Verify:** Run GET /api/manager/waiters and expect HTTP 200 with waiter list, not 500.

---

### 7. Fix CashierProfileController Photo Route

Verify `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Server\routes\api.php` contains the route for photo upload.

Check lines around 750-800 where Cashier routes are defined. Look for:
```php
Route::post('/photo', [CashierProfileController::class, 'uploadPhoto']);
```

If the route is missing, add it under the cashier profile prefix:
```php
Route::prefix('cashier/profile')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [CashierProfileController::class, 'getProfile']);
    Route::put('/', [CashierProfileController::class, 'updateProfile']);
    Route::post('/photo', [CashierProfileController::class, 'uploadPhoto']);  // <-- Ensure this exists
    Route::post('/change-password', [CashierProfileController::class, 'changePassword']);
    Route::get('/stats', [CashierProfileController::class, 'getStats']);
});
```

**Verify:** Run POST /api/cashier/profile/photo with a valid image file and expect HTTP 200, not 404.

---

## Frontend Implementation

### 8. Create Echo Configuration Helper

Create `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Client\src\services\echoService.ts`:

This service initializes Laravel Echo, handles WebSocket connection, and provides methods to subscribe to order channels. It should:
- Initialize Echo with Reverb using VITE_REVERB_* environment variables
- Export a function to subscribe to order update channels
- Handle connection/disconnection events
- Log errors in a user-friendly way

**Verify:** File exists and compiles without errors. Echo is properly initialized when imported.

---

### 9. Update KitchenDashboard to Use WebSocket Instead of Polling

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Client\src\views\kitchen\KitchenDashboard.vue`:

Remove:
- The `refreshTimer` and polling logic (lines 89-92)
- The `startAutoRefresh()` function logic that calls setInterval
- Lines that call `kitchenStore.refreshDashboard()` inside the timer

Add:
- Call `echoService.subscribeToOrderUpdates()` in onMounted
- Listen to `OrderStatusChanged` events
- On receipt, update the local kitchenStore with the new order
- Remove the setInterval entirely; only use WebSocket

**Verify:** KitchenDashboard compiles without errors. Orders update in real-time when status changes. No polling occurs in browser console Network tab.

---

### 10. Update PendingOrdersView to Use WebSocket

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Client\src\views\kitchen\PendingOrdersView.vue`:

Remove polling (if any exists). PendingOrdersView displays `kitchenStore.pendingOrders`, which is updated via WebSocket in step 9. Verify the component listens to store updates.

**Verify:** File compiles. Pending orders update instantly when a new order is created or status changes to pending.

---

### 11. Update PreparingOrdersView to Use WebSocket

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Client\src\views\kitchen\PreparingOrdersView.vue`:

Remove polling. PreparingOrdersView displays `kitchenStore.preparingOrders`, which is updated via WebSocket. Ensure the component is reactive to store changes.

**Verify:** File compiles. Preparing orders update instantly when status changes.

---

### 12. Update WaiterDashboard to Use WebSocket

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Client\src\views\waiter\WaiterDashboard.vue`, lines 634-637:

Remove the setInterval polling:
```javascript
autoRefreshTimer = setInterval(() => {
  loadDashboard(true)
}, 15000)
```

Add WebSocket subscription:
```javascript
echoService.subscribeToWaiterOrderUpdates()
```

**Verify:** WaiterDashboard compiles. No setInterval in the code. Orders update in real-time.

---

### 13. Update CashierDashboard to Use WebSocket

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Client\src\views\Cashier\CashierDashboard.vue`, lines 291-294:

Remove the setInterval:
```javascript
refreshInterval = setInterval(() => {
  triggerFetchOrders()
}, 30000)
```

Add WebSocket subscription for order/payment updates.

**Verify:** CashierDashboard compiles. No polling occurs. Updates are real-time via WebSocket.

---

### 14. Create .env Variables for Reverb

Ensure `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Client\.env` contains:

```
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
VITE_REVERB_APP_KEY=m3k5j8h9n2p4q7r1t6w0
```

These values must match the Reverb configuration in the backend render.yaml.

**Verify:** .env file exists and has these keys set.

---

### 15. Update kitchenStore to Handle WebSocket Events

Update `c:\Users\Ashu\Desktop\One_Hotel\One_Hotel\Restaurant_system2\Client\src\stores\kitchenStore.ts`:

Add a new function `receiveOrderUpdate(order: KitchenOrder)` that:
- Takes a WebSocket-delivered order object
- Calls `updateOrder(order)` to merge it into the store
- Updates statistics

This allows views to call `kitchenStore.receiveOrderUpdate(data)` when the WebSocket event arrives.

**Verify:** kitchenStore compiles and exports the new function.

---

## Verification & Testing

After each backend item, run:
```
php artisan migrate --force (if needed)
php artisan serve
```

After each frontend item, run:
```
npm run dev
npm run build (to check for TypeScript errors)
```

**Final Integration Test:**
1. Start both backend (php artisan serve) and frontend (npm run dev)
2. Open Kitchen Dashboard in one browser tab
3. Open a second tab to create a new order via admin panel
4. Watch the Kitchen Dashboard—new order should appear instantly without page refresh
5. Change order status from pending → preparing
6. Watch the Pending and Preparing orders lists update instantly without polling

---

## Summary

- **4 backend files created:** OrderStatusChanged.php, OrderCreated.php, updated channels.php, updated orderService.php
- **2 backend files fixed:** WaiterManagementController.php (undefined $hotelId), routes/api.php (missing photo route)
- **3 frontend services created:** echoService.ts to manage WebSocket
- **6 frontend views updated:** KitchenDashboard, PendingOrdersView, PreparingOrdersView, WaiterDashboard, CashierDashboard, kitchenStore
- **Result:** Zero polling, instant order updates, better UX, reduced server load

---

## Channel Naming Convention

All WebSocket channels follow this pattern:

| Channel Name | Purpose | Subscribers |
|---|---|---|
| `orders.{orderId}` | Order status updates (existing) | Guests, all staff |
| `kitchen.kitchen` | All kitchen orders | Kitchen staff, managers |
| `hotel.waiters` | All waiter assignments | Waiters, managers |
| `orders.update.{orderId}` | NEW: order status change events | Guests tracking specific order |
| `kitchen.orders.update` | NEW: broadcast all order changes to kitchen | Kitchen staff |
| `waiter.orders.update` | NEW: broadcast all order changes to waiters | Waiters |

Existing channels remain active; new channels are additive for real-time updates.

