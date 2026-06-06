<script setup lang="ts">
import {
    closeKasbonModal,
    formatPrice,
    kasbonForm,
    kasbonModalOpen,
    kasbonTargetOrder,
    submitKasbon,
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
            v-if="kasbonModalOpen && kasbonTargetOrder"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-xl rounded-3xl border border-stone-200 bg-white text-stone-900 shadow-2xl dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-stone-200/60 px-6 py-5 dark:border-slate-800/80"
                >
                    <div>
                        <span
                            class="rounded-full border border-amber-500/20 bg-amber-500/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            Kasbon / Piutang
                        </span>
                        <h3
                            class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                        >
                            {{ kasbonTargetOrder.order_number }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Tutup transaksi sebagai piutang pelanggan dan
                            lanjutkan cicilan dari menu transaksi.
                        </p>
                    </div>
                    <button
                        @click="closeKasbonModal"
                        class="text-stone-400 transition hover:text-stone-700 dark:text-slate-500 dark:hover:text-slate-200"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div
                        class="rounded-2xl border border-stone-200 bg-stone-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/60"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-600 dark:text-orange-400"
                                >
                                    Nama Pelanggan
                                </p>
                                <p
                                    class="mt-2 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{
                                        kasbonTargetOrder.customer?.name ||
                                        'Customer tidak ditemukan'
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                >
                                    {{
                                        kasbonTargetOrder.customer?.phone ||
                                        'Tanpa nomor HP'
                                    }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-600 dark:text-orange-400"
                                >
                                    Sisa Tagihan
                                </p>
                                <p
                                    class="mt-2 text-lg font-black text-amber-600 dark:text-amber-300"
                                >
                                    {{
                                        formatPrice(
                                            Number(
                                                kasbonTargetOrder.total_amount,
                                            ) -
                                                Number(
                                                    kasbonTargetOrder.paid_amount ||
                                                        0,
                                                ),
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >
                                Jatuh Tempo
                            </label>
                            <input
                                v-model="kasbonForm.due_date"
                                type="date"
                                class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-xs text-stone-900 placeholder-stone-400 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:placeholder-slate-500"
                            />
                            <p
                                class="mt-2 text-[11px] text-stone-500 dark:text-slate-500"
                            >
                                Opsional. Jika diisi, tanggal akan dipakai di
                                daftar kasbon dan struk.
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >
                                Catatan Kasbon
                            </label>
                            <textarea
                                v-model="kasbonForm.notes"
                                rows="4"
                                placeholder="Contoh: pelanggan bayar 3 hari lagi"
                                class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-xs text-stone-900 placeholder-stone-400 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:placeholder-slate-500"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t border-stone-200/60 px-6 py-4 dark:border-slate-800/80"
                >
                    <button
                        @click="closeKasbonModal"
                        type="button"
                        class="rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-xs font-bold text-stone-700 transition hover:bg-stone-200 active:scale-[0.98] dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-900"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitKasbon"
                        :disabled="kasbonForm.processing"
                        class="rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-3 text-xs font-bold text-white transition active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50"
                    >
                        {{
                            kasbonForm.processing
                                ? 'Menyimpan Kasbon...'
                                : 'Tutup Sebagai Kasbon'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
