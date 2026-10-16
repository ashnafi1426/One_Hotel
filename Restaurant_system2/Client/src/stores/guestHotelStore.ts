import { defineStore } from 'pinia'
import { publicAxios } from '@/services/axios'
import { useThemeStore } from '@/stores/theme'

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
  name: 'Sheraten Hotel',
  slug: 'sheraten',
  logo: null,
  description: 'Luxury Hotel and Restaurant Experience',
  address: 'Bole, Addis Ababa',
  city: 'Addis Ababa',
  country: 'Ethiopia',
  phone: '+251 11 555 1234',
  email: 'info@sheraten.com',
  reservation_email: 'reservations@sheraten.com',
  reservation_phone: '+251 91 100 2200',
  front_desk_hours: 'Open 24/7 / 365 Days',
  dining_hours: '06:00 AM – 11:00 PM Daily',
  currency: 'ETB',
  status: 'active',
}

function loadInitialHotel(): PublicHotel {
  try {
    const raw = localStorage.getItem('guest_current_hotel') || localStorage.getItem('current_hotel')
    if (raw) {
      const parsed = JSON.parse(raw)
      if (parsed && (parsed.id || parsed.name)) {
        return {
          ...DEFAULT_GUEST_HOTEL,
          ...parsed,
          name: parsed.name || DEFAULT_GUEST_HOTEL.name,
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
    hotelCity: (state): string => state.currentHotel?.city || 'Addis Ababa',
    hotelCountry: (state): string => state.currentHotel?.country || 'Ethiopia',
    hotelAddress: (state): string => {
      if (state.currentHotel?.address) return state.currentHotel.address
      const city = state.currentHotel?.city || 'Addis Ababa'
      const country = state.currentHotel?.country || 'Ethiopia'
      return `${city}, ${country}`
    },
    hotelPhone: (state): string => state.currentHotel?.phone || '+251 11 555 1234',
    reservationPhone: (state): string =>
      state.currentHotel?.reservation_phone || state.currentHotel?.phone || '+251 91 100 2200',
    hotelEmail: (state): string => state.currentHotel?.email || 'info@sheraten.com',
    reservationEmail: (state): string =>
      state.currentHotel?.reservation_email || state.currentHotel?.email || 'reservations@sheraten.com',
    frontDeskHours: (state): string =>
      state.currentHotel?.front_desk_hours || 'Open 24/7 / 365 Days',
    diningHours: (state): string => state.currentHotel?.dining_hours || '06:00 AM – 11:00 PM Daily',
    currency: (state): string => state.currentHotel?.currency || 'ETB',
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
