<script setup lang="ts">
import { ref, computed } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Search, Phone, Mail, Clock, Utensils, Sparkles } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Dish {
  id: string
  name: string
  category: 'Starter' | 'Pizza' | 'Traditional Dish' | 'Salad' | 'Chicken & Burger' | 'Noodles'
  price: number
  priceDisplay: string
  ingredients: string
  image: string
  imageWebp?: string
}

const categories = [
  'All',
  'Starter',
  'Pizza',
  'Traditional Dish',
  'Salad',
  'Chicken & Burger',
  'Noodles',
] as const

type CategoryType = (typeof categories)[number]

const activeCategory = ref<CategoryType>('All')
const searchQuery = ref('')

const dishes: Dish[] = [
  {
    id: 'd1',
    name: 'Bozena shiro',
    category: 'Traditional Dish',
    price: 3.0,
    priceDisplay: '$3',
    ingredients: 'Meat, Oil, Shiro Powder, knorr, Enjera, Oche',
    image: '/images/food/bozena_shiro.jpg',
    imageWebp: '/images/food/bozena_shiro.webp',
  },
  {
    id: 'd2',
    name: 'Shiro Tegabino',
    category: 'Traditional Dish',
    price: 2.7,
    priceDisplay: '$2.7',
    ingredients: 'Oil, shrio powder, Knor, enjera, Oche',
    image: '/images/food/shiro_tegabino.jpg',
    imageWebp: '/images/food/shiro_tegabino.webp',
  },
  {
    id: 'd3',
    name: 'Lambe Tibs',
    category: 'Traditional Dish',
    price: 5.21,
    priceDisplay: '$5.21',
    ingredients: 'Lamb, Oche, tomato, Chili, Onion',
    image: '/images/food/lamb_tibs.jpg',
    imageWebp: '/images/food/lamb_tibs.webp',
  },
  {
    id: 'd4',
    name: 'Chiqna Tibs',
    category: 'Traditional Dish',
    price: 5.0,
    priceDisplay: '$5',
    ingredients: 'Beef fileet, oche, tomato, chill, onion',
    image: '/images/food/chiqna_tibs.jpg',
    imageWebp: '/images/food/chiqna_tibs.webp',
  },
  {
    id: 'd5',
    name: 'Chicken cream soup',
    category: 'Starter',
    price: 2.07,
    priceDisplay: '$2.07',
    ingredients: 'Chicken breast, flour, Milk, Cream, Table butter, Parsly, Knorrpitta bread, salt',
    image: '/images/food/chicken_soup.jpg',
    imageWebp: '/images/food/chicken_soup.webp',
  },
  {
    id: 'd6',
    name: 'Veggi Soup',
    category: 'Starter',
    price: 1.14,
    priceDisplay: '$1.14',
    ingredients: 'Flour, Egg, Baking soda, Corn starch, mixed vegetables',
    image: '/images/food/veggi_soup.jpg',
    imageWebp: '/images/food/veggi_soup.webp',
  },
  {
    id: 'd7',
    name: 'Margerta Pizza',
    category: 'Pizza',
    price: 3.6,
    priceDisplay: '$3.6',
    ingredients: 'Cheese, Tomato sauce, Organo, Chill sauce',
    image: '/images/food/margherita_pizza.jpg',
  },
  {
    id: 'd8',
    name: 'Meat Lover Pizza',
    category: 'Pizza',
    price: 4.65,
    priceDisplay: '$4.65',
    ingredients: 'Tomato sauce, Cheese, Organo, Minced beef, Sauge, Chill sauce',
    image: '/images/food/meat_lover_pizza.jpg',
  },
  {
    id: 'd9',
    name: 'Chicken pizza',
    category: 'Pizza',
    price: 4.69,
    priceDisplay: '$4.69',
    ingredients: 'Bolid chicken, Tomato sauce, Cheese, Orange, Chill sauce, Flour, Yeast, Suger',
    image: '/images/food/chicken_pizza.jpg',
  },
  {
    id: 'd10',
    name: 'Mixed Salad',
    category: 'Salad',
    price: 3.41,
    priceDisplay: '$3.41',
    ingredients: 'Lettuce, Red Cabbgae, Onion, Sweet corn, Singer, Tomato',
    image: '/images/food/mixed_salad.jpg',
    imageWebp: '/images/food/mixed_salad.webp',
  },
  {
    id: 'd11',
    name: 'Strir fride veggi noodles',
    category: 'Noodles',
    price: 3.93,
    priceDisplay: '$3.93',
    ingredients:
      'carrot, zucchini, onion, cabbage, oil, baking soda, soy a sauce light, dark soy a sauce, chill flex, oyster sauce, corn starch, noodles, widge spice, broculi, mini bread',
    image: '/images/food/veggie_noodles.jpg',
    imageWebp: '/images/food/veggie_noodles.webp',
  },
  {
    id: 'd12',
    name: 'Strir fride beef noodles',
    category: 'Noodles',
    price: 4.45,
    priceDisplay: '$4.45',
    ingredients:
      'beef fillet, carrot, zucchini, onion, cabbage, oil, baking soda, soy a saouce light, dark soy a sauce, chill, flex, oyster sauce, corn starch, noodles, wodge spice, broccoli',
    image: '/images/food/beef_noodles.jpg',
    imageWebp: '/images/food/beef_noodles.webp',
  },
  {
    id: 'd13',
    name: 'Classic Crispy Chicken Burger',
    category: 'Chicken & Burger',
    price: 4.2,
    priceDisplay: '$4.20',
    ingredients:
      'Crispy golden chicken breast, melted cheddar cheese, fresh lettuce, pickles, house special sauce, toasted brioche bun',
    image: '/images/food/chicken_burger.jpg',
  },
  {
    id: 'd14',
    name: 'Gourmet Double Beef Burger',
    category: 'Chicken & Burger',
    price: 4.8,
    priceDisplay: '$4.80',
    ingredients:
      'Prime ground beef patties, caramelized onions, smoked cheddar cheese, ripe tomato, artisanal brioche bun',
    image: '/images/food/beef_burger.jpg',
  },
]

const filteredDishes = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  return dishes.filter((dish) => {
    const matchesCategory =
      activeCategory.value === 'All' || dish.category === activeCategory.value
    const matchesSearch =
      !query ||
      dish.name.toLowerCase().includes(query) ||
      dish.ingredients.toLowerCase().includes(query) ||
      dish.category.toLowerCase().includes(query)
    return matchesCategory && matchesSearch
  })
})

function getCategoryCount(cat: CategoryType): number {
  if (cat === 'All') return dishes.length
  return dishes.filter((d) => d.category === cat).length
}

function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  if (!img.dataset.fallback) {
    img.dataset.fallback = 'true'
    img.src = '/images/gallery/fine-dining.jpg'
  }
}
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
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
            >
              Clear
            </button>
          </div>
        </header>

        <!-- Category Pills Navigation -->
        <nav
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

        <!-- Menu Cards Grid -->
        <main>
          <div
            v-if="filteredDishes.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6"
          >
            <article
              v-for="dish in filteredDishes"
              :key="dish.id"
              class="bg-white dark:bg-[#182235] rounded-2xl p-3.5 sm:p-4 shadow-[0_2px_10px_rgba(0,0,0,0.04)] hover:shadow-md border border-slate-100/90 dark:border-slate-800/80 transition-all duration-300 flex flex-row items-center gap-4 group"
            >
              <!-- Dish Image with WebP picture support -->
              <div
                class="w-[115px] sm:w-[135px] h-[105px] sm:h-[120px] rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 shrink-0 relative"
              >
                <picture class="block w-full h-full">
                  <source v-if="dish.imageWebp" :srcset="dish.imageWebp" type="image/webp" />
                  <img
                    :src="dish.image"
                    :alt="dish.name"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy"
                    decoding="async"
                    width="135"
                    height="120"
                    @error="handleImageError"
                  />
                </picture>
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
                    {{ dish.priceDisplay }}
                  </span>
                </div>

                <!-- Bottom Row: Ingredients -->
                <p
                  class="text-[12px] sm:text-[13px] text-[#6B7280] dark:text-slate-400 font-sans leading-relaxed line-clamp-3 mt-1.5"
                >
                  {{ dish.ingredients }}
                </p>

                <!-- Subtle Category indicator -->
                <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                  <span class="inline-flex items-center gap-1 font-medium text-[#C08A3E]/90">
                    <Utensils class="w-3 h-3" />
                    {{ dish.category }}
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
              No dishes match your search "{{ searchQuery }}". Try adjusting your filter or search
              term.
            </p>
            <button
              @click="
                () => {
                  activeCategory = 'All'
                  searchQuery = ''
                }
              "
              class="mt-4 px-4 py-2 bg-[#4A2C2A] text-white text-xs font-semibold rounded-full hover:bg-[#3E2723] transition"
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
