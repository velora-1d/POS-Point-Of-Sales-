<script setup lang="ts">
import {
    activePaymentCheckout,
    activePaymentConfig,
    formatPrice,
    openPaymentCheckout,
    paymentCheckoutModalOpen,
    refreshCurrentOrder,
    isRefreshingOrder,
} from '@/Composables/useOrderState';
import { X } from '@lucide/vue';
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
            v-if="paymentCheckoutModalOpen && activePaymentCheckout"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-stone-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/85"
        >
            <div
                class="w-full max-w-lg rounded-3xl border-2 border-stone-200 bg-white text-stone-900 shadow-2xl dark:border-white/10 dark:bg-slate-950 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-200"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b-2 border-stone-200 px-6 py-5 dark:border-white/5"
                >
                    <div>
                        <span
                            class="rounded-full border-2 px-3 py-1 text-[10px] font-black uppercase tracking-wider"
                            :class="[
                                activePaymentConfig.borderInnerClass,
                                activePaymentConfig.iconBgClass,
                            ]"
                        >
                            Checkout {{ activePaymentConfig.label }} Aktif
                        </span>
                        <h3
                            class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                        >
                            {{ activePaymentCheckout.order_number }}
                        </h3>
                        <p
                            class="mt-1 text-xs font-semibold text-stone-500 dark:text-slate-400"
                        >
                            {{
                                activePaymentCheckout.context === 'before_kitchen'
                                    ? 'Selesaikan pembayaran dulu agar order masuk ke dapur.'
                                    : 'Selesaikan pembayaran agar transaksi otomatis ditutup.'
                            }}
                        </p>
                    </div>
                    <button
                        @click="paymentCheckoutModalOpen = false"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-stone-50 text-stone-700 hover:bg-stone-100 transition dark:border-white/10 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800"
                    >
                        <X class="h-4 w-4 stroke-[3]" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div
                        class="rounded-2xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/5 dark:bg-slate-900/40 shadow-sm"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-wider"
                            :class="activePaymentConfig.textRawClass"
                        >
                            Total Bayar
                        </p>
                        <p
                            class="mt-1 text-2xl font-black text-stone-900 dark:text-white"
                        >
                            {{ formatPrice(activePaymentCheckout.amount) }}
                        </p>
                        <p
                            class="mt-2 text-[11px] font-semibold text-stone-500 dark:text-slate-400"
                        >
                            Checkout dibuka di halaman gateway Pakasir agar customer bisa scan dan bayar dari perangkat mereka.
                        </p>
                    </div>

                    <div class="space-y-3 pt-2">
                        <button
                            type="button"
                            @click="openPaymentCheckout"
                            class="w-full rounded-2xl border-2 border-transparent px-5 py-3 text-xs font-black text-stone-950 transition hover:brightness-115 active:scale-95 shadow-sm uppercase tracking-wider"
                            :class="activePaymentConfig.gradientClass"
                        >
                            Buka Checkout {{ activePaymentConfig.label }}
                        </button>

                        <button
                            type="button"
                            @click="refreshCurrentOrder"
                            :disabled="isRefreshingOrder"
                            class="w-full rounded-2xl border-2 border-stone-200 bg-transparent px-5 py-3 text-xs font-black text-stone-700 transition hover:bg-stone-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5 active:scale-95 disabled:pointer-events-none disabled:opacity-50 uppercase tracking-wider"
                        >
                            {{ isRefreshingOrder ? 'Memeriksa Pembayaran...' : 'Cek Status Pembayaran' }}
                        </button>
                    </div>

                    <p
                        class="text-center text-[11px] font-semibold text-stone-500 dark:text-slate-500"
                    >
                        Jika belum lunas, kasir bisa buka ulang checkout ini kapan saja dari detail order aktif.
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>
