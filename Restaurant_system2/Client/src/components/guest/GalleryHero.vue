<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useRoomStore } from '@/stores/room'
import { Sparkles, Calendar, BedDouble, Clock, MapPin, ShieldCheck } from 'lucide-vue-next'

const router = useRouter()
const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
const roomStore = useRoomStore()

const roomCount = computed(() => {
  const count = roomStore.rooms?.length || 0
  return count > 0 ? `${count}` : 'Suites'
})

function goToRooms() {
  router.push('/rooms')
}

function goToReservation() {
  router.push('/rooms')
}
</script>

<template>
  <section
    class="relative min-h-[500px] overflow-hidden sm:h-[450px] md:h-[550px] lg:h-[600px] bg-[#101827] text-white font-sans pt-20"
  >
    <img
      src="/images/gallery/luxury-suite.jpg"
      :alt="guestHotelStore.hotelName + ' Gallery'"
      class="absolute inset-0 h-full w-full object-cover brightness-75 scale-100"
      @error="(e: any) => (e.target.src = '/images/hero/hero.jpg')"
    />

    <!-- Luxury dark navy gradient vignettes -->
    <div
      class="absolute inset-0 bg-gradient-to-r from-[#101827]/95 via-[#101827]/75 to-[#101827]/40"
    />
    <div class="absolute inset-0 bg-gradient-to-t from-[#101827] via-transparent to-transparent" />

    <div
      class="relative z-10 mx-auto flex h-full max-w-7xl items-center px-4 sm:px-6 py-12 sm:py-0"
    >
      <div class="max-w-3xl w-full space-y-5">
        <div
          class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-[0.2em] bg-[#D6A94F]/20 text-[#D6A94F] border border-[#D6A94F]/30"
        >
          <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
          <span>{{ guestHotelStore.hotelName }} • {{ languageStore.t('gallery', 'Visual Showcase') }}</span>
        </div>

        <h1
          class="font-serif text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight text-white tracking-tight"
        >
          {{ languageStore.t('explore_every', 'Explore Every') }}
          <span class="italic font-normal text-[#D6A94F]"> {{ languageStore.t('corner', 'Corner') }} </span>
          {{ languageStore.t('of_our_hotel', 'of Our Sanctuary') }}
        </h1>

        <p
          class="max-w-2xl text-xs sm:text-sm md:text-base leading-relaxed text-slate-200/90 font-light"
        >
          {{
            languageStore.t(
              'gallery_hero_desc',
              'Tour our signature suites, gourmet dining venues, wellness spa, and curated architectural spaces at ' +
                guestHotelStore.hotelName +
                '.',
            )
          }}
        </p>

        <div class="pt-2 flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
          <button
            @click="goToReservation"
            class="rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] px-8 py-3.5 font-black text-[#101827] shadow-lg shadow-[#D6A94F]/20 transition text-xs uppercase tracking-widest cursor-pointer flex items-center justify-center gap-2"
          >
            <Calendar class="w-4 h-4 text-[#101827]" />
            <span>{{ languageStore.t('book_your_stay', 'Book Your Stay') }}</span>
          </button>

          <button
            @click="goToRooms"
            class="rounded-full border border-[#26354D] bg-[#182235]/80 hover:bg-[#182235] px-8 py-3.5 font-bold text-white transition text-xs uppercase tracking-widest cursor-pointer backdrop-blur-md flex items-center justify-center gap-2"
          >
            <BedDouble class="w-4 h-4 text-[#D6A94F]" />
            <span>{{ languageStore.t('view_rooms', 'View Suites') }}</span>
          </button>
        </div>

        <!-- Real Property Operational Information -->
        <div class="pt-4 grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
          <div class="p-3.5 rounded-2xl bg-[#182235]/85 border border-[#26354D]/80 backdrop-blur-md space-y-1">
            <p class="text-[9px] font-extrabold uppercase text-[#D6A94F] tracking-wider flex items-center gap-1.5">
              <BedDouble class="w-3.5 h-3.5 text-[#D6A94F]" />
              Accommodations
            </p>
            <p class="text-sm font-bold text-white">{{ roomCount }} Available Suites</p>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#182235]/85 border border-[#26354D]/80 backdrop-blur-md space-y-1">
            <p class="text-[9px] font-extrabold uppercase text-[#D6A94F] tracking-wider flex items-center gap-1.5">
              <Clock class="w-3.5 h-3.5 text-[#D6A94F]" />
              Front Desk
            </p>
            <p class="text-sm font-bold text-white truncate">{{ guestHotelStore.frontDeskHours }}</p>
          </div>

          <div class="col-span-2 sm:col-span-1 p-3.5 rounded-2xl bg-[#182235]/85 border border-[#26354D]/80 backdrop-blur-md space-y-1">
            <p class="text-[9px] font-extrabold uppercase text-[#D6A94F] tracking-wider flex items-center gap-1.5">
              <MapPin class="w-3.5 h-3.5 text-[#D6A94F]" />
              Destination
            </p>
            <p class="text-sm font-bold text-white truncate">{{ guestHotelStore.hotelCity }}, {{ guestHotelStore.hotelCountry }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
