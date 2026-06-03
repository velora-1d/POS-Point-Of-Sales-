<script setup lang="ts">
import { X, Minus, Plus } from '@lucide/vue';
import {
    variantModalOpen,
    selectedProduct,
    selectedVariant,
    itemQuantity,
    itemNotes,
    formatPrice,
    confirmVariantAdd,
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
            v-if="variantModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/80 p-4 backdrop-blur-sm"
        >
            <div class="relative w-full max-w-md space-y-6 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl text-stone-900 dark:text-slate-100">
                <button
                    @click="variantModalOpen = false"
                    class="text-stone-400 hover:text-stone-700 dark:text-slate-500 dark:hover:text-slate-300 absolute right-4 top-4"
                >
                    <X class="h-5 w-5" />
                </button>

                <div>
                    <span class="mb-2 inline-flex items-center gap-1.5 rounded border border-orange-500/20 bg-orange-500/5 dark:bg-orange-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">
                        Pilih Varian Menu
                    </span>
                    <h4 class="text-lg font-extrabold text-stone-900 dark:text-white">
                        {{ selectedProduct?.name }}
                    </h4>
                    <p class="text-stone-500 dark:text-slate-400 mt-1 text-xs">
                        {{ selectedProduct?.description }}
                    </p>
                </div>

                <!-- Variant Options Radio -->
                <div class="space-y-2.5">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400">
                        Pilihan Varian
                    </label>
                    <div class="grid grid-cols-1 gap-2">
                        <div
                            v-for="variant in selectedProduct?.variants"
                            :key="variant.id"
                            @click="selectedVariant = variant"
                            :class="[
                                'flex cursor-pointer select-none items-center justify-between rounded-xl border p-3.5 transition',
                                selectedVariant?.id === variant.id
                                    ? 'border-orange-500 bg-orange-500/5 text-orange-600 dark:text-orange-400 font-semibold'
                                    : 'border-stone-200 dark:border-slate-800 bg-stone-50/50 dark:bg-slate-950/60 text-stone-700 dark:text-slate-300 hover:bg-stone-100 dark:hover:bg-slate-900',
                            ]"
                        >
                            <div class="flex items-center gap-2 text-xs font-bold">
                                <span
                                    :class="[
                                        'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border',
                                        selectedVariant?.id === variant.id
                                            ? 'border-orange-500 text-orange-400'
                                            : 'border-stone-300 dark:border-slate-700',
                                    ]"
                                >
                                    <span
                                        v-if="selectedVariant?.id === variant.id"
                                        class="h-1.5 w-1.5 rounded-full bg-orange-500"
                                    ></span>
                                </span>
                                <span>{{ variant.name }}</span>
                            </div>
                            <span class="text-xs font-black">
                                + {{ formatPrice(variant.additional_price) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quantity Control -->
                <div class="flex items-center justify-between gap-4 border-t border-stone-200/60 dark:border-slate-800/80 pt-4">
                    <span class="block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400">
                        Jumlah Item
                    </span>
                    <div class="flex items-center gap-3">
                        <button
                            @click="itemQuantity = itemQuantity > 1 ? itemQuantity - 1 : 1"
                            class="border-stone-200 dark:border-slate-800 flex h-8 w-8 items-center justify-center rounded-lg border bg-stone-100 dark:bg-slate-950 text-stone-600 dark:text-slate-400 hover:bg-stone-200 dark:hover:bg-slate-900 hover:text-stone-900 dark:hover:text-white transition"
                        >
                            <Minus class="h-4 w-4" />
                        </button>
                        <span class="w-6 text-center text-base font-extrabold text-stone-900 dark:text-slate-100">
                            {{ itemQuantity }}
                        </span>
                        <button
                            @click="itemQuantity += 1"
                            class="border-stone-200 dark:border-slate-800 flex h-8 w-8 items-center justify-center rounded-lg border bg-stone-100 dark:bg-slate-950 text-stone-600 dark:text-slate-400 hover:bg-stone-200 dark:hover:bg-slate-900 hover:text-stone-900 dark:hover:text-white transition"
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Notes field for kitchen -->
                <div>
                    <label for="item-notes" class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400">
                        Catatan Khusus Menu
                    </label>
                    <input
                        id="item-notes"
                        type="text"
                        v-model="itemNotes"
                        placeholder="Contoh: level 3 pedas, extra mayones, dll..."
                        class="border-stone-200 dark:border-slate-800 w-full rounded-xl border bg-stone-50 dark:bg-slate-950 px-4 py-3 text-xs text-stone-900 dark:text-slate-200 placeholder-stone-400 dark:placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    />
                </div>

                <!-- Confirm button -->
                <button
                    @click="confirmVariantAdd"
                    class="to-red-600 hover:to-red-700 w-full rounded-xl bg-gradient-to-r from-orange-500 px-5 py-3 text-xs font-bold text-white shadow-md transition duration-150 hover:from-orange-600 active:scale-[0.99]"
                >
                    Konfirmasi & Tambah ke Keranjang
                </button>
            </div>
        </div>
    </Transition>
</template>
