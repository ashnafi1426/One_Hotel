<script setup lang="ts">
import { Sparkles } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
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
    </div>
  </section>
</template>
