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
 * Configure Laravel Echo instance with Pusher broadcaster
 */
const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
  encrypted: true,
  
  // CRITICAL: Explicitly set authEndpoint for private channels
  // Without this, Pusher uses default /broadcasting/auth on current domain
  authEndpoint: `${import.meta.env.VITE_API_BASE_URL}/api/broadcasting/auth`,

  /**
   * Custom authorizer for private channel authentication.
   * Calls Laravel's /api/broadcasting/auth endpoint on the backend.
   */
  authorizer: (channel: any) => {
    return {
      authorize: (socketId: string, callback: Function) => {
        try {
          const token = localStorage.getItem('token')
          const hotelId = localStorage.getItem('hotel_id') || localStorage.getItem('active_hotel_id')
          let qrToken = localStorage.getItem('guest_qr_token')

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

          console.log('[Echo] Authorizing channel:', channel.name)
          console.log('[Echo] Socket ID:', socketId)
          console.log('[Echo] API URL:', import.meta.env.VITE_API_BASE_URL)
          console.log('[Echo] Hotel ID:', hotelId || 'MISSING')
          console.log('[Echo] QR Token:', qrToken ? qrToken.substring(0, 4) + '****' : 'MISSING')

          if (!import.meta.env.VITE_API_BASE_URL) {
            console.error('[Echo] ❌ VITE_API_BASE_URL is not set!')
            throw new Error('API base URL not configured')
          }

          const authUrl = `${import.meta.env.VITE_API_BASE_URL}/api/broadcasting/auth`
          console.log('[Echo] Auth URL:', authUrl)

          const requestBody: any = {
            socket_id: socketId,
            channel_name: channel.name,
          }

          if (qrToken && channel.name.includes('orders.')) {
            requestBody.qr_token = qrToken
            console.log('[Echo] Including QR token in request body for guest order channel')
          }

          fetch(authUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              Authorization: token ? `Bearer ${token}` : '',
              'X-QR-Token': qrToken || '',
            },
            body: JSON.stringify(requestBody),
          })
            .then((response) => {
              console.log('[Echo] Auth response status:', response.status)
              if (!response.ok) {
                console.error(
                  '[Echo] ❌ Authorization failed - HTTP',
                  response.status,
                  response.statusText,
                )
                return response.text().then((text) => {
                  console.error('[Echo] Response body:', text)
                  throw new Error(
                    `Authorization failed: ${response.status} ${response.statusText} - ${text}`,
                  )
                })
              }
              return response.json()
            })
            .then((data) => {
              console.log('[Echo] ✅ Channel authorization successful:', channel.name)
              callback(null, data)
            })
            .catch((error) => {
              console.error('[Echo] ⚠️ Channel authorization error:', error)
              callback(error, null)
            })
        } catch (error) {
          console.error('[Echo] ⚠️ Authorizer error:', error)
          callback(error, null)
        }
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