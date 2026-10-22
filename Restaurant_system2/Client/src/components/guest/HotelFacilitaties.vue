<script setup lang="ts">
import { computed } from 'vue'
import {
  Wifi,
  Utensils,
  Waves,
  Sparkles,
  Dumbbell,
  Car,
  ShieldCheck,
  ConciergeBell,
} from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Facility {
  id: number
  icon: any
  title: string
  description: string
}

const facilities = computed<Facility[]>(() => [
  {
    id: 1,
    icon: Wifi,
    title: languageStore.t('free_wifi', 'High-Speed Fiber Wi-Fi'),
    description:
      languageStore.currentLanguage === 'am'
        ? 'በሆቴሉ ውስጥ በሙሉ ፈጣን የፋይበር ኢንተርኔት በነጻ ያግኙ።'
        : 'Stay seamlessly connected throughout all suites, dining lounges, and gardens with high-speed fiber internet.',
  },
  {
    id: 2,
    icon: Utensils,
    title: languageStore.t('signature_dining', 'Signature Fine Dining'),
    description:
      languageStore.currentLanguage === 'am'
        ? 'በተዋጣላቸው ሼፎች ትኩስ ግብዓቶች የተዘጋጁ ምርጥ ምግቦችን ያጣጥሙ።'
        : 'Gourmet cuisine prepared by seasoned culinary masters using fresh locally harvested ingredients.',
  },
  {
    id: 3,
    icon: Waves,
    title: languageStore.currentLanguage === 'am' ? 'የዋና ገንዳ' : 'Infinity Swimming Pool',
    description:
      languageStore.currentLanguage === 'am'
        ? 'ውብ የከተማ እይታ ባለው የዋና ገንዳችን ውስጥ ዘና ይበሉ።'
        : 'Unwind in our heated outdoor infinity pool offering panoramic views of the city skyline.',
  },
  {
    id: 4,
    icon: Sparkles,
    title: languageStore.currentLanguage === 'am' ? 'ስፓ እና ጤና' : 'Thermal Spa & Wellness',
    description:
      languageStore.currentLanguage === 'am'
        ? 'በማሳጅ እና በሳውና አገልግሎቶቻችን ሰውነትዎን ያድሱ።'
        : 'Rejuvenate body and mind with bespoke therapeutic massages, herbal saunas, and relaxation lounges.',
  },
  {
    id: 5,
    icon: Dumbbell,
    title: languageStore.currentLanguage === 'am' ? 'የአካል ብቃት ማዕከል' : 'Modern Fitness Center',
    description:
      languageStore.currentLanguage === 'am'
        ? 'ዘመናዊ መሣሪያዎች የተሟሉለት የጂም ማዕከል 24/7 ክፍት ነው።'
        : 'Fully equipped modern gymnasium with premium cardio gear, free weights, and dedicated trainers open 24/7.',
  },
  {
    id: 6,
    icon: Car,
    title: languageStore.currentLanguage === 'am' ? 'አስተማማኝ የመኪና ማቆሚያ' : 'Secure Valet Parking',
    description:
      languageStore.currentLanguage === 'am'
        ? 'የ24 ሰዓት ጥበቃ ያለው አስተማማኝና ሰፊ የመኪና ማቆሚያ።'
        : 'Spacious secure parking area featuring 24/7 guarded surveillance and professional valet handling.',
  },
  {
    id: 7,
    icon: ShieldCheck,
    title:
      languageStore.currentLanguage === 'am' ? 'የአውሮፕላን ማረፊያ ትራንስፖርት' : 'Luxury Chauffeur Transfer',
    description:
      languageStore.currentLanguage === 'am'
        ? 'ከአውሮፕላን ማረፊያ ነጻ የትራንስፖርት አገልግሎት።'
        : 'Seamless airport executive chauffeur and private shuttle service available upon request.',
  },
  {
    id: 8,
    icon: ConciergeBell,
    title: languageStore.t('concierge_service', '24/7 Private Concierge'),
    description:
      languageStore.currentLanguage === 'am'
        ? 'ጣፋጭ ምግቦችን እና አገልግሎቶችን በማንኛውም ሰዓት በQR ኮድ ይዘዙ።'
        : 'Personal concierge care and instant in-room dining available 24 hours a day directly at your service.',
  },
])
</script>

<template>
  <section
    id="facilities"
    class="py-16 sm:py-20 lg:py-28 border-b border-slate-200/80 dark:border-[#26354D]/70 bg-white dark:bg-[#101827] transition-colors duration-300 font-sans"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-12">
      <!-- Section Header -->
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <div
          class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-[0.2em] bg-[#D6A94F]/10 text-[#8A641A] dark:text-[#D6A94F] border border-[#D6A94F]/30"
        >
          <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
          <span
            >{{ guestHotelStore.hotelName }} •
            {{ languageStore.t('curated_comfort', 'Curated Amenities') }}</span
          >
        </div>

        <h2
          class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-[#101827] dark:text-white tracking-tight"
        >
          {{ languageStore.t('world_class_facilities', 'World-Class Hospitality Facilities') }}
        </h2>

        <p
          class="text-sm sm:text-base text-slate-700 dark:text-slate-300 font-normal leading-relaxed"
        >
          {{
            languageStore.t(
              'facilities_desc',
              'Every space at ' +
                guestHotelStore.hotelName +
                ' is curated to deliver serenity, wellness, and exceptional guest comfort.',
            )
          }}
        </p>
      </div>

      <!-- Facilities Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="facility in facilities"
          :key="facility.id"
          class="group rounded-3xl border border-slate-200/90 dark:border-[#26354D] bg-[#F7F7F5] dark:bg-[#182235] p-7 transition-all duration-300 hover:border-[#D6A94F]/50 hover:shadow-xl space-y-4"
        >
          <div
            class="w-12 h-12 rounded-2xl bg-[#D6A94F]/10 border border-[#D6A94F]/30 flex items-center justify-center text-[#D6A94F] transition-transform duration-300 group-hover:scale-110"
          >
            <component :is="facility.icon" class="w-6 h-6 text-[#D6A94F]" />
          </div>

          <h3
            class="font-serif text-lg font-bold text-[#101827] dark:text-white group-hover:text-[#D6A94F] transition-colors leading-snug"
          >
            {{ facility.title }}
          </h3>

          <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-normal">
            {{ facility.description }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
