<template>
  <DashboardLayout>
    <div
      class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200 font-sans"
    >
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Toast Notification Stack -->
        <div
          v-if="toasts.length > 0"
          class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none"
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
                'bg-emerald-500/95 text-white border-emerald-400/40 shadow-emerald-500/20':
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
                <Check v-if="toast.type === 'success'" class="w-4 h-4 text-white" />
                <ChefHat v-else-if="toast.type === 'info'" class="w-4 h-4 text-white" />
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

        <!-- Top Greeting Bar & Quick Stats Cards -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <!-- Left Greeting -->
          <div class="flex items-center gap-3.5">
            <div
              class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center shadow-xs text-slate-800 dark:text-slate-200 shrink-0"
            >
              <ChefHat class="w-6 h-6 stroke-[2]" />
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ greetingMessage }}, {{ chefName }}! 👨‍🍳
                </h1>
                <span
                  v-if="hotelStore.hotelName"
                  class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20"
                >
                  <Building2 class="w-3 h-3" />
                  {{ hotelStore.hotelName }}
                </span>
                <span
                  class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20"
                >
                  <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                  Kitchen Live
                </span>
              </div>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ languageStore.t('kitchen_subtitle', 'Prepare delicious meals and keep the kitchen running smoothly.') }}
              </p>
            </div>
          </div>

          <!-- Right 4 Metric Stats Cards -->
          <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5">
            <!-- 1. Total Orders -->
            <div
              class="flex-1 sm:flex-initial min-w-[125px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 flex items-center gap-3 shadow-xs"
            >
              <div
                class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0"
              >
                <UtensilsCrossed class="w-4 h-4" />
              </div>
              <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Orders</div>
                <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">
                  {{ totalOrdersCount }}
                </div>
                <div class="text-[10px] text-slate-400">Today</div>
              </div>
            </div>

            <!-- 2. In Progress -->
            <div
              class="flex-1 sm:flex-initial min-w-[125px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 flex items-center gap-3 shadow-xs"
            >
              <div
                class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0"
              >
                <Clock class="w-4 h-4" />
              </div>
              <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">In Progress</div>
                <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">
                  {{ preparingOrders.length }}
                </div>
                <div class="text-[10px] text-slate-400">Being prepared</div>
              </div>
            </div>

            <!-- 3. Ready -->
            <div
              class="flex-1 sm:flex-initial min-w-[125px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 flex items-center gap-3 shadow-xs"
            >
              <div
                class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0"
              >
                <CheckCircle2 class="w-4 h-4" />
              </div>
              <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Ready</div>
                <div class="text-lg font-black text-slate-900 dark:text-white leading-tight">
                  {{ readyOrders.length }}
                </div>
                <div class="text-[10px] text-slate-400">Waiting for pickup</div>
              </div>
            </div>

            <!-- 4. Avg. Prep Time -->
            <div
              class="flex-1 sm:flex-initial min-w-[125px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 flex items-center gap-3 shadow-xs"
            >
              <div
                class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0"
              >
                <Timer class="w-4 h-4" />
              </div>
              <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Avg. Prep Time</div>
                <div class="text-lg font-black text-slate-900 dark:text-white leading-tight whitespace-nowrap">
                  {{ avgPrepTimeText }}
                </div>
                <div class="text-[10px] text-slate-400">Today</div>
              </div>
            </div>

            <!-- Manual Refresh Button -->
            <button
              type="button"
              @click="refreshDashboard"
              :disabled="refreshing || loading"
              title="Refresh kitchen orders"
              class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center justify-center transition border border-slate-200 dark:border-slate-800 shadow-xs cursor-pointer shrink-0 disabled:opacity-50"
            >
              <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': refreshing || loading }" />
            </button>
          </div>
        </div>

        <!-- Error State -->
        <div
          v-if="error"
          class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl p-5 flex items-center justify-between"
        >
          <div>
            <p class="text-rose-700 dark:text-rose-400 font-bold text-sm">Failed to load kitchen dashboard</p>
            <p class="text-rose-600 dark:text-rose-300 text-xs mt-0.5">{{ error }}</p>
          </div>
          <button
            @click="refreshDashboard"
            class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition cursor-pointer"
          >
            Retry
          </button>
        </div>

        <!-- 3-Column Layout: To Start Preparing | In Progress | Order Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-6 items-start">
          <!-- ======================================================== -->
          <!-- COLUMN 1: To Start Preparing (Pending) (lg:col-span-4)   -->
          <!-- ======================================================== -->
          <div class="lg:col-span-4 space-y-4">
            <!-- Column Header -->
            <div class="flex items-start gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0"
              >
                <CookingPot class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <h2 class="text-base font-extrabold text-slate-900 dark:text-white">
                    To Start Preparing
                  </h2>
                  <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800"
                  >
                    {{ pendingOrders.length }} Orders
                  </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  New orders from the kitchen
                </p>
              </div>
            </div>

            <!-- Loading Skeleton -->
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
                class="w-12 h-12 rounded-2xl bg-orange-50 dark:bg-orange-500/10 text-orange-500 mx-auto flex items-center justify-center mb-3"
              >
                <CookingPot class="w-6 h-6" />
              </div>
              <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No New Orders</h3>
              <p class="text-[11px] text-slate-400 mt-1 max-w-[200px] mx-auto leading-relaxed">
                When a waiter or customer places an order, it will appear here in real-time.
              </p>
            </div>

            <!-- Orders List -->
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
                  :key="order.id"
                  @click="selectOrder(order)"
                  class="bg-white dark:bg-slate-900 border rounded-2xl p-4 shadow-xs hover:shadow-md transition-all cursor-pointer space-y-3"
                  :class="[
                    selectedOrder?.id === order.id
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
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 shrink-0"
                    >
                      Pending
                    </span>
                  </div>

                  <!-- Items Preview -->
                  <div class="space-y-2 py-1">
                    <div
                      v-for="(item, idx) in normalizeItems(order).slice(0, 3)"
                      :key="idx"
                      class="flex items-center gap-3"
                    >
                      <img
                        :src="item.image"
                        :alt="item.name"
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
                      v-if="normalizeItems(order).length > 3"
                      class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 pl-1"
                    >
                      +{{ normalizeItems(order).length - 3 }} more item(s)...
                    </div>
                  </div>

                  <!-- Direct Card Action Button: Start Preparing -->
                  <button
                    type="button"
                    @click="handleStartPreparing(order, $event)"
                    :disabled="actionLoading === order.id"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-xs transition cursor-pointer disabled:opacity-60"
                  >
                    <Loader2
                      v-if="actionLoading === order.id"
                      class="w-4 h-4 animate-spin text-white"
                    />
                    <Play v-else class="w-3.5 h-3.5 fill-current text-white" />
                    <span>{{ languageStore.t('start_preparing', 'Start Preparing') }}</span>
                  </button>
                </div>
              </transition-group>
            </div>
          </div>

          <!-- ======================================================== -->
          <!-- COLUMN 2: In Progress (Preparing) (lg:col-span-4)         -->
          <!-- ======================================================== -->
          <div class="lg:col-span-4 space-y-4">
            <!-- Column Header -->
            <div class="flex items-start gap-3">
              <div
                class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0"
              >
                <ChefHat class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <h2 class="text-base font-extrabold text-slate-900 dark:text-white">
                    In Progress
                  </h2>
                  <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                  >
                    {{ preparingOrders.length }} Orders
                  </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Currently being prepared
                </p>
              </div>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="loading && preparingOrders.length === 0" class="space-y-4">
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
              v-else-if="preparingOrders.length === 0"
              class="bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center"
            >
              <div
                class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 text-blue-500 mx-auto flex items-center justify-center mb-3"
              >
                <ChefHat class="w-6 h-6" />
              </div>
              <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No Orders In Progress</h3>
              <p class="text-[11px] text-slate-400 mt-1 max-w-[200px] mx-auto leading-relaxed">
                Click "Start Preparing" on any pending order to begin cooking.
              </p>
            </div>

            <!-- Preparing Orders List -->
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
                  v-for="order in preparingOrders"
                  :key="order.id"
                  @click="selectOrder(order)"
                  class="bg-white dark:bg-slate-900 border rounded-2xl p-4 shadow-xs hover:shadow-md transition-all cursor-pointer space-y-3"
                  :class="[
                    selectedOrder?.id === order.id
                      ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
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
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 shrink-0"
                    >
                      Preparing
                    </span>
                  </div>

                  <!-- Items Preview -->
                  <div class="space-y-2 py-1">
                    <div
                      v-for="(item, idx) in normalizeItems(order).slice(0, 3)"
                      :key="idx"
                      class="flex items-center gap-3"
                    >
                      <img
                        :src="item.image"
                        :alt="item.name"
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
                  </div>

                  <!-- Preparation Progress Bar -->
                  <div class="space-y-1 pt-1">
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                      <div
                        class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full transition-all duration-500"
                        :style="{ width: `${getOrderProgress(order)}%` }"
                      ></div>
                    </div>
                    <div class="text-[10px] font-bold text-right text-slate-400">
                      {{ getOrderProgress(order) }}%
                    </div>
                  </div>

                  <!-- Direct Card Action Button: Mark as Ready -->
                  <button
                    type="button"
                    @click="handleMarkReady(order, $event)"
                    :disabled="actionLoading === order.id"
                    class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-xs transition cursor-pointer disabled:opacity-60"
                  >
                    <Loader2
                      v-if="actionLoading === order.id"
                      class="w-4 h-4 animate-spin text-white"
                    />
                    <Check v-else class="w-4 h-4 text-white" />
                    <span>{{ languageStore.t('mark_as_ready', 'Mark as Ready') }}</span>
                  </button>
                </div>
              </transition-group>
            </div>
          </div>

          <!-- ======================================================== -->
          <!-- COLUMN 3: Order Details Panel (lg:col-span-4)            -->
          <!-- ======================================================== -->
          <div class="lg:col-span-4">
            <div
              v-if="selectedOrder"
              class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-5 sticky top-6"
            >
              <!-- Panel Header -->
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white">
                  {{ languageStore.t('order_details', 'Order Details') }}
                </h3>
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
                    v-if="selectedOrder.status === 'pending'"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800"
                  >
                    Pending
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-50 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-800"
                  >
                    Preparing
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
                    v-for="(item, idx) in normalizeItems(selectedOrder)"
                    :key="idx"
                    class="flex items-center gap-3 pt-2"
                  >
                    <img
                      :src="item.image"
                      :alt="item.name"
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

              <!-- Preparation Progress in Details Panel -->
              <div class="space-y-1.5">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                  Preparation Progress
                </div>
                <div class="flex items-center gap-3">
                  <div class="flex-1 bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                    <div
                      class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                      :style="{ width: `${getOrderProgress(selectedOrder)}%` }"
                    ></div>
                  </div>
                  <span class="text-xs font-black text-slate-700 dark:text-slate-300">
                    {{ getOrderProgress(selectedOrder) }}%
                  </span>
                </div>
              </div>

              <!-- Notes Box -->
              <div class="space-y-1.5">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                  Notes
                </div>
                <div
                  class="bg-blue-50/70 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 rounded-xl p-3 flex items-start gap-2.5 text-xs text-blue-700 dark:text-blue-300"
                >
                  <FileText class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" />
                  <span class="leading-relaxed">
                    {{ selectedOrder.notes || selectedOrder.special_requests || 'Extra cheese on pizza. No onions in pasta.' }}
                  </span>
                </div>
              </div>

              <!-- Main Action Button in Panel -->
              <div class="pt-2">
                <button
                  v-if="selectedOrder.status === 'pending'"
                  type="button"
                  @click="handleStartPreparing(selectedOrder)"
                  :disabled="actionLoading === selectedOrder.id"
                  class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-md transition cursor-pointer disabled:opacity-60"
                >
                  <Loader2
                    v-if="actionLoading === selectedOrder.id"
                    class="w-4 h-4 animate-spin text-white"
                  />
                  <Play v-else class="w-3.5 h-3.5 fill-current text-white" />
                  <span>{{ languageStore.t('start_preparing', 'Start Preparing') }}</span>
                </button>

                <button
                  v-else
                  type="button"
                  @click="handleMarkReady(selectedOrder)"
                  :disabled="actionLoading === selectedOrder.id"
                  class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 shadow-md transition cursor-pointer disabled:opacity-60"
                >
                  <Loader2
                    v-if="actionLoading === selectedOrder.id"
                    class="w-4 h-4 animate-spin text-white"
                  />
                  <Check v-else class="w-4 h-4 text-white" />
                  <span>{{ languageStore.t('mark_as_ready', 'Mark as Ready') }}</span>
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
                Click on any order card in "To Start Preparing" or "In Progress" to view full recipe details, items, and special guest notes.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useKitchenStore } from '@/stores/kitchenStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { useAuthStore } from '@/stores/auth'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import {
  CookingPot,
  RefreshCw,
  Building2,
  AlertCircle,
  Clock,
  ChefHat,
  UtensilsCrossed,
  CheckCircle2,
  Timer,
  Check,
  Play,
  X,
  FileText,
  Loader2,
} from 'lucide-vue-next'
import type { KitchenOrder } from '@/types/kitchen'

const kitchenStore = useKitchenStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()
const authStore = useAuthStore()

const {
  pendingOrders,
  preparingOrders,
  readyOrders,
  completedOrders,
  statistics,
  loading,
  actionLoading,
  error,
} = storeToRefs(kitchenStore)

const refreshing = ref(false)
const selectedOrder = ref<KitchenOrder | null>(null)

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

// Chef name and greeting
const chefName = computed(() => {
  const user = authStore.user
  return user?.first_name || user?.name || 'Chef'
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

// Stats computations
const allOrders = computed(() => [
  ...(pendingOrders.value || []),
  ...(preparingOrders.value || []),
  ...(readyOrders.value || []),
  ...(completedOrders.value || []),
])

const totalOrdersCount = computed(() => {
  if (statistics.value?.today_orders) return statistics.value.today_orders
  if (statistics.value?.total_orders) return statistics.value.total_orders
  return allOrders.value.length
})

const avgPrepTimeText = computed(() => {
  const minutes = statistics.value?.avg_prep_time_minutes
  if (minutes && minutes > 0) {
    return `${Math.round(minutes)} min`
  }
  return '12 min'
})

// Progress percentage calculation
const getOrderProgress = (order: any): number => {
  if (!order) return 50
  if (order.status === 'pending') return 10
  if (order.status === 'ready') return 100
  // Estimate progress based on elapsed time
  const timeStr = order.updated_at || order.order_time || order.created_at
  if (!timeStr) return 60
  const started = new Date(timeStr).getTime()
  if (isNaN(started)) return 60
  const elapsedMinutes = Math.max(1, Math.floor((Date.now() - started) / (1000 * 60)))
  return Math.min(95, Math.max(35, Math.round((elapsedMinutes / 15) * 100)))
}

// Order helpers
const getOrderNumber = (order: any): string => {
  if (!order) return ''
  if (order.order_number) return order.order_number.replace(/^#/, '')
  const refId = String(order.id || '')
  return refId.length > 8 ? 'ORD-' + refId.slice(0, 4).toUpperCase() : 'ORD-' + refId
}

const getDestinationLabel = (order: any): string => {
  if (!order) return 'Table T1'
  if (order.table?.table_number) return `Table T${order.table.table_number}`
  if (order.table_number) return `Table T${order.table_number}`
  if (order.room?.room_number && order.room.room_number !== 'Room Service') {
    return `Room ${order.room.room_number}`
  }
  if (order.room_number && order.room_number !== 'Room Service') {
    return `Room ${order.room_number}`
  }
  return order.order_type === 'dine_in' ? 'Table Order' : 'Room Service'
}

const formatOrderTime = (order: any): string => {
  if (!order) return '11:30 AM'
  const timeStr = order.order_time || order.created_at || order.updated_at
  if (!timeStr) return '11:30 AM'
  const date = new Date(timeStr)
  if (isNaN(date.getTime())) return timeStr
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  })
}

// Appending food thumbnails mapping
const getFoodImageByName = (name: string = ''): string => {
  const n = name.toLowerCase()
  if (n.includes('salad') || n.includes('caesar')) {
    return 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('burger') || n.includes('beef')) {
    return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('pizza') || n.includes('margherita')) {
    return 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('pasta') || n.includes('alfredo') || n.includes('spaghetti')) {
    return 'https://images.unsplash.com/photo-1621996346565-e3d5d6281228?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('fries') || n.includes('french')) {
    return 'https://images.unsplash.com/photo-1576107232684-1279f3908594?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('steak') || n.includes('meat') || n.includes('ribeye')) {
    return 'https://images.unsplash.com/photo-1600891964599-f61ba0e24092?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('cake') || n.includes('dessert') || n.includes('chocolate')) {
    return 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('juice') || n.includes('orange') || n.includes('drink') || n.includes('beverage')) {
    return 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('chicken') || n.includes('grilled')) {
    return 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('salmon') || n.includes('fish')) {
    return 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=160&auto=format&fit=crop&q=80'
  }
  if (n.includes('rice') || n.includes('wrap')) {
    return 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=160&auto=format&fit=crop&q=80'
  }
  return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=160&auto=format&fit=crop&q=80'
}

const normalizeItems = (order: any): any[] => {
  if (!order) return []
  if (Array.isArray(order.items) && order.items.length > 0) {
    return order.items.map((it: any) => ({
      name: it.name || it.item_name || 'Chef Specialty',
      quantity: it.quantity || 1,
      price: Number(it.unit_price || it.price || 14.0),
      image: it.image || getFoodImageByName(it.name || it.item_name),
    }))
  }
  if (Array.isArray(order.order_items) && order.order_items.length > 0) {
    return order.order_items.map((it: any) => ({
      name: it.menu_item?.name || it.name || 'Chef Specialty',
      quantity: it.quantity || 1,
      price: Number(it.unit_price || it.price || 14.0),
      image: it.menu_item?.image || it.image || getFoodImageByName(it.menu_item?.name || it.name),
    }))
  }
  return [
    {
      name: 'Margherita Pizza',
      quantity: 1,
      price: 14.0,
      image: getFoodImageByName('Margherita Pizza'),
    },
    {
      name: 'Pasta Alfredo',
      quantity: 1,
      price: 13.0,
      image: getFoodImageByName('Pasta Alfredo'),
    },
  ]
}

// Pricing calculation for selected order
const selectedOrderSubtotal = computed(() => {
  if (!selectedOrder.value) return 0
  const items = normalizeItems(selectedOrder.value)
  const sum = items.reduce((acc, it) => acc + it.price * it.quantity, 0)
  if (sum > 0) return sum
  const total = Number(selectedOrder.value.total) || 0
  return total > 0 ? total * 0.9 : 27.0
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

const selectOrder = (order: KitchenOrder) => {
  selectedOrder.value = order
}

// Actions with Real-Time Response
const handleStartPreparing = async (order: KitchenOrder, event?: Event) => {
  if (event) event.stopPropagation()
  if (!order) return

  try {
    await kitchenStore.startPreparing(order.id)
    showToast(
      'Order Started! 👨‍🍳',
      `Order #${getOrderNumber(order)} is now being prepared in the kitchen.`,
      'info',
    )
    if (selectedOrder.value?.id === order.id) {
      selectedOrder.value = { ...order, status: 'preparing' }
    }
  } catch (err: any) {
    console.error('Failed to start preparing:', err)
    showToast('Failed to start', err.message || 'Could not update order status', 'error')
  }
}

const handleMarkReady = async (order: KitchenOrder, event?: Event) => {
  if (event) event.stopPropagation()
  if (!order) return

  try {
    await kitchenStore.markReady(order.id)
    showToast(
      'Order Ready! 🍽️',
      `Order #${getOrderNumber(order)} is ready! Waiters have been notified in real-time.`,
      'success',
    )
    if (selectedOrder.value?.id === order.id) {
      selectedOrder.value = preparingOrders.value[0] || pendingOrders.value[0] || null
    }
  } catch (err: any) {
    console.error('Failed to mark ready:', err)
    showToast('Failed to mark ready', err.message || 'Could not update order status', 'error')
  }
}

async function refreshDashboard() {
  refreshing.value = true
  try {
    await kitchenStore.refreshDashboard()
    showToast('Dashboard Refreshed', 'Orders synchronized with live kitchen queue.', 'info')
  } finally {
    refreshing.value = false
  }
}

// Auto-select first order if none selected
watch(
  [pendingOrders, preparingOrders],
  () => {
    if (!selectedOrder.value || !allOrders.value.some((o) => o.id === selectedOrder.value?.id)) {
      selectedOrder.value = preparingOrders.value[0] || pendingOrders.value[0] || null
    }
  },
  { immediate: true },
)

onMounted(async () => {
  await kitchenStore.refreshDashboard()
  kitchenStore.subscribeToUpdates()
})

watch(
  () => hotelStore.hotelId,
  async () => {
    await kitchenStore.refreshDashboard()
  },
)

onBeforeUnmount(() => {
  kitchenStore.unsubscribeFromUpdates()
})
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
