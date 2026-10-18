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
 * Initialize Laravel Echo with Reverb WebSocket connection
 */
export function initializeEcho(): Echo {
  if (echoInstance) {
    return echoInstance
  }

  const reverebHost = import.meta.env.VITE_REVERB_HOST || 'localhost'
  const reverbePort = import.meta.env.VITE_REVERB_PORT || 443
  const reverbeScheme = import.meta.env.VITE_REVERB_SCHEME || 'https'
  const reverbeAppKey = import.meta.env.VITE_REVERB_APP_KEY || 'm3k5j8h9n2p4q7r1t6w0'

  window.Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: reverbeAppKey,
    wsHost: reverebHost,
    wsPort: reverbePort,
    wssPort: reverbePort,
    forceTLS: reverbeScheme === 'https',
    enabledTransports: ['ws', 'wss'],
  })

  console.log('[Echo] Initialized with Reverb connection')

  echoInstance.connector.socket.on('connected', () => {
    console.log('[Echo] WebSocket connected')
  })

  echoInstance.connector.socket.on('error', (error: any) => {
    console.error('[Echo] WebSocket error:', error)
  })

  echoInstance.connector.socket.on('disconnected', () => {
    console.warn('[Echo] WebSocket disconnected')
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

    const channel = echo
      .channel('manager.orders.update')
      .listen('order.status.changed', (data: any) => {
        console.log('[Echo] Manager order status update:', data)
        onUpdate(data)
      })
      .listen('order.created', (data: any) => {
        console.log('[Echo] New order for manager:', data)
        onUpdate(data)
      })
      .error((error: any) => {
        console.error('[Echo] Error on manager.orders.update:', error)
        onError?.(error)
      })

    console.log('[Echo] Subscribed to manager order updates')

    // Return unsubscribe function
    return () => {
      echo.leave('manager.orders.update')
      console.log('[Echo] Unsubscribed from manager order updates')
    }
  } catch (error: any) {
    console.error('[Echo] Failed to subscribe to manager updates:', error)
    onError?.(error)
    return () => {} // Return no-op function if subscription fails
  }
}

/**
 * Subscribe to all kitchen order updates
 * Fired when any order status changes in the kitchen
 */
export function subscribeToKitchenOrderUpdates(onUpdate: (data: any) => void, onError?: (error: any) => void): void {
  try {
    const echo = getEcho()

    echo
      .channel('kitchen.orders.update')
      .listen('order.status.changed', (data: any) => {
        console.log('[Echo] Kitchen order status update:', data)
        onUpdate(data)
      })
      .listen('order.created', (data: any) => {
        console.log('[Echo] New order in kitchen:', data)
        onUpdate(data)
      })
      .error((error: any) => {
        console.error('[Echo] Error on kitchen.orders.update:', error)
        onError?.(error)
      })

    console.log('[Echo] Subscribed to kitchen order updates')
  } catch (error: any) {
    console.error('[Echo] Failed to subscribe to kitchen updates:', error)
    onError?.(error)
  }
}

/**
 * Subscribe to all waiter order updates
 * Fired when any order is assigned or status changes
 */
export function subscribeToWaiterOrderUpdates(onUpdate: (data: any) => void, onError?: (error: any) => void): void {
  try {
    const echo = getEcho()

    echo
      .channel('waiter.orders.update')
      .listen('order.status.changed', (data: any) => {
        console.log('[Echo] Waiter order update:', data)
        onUpdate(data)
      })
      .error((error: any) => {
        console.error('[Echo] Error on waiter.orders.update:', error)
        onError?.(error)
      })

    console.log('[Echo] Subscribed to waiter order updates')
  } catch (error: any) {
    console.error('[Echo] Failed to subscribe to waiter updates:', error)
    onError?.(error)
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
