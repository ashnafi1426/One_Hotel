<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Search, Clock, Utensils, Sparkles, RefreshCw, AlertCircle } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import menuService from '@/services/menuService'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface MenuItem {
  id: string | number
  name: string
  category: string
  price: number | string
  priceDisplay?: string
  description?: string
  ingredients?: string
  image?: string | null
}

// Frontend local image mapping - loads in < 20ms without any 20-second network delay!
const FRONTEND_IMAGE_MAP: Record<string, string> = {
  'bozena shiro': '/images/food/bozena_shiro.webp',
  'shiro tegabino': '/images/food/shiro_tegabino.webp',
  'shiro': '/images/food/bozena_shiro.webp',
  'lambe tibs': '/images/food/lamb_tibs.webp',
  'lamb tibs': '/images/food/lamb_tibs.webp',
  'chiqna tibs': '/images/food/chiqna_tibs.webp',
  'tibs': '/images/food/lamb_tibs.webp',
  'chicken cream soup': '/images/food/chicken_soup.webp',
  'chicken soup': '/images/food/chicken_soup.webp',
  'veggi soup': '/images/food/veggi_soup.webp',
  'vegetable soup': '/images/food/veggi_soup.webp',
  'soup': '/images/food/veggi_soup.webp',
  'margerta pizza': '/images/food/margherita_pizza.jpg',
  'margherita pizza': '/images/food/margherita_pizza.jpg',
  'meat lover pizza': '/images/food/meat_lover_pizza.jpg',
  'chicken pizza': '/images/food/chicken_pizza.jpg',
  'pizza': '/images/food/margherita_pizza.jpg',
  'mixed salad': '/images/food/mixed_salad.webp',
  'salad': '/images/food/mixed_salad.webp',
  'strir fride veggi noodles': '/images/food/veggie_noodles.webp',
  'veggie noodles': '/images/food/veggie_noodles.webp',
  'strir fride beef noodles': '/images/food/beef_noodles.webp',
  'beef noodles': '/images/food/beef_noodles.webp',
  'noodle': '/images/food/veggie_noodles.webp',
  'noodles': '/images/food/veggie_noodles.webp',
  'classic crispy chicken burger': '/images/food/chicken_burger.jpg',
  'chicken burger': '/images/food/chicken_burger.jpg',
  'gourmet double beef burger': '/images/food/beef_burger.jpg',
  'beef burger': '/images/food/beef_burger.jpg',
  'burger': '/images/food/burger.jpg',
  'coffee': '/images/food/coffee.jpg',
  'dessert': '/images/food/dessert.jpg',
}

/**
 * Resolves images directly from the local frontend assets for ultra-fast instant rendering.
 */
function getFrontendDishImage(itemName: string = '', categoryName: string = ''): string {
  const n = (itemName || '').toLowerCase().trim()
  const c = (categoryName || '').toLowerCase().trim()

  // 1. Direct name lookup or partial match
  for (const [key, path] of Object.entries(FRONTEND_IMAGE_MAP)) {
    if (n.includes(key)) {
      return path
    }
  }

  // 2. Category fallback
  if (c.includes('traditional') || c.includes('habesha')) return '/images/food/bozena_shiro.webp'
  if (c.includes('pizza')) return '/images/food/margherita_pizza.jpg'
  if (c.includes('soup') || c.includes('starter')) return '/images/food/chicken_soup.webp'
  if (c.includes('salad')) return '/images/food/mixed_salad.webp'
  if (c.includes('noodle') || c.includes('pasta')) return '/images/food/veggie_noodles.webp'
  if (c.includes('burger') || c.includes('chicken')) return '/images/food/chicken_burger.jpg'

  return '/images/food/mixed_salad.webp'
}

// Reactive state
const menuItems = ref<MenuItem[]>([])
const backendCategories = ref<string[]>([])
const activeCategory = ref<string>('All')
const searchQuery = ref('')
const loading = ref(true)
const error = ref<string | null>(null)
const imagesLoaded = ref<Record<string, boolean>>({})

// Categories are fetched dynamically from backend (NOT fixed)
const categories = computed<string[]>(() => {
  const set = new Set<string>(['All'])

  // Add backend categories returned from API
  backendCategories.value.forEach((cat) => {
    if (cat && typeof cat === 'string' && cat.trim()) {
      set.add(cat.trim())
    }
  })

  // Also include any category names discovered in menu items
  menuItems.value.forEach((item) => {
    const c = (item.category || '').trim()
    if (c && c !== 'Other') {
      set.add(c)
    }
  })

  return Array.from(set)
})

function getItemCategory(item: any): string {
  if (!item) return 'Other'
  if (typeof item.category === 'string' && item.category.trim()) {
    return item.category.trim()
  }
  if (item.category && typeof item.category === 'object' && item.category.name) {
    return String(item.category.name).trim()
  }
  return 'Other'
}

function formatPrice(price: number | string | undefined): string {
  const val = Number(price) || 0
  const currency = guestHotelStore.currency || '$'
  if (currency === '$' || currency === 'USD') {
    return `$${val % 1 === 0 ? val : val.toFixed(2)}`
  }
  return `${val.toLocaleString()} ${currency}`
}

function handleImageLoad(id: string | number) {
  imagesLoaded.value[String(id)] = true
}

function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  if (!img.dataset.fallback) {
    img.dataset.fallback = 'true'
    img.src = '/images/food/mixed_salad.webp'
  }
}

// Filtered dishes computed
const filteredDishes = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  return menuItems.value.filter((item) => {
    const cat = (item.category || '').trim()
    const matchesCategory =
      activeCategory.value === 'All' || cat.toLowerCase() === activeCategory.value.toLowerCase()

    const desc = item.description || item.ingredients || ''
    const matchesSearch =
      !query ||
      item.name.toLowerCase().includes(query) ||
      desc.toLowerCase().includes(query) ||
      cat.toLowerCase().includes(query)

    return matchesCategory && matchesSearch
  })
})

// Fetch categories and dishes from backend with local cache for instant load
async function fetchMenuAndCategories() {
  // Check local cache first so page opens in 0ms without waiting 20s
  const cachedItems = localStorage.getItem('guest_menu_items_cache')
  const cachedCats = localStorage.getItem('guest_menu_categories_cache')

  if (cachedItems) {
    try {
      const parsed = JSON.parse(cachedItems)
      if (Array.isArray(parsed) && parsed.length > 0) {
        menuItems.value = parsed
        loading.value = false
      }
    } catch (_) {}
  }

  if (cachedCats) {
    try {
      const parsedCats = JSON.parse(cachedCats)
      if (Array.isArray(parsedCats) && parsedCats.length > 0) {
        backendCategories.value = parsedCats
      }
    } catch (_) {}
  }

  try {
    // 6-second timeout so a sleeping Render backend never freezes the page for 20s
    const timeoutPromise = new Promise((_, reject) =>
      setTimeout(() => reject(new Error('timeout')), 7000),
    )

    const fetchPromise = Promise.allSettled([
      menuService.getPublicMenus({ flat: 1, is_available: 1 }),
      menuService.getPublicCategories(),
    ])

    const results: any = await Promise.race([fetchPromise, timeoutPromise])

    const [itemsResult, catsResult] = results

    // 1. Process dishes from backend
    if (itemsResult?.status === 'fulfilled') {
      const res = itemsResult.value
      const rawList = res.data?.data || res.data || []
      if (Array.isArray(rawList) && rawList.length > 0) {
        const formattedItems: MenuItem[] = rawList.map((item: any) => ({
          id: item.id,
          name: item.name,
          category: getItemCategory(item),
          price: item.price,
          description: item.description || item.ingredients || '',
          // Image comes from frontend matching for instant 0s load
          image: getFrontendDishImage(item.name, getItemCategory(item)),
        }))

        menuItems.value = formattedItems
        localStorage.setItem('guest_menu_items_cache', JSON.stringify(formattedItems))
      }
    }

    // 2. Process categories from backend (NOT fixed!)
    if (catsResult?.status === 'fulfilled') {
      const res = catsResult.value
      const rawCats = res.data?.data || res.data || []
      if (Array.isArray(rawCats) && rawCats.length > 0) {
        const extracted = rawCats
          .map((c: any) => (typeof c === 'string' ? c : c?.name))
          .filter(Boolean)
        backendCategories.value = extracted
        localStorage.setItem('guest_menu_categories_cache', JSON.stringify(extracted))
      }
    }
  } catch (err) {
    console.warn('[Menu] Background fetch took long or failed, using fast cache:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchMenuAndCategories()
})
</script>

<template>
  <GuestLayout>
    <div
      class="min-h-screen bg-[#FBF9F5] dark:bg-[#0B111D] text-[#20242B] dark:text-slate-100 transition-colors duration-300 py-8 sm:py-12 lg:py-16 px-4 sm:px-6 lg:px-8"
    >
      <div class="max-w-6xl mx-auto">
        <!-- Header Section -->
        <header class="text-center mb-6 sm:mb-10">
          <h1
            class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-[#3E2723] dark:text-[#F3EBDD] tracking-tight leading-tight"
          >
            Our Restaurant Menu
          </h1>

          <!-- Decorative Golden Accent Divider Line -->
          <div class="w-14 sm:w-16 h-[2px] bg-[#C08A3E] mx-auto mt-3 mb-3 sm:mb-4 rounded-full" />

          <!-- Subtitle -->
          <p
            class="text-[#6B7280] dark:text-slate-400 text-xs sm:text-sm md:text-base max-w-xl mx-auto font-sans leading-relaxed px-2"
          >
            Experience a culinary journey with our diverse menu featuring both international cuisine
            and local Ethiopian specialties.
          </p>

          <!-- Search Input for Mobile/Tablet/Desktop -->
          <div class="mt-5 sm:mt-6 max-w-md mx-auto relative px-2 sm:px-0">
            <Search
              class="w-4 h-4 text-slate-400 absolute left-5 sm:left-4 top-1/2 -translate-y-1/2 pointer-events-none"
            />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search dishes or ingredients..."
              class="w-full pl-10 pr-9 py-2 sm:py-2.5 bg-white dark:bg-[#182235] border border-slate-200 dark:border-slate-700 rounded-full text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#C08A3E]/40 focus:border-[#C08A3E] transition shadow-xs"
            />
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              type="button"
              class="absolute right-5 sm:right-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-1"
            >
              ✕
            </button>
          </div>
        </header>

        <!-- Category Pills Navigation (Fetched dynamically from Backend, Screenshot 1 design) -->
        <nav
          v-if="categories.length > 1"
          class="flex items-center gap-2 sm:gap-2.5 overflow-x-auto pb-3 mb-6 sm:mb-8 md:mb-10 no-scrollbar justify-start md:justify-center px-1"
          aria-label="Menu categories"
        >
          <button
            v-for="cat in categories"
            :key="cat"
            type="button"
            @click="activeCategory = cat"
            :class="[
              'px-4 sm:px-6 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-medium whitespace-nowrap transition-all duration-200 shrink-0 cursor-pointer select-none',
              activeCategory === cat
                ? 'bg-[#4A2C2A] text-white shadow-sm ring-1 ring-[#4A2C2A]'
                : 'bg-white dark:bg-[#182235] text-[#4A2C2A] dark:text-slate-300 border border-slate-200/90 dark:border-slate-700/80 hover:border-[#C08A3E] hover:text-[#3E2723] dark:hover:text-[#D6A94F] shadow-xs',
            ]"
          >
            {{ cat }}
          </button>
        </nav>

        <!-- Main Content Area -->
        <main>
          <!-- Fast Loading Skeleton (Only shown if zero cache on first visit) -->
          <div
            v-if="loading && menuItems.length === 0"
            class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4 md:gap-6 animate-pulse"
          >
            <div
              v-for="i in 6"
              :key="`skeleton-${i}`"
              class="bg-white dark:bg-[#182235] rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm border border-slate-100 dark:border-slate-800 flex items-center gap-3 sm:gap-4"
            >
              <div
                class="w-[100px] sm:w-[125px] md:w-[140px] h-[95px] sm:h-[110px] md:h-[120px] rounded-lg sm:rounded-xl bg-slate-200 dark:bg-slate-700 shrink-0"
              />
              <div class="flex-1 space-y-2.5 py-1">
                <div class="flex justify-between items-center">
                  <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3" />
                  <div class="h-4 bg-amber-200/50 dark:bg-amber-900/30 rounded w-12" />
                </div>
                <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded w-full" />
                <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded w-4/5" />
              </div>
            </div>
          </div>

          <!-- Menu Cards Grid (Responsive across mobile, tablet, and desktop) -->
          <div
            v-else-if="filteredDishes.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4 md:gap-6"
          >
            <article
              v-for="dish in filteredDishes"
              :key="dish.id"
              class="bg-white dark:bg-[#182235] rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-[0_2px_12px_rgba(0,0,0,0.04)] hover:shadow-md border border-slate-100/90 dark:border-slate-800/80 transition-all duration-300 flex flex-row items-center gap-3 sm:gap-4 group"
            >
              <!-- Dish Image: Fast Frontend Load in < 20ms -->
              <div
                class="w-[100px] sm:w-[125px] md:w-[140px] h-[95px] sm:h-[110px] md:h-[120px] rounded-lg sm:rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 shrink-0 relative"
              >
                <!-- Image placeholder while decoding -->
                <div
                  v-if="!imagesLoaded[dish.id]"
                  class="absolute inset-0 bg-slate-200/50 dark:bg-slate-700/30"
                />

                <img
                  :src="dish.image || getFrontendDishImage(dish.name, dish.category)"
                  :alt="dish.name"
                  class="w-full h-full object-cover transition-all duration-300 group-hover:scale-105"
                  :class="imagesLoaded[dish.id] ? 'opacity-100' : 'opacity-0'"
                  loading="lazy"
                  decoding="async"
                  width="140"
                  height="120"
                  @load="handleImageLoad(dish.id)"
                  @error="handleImageError"
                />
              </div>

              <!-- Dish Details -->
              <div class="flex-1 flex flex-col justify-between py-0.5 sm:py-1 min-w-0 pr-1">
                <!-- Top Row: Name and Price -->
                <div class="flex items-start justify-between gap-2">
                  <h2
                    class="font-serif font-bold text-[#3E2723] dark:text-[#F3EBDD] text-sm sm:text-base md:text-lg leading-snug line-clamp-1 group-hover:text-[#C08A3E] transition-colors"
                    :title="dish.name"
                  >
                    {{ dish.name }}
                  </h2>
                  <span
                    class="text-[#D97706] dark:text-[#E5A84B] font-bold text-sm sm:text-base md:text-[17px] whitespace-nowrap shrink-0 ml-1"
                  >
                    {{ formatPrice(dish.price) }}
                  </span>
                </div>

                <!-- Bottom Row: Ingredients / Description -->
                <p
                  class="text-[11px] sm:text-xs md:text-[13px] text-[#6B7280] dark:text-slate-400 font-sans leading-relaxed line-clamp-2 sm:line-clamp-3 mt-1"
                >
                  {{ dish.description || dish.ingredients || 'Prepared fresh with premium ingredients by our seasoned chefs.' }}
                </p>
              </div>
            </article>
          </div>

          <!-- Empty State -->
          <div
            v-else
            class="text-center py-12 sm:py-16 bg-white dark:bg-[#182235] rounded-2xl border border-slate-100 dark:border-slate-800 p-6 sm:p-8"
          >
            <Utensils class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
            <h3 class="font-serif text-base sm:text-lg font-bold text-[#3E2723] dark:text-slate-200">
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
              class="mt-3 px-4 py-2 bg-[#4A2C2A] text-white text-xs font-semibold rounded-full hover:bg-[#3E2723] transition cursor-pointer"
            >
              Reset Filters
            </button>
          </div>
        </main>

        <!-- Information Cards (Bottom Section - Fully Responsive for Mobile, Tablet, Desktop) -->
        <section
          aria-label="Restaurant information"
          class="mt-10 sm:mt-14 lg:mt-16 bg-white dark:bg-[#182235] rounded-xl sm:rounded-2xl p-5 sm:p-8 lg:p-10 shadow-sm border border-slate-100 dark:border-slate-800"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 lg:gap-14">
            <!-- Left Column: Dining Hours -->
            <div>
              <h2
                class="font-serif text-lg sm:text-xl md:text-2xl font-bold text-[#3E2723] dark:text-[#F3EBDD] mb-4 sm:mb-5 flex items-center gap-2"
              >
                <Clock class="w-4 h-4 sm:w-5 sm:h-5 text-[#C08A3E]" />
                Dining Hours
              </h2>
              <div class="space-y-2.5 sm:space-y-3.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                <div
                  class="flex justify-between items-center py-1 sm:py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">Breakfast</span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px] sm:text-xs md:text-sm"
                    >6:30 AM - 10:30 AM</span
                  >
                </div>
                <div
                  class="flex justify-between items-center py-1 sm:py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">Lunch</span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px] sm:text-xs md:text-sm"
                    >12:00 PM - 3:00 PM</span
                  >
                </div>
                <div
                  class="flex justify-between items-center py-1 sm:py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">Dinner</span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px] sm:text-xs md:text-sm"
                    >6:30 PM - 10:30 PM</span
                  >
                </div>
                <div class="flex justify-between items-center py-1 sm:py-1.5">
                  <span class="font-medium text-slate-800 dark:text-slate-200">Bar & Lounge</span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px] sm:text-xs md:text-sm"
                    >11:00 AM - 12:00 AM</span
                  >
                </div>
              </div>
            </div>

            <!-- Right Column: Special Dietary Requirements -->
            <div>
              <h2
                class="font-serif text-lg sm:text-xl md:text-2xl font-bold text-[#3E2723] dark:text-[#F3EBDD] mb-4 sm:mb-5 flex items-center gap-2"
              >
                <Sparkles class="w-4 h-4 sm:w-5 sm:h-5 text-[#C08A3E]" />
                Special Dietary Requirements
              </h2>
              <p
                class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-4 sm:mb-6 font-sans"
              >
                We cater to various dietary needs including vegetarian, vegan, gluten-free, and
                allergen-specific requirements. Please inform our staff about any special dietary
                needs when making your reservation.
              </p>
              <div
                class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 space-y-1 bg-amber-50/50 dark:bg-amber-950/20 p-3.5 sm:p-4 rounded-xl border border-amber-200/50 dark:border-amber-900/30"
              >
                <p class="leading-relaxed">
                  For reservations, please call
                  <a
                    href="tel:+251116672434"
                    class="font-bold text-[#3E2723] dark:text-[#D6A94F] hover:underline whitespace-nowrap"
                  >
                    +251 11 667 2434
                  </a>
                  or email
                  <a
                    href="mailto:contact@kenenisaevertt.com"
                    class="font-bold text-[#C08A3E] dark:text-[#D6A94F] hover:underline break-all"
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
