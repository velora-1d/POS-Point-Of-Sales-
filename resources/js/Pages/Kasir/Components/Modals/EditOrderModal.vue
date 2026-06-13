<script setup lang="ts">
import {
    categories,
    closeEditOrder,
    decreaseEditQty,
    editApprovalPin,
    editCategory,
    editingItems,
    editingOrder,
    editItemCount,
    editOrderModalOpen,
    editOrderNeedsApproval,
    editOrderNotes,
    editProductSearchQuery,
    editSubtotal,
    filteredEditProducts,
    formatPrice,
    getOrderCustomerPrimary,
    getOrderServiceLabel,
    getStatusClass,
    handleEditProductClick,
    increaseEditQty,
    isUpdatingOrder,
    removeEditItem,
    showEditApprovalPin,
    submitEditOrder,
} from '@/Composables/useOrderState';
import { Eye, EyeOff, Minus, Plus, Search, Trash2, X } from '@lucide/vue';
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
            class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-stone-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/85"
        >
            <div
                class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border-2 border-stone-200 bg-white text-stone-900 shadow-2xl dark:border-white/10 dark:bg-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-200"
            >
                <!-- Header -->
                <div
                    class="flex items-start justify-between gap-4 border-b-2 border-stone-200 px-6 py-5 dark:border-white/5"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full border-2 border-stone-200 bg-sky-100 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-sky-900 dark:border-white/10 dark:bg-sky-950 dark:text-sky-300"
                            >
                                Edit Order
                            </span>
                            <span
                                :class="[
                                    'rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider border-2 border-stone-200 dark:border-white/10',
                                    getStatusClass(editingOrder?.status || ''),
                                ]"
                            >
                                {{ editingOrder?.status }}
                            </span>
                        </div>
                        <h3
                            class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                        >
                            Edit Order {{ editingOrder?.order_number }}
                        </h3>
                        <p
                            class="mt-1 text-xs font-bold text-stone-500 dark:text-slate-400"
                        >
                            Ubah item aktif, lalu sistem akan reset status order
                            kembali ke `pending` sesuai brief.
                        </p>
                    </div>
                    <button
                        @click="closeEditOrder"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-stone-100 text-stone-700 hover:bg-stone-200 transition dark:border-white/10 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700"
                    >
                        <X class="h-4 w-4 stroke-[3]" />
                    </button>
                </div>

                <div
                    class="grid min-h-0 flex-1 gap-0 lg:grid-cols-[1.2fr_0.8fr]"
                >
                    <!-- Left Side: Catalog & Items -->
                    <div
                        class="flex min-h-0 flex-col border-r-2 border-stone-200 dark:border-white/5"
                    >
                        <!-- Order Stats/Info cards -->
                        <div
                            class="grid gap-3 border-b-2 border-stone-200 px-6 py-4 dark:border-white/5 sm:grid-cols-3"
                        >
                            <div
                                class="rounded-xl border-2 border-stone-200 bg-stone-50 p-3.5 dark:border-white/10 dark:bg-slate-950/40 shadow-sm"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >
                                    Tipe Order
                                </p>
                                <p
                                    class="mt-1.5 text-sm font-black text-stone-900 dark:text-white"
                                >
                                    {{ getOrderServiceLabel(editingOrder) }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-2 border-stone-200 bg-stone-50 p-3.5 dark:border-white/10 dark:bg-slate-950/40 shadow-sm"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >
                                    Pelanggan
                                </p>
                                <p
                                    class="mt-1.5 text-sm font-black text-stone-900 dark:text-white"
                                >
                                    {{ getOrderCustomerPrimary(editingOrder) }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-2 border-stone-200 bg-stone-50 p-3.5 dark:border-white/10 dark:bg-slate-950/40 shadow-sm"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >
                                    Jumlah Item
                                </p>
                                <p
                                    class="mt-1.5 text-sm font-black text-stone-900 dark:text-white"
                                >
                                    {{ editItemCount }} item
                                </p>
                                <p
                                    class="mt-0.5 text-[10px] font-bold text-stone-500 dark:text-slate-400"
                                >
                                    {{ editingItems.length }} jenis menu
                                </p>
                            </div>
                        </div>

                        <!-- Menu Catalog Selector -->
                        <div
                            class="border-b-2 border-stone-200 px-6 py-4 dark:border-white/5"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-600 dark:text-orange-400"
                                    >
                                        Tambah Menu
                                    </p>
                                    <p
                                        class="mt-1 text-xs font-bold text-stone-500 dark:text-slate-400"
                                    >
                                        Pilih kategori, cari menu, lalu
                                        tambahkan ke draft edit.
                                    </p>
                                </div>
                                <div class="relative w-full max-w-xs">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-stone-500 dark:text-slate-400"
                                    >
                                        <Search class="h-4 w-4 stroke-[3]" />
                                    </span>
                                    <input
                                        v-model="editProductSearchQuery"
                                        type="text"
                                        placeholder="Cari menu edit..."
                                        class="w-full rounded-xl border-2 border-stone-200 bg-white py-2 pl-9 pr-4 text-xs font-black text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-500"
                                    />
                                </div>
                            </div>

                            <!-- Category list -->
                            <div
                                class="custom-scrollbar mt-3.5 flex gap-2 overflow-x-auto pb-1"
                            >
                                <button
                                    @click="editCategory = 'all'"
                                    :class="[
                                        'whitespace-nowrap rounded-xl border-2 px-3 py-2 text-[11px] font-black transition',
                                        editCategory === 'all'
                                            ? 'border-orange-500 bg-orange-500 text-stone-950 dark:border-orange-500 dark:bg-orange-500 dark:text-white'
                                            : 'border-stone-200 bg-white text-stone-700 hover:bg-stone-100 dark:border-white/10 dark:bg-slate-950 dark:text-slate-350 dark:hover:bg-slate-900',
                                    ]"
                                >
                                    Semua
                                </button>
                                <button
                                    v-for="cat in categories"
                                    :key="`edit-${cat.id}`"
                                    @click="editCategory = cat.id"
                                    :class="[
                                        'whitespace-nowrap rounded-xl border-2 px-3 py-2 text-[11px] font-black transition',
                                        editCategory === cat.id
                                            ? 'border-orange-500 bg-orange-500 text-stone-950 dark:border-orange-500 dark:bg-orange-500 dark:text-white'
                                            : 'border-stone-200 bg-white text-stone-700 hover:bg-stone-100 dark:border-white/10 dark:bg-slate-950 dark:text-slate-350 dark:hover:bg-slate-900',
                                    ]"
                                >
                                    {{ cat.name }}
                                </button>
                            </div>

                            <!-- Products grid -->
                            <div
                                class="custom-scrollbar mt-4 max-h-56 overflow-y-auto pr-1"
                            >
                                <div
                                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
                                >
                                    <button
                                        v-for="product in filteredEditProducts"
                                        :key="`edit-product-${product.id}`"
                                        type="button"
                                        @click="handleEditProductClick(product)"
                                        class="rounded-xl border-2 border-stone-200 bg-stone-50 p-3.5 text-left transition hover:border-orange-500/50 hover:bg-white dark:border-white/10 dark:bg-slate-950/60 dark:hover:bg-slate-900 shadow-sm"
                                    >
                                        <p
                                            class="text-xs font-black text-stone-900 dark:text-white"
                                        >
                                            {{ product.name }}
                                        </p>
                                        <p
                                            class="mt-1 line-clamp-2 text-[10px] font-bold text-stone-500 dark:text-slate-400"
                                        >
                                            {{
                                                product.description ||
                                                'Tidak ada deskripsi.'
                                            }}
                                        </p>
                                        <div
                                            class="mt-3 flex items-center justify-between"
                                        >
                                            <span
                                                class="text-[11px] font-black text-orange-650 dark:text-orange-400"
                                            >
                                                {{
                                                    formatPrice(
                                                        product.base_price,
                                                    )
                                                }}
                                            </span>
                                            <span
                                                class="rounded-lg border-2 border-stone-200 bg-stone-900 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-white hover:bg-stone-800 dark:border-white/10 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700 transition"
                                            >
                                                Tambah
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Draft Items Panel -->
                        <div
                            class="custom-scrollbar min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-4 bg-stone-50/20 dark:bg-slate-950/20"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-650 dark:text-orange-400"
                                    >
                                        Draft Edit Menu
                                    </p>
                                    <p
                                        class="mt-1 text-xs font-bold text-stone-500 dark:text-slate-400"
                                    >
                                        Ubah jumlah, hapus item, atau revisi
                                        catatan per menu.
                                    </p>
                                </div>
                                <span
                                    class="rounded-full border-2 border-stone-200 bg-stone-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-stone-700 dark:border-white/10 dark:bg-slate-950 dark:text-slate-350"
                                >
                                    {{ editItemCount }} item
                                </span>
                            </div>

                            <!-- Draft list -->
                            <div
                                v-for="(item, index) in editingItems"
                                :key="`editing-${index}-${item.product_id}-${item.variant_id || 'base'}`"
                                class="space-y-3 rounded-2xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/10 dark:bg-slate-950/40 shadow-sm"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-black text-stone-900 dark:text-white"
                                        >
                                            {{ item.product_name }}
                                        </p>
                                        <p
                                            v-if="item.variant_name"
                                            class="mt-1 text-[10px] font-black uppercase tracking-wider text-orange-650 dark:text-orange-400"
                                        >
                                            {{ item.variant_name }}
                                        </p>
                                        <p
                                            class="mt-2 text-xs font-black text-stone-700 dark:text-slate-350"
                                        >
                                            {{
                                                formatPrice(
                                                    item.unit_price *
                                                        item.quantity,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <button
                                        @click="removeEditItem(index)"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border-2 border-stone-200 bg-rose-50 text-rose-750 transition hover:bg-rose-100 dark:border-white/10 dark:bg-rose-950/40 dark:text-rose-400"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        @click="decreaseEditQty(index)"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-white text-stone-700 font-black hover:bg-stone-100 dark:border-white/10 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800 transition"
                                    >
                                        <Minus class="h-3 w-3 stroke-[3]" />
                                    </button>
                                    <span
                                        class="w-8 text-center text-sm font-black text-stone-900 dark:text-white"
                                        >{{ item.quantity }}</span
                                    >
                                    <button
                                        @click="increaseEditQty(index)"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-white text-stone-700 font-black hover:bg-stone-100 dark:border-white/10 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800 transition"
                                    >
                                        <Plus class="h-3 w-3 stroke-[3]" />
                                    </button>
                                </div>

                                <input
                                    v-model="item.notes"
                                    type="text"
                                    placeholder="Catatan item, misal saus dipisah..."
                                    class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-2.5 text-xs font-black text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-500"
                                />
                            </div>

                            <div
                                v-if="editingItems.length === 0"
                                class="rounded-2xl border-2 border-dashed border-stone-200 py-12 text-center text-xs font-bold text-stone-450 dark:border-white/10 dark:text-slate-550"
                            >
                                Draft edit kosong. Tambahkan minimal satu menu.
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Kitchen details & actions -->
                    <div
                        class="custom-scrollbar flex min-h-0 flex-col overflow-y-auto px-6 py-5"
                    >
                        <div class="space-y-4">
                            <!-- Information banner -->
                            <div
                                class="rounded-2xl border-2 border-sky-200 bg-sky-50 px-4 py-4 dark:border-sky-500/10 dark:bg-sky-950/20 shadow-sm"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-wider text-sky-850 dark:text-sky-400"
                                >
                                    Informasi Dapur
                                </p>
                                <p
                                    class="mt-1 text-xs font-bold text-sky-950 dark:text-sky-200"
                                >
                                    Setelah disimpan, order akan kembali ke lane
                                    `pending` di kitchen.
                                </p>
                            </div>

                            <!-- General Notes -->
                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >
                                    Catatan Order
                                </label>
                                <textarea
                                    v-model="editOrderNotes"
                                    rows="5"
                                    class="w-full resize-none rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-xs font-black text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:placeholder-slate-500"
                                    placeholder="Update catatan utama order untuk dapur..."
                                ></textarea>
                            </div>

                            <!-- Supervisor Approval -->
                            <div
                                v-if="editOrderNeedsApproval"
                                class="rounded-2xl border-2 border-amber-250 bg-amber-50 p-4 dark:border-amber-500/10 dark:bg-amber-950/20 shadow-sm"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-wider text-amber-850 dark:text-amber-400"
                                >
                                    Persetujuan Supervisor
                                </p>
                                <p
                                    class="mt-1 text-xs font-bold leading-relaxed text-amber-950 dark:text-amber-200"
                                >
                                    Order ini sudah `in_progress`. Input PIN
                                    supervisor atau owner untuk melanjutkan
                                    edit.
                                </p>
                                <div class="relative mt-3">
                                    <input
                                        v-model="editApprovalPin"
                                        :type="
                                            showEditApprovalPin
                                                ? 'text'
                                                : 'password'
                                        "
                                        inputmode="numeric"
                                        placeholder="Masukkan PIN approval"
                                        class="w-full rounded-xl border-2 border-amber-400 bg-white py-3 pl-4 pr-12 text-xs font-black text-stone-900 placeholder-stone-400 focus:border-amber-500 focus:outline-none focus:ring-0 dark:border-amber-500/20 dark:bg-slate-950 dark:text-white dark:placeholder-slate-500"
                                    />
                                    <button
                                        type="button"
                                        @click="
                                            showEditApprovalPin =
                                                !showEditApprovalPin
                                        "
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-400 hover:text-stone-600 dark:text-slate-500 dark:hover:text-slate-300"
                                    >
                                        <component
                                            :is="
                                                showEditApprovalPin
                                                    ? EyeOff
                                                    : Eye
                                            "
                                            class="h-4 w-4"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- Summary totals -->
                            <div
                                class="rounded-2xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/10 dark:bg-slate-950/40 shadow-sm"
                            >
                                <div
                                    class="flex items-center justify-between text-xs font-bold text-stone-600 dark:text-slate-300"
                                >
                                    <span>Total Item</span>
                                    <span class="font-black text-stone-900 dark:text-white">{{ editItemCount }}</span>
                                </div>
                                <div
                                    class="mt-2 flex items-center justify-between text-xs font-bold text-stone-600 dark:text-slate-300"
                                >
                                    <span>Jenis Menu</span>
                                    <span class="font-black text-stone-900 dark:text-white">{{ editingItems.length }}</span>
                                </div>
                                <div
                                    class="mt-3 flex items-center justify-between border-t-2 border-stone-200 pt-3 text-sm font-black text-stone-900 dark:border-white/10 dark:text-white"
                                >
                                    <span>Total Order Baru</span>
                                    <span
                                        class="font-black text-lg text-orange-650 dark:text-orange-400"
                                        >{{ formatPrice(editSubtotal) }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Main Modal Buttons -->
                        <div class="mt-6 grid gap-3">
                            <button
                                @click="submitEditOrder"
                                :disabled="
                                    editingItems.length === 0 || isUpdatingOrder
                                "
                                class="w-full rounded-xl border-2 border-stone-200 bg-cyan-400 hover:bg-cyan-300 px-5 py-3 text-xs font-black text-stone-900 transition dark:border-white/10 dark:bg-cyan-500 dark:text-slate-950 shadow-sm active:scale-98 disabled:pointer-events-none disabled:opacity-50"
                            >
                                {{
                                    isUpdatingOrder
                                        ? 'Menyimpan Edit...'
                                        : 'Simpan Perubahan Order'
                                }}
                            </button>
                            <button
                                @click="closeEditOrder"
                                type="button"
                                class="w-full rounded-xl border-2 border-stone-200 bg-white hover:bg-stone-100 px-5 py-3 text-xs font-black text-stone-700 transition dark:border-white/10 dark:bg-slate-950 dark:text-white dark:hover:bg-slate-900 shadow-sm active:scale-98"
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
