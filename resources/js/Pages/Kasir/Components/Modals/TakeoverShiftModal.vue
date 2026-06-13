<script setup lang="ts">
import {
    cashiers,
    isShiftExpired,
    showTakeoverModal,
    submitTakeover,
    takeoverForm,
    dismissTakeover,
} from '@/Composables/useOrderState';
import { usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, ShieldAlert, X } from '@lucide/vue';

const page = usePage<any>();

const handleTakeoverSubmit = () => {
    submitTakeover();
};
</script>

<template>
    <div
        v-if="showTakeoverModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/85 animate-in fade-in duration-200"
    >
        <div
            class="w-full max-w-lg rounded-3xl border-2 border-stone-200 bg-white p-6 text-stone-900 shadow-2xl dark:border-white/10 dark:bg-slate-950 dark:text-slate-100 animate-in zoom-in-95 duration-200"
        >
            <!-- Modal Header -->
            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl border-2 border-orange-200 bg-orange-50 text-orange-600 dark:border-orange-500/20 dark:bg-orange-950/20 dark:text-orange-400"
                    >
                        <ArrowLeftRight class="h-5 w-5" />
                    </div>
                    <div>
                        <h3
                            class="text-lg font-black tracking-tight text-stone-900 dark:text-white"
                        >
                            {{
                                isShiftExpired
                                    ? 'Waktu Shift Selesai (Serah Terima)'
                                    : 'Serah Terima / Ganti Shift'
                            }}
                        </h3>
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400 mt-0.5"
                        >
                            Laci Kas & Login Kasir Baru
                        </p>
                    </div>
                </div>
                <!-- Close Button -->
                <button
                    @click="dismissTakeover"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 bg-stone-50 text-stone-700 hover:bg-stone-100 transition dark:border-white/10 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800"
                >
                    <X class="h-4 w-4 stroke-[3]" />
                </button>
            </div>

            <!-- Warning if expired -->
            <div
                v-if="isShiftExpired"
                class="mb-6 flex items-start gap-3 rounded-2xl border-2 border-red-300 bg-red-50 p-4 text-xs text-red-900 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400"
            >
                <ShieldAlert class="h-5 w-5 shrink-0 text-red-700 dark:text-red-500" />
                <div>
                    <span class="font-black text-red-950 dark:text-red-300"
                        >Akses Laci Kas Terkunci!</span
                    >
                    <p
                        class="mt-1 leading-relaxed font-bold text-red-900 dark:text-red-400/90"
                    >
                        Jam shift kasir aktif saat ini telah berakhir. Sistem telah mengunci menu transaksi hingga proses serah terima laci kas ke karyawan berikutnya selesai dilakukan.
                    </p>
                </div>
            </div>

            <!-- Form Ganti Shift -->
            <form @submit.prevent="handleTakeoverSubmit" class="space-y-4">
                <!-- nominal laci kas -->
                <div>
                    <label
                        class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                    >
                        Uang Tunai Fisik Akhir di Laci (IDR)
                        <span class="text-red-500">*</span>
                    </label>
                    <div
                        class="relative rounded-2xl border-2 border-stone-200 bg-stone-500/5 transition-all focus-within:border-orange-400 dark:border-white/10 dark:bg-slate-900/60"
                    >
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-stone-500 dark:text-slate-400"
                            >Rp</span
                        >
                        <input
                            v-model.number="takeoverForm.actual_cash"
                            type="number"
                            required
                            min="0"
                            class="w-full border-none bg-transparent py-2.5 pl-11 pr-4 text-xs font-bold text-stone-900 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-slate-600"
                            placeholder="Masukkan jumlah uang fisik di laci"
                        />
                    </div>
                    <p
                        class="mt-1.5 text-[10px] leading-normal font-semibold text-stone-500 dark:text-slate-400"
                    >
                        Hitung nominal uang cash riil di laci kas saat ini. Selisih dengan ekspektasi sistem akan dicatat di laporan rekap kas.
                    </p>
                    <div
                        v-if="takeoverForm.errors.actual_cash"
                        class="mt-1 text-xs font-bold text-red-600 dark:text-red-400"
                    >
                        {{ takeoverForm.errors.actual_cash }}
                    </div>
                </div>

                <!-- Karyawan Penerus -->
                <div>
                    <label
                        class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                    >
                        Pilih Karyawan Shift Berikutnya
                        <span class="text-red-500">*</span>
                    </label>
                    <div
                        class="relative rounded-2xl border-2 border-stone-200 bg-stone-500/5 transition-all focus-within:border-orange-400 dark:border-white/10 dark:bg-slate-900/60"
                    >
                        <select
                            v-model="takeoverForm.next_user_id"
                            required
                            class="w-full select-none appearance-none border-none bg-transparent px-4 py-2.5 text-xs font-bold text-stone-900 focus:outline-none focus:ring-0 dark:text-white"
                        >
                            <option
                                value=""
                                disabled
                                class="bg-white text-stone-400 dark:bg-slate-900 dark:text-slate-500"
                            >
                                -- Pilih Kasir / Karyawan --
                            </option>
                            <option
                                v-for="cashier in cashiers?.filter(
                                    (c: any) => c.id !== page.props.auth?.user?.id,
                                )"
                                :key="cashier.id"
                                :value="cashier.id"
                                class="bg-white text-stone-900 dark:bg-slate-900 dark:text-white font-bold"
                            >
                                {{ cashier.name }} ({{ cashier.role || 'Kasir' }})
                            </option>
                        </select>
                    </div>
                    <div
                        v-if="takeoverForm.errors.next_user_id"
                        class="mt-1 text-xs font-bold text-red-600 dark:text-red-400"
                    >
                        {{ takeoverForm.errors.next_user_id }}
                    </div>
                </div>

                <!-- Password / PIN Verifikasi Kasir Baru -->
                <div>
                    <label
                        class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                    >
                        PIN / Password Verifikasi Kasir Baru
                        <span class="text-red-500">*</span>
                    </label>
                    <div
                        class="relative rounded-2xl border-2 border-stone-200 bg-stone-500/5 transition-all focus-within:border-orange-400 dark:border-white/10 dark:bg-slate-900/60"
                    >
                        <input
                            v-model="takeoverForm.next_password_or_pin"
                            type="password"
                            required
                            class="w-full border-none bg-transparent px-4 py-2.5 text-xs font-bold text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-slate-600"
                            placeholder="Masukkan PIN atau Password kasir baru"
                        />
                    </div>
                    <p
                        class="mt-1.5 text-[10px] leading-normal font-semibold text-stone-500 dark:text-slate-400"
                    >
                        Kasir penerus wajib memasukkan PIN atau Password akun mereka sendiri untuk memvalidasi proses serah terima laci kas secara instan.
                    </p>
                    <div
                        v-if="takeoverForm.errors.next_password_or_pin"
                        class="mt-1 text-xs font-bold text-red-600 dark:text-red-400"
                    >
                        {{ takeoverForm.errors.next_password_or_pin }}
                    </div>
                </div>

                <!-- Catatan Opsional -->
                <div>
                    <label
                        class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                    >
                        Catatan Serah Terima (Opsional)
                    </label>
                    <div
                        class="relative rounded-2xl border-2 border-stone-200 bg-stone-500/5 transition-all focus-within:border-orange-400 dark:border-white/10 dark:bg-slate-900/60"
                    >
                        <textarea
                            v-model="takeoverForm.notes"
                            rows="2"
                            class="w-full resize-none border-none bg-transparent px-4 py-2.5 text-xs font-bold text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-slate-600"
                            placeholder="Tulis catatan jika ada selisih uang, kerusakan alat, atau titipan pesan..."
                        ></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex gap-3">
                    <button
                        type="button"
                        @click="dismissTakeover"
                        class="flex-1 rounded-2xl border-2 py-3 text-xs font-black uppercase tracking-wider transition-all duration-150 active:scale-95"
                        :class="isShiftExpired ? 'bg-sky-600 text-white border-transparent hover:bg-sky-700' : 'bg-transparent border-stone-200 text-stone-700 hover:bg-stone-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5'"
                    >
                        {{ isShiftExpired ? 'Tetap di Shift' : 'Batal' }}
                    </button>
                    <button
                        type="submit"
                        :disabled="takeoverForm.processing"
                        class="flex flex-[2] items-center justify-center gap-2 rounded-2xl border-2 border-transparent bg-orange-500 py-3 text-xs font-black uppercase tracking-wider text-stone-950 transition hover:bg-orange-400 active:scale-95 disabled:pointer-events-none disabled:opacity-50"
                    >
                        <ArrowLeftRight class="h-4 w-4" />
                        <span>{{
                            takeoverForm.processing
                                ? 'Memproses...'
                                : 'Serah Terima & Ambil Alih'
                        }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
