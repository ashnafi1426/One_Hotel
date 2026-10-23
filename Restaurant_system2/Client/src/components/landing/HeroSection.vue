<template>
  <!-- ─────────────────────────────────────────────────────────────
       Hero Section — Executive Hotel, Addis Ababa
       z-layers: z-0 (bg image) → z-[5] (overlay) → z-10 (content)
  ───────────────────────────────────────────────────────────────── -->
  <section
    id="home"
    class="relative w-full min-h-[90vh] md:min-h-screen flex flex-col justify-center items-center text-center px-4 md:px-8 py-16 md:py-24 overflow-hidden"
    aria-label="Hero — Executive Hotel"
  >
    <!-- ── Background: full-bleed hotel facade photograph ── -->
    <picture class="absolute inset-0 z-0 w-full h-full">
      <source srcset="/images/hero/sheraton_fountain_wide.jpg" type="image/jpeg" />
      <img
        src="/images/hero/sheraton_fountain_wide.jpg"
        alt="Illuminated Executive Hotel facade with fountain at night"
        class="w-full h-full object-cover object-center"
        fetchpriority="high"
        decoding="async"
      />
    </picture>

    <!-- ── Overlay: luxury scrim preserving warm window glow ── -->
    <div
      class="absolute inset-0 z-[5] bg-gradient-to-b from-slate-950/70 via-slate-900/40 to-slate-950/80 pointer-events-none"
      aria-hidden="true"
    />

    <!-- ── Content layer ── -->
    <div class="relative z-10 flex flex-col items-center text-center w-full max-w-5xl mx-auto">

      <!-- Header Badge (glassmorphism pill) -->
      <div
        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-900/60 border border-slate-700/60 backdrop-blur-md mb-6 select-none"
        role="note"
        aria-label="Executive Hotel, Addis Ababa"
      >
        <!-- Sparkle icon -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
          class="w-3.5 h-3.5 text-amber-400 flex-shrink-0"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.466 7.89l.813-2.846A.75.75 0 019 4.5zM18 1.5a.75.75 0 01.728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 010 1.456l-1.036.258c-.94.236-1.674.97-1.91 1.91l-.258 1.036a.75.75 0 01-1.456 0l-.258-1.036a2.625 2.625 0 00-1.91-1.91l-1.036-.258a.75.75 0 010-1.456l1.036-.258a2.625 2.625 0 001.91-1.91l.258-1.036A.75.75 0 0118 1.5z"
            clip-rule="evenodd"
          />
        </svg>
        <span class="text-white text-xs tracking-[0.22em] font-semibold uppercase leading-none">
          Executive Hotel&nbsp;&bull;&nbsp;Addis Ababa
        </span>
      </div>

      <!-- Main Headline -->
      <h1
        class="font-serif text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-medium tracking-tight text-white leading-[1.15] max-w-4xl mx-auto"
      >
        Timeless Luxury
        <br />
        <span class="italic font-normal font-serif text-[#C89B3C]">&amp;</span>
        Unmatched Comfort
      </h1>

      <!-- Subtitle -->
      <p
        class="font-sans text-sm sm:text-base md:text-lg text-slate-300 tracking-wider font-light mt-4 mb-8"
      >
        Luxury Hotel &amp; Restaurant Experience
      </p>

      <!-- CTA Buttons -->
      <div
        class="flex flex-col sm:flex-row items-center justify-center gap-4 w-full max-w-md mx-auto"
      >
        <!-- Primary — Book Your Stay -->
        <button
          id="hero-cta-book"
          type="button"
          class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-full px-7 py-3.5 bg-[#C89B3C] hover:bg-[#B3882F] text-slate-950 font-semibold text-xs md:text-sm tracking-widest uppercase shadow-lg transition-transform duration-200 hover:scale-[1.02] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400"
          @click="handleBook"
        >
          <!-- Calendar icon -->
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="w-4 h-4 flex-shrink-0"
            aria-hidden="true"
          >
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
          </svg>
          Book Your Stay
        </button>

        <!-- Secondary — Explore Rooms & Suites -->
        <button
          id="hero-cta-explore"
          type="button"
          class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-full px-7 py-3.5 bg-slate-900/50 hover:bg-slate-800/70 text-white border border-slate-600/60 backdrop-blur-md text-xs md:text-sm tracking-widest uppercase shadow-md transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400"
          @click="handleExplore"
        >
          <!-- Bed icon -->
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="w-4 h-4 flex-shrink-0"
            aria-hidden="true"
          >
            <path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8" />
            <path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4" />
            <line x1="12" y1="4" x2="12" y2="10" />
            <line x1="2" y1="20" x2="22" y2="20" />
          </svg>
          Explore Rooms &amp; Suites
        </button>
      </div>

    </div>
  </section>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function handleBook(): void {
  router.push({ name: 'reservations' }).catch(() => {
    document.getElementById('contact')?.scrollIntoView({ behavior: 'smooth' })
  })
}

function handleExplore(): void {
  router.push({ name: 'rooms' }).catch(() => {
    document.getElementById('rooms')?.scrollIntoView({ behavior: 'smooth' })
  })
}
</script>
