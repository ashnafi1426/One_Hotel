<script setup lang="ts">
import { Bed, Droplet, Tv, Wifi, Sparkles, ChevronDown } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

const exploreRooms = () => {
  const roomsSection = document.getElementById('rooms-section')
  if (roomsSection) {
    roomsSection.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <section
    class="relative min-h-[480px] max-h-[70vh] lg:min-h-[520px] lg:max-h-[70vh] flex flex-col justify-center overflow-hidden bg-[#101827] text-white font-sans pt-10 sm:pt-14 pb-20 sm:pb-24"
  >
    <!-- Background photography with brightened imagery & lighter overlay gradient -->
    <div class="absolute inset-0 z-0">
      <img
        src="/images/rooms/suite.png"
        :alt="guestHotelStore.hotelName + ' Luxury Suite'"
        class="h-full w-full object-cover scale-100 transition-all duration-1000 brightness-105"
        @error="(e: any) => (e.target.src = '/images/rooms/image.png')"
      />

      <!-- Refined Overlay Gradient from rgba(10,20,40,0.35) to rgba(10,20,40,0.65) -->
      <div
        class="absolute inset-0"
        style="background: linear-gradient(180deg, rgba(10, 20, 40, 0.35) 0%, rgba(10, 20, 40, 0.65) 100%);"
      />
    </div>

    <!-- Center Hero Content Area -->
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center space-y-4 sm:space-y-5 my-auto w-full">
      <!-- Luxury Hotel Badge - Consistent Executive Hotel Addis Ababa Branding -->
      <div
        class="inline-flex items-center gap-2 px-4 sm:px-5 py-2 rounded-full bg-[#182235]/90 text-white border border-[#D6A94F]/50 text-[11px] sm:text-xs font-bold uppercase tracking-[0.08em] backdrop-blur-md shadow-xl"
      >
        <Sparkles class="w-3.5 h-3.5 text-[#F5BD47]" />
        <span class="text-white font-bold">{{ guestHotelStore.hotelName }} • {{ (guestHotelStore.hotelCity || 'ADDIS ABABA').toUpperCase().replace('ADDISS', 'ADDIS') }}</span>
      </div>

      <!-- Main Heading in Luxury Serif Font -->
      <h1
        class="font-serif text-3xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight leading-[1.1] max-w-4xl mx-auto drop-shadow-lg"
      >
        {{ languageStore.t('discover_luxury_rooms', 'Discover Our Signature Suites') }}
      </h1>

      <!-- Highly readable paragraph: font-weight 400+, normal word spacing, max-width 720px, soft text shadow -->
      <p
        class="text-sm sm:text-base lg:text-lg text-white/95 font-medium max-w-[720px] mx-auto leading-relaxed"
        style="text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7); word-spacing: normal;"
      >
        {{
          languageStore.t(
            'discover_rooms_desc',
            'Experience tailored comfort and timeless elegance at ' +
              guestHotelStore.hotelName +
              ' in ' +
              guestHotelStore.hotelCity +
              '. Each suite is designed with artisanal detail, premium beds, and picturesque city views.',
          )
        }}
      </p>

      <!-- Key Amenity Highlights: CSS Grid with Identical Height & Width Cards -->
      <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 max-w-4xl mx-auto pt-2 w-full">
        <!-- King Beds -->
        <div
          class="h-14 sm:h-16 px-4 rounded-[16px] bg-[#182235]/95 border border-white/20 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md transition-all duration-200"
        >
          <Bed class="w-5 h-5 text-[#F5BD47] shrink-0" />
          <span class="text-xs sm:text-sm font-semibold text-white whitespace-nowrap">{{ languageStore.t('luxury_beds', 'King Feather Beds') }}</span>
        </div>

        <!-- Spa Bath -->
        <div
          class="h-14 sm:h-16 px-4 rounded-[16px] bg-[#182235]/95 border border-white/20 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md transition-all duration-200"
        >
          <Droplet class="w-5 h-5 text-[#F5BD47] shrink-0" />
          <span class="text-xs sm:text-sm font-semibold text-white whitespace-nowrap">{{ languageStore.t('modern_bath', 'Marble Spa Bath') }}</span>
        </div>

        <!-- Smart TV -->
        <div
          class="h-14 sm:h-16 px-4 rounded-[16px] bg-[#182235]/95 border border-white/20 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md transition-all duration-200"
        >
          <Tv class="w-5 h-5 text-[#F5BD47] shrink-0" />
          <span class="text-xs sm:text-sm font-semibold text-white whitespace-nowrap">{{ languageStore.t('smart_tv', 'Smart 4K TV') }}</span>
        </div>

        <!-- Free Wi-Fi with non-breaking hyphen to prevent awkward 'Wi-' / 'Fi' line wraps -->
        <div
          class="h-14 sm:h-16 px-4 rounded-[16px] bg-[#182235]/95 border border-white/20 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md transition-all duration-200"
        >
          <Wifi class="w-5 h-5 text-[#F5BD47] shrink-0" />
          <span class="text-xs sm:text-sm font-semibold text-white whitespace-nowrap">Free Wi&#8209;Fi</span>
        </div>
      </div>

      <!-- Larger Explore Available Suites Link with Smooth Scroll -->
      <div class="pt-3">
        <button
          @click="exploreRooms"
          type="button"
          class="inline-flex flex-col items-center group cursor-pointer select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F5BD47] rounded-lg px-3 py-1"
          aria-label="Scroll down to explore available suites"
        >
          <span class="text-xs sm:text-sm uppercase font-bold tracking-[0.12em] text-[#F5BD47] group-hover:text-white transition-colors">
            Explore Available Suites
          </span>
          <ChevronDown class="w-4 h-4 text-[#F5BD47] group-hover:text-white mt-1 animate-bounce transition-colors" />
        </button>
      </div>
    </div>
  </section>
</template>
