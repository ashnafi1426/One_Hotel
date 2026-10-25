<script setup lang="ts">
import { computed } from 'vue'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

const milestones = computed(() => [
  {
    year: languageStore.t('establishment', 'Establishment'),
    title: languageStore.t('Foundation & Opening', 'Foundation & Opening'),
    description: languageStore.t(
      'foundation_desc',
      guestHotelStore.hotelName +
        ' was established with a vision to provide world-class hospitality services to both business and leisure travelers in ' +
        guestHotelStore.hotelCity +
        ', combining modern comfort with traditional Ethiopian warmth.',
    ),
  },
  {
    year: languageStore.t('growth', 'Growth'),
    title: languageStore.t('Expansion & Development', 'Expansion & Development'),
    description: languageStore.t(
      'expansion_desc',
      'Expanded our facilities with additional guest rooms, upgraded dining experiences, and modern conference facilities to better serve our growing clientele and host international events.',
    ),
  },
  {
    year: languageStore.t('innovation', 'Innovation'),
    title: languageStore.t('Modernization & Technology', 'Modernization & Technology'),
    description: languageStore.t(
      'modernization_desc',
      'Implemented smart room technology, upgraded WiFi infrastructure, introduced mobile check-in services, and enhanced our digital guest experience while maintaining our commitment to personal service.',
    ),
  },
  {
    year: languageStore.t('today', 'Today'),
    title: languageStore.t('Continuing Excellence', 'Continuing Excellence'),
    description: languageStore.t(
      'today_desc',
      'Today, we continue to set standards for quality hospitality in the region, focusing on guest satisfaction, staff development, sustainable practices, and community engagement.',
    ),
  },
])
</script>

<template>
  <section
    class="bg-white dark:bg-slate-900 py-12 sm:py-16 md:py-20 lg:py-24 border-y border-slate-200 dark:border-slate-800 transition-colors duration-300"
  >
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <div class="mx-auto mb-12 max-w-3xl text-center space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ guestHotelStore.hotelName }} • {{ languageStore.t('our_journey', 'Our Journey') }}
        </span>
        <h2
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('hotel_history_milestones', 'Hotel History & Milestones') }}
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'hotel_history_desc',
              'From our beginnings to becoming a leading luxury destination in ' +
                guestHotelStore.hotelCity +
                ', discover our growth and commitment to excellence.',
            )
          }}
        </p>
      </div>

      <div class="relative">
        <div
          class="absolute left-4 sm:left-1/2 top-0 h-full w-0.5 -translate-x-0.5 sm:-translate-x-1/2 bg-amber-500/40"
        ></div>

        <div class="space-y-8">
          <div v-for="(milestone, index) in milestones" :key="index" class="relative">
            <div
              class="absolute left-0 sm:left-1/2 top-6 h-4 w-4 -translate-x-1.5 sm:-translate-x-1/2 rounded-full border-2 border-white dark:border-slate-900 bg-amber-500 shadow-md"
            ></div>

            <div
              :class="[
                'pl-10 sm:pl-0',
                index % 2 === 0 ? 'sm:mr-1/2 sm:pr-10' : 'sm:ml-1/2 sm:pl-10',
              ]"
            >
              <div
                class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-6 shadow-xs space-y-2"
              >
                <span class="text-xl font-black text-amber-600 dark:text-amber-400 font-mono">{{
                  milestone.year
                }}</span>
                <h4 class="text-lg font-black text-slate-900 dark:text-white">
                  {{ milestone.title }}
                </h4>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                  {{ milestone.description }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
