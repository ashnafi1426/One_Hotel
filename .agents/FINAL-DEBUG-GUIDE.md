# Final Debug Guide - Real-Time Kitchen Dashboard

## Current Issue

```
POST https://one-hotel-lemon.vercel.app/broadcasting/auth 405 (Method Not Allowed)
```

The auth request is going to the **frontend** (Vercel) instead of the **backend** (Render).

## What Just Changed

**Commit:** `197a7d4`

Enhanced the Echo authorizer with detailed logging to show:
- Exact auth URL being called
- API base URL value
- Token presence
- Authorization request/response details

---

## Deploy & Test Now

### Step 1: Deploy Backend (on Render)
```
1. Go to: https://dashboard.render.com
2. Select: one-hotel-backend  
3. Click: Manual Deploy
4. Wait: ~2 minutes for "Live" status
```

### Step 2: Deploy Frontend (Vercel - auto-deploying)
```
1. Go to: https://vercel.com/dashboard
2. Watch: Production deployment completes
```

### Step 3: Open Kitchen Dashboard & Check Console

```
Open: https://one-hotel-lemon.vercel.app/chef
DevTools: F12 → Console

Look for these NEW debug logs:
```

**CRITICAL LOGS TO LOOK FOR:**

```
[Echo] Auth URL: https://one-hotel-backend.onrender.com/api/broadcasting/auth
                   ↑↑↑ THIS MUST BE THE BACKEND URL ↑↑↑

If you see:
[Echo] Auth URL: https://one-hotel-lemon.vercel.app/api/broadcasting/auth
                   ↑↑↑ THIS IS WRONG - Should be onrender.com ↑↑↑
```

---

## If Still Getting 405 Error

### Check 1: Verify Environment Variables

**Render Backend - Check these are set:**
```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=2203245
PUSHER_APP_KEY=5df8dcb92d20f713b1f2
PUSHER_APP_SECRET=[set in dashboard]
PUSHER_APP_CLUSTER=ap2
```

**Vercel Frontend - Check these are set:**
```
VITE_API_BASE_URL=https://one-hotel-backend.onrender.com
VITE_PUSHER_APP_KEY=5df8dcb92d20f713b1f2
VITE_PUSHER_APP_CLUSTER=ap2
```

### Check 2: Verify Backend Route

On Render, check logs for:
```
POST /api/broadcasting/auth
```

If you don't see this, the route is not being hit.

### Check 3: Check VITE_API_BASE_URL

In Kitchen Dashboard console, you should see:
```
[Echo] API URL: https://one-hotel-backend.onrender.com
```

If it shows something else or blank, the env var is not loaded.

---

## Expected Success Logs

```
[Echo] Initialized with Pusher connection (cluster: ap2)
[Echo] Authorizing channel: private-kitchen.orders.update
[Echo] API URL: https://one-hotel-backend.onrender.com
[Echo] Auth URL: https://one-hotel-backend.onrender.com/api/broadcasting/auth
[Echo] Auth response status: 200
[Echo] ✅ Channel authorization successful: private-kitchen.orders.update
[Echo] ✅ Subscribed to kitchen order updates (private-kitchen.orders.update channel)
[Echo] ✅ WebSocket connected to Pusher
```

---

## If VITE_API_BASE_URL is blank/missing

**On Vercel:**
1. Go to: Project Settings → Environment Variables
2. Add/Update:
   ```
   VITE_API_BASE_URL=https://one-hotel-backend.onrender.com
   ```
3. Redeploy

**Then in browser:**
- Hard refresh: Ctrl+Shift+R (or Cmd+Shift+R on Mac)
- Clear cache if needed

---

## Next Action

1. **Deploy backend now** on Render
2. **Wait for Vercel** to auto-deploy
3. **Hard refresh** Kitchen Dashboard (Ctrl+Shift+R)
4. **Open DevTools Console**
5. **Check for the debug logs above**
6. **Report what you see**

The new logging will tell us exactly what's happening.

---

**Commit:** `197a7d4`  
**Status:** Enhanced debugging ready  
**Next:** Deploy and check console logs
