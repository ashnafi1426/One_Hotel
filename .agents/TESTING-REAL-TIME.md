# Testing Real-Time Kitchen Dashboard Updates

## Quick Test Steps

### Step 1: Open Kitchen Dashboard (2 Windows)

1. **Window 1:** Admin/Manager - Order Creation Panel
   - Open: `https://one-hotel-lemon.vercel.app/admin/orders/create`
   - Login as Admin/Manager

2. **Window 2:** Kitchen Staff - Kitchen Dashboard
   - Open: `https://one-hotel-lemon.vercel.app/kitchen`
   - Login as Chef/Kitchen Staff
   - **Open Browser DevTools** (F12 → Console)

---

### Step 2: Check Connection Status

In Kitchen Dashboard console, look for:

```
[Echo] ✅ Connected to Pusher WebSocket server
[Echo] ✅ Subscribed to kitchen order updates (kitchen.orders.update channel)
```

---

### Step 3: Test - Create New Order

**Window 1 (Admin):**
1. Create a new order with menu items
2. Click "Create Order"
3. Note the order number (e.g., #1001)

**Window 2 (Kitchen):**
- Watch the Pending Orders section
- Order should appear instantly (within 1 second)
- Console should show:
  ```
  [Echo] ✨ New order created in kitchen: {...}
  ```

✅ **If order appears without refresh** = Real-time working!

---

### Step 4: Test - Order Status Changes

**Window 2 (Kitchen Dashboard):**
1. Click "Start Preparing" on the pending order
2. Order should move to "Preparing" section instantly
3. Console should show:
   ```
   [Echo] ✨ Kitchen order status changed: {...}
   ```

**Repeat for:**
- "Mark Ready" button
- "Mark Served" button

✅ **All status changes appear instantly** = Real-time working!

---

### Step 5: Monitor WebSocket

**Window 2 DevTools:**

1. Go to **Network** tab
2. Filter by **WS** (WebSocket)
3. Look for connection to: `wss://ws-*.pusher.com`
4. Click on the connection
5. Go to **Messages** tab
6. You should see activity when events occur:
   ```json
   {
     "event": "order.status.changed",
     "data": {...},
     "channel": "kitchen.orders.update"
   }
   ```

---

## Debugging Checklist

### If Orders DON'T Update Automatically:

- [ ] Check console for errors:
  ```
  [Echo] ❌ Connection error
  [Echo] ⚠️ Authorization failed
  ```

- [ ] Verify authentication:
  - Token is being sent in Authorization header
  - User role includes kitchen permissions (chef, cook, kitchen_staff, manager, admin)

- [ ] Check network:
  - WebSocket connection to Pusher is established
  - No CORS errors

- [ ] Verify environment:
  - `BROADCAST_CONNECTION=pusher` in backend
  - `PUSHER_APP_KEY` matches in both `.env` files
  - Render service is running

---

### Common Issues & Fixes

#### Issue: "Authorization failed"
```
[Echo] ❌ Authorization failed - HTTP 403
```
**Fix:** 
- User must have kitchen role (chef, cook, kitchen_staff, manager, admin)
- Check user's role in database
- Verify token is valid

#### Issue: "Connection refused"
```
[Echo] ⚠️ Connection error: Network error
```
**Fix:**
- Check Pusher credentials (PUSHER_APP_KEY, PUSHER_APP_CLUSTER)
- Verify `BROADCAST_CONNECTION=pusher` on backend
- Wait 1-2 minutes for environment variables to sync on Render

#### Issue: Events coming via polling (slow)
```
WebSocket connection shows as "polling"
```
**Fix:**
- This is a fallback (e.g., if WebSocket is blocked by corporate firewall)
- Orders still update but with ~5 second delay
- To use WebSocket directly, check network settings

---

## Expected Behavior

### ✅ Working Correctly:
- New order appears in Pending section instantly
- Status changes happen within 1 second
- No page refresh needed
- Browser console shows `[Echo] ✨` messages
- WebSocket shows active messages

### ❌ NOT Working:
- Orders only appear after refresh
- Manual "Refresh" button needed
- Status changes don't show until refresh
- Console shows no Echo messages
- No WebSocket connection in network tab

---

## Load Test

For multiple concurrent dashboards:

1. Open Kitchen Dashboard in 3-5 browser windows
2. Create multiple orders in quick succession
3. All windows should update simultaneously
4. No "out of sync" ordering

---

## Console Output Reference

### Successful Connection & Events:

```
[Echo] Initialized with Pusher connection (cluster: ap2)
[Echo] ✅ WebSocket connected to Pusher
[Echo] Authorizing channel: kitchen.orders.update
[Echo]  Channel authorization successful: kitchen.orders.update
[Echo] ✅ Subscribed to kitchen order updates (kitchen.orders.update channel)

[Echo] ✨ New order created in kitchen: {id: "...", order_number: 1001, ...}
[Echo] ✨ Kitchen order status changed: {id: "...", status: "preparing", ...}
```

### Errors to Fix:

```
[Echo] ❌ Disconnected from Pusher WebSocket server      // Network issue
[Echo] ⚠️ Connection error: Error: ...                   // Credential issue
[Echo] ❌ Authorization failed - HTTP 401/403           // Auth issue
```

---

## Performance Metrics

- **Connection time:** < 1 second
- **New order broadcast:** < 100ms after server receives request
- **Status update broadcast:** < 100ms after status change
- **Frontend display update:** < 500ms (Vue reactivity)
- **Total latency:** < 1 second end-to-end

---

## After Confirming Real-Time Works

✅ Kitchen Dashboard is receiving live updates  
✅ No manual refresh needed  
✅ Status changes happen instantly  
✅ Multiple concurrent users sync correctly  

You can now:
- Deploy with confidence
- Remove any polling fallback if needed
- Monitor performance metrics
- Scale to production

---

**Test Date:** [Your Date]  
**Status:** ✅ Verified / ⚠️ In Progress / ❌ Failed  
**Notes:** _______________

---

**Reference:** REAL-TIME-FIX.md  
**Commit:** `ae0a5a6`
