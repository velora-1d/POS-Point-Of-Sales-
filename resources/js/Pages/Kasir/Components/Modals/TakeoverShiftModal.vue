<script setup lang="ts">
import {
    cashiers,
    isShiftExpired,
    showTakeoverModal,
    submitTakeover,
    takeoverForm,
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-white p-4 backdrop-blur-sm dark:bg-slate-950/85"
    >
        <div
            class="w-full max-w-lg rounded-3xl border border-stone-200 bg-white p-6 text-stone-900 shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
        >
            <!-- Modal Header -->
            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-orange-500/20 bg-orange-500/5 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400"
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
                            class="text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                        >
                            Laci Kas & Login Kasir Baru
                        </p>
                    </div>
                </div>
                <!-- Tampilkan tombol close HANYA jika manual (tidak expired) -->
                <button
                    v-if="!isShiftExpired"
                    @click="showTakeoverModal = false"
                    class="rounded-lg p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-900 dark:bg-slate-800 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-white"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Warning if expired -->
            <div
                v-if="isShiftExpired"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-red-500/20 bg-red-500/5 p-4 text-xs text-red-600 dark:bg-red-500/10 dark:text-red-400"
            >
                <ShieldAlert class="h-5 w-5 shrink-0 text-red-500" />
                <div>
                    <span class="font-extrabold text-red-700 dark:text-red-300"
                        >Akses Laci Kas Terkunci!</span
                    >
                    <p
                        class="mt-1 leading-relaxed text-red-600/90 dark:text-red-400/90"
                    >
                        Jam shift kasir aktif saat ini telah berakhir. Sistem
                        telah mengunci menu transaksi hingga proses serah terima
                        laci kas ke karyawan berikutnya selesai dilakukan.
                    </p>
                </div>
            </div>

            <!-- Form Ganti Shift -->
            <form @submit.prevent="handleTakeoverSubmit" class="space-y-4">
                <!-- nominal laci kas -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold text-stone-700 dark:text-slate-300"
                    >
                        Uang Tunai Fisik Akhir di Laci (IDR)
                        <span class="text-red-500">*</span>
                    </label>
                    <div
                        class="relative rounded-2xl border border-stone-200 bg-stone-50 transition-all focus-within:border-orange-500/50 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-stone-400 dark:text-slate-500"
                            >Rp</span
                        >
                        <input
                            v-model.number="takeoverForm.actual_cash"
                            type="number"
                            required
                            min="0"
                            class="w-full border-none bg-transparent py-3.5 pl-11 pr-4 text-sm text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-slate-600"
                            placeholder="Masukkan jumlah uang fisik di laci"
                        />
                    </div>
                    <p
                        class="mt-1 text-[10px] leading-normal text-stone-500 dark:text-slate-400"
                    >
                        Hitung nominal uang cash riil di laci kas saat ini.
                        Selisih dengan ekspektasi sistem akan dicatat di laporan
                        rekap kas.
                    </p>
                    <div
                        v-if="takeoverForm.errors.actual_cash"
                        class="mt-1 text-xs text-red-400"
                    >
                        {{ takeoverForm.errors.actual_cash }}
                    </div>
                </div>

                <!-- Karyawan Penerus -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold text-stone-700 dark:text-slate-300"
                    >
                        Pilih Karyawan Shift Berikutnya
                        <span class="text-red-500">*</span>
                    </label>
                    <div
                        class="relative rounded-2xl border border-stone-200 bg-stone-50 transition-all focus-within:border-orange-500/50 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <select
                            v-model="takeoverForm.next_user_id"
                            required
                            class="w-full select-none appearance-none border-none bg-transparent px-4 py-3.5 text-sm text-stone-900 focus:outline-none focus:ring-0 dark:text-white"
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
                                    (c: any) =>
                                        c.id !== page.props.auth?.user?.id,
                                )"
                                :key="cashier.id"
                                :value="cashier.id"
                                class="bg-white text-stone-900 dark:bg-slate-900 dark:text-white"
                            >
                                {{ cashier.name }} ({{ cashier.role }})
                            </option>
                        </select>
                    </div>
                    <div
                        v-if="takeoverForm.errors.next_user_id"
                        class="mt-1 text-xs text-red-400"
                    >
                        {{ takeoverForm.errors.next_user_id }}
                    </div>
                </div>

                <!-- Password / PIN Verifikasi Kasir Baru -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold text-stone-700 dark:text-slate-300"
                    >
                        PIN / Password Verifikasi Kasir Baru
                        <span class="text-red-500">*</span>
                    </label>
                    <div
                        class="relative rounded-2xl border border-stone-200 bg-stone-50 transition-all focus-within:border-orange-500/50 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <input
                            v-model="takeoverForm.next_password_or_pin"
                            type="password"
                            required
                            class="w-full border-none bg-transparent px-4 py-3.5 text-sm text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-slate-600"
                            placeholder="Masukkan PIN atau Password kasir baru"
                        />
                    </div>
                    <p
                        class="mt-1 text-[10px] leading-normal text-stone-500 dark:text-slate-400"
                    >
                        Kasir penerus wajib memasukkan PIN atau Password akun
                        mereka sendiri untuk memvalidasi proses ambil alih shift
                        secara instan.
                    </p>
                    <div
                        v-if="takeoverForm.errors.next_password_or_pin"
                        class="mt-1 text-xs text-red-400"
                    >
                        {{ takeoverForm.errors.next_password_or_pin }}
                    </div>
                </div>

                <!-- Catatan Opsional -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold text-stone-700 dark:text-slate-300"
                    >
                        Catatan Serah Terima (Opsional)
                    </label>
                    <div
                        class="relative rounded-2xl border border-stone-200 bg-stone-50 transition-all focus-within:border-orange-500/50 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <textarea
                            v-model="takeoverForm.notes"
                            class="min-h-[70px] w-full resize-none border-none bg-transparent px-4 py-3 text-sm text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-slate-600"
                            placeholder="Tulis catatan jika ada selisih uang, kerusakan alat, atau titipan pesan..."
                        ></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex gap-3">
                    <button
                        v-if="!isShiftExpired"
                        type="button"
                        @click="showTakeoverModal = false"
                        class="flex-1 rounded-2xl border border-stone-200 bg-stone-100 py-3.5 text-sm font-bold text-stone-700 transition-all hover:bg-stone-200 hover:text-stone-950 dark:border-slate-800 dark:bg-slate-800 dark:bg-transparent dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="takeoverForm.processing"
                        class="flex flex-[2] items-center justify-center gap-2 rounded-2xl bg-orange-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-orange-500/20 transition-all hover:bg-orange-600 hover:shadow-orange-600/35 disabled:opacity-50"
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
