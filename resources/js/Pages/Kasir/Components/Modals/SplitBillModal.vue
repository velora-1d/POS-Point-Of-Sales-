<script setup lang="ts">
import {
    closeSplitBill,
    formatPrice,
    isSplittingBill,
    selectedManagedOrder,
    setSplitQuantity,
    showSplitApprovalPin,
    splitApprovalPin,
    splitBillModalOpen,
    splitDraftItems,
    splitItemCount,
    splitOrderNeedsApproval,
    submitSplitBill,
} from '@/Composables/useOrderState';
import { Eye, EyeOff, Minus, Plus, X } from '@lucide/vue';
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
            v-if="splitBillModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-stone-900/40 p-4 backdrop-blur-sm dark:bg-slate-950/85"
        >
            <div
                class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-stone-200 bg-white text-stone-900 shadow-2xl dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-stone-200 px-6 py-5 dark:border-slate-800/80"
                >
                    <div>
                        <span
                            class="rounded-full border border-fuchsia-500/20 bg-fuchsia-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-fuchsia-600 dark:text-fuchsia-300"
                        >
                            Split Bill
                        </span>
                        <h3
                            class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                        >
                            Split {{ selectedManagedOrder?.order_number }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Tentukan item mana yang pindah ke bill kedua. Bill
                            asal tetap harus menyisakan minimal satu item.
                        </p>
                    </div>
                    <button
                        @click="closeSplitBill"
                        class="text-stone-400 transition hover:text-stone-600 dark:text-slate-500 dark:hover:text-slate-200"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="custom-scrollbar flex-1 overflow-y-auto px-6 py-5">
                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in splitDraftItems"
                            :key="`split-${item.order_item_id}`"
                            class="rounded-2xl border border-stone-200 bg-stone-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/60"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{ item.product_name }}
                                    </p>
                                    <p
                                        v-if="item.variant_name"
                                        class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-orange-600 dark:text-orange-300"
                                    >
                                        {{ item.variant_name }}
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] text-stone-500 dark:text-slate-500"
                                    >
                                        Total item awal: {{ item.quantity }}
                                    </p>
                                </div>
                                <span
                                    class="text-xs font-extrabold text-stone-700 dark:text-slate-300"
                                >
                                    {{ formatPrice(item.total_price) }}
                                </span>
                            </div>

                            <div
                                class="mt-4 flex items-center justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-600 dark:text-fuchsia-300"
                                    >
                                        Split Bill
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                    >
                                        Sisa di bill asal:
                                        {{
                                            item.quantity - item.split_quantity
                                        }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="
                                            setSplitQuantity(
                                                index,
                                                item.split_quantity - 1,
                                            )
                                        "
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-stone-200 bg-stone-100 text-stone-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                                    >
                                        <Minus class="h-4 w-4" />
                                    </button>
                                    <span
                                        class="w-8 text-center text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{ item.split_quantity }}
                                    </span>
                                    <button
                                        @click="
                                            setSplitQuantity(
                                                index,
                                                item.split_quantity + 1,
                                            )
                                        "
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-stone-200 bg-stone-100 text-stone-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="splitOrderNeedsApproval"
                        class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/5"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-600 dark:text-fuchsia-300"
                        >
                            Split Bill
                        </p>
                        <p
                            class="mt-1 text-xs text-amber-900/80 dark:text-amber-100/75"
                        >
                            Order sedang dimasak. Split bill butuh PIN
                            supervisor atau owner.
                        </p>
                        <div class="relative mt-3">
                            <input
                                v-model="splitApprovalPin"
                                :type="
                                    showSplitApprovalPin ? 'text' : 'password'
                                "
                                inputmode="numeric"
                                placeholder="Masukkan PIN approval"
                                class="w-full rounded-xl border border-amber-200 bg-stone-50 py-3 pl-4 pr-12 text-xs text-stone-800 placeholder-stone-400 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400 dark:border-amber-500/20 dark:bg-slate-950 dark:text-slate-100 dark:placeholder-slate-500"
                            />
                            <button
                                type="button"
                                @click="
                                    showSplitApprovalPin = !showSplitApprovalPin
                                "
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-400 hover:text-stone-600 dark:text-slate-500 dark:hover:text-slate-300"
                            >
                                <component
                                    :is="showSplitApprovalPin ? EyeOff : Eye"
                                    class="h-4 w-4"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between gap-4 border-t border-stone-200 px-6 py-4 dark:border-slate-800/80"
                >
                    <div class="text-xs text-stone-500 dark:text-slate-400">
                        {{ splitItemCount }} item akan dipindahkan ke bill
                        kedua.
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="closeSplitBill"
                            type="button"
                            class="rounded-2xl border border-stone-200 bg-white px-4 py-3 text-xs font-bold text-stone-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitSplitBill"
                            :disabled="splitItemCount === 0 || isSplittingBill"
                            class="rounded-2xl bg-gradient-to-r from-fuchsia-500 to-pink-500 px-5 py-3 text-xs font-bold text-white disabled:pointer-events-none disabled:opacity-50"
                        >
                            {{
                                isSplittingBill
                                    ? 'Memproses Split...'
                                    : 'Buat Split Bill'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
