import { publicAxios as axios } from './axios'

export interface QRResolutionResult {
  success: boolean
  context: 'room' | 'table' | null
  data: {
    hotel_id?: string
    hotel_name?: string
    room_id?: string
    room_number?: string
    floor?: string
    floor_id?: string
    room_type?: string
    guest?: {
      guest_id: string
      guest_name: string
      guest_email?: string
      guest_phone?: string
      reservation_id?: string
      check_in_date?: string
      expected_checkout?: string
    } | null
    table_id?: string
    table_number?: string
    table_name?: string | null
    capacity?: number
    location?: string | null
    status?: string
    is_checked_in?: boolean
    can_order?: boolean
    reservation_status?: string
    eligibility_message?: string
  } | null
  message: string
}

export interface QRValidationResult {
  valid: boolean
  context: 'room' | 'table' | null
}

const resolutionCache = new Map<string, { data: QRResolutionResult; time: number }>()
const CACHE_TTL_MS = 60000 // 60 seconds

export const qrService = {
  async resolveQRToken(token: string, force = false): Promise<QRResolutionResult> {
    if (!force && token) {
      const cached = resolutionCache.get(token)
      if (cached && Date.now() - cached.time < CACHE_TTL_MS) {
        return cached.data
      }
      try {
        const stored = sessionStorage.getItem(`qr_res_${token}`)
        if (stored) {
          const parsed = JSON.parse(stored)
          if (parsed && Date.now() - parsed.time < CACHE_TTL_MS) {
            resolutionCache.set(token, { data: parsed.data, time: parsed.time })
            return parsed.data
          }
        }
      } catch (e) {
        // ignore
      }
    }

    try {
      const response = await axios.get(`/qr/resolve/${token}`)
      if (response.data && response.data.success) {
        resolutionCache.set(token, { data: response.data, time: Date.now() })
        try {
          sessionStorage.setItem(
            `qr_res_${token}`,
            JSON.stringify({ data: response.data, time: Date.now() }),
          )
        } catch (e) {
          // ignore
        }
      }
      return response.data
    } catch (error: any) {
      console.error('[QRService] Error resolving QR token:', error)
      return {
        success: false,
        context: null,
        data: null,
        message: error.response?.data?.message || 'Failed to resolve QR code',
      }
    }
  },

  async validateQRToken(token: string): Promise<QRValidationResult> {
    try {
      const response = await axios.post('/qr/validate', { qr_token: token })
      return response.data
    } catch (error: any) {
      console.error('[QRService] Error validating QR token:', error)
      return {
        valid: false,
        context: null,
      }
    }
  },
}

export default qrService
