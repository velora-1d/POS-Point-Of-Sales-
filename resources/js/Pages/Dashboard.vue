<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BellRing,
    CheckCircle2,
    Clock,
    Layers,
    ShoppingCart,
    TrendingUp,
    Utensils,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const page = usePage<any>();
const props = defineProps<{
    alerts?: {
        lowStock?: {
            summary?: {
                total?: number;
                products?: number;
                raw_materials?: number;
                critical?: number;
            };
            items?: Array<{
                id: string;
                type: 'product' | 'raw_material';
                name: string;
                context?: string | null;
                current_stock: number;
                minimum_stock: number;
                unit?: string | null;
                route: string;
                severity: number;
            }>;
        };
        expired?: {
            summary?: {
                upcoming?: number;
                today?: number;
                expired?: number;
                critical?: number;
            };
            items?: Array<{
                id: string;
                trackable_type: 'product' | 'raw_material';
                name: string;
                context?: string | null;
                quantity: number;
                expired_at: string;
                days_left: number;
                status: 'upcoming' | 'today' | 'expired';
                route: string;
            }>;
        };
    };
    finance?: {
        can_view?: boolean;
        summary?: {
            revenue?: number;
            orders?: number;
            settled_orders?: number;
            avg_order_value?: number;
            total_discount?: number;
            top_product?: {
                name?: string;
                quantity?: number;
                revenue?: number;
            } | null;
            active_shift?: {
                cashier?: string | null;
                outlet?: string | null;
                opened_at?: string | null;
                opening_cash?: number;
                status?: string;
            } | null;
        };
        breakdowns?: {
            payments?: Array<{
                method: string;
                orders: number;
                amount: number;
            }>;
            sources?: Array<{
                source: string;
                orders: number;
                amount: number;
            }>;
        };
    } | null;
    filters?: {
        outlet_id?: string;
        as_of_date?: string;
    };
    referenceData?: {
        outlets?: Array<{
            id: string;
            name: string;
        }>;
    };
}>();
const user = computed(() => page.props.auth.user);
const menuProgress = computed(() => {
    const progress = page.props.menuProgress ?? {};

    return {
        totalMenus: progress.totalMenus ?? 62,
        readyCount: progress.readyCount ?? 0,
        progressPercentage: progress.progressPercentage ?? 0,
    };
});

// Calculate stats for menu tracking
const totalMenu = computed(() => menuProgress.value.totalMenus);
const readyMenuCount = computed(() => menuProgress.value.readyCount);
const progressPercentage = computed(() => menuProgress.value.progressPercentage);
const lowStockSummary = computed(() => props.alerts?.lowStock?.summary ?? {
    total: 0,
    products: 0,
    raw_materials: 0,
    critical: 0,
});
const lowStockItems = computed(() => props.alerts?.lowStock?.items ?? []);
const expiredSummary = computed(() => props.alerts?.expired?.summary ?? {
    upcoming: 0,
    today: 0,
    expired: 0,
    critical: 0,
});
const expiredItems = computed(() => props.alerts?.expired?.items ?? []);
const canViewFinance = computed(() => Boolean(props.finance?.can_view));
const financeSummary = computed(() => props.finance?.summary ?? {});
const paymentBreakdowns = computed(() => props.finance?.breakdowns?.payments ?? []);
const sourceBreakdowns = computed(() => props.finance?.breakdowns?.sources ?? []);
const financeOutletOptions = computed(() => props.referenceData?.outlets ?? []);
const canChooseFinanceOutlet = computed(() => financeOutletOptions.value.length > 1);
const financeOutletFilter = ref(props.filters?.outlet_id || '');

const formatPrice = (value: number | string | null | undefined) => {
    const amount = Number(value || 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDateTime = (value?: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const paymentMethodLabel = (method: string) => {
    switch (method) {
        case 'cash':
            return 'Cash';
        case 'qris':
            return 'QRIS';
        case 'debit':
            return 'Debit';
        case 'ewallet':
            return 'E-Wallet';
        case 'kasbon':
            return 'Kasbon';
        default:
            return method;
    }
};

const sourceLabel = (source: string) => {
    switch (source) {
        case 'kasir':
            return 'Kasir';
        case 'qr_meja':
            return 'QR Meja';
        case 'gofood':
            return 'GoFood';
        case 'grabfood':
            return 'GrabFood';
        default:
            return source;
    }
};

const submitFinanceFilters = () => {
    router.get(
        route('dashboard'),
        {
            outlet_id: financeOutletFilter.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};
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
                        <Link
                            :href="route('stock-alerts.index')"
                            class="inline-flex items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/10 px-5 py-2.5 text-xs font-semibold text-amber-300 transition duration-200 hover:bg-amber-500/15 active:scale-[0.98]"
                        >
                            <AlertTriangle class="h-4 w-4" />
                            <span>Alert Stok Menipis</span>
                        </Link>
                        <Link
                            :href="route('expired-tracking.index')"
                            class="inline-flex items-center gap-2 rounded-xl border border-rose-500/20 bg-rose-500/10 px-5 py-2.5 text-xs font-semibold text-rose-300 transition duration-200 hover:bg-rose-500/15 active:scale-[0.98]"
                        >
                            <BellRing class="h-4 w-4" />
                            <span>Reminder Expired</span>
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

            <section
                v-if="canViewFinance"
                class="rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl shadow-slate-950/20 lg:p-8"
            >
                <div class="flex flex-col gap-4 border-b border-slate-800/60 pb-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h4 class="flex items-center gap-2 text-base font-bold text-white">
                            <TrendingUp class="h-5 w-5 text-emerald-400" />
                            Dashboard Keuangan Hari Ini
                        </h4>
                        <p class="mt-1 text-xs text-slate-400">
                            Ringkasan revenue, order, rata-rata transaksi, produk terlaris, dan shift aktif per {{ props.filters?.as_of_date || '-' }}.
                        </p>
                    </div>

                    <div v-if="canChooseFinanceOutlet" class="flex flex-wrap items-end gap-3">
                        <label class="block">
                            <span class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                Outlet
                            </span>
                            <select
                                v-model="financeOutletFilter"
                                class="w-56 rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua outlet owner</option>
                                <option
                                    v-for="outlet in financeOutletOptions"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>
                        <button
                            type="button"
                            class="rounded-xl bg-orange-500 px-4 py-2.5 text-xs font-bold text-slate-950 transition hover:bg-orange-400"
                            @click="submitFinanceFilters"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <article class="rounded-xl border border-emerald-500/15 bg-emerald-500/8 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-300">
                            Total Revenue
                        </p>
                        <p class="mt-2 text-2xl font-black text-white">
                            {{ formatPrice(financeSummary.revenue) }}
                        </p>
                        <p class="mt-2 text-xs text-emerald-100/80">
                            Diskon hari ini {{ formatPrice(financeSummary.total_discount) }}
                        </p>
                    </article>

                    <article class="rounded-xl border border-slate-800 bg-slate-950/50 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            Total Order
                        </p>
                        <p class="mt-2 text-2xl font-black text-white">
                            {{ financeSummary.orders ?? 0 }}
                        </p>
                        <p class="mt-2 text-xs text-slate-400">
                            {{ financeSummary.settled_orders ?? 0 }} order sudah settle
                        </p>
                    </article>

                    <article class="rounded-xl border border-slate-800 bg-slate-950/50 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            Average Ticket
                        </p>
                        <p class="mt-2 text-2xl font-black text-white">
                            {{ formatPrice(financeSummary.avg_order_value) }}
                        </p>
                        <p class="mt-2 text-xs text-slate-400">
                            Rata-rata per order yang sudah dibayar
                        </p>
                    </article>

                    <article class="rounded-xl border border-sky-500/15 bg-sky-500/8 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-sky-300">
                            Produk Terlaris
                        </p>
                        <p class="mt-2 text-lg font-black text-white">
                            {{ financeSummary.top_product?.name || 'Belum ada data' }}
                        </p>
                        <p class="mt-2 text-xs text-sky-100/80">
                            {{ financeSummary.top_product?.quantity || 0 }} item • {{ formatPrice(financeSummary.top_product?.revenue) }}
                        </p>
                    </article>

                    <article class="rounded-xl border border-orange-500/15 bg-orange-500/8 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Shift Aktif
                        </p>
                        <p class="mt-2 text-lg font-black text-white">
                            {{ financeSummary.active_shift?.cashier || 'Tidak ada shift aktif' }}
                        </p>
                        <p class="mt-2 text-xs text-orange-100/80">
                            {{ financeSummary.active_shift?.opened_at ? `Buka ${formatDateTime(financeSummary.active_shift?.opened_at)}` : 'Shift belum dibuka hari ini' }}
                        </p>
                    </article>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <section class="rounded-xl border border-slate-800 bg-slate-950/50 p-5">
                        <h5 class="text-sm font-bold text-white">Breakdown Metode Bayar</h5>
                        <div v-if="!paymentBreakdowns.length" class="mt-4 text-sm text-slate-400">
                            Belum ada pembayaran tercatat hari ini.
                        </div>
                        <div v-else class="mt-4 space-y-3">
                            <div
                                v-for="item in paymentBreakdowns"
                                :key="item.method"
                                class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/70 px-4 py-3"
                            >
                                <div>
                                    <p class="text-sm font-bold text-white">{{ paymentMethodLabel(item.method) }}</p>
                                    <p class="text-xs text-slate-500">{{ item.orders }} order</p>
                                </div>
                                <p class="text-sm font-black text-emerald-300">
                                    {{ formatPrice(item.amount) }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-800 bg-slate-950/50 p-5">
                        <h5 class="text-sm font-bold text-white">Breakdown Channel</h5>
                        <div v-if="!sourceBreakdowns.length" class="mt-4 text-sm text-slate-400">
                            Belum ada channel penjualan tercatat hari ini.
                        </div>
                        <div v-else class="mt-4 space-y-3">
                            <div
                                v-for="item in sourceBreakdowns"
                                :key="item.source"
                                class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/70 px-4 py-3"
                            >
                                <div>
                                    <p class="text-sm font-bold text-white">{{ sourceLabel(item.source) }}</p>
                                    <p class="text-xs text-slate-500">{{ item.orders }} order</p>
                                </div>
                                <p class="text-sm font-black text-sky-300">
                                    {{ formatPrice(item.amount) }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <div
                v-else
                class="rounded-2xl border border-slate-800/80 bg-slate-900 p-5 text-sm text-slate-400 shadow-xl shadow-slate-950/20"
            >
                Ringkasan keuangan harian tersedia untuk role supervisor dan owner.
            </div>

            <div
                class="grid grid-cols-1 gap-6 lg:grid-cols-[1.2fr_0.8fr]"
            >
                <section
                    class="rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl shadow-slate-950/20"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h4 class="flex items-center gap-2 text-base font-bold text-white">
                                <AlertTriangle class="h-5 w-5 text-amber-400" />
                                Alert Stok Menipis
                            </h4>
                            <p class="mt-1 text-xs text-slate-400">
                                Alert live untuk produk jadi dan bahan baku yang sudah menyentuh minimum stock.
                            </p>
                        </div>
                        <Link
                            :href="route('stock-alerts.index')"
                            class="text-xs font-semibold text-orange-300 transition hover:text-orange-200"
                        >
                            Lihat semua
                        </Link>
                    </div>

                    <div v-if="!lowStockItems.length" class="mt-6 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-4 text-sm text-emerald-300">
                        Belum ada alert stok menipis. Semua stok utama masih aman.
                    </div>

                    <div v-else class="mt-6 space-y-3">
                        <article
                            v-for="item in lowStockItems"
                            :key="`${item.type}-${item.id}`"
                            class="flex flex-col gap-3 rounded-xl border border-slate-800 bg-slate-950/50 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-white">{{ item.name }}</p>
                                    <span
                                        :class="[
                                            'rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                            item.severity >= 2
                                                ? 'border-rose-500/20 bg-rose-500/10 text-rose-300'
                                                : 'border-amber-500/20 bg-amber-500/10 text-amber-300',
                                        ]"
                                    >
                                        {{ item.severity >= 2 ? 'Kritis' : 'Menipis' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ item.type === 'product' ? 'Produk jadi' : 'Bahan baku' }}
                                    <span v-if="item.context">• {{ item.context }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <p class="text-xs font-semibold text-slate-300">
                                    {{ item.current_stock }}/{{ item.minimum_stock }} {{ item.unit || 'pcs' }}
                                </p>
                                <Link
                                    :href="route(item.route)"
                                    class="rounded-lg border border-orange-500/20 bg-orange-500/10 px-3 py-2 text-xs font-bold text-orange-300"
                                >
                                    Tindak lanjuti
                                </Link>
                            </div>
                        </article>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl shadow-slate-950/20"
                >
                    <h4 class="text-base font-bold text-white">
                        Ringkasan Alert
                    </h4>
                    <div class="mt-6 space-y-4">
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Total Alert
                            </p>
                            <p class="mt-2 text-3xl font-black text-white">
                                {{ lowStockSummary.total }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-amber-500/15 bg-amber-500/8 p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-300">
                                    Produk Jadi
                                </p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ lowStockSummary.products }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-sky-500/15 bg-sky-500/8 p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-sky-300">
                                    Bahan Baku
                                </p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ lowStockSummary.raw_materials }}
                                </p>
                            </div>
                        </div>
                        <div class="rounded-xl border border-rose-500/15 bg-rose-500/8 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-rose-300">
                                Kritis / Habis
                            </p>
                            <p class="mt-2 text-2xl font-black text-white">
                                {{ lowStockSummary.critical }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <div
                class="grid grid-cols-1 gap-6 lg:grid-cols-[1.2fr_0.8fr]"
            >
                <section
                    class="rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl shadow-slate-950/20"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h4 class="flex items-center gap-2 text-base font-bold text-white">
                                <BellRing class="h-5 w-5 text-rose-400" />
                                Reminder Expired
                            </h4>
                            <p class="mt-1 text-xs text-slate-400">
                                Daftar produk jadi dan bahan baku yang mendekati atau melewati tanggal expired.
                            </p>
                        </div>
                        <Link
                            :href="route('expired-tracking.index')"
                            class="text-xs font-semibold text-orange-300 transition hover:text-orange-200"
                        >
                            Lihat semua
                        </Link>
                    </div>

                    <div v-if="!expiredItems.length" class="mt-6 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-4 text-sm text-emerald-300">
                        Belum ada reminder expired dalam 7 hari ke depan.
                    </div>

                    <div v-else class="mt-6 space-y-3">
                        <article
                            v-for="item in expiredItems"
                            :key="item.id"
                            class="flex flex-col gap-3 rounded-xl border border-slate-800 bg-slate-950/50 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-white">{{ item.name }}</p>
                                    <span
                                        :class="[
                                            'rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                            item.status === 'expired'
                                                ? 'border-rose-500/20 bg-rose-500/10 text-rose-300'
                                                : item.status === 'today'
                                                  ? 'border-orange-500/20 bg-orange-500/10 text-orange-300'
                                                  : 'border-amber-500/20 bg-amber-500/10 text-amber-300',
                                        ]"
                                    >
                                        {{
                                            item.status === 'expired'
                                                ? `Lewat ${Math.abs(item.days_left)} hari`
                                                : item.status === 'today'
                                                  ? 'Expired hari ini'
                                                  : `H-${item.days_left}`
                                        }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ item.trackable_type === 'product' ? 'Produk jadi' : 'Bahan baku' }}
                                    <span v-if="item.context">• {{ item.context }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <p class="text-xs font-semibold text-slate-300">
                                    Qty batch {{ item.quantity }}
                                </p>
                                <Link
                                    :href="route(item.route)"
                                    class="rounded-lg border border-orange-500/20 bg-orange-500/10 px-3 py-2 text-xs font-bold text-orange-300"
                                >
                                    Tindak lanjuti
                                </Link>
                            </div>
                        </article>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl shadow-slate-950/20"
                >
                    <h4 class="text-base font-bold text-white">
                        Ringkasan Expired
                    </h4>
                    <div class="mt-6 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-amber-500/15 bg-amber-500/8 p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-300">
                                    Upcoming
                                </p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ expiredSummary.upcoming }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-orange-500/15 bg-orange-500/8 p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-orange-300">
                                    Hari Ini
                                </p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ expiredSummary.today }}
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-rose-500/15 bg-rose-500/8 p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-rose-300">
                                    Sudah Expired
                                </p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ expiredSummary.expired }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    Butuh Tindakan
                                </p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ expiredSummary.critical }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
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
