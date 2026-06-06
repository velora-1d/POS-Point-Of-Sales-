<script setup lang="ts">
import {
    confirmVariantAdd,
    formatPrice,
    itemNotes,
    itemQuantity,
    selectedProduct,
    selectedVariant,
    variantModalOpen,
} from '@/Composables/useOrderState';
import { Minus, Plus, X } from '@lucide/vue';
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
            <div
                class="relative w-full max-w-md space-y-6 rounded-2xl border border-stone-200 bg-white p-6 text-stone-900 shadow-2xl dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
            >
                <button
                    @click="variantModalOpen = false"
                    class="absolute right-4 top-4 text-stone-400 hover:text-stone-700 dark:text-slate-500 dark:hover:text-slate-300"
                >
                    <X class="h-5 w-5" />
                </button>

                <div>
                    <span
                        class="mb-2 inline-flex items-center gap-1.5 rounded border border-orange-500/20 bg-orange-500/5 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-orange-600 dark:bg-orange-500/10 dark:text-orange-400"
                    >
                        Pilih Varian Menu
                    </span>
                    <h4
                        class="text-lg font-extrabold text-stone-900 dark:text-white"
                    >
                        {{ selectedProduct?.name }}
                    </h4>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                        {{ selectedProduct?.description }}
                    </p>
                </div>

                <!-- Variant Options Radio -->
                <div class="space-y-2.5">
                    <label
                        class="block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                    >
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
                                    ? 'border-orange-500 bg-orange-500/5 font-semibold text-orange-600 dark:text-orange-400'
                                    : 'border-stone-200 bg-stone-50/50 text-stone-700 hover:bg-stone-100 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-300 dark:hover:bg-slate-900',
                            ]"
                        >
                            <div
                                class="flex items-center gap-2 text-xs font-bold"
                            >
                                <span
                                    :class="[
                                        'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border',
                                        selectedVariant?.id === variant.id
                                            ? 'border-orange-500 text-orange-400'
                                            : 'border-stone-300 dark:border-slate-700',
                                    ]"
                                >
                                    <span
                                        v-if="
                                            selectedVariant?.id === variant.id
                                        "
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
                <div
                    class="flex items-center justify-between gap-4 border-t border-stone-200/60 pt-4 dark:border-slate-800/80"
                >
                    <span
                        class="block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                    >
                        Jumlah Item
                    </span>
                    <div class="flex items-center gap-3">
                        <button
                            @click="
                                itemQuantity =
                                    itemQuantity > 1 ? itemQuantity - 1 : 1
                            "
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-stone-200 bg-stone-100 text-stone-600 transition hover:bg-stone-200 hover:text-stone-900 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white"
                        >
                            <Minus class="h-4 w-4" />
                        </button>
                        <span
                            class="w-6 text-center text-base font-extrabold text-stone-900 dark:text-slate-100"
                        >
                            {{ itemQuantity }}
                        </span>
                        <button
                            @click="itemQuantity += 1"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-stone-200 bg-stone-100 text-stone-600 transition hover:bg-stone-200 hover:text-stone-900 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white"
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Notes field for kitchen -->
                <div>
                    <label
                        for="item-notes"
                        class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                    >
                        Catatan Khusus Menu
                    </label>
                    <input
                        id="item-notes"
                        type="text"
                        v-model="itemNotes"
                        placeholder="Contoh: level 3 pedas, extra mayones, dll..."
                        class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-xs text-stone-900 placeholder-stone-400 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:placeholder-slate-500"
                    />
                </div>

                <!-- Confirm button -->
                <button
                    @click="confirmVariantAdd"
                    class="w-full rounded-xl bg-gradient-to-r from-orange-500 to-red-600 px-5 py-3 text-xs font-bold text-white shadow-md transition duration-150 hover:from-orange-600 hover:to-red-700 active:scale-[0.99]"
                >
                    Konfirmasi & Tambah ke Keranjang
                </button>
            </div>
        </div>
    </Transition>
</template>
