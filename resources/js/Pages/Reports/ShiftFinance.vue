<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Banknote,
    BarChart3,
    ChevronDown,
    ChevronUp,
    Clock,
    Download,
    Filter,
    TrendingUp,
} from '@lucide/vue';
import { ref } from 'vue';

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps<{
    summary: {
        total_shifts: number;
        closed_shifts: number;
        active_shifts: number;
        total_revenue: number;
        total_orders: number;
        total_discount: number;
        avg_revenue_shift: number;
        cash_difference: number;
        breakdown: Record<string, number>;
    };
    dailyGroups: {
        date: string;
        date_label: string;
        total_shifts: number;
        total_revenue: number;
        total_orders: number;
        total_discount: number;
        breakdown: Record<string, number>;
        shifts: Array<{
            id: string;
            status: string;
            shift_number_of_day: number;
            opened_at: string | null;
            closed_at: string | null;
            duration_minutes: number | null;
            shift_template: {
                name: string;
                start_time: string;
                end_time: string;
            } | null;
            cashier: { id: string; name: string; role: string | null } | null;
            opener: { name: string } | null;
            closer: { name: string } | null;
            outlet: { id: string; name: string } | null;
            opening_cash: number;
            expected_cash: number;
            actual_cash: number | null;
            cash_difference: number | null;
            total_revenue: number;
            total_orders: number;
            total_discount: number;
            total_refund: number;
            breakdown: Record<string, number>;
            notes: string | null;
        }>;
    }[];
    filters: {
        period: string;
        start_date: string;
        end_date: string;
        outlet_id: string;
        user_id: string;
    };
    referenceData: {
        outlets: Array<{ id: string; name: string }>;
        cashiers: Array<{ id: string; name: string; role: string | null }>;
    };
}>();

// ─── State ────────────────────────────────────────────────────────────────────
const expandedDays = ref<Record<string, boolean>>({});
const selectedPeriod = ref(props.filters.period);
const selectedOutlet = ref(props.filters.outlet_id);
const selectedUser = ref(props.filters.user_id);
const dateFrom = ref(props.filters.start_date);
const dateTo = ref(props.filters.end_date);

// Auto-expand first day
if (props.dailyGroups.length > 0) {
    expandedDays.value[props.dailyGroups[0].date] = true;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
const formatPrice = (val: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(val);

const formatTime = (iso: string | null) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDuration = (minutes: number | null) => {
    if (!minutes) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h > 0 ? `${h}j ${m}m` : `${m}m`;
};

const initials = (name: string) =>
    name
        ?.split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase() ?? '?';

const METHOD_LABELS: Record<string, string> = {
    cash: 'Cash',
    qris: 'QRIS',
    debit: 'Debit',
    ewallet: 'E-Wallet',
    kasbon: 'Kasbon',
};
const METHOD_COLORS: Record<string, string> = {
    cash: 'bg-emerald-500',
    qris: 'bg-blue-500',
    debit: 'bg-violet-500',
    ewallet: 'bg-cyan-500',
    kasbon: 'bg-amber-500',
};
const METHOD_TEXT: Record<string, string> = {
    cash: 'text-emerald-600 dark:text-emerald-400',
    qris: 'text-blue-600 dark:text-blue-400',
    debit: 'text-violet-600 dark:text-violet-400',
    ewallet: 'text-cyan-600 dark:text-cyan-400',
    kasbon: 'text-amber-600 dark:text-amber-400',
};

const PERIOD_OPTIONS = [
    { value: 'daily', label: 'Harian (1 hari)' },
    { value: 'weekly', label: 'Mingguan' },
    { value: 'monthly', label: 'Bulanan' },
    { value: 'quarterly', label: 'Kuartal' },
    { value: 'yearly', label: 'Tahunan' },
    { value: 'custom', label: 'Custom Range' },
];

const dominantMethod = (breakdown: Record<string, number>) => {
    const entries = Object.entries(breakdown).filter(([, v]) => v > 0);
    if (!entries.length) return null;
    return entries.reduce((a, b) => (b[1] > a[1] ? b : a))[0];
};

const breakdownBar = (breakdown: Record<string, number>, total: number) => {
    return Object.entries(breakdown)
        .filter(([, v]) => v > 0)
        .map(([method, amount]) => ({
            method,
            amount,
            pct: total > 0 ? (amount / total) * 100 : 0,
        }));
};

// ─── Apply Filter ─────────────────────────────────────────────────────────────
const applyFilters = () => {
    router.get(
        route('reports.shift-finance.index'),
        {
            period: selectedPeriod.value,
            start_date: dateFrom.value,
            end_date: dateTo.value,
            outlet_id: selectedOutlet.value,
            user_id: selectedUser.value,
        },
        { preserveScroll: true, preserveState: false },
    );
};

const toggleDay = (date: string) => {
    expandedDays.value[date] = !expandedDays.value[date];
};

const cashDiffClass = (diff: number | null) => {
    if (diff === null) return 'text-stone-400 dark:text-slate-500';
    if (diff > 0) return 'text-emerald-600 dark:text-emerald-400';
    if (diff < 0) return 'text-rose-600 dark:text-rose-400';
    return 'text-stone-500 dark:text-slate-400';
};

const cashDiffBadgeClass = (diff: number | null) => {
    if (diff === null)
        return 'border-stone-200 dark:border-slate-700 text-stone-500 dark:text-slate-400';
    if (diff > 0)
        return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    if (diff < 0)
        return 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300';
    return 'border-stone-200 dark:border-slate-700 text-stone-500 dark:text-slate-400';
};
</script>

<template>
    <div class="min-h-screen bg-stone-50 dark:bg-slate-950">
        <!-- Header -->
        <div
            class="border-b border-stone-200 bg-white px-6 py-5 dark:border-slate-800 dark:bg-slate-900"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <span
                        class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-orange-600 dark:text-orange-400"
                    >
                        Laporan Keuangan
                    </span>
                    <h1
                        class="mt-2 text-2xl font-black text-stone-900 dark:text-white"
                    >
                        Rekap Keuangan Per Shift
                    </h1>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                        {{ filters.start_date }} s/d
                        {{ filters.end_date }} &bull;
                        {{ summary.total_shifts }} shift ({{
                            summary.closed_shifts
                        }}
                        selesai, {{ summary.active_shifts }} aktif)
                    </p>
                </div>
                <a
                    :href="
                        route('reports.exports.download', {
                            type: 'shifts',
                            ...filters,
                        })
                    "
                    class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-xs font-bold text-stone-700 transition hover:bg-stone-50 dark:border-slate-700 dark:bg-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    <Download class="h-4 w-4" />
                    Export
                </a>
            </div>
        </div>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6">
            <!-- Filter Bar -->
            <div
                class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <label
                            class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >Period</label
                        >
                        <select
                            v-model="selectedPeriod"
                            class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-900 outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        >
                            <option
                                v-for="opt in PERIOD_OPTIONS"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >Dari Tanggal</label
                        >
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-900 outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >Sampai Tanggal</label
                        >
                        <input
                            v-model="dateTo"
                            type="date"
                            class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-900 outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        />
                    </div>
                    <div v-if="referenceData.outlets.length > 1">
                        <label
                            class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >Outlet</label
                        >
                        <select
                            v-model="selectedOutlet"
                            class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-900 outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        >
                            <option value="">Semua Outlet</option>
                            <option
                                v-for="outlet in referenceData.outlets"
                                :key="outlet.id"
                                :value="outlet.id"
                            >
                                {{ outlet.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >Kasir</label
                        >
                        <select
                            v-model="selectedUser"
                            class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-900 outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        >
                            <option value="">Semua Kasir</option>
                            <option
                                v-for="c in referenceData.cashiers"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                    <button
                        @click="applyFilters"
                        class="flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-2 text-xs font-bold text-white transition hover:bg-orange-600 active:scale-95"
                    >
                        <Filter class="h-3.5 w-3.5" />
                        Terapkan
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div
                    class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-orange-500/10"
                        >
                            <TrendingUp class="h-4 w-4 text-orange-500" />
                        </div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                        >
                            Total Pendapatan
                        </p>
                    </div>
                    <p
                        class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                    >
                        {{ formatPrice(summary.total_revenue) }}
                    </p>
                    <p
                        class="mt-1 text-[10px] text-stone-500 dark:text-slate-500"
                    >
                        Diskon: {{ formatPrice(summary.total_discount) }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-500/10"
                        >
                            <BarChart3 class="h-4 w-4 text-blue-500" />
                        </div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                        >
                            Total Transaksi
                        </p>
                    </div>
                    <p
                        class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                    >
                        {{ summary.total_orders.toLocaleString('id-ID') }}
                    </p>
                    <p
                        class="mt-1 text-[10px] text-stone-500 dark:text-slate-500"
                    >
                        Avg/shift: {{ formatPrice(summary.avg_revenue_shift) }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-violet-500/10"
                        >
                            <Clock class="h-4 w-4 text-violet-500" />
                        </div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                        >
                            Total Shift
                        </p>
                    </div>
                    <p
                        class="mt-3 text-xl font-black text-stone-900 dark:text-white"
                    >
                        {{ summary.total_shifts }}
                    </p>
                    <p
                        class="mt-1 text-[10px] text-stone-500 dark:text-slate-500"
                    >
                        Selesai: {{ summary.closed_shifts }} &bull; Aktif:
                        {{ summary.active_shifts }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            :class="[
                                'flex h-8 w-8 items-center justify-center rounded-xl',
                                summary.cash_difference < 0
                                    ? 'bg-rose-500/10'
                                    : 'bg-emerald-500/10',
                            ]"
                        >
                            <Banknote
                                :class="[
                                    'h-4 w-4',
                                    summary.cash_difference < 0
                                        ? 'text-rose-500'
                                        : 'text-emerald-500',
                                ]"
                            />
                        </div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                        >
                            Selisih Kas (Total)
                        </p>
                    </div>
                    <p
                        :class="[
                            'mt-3 text-xl font-black',
                            cashDiffClass(summary.cash_difference),
                        ]"
                    >
                        {{ summary.cash_difference >= 0 ? '+' : ''
                        }}{{ formatPrice(summary.cash_difference) }}
                    </p>
                    <p
                        class="mt-1 text-[10px] text-stone-500 dark:text-slate-500"
                    >
                        Akumulasi semua shift tutup
                    </p>
                </div>
            </div>

            <!-- Payment Breakdown Summary -->
            <div
                class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
            >
                <p
                    class="mb-4 text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                >
                    Breakdown Metode Pembayaran (Periode)
                </p>
                <div class="flex flex-col gap-2">
                    <div
                        v-for="item in breakdownBar(
                            summary.breakdown,
                            summary.total_revenue,
                        )"
                        :key="item.method"
                        class="flex items-center gap-3"
                    >
                        <span
                            class="w-16 text-right text-[11px] font-semibold text-stone-600 dark:text-slate-400"
                            >{{ METHOD_LABELS[item.method] }}</span
                        >
                        <div
                            class="h-2.5 flex-1 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"
                        >
                            <div
                                :class="[
                                    'h-2.5 rounded-full transition-all duration-500',
                                    METHOD_COLORS[item.method],
                                ]"
                                :style="{ width: item.pct + '%' }"
                            ></div>
                        </div>
                        <span
                            :class="[
                                'w-24 text-right text-[11px] font-bold',
                                METHOD_TEXT[item.method],
                            ]"
                            >{{ formatPrice(item.amount) }}</span
                        >
                        <span
                            class="w-10 text-right text-[10px] text-stone-400 dark:text-slate-500"
                            >{{ item.pct.toFixed(1) }}%</span
                        >
                    </div>
                    <div
                        v-if="
                            !Object.values(summary.breakdown).some((v) => v > 0)
                        "
                        class="py-4 text-center text-xs text-stone-400 dark:text-slate-500"
                    >
                        Belum ada data pembayaran di periode ini.
                    </div>
                </div>
            </div>

            <!-- Daily Timeline -->
            <div
                v-if="dailyGroups.length === 0"
                class="rounded-2xl border border-dashed border-stone-300 py-20 text-center dark:border-slate-700"
            >
                <BarChart3
                    class="mx-auto mb-3 h-12 w-12 text-stone-300 dark:text-slate-600"
                />
                <p class="text-sm font-bold text-stone-500 dark:text-slate-400">
                    Tidak ada data shift di periode ini
                </p>
                <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">
                    Coba ubah filter tanggal atau outlet
                </p>
            </div>

            <div
                v-for="day in dailyGroups"
                :key="day.date"
                class="overflow-hidden rounded-2xl border border-stone-200 bg-white dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Day Header (clickable accordion) -->
                <button
                    @click="toggleDay(day.date)"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-stone-50 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-500/10"
                        >
                            <Clock class="h-4 w-4 text-orange-500" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-extrabold text-stone-900 dark:text-white"
                            >
                                {{ day.date_label }}
                            </p>
                            <p
                                class="text-[11px] text-stone-500 dark:text-slate-400"
                            >
                                {{ day.total_shifts }} shift &bull;
                                {{ day.total_orders }} transaksi
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                            >
                                Total Hari
                            </p>
                            <p
                                class="mt-0.5 text-lg font-black text-orange-600 dark:text-orange-400"
                            >
                                {{ formatPrice(day.total_revenue) }}
                            </p>
                        </div>
                        <!-- Mini breakdown pills -->
                        <div class="hidden items-center gap-1.5 md:flex">
                            <template
                                v-for="item in breakdownBar(
                                    day.breakdown,
                                    day.total_revenue,
                                ).slice(0, 3)"
                                :key="item.method"
                            >
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                        METHOD_TEXT[item.method],
                                        'bg-stone-100 dark:bg-slate-800',
                                    ]"
                                >
                                    {{ METHOD_LABELS[item.method] }}
                                    {{ item.pct.toFixed(0) }}%
                                </span>
                            </template>
                        </div>
                        <component
                            :is="
                                expandedDays[day.date] ? ChevronUp : ChevronDown
                            "
                            class="h-4 w-4 shrink-0 text-stone-400 dark:text-slate-500"
                        />
                    </div>
                </button>

                <!-- Day Detail (Shifts) -->
                <div
                    v-show="expandedDays[day.date]"
                    class="divide-y divide-stone-100 border-t border-stone-100 dark:divide-slate-800/60 dark:border-slate-800/80"
                >
                    <!-- Shift Rows -->
                    <div
                        v-for="shift in day.shifts"
                        :key="shift.id"
                        class="space-y-4 px-5 py-4"
                    >
                        <!-- Shift Header -->
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <!-- Kasir Avatar -->
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-400 to-red-500 text-sm font-black text-white shadow-md"
                                >
                                    {{ initials(shift.cashier?.name ?? '?') }}
                                </div>
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="text-sm font-extrabold text-stone-900 dark:text-white"
                                        >
                                            {{
                                                shift.cashier?.name ??
                                                'Kasir tidak diketahui'
                                            }}
                                        </span>
                                        <span
                                            class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400"
                                        >
                                            Shift ke-{{
                                                shift.shift_number_of_day
                                            }}
                                        </span>
                                        <span
                                            :class="[
                                                'rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                                shift.status === 'active'
                                                    ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                    : 'border-stone-200 text-stone-500 dark:border-slate-700 dark:text-slate-400',
                                            ]"
                                        >
                                            {{
                                                shift.status === 'active'
                                                    ? 'Aktif'
                                                    : 'Selesai'
                                            }}
                                        </span>
                                        <span
                                            v-if="shift.shift_template"
                                            class="text-[10px] text-stone-500 dark:text-slate-400"
                                        >
                                            {{ shift.shift_template.name }}
                                        </span>
                                    </div>
                                    <p
                                        class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                    >
                                        {{ formatTime(shift.opened_at) }} —
                                        {{
                                            shift.closed_at
                                                ? formatTime(shift.closed_at)
                                                : 'Aktif'
                                        }}
                                        <span
                                            v-if="shift.duration_minutes"
                                            class="ml-1.5 text-stone-400 dark:text-slate-500"
                                            >({{
                                                formatDuration(
                                                    shift.duration_minutes,
                                                )
                                            }})</span
                                        >
                                        <span v-if="shift.outlet" class="ml-2"
                                            >· {{ shift.outlet.name }}</span
                                        >
                                    </p>
                                </div>
                            </div>

                            <!-- Cash Difference Badge -->
                            <div
                                v-if="shift.status === 'closed'"
                                class="text-right"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                                >
                                    Selisih Kas
                                </p>
                                <span
                                    :class="[
                                        'mt-1 inline-block rounded-full border px-3 py-1 text-[11px] font-extrabold',
                                        cashDiffBadgeClass(
                                            shift.cash_difference,
                                        ),
                                    ]"
                                >
                                    {{
                                        shift.cash_difference !== null
                                            ? (shift.cash_difference >= 0
                                                  ? '+'
                                                  : '') +
                                              formatPrice(shift.cash_difference)
                                            : '—'
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Shift Metrics Grid -->
                        <div
                            class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5"
                        >
                            <div
                                class="rounded-xl border border-stone-100 bg-stone-50/50 p-3 dark:border-slate-800 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                                >
                                    Total Pendapatan
                                </p>
                                <p
                                    class="mt-1.5 text-sm font-extrabold text-stone-900 dark:text-white"
                                >
                                    {{ formatPrice(shift.total_revenue) }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-stone-100 bg-stone-50/50 p-3 dark:border-slate-800 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                                >
                                    Total Transaksi
                                </p>
                                <p
                                    class="mt-1.5 text-sm font-extrabold text-stone-900 dark:text-white"
                                >
                                    {{ shift.total_orders }} order
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-stone-100 bg-stone-50/50 p-3 dark:border-slate-800 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                                >
                                    Diskon
                                </p>
                                <p
                                    class="mt-1.5 text-sm font-extrabold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ formatPrice(shift.total_discount) }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-stone-100 bg-stone-50/50 p-3 dark:border-slate-800 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                                >
                                    Modal Awal
                                </p>
                                <p
                                    class="mt-1.5 text-sm font-extrabold text-stone-900 dark:text-white"
                                >
                                    {{ formatPrice(shift.opening_cash) }}
                                </p>
                            </div>
                            <div
                                v-if="shift.status === 'closed'"
                                class="rounded-xl border border-stone-100 bg-stone-50/50 p-3 dark:border-slate-800 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                                >
                                    Kas Aktual
                                </p>
                                <p
                                    class="mt-1.5 text-sm font-extrabold text-stone-900 dark:text-white"
                                >
                                    {{
                                        shift.actual_cash !== null
                                            ? formatPrice(shift.actual_cash)
                                            : '—'
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Payment Breakdown Mini Bar -->
                        <div v-if="shift.total_revenue > 0">
                            <p
                                class="mb-2 text-[9px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                            >
                                Breakdown Pembayaran
                            </p>
                            <div
                                class="flex h-3 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"
                            >
                                <div
                                    v-for="item in breakdownBar(
                                        shift.breakdown,
                                        shift.total_revenue,
                                    )"
                                    :key="item.method"
                                    :class="[METHOD_COLORS[item.method]]"
                                    :style="{ width: item.pct + '%' }"
                                    :title="`${METHOD_LABELS[item.method]}: ${formatPrice(item.amount)} (${item.pct.toFixed(1)}%)`"
                                ></div>
                            </div>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1"
                            >
                                <span
                                    v-for="item in breakdownBar(
                                        shift.breakdown,
                                        shift.total_revenue,
                                    )"
                                    :key="item.method"
                                    class="flex items-center gap-1.5 text-[10px]"
                                >
                                    <span
                                        :class="[
                                            'h-2 w-2 rounded-full',
                                            METHOD_COLORS[item.method],
                                        ]"
                                    ></span>
                                    <span
                                        class="text-stone-500 dark:text-slate-400"
                                        >{{ METHOD_LABELS[item.method] }}</span
                                    >
                                    <span
                                        :class="[
                                            'font-bold',
                                            METHOD_TEXT[item.method],
                                        ]"
                                        >{{ formatPrice(item.amount) }}</span
                                    >
                                </span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div
                            v-if="shift.notes"
                            class="rounded-xl border border-amber-200/60 bg-amber-50/60 px-4 py-3 dark:border-amber-500/15 dark:bg-amber-500/5"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400"
                            >
                                Catatan Shift
                            </p>
                            <p
                                class="mt-1 text-xs italic text-amber-900/80 dark:text-amber-100/70"
                            >
                                {{ shift.notes }}
                            </p>
                        </div>

                        <!-- Penutup Info -->
                        <div
                            v-if="shift.closer"
                            class="text-[10px] text-stone-400 dark:text-slate-500"
                        >
                            Ditutup oleh:
                            <span
                                class="font-semibold text-stone-600 dark:text-slate-300"
                                >{{ shift.closer.name }}</span
                            >
                        </div>
                    </div>

                    <!-- Day Footer Total -->
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 bg-stone-50 px-5 py-3 dark:bg-slate-950/60"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                        >
                            Total Pendapatan {{ day.date_label }}
                        </p>
                        <div class="flex items-center gap-4">
                            <span
                                class="text-[11px] text-stone-500 dark:text-slate-400"
                                >{{ day.total_orders }} transaksi</span
                            >
                            <span
                                class="text-base font-black text-orange-600 dark:text-orange-400"
                                >{{ formatPrice(day.total_revenue) }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
