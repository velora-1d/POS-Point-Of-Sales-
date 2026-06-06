<script setup lang="ts">
import {
    activeCategory,
    activePaymentCheckout,
    categories,
    filteredProducts,
    formatPrice,
    getAppliedPromos,
    getOrderCustomerPrimary,
    getOrderCustomerSecondary,
    getPaymentActionHint,
    getPaymentMeta,
    getPaymentMethodConfig,
    getPaymentStatusClass,
    getPaymentStatusLabel,
    getProductImage,
    getTableBadgeClass,
    getTableCardClass,
    getTableIconClass,
    handleProductClick,
    hasPendingBeforeKitchenPayment,
    isOrderPaid,
    isReadyTable,
    paymentCheckoutModalOpen,
    productSearchQuery,
    resetTableSelection,
    selectedManagedOrder,
    selectedManagedOrderId,
    selectedTable,
    selectedTableCategory,
    selectOnlineOrder,
    selectTable,
    selectTakeawayOrder,
    tableActiveOrders,
    tables,
} from '@/Composables/useOrderState';
import { router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    Clock,
    Package,
    Search,
    User,
    Utensils,
} from '@lucide/vue';
import { ref } from 'vue';

const showOnlinePlatformModal = ref(false);
const clearingTableId = ref<string | null>(null);

const handleOnlinePlatformSelect = (
    source: 'gofood' | 'grabfood' | 'shopeefood' | 'maximfood',
) => {
    selectOnlineOrder(source);
    showOnlinePlatformModal.value = false;
};

// Helper: persen kapasitas meja terpakai
const getCapacityPercent = (table: any): number => {
    if (!table.capacity || table.capacity <= 0) return 0;
    return Math.min(
        100,
        Math.round(((table.current_guests ?? 0) / table.capacity) * 100),
    );
};

// Helper: warna progress bar kapasitas
const getCapacityBarClass = (table: any): string => {
    const pct = getCapacityPercent(table);
    if (pct >= 90) return 'bg-rose-500';
    if (pct >= 60) return 'bg-amber-400';
    return 'bg-emerald-500';
};

// Helper: hitung durasi meja occupied dalam menit
const getOccupiedMinutes = (table: any): number => {
    if (!table.occupied_at) return 0;
    return Math.floor(
        (Date.now() - new Date(table.occupied_at).getTime()) / 60000,
    );
};

// Helper: format durasi meja ke "Xj Ym" atau "Ym"
const formatTableTimer = (table: any): string => {
    const m = getOccupiedMinutes(table);
    const h = Math.floor(m / 60);
    const min = m % 60;
    if (h > 0) return `${h}j ${min}m`;
    return `${min}m`;
};

// Helper: class warna badge timer (ok=hijau, warning=kuning, danger=merah)
const getTableTimerClass = (table: any): string => {
    const m = getOccupiedMinutes(table);
    if (m >= 180) return 'text-rose-500 dark:text-rose-400';
    if (m >= 90) return 'text-amber-500 dark:text-amber-300';
    return 'text-emerald-600 dark:text-emerald-400';
};

// Clear meja manual
const clearTable = (table: any) => {
    if (!confirm(`Bersihkan ${table.name}? Semua order aktif akan dibatalkan.`))
        return;
    clearingTableId.value = table.id;
    router.post(
        route('settings.tables.clear', { table: table.id }),
        {},
        {
            onFinish: () => {
                clearingTableId.value = null;
            },
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <!-- KONDISI TAB TRANSAKSI BARU (LAYOUT LAMA) -->
    <!-- VIEW 1: Grid of Tables (when no table selected) -->
    <div
        v-if="!selectedTable"
        class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xl dark:border-slate-800/80 dark:bg-slate-900"
    >
        <div class="mb-6 flex items-center justify-between">
            <h3
                class="flex items-center gap-2 text-lg font-bold text-stone-900 dark:text-white"
            >
                <span
                    class="flex h-5 w-5 items-center justify-center text-orange-500"
                    >🏢</span
                >
                <span>Pilih Meja Restoran (Visual Map)</span>
            </h3>
            <div class="flex items-center gap-3 text-xs">
                <span
                    class="flex items-center gap-1.5 text-stone-500 dark:text-slate-400"
                >
                    <span class="h-2.5 w-2.5 rounded-full bg-orange-400"></span>
                    Takeaway
                </span>
                <span
                    class="flex items-center gap-1.5 text-stone-500 dark:text-slate-400"
                >
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-emerald-500"
                    ></span>
                    Available
                </span>
                <span
                    class="flex items-center gap-1.5 text-stone-500 dark:text-slate-400"
                >
                    <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                    Occupied (Terisi)
                </span>
            </div>
        </div>

        <!-- Tab Kategori Meja -->
        <div
            class="mb-5 flex w-fit select-none items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-100 p-1 dark:border-slate-800 dark:bg-slate-950"
        >
            <button
                type="button"
                @click="selectedTableCategory = 'indoor'"
                :class="[
                    'rounded-lg px-4 py-1.5 text-xs font-bold uppercase tracking-wider transition-all duration-200',
                    selectedTableCategory === 'indoor'
                        ? 'bg-orange-500 text-white shadow-md'
                        : 'text-stone-500 hover:text-slate-200 dark:text-slate-200 dark:text-slate-400 dark:hover:text-stone-800',
                ]"
            >
                Indoor
            </button>
            <button
                type="button"
                @click="selectedTableCategory = 'outdoor'"
                :class="[
                    'rounded-lg px-4 py-1.5 text-xs font-bold uppercase tracking-wider transition-all duration-200',
                    selectedTableCategory === 'outdoor'
                        ? 'bg-orange-500 text-white shadow-md'
                        : 'text-stone-500 hover:text-slate-200 dark:text-slate-200 dark:text-slate-400 dark:hover:text-stone-800',
                ]"
            >
                Outdoor
            </button>
        </div>

        <!-- Tables Grid -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
            <div
                @click="selectTakeawayOrder"
                class="group relative cursor-pointer select-none rounded-2xl border border-orange-500/20 bg-orange-500/[0.08] p-5 text-center text-orange-700 shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:border-orange-500/40 hover:shadow-orange-500/10 dark:text-orange-100"
            >
                <div
                    class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border border-orange-500/30 bg-orange-500/10 text-orange-600 dark:text-orange-300"
                >
                    <Package class="h-4 w-4" />
                </div>

                <h4
                    class="text-base font-extrabold text-stone-900 dark:text-white"
                >
                    Takeaway
                </h4>
                <p
                    class="mt-1 text-[10px] uppercase tracking-wider text-stone-500 dark:text-orange-200/70"
                >
                    Pesanan bungkus / dibawa pulang
                </p>

                <span
                    class="absolute right-3 top-3 rounded border border-orange-500/20 bg-orange-500/10 px-1.5 py-0.5 text-[8px] font-bold uppercase text-orange-600 dark:text-orange-300"
                >
                    Bungkus
                </span>
            </div>

            <!-- Order Online Card -->
            <div
                @click="showOnlinePlatformModal = true"
                class="group relative cursor-pointer select-none rounded-2xl border border-blue-500/20 bg-blue-500/[0.08] p-5 text-center text-blue-700 shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-500/40 hover:shadow-blue-500/10 dark:text-blue-100"
            >
                <div
                    class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-300"
                >
                    <span class="text-base">🌐</span>
                </div>

                <h4
                    class="text-base font-extrabold text-stone-900 dark:text-white"
                >
                    Order Online
                </h4>
                <p
                    class="mt-1 text-[10px] uppercase tracking-wider text-stone-500 dark:text-blue-200/70"
                >
                    Pencatatan manual GoFood, GrabFood, ShopeeFood, Maxim
                </p>

                <span
                    class="absolute right-3 top-3 rounded border border-blue-500/20 bg-blue-500/10 px-1.5 py-0.5 text-[8px] font-bold uppercase text-blue-600 dark:text-blue-300"
                >
                    Online
                </span>
            </div>

            <div
                v-for="table in tables.filter(
                    (t: any) => t.category === selectedTableCategory,
                )"
                :key="table.id"
                @click="selectTable(table)"
                :class="[
                    'group relative cursor-pointer select-none rounded-2xl border p-5 text-center shadow-md transition-all duration-200',
                    getTableCardClass(table),
                ]"
            >
                <div
                    :class="[
                        'mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border text-sm font-bold',
                        getTableIconClass(table),
                    ]"
                >
                    T
                </div>

                <h4 class="text-base font-extrabold">{{ table.name }}</h4>

                <!-- Progress Bar Kapasitas -->
                <div v-if="table.capacity" class="mt-2 space-y-1">
                    <div class="flex items-center justify-between">
                        <span
                            class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                        >
                            {{ table.current_guests ?? 0 }}/{{ table.capacity }}
                            Pax
                        </span>
                        <span
                            v-if="table.occupied_at"
                            :class="[
                                'inline-flex items-center gap-0.5 text-[9px] font-bold',
                                getTableTimerClass(table),
                            ]"
                        >
                            <Clock class="h-2.5 w-2.5" />
                            {{ formatTableTimer(table) }}
                        </span>
                    </div>
                    <div
                        class="h-1.5 w-full overflow-hidden rounded-full bg-stone-200 dark:bg-slate-700"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="getCapacityBarClass(table)"
                            :style="{ width: getCapacityPercent(table) + '%' }"
                        />
                    </div>
                </div>
                <p
                    v-else
                    class="mt-1 text-[10px] uppercase tracking-wider text-stone-500 dark:text-slate-500"
                >
                    Kapasitas: — Pax
                </p>

                <span
                    :class="[
                        'absolute right-3 top-3 rounded border px-1.5 py-0.5 text-[8px] font-bold uppercase',
                        getTableBadgeClass(table),
                    ]"
                >
                    {{ table.status }}
                </span>

                <div
                    v-if="table.status === 'occupied' && table.active_order"
                    class="mt-3 border-t border-red-500/10 pt-3 text-left"
                >
                    <p
                        class="truncate text-[9px] font-bold uppercase text-red-600 dark:text-red-400"
                    >
                        {{ table.active_order.order_number }}
                    </p>
                    <p
                        class="mt-0.5 text-[10px] font-bold text-stone-800 dark:text-slate-300"
                    >
                        {{ formatPrice(table.active_order.total_amount) }}
                    </p>
                    <p
                        v-if="table.active_orders?.length > 1"
                        class="mt-1 text-[9px] font-semibold text-orange-600 dark:text-orange-300"
                    >
                        {{ table.active_orders.length }} bill aktif
                    </p>
                </div>
                <div
                    v-else-if="table.active_reservation"
                    class="mt-3 border-t border-amber-500/10 pt-3 text-left"
                >
                    <p
                        class="truncate text-[9px] font-bold uppercase text-amber-600 dark:text-amber-300"
                    >
                        Reservasi Aktif
                    </p>
                    <p
                        class="mt-0.5 text-[10px] font-bold text-stone-800 dark:text-slate-200"
                    >
                        {{ table.active_reservation.customer_name }}
                    </p>
                    <p
                        class="mt-1 text-[9px] text-stone-500 dark:text-slate-400"
                    >
                        {{ table.active_reservation.guest_count }} pax
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW 2: Product Catalog (when available table selected) -->
    <div
        v-else-if="isReadyTable(selectedTable)"
        class="space-y-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-xl dark:border-slate-800/80 dark:bg-slate-900"
    >
        <!-- Catalog Header with Back Button -->
        <div
            class="flex flex-col justify-between gap-4 border-b border-stone-200 pb-5 dark:border-slate-800/60 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-3">
                <button
                    @click="resetTableSelection"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-stone-200 bg-stone-100 text-stone-600 transition duration-150 hover:bg-stone-200 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    <ChevronLeft class="h-5 w-5" />
                </button>
                <div>
                    <h3
                        class="text-base font-bold text-stone-900 dark:text-white"
                    >
                        Pilih Produk Mentai
                    </h3>
                    <p
                        class="mt-0.5 text-[10px] text-stone-500 dark:text-slate-400"
                    >
                        Melayani pesanan untuk
                        <span
                            class="font-bold text-orange-500 dark:text-orange-400"
                            >{{ selectedTable.name }}</span
                        >
                    </p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="relative w-full sm:w-64">
                <span
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-stone-400 dark:text-slate-500"
                >
                    <Search class="h-4 w-4" />
                </span>
                <input
                    type="text"
                    v-model="productSearchQuery"
                    placeholder="Cari makanan/minuman..."
                    class="w-full rounded-xl border border-stone-200 bg-stone-100/50 py-2 pl-9 pr-4 text-xs text-stone-800 placeholder-stone-400 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950/80 dark:text-slate-200 dark:placeholder-slate-500"
                />
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="custom-scrollbar flex gap-2 overflow-x-auto pb-2">
            <button
                @click="activeCategory = 'all'"
                :class="[
                    'whitespace-nowrap rounded-xl border px-4 py-2 text-xs font-semibold transition duration-150',
                    activeCategory === 'all'
                        ? 'border-orange-500/30 bg-orange-500/10 text-orange-600 dark:text-orange-400'
                        : 'border-stone-200 bg-stone-100 text-stone-500 hover:bg-stone-200 hover:text-stone-900 dark:border-slate-800 dark:bg-slate-800/40 dark:bg-slate-950/60 dark:text-slate-200 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-stone-800',
                ]"
            >
                Semua Menu
            </button>
            <button
                v-for="cat in categories"
                :key="cat.id"
                @click="activeCategory = cat.id"
                :class="[
                    'whitespace-nowrap rounded-xl border px-4 py-2 text-xs font-semibold transition duration-150',
                    activeCategory === cat.id
                        ? 'border-orange-500/30 bg-orange-500/10 text-orange-600 dark:text-orange-400'
                        : 'border-stone-200 bg-stone-100 text-stone-500 hover:bg-stone-200 hover:text-stone-900 dark:border-slate-800 dark:bg-slate-800/40 dark:bg-slate-950/60 dark:text-slate-200 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-stone-800',
                ]"
            >
                {{ cat.name }}
            </button>
        </div>

        <!-- Products Grid -->
        <div
            v-if="filteredProducts.length > 0"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="product in filteredProducts"
                :key="product.id"
                @click="handleProductClick(product)"
                class="group flex cursor-pointer flex-col justify-between overflow-hidden rounded-xl border border-stone-200 bg-stone-50 transition duration-150 hover:border-orange-500/30 hover:bg-white hover:shadow-md hover:shadow-orange-500/5 dark:border-slate-800/60 dark:bg-slate-950 dark:bg-slate-950/60 dark:hover:bg-slate-800"
            >
                <div
                    class="relative aspect-square w-full overflow-hidden border-b border-stone-200 bg-stone-100 dark:border-slate-800 dark:bg-slate-900"
                >
                    <img
                        :src="getProductImage(product)"
                        :alt="product.name"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <span
                        class="absolute right-2 top-2 rounded border border-stone-200 bg-stone-100/90 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-stone-600 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-300"
                    >
                        {{ product.category_name }}
                    </span>
                </div>

                <div
                    class="flex flex-1 flex-col justify-between space-y-3 p-3.5"
                >
                    <div>
                        <h4
                            class="text-xs font-bold leading-tight text-stone-900 dark:text-white"
                        >
                            {{ product.name }}
                        </h4>
                        <p
                            class="mt-1 line-clamp-2 text-[10px] text-stone-500 dark:text-slate-400"
                        >
                            {{ product.description || 'Tidak ada deskripsi.' }}
                        </p>
                    </div>

                    <div
                        class="flex items-center justify-between border-t border-stone-200 pt-2.5 dark:border-slate-800"
                    >
                        <span
                            class="text-xs font-extrabold text-orange-600 dark:text-orange-400"
                            >{{ formatPrice(product.base_price) }}</span
                        >
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10 text-xs font-black text-orange-600 dark:text-orange-400"
                            >+</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-xl border border-dashed border-stone-200 py-12 text-center text-stone-500 dark:border-slate-800 dark:text-slate-500"
        >
            <Utensils
                class="mx-auto mb-2 h-10 w-10 text-stone-400 dark:text-slate-600"
            />
            <p class="text-xs">
                Tidak ada menu yang sesuai dengan filter pencarian.
            </p>
        </div>
    </div>

    <!-- VIEW 3: Detail Order per Meja (when occupied table selected) -->
    <div
        v-else-if="selectedTable.status === 'occupied'"
        class="space-y-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-xl dark:border-slate-800/80 dark:bg-slate-900"
    >
        <div
            class="flex items-center gap-3 border-b border-stone-200 pb-5 dark:border-slate-800/60"
        >
            <button
                @click="resetTableSelection"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-stone-200 bg-stone-100 text-stone-600 transition duration-150 hover:bg-stone-200 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-800"
            >
                <ChevronLeft class="h-5 w-5" />
            </button>
            <div>
                <h3
                    class="flex items-center gap-2 text-base font-bold text-stone-900 dark:text-white"
                >
                    <span>Detail Meja: {{ selectedTable.name }}</span>
                    <span
                        class="rounded border border-red-500/20 bg-red-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-red-600 dark:text-red-400"
                    >
                        Occupied
                    </span>
                </h3>
                <p
                    class="mt-0.5 text-[10px] text-stone-500 dark:text-slate-400"
                >
                    Menampilkan rincian pesanan pelanggan di meja saat ini.
                </p>
            </div>
        </div>

        <div
            v-if="selectedManagedOrder"
            class="grid grid-cols-1 gap-4 rounded-xl border border-stone-200 bg-stone-100/50 p-4 text-xs dark:border-slate-800 dark:bg-slate-950/60 md:grid-cols-2 xl:grid-cols-5"
        >
            <div>
                <p
                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                >
                    Nomor Transaksi
                </p>
                <p
                    class="mt-1 text-sm font-extrabold text-stone-800 dark:text-white"
                >
                    {{ selectedManagedOrder.order_number }}
                </p>
            </div>
            <div>
                <p
                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                >
                    Pelanggan
                </p>
                <p class="mt-1 font-semibold text-stone-800 dark:text-white">
                    {{ getOrderCustomerPrimary(selectedManagedOrder) }}
                </p>
                <p
                    v-if="getOrderCustomerSecondary(selectedManagedOrder)"
                    class="mt-0.5 text-[10px] text-stone-500 dark:text-slate-400"
                >
                    {{ getOrderCustomerSecondary(selectedManagedOrder) }}
                </p>
            </div>
            <div>
                <p
                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                >
                    Kasir Bertugas
                </p>
                <p
                    class="mt-1 flex items-center gap-1.5 font-semibold text-stone-800 dark:text-white"
                >
                    <User
                        class="h-3.5 w-3.5 text-stone-500 dark:text-slate-400"
                    />
                    <span>{{
                        selectedManagedOrder.cashier?.name || 'Kasir Restoran'
                    }}</span>
                </p>
            </div>
            <div>
                <p
                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                >
                    Waktu Masuk
                </p>
                <p
                    class="mt-1 flex items-center gap-1.5 font-semibold text-stone-800 dark:text-white"
                >
                    <Clock
                        class="h-3.5 w-3.5 text-stone-500 dark:text-slate-400"
                    />
                    <span
                        >{{
                            new Date(
                                selectedManagedOrder.created_at,
                            ).toLocaleTimeString('id-ID', {
                                hour: '2-digit',
                                minute: '2-digit',
                            })
                        }}
                        WIB</span
                    >
                </p>
            </div>
            <div>
                <p
                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                >
                    Status Bayar
                </p>
                <div class="mt-1 space-y-2">
                    <span
                        :class="[
                            'inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider',
                            getPaymentStatusClass(selectedManagedOrder),
                        ]"
                    >
                        {{ getPaymentStatusLabel(selectedManagedOrder) }}
                    </span>
                    <p class="text-[10px] text-stone-500 dark:text-slate-400">
                        {{
                            isOrderPaid(selectedManagedOrder)
                                ? 'Tagihan sudah diterima.'
                                : getPaymentActionHint(selectedManagedOrder)
                        }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="selectedManagedOrder" class="space-y-3">
            <div
                v-if="tableActiveOrders.length > 1"
                class="custom-scrollbar flex gap-2 overflow-x-auto pb-1"
            >
                <button
                    v-for="order in tableActiveOrders"
                    :key="`managed-order-${order.id}`"
                    @click="selectedManagedOrderId = order.id"
                    :class="[
                        'whitespace-nowrap rounded-xl border px-3 py-2 text-[11px] font-bold transition',
                        selectedManagedOrderId === order.id
                            ? 'border-orange-500/30 bg-orange-500/10 text-orange-600 dark:text-orange-300'
                            : 'border-stone-200 bg-stone-100 text-stone-500 hover:text-stone-900 dark:border-slate-800 dark:bg-slate-950/70 dark:text-slate-200 dark:text-slate-400 dark:hover:text-stone-800',
                    ]"
                >
                    {{ order.order_number }}
                </button>
            </div>

            <h4
                class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
            >
                Daftar Item Makanan/Minuman
            </h4>
            <div
                class="overflow-hidden rounded-xl border border-stone-200 bg-stone-50 dark:border-slate-800/80 dark:bg-slate-950/20"
            >
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr
                            class="border-b border-stone-200 bg-stone-100 text-stone-500 dark:border-slate-800 dark:bg-slate-950/80 dark:text-slate-400"
                        >
                            <th class="p-3">Nama Menu</th>
                            <th class="p-3 text-center">Jumlah</th>
                            <th class="p-3 text-right">Harga Satuan</th>
                            <th class="p-3 text-right">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-stone-200 dark:divide-slate-800"
                    >
                        <tr
                            v-for="item in selectedManagedOrder.items"
                            :key="item.id"
                            class="text-stone-800 dark:text-slate-200"
                        >
                            <td class="p-3">
                                <div class="font-bold">
                                    {{ item.product?.name }}
                                </div>
                                <div
                                    v-if="item.variant"
                                    class="mt-0.5 text-[9px] font-semibold text-orange-600 dark:text-orange-400"
                                >
                                    Varian: {{ item.variant.name }}
                                </div>
                                <div
                                    v-if="item.notes"
                                    class="mt-0.5 text-[9px] italic text-stone-500 dark:text-slate-400"
                                >
                                    "{{ item.notes }}"
                                </div>
                            </td>
                            <td
                                class="p-3 text-center font-bold text-stone-900 dark:text-slate-100"
                            >
                                {{ item.quantity }}x
                            </td>
                            <td class="p-3 text-right">
                                {{ formatPrice(item.unit_price) }}
                            </td>
                            <td
                                class="p-3 text-right font-extrabold text-stone-900 dark:text-white"
                            >
                                {{ formatPrice(item.total_price) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="selectedManagedOrder"
            class="flex flex-col justify-between gap-4 border-t border-stone-200 pt-5 text-xs dark:border-slate-800/50 md:flex-row"
        >
            <div class="md:w-1/2">
                <p
                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                >
                    Catatan Khusus Pesanan
                </p>
                <p
                    class="mt-1 rounded-lg border border-stone-200 bg-stone-100/50 p-3 italic text-stone-600 dark:border-slate-800 dark:bg-slate-950/50 dark:text-slate-400"
                >
                    {{
                        selectedManagedOrder.notes ||
                        'Tidak ada catatan khusus.'
                    }}
                </p>
            </div>
            <div
                class="space-y-2 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-slate-800 dark:bg-slate-950/50 md:w-1/3"
            >
                <div
                    class="flex justify-between text-stone-500 dark:text-slate-400"
                >
                    <span>Subtotal:</span>
                    <span>{{
                        formatPrice(selectedManagedOrder.subtotal)
                    }}</span>
                </div>
                <div
                    class="flex justify-between text-stone-500 dark:text-slate-400"
                >
                    <span>Diskon:</span>
                    <span>{{
                        formatPrice(selectedManagedOrder.discount_amount)
                    }}</span>
                </div>
                <div
                    class="flex justify-between border-t border-stone-200 pt-2 text-sm font-black text-stone-900 dark:border-slate-800 dark:text-white"
                >
                    <span>Total Tagihan:</span>
                    <span class="text-orange-600 dark:text-orange-400">{{
                        formatPrice(selectedManagedOrder.total_amount)
                    }}</span>
                </div>
                <div
                    class="flex justify-between border-t border-stone-200 pt-2 text-stone-500 dark:border-slate-800 dark:text-slate-400"
                >
                    <span>Terbayar:</span>
                    <span>{{
                        formatPrice(selectedManagedOrder.paid_amount)
                    }}</span>
                </div>
                <div
                    v-if="getAppliedPromos(selectedManagedOrder).length"
                    class="space-y-2 border-t border-stone-200 pt-2 dark:border-slate-800"
                >
                    <p
                        class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                    >
                        Promo Applied
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="promo in getAppliedPromos(
                                selectedManagedOrder,
                            )"
                            :key="promo.id"
                            class="rounded-full border border-orange-500/25 bg-orange-500/10 px-2 py-1 text-[10px] font-bold text-orange-600 dark:text-orange-200"
                        >
                            {{ promo.name }} •
                            {{ formatPrice(promo.discount_amount) }}
                        </span>
                    </div>
                </div>
            </div>
            <div
                class="space-y-2 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-slate-800 dark:bg-slate-950/50 md:w-1/3"
            >
                <div class="flex items-center justify-between gap-3">
                    <p
                        class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                    >
                        Ringkasan Pembayaran
                    </p>
                    <span
                        :class="[
                            'rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                            getPaymentStatusClass(selectedManagedOrder),
                        ]"
                    >
                        {{ getPaymentStatusLabel(selectedManagedOrder) }}
                    </span>
                </div>
                <p class="text-[11px] text-stone-500 dark:text-slate-400">
                    {{
                        getPaymentMeta(selectedManagedOrder).method
                            ? `Metode terakhir: ${String(getPaymentMeta(selectedManagedOrder).method).toUpperCase()}`
                            : 'Belum ada metode pembayaran yang dipilih.'
                    }}
                </p>
                <button
                    v-if="
                        getPaymentMeta(selectedManagedOrder).checkout_url &&
                        !isOrderPaid(selectedManagedOrder)
                    "
                    type="button"
                    @click="
                        activePaymentCheckout = {
                            payment_url:
                                getPaymentMeta(selectedManagedOrder)
                                    .checkout_url,
                            order_number: selectedManagedOrder.order_number,
                            amount: selectedManagedOrder.total_amount,
                            context: hasPendingBeforeKitchenPayment(
                                selectedManagedOrder,
                            )
                                ? 'before_kitchen'
                                : 'after_service',
                            method:
                                getPaymentMeta(selectedManagedOrder).method ||
                                'qris',
                        };
                        paymentCheckoutModalOpen = true;
                    "
                    class="w-full rounded-xl border px-3 py-2 text-[11px] font-bold transition"
                    :class="
                        getPaymentMethodConfig(
                            getPaymentMeta(selectedManagedOrder).method ||
                                'qris',
                        ).buttonClass
                    "
                >
                    Buka Checkout
                    {{
                        getPaymentMethodConfig(
                            getPaymentMeta(selectedManagedOrder).method ||
                                'qris',
                        ).label
                    }}
                    Aktif
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Pemilihan Platform Online -->
    <div
        v-if="showOnlinePlatformModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/40 p-4 backdrop-blur-sm dark:bg-black/60"
        @click.self="showOnlinePlatformModal = false"
    >
        <div
            class="animate-in fade-in zoom-in-95 w-full max-w-md scale-100 transform overflow-hidden rounded-3xl border border-stone-200 bg-white p-6 shadow-2xl transition-all duration-150 dark:border-slate-800 dark:bg-slate-900"
        >
            <div
                class="mb-5 flex items-center justify-between border-b border-stone-100 pb-3 dark:border-slate-800"
            >
                <h3
                    class="text-base font-extrabold text-stone-900 dark:text-white"
                >
                    Pilih Platform Order Online
                </h3>
                <button
                    @click="showOnlinePlatformModal = false"
                    class="rounded-lg p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-700 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                >
                    ✕
                </button>
            </div>

            <p class="mb-6 text-xs text-stone-500 dark:text-slate-400">
                Silakan pilih layanan ojek online / delivery platform dari
                pesanan manual yang diterima.
            </p>

            <div class="grid grid-cols-2 gap-4">
                <!-- GoFood (Merah) -->
                <button
                    @click="handleOnlinePlatformSelect('gofood')"
                    class="flex flex-col items-center gap-3 rounded-2xl border border-red-500/20 bg-red-500/[0.04] p-4 text-center transition hover:-translate-y-0.5 hover:border-red-500/50 hover:bg-red-500/[0.08] dark:bg-red-500/[0.06] dark:hover:bg-red-500/[0.12]"
                >
                    <span class="text-2xl">🔴</span>
                    <span
                        class="text-xs font-bold text-red-600 dark:text-red-400"
                        >GoFood</span
                    >
                </button>

                <!-- GrabFood (Hijau) -->
                <button
                    @click="handleOnlinePlatformSelect('grabfood')"
                    class="flex flex-col items-center gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-500/[0.04] p-4 text-center transition hover:-translate-y-0.5 hover:border-emerald-500/50 hover:bg-emerald-500/[0.08] dark:bg-emerald-500/[0.06] dark:hover:bg-emerald-500/[0.12]"
                >
                    <span class="text-2xl">🟢</span>
                    <span
                        class="text-xs font-bold text-emerald-600 dark:text-emerald-400"
                        >GrabFood</span
                    >
                </button>

                <!-- ShopeeFood (Oranye) -->
                <button
                    @click="handleOnlinePlatformSelect('shopeefood')"
                    class="flex flex-col items-center gap-3 rounded-2xl border border-orange-500/20 bg-orange-500/[0.04] p-4 text-center transition hover:-translate-y-0.5 hover:border-orange-500/50 hover:bg-orange-500/[0.08] dark:bg-orange-500/[0.06] dark:hover:bg-orange-500/[0.12]"
                >
                    <span class="text-2xl">🟠</span>
                    <span
                        class="text-xs font-bold text-orange-600 dark:text-orange-400"
                        >ShopeeFood</span
                    >
                </button>

                <!-- Maxim Food (Kuning/Aksen Hitam-Kuning) -->
                <button
                    @click="handleOnlinePlatformSelect('maximfood')"
                    class="flex flex-col items-center gap-3 rounded-2xl border border-yellow-500/20 bg-yellow-500/[0.04] p-4 text-center transition hover:-translate-y-0.5 hover:border-yellow-500/50 hover:bg-yellow-500/[0.08] dark:bg-yellow-500/[0.06] dark:hover:bg-yellow-500/[0.12]"
                >
                    <span class="text-2xl">🟡</span>
                    <span
                        class="text-xs font-bold text-yellow-600 dark:text-yellow-400"
                        >Maxim Food</span
                    >
                </button>
            </div>
        </div>
    </div>
</template>
