<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { Calendar, BedDouble, Sparkles } from 'lucide-vue-next'

const router = useRouter()
const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

function bookNow() {
  router.push('/rooms')
}

function exploreRooms() {
  const target =
    document.getElementById('featured-rooms') || document.getElementById('rooms-section')

  if (target) {
    target.scrollIntoView({ behavior: 'smooth' })
  } else {
    router.push('/rooms')
  }
}
</script>

<template>
  <section
    class="relative w-full min-h-[90vh] md:min-h-screen flex flex-col justify-center items-center text-center px-4 md:px-8 py-16 md:py-24 overflow-hidden bg-slate-950 text-white"
  >
    <!-- Background Image (z-0) -->
    <div class="absolute inset-0 z-0">
      <picture class="block h-full w-full">
        <source srcset="/images/rooms/image.webp" type="image/webp" />
        <img
          src="/images/rooms/image.png"
          :alt="`${guestHotelStore.hotelName} hotel exterior`"
          class="absolute inset-0 w-full h-full object-cover object-center"
          loading="eager"
          fetchpriority="high"
          decoding="async"
          width="1920"
          height="1080"
          @error="
            (e: Event) => {
              const img = e.target as HTMLImageElement
              if (!img.dataset.fallback) {
                img.dataset.fallback = 'true'
                img.src = '/images/gallery/executive-suite.jpg'
              }
            }
          "
        />
      </picture>
    </div>

    <!-- Calibrated Luxury Scrim Overlay Gradient (z-[5]) -->
    <div
      class="absolute inset-0 z-[5] bg-gradient-to-b from-slate-950/70 via-slate-900/40 to-slate-950/80 pointer-events-none"
    />

    <!-- Header Badge Top Pill (relative z-10) -->
    <div
      class="relative z-10 inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-900/60 border border-slate-700/60 backdrop-blur-md mb-6 select-none shadow-md"
    >
      <Sparkles class="w-3.5 h-3.5 text-amber-400 shrink-0" />
      <span class="text-white text-xs tracking-[0.22em] font-semibold uppercase">
        {{ languageStore.isAmharic ? 'ኤክስኪዩቲቭ ሆቴል • አዲስ አበባ' : 'EXECUTIVE HOTEL • ADDIS ABABA' }}
      </span>
    </div>

    <!-- Main Headline: Serif + Italic Cursive Accent (relative z-10) -->
    <h1
      class="relative z-10 font-serif text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-medium tracking-tight text-white leading-[1.15] max-w-4xl mx-auto drop-shadow-lg text-balance"
    >
      <span class="block">
        {{ languageStore.isAmharic ? 'ዘመን የማይሽረው ቅንጦት' : 'Timeless Luxury' }}
      </span>
      <span class="block mt-1 sm:mt-2">
        <span v-if="!languageStore.isAmharic" class="italic font-normal font-serif text-[#C89B3C]">&amp; </span>
        {{ languageStore.isAmharic ? 'ተወዳዳሪ የሌለው ምቾት' : 'Unmatched Comfort' }}
      </span>
    </h1>

    <!-- Subtitle (relative z-10) -->
    <p
      class="relative z-10 font-sans text-sm sm:text-base md:text-lg text-slate-300 tracking-wider font-light mt-4 mb-8 max-w-2xl mx-auto px-2"
    >
      {{ languageStore.isAmharic ? 'የቅንጦት ሆቴል እና ሬስቶራንት ተሞክሮ' : 'Luxury Hotel & Restaurant Experience' }}
    </p>

    <!-- Call-to-Action (CTA) Buttons (relative z-10) -->
    <div
      class="relative z-10 flex flex-col sm:flex-row items-center justify-center gap-4 w-full max-w-md mx-auto"
    >
      <!-- Primary CTA: BOOK YOUR STAY -->
      <button
        type="button"
        @click="bookNow"
        class="w-full sm:w-auto rounded-full px-7 py-3.5 bg-[#C89B3C] hover:bg-[#B3882F] text-slate-950 font-semibold shadow-lg transition-transform hover:scale-[1.02] text-xs md:text-sm tracking-widest uppercase flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98]"
      >
        <Calendar class="w-4 h-4 shrink-0 text-slate-950" />
        <span>{{ languageStore.t('book_your_stay', 'BOOK YOUR STAY') }}</span>
      </button>

      <!-- Secondary CTA: EXPLORE ROOMS & SUITES -->
      <button
        type="button"
        @click="exploreRooms"
        class="w-full sm:w-auto rounded-full px-7 py-3.5 bg-slate-900/50 hover:bg-slate-800/70 text-white border border-slate-600/60 backdrop-blur-md shadow-md transition-all hover:scale-[1.02] text-xs md:text-sm tracking-widest uppercase flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98]"
      >
        <BedDouble class="w-4 h-4 shrink-0 text-[#C89B3C]" />
        <span>{{ languageStore.t('explore_rooms_suites', 'EXPLORE ROOMS & SUITES') }}</span>
      </button>
    </div>
  </section>
</template>
