<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { MapPin, Phone, Mail, LogIn, Sparkles } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { storageUrl } from '@/utils/urlHelpers'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
const imageLoadFailed = ref(false)

watch(
  () => [guestHotelStore.hotelId, guestHotelStore.currentHotel?.logo],
  () => {
    imageLoadFailed.value = false
  },
  { immediate: true },
)

const hotelLogoUrl = computed(() => {
  if (imageLoadFailed.value) return null
  const logo = guestHotelStore.currentHotel?.logo
  if (logo && typeof logo === 'string' && logo.trim()) {
    const trimmed = logo.trim()
    if (
      trimmed.startsWith('http://') ||
      trimmed.startsWith('https://') ||
      trimmed.startsWith('data:') ||
      trimmed.startsWith('/images/')
    ) {
      return trimmed
    }
    if (
      trimmed.startsWith('/storage/') ||
      trimmed.startsWith('storage/') ||
      trimmed.startsWith('hotels/')
    ) {
      return storageUrl(trimmed)
    }
    if (trimmed.startsWith('images/')) {
      return `/${trimmed}`
    }
    return storageUrl(trimmed)
  }

  const name = (guestHotelStore.hotelName || '').toLowerCase()
  if (name.includes('qelem') || name.includes('meda')) {
    return '/images/qelem meda logo.png'
  }
  return '/images/Hotel logo.png'
})
</script>

<template>
  <footer
    class="bg-[#101827] border-t border-[#26354D] text-slate-300 font-sans transition-colors duration-300 relative overflow-hidden"
  >
    <!-- Top hairline gold accent -->
    <div
      class="h-[2px] w-full bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent opacity-80"
    />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16 space-y-12">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
        <!-- Brand & Summary -->
        <div class="space-y-4">
          <div class="flex items-center gap-3">
            <div
              class="w-11 h-11 rounded-2xl bg-[#182235] border border-[#D6A94F]/30 text-[#D6A94F] flex items-center justify-center font-bold text-lg shadow-md overflow-hidden p-1.5"
            >
              <img
                v-if="hotelLogoUrl && !imageLoadFailed"
                :src="hotelLogoUrl"
                :alt="guestHotelStore.hotelName"
                class="w-full h-full object-contain brightness-110"
                width="44"
                height="44"
                loading="lazy"
                decoding="async"
                @error="imageLoadFailed = true"
              />
              <span v-else>{{ (guestHotelStore.hotelName || 'H').charAt(0).toUpperCase() }}</span>
            </div>
            <div>
              <span
                class="font-serif text-lg font-bold text-white tracking-wider uppercase block leading-tight"
              >
                {{ guestHotelStore.hotelName }}
              </span>
              <span class="text-[9px] font-bold text-[#D6A94F] uppercase tracking-[0.2em] block">
                {{ (guestHotelStore.currentHotel?.city || 'Addis Ababa').toUpperCase() }} • LUXURY
                HOTEL
              </span>
            </div>
          </div>

          <p class="text-xs text-slate-400 leading-relaxed font-light">
            A sanctuary of timeless elegance, authentic Ethiopian hospitality, signature fine
            dining, and master suites.
          </p>

          <div class="space-y-2 text-xs font-light text-slate-300">
            <div class="flex items-start gap-2">
              <MapPin class="w-4 h-4 text-[#D6A94F] flex-shrink-0 mt-0.5" />
              <span>{{ guestHotelStore.hotelAddress }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Phone class="w-4 h-4 text-[#D6A94F] flex-shrink-0" />
              <span>{{ guestHotelStore.hotelPhone }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Mail class="w-4 h-4 text-[#D6A94F] flex-shrink-0" />
              <span>{{ guestHotelStore.hotelEmail }}</span>
            </div>
          </div>
        </div>

        <!-- Quick Navigation -->
        <div class="space-y-4">
          <h3
            class="text-[11px] font-extrabold text-[#D6A94F] uppercase tracking-[0.2em] border-b border-[#26354D] pb-2"
          >
            Hotel Exploration
          </h3>
          <ul class="space-y-2.5 text-xs font-medium text-slate-300">
            <li>
              <RouterLink to="/" class="hover:text-[#D6A94F] transition flex items-center gap-1.5">
                <span>{{ languageStore.t('home', 'Home Sanctuary') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/rooms"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('rooms_and_suites', 'Rooms & Suites') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/#facilities"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('facilities', 'Hotel Facilities') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/menu"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('restaurant_menu', 'Restaurant Menu') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/#dining"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('dining', 'Signature Dining') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/gallery"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('gallery', 'Property Gallery') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/about"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('about', 'About Our Hotel') }}</span>
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Guest Services -->
        <div class="space-y-4">
          <h3
            class="text-[11px] font-extrabold text-[#D6A94F] uppercase tracking-[0.2em] border-b border-[#26354D] pb-2"
          >
            Guest Services
          </h3>
          <ul class="space-y-2.5 text-xs font-medium text-slate-300">
            <li>
              <RouterLink
                to="/rooms"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('book_now', 'Direct Online Reservation') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>Private Table Booking</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>Bespoke Event Hosting</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>Chauffeur & Airport Shuttle</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-[#D6A94F] transition flex items-center gap-1.5"
              >
                <span>Concierge & Guest Support</span>
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Property Portal Access -->
        <div class="space-y-4">
          <h3
            class="text-[11px] font-extrabold text-[#D6A94F] uppercase tracking-[0.2em] border-b border-[#26354D] pb-2"
          >
            Property Management
          </h3>
          <p class="text-xs text-slate-400 leading-relaxed font-light">
            Authorized hospitality staff, front-desk hosts, and management can access the property
            portal below.
          </p>

          <RouterLink
            to="/login"
            class="inline-flex items-center justify-center gap-2 w-full px-5 py-3 rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black text-xs uppercase tracking-wider shadow-lg shadow-[#D6A94F]/20 transition cursor-pointer"
          >
            <LogIn class="w-4 h-4 text-[#101827]" />
            <span>{{
              languageStore.currentLanguage === 'am' ? 'የሰራተኞች መግቢያ' : 'Staff Portal Login'
            }}</span>
          </RouterLink>
        </div>
      </div>

      <!-- Bottom Hairline & Legal -->
      <div
        class="pt-8 border-t border-[#26354D] flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-light text-slate-400"
      >
        <p>
          &copy; {{ new Date().getFullYear() }} {{ guestHotelStore.hotelName }}. All rights
          reserved.
        </p>
        <div class="flex items-center gap-4 text-slate-400">
          <RouterLink to="/contact" class="hover:text-[#D6A94F] transition"
            >Privacy Policy</RouterLink
          >
          <span>•</span>
          <RouterLink to="/contact" class="hover:text-[#D6A94F] transition"
            >Terms of Service</RouterLink
          >
          <span>•</span>
          <RouterLink to="/contact" class="hover:text-[#D6A94F] transition"
            >Concierge Support</RouterLink
          >
        </div>
      </div>
    </div>
  </footer>
</template>
