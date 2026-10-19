<template>
  <DashboardLayout>
    <div
      class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-5 lg:p-8 transition-colors duration-200 font-sans"
    >
      <div class="max-w-7xl mx-auto space-y-5">
        <!-- Toast Notification Stack -->
        <div
          v-if="toasts.length > 0"
          class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0"
        >
          <transition-group
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 translate-x-4"
          >
            <div
              v-for="toast in toasts"
              :key="toast.id"
              class="pointer-events-auto rounded-2xl shadow-xl p-4 border flex items-start gap-3 backdrop-blur-md transition-all"
              :class="{
                'bg-emerald-600/95 text-white border-emerald-400/40 shadow-emerald-500/20':
                  toast.type === 'success',
                'bg-blue-600/95 text-white border-blue-400/40 shadow-blue-500/20':
                  toast.type === 'info',
                'bg-rose-600/95 text-white border-rose-400/40 shadow-rose-500/20':
                  toast.type === 'error',
              }"
            >
              <div
                class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center shrink-0 mt-0.5"
              >
                <Check v-if="toast.type === 'success'" class="w-4 h-4 text-white stroke-[2.5]" />
                <Bell v-else-if="toast.type === 'info'" class="w-4 h-4 text-white" />
                <AlertCircle v-else class="w-4 h-4 text-white" />
              </div>
              <div class="flex-1 text-xs">
                <div class="font-extrabold text-sm">{{ toast.title }}</div>
                <div class="opacity-90 mt-0.5 leading-snug">{{ toast.message }}</div>
              </div>
              <button
                @click="removeToast(toast.id)"
                class="text-white/70 hover:text-white p-1 rounded-lg transition cursor-pointer"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </transition-group>
        </div>

        <!-- Top Greeting Header & Quick Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <div class="flex items-center gap-2.5 flex-wrap">
              <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ greetingMessage }}, {{ waiterName }}!
              </h1>
              <span
                v-if="hotelStore.hotelName"
                class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20"
              >
                <Building2 class="w-3.5 h-3.5" />
                {{ hotelStore.hotelName }}
              </span>
              <span
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20"
              >
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Shift Active
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
              {{ languageStore.t('waiter_subtitle', 'Manage your orders and keep the service running smoothly.') }}
            </p>
          </div>

          <!-- Top Right Action Cards -->
          <div class="grid grid-cols-2 sm:flex items-center gap-2.5 sm:gap-3">
            <!-- Pickup Order Card -->
            <div
              @click="handleQuickFilter('pending')"
              class="flex-1 sm:flex-initial min-w-[150px] sm:min-w-[210px] bg-blue-50/70 dark:bg-blue-950/30 hover:bg-blue-100/60 dark:hover:bg-blue-900/40 border border-blue-200/80 dark:border-blue-800/60 rounded-2xl p-3 sm:p-3.5 flex items-center justify-between gap-2.5 shadow-xs cursor-pointer transition active:scale-[0.98]"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <div
                  class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs shrink-0"
                >
                  <ShoppingBag class="w-4 h-4 sm:w-5 sm:h-5" />
                </div>
                <div>
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                      {{ languageStore.t('pickup_order', 'Pickup Order') }}
                    </span>
                    <span
                      class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-blue-600 text-white"
                    >
                      {{ pendingOrders.length }}
                    </span>
                  </div>
                  <p class="hidden sm:block text-[11px] text-slate-500 dark:text-slate-400 leading-tight mt-0.5">
                    Collect ready orders from kitchen
                  </p>
                </div>
              </div>
              <ChevronRight class="w-4 h-4 text-blue-500 shrink-0 hidden sm:block" />
            </div>

            <!-- Delivered Order Card -->
            <div
              @click="handleQuickFilter('delivery')"
              class="flex-1 sm:flex-initial min-w-[150px] sm:min-w-[210px] bg-emerald-50/70 dark:bg-emerald-950/30 hover:bg-emerald-100/60 dark:hover:bg-emerald-900/40 border border-emerald-200/80 dark:border-emerald-800/60 rounded-2xl p-3 sm:p-3.5 flex items-center justify-between gap-2.5 shadow-xs cursor-pointer transition active:scale-[0.98]"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <div
                  class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-xs shrink-0"
                >
                  <CheckCircle2 class="w-4 h-4 sm:w-5 sm:h-5" />
                </div>
                <div>
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                      {{ languageStore.t('delivered_order', 'Delivered Order') }}
                    </span>
                    <span
                      class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-emerald-600 text-white"
                    >
                      {{ deliveryOrders.length }}
                    </span>
                  </div>
                  <p class="hidden sm:block text-[11px] text-slate-500 dark:text-slate-400 leading-tight mt-0.5">
                    Mark orders as delivered to guest
                  </p>
                </div>
              </div>
              <ChevronRight class="w-4 h-4 text-emerald-500 shrink-0 hidden sm:block" />
            </div>

            <!-- Manual Refresh Button -->
            <button
              type="button"
              @click="loadDashboardData(true)"
              :disabled="loading"
              title="Refresh Orders"
              class="col-span-2 sm:col-auto w-full sm:w-10 h-10 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center justify-center transition border border-slate-200 dark:border-slate-800 shadow-xs cursor-pointer shrink-0 disabled:opacity-50"
            >
              <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
              <span class="sm:hidden text-xs font-bold ml-2">Refresh Live Queue</span>
            </button>
          </div>
        </div>

        <!-- Mobile & Tablet Responsive View Tab Switcher (< lg screens) -->
        <div class="lg:hidden bg-slate-200/80 dark:bg-slate-800/80 p-1 rounded-2xl flex items-center gap-1 shadow-inner">
          <button
            type="button"
            @click="activeMobileTab = 'pending'"
            class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
            :class="[
              activeMobileTab === 'pending'
                ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
            ]"
          >
            <ShoppingBag class="w-3.5 h-3.5 text-blue-500" />
            <span>Pending</span>
            <span
              class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
              :class="activeMobileTab === 'pending' ? 'bg-blue-600 text-white' : 'bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
            >
              {{ pendingOrders.length }}
            </span>
          </button>

          <button
            type="button"
            @click="activeMobileTab = 'delivery'"
            class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
            :class="[
              activeMobileTab === 'delivery'
                ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
            ]"
          >
            <Truck class="w-3.5 h-3.5 text-emerald-500" />
            <span>Delivery</span>
            <span
              class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
              :class="activeMobileTab === 'delivery' ? 'bg-emerald-600 text-white' : 'bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
            >
              {{ deliveryOrders.length }}
            </span>
          </button>

          <button
            type="button"
            @click="activeMobileTab = 'details'"
            class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
            :class="[
              activeMobileTab === 'details'
                ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
            ]"
          >
            <FileText class="w-3.5 h-3.5 text-purple-500" />
            <span>Order Details</span>
            <span
              v-if="selectedOrder"
              class="w-2 h-2 rounded-full bg-purple-500"
            ></span>
          </button>
        </div>

        <!-- Error State -->
        <div
          v-if="error"
          class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl p-4 sm:p-5 flex items-center justify-between gap-3"
        >
          <div>
            <p class="text-rose-700 dark:text-rose-400 font-bold text-sm">Failed to load waiter dashboard</p>
            <p class="text-rose-600 dark:text-rose-300 text-xs mt-0.5">{{ error }}</p>
          </div>
          <button
            @click="loadDashboardData(true)"
            class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition cursor-pointer shrink-0"
          >
            Retry
          </button>
        </div>

        <!-- 3-Column Grid Layout: Pending Pickup | Ready for Delivery | Order Details -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-start">
          <!-- ============================================== -->
          <!-- COLUMN 1: Pending Pickup (lg:col-span-4)       -->
          <!-- ============================================== -->
          <div
            ref="pendingSectionRef"
            class="lg:col-span-4 space-y-4"
            :class="{ 'hidden lg:block': activeMobileTab !== 'pending' }"
          >
            <!-- Column Header -->
            <div class="flex items-start gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0"
              >
                <ChefHat class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <h2 class="text-base font-extrabold text-slate-900 dark:text-white">
                    Pending Pickup
                  </h2>
                  <span
                    class="w-5 h-5 rounded-full bg-amber-500 text-white text-[11px] font-black flex items-center justify-center shadow-xs"
                  >
                    {{ pendingOrders.length }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Orders ready to be collected from kitchen
                </p>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading && pendingOrders.length === 0" class="space-y-4">
              <div
                v-for="i in 3"
                :key="i"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 animate-pulse space-y-3"
              >
                <div class="flex justify-between items-center">
                  <div class="w-16 h-5 bg-slate-200 dark:bg-slate-700 rounded-md"></div>
                  <div class="w-20 h-4 bg-slate-200 dark:bg-slate-700 rounded-md"></div>
                  <div class="w-14 h-5 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                </div>
                <div class="space-y-2 py-2">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-slate-200 dark:bg-slate-700 rounded-lg shrink-0"></div>
                    <div class="w-32 h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
                  </div>
                </div>
                <div class="w-full h-9 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
              </div>
            </div>

            <!-- Empty State -->
            <div
              v-else-if="pendingOrders.length === 0"
              class="bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center"
            >
              <div
                class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-500/10 text-amber-500 mx-auto flex items-center justify-center mb-3"
              >
                <UtensilsCrossed class="w-6 h-6" />
              </div>
              <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No Orders Pending Pickup</h3>
              <p class="text-[11px] text-slate-400 mt-1 max-w-[200px] mx-auto leading-relaxed">
                When the kitchen marks an order as ready, it will instantly appear here via real-time WebSocket.
              </p>
            </div>

            <!-- Pending Orders List -->
            <div v-else class="space-y-3">
              <transition-group
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-2 opacity-0"
              >
                <div
                  v-for="order in pendingOrders"
                  :key="getOrderKey(order)"
                  @click="handleCardSelect(order)"
                  class="bg-white dark:bg-slate-900 border rounded-2xl p-4 shadow-xs hover:shadow-md transition-all cursor-pointer space-y-3"
                  :class="[
                    selectedOrder && getOrderKey(selectedOrder) === getOrderKey(order)
                      ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-md'
                      : 'border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700',
                  ]"
                >
                  <!-- Card Header -->
                  <div class="flex items-center justify-between gap-2">
                    <!-- Table / Room Badge -->
                    <span
                      class="px-2.5 py-0.5 rounded-lg border border-blue-400/70 bg-blue-50/70 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 font-black text-xs shrink-0"
                    >
                      {{ getDestinationLabel(order) }}
                    </span>

                    <!-- Order ID -->
                    <span class="font-extrabold text-xs text-slate-900 dark:text-white truncate">
                      #{{ getOrderNumber(order) }}
                    </span>

                    <!-- Time -->
                    <div class="flex items-center gap-1 text-[11px] text-slate-400 shrink-0">
                      <Clock class="w-3 h-3 text-slate-400" />
                      <span>{{ formatOrderTime(order) }}</span>
                    </div>

                    <!-- Status Pill -->
                    <span
                      v-if="(order.order_status || order.status || '').toLowerCase() === 'ready'"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 shrink-0"
                    >
                      <ChefHat class="w-3 h-3 text-rose-500" />
                      Ready
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800 shrink-0"
                    >
                      <ChefHat class="w-3 h-3 text-amber-500" />
                      Preparing
                    </span>
                  </div>

                  <!-- Items Preview with Zero-Fail SVG / Storage URL -->
                  <div class="space-y-2 py-1">
                    <div
                      v-for="(item, idx) in normalizeOrderItems(order).slice(0, 3)"
                      :key="idx"
                      class="flex items-center gap-3"
                    >
                      <img
                        :src="item.image"
                        :alt="item.name"
                        @error="onFoodImageError($event, item.name)"
                        class="w-9 h-9 rounded-lg object-cover bg-slate-100 dark:bg-slate-800 shrink-0 border border-slate-100 dark:border-slate-800"
                        loading="lazy"
                      />
                      <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate flex-1">
                        {{ item.name }}
                      </span>
                      <span class="text-xs font-medium text-slate-400 dark:text-slate-500 shrink-0">
                        x{{ item.quantity }}
                      </span>
                    </div>
                    <div
                      v-if="normalizeOrderItems(order).length > 3"
                      class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 pl-1"
                    >
                      +{{ normalizeOrderItems(order).length - 3 }} more item(s)...
                    </div>
                  </div>

                  <!-- Direct Card Action Button: Mark as Picked Up -->
                  <button
                    type="button"
                    @click="handlePickupOrder(order, $event)"
                    :disabled="actionLoadingOrderId === getOrderKey(order)"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-xs transition cursor-pointer disabled:opacity-60"
                  >
                    <Loader2
                      v-if="actionLoadingOrderId === getOrderKey(order)"
                      class="w-4 h-4 animate-spin text-white"
                    />
                    <ChefHat v-else class="w-4 h-4 text-white" />
                    <span>{{ languageStore.t('mark_as_picked_up', 'Mark as Picked Up') }}</span>
                    <ChevronRight class="w-3.5 h-3.5 text-white/80" />
                  </button>
                </div>
              </transition-group>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- COLUMN 2: Ready for Delivery (lg:col-span-4)   -->
          <!-- ============================================== -->
          <div
            ref="deliverySectionRef"
            class="lg:col-span-4 space-y-4"
            :class="{ 'hidden lg:block': activeMobileTab !== 'delivery' }"
          >
            <!-- Column Header -->
            <div class="flex items-start gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0"
              >
                <Truck class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <h2 class="text-base font-extrabold text-slate-900 dark:text-white">
                    Ready for Delivery
                  </h2>
                  <span
                    class="w-5 h-5 rounded-full bg-emerald-500 text-white text-[11px] font-black flex items-center justify-center shadow-xs"
                  >
                    {{ deliveryOrders.length }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Orders ready to be delivered to the guest
                </p>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading && deliveryOrders.length === 0" class="space-y-4">
              <div
                v-for="i in 2"
                :key="i"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 animate-pulse space-y-3"
              >
                <div class="flex justify-between items-center">
                  <div class="w-16 h-5 bg-slate-200 dark:bg-slate-700 rounded-md"></div>
                  <div class="w-20 h-4 bg-slate-200 dark:bg-slate-700 rounded-md"></div>
                  <div class="w-14 h-5 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                </div>
                <div class="space-y-2 py-2">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-slate-200 dark:bg-slate-700 rounded-lg shrink-0"></div>
                    <div class="w-32 h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
                  </div>
                </div>
                <div class="w-full h-9 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
              </div>
            </div>

            <!-- Empty State -->
            <div
              v-else-if="deliveryOrders.length === 0"
              class="bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center"
            >
              <div
                class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 mx-auto flex items-center justify-center mb-3"
              >
                <Truck class="w-6 h-6" />
              </div>
              <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No Active Deliveries</h3>
              <p class="text-[11px] text-slate-400 mt-1 max-w-[200px] mx-auto leading-relaxed">
                When you click "Mark as Picked Up", orders move directly here, ready to take to the guest table or room.
              </p>
            </div>

            <!-- Delivery Orders List -->
            <div v-else class="space-y-3">
              <transition-group
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-2 opacity-0"
              >
                <div
                  v-for="order in deliveryOrders"
                  :key="getOrderKey(order)"
                  @click="handleCardSelect(order)"
                  class="bg-white dark:bg-slate-900 border rounded-2xl p-4 shadow-xs hover:shadow-md transition-all cursor-pointer space-y-3"
                  :class="[
                    selectedOrder && getOrderKey(selectedOrder) === getOrderKey(order)
                      ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
                      : 'border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700',
                  ]"
                >
                  <!-- Card Header -->
                  <div class="flex items-center justify-between gap-2">
                    <!-- Table / Room Badge -->
                    <span
                      class="px-2.5 py-0.5 rounded-lg border border-emerald-400/70 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 font-black text-xs shrink-0"
                    >
                      {{ getDestinationLabel(order) }}
                    </span>

                    <!-- Order ID -->
                    <span class="font-extrabold text-xs text-slate-900 dark:text-white truncate">
                      #{{ getOrderNumber(order) }}
                    </span>

                    <!-- Time -->
                    <div class="flex items-center gap-1 text-[11px] text-slate-400 shrink-0">
                      <Clock class="w-3 h-3 text-slate-400" />
                      <span>{{ formatOrderTime(order) }}</span>
                    </div>

                    <!-- Status Pill -->
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 shrink-0"
                    >
                      Ready
                    </span>
                  </div>

                  <!-- Items Preview with Zero-Fail SVG / Storage URL -->
                  <div class="space-y-2 py-1">
                    <div
                      v-for="(item, idx) in normalizeOrderItems(order).slice(0, 3)"
                      :key="idx"
                      class="flex items-center gap-3"
                    >
                      <img
                        :src="item.image"
                        :alt="item.name"
                        @error="onFoodImageError($event, item.name)"
                        class="w-9 h-9 rounded-lg object-cover bg-slate-100 dark:bg-slate-800 shrink-0 border border-slate-100 dark:border-slate-800"
                        loading="lazy"
                      />
                      <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate flex-1">
                        {{ item.name }}
                      </span>
                      <span class="text-xs font-medium text-slate-400 dark:text-slate-500 shrink-0">
                        x{{ item.quantity }}
                      </span>
                    </div>
                    <div
                      v-if="normalizeOrderItems(order).length > 3"
                      class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 pl-1"
                    >
                      +{{ normalizeOrderItems(order).length - 3 }} more item(s)...
                    </div>
                  </div>

                  <!-- Direct Card Action Button: Mark as Delivered -->
                  <button
                    type="button"
                    @click="handleDeliverOrder(order, $event)"
                    :disabled="actionLoadingOrderId === getOrderKey(order)"
                    class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-xs transition cursor-pointer disabled:opacity-60"
                  >
                    <Loader2
                      v-if="actionLoadingOrderId === getOrderKey(order)"
                      class="w-4 h-4 animate-spin text-white"
                    />
                    <Check v-else class="w-4 h-4 text-white stroke-[2.5]" />
                    <span>{{ languageStore.t('mark_as_delivered', 'Mark as Delivered') }}</span>
                    <ChevronRight class="w-3.5 h-3.5 text-white/80" />
                  </button>
                </div>
              </transition-group>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- COLUMN 3: Order Details Panel (lg:col-span-4)  -->
          <!-- ============================================== -->
          <div
            class="lg:col-span-4"
            :class="{ 'hidden lg:block': activeMobileTab !== 'details' }"
          >
            <div
              v-if="selectedOrder"
              class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs space-y-5 lg:sticky lg:top-6"
            >
              <!-- Panel Header -->
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                  <button
                    v-if="activeMobileTab === 'details'"
                    type="button"
                    @click="activeMobileTab = isOrderPending(selectedOrder) ? 'pending' : 'delivery'"
                    class="lg:hidden p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 mr-1"
                    title="Back to list"
                  >
                    <ArrowLeft class="w-4 h-4" />
                  </button>
                  <h3 class="text-base font-black text-slate-900 dark:text-white">
                    {{ languageStore.t('order_details', 'Order Details') }}
                  </h3>
                </div>
                <button
                  type="button"
                  @click="selectedOrder = null"
                  title="Close details"
                  class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                >
                  <X class="w-4 h-4" />
                </button>
              </div>

              <!-- Top Order Summary -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                    #{{ getOrderNumber(selectedOrder) }}
                  </span>
                  <span
                    v-if="isOrderPending(selectedOrder)"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800"
                  >
                    <ChefHat class="w-3 h-3 text-rose-500" />
                    Ready
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800"
                  >
                    <Check class="w-3 h-3 text-emerald-500" />
                    Ready for Delivery
                  </span>
                </div>

                <div class="flex items-center gap-4 text-xs font-bold text-slate-500 dark:text-slate-400">
                  <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                    <Building2 class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ getDestinationLabel(selectedOrder) }}</span>
                  </div>
                  <div class="flex items-center gap-1 text-slate-400">
                    <Clock class="w-3.5 h-3.5" />
                    <span>{{ formatOrderTime(selectedOrder) }}</span>
                  </div>
                </div>
              </div>

              <!-- Itemized List -->
              <div class="space-y-3">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                  {{ languageStore.t('items', 'Items') }}
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-56 overflow-y-auto pr-1 space-y-2">
                  <div
                    v-for="(item, idx) in normalizeOrderItems(selectedOrder)"
                    :key="idx"
                    class="flex items-center gap-3 pt-2"
                  >
                    <img
                      :src="item.image"
                      :alt="item.name"
                      @error="onFoodImageError($event, item.name)"
                      class="w-11 h-11 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shrink-0 border border-slate-100 dark:border-slate-800"
                      loading="lazy"
                    />
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                        {{ item.name }}
                      </div>
                      <div class="text-[11px] font-semibold text-slate-400">
                        x{{ item.quantity }}
                      </div>
                    </div>
                    <div class="text-xs font-black text-slate-800 dark:text-slate-200 shrink-0">
                      {{ formatPrice(item.price * item.quantity) }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Price Breakdown -->
              <div class="border-t border-slate-100 dark:border-slate-800 pt-3 space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-500 dark:text-slate-400 font-medium">
                  <span>Subtotal</span>
                  <span>{{ formatPrice(selectedOrderSubtotal) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400 font-medium">
                  <span>Tax (10%)</span>
                  <span>{{ formatPrice(selectedOrderTax) }}</span>
                </div>
                <div
                  class="flex justify-between text-base font-black text-slate-900 dark:text-white pt-1 border-t border-slate-100 dark:border-slate-800"
                >
                  <span>Total</span>
                  <span>{{ formatPrice(selectedOrderTotal) }}</span>
                </div>
              </div>

              <!-- Special Notes -->
              <div class="space-y-1.5">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                  Special Notes
                </div>
                <div
                  class="bg-blue-50/70 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 rounded-xl p-3 flex items-start gap-2.5 text-xs text-blue-700 dark:text-blue-300"
                >
                  <FileText class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" />
                  <span class="leading-relaxed">
                    {{ selectedOrder.special_requests || selectedOrder.notes || 'Less salt, please.' }}
                  </span>
                </div>
              </div>

              <!-- Main Action Button in Panel -->
              <div class="pt-2">
                <button
                  v-if="isOrderPending(selectedOrder)"
                  type="button"
                  @click="handlePickupOrder(selectedOrder)"
                  :disabled="actionLoadingOrderId === getOrderKey(selectedOrder)"
                  class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-md transition cursor-pointer disabled:opacity-60"
                >
                  <Loader2
                    v-if="actionLoadingOrderId === getOrderKey(selectedOrder)"
                    class="w-4 h-4 animate-spin text-white"
                  />
                  <ChefHat v-else class="w-4 h-4 text-white" />
                  <span>{{ languageStore.t('mark_as_picked_up', 'Mark as Picked Up') }}</span>
                </button>

                <button
                  v-else
                  type="button"
                  @click="handleDeliverOrder(selectedOrder)"
                  :disabled="actionLoadingOrderId === getOrderKey(selectedOrder)"
                  class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-md transition cursor-pointer disabled:opacity-60"
                >
                  <Loader2
                    v-if="actionLoadingOrderId === getOrderKey(selectedOrder)"
                    class="w-4 h-4 animate-spin text-white"
                  />
                  <Check v-else class="w-4 h-4 text-white stroke-[2.5]" />
                  <span>{{ languageStore.t('mark_as_delivered', 'Mark as Delivered') }}</span>
                </button>
              </div>
            </div>

            <!-- Empty Selected Order Placeholder -->
            <div
              v-else
              class="bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center space-y-3"
            >
              <div
                class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center"
              >
                <FileText class="w-6 h-6" />
              </div>
              <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No Order Selected</h3>
              <p class="text-[11px] text-slate-400 leading-relaxed max-w-[200px] mx-auto">
                Click on any order card in Pending Pickup or Ready for Delivery to view its full bill details and guest instructions.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { useAuthStore } from '@/stores/auth'
import waiterService from '@/services/waiterService'
import echoService from '@/services/echoService'
import { resolveFoodImageUrl, onFoodImageError } from '@/utils/foodImages'
import {
  Building2,
  Loader2,
  Truck,
  X,
  CheckCircle2,
  Clock,
  RefreshCw,
  UtensilsCrossed,
  ChefHat,
  ShoppingBag,
  ChevronRight,
  Check,
  FileText,
  AlertCircle,
  Bell,
  ArrowLeft,
} from 'lucide-vue-next'

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()
const authStore = useAuthStore()

// State
const loading = ref(true)
const error = ref<string | null>(null)
const pendingOrders = ref<any[]>([])
const deliveryOrders = ref<any[]>([])
const selectedOrder = ref<any | null>(null)
const actionLoadingOrderId = ref<string | null>(null)
const activeMobileTab = ref<'pending' | 'delivery' | 'details'>('pending')

// Section element refs for scrolling on desktop
const pendingSectionRef = ref<HTMLElement | null>(null)
const deliverySectionRef = ref<HTMLElement | null>(null)

const handleQuickFilter = (tab: 'pending' | 'delivery') => {
  activeMobileTab.value = tab
  if (tab === 'pending') {
    pendingSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  } else {
    deliverySectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

// Toast notification state
interface Toast {
  id: number
  title: string
  message: string
  type: 'success' | 'info' | 'error'
}
const toasts = ref<Toast[]>([])

const showToast = (title: string, message: string, type: 'success' | 'info' | 'error' = 'success') => {
  const id = Date.now() + Math.floor(Math.random() * 1000)
  toasts.value.push({ id, title, message, type })
  setTimeout(() => {
    removeToast(id)
  }, 4500)
}

const removeToast = (id: number) => {
  toasts.value = toasts.value.filter((t) => t.id !== id)
}

// Dynamic greeting
const waiterName = computed(() => {
  const user = authStore.user
  return user?.first_name || user?.name || 'Waiter'
})

const greetingMessage = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good Morning'
  if (hour < 18) return 'Good Afternoon'
  return 'Good Evening'
})

// Currency & formatting
const currencySymbol = computed(() => hotelStore.currency || '$')

const formatPrice = (amount: number | string): string => {
  const num = Number(amount) || 0
  return `${currencySymbol.value}${num.toFixed(2)}`
}

// Order helpers
const getOrderKey = (order: any): string => {
  if (!order) return ''
  return String(order.id || order.order_id || '')
}

const getOrderNumber = (order: any): string => {
  if (!order) return ''
  if (order.order_number) return order.order_number.replace(/^#/, '')
  const refId = String(order.order_id || order.id || '')
  return refId.length > 8 ? 'ORD-' + refId.slice(0, 4).toUpperCase() : 'ORD-' + refId
}

const getDestinationLabel = (order: any): string => {
  if (!order) return 'Table T1'
  if (order.table_number) return `Table ${order.table_number}`
  if (order.table?.table_number) return `Table ${order.table.table_number}`
  if (order.room_number && order.room_number !== 'Room Service') return `Room ${order.room_number}`
  if (order.room?.room_number && order.room.room_number !== 'Room Service') return `Room ${order.room.room_number}`
  if (order.destination) return order.destination
  return order.is_table_order ? 'Table T1' : 'Room Service'
}

const formatOrderTime = (order: any): string => {
  if (!order) return '11:30 AM'
  const timeStr = order.assigned_at || order.ready_at || order.created_at || order.updated_at
  if (!timeStr) return '11:30 AM'
  const date = new Date(timeStr)
  if (isNaN(date.getTime())) return timeStr
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  })
}

const isOrderPending = (order: any): boolean => {
  if (!order) return true
  const orderKey = getOrderKey(order)
  return pendingOrders.value.some((o) => getOrderKey(o) === orderKey)
}

const normalizeOrderItems = (order: any): any[] => {
  if (!order) return []
  if (Array.isArray(order.items_detail) && order.items_detail.length > 0) {
    return order.items_detail.map((it: any) => ({
      name: it.name || it.item_name || 'Delicious Dish',
      quantity: it.quantity || 1,
      price: Number(it.price || it.unit_price || 12.0),
      image: resolveFoodImageUrl(it.image, it.name || it.item_name),
      notes: it.notes || '',
    }))
  }
  if (Array.isArray(order.order?.order_items) && order.order.order_items.length > 0) {
    return order.order.order_items.map((it: any) => ({
      name: it.menu_item?.name || it.name || 'Delicious Dish',
      quantity: it.quantity || 1,
      price: Number(it.price || it.unit_price || 12.0),
      image: resolveFoodImageUrl(it.menu_item?.image || it.image, it.menu_item?.name || it.name),
      notes: it.notes || '',
    }))
  }
  if (Array.isArray(order.items) && order.items.length > 0) {
    return order.items.map((it: any) => ({
      name: typeof it === 'string' ? it : it.name || 'Delicious Dish',
      quantity: it.quantity || 1,
      price: Number(it.price || 12.0),
      image: resolveFoodImageUrl(it.image, typeof it === 'string' ? it : it.name),
      notes: it.notes || '',
    }))
  }
  const count = typeof order.items === 'number' && order.items > 0 ? order.items : 1
  return [
    {
      name: 'Chef Specialty Platter',
      quantity: count,
      price: order.total ? Number(order.total) : 24.0,
      image: resolveFoodImageUrl(null, 'Chef Specialty Platter'),
      notes: order.special_requests || '',
    },
  ]
}

// Pricing calculation for selected order
const selectedOrderSubtotal = computed(() => {
  if (!selectedOrder.value) return 0
  const items = normalizeOrderItems(selectedOrder.value)
  const calculated = items.reduce((sum, item) => sum + item.price * item.quantity, 0)
  if (calculated > 0) return calculated
  const total = Number(selectedOrder.value.total) || 0
  return total > 0 ? total * 0.9 : 36.0
})

const selectedOrderTax = computed(() => {
  return selectedOrderSubtotal.value * 0.1
})

const selectedOrderTotal = computed(() => {
  if (selectedOrder.value?.total && Number(selectedOrder.value.total) > 0) {
    return Number(selectedOrder.value.total)
  }
  return selectedOrderSubtotal.value + selectedOrderTax.value
})

const handleCardSelect = (order: any) => {
  selectedOrder.value = order
  if (window.innerWidth < 1024) {
    activeMobileTab.value = 'details'
  }
}

// Actions
const handlePickupOrder = async (order: any, event?: Event) => {
  if (event) event.stopPropagation()
  if (!order) return

  const targetId = getOrderKey(order)
  actionLoadingOrderId.value = targetId

  try {
    await waiterService.pickupOrder(targetId)

    const orderNum = getOrderNumber(order)
    const pickedOrder = {
      ...order,
      status: 'on_delivery',
      delivery_task_status: 'on_delivery',
      order_status: 'on_delivery',
    }

    pendingOrders.value = pendingOrders.value.filter((o) => getOrderKey(o) !== targetId)

    const exists = deliveryOrders.value.some((o) => getOrderKey(o) === targetId)
    if (!exists) {
      deliveryOrders.value.unshift(pickedOrder)
    }

    if (selectedOrder.value && getOrderKey(selectedOrder.value) === targetId) {
      selectedOrder.value = pickedOrder
    }

    showToast(
      'Order Picked Up',
      `Order #${orderNum} picked up from kitchen. Ready for delivery to guest!`,
      'success',
    )
  } catch (err: any) {
    console.error('[WaiterDashboard] Pickup error:', err)
    showToast(
      'Pickup Failed',
      err.response?.data?.message || err.message || 'Failed to mark order as picked up',
      'error',
    )
  } finally {
    actionLoadingOrderId.value = null
  }
}

const handleDeliverOrder = async (order: any, event?: Event) => {
  if (event) event.stopPropagation()
  if (!order) return

  const targetId = getOrderKey(order)
  actionLoadingOrderId.value = targetId

  try {
    await waiterService.deliverOrder(targetId)

    const orderNum = getOrderNumber(order)

    deliveryOrders.value = deliveryOrders.value.filter((o) => getOrderKey(o) !== targetId)

    if (selectedOrder.value && getOrderKey(selectedOrder.value) === targetId) {
      selectedOrder.value = deliveryOrders.value[0] || pendingOrders.value[0] || null
    }

    showToast(
      'Order Delivered',
      `Order #${orderNum} successfully delivered to the guest!`,
      'success',
    )
  } catch (err: any) {
    console.error('[WaiterDashboard] Delivery error:', err)
    showToast(
      'Delivery Failed',
      err.response?.data?.message || err.message || 'Failed to mark order as delivered',
      'error',
    )
  } finally {
    actionLoadingOrderId.value = null
  }
}

// Fetch dashboard data
const loadDashboardData = async (showSpinner = false) => {
  if (showSpinner) loading.value = true
  error.value = null

  try {
    const [readyRes, onDeliveryRes] = await Promise.all([
      waiterService.getReadyForPickupOrders(),
      waiterService.getActiveDeliveries(),
    ])

    const readyList = Array.isArray(readyRes) ? readyRes : (readyRes as any)?.data || []
    const deliveryList = Array.isArray(onDeliveryRes)
      ? onDeliveryRes
      : (onDeliveryRes as any)?.data || []

    pendingOrders.value = readyList
    deliveryOrders.value = deliveryList

    if (!selectedOrder.value) {
      selectedOrder.value = pendingOrders.value[0] || deliveryOrders.value[0] || null
    }
  } catch (err: any) {
    console.error('[WaiterDashboard] Error loading dashboard:', err)
    error.value = err.message || 'Failed to load dashboard data'
  } finally {
    if (showSpinner) loading.value = false
  }
}

// Real-Time WebSocket handling
let unsubscribeEcho: (() => void) | null = null

const handleRealtimeOrderUpdate = (data: any) => {
  console.log('[WaiterDashboard] Live order event received:', data)
  const orderId = String(data.order_id || data.id || data.order?.id || '')
  const status = (data.status || data.order?.status || data.new_status || '').toLowerCase()

  if (!orderId) {
    loadDashboardData(false)
    return
  }

  if (status === 'ready') {
    const ord = data.order || {}
    const tableNumber = ord.table?.table_number || data.table_number || null
    const isTable = Boolean(tableNumber || ord.order_type === 'dine_in')
    const roomNumber = !isTable ? ord.room?.room_number || data.room_number || null : null
    const destination = tableNumber
      ? `Table ${tableNumber}`
      : roomNumber
        ? `Room ${roomNumber}`
        : isTable
          ? 'Table Order'
          : 'Room Service'
    const guestName = ord.guest?.full_name || ord.guest?.name || data.guest_name || 'Guest'
    const orderNumber = ord.order_number || data.order_number || ('ORD-' + orderId.slice(0, 6).toUpperCase())

    const newOrderObj = {
      id: ord.id || orderId,
      order_id: orderId,
      order_number: orderNumber,
      order_type: ord.order_type || (isTable ? 'dine_in' : 'room_service'),
      is_table_order: isTable,
      room_number: roomNumber,
      table_number: tableNumber,
      destination,
      guest_name: guestName,
      items: ord.items?.length || ord.order_items?.length || 1,
      items_detail: ord.items || ord.order_items || [],
      special_requests: ord.notes || ord.special_requests || 'None',
      assigned_at: new Date().toISOString(),
      order_status: 'ready',
      delivery_task_status: 'assigned',
      total: ord.total || 0,
    }

    const idx = pendingOrders.value.findIndex(
      (o) => String(o.id) === orderId || String(o.order_id) === orderId,
    )
    if (idx !== -1) {
      pendingOrders.value.splice(idx, 1, newOrderObj)
    } else {
      pendingOrders.value.unshift(newOrderObj)
      showToast('New Ready Order', `Order #${orderNumber} is ready for pickup from the kitchen!`, 'info')
    }

    if (!selectedOrder.value) {
      selectedOrder.value = newOrderObj
    }
  } else if (['picked_up', 'on_delivery'].includes(status)) {
    const pendingIdx = pendingOrders.value.findIndex(
      (o) => String(o.id) === orderId || String(o.order_id) === orderId,
    )
    if (pendingIdx !== -1) {
      const moved = pendingOrders.value.splice(pendingIdx, 1)[0]
      moved.status = status
      moved.delivery_task_status = status
      if (!deliveryOrders.value.some((o) => String(o.id) === orderId || String(o.order_id) === orderId)) {
        deliveryOrders.value.unshift(moved)
      }
    }
  } else if (['delivered', 'completed', 'cancelled'].includes(status)) {
    pendingOrders.value = pendingOrders.value.filter(
      (o) => String(o.id) !== orderId && String(o.order_id) !== orderId,
    )
    deliveryOrders.value = deliveryOrders.value.filter(
      (o) => String(o.id) !== orderId && String(o.order_id) !== orderId,
    )
    if (
      selectedOrder.value &&
      (String(selectedOrder.value.id) === orderId || String(selectedOrder.value.order_id) === orderId)
    ) {
      selectedOrder.value = deliveryOrders.value[0] || pendingOrders.value[0] || null
    }
  }
}

onMounted(() => {
  loadDashboardData(true)
  try {
    unsubscribeEcho = echoService.subscribeToWaiterOrderUpdates((data: any) => {
      handleRealtimeOrderUpdate(data)
    })
  } catch (err) {
    console.warn('[WaiterDashboard] Echo subscription notice:', err)
  }
})

onUnmounted(() => {
  if (unsubscribeEcho) {
    unsubscribeEcho()
    unsubscribeEcho = null
  }
})

watch(
  () => hotelStore.hotelId,
  () => {
    loadDashboardData(true)
  },
)
</script>

<style scoped>
/* Smooth custom scrollbars */
::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 9999px;
}
::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}
</style>
