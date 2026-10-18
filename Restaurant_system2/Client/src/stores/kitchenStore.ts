import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import kitchenService from '@/services/kitchenService'
import echoService from '@/services/echoService'
import type { KitchenOrder, KitchenStatistics } from '@/types/kitchen'

export const useKitchenStore = defineStore('kitchen', () => {
  const orders = ref<KitchenOrder[]>([])
  const statistics = ref<KitchenStatistics | null>(null)
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
      statistics.value = data?.statistics || null
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
    console.log('[kitchenStore] Received order update via WebSocket:', data)
    
    // Extract order ID from the broadcast data
    const orderId = data.order_id
    if (!orderId) {
      console.warn('[kitchenStore] No order_id in WebSocket data, doing full refresh')
      fetchDashboard()
      return
    }
    
    // Try to find and update existing order incrementally (without full dashboard refresh)
    const existingIndex = orders.value.findIndex((o) => o.id === orderId)
    if (existingIndex !== -1) {
      // Update existing order incrementally
      const updatedOrder = orders.value[existingIndex]
      updatedOrder.status = data.status || updatedOrder.status
      updatedOrder.previous_status = data.previous_status
      updatedOrder.updated_at = data.updated_at
      
      // Trigger Vue reactivity by reassigning the array element
      orders.value[existingIndex] = { ...updatedOrder }
      
      console.log('[kitchenStore] ✨ Incremental update for order:', orderId, 'new status:', data.status)
    } else {
      // Order not found locally - do full refresh to fetch new orders
      console.log('[kitchenStore] Order not in local state, doing full refresh')
      fetchDashboard()
    }
    
    // Always sync statistics
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

    statistics.value = null

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
