import axios from '@/services/axios'

export interface HotelSettings {
  id?: number
  name: string
  email: string
  reservation_email: string
  phone: string
  reservation_phone: string
  address: string
  city: string
  country: string
  front_desk_hours: string
  dining_hours: string
  currency: string
  timezone: string
}

export const hotelSettingsService = {
  async getHotelSettings(): Promise<HotelSettings> {
    const response = await axios.get('/admin/profile/hotel-settings')
    return response.data.data ?? response.data
  },

  async updateHotelSettings(data: Partial<HotelSettings>): Promise<HotelSettings> {
    const response = await axios.put('/admin/profile/hotel-settings', data)
    return response.data.data ?? response.data
  },
}
