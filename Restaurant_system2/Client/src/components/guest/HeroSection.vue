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
    <!-- Background Hotel Visual with Lightened Overlay -->
    <div class="absolute inset-0 z-0">
      <img
        src="/images/rooms/image.png"
        :alt="guestHotelStore.hotelName"
        class="h-full w-full object-cover object-[center_35%] sm:object-center brightness-105 transition-transform duration-1000"
        @error="(e: any) => (e.target.src = 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1920&h=1080&fit=crop')"
      />

      <!-- Refined Overlay Gradient from rgba(10,20,40,0.35) to rgba(10,20,40,0.65) -->
      <div
        class="absolute inset-0"
        style="background: linear-gradient(180deg, rgba(10, 20, 40, 0.35) 0%, rgba(10, 20, 40, 0.65) 100%);"
      />
    </div>

    <!-- Center Hero Main Content Area -->
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center my-auto w-full pt-4">
      <!-- Luxury Hotel Eyebrow Badge -->
      <div
        class="inline-flex items-center gap-2 px-4 sm:px-5 py-2 rounded-full bg-[#182235]/90 text-white border border-[#D6A94F]/50 text-[11px] sm:text-xs font-bold uppercase tracking-[0.08em] backdrop-blur-md shadow-xl"
      >
        <Sparkles class="w-3.5 h-3.5 text-[#F5BD47]" />
        <span class="text-white font-bold">{{ guestHotelStore.hotelName }} • {{ (guestHotelStore.hotelCity || 'ADDIS ABABA').toUpperCase().replace('ADDISS', 'ADDIS') }}</span>
      </div>

      <!-- Main Headline with Cleaned Line 1 & Line 2 and Tight 1.1 Line Height -->
      <h1
        class="font-serif font-bold text-white tracking-tight max-w-3xl mx-auto drop-shadow-md text-balance mt-4 sm:mt-5"
        style="font-size: clamp(2rem, 5vw + 0.5rem, 4.25rem); line-height: 1.1;"
      >
        <span class="block text-white drop-shadow-md">{{ languageStore.t('hero_title_part1', 'Timeless Luxury') }}</span>
        <span class="italic font-normal text-[#F5BD47] block mt-1 sm:mt-1.5 drop-shadow-md">
          &amp; {{ languageStore.t('hero_title_part2', 'Unmatched Comfort') }}
        </span>
      </h1>

      <!-- High-Contrast Subtitle with soft text-shadow -->
      <p
        class="text-white/95 font-medium max-w-2xl mx-auto leading-relaxed font-sans mt-3 sm:mt-4"
        style="font-size: clamp(0.95rem, 1.2vw + 0.2rem, 1.15rem); text-shadow: 0 2px 10px rgba(0, 0, 0, 0.75);"
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

      <!-- Primary & Secondary CTA Buttons with Reduced Gap -->
      <div
        class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 sm:gap-4 mt-5 sm:mt-6 w-full max-w-xs sm:max-w-none mx-auto"
      >
        <!-- Primary Hero Action -->
        <button
          @click="bookNow"
          type="button"
          class="w-full sm:w-auto min-h-[48px] sm:min-h-[50px] px-8 py-3.5 rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-extrabold text-xs uppercase tracking-[0.08em] shadow-xl shadow-[#D6A94F]/30 hover:shadow-2xl transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        >
          <Calendar class="w-4 h-4 text-[#101827]" />
          <span class="text-[#101827] font-extrabold">{{ languageStore.t('book_your_stay', 'Book Your Stay') }}</span>
        </button>

        <!-- High-Contrast Secondary Action -->
        <button
          @click="exploreRooms"
          type="button"
          class="w-full sm:w-auto min-h-[48px] sm:min-h-[50px] px-8 py-3.5 rounded-full bg-white/15 hover:bg-white/25 text-white font-bold text-xs uppercase tracking-[0.08em] border-[1.5px] border-white/80 hover:border-[#D6A94F] shadow-lg backdrop-blur-sm transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D6A94F]"
        >
          <BedDouble class="w-4 h-4 text-[#F5BD47]" />
          <span class="text-white font-bold">{{ languageStore.t('explore_rooms_suites', 'Explore Rooms & Suites') }}</span>
        </button>
      </div>

      <!-- 3 Operational Highlight Cards with 16px Radius and No Clipping -->
      <div
        class="mt-8 sm:mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4 max-w-4xl mx-auto text-left w-full"
      >
        <!-- Check-in Card -->
        <div
          class="p-4 rounded-[16px] bg-[#182235]/95 border border-white/20 backdrop-blur-md space-y-1.5 flex flex-col justify-center shadow-lg text-white"
        >
          <p class="text-[11px] font-bold uppercase text-[#F5BD47] tracking-[0.08em] flex items-center gap-2">
            <Clock class="w-4 h-4 text-[#F5BD47]" />
            <span class="text-[#F5BD47] font-bold">{{ languageStore.t('check_in', 'Check-In') }}</span>
          </p>
          <p class="text-xs sm:text-sm font-semibold text-white">
            {{ guestHotelStore.currentHotel?.front_desk_hours || '02:00 PM Daily' }}
          </p>
        </div>

        <!-- Location Card -->
        <div
          class="p-4 rounded-[16px] bg-[#182235]/95 border border-white/20 backdrop-blur-md space-y-1.5 flex flex-col justify-center shadow-lg text-white"
        >
          <p class="text-[11px] font-bold uppercase text-[#F5BD47] tracking-[0.08em] flex items-center gap-2">
            <MapPin class="w-4 h-4 text-[#F5BD47]" />
            <span class="text-[#F5BD47] font-bold">{{ languageStore.t('location', 'Location') }}</span>
          </p>
          <p class="text-xs sm:text-sm font-semibold text-white truncate" :title="heroLocation">
            {{ heroLocation }}
          </p>
        </div>

        <!-- Concierge Card -->
        <div
          class="sm:col-span-2 lg:col-span-1 p-4 rounded-[16px] bg-[#182235]/95 border border-white/20 backdrop-blur-md space-y-1.5 flex flex-col justify-center shadow-lg text-white"
        >
          <p class="text-[11px] font-bold uppercase text-[#F5BD47] tracking-[0.08em] flex items-center gap-2">
            <ShieldCheck class="w-4 h-4 text-[#F5BD47]" />
            <span class="text-[#F5BD47] font-bold">{{ languageStore.t('concierge', 'Concierge Service') }}</span>
          </p>
          <p class="text-xs sm:text-sm font-semibold text-white truncate">
            {{ languageStore.t('concierge_service', '24/7 Dedicated Care') }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
