<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Building2,
    CalendarRange,
    Crown,
    TrendingDown,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface OutletRow {
    id: string;
    name: string;
    current: {
        revenue: number;
        orders: number;
        average_ticket: number;
        discount: number;
        revenue_bar_percentage: number;
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
        total_outlets: number;
        active_outlets: number;
        total_revenue: number;
        total_orders: number;
        average_ticket: number;
        top_outlet?: {
            name: string;
            revenue: number;
        } | null;
        best_growth?: {
            name: string;
            percentage: number | null;
        } | null;
    };
    outlets: OutletRow[];
    filters: {
        start_date: string;
        end_date: string;
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

const growthLabel = (value: number | null) => {
    if (value === null) {
        return 'Outlet baru / belum ada basis';
    }

    const prefix = value > 0 ? '+' : '';

    return `${prefix}${value.toFixed(1)}%`;
};

const growthClass = (value: number | null) => {
    if (value === null) {
        return 'text-stone-600 dark:text-slate-300';
    }

    if (value > 0.001) {
        return 'text-emerald-300';
    }

    if (value < -0.001) {
        return 'text-rose-300';
    }

    return 'text-stone-600 dark:text-slate-300';
};

const growthSurfaceClass = (value: number | null) => {
    if (value === null) {
        return 'border-stone-200 dark:border-slate-700/60 bg-stone-50 dark:bg-slate-900/70';
    }

    if (value > 0.001) {
        return 'border-emerald-400/15 bg-emerald-500/10';
    }

    if (value < -0.001) {
        return 'border-rose-400/15 bg-rose-500/10';
    }

    return 'border-stone-200 dark:border-slate-700/60 bg-stone-50 dark:bg-slate-900/70';
};

const submitFilters = () => {
    router.get(
        route('reports.outlets.index'),
        {
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
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
    submitFilters();
};

const summaryCards = computed(() => [
    {
        label: 'Revenue Semua Outlet',
        value: formatPrice(props.summary.total_revenue),
        helper: `${props.summary.total_orders} order lunas`,
        icon: Wallet,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
    },
    {
        label: 'Outlet Aktif',
        value: `${props.summary.active_outlets}/${props.summary.total_outlets}`,
        helper: 'Outlet dengan transaksi di periode ini',
        icon: Building2,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
    },
    {
        label: 'Rata-rata Ticket',
        value: formatPrice(props.summary.average_ticket),
        helper: 'AOV gabungan semua outlet',
        icon: BarChart3,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
    },
    {
        label: 'Top Outlet',
        value: props.summary.top_outlet?.name || '-',
        helper: props.summary.top_outlet
            ? formatPrice(props.summary.top_outlet.revenue)
            : 'Belum ada revenue',
        icon: Crown,
        tone: 'text-violet-300',
        surface: 'border-violet-400/15 bg-violet-500/10',
    },
]);
</script>

<template>
    <Head title="Laporan Per Outlet" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-stone-900 dark:text-stone-900 dark:text-white">
Laporan Per Outlet
                    </h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                        Perbandingan performa semua outlet untuk owner.
                        <span class="font-semibold text-orange-300">
                            {{ formatDate(period.current.start_date) }} - {{ formatDate(period.current.end_date) }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-orange-300">
                        Owner Only
                    </span>
                    <span class="rounded-full border border-stone-200 dark:border-white/10 bg-white/[0.03] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-stone-600 dark:text-slate-300">
                        {{ user?.role || '-' }}
                    </span>
                    <Link
                        :href="route('reports.sales.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-stone-200 dark:border-white/10 bg-white/[0.03] px-4 py-2.5 text-xs font-semibold text-stone-800 dark:text-slate-200 transition hover:border-stone-200 dark:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4 text-sky-300" />
                        Lihat Laporan Penjualan
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tab Navigation Laporan Transaksi & Kas -->
            <div class="flex flex-wrap border-b border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/40 rounded-2xl p-1 gap-1 max-w-4xl">
                <Link
                    :href="route('reports.sales.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.sales.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Penjualan & Kas
                </Link>
                <Link
                    :href="route('reports.outlets.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.outlets.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Per Outlet
                </Link>
                <Link
                    :href="route('reports.cashiers.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.cashiers.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Per Kasir
                </Link>
                <Link
                    :href="route('reports.top-products.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.top-products.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Produk Terlaris
                </Link>
                <Link
                    :href="route('reports.expenses.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.expenses.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Pengeluaran
                </Link>
            </div>

            <section class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Dari tanggal</span>
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/80 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Sampai tanggal</span>
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/80 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <div class="rounded-2xl border border-stone-200 dark:border-slate-700/60 bg-stone-50 dark:bg-slate-900/70 px-4 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Periode Sebelumnya
                            </p>
                            <p class="mt-2 text-sm font-bold text-stone-900 dark:text-white">
                                {{ formatDate(period.previous.start_date) }}
                            </p>
                            <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                sampai {{ formatDate(period.previous.end_date) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-stone-200 dark:border-slate-700/60 bg-stone-50 dark:bg-slate-900/70 px-4 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Basis Growth
                            </p>
                            <p class="mt-2 text-sm font-bold text-stone-900 dark:text-white">
                                Revenue vs periode sebelumnya
                            </p>
                            <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                Zero baseline ditandai sebagai outlet baru.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-stone-200 dark:border-white/10 px-4 py-3 text-sm font-semibold text-stone-800 dark:text-slate-200 transition hover:border-stone-200 dark:border-white/20 hover:bg-stone-100 dark:bg-white/5"
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
            </section>

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border p-5 shadow-[0_20px_60px_rgba(15,23,42,0.22)]"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-2xl font-black" :class="card.tone">
                                {{ card.value }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/30 p-3">
                            <component :is="card.icon" class="h-5 w-5 text-stone-900 dark:text-white" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-stone-600 dark:text-slate-300">
                        {{ card.helper }}
                    </p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <article class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300">
                            Ranking Revenue Outlet
                        </h3>
                        <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">
                            Perbandingan visual revenue outlet pada periode aktif.
                        </p>
                    </div>

                    <div v-if="!outlets.length" class="mt-5 rounded-2xl border border-dashed border-stone-200 dark:border-white/10 px-4 py-10 text-center text-sm text-stone-500 dark:text-slate-400">
                        Belum ada outlet aktif untuk dibandingkan.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <article
                            v-for="outlet in outlets"
                            :key="outlet.id"
                            class="rounded-2xl border border-stone-200 dark:border-white/10 bg-white/[0.03] px-4 py-4"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-sm font-black text-stone-900 dark:text-white">{{ outlet.name }}</p>
                                        <component
                                            :is="outlet.growth.revenue_percentage !== null && outlet.growth.revenue_percentage >= 0 ? TrendingUp : TrendingDown"
                                            class="h-4 w-4 shrink-0"
                                            :class="growthClass(outlet.growth.revenue_percentage)"
                                        />
                                    </div>
                                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-orange-500 via-amber-400 to-emerald-400"
                                            :style="{ width: `${outlet.current.revenue_bar_percentage}%` }"
                                        />
                                    </div>
                                </div>

                                <div class="md:ps-4 md:text-right">
                                    <p class="text-lg font-black text-emerald-300">
                                        {{ formatPrice(outlet.current.revenue) }}
                                    </p>
                                    <p class="mt-1 text-xs" :class="growthClass(outlet.growth.revenue_percentage)">
                                        {{ growthLabel(outlet.growth.revenue_percentage) }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <article class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300">
                            Highlight Growth
                        </h3>
                        <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">
                            Outlet dengan performa naik atau turun paling kentara.
                        </p>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article
                            v-for="outlet in outlets.slice(0, 4)"
                            :key="`${outlet.id}-growth`"
                            class="rounded-2xl border px-4 py-4"
                            :class="growthSurfaceClass(outlet.growth.revenue_percentage)"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-stone-900 dark:text-white">{{ outlet.name }}</p>
                                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                        {{ outlet.current.orders }} order • avg {{ formatPrice(outlet.current.average_ticket) }}
                                    </p>
                                </div>
                                <p class="text-sm font-black" :class="growthClass(outlet.growth.revenue_percentage)">
                                    {{ growthLabel(outlet.growth.revenue_percentage) }}
                                </p>
                            </div>
                            <p class="mt-3 text-xs text-stone-600 dark:text-slate-300">
                                Selisih revenue {{ formatPrice(outlet.growth.revenue_amount) }} dibanding periode sebelumnya.
                            </p>
                        </article>
                    </div>
                </article>
            </section>

            <section class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/70 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-3 border-b border-stone-200 dark:border-white/10 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300">
                            Tabel Perbandingan Outlet
                        </h3>
                        <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">
                            Revenue, jumlah order, average ticket, diskon, dan growth revenue dibanding periode sebelumnya.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-stone-200 dark:border-white/10 bg-white/[0.03] px-4 py-3 text-xs text-stone-600 dark:text-slate-300">
                        Growth dihitung vs {{ formatDate(period.previous.start_date) }} - {{ formatDate(period.previous.end_date) }}
                    </div>
                </div>

                <div v-if="!outlets.length" class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400">
                    Tidak ada outlet aktif untuk ditampilkan.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="outlet in outlets"
                        :key="`${outlet.id}-table`"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[1.1fr_1fr_1fr_0.9fr]"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-stone-900 dark:text-white">{{ outlet.name }}</h3>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="growthSurfaceClass(outlet.growth.revenue_percentage)"
                                >
                                    Growth
                                </span>
                            </div>
                            <p class="text-xs text-stone-400 dark:text-slate-500">
                                Revenue sebelumnya {{ formatPrice(outlet.previous.revenue) }}
                            </p>
                            <p class="text-xs text-stone-400 dark:text-slate-500">
                                Order sebelumnya {{ outlet.previous.orders }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Revenue saat ini</p>
                            <p class="text-lg font-black text-emerald-300">{{ formatPrice(outlet.current.revenue) }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Diskon</p>
                            <p class="text-sm text-amber-300">{{ formatPrice(outlet.current.discount) }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Jumlah order</p>
                            <p class="text-sm font-semibold text-stone-900 dark:text-white">{{ outlet.current.orders }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Average ticket</p>
                            <p class="text-sm text-stone-600 dark:text-slate-300">{{ formatPrice(outlet.current.average_ticket) }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Growth revenue</p>
                            <p class="text-lg font-black" :class="growthClass(outlet.growth.revenue_percentage)">
                                {{ growthLabel(outlet.growth.revenue_percentage) }}
                            </p>
                            <p class="text-xs text-stone-500 dark:text-slate-400">
                                Selisih {{ formatPrice(outlet.growth.revenue_amount) }}
                            </p>
                            <p class="text-xs text-stone-500 dark:text-slate-400">
                                Delta order {{ outlet.growth.order_amount > 0 ? '+' : '' }}{{ outlet.growth.order_amount }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
