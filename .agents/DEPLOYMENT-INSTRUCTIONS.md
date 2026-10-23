# Deployment Instructions - Real-Time Kitchen Dashboard

## 🚀 Quick Deploy Guide

### Step 1: Render Backend Deployment

**Option A: Auto-Deploy (Recommended)**
1. Go to: https://dashboard.render.com
2. Select service: `one-hotel-backend`
3. Wait for auto-deployment from GitHub (should trigger automatically)
4. Monitor logs for any errors
5. Verify `BROADCAST_CONNECTION=pusher` is set in Environment tab

**Option B: Manual Deployment**
1. Go to: https://dashboard.render.com → `one-hotel-backend`
2. Click **"Manual Deploy"** button
3. Select branch: **`main`**
4. Click **"Deploy latest commit"**
5. Watch logs scroll by
6. Wait for service to show "Live" status

**Verification:**
- Service status should be "Live" (green)
- No errors in the Logs tab
- Check environment variables contain:
  - `BROADCAST_CONNECTION=pusher`
  - `PUSHER_APP_ID=2203245`
  - `PUSHER_APP_KEY=5df8dcb92d20f713b1f2`
  - `PUSHER_APP_SECRET` (should be set)
  - `PUSHER_APP_CLUSTER=ap2`

---

### Step 2: Vercel Frontend Deployment

**Auto-Deploy (Automatic)**
1. Go to: https://vercel.com/dashboard
2. Select project: `One_Hotel` (or your project name)
3. Should show **automatic deployment in progress** from GitHub
4. Wait for "Production" deployment to complete (shows green checkmark)
5. Click on deployment to verify it succeeded

**If auto-deploy doesn't trigger:**
1. Go to Vercel Dashboard
2. Click **"Redeploy"** on the latest deployment
3. Select **"Redeploy with existing Build Cache"**
4. Wait for deployment to complete

**Verification:**
- Production deployment shows green checkmark ✅
- Visit: https://one-hotel-lemon.vercel.app
- No 5xx errors in browser console
- Check that new commit hash is deployed

---

### Step 3: Verify Environment Variables

#### On Render Backend:

Go to: `one-hotel-backend` → **Environment**

Required variables:
```
✅ BROADCAST_CONNECTION=pusher
✅ PUSHER_APP_ID=2203245
✅ PUSHER_APP_KEY=5df8dcb92d20f713b1f2
✅ PUSHER_APP_SECRET=[Must be set - check it exists]
✅ PUSHER_APP_CLUSTER=ap2
✅ PUSHER_SCHEME=https
✅ PUSHER_PORT=443
```

#### On Vercel Frontend:

Go to: Project Settings → **Environment Variables**

Required variables:
```
✅ VITE_PUSHER_APP_KEY=5df8dcb92d20f713b1f2
✅ VITE_PUSHER_APP_CLUSTER=ap2
✅ VITE_API_BASE_URL=https://one-hotel-backend.onrender.com
```

---

## 📋 Pre-Deployment Checklist

- [ ] All commits pushed to GitHub (branch `main`)
- [ ] Latest commit: `bfc87cd` (or later)
- [ ] Render service updated with latest code
- [ ] Vercel deployment in progress
- [ ] PUSHER_APP_SECRET is set in Render dashboard
- [ ] No hardcoded secrets in code
- [ ] Frontend build succeeds (npm run build)
- [ ] Backend tests pass (if applicable)

---

## 🧪 Testing After Deployment

### Test 1: Connection Test (5 minutes)

**Open Kitchen Dashboard:**
```
URL: https://one-hotel-lemon.vercel.app/kitchen
Login as: Chef or Kitchen Staff

DevTools Console should show:
✅ [Echo] ✅ WebSocket connected to Pusher
✅ [Echo] ✅ Subscribed to kitchen order updates
```

If you see errors:
```
❌ [Echo] Authorization failed
  → Check: User role includes kitchen permissions
  → Check: Token is valid
  → Check: /api/broadcasting/auth endpoint works

❌ [Echo] Connection error
  → Check: Pusher credentials are correct
  → Check: BROADCAST_CONNECTION=pusher on backend
  → Wait 1-2 minutes for env vars to sync
```

### Test 2: Real-Time Update Test (5 minutes)

**Create Order:**
1. Go to Admin Dashboard: `https://one-hotel-lemon.vercel.app/admin/orders/create`
2. Create a test order with menu items
3. Click "Create Order"

**Verify Kitchen Dashboard Updates:**
1. Check Kitchen Dashboard in another window
2. New order should appear instantly
3. Console should show:
   ```
   [Echo] ✨ New order created in kitchen: {order_data}
   ```

**If order doesn't appear:**
- Check backend logs on Render
- Verify `BROADCAST_CONNECTION=pusher`
- Check Pusher dashboard for event delivery
- Verify auth token in browser DevTools

### Test 3: Status Change Test (5 minutes)

**From Kitchen Dashboard:**
1. Click "Start Preparing" on a pending order
2. Order should move from Pending to Preparing instantly
3. Console should show:
   ```
   [Echo] ✨ Kitchen order status changed: {status: "preparing"}
   ```

**Check all status transitions:**
- Pending → Preparing
- Preparing → Ready
- Ready → Served

All should update instantly without refresh.

---

## 🔍 Debugging in Production

### Check Backend Logs (Render):

1. Go to: `one-hotel-backend` → **Logs**
2. Look for:
   ```
   ✅ "Broadcasting event to Pusher"
   ✅ "Event dispatched successfully"
   ❌ "Failed to broadcast" (means Pusher credentials wrong)
   ❌ "Connection refused" (means Pusher down or blocked)
   ```

### Check Pusher Activity:

1. Go to: https://dashboard.pusher.com
2. Select your app
3. Go to **Console** tab
4. Create an order
5. Should see event in real-time
6. Check for any errors

### Check Frontend Network:

Browser DevTools → Network:
1. Filter by "WS" (WebSocket)
2. Should see connection to `wss://ws-ap2.pusher.com`
3. Click connection → **Messages** tab
4. Create order
5. Should see message events
6. Check for any failures

---

## ⏱️ Deployment Timeline

| Step | Duration | Status |
|------|----------|--------|
| Git push | Instant | ✅ Done |
| GitHub receives | < 5 sec | ✅ Done |
| Render webhook | < 1 min | Auto |
| Backend build | 2-3 min | Auto |
| Backend deploy | 1-2 min | Auto |
| Backend live | ~ 5 min total | Auto |
| Vercel webhook | < 1 min | Auto |
| Vercel build | 2-3 min | Auto |
| Vercel deploy | 1-2 min | Auto |
| Vercel live | ~ 5 min total | Auto |
| **Total time** | **~10 minutes** | ✅ |

---

## 🆘 If Something Goes Wrong

### Backend Deployment Failed

**Check:**
1. Render Logs tab for error messages
2. Verify git push succeeded: `git log --oneline -1`
3. Check environment variables are correct
4. Verify Dockerfile is valid

**Fix:**
```bash
git log --oneline -1
# If correct commit not shown, check:
git status
git push origin main -f  # Force push if needed
```

### Vercel Deployment Failed

**Check:**
1. Vercel Deployments tab for build errors
2. Check Node.js version matches requirements
3. Verify package.json is valid
4. Check all required env vars are set

**Fix:**
```bash
# Rebuild locally
npm install
npm run build
# If builds locally, Vercel will deploy successfully
```

### Real-Time Still Not Working

**Debug steps:**

1. **Check Pusher connection:**
   ```
   DevTools → Network → WS filter
   Should show: wss://ws-ap2.pusher.com
   If missing: Pusher not configured
   ```

2. **Check auth endpoint:**
   ```
   DevTools → Network → XHR filter
   Look for: /api/broadcasting/auth
   Should return: 200 OK with channel_data
   If 403: User not authorized
   If 404: Endpoint missing
   ```

3. **Check channel subscription:**
   ```
   Console should show:
   [Echo] ✅ Subscribed to kitchen order updates
   If error: Check channels.php for private-kitchen.orders.update
   ```

4. **Check event delivery:**
   ```
   Create order and check console for:
   [Echo] ✨ New order created in kitchen
   If missing: Check OrderCreated event broadcasts to correct channel
   ```

---

## 📞 Support Resources

- **Render Docs:** https://render.com/docs
- **Vercel Docs:** https://vercel.com/docs
- **Pusher Docs:** https://pusher.com/docs
- **Laravel Broadcasting:** https://laravel.com/docs/broadcasting

---

## ✅ Success Criteria

Deployment is successful when:

- ✅ Kitchen Dashboard connects without errors
- ✅ Console shows successful Pusher connection
- ✅ Console shows successful channel authorization
- ✅ Creating order makes it appear instantly
- ✅ Changing status updates instantly
- ✅ No page refresh needed
- ✅ WebSocket connection visible in DevTools
- ✅ Multiple users sync correctly
- ✅ No errors in backend logs
- ✅ No errors in browser console

---

## 🎉 You're Done!

Real-time Kitchen Dashboard is now live! 

**Next Steps:**
- Monitor logs for first 24 hours
- Watch for any errors or issues
- Keep Pusher credentials safe
- Celebrate! 🎊

---

**Deployment Date:** [Your Date]  
**Commit:** `bfc87cd`  
**Status:** Ready to Deploy  
**Time Required:** ~10-15 minutes
