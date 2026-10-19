import { defineStore } from 'pinia'
import { publicAxios } from '@/services/axios'
import { useThemeStore } from '@/stores/theme'
import { HOTEL_CONFIG } from '@/constants/hotelConfig'

export interface PublicHotel {
  id: string
  name: string
  slug: string
  logo: string | null
  description?: string | null
  address: string | null
  city?: string | null
  country?: string | null
  phone: string | null
  email: string | null
  reservation_email?: string | null
  reservation_phone?: string | null
  front_desk_hours?: string | null
  dining_hours?: string | null
  currency: string
  status: string
}

const DEFAULT_GUEST_HOTEL: PublicHotel = {
  id: '41b094f8-7ed2-4af0-bd16-6f0533e34544',
  name: HOTEL_CONFIG.name,
  slug: 'executive-hotel',
  logo: null,
  description: HOTEL_CONFIG.tagline,
  address: HOTEL_CONFIG.address,
  city: HOTEL_CONFIG.city,
  country: HOTEL_CONFIG.country,
  phone: HOTEL_CONFIG.phone,
  email: HOTEL_CONFIG.email,
  reservation_email: HOTEL_CONFIG.reservationEmail,
  reservation_phone: HOTEL_CONFIG.reservationPhone,
  front_desk_hours: HOTEL_CONFIG.frontDeskHours,
  dining_hours: HOTEL_CONFIG.diningHours,
  currency: HOTEL_CONFIG.currency,
  status: 'active',
}

function loadInitialHotel(): PublicHotel {
  try {
    const raw = localStorage.getItem('guest_current_hotel') || localStorage.getItem('current_hotel')
    if (raw) {
      const parsed = JSON.parse(raw)
      if (parsed && (parsed.id || parsed.name)) {
        let hotelName = parsed.name || DEFAULT_GUEST_HOTEL.name
        const lower = hotelName.toLowerCase()
        if (lower.includes('sherat') || lower.includes('grand horizon') || lower.includes('metropolitan')) {
          hotelName = DEFAULT_GUEST_HOTEL.name
        }
        return {
          ...DEFAULT_GUEST_HOTEL,
          ...parsed,
          name: hotelName,
          slug: parsed.slug || DEFAULT_GUEST_HOTEL.slug,
          currency: parsed.currency || DEFAULT_GUEST_HOTEL.currency,
        }
      }
    }
  } catch (e) {
    console.error('[guestHotelStore] Failed to parse guest hotel from localStorage:', e)
  }
  return DEFAULT_GUEST_HOTEL
}

export const useGuestHotelStore = defineStore('guestHotel', {
  state: () => ({
    currentHotel: loadInitialHotel() as PublicHotel,
    availableHotels: [DEFAULT_GUEST_HOTEL] as PublicHotel[],
    isLoading: false,
    error: null as string | null,
    isSelectorOpen: false,
  }),

  getters: {
    hotelId: (state): string => state.currentHotel?.id || DEFAULT_GUEST_HOTEL.id,
    hotelName: (state): string => state.currentHotel?.name || DEFAULT_GUEST_HOTEL.name,
    hotelCity: (state): string => state.currentHotel?.city || HOTEL_CONFIG.city,
    hotelCountry: (state): string => state.currentHotel?.country || HOTEL_CONFIG.country,
    hotelAddress: (state): string => {
      if (state.currentHotel?.address) return state.currentHotel.address
      const city = state.currentHotel?.city || HOTEL_CONFIG.city
      const country = state.currentHotel?.country || HOTEL_CONFIG.country
      return `${city}, ${country}`
    },
    hotelPhone: (state): string => state.currentHotel?.phone || HOTEL_CONFIG.phone,
    reservationPhone: (state): string =>
      state.currentHotel?.reservation_phone || state.currentHotel?.phone || HOTEL_CONFIG.reservationPhone,
    hotelEmail: (state): string => state.currentHotel?.email || HOTEL_CONFIG.email,
    reservationEmail: (state): string =>
      state.currentHotel?.reservation_email || state.currentHotel?.email || HOTEL_CONFIG.reservationEmail,
    frontDeskHours: (state): string =>
      state.currentHotel?.front_desk_hours || HOTEL_CONFIG.frontDeskHours,
    diningHours: (state): string => state.currentHotel?.dining_hours || HOTEL_CONFIG.diningHours,
    currency: (state): string => state.currentHotel?.currency || HOTEL_CONFIG.currency,
    hasSelectedHotel: (): boolean => true,
  },

  actions: {
    async fetchAvailableHotels() {
      this.isLoading = true
      this.error = null
      try {
        const response = await publicAxios.get('/guest/hotels')
        if (response.data?.success && Array.isArray(response.data?.data) && response.data.data.length > 0) {
          const hotel = response.data.data[0]
          this.currentHotel = { ...DEFAULT_GUEST_HOTEL, ...hotel }
          this.availableHotels = [this.currentHotel]
          try {
            const themeStore = useThemeStore()
            themeStore.syncWithHotel(this.currentHotel)
          } catch {
            // Theme sync optional
          }
        }
      } catch (error: any) {
        console.warn('[guestHotelStore] Using default hotel info:', error)
      } finally {
        this.isLoading = false
      }
    },

    openHotelSelector() {},
    closeHotelSelector() {},

    async selectHotel(hotel: PublicHotel) {
      this.currentHotel = { ...DEFAULT_GUEST_HOTEL, ...hotel }
      this.availableHotels = [this.currentHotel]
      try {
        const themeStore = useThemeStore()
        themeStore.syncWithHotel(this.currentHotel)
      } catch {
        // Theme sync optional
      }
    },

    async selectHotelById(_hotelId: string) {
      await this.fetchAvailableHotels()
    },

    clearSelectedHotel() {},
  },
})
