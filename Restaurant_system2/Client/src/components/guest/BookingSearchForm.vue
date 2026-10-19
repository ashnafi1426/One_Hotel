<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Calendar, Users, BedDouble, Search, AlertCircle } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useRoomTypeStore } from '@/stores/roomType'

const props = withDefaults(
  defineProps<{
    variant?: 'hero' | 'standalone' | 'compact'
    initialCheckIn?: string
    initialCheckOut?: string
    initialGuests?: string | number
    initialRoomType?: string
  }>(),
  {
    variant: 'hero',
    initialGuests: '2',
    initialRoomType: 'All',
  },
)

const emit = defineEmits<{
  (e: 'search', params: { checkIn: string; checkOut: string; guests: number; roomType: string }): void
}>()

const router = useRouter()
const languageStore = useLanguageStore()
const roomTypeStore = useRoomTypeStore()

// Helper to format Date to YYYY-MM-DD
function formatDate(d: Date): string {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const todayDate = new Date()
const tomorrowDate = new Date()
tomorrowDate.setDate(tomorrowDate.getDate() + 1)

const todayStr = formatDate(todayDate)
const tomorrowStr = formatDate(tomorrowDate)

const checkIn = ref(props.initialCheckIn || todayStr)
const checkOut = ref(props.initialCheckOut || tomorrowStr)
const guests = ref(Number(props.initialGuests) || 2)
const selectedRoomType = ref(props.initialRoomType || 'All')
const errorMessage = ref('')

// Compute minimum checkout date based on checkIn
const minCheckOut = computed(() => {
  if (!checkIn.value) return tomorrowStr
  const d = new Date(checkIn.value)
  d.setDate(d.getDate() + 1)
  return formatDate(d)
})

function handleCheckInChange() {
  errorMessage.value = ''
  if (checkIn.value < todayStr) {
    checkIn.value = todayStr
  }
  if (checkOut.value <= checkIn.value) {
    const nextDay = new Date(checkIn.value)
    nextDay.setDate(nextDay.getDate() + 1)
    checkOut.value = formatDate(nextDay)
  }
}

function handleCheckOutChange() {
  errorMessage.value = ''
  if (checkOut.value <= checkIn.value) {
    errorMessage.value = languageStore.t(
      'checkout_after_checkin',
      'Check-out date must be after check-in date',
    )
  }
}

function submitSearch() {
  errorMessage.value = ''
  if (!checkIn.value || !checkOut.value) {
    errorMessage.value = languageStore.t('select_dates_error', 'Please select both check-in and check-out dates.')
    return
  }

  if (checkIn.value < todayStr) {
    errorMessage.value = languageStore.t('checkin_past_error', 'Check-in date cannot be in the past.')
    return
  }

  if (checkOut.value <= checkIn.value) {
    errorMessage.value = languageStore.t(
      'checkout_after_checkin',
      'Check-out date must be after check-in date.',
    )
    return
  }

  const searchParams = {
    checkIn: checkIn.value,
    checkOut: checkOut.value,
    guests: Number(guests.value),
    roomType: selectedRoomType.value,
  }

  emit('search', searchParams)

  // Navigate to rooms page with query params if not already on rooms page
  if (router.currentRoute.value.path !== '/rooms') {
    router.push({
      path: '/rooms',
      query: {
        checkIn: checkIn.value,
        checkOut: checkOut.value,
        guests: String(guests.value),
        type: selectedRoomType.value !== 'All' ? selectedRoomType.value : undefined,
      },
    })
  }
}

onMounted(async () => {
  if (roomTypeStore.roomTypes.length === 0) {
    try {
      await roomTypeStore.fetchRoomTypes()
    } catch {
      // Fallback already handled
    }
  }
})
</script>

<template>
  <div
    class="w-full relative z-30 transition-all duration-300"
    :class="[
      variant === 'hero'
        ? 'max-w-6xl mx-auto px-4 sm:px-6'
        : 'max-w-7xl mx-auto',
    ]"
  >
    <div
      class="rounded-3xl bg-[#182235] border border-white/10 p-5 sm:p-6 lg:p-7 shadow-2xl text-white relative overflow-hidden"
    >
      <!-- Subtle luxury gold top hairline accent -->
      <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent opacity-90" />

      <form @submit.prevent="submitSearch" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4 items-end">
          <!-- Check-In Date -->
          <div class="space-y-1.5 text-left">
            <label
              for="hero-check-in"
              class="block text-[11px] font-bold uppercase tracking-wider text-[#F5BD47] flex items-center gap-1.5"
            >
              <Calendar class="w-3.5 h-3.5 text-[#F5BD47]" />
              <span class="text-[#F5BD47]">{{ languageStore.t('check_in', 'Check-In') }}</span>
            </label>
            <input
              id="hero-check-in"
              v-model="checkIn"
              type="date"
              :min="todayStr"
              @change="handleCheckInChange"
              required
              class="w-full h-12 px-3.5 rounded-2xl bg-[#101827] border border-white/15 text-white font-medium text-sm focus:outline-none focus:border-[#F5BD47] focus:ring-1 focus:ring-[#F5BD47] transition cursor-pointer [color-scheme:dark]"
            />
          </div>

          <!-- Check-Out Date -->
          <div class="space-y-1.5 text-left">
            <label
              for="hero-check-out"
              class="block text-[11px] font-bold uppercase tracking-wider text-[#F5BD47] flex items-center gap-1.5"
            >
              <Calendar class="w-3.5 h-3.5 text-[#F5BD47]" />
              <span class="text-[#F5BD47]">{{ languageStore.t('check_out', 'Check-Out') }}</span>
            </label>
            <input
              id="hero-check-out"
              v-model="checkOut"
              type="date"
              :min="minCheckOut"
              @change="handleCheckOutChange"
              required
              class="w-full h-12 px-3.5 rounded-2xl bg-[#101827] border border-white/15 text-white font-medium text-sm focus:outline-none focus:border-[#F5BD47] focus:ring-1 focus:ring-[#F5BD47] transition cursor-pointer [color-scheme:dark]"
            />
          </div>

          <!-- Number of Guests -->
          <div class="space-y-1.5 text-left">
            <label
              for="hero-guests"
              class="block text-[11px] font-bold uppercase tracking-wider text-[#F5BD47] flex items-center gap-1.5"
            >
              <Users class="w-3.5 h-3.5 text-[#F5BD47]" />
              <span class="text-[#F5BD47]">{{ languageStore.t('guests', 'Guests') }}</span>
            </label>
            <select
              id="hero-guests"
              v-model="guests"
              class="w-full h-12 px-3.5 rounded-2xl bg-[#101827] border border-white/15 text-white font-medium text-sm focus:outline-none focus:border-[#F5BD47] focus:ring-1 focus:ring-[#F5BD47] transition cursor-pointer [color-scheme:dark]"
            >
              <option :value="1">1 {{ languageStore.t('guest_singular', 'Guest') }}</option>
              <option :value="2">2 {{ languageStore.t('guests_plural', 'Guests') }}</option>
              <option :value="3">3 {{ languageStore.t('guests_plural', 'Guests') }}</option>
              <option :value="4">4+ {{ languageStore.t('guests_plural', 'Guests') }}</option>
            </select>
          </div>

          <!-- Room Type -->
          <div class="space-y-1.5 text-left">
            <label
              for="hero-room-type"
              class="block text-[11px] font-bold uppercase tracking-wider text-[#F5BD47] flex items-center gap-1.5"
            >
              <BedDouble class="w-3.5 h-3.5 text-[#F5BD47]" />
              <span class="text-[#F5BD47]">{{ languageStore.t('room_type', 'Room Type') }}</span>
            </label>
            <select
              id="hero-room-type"
              v-model="selectedRoomType"
              class="w-full h-12 px-3.5 rounded-2xl bg-[#101827] border border-white/15 text-white font-medium text-sm focus:outline-none focus:border-[#F5BD47] focus:ring-1 focus:ring-[#F5BD47] transition cursor-pointer [color-scheme:dark] truncate"
            >
              <option value="All">{{ languageStore.t('all_types', 'All Room Types') }}</option>
              <option
                v-for="rt in roomTypeStore.roomTypes"
                :key="rt.id"
                :value="rt.name"
              >
                {{ rt.name }}
              </option>
            </select>
          </div>

          <!-- Search CTA Action Button -->
          <div class="sm:col-span-2 lg:col-span-1 pt-1 sm:pt-0">
            <button
              type="submit"
              class="w-full h-12 min-h-[48px] px-6 rounded-2xl bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black text-xs uppercase tracking-wider shadow-lg shadow-[#D6A94F]/25 hover:shadow-xl hover:shadow-[#D6A94F]/35 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95"
              :title="languageStore.t('check_availability', 'Check Availability')"
            >
              <Search class="w-4 h-4 text-[#101827]" />
              <span>{{ languageStore.t('check_availability', 'Check Availability') }}</span>
            </button>
          </div>
        </div>

        <!-- Validation error message if any -->
        <div
          v-if="errorMessage"
          class="flex items-center gap-2 text-rose-400 text-xs font-semibold pt-1"
        >
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>
      </form>
    </div>
  </div>
</template>
