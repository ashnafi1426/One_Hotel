<script setup lang="ts">
import { computed, ref } from 'vue'
import BookingModal from '@/components/guest/BookingModal.vue'

interface Room {
  id: string | number
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
  available: boolean
  amenities?: string[]
  description?: string
}

const props = defineProps<{
  modelValue: boolean
  room: Room
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'close'): void
  (e: 'reserve', room: Room): void
}>()

const showBookingModal = ref(false)

const isOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => {
    emit('update:modelValue', value)
  },
})

const formattedPrice = computed(() => {
  return `$${Number(props.room.price || 0).toLocaleString()}`
})

const availabilityText = computed(() => {
  return props.room.available ? 'Available' : 'Currently Reserved'
})

const availabilityClass = computed(() => {
  return props.room.available
    ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
    : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
})

function closeDialog() {
  emit('update:modelValue', false)
  emit('close')
}

function handleReserve() {
  emit('reserve', props.room)
  showBookingModal.value = true
}

const roomForModal = computed(() => ({
  id: String(props.room.id),
  room_number: props.room.room_number || String(props.room.id),
  room_type: props.room.room_type,
  price_per_night: props.room.price,
}))
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="duration-200 ease-in"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-[#070C14]/80 backdrop-blur-md">
        <div class="flex min-h-screen items-center justify-center p-4 sm:p-6">
          <div class="relative w-full max-w-4xl overflow-hidden rounded-2xl bg-[#101827] border border-white/10 shadow-2xl text-white">
            <!-- Close button -->
            <button
              @click="closeDialog"
              aria-label="Close dialog"
              class="absolute right-5 top-5 z-20 flex h-10 w-10 items-center justify-center rounded-full bg-[#182235]/80 text-white/80 backdrop-blur-sm transition hover:bg-[#D6A94F] hover:text-[#101827]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <!-- Hero Image Banner -->
            <div class="relative h-72 sm:h-96 w-full overflow-hidden">
              <img :src="room.image" :alt="room.name" class="h-full w-full object-cover" />
              <div class="absolute inset-0 bg-gradient-to-t from-[#101827] via-[#101827]/40 to-transparent" />

              <div
                class="absolute left-6 top-6 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider backdrop-blur-md"
                :class="availabilityClass"
              >
                {{ availabilityText }}
              </div>

              <div class="absolute bottom-6 left-6 right-6">
                <span class="text-xs font-semibold uppercase tracking-[0.25em] text-[#D6A94F]">
                  {{ room.room_type }}
                </span>
                <h2 class="mt-2 text-2xl sm:text-4xl font-serif font-light text-white">
                  {{ room.name }}
                </h2>
                <div class="mt-3 flex items-center gap-4 text-sm text-slate-300">
                  <span>Room #{{ room.room_number || room.id }}</span>
                  <span class="text-white/40">•</span>
                  <span class="text-[#D6A94F] font-semibold text-lg">{{ formattedPrice }} <span class="text-xs font-normal text-slate-400">/ night</span></span>
                </div>
              </div>
            </div>

            <!-- Content Area -->
            <div class="p-6 sm:p-8 space-y-8">
              <!-- Key Specs -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-xl bg-[#182235] border border-white/5 p-4 text-center">
                  <div class="text-xs uppercase tracking-wider text-slate-400">Capacity</div>
                  <div class="mt-1 font-semibold text-white">{{ room.capacity }} Guests</div>
                </div>
                <div class="rounded-xl bg-[#182235] border border-white/5 p-4 text-center">
                  <div class="text-xs uppercase tracking-wider text-slate-400">Room Size</div>
                  <div class="mt-1 font-semibold text-white">{{ room.size || 35 }} m²</div>
                </div>
                <div class="rounded-xl bg-[#182235] border border-white/5 p-4 text-center">
                  <div class="text-xs uppercase tracking-wider text-slate-400">Bed Type</div>
                  <div class="mt-1 font-semibold text-white">{{ room.bed_type || 'King Bed' }}</div>
                </div>
                <div class="rounded-xl bg-[#182235] border border-white/5 p-4 text-center">
                  <div class="text-xs uppercase tracking-wider text-slate-400">Rate</div>
                  <div class="mt-1 font-semibold text-[#D6A94F]">{{ formattedPrice }}</div>
                </div>
              </div>

              <!-- Description -->
              <div>
                <h3 class="text-lg font-serif font-medium text-white mb-2">Room Overview</h3>
                <p class="text-sm sm:text-base leading-relaxed text-slate-300">
                  {{
                    room.description ||
                    'Experience an exceptional standard of sanctuary and comfort. Featuring bespoke furnishings, artisanal linens, spa-inspired bath amenities, high-speed connectivity, and attentive round-the-clock service designed for seamless relaxation.'
                  }}
                </p>
              </div>

              <!-- Amenities -->
              <div v-if="room.amenities && room.amenities.length > 0">
                <h3 class="text-lg font-serif font-medium text-white mb-3">Room Amenities</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  <div
                    v-for="amenity in room.amenities"
                    :key="amenity"
                    class="flex items-center gap-2.5 rounded-lg bg-[#182235]/60 border border-white/5 px-3 py-2 text-sm text-slate-200"
                  >
                    <span class="text-[#D6A94F] text-xs">◆</span>
                    <span>{{ amenity }}</span>
                  </div>
                </div>
              </div>

              <!-- Footer CTA -->
              <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-white/10">
                <div>
                  <div class="text-xs text-slate-400 uppercase tracking-wider">Nightly Stay</div>
                  <div class="text-2xl font-serif text-[#D6A94F] font-semibold">{{ formattedPrice }}</div>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                  <button
                    @click="closeDialog"
                    class="flex-1 sm:flex-none px-6 py-3 rounded-lg border border-white/20 text-sm font-medium text-slate-300 hover:bg-white/5 transition"
                  >
                    Close
                  </button>
                  <button
                    @click="handleReserve"
                    :disabled="!room.available"
                    class="flex-1 sm:flex-none px-8 py-3 rounded-lg bg-[#D6A94F] hover:bg-[#C59B3F] disabled:bg-slate-700 disabled:cursor-not-allowed text-[#101827] font-semibold text-sm transition shadow-lg shadow-[#D6A94F]/20"
                  >
                    Reserve This Suite
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <BookingModal
      v-if="showBookingModal"
      :isOpen="showBookingModal"
      :room="roomForModal"
      @close="showBookingModal = false"
    />
  </Teleport>
</template>
