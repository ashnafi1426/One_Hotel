# Pusher Migration Implementation Plan

## Diagnosis

**Current State:**
- Backend: Configured for both Reverb and Pusher in `config/broadcasting.php`, but render.yaml has `BROADCAST_CONNECTION=reverb`
- Frontend: Configured for Reverb in Client/.env and Client/src/plugins/echo.ts
- Backend Events: OrderCreated and OrderStatusUpdated broadcast to `kitchen.orders.update` channel
- Kitchen Store: Subscribes to kitchen order updates via Echo
- Channel Authorization: Properly implemented in routes/channels.php with role-based authorization

**Root Cause of Unreliable Production Behavior:**
1. `render.yaml` specifies `BROADCAST_CONNECTION=reverb` but Reverb is a self-hosted WebSocket server that requires specific infrastructure setup
2. Frontend is still pointing to Reverb (`VITE_REVERB_*` vars) instead of Pusher
3. Laravel Echo plugin is configured with `broadcaster: 'reverb'` instead of `broadcaster: 'pusher'`
4. When deployed, the reverb connection string attempts to connect to a non-existent or misconfigured reverse proxy path (`/app`)

**Why Pusher is Better for Production:**
- Managed third-party WebSocket service with global CDN
- No infrastructure setup required beyond credentials
- Built-in redundancy and failover
- Proper firewall/NAT traversal with fallback transports

---

## Implementation Plan

- [ ] 1. Update render.yaml backend environment variables.
      Replace Reverb connection with Pusher configuration using the provided credentials.
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/render.yaml
      Verify: Open render.yaml and confirm BROADCAST_CONNECTION=pusher and all PUSHER_* vars are present (PUSHER_APP_ID, PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_CLUSTER, PUSHER_SCHEME, PUSHER_PORT).

- [ ] 2. Update Client/.env.production with Pusher-specific variables.
      Replace VITE_REVERB_* variables with VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER. Remove VITE_REVERB_PATH since Pusher doesn't need a custom path.
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Client/.env
      Verify: Check that the file contains VITE_PUSHER_APP_KEY=5df8dcb92d20f713b1f2 and VITE_PUSHER_APP_CLUSTER=ap2 and no REVERB vars remain.

- [ ] 3. Update Client/.env.example with Pusher environment variables template.
      Replace example VITE_REVERB_* variables with VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER for documentation.
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Client/.env.example
      Verify: Check that the file shows the correct template with VITE_PUSHER_* vars and instructive comments.

- [ ] 4. Update Client/src/plugins/echo.ts to use Pusher broadcaster.
      Change broadcaster from 'reverb' to 'pusher', replace wsHost/wsPort/wssPort config with key and cluster, remove path config and VITE_REVERB_* env vars. Keep the same custom authorizer function.
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Client/src/plugins/echo.ts
      Verify: Build the frontend with `npm run build` in the Client directory and confirm no errors occur.

- [ ] 5. Update Client/src/services/echoService.ts to use Pusher configuration.
      Update initializeEcho() function to initialize Echo with broadcaster: 'pusher', use VITE_PUSHER_APP_KEY and VITE_PUSHER_APP_CLUSTER instead of Reverb variables. Keep all other subscription functions unchanged.
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Client/src/services/echoService.ts
      Verify: Check that the service initializes correctly by reviewing the code for proper Pusher config structure.

- [ ] 6. Verify backend Pusher SDK is installed and available.
      Check that pusher/pusher-php-server is in composer.json require section. If missing, it would need to be added with `composer require pusher/pusher-php-server:^7.3`.
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Server/composer.json
      Verify: Confirm "pusher/pusher-php-server": "^7.3" (or higher compatible version) is listed in the require section.

- [ ] 7. Ensure backend broadcasting events remain unchanged.
      OrderCreated and OrderStatusUpdated events already broadcast to the correct channels (kitchen.orders.update, waiter.orders.update, manager.orders.update). No changes required.
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Server/app/Events/OrderStatusUpdated.php, c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Server/app/Events/OrderCreated.php
      Verify: Events are already correct, no code changes needed. Confirm with grep that they still broadcast to the correct channels.

- [ ] 8. Verify channel authorization remains secure.
      routes/channels.php already implements proper role-based authorization for kitchen, waiter, and manager channels. No changes needed.
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Server/routes/channels.php
      Verify: Review the file to confirm kitchen channel authorization checks for kitchen-related roles (chef, cook, kitchen_staff, manager, admin). No code changes needed.

- [ ] 9. Update .env.production or deployment documentation to include Pusher credentials.
      Ensure that the Render production environment has the Pusher credentials set correctly. Document that PUSHER_APP_SECRET must never be exposed in frontend code, logs, or Vite variables.
      Files: Deployment configuration (render.yaml, or equivalent environment setup documentation)
      Verify: Confirm that render.yaml has been updated in step 1 with the Pusher credentials in environment variables.

- [ ] 10. Test the migration locally by switching to Pusher in development.
      Create or update a .env.development file to use Pusher with development app credentials, or simulate the production setup locally by updating the Client/.env to point to production Pusher cluster (optional for testing).
      Files: c:/Users/Ashu/Desktop/One_Hotel/One_Hotel/Restaurant_system2/Client/.env (already handles production)
      Verify: Start the development server with `npm run dev` in the Client directory and kitchen service with `npm run dev` in the Server directory. Check browser console for successful Echo connection to Pusher and order events being received without errors.

---

## Security Checklist

✓ PUSHER_APP_SECRET is stored only in render.yaml (backend environment)  
✓ Frontend uses only VITE_PUSHER_APP_KEY (public key for client-side)  
✓ Frontend uses only VITE_PUSHER_APP_CLUSTER (region, not secret)  
✓ No credentials are hardcoded in source files  
✓ Channel authorization is role-based and enforced server-side  
✓ Private channels use proper authorization callbacks  
✓ No sensitive data exposed in broadcastWith() payloads  

---

## Credential Values (for reference during implementation)

- PUSHER_APP_ID: 2203245
- PUSHER_APP_KEY: 5df8dcb92d20f713b1f2
- PUSHER_APP_SECRET: b08a3aed411fd2d1d155 (BACKEND ONLY - NEVER in frontend)
- PUSHER_APP_CLUSTER: ap2
- VITE_PUSHER_APP_KEY: 5df8dcb92d20f713b1f2 (frontend public key, same as PUSHER_APP_KEY)
- VITE_PUSHER_APP_CLUSTER: ap2

---

## Notes

- All changes are additive or replacement of environment variables; no database migrations required
- Kitchen Dashboard component does not need changes; it already uses echoService.subscribeToKitchenOrderUpdates()
- Broadcasting event names remain stable; Pusher uses the same Laravel Echo interface
- Fallback transports configured in echo.ts allow graceful degradation if WebSocket is blocked
