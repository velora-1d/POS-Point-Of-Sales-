<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    CalendarRange,
    CreditCard,
    Search,
    TrendingDown,
    TrendingUp,
    UserRound,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface OutletOption {
    id: string;
    name: string;
}

interface CashierOption {
    id: string;
    name: string;
    role?: string | null;
}

interface CashierRow {
    id: string;
    name: string;
    outlet?: {
        id: string;
        name: string;
    } | null;
    current: {
        revenue: number;
        orders: number;
        average_ticket: number;
        discount: number;
        revenue_bar_percentage: number;
        payment_breakdown: Array<{
            method: string;
            amount: number;
        }>;
    };
    previous: {
        revenue: number;
        orders: number;
        average_ticket: number;
        discount: number;
    };
    growth: {
        revenue_amount: number;
        revenue_percentage: number | null;
        order_amount: number;
    };
}

const page = usePage<any>();
const props = defineProps<{
    summary: {
        active_cashiers: number;
        total_revenue: number;
        total_orders: number;
        average_ticket: number;
        top_cashier?: {
            name: string;
            revenue: number;
        } | null;
        best_growth?: {
            name: string;
            percentage: number | null;
        } | null;
    };
    cashiers: CashierRow[];
    filters: {
        start_date: string;
        end_date: string;
        outlet_id?: string;
        user_id?: string;
    };
    referenceData: {
        outlets: OutletOption[];
        cashiers: CashierOption[];
    };
    period: {
        current: {
            start_date: string;
            end_date: string;
        };
        previous: {
            start_date: string;
            end_date: string;
        };
    };
}>();

const user = computed(() => page.props.auth.user);
const startDateFilter = ref(props.filters.start_date);
const endDateFilter = ref(props.filters.end_date);
const outletFilter = ref(props.filters.outlet_id || '');
const cashierFilter = ref(props.filters.user_id || '');
const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);
const canChooseCashier = computed(
    () => props.referenceData.cashiers.length > 0,
);

const currentDateInput = (date: Date) => {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');

    return `${year}-${month}-${day}`;
};

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

const growthLabel = (value: number | null) => {
    if (value === null) {
        return 'Kasir baru / belum ada basis';
    }

    const prefix = value > 0 ? '+' : '';

    return `${prefix}${value.toFixed(1)}%`;
};

const growthClass = (value: number | null) => {
    if (value === null) {
        return 'text-stone-600 dark:text-slate-300';
    }

    if (value > 0.001) {
        return 'text-emerald-600 dark:text-emerald-400';
    }

    if (value < -0.001) {
        return 'text-rose-600 dark:text-rose-400';
    }

    return 'text-stone-600 dark:text-slate-300';
};

const growthSurfaceClass = (value: number | null) => {
    if (value === null) {
        return 'border-2 border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/60';
    }

    if (value > 0.001) {
        return 'border-2 border-emerald-200 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/5';
    }

    if (value < -0.001) {
        return 'border-2 border-rose-200 bg-rose-50 dark:border-rose-500/20 dark:bg-rose-500/5';
    }

    return 'border-2 border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/60';
};

const submitFilters = () => {
    router.get(
        route('reports.cashiers.index'),
        {
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
            outlet_id: outletFilter.value || undefined,
            user_id: cashierFilter.value || undefined,
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

    startDateFilter.value = currentDateInput(
        new Date(now.getFullYear(), now.getMonth(), 1),
    );
    endDateFilter.value = currentDateInput(now);
    outletFilter.value = '';
    cashierFilter.value = '';
    submitFilters();
};

const summaryCards = computed(() => [
    {
        label: 'Revenue Kasir',
        value: formatPrice(props.summary.total_revenue),
        helper: `${props.summary.total_orders} transaksi lunas`,
        icon: Wallet,
        tone: 'text-emerald-600 dark:text-emerald-400',
        surface: 'bg-emerald-50/30 border-emerald-500/20',
    },
    {
        label: 'Kasir Aktif',
        value: props.summary.active_cashiers,
        helper: 'Kasir dengan transaksi pada periode ini',
        icon: UserRound,
        tone: 'text-sky-600 dark:text-sky-400',
        surface: 'bg-sky-50/30 border-stone-200',
    },
    {
        label: 'Rata-rata Ticket',
        value: formatPrice(props.summary.average_ticket),
        helper: 'Nilai order rata-rata per kasir',
        icon: BarChart3,
        tone: 'text-amber-600 dark:text-amber-400',
        surface: 'bg-amber-50/30 border-stone-200',
    },
    {
        label: 'Top Kasir',
        value: props.summary.top_cashier?.name || '-',
        helper: props.summary.top_cashier
            ? formatPrice(props.summary.top_cashier.revenue)
            : 'Belum ada revenue',
        icon: CreditCard,
        tone: 'text-violet-600 dark:text-violet-400',
        surface: 'bg-violet-50/30 border-stone-200',
    },
]);
</script>

<template>
    <Head title="Laporan Per Kasir" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"
            >
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-stone-900 dark:text-white"
                    >
                        Laporan Per Kasir
                    </h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                        Penjualan kasir untuk periode
                        <span class="font-semibold text-orange-300">
                            {{ formatDate(period.current.start_date) }} -
                            {{ formatDate(period.current.end_date) }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-xl border border-emerald-500/20 bg-emerald-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                    >
                        Order Lunas
                    </span>
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-1.5 rounded-2xl border-2 border-stone-200 bg-white px-4 py-2 text-xs font-black text-stone-700 transition hover:border-stone-300 hover:bg-stone-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                    >
                        <CalendarRange class="h-4 w-4 text-orange-500" />
                        Dashboard Utama
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Navigasi Menu Laporan Keuangan (Horizontal Tabs) -->
            <div
                class="flex w-full flex-wrap gap-2 rounded-2xl border-2 border-stone-200 bg-stone-100 p-1.5 dark:border-white/10 dark:bg-slate-950"
            >
                <Link
                    :href="route('reports.sales.index')"
                    class="min-w-[120px] flex-1 rounded-xl py-2.5 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('reports.sales.index')
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Penjualan & Kas
                </Link>
                <Link
                    :href="route('reports.outlets.index')"
                    class="min-w-[120px] flex-1 rounded-xl py-2.5 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('reports.outlets.index')
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Per Outlet
                </Link>
                <Link
                    :href="route('reports.cashiers.index')"
                    class="min-w-[120px] flex-1 rounded-xl py-2.5 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('reports.cashiers.index')
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Per Kasir
                </Link>
                <Link
                    :href="route('reports.top-products.index')"
                    class="min-w-[120px] flex-1 rounded-xl py-2.5 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('reports.top-products.index')
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Produk Terlaris
                </Link>
                <Link
                    :href="route('reports.expenses.index')"
                    class="min-w-[120px] flex-1 rounded-xl py-2.5 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('reports.expenses.index')
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Keuangan
                </Link>
            </div>

            <!-- Panel Filter -->
            <section
                class="rounded-3xl border-2 border-stone-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div
                        class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-5"
                    >
                        <label v-if="canChooseOutlet" class="block">
                            <span
                                class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-700 dark:text-slate-300"
                                >Outlet</span
                            >
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-white px-3 py-2 text-xs font-bold text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
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
                            <span
                                class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-700 dark:text-slate-300"
                                >Dari tanggal</span
                            >
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-white px-3 py-2 text-xs font-bold text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span
                                class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-700 dark:text-slate-300"
                                >Sampai tanggal</span
                            >
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-white px-3 py-2 text-xs font-bold text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </label>

                        <label v-if="canChooseCashier" class="block">
                            <span
                                class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-700 dark:text-slate-300"
                                >Kasir</span
                            >
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-stone-400 dark:text-slate-500"
                                >
                                    <Search class="h-3.5 w-3.5" />
                                </span>
                                <select
                                    v-model="cashierFilter"
                                    class="w-full rounded-2xl border-2 border-stone-300 bg-white py-2 pl-9 pr-3 text-xs font-bold text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                                >
                                    <option value="">Semua kasir</option>
                                    <option
                                        v-for="cashier in referenceData.cashiers"
                                        :key="cashier.id"
                                        :value="cashier.id"
                                    >
                                        {{ cashier.name }}
                                    </option>
                                </select>
                            </div>
                        </label>

                        <div
                            class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/40"
                        >
                            <p
                                class="text-[9px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Periode Sebelumnya
                            </p>
                            <p
                                class="mt-1 text-xs font-black text-stone-900 dark:text-white"
                            >
                                {{ formatDate(period.previous.start_date) }}
                            </p>
                            <p
                                class="text-[10px] font-semibold text-stone-500 dark:text-slate-400"
                            >
                                s/d {{ formatDate(period.previous.end_date) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-xl border-2 border-stone-300 bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wider text-stone-700 transition hover:bg-stone-100 hover:text-stone-950 dark:border-slate-800 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
                            @click="clearFilters"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-orange-500 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-stone-950 transition hover:bg-orange-400"
                            @click="submitFilters"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border-2 p-6 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-2 text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                            >
                                {{ card.value }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border-2 border-stone-100 bg-white p-2.5 dark:border-white/10 dark:bg-slate-900"
                        >
                            <component
                                :is="card.icon"
                                class="h-5 w-5"
                                :class="card.tone"
                            />
                        </div>
                    </div>
                    <p
                        class="mt-6 border-t border-stone-200/50 pt-4 text-[11px] font-medium text-stone-400 dark:border-white/5 dark:text-slate-500"
                    >
                        {{ card.helper }}
                    </p>
                </article>
            </section>


            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <article
                    class="rounded-3xl border-2 border-stone-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-700 dark:text-slate-200"
                        >
                            Ranking Revenue Kasir
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Perbandingan revenue kasir pada periode aktif.
                        </p>
                    </div>

                    <div
                        v-if="!cashiers.length"
                        class="mt-5 rounded-2xl border-2 border-dashed border-stone-200 px-4 py-10 text-center text-sm text-stone-500 dark:border-white/10 dark:text-slate-400"
                    >
                        Belum ada transaksi kasir pada periode ini.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <article
                            v-for="cashier in cashiers"
                            :key="cashier.id"
                            class="rounded-2xl border-2 border-stone-200 bg-stone-50/50 px-4 py-4 dark:border-white/10 dark:bg-slate-900/40"
                        >
                            <div
                                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p
                                            class="truncate text-sm font-black text-stone-900 dark:text-white"
                                        >
                                            {{ cashier.name }}
                                        </p>
                                        <component
                                            :is="
                                                cashier.growth
                                                    .revenue_percentage !==
                                                    null &&
                                                cashier.growth
                                                    .revenue_percentage >= 0
                                                    ? TrendingUp
                                                    : TrendingDown
                                            "
                                            class="h-4 w-4 shrink-0"
                                            :class="
                                                growthClass(
                                                    cashier.growth
                                                        .revenue_percentage,
                                                )
                                            "
                                        />
                                    </div>
                                    <p
                                        class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        {{
                                            cashier.outlet?.name ||
                                            'Tanpa outlet'
                                        }}
                                        • {{ cashier.current.orders }} order
                                    </p>
                                    <div
                                        class="mt-3 h-2.5 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"
                                    >
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-orange-500 via-amber-400 to-emerald-400"
                                            :style="{
                                                width: `${cashier.current.revenue_bar_percentage}%`,
                                            }"
                                        />
                                    </div>
                                </div>

                                <div class="md:ps-4 md:text-right">
                                    <p
                                        class="text-lg font-black text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{
                                            formatPrice(cashier.current.revenue)
                                        }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs"
                                        :class="
                                            growthClass(
                                                cashier.growth
                                                    .revenue_percentage,
                                            )
                                        "
                                    >
                                        {{
                                            growthLabel(
                                                cashier.growth
                                                    .revenue_percentage,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <article
                    class="rounded-3xl border-2 border-stone-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-700 dark:text-slate-200"
                        >
                            Highlight Growth
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Kasir dengan perubahan revenue paling terlihat.
                        </p>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article
                            v-for="cashier in cashiers.slice(0, 4)"
                            :key="`${cashier.id}-growth`"
                            class="rounded-2xl border-2 px-4 py-4"
                            :class="
                                growthSurfaceClass(
                                    cashier.growth.revenue_percentage,
                                )
                            "
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-sm font-black text-stone-900 dark:text-white"
                                    >
                                        {{ cashier.name }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        Avg ticket
                                        {{
                                            formatPrice(
                                                cashier.current.average_ticket,
                                            )
                                        }}
                                    </p>
                                </div>
                                <p
                                    class="text-sm font-black"
                                    :class="
                                        growthClass(
                                            cashier.growth.revenue_percentage,
                                        )
                                    "
                                >
                                    {{
                                        growthLabel(
                                            cashier.growth.revenue_percentage,
                                        )
                                    }}
                                </p>
                            </div>
                            <p
                                class="mt-3 text-xs text-stone-600 dark:text-slate-350"
                            >
                                Selisih revenue
                                <span class="font-bold text-stone-850 dark:text-white">
                                    {{ formatPrice(cashier.growth.revenue_amount) }}
                                </span>
                                dan delta order
                                <span class="font-bold text-stone-850 dark:text-white">
                                    {{ cashier.growth.order_amount > 0 ? '+' : '' }}{{ cashier.growth.order_amount }}
                                </span>.
                            </p>
                        </article>
                    </div>
                </article>
            </section>

            <section
                class="rounded-3xl border-2 border-stone-200 bg-white shadow-xl dark:border-white/10 dark:bg-slate-950/45"
            >
                <div
                    class="flex flex-col gap-3 border-b-2 border-stone-200 px-5 py-4 dark:border-white/10 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-700 dark:text-slate-200"
                        >
                            Tabel Performa Kasir
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Revenue, order, average ticket, diskon, growth, dan
                            breakdown pembayaran per kasir.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-xs text-stone-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300"
                    >
                        Growth dibanding
                        <span class="font-bold text-orange-600 dark:text-orange-400">
                            {{ formatDate(period.previous.start_date) }} -
                            {{ formatDate(period.previous.end_date) }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="!cashiers.length"
                    class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400"
                >
                    Tidak ada data kasir pada filter ini.
                </div>

                <div v-else class="divide-y-2 divide-stone-200 dark:divide-white/10">
                    <article
                        v-for="cashier in cashiers"
                        :key="`${cashier.id}-table`"
                        class="grid gap-4 px-5 py-6 xl:grid-cols-[1.1fr_0.95fr_0.9fr_1.05fr] hover:bg-stone-50/50 dark:hover:bg-slate-900/20 transition-colors"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <h3
                                    class="text-base font-black text-stone-900 dark:text-white"
                                >
                                    {{ cashier.name }}
                                </h3>
                                <span
                                    class="rounded-full border-2 px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.18em]"
                                    :class="
                                        growthSurfaceClass(
                                            cashier.growth.revenue_percentage,
                                        )
                                    "
                                >
                                    Growth
                                </span>
                            </div>
                            <p
                                class="text-sm font-bold text-stone-600 dark:text-slate-350"
                            >
                                {{ cashier.outlet?.name || 'Tanpa outlet' }}
                            </p>
                            <p
                                class="text-xs text-stone-500 dark:text-slate-400"
                            >
                                Revenue sebelumnya:
                                <span class="font-bold text-stone-850 dark:text-slate-200">
                                    {{ formatPrice(cashier.previous.revenue) }}
                                </span>
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p
                                class="text-[9px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Revenue saat ini
                            </p>
                            <p class="text-lg font-black text-emerald-600 dark:text-emerald-400">
                                {{ formatPrice(cashier.current.revenue) }}
                            </p>
                            <p
                                class="text-[9px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Diskon
                            </p>
                            <p class="text-sm font-bold text-amber-600 dark:text-amber-400">
                                {{ formatPrice(cashier.current.discount) }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p
                                class="text-[9px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Jumlah order
                            </p>
                            <p
                                class="text-sm font-black text-stone-900 dark:text-white"
                            >
                                {{ cashier.current.orders }}
                            </p>
                            <p
                                class="text-[9px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Average ticket
                            </p>
                            <p
                                class="text-sm font-bold text-stone-700 dark:text-slate-300"
                            >
                                {{
                                    formatPrice(cashier.current.average_ticket)
                                }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p
                                class="text-[9px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Growth revenue
                            </p>
                            <p
                                class="text-lg font-black"
                                :class="
                                    growthClass(
                                        cashier.growth.revenue_percentage,
                                    )
                                "
                            >
                                {{
                                    growthLabel(
                                        cashier.growth.revenue_percentage,
                                    )
                                }}
                            </p>
                            <p
                                class="text-xs text-stone-500 dark:text-slate-400"
                            >
                                Selisih:
                                <span class="font-bold text-stone-850 dark:text-slate-200">
                                    {{ formatPrice(cashier.growth.revenue_amount) }}
                                </span>
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2">
                                <span
                                    v-for="payment in cashier.current
                                        .payment_breakdown"
                                    :key="`${cashier.id}-${payment.method}`"
                                    class="rounded-full border-2 border-stone-200 bg-stone-50 px-2 py-0.5 text-[9px] font-bold text-stone-600 dark:border-slate-850 dark:bg-slate-900/40 dark:text-slate-300"
                                >
                                    {{ paymentMethodLabel(payment.method) }}
                                    {{ formatPrice(payment.amount) }}
                                </span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
