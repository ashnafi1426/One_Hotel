<script setup lang="ts">
import { onMounted } from 'vue'
import Sidebar from '../components/dashboard/Sidebar.vue'
import Navbar from '../components/dashboard/Navbar.vue'
import { useThemeStore } from '../stores/theme'
import { useSidebarStore } from '../stores/sidebarStore'
import { useLanguageStore } from '../stores/language'

const themeStore = useThemeStore()
const sidebarStore = useSidebarStore()
const languageStore = useLanguageStore()

onMounted(() => {
  themeStore.initTheme()
})

const closeMobileSidebar = () => {
  if (window.innerWidth < 1024) {
    sidebarStore.closeMobile()
  }
}
</script>

<template>
  <div
    class="h-screen flex bg-white dark:bg-slate-950 overflow-hidden transition-colors duration-300"
  >
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarStore.isMobileOpen"
        @click="sidebarStore.closeMobile()"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 lg:hidden"
        role="presentation"
      ></div>
    </Transition>

    <div
      :class="[
        'h-screen flex flex-col flex-shrink-0 transition-all duration-300 ease-in-out',
        'fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] shadow-2xl lg:shadow-none',
        'lg:sticky lg:top-0 lg:left-0',
        sidebarStore.sidebarCollapsed && sidebarStore.hoverExpand ? 'lg:z-50' : 'lg:z-30',
        sidebarStore.sidebarCollapsed ? 'lg:w-20' : 'lg:w-72',
        sidebarStore.isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
    >
      <div
        :class="[
          'h-full transition-all duration-300 ease-in-out',
          sidebarStore.sidebarCollapsed && sidebarStore.hoverExpand
            ? 'lg:absolute lg:top-0 lg:left-0 lg:w-72 lg:shadow-2xl lg:z-50'
            : 'w-full',
        ]"
      >
        <Sidebar @navigate="closeMobileSidebar" />
      </div>
    </div>
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <Navbar />

      <main
        class="flex-1 overflow-y-auto bg-slate-50/50 dark:bg-slate-950 transition-colors duration-300"
      >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
          <div>
            <slot name="header"></slot>
          </div>

          <slot></slot>
        </div>
      </main>

      <footer
        class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 backdrop-blur-sm px-4 sm:px-6 lg:px-8 py-3 transition-colors flex-shrink-0"
      >
        <div
          class="w-full flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400"
        >
          <div
            class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4 text-center sm:text-left"
          >
            <span class="whitespace-nowrap"
              >&copy; 2026
              {{ languageStore.t('hotel_management_system', 'Hotel Management System') }}</span
            >
            <span class="hidden sm:inline text-slate-300 dark:text-slate-600">&bull;</span>
            <span class="whitespace-nowrap">{{
              languageStore.t('all_rights_reserved', 'All rights reserved')
            }}</span>
          </div>

          <div class="flex items-center gap-4">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span class="font-medium text-emerald-600 dark:text-emerald-400">{{
                languageStore.t('system_online', 'System Online')
              }}</span>
            </span>
            <span class="text-slate-300 dark:text-slate-600">&bull;</span>
            <span>v2.0.0</span>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>
