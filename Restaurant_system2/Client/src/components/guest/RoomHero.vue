<script setup lang="ts">
import { BedDouble, Droplet, Tv, Wifi, Sparkles, ChevronDown } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

// Preserve existing scroll functionality
const exploreRooms = () => {
  const roomsSection = document.getElementById('rooms-section')

  if (roomsSection) {
    const offset = 80
    const bodyRect = document.body.getBoundingClientRect().top
    const elementRect = roomsSection.getBoundingClientRect().top
    const elementPosition = elementRect - bodyRect
    const offsetPosition = elementPosition - offset

    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth',
    })
  }
}
</script>

<template>
  <section
    class="relative isolate flex min-h-[650px] w-full flex-col justify-center overflow-hidden bg-[#101827] py-14 text-white sm:min-h-[680px] sm:py-16 lg:min-h-[700px] lg:py-20 xl:min-h-[740px]"
  >
    <!-- Background image -->
    <div class="absolute inset-0 -z-10 overflow-hidden">
      <img
        src="/images/rooms/deluxe.jpg"
        :alt="guestHotelStore.hotelName + ' Luxury Suite'"
        fetchpriority="high"
        class="h-full w-full object-cover object-center transition-transform duration-700 [object-position:50%_45%]"
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

      <!-- Deep navy overlay for consistent text contrast -->
      <div
        class="absolute inset-0"
        style="
          background: linear-gradient(
            180deg,
            rgba(12, 20, 36, 0.62) 0%,
            rgba(12, 20, 36, 0.48) 45%,
            rgba(12, 20, 36, 0.72) 100%
          );
        "
      />

      <!-- Gentle central contrast behind heading -->
      <div
        class="pointer-events-none absolute inset-0"
        style="
          background: radial-gradient(
            ellipse at 50% 38%,
            rgba(12, 20, 36, 0.3) 0%,
            rgba(12, 20, 36, 0.12) 48%,
            transparent 78%
          );
        "
      />
    </div>

    <!-- Main hero content -->
    <div
      class="relative z-10 mx-auto flex w-full max-w-[1240px] flex-col items-center px-5 text-center sm:px-8 lg:px-10"
    >
      <!-- Hotel identity badge -->
      <div
        class="mb-6 inline-flex max-w-full items-center justify-center gap-2.5 rounded-full border border-white/50 bg-[#192438]/95 px-4 py-2 shadow-lg backdrop-blur-md sm:mb-7 sm:gap-3 sm:px-6 sm:py-2.5"
      >
        <Sparkles class="h-4 w-4 shrink-0 text-[#E2B34F] sm:h-5 sm:w-5" />

        <span
          class="truncate text-[10px] font-bold uppercase tracking-[0.07em] text-white sm:text-xs md:text-sm md:tracking-[0.09em]"
        >
          {{ guestHotelStore.hotelName }}
          •
          {{
            (guestHotelStore.hotelCity || 'ADDIS ABABA').toUpperCase().replace('ADDISS', 'ADDIS')
          }}
        </span>
      </div>

      <!-- Elegant responsive heading -->
      <h1
        class="mx-auto mb-5 max-w-[1000px] font-serif font-medium tracking-[-0.035em] text-white text-[34px] leading-[1.15] sm:text-[46px] sm:leading-[1.12] md:text-[54px] lg:text-[64px] xl:text-[72px] [text-shadow:0_3px_18px_rgba(0,0,0,0.25)]"
      >
        <span class="sm:hidden">
          {{ languageStore.t('discover_rooms_short', 'Discover Our Rooms') }}
        </span>

        <span class="hidden sm:inline">
          {{ languageStore.t('discover_luxury_rooms', 'Discover Our Luxurious Rooms') }}
        </span>
      </h1>

      <!-- Supporting description with controlled line length -->
      <p
        class="mx-auto mb-7 max-w-[820px] px-1 text-[14px] font-normal leading-[1.8] text-white/95 sm:mb-9 sm:px-0 sm:text-[17px] sm:leading-[1.8] lg:text-[19px] lg:leading-[1.75]"
      >
        {{
          languageStore.t(
            'discover_rooms_desc',
            'Experience unparalleled comfort and elegance in our exquisitely designed rooms. Each accommodation is crafted with premium amenities, modern furnishings, and stunning views.',
          )
        }}
      </p>

      <!-- Four balanced amenity cards -->
      <div
        class="mb-7 grid w-full max-w-[1040px] grid-cols-2 gap-3 sm:mb-9 sm:gap-4 lg:grid-cols-4 lg:gap-5"
      >
        <!-- Luxury Beds -->
        <div
          class="flex min-h-[58px] min-w-0 items-center justify-center gap-2.5 rounded-2xl border border-white/50 bg-[#192438]/95 px-2.5 py-3 shadow-lg backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-[#D6A746] hover:bg-[#202D43] sm:min-h-[64px] sm:gap-3 sm:px-4"
        >
          <BedDouble class="h-4 w-4 shrink-0 text-[#E2B34F] sm:h-5 sm:w-5" />
          <span class="text-xs font-medium text-white sm:text-sm lg:text-base">
            {{ languageStore.t('luxury_beds', 'Luxury Beds') }}
          </span>
        </div>

        <!-- Modern Bath -->
        <div
          class="flex min-h-[58px] min-w-0 items-center justify-center gap-2.5 rounded-2xl border border-white/50 bg-[#192438]/95 px-2.5 py-3 shadow-lg backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-[#D6A746] hover:bg-[#202D43] sm:min-h-[64px] sm:gap-3 sm:px-4"
        >
          <Droplet class="h-4 w-4 shrink-0 text-[#E2B34F] sm:h-5 sm:w-5" />
          <span class="text-xs font-medium text-white sm:text-sm lg:text-base">
            {{ languageStore.t('modern_bath', 'Modern Bath') }}
          </span>
        </div>

        <!-- Smart HD TV -->
        <div
          class="flex min-h-[58px] min-w-0 items-center justify-center gap-2.5 rounded-2xl border border-white/50 bg-[#192438]/95 px-2.5 py-3 shadow-lg backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-[#D6A746] hover:bg-[#202D43] sm:min-h-[64px] sm:gap-3 sm:px-4"
        >
          <Tv class="h-4 w-4 shrink-0 text-[#E2B34F] sm:h-5 sm:w-5" />
          <span class="text-xs font-medium text-white sm:text-sm lg:text-base">
            {{ languageStore.t('smart_hd_tv', 'Smart HD TV') }}
          </span>
        </div>

        <!-- Free Wi-Fi -->
        <div
          class="flex min-h-[58px] min-w-0 items-center justify-center gap-2 rounded-2xl border border-white/50 bg-[#192438]/95 px-2 py-3 shadow-lg backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-[#D6A746] hover:bg-[#202D43] sm:min-h-[64px] sm:gap-3 sm:px-4"
        >
          <Wifi class="h-4 w-4 shrink-0 text-[#E2B34F] sm:h-5 sm:w-5" />

          <span class="text-xs font-medium text-white sm:text-sm lg:text-base">
            <span class="sm:hidden">Free Wi-Fi</span>
            <span class="hidden sm:inline">
              {{ languageStore.t('free_wifi', 'Free High-Speed Wi-Fi') }}
            </span>
          </span>
        </div>
      </div>

      <!-- Explore suites: existing scroll action preserved -->
      <button
        @click="exploreRooms"
        type="button"
        class="group inline-flex min-h-[48px] max-w-full items-center justify-center gap-3 rounded-full border border-[#D6A746]/80 bg-[#192438]/90 px-5 py-3 text-xs font-semibold uppercase tracking-[0.08em] text-[#E2B34F] shadow-lg backdrop-blur-md transition-all duration-300 hover:border-[#E2B34F] hover:bg-[#D6A746] hover:text-[#101827] active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E2B34F] focus-visible:ring-offset-2 focus-visible:ring-offset-[#101827] sm:px-7 sm:text-sm"
        aria-label="Scroll down to explore available suites"
      >
        <span>
          {{ languageStore.t('explore_available_suites', 'Explore Available Suites') }}
        </span>

        <ChevronDown
          class="h-4 w-4 shrink-0 transition-transform duration-300 group-hover:translate-y-1"
        />
      </button>
    </div>
  </section>
</template>
