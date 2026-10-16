<script setup lang="ts">
import { computed } from 'vue'
import { Search, X, SlidersHorizontal, Users, BedDouble, ArrowUpDown } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useRoomTypeStore } from '@/stores/roomType'

const languageStore = useLanguageStore()
const roomTypeStore = useRoomTypeStore()

const props = withDefaults(
  defineProps<{
    modelValue: string
    type?: string
    capacity?: string
    sort?: string
  }>(),
  {
    type: 'All',
    capacity: 'All',
    sort: 'recommended',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'update:type': [value: string]
  'update:capacity': [value: string]
  'update:sort': [value: string]
  'clear': []
}>()

const hasActiveFilters = computed(() => {
  return props.modelValue !== '' || props.type !== 'All' || props.capacity !== 'All' || props.sort !== 'recommended'
})
</script>

<template>
  <div class="w-full space-y-4">
    <!-- Filter Card in Luxury Dark Navy Glass -->
    <div
      class="bg-[#182235]/95 backdrop-blur-xl border border-[#26354D] rounded-3xl p-4 sm:p-5 shadow-2xl text-white relative overflow-hidden"
    >
      <!-- Top gold hairline -->
      <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent opacity-80" />

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-center">
        <!-- Search Keyword Input -->
        <div class="lg:col-span-4 relative">
          <label for="room-keyword-search" class="sr-only">Search rooms</label>
          <div class="relative flex items-center">
            <Search class="absolute left-3.5 w-4 h-4 text-[#D6A94F] pointer-events-none" />
            <input
              id="room-keyword-search"
              :value="modelValue"
              @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
              type="text"
              :placeholder="languageStore.t('search_rooms_guest_placeholder', 'Search by suite name, number…')"
              class="w-full h-11 pl-10 pr-9 rounded-2xl bg-[#101827] border border-[#26354D] text-white text-xs sm:text-sm font-medium placeholder-slate-400 focus:outline-none focus:border-[#D6A94F] focus:ring-1 focus:ring-[#D6A94F] transition"
              autocomplete="off"
            />
            <button
              v-if="modelValue"
              type="button"
              class="absolute right-3 text-slate-400 hover:text-white transition cursor-pointer"
              @click="$emit('update:modelValue', '')"
              title="Clear search"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Room Type Selector -->
        <div class="lg:col-span-3 relative">
          <label for="room-type-filter" class="sr-only">Filter by Room Type</label>
          <div class="relative flex items-center">
            <BedDouble class="absolute left-3.5 w-4 h-4 text-[#D6A94F] pointer-events-none" />
            <select
              id="room-type-filter"
              :value="type"
              @change="$emit('update:type', ($event.target as HTMLSelectElement).value)"
              class="w-full h-11 pl-10 pr-8 rounded-2xl bg-[#101827] border border-[#26354D] text-white text-xs sm:text-sm font-medium focus:outline-none focus:border-[#D6A94F] focus:ring-1 focus:ring-[#D6A94F] transition cursor-pointer [color-scheme:dark]"
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
        </div>

        <!-- Guest Capacity Selector -->
        <div class="lg:col-span-2 relative">
          <label for="room-capacity-filter" class="sr-only">Filter by Capacity</label>
          <div class="relative flex items-center">
            <Users class="absolute left-3.5 w-4 h-4 text-[#D6A94F] pointer-events-none" />
            <select
              id="room-capacity-filter"
              :value="capacity"
              @change="$emit('update:capacity', ($event.target as HTMLSelectElement).value)"
              class="w-full h-11 pl-10 pr-8 rounded-2xl bg-[#101827] border border-[#26354D] text-white text-xs sm:text-sm font-medium focus:outline-none focus:border-[#D6A94F] focus:ring-1 focus:ring-[#D6A94F] transition cursor-pointer [color-scheme:dark]"
            >
              <option value="All">{{ languageStore.t('all_capacities', 'Any Guests') }}</option>
              <option value="1">1+ {{ languageStore.t('guest_singular', 'Guest') }}</option>
              <option value="2">2+ {{ languageStore.t('guests_plural', 'Guests') }}</option>
              <option value="3">3+ {{ languageStore.t('guests_plural', 'Guests') }}</option>
              <option value="4">4+ {{ languageStore.t('guests_plural', 'Guests') }}</option>
            </select>
          </div>
        </div>

        <!-- Sort By Price -->
        <div class="lg:col-span-2 relative">
          <label for="room-sort-filter" class="sr-only">Sort rooms</label>
          <div class="relative flex items-center">
            <ArrowUpDown class="absolute left-3.5 w-4 h-4 text-[#D6A94F] pointer-events-none" />
            <select
              id="room-sort-filter"
              :value="sort"
              @change="$emit('update:sort', ($event.target as HTMLSelectElement).value)"
              class="w-full h-11 pl-10 pr-8 rounded-2xl bg-[#101827] border border-[#26354D] text-white text-xs sm:text-sm font-medium focus:outline-none focus:border-[#D6A94F] focus:ring-1 focus:ring-[#D6A94F] transition cursor-pointer [color-scheme:dark]"
            >
              <option value="recommended">{{ languageStore.t('recommended', 'Featured') }}</option>
              <option value="price-asc">{{ languageStore.t('price_low_high', 'Price: Low → High') }}</option>
              <option value="price-desc">{{ languageStore.t('price_high_low', 'Price: High → Low') }}</option>
            </select>
          </div>
        </div>

        <!-- Clear Filters button -->
        <div class="lg:col-span-1 flex items-center justify-end">
          <button
            v-if="hasActiveFilters"
            @click="$emit('clear')"
            type="button"
            class="h-11 px-3 w-full rounded-2xl bg-[#101827] hover:bg-slate-800 text-slate-300 hover:text-white border border-[#26354D] hover:border-slate-600 font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
            title="Reset all filters"
          >
            <X class="w-3.5 h-3.5 text-[#D6A94F]" />
            <span class="lg:hidden">{{ languageStore.t('clear_all', 'Reset') }}</span>
          </button>
          <div v-else class="hidden lg:flex items-center justify-center w-full h-11 text-slate-600">
            <SlidersHorizontal class="w-4 h-4" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
