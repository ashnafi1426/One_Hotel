/**
 * Shared Hotel Configuration and Brand Constants
 * Fallback values - actual data loaded from API
 */

export const HOTEL_CONFIG = {
  name: 'Sunny Side Hotel',
  fullName: 'Sunny Side Hotel – Kombolcha',
  city: 'Kombolcha',
  country: 'Ethiopia',
  tagline: 'Luxury Hotel & Restaurant Experience',
  badge: 'SUNNY SIDE HOTEL • KOMBOLCHA',
  locationLabel: 'KOMBOLCHA • LUXURY HOTEL',
  phone: '+2519234322',
  reservationPhone: '+2519234322',
  email: 'sunnyside@gmail.com',
  reservationEmail: 'sunnyside@gmail.com',
  address: 'Kombolcha, Ethiopia',
  frontDeskHours: 'Open 24/7 / 365 Days',
  diningHours: '06:00 AM – 11:00 PM Daily',
  currency: 'ETB',
} as const

export type HotelConfig = typeof HOTEL_CONFIG
