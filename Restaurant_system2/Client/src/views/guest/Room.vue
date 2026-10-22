<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useRoomStore } from '@/stores/room'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useLanguageStore } from '@/stores/language'
import RoomHero from '@/components/guest/RoomHero.vue'
import RoomSearchBar from '@/components/guest/RoomSearchBar.vue'
import RoomGrid from '@/components/guest/RoomGrid.vue'
import NoRoomsFound from '@/components/guest/NoRoomsFound.vue'
import RoomPagination from '@/components/guest/RoomPagination.vue'
import RoomCTA from '@/components/guest/RoomCTA.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const route = useRoute()
const roomStore = useRoomStore()
const guestHotelStore = useGuestHotelStore()
const languageStore = useLanguageStore()

const search = ref('')
const selectedType = ref('All')
const selectedCapacity = ref('All')
const sortBy = ref<'recommended' | 'price-asc' | 'price-desc'>('recommended')
const currentPage = ref(1)

const rooms = computed(() => roomStore.rooms)
const loading = computed(() => roomStore.loading)
const error = computed(() => roomStore.error)

const filteredRooms = computed(() => {
  const roomList = Array.isArray(rooms.value) ? [...rooms.value] : []
  const filtered = roomList.filter((room: any) => {
    if (!room) return false

    const roomName = (room.room_name || room.name || `Room ${room.room_number || ''}`).toLowerCase()
    const matchesSearch = roomName.includes((search.value || '').toLowerCase())

    const roomType =
      typeof room.room_type === 'string' ? room.room_type : room.room_type?.name || 'Standard'
    const matchesType =
      selectedType.value === 'All' || roomType.toLowerCase() === selectedType.value.toLowerCase()

    const capacity = room.capacity || room.room_type?.capacity || 2
    const matchesCapacity =
      selectedCapacity.value === 'All' || capacity >= Number(selectedCapacity.value)

    return matchesSearch && matchesType && matchesCapacity
  })

  // Sorting
  if (sortBy.value === 'price-asc') {
    filtered.sort((a, b) => {
      const pa = Number(a.room_type?.base_price_per_night || a.price_per_night || 0)
      const pb = Number(b.room_type?.base_price_per_night || b.price_per_night || 0)
      return pa - pb
    })
  } else if (sortBy.value === 'price-desc') {
    filtered.sort((a, b) => {
      const pa = Number(a.room_type?.base_price_per_night || a.price_per_night || 0)
      const pb = Number(b.room_type?.base_price_per_night || b.price_per_night || 0)
      return pb - pa
    })
  }

  return filtered
})

// Pagination
const itemsPerPage = ref(9)

const paginatedRooms = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredRooms.value.slice(start, end)
})

const pagination = computed(() => {
  const total = filteredRooms.value.length
  const lastPage = Math.ceil(total / itemsPerPage.value) || 1

  return {
    current_page: currentPage.value,
    last_page: lastPage,
    per_page: itemsPerPage.value,
    total: total,
    from: (currentPage.value - 1) * itemsPerPage.value + 1,
    to: Math.min(currentPage.value * itemsPerPage.value, total),
  }
})

// Load rooms from backend
async function loadRooms() {
  try {
    await roomStore.fetchRooms({ page: currentPage.value, per_page: itemsPerPage.value })
  } catch (err) {
    console.error('Failed to load rooms:', err)
  }
}

function loadPage(page: number) {
  currentPage.value = page
  const section = document.getElementById('rooms-section')
  if (section) {
    section.scrollIntoView({ behavior: 'smooth' })
  }
}

function clearFilters() {
  search.value = ''
  selectedType.value = 'All'
  selectedCapacity.value = 'All'
  sortBy.value = 'recommended'
  currentPage.value = 1
}

// Fetch rooms on mount and sync route queries
onMounted(() => {
  if (route.query.type) {
    selectedType.value = String(route.query.type)
  }
  if (route.query.guests) {
    selectedCapacity.value = String(route.query.guests)
  }
  loadRooms()
})

// Reset to page 1 when filters change
watch([search, selectedType, selectedCapacity, sortBy], () => {
  currentPage.value = 1
})

// Re-fetch rooms when guest switches hotel
watch(
  () => guestHotelStore.hotelId,
  () => {
    clearFilters()
    loadRooms()
  },
)
</script>

<template>
  <GuestLayout>
    <div
      class="bg-[#F7F7F5] dark:bg-[#0B111D] min-h-screen text-[#20242B] dark:text-slate-100 transition-colors duration-300 pb-16"
    >
      <RoomHero />

      <!-- Docked Search & Filter Bar with smooth mobile flow and desktop docking -->
      <section class="mx-auto mt-2 sm:-mt-10 lg:-mt-12 max-w-6xl px-4 sm:px-6 relative z-20">
        <RoomSearchBar
          v-model="search"
          :type="selectedType"
          :capacity="selectedCapacity"
          :sort="sortBy"
          @update:type="selectedType = $event"
          @update:capacity="selectedCapacity = $event"
          @update:sort="sortBy = $event as any"
          @clear="clearFilters"
        />
      </section>

      <!-- Loading Skeleton State in Luxury Gold & Navy -->
      <section v-if="loading" class="mx-auto mt-12 max-w-7xl px-6">
        <div class="flex justify-center py-24">
          <div class="flex flex-col items-center gap-4">
            <div class="relative w-12 h-12">
              <div class="w-12 h-12 rounded-full border-2 border-[#D6A94F]/20"></div>
              <div
                class="absolute inset-0 w-12 h-12 rounded-full border-2 border-transparent border-t-[#D6A94F] border-r-[#D6A94F]/60 animate-spin"
              ></div>
            </div>
            <p
              class="text-slate-600 dark:text-slate-300 font-semibold text-xs uppercase tracking-widest"
            >
              {{ languageStore.t('loading_rooms', 'Finding available suites…') }}
            </p>
          </div>
        </div>
      </section>

      <!-- Error State with Luxury Card & Retry -->
      <section v-else-if="error" class="mx-auto mt-12 max-w-2xl px-6">
        <div
          class="rounded-3xl bg-[#182235] border border-[#26354D] p-8 text-center text-white space-y-4 shadow-xl"
        >
          <h3 class="font-serif text-xl font-bold text-white">
            {{ languageStore.t('error_loading_rooms', 'Unable to retrieve rooms') }}
          </h3>
          <p class="text-slate-300 text-sm font-light">{{ error }}</p>
          <button
            @click="loadRooms"
            class="px-6 py-2.5 bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black text-xs uppercase tracking-wider rounded-full transition cursor-pointer"
          >
            {{ languageStore.t('try_again', 'Try Again') }}
          </button>
        </div>
      </section>

      <!-- Room Grid -->
      <section v-else id="rooms-section" class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
        <RoomGrid v-if="paginatedRooms.length" :rooms="paginatedRooms" />
        <NoRoomsFound v-else @clear-filters="clearFilters" />
      </section>

      <!-- Pagination -->
      <section
        v-if="paginatedRooms.length && pagination.last_page > 1"
        class="mx-auto mt-12 max-w-7xl px-4 sm:px-6 lg:px-8"
      >
        <RoomPagination :meta="pagination" @change-page="loadPage" />
      </section>

      <!-- CTA Callout -->
      <section class="mx-auto mt-20 max-w-7xl px-4 sm:px-6 lg:px-8">
        <RoomCTA />
      </section>
    </div>
  </GuestLayout>
</template>
