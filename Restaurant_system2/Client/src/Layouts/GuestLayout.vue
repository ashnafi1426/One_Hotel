<script setup lang="ts">
import { onMounted } from 'vue'
import GuestNavbar from '@/components/guest/GuestNavbar.vue'
import Footer from '@/components/guest/Footer.vue'
import { useThemeStore } from '@/stores/themeStore'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const theme = useThemeStore()
const guestHotelStore = useGuestHotelStore()

onMounted(() => {
  theme.initializeTheme()
  if (guestHotelStore.availableHotels.length === 0) {
    guestHotelStore.fetchAvailableHotels()
  }
})
</script>

<template>
  <div
    class="min-h-screen bg-[#F7F7F5] dark:bg-[#0B111D] text-[#20242B] dark:text-slate-100 transition-colors duration-300 font-sans"
  >
    <GuestNavbar />
    <main class="min-h-screen">
      <slot />
    </main>
    <Footer />
  </div>
</template>
