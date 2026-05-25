<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    Layers,
    ShoppingCart,
    TrendingUp,
    Utensils,
} from '@lucide/vue';
import { computed } from 'vue';

const page = usePage<any>();
const user = computed(() => page.props.auth.user);

// Calculate stats for menu tracking
const totalMenu = 62;
const readyMenuCount = 9; // Auth, menu 1-6 di Kasir, dan menu 13 di Kitchen Display
const progressPercentage = computed(() => {
    return Math.round((readyMenuCount / totalMenu) * 100);
});
</script>

<template>
    <Head title="Dashboard Utama" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
            >
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Dashboard Utama
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Selamat datang kembali,
                        <span class="font-semibold text-orange-400">{{
                            user?.name
                        }}</span>
                        &bull; Cabang:
                        <span class="text-slate-300">{{
                            user?.outlet || 'Tidak Terikat Outlet'
                        }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 animate-ping rounded-full bg-emerald-500"
                    ></span>
                    <span
                        class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-400"
                    >
                        Sistem Online
                    </span>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Top Section: Welcome Banner & Quick Menu Tracker -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Welcome Banner Card -->
                <div
                    class="relative flex min-h-[220px] flex-col justify-between overflow-hidden rounded-2xl border border-slate-800/80 bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 p-6 shadow-xl shadow-slate-950/20 lg:col-span-2 lg:p-8"
                >
                    <!-- Glow decoration -->
                    <div
                        class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-orange-500/10 blur-3xl"
                    ></div>

                    <div>
                        <span
                            class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-orange-500/20 bg-orange-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-orange-400"
                        >
                            🔥 POS Mentai Suite v1.0
                        </span>
                        <h3
                            class="text-2xl font-black leading-tight text-white lg:text-3xl"
                        >
                            Kelola Operasional <br />
                            Restoran Lebih Efisien
                        </h3>
                        <p
                            class="mt-2 max-w-md text-sm leading-relaxed text-slate-400"
                        >
                            Akses cepat flow operasional dari meja, buat order,
                            antrian dapur, sampai tracking progress sistem
                            langsung dari sidebar kiri.
                        </p>
                    </div>

                    <div
                        class="mt-6 flex flex-wrap items-center gap-4 border-t border-slate-800/50 pt-4"
                    >
                        <Link
                            :href="route('kasir.order')"
                            class="to-red-650 hover:to-red-750 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-orange-500/20 transition duration-200 hover:from-orange-600 active:scale-[0.98]"
                        >
                            <ShoppingCart class="h-4 w-4" />
                            <span>Buka Buat Order Baru</span>
                        </Link>
                        <Link
                            :href="route('kitchen.display')"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-700/60 bg-slate-800 px-5 py-2.5 text-xs font-semibold text-slate-200 transition duration-200 hover:bg-slate-700 active:scale-[0.98]"
                        >
                            <Utensils class="h-4 w-4 text-orange-400" />
                            <span>Kitchen Display</span>
                        </Link>
                    </div>
                </div>

                <!-- Progress Tracker Card -->
                <div
                    class="relative flex flex-col justify-between rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl shadow-slate-950/20 lg:p-8"
                >
                    <div
                        class="pointer-events-none absolute -bottom-6 -right-6 h-32 w-32 rounded-full bg-orange-600/5 blur-2xl"
                    ></div>

                    <div>
                        <h4
                            class="text-sm font-bold uppercase tracking-wider text-slate-400"
                        >
                            Progress Pengerjaan Menu
                        </h4>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span
                                class="text-4xl font-black leading-none text-white lg:text-5xl"
                            >
                                {{ progressPercentage }}%
                            </span>
                            <span class="text-xs text-slate-500">
                                Selesai
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div
                            class="border-slate-850 mt-4 h-2.5 w-full overflow-hidden rounded-full border bg-slate-950"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-orange-500 to-emerald-500 transition-all duration-500"
                                :style="{ width: `${progressPercentage}%` }"
                            ></div>
                        </div>

                        <div
                            class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-800/50 pt-4 text-xs"
                        >
                            <div>
                                <p
                                    class="font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Ready / Aktif
                                </p>
                                <p
                                    class="mt-1 text-lg font-bold text-emerald-400"
                                >
                                    {{ readyMenuCount }} Menu
                                </p>
                            </div>
                            <div>
                                <p
                                    class="font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Coming Soon
                                </p>
                                <p
                                    class="mt-1 text-lg font-bold text-slate-400"
                                >
                                    {{ totalMenu - readyMenuCount }} Menu
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-[10px] text-slate-500">
                        * Total 62 menu berdasarkan dokumen analisis RBAC
                    </div>
                </div>
            </div>

            <!-- Stats Grid Section (Mockup / Preview) -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <!-- Stat Card 1 -->
                <div
                    class="flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-900 p-5 shadow-lg shadow-slate-950/10"
                >
                    <div class="space-y-1">
                        <p
                            class="text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Omset Hari Ini
                        </p>
                        <p class="text-lg font-extrabold text-white lg:text-xl">
                            Rp 4.250.000
                        </p>
                        <span
                            class="inline-flex items-center gap-0.5 rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-400"
                        >
                            <TrendingUp class="h-3 w-3" /> +12.4%
                        </span>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10 text-orange-400"
                    >
                        <TrendingUp class="h-5 w-5" />
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div
                    class="flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-900 p-5 shadow-lg shadow-slate-950/10"
                >
                    <div class="space-y-1">
                        <p
                            class="text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Order Aktif
                        </p>
                        <p class="text-lg font-extrabold text-white lg:text-xl">
                            12 Pesanan
                        </p>
                        <span class="text-[10px] text-slate-400">
                            Sedang diproses dapur
                        </span>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10 text-orange-400"
                    >
                        <ShoppingCart class="h-5 w-5" />
                    </div>
                </div>

                <!-- Stat Card 3 -->
                <div
                    class="flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-900 p-5 shadow-lg shadow-slate-950/10"
                >
                    <div class="space-y-1">
                        <p
                            class="text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Meja Terisi
                        </p>
                        <p class="text-lg font-extrabold text-white lg:text-xl">
                            8 / 20 Meja
                        </p>
                        <span class="text-[10px] text-slate-400">
                            Kapasitas: 40%
                        </span>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10 text-orange-400"
                    >
                        <Layers class="h-5 w-5" />
                    </div>
                </div>

                <!-- Stat Card 4 -->
                <div
                    class="flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-900 p-5 shadow-lg shadow-slate-950/10"
                >
                    <div class="space-y-1">
                        <p
                            class="text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Status Shift
                        </p>
                        <p
                            class="text-lg font-extrabold text-emerald-400 lg:text-xl"
                        >
                            Shift Pagi
                        </p>
                        <span class="text-[10px] text-slate-400">
                            Kasir: {{ user?.name }}
                        </span>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg border border-emerald-500/20 bg-emerald-500/10 text-emerald-400"
                    >
                        <Clock class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <!-- Lower Section: Implemented Modules & Coming Soon Highlight -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Implemented Features Card -->
                <div
                    class="rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl shadow-slate-950/20 lg:p-8"
                >
                    <h4
                        class="mb-6 flex items-center gap-2 text-base font-bold text-white"
                    >
                        <CheckCircle2 class="h-5 w-5 text-emerald-400" />
                        <span>Modul Terimplementasi (Ready)</span>
                    </h4>
                    <div class="space-y-4">
                        <div
                            class="border-slate-850 flex items-start gap-3 rounded-xl border bg-slate-950/50 p-3"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500/20 bg-emerald-500/10 text-xs font-bold text-emerald-400"
                                >✓</span
                            >
                            <div>
                                <p class="text-xs font-bold text-slate-200">
                                    Sistem Autentikasi Pengguna & Sesi
                                </p>
                                <p class="text-slate-450 mt-0.5 text-xs">
                                    Login multi-role, logout aman, logout
                                    session di Neon PostgreSQL, override
                                    remember token, and penambahan penampil
                                    password (eye icon toggle).
                                </p>
                            </div>
                        </div>

                        <div
                            class="border-slate-850 flex items-start gap-3 rounded-xl border bg-slate-950/50 p-3"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500/20 bg-emerald-500/10 text-xs font-bold text-emerald-400"
                                >✓</span
                            >
                            <div>
                                <p class="text-xs font-bold text-slate-200">
                                    Kategori 1: Order & Transaksi (Dasar)
                                </p>
                                <p class="text-slate-450 mt-0.5 text-xs">
                                    Menu *Buat Order Baru*, *Daftar Order
                                    Aktif*, dan *Detail Order per Meja* sudah
                                    berjalan dalam satu flow di rute `/order`.
                                </p>
                            </div>
                        </div>

                        <div
                            class="border-slate-850 flex items-start gap-3 rounded-xl border bg-slate-950/50 p-3"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500/20 bg-emerald-500/10 text-xs font-bold text-emerald-400"
                                >✓</span
                            >
                            <div>
                                <p class="text-xs font-bold text-slate-200">
                                    Kategori 2: Kitchen Display (Dasar)
                                </p>
                                <p class="text-slate-450 mt-0.5 text-xs">
                                    Menu *Antrian Order Real-time* sudah terarah
                                    ke `/kitchen` dan `/bar` display berdasarkan
                                    route yang sesuai.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Development Goals Card -->
                <div
                    class="flex flex-col justify-between rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl shadow-slate-950/20 lg:p-8"
                >
                    <div>
                        <h4
                            class="mb-6 flex items-center gap-2 text-base font-bold text-white"
                        >
                            <Clock
                                class="animate-pulse-subtle h-5 w-5 text-orange-400"
                            />
                            <span>Pengembangan Selanjutnya (Coming Soon)</span>
                        </h4>
                        <p class="text-xs leading-relaxed text-slate-400">
                            Kami sedang mengerjakan modul-modul berikut untuk
                            mengintegrasikan fungsionalitas penuh restoran:
                        </p>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div
                                class="border-slate-850 flex items-center gap-2 rounded-lg border bg-slate-950/30 p-2.5"
                            >
                                <div
                                    class="h-2 w-2 rounded-full bg-orange-400"
                                ></div>
                                <span class="text-xs text-slate-300"
                                    >Sistem Integrasi Pembayaran</span
                                >
                            </div>
                            <div
                                class="border-slate-850 flex items-center gap-2 rounded-lg border bg-slate-950/30 p-2.5"
                            >
                                <div
                                    class="h-2 w-2 rounded-full bg-orange-400"
                                ></div>
                                <span class="text-xs text-slate-300"
                                    >Visual Layout Meja</span
                                >
                            </div>
                            <div
                                class="border-slate-850 flex items-center gap-2 rounded-lg border bg-slate-950/30 p-2.5"
                            >
                                <div
                                    class="h-2 w-2 rounded-full bg-orange-400"
                                ></div>
                                <span class="text-xs text-slate-300"
                                    >Loyalty & Membership</span
                                >
                            </div>
                            <div
                                class="border-slate-850 flex items-center gap-2 rounded-lg border bg-slate-950/30 p-2.5"
                            >
                                <div
                                    class="h-2 w-2 rounded-full bg-orange-400"
                                ></div>
                                <span class="text-xs text-slate-300"
                                    >HPP & Resep Otomatis</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-6 flex items-center gap-3 rounded-xl border border-orange-500/10 bg-orange-500/5 p-4"
                    >
                        <span class="text-xl">💡</span>
                        <p class="text-slate-350 text-[11px] leading-normal">
                            Menu sidebar kiri akan terus memutakhirkan statusnya
                            dari
                            <span class="font-semibold text-slate-400"
                                >Soon</span
                            >
                            menjadi
                            <span class="font-semibold text-emerald-400"
                                >Ready</span
                            >
                            secara dinamis seiring pengerjaan modul.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
