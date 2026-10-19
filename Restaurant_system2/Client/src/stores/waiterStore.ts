import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import waiterService from '@/services/waiterService'
import echoService from '@/services/echoService'

/**
 * Waiter Dashboard Store
 *
 * Manages waiter assignments, delivery tracking, and real-time updates via WebSocket.
 * Features:
 * - Centralized state for waiter dashboard
 * - Real-time updates via Echo WebSocket (incremental + fallback to full refresh)
 * - Proper subscription cleanup on unmount
 * - Performance-optimized incremental updates
 */

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

  /**
   * Fetch complete waiter dashboard data
   */
  async function fetchDashboard(showLoading = false) {
    if (showLoading) {
      loading.value = true
    }
    error.value = null
    try {
      const data = await waiterService.getDashboard({ refresh: 'true' })
      assignments.value = data.recent_assignments || []
      activeDelivery.value = data.active_delivery || null

      // Update stats
      stats.value = {
        todayDeliveries: data.today_stats?.completed_deliveries || 0,
        pendingDeliveries: data.today_stats?.pending_assignments || 0,
        onDelivery: data.today_stats?.on_delivery_count || 0,
        avgDeliveryTime: Math.round(data.today_stats?.average_delivery_time || 0),
      }

      console.log('[waiterStore] Dashboard loaded:', {
        assignments: assignments.value.length,
        activeDelivery: !!activeDelivery.value,
        stats: stats.value,
      })
    } catch (err: any) {
      console.error('[waiterStore] Failed to load dashboard:', err)
      error.value = err.message || 'Failed to load dashboard'
    } finally {
      if (showLoading) {
        loading.value = false
      }
    }
  }

  /**
   * Update a single assignment status incrementally (no full refresh flicker)
   */
  function updateAssignmentStatus(orderId: string, newStatus: string) {
    const index = assignments.value.findIndex(
      (a) => String(a.order_id) === String(orderId) || String(a.id) === String(orderId)
    )
    if (index !== -1) {
      assignments.value[index].status = newStatus
      assignments.value[index].order_status = newStatus
      if (newStatus === 'ready') {
        assignments.value[index].is_ready = true
      }
      assignments.value[index].updated_at = new Date().toISOString()
      console.log('[waiterStore] ✨ Incremental update - assignment', orderId, '→', newStatus)
      return true
    }
    return false
  }

  /**
   * Handle WebSocket order update
   */
  function receiveOrderUpdate(data: any) {
    console.log('[waiterStore] Received order update via WebSocket:', data)

    const orderId = data.order_id || data.id || data.order?.id
    const status = data.status || data.order?.status || data.new_status

    if (orderId && status) {
      updateAssignmentStatus(String(orderId), status)
    }

    // Always fetch fresh dashboard data silently to update stats & cards
    fetchDashboard(false)
  }

  /**
   * Subscribe to real-time order updates via WebSocket
   */
  let unsubscribers: (() => void)[] = []

  /**
   * Subscribe to real-time order updates via WebSocket
   */
  function subscribeToUpdates() {
    console.log('[waiterStore] Subscribing to WebSocket updates')

    // Unsubscribe from previous subscriptions if they exist
    unsubscribeFromUpdates()

    try {
      const unsubManager = echoService.subscribeToManagerOrderUpdates(
        (data: any) => {
          receiveOrderUpdate(data)
        },
        (error: any) => {
          console.error('[waiterStore] Manager WebSocket error:', error)
        }
      )
      if (typeof unsubManager === 'function') {
        unsubscribers.push(unsubManager)
      }
    } catch (e) {
      console.warn('[waiterStore] Error subscribing to manager channel:', e)
    }

    try {
      const unsubWaiter = echoService.subscribeToWaiterOrderUpdates(
        (data: any) => {
          receiveOrderUpdate(data)
        },
        (error: any) => {
          console.error('[waiterStore] Waiter WebSocket error:', error)
        }
      )
      if (typeof unsubWaiter === 'function') {
        unsubscribers.push(unsubWaiter)
      }
    } catch (e) {
      console.warn('[waiterStore] Error subscribing to waiter channel:', e)
    }
  }

  /**
   * Unsubscribe from WebSocket updates and cleanup
   */
  function unsubscribeFromUpdates() {
    console.log('[waiterStore] Unsubscribing from WebSocket updates')
    while (unsubscribers.length > 0) {
      const unsub = unsubscribers.pop()
      try {
        unsub?.()
      } catch (e) {
        // ignore cleanup error
      }
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
    receiveOrderUpdate,
    subscribeToUpdates,
    unsubscribeFromUpdates,
  }
})
