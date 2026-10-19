import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Echo?: Echo
    Pusher?: typeof Pusher
  }
}

let echoInstance: Echo | null = null

/**
 * Initialize Laravel Echo with Pusher WebSocket connection
 */
export function initializeEcho(): Echo {
  if (echoInstance) {
    return echoInstance
  }

  const pusherAppKey = import.meta.env.VITE_PUSHER_APP_KEY || '5df8dcb92d20f713b1f2'
  const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap2'

  window.Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: pusherAppKey,
    cluster: pusherCluster,
    enabledTransports: ['ws', 'wss'],
    fallbackTransport: 'polling',
  })

  console.log('[Echo] Initialized with Pusher connection (cluster: ' + pusherCluster + ')')

  echoInstance.connector.pusher.connection.bind('connected', () => {
    console.log('[Echo] ✅ WebSocket connected to Pusher')
  })

  echoInstance.connector.pusher.connection.bind('error', (error: any) => {
    console.error('[Echo] ⚠️ WebSocket error:', error)
  })

  echoInstance.connector.pusher.connection.bind('disconnected', () => {
    console.warn('[Echo] ❌ WebSocket disconnected from Pusher')
  })

  return echoInstance
}

/**
 * Get the Echo instance (initialize if not already done)
 */
export function getEcho(): Echo {
  return echoInstance || initializeEcho()
}

/**
 * Subscribe to order status updates for a specific order
 * Listener will be called whenever OrderStatusChanged event is broadcast
 */
export function subscribeToOrderUpdates(
  orderId: string,
  onUpdate: (data: any) => void,
  onError?: (error: any) => void
): void {
  try {
    const echo = getEcho()

    // Subscribe to the private channel for this specific order
    echo
      .private(`orders.update.${orderId}`)
      .listen('order.status.changed', (data: any) => {
        console.log(`[Echo] Order #${orderId} status changed:`, data)
        onUpdate(data)
      })
      .error((error: any) => {
        console.error(`[Echo] Error on orders.update.${orderId}:`, error)
        onError?.(error)
      })

    console.log(`[Echo] Subscribed to order #${orderId} updates`)
  } catch (error: any) {
    console.error('[Echo] Failed to subscribe to order updates:', error)
    onError?.(error)
  }
}

/**
 * Subscribe to all manager order updates
 * Fired when any order status changes or new orders arrive
 */
export function subscribeToManagerOrderUpdates(onUpdate: (data: any) => void, onError?: (error: any) => void): (() => void) {
  try {
    const echo = getEcho()
    const channel = echo.channel('manager.orders.update')

    const events = [
      'OrderStatusUpdated',
      '.OrderStatusUpdated',
      'order.ready',
      '.order.ready',
      'order.status.changed',
      '.order.status.changed',
      'order.created',
      '.order.created',
      'waiter.assigned',
      '.waiter.assigned',
      'OrderReadyEvent',
      '.OrderReadyEvent',
      'WaiterOrderReadyEvent',
      '.WaiterOrderReadyEvent',
    ]

    events.forEach((evt) => {
      channel.listen(evt, (data: any) => {
        console.log(`[Echo] Manager update [${evt}]:`, data)
        onUpdate(data)
      })
    })

    channel.error((error: any) => {
      console.error('[Echo] Error on manager.orders.update:', error)
      onError?.(error)
    })

    console.log('[Echo] Subscribed to manager order updates')

    return () => {
      echo.leave('manager.orders.update')
      console.log('[Echo] Unsubscribed from manager order updates')
    }
  } catch (error: any) {
    console.error('[Echo] Failed to subscribe to manager updates:', error)
    onError?.(error)
    return () => {}
  }
}

/**
 * Subscribe to all kitchen order updates
 * Fired when any order status changes in the kitchen
 */
export function subscribeToKitchenOrderUpdates(onUpdate: (data: any) => void, onError?: (error: any) => void): (() => void) {
  try {
    const echo = getEcho()
    const channelNames = ['kitchen.orders.update', 'manager.orders.update']

    const events = [
      'OrderStatusUpdated',
      '.OrderStatusUpdated',
      'order.status.changed',
      '.order.status.changed',
      'OrderStatusChanged',
      '.OrderStatusChanged',
      'order.created',
      '.order.created',
      'OrderCreated',
      '.OrderCreated',
      'order.ready',
      '.order.ready',
    ]

    // In-memory deduplicator to prevent duplicate callbacks within 350ms
    const recentTriggers = new Map<string, number>()

    const handleEvent = (evt: string, ch: string, data: any) => {
      const id = data?.id || data?.order_id || data?.order?.id
      const status = data?.status || data?.order?.status || 'created'
      const key = `${id}_${status}`
      const now = Date.now()

      if (id && recentTriggers.has(key)) {
        const last = recentTriggers.get(key)!
        if (now - last < 350) {
          return // duplicate from another channel
        }
      }

      if (id) {
        recentTriggers.set(key, now)
        if (recentTriggers.size > 200) {
          recentTriggers.clear()
        }
      }

      console.log(`[Echo] Kitchen update [${ch} -> ${evt}]:`, data)
      onUpdate(data)
    }

    channelNames.forEach((chName) => {
      const ch = echo.channel(chName)
      events.forEach((evt) => {
        ch.listen(evt, (data: any) => handleEvent(evt, chName, data))
      })
      ch.error((err: any) => {
        console.error(`[Echo] Error on ${chName}:`, err)
        onError?.(err)
      })
    })

    console.log('[Echo] Subscribed to kitchen order updates on channels:', channelNames.join(', '))

    return () => {
      channelNames.forEach((chName) => {
        echo.leave(chName)
      })
      console.log('[Echo] Unsubscribed from kitchen order updates')
    }
  } catch (error: any) {
    console.error('[Echo] Failed to subscribe to kitchen updates:', error)
    onError?.(error)
    return () => {}
  }
}

/**
 * Subscribe to all waiter order updates via WebSocket
 * Connects to waiter, kitchen, and manager channels so ANY kitchen status update (ready, preparing, served)
 * triggers real-time UI updates without any polling.
 */
export function subscribeToWaiterOrderUpdates(onUpdate: (data: any) => void, onError?: (error: any) => void): (() => void) {
  try {
    const echo = getEcho()
    const channelNames = ['waiter.orders.update', 'kitchen.orders.update', 'manager.orders.update']

    const events = [
      'OrderStatusUpdated',
      '.OrderStatusUpdated',
      'order.ready',
      '.order.ready',
      'order.status.changed',
      '.order.status.changed',
      'order.created',
      '.order.created',
      'waiter.assigned',
      '.waiter.assigned',
      'OrderReadyEvent',
      '.OrderReadyEvent',
      'WaiterOrderReadyEvent',
      '.WaiterOrderReadyEvent',
    ]

    // In-memory deduplicator to prevent duplicate callbacks within 350ms
    const recentTriggers = new Map<string, number>()

    const handleEvent = (evt: string, ch: string, data: any) => {
      const id = data?.id || data?.order_id || data?.order?.id
      const status = data?.status || data?.order?.status
      const key = `${id}_${status}`
      const now = Date.now()

      if (id && status && recentTriggers.has(key)) {
        const last = recentTriggers.get(key)!
        if (now - last < 350) {
          return // duplicate from another channel
        }
      }

      if (id && status) {
        recentTriggers.set(key, now)
        if (recentTriggers.size > 200) {
          recentTriggers.clear()
        }
      }

      console.log(`[Echo] ⚡ Waiter WebSocket update [${ch} -> ${evt}]:`, data)
      onUpdate(data)
    }

    channelNames.forEach((chName) => {
      const ch = echo.channel(chName)
      events.forEach((evt) => {
        ch.listen(evt, (data: any) => handleEvent(evt, chName, data))
      })
      ch.error((err: any) => {
        console.error(`[Echo] WebSocket error on ${chName}:`, err)
        onError?.(err)
      })
    })

    console.log('[Echo] ✅ Waiter subscribed to real-time WebSocket channels:', channelNames.join(', '))

    return () => {
      channelNames.forEach((chName) => {
        echo.leave(chName)
      })
      console.log('[Echo] Unsubscribed from real-time waiter channels')
    }
  } catch (error: any) {
    console.error('[Echo] Failed to subscribe to waiter updates:', error)
    onError?.(error)
    return () => {}
  }
}

/**
 * Unsubscribe from a specific order channel
 */
export function unsubscribeFromOrder(orderId: string): void {
  try {
    const echo = getEcho()
    echo.leave(`orders.update.${orderId}`)
    console.log(`[Echo] Unsubscribed from order #${orderId}`)
  } catch (error: any) {
    console.error('[Echo] Failed to unsubscribe from order:', error)
  }
}

/**
 * Disconnect Echo and clean up
 */
export function disconnectEcho(): void {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
    console.log('[Echo] Disconnected')
  }
}

/**
 * Default export for convenience
 */
export default {
  initializeEcho,
  getEcho,
  subscribeToOrderUpdates,
  subscribeToManagerOrderUpdates,
  subscribeToKitchenOrderUpdates,
  subscribeToWaiterOrderUpdates,
  unsubscribeFromOrder,
  disconnectEcho,
  initialize: initializeEcho, // Alias for consistency with store
}
