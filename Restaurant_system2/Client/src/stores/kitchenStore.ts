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
    const pending = orders.value.filter((o) => o.status === 'pending').length
    const preparing = orders.value.filter((o) => o.status === 'preparing').length
    const ready = orders.value.filter((o) => o.status === 'ready').length
    const served = orders.value.filter((o) => o.status === 'served').length
    const cancelled = orders.value.filter((o) => o.status === 'cancelled').length
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
    targetStatus: 'pending' | 'preparing' | 'ready' | 'served' | null,
    action: () => Promise<KitchenOrder>,
    fallbackMsg: string,
  ): Promise<KitchenOrder> {
    actionLoading.value = orderId
    error.value = null

    // Optimistic UI update: instantly update status so cards move in 0ms
    const existingIndex = orders.value.findIndex((o) => String(o.id) === String(orderId))
    const previousStatus = existingIndex !== -1 ? orders.value[existingIndex].status : null
    if (existingIndex !== -1 && targetStatus) {
      orders.value[existingIndex] = {
        ...orders.value[existingIndex],
        status: targetStatus,
      }
    }

    try {
      const updatedOrder = await action()
      updateOrder(updatedOrder)
      return updatedOrder
    } catch (err: any) {
      console.error(`[kitchenStore] ${fallbackMsg}:`, err)
      // Revert optimistic update on failure
      if (existingIndex !== -1 && previousStatus) {
        orders.value[existingIndex] = {
          ...orders.value[existingIndex],
          status: previousStatus,
        }
      }
      error.value = err?.response?.data?.message ?? err.message ?? fallbackMsg
      throw err
    } finally {
      actionLoading.value = null
    }
  }

  function startPreparing(orderId: string) {
    return executeStatusChange(
      orderId,
      'preparing',
      () => kitchenService.startPreparing(orderId),
      'Failed to start preparing order',
    )
  }

  function markReady(orderId: string) {
    return executeStatusChange(
      orderId,
      'ready',
      () => kitchenService.markReady(orderId),
      'Failed to mark order ready',
    )
  }

  function markServed(orderId: string) {
    return executeStatusChange(
      orderId,
      'served',
      () => kitchenService.markServed(orderId),
      'Failed to complete order',
    )
  }

  function syncStatistics() {
    kitchenService
      .getStatistics()
      .then((stats) => {
        if (stats) statisticsFromApi.value = stats
      })
      .catch(() => {})
  }

  function updateOrder(updatedOrder: KitchenOrder) {
    const index = orders.value.findIndex((order) => order.id === updatedOrder.id)

    if (index !== -1) {
      orders.value.splice(index, 1, updatedOrder)
    } else {
      orders.value = [updatedOrder, ...orders.value]
    }
  }

  function receiveOrderUpdate(data: any) {
    console.log('[kitchenStore] ? Received order update via WebSocket:', data)

    const orderId = data.id || data.order_id
    if (!orderId) {
      console.warn('[kitchenStore] ? No order ID in WebSocket data, doing full refresh')
      fetchDashboard()
      return
    }

    console.log('[kitchenStore] ?? Processing update for order:', orderId)

    if (data.order && typeof data.order === 'object') {
      const fullOrder = data.order as KitchenOrder

      console.log('[kitchenStore] ?? Order data:', {
        id: fullOrder.id,
        order_number: fullOrder.order_number,
        status: fullOrder.status,
        room: fullOrder.room?.room_number,
        table: fullOrder.table?.table_number,
        guest: fullOrder.guest?.full_name,
        items: fullOrder.items?.length || 0,
      })

      if (!fullOrder.room && !fullOrder.table) {
        console.warn('[kitchenStore] ?? Order missing location:', orderId)
      }
      if (!fullOrder.items || fullOrder.items.length === 0) {
        console.warn('[kitchenStore] ?? Order missing items:', orderId)
      }

      const existingIndex = orders.value.findIndex((o) => String(o.id) === String(orderId))

      if (existingIndex !== -1) {
        orders.value.splice(existingIndex, 1, fullOrder)
        console.log('[kitchenStore] ? Order updated:', orderId, '?', fullOrder.status)
      } else {
        orders.value = [fullOrder, ...orders.value]
        console.log('[kitchenStore] ? New order added:', orderId, '?', fullOrder.status)
      }
    } else {
      console.log('[kitchenStore] ?? Partial data received, doing full refresh')
      fetchDashboard()
      return
    }

    console.log('[kitchenStore] ?? Statistics will auto-compute')
  }

  function subscribeToUpdates() {
    console.log('[kitchenStore] Subscribing to WebSocket updates')

    if (unsubscribe) {
      unsubscribe()
    }

    unsubscribe = echoService.subscribeToKitchenOrderUpdates(
      (data: any) => {
        receiveOrderUpdate(data)
      },
      (error: any) => {
        console.error('[kitchenStore] WebSocket error:', error)
      },
    )
  }

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
