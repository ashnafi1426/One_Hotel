<script setup lang="ts">
import { computed } from 'vue'
import { Image, Bed, Zap, UtensilsCrossed, Trees } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const languageStore = useLanguageStore()

const categories = computed(() => [
  {
    key: 'All',
    name: languageStore.t('all', 'All'),
    icon: Image,
    description: languageStore.t('view_all', 'View All'),
  },
  {
    key: 'Rooms',
    name: languageStore.t('rooms', 'Rooms'),
    icon: Bed,
    description: languageStore.t('luxury_rooms', 'Luxury Rooms'),
  },
  {
    key: 'Facilities',
    name: languageStore.t('facilities', 'Facilities'),
    icon: Zap,
    description: languageStore.t('all_facilities', 'All Facilities'),
  },
  {
    key: 'Restaurant',
    name: languageStore.t('restaurant', 'Restaurant'),
    icon: UtensilsCrossed,
    description: languageStore.t('fine_dining', 'Fine Dining'),
  },
  {
    key: 'Outdoor',
    name: languageStore.t('outdoor', 'Outdoor'),
    icon: Trees,
    description: languageStore.t('outdoor_spaces', 'Outdoor Spaces'),
  },
])
</script>

<template>
  <section
    class="relative py-16 bg-[#F7F7F5] dark:bg-[#0B111D] overflow-hidden transition-colors duration-300 border-b border-slate-200/80 dark:border-[#26354D]/70"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
      <div class="text-center mb-12">
        <h2 class="font-serif text-3xl sm:text-4xl font-bold text-[#101827] dark:text-white">
          {{ languageStore.t('browse_by_category', 'Browse by Gallery Collection') }}
        </h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 font-light">
          {{
            languageStore.t(
              'browse_by_category_desc',
              'Discover every corner of our luxury property.',
            )
          }}
        </p>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-5">
        <button
          v-for="category in categories"
          :key="category.key"
          @click="emit('update:modelValue', category.key)"
          class="group relative h-36 rounded-2xl overflow-hidden transition-all duration-300 bg-white dark:bg-[#182235] border border-slate-200/90 dark:border-[#26354D] cursor-pointer"
          :class="
            modelValue === category.key
              ? 'ring-2 ring-[#D6A94F] border-[#D6A94F] shadow-lg bg-[#FAF3E7] dark:bg-[#182235]'
              : 'shadow-sm hover:shadow-md hover:border-[#D6A94F]/40'
          "
        >
          <div class="absolute inset-0 flex flex-col items-center justify-center p-3 space-y-2">
            <div
              class="transition-all duration-300"
              :class="
                modelValue === category.key
                  ? 'text-[#D6A94F] w-10 h-10'
                  : 'text-slate-500 dark:text-slate-400 w-9 h-9 group-hover:text-[#D6A94F]'
              "
            >
              <component :is="category.icon" class="w-full h-full" stroke-width="1.5" />
            </div>

            <h3
              class="font-serif text-base font-bold transition-colors duration-300"
              :class="
                modelValue === category.key
                  ? 'text-[#8A641A] dark:text-[#D6A94F]'
                  : 'text-[#101827] dark:text-slate-200 group-hover:text-[#D6A94F]'
              "
            >
              {{ category.name }}
            </h3>

            <p
              class="text-[11px] font-light transition-colors duration-300"
              :class="
                modelValue === category.key
                  ? 'text-[#8A641A]/80 dark:text-[#D6A94F]/80'
                  : 'text-slate-400 group-hover:text-[#D6A94F]'
              "
            >
              {{ category.description }}
            </p>
          </div>
        </button>
      </div>
    </div>
  </section>
</template>
