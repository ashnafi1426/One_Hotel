<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ref, onMounted, watch } from 'vue'
import menuService from '@/services/menuService'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { Utensils, ArrowRight, Sparkles, Clock, MapPin } from 'lucide-vue-next'

const router = useRouter()
const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface MenuItem {
  id: string
  name: string
  image: string
  description: string
  price: number
}

const featuredMenu = ref<MenuItem[]>([])
const loading = ref(false)

const defaultDishPlaceholder = '/images/gallery/fine-dining.jpg'

const fallbackMenu: MenuItem[] = [
  {
    id: 'm1',
    name: 'Prime Aged Ribeye Steak',
    image: '/images/gallery/fine-dining.jpg',
    description: 'Tender grain-fed beef seared with aromatic rosemary butter and roasted truffle roots.',
    price: 1250,
  },
  {
    id: 'm2',
    name: 'Authentic Special Tibs & Injera',
    image: '/images/gallery/chef-special.jpg',
    description: 'Traditional sauteed beef with rosemary, onions, and jalapenos served on fresh teff injera.',
    price: 850,
  },
  {
    id: 'm3',
    name: 'Mediterranean Sea Bass',
    image: '/images/gallery/restaurant-interior.jpg',
    description: 'Pan-roasted wild sea bass with lemon herb veloute, asparagus, and saffron potato puree.',
    price: 1100,
  },
]

function reserveTable() {
  router.push('/contact')
}

function viewRestaurantMenu() {
  router.push('/menu')
}

function formatPrice(price: number) {
  const currency = guestHotelStore.currency || 'ETB'
  return `${(price || 0).toLocaleString()} ${currency}`
}

async function loadFeaturedMenu() {
  loading.value = true
  try {
    const response = await menuService.getMenuItems({ per_page: 3, flat: 1, is_available: 1 })
    const items = response.data?.data || response.data
    if (Array.isArray(items) && items.length > 0) {
      featuredMenu.value = items.slice(0, 3).map((item: any) => ({
        id: item.id || String(Math.random()),
        name: item.name,
        image: item.image || item.image_url || defaultDishPlaceholder,
        description: item.description || 'Delicious gourmet chef specialty dish.',
        price: item.total_price != null ? item.total_price : item.price || 0,
      }))
    } else {
      featuredMenu.value = fallbackMenu
    }
  } catch (err) {
    console.error('[RestaurantSection] Failed to load featured menu:', err)
    featuredMenu.value = fallbackMenu
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadFeaturedMenu()
})

watch(
  () => guestHotelStore.hotelId,
  () => {
    loadFeaturedMenu()
  },
)
</script>

<template>
  <section
    id="dining"
    class="py-16 sm:py-20 lg:py-28 transition-colors duration-300 font-sans border-b border-slate-200/80 dark:border-[#26354D]/70 bg-[#F7F7F5] dark:bg-[#0B111D]"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      <!-- Section Header -->
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-[0.2em] bg-[#D6A94F]/10 text-[#8A641A] dark:text-[#D6A94F] border border-[#D6A94F]/30">
          <Sparkles class="w-3.5 h-3.5 text-[#D6A94F]" />
          <span>{{ guestHotelStore.hotelName }} • {{ languageStore.t('signature_dining', 'Gastronomy & Lounge') }}</span>
        </div>

        <h2
          class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-[#101827] dark:text-white tracking-tight"
        >
          {{ languageStore.t('culinary_experience', 'An Exceptional Culinary Journey') }}
        </h2>

        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-light leading-relaxed">
          {{
            languageStore.t(
              'culinary_experience_desc',
              'Indulge in rich culinary traditions and modern international gastronomy crafted by master chefs using the finest regional produce.',
            )
          }}
        </p>

        <!-- Hours & Dining Location info -->
        <div class="flex items-center justify-center gap-6 pt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
          <span class="flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-[#D6A94F]" />
            {{ guestHotelStore.diningHours }}
          </span>
          <span>•</span>
          <span class="flex items-center gap-1.5">
            <MapPin class="w-3.5 h-3.5 text-[#D6A94F]" />
            {{ guestHotelStore.hotelName }} Ground Level
          </span>
        </div>
      </div>

      <!-- Featured Menu Items Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div
          v-for="dish in featuredMenu"
          :key="dish.id"
          class="group bg-white dark:bg-[#182235] border border-slate-200/90 dark:border-[#26354D] rounded-3xl overflow-hidden shadow-md hover:shadow-2xl hover:border-[#D6A94F]/50 transition-all duration-500 flex flex-col justify-between"
        >
          <!-- Dish Visual with price tag -->
          <div class="relative aspect-[16/10] overflow-hidden bg-slate-900">
            <img
              :src="dish.image"
              :alt="dish.name"
              class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-700"
              loading="lazy"
              @error="(e: any) => (e.target.src = '/images/gallery/fine-dining.jpg')"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20" />

            <div
              class="absolute bottom-3 right-3 px-3.5 py-1.5 bg-[#101827]/90 backdrop-blur-md text-[#D6A94F] font-bold text-xs rounded-xl border border-[#D6A94F]/40 shadow-lg"
            >
              {{ formatPrice(dish.price) }}
            </div>
          </div>

          <!-- Dish Info -->
          <div class="p-6 space-y-3 flex-1 flex flex-col justify-between">
            <div class="space-y-2">
              <h3
                class="font-serif text-lg font-bold text-[#101827] dark:text-white group-hover:text-[#D6A94F] transition-colors leading-snug"
              >
                {{ dish.name }}
              </h3>

              <p class="text-xs text-slate-600 dark:text-slate-400 font-light leading-relaxed line-clamp-2">
                {{ dish.description }}
              </p>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-[#26354D]/60 flex items-center justify-between">
              <span class="text-[11px] font-bold text-[#D6A94F] uppercase tracking-wider flex items-center gap-1">
                <Utensils class="w-3 h-3" />
                Chef Selection
              </span>

              <button
                @click="reserveTable"
                class="text-xs font-bold text-[#101827] dark:text-slate-200 hover:text-[#D6A94F] flex items-center gap-1 transition cursor-pointer"
              >
                <span>Reserve Table</span>
                <ArrowRight class="w-3.5 h-3.5 text-[#D6A94F]" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Dining Actions Row -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
        <button
          @click="reserveTable"
          class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-[#D6A94F] hover:bg-[#C59B3F] text-[#101827] font-black text-xs uppercase tracking-widest shadow-lg shadow-[#D6A94F]/20 hover:shadow-xl transition-all cursor-pointer flex items-center justify-center gap-2"
        >
          <Utensils class="w-4 h-4 text-[#101827]" />
          <span>{{ languageStore.t('reserve_a_table', 'Reserve a Table') }}</span>
        </button>

        <button
          @click="viewRestaurantMenu"
          class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-[#182235] hover:bg-[#101827] text-white font-bold text-xs uppercase tracking-widest border border-[#26354D] hover:border-[#D6A94F]/50 transition-all cursor-pointer flex items-center justify-center gap-2"
        >
          <span>{{ languageStore.t('explore_menu', 'View Full Menu') }}</span>
          <ArrowRight class="w-4 h-4 text-[#D6A94F]" />
        </button>
      </div>
    </div>
  </section>
</template>
