<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Search, Clock, Utensils, Sparkles, RefreshCw, AlertCircle } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import menuService from '@/services/menuService'
import { resolveFoodImageUrl, onFoodImageError } from '@/utils/foodImages'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface MenuItem {
  id: string | number
  name: string
  category?: any
  price: number | string
  formatted_price?: string
  description?: string
  ingredients?: string
  image?: string | null
  is_available?: boolean
}

interface CategoryItem {
  id: string | number
  name: string
  slug?: string
  count?: number
}

// Dynamic state from backend
const menuItems = ref<MenuItem[]>([])
const backendCategories = ref<CategoryItem[]>([])
const activeCategory = ref<string>('All')
const searchQuery = ref('')
const loading = ref(true)
const error = ref<string | null>(null)

function getItemCategory(item: MenuItem): string {
  if (!item) return 'Other'
  if (typeof item.category === 'string' && item.category.trim()) {
    return item.category.trim()
  }
  if (item.category && typeof item.category === 'object' && item.category.name) {
    return String(item.category.name).trim()
  }
  return 'Other'
}

// Dynamically derived category tabs from backend data
const categories = computed<string[]>(() => {
  const list: string[] = ['All']

  // 1. From backend categories endpoint
  backendCategories.value.forEach((c) => {
    const name = typeof c === 'string' ? c : c?.name
    if (name && !list.includes(name)) {
      list.push(name)
    }
  })

  // 2. From actual menu items
  menuItems.value.forEach((item) => {
    const cat = getItemCategory(item)
    if (cat && cat !== 'Other' && !list.includes(cat)) {
      list.push(cat)
    }
  })

  // If any item ended up in 'Other', add to end
  const hasOther = menuItems.value.some((item) => getItemCategory(item) === 'Other')
  if (hasOther && !list.includes('Other')) {
    list.push('Other')
  }

  return list
})

function getCategoryCount(catName: string): number {
  if (catName === 'All') return menuItems.value.length
  return menuItems.value.filter(
    (item) => getItemCategory(item).toLowerCase() === catName.toLowerCase(),
  ).length
}

// Filtered dishes computed dynamically
const filteredDishes = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  return menuItems.value.filter((item) => {
    const cat = getItemCategory(item)
    const matchesCategory =
      activeCategory.value === 'All' || cat.toLowerCase() === activeCategory.value.toLowerCase()

    const description = item.description || item.ingredients || ''
    const matchesSearch =
      !query ||
      item.name.toLowerCase().includes(query) ||
      description.toLowerCase().includes(query) ||
      cat.toLowerCase().includes(query)

    return matchesCategory && matchesSearch
  })
})

function formatPrice(price: number | string | undefined): string {
  const val = Number(price) || 0
  const currency = guestHotelStore.currency || '$'
  if (currency === '$' || currency === 'USD') {
    return `$${val % 1 === 0 ? val : val.toFixed(2)}`
  }
  return `${val.toLocaleString()} ${currency}`
}

function getDishImage(item: MenuItem): string {
  if (item.image) {
    return resolveFoodImageUrl(item.image, item.name)
  }
  const n = (item.name || '').toLowerCase()
  if (n.includes('shiro')) return '/images/food/bozena_shiro.webp'
  if (n.includes('tibs')) return '/images/food/lamb_tibs.webp'
  if (n.includes('soup') && n.includes('chicken')) return '/images/food/chicken_soup.webp'
  if (n.includes('soup')) return '/images/food/veggi_soup.webp'
  if (n.includes('pizza')) return '/images/food/margherita_pizza.jpg'
  if (n.includes('noodle')) return '/images/food/veggie_noodles.webp'
  if (n.includes('salad')) return '/images/food/mixed_salad.webp'
  if (n.includes('burger')) return '/images/food/beef_burger.jpg'
  return resolveFoodImageUrl(null, item.name)
}

function handleImageError(event: Event, item: MenuItem) {
  onFoodImageError(event, item.name)
}

// Fetch all menu items and categories directly from backend
async function fetchMenuData() {
  loading.value = true
  error.value = null
  try {
    const [itemsResult, catsResult] = await Promise.allSettled([
      menuService.getPublicMenus({ flat: 1, is_available: 1 }),
      menuService.getPublicCategories(),
    ])

    if (itemsResult.status === 'fulfilled') {
      const res = itemsResult.value
      const data = res.data?.data || res.data || []
      menuItems.value = Array.isArray(data) ? data : []
    } else {
      console.warn('[Menu] Failed to fetch items:', itemsResult.reason)
    }

    if (catsResult.status === 'fulfilled') {
      const res = catsResult.value
      const data = res.data?.data || res.data || []
      backendCategories.value = Array.isArray(data) ? data : []
    } else {
      console.warn('[Menu] Failed to fetch categories:', catsResult.reason)
    }

    if (menuItems.value.length === 0 && itemsResult.status === 'rejected') {
      error.value = 'Failed to load restaurant menu from server. Please try again.'
    }
  } catch (err: any) {
    console.error('[Menu] Error fetching menu data:', err)
    error.value = err?.message || 'Failed to load menu items.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchMenuData()
})
</script>

<template>
  <GuestLayout>
    <div
      class="min-h-screen bg-[#FBF9F5] dark:bg-[#0B111D] text-[#20242B] dark:text-slate-100 transition-colors duration-300 py-10 sm:py-16 px-4 sm:px-6 lg:px-8"
    >
      <div class="max-w-6xl mx-auto">
        <!-- Header Section -->
        <header class="text-center mb-8 sm:mb-12">
          <h1
            class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-[#3E2723] dark:text-[#F3EBDD] tracking-tight"
          >
            Our Restaurant Menu
          </h1>

          <!-- Decorative Golden Divider Line -->
          <div class="w-16 h-[2px] bg-[#C08A3E] mx-auto mt-3 mb-4 rounded-full" />

          <!-- Subtitle -->
          <p
            class="text-[#6B7280] dark:text-slate-400 text-sm sm:text-base max-w-xl mx-auto font-sans leading-relaxed"
          >
            Experience a culinary journey with our diverse menu featuring both international cuisine
            and local Ethiopian specialties.
          </p>

          <!-- Search Input for Convenience -->
          <div class="mt-6 max-w-md mx-auto relative">
            <Search
              class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"
            />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search dishes or ingredients (e.g. shiro, tibs, pizza)..."
              class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-[#182235] border border-slate-200 dark:border-slate-700 rounded-full text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#C08A3E]/40 focus:border-[#C08A3E] transition shadow-xs"
            />
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
            >
              Clear
            </button>
          </div>
        </header>

        <!-- Category Pills Navigation (Fetched dynamically from Backend) -->
        <nav
          v-if="categories.length > 1"
          class="flex items-center gap-2.5 overflow-x-auto pb-4 mb-8 sm:mb-10 no-scrollbar justify-start md:justify-center px-1"
          aria-label="Menu categories"
        >
          <button
            v-for="cat in categories"
            :key="cat"
            type="button"
            @click="activeCategory = cat"
            :class="[
              'px-5 py-2.5 rounded-full text-xs sm:text-sm font-medium whitespace-nowrap transition-all duration-200 shrink-0 cursor-pointer select-none',
              activeCategory === cat
                ? 'bg-[#4A2C2A] text-white shadow-sm ring-1 ring-[#4A2C2A]'
                : 'bg-white dark:bg-[#182235] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/80 hover:border-[#C08A3E] hover:text-[#4A2C2A] dark:hover:text-[#D6A94F] shadow-xs',
            ]"
          >
            {{ cat }}
            <span
              :class="[
                'ml-1 text-[11px] font-normal opacity-75',
                activeCategory === cat ? 'text-amber-200' : 'text-slate-400',
              ]"
            >
              ({{ getCategoryCount(cat) }})
            </span>
          </button>
        </nav>

        <!-- Main Content Area -->
        <main>
          <!-- Loading State Skeletons -->
          <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 animate-pulse">
            <div
              v-for="i in 6"
              :key="`skeleton-${i}`"
              class="bg-white dark:bg-[#182235] rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-800 flex items-center gap-4"
            >
              <div
                class="w-[115px] sm:w-[135px] h-[105px] sm:h-[120px] rounded-xl bg-slate-200 dark:bg-slate-700 shrink-0"
              />
              <div class="flex-1 space-y-2.5 py-1">
                <div class="flex justify-between items-center">
                  <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3" />
                  <div class="h-4 bg-amber-200/50 dark:bg-amber-900/30 rounded w-12" />
                </div>
                <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded w-full" />
                <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded w-4/5" />
                <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded w-1/4 mt-2" />
              </div>
            </div>
          </div>

          <!-- Error State -->
          <div
            v-else-if="error"
            class="text-center py-12 bg-white dark:bg-[#182235] rounded-2xl border border-red-200 dark:border-red-900/50 p-8 max-w-lg mx-auto"
          >
            <AlertCircle class="w-12 h-12 text-red-500 mx-auto mb-3" />
            <h3 class="font-serif text-lg font-bold text-red-900 dark:text-red-300">
              Unable to load menu
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-4">
              {{ error }}
            </p>
            <button
              @click="fetchMenuData"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#4A2C2A] text-white text-xs font-semibold rounded-full hover:bg-[#3E2723] transition cursor-pointer"
            >
              <RefreshCw class="w-3.5 h-3.5" />
              Try Again
            </button>
          </div>

          <!-- Menu Cards Grid -->
          <div
            v-else-if="filteredDishes.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6"
          >
            <article
              v-for="dish in filteredDishes"
              :key="dish.id"
              class="bg-white dark:bg-[#182235] rounded-2xl p-3.5 sm:p-4 shadow-[0_2px_10px_rgba(0,0,0,0.04)] hover:shadow-md border border-slate-100/90 dark:border-slate-800/80 transition-all duration-300 flex flex-row items-center gap-4 group"
            >
              <!-- Dish Image -->
              <div
                class="w-[115px] sm:w-[135px] h-[105px] sm:h-[120px] rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 shrink-0 relative"
              >
                <img
                  :src="getDishImage(dish)"
                  :alt="dish.name"
                  class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                  loading="lazy"
                  decoding="async"
                  width="135"
                  height="120"
                  @error="handleImageError($event, dish)"
                />
              </div>

              <!-- Dish Details -->
              <div class="flex-1 flex flex-col justify-between py-1 min-w-0 pr-1">
                <!-- Top Row: Name and Price -->
                <div class="flex items-start justify-between gap-2">
                  <h2
                    class="font-serif font-bold text-[#3E2723] dark:text-[#F3EBDD] text-base sm:text-[17px] leading-snug line-clamp-1 group-hover:text-[#C08A3E] transition-colors"
                    :title="dish.name"
                  >
                    {{ dish.name }}
                  </h2>
                  <span
                    class="text-[#C08A3E] dark:text-[#E5A84B] font-bold text-base sm:text-[17px] whitespace-nowrap shrink-0"
                  >
                    {{ formatPrice(dish.price) }}
                  </span>
                </div>

                <!-- Bottom Row: Ingredients / Description -->
                <p
                  class="text-[12px] sm:text-[13px] text-[#6B7280] dark:text-slate-400 font-sans leading-relaxed line-clamp-3 mt-1.5"
                >
                  {{ dish.description || dish.ingredients || 'Prepared fresh with premium ingredients by our seasoned chefs.' }}
                </p>

                <!-- Category indicator -->
                <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                  <span class="inline-flex items-center gap-1 font-medium text-[#C08A3E]/90">
                    <Utensils class="w-3 h-3" />
                    {{ getItemCategory(dish) }}
                  </span>
                </div>
              </div>
            </article>
          </div>

          <!-- Empty State -->
          <div
            v-else
            class="text-center py-16 bg-white dark:bg-[#182235] rounded-2xl border border-slate-100 dark:border-slate-800 p-8"
          >
            <Utensils class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
            <h3 class="font-serif text-lg font-bold text-[#3E2723] dark:text-slate-200">
              No menu items found
            </h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
              No dishes match your filter "{{ activeCategory }}"
              <span v-if="searchQuery"> or search "{{ searchQuery }}"</span>.
            </p>
            <button
              @click="
                () => {
                  activeCategory = 'All'
                  searchQuery = ''
                }
              "
              class="mt-4 px-4 py-2 bg-[#4A2C2A] text-white text-xs font-semibold rounded-full hover:bg-[#3E2723] transition cursor-pointer"
            >
              Reset Filters
            </button>
          </div>
        </main>

        <!-- Information Cards (Bottom Section) -->
        <section
          aria-label="Restaurant information"
          class="mt-12 sm:mt-16 bg-white dark:bg-[#182235] rounded-2xl p-6 sm:p-10 shadow-sm border border-slate-100 dark:border-slate-800"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-14">
            <!-- Left Column: Dining Hours -->
            <div>
              <h2
                class="font-serif text-xl sm:text-2xl font-bold text-[#3E2723] dark:text-[#F3EBDD] mb-5 flex items-center gap-2"
              >
                <Clock class="w-5 h-5 text-[#C08A3E]" />
                Dining Hours
              </h2>
              <div class="space-y-3.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                <div
                  class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">Breakfast</span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono"
                    >6:30 AM - 10:30 AM</span
                  >
                </div>
                <div
                  class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">Lunch</span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono"
                    >12:00 PM - 3:00 PM</span
                  >
                </div>
                <div
                  class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">Dinner</span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono"
                    >6:30 PM - 10:30 PM</span
                  >
                </div>
                <div class="flex justify-between items-center py-1.5">
                  <span class="font-medium text-slate-800 dark:text-slate-200">Bar & Lounge</span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono"
                    >11:00 AM - 12:00 AM</span
                  >
                </div>
              </div>
            </div>

            <!-- Right Column: Special Dietary Requirements -->
            <div>
              <h2
                class="font-serif text-xl sm:text-2xl font-bold text-[#3E2723] dark:text-[#F3EBDD] mb-5 flex items-center gap-2"
              >
                <Sparkles class="w-5 h-5 text-[#C08A3E]" />
                Special Dietary Requirements
              </h2>
              <p
                class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-6 font-sans"
              >
                We cater to various dietary needs including vegetarian, vegan, gluten-free, and
                allergen-specific requirements. Please inform our staff about any special dietary
                needs when making your reservation.
              </p>
              <div
                class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 space-y-1.5 bg-amber-50/50 dark:bg-amber-950/20 p-4 rounded-xl border border-amber-200/50 dark:border-amber-900/30"
              >
                <p>
                  For reservations, please call
                  <a
                    href="tel:+251116672434"
                    class="font-bold text-[#3E2723] dark:text-[#D6A94F] hover:underline"
                  >
                    +251 11 667 2434
                  </a>
                  or email
                  <a
                    href="mailto:contact@kenenisaevertt.com"
                    class="font-bold text-[#C08A3E] dark:text-[#D6A94F] hover:underline"
                  >
                    contact@kenenisaevertt.com
                  </a>
                </p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </GuestLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
