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
  Sparkles,
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

// Navigation links with Title Case labels and consistent 0.08em tracking (Facilities removed)
const menus = computed(() => [
  { title: languageStore.t('home', 'Home'), route: '/' },
  { title: languageStore.t('rooms', 'Rooms & Suites'), route: '/rooms' },
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
    :class="[scrolled ? 'shadow-md shadow-slate-900/5 dark:shadow-black/40' : 'shadow-none']"
  >
    <div
      class="h-[2px] w-full bg-gradient-to-r from-transparent via-[#9A7418] dark:via-[#D6A94F] to-transparent opacity-80"
    />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[76px] flex items-center justify-between">
      <!-- Logo + Brand Emblem (56px) -->
      <RouterLink
        to="/"
        class="flex items-center gap-3 sm:gap-3.5 min-w-0 select-none rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#9A7418] dark:focus-visible:ring-[#D6A94F] group"
        aria-label="Executive Hotel Addis Ababa Home"
      >
        <!-- Responsive Emblem Container for clear visibility of crest details -->
        <div
          class="relative w-11 h-11 sm:w-14 sm:h-14 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-[#182235] p-1 shadow-sm border border-slate-200/90 dark:border-[#D6A746]/30 shrink-0 transition-transform duration-300 group-hover:scale-105 overflow-hidden"
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
            class="font-serif text-[15px] sm:text-[19px] lg:text-[20px] font-bold tracking-[0.06em] sm:tracking-[0.08em] text-slate-900 dark:text-white leading-tight uppercase whitespace-nowrap"
          >
            {{ guestHotelStore.hotelName }}
          </span>
          <span
            class="text-[8.5px] sm:text-[10px] font-bold text-[#9A7418] dark:text-[#D6A746] tracking-[0.06em] sm:tracking-[0.08em] uppercase mt-0.5 leading-none whitespace-nowrap"
          >
            {{
              (guestHotelStore.hotelCity || 'ADDIS ABABA').toUpperCase().replace('ADDISS', 'ADDIS')
            }}
            • LUXURY HOTEL
          </span>
        </div>
      </RouterLink>

      <!-- Desktop Navigation Menu (>= 1024px) -->
      <nav class="hidden lg:flex items-center gap-6 xl:gap-8 shrink-0" aria-label="Main Navigation">
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

    <!-- Floating Card Modal & Backdrop (< 1024px) -->
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
          class="fixed inset-0 bg-black/45 backdrop-blur-xs z-[998] lg:hidden flex justify-end items-start p-3 sm:p-5"
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
              class="w-full max-w-[340px] sm:max-w-[360px] bg-white dark:bg-[#111A28] text-slate-900 dark:text-white rounded-[26px] shadow-[0_20px_50px_-10px_rgba(0,0,0,0.3)] dark:shadow-[0_25px_60px_-10px_rgba(0,0,0,0.8)] border border-slate-200/90 dark:border-white/10 p-5 sm:p-6 flex flex-col gap-4 overflow-y-auto max-h-[calc(100vh-28px)]"
              @click.stop
              role="dialog"
              aria-modal="true"
              aria-label="Navigation Menu"
            >
              <!-- Card Header: Brand Identity, Theme Pill & Close Button -->
              <div class="flex items-center justify-between pb-1">
                <!-- Brand Identity -->
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50/80 dark:bg-[#182235] p-1 border border-blue-100 dark:border-[#D6A94F]/30 shrink-0 shadow-xs"
                  >
                    <img
                      :src="currentHotelLogo"
                      :alt="guestHotelStore.hotelName"
                      class="w-full h-full object-contain"
                      @error="
                        (e: any) => {
                          e.target.src = '/images/Hotel logo.png'
                        }
                      "
                    />
                  </div>
                  <div class="flex flex-col min-w-0">
                    <span
                      class="font-serif text-[15px] sm:text-base font-bold text-slate-900 dark:text-white tracking-tight truncate"
                    >
                      {{ guestHotelStore.hotelName }}
                    </span>
                    <span
                      class="text-[9px] font-bold text-[#9A7418] dark:text-[#D6A94F] tracking-[0.06em] uppercase"
                    >
                      {{
                        (guestHotelStore.hotelCity || 'ADDIS ABABA')
                          .toUpperCase()
                          .replace('ADDISS', 'ADDIS')
                      }}
                    </span>
                  </div>
                </div>

                <!-- Right Controls: Theme Switch Pill & Circular Close Button -->
                <div class="flex items-center gap-2 shrink-0">
                  <!-- Theme Pill Toggle (Moon & Sun) -->
                  <button
                    @click="handleThemeToggle"
                    type="button"
                    class="flex items-center gap-0.5 p-0.5 rounded-full bg-slate-100 dark:bg-white/10 border border-slate-200/70 dark:border-white/10 transition-colors cursor-pointer"
                    :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                  >
                    <span
                      class="w-6 h-6 flex items-center justify-center rounded-full transition-all"
                      :class="
                        theme.isDark ? 'bg-[#101827] text-[#D6A94F] shadow-xs' : 'text-slate-400'
                      "
                    >
                      <Moon class="w-3.5 h-3.5" />
                    </span>
                    <span
                      class="w-6 h-6 flex items-center justify-center rounded-full transition-all"
                      :class="
                        !theme.isDark ? 'bg-[#0F56B3] text-white shadow-xs' : 'text-slate-400'
                      "
                    >
                      <Sun class="w-3.5 h-3.5" />
                    </span>
                  </button>

                  <!-- Circular Close Button -->
                  <button
                    @click="closeMenu"
                    type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-white/10 hover:bg-slate-200 dark:hover:bg-white/20 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors cursor-pointer focus:outline-none"
                    aria-label="Close navigation menu"
                  >
                    <CloseIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Navigation Links with ArrowUpRight -->
              <nav class="flex flex-col py-1" aria-label="Mobile Navigation Links">
                <RouterLink
                  v-for="menu in menus"
                  :key="menu.route"
                  :to="menu.route"
                  @click="closeMenu"
                  class="flex items-center justify-between py-2.5 sm:py-3 px-1 text-base font-serif transition-colors group border-b border-slate-100/80 dark:border-white/5 last:border-0"
                  :class="[
                    isItemActive(menu.route)
                      ? 'text-[#0F56B3] dark:text-[#D6A94F] font-bold'
                      : 'text-slate-800 dark:text-slate-200 hover:text-[#0F56B3] dark:hover:text-[#D6A94F] font-medium',
                  ]"
                >
                  <span>{{ menu.title }}</span>
                  <ArrowUpRight
                    class="w-4 h-4 text-[#0F56B3] dark:text-[#D6A94F] transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                  />
                </RouterLink>
              </nav>

              <!-- Language Selector Section -->
              <div class="pt-2 border-t border-slate-100 dark:border-white/10">
                <span
                  class="block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500 mb-2"
                >
                  {{ languageStore.t('language', 'Language') }}
                </span>
                <div
                  class="inline-flex items-center gap-1 bg-slate-100 dark:bg-white/5 p-1 rounded-full border border-slate-200/60 dark:border-white/10"
                >
                  <button
                    v-for="opt in languageStore.options"
                    :key="opt.code"
                    @click="selectLanguage(opt.code)"
                    type="button"
                    class="px-3.5 py-1 text-xs font-semibold rounded-full transition-all focus:outline-none cursor-pointer"
                    :class="[
                      languageStore.currentLanguage === opt.code
                        ? 'bg-[#0F56B3] dark:bg-[#D6A94F] text-white dark:text-[#101827] shadow-xs'
                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                    ]"
                  >
                    {{ opt.nativeName }}
                  </button>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex flex-col gap-2.5 pt-1">
                <!-- Secondary Button: Sign In / Dashboard -->
                <RouterLink
                  :to="authStore.isAuthenticated ? '/admin' : '/login'"
                  @click="closeMenu"
                  class="w-full py-2.5 sm:py-3 rounded-2xl border border-slate-200/90 dark:border-white/15 bg-white dark:bg-white/5 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-white/10 text-xs sm:text-sm font-semibold tracking-wide text-center transition-all shadow-xs block cursor-pointer"
                >
                  {{
                    authStore.isAuthenticated
                      ? languageStore.t('dashboard', 'Dashboard')
                      : languageStore.t('sign_in', 'Sign in')
                  }}
                </RouterLink>

                <!-- Primary Button: Book Your Stay -->
                <RouterLink
                  to="/rooms"
                  @click="closeMenu"
                  class="w-full py-3 sm:py-3.5 rounded-2xl bg-[#0F56B3] hover:bg-[#0c448d] dark:bg-[#D6A94F] dark:hover:bg-[#C59B3F] text-white dark:text-[#101827] font-semibold text-xs sm:text-sm tracking-wide shadow-md shadow-[#0F56B3]/20 dark:shadow-black/30 text-center transition-all block active:scale-[0.99] cursor-pointer"
                >
                  {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
                </RouterLink>
              </div>
            </aside>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </header>
</template>
