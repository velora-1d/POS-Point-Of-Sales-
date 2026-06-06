<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Globe, ReceiptText, Store, Timer, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';

interface OutletOption {
    id: string;
    name: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface OnlineOrderItem {
    id: string;
    product_name: string;
    variant_name?: string | null;
    quantity: number;
    unit_price: number;
    total_price: number;
    notes?: string | null;
}

interface OnlineOrderRow {
    id: string;
    order_number: string;
    status: string;
    source: string;
    external_order_id?: string | null;
    external_platform?: string | null;
    subtotal: number;
    discount_amount: number;
    total_amount: number;
    paid_amount: number;
    notes?: string | null;
    estimated_time: number;
    created_at?: string | null;
    updated_at?: string | null;
    customer?: {
        id: string;
        name: string;
        phone?: string | null;
    } | null;
    outlet?: {
        id: string;
        name: string;
    } | null;
    online_sync?: {
        internal_status?: string | null;
        platform_status?: string | null;
        platform?: string | null;
        transport?: string | null;
        synced_at?: string | null;
        notes?: string | null;
        history_count?: number;
    } | null;
    items: OnlineOrderItem[];
}

interface OnlineOrderHistoryRow {
    id: string;
    order_number?: string | null;
    external_order_id?: string | null;
    platform?: string | null;
    current_status?: string | null;
    from_status?: string | null;
    to_status: string;
    changed_by_name?: string | null;
    changed_by_type?: string | null;
    notes?: string | null;
    created_at?: string | null;
    customer_name?: string | null;
    outlet_name?: string | null;
    sync_history_count?: number;
}

const props = defineProps<{
    summary: {
        total_orders: number;
        total_revenue: number;
        gofood_orders: number;
        grabfood_orders: number;
        shopeefood_orders: number;
        maximfood_orders: number;
        pending_orders: number;
    };
    orders: {
        data: OnlineOrderRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    history: OnlineOrderHistoryRow[];
    filters: {
        platform?: string;
        status?: string;
        outlet_id?: string;
        start_date?: string;
        end_date?: string;
        per_page?: number;
    };
    referenceData: {
        outlets: OutletOption[];
    };
    success?: string | null;
}>();

const platformFilter = ref(props.filters.platform || '');
const statusFilter = ref(props.filters.status || '');
const outletFilter = ref(props.filters.outlet_id || '');
const startDateFilter = ref(
    props.filters.start_date || new Date().toISOString().slice(0, 10),
);
const endDateFilter = ref(
    props.filters.end_date || new Date().toISOString().slice(0, 10),
);

const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);

const summaryCards = computed(() => [
    {
        label: 'Order Online',
        value: props.summary.total_orders,
        helper: `${props.summary.pending_orders} order masih aktif`,
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
        icon: ReceiptText,
    },
    {
        label: 'Revenue Online',
        value: formatPrice(props.summary.total_revenue),
        helper: 'Akumulasi total order online',
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: Wallet,
    },
    {
        label: 'GoFood',
        value: props.summary.gofood_orders,
        helper: 'Order dari GoFood',
        tone: 'text-orange-300',
        surface: 'border-orange-400/15 bg-orange-500/10',
        icon: Store,
    },
    {
        label: 'GrabFood',
        value: props.summary.grabfood_orders,
        helper: 'Order dari GrabFood',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Globe,
    },
    {
        label: 'ShopeeFood',
        value: props.summary.shopeefood_orders || 0,
        helper: 'Order dari ShopeeFood',
        tone: 'text-orange-400',
        surface: 'border-orange-500/15 bg-orange-500/10',
        icon: Store,
    },
    {
        label: 'Maxim Food',
        value: props.summary.maximfood_orders || 0,
        helper: 'Order dari Maxim Food',
        tone: 'text-yellow-400',
        surface: 'border-yellow-500/15 bg-yellow-500/10',
        icon: Globe,
    },
]);

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

const platformLabel = (platform?: string | null) => {
    if (platform === 'gofood') return 'GoFood';
    if (platform === 'grabfood') return 'GrabFood';
    if (platform === 'shopeefood') return 'ShopeeFood';
    if (platform === 'maximfood') return 'Maxim Food';

    return platform || 'Online';
};

const platformClass = (platform?: string | null) => {
    if (platform === 'gofood') {
        return 'border-orange-400/20 bg-orange-500/10 text-orange-200';
    }

    if (platform === 'grabfood') {
        return 'border-sky-400/20 bg-sky-500/10 text-sky-200';
    }

    if (platform === 'shopeefood') {
        return 'border-orange-500/20 bg-orange-500/10 text-orange-300';
    }

    if (platform === 'maximfood') {
        return 'border-yellow-500/20 bg-yellow-500/10 text-yellow-300';
    }

    return 'border-stone-200 dark:border-white/10 bg-white/[0.03] text-stone-600 dark:text-slate-300';
};

const statusLabel = (status: string) => {
    switch (status) {
        case 'pending':
            return 'Pending';
        case 'in_progress':
            return 'Diproses';
        case 'waiting_bar_approval':
            return 'Menunggu Bar';
        case 'ready':
            return 'Ready';
        case 'completed':
            return 'Selesai';
        case 'cancelled':
            return 'Batal';
        default:
            return status;
    }
};

const statusClass = (status: string) => {
    if (
        ['pending', 'in_progress', 'waiting_bar_approval', 'ready'].includes(
            status,
        )
    ) {
        return 'border-amber-400/20 bg-amber-500/10 text-amber-200';
    }

    if (status === 'completed') {
        return 'border-emerald-400/20 bg-emerald-500/10 text-emerald-200';
    }

    return 'border-rose-400/20 bg-rose-500/10 text-rose-200';
};

const platformStatusLabel = (status?: string | null) => {
    switch (status) {
        case 'accepted':
            return 'Accepted';
        case 'preparing':
            return 'Preparing';
        case 'almost_ready':
            return 'Almost Ready';
        case 'ready_for_pickup':
            return 'Ready for Pickup';
        case 'completed':
            return 'Completed';
        case 'cancelled':
            return 'Cancelled';
        default:
            return status || 'Belum ada sync';
    }
};

const syncTransportLabel = (transport?: string | null) => {
    if (transport === 'stub') return 'Stub';

    return transport || '-';
};

const submitFilters = () => {
    router.get(
        route('online-orders.index'),
        {
            platform: platformFilter.value || undefined,
            status: statusFilter.value || undefined,
            outlet_id: outletFilter.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    platformFilter.value = '';
    statusFilter.value = '';
    outletFilter.value = '';
    startDateFilter.value = new Date().toISOString().slice(0, 10);
    endDateFilter.value = new Date().toISOString().slice(0, 10);
    submitFilters();
};
</script>

<template>
    <Head title="Order Online" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Order Online
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Pantau inbox order online, status sync platform
                        terakhir, dan riwayat perubahan status order GoFood atau
                        GrabFood dalam satu halaman.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <div
                v-if="success"
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/12 px-4 py-3 text-sm font-medium text-emerald-300"
            >
                {{ success }}
            </div>

            <section
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
            >
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-2xl border p-4 shadow-[0_18px_50px_rgba(15,23,42,0.16)]"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-3 text-2xl font-black"
                                :class="card.tone"
                            >
                                {{ card.value }}
                            </p>
                            <p
                                class="mt-2 text-xs text-stone-400 dark:text-slate-500"
                            >
                                {{ card.helper }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-stone-900 dark:border-white/10 dark:bg-slate-950/40 dark:text-white"
                        >
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <section
                class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div
                        class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-5"
                    >
                        <label v-if="canChooseOutlet" class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Outlet</span
                            >
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
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
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Platform</span
                            >
                            <select
                                v-model="platformFilter"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua platform</option>
                                <option value="gofood">GoFood</option>
                                <option value="grabfood">GrabFood</option>
                                <option value="shopeefood">ShopeeFood</option>
                                <option value="maximfood">Maxim Food</option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Status</span
                            >
                            <select
                                v-model="statusFilter"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="waiting_bar_approval">
                                    Waiting Bar
                                </option>
                                <option value="ready">Ready</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Dari tanggal</span
                            >
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Sampai tanggal</span
                            >
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            />
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-stone-200 px-4 py-3 text-sm font-semibold text-stone-800 transition hover:border-stone-200 hover:bg-stone-100 dark:border-white/10 dark:border-white/20 dark:bg-white/5 dark:text-slate-200"
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

            <section
                class="rounded-3xl border border-stone-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex items-center justify-between border-b border-stone-200 px-5 py-4 dark:border-white/10"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            Inbox Order Online
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                        >
                            Menampilkan {{ orders.from ?? 0 }} -
                            {{ orders.to ?? 0 }} dari {{ orders.total }} order
                            online.
                        </p>
                    </div>
                </div>

                <div
                    v-if="!orders.data.length"
                    class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400"
                >
                    Belum ada order online pada filter ini.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="order in orders.data"
                        :key="order.id"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[0.9fr_0.85fr_1.25fr]"
                    >
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    class="text-base font-black text-stone-900 dark:text-white"
                                >
                                    {{ order.order_number }}
                                </h3>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="
                                        platformClass(order.external_platform)
                                    "
                                >
                                    {{ platformLabel(order.external_platform) }}
                                </span>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="statusClass(order.status)"
                                >
                                    {{ statusLabel(order.status) }}
                                </span>
                            </div>

                            <div
                                class="space-y-1 text-sm text-stone-600 dark:text-slate-300"
                            >
                                <p>
                                    {{
                                        order.customer?.name ||
                                        'Customer Platform'
                                    }}
                                </p>
                                <p
                                    class="text-xs text-stone-400 dark:text-slate-500"
                                >
                                    External ID:
                                    {{ order.external_order_id || '-' }}
                                </p>
                                <p
                                    class="text-xs text-stone-400 dark:text-slate-500"
                                >
                                    Outlet: {{ order.outlet?.name || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 text-xs text-stone-500 dark:border-white/10 dark:text-slate-400"
                            >
                                <div
                                    class="flex items-center gap-2 text-stone-600 dark:text-slate-300"
                                >
                                    <Timer class="h-3.5 w-3.5" />
                                    Estimasi {{ order.estimated_time }} menit
                                </div>
                                <p class="mt-2">
                                    Masuk:
                                    {{ formatDateTime(order.created_at) }}
                                </p>
                                <p class="mt-1">
                                    Update terakhir:
                                    {{ formatDateTime(order.updated_at) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-sky-400/15 bg-sky-500/10 px-4 py-3 text-xs text-sky-100"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-[0.18em] text-sky-300"
                                    >
                                        Status Platform
                                    </span>
                                    <span
                                        class="rounded-full border border-sky-300/20 bg-sky-200/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-sky-100"
                                    >
                                        {{
                                            platformStatusLabel(
                                                order.online_sync
                                                    ?.platform_status,
                                            )
                                        }}
                                    </span>
                                </div>
                                <p class="mt-2">
                                    Sync terakhir:
                                    {{
                                        formatDateTime(
                                            order.online_sync?.synced_at,
                                        )
                                    }}
                                </p>
                                <p class="mt-1">
                                    Transport:
                                    {{
                                        syncTransportLabel(
                                            order.online_sync?.transport,
                                        )
                                    }}
                                    • Riwayat
                                    {{ order.online_sync?.history_count || 0 }}
                                    event
                                </p>
                                <p
                                    v-if="order.online_sync?.notes"
                                    class="mt-2 text-sky-50/90"
                                >
                                    {{ order.online_sync.notes }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Subtotal
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{ formatPrice(order.subtotal) }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Diskon
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{ formatPrice(order.discount_amount) }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Total Dibayar Platform
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-emerald-300"
                                >
                                    {{ formatPrice(order.paid_amount) }}
                                </p>
                            </div>
                            <div
                                v-if="order.notes"
                                class="rounded-2xl border border-amber-400/15 bg-amber-500/10 px-4 py-3 text-xs text-amber-100"
                            >
                                {{ order.notes }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Item Order
                                </p>
                            </div>

                            <article
                                v-for="item in order.items"
                                :key="item.id"
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 dark:border-white/10"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-bold text-stone-900 dark:text-white"
                                        >
                                            {{ item.product_name }}
                                            <span
                                                v-if="item.variant_name"
                                                class="text-stone-500 dark:text-slate-400"
                                            >
                                                • {{ item.variant_name }}
                                            </span>
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                                        >
                                            Qty {{ item.quantity }} ×
                                            {{ formatPrice(item.unit_price) }}
                                        </p>
                                        <p
                                            v-if="item.notes"
                                            class="mt-2 text-xs text-amber-200"
                                        >
                                            {{ item.notes }}
                                        </p>
                                    </div>
                                    <p
                                        class="text-sm font-bold text-emerald-300"
                                    >
                                        {{ formatPrice(item.total_price) }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </article>
                </div>

                <div
                    v-if="orders.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-5 py-4 dark:border-white/10"
                >
                    <p class="text-xs text-stone-400 dark:text-slate-500">
                        Inbox dipaginasi agar monitoring order online tetap
                        ringan.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in orders.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                            :class="
                                link.active
                                    ? 'border-orange-400/30 bg-orange-500/15 text-orange-100'
                                    : 'border-stone-200 text-stone-600 hover:bg-stone-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'
                            "
                        >
                            <span v-html="link.label"></span>
                        </Link>
                    </div>
                </div>
            </section>

            <section
                class="rounded-3xl border border-stone-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex items-center justify-between border-b border-stone-200 px-5 py-4 dark:border-white/10"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            Riwayat Order Online
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                        >
                            Timeline perubahan status terbaru untuk order GoFood
                            dan GrabFood sesuai filter aktif.
                        </p>
                    </div>
                </div>

                <div
                    v-if="!history.length"
                    class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400"
                >
                    Belum ada riwayat order online pada filter ini.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="entry in history"
                        :key="entry.id"
                        class="grid gap-4 px-5 py-5 lg:grid-cols-[0.9fr_1.4fr_0.7fr]"
                    >
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <p
                                    class="text-sm font-black text-stone-900 dark:text-white"
                                >
                                    {{ entry.order_number || '-' }}
                                </p>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="platformClass(entry.platform)"
                                >
                                    {{ platformLabel(entry.platform) }}
                                </span>
                            </div>
                            <p
                                class="text-xs text-stone-400 dark:text-slate-500"
                            >
                                External ID:
                                {{ entry.external_order_id || '-' }}
                            </p>
                            <p
                                class="text-xs text-stone-500 dark:text-slate-400"
                            >
                                {{ entry.customer_name || 'Customer Platform' }}
                                • {{ entry.outlet_name || '-' }}
                            </p>
                        </div>

                        <div
                            class="space-y-2 rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 dark:border-white/10"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="entry.from_status"
                                    class="rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-stone-600 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-300"
                                >
                                    {{ statusLabel(entry.from_status) }}
                                </span>
                                <span
                                    class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    ke
                                </span>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em]"
                                    :class="statusClass(entry.to_status)"
                                >
                                    {{ statusLabel(entry.to_status) }}
                                </span>
                                <span
                                    v-if="entry.current_status"
                                    class="rounded-full border border-sky-400/20 bg-sky-500/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-sky-200"
                                >
                                    Status kini
                                    {{ statusLabel(entry.current_status) }}
                                </span>
                            </div>
                            <p
                                class="text-sm text-stone-600 dark:text-slate-300"
                            >
                                {{
                                    entry.notes ||
                                    'Perubahan status order online tercatat tanpa catatan tambahan.'
                                }}
                            </p>
                            <p
                                class="text-xs text-stone-400 dark:text-slate-500"
                            >
                                Riwayat sync tersimpan:
                                {{ entry.sync_history_count || 0 }} event
                            </p>
                        </div>

                        <div class="space-y-2">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Diubah oleh
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{ entry.changed_by_name || 'System' }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                                >
                                    {{ entry.changed_by_type || 'system' }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Waktu
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{ formatDateTime(entry.created_at) }}
                                </p>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
