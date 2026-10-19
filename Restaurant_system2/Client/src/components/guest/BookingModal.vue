<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useGuestHotelStore } from '../../stores/guestHotelStore'
import { useLanguageStore } from '../../stores/language'
import { roomService } from '../../services/roomService'
import paymentService from '../../services/paymentService'
import { publicAxios } from '../../services/axios'
import {
  X,
  Calendar,
  Users,
  User,
  Mail,
  Phone,
  Coffee,
  Star,
  CheckCircle,
  MapPin,
  Sparkles,
  Utensils,
  Loader,
} from 'lucide-vue-next'

interface RoomType {
  id?: string | number
  name: string
  description?: string
  base_price_per_night: number
  capacity: number
  amenities?: string[]
  amenities_count?: number
}

interface Room {
  id: string
  hotel_id?: string | number
  hotel?: {
    id: string
    name: string
    city?: string
  }
  room_number?: string
  room_type_id?: string | number
  room_type?: RoomType | string
  description?: string
  floor?: number
  status?: string
  is_active?: boolean
  created_at?: string
  updated_at?: string
}

interface BookingSessionData {
  guest_id: string
  room_id: string
  check_in_date: string
  check_out_date: string
  number_of_guests: number
  special_requests?: string
  first_name: string
  last_name: string
  email: string
  phone: string
  payment_id: string
  tx_ref: string
  price_breakdown?: {
    price_per_night: number
    number_of_nights: number
    room_subtotal: number
    services?: any
    services_total: number
    subtotal: number
    tax: number
    total: number
  }
}

const props = defineProps<{
  isOpen: boolean
  room: Room | null
}>()

const emit = defineEmits<{
  close: []
  submit: [data: any]
}>()

const router = useRouter()
const guestHotelStore = useGuestHotelStore()
const languageStore = useLanguageStore()

const bookingForm = ref({
  checkInDate: '',
  checkOutDate: '',
  guests: 1,
  guestName: '',
  guestEmail: '',
  guestPhone: '',
  roomPreference: '',
  specialRequests: '',
  includeBreakfast: false,
  includeDinner: false,
  includeSpa: false,
})

const isBooking = ref(false)
const isVisible = ref(false)
const modalScale = ref(0.95)

const showPaymentDialog = ref(false)
const paymentLoading = ref(false)

const roomStatus = ref<'available' | 'booked' | 'maintenance' | 'loading'>('loading')
const roomStatusMessage = ref('Checking Status...')
const isFetchingStatus = ref(false)
const statusFetchError = ref<string | null>(null)

const getRoomTypeName = (room: Room | null): string => {
  if (!room) return 'Luxury Suite'
  if (typeof room.room_type === 'string') {
    return room.room_type
  }
  return room.room_type?.name || 'Luxury Suite'
}

const getRoomPrice = (room: Room | null): number => {
  if (!room) return 0
  if (typeof room.room_type === 'object' && room.room_type?.base_price_per_night) {
    return Math.round(room.room_type.base_price_per_night)
  }
  return 0
}

const getRoomCapacity = (room: Room | null): number => {
  if (!room) return 2
  if (typeof room.room_type === 'object' && room.room_type?.capacity) {
    return room.room_type.capacity
  }
  return 2
}

const getRoomDescription = (room: Room | null): string => {
  if (!room) return 'Experience luxury and comfort in our premium accommodation.'
  if (typeof room.room_type === 'object' && room.room_type?.description) {
    return room.room_type.description
  }
  return room.description || 'Experience luxury and comfort in our premium accommodation.'
}

const getRoomImage = (room: Room | null): string => {
  if (!room) return '/images/rooms/deluxe.jpg'
  const roomType = getRoomTypeName(room).toLowerCase()
  const imageMap: Record<string, string> = {
    deluxe: '/images/rooms/deluxe.jpg',
    vip: '/images/rooms/suite.jpg',
    wvip: '/images/rooms/suite.jpg',
    suite: '/images/rooms/suite.jpg',
    'twin small': '/images/rooms/family.jpg',
    'twin big': '/images/rooms/family.jpg',
    standard: '/images/rooms/deluxe.jpg',
    presidential: '/images/rooms/presidential.jpg',
    executive: '/images/rooms/executive.jpg',
  }

  for (const [key, value] of Object.entries(imageMap)) {
    if (roomType.includes(key)) {
      return value
    }
  }

  return '/images/rooms/deluxe.jpg'
}

const getRoomAmenities = (room: Room | null): string[] => {
  if (!room) return ['Free Wi-Fi', 'Air Conditioning', 'Flat-screen TV', 'Mini Bar']
  if (typeof room.room_type === 'object' && room.room_type?.amenities) {
    return room.room_type.amenities
  }
  return ['Free Wi-Fi', 'Air Conditioning', 'Flat-screen TV', 'Mini Bar']
}

const getRoomRating = (room: Room | null): number => {
  if (!room) return 4.8
  const ratings: Record<string, number> = {
    deluxe: 4.8,
    vip: 4.9,
    wvip: 4.9,
    suite: 4.9,
    'twin small': 4.6,
    'twin big': 4.7,
    standard: 4.5,
    presidential: 5.0,
    executive: 4.8,
  }
  const roomType = getRoomTypeName(room).toLowerCase()
  for (const [key, value] of Object.entries(ratings)) {
    if (roomType.includes(key)) {
      return value
    }
  }
  return 4.7
}

const today = new Date().toISOString().split('T')[0]

const isValidDateRange = computed(() => {
  if (!bookingForm.value.checkInDate || !bookingForm.value.checkOutDate) return true
  return bookingForm.value.checkOutDate > bookingForm.value.checkInDate
})

const isPastDate = computed(() => {
  if (!bookingForm.value.checkInDate) return false
  return bookingForm.value.checkInDate < today
})

const isCapacityExceeded = computed(() => {
  if (!props.room) return false
  return Number(bookingForm.value.guests) > getRoomCapacity(props.room)
})

async function fetchRoomStatusFromBackend() {
  if (!props.room) {
    roomStatus.value = 'available'
    roomStatusMessage.value = 'Available'
    return
  }

  isFetchingStatus.value = true
  statusFetchError.value = null

  try {
    const headers: Record<string, string> = {}

    if (
      bookingForm.value.checkInDate &&
      bookingForm.value.checkOutDate &&
      isValidDateRange.value &&
      !isPastDate.value
    ) {
      const response = await publicAxios.get('/reservations/availability', {
        params: {
          room_id: props.room.id,
          check_in_date: bookingForm.value.checkInDate,
          check_out_date: bookingForm.value.checkOutDate,
        },
        headers,
      })

      if (response.data.available) {
        roomStatus.value = 'available'
        roomStatusMessage.value = 'Available for Dates'
      } else {
        roomStatus.value = 'booked'
        roomStatusMessage.value = response.data.message || 'Booked for Dates'
      }
    } else {
      const response = await roomService.getRoom(props.room.id)
      let roomData = response.data?.data || response.data
      const status = roomData?.status ? String(roomData.status).toLowerCase() : 'available'
      const isActive = roomData?.is_active !== false

      if (status === 'available' && isActive) {
        roomStatus.value = 'available'
        roomStatusMessage.value = 'Available'
      } else if (status === 'occupied' || !isActive) {
        roomStatus.value = 'booked'
        roomStatusMessage.value = 'Occupied'
      } else if (status === 'reserved') {
        roomStatus.value = 'booked'
        roomStatusMessage.value = 'Reserved'
      } else if (status === 'maintenance') {
        roomStatus.value = 'maintenance'
        roomStatusMessage.value = 'Under Maintenance'
      } else {
        roomStatus.value = 'available'
        roomStatusMessage.value = 'Available'
      }
    }
  } catch (error: any) {
    console.error('[BookingModal] Error fetching room status from backend:', error)
    const msg = error.response?.data?.message || 'Date Conflict'
    roomStatus.value = 'booked'
    roomStatusMessage.value = msg
  } finally {
    isFetchingStatus.value = false
  }
}

watch(
  () => [bookingForm.value.checkInDate, bookingForm.value.checkOutDate],
  () => {
    if (
      props.isOpen &&
      props.room &&
      bookingForm.value.checkInDate &&
      bookingForm.value.checkOutDate
    ) {
      fetchRoomStatusFromBackend()
    }
  },
)

function calculateNights(): number {
  if (!bookingForm.value.checkInDate || !bookingForm.value.checkOutDate) return 0
  const checkIn = new Date(bookingForm.value.checkInDate)
  const checkOut = new Date(bookingForm.value.checkOutDate)
  const nights = Math.floor((checkOut.getTime() - checkIn.getTime()) / (1000 * 60 * 60 * 24))
  return Math.max(nights, 1)
}

function calculateRoomTotal(): number {
  if (!props.room) return 0
  return getRoomPrice(props.room) * calculateNights()
}

function calculateAdditionalServices(): number {
  let total = 0
  if (bookingForm.value.includeBreakfast) total += 0
  if (bookingForm.value.includeDinner) total += 45 * calculateNights()
  if (bookingForm.value.includeSpa) total += 35 * calculateNights()
  return total
}

function getSubtotal(): number {
  return calculateRoomTotal() + calculateAdditionalServices()
}

function getTaxAmount(): number {
  return getSubtotal() * 0.15
}

function calculateGrandTotal(): number {
  return getSubtotal() + getTaxAmount()
}

function getTotalAmount(): number {
  return calculateGrandTotal()
}

function closeModal() {
  modalScale.value = 0.95
  isVisible.value = false
  setTimeout(() => {
    resetBookingForm()
    emit('close')
  }, 300)
}

function resetBookingForm() {
  bookingForm.value = {
    checkInDate: '',
    checkOutDate: '',
    guests: 1,
    guestName: '',
    guestEmail: '',
    guestPhone: '',
    roomPreference: '',
    specialRequests: '',
    includeBreakfast: false,
    includeDinner: false,
    includeSpa: false,
  }
}

function openPaymentDialog() {
  if (isPastDate.value) {
    alert('Check-in date cannot be in the past.')
    return
  }
  if (!isValidDateRange.value) {
    alert('Check-out date must be after check-in date.')
    return
  }
  if (isCapacityExceeded.value) {
    alert(
      `Maximum capacity for this room is ${getRoomCapacity(props.room)} guest(s). Please adjust your guest count.`,
    )
    return
  }
  if (roomStatus.value !== 'available') {
    alert(
      roomStatusMessage.value || 'This room is not available for the selected dates in this hotel.',
    )
    return
  }
  if (
    !bookingForm.value.guestName ||
    !bookingForm.value.guestEmail ||
    !bookingForm.value.guestPhone
  ) {
    const fields = []
    if (!bookingForm.value.guestName) fields.push('Full Name')
    if (!bookingForm.value.guestEmail) fields.push('Email')
    if (!bookingForm.value.guestPhone) fields.push('Phone')
    alert(`Please fill in: ${fields.join(', ')}`)
    return
  }
  showPaymentDialog.value = true
}

function closePaymentDialog() {
  showPaymentDialog.value = false
}

async function proceedWithPayment() {
  if (paymentLoading.value || isBooking.value) return

  if (
    !bookingForm.value.guestName ||
    !bookingForm.value.guestEmail ||
    !bookingForm.value.guestPhone
  ) {
    const fields = []
    if (!bookingForm.value.guestName) fields.push('Full Name')
    if (!bookingForm.value.guestEmail) fields.push('Email')
    if (!bookingForm.value.guestPhone) fields.push('Phone')

    alert(`Please fill in: ${fields.join(', ')}`)
    return
  }

  paymentLoading.value = true
  isBooking.value = true

  try {
    const nameParts = bookingForm.value.guestName.trim().split(' ')
    const firstName = nameParts[0] || 'Guest'
    const lastName = nameParts.slice(1).join(' ') || 'Customer'

    const roomId = props.room?.id
    if (!roomId) {
      throw new Error('Room information is missing')
    }

    const paymentPayload = {
      room_id: String(roomId),
      check_in_date: bookingForm.value.checkInDate,
      check_out_date: bookingForm.value.checkOutDate,
      number_of_guests: Number(bookingForm.value.guests) || 1,
      special_requests: bookingForm.value.specialRequests || undefined,
      first_name: firstName,
      last_name: lastName,
      email: bookingForm.value.guestEmail.trim(),
      phone: bookingForm.value.guestPhone.trim(),
      include_breakfast: bookingForm.value.includeBreakfast,
      include_dinner: bookingForm.value.includeDinner,
      include_spa: bookingForm.value.includeSpa,
    }

    const paymentData = await paymentService.initializeReservationPayment(paymentPayload)

    if (!paymentData.success && !paymentData.checkout_url) {
      throw new Error(paymentData.message || 'Payment initialization failed')
    }

    const bookingSessionData = {
      room_id: roomId,
      check_in_date: bookingForm.value.checkInDate,
      check_out_date: bookingForm.value.checkOutDate,
      number_of_guests: bookingForm.value.guests,
      special_requests: bookingForm.value.specialRequests,
      first_name: firstName,
      last_name: lastName,
      email: bookingForm.value.guestEmail,
      phone: bookingForm.value.guestPhone,
      payment_id: paymentData.payment_id || '',
      tx_ref: paymentData.tx_ref || '',
      amount: paymentData.amount || calculateGrandTotal(),
      price_breakdown: {
        price_per_night: getRoomPrice(props.room),
        number_of_nights: calculateNights(),
        room_subtotal: calculateRoomTotal(),
        services_total: calculateAdditionalServices(),
        subtotal: getSubtotal(),
        tax: getTaxAmount(),
        total: calculateGrandTotal(),
      },
    }

    sessionStorage.setItem('booking_session', JSON.stringify(bookingSessionData))
    if (paymentData.tx_ref) {
      sessionStorage.setItem('booking_reference', paymentData.tx_ref)
    }

    if (paymentData.checkout_url) {
      window.location.href = paymentData.checkout_url
      return
    }

    closePaymentDialog()
    closeModal()
    router.push({
      path: '/payment/success',
      query: { tx_ref: paymentData.tx_ref || '' },
    })
  } catch (error: any) {
    console.error('[BookingModal] Payment initialization error:', error)
    const errorMessage =
      error.response?.data?.message || error.message || 'Failed to initialize payment'
    alert(`Payment Error: ${errorMessage}`)
  } finally {
    paymentLoading.value = false
    isBooking.value = false
  }
}

const initializeForm = () => {
  if (props.isOpen && props.room) {
    const today = new Date()
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    bookingForm.value.checkInDate = today.toISOString().split('T')[0]
    bookingForm.value.checkOutDate = tomorrow.toISOString().split('T')[0]
    bookingForm.value.guests = getRoomCapacity(props.room)

    fetchRoomStatusFromBackend()

    nextTick(() => {
      isVisible.value = true
      modalScale.value = 1
    })
  }
}

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      initializeForm()
      document.body.style.overflow = 'hidden'
      document.body.style.touchAction = 'none'
    } else {
      isVisible.value = false
      modalScale.value = 0.95
      document.body.style.overflow = 'auto'
      document.body.style.touchAction = 'auto'
    }
  },
  { immediate: true },
)

onMounted(() => {
  if (props.isOpen && props.room) {
    initializeForm()
  }
})

onUnmounted(() => {
  document.body.style.overflow = 'auto'
  document.body.style.touchAction = 'auto'
})

</script>

<template>
  <Teleport to="body">
    <div
      v-if="isOpen && room"
      class="fixed inset-0 bg-black/75 backdrop-blur-xs z-50 flex items-center justify-center p-2 sm:p-4 md:p-6 overflow-y-auto transition-opacity duration-300"
      :class="isVisible ? 'opacity-100' : 'opacity-0'"
      @click.self="closeModal"
    >
      <div
        class="bg-white dark:bg-[#101827] text-slate-900 dark:text-white w-full max-w-5xl rounded-2xl md:rounded-3xl flex flex-col overflow-hidden max-h-[94vh] sm:max-h-[90vh] shadow-2xl border border-slate-200/90 dark:border-white/10 transition-all duration-300 my-auto"
        :style="{ transform: `scale(${modalScale})`, opacity: isVisible ? 1 : 0 }"
      >
        <!-- Modal Header -->
        <div
          class="relative bg-[#101827] border-b border-[#26354D] px-4 sm:px-6 md:px-8 py-3.5 sm:py-4 flex justify-between items-center flex-shrink-0"
        >
          <!-- Top gold hairline -->
          <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent opacity-80" />

          <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <div class="p-1.5 sm:p-2 bg-[#182235] border border-[#D6A94F]/30 rounded-xl backdrop-blur-sm shrink-0">
              <Sparkles class="w-4 h-4 sm:w-5 sm:h-5 text-[#D6A94F]" />
            </div>
            <div class="min-w-0">
              <h2 class="font-serif text-base sm:text-xl md:text-2xl font-bold text-white tracking-tight truncate">
                {{ languageStore.t('complete_your_booking', 'Complete Your Booking') }}
              </h2>
              <p class="text-[11px] sm:text-xs text-[#D6A94F] truncate">
                {{ languageStore.t('secure_your_stay', 'Secure your stay at') }}
                {{ guestHotelStore.hotelName || room?.hotel?.name || 'Executive Hotel' }}
              </p>
            </div>
          </div>
          <button
            @click="closeModal"
            class="text-slate-400 hover:text-white hover:bg-[#182235] p-2 rounded-xl transition-all duration-200 leading-none flex-shrink-0 cursor-pointer"
            aria-label="Close booking modal"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Responsive Modal Body -->
        <div
          class="flex-1 overflow-y-auto md:overflow-hidden md:grid md:grid-cols-12 md:gap-6 p-4 sm:p-5 md:p-6 lg:p-7 min-h-0"
        >
          <!-- Left Column: Room Details & Breakdown (Desktop & Tablet left; Mobile stacked below) -->
          <div class="md:col-span-5 flex flex-col space-y-4 md:overflow-y-auto order-2 md:order-1 pr-0 md:pr-1">
            <!-- Room Image Banner -->
            <div
              class="relative w-full bg-slate-100 dark:bg-slate-800 rounded-xl sm:rounded-2xl overflow-hidden flex-shrink-0 shadow-md aspect-[4/3] max-h-[220px] sm:max-h-[260px]"
            >
              <img
                :src="getRoomImage(room)"
                :alt="getRoomTypeName(room)"
                class="w-full h-full object-cover"
              />
              <!-- Rating Badge -->
              <div
                class="absolute top-3 right-3 bg-black/75 backdrop-blur-sm text-white text-xs font-semibold px-2.5 py-1 rounded-full flex items-center gap-1.5 whitespace-nowrap shadow-md"
              >
                <Star class="w-3.5 h-3.5 fill-amber-400 text-amber-400 shrink-0" />
                <span>{{ getRoomRating(room) }}</span>
              </div>

              <!-- Status Badge -->
              <div
                v-if="!isFetchingStatus"
                :class="[
                  'absolute bottom-3 left-3 backdrop-blur-sm text-white text-xs font-semibold px-2.5 py-1 rounded-full flex items-center gap-1.5 shadow-md whitespace-nowrap',
                  roomStatus === 'available'
                    ? 'bg-emerald-600/90'
                    : roomStatus === 'booked'
                      ? 'bg-rose-600/90'
                      : 'bg-amber-600/90',
                ]"
              >
                <CheckCircle class="w-3.5 h-3.5 shrink-0" />
                <span>{{ roomStatusMessage }}</span>
              </div>
              <div
                v-else
                class="absolute bottom-3 left-3 bg-slate-700/90 backdrop-blur-sm text-white text-xs font-semibold px-2.5 py-1 rounded-full flex items-center gap-1.5 shadow-md whitespace-nowrap"
              >
                <div class="relative w-3.5 h-3.5 shrink-0">
                  <div
                    class="absolute inset-0 animate-spin"
                    style="animation: spin 1.5s linear infinite"
                  >
                    <svg viewBox="0 0 100 100" class="w-full h-full">
                      <circle
                        cx="50"
                        cy="50"
                        r="40"
                        fill="none"
                        stroke="#FBBF24"
                        stroke-width="8"
                        stroke-linecap="round"
                        stroke-dasharray="60 240"
                      />
                    </svg>
                  </div>
                </div>
                <span>{{ languageStore.t('checking_status', 'Checking Status...') }}</span>
              </div>
            </div>

            <!-- Status banner strip -->
            <div
              :class="[
                'rounded-xl p-3 flex items-center justify-between flex-shrink-0 shadow-xs border',
                roomStatus === 'available'
                  ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/40'
                  : roomStatus === 'booked'
                    ? 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800/40'
                    : 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/40',
              ]"
            >
              <div class="flex items-center gap-2 min-w-0">
                <CheckCircle
                  :class="[
                    'w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0',
                    roomStatus === 'available'
                      ? 'text-emerald-600 dark:text-emerald-400'
                      : roomStatus === 'booked'
                        ? 'text-rose-600 dark:text-rose-400'
                        : 'text-amber-600 dark:text-amber-400',
                  ]"
                />
                <div class="min-w-0">
                  <p
                    :class="[
                      'text-xs font-semibold',
                      roomStatus === 'available'
                        ? 'text-emerald-900 dark:text-emerald-200'
                        : roomStatus === 'booked'
                          ? 'text-rose-900 dark:text-rose-200'
                          : 'text-amber-900 dark:text-amber-200',
                    ]"
                  >
                    {{ languageStore.t('room_status', 'Room Status') }}
                  </p>
                  <p
                    :class="[
                      'text-sm font-bold truncate',
                      roomStatus === 'available'
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : roomStatus === 'booked'
                          ? 'text-rose-600 dark:text-rose-400'
                          : 'text-amber-600 dark:text-amber-400',
                    ]"
                  >
                    {{ roomStatusMessage }}
                  </p>
                </div>
              </div>
              <span
                :class="[
                  'px-2.5 py-1 rounded-full text-xs font-bold shrink-0',
                  roomStatus === 'available'
                    ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200'
                    : roomStatus === 'booked'
                      ? 'bg-rose-100 dark:bg-rose-900/50 text-rose-800 dark:text-rose-200'
                      : 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200',
                ]"
              >
                {{ roomStatusMessage }}
              </span>
            </div>

            <!-- Room info summary box -->
            <div
              class="bg-slate-50 dark:bg-[#182235]/60 border border-slate-200/80 dark:border-[#26354D] rounded-xl sm:rounded-2xl p-4 sm:p-5 space-y-3 shrink-0 shadow-xs"
            >
              <div class="border-b border-slate-200/80 dark:border-[#26354D] pb-3">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white truncate">
                      {{ getRoomTypeName(room) }}
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                      {{ languageStore.t('room_label', 'Room') }} #{{ room.room_number }}
                    </p>
                  </div>
                  <div class="text-right shrink-0">
                    <span class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-[#D6A94F]">
                      ETB {{ getRoomPrice(room) }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 block"
                      >/ {{ languageStore.t('night_singular', 'Night') }}</span
                    >
                  </div>
                </div>
              </div>

              <!-- Amenities tags -->
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="amenity in getRoomAmenities(room).slice(0, 4)"
                  :key="amenity"
                  class="inline-flex items-center gap-1 text-[11px] bg-white dark:bg-[#101827] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10 px-2.5 py-1 rounded-full"
                >
                  <CheckCircle class="w-3 h-3 text-emerald-500 shrink-0" />
                  <span>{{ amenity }}</span>
                </span>
                <span
                  v-if="getRoomAmenities(room).length > 4"
                  class="text-[11px] text-slate-400 font-medium self-center px-1"
                >
                  +{{ getRoomAmenities(room).length - 4 }} more
                </span>
              </div>

              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed line-clamp-3">
                {{ getRoomDescription(room) }}
              </p>

              <!-- Stay Duration Box -->
              <div class="bg-amber-50/70 dark:bg-amber-500/10 border border-amber-200/70 dark:border-amber-500/20 rounded-xl p-3 space-y-2 text-slate-800 dark:text-slate-200">
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-1.5">
                    <Calendar class="w-4 h-4 text-amber-600 dark:text-[#D6A94F]" />
                    <span class="text-xs font-semibold">{{
                      languageStore.t('stay_duration', 'Stay Duration')
                    }}</span>
                  </div>
                  <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                    {{ calculateNights() }}
                    {{
                      calculateNights() === 1
                        ? languageStore.t('night_singular', 'Night')
                        : languageStore.t('nights_plural', 'Nights')
                    }}
                  </span>
                </div>
                <div class="flex justify-between text-xs sm:text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ languageStore.t('check_in', 'Check-in') }}</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200">{{
                    bookingForm.checkInDate
                      ? new Date(bookingForm.checkInDate).toLocaleDateString('en-US', {
                          weekday: 'short',
                          month: 'short',
                          day: 'numeric',
                        })
                      : '--'
                  }}</span>
                </div>
                <div class="flex justify-between text-xs sm:text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{
                    languageStore.t('check_out', 'Check-out')
                  }}</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200">{{
                    bookingForm.checkOutDate
                      ? new Date(bookingForm.checkOutDate).toLocaleDateString('en-US', {
                          weekday: 'short',
                          month: 'short',
                          day: 'numeric',
                        })
                      : '--'
                  }}</span>
                </div>
                <div class="flex justify-between text-xs sm:text-sm pt-1.5 border-t border-amber-200/50 dark:border-amber-500/20">
                  <span class="text-slate-600 dark:text-slate-400">{{ languageStore.t('guests', 'Guests') }}</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200">
                    {{ bookingForm.guests }} {{ languageStore.t('guests', 'Guests') }}
                  </span>
                </div>
              </div>

              <!-- Price Breakdown Items -->
              <div class="border-t border-slate-200/80 dark:border-[#26354D] pt-3 space-y-1.5">
                <div class="flex justify-between text-xs sm:text-sm">
                  <span class="text-slate-600 dark:text-slate-400">
                    {{ languageStore.t('room_label', 'Room') }} ({{ calculateNights() }} × ETB
                    {{ getRoomPrice(room) }})
                  </span>
                  <span class="font-semibold text-slate-900 dark:text-white">ETB {{ calculateRoomTotal() }}</span>
                </div>
                <div
                  v-if="bookingForm.includeBreakfast"
                  class="flex justify-between text-xs sm:text-sm text-emerald-600 dark:text-emerald-400"
                >
                  <span class="flex items-center gap-1.5">
                    <Coffee class="w-3.5 h-3.5" />
                    {{ languageStore.t('breakfast', 'Breakfast') }}
                  </span>
                  <span>{{ languageStore.t('free', 'Free') }}</span>
                </div>
                <div
                  v-if="bookingForm.includeDinner"
                  class="flex justify-between text-xs sm:text-sm text-slate-700 dark:text-slate-300"
                >
                  <span class="flex items-center gap-1.5">
                    <Utensils class="w-3.5 h-3.5" />
                    {{ languageStore.t('dinner', 'Dinner') }} ({{ calculateNights() }} × ETB 45)
                  </span>
                  <span>ETB {{ calculateNights() * 45 }}</span>
                </div>
                <div
                  v-if="bookingForm.includeSpa"
                  class="flex justify-between text-xs sm:text-sm text-slate-700 dark:text-slate-300"
                >
                  <span class="flex items-center gap-1.5">
                    <Sparkles class="w-3.5 h-3.5" />
                    {{ languageStore.t('spa', 'Spa') }} ({{ calculateNights() }} × ETB 35)
                  </span>
                  <span>ETB {{ calculateNights() * 35 }}</span>
                </div>
              </div>

              <!-- Grand Total Box -->
              <div
                class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-[#1e2a3f] dark:to-[#182235] border border-amber-200 dark:border-[#D6A94F]/30 rounded-xl p-3 sm:p-3.5 flex justify-between items-center"
              >
                <span class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">{{
                  languageStore.t('total', 'Grand Total')
                }}</span>
                <span class="text-lg sm:text-xl md:text-2xl font-bold text-amber-600 dark:text-[#D6A94F]">
                  ETB {{ calculateGrandTotal() }}
                </span>
              </div>
            </div>
          </div>

          <!-- Right Column: Guest Information Form -->
          <div class="md:col-span-7 flex flex-col space-y-3.5 md:overflow-y-auto order-1 md:order-2 pr-0 md:pr-1">
            <div class="flex items-center justify-between flex-shrink-0 pb-1">
              <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
                  {{ languageStore.t('guest_information', 'Guest Information') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{
                    languageStore.t(
                      'fill_details_booking',
                      'Fill in your details to complete the booking',
                    )
                  }}
                </p>
              </div>
              <span class="text-xs text-rose-500 font-semibold"
                >* {{ languageStore.t('required_mark', 'Required') }}</span
              >
            </div>

            <!-- Mobile-only compact room banner -->
            <div
              class="bg-slate-50 dark:bg-[#182235]/70 border border-slate-200/90 dark:border-[#26354D] rounded-xl overflow-hidden md:hidden flex-shrink-0 shadow-xs"
            >
              <div class="flex items-center gap-3 p-3">
                <img
                  :src="getRoomImage(room)"
                  :alt="getRoomTypeName(room)"
                  class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg flex-shrink-0"
                />
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                    {{ getRoomTypeName(room) }}
                  </h4>
                  <p class="text-xs text-slate-500 dark:text-slate-400">Room #{{ room.room_number }}</p>
                  <p class="text-sm font-bold text-amber-600 dark:text-[#D6A94F] mt-0.5">
                    ETB {{ calculateGrandTotal() }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Form Fields -->
            <div class="space-y-3 flex-1">
              <!-- Check-In & Check-Out Inputs -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    {{ languageStore.t('check_in', 'Check-in') }}
                    <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="bookingForm.checkInDate"
                    type="date"
                    :min="today"
                    class="w-full px-3 py-2.5 bg-slate-50 dark:bg-[#182235] border border-slate-300 dark:border-[#26354D] rounded-xl focus:border-[#D6A94F] focus:ring-2 focus:ring-[#D6A94F]/20 focus:outline-none text-sm text-slate-900 dark:text-white transition-all"
                  />
                  <p v-if="isPastDate" class="text-xs text-rose-500 font-semibold mt-1">
                    {{ languageStore.t('check_in_past_error', 'Check-in cannot be in past') }}
                  </p>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    {{ languageStore.t('check_out', 'Check-out') }}
                    <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="bookingForm.checkOutDate"
                    type="date"
                    :min="bookingForm.checkInDate || today"
                    class="w-full px-3 py-2.5 bg-slate-50 dark:bg-[#182235] border border-slate-300 dark:border-[#26354D] rounded-xl focus:border-[#D6A94F] focus:ring-2 focus:ring-[#D6A94F]/20 focus:outline-none text-sm text-slate-900 dark:text-white transition-all"
                  />
                  <p v-if="!isValidDateRange" class="text-xs text-rose-500 font-semibold mt-1">
                    {{ languageStore.t('must_be_after_checkin', 'Must be after check-in') }}
                  </p>
                </div>
              </div>

              <!-- Guests Input -->
              <div>
                <div class="flex justify-between items-center mb-1">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                    {{ languageStore.t('guests', 'Guests') }} <span class="text-rose-500">*</span>
                  </label>
                  <span v-if="room" class="text-xs text-slate-500 dark:text-slate-400">
                    {{
                      languageStore
                        .t('max_capacity_guests', 'Max: {count} guests')
                        .replace('{count}', String(getRoomCapacity(room)))
                    }}
                  </span>
                </div>
                <div class="relative flex items-center">
                  <Users class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" />
                  <input
                    v-model.number="bookingForm.guests"
                    type="number"
                    min="1"
                    :max="getRoomCapacity(room)"
                    :class="{ 'border-rose-500 focus:border-rose-500': isCapacityExceeded }"
                    class="w-full pl-9 pr-3 py-2.5 bg-slate-50 dark:bg-[#182235] border border-slate-300 dark:border-[#26354D] rounded-xl focus:border-[#D6A94F] focus:ring-2 focus:ring-[#D6A94F]/20 focus:outline-none text-sm text-slate-900 dark:text-white transition-all"
                  />
                </div>
                <p v-if="isCapacityExceeded" class="text-xs text-rose-500 font-semibold mt-1">
                  {{ languageStore.t('exceeds_max_capacity', 'Exceeds room maximum capacity') }} ({{
                    getRoomCapacity(room)
                  }}
                  {{ languageStore.t('guests', 'guests') }}).
                </p>
              </div>

              <!-- Full Name Input -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  {{ languageStore.t('full_name', 'Full Name') }}
                  <span class="text-rose-500">*</span>
                </label>
                <div class="relative flex items-center">
                  <User class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" />
                  <input
                    v-model="bookingForm.guestName"
                    type="text"
                    :placeholder="languageStore.t('full_name', 'Full Name')"
                    class="w-full pl-9 pr-3 py-2.5 bg-slate-50 dark:bg-[#182235] border border-slate-300 dark:border-[#26354D] rounded-xl focus:border-[#D6A94F] focus:ring-2 focus:ring-[#D6A94F]/20 focus:outline-none text-sm text-slate-900 dark:text-white transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500"
                  />
                </div>
              </div>

              <!-- Email Address Input -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  {{ languageStore.t('email_address', 'Email Address') }}
                  <span class="text-rose-500">*</span>
                </label>
                <div class="relative flex items-center">
                  <Mail class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" />
                  <input
                    v-model="bookingForm.guestEmail"
                    type="email"
                    :placeholder="languageStore.t('email_address', 'Email Address')"
                    class="w-full pl-9 pr-3 py-2.5 bg-slate-50 dark:bg-[#182235] border border-slate-300 dark:border-[#26354D] rounded-xl focus:border-[#D6A94F] focus:ring-2 focus:ring-[#D6A94F]/20 focus:outline-none text-sm text-slate-900 dark:text-white transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500"
                  />
                </div>
              </div>

              <!-- Phone Number Input -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  {{ languageStore.t('phone_number', 'Phone Number') }}
                  <span class="text-rose-500">*</span>
                </label>
                <div class="relative flex items-center">
                  <Phone class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" />
                  <input
                    v-model="bookingForm.guestPhone"
                    type="tel"
                    :placeholder="languageStore.t('phone_number', 'Phone Number')"
                    class="w-full pl-9 pr-3 py-2.5 bg-slate-50 dark:bg-[#182235] border border-slate-300 dark:border-[#26354D] rounded-xl focus:border-[#D6A94F] focus:ring-2 focus:ring-[#D6A94F]/20 focus:outline-none text-sm text-slate-900 dark:text-white transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500"
                  />
                </div>
              </div>

              <!-- Preferred Room Input -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  {{ languageStore.t('preferred_room_optional', 'Preferred Room (Optional)') }}
                </label>
                <div class="relative flex items-center">
                  <MapPin class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" />
                  <input
                    v-model="bookingForm.roomPreference"
                    type="text"
                    placeholder="e.g., 204"
                    class="w-full pl-9 pr-3 py-2.5 bg-slate-50 dark:bg-[#182235] border border-slate-300 dark:border-[#26354D] rounded-xl focus:border-[#D6A94F] focus:ring-2 focus:ring-[#D6A94F]/20 focus:outline-none text-sm text-slate-900 dark:text-white transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500"
                  />
                </div>
              </div>

              <!-- Add-on Services Selection -->
              <div class="bg-slate-50 dark:bg-[#182235]/60 rounded-xl p-3 sm:p-3.5 space-y-2 border border-slate-200/80 dark:border-[#26354D]">
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  {{ languageStore.t('addon_services', 'Add-on Services') }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                  <label
                    class="flex items-center gap-2 bg-white dark:bg-[#182235] px-3 py-2 rounded-xl border border-slate-200 dark:border-[#26354D] hover:border-amber-400 dark:hover:border-amber-400 transition-all cursor-pointer shadow-2xs"
                  >
                    <input
                      v-model="bookingForm.includeBreakfast"
                      type="checkbox"
                      class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                    />
                    <div class="flex items-center gap-1.5 min-w-0">
                      <Coffee class="w-3.5 h-3.5 text-amber-600 dark:text-[#D6A94F] shrink-0" />
                      <span class="text-xs font-medium text-slate-700 dark:text-slate-200 truncate">{{
                        languageStore.t('breakfast', 'Breakfast')
                      }}</span>
                    </div>
                  </label>
                  <label
                    class="flex items-center gap-2 bg-white dark:bg-[#182235] px-3 py-2 rounded-xl border border-slate-200 dark:border-[#26354D] hover:border-amber-400 dark:hover:border-amber-400 transition-all cursor-pointer shadow-2xs"
                  >
                    <input
                      v-model="bookingForm.includeDinner"
                      type="checkbox"
                      class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                    />
                    <div class="flex items-center gap-1.5 min-w-0">
                      <Utensils class="w-3.5 h-3.5 text-amber-600 dark:text-[#D6A94F] shrink-0" />
                      <span class="text-xs font-medium text-slate-700 dark:text-slate-200 truncate">{{
                        languageStore.t('dinner', 'Dinner')
                      }}</span>
                    </div>
                  </label>
                  <label
                    class="flex items-center gap-2 bg-white dark:bg-[#182235] px-3 py-2 rounded-xl border border-slate-200 dark:border-[#26354D] hover:border-amber-400 dark:hover:border-amber-400 transition-all cursor-pointer shadow-2xs"
                  >
                    <input
                      v-model="bookingForm.includeSpa"
                      type="checkbox"
                      class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                    />
                    <div class="flex items-center gap-1.5 min-w-0">
                      <Sparkles class="w-3.5 h-3.5 text-amber-600 dark:text-[#D6A94F] shrink-0" />
                      <span class="text-xs font-medium text-slate-700 dark:text-slate-200 truncate">{{
                        languageStore.t('spa', 'Spa')
                      }}</span>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Special Requests Textarea -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  {{ languageStore.t('special_requests_optional', 'Special Requests (Optional)') }}
                </label>
                <textarea
                  v-model="bookingForm.specialRequests"
                  placeholder="e.g., Ground floor, extra pillows, quiet room..."
                  rows="2"
                  class="w-full px-3 py-2 bg-slate-50 dark:bg-[#182235] border border-slate-300 dark:border-[#26354D] rounded-xl focus:border-[#D6A94F] focus:ring-2 focus:ring-[#D6A94F]/20 focus:outline-none text-sm text-slate-900 dark:text-white transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 resize-none"
                />
              </div>

              <!-- Confirmation Note -->
              <div
                class="bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-800/50 rounded-xl p-2.5 text-xs text-emerald-800 dark:text-emerald-300 flex items-start gap-2"
              >
                <CheckCircle class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                <p>
                  {{
                    languageStore.t(
                      'referral_code_notice',
                      "Your booking will generate a referral code. You'll receive a confirmation email shortly.",
                    )
                  }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions -->
        <div
          class="border-t border-slate-200 dark:border-[#26354D] bg-slate-50/90 dark:bg-[#101827] px-4 sm:px-6 md:px-8 py-3.5 sm:py-4 flex flex-col sm:flex-row gap-2.5 sm:gap-3 flex-shrink-0"
        >
          <button
            @click="closeModal"
            type="button"
            class="order-2 sm:order-1 sm:w-1/3 px-4 py-2.5 sm:py-3 bg-white dark:bg-[#182235] hover:bg-slate-100 dark:hover:bg-[#1f2d44] text-slate-700 dark:text-slate-200 font-semibold rounded-xl border border-slate-300 dark:border-[#26354D] transition-all text-xs sm:text-sm cursor-pointer shadow-2xs"
          >
            {{ languageStore.t('cancel', 'Cancel') }}
          </button>
          <button
            @click="openPaymentDialog"
            type="button"
            :disabled="
              isBooking ||
              roomStatus !== 'available' ||
              isPastDate ||
              !isValidDateRange ||
              isCapacityExceeded ||
              !bookingForm.guestName ||
              !bookingForm.guestEmail ||
              !bookingForm.guestPhone
            "
            class="order-1 sm:order-2 flex-1 px-4 py-3 rounded-xl font-bold text-xs sm:text-sm uppercase tracking-wide transition-all duration-300 shadow-md flex items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-60 cursor-pointer"
            :class="
              isBooking
                ? 'bg-slate-700 text-white'
                : roomStatus === 'available' &&
                    !isPastDate &&
                    isValidDateRange &&
                    !isCapacityExceeded
                  ? 'bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] shadow-[#D6A94F]/25 active:scale-[0.99]'
                  : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500'
            "
          >
            <template v-if="isBooking">
              <Loader class="w-4 h-4 sm:w-5 sm:h-5 animate-spin" />
              <span>{{ languageStore.t('processing_payment', 'Processing Payment...') }}</span>
            </template>

            <template v-else>
              <Sparkles class="w-4 h-4 sm:w-5 sm:h-5" />
              <span>{{ languageStore.t('proceed_to_payment_btn', 'Proceed to Payment') }}</span>
            </template>
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="showPaymentDialog"
      class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[9999] p-3 sm:p-4 overflow-hidden"
      @click.self="closePaymentDialog"
    >
      <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden border border-slate-200 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200"
      >
        <div
          class="bg-[#101827] border-b border-[#26354D] px-5 py-4 text-white flex items-center justify-between flex-shrink-0 relative overflow-hidden"
        >
          <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent opacity-80" />
          <div>
            <h3 class="font-serif text-lg sm:text-xl font-bold tracking-tight text-white">
              {{ languageStore.t('payment_confirmation', 'Payment Confirmation') }}
            </h3>
            <p class="text-xs text-[#D6A94F]">
              {{
                languageStore.t(
                  'review_stay_charges',
                  'Review your stay and charges before payment',
                )
              }}
            </p>
          </div>
          <button
            @click="closePaymentDialog"
            class="text-slate-400 hover:text-white hover:bg-[#182235] p-1.5 rounded-lg transition cursor-pointer"
            title="Close"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-5 space-y-4 overflow-y-auto flex-1 text-slate-800 dark:text-slate-200">
          <div
            class="space-y-2.5 bg-slate-50 dark:bg-slate-800/50 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-700/60"
          >
            <h4
              class="text-xs uppercase tracking-wider font-extrabold text-slate-500 dark:text-slate-400"
            >
              {{ languageStore.t('stay_details', 'Stay Details') }}
            </h4>

            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600 dark:text-slate-400"
                >{{ languageStore.t('hotel_label', 'Hotel') }}:</span
              >
              <span class="font-bold text-slate-900 dark:text-white text-right">{{
                guestHotelStore.hotelName || props.room?.hotel?.name || 'Hotel Stay'
              }}</span>
            </div>

            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600 dark:text-slate-400"
                >{{ languageStore.t('room_label', 'Room') }}:</span
              >
              <span class="font-medium text-slate-900 dark:text-white text-right"
                >#{{ props.room?.room_number || 'N/A' }} • {{ getRoomTypeName(props.room) }}</span
              >
            </div>

            <div
              class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-200/60 dark:border-slate-700/60 text-xs"
            >
              <div>
                <span class="text-slate-500 block">{{
                  languageStore.t('check_in', 'Check-in')
                }}</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{
                  bookingForm.checkInDate
                }}</span>
              </div>
              <div class="text-right">
                <span class="text-slate-500 block">{{
                  languageStore.t('check_out', 'Check-out')
                }}</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{
                  bookingForm.checkOutDate
                }}</span>
              </div>
            </div>

            <div
              class="flex justify-between items-center text-xs pt-1 border-t border-slate-200/60 dark:border-slate-700/60"
            >
              <span class="text-slate-600 dark:text-slate-400"
                >{{ languageStore.t('guests_and_duration', 'Guests & Duration') }}:</span
              >
              <span class="font-bold text-slate-800 dark:text-slate-200">
                {{ bookingForm.guests }} {{ languageStore.t('guests', 'Guests') }} •
                {{ calculateNights() }}
                {{
                  calculateNights() === 1
                    ? languageStore.t('night_singular', 'Night')
                    : languageStore.t('nights_plural', 'Nights')
                }}
              </span>
            </div>
          </div>

          <div
            class="space-y-2 bg-slate-50 dark:bg-slate-800/50 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-700/60"
          >
            <h4
              class="text-xs uppercase tracking-wider font-extrabold text-slate-500 dark:text-slate-400"
            >
              {{ languageStore.t('payment_breakdown', 'Payment Breakdown') }}
            </h4>

            <div class="flex justify-between text-sm">
              <span class="text-slate-600 dark:text-slate-400"
                >{{ languageStore.t('room_label', 'Room') }} ({{ calculateNights() }} × ETB
                {{ getRoomPrice(props.room) }})</span
              >
              <span class="font-semibold text-slate-900 dark:text-white"
                >ETB {{ calculateRoomTotal().toFixed(2) }}</span
              >
            </div>

            <div
              v-if="calculateAdditionalServices() > 0"
              class="flex justify-between text-sm text-amber-600 dark:text-amber-400 font-medium"
            >
              <span>{{ languageStore.t('addon_services', 'Add-on Services') }}</span>
              <span>+ETB {{ calculateAdditionalServices().toFixed(2) }}</span>
            </div>

            <div
              class="flex justify-between text-sm border-t border-slate-200/60 dark:border-slate-700/60 pt-1.5"
            >
              <span class="text-slate-600 dark:text-slate-400">{{
                languageStore.t('subtotal', 'Subtotal')
              }}</span>
              <span class="font-medium text-slate-900 dark:text-white"
                >ETB {{ getSubtotal().toFixed(2) }}</span
              >
            </div>

            <div class="flex justify-between text-sm">
              <span class="text-slate-600 dark:text-slate-400">{{
                languageStore.t('tax_15', 'Tax (15%)')
              }}</span>
              <span class="font-medium text-slate-900 dark:text-white"
                >ETB {{ getTaxAmount().toFixed(2) }}</span
              >
            </div>

            <div
              class="flex justify-between text-base font-extrabold pt-2 border-t border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white"
            >
              <span>{{ languageStore.t('total_payable', 'Total Payable') }}:</span>
              <span class="text-amber-600 dark:text-amber-400 text-lg"
                >ETB {{ getTotalAmount().toFixed(2) }}</span
              >
            </div>
          </div>

          <div
            class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 rounded-xl p-3 text-xs text-blue-800 dark:text-blue-300 flex items-center gap-2"
          >
            <CheckCircle class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
            <span>{{
              languageStore.t(
                'secure_chapa_notice',
                'Secure payment processed via Chapa payment gateway.',
              )
            }}</span>
          </div>
        </div>

        <div
          class="bg-slate-50 dark:bg-slate-800/90 px-5 py-3.5 border-t border-slate-200 dark:border-slate-700 flex gap-3 flex-shrink-0 shadow-lg"
        >
          <button
            @click="closePaymentDialog"
            :disabled="paymentLoading"
            class="flex-1 px-4 py-2.5 text-sm font-bold border-2 border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition duration-200 disabled:opacity-50 cursor-pointer"
          >
            {{ languageStore.t('cancel', 'Cancel') }}
          </button>

          <button
            @click="proceedWithPayment"
            :disabled="paymentLoading"
            class="flex-1 px-4 py-2.5 text-sm font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-xl transition duration-200 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-amber-500/25 cursor-pointer"
          >
            <span v-if="paymentLoading" class="animate-spin">⌛</span>
            <span v-if="paymentLoading">{{ languageStore.t('processing', 'Processing...') }}</span>
            <span v-else
              >💳 {{ languageStore.t('pay_amount', 'Pay') }} ETB
              {{ getTotalAmount().toFixed(2) }}</span
            >
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 9999px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
