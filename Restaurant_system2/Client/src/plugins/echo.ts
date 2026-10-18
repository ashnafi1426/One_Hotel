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
console.log('[Echo] Initializing Pusher with:')
console.log('[Echo] API Base URL:', import.meta.env.VITE_API_BASE_URL)
console.log('[Echo] Auth Endpoint:', `${import.meta.env.VITE_API_BASE_URL}/api/broadcasting/auth`)

/**
 * Configure Laravel Echo instance with Pusher broadcaster
 */
const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
  encrypted: true,

  // CRITICAL: authEndpoint MUST point to backend, not frontend
  authEndpoint: `${import.meta.env.VITE_API_BASE_URL}/api/broadcasting/auth`,

  // Use auth object with headers instead of custom authorizer
  // This ensures Pusher uses the authEndpoint without falling back to default
  auth: {
    headers: {
      get Authorization() {
        const { token } = getPusherAuthHeaders()
        return token ? `Bearer ${token}` : ''
      },
      get 'X-QR-Token'() {
        const { qrToken } = getPusherAuthHeaders()
        return qrToken || ''
      },
    },
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