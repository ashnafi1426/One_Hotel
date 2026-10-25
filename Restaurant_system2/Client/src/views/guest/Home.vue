<script setup lang="ts">
import HeroSection from '@/components/guest/HeroSection.vue'
import FeaturedRooms from '@/components/guest/FeaturedRoom.vue'
import HotelFacilitaties from '@/components/guest/HotelFacilitaties.vue'
import RestaurantSection from '@/components/guest/RestaurantSection.vue'
import Testimonial from '@/components/guest/Testimonial.vue'
import ContactSection from '@/components/landing/ContactSection.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
</script>

<template>
  <GuestLayout>
    <div
      class="bg-[#F7F7F5] dark:bg-[#0B111D] min-h-screen text-[#20242B] dark:text-slate-100 transition-colors duration-300 overflow-x-hidden"
    >
      <HeroSection />

      <FeaturedRooms />
      <HotelFacilitaties />
      <RestaurantSection />
      <Testimonial />
      <ContactSection />
    </div>
  </GuestLayout>
</template>
