<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Clock, Utensils, Sparkles, RefreshCw, AlertCircle, ChefHat } from 'lucide-vue-next'
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

// Local frontend image mapping for instant, ultra-fast loading (< 20ms)
const FRONTEND_IMAGE_MAP: [RegExp, string][] = [
  // Ethiopian Specialties
  [/shiro/i, '/images/food/bozena_shiro.webp'],
  [/tibs/i, '/images/food/lamb_tibs.webp'],
  [/asanbussa|sambusa|samosa/i, '/images/food/sambusa.webp'],
  // Sandwiches
  [/sandu?a?ch|sandwich|panini/i, '/images/food/sandwich.webp'],
  // Seafood
  [/lobster|shrimp|salmon|seafood/i, '/images/food/lobster_tail.webp'],
  // Soups & Starters
  [/chicken.*soup|cream.*soup/i, '/images/food/chicken_soup.webp'],
  [/vegg?i.*soup|vegetable.*soup/i, '/images/food/veggi_soup.webp'],
  [/soup/i, '/images/food/veggi_soup.webp'],
  // Pizzas
  [/meat.*lover|pepperoni|beef.*pizza/i, '/images/food/meat_lover_pizza.jpg'],
  [/chicken.*pizza/i, '/images/food/chicken_pizza.jpg'],
  [/marge?r[ei]ta|pizza/i, '/images/food/margherita_pizza.jpg'],
  // Burgers
  [/chicken.*burger/i, '/images/food/chicken_burger.jpg'],
  [/beef.*burger|burger/i, '/images/food/beef_burger.jpg'],
  // Salads
  [/salad/i, '/images/food/mixed_salad.webp'],
  // Noodles & Pasta
  [/beef.*noodle/i, '/images/food/beef_noodles.webp'],
  [/noodle|pasta|spaghetti/i, '/images/food/veggie_noodles.webp'],
  // Desserts & Beverages
  [/cake|dessert|sweet/i, '/images/food/dessert.jpg'],
  [/coffee|tea|juice|drink|beverage/i, '/images/food/coffee.jpg'],
]

/**
 * Resolves images with high accuracy based on item name and category.
 */
function getFrontendDishImage(itemName: string = '', categoryName: string = ''): string {
  const name = (itemName || '').trim()
  for (const [pattern, path] of FRONTEND_IMAGE_MAP) {
    if (pattern.test(name)) {
      return path
    }
  }

  const cat = (categoryName || '').trim()
  for (const [pattern, path] of FRONTEND_IMAGE_MAP) {
    if (pattern.test(cat)) {
      return path
    }
  }

  return '/images/food/mixed_salad.webp'
}

// Reactive state
const menuItems = ref<MenuItem[]>([])
const backendCategories = ref<string[]>([])
const activeCategory = ref<string>('All')
const loading = ref(true)
const isSyncingBackground = ref(false)
const fetchError = ref<string | null>(null)
const imagesLoaded = ref<Record<string, boolean>>({})

// Loading animation step timer
const loadingElapsedSeconds = ref(0)
let loadingTimer: any = null

const loadingStageMessage = computed(() => {
  if (languageStore.isAmharic) {
    if (loadingElapsedSeconds.value < 2) {
      return 'ከማብሰያ ቤት ጋር በመገናኘት ላይ...'
    } else if (loadingElapsedSeconds.value < 5) {
      return 'የዛሬ ልዩ እና ወቅታዊ ምግቦችን በማምጣት ላይ...'
    } else {
      return 'የቀጥታ ሰርቨሩን በማዘጋጀት ላይ፣ ጥቂት ሰኮንዶች ይጠብቁ...'
    }
  }
  if (loadingElapsedSeconds.value < 2) {
    return 'Connecting to Executive Kitchen...'
  } else if (loadingElapsedSeconds.value < 5) {
    return "Fetching today's culinary specialties & seasonal dishes..."
  } else {
    return 'Waking up our live kitchen server, preparing your menu...'
  }
})

// Amharic Category translation mapping
const CATEGORY_AMHARIC_MAP: Record<string, string> = {
  all: 'ሁሉም',
  starter: 'መክሰስ',
  starters: 'መክሰሶች',
  pizza: 'ፒዛ',
  pizzas: 'ፒዛዎች',
  'traditional dish': 'ባህላዊ ምግብ',
  'traditional dishes': 'ባህላዊ ምግቦች',
  traditional: 'ባህላዊ',
  salad: 'ሰላጣ',
  salads: 'ሰላጣዎች',
  'chicken & burger': 'ዶሮ እና በርገር',
  chicken: 'የዶሮ ምግብ',
  burger: 'በርገር',
  burgers: 'በርገሮች',
  noodles: 'ኑድል',
  pasta: 'ፓስታ',
  soup: 'ሾርባ',
  soups: 'ሾርባዎች',
  breakfast: 'ቁርስ',
  'main courses': 'ዋና ምግቦች',
  'main course': 'ዋና ምግብ',
  dessert: 'ጣፋጭ',
  desserts: 'ጣፋጭ ምግቦች',
  beverage: 'መጠጥ',
  beverages: 'መጠጦች',
  drinks: 'መጠጦች',
  seafood: 'የባህር ምግቦች',
  fish: 'ዓሳ',
  sandwich: 'ሳንድዊች',
  sandwiches: 'ሳንድዊቾች',
  other: 'ሌሎች',
}

function getCategoryDisplayName(cat: string): string {
  if (!languageStore.isAmharic) return cat
  const key = cat.toLowerCase().trim()
  return CATEGORY_AMHARIC_MAP[key] || cat
}

function formatCategoryName(name: string): string {
  if (!name) return 'Other'
  const cleaned = name.replace(/[-_]+/g, ' ').trim()
  return cleaned
    .split(' ')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
    .join(' ')
}

function getItemCategory(item: any): string {
  if (!item) return 'Other'
  if (typeof item.category === 'string' && item.category.trim()) {
    return formatCategoryName(item.category)
  }
  if (item.category && typeof item.category === 'object' && item.category.name) {
    return formatCategoryName(String(item.category.name))
  }
  return 'Other'
}

// Dynamic Categories: ONLY categories with available dishes (> 0 items)
const categories = computed<string[]>(() => {
  const set = new Set<string>()

  // Collect category names from actual dishes available
  menuItems.value.forEach((item) => {
    const cat = getItemCategory(item)
    if (cat && cat !== 'Other') {
      set.add(cat)
    }
  })

  // Also include active backend categories if they match items
  backendCategories.value.forEach((rawCat) => {
    const formatted = formatCategoryName(rawCat)
    if (formatted && formatted !== 'Other') {
      const hasDish = menuItems.value.some((item) => getItemCategory(item) === formatted)
      if (hasDish) {
        set.add(formatted)
      }
    }
  })

  return ['All', ...Array.from(set)]
})

function formatPrice(price: number | string | undefined): string {
  const val = Number(price) || 0
  const currency = guestHotelStore.currency || 'ETB'
  if (currency === '$' || currency === 'USD') {
    return `$${val % 1 === 0 ? val : val.toFixed(2)}`
  }
  const currLabel = languageStore.isAmharic ? 'ብር' : currency
  return `${val.toLocaleString()} ${currLabel}`
}

function handleImageLoad(id: string | number) {
  imagesLoaded.value[String(id)] = true
}

function handleImageError(event: Event, item: MenuItem) {
  const img = event.target as HTMLImageElement
  if (!img.dataset.fallback) {
    img.dataset.fallback = 'true'
    img.src = getFrontendDishImage(item.name, item.category)
  }
}

// Filtered dishes computed by category
const filteredDishes = computed(() => {
  return menuItems.value.filter((item) => {
    const cat = getItemCategory(item)
    return (
      activeCategory.value === 'All' || cat.toLowerCase() === activeCategory.value.toLowerCase()
    )
  })
})

const CACHE_KEY = 'restaurant_guest_menu_cache_v2'

/**
 * Fetch menu items and categories directly from backend.
 * Uses smart caching for 0ms return visits and displays a luxury loading spinner on first load.
 */
async function fetchMenuFromBackend() {
  fetchError.value = null

  // 1. Check local cache first for instant load
  const cached = localStorage.getItem(CACHE_KEY)
  if (cached) {
    try {
      const parsed = JSON.parse(cached)
      if (Array.isArray(parsed?.items) && parsed.items.length > 0) {
        menuItems.value = parsed.items
        if (Array.isArray(parsed.categories)) {
          backendCategories.value = parsed.categories
        }
        loading.value = false
        isSyncingBackground.value = true
      }
    } catch (_) {}
  }

  // If no cache, start timer for loading spinner message progression
  if (menuItems.value.length === 0) {
    loading.value = true
    loadingElapsedSeconds.value = 0
    clearInterval(loadingTimer)
    loadingTimer = setInterval(() => {
      loadingElapsedSeconds.value += 1
    }, 1000)
  }

  try {
    // Concurrent fetch from backend API
    const [menuRes, catRes] = await Promise.allSettled([
      menuService.getPublicMenus({ flat: 1, is_available: 1 }),
      menuService.getPublicCategories(),
    ])

    // Process menu items
    if (menuRes.status === 'fulfilled') {
      const resData = menuRes.value.data?.data || menuRes.value.data || []
      let rawList: any[] = []

      // Handle grouped or flat structures
      if (Array.isArray(resData)) {
        if (resData.length > 0 && Array.isArray((resData[0] as any)?.items)) {
          rawList = resData.flatMap((g: any) =>
            (g.items || []).map((item: any) => ({
              ...item,
              category: g.category || item.category,
            })),
          )
        } else {
          rawList = resData
        }
      }

      if (rawList.length > 0) {
        const parsedItems: MenuItem[] = rawList.map((item: any) => {
          const cat = getItemCategory(item)
          // Resolve fast image from frontend mapping
          const image = getFrontendDishImage(item.name, cat)

          return {
            id: item.id,
            name: item.name,
            category: cat,
            price: item.total_price != null ? item.total_price : item.price || 0,
            description:
              item.description ||
              item.ingredients ||
              'Prepared fresh with premium ingredients by our seasoned chefs.',
            image,
          }
        })

        menuItems.value = parsedItems

        // Process categories
        let parsedCats: string[] = []
        if (catRes.status === 'fulfilled') {
          const catData = catRes.value.data?.data || catRes.value.data || []
          if (Array.isArray(catData)) {
            parsedCats = catData
              .map((c: any) => (typeof c === 'string' ? c : c?.name || c?.slug))
              .filter(Boolean)
            backendCategories.value = parsedCats
          }
        }

        // Cache for instant return visits
        localStorage.setItem(
          CACHE_KEY,
          JSON.stringify({
            items: parsedItems,
            categories: parsedCats,
            savedAt: Date.now(),
          }),
        )
      }
    } else {
      console.warn('[Menu] Menu fetch error:', menuRes.reason)
      if (menuItems.value.length === 0) {
        fetchError.value = 'Unable to connect to the kitchen server. Please try again.'
      }
    }
  } catch (err: any) {
    console.error('[Menu] Fetch error:', err)
    if (menuItems.value.length === 0) {
      fetchError.value = 'Failed to load menu items. Please check your connection and retry.'
    }
  } finally {
    clearInterval(loadingTimer)
    loading.value = false
    isSyncingBackground.value = false
  }
}

onMounted(() => {
  fetchMenuFromBackend()
})

onUnmounted(() => {
  if (loadingTimer) clearInterval(loadingTimer)
})
</script>

<template>
  <GuestLayout>
    <div
      class="min-h-screen bg-[#FBF9F5] dark:bg-[#0B111D] text-[#20242B] dark:text-slate-100 transition-colors duration-300 pt-7 sm:pt-10 pb-12 sm:pb-16 px-4 sm:px-6 lg:px-8"
    >
      <div class="max-w-6xl mx-auto">
        <!-- Header Section (Matches Screenshot 1) -->
        <header class="text-center mb-5 sm:mb-7">
          <h1
            class="font-serif text-3xl sm:text-4xl lg:text-[40px] font-bold text-[#3E2723] dark:text-[#F3EBDD] tracking-tight leading-tight"
          >
            {{ languageStore.isAmharic ? 'የምግብ ቤታችን ሜኑ' : 'Our Restaurant Menu' }}
          </h1>

          <!-- Decorative Golden Accent Divider Line -->
          <div class="w-14 sm:w-16 h-[2.5px] bg-[#C08A3E] mx-auto mt-2.5 mb-3.5 rounded-full" />

          <!-- Subtitle -->
          <p
            class="text-[#6B7280] dark:text-slate-400 text-xs sm:text-sm md:text-[14.5px] max-w-xl mx-auto font-sans leading-relaxed px-2"
          >
            {{
              languageStore.isAmharic
                ? 'በተለያዩ አለም አቀፍ እና ባህላዊ የኢትዮጵያ ምግቦች የተዘጋጀውን ልዩ የምግብ ዝርዝር ይጎብኙ።'
                : 'Experience a culinary journey with our diverse menu featuring both international cuisine and local Ethiopian specialties.'
            }}
          </p>

          <!-- Subtle Background Sync Indicator -->
          <div
            v-if="isSyncingBackground && menuItems.length > 0"
            class="mt-2 inline-flex items-center gap-1.5 text-[11px] text-[#C08A3E] dark:text-[#E5A84B] animate-pulse"
          >
            <RefreshCw class="w-3 h-3 animate-spin" />
            <span>{{ languageStore.isAmharic ? 'የቅርብ ጊዜ የምግብ ቤት መረጃዎችን በማመሳሰል ላይ...' : 'Syncing latest kitchen updates...' }}</span>
          </div>
        </header>

        <!-- Category Pills Navigation (Matches Screenshot 1: Dynamic categories, centered, clean pill design) -->
        <nav
          v-if="categories.length > 1 && !loading"
          class="flex items-center justify-start sm:justify-center gap-2 sm:gap-2.5 overflow-x-auto pb-2 mb-6 sm:mb-8 no-scrollbar px-1"
          aria-label="Menu categories"
        >
          <button
            v-for="cat in categories"
            :key="cat"
            type="button"
            @click="activeCategory = cat"
            :class="[
              'px-4 sm:px-5 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-medium whitespace-nowrap transition-all duration-200 shrink-0 cursor-pointer select-none',
              activeCategory === cat
                ? 'bg-[#48281E] text-white shadow-xs'
                : 'bg-white dark:bg-[#182235] text-[#4A2C2A] dark:text-slate-300 border border-slate-200/90 dark:border-slate-700/80 hover:border-[#C08A3E] hover:text-[#3E2723] dark:hover:text-[#D6A94F]',
            ]"
          >
            {{ getCategoryDisplayName(cat) }}
          </button>
        </nav>

        <!-- Main Content Area -->
        <main>
          <!-- LUXURY LOADING SPINNER: Displayed while fetching menu from backend -->
          <div
            v-if="loading && menuItems.length === 0"
            class="py-14 sm:py-20 flex flex-col items-center justify-center text-center px-4"
          >
            <!-- Animated Luxury Golden Dual-Ring Spinner -->
            <div class="relative w-20 h-20 sm:w-24 sm:h-24 mb-6">
              <!-- Outer glowing pulse ring -->
              <div
                class="absolute inset-0 rounded-full border-4 border-[#C08A3E]/20 animate-ping opacity-40"
              />
              <!-- Middle track -->
              <div
                class="absolute inset-0 rounded-full border-4 border-slate-200/60 dark:border-slate-700/60"
              />
              <!-- Rotating Golden Gradient Arc -->
              <div
                class="absolute inset-0 rounded-full border-4 border-transparent border-t-[#C08A3E] border-r-[#D97706] animate-spin"
              />
              <!-- Center Pulse Icon -->
              <div class="absolute inset-0 flex items-center justify-center">
                <ChefHat class="w-8 h-8 sm:w-9 sm:h-9 text-[#C08A3E] animate-bounce" />
              </div>
            </div>

            <!-- Title & Subtitle -->
            <h2
              class="font-serif text-xl sm:text-2xl font-bold text-[#3E2723] dark:text-[#F3EBDD] tracking-tight mb-2"
            >
              {{ languageStore.isAmharic ? 'የምግብ ዝርዝሩን በመጫን ላይ...' : 'Loading Our Restaurant Menu' }}
            </h2>
            <p
              class="text-xs sm:text-sm text-[#6B7280] dark:text-slate-400 font-sans max-w-md mx-auto leading-relaxed mb-5"
            >
              {{ loadingStageMessage }}
            </p>

            <!-- Golden Shimmer Progress Bar -->
            <div class="w-64 sm:w-72 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden relative mb-3">
              <div
                class="h-full bg-gradient-to-r from-[#D97706] via-[#F59E0B] to-[#C08A3E] rounded-full shimmer-bar"
              />
            </div>

            <!-- Server Warm-up Reassurance Note -->
            <p
              v-if="loadingElapsedSeconds > 4"
              class="text-[11px] sm:text-xs text-amber-700/80 dark:text-amber-400/80 max-w-sm mx-auto font-medium transition-opacity duration-300"
            >
              {{ languageStore.isAmharic ? '⚡ ከሰርቨር ጋር በመገናኘት ላይ። በትዕግስትዎ እናመሰግናለን...' : '⚡ Connecting with backend server. Thank you for your patience...' }}
            </p>

            <!-- Skeleton Outlines Behind for layout stability -->
            <div class="w-full mt-10 grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4 md:gap-6 opacity-30 pointer-events-none">
              <div
                v-for="i in 4"
                :key="`bg-skel-${i}`"
                class="bg-white dark:bg-[#182235] rounded-xl p-3 sm:p-4 border border-slate-200 dark:border-slate-800 flex items-center gap-3"
              >
                <div class="w-24 h-20 rounded-lg bg-slate-200 dark:bg-slate-700 shrink-0" />
                <div class="flex-1 space-y-2">
                  <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3" />
                  <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded w-full" />
                </div>
              </div>
            </div>
          </div>

          <!-- Error State with Retry Button -->
          <div
            v-else-if="fetchError && menuItems.length === 0"
            class="text-center py-12 sm:py-16 bg-white dark:bg-[#182235] rounded-2xl border border-rose-200/80 dark:border-rose-900/40 p-6 sm:p-8 max-w-lg mx-auto shadow-sm"
          >
            <div class="w-12 h-12 rounded-full bg-rose-50 dark:bg-rose-950/50 text-rose-500 mx-auto flex items-center justify-center mb-3">
              <AlertCircle class="w-6 h-6" />
            </div>
            <h3 class="font-serif text-lg font-bold text-[#3E2723] dark:text-white">
              {{ languageStore.isAmharic ? 'ሜኑውን መጫን አልተቻለም' : 'Unable to Load Menu' }}
            </h3>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 mb-5 leading-relaxed">
              {{ fetchError }}
            </p>
            <button
              @click="fetchMenuFromBackend"
              type="button"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#4A2C2A] hover:bg-[#3E2723] text-white text-xs sm:text-sm font-semibold rounded-full shadow-sm hover:shadow transition cursor-pointer"
            >
              <RefreshCw class="w-3.5 h-3.5" />
              <span>{{ languageStore.isAmharic ? 'እንደገና ሞክር' : 'Retry Kitchen Connection' }}</span>
            </button>
          </div>

          <!-- Menu Cards Grid (Matches Screenshot 1 Clean Layout: Image on left, Name & Price top, Ingredients bottom) -->
          <div
            v-else-if="filteredDishes.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 md:gap-6"
          >
            <article
              v-for="dish in filteredDishes"
              :key="dish.id"
              class="bg-white dark:bg-[#182235] rounded-xl sm:rounded-2xl p-3 sm:p-3.5 md:p-4 shadow-[0_2px_12px_rgba(0,0,0,0.04)] hover:shadow-md border border-slate-100/90 dark:border-slate-800/80 transition-all duration-300 flex flex-row items-center gap-3.5 sm:gap-4.5 group"
            >
              <!-- Dish Image: Fast Frontend Load in < 20ms -->
              <div
                class="w-[105px] sm:w-[125px] md:w-[145px] h-[90px] sm:h-[105px] md:h-[115px] rounded-lg sm:rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 shrink-0 relative"
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
                  width="145"
                  height="115"
                  @load="handleImageLoad(dish.id)"
                  @error="handleImageError($event, dish)"
                />
              </div>

              <!-- Dish Details (Matches Screenshot 1: Name + Price on top, Ingredients below) -->
              <div class="flex-1 flex flex-col justify-center min-w-0 pr-1 py-0.5">
                <!-- Top Row: Name and Price -->
                <div class="flex items-baseline justify-between gap-2.5">
                  <h2
                    class="font-serif font-bold text-[#3E2723] dark:text-[#F3EBDD] text-sm sm:text-base md:text-[17px] leading-snug line-clamp-1 group-hover:text-[#C08A3E] transition-colors"
                    :title="dish.name"
                  >
                    {{ dish.name }}
                  </h2>
                  <span
                    class="text-[#C08A3E] dark:text-[#E5A84B] font-serif font-bold text-sm sm:text-base md:text-[16px] whitespace-nowrap shrink-0 ml-1"
                  >
                    {{ formatPrice(dish.price) }}
                  </span>
                </div>

                <!-- Bottom Row: Ingredients / Description -->
                <p
                  class="text-[11px] sm:text-xs md:text-[13px] text-[#6B7280] dark:text-slate-400 font-sans leading-relaxed line-clamp-2 mt-1 sm:mt-1.5"
                >
                  {{
                    dish.ingredients ||
                    dish.description ||
                    (languageStore.isAmharic
                      ? 'በልዩ ባለሙያዎቻችን ትኩስ እና ጥራት ባላቸው ግብዓቶች የተዘጋጀ።'
                      : 'Prepared fresh with premium ingredients by our seasoned chefs.')
                  }}
                </p>
              </div>
            </article>
          </div>

          <!-- Empty State (No filter matches) -->
          <div
            v-else
            class="text-center py-12 sm:py-16 bg-white dark:bg-[#182235] rounded-2xl border border-slate-100 dark:border-slate-800 p-6 sm:p-8"
          >
            <Utensils class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
            <h3 class="font-serif text-base sm:text-lg font-bold text-[#3E2723] dark:text-slate-200">
              {{ languageStore.isAmharic ? 'ምንም የተገኘ የምግብ አይነት የለም' : 'No menu items found' }}
            </h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
              {{
                languageStore.isAmharic
                  ? `በ "${getCategoryDisplayName(activeCategory)}" ምድብ ምንም ምግብ አልተገኘም።`
                  : `No dishes match your filter "${activeCategory}".`
              }}
            </p>
            <button
              @click="activeCategory = 'All'"
              class="mt-3 px-4 py-2 bg-[#4A2C2A] text-white text-xs font-semibold rounded-full hover:bg-[#3E2723] transition cursor-pointer"
            >
              {{ languageStore.isAmharic ? 'ማጣሪያዎችን አጽዳ' : 'Reset Filters' }}
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
                {{ languageStore.isAmharic ? 'የመመገቢያ ሰዓታት' : 'Dining Hours' }}
              </h2>
              <div class="space-y-2.5 sm:space-y-3.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                <div
                  class="flex justify-between items-center py-1 sm:py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">
                    {{ languageStore.isAmharic ? 'ቁርስ' : 'Breakfast' }}
                  </span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px] sm:text-xs md:text-sm"
                    >6:30 AM - 10:30 AM</span
                  >
                </div>
                <div
                  class="flex justify-between items-center py-1 sm:py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">
                    {{ languageStore.isAmharic ? 'ምሳ' : 'Lunch' }}
                  </span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px] sm:text-xs md:text-sm"
                    >12:00 PM - 3:00 PM</span
                  >
                </div>
                <div
                  class="flex justify-between items-center py-1 sm:py-1.5 border-b border-slate-100 dark:border-slate-800/80"
                >
                  <span class="font-medium text-slate-800 dark:text-slate-200">
                    {{ languageStore.isAmharic ? 'እራት' : 'Dinner' }}
                  </span>
                  <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px] sm:text-xs md:text-sm"
                    >6:30 PM - 10:30 PM</span
                  >
                </div>
                <div class="flex justify-between items-center py-1 sm:py-1.5">
                  <span class="font-medium text-slate-800 dark:text-slate-200">
                    {{ languageStore.isAmharic ? 'ባር እና ላውንጅ' : 'Bar & Lounge' }}
                  </span>
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
                {{ languageStore.isAmharic ? 'ልዩ የምግብ ፍላጎቶች' : 'Special Dietary Requirements' }}
              </h2>
              <p
                class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-4 sm:mb-6 font-sans"
              >
                {{
                  languageStore.isAmharic
                    ? 'የቬጀቴሪያን፣ ቪጋን፣ ከግሉተን ነፃ እና ከአለርጂ የጸዱ ልዩ የምግብ ፍላጎቶችን እናስተናግዳለን። እባክዎ ቦታ ሲያስይዙ ለአስተናጋጆቻችን ያሳውቁ።'
                    : 'We cater to various dietary needs including vegetarian, vegan, gluten-free, and allergen-specific requirements. Please inform our staff about any special dietary needs when making your reservation.'
                }}
              </p>
              <div
                class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 space-y-1 bg-amber-50/50 dark:bg-amber-950/20 p-3.5 sm:p-4 rounded-xl border border-amber-200/50 dark:border-amber-900/30"
              >
                <p class="leading-relaxed">
                  {{ languageStore.isAmharic ? 'ለቅድመ ማስያዝ እባክዎ በዚህ ይደውሉ፡' : 'For reservations, please call' }}
                  <a
                    href="tel:+251116672434"
                    class="font-bold text-[#3E2723] dark:text-[#D6A94F] hover:underline whitespace-nowrap"
                  >
                    +251 11 667 2434
                  </a>
                  {{ languageStore.isAmharic ? 'ወይም በኢሜይል ያግኙን፡' : 'or email' }}
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

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(200%);
  }
}

.shimmer-bar {
  width: 50%;
  animation: shimmer 1.8s infinite ease-in-out;
}
</style>
