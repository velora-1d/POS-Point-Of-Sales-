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
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-white p-4 backdrop-blur-sm dark:bg-slate-950/85"
        >
            <div
                class="w-full max-w-xl rounded-3xl border border-stone-200 bg-white text-stone-900 shadow-2xl dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-stone-200/60 px-6 py-5 dark:border-slate-800/80"
                >
                    <div>
                        <span
                            class="rounded-full border border-orange-500/20 bg-orange-500/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-orange-600 dark:bg-orange-500/10 dark:text-orange-300"
                        >
                            Pembayaran Pesanan
                        </span>
                        <h3
                            class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                        >
                            {{ paymentTargetOrder.order_number }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            {{ getPaymentActionHint(paymentTargetOrder) }}
                        </p>
                    </div>
                    <button
                        @click="closePaymentModal"
                        class="text-stone-400 transition hover:text-stone-700 dark:text-slate-400 dark:hover:text-slate-200"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div
                        class="space-y-3 rounded-2xl border border-stone-200 bg-stone-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/60"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-600 dark:text-orange-400"
                                >
                                    Total Tagihan
                                </p>
                                <p
                                    class="mt-2 text-2xl font-black text-stone-900 dark:text-white"
                                >
                                    {{ formatPrice(existingPaymentTotal) }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider',
                                    getPaymentStatusClass(paymentTargetOrder),
                                ]"
                            >
                                {{ getPaymentStatusLabel(paymentTargetOrder) }}
                            </span>
                        </div>
                        <div
                            v-if="existingPaymentDiscount > 0"
                            class="space-y-1 border-t border-stone-200/60 pt-2 text-[11px] dark:border-slate-800/80"
                        >
                            <div
                                class="flex justify-between text-stone-500 dark:text-slate-400"
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
                                class="flex justify-between font-semibold text-emerald-600 dark:text-emerald-400"
                            >
                                <span>Diskon Voucher:</span>
                                <span
                                    >-{{
                                        formatPrice(existingPaymentDiscount)
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Box Informasi QRIS Aktif (Menunggu Pembayaran) -->
                    <div
                        v-if="
                            hasPendingBeforeKitchenPayment(paymentTargetOrder)
                        "
                        :class="[
                            'space-y-3 rounded-2xl border p-4',
                            getPaymentMethodConfig(
                                getPaymentMeta(paymentTargetOrder).method ||
                                    'qris',
                            ).borderClass,
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-xl',
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
                                    class="mt-0.5 text-[10px] leading-normal text-stone-500 dark:text-slate-400"
                                >
                                    Sistem sedang memantau pembayaran dari
                                    pelanggan secara real-time. Halaman ini akan
                                    otomatis berganti ke proses berikutnya
                                    setelah pembayaran lunas.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="refreshCurrentOrder"
                                :disabled="isRefreshingOrder"
                                class="rounded-xl border border-stone-200 bg-stone-100 px-3 py-1.5 text-[10px] font-bold text-stone-700 transition hover:bg-stone-200 disabled:opacity-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
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
                                'flex items-center justify-between border-t pt-2 text-[10px]',
                                getPaymentMethodConfig(
                                    getPaymentMeta(paymentTargetOrder).method ||
                                        'qris',
                                ).borderInnerClass,
                            ]"
                        >
                            <span class="text-stone-500 dark:text-slate-400"
                                >Link pembayaran dapat ditunjukkan kembali
                                via:</span
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
                                    'font-extrabold transition',
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

                    <div class="grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            @click="existingPaymentMethod = 'cash'"
                            :class="[
                                'rounded-2xl border p-4 text-left transition',
                                existingPaymentMethod === 'cash'
                                    ? 'border-orange-500 bg-orange-500/10 font-bold text-orange-600 ring-2 ring-orange-500/20 dark:text-white'
                                    : 'border-stone-200 bg-stone-50/50 text-stone-700 hover:bg-stone-100 dark:border-slate-800 dark:bg-slate-900 dark:bg-slate-950/70 dark:text-slate-300 dark:hover:bg-white',
                            ]"
                        >
                            <p class="text-sm font-bold">Cash</p>
                            <p
                                class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                            >
                                Input nominal tunai. Sistem akan catat lunas dan
                                lanjut ke status berikutnya.
                            </p>
                        </button>
                        <button
                            v-for="method in activePaymentMethods"
                            :key="method"
                            type="button"
                            @click="existingPaymentMethod = method as any"
                            :class="[
                                'rounded-2xl border p-4 text-left transition',
                                existingPaymentMethod === method
                                    ? getPaymentMethodConfig(method).colorClass
                                    : 'border-stone-200 bg-stone-50/50 text-stone-700 hover:bg-stone-100 dark:border-slate-800 dark:bg-slate-900 dark:bg-slate-950/70 dark:text-slate-300 dark:hover:bg-white',
                            ]"
                        >
                            <p class="text-sm font-bold">
                                {{ getPaymentMethodConfig(method).label }}
                                Gateway
                            </p>
                            <p
                                class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                            >
                                {{
                                    getPaymentMethodConfig(method).existingDesc
                                }}
                            </p>
                        </button>
                    </div>

                    <div
                        v-if="existingPaymentMethod === 'cash'"
                        class="grid gap-3 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >
                                Nominal Diterima
                            </label>
                            <input
                                v-model="existingPaymentCashReceived"
                                type="number"
                                min="0"
                                step="1000"
                                placeholder="Contoh: 150000"
                                class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-xs text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:placeholder-slate-500"
                            />
                        </div>
                        <div
                            class="rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-slate-800 dark:bg-slate-950"
                        >
                            <p
                                class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                            >
                                Estimasi Kembalian
                            </p>
                            <p
                                class="mt-2 text-sm font-extrabold text-emerald-600 dark:text-emerald-300"
                            >
                                {{ formatPrice(existingPaymentCashChange) }}
                            </p>
                            <p
                                class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                            >
                                Kosongkan jika pembayaran pas sesuai total
                                tagihan.
                            </p>
                        </div>
                    </div>

                    <div
                        v-else
                        :class="[
                            'rounded-2xl border p-4',
                            getPaymentMethodConfig(existingPaymentMethod)
                                .method === 'ewallet'
                                ? 'border-blue-500/15 bg-blue-500/5'
                                : getPaymentMethodConfig(existingPaymentMethod)
                                        .method === 'debit'
                                  ? 'border-emerald-500/15 bg-emerald-500/5'
                                  : getPaymentMethodConfig(
                                          existingPaymentMethod,
                                      ).method === 'transfer'
                                    ? 'border-indigo-500/15 bg-indigo-500/5'
                                    : 'border-fuchsia-500/15 bg-fuchsia-500/5',
                        ]"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em]"
                            :class="
                                getPaymentMethodConfig(existingPaymentMethod)
                                    .textRawClass
                            "
                        >
                            Info Pembayaran
                            {{
                                getPaymentMethodConfig(existingPaymentMethod)
                                    .label
                            }}
                        </p>
                        <p
                            class="mt-1 text-xs leading-relaxed"
                            :class="
                                getPaymentMethodConfig(existingPaymentMethod)
                                    .method === 'ewallet'
                                    ? 'text-blue-700/90 dark:text-blue-100/80'
                                    : getPaymentMethodConfig(
                                            existingPaymentMethod,
                                        ).method === 'debit'
                                      ? 'text-emerald-700/90 dark:text-emerald-100/80'
                                      : getPaymentMethodConfig(
                                              existingPaymentMethod,
                                          ).method === 'transfer'
                                        ? 'text-indigo-700/90 dark:text-indigo-100/80'
                                        : 'text-fuchsia-700/90 dark:text-fuchsia-100/80'
                            "
                        >
                            {{
                                hasPendingBeforeKitchenPayment(
                                    paymentTargetOrder,
                                )
                                    ? `Setelah ${getPaymentMethodConfig(existingPaymentMethod).label} lunas, order otomatis pindah ke lane pending agar bisa diproses kitchen.`
                                    : `Setelah ${getPaymentMethodConfig(existingPaymentMethod).label} lunas, order otomatis ditutup sebagai completed.`
                            }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-stone-200 bg-stone-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/60"
                    >
                        <label
                            class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                        >
                            Voucher / Promo Code
                        </label>
                        <select
                            v-model="selectedExistingPaymentPromoCode"
                            @change="handleExistingPaymentPromoChange"
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-900 outline-none transition focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
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
                            class="mt-2 w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-xs uppercase text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:placeholder-slate-500"
                        />
                        <p
                            v-if="existingPaymentPromoWarning"
                            class="mt-1.5 text-[11px] font-bold leading-relaxed text-rose-600 dark:text-rose-400"
                        >
                            {{ existingPaymentPromoWarning }}
                        </p>
                        <p
                            class="mt-2 text-[11px] leading-relaxed text-stone-500 dark:text-slate-500"
                        >
                            Total tagihan akan divalidasi ulang dengan promo
                            otomatis, metode bayar, tier member, dan voucher
                            sebelum settlement diproses.
                        </p>
                        <div class="mt-4">
                            <label
                                class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
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
                                    class="w-full rounded-xl border border-stone-200 bg-stone-50 py-3 pl-4 pr-12 text-xs text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:placeholder-slate-500"
                                />
                                <button
                                    type="button"
                                    @click="
                                        showExistingPaymentApprovalPin =
                                            !showExistingPaymentApprovalPin
                                    "
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-400 hover:text-stone-700 dark:text-slate-500 dark:hover:text-slate-400"
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
                                class="mt-2 text-[11px] leading-relaxed text-stone-500 dark:text-slate-500"
                            >
                                Approval owner hanya dipakai bila promo manual
                                menghasilkan diskon di atas batas outlet.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t border-stone-200/60 px-6 py-4 dark:border-slate-800/80"
                >
                    <button
                        @click="closePaymentModal"
                        type="button"
                        class="rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-xs font-bold text-stone-700 transition hover:bg-stone-200 active:scale-[0.98] dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-900"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitExistingPayment"
                        :disabled="isProcessingPayment"
                        class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-5 py-3 text-xs font-bold text-white transition active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50"
                    >
                        {{
                            isProcessingPayment
                                ? 'Memproses Pembayaran...'
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
