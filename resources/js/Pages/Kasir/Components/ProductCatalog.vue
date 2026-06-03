<script setup lang="ts">
import {
    ChevronLeft,
    Search,
    Utensils,
    Package,
    Clock,
    User,
    Receipt,
} from '@lucide/vue';
import {
    tables,
    categories,
    selectedTable,
    selectedTableCategory,
    productSearchQuery,
    activeCategory,
    filteredProducts,
    handleProductClick,
    getProductImage,
    formatPrice,
    isReadyTable,
    getTableCardClass,
    getTableIconClass,
    getTableBadgeClass,
    selectedManagedOrder,
    selectedManagedOrderId,
    tableActiveOrders,
    getOrderCustomerPrimary,
    getOrderCustomerSecondary,
    getPaymentStatusClass,
    getPaymentStatusLabel,
    isOrderPaid,
    getPaymentActionHint,
    getAppliedPromos,
    getPaymentMeta,
    hasPendingBeforeKitchenPayment,
    paymentCheckoutModalOpen,
    activePaymentCheckout,
    getPaymentMethodConfig,
    resetTableSelection,
    selectTable,
    selectTakeawayOrder,
    getStatusClass,
} from '@/Composables/useOrderState';
</script>

<template>
    <!-- KONDISI TAB TRANSAKSI BARU (LAYOUT LAMA) -->
    <!-- VIEW 1: Grid of Tables (when no table selected) -->
    <div
        v-if="!selectedTable"
        class="rounded-2xl border border-stone-200 dark:border-slate-800/80 bg-white dark:bg-slate-900 p-6 shadow-xl"
    >
        <div class="mb-6 flex items-center justify-between">
            <h3 class="flex items-center gap-2 text-lg font-bold text-stone-900 dark:text-white">
                <span class="flex h-5 w-5 text-orange-500 items-center justify-center">🏢</span>
                <span>Pilih Meja Restoran (Visual Map)</span>
            </h3>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5 text-stone-500 dark:text-slate-400">
                    <span class="h-2.5 w-2.5 rounded-full bg-orange-400"></span>
                    Takeaway
                </span>
                <span class="flex items-center gap-1.5 text-stone-500 dark:text-slate-400">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Available
                </span>
                <span class="flex items-center gap-1.5 text-stone-500 dark:text-slate-400">
                    <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                    Occupied (Terisi)
                </span>
            </div>
        </div>

        <!-- Tab Kategori Meja -->
        <div class="mb-5 flex items-center gap-1.5 rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 p-1 w-fit select-none">
            <button
                type="button"
                @click="selectedTableCategory = 'indoor'"
                :class="[
                    'rounded-lg px-4 py-1.5 text-xs font-bold uppercase tracking-wider transition-all duration-200',
                    selectedTableCategory === 'indoor'
                        ? 'bg-orange-500 text-white shadow-md'
                        : 'text-stone-500 dark:text-slate-400 hover:text-slate-250 dark:hover:text-stone-800 dark:text-slate-200',
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
                        : 'text-stone-500 dark:text-slate-400 hover:text-slate-250 dark:hover:text-stone-800 dark:text-slate-200',
                ]"
            >
                Outdoor
            </button>
        </div>

        <!-- Tables Grid -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
            <div
                @click="selectTakeawayOrder"
                class="bg-orange-500/[0.08] group relative cursor-pointer select-none rounded-2xl border border-orange-500/20 p-5 text-center text-orange-700 dark:text-orange-100 shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:border-orange-500/40 hover:shadow-orange-500/10"
            >
                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border border-orange-500/30 bg-orange-500/10 text-orange-600 dark:text-orange-300">
                    <Package class="h-4 w-4" />
                </div>

                <h4 class="text-base font-extrabold text-stone-900 dark:text-white">Takeaway</h4>
                <p class="mt-1 text-[10px] uppercase tracking-wider text-stone-500 dark:text-orange-200/70">
                    Pesanan bungkus / dibawa pulang
                </p>

                <span class="absolute right-3 top-3 rounded border border-orange-500/20 bg-orange-500/10 px-1.5 py-0.5 text-[8px] font-bold uppercase text-orange-600 dark:text-orange-300">
                    Bungkus
                </span>
            </div>

            <div
            v-for="table in tables.filter((t: any) => t.category === selectedTableCategory)"
                :key="table.id"
                @click="selectTable(table)"
                :class="[
                    'group relative cursor-pointer select-none rounded-2xl border p-5 text-center shadow-md transition-all duration-200',
                    getTableCardClass(table),
                ]"
            >
                <div :class="['mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border text-sm font-bold', getTableIconClass(table)]">
                    T
                </div>

                <h4 class="text-base font-extrabold">{{ table.name }}</h4>
                <p class="mt-1 text-[10px] uppercase tracking-wider text-stone-550 dark:text-slate-500">
                    Kapasitas: {{ table.capacity }} Pax
                </p>

                <span :class="['absolute right-3 top-3 rounded border px-1.5 py-0.5 text-[8px] font-bold uppercase', getTableBadgeClass(table)]">
                    {{ table.status }}
                </span>

                <div v-if="table.status === 'occupied' && table.active_order" class="mt-3 border-t border-red-500/10 pt-3 text-left">
                    <p class="truncate text-[9px] font-bold uppercase text-red-650 dark:text-red-400">
                        {{ table.active_order.order_number }}
                    </p>
                    <p class="mt-0.5 text-[10px] font-bold text-stone-850 dark:text-slate-300">
                        {{ formatPrice(table.active_order.total_amount) }}
                    </p>
                    <p v-if="table.active_orders?.length > 1" class="mt-1 text-[9px] font-semibold text-orange-600 dark:text-orange-300">
                        {{ table.active_orders.length }} bill aktif
                    </p>
                </div>
                <div v-else-if="table.active_reservation" class="mt-3 border-t border-amber-500/10 pt-3 text-left">
                    <p class="truncate text-[9px] font-bold uppercase text-amber-600 dark:text-amber-300">
                        Reservasi Aktif
                    </p>
                    <p class="mt-0.5 text-[10px] font-bold text-stone-850 dark:text-slate-200">
                        {{ table.active_reservation.customer_name }}
                    </p>
                    <p class="mt-1 text-[9px] text-stone-500 dark:text-slate-400">
                        {{ table.active_reservation.guest_count }} pax
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW 2: Product Catalog (when available table selected) -->
    <div
        v-else-if="isReadyTable(selectedTable)"
        class="space-y-6 rounded-2xl border border-stone-200 dark:border-slate-800/80 bg-white dark:bg-slate-900 p-6 shadow-xl"
    >
        <!-- Catalog Header with Back Button -->
        <div class="flex flex-col justify-between gap-4 border-b border-stone-200 dark:border-slate-800/60 pb-5 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                <button
                    @click="resetTableSelection"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-955 text-stone-600 dark:text-slate-300 transition duration-150 hover:bg-stone-200 dark:hover:bg-stone-100 dark:bg-slate-800"
                >
                    <ChevronLeft class="h-5 w-5" />
                </button>
                <div>
                    <h3 class="text-base font-bold text-stone-900 dark:text-white">Pilih Produk Mentai</h3>
                    <p class="mt-0.5 text-[10px] text-stone-500 dark:text-slate-400">
                        Melayani pesanan untuk
                        <span class="font-bold text-orange-500 dark:text-orange-400">{{ selectedTable.name }}</span>
                    </p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="relative w-full sm:w-64">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-stone-450 dark:text-slate-500">
                    <Search class="h-4 w-4" />
                </span>
                <input
                    type="text"
                    v-model="productSearchQuery"
                    placeholder="Cari makanan/minuman..."
                    class="border-stone-200 dark:border-slate-850 w-full rounded-xl border bg-stone-100/50 dark:bg-slate-950/80 py-2 pl-9 pr-4 text-xs text-stone-850 dark:text-slate-200 placeholder-stone-400 dark:placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
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
                        : 'border-stone-200 dark:border-slate-850 bg-stone-100 dark:bg-slate-950/60 text-stone-500 dark:text-slate-400 hover:bg-stone-200 dark:hover:bg-stone-100 dark:bg-slate-800/40 hover:text-stone-900 dark:hover:text-stone-800 dark:text-slate-200',
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
                        : 'border-stone-200 dark:border-slate-850 bg-stone-100 dark:bg-slate-950/60 text-stone-500 dark:text-slate-400 hover:bg-stone-200 dark:hover:bg-stone-100 dark:bg-slate-800/40 hover:text-stone-900 dark:hover:text-stone-800 dark:text-slate-200',
                ]"
            >
                {{ cat.name }}
            </button>
        </div>

        <!-- Products Grid -->
        <div v-if="filteredProducts.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="product in filteredProducts"
                :key="product.id"
                @click="handleProductClick(product)"
                class="hover:shadow-orange-500/5 group flex cursor-pointer flex-col justify-between overflow-hidden rounded-xl border border-stone-200 dark:border-slate-800/60 bg-stone-50 dark:bg-slate-950/60 transition duration-150 hover:bg-white dark:hover:bg-stone-100 dark:bg-slate-950 hover:border-orange-500/30 hover:shadow-md"
            >
                <div class="relative aspect-square w-full overflow-hidden border-b border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-900">
                    <img
                        :src="getProductImage(product)"
                        :alt="product.name"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <span class="text-stone-600 dark:text-slate-350 absolute right-2 top-2 rounded border border-stone-200 dark:border-slate-800 bg-stone-100/90 dark:bg-slate-900/80 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider backdrop-blur-sm">
                        {{ product.category_name }}
                    </span>
                </div>

                <div class="flex flex-1 flex-col justify-between space-y-3 p-3.5">
                    <div>
                        <h4 class="text-xs font-bold leading-tight text-stone-900 dark:text-white">{{ product.name }}</h4>
                        <p class="text-stone-500 dark:text-slate-400 mt-1 line-clamp-2 text-[10px]">{{ product.description || 'Tidak ada deskripsi.' }}</p>
                    </div>

                    <div class="border-stone-200 dark:border-slate-850 flex items-center justify-between border-t pt-2.5">
                        <span class="text-xs font-extrabold text-orange-655 dark:text-orange-400">{{ formatPrice(product.base_price) }}</span>
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10 text-xs font-black text-orange-600 dark:text-orange-400">+</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="rounded-xl border border-dashed border-stone-200 dark:border-slate-800 py-12 text-center text-stone-500 dark:text-slate-500">
            <Utensils class="mx-auto mb-2 h-10 w-10 text-stone-400 dark:text-slate-650" />
            <p class="text-xs">Tidak ada menu yang sesuai dengan filter pencarian.</p>
        </div>
    </div>

    <!-- VIEW 3: Detail Order per Meja (when occupied table selected) -->
    <div
        v-else-if="selectedTable.status === 'occupied'"
        class="space-y-6 rounded-2xl border border-stone-200 dark:border-slate-800/80 bg-white dark:bg-slate-900 p-6 shadow-xl"
    >
        <div class="flex items-center gap-3 border-b border-stone-200 dark:border-slate-800/60 pb-5">
            <button
                @click="resetTableSelection"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-955 text-stone-600 dark:text-slate-300 transition duration-150 hover:bg-stone-200 dark:hover:bg-stone-100 dark:bg-slate-800"
            >
                <ChevronLeft class="h-5 w-5" />
            </button>
            <div>
                <h3 class="flex items-center gap-2 text-base font-bold text-stone-900 dark:text-white">
                    <span>Detail Meja: {{ selectedTable.name }}</span>
                    <span class="rounded border border-red-500/20 bg-red-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-red-650 dark:text-red-400">
                        Occupied
                    </span>
                </h3>
                <p class="mt-0.5 text-[10px] text-stone-500 dark:text-slate-400">
                    Menampilkan rincian pesanan pelanggan di meja saat ini.
                </p>
            </div>
        </div>

        <div v-if="selectedManagedOrder" class="border-stone-200 dark:border-slate-850 grid grid-cols-1 gap-4 rounded-xl border bg-stone-100/50 dark:bg-slate-955/60 p-4 text-xs md:grid-cols-2 xl:grid-cols-5">
            <div>
                <p class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500">Nomor Transaksi</p>
                <p class="mt-1 text-sm font-extrabold text-stone-850 dark:text-white">{{ selectedManagedOrder.order_number }}</p>
            </div>
            <div>
                <p class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500">Pelanggan</p>
                <p class="mt-1 font-semibold text-stone-850 dark:text-white">{{ getOrderCustomerPrimary(selectedManagedOrder) }}</p>
                <p v-if="getOrderCustomerSecondary(selectedManagedOrder)" class="mt-0.5 text-[10px] text-stone-500 dark:text-slate-400">
                    {{ getOrderCustomerSecondary(selectedManagedOrder) }}
                </p>
            </div>
            <div>
                <p class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500">Kasir Bertugas</p>
                <p class="mt-1 flex items-center gap-1.5 font-semibold text-stone-850 dark:text-white">
                    <User class="text-stone-500 dark:text-slate-455 h-3.5 w-3.5" />
                    <span>{{ selectedManagedOrder.cashier?.name || 'Kasir Restoran' }}</span>
                </p>
            </div>
            <div>
                <p class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500">Waktu Masuk</p>
                <p class="mt-1 flex items-center gap-1.5 font-semibold text-stone-850 dark:text-white">
                    <Clock class="text-stone-500 dark:text-slate-455 h-3.5 w-3.5" />
                    <span>{{ new Date(selectedManagedOrder.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }} WIB</span>
                </p>
            </div>
            <div>
                <p class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500">Status Bayar</p>
                <div class="mt-1 space-y-2">
                    <span :class="['inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider', getPaymentStatusClass(selectedManagedOrder)]">
                        {{ getPaymentStatusLabel(selectedManagedOrder) }}
                    </span>
                    <p class="text-[10px] text-stone-500 dark:text-slate-400">
                        {{ isOrderPaid(selectedManagedOrder) ? 'Tagihan sudah diterima.' : getPaymentActionHint(selectedManagedOrder) }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="selectedManagedOrder" class="space-y-3">
            <div v-if="tableActiveOrders.length > 1" class="custom-scrollbar flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="order in tableActiveOrders"
                    :key="`managed-order-${order.id}`"
                    @click="selectedManagedOrderId = order.id"
                    :class="[
                        'whitespace-nowrap rounded-xl border px-3 py-2 text-[11px] font-bold transition',
                        selectedManagedOrderId === order.id
                            ? 'border-orange-500/30 bg-orange-500/10 text-orange-600 dark:text-orange-305'
                            : 'border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950/70 text-stone-500 dark:text-slate-400 hover:text-stone-900 dark:hover:text-stone-800 dark:text-slate-200',
                    ]"
                >
                    {{ order.order_number }}
                </button>
            </div>

            <h4 class="text-xs font-bold uppercase tracking-wider text-stone-550 dark:text-slate-400">Daftar Item Makanan/Minuman</h4>
            <div class="overflow-hidden rounded-xl border border-stone-200 dark:border-slate-800/80 bg-stone-50 dark:bg-slate-950/20">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-stone-200 dark:border-slate-850 border-b bg-stone-100 dark:bg-slate-955/80 text-stone-550 dark:text-slate-400">
                            <th class="p-3">Nama Menu</th>
                            <th class="p-3 text-center">Jumlah</th>
                            <th class="p-3 text-right">Harga Satuan</th>
                            <th class="p-3 text-right">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-stone-200 dark:divide-slate-850 divide-y">
                        <tr v-for="item in selectedManagedOrder.items" :key="item.id" class="text-stone-850 dark:text-slate-200">
                            <td class="p-3">
                                <div class="font-bold">{{ item.product?.name }}</div>
                                <div v-if="item.variant" class="mt-0.5 text-[9px] font-semibold text-orange-600 dark:text-orange-400">
                                    Varian: {{ item.variant.name }}
                                </div>
                                <div v-if="item.notes" class="text-stone-500 dark:text-slate-450 mt-0.5 text-[9px] italic">
                                    "{{ item.notes }}"
                                </div>
                            </td>
                            <td class="p-3 text-center font-bold text-stone-900 dark:text-slate-100">
                                {{ item.quantity }}x
                            </td>
                            <td class="p-3 text-right">{{ formatPrice(item.unit_price) }}</td>
                            <td class="p-3 text-right font-extrabold text-stone-900 dark:text-white">
                                {{ formatPrice(item.total_price) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="selectedManagedOrder" class="flex flex-col justify-between gap-4 border-t border-stone-200 dark:border-slate-800/50 pt-5 text-xs md:flex-row">
            <div class="md:w-1/2">
                <p class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500">Catatan Khusus Pesanan</p>
                <p class="text-stone-600 dark:text-slate-355 border-stone-200 dark:border-slate-850 mt-1 rounded-lg border bg-stone-100/50 dark:bg-slate-950/50 p-3 italic">
                    {{ selectedManagedOrder.notes || 'Tidak ada catatan khusus.' }}
                </p>
            </div>
            <div class="border-stone-200 dark:border-slate-850 space-y-2 rounded-xl border bg-stone-50 dark:bg-slate-950/50 p-4 md:w-1/3">
                <div class="flex justify-between text-stone-500 dark:text-slate-400">
                    <span>Subtotal:</span>
                    <span>{{ formatPrice(selectedManagedOrder.subtotal) }}</span>
                </div>
                <div class="flex justify-between text-stone-500 dark:text-slate-400">
                    <span>Diskon:</span>
                    <span>{{ formatPrice(selectedManagedOrder.discount_amount) }}</span>
                </div>
                <div class="flex justify-between border-t border-stone-200 dark:border-slate-800 pt-2 text-sm font-black text-stone-900 dark:text-white">
                    <span>Total Tagihan:</span>
                    <span class="text-orange-600 dark:text-orange-405">{{ formatPrice(selectedManagedOrder.total_amount) }}</span>
                </div>
                <div class="flex justify-between border-t border-stone-200 dark:border-slate-800 pt-2 text-stone-500 dark:text-slate-400">
                    <span>Terbayar:</span>
                    <span>{{ formatPrice(selectedManagedOrder.paid_amount) }}</span>
                </div>
                <div v-if="getAppliedPromos(selectedManagedOrder).length" class="space-y-2 border-t border-stone-200 dark:border-slate-800 pt-2">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500">Promo Applied</p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="promo in getAppliedPromos(selectedManagedOrder)"
                            :key="promo.id"
                            class="rounded-full border border-orange-500/25 bg-orange-500/10 px-2 py-1 text-[10px] font-bold text-orange-600 dark:text-orange-200"
                        >
                            {{ promo.name }} • {{ formatPrice(promo.discount_amount) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="border-stone-200 dark:border-slate-850 space-y-2 rounded-xl border bg-stone-50 dark:bg-slate-950/50 p-4 md:w-1/3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500">Ringkasan Pembayaran</p>
                    <span :class="['rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider', getPaymentStatusClass(selectedManagedOrder)]">
                        {{ getPaymentStatusLabel(selectedManagedOrder) }}
                    </span>
                </div>
                <p class="text-[11px] text-stone-555 dark:text-slate-400">
                    {{ getPaymentMeta(selectedManagedOrder).method
                        ? `Metode terakhir: ${String(getPaymentMeta(selectedManagedOrder).method).toUpperCase()}`
                        : 'Belum ada metode pembayaran yang dipilih.' }}
                </p>
                <button
                    v-if="getPaymentMeta(selectedManagedOrder).checkout_url && !isOrderPaid(selectedManagedOrder)"
                    type="button"
                    @click="
                        activePaymentCheckout = {
                            payment_url: getPaymentMeta(selectedManagedOrder).checkout_url,
                            order_number: selectedManagedOrder.order_number,
                            amount: selectedManagedOrder.total_amount,
                            context: hasPendingBeforeKitchenPayment(selectedManagedOrder) ? 'before_kitchen' : 'after_service',
                            method: getPaymentMeta(selectedManagedOrder).method || 'qris',
                        };
                        paymentCheckoutModalOpen = true;
                    "
                    class="w-full rounded-xl border px-3 py-2 text-[11px] font-bold transition"
                    :class="getPaymentMethodConfig(getPaymentMeta(selectedManagedOrder).method || 'qris').buttonClass"
                >
                    Buka Checkout {{ getPaymentMethodConfig(getPaymentMeta(selectedManagedOrder).method || 'qris').label }} Aktif
                </button>
            </div>
        </div>
    </div>
</template>
