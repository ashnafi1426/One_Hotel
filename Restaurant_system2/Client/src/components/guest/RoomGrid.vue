<script setup lang="ts">
import { ref } from 'vue'
import BookingModal from './BookingModal.vue'
import { useLanguageStore } from '@/stores/language'
import { Users, BedDouble, Check, Eye, Calendar, X } from 'lucide-vue-next'

const languageStore = useLanguageStore()

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
  images?: any[]
}

defineProps<{
  rooms: Room[]
}>()

const selectedRoom = ref<Room | null>(null)
const showDetails = ref(false)
const showBookingModal = ref(false)
const roomToBook = ref<Room | null>(null)

const getRoomTypeName = (room: Room): string => {
  if (typeof room.room_type === 'string') {
    return room.room_type
  }
  return room.room_type?.name || 'Luxury Suite'
}

const getRoomPrice = (room: Room): number => {
  if (typeof room.room_type === 'object' && room.room_type?.base_price_per_night) {
    return Math.round(room.room_type.base_price_per_night)
  }
  return 2800
}

const getRoomCapacity = (room: Room): number => {
  if (typeof room.room_type === 'object' && room.room_type?.capacity) {
    return room.room_type.capacity
  }
  return 2
}

const getRoomAmenities = (room: Room): string[] => {
  if (typeof room.room_type === 'object' && room.room_type?.amenities && Array.isArray(room.room_type.amenities)) {
    return room.room_type.amenities
  }
  return ['King Bed', 'City Skyline View', 'High-Speed Wi-Fi', 'Room Service']
}

const getRoomDescription = (room: Room): string => {
  if (typeof room.room_type === 'object' && room.room_type?.description) {
    return room.room_type.description
  }
  return room.description || 'Experience ultimate comfort and tranquility in our signature luxury suites.'
}

const getRoomImage = (room: Room): string => {
  if (room.images && room.images.length > 0) {
    const firstImg = typeof room.images[0] === 'object' ? room.images[0]?.url : room.images[0]
    if (firstImg) return firstImg
  }
  const roomType = getRoomTypeName(room).toLowerCase()
  const imageMap: Record<string, string> = {
    deluxe: '/images/rooms/deluxe.jpg',
    vip: '/images/rooms/suite.jpg',
    wvip: '/images/rooms/suite.jpg',
    suite: '/images/rooms/suite.jpg',
    presidential: '/images/rooms/suite.jpg',
    'twin small': '/images/rooms/family.jpg',
    'twin big': '/images/rooms/family.jpg',
    standard: '/images/rooms/deluxe.jpg',
  }

  for (const [key, value] of Object.entries(imageMap)) {
    if (roomType.includes(key)) {
      return value
    }
  }

  return '/images/rooms/deluxe.jpg'
}

function selectRoom(room: Room) {
  selectedRoom.value = room
  showDetails.value = true
}

function openBookingModal(room: Room) {
  roomToBook.value = room
  showBookingModal.value = true
}

function closeBookingModal() {
  showBookingModal.value = false
  roomToBook.value = null
}

function handleBookingSubmit(_bookingData: any) {}
</script>

<template>
  <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
    <!-- Results summary bar -->
    <div class="results-bar">
      <div class="results-left">
        <span class="results-dot"></span>
        <span class="results-count text-[#101827] dark:text-white">{{ rooms.length }}</span>
        <span class="results-label text-slate-500 dark:text-slate-400">
          {{ rooms.length === 1 ? languageStore.t('room', 'suite') : languageStore.t('rooms', 'suites') }}
          {{ languageStore.t('rooms_found', 'available for your stay') }}
        </span>
      </div>
      <div class="results-line"></div>
    </div>

    <!-- Responsive Room Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div
        v-for="room in rooms"
        :key="room.id"
        class="group bg-white dark:bg-[#182235] rounded-3xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 border border-slate-200/90 dark:border-[#26354D] flex flex-col justify-between"
      >
        <!-- Room Visual & Floating Price Tag -->
        <div class="relative w-full aspect-[16/10] overflow-hidden bg-slate-900 flex-shrink-0">
          <img
            :src="getRoomImage(room)"
            :alt="getRoomTypeName(room)"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
            loading="lazy"
            @error="(e: any) => (e.target.src = '/images/rooms/deluxe.jpg')"
          />

          <div
            class="absolute inset-0 bg-gradient-to-t from-[#101827]/70 via-transparent to-[#101827]/20"
          ></div>

          <div
            class="absolute bottom-3 left-3 bg-[#101827]/90 backdrop-blur-md px-3.5 py-1.5 rounded-xl border border-[#D6A94F]/40 shadow-lg text-[#D6A94F]"
          >
            <div class="text-[9px] uppercase tracking-wider font-extrabold text-[#D6A94F]/90 leading-none mb-0.5">
              {{ languageStore.t('nightly_rate', 'Nightly Rate') }}
            </div>
            <div class="flex items-baseline gap-1">
              <span class="font-bold text-base md:text-lg leading-none">
                ETB {{ getRoomPrice(room).toLocaleString() }}
              </span>
              <span class="text-[10px] text-slate-300 font-normal">
                {{ languageStore.t('per_night', '/night') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Room Information Body -->
        <div class="p-6 space-y-4 flex flex-col flex-grow">
          <div class="space-y-1.5">
            <h3 class="font-serif text-xl font-bold text-[#101827] dark:text-white group-hover:text-[#D6A94F] transition-colors leading-snug">
              {{ getRoomTypeName(room) }}
            </h3>

            <div class="flex items-center gap-4 text-xs text-slate-600 dark:text-slate-400 font-medium">
              <span class="flex items-center gap-1.5">
                <Users class="w-3.5 h-3.5 text-[#D6A94F]" />
                {{
                  languageStore
                    .t('max_capacity_guests', 'Max {count} Guests')
                    .replace('{count}', String(getRoomCapacity(room)))
                }}
              </span>
              <span class="text-slate-300 dark:text-slate-700">|</span>
              <span class="flex items-center gap-1.5">
                <BedDouble class="w-3.5 h-3.5 text-[#D6A94F]" />
                {{ languageStore.t('room', 'Room') }} #{{ room.room_number || '101' }}
              </span>
            </div>
          </div>

          <!-- Room Amenities Highlights -->
          <div class="space-y-1.5 pt-2 border-t border-slate-100 dark:border-[#26354D]/60 flex-grow text-xs text-slate-600 dark:text-slate-300 font-medium">
            <div class="flex items-center gap-2">
              <Check class="w-3.5 h-3.5 text-[#D6A94F] flex-shrink-0" />
              <span>{{ languageStore.t('comfort_and_privacy', 'Comfort and Serene Privacy') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Check class="w-3.5 h-3.5 text-[#D6A94F] flex-shrink-0" />
              <span>{{ languageStore.t('complimentary_breakfast', 'Complimentary Breakfast Included') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Check class="w-3.5 h-3.5 text-[#D6A94F] flex-shrink-0" />
              <span>{{ languageStore.t('free_wifi_feature', 'High-Speed Fiber Wi-Fi') }}</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-2.5 pt-4 border-t border-slate-100 dark:border-[#26354D]/60 mt-auto">
            <button
              @click="selectRoom(room)"
              class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-[#26354D] dark:border-[#26354D] text-[#101827] dark:text-slate-200 font-bold hover:border-[#D6A94F] hover:text-[#D6A94F] transition-all text-xs uppercase tracking-wider cursor-pointer"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>{{ languageStore.t('details', 'Details') }}</span>
            </button>

            <button
              @click="openBookingModal(room)"
              class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black transition-all text-xs uppercase tracking-wider shadow-md shadow-[#D6A94F]/20 hover:shadow-lg cursor-pointer"
            >
              <Calendar class="w-3.5 h-3.5" />
              <span>{{ languageStore.t('book_room', 'Book Now') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Details Modal with Luxury Styling -->
    <div
      v-if="showDetails && selectedRoom"
      class="fixed inset-0 bg-[#101827]/80 backdrop-blur-md flex items-center justify-center p-4 md:p-6 z-50 transition-opacity"
      @click.self="showDetails = false"
    >
      <div
        class="bg-white dark:bg-[#182235] rounded-3xl w-full max-w-lg lg:max-w-xl p-6 md:p-8 max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 dark:border-[#26354D] relative"
      >
        <button
          @click="showDetails = false"
          class="absolute top-5 right-5 w-9 h-9 flex items-center justify-center rounded-full bg-slate-100 dark:bg-[#101827] text-slate-500 hover:text-white hover:bg-[#D6A94F] transition-all cursor-pointer"
          aria-label="Close modal"
        >
          <X class="w-4 h-4" />
        </button>

        <h2 class="font-serif text-2xl md:text-3xl font-bold text-[#101827] dark:text-white mb-2 pr-10">
          {{ getRoomTypeName(selectedRoom) }}
        </h2>

        <p class="text-slate-600 dark:text-slate-300 mb-6 text-sm leading-relaxed font-light">
          {{ getRoomDescription(selectedRoom) }}
        </p>

        <div class="mb-6 space-y-3 text-sm bg-[#F7F7F5] dark:bg-[#101827] p-4 rounded-2xl border border-slate-200/80 dark:border-[#26354D]">
          <div class="flex items-center justify-between">
            <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('capacity', 'Guest Capacity') }}</span>
            <span class="font-bold text-[#101827] dark:text-white">{{ getRoomCapacity(selectedRoom) }} {{ languageStore.t('guests', 'guests') }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('nightly_rate', 'Nightly Rate') }}</span>
            <span class="font-bold text-[#D6A94F]">ETB {{ getRoomPrice(selectedRoom).toLocaleString() }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('room_number', 'Room Number') }}</span>
            <span class="font-bold text-[#101827] dark:text-white">#{{ selectedRoom.room_number || '101' }}</span>
          </div>
        </div>

        <div class="flex gap-3">
          <button
            @click="showDetails = false"
            class="flex-1 px-5 py-3 border border-[#26354D] text-slate-700 dark:text-slate-300 font-bold rounded-xl transition text-xs uppercase tracking-wider hover:bg-slate-100 dark:hover:bg-[#101827] cursor-pointer"
          >
            {{ languageStore.t('close', 'Close') }}
          </button>
          <button
            @click="() => { showDetails = false; openBookingModal(selectedRoom!) }"
            class="flex-1 px-5 py-3 bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black rounded-xl transition text-xs uppercase tracking-wider shadow-lg shadow-[#D6A94F]/25 cursor-pointer"
          >
            {{ languageStore.t('book_now', 'Book Now') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Booking Modal -->
    <BookingModal
      :isOpen="showBookingModal"
      :room="roomToBook"
      @close="closeBookingModal"
      @submit="handleBookingSubmit"
    />
  </div>
</template>

<style scoped>
.results-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 28px;
}

.results-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.results-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #D6A94F;
  box-shadow: 0 0 0 3px rgba(214, 169, 79, 0.25);
  display: block;
}

.results-count {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.results-label {
  font-size: 13px;
  font-weight: 500;
}

.results-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(to right, rgba(214, 169, 79, 0.3) 40%, transparent);
}
</style>
