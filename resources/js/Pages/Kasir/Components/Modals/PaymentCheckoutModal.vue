<script setup lang="ts">
import {
    activePaymentCheckout,
    activePaymentConfig,
    formatPrice,
    openPaymentCheckout,
    paymentCheckoutModalOpen,
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
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-lg rounded-3xl border border-stone-200 bg-white text-stone-900 shadow-2xl dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-stone-200/60 px-6 py-5 dark:border-slate-800/80"
                >
                    <div>
                        <span
                            class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em]"
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
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            {{
                                activePaymentCheckout.context ===
                                'before_kitchen'
                                    ? 'Selesaikan pembayaran dulu agar order masuk ke dapur.'
                                    : 'Selesaikan pembayaran agar transaksi otomatis ditutup.'
                            }}
                        </p>
                    </div>
                    <button
                        @click="paymentCheckoutModalOpen = false"
                        class="text-stone-400 transition hover:text-stone-700 dark:text-slate-500 dark:hover:text-slate-200"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div
                        class="rounded-2xl border border-stone-200 bg-stone-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/60"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em]"
                            :class="activePaymentConfig.textRawClass"
                        >
                            Total Bayar
                        </p>
                        <p
                            class="mt-2 text-2xl font-black text-stone-900 dark:text-white"
                        >
                            {{ formatPrice(activePaymentCheckout.amount) }}
                        </p>
                        <p
                            class="mt-2 text-[11px] text-stone-500 dark:text-slate-400"
                        >
                            Checkout dibuka di halaman gateway Pakasir agar
                            customer bisa scan dan bayar dari perangkat mereka.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openPaymentCheckout"
                        class="w-full rounded-2xl px-5 py-3 text-sm font-bold text-white transition hover:brightness-110"
                        :class="activePaymentConfig.gradientClass"
                    >
                        Buka Checkout {{ activePaymentConfig.label }}
                    </button>

                    <p
                        class="text-center text-[11px] text-stone-500 dark:text-slate-500"
                    >
                        Jika belum lunas, kasir bisa buka ulang checkout ini
                        kapan saja dari detail order aktif.
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>
