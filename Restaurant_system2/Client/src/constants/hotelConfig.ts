/**
 * Shared Hotel Configuration and Brand Constants
 * Single source of truth for Executive Hotel – Addis Ababa
 */

export const HOTEL_CONFIG = {
  name: 'Executive Hotel',
  fullName: 'Executive Hotel – Addis Ababa',
  city: 'Addis Ababa',
  country: 'Ethiopia',
  tagline: 'Luxury Hotel & Restaurant Experience',
  badge: 'EXECUTIVE HOTEL • ADDIS ABABA',
  locationLabel: 'ADDIS ABABA • LUXURY HOTEL',
  phone: '+251 11 555 1234',
  reservationPhone: '+251 91 100 2200',
  email: 'info@executivehoteladdis.com',
  reservationEmail: 'reservations@executivehoteladdis.com',
  address: 'Bole Road, Addis Ababa, Ethiopia',
  frontDeskHours: 'Open 24/7 / 365 Days',
  diningHours: '06:00 AM – 11:00 PM Daily',
  currency: 'ETB',
} as const

export type HotelConfig = typeof HOTEL_CONFIG
