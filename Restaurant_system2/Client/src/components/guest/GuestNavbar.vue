<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useThemeStore } from '@/stores/theme'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useLanguageStore, type LanguageCode } from '@/stores/language'
import LanguageSelector from '@/components/common/LanguageSelector.vue'
import {
  Sun,
  Moon,
  Menu as MenuIcon,
  X as CloseIcon,
  Globe,
  Sparkles,
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

// Navigation links with Title Case labels and consistent 0.08em tracking
const menus = computed(() => [
  { title: languageStore.t('home', 'Home'), route: '/' },
  { title: languageStore.t('rooms', 'Rooms & Suites'), route: '/rooms' },
  { title: languageStore.t('facilities', 'Facilities'), route: '/#facilities' },
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
  scrolled.value = window.scrollY > 15
}

function handleThemeToggle() {
  theme.toggleTheme()
}

function selectLanguage(code: LanguageCode) {
  languageStore.setLanguage(code)
}

function handleKeyDown(e: KeyboardEvent) {
  if (e.key === 'Escape' && mobileMenu.value) {
    closeMenu()
  }
}

watch(mobileMenu, (isOpen) => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = isOpen ? 'hidden' : ''
  }
})

onMounted(() => {
  theme.initializeTheme()
  window.addEventListener('scroll', handleScroll, { passive: true })
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('keydown', handleKeyDown)
  if (typeof document !== 'undefined') {
    document.body.style.overflow = ''
  }
})
</script>

<template>
  <header
    class="sticky top-0 z-50 w-full transition-shadow duration-300 select-none bg-white/95 dark:bg-[#101827]/95 backdrop-blur-md border-b border-slate-200/80 dark:border-white/10"
    :class="[
      scrolled
        ? 'shadow-md shadow-slate-900/5 dark:shadow-black/40'
        : 'shadow-none',
    ]"
  >
    <!-- Top signature gold accent hairline -->
    <div
      class="h-[2px] w-full bg-gradient-to-r from-transparent via-[#9A7418] dark:via-[#D6A94F] to-transparent opacity-80"
    />

    <!-- Fixed 76px navbar height across all pages and viewports to prevent layout jump -->
    <div
      class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[76px] flex items-center justify-between"
    >
      <!-- Logo + Brand Emblem (56px) -->
      <RouterLink
        to="/"
        class="flex items-center gap-3 sm:gap-3.5 min-w-0 select-none rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F] group"
        aria-label="Executive Hotel Addis Ababa Home"
      >
        <!-- 56px Emblem Container for clear visibility of crest details -->
        <div
          class="relative w-14 h-14 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-[#182235] p-1 shadow-sm border border-slate-200/90 dark:border-[#D6A94F]/30 shrink-0 transition-transform duration-300 group-hover:scale-105 overflow-hidden"
        >
          <img
            :src="currentHotelLogo"
            :alt="guestHotelStore.hotelName"
            class="w-full h-full object-contain select-none brightness-105"
            @error="
              (e: any) => {
                e.target.src = '/images/Hotel logo.png'
              }
            "
          />
        </div>

        <div class="flex flex-col min-w-0">
          <span
            class="font-serif text-[17px] sm:text-[19px] lg:text-[20px] font-bold tracking-[0.08em] text-slate-900 dark:text-white leading-tight uppercase whitespace-nowrap"
          >
            {{ guestHotelStore.hotelName }}
          </span>
          <span
            class="text-[9px] sm:text-[10px] font-bold text-[#9A7418] dark:text-[#D6A94F] tracking-[0.08em] uppercase mt-0.5 leading-none whitespace-nowrap"
          >
            {{
              (guestHotelStore.hotelCity || 'ADDIS ABABA')
                .toUpperCase()
                .replace('ADDISS', 'ADDIS')
            }} • LUXURY HOTEL
          </span>
        </div>
      </RouterLink>

      <!-- Desktop Navigation Menu (>= 1024px) -->
      <nav
        class="hidden lg:flex items-center gap-6 xl:gap-8 shrink-0"
        aria-label="Main Navigation"
      >
        <RouterLink
          v-for="menu in menus"
          :key="menu.route"
          :to="menu.route"
          class="text-[13px] uppercase tracking-[0.08em] transition-colors relative py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F] rounded-md px-1"
          :class="[
            isItemActive(menu.route)
              ? 'text-[#9A7418] dark:text-[#D6A94F] font-bold'
              : 'text-slate-700 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white font-semibold',
          ]"
        >
          {{ menu.title }}
          <!-- 2px full-width underline for active link -->
          <span
            v-if="isItemActive(menu.route)"
            class="absolute bottom-0 inset-x-0 h-[2px] bg-[#9A7418] dark:bg-[#D6A94F] rounded-full"
          />
        </RouterLink>
      </nav>

      <!-- Desktop Right Actions Toolbar (>= 1024px) -->
      <div class="hidden lg:flex items-center gap-3 shrink-0">
        <LanguageSelector variant="header" />

        <button
          @click="handleThemeToggle"
          type="button"
          class="w-10 h-10 flex items-center justify-center rounded-full border border-slate-200 dark:border-white/15 bg-white dark:bg-[#182235] hover:bg-slate-50 dark:hover:bg-[#182235]/80 text-slate-700 dark:text-slate-200 hover:text-slate-950 dark:hover:text-white hover:border-[#9A7418]/70 dark:hover:border-[#D6A94F]/70 transition-colors duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F]"
          :title="
            theme.isDark
              ? languageStore.t('light_mode', 'Light Mode')
              : languageStore.t('dark_mode', 'Dark Mode')
          "
          :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="theme.isDark" class="w-4 h-4 text-[#D6A94F]" />
          <Moon v-else class="w-4 h-4 text-slate-700" />
        </button>

        <RouterLink
          to="/rooms"
          class="h-10 px-5 xl:px-6 flex items-center justify-center rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-bold text-[12px] tracking-[0.08em] uppercase shadow-md shadow-[#D6A94F]/25 hover:shadow-lg hover:shadow-[#D6A94F]/35 transition-all duration-200 whitespace-nowrap active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F]"
        >
          {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
        </RouterLink>
      </div>

      <!-- Mobile & Tablet Hamburger Toggle (< 1024px) -->
      <div class="flex items-center gap-2 lg:hidden">
        <button
          @click="handleThemeToggle"
          type="button"
          class="w-9 h-9 flex items-center justify-center rounded-full border border-slate-200 dark:border-white/15 bg-white dark:bg-[#182235] text-slate-700 dark:text-slate-200 hover:text-slate-950 dark:hover:text-white transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F]"
          :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="theme.isDark" class="w-4 h-4 text-[#D6A94F]" />
          <Moon v-else class="w-4 h-4 text-slate-700" />
        </button>

        <button
          @click="toggleMenu"
          type="button"
          class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 dark:border-white/15 bg-white dark:bg-[#182235] text-slate-800 dark:text-white hover:border-[#9A7418] dark:hover:border-[#D6A94F] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F] cursor-pointer"
          :aria-expanded="mobileMenu"
          aria-label="Open navigation menu"
        >
          <MenuIcon v-if="!mobileMenu" class="w-5 h-5 text-slate-800 dark:text-slate-100" />
          <CloseIcon v-else class="w-5 h-5 text-[#9A7418] dark:text-[#D6A94F]" />
        </button>
      </div>
    </div>

    <!-- Responsive Slide-In Drawer & Backdrop (< 1024px) -->
    <Teleport to="body">
      <!-- Backdrop Dimmer Overlay -->
      <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="mobileMenu"
          class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[998] lg:hidden"
          @click="closeMenu"
          aria-hidden="true"
        />
      </Transition>

      <!-- Slide-In Drawer Panel -->
      <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
      >
        <aside
          v-if="mobileMenu"
          class="fixed top-0 right-0 bottom-0 w-full max-w-sm bg-white dark:bg-[#101827] text-slate-900 dark:text-white shadow-2xl z-[999] lg:hidden flex flex-col justify-between border-l border-slate-200 dark:border-white/10"
          role="dialog"
          aria-modal="true"
          aria-label="Mobile Navigation Drawer"
        >
          <!-- Drawer Header with Logo & Close Button -->
          <div class="p-5 border-b border-slate-200 dark:border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="w-11 h-11 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-[#182235] p-1 shadow-sm border border-slate-200 dark:border-[#D6A94F]/30 shrink-0"
              >
                <img
                  :src="currentHotelLogo"
                  :alt="guestHotelStore.hotelName"
                  class="w-full h-full object-contain"
                />
              </div>
              <div class="flex flex-col">
                <span class="font-serif text-base font-bold tracking-[0.08em] text-slate-900 dark:text-white uppercase leading-tight">
                  {{ guestHotelStore.hotelName }}
                </span>
                <span class="text-[9px] font-bold text-[#9A7418] dark:text-[#D6A94F] tracking-[0.08em] uppercase">
                  {{ guestHotelStore.hotelCity }}
                </span>
              </div>
            </div>

            <button
              @click="closeMenu"
              type="button"
              class="w-9 h-9 flex items-center justify-center rounded-full border border-slate-200 dark:border-white/15 bg-slate-50 dark:bg-[#182235] text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F] cursor-pointer"
              aria-label="Close navigation menu"
            >
              <CloseIcon class="w-4 h-4 text-slate-700 dark:text-slate-200" />
            </button>
          </div>

          <!-- Drawer Navigation Links -->
          <div class="flex-1 overflow-y-auto p-5 space-y-1.5">
            <p class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400 dark:text-slate-500 mb-3 px-3">
              Menu Navigation
            </p>
            <RouterLink
              v-for="menu in menus"
              :key="menu.route"
              :to="menu.route"
              @click="closeMenu"
              class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium tracking-[0.08em] uppercase transition-all"
              :class="[
                isItemActive(menu.route)
                  ? 'font-semibold text-[#9A7418] dark:text-[#D6A94F] bg-[#FAF3E7] dark:bg-[#182235] border border-[#9A7418]/30 dark:border-[#D6A94F]/30'
                  : 'text-slate-700 hover:text-slate-950 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-[#182235]/60',
              ]"
            >
              <span>{{ menu.title }}</span>
              <span
                v-if="isItemActive(menu.route)"
                class="w-2 h-2 rounded-full bg-[#9A7418] dark:bg-[#D6A94F]"
              />
            </RouterLink>
          </div>

          <!-- Drawer Bottom Actions: Language Selector, Theme Toggle & CTA -->
          <div class="p-5 border-t border-slate-200 dark:border-white/10 space-y-4 bg-slate-50/60 dark:bg-[#101827]">
            <!-- Language Selector Section -->
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <Globe class="w-3.5 h-3.5 text-[#9A7418] dark:text-[#D6A94F]" />
                {{ languageStore.t('language', 'Language') }}
              </span>
              <div class="flex items-center gap-1 bg-white dark:bg-[#182235] p-1 rounded-xl border border-slate-200 dark:border-white/10 shadow-xs">
                <button
                  v-for="opt in languageStore.options"
                  :key="opt.code"
                  @click="selectLanguage(opt.code)"
                  type="button"
                  class="px-2.5 py-1 rounded-lg text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F]"
                  :class="[
                    languageStore.currentLanguage === opt.code
                      ? 'bg-[#FAF3E7] dark:bg-[#101827] text-[#9A7418] dark:text-[#D6A94F] shadow-xs font-bold'
                      : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                  ]"
                  :aria-label="'Switch language to ' + opt.name"
                >
                  {{ opt.nativeName }}
                </button>
              </div>
            </div>

            <!-- Theme Toggle Section -->
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <Sparkles class="w-3.5 h-3.5 text-[#9A7418] dark:text-[#D6A94F]" />
                {{ languageStore.t('theme', 'Color Theme') }}
              </span>
              <button
                @click="handleThemeToggle"
                type="button"
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-white/15 bg-white dark:bg-[#182235] text-xs font-semibold text-slate-800 dark:text-slate-100 hover:border-[#9A7418] dark:hover:border-[#D6A94F] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F]"
                :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
              >
                <Sun v-if="theme.isDark" class="w-3.5 h-3.5 text-[#D6A94F]" />
                <Moon v-else class="w-3.5 h-3.5 text-slate-700" />
                <span>{{ theme.isDark ? 'Dark Mode' : 'Light Mode' }}</span>
              </button>
            </div>

            <!-- Primary Book Stay CTA -->
            <RouterLink
              to="/rooms"
              @click="closeMenu"
              class="block w-full text-center py-3.5 rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] text-xs font-bold tracking-[0.08em] uppercase shadow-lg shadow-[#D6A94F]/25 transition-all cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] active:scale-[0.99]"
            >
              {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
            </RouterLink>
          </div>
        </aside>
      </Transition>
    </Teleport>
  </header>
</template>
