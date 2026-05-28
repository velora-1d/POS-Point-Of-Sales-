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
const canChooseCashier = computed(() => props.referenceData.cashiers.length > 0);

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
        return 'text-slate-300';
    }

    if (value > 0.001) {
        return 'text-emerald-300';
    }

    if (value < -0.001) {
        return 'text-rose-300';
    }

    return 'text-slate-300';
};

const growthSurfaceClass = (value: number | null) => {
    if (value === null) {
        return 'border-slate-700/60 bg-slate-900/70';
    }

    if (value > 0.001) {
        return 'border-emerald-400/15 bg-emerald-500/10';
    }

    if (value < -0.001) {
        return 'border-rose-400/15 bg-rose-500/10';
    }

    return 'border-slate-700/60 bg-slate-900/70';
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

    startDateFilter.value = currentDateInput(new Date(now.getFullYear(), now.getMonth(), 1));
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
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
    },
    {
        label: 'Kasir Aktif',
        value: props.summary.active_cashiers,
        helper: 'Kasir dengan transaksi pada periode ini',
        icon: UserRound,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
    },
    {
        label: 'Rata-rata Ticket',
        value: formatPrice(props.summary.average_ticket),
        helper: 'Nilai order rata-rata per kasir',
        icon: BarChart3,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
    },
    {
        label: 'Top Kasir',
        value: props.summary.top_cashier?.name || '-',
        helper: props.summary.top_cashier
            ? formatPrice(props.summary.top_cashier.revenue)
            : 'Belum ada revenue',
        icon: CreditCard,
        tone: 'text-violet-300',
        surface: 'border-violet-400/15 bg-violet-500/10',
    },
]);
</script>

<template>
    <Head title="Laporan Per Kasir" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Laporan Per Kasir
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Penjualan kasir untuk periode
                        <span class="font-semibold text-orange-300">
                            {{ formatDate(period.current.start_date) }} - {{ formatDate(period.current.end_date) }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-300">
                        {{ user?.role || '-' }}
                    </span>
                    <Link
                        :href="route('reports.outlets.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-2.5 text-xs font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4 text-sky-300" />
                        Lihat Laporan Per Outlet
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
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

                        <label v-if="canChooseCashier" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Kasir</span>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                    <Search class="h-4 w-4" />
                                </span>
                                <select
                                    v-model="cashierFilter"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900/80 py-3 pl-10 pr-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
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

                        <div class="rounded-2xl border border-slate-700/60 bg-slate-900/70 px-4 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                Periode Sebelumnya
                            </p>
                            <p class="mt-2 text-sm font-bold text-white">
                                {{ formatDate(period.previous.start_date) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                sampai {{ formatDate(period.previous.end_date) }}
                            </p>
                        </div>
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

            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                            Ranking Revenue Kasir
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Perbandingan revenue kasir pada periode aktif.
                        </p>
                    </div>

                    <div v-if="!cashiers.length" class="mt-5 rounded-2xl border border-dashed border-white/10 px-4 py-10 text-center text-sm text-slate-400">
                        Belum ada transaksi kasir pada periode ini.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <article
                            v-for="cashier in cashiers"
                            :key="cashier.id"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-sm font-black text-white">{{ cashier.name }}</p>
                                        <component
                                            :is="cashier.growth.revenue_percentage !== null && cashier.growth.revenue_percentage >= 0 ? TrendingUp : TrendingDown"
                                            class="h-4 w-4 shrink-0"
                                            :class="growthClass(cashier.growth.revenue_percentage)"
                                        />
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ cashier.outlet?.name || 'Tanpa outlet' }} • {{ cashier.current.orders }} order
                                    </p>
                                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-800">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-orange-500 via-amber-400 to-emerald-400"
                                            :style="{ width: `${cashier.current.revenue_bar_percentage}%` }"
                                        />
                                    </div>
                                </div>

                                <div class="md:ps-4 md:text-right">
                                    <p class="text-lg font-black text-emerald-300">
                                        {{ formatPrice(cashier.current.revenue) }}
                                    </p>
                                    <p class="mt-1 text-xs" :class="growthClass(cashier.growth.revenue_percentage)">
                                        {{ growthLabel(cashier.growth.revenue_percentage) }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                            Highlight Growth
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Kasir dengan perubahan revenue paling terlihat.
                        </p>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article
                            v-for="cashier in cashiers.slice(0, 4)"
                            :key="`${cashier.id}-growth`"
                            class="rounded-2xl border px-4 py-4"
                            :class="growthSurfaceClass(cashier.growth.revenue_percentage)"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-white">{{ cashier.name }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Avg ticket {{ formatPrice(cashier.current.average_ticket) }}
                                    </p>
                                </div>
                                <p class="text-sm font-black" :class="growthClass(cashier.growth.revenue_percentage)">
                                    {{ growthLabel(cashier.growth.revenue_percentage) }}
                                </p>
                            </div>
                            <p class="mt-3 text-xs text-slate-300">
                                Selisih revenue {{ formatPrice(cashier.growth.revenue_amount) }} dan delta order
                                {{ cashier.growth.order_amount > 0 ? '+' : '' }}{{ cashier.growth.order_amount }}.
                            </p>
                        </article>
                    </div>
                </article>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-950/70 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-3 border-b border-white/10 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                            Tabel Performa Kasir
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Revenue, order, average ticket, diskon, growth, dan breakdown pembayaran per kasir.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-xs text-slate-300">
                        Growth dibanding {{ formatDate(period.previous.start_date) }} - {{ formatDate(period.previous.end_date) }}
                    </div>
                </div>

                <div v-if="!cashiers.length" class="px-5 py-10 text-center text-sm text-slate-400">
                    Tidak ada data kasir pada filter ini.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="cashier in cashiers"
                        :key="`${cashier.id}-table`"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[1.1fr_0.95fr_0.9fr_1.05fr]"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-white">{{ cashier.name }}</h3>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="growthSurfaceClass(cashier.growth.revenue_percentage)"
                                >
                                    Growth
                                </span>
                            </div>
                            <p class="text-sm text-slate-300">{{ cashier.outlet?.name || 'Tanpa outlet' }}</p>
                            <p class="text-xs text-slate-500">
                                Revenue sebelumnya {{ formatPrice(cashier.previous.revenue) }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Revenue saat ini</p>
                            <p class="text-lg font-black text-emerald-300">{{ formatPrice(cashier.current.revenue) }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Diskon</p>
                            <p class="text-sm text-amber-300">{{ formatPrice(cashier.current.discount) }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Jumlah order</p>
                            <p class="text-sm font-semibold text-white">{{ cashier.current.orders }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Average ticket</p>
                            <p class="text-sm text-slate-300">{{ formatPrice(cashier.current.average_ticket) }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Growth revenue</p>
                            <p class="text-lg font-black" :class="growthClass(cashier.growth.revenue_percentage)">
                                {{ growthLabel(cashier.growth.revenue_percentage) }}
                            </p>
                            <p class="text-xs text-slate-400">
                                Selisih {{ formatPrice(cashier.growth.revenue_amount) }}
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2">
                                <span
                                    v-for="payment in cashier.current.payment_breakdown"
                                    :key="`${cashier.id}-${payment.method}`"
                                    class="rounded-full border border-white/10 bg-white/[0.03] px-2.5 py-1 text-[10px] font-semibold text-slate-300"
                                >
                                    {{ paymentMethodLabel(payment.method) }} {{ formatPrice(payment.amount) }}
                                </span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
