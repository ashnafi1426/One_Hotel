<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useRoomStore } from '@/stores/room'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useLanguageStore } from '@/stores/language'
import { BedDouble, Users, ArrowRight, Sparkles, Check } from 'lucide-vue-next'
import BookingModal from './BookingModal.vue'

const router = useRouter()
const roomStore = useRoomStore()
const guestHotelStore = useGuestHotelStore()
const languageStore = useLanguageStore()
const loading = ref(false)

const showBookingModal = ref(false)
const selectedRoomForBooking = ref<any>(null)

const fallbackRooms = [
  {
    id: 'f1',
    room_number: '101',
    room_type: { name: 'Executive Deluxe Suite', base_price_per_night: 3500, capacity: 2 },
    images: ['/images/rooms/deluxe.jpg'],
    amenities: ['King Bed', 'City Skyline View', 'High-Speed Wi-Fi', 'En-suite Jacuzzi'],
  },
  {
    id: 'f2',
    room_number: '202',
    room_type: { name: 'Presidential Royal Suite', base_price_per_night: 5500, capacity: 4 },
    images: ['/images/rooms/suite.jpg'],
    amenities: ['2 Master Bedrooms', 'Private Balcony', 'Breakfast Included', 'Mini Bar & Lounge'],
  },
  {
    id: 'f3',
    room_number: '303',
    room_type: { name: 'Premier King Sanctuary', base_price_per_night: 2800, capacity: 2 },
    images: ['/images/rooms/family.jpg'],
    amenities: ['King Bed', 'Ergonomic Work Desk', 'Smart 4K TV', '24/7 Room Service'],
  },
]

const rooms = computed(() => {
  if (roomStore.rooms && roomStore.rooms.length > 0) {
    return roomStore.rooms.slice(0, 3)
  }
  return fallbackRooms
})

function getRoomName(room: any): string {
  return room.room_type?.name || room.name || `Luxury Suite #${room.room_number || ''}`
}

function getRoomPrice(room: any): string {
  const price = room.room_type?.base_price_per_night || room.price_per_night || 2800
  const currency = guestHotelStore.currency || 'ETB'
  return `${Number(price).toLocaleString()} ${currency}`
}

function getRoomImage(room: any): string {
  if (room.images && room.images.length > 0) {
    const firstImg = typeof room.images[0] === 'object' ? room.images[0]?.url : room.images[0]
    if (firstImg) return firstImg
  }
  const name = getRoomName(room).toLowerCase()
  if (name.includes('presidential') || name.includes('royal') || name.includes('suite')) {
    return '/images/rooms/suite.jpg'
  }
  if (name.includes('family') || name.includes('twin')) {
    return '/images/rooms/family.jpg'
  }
  return '/images/rooms/deluxe.jpg'
}

function openBooking(room: any) {
  selectedRoomForBooking.value = room
  showBookingModal.value = true
}

function closeBooking() {
  showBookingModal.value = false
  selectedRoomForBooking.value = null
}

function goToRooms() {
  router.push('/rooms')
}

onMounted(async () => {
  loading.value = true
  try {
    await roomStore.fetchRooms()
  } catch (err) {
    console.error('[FeaturedRoom] Error fetching rooms:', err)
  } finally {
    loading.value = false
  }
})

watch(
  () => guestHotelStore.hotelId,
  async () => {
    loading.value = true
    try {
      await roomStore.fetchRooms()
    } catch (err) {
      console.error('[FeaturedRoom] Error fetching rooms on hotel change:', err)
    } finally {
      loading.value = false
    }
  },
)
</script>

<template>
  <section
    id="featured-rooms"
    class="py-16 sm:py-20 lg:py-28 transition-colors duration-300 font-sans border-b border-slate-200/80 dark:border-[#26354D]/70 bg-[#F7F7F5] dark:bg-[#0B111D]"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      <!-- Section Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="space-y-3 max-w-2xl">
          <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-[0.2em] bg-[#D6A94F]/10 text-[#8A641A] dark:text-[#D6A94F] border border-[#D6A94F]/30">
            <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
            <span>{{ languageStore.t('featured_accommodation', 'Exclusive Accommodations') }}</span>
          </div>
          <h2
            class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-[#101827] dark:text-white tracking-tight"
          >
            {{ languageStore.t('explore_fine_rooms', 'Signature Rooms & Suites') }}
          </h2>
          <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-light leading-relaxed">
            {{
              languageStore.t(
                'designed_for_luxury',
                'Each private sanctuary combines handcrafted furnishings, bespoke amenities, and serene comfort for an extraordinary stay.',
              )
            }}
          </p>
        </div>

        <button
          @click="goToRooms"
          class="px-6 py-3 rounded-full bg-[#182235] hover:bg-[#101827] text-white dark:bg-[#182235] dark:hover:bg-[#26354D] font-bold text-xs uppercase tracking-wider transition flex items-center gap-2 cursor-pointer self-start md:self-auto border border-[#26354D] shadow-md hover:border-[#D6A94F]/60"
        >
          <span>{{ languageStore.t('view_all_rooms', 'View All Suites') }}</span>
          <ArrowRight class="w-4 h-4 text-[#D6A94F]" />
        </button>
      </div>

      <!-- Room Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div
          v-for="room in rooms"
          :key="room.id"
          class="group bg-white dark:bg-[#182235] border border-slate-200/90 dark:border-[#26354D] rounded-3xl overflow-hidden shadow-md hover:shadow-2xl hover:border-[#D6A94F]/50 transition-all duration-500 flex flex-col justify-between"
        >
          <!-- Image Container with 16:10 aspect ratio and rate badge -->
          <div class="relative aspect-[16/10] overflow-hidden bg-slate-900">
            <img
              :src="getRoomImage(room)"
              :alt="getRoomName(room)"
              class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
              loading="lazy"
              @error="(e: any) => (e.target.src = '/images/rooms/deluxe.jpg')"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20" />

            <div
              class="absolute bottom-3 left-3 px-3 py-1.5 bg-[#101827]/90 backdrop-blur-md text-[#D6A94F] font-bold text-xs rounded-xl border border-[#D6A94F]/40 shadow-lg"
            >
              <span>{{ getRoomPrice(room) }}</span>
              <span class="text-[10px] text-slate-300 font-normal"> / {{ languageStore.t('per_night', 'night') }}</span>
            </div>
          </div>

          <!-- Room Details Body -->
          <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
            <div class="space-y-2.5">
              <h3
                class="font-serif text-xl font-bold text-[#101827] dark:text-white group-hover:text-[#D6A94F] transition-colors leading-snug"
              >
                {{ getRoomName(room) }}
              </h3>

              <div
                class="flex items-center gap-4 text-xs font-medium text-slate-600 dark:text-slate-400"
              >
                <div class="flex items-center gap-1.5">
                  <Users class="w-4 h-4 text-[#D6A94F]" />
                  <span>
                    {{ languageStore.t('up_to', 'Up to') }}
                    {{ (room.room_type as any)?.capacity || (room.room_type as any)?.max_occupancy || 2 }}
                    {{ languageStore.t('guests_count', 'Guests') }}
                  </span>
                </div>
                <div class="flex items-center gap-1.5">
                  <BedDouble class="w-4 h-4 text-[#D6A94F]" />
                  <span>{{ languageStore.t('room', 'Room') }} #{{ room.room_number || '101' }}</span>
                </div>
              </div>
            </div>

            <!-- Amenities Badges -->
            <div class="pt-4 border-t border-slate-100 dark:border-[#26354D]/60 flex flex-wrap gap-1.5">
              <span
                v-for="(amenity, idx) in (room.amenities || ['King Bed', 'City View', 'Free Wi-Fi']).slice(0, 3)"
                :key="typeof amenity === 'object' ? amenity.name || idx : amenity || idx"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-[#FAF3E7] dark:bg-[#101827] text-[#8A641A] dark:text-slate-300 border border-[#D6A94F]/20 dark:border-[#26354D]"
              >
                <Check class="w-3 h-3 text-[#D6A94F]" />
                {{ typeof amenity === 'object' ? amenity.name : amenity }}
              </span>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-2 gap-2.5 pt-4">
              <button
                @click="openBooking(room)"
                class="py-2.5 px-3 rounded-xl border border-[#26354D] dark:border-[#26354D] text-[#101827] dark:text-slate-200 font-bold text-xs uppercase tracking-wider hover:border-[#D6A94F] hover:text-[#D6A94F] transition-all cursor-pointer text-center"
              >
                {{ languageStore.t('details', 'Details') }}
              </button>

              <button
                @click="openBooking(room)"
                class="py-2.5 px-3 rounded-xl bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black text-xs uppercase tracking-wider shadow-md shadow-[#D6A94F]/20 hover:shadow-lg transition-all cursor-pointer flex items-center justify-center gap-1.5"
              >
                <span>{{ languageStore.t('book_now', 'Book Now') }}</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Integrated Booking Modal for seamless direct booking -->
    <BookingModal
      :is-open="showBookingModal"
      :room="selectedRoomForBooking"
      @close="closeBooking"
    />
  </section>
</template>
