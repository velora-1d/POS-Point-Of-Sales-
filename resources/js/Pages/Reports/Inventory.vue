<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    Boxes,
    CalendarRange,
    PackageCheck,
    Warehouse,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface OutletOption {
    id: string;
    name: string;
}

interface InventoryRow {
    type: 'product' | 'raw_material';
    id: string;
    name: string;
    outlet?: {
        id: string;
        name: string;
    } | null;
    context?: string | null;
    current_stock: number;
    minimum_stock: number;
    unit: string;
    status: 'healthy' | 'low' | 'out' | 'inactive';
    stock_value: number;
    last_restocked_at?: string | null;
    track_expired?: boolean;
}

interface ExpiryRow {
    id: string;
    type: 'product' | 'raw_material';
    name: string;
    outlet?: {
        id: string;
        name: string;
    } | null;
    context?: string | null;
    quantity: number;
    batch_code?: string | null;
    expired_at?: string | null;
    estimated_loss: number;
    is_resolved: boolean;
}

const page = usePage<any>();
const props = defineProps<{
    summary: {
        tracked_products: number;
        active_raw_materials: number;
        low_or_out_items: number;
        estimated_stock_value: number;
        restocked_in_period: number;
        expired_loss_estimate: number;
    };
    inventory: InventoryRow[];
    expiries: ExpiryRow[];
    filters: {
        start_date: string;
        end_date: string;
        outlet_id?: string;
        type?: string;
        status?: string;
    };
    referenceData: {
        outlets: OutletOption[];
    };
    period: {
        start_date: string;
        end_date: string;
    };
    limitations: {
        movement_logs: string;
        stock_opname: string;
    };
}>();

const user = computed(() => page.props.auth.user);
const startDateFilter = ref(props.filters.start_date);
const endDateFilter = ref(props.filters.end_date);
const outletFilter = ref(props.filters.outlet_id || '');
const typeFilter = ref(props.filters.type || 'all');
const statusFilter = ref(props.filters.status || 'all');
const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);

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

const formatDateTime = (value?: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const typeLabel = (type: string) => {
    return type === 'product' ? 'Produk' : 'Bahan baku';
};

const statusLabel = (status: string) => {
    switch (status) {
        case 'healthy':
            return 'Sehat';
        case 'low':
            return 'Menipis';
        case 'out':
            return 'Habis';
        case 'inactive':
            return 'Nonaktif';
        default:
            return status;
    }
};

const statusClass = (status: string) => {
    switch (status) {
        case 'healthy':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-550/20 dark:bg-emerald-500/10 dark:text-emerald-400';
        case 'low':
            return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-550/20 dark:bg-amber-500/10 dark:text-amber-500';
        case 'out':
            return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-550/20 dark:bg-rose-500/10 dark:text-rose-450';
        case 'inactive':
            return 'border-stone-200 bg-stone-50 text-stone-700 dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-350';
        default:
            return 'border-stone-200 bg-white text-stone-700 dark:border-white/10 dark:bg-slate-900/60 dark:text-slate-300';
    }
};

const submitFilters = () => {
    router.get(
        route('reports.inventory.index'),
        {
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
            outlet_id: outletFilter.value || undefined,
            type: typeFilter.value || undefined,
            status: statusFilter.value || undefined,
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
    typeFilter.value = 'all';
    statusFilter.value = 'all';
    submitFilters();
};

const summaryCards = computed(() => [
    {
        label: 'Nilai Stok Estimasi',
        value: formatPrice(props.summary.estimated_stock_value),
        helper: 'Produk tracked + bahan baku aktif',
        icon: Warehouse,
        tone: 'text-emerald-600 dark:text-emerald-400',
        surface: 'border-emerald-250 bg-emerald-50/50 dark:border-emerald-500/10 dark:bg-emerald-950/20',
    },
    {
        label: 'Item Low / Habis',
        value: props.summary.low_or_out_items,
        helper: 'Gabungan produk dan bahan baku',
        icon: AlertTriangle,
        tone: 'text-amber-600 dark:text-amber-400',
        surface: 'border-amber-250 bg-amber-50/50 dark:border-amber-500/10 dark:bg-amber-950/20',
    },
    {
        label: 'Restock di Periode',
        value: props.summary.restocked_in_period,
        helper: 'Berdasarkan last_restocked_at',
        icon: PackageCheck,
        tone: 'text-sky-650 dark:text-sky-400',
        surface: 'border-sky-250 bg-sky-50/50 dark:border-sky-550/15 dark:bg-sky-950/20',
    },
    {
        label: 'Kerugian Expired',
        value: formatPrice(props.summary.expired_loss_estimate),
        helper: 'Estimasi nilai batch expired di periode ini',
        icon: Archive,
        tone: 'text-rose-650 dark:text-rose-455',
        surface: 'border-rose-250 bg-rose-50/50 dark:border-rose-550/15 dark:bg-rose-950/20',
    },
    {
        label: 'Item Produk/Bahan',
        value: `${props.summary.tracked_products}/${props.summary.active_raw_materials}`,
        helper: 'Produk tracked / bahan baku aktif',
        icon: Boxes,
        tone: 'text-violet-650 dark:text-violet-400',
        surface: 'border-violet-250 bg-violet-50/50 dark:border-violet-550/15 dark:bg-violet-950/20',
    },
]);
</script>

<template>
    <Head title="Laporan Stok & Inventori" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"
            >
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Laporan Stok & Inventori
                    </h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400 font-semibold">
                        Snapshot stok saat ini dan exposure expired untuk periode
                        <span class="font-bold text-orange-600 dark:text-orange-400">
                            {{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span
                        class="rounded-full border-2 border-stone-200 bg-stone-500/5 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-stone-600 dark:border-white/10 dark:text-slate-300"
                    >
                        {{ user?.role || '-' }}
                    </span>
                    <Link
                        :href="route('reports.top-products.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border-2 border-stone-200 bg-white/[0.03] px-4 py-2.5 text-xs font-bold text-stone-800 transition hover:border-stone-200 hover:bg-white/[0.05] dark:border-white/10 dark:text-slate-200"
                    >
                        <CalendarRange class="h-4 w-4 text-sky-500" />
                        Lihat Produk Terlaris
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tab Navigation Laporan Operasional -->
            <div
                class="flex w-full flex-wrap gap-2 rounded-2xl border-2 border-stone-200 bg-stone-100 p-1.5 dark:border-white/10 dark:bg-slate-950"
            >
                <Link
                    :href="route('reports.inventory.index')"
                    class="min-w-[120px] flex-1 rounded-xl py-2.5 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('reports.inventory.index')
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Stok & Inventori
                </Link>
                <Link
                    :href="route('reports.attendance-shifts.index')"
                    class="min-w-[120px] flex-1 rounded-xl py-2.5 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('reports.attendance-shifts.index')
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Absensi & Shift
                </Link>
                <Link
                    :href="route('reports.exports.index')"
                    class="min-w-[120px] flex-1 rounded-xl py-2.5 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('reports.exports.index')
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Export Data
                </Link>
            </div>

            <!-- Limitasi Banner -->
            <section
                class="rounded-3xl border-2 border-amber-300 bg-amber-50 p-5 text-sm text-stone-900 shadow-xl dark:border-amber-500/20 dark:bg-amber-950/20 dark:text-amber-100"
            >
                <p class="font-extrabold text-amber-800 dark:text-amber-300">{{ limitations.movement_logs }}</p>
                <p class="mt-2 text-stone-600 dark:text-amber-250 font-medium">
                    {{ limitations.stock_opname }}
                </p>
            </section>

            <!-- Filter Panel -->
            <section
                class="rounded-3xl border-2 border-stone-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <label v-if="canChooseOutlet" class="block">
                            <span
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Outlet</span
                            >
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-50 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
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
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Dari tanggal</span
                            >
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-50 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Sampai tanggal</span
                            >
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-50 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Tipe inventori</span
                            >
                            <select
                                v-model="typeFilter"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-50 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option value="all">Semua</option>
                                <option value="product">Produk</option>
                                <option value="raw_material">Bahan baku</option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Status stok</span
                            >
                            <select
                                v-model="statusFilter"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-50 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option value="all">Semua</option>
                                <option value="healthy">Sehat</option>
                                <option value="low">Menipis</option>
                                <option value="out">Habis</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex items-center gap-2 self-stretch lg:self-auto pt-2">
                        <button
                            type="button"
                            class="flex-1 rounded-2xl border-2 border-stone-200 bg-transparent px-4 py-2.5 text-xs font-bold text-stone-75 transition hover:bg-stone-100 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5"
                            @click="clearFilters"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-2xl border-2 border-transparent bg-orange-500 px-5 py-2.5 text-xs font-black uppercase tracking-wider text-stone-950 transition hover:bg-orange-400"
                            @click="submitFilters"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </section>

            <!-- Summary Cards -->
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border-2 p-5 shadow-lg flex flex-col justify-between"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-3 text-2xl font-black"
                                :class="card.tone"
                            >
                                {{ card.value }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border-2 border-stone-200 bg-stone-50 p-2.5 dark:border-white/10 dark:bg-slate-900/80"
                        >
                            <component
                                :is="card.icon"
                                class="h-5 w-5 text-stone-900 dark:text-white"
                            />
                        </div>
                    </div>
                    <p class="mt-4 text-[11px] font-semibold text-stone-600 dark:text-slate-350">
                        {{ card.helper }}
                    </p>
                </article>
            </section>

            <!-- Grid: Snapshot & Expiry -->
            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <!-- Snapshot Inventori -->
                <article
                    class="rounded-3xl border-2 border-stone-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
                >
                    <div>
                        <h3
                            class="text-xs font-black uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            Snapshot Inventori
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-500 font-semibold"
                        >
                            Daftar item inventori terurut berdasarkan nilai stok estimasi (maks. 8 item).
                        </p>
                    </div>

                    <div
                        v-if="!inventory.length"
                        class="mt-5 rounded-2xl border-2 border-dashed border-stone-200 px-4 py-10 text-center text-xs font-bold text-stone-500 dark:border-white/10 dark:text-slate-400"
                    >
                        Tidak ada item inventori pada filter ini.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <article
                            v-for="item in inventory.slice(0, 8)"
                            :key="`${item.type}-${item.id}`"
                            class="rounded-2xl border-2 border-stone-200 bg-stone-50/50 px-4 py-4 dark:border-white/10 dark:bg-slate-900/30"
                        >
                            <div
                                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p
                                            class="text-sm font-black text-stone-900 dark:text-white"
                                        >
                                            {{ item.name }}
                                        </p>
                                        <span
                                            class="rounded-full border-2 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-[0.18em]"
                                            :class="statusClass(item.status)"
                                        >
                                            {{ statusLabel(item.status) }}
                                        </span>
                                    </div>
                                    <p
                                        class="mt-1 text-xs text-stone-500 dark:text-slate-400 font-semibold"
                                    >
                                        {{ typeLabel(item.type) }} •
                                        {{ item.context }} •
                                        {{ item.outlet?.name || 'Tanpa outlet' }}
                                    </p>
                                </div>
                                <div class="md:text-right">
                                    <p
                                        class="text-lg font-black text-emerald-600 dark:text-emerald-450"
                                    >
                                        {{ formatPrice(item.stock_value) }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[11px] text-stone-600 dark:text-slate-455 font-bold"
                                    >
                                        {{ item.current_stock }}
                                        {{ item.unit }} / min
                                        {{ item.minimum_stock }} {{ item.unit }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <!-- Batch Expired -->
                <article
                    class="rounded-3xl border-2 border-stone-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
                >
                    <div>
                        <h3
                            class="text-xs font-black uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            Batch Expired di Periode
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-500 font-semibold"
                        >
                            Exposure item expired berdasarkan catatan `inventory_expiries` (maks. 6 batch).
                        </p>
                    </div>

                    <div
                        v-if="!expiries.length"
                        class="mt-5 rounded-2xl border-2 border-dashed border-stone-200 px-4 py-10 text-center text-xs font-bold text-stone-500 dark:border-white/10 dark:text-slate-400"
                    >
                        Tidak ada batch expired pada periode terpilih.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <article
                            v-for="expiry in expiries.slice(0, 6)"
                            :key="expiry.id"
                            class="rounded-2xl border-2 border-rose-200 bg-rose-50/60 px-4 py-4 dark:border-rose-500/20 dark:bg-rose-950/20"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-sm font-black text-stone-900 dark:text-white"
                                    >
                                        {{ expiry.name }}
                                    </p>
                                    <p class="mt-1 text-xs text-stone-600 dark:text-rose-200/90 font-medium">
                                        {{ typeLabel(expiry.type) }} •
                                        {{ expiry.context }} •
                                        {{ formatDate(expiry.expired_at) }}
                                    </p>
                                </div>
                                <p class="text-sm font-black text-rose-700 dark:text-rose-400">
                                    {{ formatPrice(expiry.estimated_loss) }}
                                </p>
                            </div>
                            <p class="mt-2 text-xs text-stone-600 dark:text-rose-200/80 font-semibold border-t border-rose-200/40 dark:border-rose-500/20 pt-2">
                                Qty {{ expiry.quantity }} • Batch
                                {{ expiry.batch_code || '-' }} •
                                {{ expiry.outlet?.name || 'Tanpa outlet' }}
                            </p>
                        </article>
                    </div>
                </article>
            </section>

            <!-- Tabel Detail -->
            <section
                class="rounded-3xl border-2 border-stone-200 bg-white shadow-xl dark:border-white/10 dark:bg-slate-950/45"
            >
                <div
                    class="flex flex-col gap-3 border-b-2 border-stone-200 px-5 py-4 dark:border-white/10 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <h3
                            class="text-xs font-black uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            Tabel Inventori Detail
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-500 font-semibold"
                        >
                            Snapshot stok live dari produk tracked dan bahan baku aktif/nonaktif.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-2.5 text-xs font-bold text-stone-600 dark:border-white/10 dark:bg-slate-900/60 dark:text-slate-300"
                    >
                        Expired memakai periode
                        {{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }}
                    </div>
                </div>

                <div
                    v-if="!inventory.length"
                    class="px-5 py-10 text-center text-xs font-bold text-stone-500 dark:text-slate-455"
                >
                    Tidak ada data inventori pada filter ini.
                </div>

                <div v-else class="divide-y-2 divide-stone-200 dark:divide-white/5">
                    <article
                        v-for="item in inventory"
                        :key="`table-${item.type}-${item.id}`"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[1.15fr_0.8fr_0.9fr_1fr]"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <h3
                                    class="text-base font-black text-stone-900 dark:text-white"
                                >
                                    {{ item.name }}
                                </h3>
                                <span
                                    class="rounded-full border-2 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-[0.18em]"
                                    :class="statusClass(item.status)"
                                >
                                    {{ statusLabel(item.status) }}
                                </span>
                            </div>
                            <p
                                class="text-sm font-semibold text-stone-700 dark:text-slate-300"
                            >
                                {{ item.context || '-' }}
                            </p>
                            <p
                                class="text-xs text-stone-500 dark:text-slate-455 font-bold"
                            >
                                {{ typeLabel(item.type) }} •
                                {{ item.outlet?.name || 'Tanpa outlet' }}
                            </p>
                        </div>

                        <div class="space-y-2 border-l-0 xl:border-l-2 xl:pl-4 border-stone-200 dark:border-white/5">
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Stok saat ini
                            </p>
                            <p class="text-lg font-black text-emerald-600 dark:text-emerald-450">
                                {{ item.current_stock }} {{ item.unit }}
                            </p>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-455"
                            >
                                Minimum Stok
                            </p>
                            <p
                                class="text-xs font-bold text-stone-600 dark:text-slate-350"
                            >
                                {{ item.minimum_stock }} {{ item.unit }}
                            </p>
                        </div>

                        <div class="space-y-2 border-l-0 xl:border-l-2 xl:pl-4 border-stone-200 dark:border-white/5">
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Nilai stok
                            </p>
                            <p
                                class="text-sm font-black text-stone-900 dark:text-white"
                            >
                                {{ formatPrice(item.stock_value) }}
                            </p>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-455"
                            >
                                Tracking expired
                            </p>
                            <p
                                class="text-xs font-bold text-stone-600 dark:text-slate-350"
                            >
                                {{ item.track_expired ? 'Ya' : 'Tidak' }}
                            </p>
                        </div>

                        <div class="space-y-2 border-l-0 xl:border-l-2 xl:pl-4 border-stone-200 dark:border-white/5">
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.18em] text-stone-500 dark:text-slate-455"
                            >
                                Restock terakhir
                            </p>
                            <p
                                class="text-xs font-bold text-stone-800 dark:text-slate-300"
                            >
                                {{ formatDateTime(item.last_restocked_at) }}
                            </p>
                            <p
                                class="text-[11px] text-stone-500 dark:text-slate-455 font-medium"
                            >
                                Digunakan sebagai indikator restock di periode, bukan log mutasi detail.
                            </p>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
