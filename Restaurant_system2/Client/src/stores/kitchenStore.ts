import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import kitchenService from '@/services/kitchenService'
import echoService from '@/services/echoService'
import type { KitchenOrder, KitchenStatistics } from '@/types/kitchen'

export const useKitchenStore = defineStore('kitchen', () => {
  const orders = ref<KitchenOrder[]>([])
  const statisticsFromApi = ref<KitchenStatistics | null>(null)
  
  // Compute statistics in real-time from orders array for instant updates
  const statistics = computed<KitchenStatistics | null>(() => {
    if (orders.value.length === 0 && !statisticsFromApi.value) {
      return null
    }
    
    // Count orders by status from the live orders array
    const pending = orders.value.filter(o => o.status === 'pending').length
    const preparing = orders.value.filter(o => o.status === 'preparing').length
    const ready = orders.value.filter(o => o.status === 'ready').length
    const served = orders.value.filter(o => o.status === 'served').length
    const cancelled = orders.value.filter(o => o.status === 'cancelled').length
    const total = orders.value.length
    
    // Use API stats as base for today's counts, or compute if not available
    const apiStats = statisticsFromApi.value
    
    return {
      pending_orders: pending,
      preparing_orders: preparing,
      ready_orders: ready,
      served_orders: served,
      cancelled_orders: cancelled,
      total_orders: total,
      today_orders: apiStats?.today_orders ?? total,
      today_pending: apiStats?.today_pending ?? pending,
      today_preparing: apiStats?.today_preparing ?? preparing,
      today_ready: apiStats?.today_ready ?? ready,
      today_served: apiStats?.today_served ?? served,
      avg_prep_time_minutes: apiStats?.avg_prep_time_minutes ?? 0,
    }
  })
  const loading = ref(false)
  const actionLoading = ref<string | null>(null)
  const error = ref<string | null>(null)
  let unsubscribe: (() => void) | null = null

  const pendingOrders = computed(() => orders.value.filter((order) => order.status === 'pending'))
  const preparingOrders = computed(() =>
    orders.value.filter((order) => order.status === 'preparing'),
  )
  const readyOrders = computed(() => orders.value.filter((order) => order.status === 'ready'))
  const completedOrders = computed(() => orders.value.filter((order) => order.status === 'served'))

  async function fetchDashboard() {
    loading.value = true
    error.value = null

    try {
      const data = await kitchenService.refresh()
      const ordersObj = data?.orders || (data as any) || {}
      const pending = Array.isArray(ordersObj.pending) ? ordersObj.pending : []
      const preparing = Array.isArray(ordersObj.preparing) ? ordersObj.preparing : []
      const ready = Array.isArray(ordersObj.ready) ? ordersObj.ready : []
      const served = Array.isArray(ordersObj.served) ? ordersObj.served : []

      orders.value = [...pending, ...preparing, ...ready, ...served]
      statisticsFromApi.value = data?.statistics || null
    } catch (err: any) {
      console.error('[kitchenStore] Failed to load kitchen dashboard:', err)
      error.value =
        err?.response?.data?.message ?? err.message ?? 'Failed to load kitchen dashboard'
    } finally {
      loading.value = false
    }
  }

  const refreshDashboard = fetchDashboard

  async function executeStatusChange(
    orderId: string,
    action: () => Promise<KitchenOrder>,
    fallbackMsg: string,
  ): Promise<KitchenOrder> {
    actionLoading.value = orderId
    error.value = null
    try {
      const updatedOrder = await action()
      updateOrder(updatedOrder)
      syncStatistics()
      return updatedOrder
    } catch (err: any) {
      console.error(`[kitchenStore] ${fallbackMsg}:`, err)
      error.value = err?.response?.data?.message ?? err.message ?? fallbackMsg
      throw err
    } finally {
      actionLoading.value = null
    }
  }

  function startPreparing(orderId: string) {
    return executeStatusChange(
      orderId,
      () => kitchenService.startPreparing(orderId),
      'Failed to start preparing order',
    )
  }

  function markReady(orderId: string) {
    return executeStatusChange(
      orderId,
      () => kitchenService.markReady(orderId),
      'Failed to mark order ready',
    )
  }

  function markServed(orderId: string) {
    return executeStatusChange(
      orderId,
      () => kitchenService.markServed(orderId),
      'Failed to complete order',
    )
  }

  function syncStatistics() {
    kitchenService
      .getStatistics()
      .then((stats) => {
        if (stats) statistics.value = stats
      })
      .catch(() => {})
  }

  function updateOrder(updatedOrder: KitchenOrder) {
    const index = orders.value.findIndex((order) => order.id === updatedOrder.id)

    if (index !== -1) {
      orders.value[index] = updatedOrder
    } else {
      orders.value.unshift(updatedOrder)
    }
  }

  function receiveOrderUpdate(data: any) {
    console.log('[kitchenStore] ? Received order update via WebSocket:', data)
    
    // Extract order ID - backend sends 'id' field in broadcast
    const orderId = data.id || data.order_id
    if (!orderId) {
      console.warn('[kitchenStore] ? No order id in WebSocket data, doing full refresh')
      console.log('[kitchenStore] Data received:', data)
      fetchDashboard()
      return
    }
    
    console.log('[kitchenStore] Processing update for order:', orderId)
    
    // If backend sends full order data in 'order' field, use it
    // Otherwise, we need to fetch full data to avoid partial updates
    if (data.order && typeof data.order === 'object') {
      // Use the full order object from backend
      const fullOrder = data.order as KitchenOrder
      console.log('[kitchenStore] ? Full order data received from WebSocket')
      
      const existingIndex = orders.value.findIndex((o) => o.id === orderId)
      if (existingIndex !== -1) {
        // Replace the entire order to ensure all fields are updated
        orders.value[existingIndex] = fullOrder
        console.log('[kitchenStore] ? Order updated:', orderId, '?', fullOrder.status)
      } else {
        // New order - add it to the list
        orders.value.unshift(fullOrder)
        console.log('[kitchenStore] ? New order added:', orderId, '?', fullOrder.status)
      }
    } else {
      // Backend only sent partial data (status change) - do full refresh to be safe
      console.log('[kitchenStore] ?? Partial data received, doing full refresh')
      fetchDashboard()
      return
    }
    
    // Always sync statistics after update
    syncStatistics()
  }

  /**
   * Subscribe to real-time kitchen order updates via WebSocket
   */
  function subscribeToUpdates() {
    console.log('[kitchenStore] Subscribing to WebSocket updates')

    // Unsubscribe from previous subscription if it exists
    if (unsubscribe) {
      unsubscribe()
    }

    // Subscribe to kitchen order updates and capture the unsubscribe function
    unsubscribe = echoService.subscribeToKitchenOrderUpdates(
      (data: any) => {
        receiveOrderUpdate(data)
      },
      (error: any) => {
        console.error('[kitchenStore] WebSocket error:', error)
      }
    )
  }

  /**
   * Unsubscribe from WebSocket updates
   */
  function unsubscribeFromUpdates() {
    console.log('[kitchenStore] Unsubscribing from WebSocket updates')
    if (unsubscribe) {
      unsubscribe()
      unsubscribe = null
    }
  }

  function clearStore() {
    orders.value = []

    statisticsFromApi.value = null

    error.value = null

    loading.value = false

    actionLoading.value = null

    unsubscribeFromUpdates()
  }

  return {
    orders,

    statistics,

    loading,

    actionLoading,

    error,

    pendingOrders,

    preparingOrders,
    readyOrders,
    completedOrders,
    fetchDashboard,
    refreshDashboard,
    startPreparing,
    markReady,
    markServed,
    updateOrder,
    receiveOrderUpdate,
    subscribeToUpdates,
    unsubscribeFromUpdates,
    clearStore,
  }
})
