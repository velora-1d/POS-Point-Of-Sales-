<script setup lang="ts">
import { X, Search, Trash2, Minus, Plus, Eye, EyeOff } from '@lucide/vue';
import {
    editOrderModalOpen,
    editingOrder,
    editCategory,
    editProductSearchQuery,
    filteredEditProducts,
    editingItems,
    editItemCount,
    editSubtotal,
    editOrderNotes,
    editOrderNeedsApproval,
    editApprovalPin,
    showEditApprovalPin,
    isUpdatingOrder,
    closeEditOrder,
    handleEditProductClick,
    removeEditItem,
    decreaseEditQty,
    increaseEditQty,
    submitEditOrder,
    categories,
    getOrderServiceLabel,
    getOrderCustomerPrimary,
    formatPrice,
    getStatusClass,
} from '@/Composables/useOrderState';
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="editOrderModalOpen"
            class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-stone-900/40 dark:bg-slate-955/85 p-4 backdrop-blur-sm"
        >
            <div class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-200 dark:border-slate-800/80 px-6 py-5">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-sky-600 dark:text-sky-305">
                                Edit Order
                            </span>
                            <span :class="['rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em]', getStatusClass(editingOrder?.status || '')]">
                                {{ editingOrder?.status }}
                            </span>
                        </div>
                        <h3 class="mt-3 text-xl font-black text-stone-900 dark:text-white">
                            Edit Order {{ editingOrder?.order_number }}
                        </h3>
                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                            Ubah item aktif, lalu sistem akan reset status order kembali ke `pending` sesuai brief.
                        </p>
                    </div>
                    <button
                        @click="closeEditOrder"
                        class="text-stone-400 dark:text-slate-500 transition hover:text-stone-600 dark:hover:text-slate-200"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="grid min-h-0 flex-1 gap-0 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="flex min-h-0 flex-col border-r border-stone-200 dark:border-slate-800/70">
                        <div class="grid gap-3 border-b border-stone-200 dark:border-slate-800/70 px-6 py-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-50/50 dark:bg-slate-950/60 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-400">Tipe Order</p>
                                <p class="mt-2 text-sm font-extrabold text-stone-900 dark:text-white">
                                    {{ getOrderServiceLabel(editingOrder) }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-50/50 dark:bg-slate-950/60 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-400">Pelanggan</p>
                                <p class="mt-2 text-sm font-extrabold text-stone-900 dark:text-white">
                                    {{ getOrderCustomerPrimary(editingOrder) }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-50/50 dark:bg-slate-955/60 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-400">Jumlah Item</p>
                                <p class="mt-2 text-sm font-extrabold text-stone-900 dark:text-white">{{ editItemCount }} item</p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-slate-400">{{ editingItems.length }} jenis menu</p>
                            </div>
                        </div>

                        <div class="border-b border-stone-200 dark:border-slate-800/70 px-6 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-400">Tambah Menu</p>
                                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                        Pilih kategori, cari menu, lalu tambahkan ke draft edit.
                                    </p>
                                </div>
                                <div class="relative w-full max-w-xs">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-stone-400 dark:text-slate-500">
                                        <Search class="h-4 w-4" />
                                    </span>
                                    <input
                                        v-model="editProductSearchQuery"
                                        type="text"
                                        placeholder="Cari menu edit..."
                                        class="border-stone-200 dark:border-slate-855 w-full rounded-xl border bg-stone-55 dark:bg-slate-950 py-2 pl-9 pr-4 text-xs text-stone-855 dark:text-slate-200 placeholder-stone-400 dark:placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                    />
                                </div>
                            </div>

                            <div class="custom-scrollbar mt-3 flex gap-2 overflow-x-auto pb-1">
                                <button
                                    @click="editCategory = 'all'"
                                    :class="[
                                        'whitespace-nowrap rounded-xl border px-3 py-2 text-[11px] font-semibold transition',
                                        editCategory === 'all'
                                            ? 'border-orange-500/30 bg-orange-500/10 text-orange-655 dark:text-orange-405'
                                            : 'border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-955/60 text-stone-600 dark:text-slate-400 hover:text-stone-900 dark:hover:text-slate-200',
                                    ]"
                                >
                                    Semua
                                </button>
                                <button
                                    v-for="cat in categories"
                                    :key="`edit-${cat.id}`"
                                    @click="editCategory = cat.id"
                                    :class="[
                                        'whitespace-nowrap rounded-xl border px-3 py-2 text-[11px] font-semibold transition',
                                        editCategory === cat.id
                                            ? 'border-orange-500/30 bg-orange-500/10 text-orange-655 dark:text-orange-405'
                                            : 'border-stone-200 dark:border-slate-800 bg-stone-55 dark:bg-slate-955/60 text-stone-600 dark:text-slate-400 hover:text-stone-900 dark:hover:text-slate-200',
                                    ]"
                                >
                                    {{ cat.name }}
                                </button>
                            </div>

                            <div class="custom-scrollbar mt-4 max-h-56 overflow-y-auto pr-1">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    <button
                                        v-for="product in filteredEditProducts"
                                        :key="`edit-product-${product.id}`"
                                        type="button"
                                        @click="handleEditProductClick(product)"
                                        class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-55 dark:bg-slate-955/60 p-3 text-left transition hover:border-orange-500/30 hover:bg-white dark:hover:bg-slate-950"
                                    >
                                        <p class="text-xs font-bold text-stone-900 dark:text-white">{{ product.name }}</p>
                                        <p class="mt-1 line-clamp-2 text-[10px] text-stone-505">{{ product.description || 'Tidak ada deskripsi.' }}</p>
                                        <div class="mt-3 flex items-center justify-between">
                                            <span class="text-[11px] font-extrabold text-orange-600 dark:text-orange-400">
                                                {{ formatPrice(product.base_price) }}
                                            </span>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400">Tambah</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="custom-scrollbar min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-400">Draft Edit Menu</p>
                                    <p class="mt-1 text-xs text-slate-455 dark:text-slate-400">
                                        Ubah jumlah, hapus item, atau revisi catatan per menu.
                                    </p>
                                </div>
                                <span class="rounded-full border border-stone-250 dark:border-slate-700 bg-stone-100 dark:bg-slate-955 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-stone-600 dark:text-slate-300">
                                    {{ editItemCount }} item
                                </span>
                            </div>

                            <div
                                v-for="(item, index) in editingItems"
                                :key="`editing-${index}-${item.product_id}-${item.variant_id || 'base'}`"
                                class="space-y-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-55 dark:bg-slate-955/60 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-stone-900 dark:text-white">{{ item.product_name }}</p>
                                        <p v-if="item.variant_name" class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-orange-600 dark:text-orange-300">
                                            {{ item.variant_name }}
                                        </p>
                                        <p class="mt-2 text-[11px] font-extrabold text-stone-600 dark:text-slate-300">
                                            {{ formatPrice(item.unit_price * item.quantity) }}
                                        </p>
                                    </div>
                                    <button
                                        @click="removeEditItem(index)"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-red-200 dark:border-red-900/30 bg-red-50 dark:bg-red-950/20 text-red-655 dark:text-red-450 transition hover:text-red-700 dark:hover:text-red-300"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        @click="decreaseEditQty(index)"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-905 text-stone-600 dark:text-slate-300"
                                    >
                                        <Minus class="h-3 w-3" />
                                    </button>
                                    <span class="w-7 text-center text-sm font-bold text-stone-900 dark:text-white">{{ item.quantity }}</span>
                                    <button
                                        @click="increaseEditQty(index)"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-905 text-stone-600 dark:text-slate-300"
                                    >
                                        <Plus class="h-3 w-3" />
                                    </button>
                                </div>

                                <input
                                    v-model="item.notes"
                                    type="text"
                                    placeholder="Catatan item, misal saus dipisah..."
                                    class="border-stone-200 dark:border-slate-855 w-full rounded-xl border bg-stone-55 dark:bg-slate-905 px-4 py-2.5 text-xs text-stone-855 dark:text-slate-200 placeholder-stone-400 dark:placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                />
                            </div>

                            <div v-if="editingItems.length === 0" class="rounded-2xl border border-dashed border-stone-200 dark:border-slate-800 py-12 text-center text-xs text-stone-400 dark:text-slate-500">
                                Draft edit kosong. Tambahkan minimal satu menu.
                            </div>
                        </div>
                    </div>

                    <div class="custom-scrollbar flex min-h-0 flex-col overflow-y-auto px-6 py-5">
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50/50 dark:bg-slate-950/60 p-4">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-400">Informasi Dapur</p>
                                <p class="mt-1 text-xs text-stone-555 dark:text-slate-400">
                                    Setelah disimpan, order akan kembali ke lane `pending` di kitchen.
                                </p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400">
                                    Catatan Order
                                </label>
                                <textarea
                                    v-model="editOrderNotes"
                                    rows="5"
                                    class="border-stone-200 dark:border-slate-855 w-full resize-none rounded-2xl border bg-stone-55 dark:bg-slate-950 px-4 py-3 text-xs text-stone-855 dark:text-slate-200 placeholder-stone-400 dark:placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                    placeholder="Update catatan utama order untuk dapur..."
                                ></textarea>
                            </div>

                            <div v-if="editOrderNeedsApproval" class="rounded-2xl border border-amber-250 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-500/5 p-4">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-455">Persetujuan Supervisor</p>
                                <p class="mt-1 text-xs leading-relaxed text-amber-900/80 dark:text-amber-100/75">
                                    Order ini sudah `in_progress`. Input PIN supervisor atau owner untuk melanjutkan edit.
                                </p>
                                <div class="relative mt-3">
                                    <input
                                        v-model="editApprovalPin"
                                        :type="showEditApprovalPin ? 'text' : 'password'"
                                        inputmode="numeric"
                                        placeholder="Masukkan PIN approval"
                                        class="w-full rounded-xl border border-amber-250 dark:border-amber-500/20 bg-stone-55 dark:bg-slate-950 pl-4 pr-12 py-3 text-xs text-stone-855 dark:text-slate-100 placeholder-stone-400 dark:placeholder-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                                    />
                                    <button
                                        type="button"
                                        @click="showEditApprovalPin = !showEditApprovalPin"
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-400 dark:text-slate-500 hover:text-stone-600 dark:hover:text-slate-350"
                                    >
                                        <component :is="showEditApprovalPin ? EyeOff : Eye" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50/50 dark:bg-slate-950/60 p-4">
                                <div class="flex items-center justify-between text-xs text-stone-500 dark:text-slate-400">
                                    <span>Total Item</span>
                                    <span>{{ editItemCount }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs text-stone-500 dark:text-slate-400">
                                    <span>Jenis Menu</span>
                                    <span>{{ editingItems.length }}</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between border-t border-stone-200 dark:border-slate-800 pt-3 text-sm font-black text-stone-900 dark:text-white">
                                    <span>Total Order Baru</span>
                                    <span class="text-orange-600 dark:text-orange-400 font-extrabold">{{ formatPrice(editSubtotal) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3">
                            <button
                                @click="submitEditOrder"
                                :disabled="editingItems.length === 0 || isUpdatingOrder"
                                class="w-full rounded-2xl bg-gradient-to-r from-sky-500 to-cyan-500 px-5 py-3 text-xs font-bold text-slate-950 transition hover:from-sky-400 hover:to-cyan-400 disabled:pointer-events-none disabled:opacity-50"
                            >
                                {{ isUpdatingOrder ? 'Menyimpan Edit...' : 'Simpan Perubahan Order' }}
                            </button>
                            <button
                                @click="closeEditOrder"
                                type="button"
                                class="w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950 px-5 py-3 text-xs font-bold text-stone-700 dark:text-slate-300 transition hover:bg-stone-50 dark:hover:bg-slate-900"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
