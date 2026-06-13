<script setup lang="ts">
import {
    activeOrders,
    activePaymentMethods,
    activeSubTab,
    cancelActiveOrder,
    canCloseAsKasbon,
    canEditOrderStatus,
    canMergeSelectedOrders,
    canOpenPaymentModal,
    canSplitOrderStatus,
    cart,
    cartDiscount,
    cartItemCount,
    cartSubtotal,
    cartTax,
    cartTotal,
    customerEmail,
    customerName,
    customerPhone,
    customerSearchQuery,
    decreaseCartQty,
    deliverOrder,
    filteredCustomers,
    formatPrice,
    getKasbonActionHint,
    getOrderCustomerPrimary,
    getOrderCustomerSecondary,
    getOrderServiceBadgeClass,
    getOrderServiceLabel,
    getPaymentActionHint,
    getPaymentActionLabel,
    getPaymentMethodConfig,
    getStatusClass,
    guestsCount,
    handleNewOrderPromoChange,
    increaseCartQty,
    isCustomNewOrderPromo,
    isDeliveringOrder,
    isForcedNewCustomer,
    isOnlineSelection,
    isOrderPaid,
    isPhoneLookup,
    isReadyTable,
    isSubmitting,
    isTakeawaySelection,
    mergeSelectionIds,
    newOrderApprovalPin,
    newOrderCashChange,
    newOrderCashReceived,
    newOrderPaymentMethod,
    newOrderPromoCode,
    newOrderPromoWarning,
    openEditOrder,
    openKasbonModal,
    openKasbonModalForOrder,
    openMergeBill,
    openPaymentModal,
    openPaymentModalForOrder,
    openSplitBill,
    orderNotes,
    outlet,
    paymentOption,
    promos,
    removeCartItem,
    resetCustomerSelection,
    selectCustomer,
    selectedCustomer,
    selectedManagedOrder,
    selectedNewOrderPromoCode,
    selectedTable,
    showNewCustomerForm,
    showNewOrderApprovalPin,
    submitOrder,
    tableActiveOrders,
    tableRemainingCapacity,
    toggleMergeSelection,
} from '@/Composables/useOrderState';
import {
    CookingPot,
    Eye,
    EyeOff,
    Minus,
    Plus,
    Receipt,
    ShoppingCart,
    Trash2,
} from '@lucide/vue';
import { watch } from 'vue';

watch(customerSearchQuery, (value) => {
    if (selectedCustomer.value) return;

    const trimmed = value.trim();
    if (!trimmed) return;

    if (isPhoneLookup(trimmed)) {
        if (!customerPhone.value) {
            customerPhone.value = trimmed;
        }
        return;
    }

    if (!customerName.value) {
        customerName.value = trimmed;
    }
});
</script>

<template>
    <!-- KONDISI TAB ORDER AKTIF -->
    <template v-if="activeSubTab === 'active_orders'">
        <!-- DETAIL & ACTIONS ORDER AKTIF YANG DIPILIH -->
        <div
            v-if="selectedManagedOrder"
            class="flex min-h-[500px] flex-col justify-between space-y-4 rounded-2xl border-2 border-stone-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-900"
        >
            <div class="space-y-4">
                <h3
                    class="flex items-center gap-2 border-b-2 border-stone-200 pb-3 text-sm font-black text-stone-900 dark:border-white/5 dark:text-white"
                >
                    <Receipt class="h-4.5 w-4.5 text-orange-600" />
                    <span>Detail Transaksi Aktif</span>
                </h3>

                <div
                    class="space-y-2 rounded-xl border-2 border-stone-200 bg-stone-50 p-3.5 text-xs dark:border-white/5 dark:bg-slate-950/60"
                >
                    <div class="flex justify-between">
                        <span class="font-bold text-stone-500 dark:text-slate-400"
                            >Order ID:</span
                        ><span
                            class="font-black text-stone-900 dark:text-white"
                            >{{ selectedManagedOrder.order_number }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold text-stone-500 dark:text-slate-400"
                            >Layanan:</span
                        ><span
                            class="font-black text-stone-900 dark:text-slate-200"
                            >{{
                                getOrderServiceLabel(selectedManagedOrder)
                            }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold text-stone-500 dark:text-slate-400"
                            >Meja:</span
                        ><span
                            class="font-black text-stone-900 dark:text-slate-200"
                            >{{
                                selectedManagedOrder.table?.name ?? 'Takeaway'
                            }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold text-stone-500 dark:text-slate-400"
                            >Customer:</span
                        ><span
                            class="font-black text-stone-900 dark:text-slate-200"
                            >{{
                                getOrderCustomerPrimary(selectedManagedOrder)
                            }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold text-stone-500 dark:text-slate-400"
                            >Status Bayar:</span
                        >
                        <span
                            :class="[
                                'font-black',
                                isOrderPaid(selectedManagedOrder)
                                    ? 'text-emerald-650 dark:text-emerald-400'
                                    : 'text-rose-650 dark:text-rose-400',
                            ]"
                        >
                            {{
                                isOrderPaid(selectedManagedOrder)
                                    ? 'Lunas'
                                    : 'Belum Lunas'
                            }}
                        </span>
                    </div>
                </div>

                <!-- Ringkasan Item Pesanan -->
                <div class="space-y-2">
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                    >
                        Rincian Menu
                    </p>
                    <div
                        class="custom-scrollbar max-h-[150px] space-y-1.5 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="item in selectedManagedOrder.items"
                            :key="item.id"
                            class="flex items-center justify-between rounded-lg border-2 border-stone-200 bg-white p-2.5 text-xs dark:border-white/5 dark:bg-slate-900"
                        >
                            <div class="min-w-0 flex-1 pr-2">
                                <p
                                    class="truncate font-black text-stone-900 dark:text-slate-200"
                                >
                                    {{ item.product?.name }}
                                </p>
                                <p
                                    v-if="item.variant"
                                    class="mt-0.5 text-[10px] font-bold text-orange-600 dark:text-orange-400"
                                >
                                    {{ item.variant.name }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span
                                    class="font-bold text-stone-500 dark:text-slate-400"
                                    >x{{ item.quantity }}</span
                                >
                                <span
                                    class="ml-2 font-black text-stone-900 dark:text-white"
                                    >{{ formatPrice(item.total_price) }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kalkulasi Harga -->
                <div
                    class="space-y-1.5 border-t-2 border-stone-200 pt-3 text-xs dark:border-white/5"
                >
                    <div
                        class="flex justify-between font-bold text-stone-600 dark:text-slate-300"
                    >
                        <span>Subtotal:</span
                        ><span class="font-black text-stone-900 dark:text-white">{{
                            formatPrice(selectedManagedOrder.subtotal)
                        }}</span>
                    </div>
                    <div
                        v-if="Number(selectedManagedOrder.discount_amount) > 0"
                        class="flex justify-between font-bold text-rose-650 dark:text-rose-455"
                    >
                        <span>Diskon:</span
                        ><span
                            class="font-black"
                            >-{{
                                formatPrice(
                                    selectedManagedOrder.discount_amount,
                                )
                            }}</span
                        >
                    </div>
                    <div
                        class="flex justify-between border-t-2 border-stone-200 pt-1 text-sm font-black text-stone-900 dark:border-white/5 dark:text-white"
                    >
                        <span>Total Tagihan:</span
                        ><span class="text-orange-600 dark:text-orange-400">{{
                            formatPrice(selectedManagedOrder.total_amount)
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Button Actions -->
            <div
                class="space-y-3 border-t-2 border-stone-200 pt-3 dark:border-white/5"
            >
                <!-- Tombol Bayar Tagihan -->
                <button
                    v-if="canOpenPaymentModal(selectedManagedOrder)"
                    @click="openPaymentModalForOrder(selectedManagedOrder)"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-orange-500 bg-orange-50 p-3 text-xs font-black text-orange-700 transition hover:bg-orange-100 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-400"
                >
                    <span>{{
                        getPaymentActionLabel(selectedManagedOrder)
                    }}</span>
                </button>

                <!-- Tombol Sajikan (Muncul jika status ready atau delivered) -->
                <button
                    v-if="
                        ['ready', 'delivered'].includes(
                            selectedManagedOrder.status,
                        )
                    "
                    @click="deliverOrder(selectedManagedOrder)"
                    :disabled="isDeliveringOrder"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-emerald-500 bg-emerald-50 p-3 text-xs font-black text-emerald-700 transition hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
                >
                    <span>{{
                        isOrderPaid(selectedManagedOrder)
                             ? 'Sajikan & Selesaikan'
                             : 'Sajikan Pesanan'
                    }}</span>
                </button>

                <!-- Opsi Tambahan (Split Bill & Batal) -->
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-if="canSplitOrderStatus(selectedManagedOrder.status)"
                        @click="openSplitBill"
                        class="rounded-xl border-2 border-fuchsia-200 bg-fuchsia-50 px-3 py-2.5 text-[11px] font-black text-fuchsia-700 transition hover:bg-fuchsia-100 dark:border-fuchsia-500/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-400"
                    >
                        Split Bill
                    </button>
                    <button
                        v-if="
                            !['completed', 'cancelled'].includes(
                                selectedManagedOrder.status,
                            )
                        "
                        @click="cancelActiveOrder(selectedManagedOrder)"
                        class="rounded-xl border-2 border-rose-200 bg-rose-50 px-3 py-2.5 text-[11px] font-black text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400"
                    >
                        Batalkan
                    </button>
                </div>
            </div>
        </div>

        <!-- Placeholder -->
        <div
            v-else
            class="flex min-h-[500px] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-stone-200 bg-stone-50 p-6 text-center text-stone-400 dark:border-white/10 dark:bg-slate-900/40 dark:text-slate-500"
        >
            <Receipt
                class="mb-4 h-12 w-12 text-stone-400 dark:text-slate-500"
            />
            <p class="text-sm font-black text-stone-900 dark:text-white">
                Pilih Order Aktif
            </p>
            <p class="mt-1 text-xs font-bold text-stone-500 dark:text-slate-400">
                Silakan pilih salah satu pesanan aktif di sebelah kiri untuk
                melihat detail transaksi dan memproses pembayaran.
            </p>
        </div>
    </template>

    <!-- KONDISI TAB TRANSAKSI BARU (LAYOUT LAMA) -->
    <template v-else>
        <!-- VIEW 1: Cart (when available table selected) -->
        <div
            v-if="selectedTable && isReadyTable(selectedTable)"
            class="flex min-h-[500px] flex-col rounded-2xl border-2 border-stone-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-900"
        >
            <h3
                class="mb-4 flex items-center gap-2 border-b-2 border-stone-200 pb-4 text-lg font-black text-black dark:border-white/10 dark:text-white"
            >
                <ShoppingCart class="h-5 w-5 text-orange-600 dark:text-orange-500" />
                <span>{{
                    isOnlineSelection
                        ? `Keranjang Online: ${selectedTable.name}`
                        : isTakeawaySelection
                          ? 'Keranjang Takeaway'
                          : `Keranjang Meja: ${selectedTable.name}`
                }}</span>
            </h3>

            <div
                class="mb-4 grid gap-3 rounded-xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/10 dark:bg-slate-950/40 sm:grid-cols-3"
            >
                <div
                    class="rounded-xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                >
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                    >
                        Tipe Order
                    </p>
                    <p
                        class="mt-2 text-sm font-black text-black dark:text-white"
                    >
                        {{
                            isOnlineSelection
                                ? 'Online'
                                : isTakeawaySelection
                                  ? 'Takeaway'
                                  : 'Dine In'
                        }}
                    </p>
                    <p
                        class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                    >
                        {{
                            isOnlineSelection
                                ? 'Pesanan ojek online / delivery'
                                : isTakeawaySelection
                                  ? 'Pesanan bungkus / pickup'
                                  : 'Pesanan makan di tempat'
                        }}
                    </p>
                </div>
                <div
                    class="rounded-xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                >
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                    >
                        {{ isOnlineSelection ? 'Platform' : 'Meja' }}
                    </p>
                    <p
                        class="mt-2 text-sm font-black text-black dark:text-white"
                    >
                        {{ selectedTable.name }}
                    </p>
                    <p
                        class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                    >
                        {{
                            isOnlineSelection
                                ? 'Order manual ojol'
                                : isTakeawaySelection
                                  ? 'Ambil di counter kasir'
                                  : 'Kirim ke meja terpilih'
                        }}
                    </p>
                </div>
                <div
                    class="rounded-xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                >
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                    >
                        Menu
                    </p>
                    <p
                        class="mt-2 text-sm font-black text-black dark:text-white"
                    >
                        {{ cartItemCount }} item
                    </p>
                    <p
                        class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                    >
                        {{ cart.length }} jenis menu aktif
                    </p>
                </div>
            </div>

            <div
                class="mb-4 space-y-3 rounded-xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/10 dark:bg-slate-950/40"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                        >
                            Pelanggan (WhatsApp)
                        </p>
                        <p
                            class="mt-1 text-xs font-bold text-black dark:text-slate-300"
                        >
                            Input nomor HP terlebih dulu, lalu pilih customer
                            atau daftar singkat jika belum ada.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-if="!selectedCustomer && !showNewCustomerForm"
                            type="button"
                            @click="isForcedNewCustomer = true"
                            class="rounded-lg border-2 border-orange-700 bg-orange-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-orange-950 transition hover:bg-orange-200 dark:border-orange-500 dark:bg-orange-950/80 dark:text-orange-100"
                        >
                            + Tambah Baru
                        </button>
                        <button
                            v-if="
                                selectedCustomer ||
                                customerSearchQuery ||
                                customerName ||
                                customerPhone ||
                                isForcedNewCustomer
                            "
                            type="button"
                            @click="resetCustomerSelection"
                            class="rounded-lg border-2 border-stone-200 bg-stone-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-black transition hover:bg-stone-200 dark:border-white/10 dark:bg-slate-800 dark:text-white"
                        >
                            Reset / Batal
                        </button>
                    </div>
                </div>

                <div class="relative">
                    <input
                        v-model="customerSearchQuery"
                        type="text"
                        placeholder="Cari pelanggan by nomor HP atau nama..."
                        class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-xs font-black text-black placeholder-stone-600 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-400"
                    />

                    <div
                        v-if="!selectedCustomer && filteredCustomers.length > 0"
                        class="absolute z-10 mt-2 w-full space-y-2 rounded-xl border-2 border-stone-200 bg-white p-2 shadow-2xl dark:border-white/10 dark:bg-slate-950"
                    >
                        <button
                            v-for="customer in filteredCustomers"
                            :key="customer.id"
                            type="button"
                            @click="selectCustomer(customer)"
                            class="flex w-full items-start justify-between rounded-lg border-2 border-stone-200 bg-white px-3 py-2 text-left transition hover:border-orange-600 hover:bg-orange-50 dark:border-white/10 dark:bg-slate-900 dark:hover:bg-slate-850"
                        >
                            <div>
                                <p
                                    class="text-xs font-black text-black dark:text-white"
                                >
                                    {{ customer.name || 'Pelanggan POS' }}
                                </p>
                                <p
                                    class="mt-0.5 text-[11px] font-bold text-black dark:text-slate-300"
                                >
                                    {{ customer.phone }}
                                </p>
                            </div>
                            <span
                                v-if="customer.membership?.tier?.name"
                                class="rounded-full border-2 border-emerald-700 bg-emerald-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-950 dark:border-emerald-500 dark:bg-emerald-950/85 dark:text-emerald-100"
                            >
                                {{ customer.membership.tier.name }}
                            </span>
                        </button>
                    </div>
                </div>

                <div
                    v-if="selectedCustomer"
                    class="rounded-xl border-2 border-emerald-700 bg-emerald-50 p-3 dark:bg-emerald-500/5"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p
                                class="text-xs font-black text-black dark:text-white"
                            >
                                {{ selectedCustomer.name || 'Pelanggan POS' }}
                            </p>
                            <p
                                class="mt-0.5 text-[11px] font-bold text-black dark:text-slate-300"
                            >
                                {{ selectedCustomer.phone }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p
                                v-if="selectedCustomer.membership?.tier?.name"
                                class="text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-300"
                            >
                                {{ selectedCustomer.membership.tier.name }}
                            </p>
                            <p
                                v-if="
                                    selectedCustomer.membership
                                        ?.total_points !== undefined
                                "
                                class="mt-0.5 text-[11px] font-bold text-black dark:text-slate-300"
                            >
                                {{ selectedCustomer.membership.total_points }}
                                poin
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="showNewCustomerForm"
                    class="grid gap-3 rounded-xl border-2 border-orange-700 bg-orange-50 p-3 dark:border-orange-500 dark:bg-orange-500/5"
                >
                    <p
                        class="text-[11px] font-black text-orange-950 dark:text-orange-200"
                    >
                        Customer belum ditemukan. Simpan sebagai pelanggan baru
                        dari transaksi ini.
                    </p>
                    <input
                        v-model="customerName"
                        type="text"
                        placeholder="Nama pelanggan"
                        class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-xs font-black text-black placeholder-stone-600 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-400"
                    />
                    <input
                        v-model="customerPhone"
                        type="text"
                        placeholder="Nomor HP pelanggan"
                        class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-xs font-black text-black placeholder-stone-600 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-400"
                    />
                    <input
                        v-model="customerEmail"
                        type="email"
                        placeholder="Email pelanggan (opsional)"
                        class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-xs font-black text-black placeholder-stone-600 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-400"
                    />
                </div>
            </div>

            <!-- Jumlah Tamu (Only for Dine In) -->
            <div
                v-if="!isTakeawaySelection && !isOnlineSelection"
                class="mb-4 space-y-3 rounded-xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/10 dark:bg-slate-950/40"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                        >
                            Jumlah Tamu
                        </p>
                        <p
                            class="mt-1 text-xs font-bold text-black dark:text-slate-300"
                        >
                            Masukkan jumlah orang dalam grup (Opsional).
                        </p>
                    </div>
                    <!-- Kapasitas info -->
                    <div
                        v-if="selectedTable.capacity"
                        class="rounded-full border-2 border-stone-200 bg-stone-200 px-2 py-0.5 text-[10px] font-black text-black dark:border-white/10 dark:bg-slate-800 dark:text-white"
                    >
                        Sisa Kapasitas:
                        {{
                            tableRemainingCapacity !== null
                                ? tableRemainingCapacity
                                : selectedTable.capacity
                        }}
                        Orang
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="guestsCount = Math.max(1, guestsCount - 1)"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border-2 border-stone-200 bg-white text-black font-black transition hover:bg-stone-100 dark:border-white/10 dark:bg-slate-900 dark:text-white"
                    >
                        <Minus class="h-4 w-4" />
                    </button>
                    <input
                        v-model.number="guestsCount"
                        type="number"
                        min="1"
                        :max="selectedTable.capacity || 100"
                        class="h-10 w-20 rounded-xl border-2 border-stone-200 bg-white text-center text-sm font-black text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                    />
                    <button
                        type="button"
                        @click="
                            guestsCount = Math.min(
                                selectedTable.capacity || 100,
                                guestsCount + 1,
                            )
                        "
                        class="flex h-10 w-10 items-center justify-center rounded-xl border-2 border-stone-200 bg-white text-black font-black transition hover:bg-stone-100 dark:border-white/10 dark:bg-slate-900 dark:text-white"
                    >
                        <Plus class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Cart items list -->
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                    >
                        Langkah 2 • Ringkasan Menu
                    </p>
                    <p class="mt-1 text-xs font-bold text-black dark:text-slate-300">
                        Cek item, jumlah, varian, dan catatan per menu.
                    </p>
                </div>
                <span
                    class="rounded-full border-2 border-stone-200 bg-stone-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-black dark:border-white/10 dark:bg-slate-900 dark:text-white"
                >
                    {{ cartItemCount }} item
                </span>
            </div>

            <div
                class="custom-scrollbar max-h-[300px] flex-1 space-y-3 overflow-y-auto pr-1"
            >
                <div
                    v-for="(item, index) in cart"
                    :key="index"
                    class="flex items-start justify-between gap-3 rounded-xl border-2 border-stone-200 bg-white p-3.5 text-xs dark:border-white/10 dark:bg-slate-900"
                >
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate font-black leading-snug text-black dark:text-white"
                        >
                            {{ item.product_name }}
                        </p>
                        <p
                            v-if="item.variant_name"
                            class="mt-0.5 text-[10px] font-black text-orange-600 dark:text-orange-400"
                        >
                            Varian: {{ item.variant_name }}
                        </p>
                        <p
                            v-if="item.notes"
                            class="mt-0.5 text-[9px] font-bold italic text-red-600 dark:text-red-400"
                        >
                            "{{ item.notes }}"
                        </p>
                        <p
                            class="mt-2 font-black text-black dark:text-slate-200"
                        >
                            {{ formatPrice(item.unit_price * item.quantity) }}
                        </p>
                    </div>

                    <!-- Control Quantity -->
                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            @click="decreaseCartQty(index)"
                            class="flex h-6 w-6 items-center justify-center rounded border-2 border-stone-200 bg-stone-100 text-black font-black hover:bg-stone-200 dark:border-white/10 dark:bg-slate-800 dark:text-white"
                        >
                            <Minus class="h-3 w-3" />
                        </button>
                        <span
                            class="w-5 text-center text-xs font-black text-black dark:text-slate-200"
                            >{{ item.quantity }}</span
                        >
                        <button
                            @click="increaseCartQty(index)"
                            class="flex h-6 w-6 items-center justify-center rounded border-2 border-stone-200 bg-stone-100 text-black font-black hover:bg-stone-200 dark:border-white/10 dark:bg-slate-800 dark:text-white"
                        >
                            <Plus class="h-3 w-3" />
                        </button>
                        <button
                            @click="removeCartItem(index)"
                            class="ml-1 flex h-6 w-6 items-center justify-center rounded border-2 border-red-700 bg-red-100 text-red-950 font-black hover:bg-red-200 dark:border-red-500 dark:bg-red-950/80 dark:text-red-100"
                        >
                            <Trash2 class="h-3 w-3" />
                        </button>
                    </div>
                </div>

                <div
                    v-if="cart.length === 0"
                    class="rounded-xl border-2 border-dashed border-stone-200 py-16 text-center text-xs font-bold text-black dark:border-white/10 dark:bg-slate-900 dark:text-slate-300"
                >
                    Keranjang belanja kosong.<br />Silakan pilih makanan di
                    panel kiri.
                </div>
            </div>

            <!-- Cart summary & notes -->
            <div
                class="mt-4 shrink-0 space-y-4 border-t-2 border-stone-200 pt-4 text-xs dark:border-white/10"
            >
                <div>
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                    >
                        Catatan & Ringkasan
                    </p>
                    <p class="mt-1 text-xs font-bold text-black dark:text-slate-300">
                        Tambahkan catatan dapur, lalu tentukan order dibayar
                        sekarang atau nanti.
                    </p>
                </div>
                <div>
                    <label
                        for="order-notes"
                        class="mb-1.5 block text-[9px] font-black uppercase tracking-wider text-black dark:text-slate-300"
                    >
                        Catatan Dapur (Order Notes)
                    </label>
                    <textarea
                        id="order-notes"
                        v-model="orderNotes"
                        :placeholder="
                            isOnlineSelection
                                ? 'Contoh: No order ojol #1234, driver atas nama Joko, sendok 2...'
                                : isTakeawaySelection
                                  ? 'Contoh: Sambal dipisah, sendok plastik 2, tanpa daun bawang...'
                                  : 'Contoh: Meja 1 minta saus mentai dipanggang lebih garing...'
                        "
                        class="h-16 w-full resize-none rounded-xl border-2 border-stone-200 bg-white p-3 text-xs font-black text-black placeholder-stone-650 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-400"
                    ></textarea>
                </div>

                <!-- Pricing Summary -->
                <div
                    class="space-y-2 rounded-xl border-2 border-stone-200 bg-stone-50/50 p-4 dark:border-white/10 dark:bg-slate-950/50"
                >
                    <div class="space-y-2 pb-2">
                        <label
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-black dark:text-slate-300"
                        >
                            Voucher / Promo Code
                        </label>
                        <select
                            v-model="selectedNewOrderPromoCode"
                            @change="handleNewOrderPromoChange"
                            class="w-full rounded-xl border-2 border-stone-200 bg-white px-3 py-2 text-xs font-black text-black outline-none transition focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                        >
                            <option
                                value=""
                                class="bg-white text-black dark:bg-slate-950 dark:text-slate-100"
                            >
                                Tidak Ada Voucher
                            </option>
                            <option
                                v-for="promo in promos"
                                :key="promo.id"
                                :value="promo.code"
                                class="bg-white text-black dark:bg-slate-950 dark:text-slate-100"
                            >
                                {{ promo.name }} ({{ promo.code }})
                            </option>
                            <option
                                value="custom"
                                class="bg-white text-black dark:bg-slate-950 dark:text-slate-100"
                            >
                                -- Ketik Kode Manual --
                            </option>
                        </select>

                        <input
                            v-if="isCustomNewOrderPromo"
                            v-model="newOrderPromoCode"
                            type="text"
                            placeholder="Ketik kode voucher manual..."
                            class="mt-2 w-full rounded-xl border-2 border-stone-200 bg-white px-3 py-2 text-xs uppercase font-black text-black placeholder-stone-650 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-400"
                        />
                        <p
                            v-if="newOrderPromoWarning"
                            class="mt-1.5 text-[11px] font-black leading-5 text-rose-600 dark:text-rose-400"
                        >
                            {{ newOrderPromoWarning }}
                        </p>
                        <p
                            class="text-[11px] leading-5 font-bold text-black dark:text-slate-400"
                        >
                            Promo otomatis, tier member, happy hour, dan voucher
                            diverifikasi server saat order disimpan.
                        </p>

                        <div class="pt-1">
                            <label
                                class="mb-1.5 block text-[9px] font-black uppercase tracking-wider text-black dark:text-slate-300"
                            >
                                PIN Owner (Opsional)
                            </label>
                            <div class="relative">
                                <input
                                    v-model="newOrderApprovalPin"
                                    :type="
                                        showNewOrderApprovalPin
                                            ? 'text'
                                            : 'password'
                                    "
                                    inputmode="numeric"
                                    placeholder="Isi jika diskon manual melewati threshold"
                                    class="w-full rounded-xl border-2 border-stone-200 bg-white py-2 pl-3 pr-10 text-xs font-black text-black placeholder-stone-650 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-400"
                                />
                                <button
                                    type="button"
                                    @click="
                                        showNewOrderApprovalPin =
                                            !showNewOrderApprovalPin
                                    "
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-black hover:text-stone-700 dark:text-slate-400 dark:hover:text-slate-200"
                                >
                                    <component
                                        :is="
                                            showNewOrderApprovalPin
                                                ? EyeOff
                                                : Eye
                                        "
                                        class="h-4 w-4"
                                    />
                                </button>
                            </div>
                            <p
                                class="mt-2 text-[11px] leading-5 font-bold text-black dark:text-slate-400"
                            >
                                Hanya diperlukan jika voucher atau diskon manual
                                memicu approval owner.
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex justify-between font-bold text-black dark:text-slate-300"
                    >
                        <span>Subtotal:</span>
                        <span class="font-black">{{ formatPrice(cartSubtotal) }}</span>
                    </div>
                    <div
                        v-if="cartDiscount > 0"
                        class="flex justify-between font-black text-emerald-700 dark:text-emerald-400"
                    >
                        <span>Diskon Voucher:</span>
                        <span>-{{ formatPrice(cartDiscount) }}</span>
                    </div>
                    <div
                        v-if="cartTax > 0"
                        class="flex justify-between font-bold text-black dark:text-slate-300"
                    >
                        <span>
                            Pajak ({{
                                outlet?.settings?.tax_percentage || 0
                            }}%):
                            <span
                                v-if="outlet?.settings?.tax_is_inclusive"
                                class="text-[10px] italic font-black opacity-70"
                                >(Inklusif)</span
                            >
                        </span>
                        <span class="font-black">{{ formatPrice(cartTax) }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t-2 border-stone-200 pt-2 text-sm font-black text-black dark:border-white/10 dark:text-white"
                    >
                        <span>Total Tagihan:</span>
                        <span
                            class="text-base font-black text-orange-700 dark:text-orange-400"
                            >{{ formatPrice(cartTotal) }}</span
                        >
                    </div>
                </div>

                <div
                    class="space-y-4 rounded-xl border-2 border-stone-200 bg-stone-50/50 p-4 dark:border-white/10 dark:bg-slate-950/50"
                >
                    <div>
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                        >
                            Opsi Pembayaran
                        </p>
                        <p
                            class="mt-1 text-xs font-bold text-black dark:text-slate-300"
                        >
                            Kasir manual bisa langsung lunas atau tetap buka
                            tagihan untuk dibayar nanti.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            @click="paymentOption = 'pay_later'"
                            :class="[
                                'rounded-xl border-2 p-3 text-left transition',
                                paymentOption === 'pay_later'
                                    ? 'border-emerald-700 bg-emerald-100 font-black text-emerald-955 dark:border-emerald-500 dark:bg-emerald-950/80 dark:text-emerald-100'
                                    : 'border-black bg-white text-black font-bold hover:border-emerald-700 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-700',
                            ]"
                        >
                            <p class="text-xs font-black">Bayar Nanti</p>
                            <p
                                class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                            >
                                Order langsung masuk dapur, pembayaran
                                diselesaikan saat closing transaksi.
                            </p>
                        </button>
                        <button
                            type="button"
                            @click="paymentOption = 'pay_now'"
                            :class="[
                                'rounded-xl border-2 p-3 text-left transition',
                                paymentOption === 'pay_now'
                                    ? 'border-orange-700 bg-orange-100 font-black text-orange-950 dark:border-orange-500 dark:bg-orange-950/80 dark:text-orange-100'
                                    : 'border-black bg-white text-black font-bold hover:border-orange-700 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-700',
                            ]"
                        >
                            <p class="text-xs font-black">Bayar Sekarang</p>
                            <p
                                class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                            >
                                Cocok untuk cash langsung atau QRIS sebelum
                                order lanjut ke operasional.
                            </p>
                        </button>
                    </div>

                    <div
                        v-if="paymentOption === 'pay_now'"
                        class="space-y-3 rounded-xl border-2 border-orange-700 bg-orange-50 p-4 dark:bg-orange-500/5"
                    >
                        <div class="grid gap-3 sm:grid-cols-2">
                            <!-- Opsi Sudah Dibayar via Platform (Hanya muncul jika isOnlineSelection aktif) -->
                            <button
                                v-if="isOnlineSelection"
                                type="button"
                                @click="
                                    newOrderPaymentMethod = 'online_platform'
                                "
                                :class="[
                                    'col-span-2 rounded-xl border-2 p-3 text-left transition',
                                    newOrderPaymentMethod === 'online_platform'
                                        ? 'border-blue-750 bg-blue-100 font-black text-blue-950 dark:border-blue-500 dark:bg-blue-950/80 dark:text-blue-100'
                                        : 'border-black bg-white text-black font-bold dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-300',
                                ]"
                            >
                                <p
                                    class="flex items-center gap-1.5 text-xs font-black"
                                >
                                    <span>Sudah Dibayar (Platform)</span>
                                    <span
                                        class="rounded bg-blue-600 px-1 py-0.5 text-[8px] text-white font-black"
                                        >Rekomendasi</span
                                    >
                                </p>
                                <p
                                    class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                                >
                                    Uang sudah diterima oleh platform ojol
                                    (GoFood/GrabFood/ShopeeFood/Maxim).
                                </p>
                            </button>
                            <button
                                type="button"
                                @click="newOrderPaymentMethod = 'cash'"
                                :class="[
                                    'rounded-xl border-2 p-3 text-left transition',
                                    newOrderPaymentMethod === 'cash'
                                        ? 'border-orange-700 bg-orange-100 font-black text-orange-950 dark:border-orange-500 dark:bg-orange-950/80 dark:text-orange-100'
                                        : 'border-black bg-white text-black font-bold dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-300',
                                ]"
                            >
                                <p class="text-xs font-black">Cash</p>
                                <p
                                    class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                                >
                                    Kasir input nominal diterima, lalu order
                                    langsung lanjut ke dapur.
                                </p>
                            </button>
                            <button
                                v-for="method in activePaymentMethods"
                                :key="method"
                                type="button"
                                @click="newOrderPaymentMethod = method as any"
                                :class="[
                                    'rounded-xl border-2 p-3 text-left transition',
                                    newOrderPaymentMethod === method
                                        ? 'border-violet-755 bg-violet-100 font-black text-violet-955'
                                        : 'border-black bg-white text-black font-bold dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-300',
                                ]"
                            >
                                <p class="text-xs font-black">
                                    {{ getPaymentMethodConfig(method).label }}
                                </p>
                                <p
                                    class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                                >
                                    {{ getPaymentMethodConfig(method).desc }}
                                </p>
                            </button>
                        </div>

                        <div
                            v-if="newOrderPaymentMethod === 'cash'"
                            class="grid gap-3 sm:grid-cols-2"
                        >
                            <div>
                                <label
                                    class="mb-1.5 block text-[9px] font-black uppercase tracking-wider text-black dark:text-slate-300"
                                >
                                    Nominal Diterima
                                </label>
                                <input
                                    v-model="newOrderCashReceived"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    placeholder="Contoh: 100000"
                                    class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-xs font-black text-black placeholder-stone-650 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-400"
                                />
                            </div>
                            <div
                                class="rounded-xl border-2 border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950"
                            >
                                <p
                                    class="text-[9px] font-black uppercase tracking-wider text-black dark:text-slate-300"
                                >
                                    Estimasi Kembalian
                                </p>
                                <p
                                    class="mt-2 text-sm font-black text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ formatPrice(newOrderCashChange) }}
                                </p>
                                <p
                                    class="mt-1 text-[11px] font-bold text-black dark:text-slate-300"
                                >
                                    Jika nominal kosong, sistem anggap pas
                                    sesuai total.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Action Buttons -->
                <button
                    @click="submitOrder"
                    :disabled="cart.length === 0 || isSubmitting"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-stone-200 bg-orange-500 hover:bg-orange-400 px-5 py-3 font-black text-stone-950 shadow-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500/50 active:scale-[0.99] disabled:pointer-events-none disabled:opacity-50"
                >
                    <svg
                        v-if="isSubmitting"
                        class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span>{{
                        isSubmitting
                            ? 'Mengirim Order...'
                            : paymentOption === 'pay_later'
                              ? 'Kirim Order ke Dapur (Bayar Nanti)'
                              : newOrderPaymentMethod === 'online_platform'
                                ? 'Proses Order (Sudah Dibayar)'
                                : newOrderPaymentMethod === 'cash'
                                  ? 'Bayar Tunai & Kirim ke Dapur'
                                  : 'Buat Checkout ' +
                                    (newOrderPaymentMethod
                                        ? newOrderPaymentMethod.toUpperCase()
                                        : 'QRIS')
                    }}</span>
                </button>
            </div>
        </div>

        <!-- VIEW 2: Actions & tracking for occupied table -->
        <div
            v-else-if="selectedTable && selectedTable.status === 'occupied'"
            class="space-y-4 rounded-2xl border-2 border-stone-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-900"
        >
            <h3
                class="flex items-center gap-2 border-b-2 border-stone-200 pb-4 text-lg font-black text-black dark:border-white/10 dark:text-white"
            >
                <Receipt class="h-5 w-5 text-orange-600 dark:text-orange-500" />
                <span>Manajemen Transaksi</span>
            </h3>

            <p
                class="text-xs font-bold leading-relaxed text-black dark:text-slate-300"
            >
                Meja ini sedang digunakan. Anda dapat melakukan pembayaran
                kasir, edit pesanan, atau pembagian tagihan.
            </p>

            <div
                v-if="selectedManagedOrder"
                class="rounded-xl border-2 border-stone-200 bg-stone-50/50 p-3.5 dark:border-white/10 dark:bg-slate-950/60"
            >
                <p
                    class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                >
                    Pesanan/Tagihan Aktif
                </p>
                <div
                    class="mt-2 flex items-center justify-between gap-3 text-xs"
                >
                    <div>
                        <p class="font-black text-black dark:text-white">
                            {{ selectedManagedOrder.order_number }}
                        </p>
                        <p class="mt-0.5 font-bold text-black dark:text-slate-300">
                            {{ getOrderCustomerPrimary(selectedManagedOrder) }}
                        </p>
                    </div>
                    <span
                        class="rounded-full border-2 border-stone-200 bg-stone-100 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-black dark:border-white/10 dark:bg-slate-900 dark:text-white"
                    >
                        {{ tableActiveOrders.length }} bill aktif
                    </span>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="space-y-3 pt-2">
                <div class="group relative">
                    <button
                        @click="openPaymentModal"
                        :disabled="!canOpenPaymentModal(selectedManagedOrder)"
                        class="flex w-full items-center justify-between rounded-xl border-2 border-orange-700 bg-orange-100 p-3.5 text-xs font-black text-orange-950 transition duration-150 hover:bg-orange-200 disabled:cursor-not-allowed disabled:bg-stone-100 disabled:text-stone-400 disabled:border-stone-300 disabled:opacity-70 dark:border-orange-500 dark:bg-orange-950/80 dark:text-orange-100 dark:disabled:bg-slate-900 dark:disabled:text-slate-600 dark:disabled:text-slate-500 dark:disabled:border-slate-800"
                    >
                        <span>{{
                            getPaymentActionLabel(selectedManagedOrder)
                        }}</span>
                    </button>
                    <p
                        class="mt-1 px-1 text-[10px] font-bold text-black dark:text-slate-400"
                    >
                        {{
                            canOpenPaymentModal(selectedManagedOrder)
                                ? getPaymentActionHint(selectedManagedOrder)
                                : 'Order ini belum ada di tahap settlement atau sudah lunas.'
                        }}
                    </p>
                </div>

                <!-- Tombol Sajikan (Muncul jika status ready atau delivered) -->
                <div
                    v-if="
                        selectedManagedOrder &&
                        ['ready', 'delivered'].includes(
                            selectedManagedOrder.status,
                        )
                    "
                    class="group relative"
                >
                    <button
                        @click="deliverOrder(selectedManagedOrder)"
                        :disabled="isDeliveringOrder"
                        class="flex w-full items-center justify-between rounded-xl border-2 border-emerald-700 bg-emerald-100 p-3.5 text-xs font-black text-emerald-900 transition duration-150 hover:bg-emerald-200 disabled:cursor-not-allowed disabled:bg-stone-100 disabled:text-stone-400 disabled:border-stone-300 disabled:opacity-70 dark:border-emerald-500 dark:bg-emerald-950/80 dark:text-emerald-100 dark:disabled:bg-slate-900 dark:disabled:text-slate-500 dark:disabled:border-slate-800"
                    >
                        <span>{{
                            isOrderPaid(selectedManagedOrder)
                                ? 'Sajikan & Selesaikan'
                                : 'Sajikan Pesanan'
                        }}</span>
                    </button>
                    <p
                        class="mt-1 px-1 text-[10px] font-bold text-black dark:text-slate-400"
                    >
                        {{
                            isOrderPaid(selectedManagedOrder)
                                ? 'Pesanan sudah lunas. Sajikan hidangan dan selesaikan transaksi (kosongkan meja).'
                                : 'Sajikan hidangan ke meja customer sebelum pembayaran diselesaikan.'
                        }}
                    </p>
                </div>

                <div class="group relative">
                    <button
                        @click="openKasbonModal"
                        :disabled="!canCloseAsKasbon(selectedManagedOrder)"
                        class="flex w-full items-center justify-between rounded-xl border-2 border-amber-700 bg-amber-100 p-3.5 text-xs font-black text-amber-900 transition duration-150 hover:bg-amber-200 disabled:cursor-not-allowed disabled:bg-stone-100 disabled:text-stone-400 disabled:border-stone-300 disabled:opacity-70 dark:border-amber-500 dark:bg-amber-950/80 dark:text-amber-100 dark:disabled:bg-slate-900 dark:disabled:text-slate-500 dark:disabled:border-slate-800"
                    >
                        <span>Tutup Sebagai Kasbon</span>
                    </button>
                    <p
                        class="mt-1 px-1 text-[10px] font-bold text-black dark:text-slate-400"
                    >
                        {{ getKasbonActionHint(selectedManagedOrder) }}
                    </p>
                </div>

                <div class="group relative">
                    <button
                        @click="openEditOrder"
                        class="flex w-full items-center justify-between rounded-xl border-2 border-sky-700 bg-sky-100 p-3.5 text-xs font-black text-sky-900 transition duration-150 hover:bg-sky-200 dark:border-sky-500 dark:bg-sky-950/80 dark:text-sky-100"
                    >
                        <span>Edit Order (Approval Flow)</span>
                    </button>
                    <p
                        class="mt-1 px-1 text-[10px] font-bold text-black dark:text-slate-400"
                    >
                        Status `pending` bisa diedit langsung. Status
                        `in_progress` butuh PIN approval supervisor.
                    </p>
                </div>

                <div class="group relative">
                    <button
                        @click="openSplitBill"
                        class="flex w-full items-center justify-between rounded-xl border-2 border-fuchsia-700 bg-fuchsia-100 p-3.5 text-xs font-black text-fuchsia-900 transition duration-150 hover:bg-fuchsia-200 dark:border-fuchsia-500 dark:bg-fuchsia-950/80 dark:text-fuchsia-100"
                    >
                        <span>Split Bill</span>
                    </button>
                    <p
                        class="mt-1 px-1 text-[10px] font-bold text-black dark:text-slate-400"
                    >
                        Pisahkan item tertentu ke bill kedua dari order aktif
                        yang dipilih.
                    </p>
                </div>

                <div class="group relative">
                    <button
                        @click="cancelActiveOrder(selectedManagedOrder)"
                        class="flex w-full items-center justify-between rounded-xl border-2 border-rose-700 bg-rose-100 p-3.5 text-xs font-black text-rose-900 transition duration-150 hover:bg-rose-200 dark:border-rose-500 dark:bg-rose-950/80 dark:text-rose-100"
                    >
                        <span>Batalkan Order</span>
                    </button>
                    <p
                        class="mt-1 px-1 text-[10px] font-bold text-black dark:text-slate-400"
                    >
                        Batalkan seluruh pesanan ini secara permanen dari
                        sistem.
                    </p>
                </div>
            </div>
        </div>

        <!-- VIEW 3: List of Active Orders (when no table selected) -->
        <div
            v-else
            class="flex min-h-[500px] flex-col rounded-2xl border-2 border-stone-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-900"
        >
            <h3
                class="mb-4 flex items-center gap-2 border-b-2 border-stone-200 pb-4 text-lg font-black text-black dark:border-white/10 dark:text-white"
            >
                <CookingPot class="h-5 w-5 text-orange-600 dark:text-orange-500" />
                <span>Daftar Order Aktif</span>
            </h3>

            <div
                class="mb-4 rounded-xl border-2 border-stone-200 bg-stone-50/50 p-3.5 dark:border-white/10 dark:bg-slate-950/50"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400"
                        >
                            Gabung Bill
                        </p>
                        <p
                            class="mt-1 text-xs font-bold text-black dark:text-slate-300"
                        >
                            Pilih minimal dua order aktif dari meja yang sama,
                            lalu gabungkan jadi satu bill.
                        </p>
                    </div>
                    <button
                        @click="openMergeBill"
                        :disabled="!canMergeSelectedOrders"
                        class="rounded-xl border-2 border-fuchsia-700 bg-fuchsia-100 px-3 py-2.5 text-[11px] font-black uppercase tracking-wider text-fuchsia-955 transition disabled:pointer-events-none disabled:opacity-40 dark:border-fuchsia-500 dark:bg-fuchsia-950/80 dark:text-fuchsia-100"
                    >
                        Gabung Bill
                    </button>
                </div>
            </div>

            <!-- Order list -->
            <div
                class="custom-scrollbar max-h-[420px] flex-1 space-y-3 overflow-y-auto pr-1"
            >
                <div
                    v-for="order in activeOrders"
                    :key="order.id"
                    class="space-y-3 rounded-xl border-2 border-stone-200 bg-white p-4 transition duration-150 hover:border-orange-600 dark:border-white/10 dark:bg-slate-950/60 dark:hover:border-orange-400"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <input
                                    v-if="order.table?.id"
                                    :checked="
                                        mergeSelectionIds.includes(order.id)
                                    "
                                    :disabled="
                                        !canEditOrderStatus(order.status)
                                    "
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-2 border-stone-200 bg-white text-fuchsia-600 focus:ring-fuchsia-500 dark:border-white/10 dark:bg-slate-950"
                                    @change="toggleMergeSelection(order.id)"
                                />
                                <span
                                    class="text-xs font-black text-black dark:text-white"
                                    >{{ order.order_number }}</span
                                >
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="[
                                        'rounded border-2 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider',
                                        getStatusClass(order.status),
                                    ]"
                                >
                                    {{ order.status }}
                                </span>
                                <span
                                    :class="[
                                        'rounded border-2 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider',
                                        getOrderServiceBadgeClass(order),
                                    ]"
                                >
                                    {{ getOrderServiceLabel(order) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p
                                class="text-[9px] font-bold uppercase tracking-wider text-black dark:text-slate-300"
                            >
                                Total Bill
                            </p>
                            <p
                                class="mt-1 font-black text-orange-700 dark:text-orange-400"
                            >
                                {{ formatPrice(order.total_amount) }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-2 text-[11px] text-black dark:text-slate-300 sm:grid-cols-2"
                    >
                        <div>
                            <p
                                class="text-[9px] font-bold uppercase text-black dark:text-slate-400"
                            >
                                Layanan
                            </p>
                            <p
                                class="mt-0.5 font-black text-black dark:text-white"
                            >
                                {{ getOrderServiceLabel(order) }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[9px] font-bold uppercase text-black dark:text-slate-400"
                            >
                                Pelanggan
                            </p>
                            <p
                                class="mt-0.5 font-black text-black dark:text-white"
                            >
                                {{ getOrderCustomerPrimary(order) }}
                            </p>
                            <p
                                v-if="getOrderCustomerSecondary(order)"
                                class="mt-0.5 font-bold text-black dark:text-slate-300"
                            >
                                {{ getOrderCustomerSecondary(order) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="canOpenPaymentModal(order)"
                            type="button"
                            @click="openPaymentModalForOrder(order)"
                            class="rounded-xl border-2 border-orange-700 bg-orange-100 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-orange-955 transition hover:bg-orange-200 dark:border-orange-500 dark:bg-orange-500/10 dark:text-orange-300 dark:hover:bg-orange-500/15"
                        >
                            {{ getPaymentActionLabel(order) }}
                        </button>
                        <button
                            v-if="['ready', 'delivered'].includes(order.status)"
                            type="button"
                            @click="deliverOrder(order)"
                            :disabled="isDeliveringOrder"
                            class="rounded-xl border-2 border-emerald-700 bg-emerald-100 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-emerald-955 transition hover:bg-emerald-200 disabled:opacity-50 dark:border-emerald-500 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20"
                        >
                            {{
                                isOrderPaid(order)
                                    ? 'Sajikan & Selesaikan'
                                    : 'Sajikan'
                            }}
                        </button>

                        <button
                            v-if="canCloseAsKasbon(order)"
                            type="button"
                            @click="openKasbonModalForOrder(order)"
                            class="rounded-xl border-2 border-amber-700 bg-amber-100 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-amber-955 transition hover:bg-amber-200 dark:border-amber-500 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/15"
                        >
                            Tutup Kasbon
                        </button>
                        <span
                            v-else-if="
                                !canCloseAsKasbon(order) && !isOrderPaid(order)
                            "
                            class="rounded-xl border-2 border-stone-200 bg-stone-100 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-black dark:border-white/10 dark:bg-slate-900 dark:text-slate-400"
                        >
                            {{ getKasbonActionHint(order) }}
                        </span>
                    </div>

                    <!-- Footer detail items counter -->
                    <div
                        class="flex items-center justify-between border-t-2 border-stone-200 pt-2 text-[10px] text-black dark:border-white/10 dark:text-slate-400"
                    >
                        <span class="font-bold"
                            >{{ order.items?.length || 0 }} Jenis Menu
                            Pesanan</span
                        >
                        <span class="font-bold"
                            >{{
                                new Date(order.created_at).toLocaleTimeString(
                                    'id-ID',
                                    { hour: '2-digit', minute: '2-digit' },
                                )
                            }}
                            WIB</span
                        >
                    </div>
                </div>

                <div
                    v-if="activeOrders.length === 0"
                    class="rounded-xl border-2 border-dashed border-stone-200 py-20 text-center text-xs font-black text-black dark:border-white/10 dark:bg-slate-900 dark:text-slate-300"
                >
                    Belum ada transaksi aktif saat ini.<br />Gunakan panel peta
                    meja untuk membuat baru.
                </div>
            </div>
        </div>
    </template>
</template>
