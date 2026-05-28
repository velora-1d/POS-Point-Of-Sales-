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
            return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300';
        case 'low':
            return 'border-amber-500/20 bg-amber-500/10 text-amber-300';
        case 'out':
            return 'border-rose-500/20 bg-rose-500/10 text-rose-300';
        case 'inactive':
            return 'border-slate-600/40 bg-slate-800/80 text-slate-300';
        default:
            return 'border-white/10 bg-white/[0.03] text-slate-300';
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

    startDateFilter.value = currentDateInput(new Date(now.getFullYear(), now.getMonth(), 1));
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
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
    },
    {
        label: 'Item Low / Habis',
        value: props.summary.low_or_out_items,
        helper: 'Gabungan produk dan bahan baku',
        icon: AlertTriangle,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
    },
    {
        label: 'Restock di Periode',
        value: props.summary.restocked_in_period,
        helper: 'Berdasarkan last_restocked_at',
        icon: PackageCheck,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
    },
    {
        label: 'Kerugian Expired',
        value: formatPrice(props.summary.expired_loss_estimate),
        helper: 'Estimasi nilai batch expired di periode ini',
        icon: Archive,
        tone: 'text-rose-300',
        surface: 'border-rose-400/15 bg-rose-500/10',
    },
    {
        label: 'Item Produk/Bahan',
        value: `${props.summary.tracked_products}/${props.summary.active_raw_materials}`,
        helper: 'Produk tracked / bahan baku aktif',
        icon: Boxes,
        tone: 'text-violet-300',
        surface: 'border-violet-400/15 bg-violet-500/10',
    },
]);
</script>

<template>
    <Head title="Laporan Stok & Inventori" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Laporan Stok & Inventori
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Snapshot stok saat ini dan exposure expired untuk periode
                        <span class="font-semibold text-orange-300">
                            {{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-300">
                        {{ user?.role || '-' }}
                    </span>
                    <Link
                        :href="route('reports.top-products.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-2.5 text-xs font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4 text-sky-300" />
                        Lihat Produk Terlaris
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="rounded-3xl border border-amber-400/15 bg-amber-500/10 p-5 text-sm text-amber-100 shadow-[0_20px_50px_rgba(120,53,15,0.18)]">
                <p class="font-semibold">{{ limitations.movement_logs }}</p>
                <p class="mt-2 text-amber-200/90">{{ limitations.stock_opname }}</p>
            </section>

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

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tipe inventori</span>
                            <select
                                v-model="typeFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="all">Semua</option>
                                <option value="product">Produk</option>
                                <option value="raw_material">Bahan baku</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Status stok</span>
                            <select
                                v-model="statusFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="all">Semua</option>
                                <option value="healthy">Sehat</option>
                                <option value="low">Menipis</option>
                                <option value="out">Habis</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
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

            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                            Snapshot Inventori
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Daftar item inventori terurut berdasarkan nilai stok estimasi.
                        </p>
                    </div>

                    <div v-if="!inventory.length" class="mt-5 rounded-2xl border border-dashed border-white/10 px-4 py-10 text-center text-sm text-slate-400">
                        Tidak ada item inventori pada filter ini.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <article
                            v-for="item in inventory.slice(0, 8)"
                            :key="`${item.type}-${item.id}`"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-black text-white">{{ item.name }}</p>
                                        <span
                                            class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                            :class="statusClass(item.status)"
                                        >
                                            {{ statusLabel(item.status) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ typeLabel(item.type) }} • {{ item.context }} • {{ item.outlet?.name || 'Tanpa outlet' }}
                                    </p>
                                </div>
                                <div class="md:text-right">
                                    <p class="text-lg font-black text-emerald-300">{{ formatPrice(item.stock_value) }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ item.current_stock }} {{ item.unit }} / min {{ item.minimum_stock }} {{ item.unit }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                            Batch Expired di Periode
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Exposure item expired berdasarkan catatan `inventory_expiries`.
                        </p>
                    </div>

                    <div v-if="!expiries.length" class="mt-5 rounded-2xl border border-dashed border-white/10 px-4 py-10 text-center text-sm text-slate-400">
                        Tidak ada batch expired pada periode terpilih.
                    </div>

                    <div v-else class="mt-5 space-y-3">
                        <article
                            v-for="expiry in expiries.slice(0, 6)"
                            :key="expiry.id"
                            class="rounded-2xl border border-rose-400/15 bg-rose-500/10 px-4 py-4"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-white">{{ expiry.name }}</p>
                                    <p class="mt-1 text-xs text-rose-100/80">
                                        {{ typeLabel(expiry.type) }} • {{ expiry.context }} • {{ formatDate(expiry.expired_at) }}
                                    </p>
                                </div>
                                <p class="text-sm font-black text-rose-200">
                                    {{ formatPrice(expiry.estimated_loss) }}
                                </p>
                            </div>
                            <p class="mt-2 text-xs text-rose-100/80">
                                Qty {{ expiry.quantity }} • Batch {{ expiry.batch_code || '-' }} • {{ expiry.outlet?.name || 'Tanpa outlet' }}
                            </p>
                        </article>
                    </div>
                </article>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-950/70 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-3 border-b border-white/10 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                            Tabel Inventori
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Snapshot stok live dari produk tracked dan bahan baku aktif/nonaktif yang ada sekarang.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-xs text-slate-300">
                        Expired memakai periode {{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }}
                    </div>
                </div>

                <div v-if="!inventory.length" class="px-5 py-10 text-center text-sm text-slate-400">
                    Tidak ada data inventori pada filter ini.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="item in inventory"
                        :key="`table-${item.type}-${item.id}`"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[1.15fr_0.8fr_0.9fr_1fr]"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-white">{{ item.name }}</h3>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="statusClass(item.status)"
                                >
                                    {{ statusLabel(item.status) }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-300">{{ item.context }}</p>
                            <p class="text-xs text-slate-500">
                                {{ typeLabel(item.type) }} • {{ item.outlet?.name || 'Tanpa outlet' }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Stok saat ini</p>
                            <p class="text-lg font-black text-emerald-300">{{ item.current_stock }} {{ item.unit }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Minimum</p>
                            <p class="text-sm text-slate-300">{{ item.minimum_stock }} {{ item.unit }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Nilai stok</p>
                            <p class="text-sm font-semibold text-white">{{ formatPrice(item.stock_value) }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Tracking expired</p>
                            <p class="text-sm text-slate-300">{{ item.track_expired ? 'Ya' : 'Tidak' }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Restock terakhir</p>
                            <p class="text-sm text-slate-300">{{ formatDateTime(item.last_restocked_at) }}</p>
                            <p class="text-xs text-slate-500">
                                Digunakan sebagai indikator restock di periode, bukan log mutasi detail.
                            </p>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
