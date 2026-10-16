<script setup lang="ts">
import { computed, ref } from 'vue'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { Users, BedDouble, Check, Calendar, Eye } from 'lucide-vue-next'
import BookingModal from './BookingModal.vue'

interface Room {
  id: string
  name: string
  room_number?: string
  room_type: string
  price: number
  capacity: number
  size?: number
  bed_type?: string
  image: string
  rating?: number
  reviews?: number
  available?: boolean
  amenities?: string[]
}

const props = defineProps<{
  room: Room
}>()

const emit = defineEmits<{
  (e: 'details', room: Room): void
  (e: 'book', room: Room): void
}>()

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
const showBookingModal = ref(false)

const formattedPrice = computed(() => {
  const currency = guestHotelStore.currency || 'ETB'
  return `${Number(props.room.price || 0).toLocaleString()} ${currency}`
})

function viewDetails() {
  emit('details', props.room)
}

function reserveRoom() {
  emit('book', props.room)
  showBookingModal.value = true
}

function closeBooking() {
  showBookingModal.value = false
}
</script>

<template>
  <div
    class="group relative overflow-hidden rounded-3xl bg-white dark:bg-[#182235] shadow-md hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 border border-slate-200/90 dark:border-[#26354D] flex flex-col text-slate-800 dark:text-slate-100 justify-between"
  >
    <!-- Room Visual -->
    <div class="relative overflow-hidden aspect-[16/10] flex-shrink-0 bg-slate-900">
      <img
        :src="room.image || '/images/rooms/deluxe.jpg'"
        :alt="room.name"
        class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
        loading="lazy"
        @error="(e: any) => (e.target.src = '/images/rooms/deluxe.jpg')"
      />

      <div class="absolute inset-0 bg-gradient-to-t from-[#101827]/70 via-transparent to-[#101827]/20" />

      <div
        class="absolute bottom-3 left-3 bg-[#101827]/90 backdrop-blur-md px-3.5 py-1.5 rounded-xl border border-[#D6A94F]/40 shadow-lg text-[#D6A94F]"
      >
        <span class="font-bold text-sm md:text-base leading-none">
          {{ formattedPrice }}
        </span>
        <span class="text-[10px] text-slate-300 font-normal">
          / {{ languageStore.t('per_night', 'night') }}
        </span>
      </div>
    </div>

    <!-- Room Body -->
    <div class="p-5 flex flex-col flex-grow justify-between space-y-4">
      <div class="space-y-1.5">
        <h3 class="font-serif text-lg font-bold text-[#101827] dark:text-white group-hover:text-[#D6A94F] transition-colors leading-snug">
          {{ room.name }}
        </h3>

        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 font-medium">
          <span class="flex items-center gap-1">
            <Users class="w-3.5 h-3.5 text-[#D6A94F]" />
            {{ room.capacity }} {{ languageStore.t('guests', 'Guests') }}
          </span>
          <span class="text-slate-300 dark:text-slate-700">|</span>
          <span class="flex items-center gap-1">
            <BedDouble class="w-3.5 h-3.5 text-[#D6A94F]" />
            {{ room.bed_type || 'King Bed' }}
          </span>
        </div>
      </div>

      <!-- Amenities Highlights -->
      <div v-if="room.amenities && room.amenities.length" class="flex flex-wrap gap-1.5 pt-2 border-t border-slate-100 dark:border-[#26354D]/60">
        <span
          v-for="item in room.amenities.slice(0, 3)"
          :key="item"
          class="inline-flex items-center gap-1 text-[10px] font-medium bg-[#FAF3E7] dark:bg-[#101827] text-[#8A641A] dark:text-slate-300 px-2 py-0.5 rounded-md border border-[#D6A94F]/20 dark:border-[#26354D]"
        >
          <Check class="w-2.5 h-2.5 text-[#D6A94F]" />
          {{ item }}
        </span>
      </div>

      <!-- Actions -->
      <div class="grid grid-cols-2 gap-2.5 pt-3 border-t border-slate-100 dark:border-[#26354D]/60 mt-auto">
        <button
          @click="viewDetails"
          class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl border border-[#26354D] text-[#101827] dark:text-slate-200 font-bold hover:border-[#D6A94F] hover:text-[#D6A94F] transition text-xs uppercase tracking-wider cursor-pointer"
        >
          <Eye class="w-3.5 h-3.5" />
          <span>{{ languageStore.t('details', 'Details') }}</span>
        </button>

        <button
          @click="reserveRoom"
          class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black transition text-xs uppercase tracking-wider shadow-md shadow-[#D6A94F]/20 cursor-pointer"
        >
          <Calendar class="w-3.5 h-3.5" />
          <span>{{ languageStore.t('book_now', 'Book Now') }}</span>
        </button>
      </div>
    </div>

    <!-- Booking Modal -->
    <BookingModal
      :isOpen="showBookingModal"
      :room="(room as any)"
      @close="closeBooking"
    />
  </div>
</template>
