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
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-stone-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/80"
        >
            <div
                class="w-full max-w-xl rounded-3xl border-2 border-stone-200 bg-white text-stone-900 shadow-2xl dark:border-white/10 dark:bg-slate-950 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-200"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b-2 border-stone-200 px-6 py-5 dark:border-white/5"
                >
                    <div>
                        <span
                            class="rounded-full border-2 border-amber-200 bg-amber-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-amber-700 dark:border-amber-500/20 dark:bg-amber-950/20 dark:text-amber-300"
                        >
                            Kasbon / Piutang
                        </span>
                        <h3
                            class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                        >
                            {{ kasbonTargetOrder.order_number }}
                        </h3>
                        <p
                            class="mt-1 text-xs font-semibold text-stone-500 dark:text-slate-400"
                        >
                            Tutup transaksi sebagai piutang pelanggan dan lanjutkan cicilan dari menu transaksi.
                        </p>
                    </div>
                    <button
                        @click="closeKasbonModal"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-stone-50 text-stone-700 hover:bg-stone-100 transition dark:border-white/10 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800"
                    >
                        <X class="h-4 w-4 stroke-[3]" />
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div
                        class="rounded-2xl border-2 border-stone-200 bg-stone-50 p-4 dark:border-white/5 dark:bg-slate-900/40 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-600 dark:text-orange-400"
                                >
                                    Nama Pelanggan
                                </p>
                                <p
                                    class="mt-2 text-sm font-black text-stone-900 dark:text-white"
                                >
                                    {{
                                        kasbonTargetOrder.customer?.name ||
                                        'Customer tidak ditemukan'
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-500 dark:text-slate-400 font-semibold"
                                >
                                    {{
                                        kasbonTargetOrder.customer?.phone ||
                                        'Tanpa nomor HP'
                                    }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-600 dark:text-orange-400"
                                >
                                    Sisa Tagihan
                                </p>
                                <p
                                    class="mt-2 text-lg font-black text-amber-600 dark:text-amber-500"
                                >
                                    {{
                                        formatPrice(
                                            Number(kasbonTargetOrder.total_amount) -
                                                Number(kasbonTargetOrder.paid_amount || 0)
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                            >
                                Jatuh Tempo
                            </label>
                            <input
                                v-model="kasbonForm.due_date"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                            <p
                                class="mt-2 text-[11px] font-semibold text-stone-500 dark:text-slate-400"
                            >
                                Opsional. Jika diisi, tanggal akan dipakai di daftar kasbon dan struk.
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                            >
                                Catatan Kasbon
                            </label>
                            <textarea
                                v-model="kasbonForm.notes"
                                rows="3"
                                placeholder="Contoh: pelanggan bayar 3 hari lagi"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t-2 border-stone-200 px-6 py-4 dark:border-white/5 bg-stone-50 dark:bg-slate-900/80"
                >
                    <button
                        @click="closeKasbonModal"
                        type="button"
                        class="rounded-2xl border-2 border-stone-200 bg-transparent px-4 py-2.5 text-xs font-bold text-stone-700 transition hover:bg-stone-100 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5 active:scale-95"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitKasbon"
                        :disabled="kasbonForm.processing"
                        class="rounded-2xl border-2 border-transparent bg-orange-500 px-5 py-2.5 text-xs font-black uppercase tracking-wider text-stone-950 transition hover:bg-orange-400 active:scale-95 disabled:pointer-events-none disabled:opacity-50"
                    >
                        {{
                            kasbonForm.processing
                                ? 'Menyimpan...'
                                : 'Tutup Sebagai Kasbon'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
