/**
 * Laravel Echo WebSocket Plugin
 *
 * Configures Laravel Echo to connect to Pusher Channels WebSocket service.
 * Uses pusher-js SDK to communicate with Pusher's managed WebSocket infrastructure.
 *
 * This plugin is imported in main.ts to initialize WebSocket connection on app startup.
 */

import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo<any>
  }
}

window.Pusher = Pusher

/**
 * Helper to get authentication token and headers for Pusher auth
 */
function getPusherAuthHeaders() {
  const token = localStorage.getItem('token')
  let qrToken = localStorage.getItem('guest_qr_token')

  // Try to extract QR token from various storage locations
  if (!qrToken) {
    try {
      const walkInData =
        localStorage.getItem('walk_in_payment_data') ||
        sessionStorage.getItem('walk_in_payment_data')
      if (walkInData) {
        const parsed = JSON.parse(walkInData)
        if (parsed.qr_token) {
          qrToken = parsed.qr_token
          localStorage.setItem('guest_qr_token', qrToken)
        }
      }
    } catch (_) {}
  }

  if (!qrToken) {
    try {
      const orderPaymentData =
        localStorage.getItem('order_payment_data') ||
        sessionStorage.getItem('order_payment_data')
      if (orderPaymentData) {
        const parsed = JSON.parse(orderPaymentData)
        if (parsed.qr_token) {
          qrToken = parsed.qr_token
          localStorage.setItem('guest_qr_token', qrToken)
        }
      }
    } catch (_) {}
  }

  return {
    token,
    qrToken,
  }
}

// Log configuration for debugging
const backendAuthEndpoint = `${import.meta.env.VITE_API_BASE_URL}/api/broadcasting/auth`
console.log('[Echo] Initializing Pusher with:')
console.log('[Echo] API Base URL:', import.meta.env.VITE_API_BASE_URL)
console.log('[Echo] Auth Endpoint:', backendAuthEndpoint)

/**
 * Configure Laravel Echo instance with Pusher broadcaster
 */
const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
  encrypted: true,

  // CRITICAL: Use custom authorizer to FORCE requests to backend
  // This ensures Pusher NEVER falls back to default /broadcasting/auth
  authorizer: (channel: any) => {
    return {
      authorize: (socketId: string, callback: Function) => {
        const { token, qrToken } = getPusherAuthHeaders()

        console.log('[Echo] Authorizing channel:', channel.name)
        console.log('[Echo] Posting to:', backendAuthEndpoint)

        fetch(backendAuthEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': token ? `Bearer ${token}` : '',
            'X-QR-Token': qrToken || '',
          },
          body: JSON.stringify({
            socket_id: socketId,
            channel_name: channel.name,
          }),
        })
          .then((response) => {
            console.log('[Echo] Auth response status:', response.status)
            if (!response.ok) {
              console.error('[Echo] ❌ Auth failed:', response.status, response.statusText)
              throw new Error(`HTTP ${response.status}`)
            }
            return response.json()
          })
          .then((data) => {
            console.log('[Echo] ✅ Channel authorized:', channel.name)
            callback(false, data)
          })
          .catch((error) => {
            console.error('[Echo] ❌ Authorization error:', error)
            callback(error)
          })
      },
    }
  },
})

// Make Echo available globally
window.Echo = echo

// Log connection status in development
if (import.meta.env.DEV) {
  echo.connector.pusher.connection.bind('connected', () => {
    console.log('[Echo] ✅ Connected to Pusher WebSocket server')
  })

  echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('[Echo] ❌ Disconnected from Pusher WebSocket server')
  })

  echo.connector.pusher.connection.bind('error', (err: any) => {
    console.error('[Echo] ⚠️ Connection error:', err)
  })
}

export default echo