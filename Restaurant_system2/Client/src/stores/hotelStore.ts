import { defineStore } from 'pinia'
import { axiosInstance } from '@/services/axios'

export interface HotelItem {
  id: string
  name: string
  slug: string
  logo?: string
  role?: string
  currency?: string
  is_viewing_as_platform_admin?: boolean
}

const DEFAULT_HOTEL: HotelItem = {
  id: '41b094f8-7ed2-4af0-bd16-6f0533e34544',
  name: 'Sheraten Hotel',
  slug: 'sheraten',
  currency: 'ETB',
  role: 'admin',
}

const loadInitialHotelItem = (): HotelItem => {
  for (const key of ['current_hotel', 'guest_current_hotel']) {
    try {
      const raw = localStorage.getItem(key)
      if (raw) {
        const parsed = JSON.parse(raw)
        if (parsed && (parsed.id || parsed.name)) {
          return {
            id: String(parsed.id || DEFAULT_HOTEL.id),
            name: parsed.name || DEFAULT_HOTEL.name,
            slug: parsed.slug || DEFAULT_HOTEL.slug,
            logo: parsed.logo || undefined,
            role: parsed.role || 'admin',
            currency: parsed.currency || 'ETB',
          }
        }
      }
    } catch (e) {
      console.error(`[hotelStore] Error reading initial hotel from ${key}:`, e)
    }
  }
  return DEFAULT_HOTEL
}

export const useHotelStore = defineStore('hotel', {
  state: () => ({
    currentHotel: loadInitialHotelItem() as HotelItem | null,
    availableHotels: [DEFAULT_HOTEL] as HotelItem[],
    isLoading: false,
  }),

  getters: {
    hotelId: (state): string => state.currentHotel?.id || DEFAULT_HOTEL.id,
    hotelName: (state): string => state.currentHotel?.name || DEFAULT_HOTEL.name,
    currency: (state): string => state.currentHotel?.currency || 'ETB',
    hasMultipleHotels: (): boolean => false,
    isViewingAsPlatformAdmin: (): boolean => false,
  },

  actions: {
    enterPlatformViewMode() {},
    exitPlatformViewMode() {},

    setHotels(hotels: HotelItem[], current: HotelItem | null) {
      if (current) {
        this.currentHotel = current
      } else if (hotels && hotels.length > 0) {
        this.currentHotel = hotels[0]
      }
      this.availableHotels = this.currentHotel ? [this.currentHotel] : [DEFAULT_HOTEL]
    },

    async switchHotel(_hotelId: string): Promise<boolean> {
      return true
    },

    async fetchMyHotels() {
      try {
        const response = await axiosInstance.get('/guest/hotels')
        if (response.data?.success && Array.isArray(response.data?.data) && response.data.data.length > 0) {
          const hotelData = response.data.data[0]
          this.currentHotel = {
            id: String(hotelData.id),
            name: hotelData.name,
            slug: hotelData.slug,
            logo: hotelData.logo || undefined,
            currency: hotelData.currency || 'ETB',
          }
          this.availableHotels = [this.currentHotel]
        }
      } catch (err) {
        console.warn('[hotelStore] fetchMyHotels fallback to default hotel:', err)
      }
    },

    clearHotel() {},

    async loadHotels() {
      return this.fetchMyHotels()
    },
  },
})
