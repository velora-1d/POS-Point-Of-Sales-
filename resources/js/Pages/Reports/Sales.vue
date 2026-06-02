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

const summaryCards = computed(() => [
    {
        label: 'Total Revenue',
        value: formatPrice(props.summary.total_revenue),
        helper: `${props.summary.total_orders} transaksi lunas`,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: Wallet,
    },
    {
        label: 'Rata-rata Ticket',
        value: formatPrice(props.summary.average_ticket),
        helper: 'Nilai transaksi rata-rata',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: ReceiptText,
    },
    {
        label: 'Total Diskon',
        value: formatPrice(props.summary.total_discount),
        helper: 'Akumulasi potongan transaksi',
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: CreditCard,
    },
    {
        label: 'Item Terjual',
        value: props.summary.total_items_sold,
        helper: 'Total kuantitas item lunas',
        tone: 'text-violet-300',
        surface: 'border-violet-400/15 bg-violet-500/10',
        icon: ShoppingBag,
    },
    {
        label: 'Transaksi Tertinggi',
        value: formatPrice(props.summary.largest_transaction),
        helper: 'Nominal order terbesar di periode ini',
        tone: 'text-rose-300',
        surface: 'border-rose-400/15 bg-rose-500/10',
        icon: BarChart3,
    },
]);
</script>

<template>
    <Head title="Laporan Penjualan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Laporan Penjualan
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Ringkasan order lunas berdasarkan tanggal order.
                        <span class="font-semibold text-orange-300">{{ selectedOutletLabel }}</span>
                        • Viewer:
                        <span class="text-slate-300">{{ user?.role || '-' }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-300">
                        Order Lunas
                    </span>
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-2.5 text-xs font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4 text-orange-300" />
                        Kembali ke Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tab Navigation Laporan Transaksi & Kas -->
            <div class="flex flex-wrap border-b border-slate-800 bg-slate-900/40 rounded-2xl p-1 gap-1 max-w-4xl">
                <Link
                    :href="route('reports.sales.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.sales.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Penjualan & Kas
                </Link>
                <Link
                    :href="route('reports.outlets.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.outlets.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Per Outlet
                </Link>
                <Link
                    :href="route('reports.cashiers.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.cashiers.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Per Kasir
                </Link>
                <Link
                    :href="route('reports.top-products.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.top-products.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Produk Terlaris
                </Link>
                <Link
                    :href="route('reports.expenses.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.expenses.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Pengeluaran
                </Link>
            </div>

            <section class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                        <label v-if="canChooseOutlet" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Outlet</span>
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua outlet</option>
                                <option
                                    v-for="outlet in referenceData.outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Dari tanggal</span>
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Sampai tanggal</span>
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Channel penjualan</span>
                            <select
                                v-model="sourceFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua channel</option>
                                <option
                                    v-for="source in referenceData.sources"
                                    :key="source.value"
                                    :value="source.value"
                                >
                                    {{ source.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Metode bayar</span>
                            <select
                                v-model="paymentMethodFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua metode</option>
                                <option
                                    v-for="method in referenceData.paymentMethods"
                                    :key="method.value"
                                    :value="method.value"
                                >
                                    {{ method.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Cari order</span>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                    <Search class="h-4 w-4" />
                                </span>
                                <input
                                    v-model="searchFilter"
                                    type="text"
                                    placeholder="ORD / external id"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900/80 py-3 pl-10 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                                />
                            </div>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/5"
                            @click="clearFilters"
                        >
                            Reset Filter
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-orange-400"
                            @click="submitFilters"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-sky-400/15 bg-sky-500/10 px-4 py-3 text-xs text-sky-100">
                    Periode {{ formatDate(filters.start_date) }} sampai {{ formatDate(filters.end_date) }}.
                    Laporan ini hanya menghitung order yang sudah lunas dan tidak termasuk order cancelled.
                </div>
            </section>

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border p-5 shadow-[0_20px_60px_rgba(15,23,42,0.22)]"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-2xl font-black" :class="card.tone">
                                {{ card.value }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/30 p-3">
                            <component :is="card.icon" class="h-5 w-5 text-white" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-300">
                        {{ card.helper }}
                    </p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
                <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                Tren Penjualan Harian
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Revenue dan jumlah transaksi lunas per hari dalam periode aktif.
                            </p>
                        </div>
                        <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-300">
                            {{ trend.length }} hari
                        </span>
                    </div>

                    <div v-if="!trend.length" class="mt-5 rounded-2xl border border-dashed border-white/10 px-4 py-10 text-center text-sm text-slate-400">
                        Belum ada transaksi lunas pada periode ini.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <article
                            v-for="row in trend"
                            :key="row.date"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-black text-white">{{ formatDate(row.date) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ row.orders }} transaksi lunas</p>
                                </div>
                                <p class="text-lg font-black text-emerald-300">
                                    {{ formatPrice(row.revenue) }}
                                </p>
                            </div>
                        </article>
                    </div>
                </article>

                <div class="space-y-6">
                    <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                Breakdown Pembayaran
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Komposisi omzet berdasarkan metode pembayaran.
                            </p>
                        </div>

                        <div v-if="!breakdowns.payments.length" class="mt-5 rounded-2xl border border-dashed border-white/10 px-4 py-8 text-center text-sm text-slate-400">
                            Belum ada data metode pembayaran.
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <article
                                v-for="row in breakdowns.payments"
                                :key="row.method"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-white">{{ paymentMethodLabel(row.method) }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ row.orders }} transaksi</p>
                                    </div>
                                    <p class="text-sm font-black text-sky-300">{{ formatPrice(row.amount) }}</p>
                                </div>
                            </article>
                        </div>
                    </article>

                    <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                Breakdown Channel
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Distribusi revenue berdasarkan sumber order.
                            </p>
                        </div>

                        <div v-if="!breakdowns.sources.length" class="mt-5 rounded-2xl border border-dashed border-white/10 px-4 py-8 text-center text-sm text-slate-400">
                            Belum ada data channel penjualan.
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <article
                                v-for="row in breakdowns.sources"
                                :key="row.source"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-white">{{ sourceLabel(row.source) }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ row.orders }} transaksi</p>
                                    </div>
                                    <p class="text-sm font-black text-emerald-300">{{ formatPrice(row.amount) }}</p>
                                </div>
                            </article>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-950/70 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-3 border-b border-white/10 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                            Daftar Transaksi Lunas
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Menampilkan {{ transactions.from ?? 0 }} - {{ transactions.to ?? 0 }} dari {{ transactions.total }} order pada filter aktif.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-xs text-slate-300">
                        Basis tanggal: {{ scope.date_basis === 'created_at' ? 'Tanggal order' : scope.date_basis }}
                    </div>
                </div>

                <div v-if="!transactions.data.length" class="px-5 py-10 text-center text-sm text-slate-400">
                    Tidak ada transaksi lunas yang cocok dengan filter ini.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="row in transactions.data"
                        :key="row.id"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[1.1fr_0.8fr_0.8fr_0.9fr]"
                    >
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-black text-white">{{ row.order_number }}</h3>
                                <span class="rounded-full border border-sky-400/15 bg-sky-500/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-sky-300">
                                    {{ sourceLabel(row.source) }}
                                </span>
                                <span class="rounded-full border border-white/10 bg-white/[0.03] px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-300">
                                    {{ typeLabel(row.type) }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-300">{{ row.outlet?.name || 'Semua outlet' }}</p>
                            <p class="text-xs text-slate-500">{{ formatDateTime(row.ordered_at) }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Kasir</p>
                            <p class="text-sm font-semibold text-white">{{ row.cashier?.name || 'System / Online' }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Metode bayar</p>
                            <p class="text-sm text-slate-300">{{ paymentMethodLabel(row.payment_method) }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Subtotal</p>
                            <p class="text-sm font-semibold text-white">{{ formatPrice(row.subtotal) }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Diskon</p>
                            <p class="text-sm text-amber-300">{{ formatPrice(row.discount_amount) }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Item terjual</p>
                            <p class="text-sm font-semibold text-white">{{ row.items_sold }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Total bayar</p>
                            <p class="text-lg font-black text-emerald-300">{{ formatPrice(row.total_amount) }}</p>
                        </div>
                    </article>
                </div>

                <div
                    v-if="transactions.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 px-5 py-4"
                >
                    <p class="text-xs text-slate-500">
                        Halaman transaksi mengikuti filter aktif dan hanya berisi order lunas.
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="link in transactions.links"
                            :key="link.label"
                            :href="link.url || undefined"
                            class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                            :class="link.active
                                ? 'border-orange-400/40 bg-orange-500/10 text-orange-300'
                                : link.url
                                    ? 'border-white/10 bg-white/[0.03] text-slate-300 hover:border-white/20 hover:bg-white/[0.05]'
                                    : 'border-white/5 bg-white/[0.02] text-slate-600'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
