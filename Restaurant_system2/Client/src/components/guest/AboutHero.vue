<script setup lang="ts">
import { computed } from 'vue'
import { Target, Globe, Heart, ShieldCheck, Sparkles } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Value {
  icon: any
  title: string
  description: string
}

const values = computed<Value[]>(() => [
  {
    icon: Target,
    title: languageStore.t('our_mission', 'Our Mission'),
    description: languageStore.t(
      'our_mission_desc',
      'To provide exceptional hospitality at ' +
        guestHotelStore.hotelName +
        ' through luxurious accommodation, personalized service, and memorable experiences for every guest.',
    ),
  },
  {
    icon: Globe,
    title: languageStore.t('our_vision', 'Our Vision'),
    description: languageStore.t(
      'our_vision_desc',
      'To be recognized as the premier luxury hospitality destination in ' +
        guestHotelStore.hotelCity +
        ', known for excellence, innovation, and warm hospitality.',
    ),
  },
  {
    icon: Heart,
    title: languageStore.t('core_values', 'Core Values'),
    description: languageStore.t(
      'core_values_desc',
      'Integrity, Excellence, Respect, Innovation, Teamwork, and Customer Satisfaction guide everything we do every day.',
    ),
  },
])

const commitments = computed(() => [
  languageStore.t('Luxury Accommodation', 'Luxury Accommodation'),
  languageStore.t('24/7 Professional Service', '24/7 Professional Service'),
  languageStore.t('Guest Satisfaction Guaranteed', 'Guest Satisfaction Guaranteed'),
  languageStore.t('Environmental Sustainability', 'Environmental Sustainability'),
  languageStore.t('Innovation & Smart Technology', 'Innovation & Smart Technology'),
  languageStore.t('Safe & Secure Environment', 'Safe & Secure Environment'),
])
</script>

<template>
  <section
    class="bg-[#F7F7F5] dark:bg-[#0B111D] py-16 sm:py-20 lg:py-28 transition-colors duration-300 font-sans border-b border-slate-200/80 dark:border-[#26354D]/70 pt-28"
  >
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <div
          class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-[0.2em] bg-[#D6A94F]/10 text-[#8A641A] dark:text-[#D6A94F] border border-[#D6A94F]/30"
        >
          <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
          <span
            >{{ guestHotelStore.hotelName }} •
            {{ languageStore.t('our_philosophy', 'Our Philosophy') }}</span
          >
        </div>

        <h1
          class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-[#101827] dark:text-white tracking-tight"
        >
          {{ languageStore.t('mission_vision_values', 'Mission, Vision & Principles') }}
        </h1>

        <p
          class="text-sm sm:text-base leading-relaxed text-slate-600 dark:text-slate-400 font-light"
        >
          {{
            languageStore.t(
              'about_hero_desc',
              'At ' +
                guestHotelStore.hotelName +
                ', everything we do is guided by a commitment to excellence, genuine hospitality, and creating unforgettable experiences for every guest.',
            )
          }}
        </p>
      </div>

      <div class="mt-12 grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="item in values"
          :key="item.title"
          class="rounded-3xl bg-white dark:bg-[#182235] border border-slate-200/90 dark:border-[#26354D] p-6 sm:p-8 shadow-sm hover:shadow-xl hover:border-[#D6A94F]/50 transition duration-300 flex flex-col justify-between"
        >
          <div class="space-y-4">
            <div
              class="w-14 h-14 rounded-2xl bg-[#D6A94F]/10 text-[#D6A94F] border border-[#D6A94F]/30 flex items-center justify-center"
            >
              <component :is="item.icon" class="w-7 h-7" />
            </div>

            <h3 class="font-serif text-xl font-bold text-[#101827] dark:text-white">
              {{ item.title }}
            </h3>

            <p
              class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-light"
            >
              {{ item.description }}
            </p>
          </div>
        </div>
      </div>

      <div
        class="mt-12 rounded-3xl bg-[#101827] border border-[#26354D] p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden"
      >
        <div
          class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent opacity-80"
        />

        <div class="grid gap-8 grid-cols-1 md:grid-cols-2 items-center">
          <div class="space-y-3">
            <span
              class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-[#D6A94F]/20 text-[#D6A94F] border border-[#D6A94F]/30"
            >
              {{ languageStore.t('our_standard', 'Our Standard') }}
            </span>
            <h3 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-white">
              {{
                languageStore.t('uncompromising_quality', 'Uncompromising Quality in Every Detail')
              }}
            </h3>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-light">
              {{
                languageStore.t(
                  'uncompromising_quality_desc',
                  'We pledge to deliver unparalleled standards across all hotel facilities, dining experiences, and personal concierge services.',
                )
              }}
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-semibold">
            <div
              v-for="item in commitments"
              :key="item"
              class="flex items-center gap-2.5 p-3 rounded-2xl bg-[#182235] border border-[#26354D] text-slate-200"
            >
              <ShieldCheck class="w-4 h-4 text-[#D6A94F] flex-shrink-0" />
              <span>{{ item }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
