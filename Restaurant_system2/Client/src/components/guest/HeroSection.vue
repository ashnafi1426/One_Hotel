<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { Calendar, BedDouble, MapPin, Clock, ShieldCheck, Sparkles } from 'lucide-vue-next'
import BookingSearchForm from './BookingSearchForm.vue'

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
  router.push('/rooms')
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
    class="relative min-h-[92vh] flex flex-col justify-between overflow-hidden bg-[#101827] text-white pt-24 sm:pt-28 pb-10 sm:pb-14"
  >
    <!-- Background Hotel Visual with Luxury Dark Navy Overlays -->
    <div class="absolute inset-0 z-0">
      <img
        src="/images/hero/hero.jpg"
        :alt="guestHotelStore.hotelName"
        class="h-full w-full object-cover scale-100 transition-transform duration-1000 brightness-90"
        @error="(e: any) => (e.target.src = 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1920&h=1080&fit=crop')"
      />

      <!-- Multi-stop luxury dark navy vignettes for maximum contrast and elegance -->
      <div
        class="absolute inset-0 bg-gradient-to-t from-[#101827] via-[#101827]/70 to-[#101827]/40"
      />
      <div
        class="absolute inset-0 bg-gradient-to-r from-[#101827]/85 via-transparent to-[#101827]/75"
      />
    </div>

    <!-- Center Hero Content -->
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6 sm:space-y-8 my-auto">
      <!-- Luxury Hotel Badge -->
      <div class="inline-flex items-center gap-2.5 px-4 sm:px-5 py-2 rounded-full bg-[#182235]/85 text-[#D6A94F] border border-[#D6A94F]/40 text-[10px] sm:text-xs font-bold uppercase tracking-[0.25em] backdrop-blur-md shadow-xl">
        <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
        <span>{{ guestHotelStore.hotelName }} • {{ guestHotelStore.hotelCity }}</span>
      </div>

      <!-- Main Heading in Luxury Serif Font -->
      <h1
        class="font-serif text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-bold text-white tracking-tight leading-[1.12] max-w-4xl mx-auto drop-shadow-lg"
      >
        {{ languageStore.t('hero_title_part1', 'A Sanctuary of Refined Luxury &') }}
        <span class="italic font-normal text-[#D6A94F] block sm:inline">
          {{ languageStore.t('hero_title_part2', 'Timeless Elegance') }}
        </span>
      </h1>

      <!-- Supporting description -->
      <p
        class="text-sm sm:text-base md:text-lg text-slate-200/90 font-light max-w-2xl mx-auto leading-relaxed drop-shadow-sm font-sans"
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

      <!-- Primary & Secondary CTA Buttons -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 sm:gap-4 pt-2">
        <button
          @click="bookNow"
          class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black text-xs uppercase tracking-widest shadow-xl shadow-[#D6A94F]/25 hover:shadow-2xl hover:shadow-[#D6A94F]/35 transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer active:scale-95"
        >
          <Calendar class="w-4 h-4 text-[#101827]" />
          <span>{{ languageStore.t('book_your_stay', 'Book Your Stay') }}</span>
        </button>

        <button
          @click="exploreRooms"
          class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-[#182235]/80 hover:bg-[#182235] text-white font-bold text-xs uppercase tracking-widest border border-[#26354D] hover:border-[#D6A94F]/60 shadow-lg backdrop-blur-md transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer active:scale-95"
        >
          <BedDouble class="w-4 h-4 text-[#D6A94F]" />
          <span>{{ languageStore.t('explore_rooms', 'Explore Suites') }}</span>
        </button>
      </div>

      <!-- Quick Hotel Operational Highlights -->
      <div class="pt-6 sm:pt-8 grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4 max-w-3xl mx-auto text-left">
        <div class="p-3 sm:p-3.5 rounded-2xl bg-[#182235]/75 border border-[#26354D]/80 backdrop-blur-md space-y-1">
          <p class="text-[9px] font-extrabold uppercase text-[#D6A94F] tracking-wider flex items-center gap-1.5">
            <Clock class="w-3 h-3 text-[#D6A94F]" />
            {{ languageStore.t('check_in', 'Check-In') }}
          </p>
          <p class="text-xs font-bold text-white truncate">
            {{ guestHotelStore.currentHotel?.front_desk_hours || '02:00 PM Daily' }}
          </p>
        </div>

        <div class="p-3 sm:p-3.5 rounded-2xl bg-[#182235]/75 border border-[#26354D]/80 backdrop-blur-md space-y-1">
          <p class="text-[9px] font-extrabold uppercase text-[#D6A94F] tracking-wider flex items-center gap-1.5">
            <MapPin class="w-3 h-3 text-[#D6A94F]" />
            {{ languageStore.t('location', 'Location') }}
          </p>
          <p class="text-xs font-bold text-white truncate" :title="heroLocation">
            {{ heroLocation }}
          </p>
        </div>

        <div class="col-span-2 md:col-span-1 p-3 sm:p-3.5 rounded-2xl bg-[#182235]/75 border border-[#26354D]/80 backdrop-blur-md space-y-1">
          <p class="text-[9px] font-extrabold uppercase text-[#D6A94F] tracking-wider flex items-center gap-1.5">
            <ShieldCheck class="w-3 h-3 text-[#D6A94F]" />
            {{ languageStore.t('concierge', 'Concierge Service') }}
          </p>
          <p class="text-xs font-bold text-white truncate">
            {{ languageStore.t('concierge_service', '24/7 Dedicated Care') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Booking Search Interface docked at bottom of Hero -->
    <div class="relative z-20 mt-8 sm:mt-12">
      <BookingSearchForm variant="hero" />
    </div>
  </section>
</template>
