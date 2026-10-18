# Environment Setup Guide - Pusher Integration

## Render Production Backend Environment Variables

These variables must be set in the Render dashboard for the `one-hotel-backend` service:

### Required - Pusher Configuration

```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=2203245
PUSHER_APP_KEY=5df8dcb92d20f713b1f2
PUSHER_APP_SECRET=[SET IN RENDER DASHBOARD - DO NOT COMMIT]
PUSHER_APP_CLUSTER=ap2
PUSHER_SCHEME=https
PUSHER_PORT=443
```

### How to Set in Render Dashboard

1. Navigate to your Render service: `one-hotel-backend`
2. Go to **Environment** tab
3. Add or update each variable:
   - Click **Add Environment Variable**
   - Enter key and value
   - For `PUSHER_APP_SECRET`, ensure it's only set in dashboard (not in render.yaml)

### Secret Storage

- **PUSHER_APP_SECRET**: Use Render's secure storage (environment variable sync)
  - Set `sync: false` in render.yaml means the value is manually entered in dashboard
  - This prevents the secret from being committed to Git

### Verify in render.yaml

The render.yaml file already contains these entries. Example:

```yaml
- key: BROADCAST_CONNECTION
  value: pusher
- key: PUSHER_APP_ID
  value: "2203245"
- key: PUSHER_APP_KEY
  value: 5df8dcb92d20f713b1f2
- key: PUSHER_APP_SECRET
  sync: false  # ← Secret value set in Render dashboard only
```

---

## Frontend Environment Variables

The frontend `.env` file is already configured:

**File:** `Restaurant_system2/Client/.env`

```
VITE_PUSHER_APP_KEY=5df8dcb92d20f713b1f2
VITE_PUSHER_APP_CLUSTER=ap2
```

**Important:** These are public values only. The app secret is never included.

---

## Local Development Setup (Optional)

To test locally with Pusher development credentials:

1. Create `Restaurant_system2/Client/.env.local`:
```
VITE_PUSHER_APP_KEY=your_dev_pusher_key
VITE_PUSHER_APP_CLUSTER=mt1
VITE_API_BASE_URL=http://127.0.0.1:8000
```

2. Create `Restaurant_system2/Server/.env.local`:
```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_dev_app_id
PUSHER_APP_KEY=your_dev_pusher_key
PUSHER_APP_SECRET=your_dev_pusher_secret
PUSHER_APP_CLUSTER=mt1
```

3. Start services:
```bash
cd Restaurant_system2/Server && php artisan serve
cd Restaurant_system2/Client && npm run dev
```

---

## Verifying the Setup

### 1. Backend Configuration
```bash
# Check if Pusher is selected as broadcaster
php artisan config:show broadcast.default
```

Should output: `pusher`

### 2. Frontend Connection
Open browser console and look for:
```
[Echo] ✅ Connected to Pusher WebSocket server
```

### 3. Event Broadcasting
- Create an order
- Check browser console for:
  ```
  [Echo] ✨ New order created in kitchen: {...}
  [Echo] ✨ Kitchen order status changed: {...}
  ```

### 4. Check Network
- Open DevTools → Network tab
- Look for WebSocket connections to Pusher (`wss://...`)

---

## Troubleshooting

### Connection Fails
- Verify `PUSHER_APP_KEY` matches between render.yaml and Pusher dashboard
- Check `PUSHER_APP_CLUSTER` is correct (ap2 for Asia-Pacific)
- Ensure Render environment variables are synced (wait ~1 minute after update)

### Events Not Broadcasting
- Check that `BROADCAST_CONNECTION=pusher` in backend
- Verify kitchen user has correct role in database
- Check Laravel logs on Render for broadcasting errors

### Authorization Errors
- Verify token is being sent in Authorization header
- Check `/api/broadcasting/auth` endpoint returns correct channel auth

---

## Production Checklist

- [ ] Set `PUSHER_APP_SECRET` in Render dashboard
- [ ] Verify `BROADCAST_CONNECTION=pusher` deployed
- [ ] Test order creation triggers real-time updates
- [ ] Monitor Render logs for any broadcasting errors
- [ ] Verify WebSocket connection in browser DevTools
- [ ] Check Kitchen Dashboard receives live updates

---

## Quick Reference

| Component | Value |
|-----------|-------|
| Broadcaster | Pusher |
| Cluster | ap2 (Asia-Pacific) |
| Port | 443 (HTTPS) |
| Protocol | WebSocket Secure (wss) |
| Fallback | Polling |
| Frontend Secret Exposure | ❌ None |
| Backend Secret Storage | ✅ Render Secure Variables |

---

**Last Updated:** 2025-10-18  
**Migration Status:** ✅ Complete and Pushed to GitHub
