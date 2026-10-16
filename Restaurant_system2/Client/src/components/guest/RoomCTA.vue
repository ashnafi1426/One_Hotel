<template>
  <div class="relative overflow-hidden rounded-3xl bg-[#101827] border border-[#26354D] p-8 sm:p-12 md:p-16 text-center text-white shadow-2xl">
    <!-- Top and bottom gold accent hairlines -->
    <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#D6A94F] to-transparent opacity-80" />
    <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-[#D6A94F]/10 rounded-full blur-3xl pointer-events-none" />
    <div class="absolute -left-20 -top-20 w-64 h-64 bg-[#D6A94F]/5 rounded-full blur-3xl pointer-events-none" />

    <div class="relative z-10 max-w-2xl mx-auto space-y-4">
      <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] bg-[#D6A94F]/10 text-[#D6A94F] border border-[#D6A94F]/30">
        <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
        <span>{{ languageStore.t('experience_luxury', 'A Luxury Experience') }}</span>
      </div>

      <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-white tracking-tight leading-tight">
        {{ languageStore.t('ready_to_book', 'Reserve Your Private Sanctuary') }}
      </h2>

      <p class="text-slate-300 font-light text-sm sm:text-base leading-relaxed">
        {{
          languageStore.t(
            'ready_to_book_desc',
            'Select your dates and experience unmatched hospitality, exquisite fine dining, and peaceful relaxation.',
          )
        }}
      </p>

      <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3.5">
        <button
          @click="scrollToRooms"
          class="w-full sm:w-auto px-8 py-3.5 bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black text-xs uppercase tracking-widest rounded-full shadow-lg shadow-[#D6A94F]/25 hover:shadow-xl transition-all cursor-pointer flex items-center justify-center gap-2"
        >
          <Calendar class="w-4 h-4 text-[#101827]" />
          <span>{{ languageStore.t('browse_rooms', 'Select Your Room') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useLanguageStore } from '@/stores/language'
import { Sparkles, Calendar } from 'lucide-vue-next'

const languageStore = useLanguageStore()

function scrollToRooms() {
  const roomsSection = document.getElementById('rooms-section')
  if (roomsSection) {
    roomsSection.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>
