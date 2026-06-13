<script setup lang="ts">
import {
    activePaymentCheckout,
    activePaymentMethods,
    closePaymentModal,
    existingPaymentApprovalPin,
    existingPaymentCashChange,
    existingPaymentCashReceived,
    existingPaymentDiscount,
    existingPaymentMethod,
    existingPaymentPromoCode,
    existingPaymentPromoWarning,
    existingPaymentTotal,
    formatPrice,
    getPaymentActionHint,
    getPaymentMeta,
    getPaymentMethodConfig,
    getPaymentStatusClass,
    getPaymentStatusLabel,
    handleExistingPaymentPromoChange,
    hasPendingBeforeKitchenPayment,
    isCustomExistingPaymentPromo,
    isProcessingPayment,
    isRefreshingOrder,
    paymentCheckoutModalOpen,
    paymentModalOpen,
    paymentTargetOrder,
    promos,
    refreshCurrentOrder,
    selectedExistingPaymentPromoCode,
    showExistingPaymentApprovalPin,
    submitExistingPayment,
} from '@/Composables/useOrderState';
import { Eye, EyeOff, X } from '@lucide/vue';
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
            v-if="paymentModalOpen && paymentTargetOrder"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-stone-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/80"
        >
            <div
                class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border-2 border-stone-200 bg-white text-stone-900 shadow-2xl dark:border-white/10 dark:bg-slate-950 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-200"
            >
                <!-- Header -->
                <div
                    class="flex items-start justify-between gap-4 border-b-2 border-stone-200 px-6 py-5 dark:border-white/5"
                >
                    <div>
                        <span
                            class="rounded-full border-2 border-orange-200 bg-orange-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-orange-700 dark:border-orange-500/20 dark:bg-orange-950/20 dark:text-orange-400"
                        >
                            Pembayaran Pesanan
                        </span>
                        <h3
                            class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                        >
                            {{ paymentTargetOrder.order_number }}
                        </h3>
                        <p
                            class="mt-1 text-xs font-semibold text-stone-500 dark:text-slate-400"
                        >
                            {{ getPaymentActionHint(paymentTargetOrder) }}
                        </p>
                    </div>
                    <button
                        @click="closePaymentModal"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-stone-50 text-stone-700 hover:bg-stone-100 transition dark:border-white/10 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800"
                    >
                        <X class="h-4 w-4 stroke-[3]" />
                    </button>
                </div>

                <!-- Scrollable Body Content -->
                <div class="custom-scrollbar min-h-0 flex-1 overflow-y-auto px-6 py-5 space-y-4 bg-stone-50/10 dark:bg-slate-950/10">
                    <!-- Total Bill Box -->
                    <div
                        class="space-y-3 rounded-2xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/5 dark:bg-slate-900/40 shadow-sm"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-wider text-orange-600 dark:text-orange-400"
                                >
                                    Total Tagihan
                                </p>
                                <p
                                    class="mt-1 text-2xl font-black text-stone-900 dark:text-white"
                                >
                                    {{ formatPrice(existingPaymentTotal) }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider border-2 border-stone-200 dark:border-white/10',
                                    getPaymentStatusClass(paymentTargetOrder),
                                ]"
                            >
                                {{ getPaymentStatusLabel(paymentTargetOrder) }}
                            </span>
                        </div>
                        <div
                            v-if="existingPaymentDiscount > 0"
                            class="space-y-1 border-t-2 border-stone-200 pt-2 text-[11px] dark:border-white/5"
                        >
                            <div
                                class="flex justify-between font-semibold text-stone-500 dark:text-slate-400"
                            >
                                <span>Subtotal:</span>
                                <span>{{
                                    formatPrice(
                                        Number(
                                            paymentTargetOrder.subtotal || 0,
                                        ),
                                    )
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between font-black text-emerald-700 dark:text-emerald-450"
                            >
                                <span>Diskon Voucher:</span>
                                <span>-{{
                                        formatPrice(existingPaymentDiscount)
                                    }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Active QRIS Monitoring Status Box -->
                    <div
                        v-if="
                            hasPendingBeforeKitchenPayment(paymentTargetOrder)
                        "
                        :class="[
                            'space-y-3 rounded-2xl border-2 shadow-sm p-4',
                            getPaymentMethodConfig(
                                getPaymentMeta(paymentTargetOrder).method ||
                                    'qris',
                            ).borderClass,
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-xl border-2 border-stone-200 dark:border-white/10',
                                    getPaymentMethodConfig(
                                        getPaymentMeta(paymentTargetOrder)
                                            .method || 'qris',
                                    ).iconBgClass,
                                ]"
                            >
                                <svg
                                    :class="[
                                        'h-4 w-4 animate-spin',
                                        getPaymentMethodConfig(
                                            getPaymentMeta(paymentTargetOrder)
                                                .method || 'qris',
                                        ).spinnerClass,
                                    ]"
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
                            </div>
                            <div class="flex-1">
                                <h4
                                    class="text-xs font-black text-stone-900 dark:text-white"
                                >
                                    {{
                                        getPaymentMethodConfig(
                                            getPaymentMeta(paymentTargetOrder)
                                                .method || 'qris',
                                        ).activeLabel
                                    }}
                                </h4>
                                <p
                                    class="mt-0.5 text-[10px] font-semibold leading-normal text-stone-500 dark:text-slate-400"
                                >
                                    Sistem sedang memantau pembayaran dari pelanggan secara real-time. Halaman ini akan otomatis berganti ke proses berikutnya setelah pembayaran lunas.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="refreshCurrentOrder"
                                :disabled="isRefreshingOrder"
                                class="rounded-lg border-2 border-stone-200 bg-white px-3 py-1.5 text-[10px] font-black text-stone-850 transition hover:bg-stone-50 disabled:opacity-50 dark:border-white/10 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800"
                            >
                                {{
                                    isRefreshingOrder
                                        ? 'Checking...'
                                        : 'Cek Status'
                                }}
                            </button>
                        </div>
                        <div
                            v-if="
                                getPaymentMeta(paymentTargetOrder).checkout_url
                            "
                            :class="[
                                'flex items-center justify-between border-t-2 pt-2 text-[10px]',
                                getPaymentMethodConfig(
                                    getPaymentMeta(paymentTargetOrder).method ||
                                        'qris',
                                ).borderInnerClass,
                            ]"
                        >
                            <span class="text-stone-500 dark:text-slate-400 font-semibold"
                                >Link pembayaran dapat ditunjukkan kembali via:</span
                            >
                            <button
                                type="button"
                                @click="
                                    activePaymentCheckout = {
                                        payment_url:
                                            getPaymentMeta(paymentTargetOrder)
                                                .checkout_url,
                                        order_number:
                                            paymentTargetOrder.order_number,
                                        amount: paymentTargetOrder.total_amount,
                                        context: 'before_kitchen',
                                        method:
                                            getPaymentMeta(paymentTargetOrder)
                                                .method || 'qris',
                                    };
                                    paymentCheckoutModalOpen = true;
                                "
                                :class="[
                                    'font-black transition hover:underline',
                                    getPaymentMethodConfig(
                                        getPaymentMeta(paymentTargetOrder)
                                            .method || 'qris',
                                    ).textClass,
                                ]"
                            >
                                {{
                                    getPaymentMethodConfig(
                                        getPaymentMeta(paymentTargetOrder)
                                            .method || 'qris',
                                    ).showText
                                }}
                                &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- Payment methods selector grid -->
                    <div class="grid gap-3 sm:grid-cols-2">
                        <!-- Cash option -->
                        <button
                            type="button"
                            @click="existingPaymentMethod = 'cash'"
                            :class="[
                                'rounded-2xl border-2 p-4 text-left transition duration-150',
                                existingPaymentMethod === 'cash'
                                    ? 'border-transparent bg-orange-500 text-stone-950 font-black shadow-md'
                                    : 'border-stone-200 bg-white text-stone-850 font-bold hover:bg-stone-50 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
                            ]"
                        >
                            <p class="text-sm font-black">Cash</p>
                            <p
                                class="mt-1 text-[11px] font-bold"
                                :class="existingPaymentMethod === 'cash' ? 'text-stone-950/80' : 'text-stone-500 dark:text-slate-400'"
                            >
                                Input nominal tunai. Sistem akan catat lunas dan lanjut ke status berikutnya.
                            </p>
                        </button>
                        <!-- Gateway options -->
                        <button
                            v-for="method in activePaymentMethods"
                            :key="method"
                            type="button"
                            @click="existingPaymentMethod = method as any"
                            :class="[
                                'rounded-2xl border-2 p-4 text-left transition duration-150',
                                existingPaymentMethod === method
                                    ? getPaymentMethodConfig(method).colorClass + ' border-transparent shadow-md'
                                    : 'border-stone-200 bg-white text-stone-850 font-bold hover:bg-stone-50 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
                            ]"
                        >
                            <p class="text-sm font-black">
                                {{ getPaymentMethodConfig(method).label }} Gateway
                            </p>
                            <p
                                class="mt-1 text-[11px] font-bold"
                                :class="existingPaymentMethod === method ? 'text-stone-900 dark:text-slate-200' : 'text-stone-500 dark:text-slate-455'"
                            >
                                {{ getPaymentMethodConfig(method).existingDesc }}
                            </p>
                        </button>
                    </div>

                    <!-- Cash Input Area -->
                    <div
                        v-if="existingPaymentMethod === 'cash'"
                        class="grid gap-3 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                            >
                                Nominal Diterima
                            </label>
                            <input
                                v-model="existingPaymentCashReceived"
                                type="number"
                                min="0"
                                step="1000"
                                placeholder="Contoh: 150000"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </div>
                        <div
                            class="rounded-2xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/5 dark:bg-slate-900/40 shadow-sm"
                        >
                            <p
                                class="text-[9px] font-black uppercase tracking-wider text-orange-600 dark:text-orange-400"
                            >
                                Estimasi Kembalian
                            </p>
                            <p
                                class="mt-2 text-sm font-black text-emerald-700 dark:text-emerald-450"
                            >
                                {{ formatPrice(existingPaymentCashChange) }}
                            </p>
                            <p
                                class="mt-1 text-[11px] font-semibold text-stone-500 dark:text-slate-400"
                            >
                                Kosongkan jika pembayaran pas sesuai total tagihan.
                            </p>
                        </div>
                    </div>

                    <!-- Gateway Info Panel -->
                    <div
                        v-else
                        :class="[
                            'rounded-2xl border-2 p-4 shadow-sm border-transparent',
                            getPaymentMethodConfig(existingPaymentMethod).method === 'ewallet'
                                ? 'bg-blue-500/10'
                                : getPaymentMethodConfig(existingPaymentMethod).method === 'debit'
                                  ? 'bg-emerald-500/10'
                                  : getPaymentMethodConfig(existingPaymentMethod).method === 'transfer'
                                    ? 'bg-indigo-500/10'
                                    : 'bg-fuchsia-500/10',
                        ]"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em]"
                            :class="getPaymentMethodConfig(existingPaymentMethod).textRawClass"
                        >
                            Info Pembayaran {{ getPaymentMethodConfig(existingPaymentMethod).label }}
                        </p>
                        <p
                            class="mt-1.5 text-xs font-semibold leading-relaxed"
                            :class="
                                getPaymentMethodConfig(existingPaymentMethod).method === 'ewallet'
                                    ? 'text-blue-700 dark:text-blue-200'
                                    : getPaymentMethodConfig(existingPaymentMethod).method === 'debit'
                                      ? 'text-emerald-700 dark:text-emerald-200'
                                      : getPaymentMethodConfig(existingPaymentMethod).method === 'transfer'
                                        ? 'text-indigo-700 dark:text-indigo-200'
                                        : 'text-fuchsia-700 dark:text-fuchsia-200'
                            "
                        >
                            {{
                                hasPendingBeforeKitchenPayment(paymentTargetOrder)
                                    ? `Setelah ${getPaymentMethodConfig(existingPaymentMethod).label} lunas, order otomatis pindah ke lane pending agar bisa diproses kitchen.`
                                    : `Setelah ${getPaymentMethodConfig(existingPaymentMethod).label} lunas, order otomatis ditutup sebagai completed.`
                            }}
                        </p>
                    </div>

                    <!-- Voucher & Promo section -->
                    <div
                        class="rounded-2xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/5 dark:bg-slate-900/40 shadow-sm space-y-4"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                            >
                                Voucher / Promo Code
                            </label>
                            <select
                                v-model="selectedExistingPaymentPromoCode"
                                @change="handleExistingPaymentPromoChange"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-white px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option
                                    value=""
                                    class="bg-white text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                                >
                                    Tidak Ada Voucher
                                </option>
                                <option
                                    v-for="promo in promos"
                                    :key="promo.id"
                                    :value="promo.code"
                                    class="bg-white text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                                >
                                    {{ promo.name }} ({{ promo.code }})
                                </option>
                                <option
                                    value="custom"
                                    class="bg-white text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                                >
                                    -- Ketik Kode Manual --
                                </option>
                            </select>

                            <input
                                v-if="isCustomExistingPaymentPromo"
                                v-model="existingPaymentPromoCode"
                                type="text"
                                placeholder="Ketik kode voucher manual..."
                                class="mt-2 w-full rounded-2xl border-2 border-stone-200 bg-white px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                            <p
                                v-if="existingPaymentPromoWarning"
                                class="mt-1.5 text-[11px] font-black leading-relaxed text-rose-600 dark:text-rose-400"
                            >
                                {{ existingPaymentPromoWarning }}
                            </p>
                            <p
                                class="mt-2 text-[11px] font-semibold leading-relaxed text-stone-500 dark:text-slate-400"
                            >
                                Total tagihan akan divalidasi ulang dengan promo otomatis, metode bayar, tier member, dan voucher sebelum settlement diproses.
                            </p>
                        </div>

                        <!-- PIN Owner (Approval) -->
                        <div class="border-t-2 border-stone-200 dark:border-white/5 pt-3">
                            <label
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                            >
                                PIN Owner (Opsional)
                            </label>
                            <div class="relative">
                                <input
                                    v-model="existingPaymentApprovalPin"
                                    :type="
                                        showExistingPaymentApprovalPin
                                            ? 'text'
                                            : 'password'
                                    "
                                    inputmode="numeric"
                                    placeholder="Isi jika diskon manual melewati threshold"
                                    class="w-full rounded-2xl border-2 border-stone-200 bg-white py-2.5 pl-4 pr-12 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                                />
                                <button
                                    type="button"
                                    @click="
                                        showExistingPaymentApprovalPin =
                                            !showExistingPaymentApprovalPin
                                    "
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-500 hover:text-stone-700 dark:text-slate-400 dark:hover:text-slate-200"
                                >
                                    <component
                                        :is="
                                            showExistingPaymentApprovalPin
                                                ? EyeOff
                                                : Eye
                                        "
                                        class="h-4 w-4"
                                    />
                                </button>
                            </div>
                            <p
                                class="mt-2 text-[11px] font-semibold leading-relaxed text-stone-500 dark:text-slate-400"
                            >
                                Approval owner hanya dipakai bila promo manual menghasilkan diskon di atas batas outlet.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div
                    class="flex items-center justify-end gap-3 border-t-2 border-stone-200 px-6 py-4 dark:border-white/5 bg-stone-50 dark:bg-slate-900/80"
                >
                    <button
                        @click="closePaymentModal"
                        type="button"
                        class="rounded-2xl border-2 border-stone-200 bg-transparent px-4 py-2.5 text-xs font-bold text-stone-700 transition hover:bg-stone-100 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5 active:scale-95"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitExistingPayment"
                        :disabled="isProcessingPayment"
                        class="rounded-2xl border-2 border-transparent bg-orange-500 px-5 py-2.5 text-xs font-black uppercase tracking-wider text-stone-950 transition hover:bg-orange-400 active:scale-95 disabled:pointer-events-none disabled:opacity-50"
                    >
                        {{
                            isProcessingPayment
                                ? 'Memproses...'
                                : existingPaymentMethod === 'cash'
                                  ? 'Simpan Pembayaran Cash'
                                  : 'Buat Checkout ' +
                                    (existingPaymentMethod
                                        ? existingPaymentMethod.toUpperCase()
                                        : 'QRIS')
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
