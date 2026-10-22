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
    class="relative isolate flex min-h-[650px] flex-col justify-center overflow-hidden bg-[#101827] text-white sm:min-h-[700px] lg:min-h-[710px] xl:min-h-[740px]"
  >
    <!-- Background image -->
    <div class="absolute inset-0 -z-10">
      <img
        src="/images/rooms/image.png"
        :alt="`${guestHotelStore.hotelName} hotel exterior`"
        class="h-full w-full object-cover object-center"
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

      <!-- Keep the hotel visible while protecting text contrast -->
      <div class="absolute inset-0 bg-[#101827]/35" />

      <div
        class="absolute inset-0 bg-gradient-to-b from-[#101827]/60 via-[#101827]/35 to-[#101827]/65"
      />

      <!-- Local contrast behind the heading -->
      <div
        class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(16,24,39,0.35)_0%,transparent_72%)]"
      />
    </div>

    <!-- Main hero content -->
    <div
      class="relative z-10 mx-auto flex w-full max-w-6xl flex-col items-center px-5 py-12 text-center sm:px-8 sm:py-14 lg:px-10 lg:py-16"
    >
      <!-- Hotel badge -->
      <div
        class="inline-flex max-w-full items-center justify-center gap-2 rounded-full border border-white/60 bg-[#192438]/90 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.08em] text-white shadow-lg backdrop-blur-md sm:px-6 sm:text-xs"
      >
        <Sparkles class="h-4 w-4 shrink-0 text-[#D6A746]" />

        <span class="truncate">
          {{ guestHotelStore.hotelName }}
          •
          {{
            (guestHotelStore.hotelCity || 'ADDIS ABABA').toUpperCase().replace('ADDISS', 'ADDIS')
          }}
        </span>
      </div>

      <!-- Heading: one clear primary line and a gold accent line -->
      <h1
        class="mt-6 max-w-4xl font-serif font-bold text-[clamp(2.5rem,5vw,4.5rem)] leading-[1.08] tracking-[-0.035em] text-white drop-shadow-lg text-balance"
      >
        {{ languageStore.t('hero_title_part1', 'Timeless Luxury') }}
      </h1>

      <p
        class="mt-3 max-w-4xl font-serif text-[clamp(1.65rem,3.5vw,3rem)] font-normal italic leading-tight tracking-normal text-[#E7B84F] drop-shadow-md text-balance"
      >
        &amp; {{ languageStore.t('hero_title_part2', 'Unmatched Comfort') }}
      </p>

      <!-- Supporting subtitle -->
      <p
        class="mt-5 max-w-2xl px-2 font-sans text-sm font-normal leading-relaxed text-white/95 drop-shadow-md sm:text-base md:text-lg"
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

      <!-- Primary and secondary actions -->
      <div
        class="mt-7 flex w-full max-w-2xl flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center sm:gap-4"
      >
        <button
          type="button"
          @click="bookNow"
          class="inline-flex min-h-[54px] flex-1 items-center justify-center gap-2.5 rounded-full bg-[#D6A746] px-6 py-3 text-sm font-bold uppercase tracking-[0.07em] text-[#192438] shadow-lg shadow-black/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#E3B956] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#192438] active:scale-[0.98]"
        >
          <Calendar class="h-5 w-5 shrink-0" />
          <span>
            {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
          </span>
        </button>

        <button
          type="button"
          @click="exploreRooms"
          class="inline-flex min-h-[54px] flex-1 items-center justify-center gap-2.5 rounded-full border border-white/70 bg-[#192438]/75 px-6 py-3 text-sm font-semibold uppercase tracking-[0.06em] text-white shadow-lg backdrop-blur-md transition-all duration-300 hover:-translate-y-0.5 hover:border-[#D6A746] hover:bg-[#192438]/95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#D6A746] active:scale-[0.98]"
        >
          <BedDouble class="h-5 w-5 shrink-0 text-[#D6A746]" />
          <span>
            {{ languageStore.t('explore_rooms_suites', 'Explore Rooms & Suites') }}
          </span>
        </button>
      </div>

      <!-- Hotel information cards -->
      <div
        class="mt-9 grid w-full max-w-5xl grid-cols-1 gap-3 text-left sm:grid-cols-3 sm:gap-4 lg:mt-12"
      >
        <article
          class="flex min-h-[78px] flex-col justify-center rounded-2xl border border-white/35 bg-[#192438]/90 px-4 py-3 shadow-lg backdrop-blur-md transition-colors hover:bg-[#192438]/95 sm:px-5"
        >
          <p
            class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.09em] text-[#E7B84F]"
          >
            <Clock class="h-4 w-4 shrink-0" />
            {{ languageStore.t('check_in', 'Check-In') }}
          </p>

          <p class="mt-1.5 text-sm font-medium leading-snug text-white sm:text-base">
            {{ guestHotelStore.currentHotel?.front_desk_hours || 'Open 24/7 / 365 Days' }}
          </p>
        </article>

        <article
          class="flex min-h-[78px] flex-col justify-center rounded-2xl border border-white/35 bg-[#192438]/90 px-4 py-3 shadow-lg backdrop-blur-md transition-colors hover:bg-[#192438]/95 sm:px-5"
        >
          <p
            class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.09em] text-[#E7B84F]"
          >
            <MapPin class="h-4 w-4 shrink-0" />
            {{ languageStore.t('location', 'Location') }}
          </p>

          <p
            class="mt-1.5 line-clamp-2 break-words text-sm font-medium leading-snug text-white sm:text-base"
            :title="heroLocation"
          >
            {{ heroLocation }}
          </p>
        </article>

        <article
          class="flex min-h-[78px] flex-col justify-center rounded-2xl border border-white/35 bg-[#192438]/90 px-4 py-3 shadow-lg backdrop-blur-md transition-colors hover:bg-[#192438]/95 sm:px-5"
        >
          <p
            class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.09em] text-[#E7B84F]"
          >
            <ShieldCheck class="h-4 w-4 shrink-0" />
            {{ languageStore.t('concierge', 'Concierge Service') }}
          </p>

          <p class="mt-1.5 text-sm font-medium leading-snug text-white sm:text-base">
            {{ languageStore.t('concierge_service', '24/7 Dedicated Care') }}
          </p>
        </article>
      </div>
    </div>
  </section>
</template>
