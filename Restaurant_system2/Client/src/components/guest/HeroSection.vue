<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { Calendar, BedDouble, MapPin, Clock, ShieldCheck, Sparkles } from 'lucide-vue-next'

const router = useRouter()
const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

const heroLocation = computed(() => {
  if (guestHotelStore.currentHotel?.address) {
    return guestHotelStore.currentHotel.address
  }
  return `${guestHotelStore.hotelCity}, ${guestHotelStore.hotelCountry}`
})

function bookNow() {
  const bookingForm = document.getElementById('booking-search-section')
  if (bookingForm) {
    bookingForm.scrollIntoView({ behavior: 'smooth' })
  } else {
    router.push('/rooms')
  }
}

function exploreRooms() {
  const target = document.getElementById('featured-rooms') || document.getElementById('rooms-section')
  if (target) {
    target.scrollIntoView({ behavior: 'smooth' })
  } else {
    router.push('/rooms')
  }
}
</script>

<template>
  <section
    class="relative min-h-[88vh] lg:min-h-[92vh] flex flex-col justify-between overflow-hidden bg-[#101827] text-white pt-10 sm:pt-14 pb-24 sm:pb-28 lg:pb-32"
  >
    <!-- Background Hotel Visual with Balanced Directional & Radial Overlay -->
    <div class="absolute inset-0 z-0">
      <img
        src="/images/rooms/image.png"
        :alt="guestHotelStore.hotelName"
        class="h-full w-full object-cover object-[center_60%] sm:object-[center_55%] lg:object-center brightness-105 transition-transform duration-1000"
        @error="(e: any) => (e.target.src = 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1920&h=1080&fit=crop')"
      />

      <!-- Directional gradient ensuring high text contrast while letting illuminated entrance & fountains shine -->
      <div
        class="absolute inset-0"
        style="background: linear-gradient(180deg, rgba(16, 24, 39, 0.72) 0%, rgba(16, 24, 39, 0.42) 48%, rgba(16, 24, 39, 0.68) 100%);"
      />
      <!-- Radial vignette focused behind headline for a quiet text backdrop -->
      <div
        class="absolute inset-0 pointer-events-none"
        style="background: radial-gradient(ellipse at 50% 36%, rgba(16, 24, 39, 0.68) 0%, rgba(16, 24, 39, 0.3) 60%, transparent 100%);"
      />
    </div>

    <!-- Center Hero Main Content Area -->
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center my-auto w-full pt-4">
      <!-- Luxury Hotel Eyebrow Badge -->
      <div
        class="inline-flex items-center gap-2 px-4 sm:px-5 py-1.5 sm:py-2 rounded-full bg-[#192438]/90 text-white border border-[#D6A746]/50 text-[11px] sm:text-xs font-bold uppercase tracking-[0.08em] backdrop-blur-md shadow-xl"
      >
        <Sparkles class="w-3.5 h-3.5 text-[#D6A746]" />
        <span class="text-white font-bold">{{ guestHotelStore.hotelName }} • {{ (guestHotelStore.hotelCity || 'ADDIS ABABA').toUpperCase().replace('ADDISS', 'ADDIS') }}</span>
      </div>

      <!-- Main Headline with Clear Visual Hierarchy (White Dominates, Gold Italic Complements) -->
      <h1
        class="max-w-3xl mx-auto drop-shadow-md text-balance mt-4 sm:mt-5"
      >
        <span class="block font-serif font-bold text-white text-3xl sm:text-5xl lg:text-6xl tracking-tight leading-[1.12]">
          {{ languageStore.t('hero_title_part1', 'Timeless Luxury') }}
        </span>
        <span class="block font-serif italic font-normal text-[#D6A746] text-xl sm:text-3xl lg:text-4xl mt-1.5 sm:mt-2.5 tracking-normal">
          &amp; {{ languageStore.t('hero_title_part2', 'Unmatched Comfort') }}
        </span>
      </h1>

      <!-- Supporting Subtitle with Balanced Width and Clear Readability -->
      <p
        class="text-slate-100/90 font-normal max-w-xl sm:max-w-2xl mx-auto leading-relaxed font-sans text-sm sm:text-base lg:text-[17px] mt-3 sm:mt-4"
      >
        {{
          guestHotelStore.currentHotel?.description ||
          languageStore.t(
            'hero_desc',
            'Immerse yourself in world-class 5-star hospitality, master suites, signature gastronomy, and bespoke concierge care in the historic heart of ' +
              guestHotelStore.hotelCity +
              '.',
          )
        }}
      </p>

      <!-- Primary & Secondary CTA Buttons with Refined Sizing, Padding and Focus States -->
      <div
        class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 sm:gap-4 mt-6 sm:mt-7 w-full max-w-xs sm:max-w-none mx-auto"
      >
        <!-- Primary Action: Book Your Stay -->
        <button
          @click="bookNow"
          type="button"
          class="w-full sm:w-auto min-h-[48px] px-8 py-3.5 rounded-full bg-[#D6A746] hover:bg-[#C59B3F] text-[#192438] font-bold text-xs sm:text-sm uppercase tracking-[0.08em] shadow-lg shadow-[#D6A746]/25 hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        >
          <Calendar class="w-4 h-4 text-[#192438]" />
          <span class="text-[#192438] font-bold">{{ languageStore.t('book_your_stay', 'Book Your Stay') }}</span>
        </button>

        <!-- Secondary Action: Explore Rooms & Suites -->
        <button
          @click="exploreRooms"
          type="button"
          class="w-full sm:w-auto min-h-[48px] px-8 py-3.5 rounded-full bg-[#192438]/80 hover:bg-[#192438] text-white font-semibold text-xs sm:text-sm uppercase tracking-[0.08em] border border-white/30 hover:border-[#D6A746] shadow-md backdrop-blur-sm transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D6A746]"
        >
          <BedDouble class="w-4 h-4 text-[#D6A746]" />
          <span class="text-white font-semibold">{{ languageStore.t('explore_rooms_suites', 'Explore Rooms & Suites') }}</span>
        </button>
      </div>

      <!-- 3 Operational Highlight Cards with Compact Sizing and Consistent Alignment -->
      <div
        class="mt-8 sm:mt-10 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 max-w-3xl mx-auto text-left w-full"
      >
        <!-- Check-in Card -->
        <div
          class="px-4 py-3 rounded-xl bg-[#192438]/90 border border-white/15 backdrop-blur-md space-y-1 flex flex-col justify-center shadow-lg text-white transition-transform duration-200 hover:-translate-y-0.5"
        >
          <p class="text-[10px] sm:text-[11px] font-bold uppercase text-[#D6A746] tracking-[0.08em] flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-[#D6A746] shrink-0" />
            <span class="text-[#D6A746] font-bold">{{ languageStore.t('check_in', 'Check-In') }}</span>
          </p>
          <p class="text-xs sm:text-sm font-semibold text-white">
            {{ guestHotelStore.currentHotel?.front_desk_hours || 'Open 24/7 / 365 Days' }}
          </p>
        </div>

        <!-- Location Card -->
        <div
          class="px-4 py-3 rounded-xl bg-[#192438]/90 border border-white/15 backdrop-blur-md space-y-1 flex flex-col justify-center shadow-lg text-white transition-transform duration-200 hover:-translate-y-0.5"
        >
          <p class="text-[10px] sm:text-[11px] font-bold uppercase text-[#D6A746] tracking-[0.08em] flex items-center gap-1.5">
            <MapPin class="w-3.5 h-3.5 text-[#D6A746] shrink-0" />
            <span class="text-[#D6A746] font-bold">{{ languageStore.t('location', 'Location') }}</span>
          </p>
          <p class="text-xs sm:text-sm font-semibold text-white truncate" :title="heroLocation">
            {{ heroLocation }}
          </p>
        </div>

        <!-- Concierge Card -->
        <div
          class="px-4 py-3 rounded-xl bg-[#192438]/90 border border-white/15 backdrop-blur-md space-y-1 flex flex-col justify-center shadow-lg text-white transition-transform duration-200 hover:-translate-y-0.5"
        >
          <p class="text-[10px] sm:text-[11px] font-bold uppercase text-[#D6A746] tracking-[0.08em] flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-[#D6A746] shrink-0" />
            <span class="text-[#D6A746] font-bold">{{ languageStore.t('concierge', 'Concierge Service') }}</span>
          </p>
          <p class="text-xs sm:text-sm font-semibold text-white truncate">
            {{ languageStore.t('concierge_service', '24/7 Dedicated Care') }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
