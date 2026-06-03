<script setup lang="ts">
import { X, Eye, EyeOff } from '@lucide/vue';
import {
    mergeBillModalOpen,
    mergeSelectedOrders,
    mergeNeedsApproval,
    mergeApprovalPin,
    showMergeApprovalPin,
    canMergeSelectedOrders,
    isMergingBills,
    closeMergeBill,
    submitMergeBills,
    formatPrice,
    getOrderCustomerPrimary,
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
            v-if="mergeBillModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-stone-900/40 dark:bg-slate-955/85 p-4 backdrop-blur-sm"
        >
            <div class="relative w-full max-w-2xl rounded-3xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl text-stone-900 dark:text-slate-100">
                <div class="border-b border-stone-200 dark:border-slate-800/80 px-6 py-5">
                    <span class="rounded-full border border-fuchsia-500/20 bg-fuchsia-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-fuchsia-600 dark:text-fuchsia-305">
                        Gabung Bill
                    </span>
                    <h3 class="mt-3 text-xl font-black text-stone-900 dark:text-white">
                        Konfirmasi Gabung Bill
                    </h3>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                        Order terpilih akan digabung menjadi satu bill baru, lalu order asal diarsipkan.
                    </p>
                </div>

                <div class="space-y-3 px-6 py-5">
                    <div
                        v-for="order in mergeSelectedOrders"
                        :key="`merge-${order.id}`"
                        class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50/50 dark:bg-slate-950/60 p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-stone-900 dark:text-white">
                                    {{ order.order_number }}
                                </p>
                                <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                    {{ order.table?.name }} • {{ getOrderCustomerPrimary(order) }}
                                </p>
                            </div>
                            <span class="text-xs font-extrabold text-orange-600 dark:text-orange-400">
                                {{ formatPrice(order.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="mergeNeedsApproval" class="rounded-2xl border border-amber-250 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-500/5 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-455">Gabung Bill</p>
                        <p class="mt-1 text-xs text-amber-900/80 dark:text-amber-100/75">
                            Ada order `in_progress` di pilihan ini. Gabung bill butuh PIN supervisor atau owner.
                        </p>
                        <div class="relative mt-3">
                            <input
                                v-model="mergeApprovalPin"
                                :type="showMergeApprovalPin ? 'text' : 'password'"
                                inputmode="numeric"
                                placeholder="Masukkan PIN approval"
                                class="w-full rounded-xl border border-amber-250 dark:border-amber-500/20 bg-stone-55 dark:bg-slate-950 pl-4 pr-12 py-3 text-xs text-stone-855 dark:text-slate-100 placeholder-stone-400 dark:placeholder-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                            />
                            <button
                                type="button"
                                @click="showMergeApprovalPin = !showMergeApprovalPin"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-400 dark:text-slate-500 hover:text-stone-600 dark:hover:text-slate-350"
                            >
                                <component :is="showMergeApprovalPin ? EyeOff : Eye" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-stone-200 dark:border-slate-800/80 px-6 py-4">
                    <button
                        @click="closeMergeBill"
                        type="button"
                        class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-955 px-4 py-3 text-xs font-bold text-stone-700 dark:text-slate-305 hover:bg-stone-100 dark:hover:bg-slate-900"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitMergeBills"
                        :disabled="!canMergeSelectedOrders || isMergingBills"
                        class="rounded-2xl bg-gradient-to-r from-fuchsia-500 to-pink-500 px-5 py-3 text-xs font-bold text-white disabled:pointer-events-none disabled:opacity-50"
                    >
                        {{ isMergingBills ? 'Memproses Merge...' : 'Konfirmasi Gabung Bill' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
