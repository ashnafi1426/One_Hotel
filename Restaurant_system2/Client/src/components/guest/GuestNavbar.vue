<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useThemeStore } from '@/stores/theme'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useLanguageStore, type LanguageCode } from '@/stores/language'
import { useAuthStore } from '@/stores/auth'
import LanguageSelector from '@/components/common/LanguageSelector.vue'
import { storageUrl } from '@/utils/urlHelpers'
import {
  Sun,
  Moon,
  Menu as MenuIcon,
  X as CloseIcon,
  Globe,
  ArrowUpRight,
} from 'lucide-vue-next'

const route = useRoute()
const theme = useThemeStore()
const guestHotelStore = useGuestHotelStore()
const languageStore = useLanguageStore()
const authStore = useAuthStore()

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
    if (
      trimmed.startsWith('/storage/') ||
      trimmed.startsWith('storage/') ||
      trimmed.startsWith('hotels/')
    ) {
      return storageUrl(trimmed)
    }
    return storageUrl(trimmed)
  }

  return '/images/Hotel logo transparent.png'
})

const currentHotelName = computed(() => {
  return guestHotelStore.hotelName || 'Executive Hotel'
})

const mobileMenu = ref(false)
const scrolled = ref(false)

function isItemActive(itemRoute: string) {
  if (itemRoute === '/') {
    return route.path === '/' || route.name === 'home'
  }
  return route.path === itemRoute || route.path.startsWith(`${itemRoute}/`)
}

// Navigation links with clean uppercase titles matching requirements
const menus = computed(() => [
  { title: 'HOME', route: '/' },
  { title: 'ROOMS', route: '/rooms' },
  { title: 'MENU', route: '/menu' },
  { title: 'GALLERY', route: '/gallery' },
  { title: 'ABOUT', route: '/about' },
  { title: 'CONTACT', route: '/contact' },
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
    class="sticky top-0 z-50 w-full transition-all duration-300 select-none bg-[#0B1320] border-0 border-none outline-none shadow-none"
    :class="[scrolled ? 'shadow-lg shadow-black/40 backdrop-blur-md bg-[#0B1320]/95' : '']"
  >
    <!-- Main Header Bar Container -->
    <div class="max-w-7xl mx-auto px-6 md:px-12 py-3.5 flex items-center justify-between">
      <!-- 1. Brand Logo + Typography -->
      <RouterLink
        to="/"
        class="flex items-center gap-3 sm:gap-3.5 min-w-0 select-none group focus:outline-none focus-visible:ring-2 focus-visible:ring-[#C89B3C] rounded-lg"
        :aria-label="`${currentHotelName} Home`"
      >
        <!-- Logo Emblem Container: Clean & Transparent (No solid white square background) -->
        <div
          class="relative w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-105"
        >
          <img
            :src="currentHotelLogo"
            :alt="`${currentHotelName} Logo`"
            class="w-full h-full object-contain select-none drop-shadow-md"
            width="48"
            height="48"
            decoding="async"
            @error="
              (e: any) => {
                e.target.src = '/images/Hotel logo transparent.png'
              }
            "
          />
        </div>

        <!-- Brand Typography - Single Hotel Name Line -->
        <div class="flex items-center">
          <span
            class="font-sans text-[14px] sm:text-[15px] lg:text-[16px] font-medium tracking-[0.35em] text-white leading-none uppercase whitespace-nowrap"
          >
            {{ currentHotelName }}
          </span>
        </div>
      </RouterLink>

      <!-- 2. Desktop Navigation Links (>= 1024px) -->
      <nav class="hidden lg:flex items-center gap-6 xl:gap-8 shrink-0" aria-label="Main Navigation">
        <RouterLink
          v-for="menu in menus"
          :key="menu.route"
          :to="menu.route"
          class="text-sm uppercase tracking-widest font-medium transition-colors duration-200 relative py-1.5 focus:outline-none focus-visible:ring-1 focus-visible:ring-[#C89B3C] rounded-sm px-1"
          :class="[
            isItemActive(menu.route)
              ? 'text-[#C89B3C] font-semibold'
              : 'text-slate-300 hover:text-white',
          ]"
        >
          {{ menu.title }}

          <!-- Active Link Accent Gold Bar Underline Indicator -->
          <span
            v-if="isItemActive(menu.route)"
            class="absolute -bottom-1.5 inset-x-0 mx-auto w-full h-[2px] bg-[#C89B3C] rounded-full"
          />
        </RouterLink>
      </nav>

      <!-- 3. Desktop Right Controls Toolbar (>= 1024px) -->
      <div class="hidden lg:flex items-center gap-3.5 shrink-0">
        <!-- Language Selector Pill -->
        <LanguageSelector variant="header" />

        <!-- Theme Toggle Circular Button -->
        <button
          @click="handleThemeToggle"
          type="button"
          class="w-9 h-9 flex items-center justify-center rounded-full border border-slate-700/60 bg-slate-900/40 hover:bg-slate-900/80 text-slate-300 hover:text-amber-400 hover:border-[#C89B3C]/60 transition-colors duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#C89B3C]"
          :title="
            theme.isDark
              ? languageStore.t('light_mode', 'Light Mode')
              : languageStore.t('dark_mode', 'Dark Mode')
          "
          :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="theme.isDark" class="w-4 h-4 text-[#C89B3C]" />
          <Moon v-else class="w-4 h-4 text-slate-300" />
        </button>

        <!-- "Book Your Stay" Button: Solid warm gold with crisp dark navy text -->
        <RouterLink
          to="/rooms"
          class="h-9 px-6 flex items-center justify-center rounded-full bg-[#C89B3C] hover:bg-[#B3882F] text-[#0B1320] font-semibold text-xs tracking-wider uppercase shadow-md transition-all duration-200 whitespace-nowrap active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#C89B3C]"
        >
          {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
        </RouterLink>
      </div>

      <!-- 4. Mobile & Tablet Navigation Controls (< 1024px) -->
      <div class="flex items-center gap-2.5 lg:hidden">
        <!-- Compact Language Selector on Mobile -->
        <LanguageSelector variant="header" :show-label="false" />

        <!-- Theme Toggle on Mobile -->
        <button
          @click="handleThemeToggle"
          type="button"
          class="w-8 h-8 flex items-center justify-center rounded-full border border-slate-700/60 bg-slate-900/40 text-slate-300 hover:text-amber-400 transition-colors cursor-pointer"
          :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="theme.isDark" class="w-3.5 h-3.5 text-[#C89B3C]" />
          <Moon v-else class="w-3.5 h-3.5 text-slate-300" />
        </button>

        <!-- Clean Hamburger Menu Toggle Button -->
        <button
          @click="toggleMenu"
          type="button"
          class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-700/60 bg-slate-900/40 text-slate-200 hover:text-white hover:border-[#C89B3C]/60 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#C89B3C]"
          :aria-expanded="mobileMenu"
          aria-label="Toggle navigation menu"
        >
          <MenuIcon v-if="!mobileMenu" class="w-5 h-5" />
          <CloseIcon v-else class="w-5 h-5 text-[#C89B3C]" />
        </button>
      </div>
    </div>

    <!-- 5. Floating Mobile & Tablet Navigation Drawer (< 1024px) -->
    <Teleport to="body">
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
          class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[998] lg:hidden flex justify-end items-start p-3 sm:p-5"
          @click="closeMenu"
          aria-hidden="true"
        >
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-2"
            appear
          >
            <aside
              class="w-full max-w-[340px] sm:max-w-[360px] bg-[#0B1320] text-white rounded-3xl shadow-2xl border border-slate-800 p-5 sm:p-6 flex flex-col gap-4 overflow-y-auto max-h-[calc(100vh-28px)]"
              @click.stop
              role="dialog"
              aria-modal="true"
              aria-label="Navigation Menu"
            >
              <!-- Drawer Header: Brand Identity & Close Button -->
              <div class="flex items-center justify-between pb-2 border-b border-slate-800/80">
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-8 h-8 flex items-center justify-center shrink-0">
                    <img
                      :src="currentHotelLogo"
                      :alt="`${currentHotelName} Logo`"
                      class="w-full h-full object-contain"
                      width="32"
                      height="32"
                      decoding="async"
                    />
                  </div>
                  <div class="flex items-center">
                    <span class="font-sans text-[12px] font-medium text-white tracking-[0.3em] uppercase leading-none whitespace-nowrap">
                      {{ currentHotelName }}
                    </span>
                  </div>
                </div>

                <button
                  @click="closeMenu"
                  type="button"
                  class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors cursor-pointer"
                  aria-label="Close navigation menu"
                >
                  <CloseIcon class="w-4 h-4" />
                </button>
              </div>

              <!-- Drawer Navigation Links -->
              <nav class="flex flex-col py-1" aria-label="Mobile Navigation Links">
                <RouterLink
                  v-for="menu in menus"
                  :key="menu.route"
                  :to="menu.route"
                  @click="closeMenu"
                  class="flex items-center justify-between py-2.5 px-2 text-sm font-medium tracking-widest uppercase transition-colors group border-b border-slate-800/60 last:border-0"
                  :class="[
                    isItemActive(menu.route)
                      ? 'text-[#C89B3C] font-semibold bg-slate-900/60 rounded-lg'
                      : 'text-slate-300 hover:text-white',
                  ]"
                >
                  <span>{{ menu.title }}</span>
                  <ArrowUpRight
                    class="w-4 h-4 text-[#C89B3C] transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                  />
                </RouterLink>
              </nav>

              <!-- Language Selection in Drawer -->
              <div class="pt-2 border-t border-slate-800/80">
                <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
                  {{ languageStore.t('language', 'Language') }}
                </span>
                <div class="inline-flex items-center gap-1.5 bg-slate-900/80 p-1 rounded-full border border-slate-800">
                  <button
                    v-for="opt in languageStore.options"
                    :key="opt.code"
                    @click="selectLanguage(opt.code)"
                    type="button"
                    class="px-3.5 py-1 text-xs font-semibold rounded-full transition-all cursor-pointer"
                    :class="[
                      languageStore.currentLanguage === opt.code
                        ? 'bg-[#C89B3C] text-[#0B1320] shadow-sm'
                        : 'text-slate-400 hover:text-white',
                    ]"
                  >
                    {{ opt.nativeName }}
                  </button>
                </div>
              </div>

              <!-- Drawer Action Buttons -->
              <div class="flex flex-col gap-2.5 pt-2">
                <RouterLink
                  to="/rooms"
                  @click="closeMenu"
                  class="w-full py-3 rounded-full bg-[#C89B3C] hover:bg-[#B3882F] text-[#0B1320] font-semibold text-xs tracking-wider uppercase text-center shadow-md transition-all block active:scale-[0.99] cursor-pointer"
                >
                  {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
                </RouterLink>

                <RouterLink
                  :to="authStore.isAuthenticated ? '/admin' : '/login'"
                  @click="closeMenu"
                  class="w-full py-2.5 rounded-full border border-slate-700/60 bg-slate-900/40 text-slate-300 hover:text-white text-xs font-medium tracking-wider uppercase text-center transition-all block cursor-pointer"
                >
                  {{
                    authStore.isAuthenticated
                      ? languageStore.t('dashboard', 'Dashboard')
                      : languageStore.t('sign_in', 'Sign In')
                  }}
                </RouterLink>
              </div>
            </aside>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </header>
</template>

<style scoped>
header {
  border: none !important;
  border-bottom: none !important;
  border-top: none !important;
  outline: none !important;
}
</style>
