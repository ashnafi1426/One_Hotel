# Real-Time Dashboard Updates - Implementation Plan

## Objective
Enable instant updates across Order Status Page, Kitchen Dashboard, and Waiter Dashboard without page refresh when order statuses change.

---

## Current State Analysis

### 1. **Order Status Page (Guest)** ✅ ALREADY COMPLETE
- **File:** `src/views/guest/OrderStatusPage.vue`
- **Status:** Already has WebSocket setup via `useOrderStatus` composable
- **Implementation:**
  - Subscribes to private channel: `orders.{orderId}`
  - Listens for events: `.OrderStatusUpdated`, `.PaymentStatusUpdated`, `.OrderCancelled`
  - Updates instantly without polling or refresh
- **Action:** NO CHANGES NEEDED - Already working correctly

### 2. **Kitchen Dashboard** ⚠️ PARTIAL IMPLEMENTATION
- **File:** `src/views/kitchen/KitchenDashboard.vue`
- **Status:** Has WebSocket subscription hooks but NOT actively using them
- **Current Implementation:**
  - Calls `kitchenStore.subscribeToUpdates()` in `onMounted`
  - Calls `kitchenStore.unsubscribeFromUpdates()` in `onBeforeUnmount`
  - BUT: The store's `receiveOrderUpdate()` calls `fetchDashboard()` which RESETS the entire dashboard
- **Issues:**
  - Full `fetchDashboard()` on every WebSocket event = heavy refresh
  - Loading spinners may show briefly
  - Better UX would be incremental updates (move order between statuses without refresh)
- **Action:** OPTIMIZE for incremental updates (optional but recommended)

### 3. **Waiter Dashboard** ❌ MANUAL POLLING ONLY
- **File:** `src/views/waiter/WaiterDashboard.vue`
- **Status:** Manual WebSocket setup that does full dashboard reload
- **Current Implementation:**
  - Sets up manual Echo subscriptions in `setupWebSocket()`
  - Listens to: `.waiter.assigned`, `WaiterAssignedEvent`, `.OrderStatusUpdated`, `.OrderCreated`
  - Calls `loadDashboard(true)` on every event (full reload)
- **Issues:**
  - No formalized stores/composables for state management
  - Dashboard state scattered in component refs
  - Every event triggers full API call and re-render
- **Action:** IMPLEMENT proper WebSocket with incremental updates

---

## Implementation Strategy

### Approach 1: Full Refresh (Current - Simplest)
- Keep existing full `fetchDashboard()` calls
- UI remains responsive but data fully re-fetches
- Works but not optimal for high-frequency updates

### Approach 2: Incremental Updates (Recommended)
- Update only the affected order in the local state
- No full dashboard reload
- Smoother UI, better performance
- Requires careful state management

**We'll implement BOTH:**
- Keep full refresh as fallback
- Add incremental updates for smooth UX
- Detect which orders changed and update only those

---

## Implementation Details

### Kitchen Dashboard Incremental Updates

**File:** `src/stores/kitchenStore.ts`

Current `receiveOrderUpdate()`:
```typescript
function receiveOrderUpdate(data: any) {
  console.log('[kitchenStore] Received order update via WebSocket:', data)
  // Refresh the full dashboard to ensure order list stays in sync
  fetchDashboard()
}
```

**New `receiveOrderUpdate()` - Incremental**:
```typescript
function receiveOrderUpdate(data: any) {
  console.log('[kitchenStore] Received order update via WebSocket:', data)
  
  // Extract order ID from the broadcast data
  const orderId = data.order_id
  if (!orderId) {
    console.warn('[kitchenStore] No order_id in WebSocket data, doing full refresh')
    fetchDashboard()
    return
  }
  
  // Try to find and update existing order
  const existingIndex = orders.value.findIndex((o) => o.id === orderId)
  if (existingIndex !== -1) {
    // Update existing order incrementally
    const updatedOrder = orders.value[existingIndex]
    updatedOrder.status = data.status || updatedOrder.status
    updatedOrder.previous_status = data.previous_status
    updatedOrder.updated_at = data.updated_at
    
    // Trigger Vue reactivity
    orders.value[existingIndex] = { ...updatedOrder }
    
    console.log('[kitchenStore] Incremental update for order:', orderId)
  } else {
    // Order not found locally - do full refresh
    console.log('[kitchenStore] Order not in local state, doing full refresh')
    fetchDashboard()
  }
  
  // Always sync statistics
  syncStatistics()
}
```

### Waiter Dashboard - Create Formal Store

**File:** `src/stores/waiterStore.ts` (NEW)

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import waiterService from '@/services/waiterService'
import echoService from '@/services/echoService'

export const useWaiterStore = defineStore('waiter', () => {
  const assignments = ref<any[]>([])
  const stats = ref({
    todayDeliveries: 0,
    pendingDeliveries: 0,
    onDelivery: 0,
    avgDeliveryTime: 0,
  })
  const activeDelivery = ref<any | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  let unsubscribe: (() => void) | null = null

  async function fetchDashboard() {
    loading.value = true
    error.value = null
    try {
      const data = await waiterService.getDashboard()
      assignments.value = data.recent_assignments || []
      activeDelivery.value = data.active_delivery || null
      
      // Update stats
      stats.value = {
        todayDeliveries: data.today_stats?.completed_deliveries || 0,
        pendingDeliveries: data.today_stats?.pending_assignments || 0,
        onDelivery: data.today_stats?.on_delivery_count || 0,
        avgDeliveryTime: Math.round(data.today_stats?.average_delivery_time || 0),
      }
    } catch (err: any) {
      console.error('[waiterStore] Failed to load dashboard:', err)
      error.value = err.message || 'Failed to load dashboard'
    } finally {
      loading.value = false
    }
  }

  function updateAssignmentStatus(orderId: string, newStatus: string) {
    const index = assignments.value.findIndex((a) => a.order_id === orderId || a.id === orderId)
    if (index !== -1) {
      assignments.value[index].status = newStatus
      assignments.value[index].updated_at = new Date().toISOString()
    }
  }

  function subscribeToUpdates() {
    console.log('[waiterStore] Subscribing to order updates')
    
    // Unsubscribe from previous if exists
    if (unsubscribe) {
      unsubscribe()
    }
    
    // Subscribe to manager order updates (covers all orders)
    unsubscribe = echoService.subscribeToManagerOrderUpdates(
      (data: any) => {
        console.log('[waiterStore] Order update received:', data)
        // Update incrementally or refresh if needed
        if (data.order_id) {
          updateAssignmentStatus(data.order_id, data.status)
        } else {
          fetchDashboard()
        }
      },
      (error: any) => {
        console.error('[waiterStore] WebSocket error:', error)
      }
    )
  }

  function unsubscribeFromUpdates() {
    console.log('[waiterStore] Unsubscribing from updates')
    if (unsubscribe) {
      unsubscribe()
      unsubscribe = null
    }
  }

  return {
    assignments,
    stats,
    activeDelivery,
    loading,
    error,
    fetchDashboard,
    updateAssignmentStatus,
    subscribeToUpdates,
    unsubscribeFromUpdates,
  }
})
```

### Waiter Dashboard View Update

**File:** `src/views/waiter/WaiterDashboard.vue`

Replace `setupWebSocket()` and `teardownWebSocket()` with store usage:

```typescript
import { useWaiterStore } from '@/stores/waiterStore'

const waiterStore = useWaiterStore()
const { assignments: recentAssignments, stats, activeDelivery, loading, error } = storeToRefs(waiterStore)

onMounted(() => {
  restoreCachedData()
  waiterStore.fetchDashboard()
  waiterStore.subscribeToUpdates()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  waiterStore.unsubscribeFromUpdates()
  window.removeEventListener('click', handleOutsideClick)
})
```

---

## Summary of Changes

| Component | Current | New | Benefit |
|-----------|---------|-----|---------|
| **Order Status Page** | WebSocket private channel | ✅ No change | Already instant updates |
| **Kitchen Dashboard** | Full refresh on WebSocket | Incremental + fallback | Smoother UI, less flashing |
| **Waiter Dashboard** | Manual Echo + full reload | Formal store + incremental | Consistent pattern, better performance |

---

## Verification Steps

1. **Order Status Page (Guest)**
   - Open QR order tracking page
   - Change order status from backend (pending → preparing)
   - Verify status updates instantly in browser WITHOUT refresh

2. **Kitchen Dashboard**
   - Open Kitchen Dashboard
   - Create a new order via API or front desk
   - Verify order appears in "Pending" queue instantly
   - Update order status (pending → preparing)
   - Verify order moves to "Preparing" queue WITHOUT full page refresh

3. **Waiter Dashboard**
   - Open Waiter Dashboard
   - Assign a new order to waiter
   - Verify new assignment appears in table instantly
   - Update order status
   - Verify status badge changes WITHOUT full page refresh
   - Navigate away and back (verify cleanup works)

---

## Risk Mitigation

- **Fallback:** If incremental update fails, triggers full `fetchDashboard()`
- **Cleanup:** Proper unsubscribe prevents memory leaks
- **Error Handling:** WebSocket errors logged but don't break UI
- **Backward Compatible:** Kitchen/Waiter Dashboard can run without WebSocket (will just show cached data)

