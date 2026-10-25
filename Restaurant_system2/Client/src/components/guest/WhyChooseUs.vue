<script setup lang="ts">
import { computed } from 'vue'
import { Sparkles, UserCheck, UtensilsCrossed, Waves, MapPin, Tag } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Reason {
  id: number
  icon: any
  title: string
  description: string
  color: string
}

const reasons = computed<Reason[]>(() => [
  {
    id: 1,
    icon: Sparkles,
    title: languageStore.t('Comfort & Quality', 'Comfort & Quality'),
    description: languageStore.t(
      'reason_comfort_desc',
      'Well-appointed rooms with comfortable beds, quality linens, modern amenities, and attention to cleanliness that ensures a restful stay.',
    ),
    color: 'text-amber-500 bg-amber-500/10 border-amber-500/20',
  },
  {
    id: 2,
    icon: UserCheck,
    title: languageStore.t('Professional Service', 'Professional Service'),
    description: languageStore.t(
      'reason_service_desc',
      'Our dedicated team provides attentive, friendly service 24/7 to ensure your comfort and address any needs during your stay.',
    ),
    color: 'text-blue-500 bg-blue-500/10 border-blue-500/20',
  },
  {
    id: 3,
    icon: UtensilsCrossed,
    title: languageStore.t('Quality Dining', 'Quality Dining'),
    description: languageStore.t(
      'reason_dining_desc',
      'Enjoy a variety of Ethiopian and international dishes prepared fresh daily by our experienced culinary team.',
    ),
    color: 'text-rose-500 bg-rose-500/10 border-rose-500/20',
  },
  {
    id: 4,
    icon: Waves,
    title: languageStore.t('Modern Facilities', 'Modern Facilities'),
    description: languageStore.t(
      'reason_facilities_desc',
      'Access to conference rooms, business center, high-speed WiFi, and other amenities designed for both business and leisure travelers.',
    ),
    color: 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
  },
  {
    id: 5,
    icon: MapPin,
    title: languageStore.t('Convenient Location', 'Convenient Location'),
    description: languageStore.t(
      'reason_location_desc',
      'Conveniently located in ' +
        guestHotelStore.hotelCity +
        ' with easy access to local businesses, markets, and transportation.',
    ),
    color: 'text-purple-500 bg-purple-500/10 border-purple-500/20',
  },
  {
    id: 6,
    icon: Tag,
    title: languageStore.t('Value for Money', 'Value for Money'),
    description: languageStore.t(
      'reason_value_desc',
      'Competitive rates with complimentary breakfast, WiFi, and parking included to provide excellent value for our guests.',
    ),
    color: 'text-teal-500 bg-teal-500/10 border-teal-500/20',
  },
])
</script>

<template>
  <section
    class="bg-white dark:bg-slate-900 py-12 sm:py-16 md:py-20 lg:py-24 border-t border-slate-200 dark:border-slate-800 transition-colors duration-300"
  >
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-12">
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ guestHotelStore.hotelName }} • {{ languageStore.t('why_choose_us', 'Why Choose Us') }}
        </span>
        <h2
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('what_sets_us_apart', 'What Sets Us Apart') }}
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'why_choose_us_desc',
              'Discover why guests choose ' +
                guestHotelStore.hotelName +
                ' for their luxury hospitality experience in ' +
                guestHotelStore.hotelCity +
                '.',
            )
          }}
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="reason in reasons"
          :key="reason.id"
          class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-6 shadow-xs hover:border-amber-500/40 transition duration-300 space-y-3"
        >
          <div
            :class="['w-12 h-12 rounded-2xl border flex items-center justify-center', reason.color]"
          >
            <component :is="reason.icon" class="w-6 h-6" />
          </div>

          <h3 class="text-lg font-black text-slate-900 dark:text-white">
            {{ reason.title }}
          </h3>

          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
            {{ reason.description }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
