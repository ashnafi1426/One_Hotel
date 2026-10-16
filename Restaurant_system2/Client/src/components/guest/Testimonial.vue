<script setup lang="ts">
import { Star, Quote, Sparkles } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Testimonial {
  id: string
  name: string
  country: string
  image: string
  rating: number
  comment: string
}

const testimonials: Testimonial[] = [
  {
    id: '1',
    name: 'Emily Johnson',
    country: 'United States',
    image: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&h=200&fit=crop',
    rating: 5,
    comment:
      'The hotel exceeded every expectation. Our suite was immaculate with breathtaking views, the staff were attentive, and the dining was truly world-class.',
  },
  {
    id: '2',
    name: 'Michael Brown',
    country: 'Canada',
    image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop',
    rating: 5,
    comment:
      'A sanctuary of peace in the city. The check-in was seamless, the concierge arranged everything flawlessly, and the spa was deeply rejuvenating.',
  },
  {
    id: '3',
    name: 'Sophia Martinez',
    country: 'Spain',
    image: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=200&h=200&fit=crop',
    rating: 5,
    comment:
      'One of the finest luxury hotel stays I have experienced. Exceptional hospitality, superb culinary options, and absolute attention to detail.',
  },
]
</script>

<template>
  <section
    class="bg-white dark:bg-[#101827] py-16 sm:py-20 lg:py-28 border-b border-slate-200/80 dark:border-[#26354D]/70 transition-colors duration-300 font-sans"
  >
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-12">
      <!-- Section Header -->
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-[0.2em] bg-[#D6A94F]/10 text-[#8A641A] dark:text-[#D6A94F] border border-[#D6A94F]/30">
          <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
          <span>{{ languageStore.t('guest_reviews', 'Guest Perspectives') }}</span>
        </div>

        <h2
          class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-[#101827] dark:text-white tracking-tight"
        >
          {{ languageStore.t('what_guests_say', 'Memories from Our Guests') }}
        </h2>

        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-light leading-relaxed">
          {{
            languageStore.t(
              'what_guests_say_desc',
              'Authentic feedback from international travelers who experienced the luxury and hospitality of ' +
                guestHotelStore.hotelName +
                '.',
            )
          }}
        </p>
      </div>

      <!-- Testimonials Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div
          v-for="item in testimonials"
          :key="item.id"
          class="rounded-3xl bg-[#F7F7F5] dark:bg-[#182235] border border-slate-200/90 dark:border-[#26354D] p-7 shadow-sm flex flex-col justify-between space-y-5 hover:border-[#D6A94F]/50 hover:shadow-xl transition-all duration-300"
        >
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1 text-[#D6A94F]">
                <Star v-for="i in item.rating" :key="i" class="w-4 h-4 fill-[#D6A94F] text-[#D6A94F]" />
              </div>
              <Quote class="w-7 h-7 text-[#D6A94F]/30" />
            </div>

            <p
              class="font-serif text-sm sm:text-base text-slate-700 dark:text-slate-200 leading-relaxed italic"
            >
              "{{ item.comment }}"
            </p>
          </div>

          <div class="pt-4 border-t border-slate-200/80 dark:border-[#26354D]/60 flex items-center gap-3.5">
            <img
              :src="item.image"
              :alt="item.name"
              class="w-11 h-11 rounded-full object-cover border-2 border-[#D6A94F]/40"
              loading="lazy"
            />
            <div>
              <h3 class="text-xs font-bold text-[#101827] dark:text-white">{{ item.name }}</h3>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium tracking-wider uppercase">{{ item.country }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
