<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    CalendarRange,
    CreditCard,
    ReceiptText,
    Search,
    ShoppingBag,
    Wallet,
    ChevronDown,
    ChevronUp,
    Filter,
    RefreshCw,
    TrendingUp,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface OptionRow {
    value: string;
    label: string;
}

interface OutletOption {
    id: string;
    name: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface TransactionRow {
    id: string;
    order_number: string;
    ordered_at?: string | null;
    outlet?: {
        id: string;
        name: string;
    } | null;
    cashier?: {
        id: string;
        name: string;
    } | null;
    source: string;
    type: string;
    payment_method?: string | null;
    items_sold: number;
    subtotal: number;
    discount_amount: number;
    total_amount: number;
}

const page = usePage<any>();
const props = defineProps<{
    summary: {
        total_revenue: number;
        total_orders: number;
        total_discount: number;
        total_items_sold: number;
        average_ticket: number;
        largest_transaction: number;
    };
    trend: Array<{
        date: string;
        orders: number;
        revenue: number;
    }>;
    breakdowns: {
        payments: Array<{
            method: string;
            orders: number;
            amount: number;
        }>;
        sources: Array<{
            source: string;
            orders: number;
            amount: number;
        }>;
    };
    transactions: {
        data: TransactionRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        start_date: string;
        end_date: string;
        outlet_id?: string;
        source?: string;
        payment_method?: string;
        search?: string;
        per_page?: number;
    };
    referenceData: {
        outlets: OutletOption[];
        sources: OptionRow[];
        paymentMethods: OptionRow[];
    };
    scope: {
        viewer_role?: string | null;
        outlet_id?: string | null;
        date_basis?: string;
    };
}>();

const user = computed(() => page.props.auth.user);
const startDateFilter = ref(props.filters.start_date);
const endDateFilter = ref(props.filters.end_date);
const outletFilter = ref(props.filters.outlet_id || '');
const sourceFilter = ref(props.filters.source || '');
const paymentMethodFilter = ref(props.filters.payment_method || '');
const searchFilter = ref(props.filters.search || '');
const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);

const isFilterExpanded = ref(false);

const selectedOutletLabel = computed(() => {
    if (!outletFilter.value) {
        return 'Semua outlet';
    }
    return props.referenceData.outlets.find((outlet) => outlet.id === outletFilter.value)?.name || 'Outlet aktif';
});

const formatPrice = (value: number | string | null | undefined) => {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (value?: string | null) => {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(new Date(value));
};

const formatDateTime = (value?: string | null) => {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const sourceLabel = (value?: string | null) => {
    if (!value) return '-';
    return props.referenceData.sources.find((option) => option.value === value)?.label || value;
};

const paymentMethodLabel = (value?: string | null) => {
    if (!value) return '-';
    return props.referenceData.paymentMethods.find((option) => option.value === value)?.label || value;
};

const currentDateInput = (date: Date) => {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const typeLabel = (value?: string | null) => {
    switch (value) {
        case 'dine_in':
            return 'Dine In';
        case 'takeaway':
            return 'Takeaway';
        default:
            return value || '-';
    }
};

const submitFilters = () => {
    router.get(
        route('reports.sales.index'),
        {
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
            outlet_id: outletFilter.value || undefined,
            source: sourceFilter.value || undefined,
            payment_method: paymentMethodFilter.value || undefined,
            search: searchFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    const now = new Date();
    startDateFilter.value = currentDateInput(new Date(now.getFullYear(), now.getMonth(), 1));
    endDateFilter.value = currentDateInput(now);
    outletFilter.value = '';
    sourceFilter.value = '';
    paymentMethodFilter.value = '';
    searchFilter.value = '';
    submitFilters();
};

const activeFiltersCount = computed(() => {
    let count = 0;
    if (outletFilter.value) count++;
    if (sourceFilter.value) count++;
    if (paymentMethodFilter.value) count++;
    if (searchFilter.value) count++;
    return count;
});

// Hitung nilai maksimum tren untuk bar presentasi
const maxDailyRevenue = computed(() => {
    if (!props.trend.length) return 1;
    return Math.max(...props.trend.map(t => t.revenue), 1);
});

// Hitung persentase kontribusi metode pembayaran
const getPaymentPercentage = (amount: number) => {
    if (props.summary.total_revenue <= 0) return 0;
    return (amount / props.summary.total_revenue) * 100;
};
</script>

<template>
    <Head title="Laporan Penjualan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white flex items-center gap-2">
                        <TrendingUp class="h-6 w-6 text-emerald-400" />
                        Laporan Penjualan
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Ringkasan order lunas berdasarkan tanggal order.
                        <span class="font-semibold text-orange-300">{{ selectedOutletLabel }}</span>
                        • Viewer: <span class="text-slate-300 font-medium">{{ user?.role || '-' }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-300">
                        Order Lunas
                    </span>
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-1.5 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-bold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4 text-orange-400" />
                        Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Navigasi Menu Laporan Keuangan (Horizontal Tabs) -->
            <div class="flex flex-wrap border border-white/10 bg-slate-900/60 backdrop-blur-md rounded-2xl p-1 gap-1 max-w-4xl">
                <Link
                    :href="route('reports.sales.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.sales.index') ? 'bg-orange-500 text-slate-950 shadow-lg shadow-orange-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Penjualan & Kas
                </Link>
                <Link
                    :href="route('reports.outlets.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.outlets.index') ? 'bg-orange-500 text-slate-950 shadow-lg shadow-orange-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Per Outlet
                </Link>
                <Link
                    :href="route('reports.cashiers.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.cashiers.index') ? 'bg-orange-500 text-slate-950 shadow-lg shadow-orange-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Per Kasir
                </Link>
                <Link
                    :href="route('reports.top-products.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.top-products.index') ? 'bg-orange-500 text-slate-950 shadow-lg shadow-orange-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Terlaris
                </Link>
                <Link
                    :href="route('reports.expenses.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.expenses.index') ? 'bg-orange-500 text-slate-950 shadow-lg shadow-orange-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Keuangan
                </Link>
            </div>

            <!-- Panel Filter Collapsible -->
            <section class="rounded-3xl border border-white/10 bg-slate-950/40 p-4 shadow-[0_15px_50px_rgba(15,23,42,0.15)]">
                <div class="flex items-center justify-between">
                    <button
                        type="button"
                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-300 hover:text-white transition"
                        @click="isFilterExpanded = !isFilterExpanded"
                    >
                        <Filter class="h-4 w-4 text-orange-400" />
                        <span>Filter & Pencarian</span>
                        <span v-if="activeFiltersCount > 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-[10px] font-black text-slate-950">
                            {{ activeFiltersCount }}
                        </span>
                        <component :is="isFilterExpanded ? ChevronUp : ChevronDown" class="h-4 w-4 text-slate-500" />
                    </button>
                    <div class="text-[11px] font-semibold text-slate-500">
                        {{ formatDate(filters.start_date) }} - {{ formatDate(filters.end_date) }}
                    </div>
                </div>

                <div v-show="isFilterExpanded" class="mt-4 pt-4 border-t border-white/5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                        <label v-if="canChooseOutlet" class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Outlet</span>
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/60 px-3 py-2.5 text-xs text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua outlet</option>
                                <option v-for="outlet in referenceData.outlets" :key="outlet.id" :value="outlet.id">
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Dari Tanggal</span>
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/60 px-3 py-2.5 text-xs text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Sampai Tanggal</span>
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/60 px-3 py-2.5 text-xs text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Channel</span>
                            <select
                                v-model="sourceFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/60 px-3 py-2.5 text-xs text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua channel</option>
                                <option v-for="source in referenceData.sources" :key="source.value" :value="source.value">
                                    {{ source.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Metode Bayar</span>
                            <select
                                v-model="paymentMethodFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/60 px-3 py-2.5 text-xs text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua metode</option>
                                <option v-for="method in referenceData.paymentMethods" :key="method.value" :value="method.value">
                                    {{ method.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Cari Order</span>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                    <Search class="h-3.5 w-3.5" />
                                </span>
                                <input
                                    v-model="searchFilter"
                                    type="text"
                                    placeholder="ORD / Ext ID"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900/60 py-2.5 pl-9 pr-3 text-xs text-white placeholder:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                                />
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-xl border border-white/10 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/5"
                            @click="clearFilters"
                        >
                            <RefreshCw class="h-3.5 w-3.5" />
                            Reset
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-orange-500 px-4 py-2 text-xs font-bold text-slate-950 transition hover:bg-orange-400"
                            @click="submitFilters"
                        >
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </section>

            <!-- Dashboard Summary Grid (Visual Hierarchy) -->
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Card Utama: Revenue -->
                <article class="sm:col-span-2 rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-emerald-950/40 to-slate-950/60 p-6 shadow-[0_20px_60px_rgba(16,185,129,0.08)] relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 opacity-[0.03] text-emerald-400 pointer-events-none">
                        <Wallet class="h-44 w-44" />
                    </div>
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.25em] text-emerald-400/80">Total Pendapatan Penjualan</span>
                            <p class="mt-2 text-3xl font-black text-emerald-300 tracking-tight leading-none">
                                {{ formatPrice(summary.total_revenue) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-3 text-emerald-300">
                            <Wallet class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 pt-4 border-t border-white/5">
                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-300 border border-emerald-500/15">
                            Lunas
                        </span>
                        <span class="text-[11px] font-medium text-slate-400">
                            {{ summary.total_orders }} Transaksi Sukses • {{ summary.total_items_sold }} Produk Terjual
                        </span>
                    </div>
                </article>

                <!-- Card Sekunder: Rerata Tiket -->
                <article class="rounded-3xl border border-white/10 bg-slate-950/40 p-5 shadow-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Rerata Per Tiket</span>
                            <p class="mt-3 text-xl font-black text-white tracking-tight leading-none">
                                {{ formatPrice(summary.average_ticket) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-900 p-2.5 text-sky-400">
                            <ReceiptText class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-4 text-[11px] text-slate-500">
                        Rata-rata belanja customer per transaksi.
                    </p>
                </article>

                <!-- Card Sekunder: Diskon & Potongan -->
                <article class="rounded-3xl border border-white/10 bg-slate-950/40 p-5 shadow-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total Potongan Diskon</span>
                            <p class="mt-3 text-xl font-black text-amber-300 tracking-tight leading-none">
                                {{ formatPrice(summary.total_discount) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-900 p-2.5 text-amber-400">
                            <CreditCard class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-4 text-[11px] text-slate-500">
                        Akumulasi pemotongan dari diskon & promo.
                    </p>
                </article>
            </section>

            <!-- Bento Grid: Tren Harian, Metode Bayar & Channel -->
            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <!-- Panel 1: Tren Harian dengan Mini Progress Bar -->
                <article class="rounded-3xl border border-white/10 bg-slate-950/40 p-5 shadow-lg">
                    <div class="flex items-center justify-between border-b border-white/5 pb-3">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-300">
                                Tren Penjualan Harian
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Grafik performa omzet lunas per hari.
                            </p>
                        </div>
                        <span class="rounded-xl border border-white/10 bg-white/[0.02] px-2.5 py-1 text-[10px] font-bold text-slate-400">
                            {{ trend.length }} Hari Aktif
                        </span>
                    </div>

                    <div v-if="!trend.length" class="mt-4 rounded-2xl border border-dashed border-white/10 py-12 text-center text-xs text-slate-500">
                        Tidak ada transaksi tercatat pada periode ini.
                    </div>

                    <div v-else class="mt-4 space-y-3 max-h-[350px] overflow-y-auto pr-1">
                        <div
                            v-for="row in trend"
                            :key="row.date"
                            class="rounded-2xl border border-white/5 bg-white/[0.01] px-4 py-3 hover:bg-white/[0.02] transition"
                        >
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-300">{{ formatDate(row.date) }}</p>
                                        <p class="text-[10px] text-slate-500">{{ row.orders }} Transaksi</p>
                                    </div>
                                    <p class="text-sm font-black text-emerald-300">
                                        {{ formatPrice(row.revenue) }}
                                    </p>
                                </div>
                                <!-- Visual Bar Penjualan Harian -->
                                <div class="h-1.5 w-full bg-slate-900 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-emerald-500 rounded-full"
                                        :style="{ width: `${(row.revenue / maxDailyRevenue) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Panel 2: Breakdown Pembayaran & Channel -->
                <div class="space-y-6">
                    <!-- Metode Pembayaran -->
                    <article class="rounded-3xl border border-white/10 bg-slate-950/40 p-5 shadow-lg">
                        <div class="border-b border-white/5 pb-3">
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-300">
                                Metode Pembayaran
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Kontribusi omzet berdasarkan jenis pembayaran.
                            </p>
                        </div>

                        <div v-if="!breakdowns.payments.length" class="mt-4 rounded-2xl border border-dashed border-white/10 py-8 text-center text-xs text-slate-500">
                            Tidak ada data metode pembayaran.
                        </div>

                        <div v-else class="mt-4 space-y-3">
                            <div
                                v-for="row in breakdowns.payments"
                                :key="row.method"
                                class="rounded-xl border border-white/5 bg-white/[0.01] p-3 space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-bold text-slate-300">{{ paymentMethodLabel(row.method) }}</span>
                                        <span class="text-[10px] text-slate-500 ml-2">({{ row.orders }} Tx)</span>
                                    </div>
                                    <p class="text-xs font-black text-sky-300">
                                        {{ formatPrice(row.amount) }}
                                    </p>
                                </div>
                                <!-- Progress bar kontribusi -->
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 flex-1 bg-slate-900 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-sky-500 rounded-full"
                                            :style="{ width: `${getPaymentPercentage(row.amount)}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-[9px] font-black text-slate-500 w-8 text-right">
                                        {{ getPaymentPercentage(row.amount).toFixed(0) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Channel Penjualan -->
                    <article class="rounded-3xl border border-white/10 bg-slate-950/40 p-5 shadow-lg">
                        <div class="border-b border-white/5 pb-3">
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-300">
                                Channel Penjualan
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Distribusi order berdasarkan asal pesanan.
                            </p>
                        </div>

                        <div v-if="!breakdowns.sources.length" class="mt-4 rounded-2xl border border-dashed border-white/10 py-8 text-center text-xs text-slate-500">
                            Tidak ada data channel penjualan.
                        </div>

                        <div v-else class="mt-4 space-y-3">
                            <div
                                v-for="row in breakdowns.sources"
                                :key="row.source"
                                class="rounded-xl border border-white/5 bg-white/[0.01] p-3 space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-bold text-slate-300">{{ sourceLabel(row.source) }}</span>
                                        <span class="text-[10px] text-slate-500 ml-2">({{ row.orders }} Tx)</span>
                                    </div>
                                    <p class="text-xs font-black text-orange-300">
                                        {{ formatPrice(row.amount) }}
                                    </p>
                                </div>
                                <!-- Progress bar kontribusi -->
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 flex-1 bg-slate-900 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-orange-500 rounded-full"
                                            :style="{ width: `${getPaymentPercentage(row.amount)}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-[9px] font-black text-slate-500 w-8 text-right">
                                        {{ getPaymentPercentage(row.amount).toFixed(0) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Tabel Transaksi Minimalis -->
            <section class="rounded-3xl border border-white/10 bg-slate-950/40 shadow-lg">
                <div class="flex flex-col gap-3 border-b border-white/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-300">
                            Daftar Transaksi Lunas
                        </h3>
                        <p class="text-[10px] text-slate-500">
                            Menampilkan {{ transactions.from ?? 0 }} - {{ transactions.to ?? 0 }} dari {{ transactions.total }} order.
                        </p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] px-3 py-1.5 text-[10px] font-medium text-slate-300">
                        Basis tanggal: {{ scope.date_basis === 'created_at' ? 'Tanggal Order' : scope.date_basis }}
                    </div>
                </div>

                <div v-if="!transactions.data.length" class="px-5 py-12 text-center text-xs text-slate-500">
                    Tidak ada transaksi lunas yang cocok dengan filter aktif.
                </div>

                <div v-else class="divide-y divide-white/5">
                    <article
                        v-for="row in transactions.data"
                        :key="row.id"
                        class="grid gap-3 px-5 py-4 sm:grid-cols-2 md:grid-cols-4 items-center"
                    >
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h4 class="text-sm font-black text-white leading-none">{{ row.order_number }}</h4>
                                <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-0.5 text-[9px] font-bold text-sky-300">
                                    {{ sourceLabel(row.source) }}
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1">{{ formatDateTime(row.ordered_at) }}</p>
                        </div>

                        <div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 block">Kasir</span>
                            <span class="text-xs font-bold text-slate-300">{{ row.cashier?.name || 'System / Online' }}</span>
                            <span class="text-[10px] text-slate-400 block mt-0.5">{{ row.outlet?.name || '-' }}</span>
                        </div>

                        <div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 block">Pembayaran</span>
                            <span class="text-xs font-bold text-slate-300">{{ paymentMethodLabel(row.payment_method) }}</span>
                            <span class="text-[10px] text-slate-500 block mt-0.5">{{ typeLabel(row.type) }}</span>
                        </div>

                        <div class="sm:text-right">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 block sm:inline-block sm:mr-1">Total:</span>
                            <span class="text-sm font-black text-emerald-300 block sm:inline-block">{{ formatPrice(row.total_amount) }}</span>
                            <p class="text-[10px] text-slate-500 sm:mt-0.5">
                                {{ row.items_sold }} Item • Potongan: {{ formatPrice(row.discount_amount) }}
                            </p>
                        </div>
                    </article>
                </div>

                <!-- Paginator -->
                <div v-if="transactions.links.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-white/5 px-5 py-3">
                    <p class="text-[10px] text-slate-500">
                        Gunakan tombol di samping untuk menavigasi halaman transaksi.
                    </p>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="link in transactions.links"
                            :key="link.label"
                            :href="link.url || undefined"
                            class="rounded-lg border px-2.5 py-1.5 text-[10px] font-semibold transition"
                            :class="link.active
                                ? 'border-orange-400/40 bg-orange-500/10 text-orange-300'
                                : link.url
                                    ? 'border-white/10 bg-white/[0.02] text-slate-300 hover:border-white/20 hover:bg-white/[0.04]'
                                    : 'border-white/5 bg-white/[0.01] text-slate-600'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
