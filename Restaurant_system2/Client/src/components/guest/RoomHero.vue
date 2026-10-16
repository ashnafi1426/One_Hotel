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
    class="relative min-h-[65vh] sm:min-h-[70vh] flex items-center justify-center overflow-hidden bg-[#101827] text-white font-sans pt-24 pb-20"
  >
    <!-- Background photography with luxury dark navy vignettes -->
    <div class="absolute inset-0 z-0">
      <img
        src="/images/rooms/suite.jpg"
        :alt="guestHotelStore.hotelName + ' Luxury Suite'"
        class="h-full w-full object-cover scale-100 transition-all duration-1000 brightness-90"
        @error="(e: any) => (e.target.src = '/images/hero/hero.jpg')"
      />

      <div
        class="absolute inset-0 bg-gradient-to-t from-[#101827] via-[#101827]/70 to-[#101827]/40"
      />
      <div
        class="absolute inset-0 bg-gradient-to-r from-[#101827]/80 via-transparent to-[#101827]/80"
      />
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center space-y-6">
      <!-- Luxury Hotel Badge -->
      <div
        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#182235]/85 text-[#D6A94F] border border-[#D6A94F]/40 text-xs font-bold uppercase tracking-[0.2em] backdrop-blur-md shadow-lg"
      >
        <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
        <span>{{ guestHotelStore.hotelName }} • {{ languageStore.t('hero_badge', 'Accommodations') }}</span>
      </div>

      <!-- Main Heading in Luxury Serif Font -->
      <h1
        class="font-serif text-3xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight leading-tight max-w-4xl mx-auto drop-shadow-lg"
      >
        {{ languageStore.t('discover_luxury_rooms', 'Discover Our Signature Suites') }}
      </h1>

      <p
        class="text-sm sm:text-base lg:text-lg text-slate-200/90 font-light max-w-2xl mx-auto leading-relaxed drop-shadow-xs"
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

      <!-- Key Amenity Highlights -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-3xl mx-auto pt-2">
        <div
          class="p-3 rounded-2xl bg-[#182235]/80 border border-[#26354D]/80 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md"
        >
          <Bed class="w-4 h-4 text-[#D6A94F] flex-shrink-0" />
          <span class="text-xs font-semibold">{{ languageStore.t('luxury_beds', 'King Feather Beds') }}</span>
        </div>

        <div
          class="p-3 rounded-2xl bg-[#182235]/80 border border-[#26354D]/80 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md"
        >
          <Droplet class="w-4 h-4 text-[#D6A94F] flex-shrink-0" />
          <span class="text-xs font-semibold">{{ languageStore.t('modern_bath', 'Marble Spa Bath') }}</span>
        </div>

        <div
          class="p-3 rounded-2xl bg-[#182235]/80 border border-[#26354D]/80 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md"
        >
          <Tv class="w-4 h-4 text-[#D6A94F] flex-shrink-0" />
          <span class="text-xs font-semibold">{{ languageStore.t('smart_tv', 'Smart 4K TV') }}</span>
        </div>

        <div
          class="p-3 rounded-2xl bg-[#182235]/80 border border-[#26354D]/80 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md"
        >
          <Wifi class="w-4 h-4 text-[#D6A94F] flex-shrink-0" />
          <span class="text-xs font-semibold">{{ languageStore.t('free_wifi', 'Fiber Wi-Fi') }}</span>
        </div>
      </div>

      <div
        @click="exploreRooms"
        class="pt-4 inline-flex flex-col items-center text-slate-300 hover:text-[#D6A94F] transition cursor-pointer select-none"
      >
        <span class="text-[10px] uppercase font-bold tracking-[0.2em] text-[#D6A94F]">Explore Available Suites</span>
        <ChevronDown class="w-4 h-4 text-[#D6A94F] mt-1 animate-bounce" />
      </div>
    </div>
  </section>
</template>
