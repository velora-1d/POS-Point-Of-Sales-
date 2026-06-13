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
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-stone-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/80 animate-in fade-in duration-200"
        >
            <div
                class="relative w-full max-w-md space-y-6 rounded-3xl border-2 border-stone-200 bg-white p-6 text-stone-900 shadow-2xl dark:border-white/10 dark:bg-slate-950 dark:text-slate-100 animate-in zoom-in-95 duration-200"
            >
                <button
                    @click="variantModalOpen = false"
                    class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-stone-50 text-stone-700 hover:bg-stone-100 transition dark:border-white/10 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800"
                >
                    <X class="h-4 w-4 stroke-[3]" />
                </button>

                <div>
                    <span
                        class="mb-2 inline-flex items-center gap-1.5 rounded-full border-2 border-orange-200 bg-orange-50 px-3 py-1 text-[9px] font-black uppercase tracking-wider text-orange-700 dark:border-orange-500/20 dark:bg-orange-950/20 dark:text-orange-400"
                    >
                        Pilih Varian Menu
                    </span>
                    <h4
                        class="text-lg font-black text-stone-900 dark:text-white"
                    >
                        {{ selectedProduct?.name }}
                    </h4>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400 font-semibold">
                        {{ selectedProduct?.description }}
                    </p>
                </div>

                <!-- Variant Options Radio -->
                <div class="space-y-2.5">
                    <label
                        class="block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                    >
                        Pilihan Varian
                    </label>
                    <div class="grid grid-cols-1 gap-2">
                        <div
                            v-for="variant in selectedProduct?.variants"
                            :key="variant.id"
                            @click="selectedVariant = variant"
                            :class="[
                                'flex cursor-pointer select-none items-center justify-between rounded-2xl border-2 p-3.5 transition duration-150',
                                selectedVariant?.id === variant.id
                                    ? 'border-orange-500 bg-orange-50 text-orange-700 dark:text-orange-400 font-extrabold'
                                    : 'border-stone-200 bg-stone-50/50 text-stone-700 hover:bg-stone-100 dark:border-white/10 dark:bg-slate-900/60 dark:text-slate-350 dark:hover:bg-slate-800',
                            ]"
                        >
                            <div
                                class="flex items-center gap-2.5 text-xs font-bold"
                            >
                                <span
                                    :class="[
                                        'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2',
                                        selectedVariant?.id === variant.id
                                            ? 'border-orange-500'
                                            : 'border-stone-300 dark:border-white/10',
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
                <div
                    class="flex items-center justify-between gap-4 border-t-2 border-stone-200 pt-4 dark:border-white/5"
                >
                    <span
                        class="block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                    >
                        Jumlah Item
                    </span>
                    <div class="flex items-center gap-3">
                        <button
                            @click="
                                itemQuantity =
                                    itemQuantity > 1 ? itemQuantity - 1 : 1
                            "
                            class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-stone-50 text-stone-600 transition hover:bg-stone-100 dark:border-white/10 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
                        >
                            <Minus class="h-4 w-4" />
                        </button>
                        <span
                            class="w-6 text-center text-base font-black text-stone-900 dark:text-slate-100"
                        >
                            {{ itemQuantity }}
                        </span>
                        <button
                            @click="itemQuantity += 1"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-stone-50 text-stone-600 transition hover:bg-stone-100 dark:border-white/10 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Notes field for kitchen -->
                <div>
                    <label
                        for="item-notes"
                        class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                    >
                        Catatan Khusus Menu
                    </label>
                    <input
                        id="item-notes"
                        type="text"
                        v-model="itemNotes"
                        placeholder="Contoh: level 3 pedas, extra mayones, dll..."
                        class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                    />
                </div>

                <!-- Confirm button -->
                <button
                    @click="confirmVariantAdd"
                    class="w-full rounded-2xl border-2 border-transparent bg-orange-500 px-5 py-3 text-xs font-black uppercase tracking-wider text-stone-950 transition hover:bg-orange-400 active:scale-95 shadow-sm"
                >
                    Konfirmasi & Tambah ke Keranjang
                </button>
            </div>
        </div>
    </Transition>
</template>
