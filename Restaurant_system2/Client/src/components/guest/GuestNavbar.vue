<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useThemeStore } from '@/stores/theme'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useLanguageStore } from '@/stores/language'
import LanguageSelector from '@/components/common/LanguageSelector.vue'
import {
  Sun,
  Moon,
  Menu as MenuIcon,
  X as CloseIcon,
} from 'lucide-vue-next'

const route = useRoute()
const theme = useThemeStore()
const guestHotelStore = useGuestHotelStore()
const languageStore = useLanguageStore()

const currentHotelLogo = computed(() => {
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
    if (trimmed.startsWith('/storage/')) {
      return `http://127.0.0.1:8000${trimmed}`
    }
    if (trimmed.startsWith('storage/')) {
      return `http://127.0.0.1:8000/${trimmed}`
    }
    if (trimmed.startsWith('hotels/')) {
      return `http://127.0.0.1:8000/storage/${trimmed}`
    }
    return `http://127.0.0.1:8000/storage/${trimmed}`
  }

  const name = (guestHotelStore.hotelName || '').toLowerCase()
  if (name.includes('qelem') || name.includes('meda')) {
    return '/images/qelem meda logo.png'
  }
  return '/images/Hotel logo.png'
})

const mobileMenu = ref(false)
const scrolled = ref(false)

function isItemActive(itemRoute: string) {
  if (itemRoute === '/') {
    return route.path === '/' || route.name === 'home'
  }
  return route.path === itemRoute || route.path.startsWith(`${itemRoute}/`)
}

// Title Case navigation labels
const menus = computed(() => [
  { title: languageStore.t('home', 'Home'), route: '/' },
  { title: languageStore.t('rooms', 'Rooms & Suites'), route: '/rooms' },
  { title: languageStore.t('facilities', 'Facilities'), route: '/#facilities' },
  { title: languageStore.t('dining', 'Dining'), route: '/#dining' },
  { title: languageStore.t('gallery', 'Gallery'), route: '/gallery' },
  { title: languageStore.t('about', 'About Us'), route: '/about' },
  { title: languageStore.t('contact', 'Contact'), route: '/contact' },
])

function toggleMenu() {
  mobileMenu.value = !mobileMenu.value
}

function closeMenu() {
  mobileMenu.value = false
}

function handleScroll() {
  scrolled.value = window.scrollY > 20
}

function handleThemeToggle() {
  theme.toggleTheme()
}

onMounted(() => {
  theme.initializeTheme()
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <header
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300 border-b"
    :class="[
      scrolled
        ? 'bg-[#101827]/95 dark:bg-[#0B111D]/95 backdrop-blur-md border-[#26354D]/80 shadow-xl py-0'
        : 'bg-[#101827]/90 dark:bg-[#0B111D]/90 backdrop-blur-sm border-[#26354D]/50 py-0.5',
    ]"
  >
    <!-- Top gold accent hairline -->
    <div class="h-[2px] w-full bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent opacity-80" />

    <div
      class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 sm:h-20 flex items-center justify-between"
    >
      <!-- Logo + Hotel Brand -->
      <div class="flex items-center gap-4 lg:gap-8 min-w-0 flex-1 mr-2 sm:mr-4">
        <RouterLink
          to="/"
          class="flex items-center gap-3 min-w-0 select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D6A94F] rounded-lg group"
        >
          <div
            class="relative w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-xl bg-[#182235] p-1.5 shadow-md border border-[#D6A94F]/30 shrink-0 transition-transform duration-300 group-hover:scale-105 overflow-hidden"
          >
            <img
              :src="currentHotelLogo"
              :alt="guestHotelStore.hotelName"
              class="w-full h-full object-contain select-none brightness-110"
              @error="
                (e: any) => {
                  e.target.src = '/images/Hotel logo.png'
                }
              "
            />
          </div>

          <div class="flex flex-col min-w-0 flex-1">
            <span
              class="font-serif text-[15px] sm:text-[18px] lg:text-[19px] font-bold tracking-wider text-white leading-tight uppercase truncate"
            >
              {{ guestHotelStore.hotelName }}
            </span>
            <span
              class="text-[9px] sm:text-[10px] font-semibold text-[#D6A94F] tracking-[0.2em] uppercase mt-0.5 leading-none truncate"
            >
              {{
                (guestHotelStore.hotelCity || 'ADDIS ABABA')
                  .toUpperCase()
                  .replace('ADDISS', 'ADDIS')
              }} • LUXURY HOTEL
            </span>
          </div>
        </RouterLink>

        <!-- Desktop Navigation Links -->
        <nav
          class="hidden lg:flex items-center gap-5 xl:gap-7 shrink-0 ml-2"
          aria-label="Main Navigation"
        >
          <RouterLink
            v-for="menu in menus"
            :key="menu.route"
            :to="menu.route"
            class="text-[12px] xl:text-[13px] font-medium uppercase tracking-wider transition-colors relative py-1 focus:outline-none focus-visible:text-[#D6A94F]"
            :class="[
              isItemActive(menu.route)
                ? 'text-[#D6A94F] font-bold'
                : 'text-slate-200 hover:text-white',
            ]"
          >
            {{ menu.title }}
            <span
              v-if="isItemActive(menu.route)"
              class="absolute inset-x-0 -bottom-1 h-[2px] bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent rounded-full"
            />
          </RouterLink>
        </nav>
      </div>

      <!-- Desktop Right Actions -->
      <div class="hidden lg:flex items-center gap-3 shrink-0">
        <LanguageSelector variant="header" />

        <button
          @click="handleThemeToggle"
          type="button"
          class="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center rounded-full border border-[#26354D] bg-[#182235]/60 hover:bg-[#182235] text-slate-300 hover:text-white hover:border-[#D6A94F]/60 transition-colors duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D6A94F]"
          :title="
            theme.isDark
              ? languageStore.t('light_mode', 'Light Mode')
              : languageStore.t('dark_mode', 'Dark Mode')
          "
          :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="theme.isDark" class="w-4 h-4 text-[#D6A94F]" />
          <Moon v-else class="w-4 h-4 text-slate-300" />
        </button>

        <RouterLink
          to="/rooms"
          class="h-10 px-5 xl:px-6 flex items-center justify-center rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-extrabold text-[12px] tracking-wider uppercase shadow-md shadow-[#D6A94F]/20 hover:shadow-lg hover:shadow-[#D6A94F]/30 transition-all duration-200 whitespace-nowrap active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D6A94F]"
        >
          {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
        </RouterLink>
      </div>

      <!-- Mobile & Tablet Right Actions -->
      <div class="flex items-center gap-2 shrink-0 lg:hidden">
        <LanguageSelector variant="header" />

        <button
          @click="handleThemeToggle"
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-full border border-[#26354D] bg-[#182235] text-slate-300 hover:text-white transition-colors shrink-0"
          :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="theme.isDark" class="w-4 h-4 text-[#D6A94F]" />
          <Moon v-else class="w-4 h-4" />
        </button>

        <button
          @click="toggleMenu"
          type="button"
          class="h-9 w-9 sm:h-10 sm:w-10 flex items-center justify-center rounded-full border border-[#26354D] bg-[#182235] text-white transition-colors hover:border-[#D6A94F] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D6A94F] shrink-0 cursor-pointer"
          :aria-expanded="mobileMenu"
          aria-label="Toggle navigation"
        >
          <MenuIcon v-if="!mobileMenu" class="w-4.5 h-4.5" />
          <CloseIcon v-else class="w-4.5 h-4.5 text-[#D6A94F]" />
        </button>
      </div>
    </div>

    <!-- Mobile Drawer -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div
        v-if="mobileMenu"
        class="lg:hidden bg-[#101827]/98 border-b border-[#26354D] px-4 py-5 space-y-4 shadow-2xl backdrop-blur-xl"
      >
        <div class="flex flex-col space-y-1">
          <RouterLink
            v-for="menu in menus"
            :key="menu.route"
            :to="menu.route"
            @click="closeMenu"
            class="px-4 py-2.5 rounded-xl text-sm font-medium tracking-wide uppercase transition-colors"
            :class="[
              isItemActive(menu.route)
                ? 'font-bold text-[#D6A94F] bg-[#182235] border border-[#D6A94F]/30'
                : 'text-slate-300 hover:text-white hover:bg-[#182235]/60',
            ]"
          >
            {{ menu.title }}
          </RouterLink>
        </div>

        <div class="pt-2 border-t border-[#26354D]">
          <RouterLink
            to="/rooms"
            @click="closeMenu"
            class="block w-full text-center py-3 rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] text-xs font-black tracking-widest uppercase shadow-lg shadow-[#D6A94F]/20 transition-all cursor-pointer"
          >
            {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
          </RouterLink>
        </div>
      </div>
    </Transition>
  </header>
</template>
