<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Clock3,
    Crown,
    Gem,
    HandCoins,
    ReceiptText,
    Search,
    Users,
    Wallet,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface CustomerRow {
    id: string;
    name: string;
    phone?: string | null;
    email?: string | null;
    registered_via?: string | null;
    created_at?: string | null;
    orders_count?: number;
    total_spent?: number | string | null;
    kasbon_orders_count?: number;
    kasbon_total_due?: number | string | null;
    membership?: {
        is_active?: boolean;
        total_points?: number;
        lifetime_points?: number;
        tier?: {
            name?: string | null;
        } | null;
    } | null;
    latest_kasbon_order?: {
        order_number?: string | null;
        total_amount?: number | string | null;
        paid_amount?: number | string | null;
        created_at?: string | null;
        status?: string | null;
        table?: {
            name?: string | null;
        } | null;
    } | null;
    latest_order?: {
        order_number?: string | null;
        total_amount?: number | string | null;
        created_at?: string | null;
        status?: string | null;
        table?: {
            name?: string | null;
        } | null;
    } | null;
    recent_orders?: Array<{
        id: string;
        order_number?: string | null;
        total_amount?: number | string | null;
        created_at?: string | null;
        status?: string | null;
        type?: string | null;
        table?: {
            name?: string | null;
        } | null;
    }>;
}

interface TierRow {
    id: string;
    name: string;
    point_threshold?: number;
    discount_percent?: number | string | null;
    point_rate_per_amount?: number | string | null;
    description?: string | null;
    active_members_count?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    customers: {
        data: CustomerRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total: number;
        members: number;
        withOrders: number;
        registeredThisMonth: number;
    };
    loyalty: {
        activeMembers: number;
        totalPoints: number;
        lifetimePoints: number;
        activeTiers: number;
        topTierName?: string | null;
    };
    kasbon: {
        customersWithDebt: number;
        openKasbonOrders: number;
        totalOutstanding: number | string;
    };
    tiers: TierRow[];
    topMembers: CustomerRow[];
    topKasbonCustomers: CustomerRow[];
    filters: {
        search?: string;
        per_page?: number;
    };
}>();

const search = ref(props.filters.search || '');

const summaryCards = computed(() => [
    {
        label: 'Total Customer',
        value: props.summary.total,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: Users,
    },
    {
        label: 'Member Aktif',
        value: props.summary.members,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/8',
        icon: BadgeCheck,
    },
    {
        label: 'Pernah Order',
        value: props.summary.withOrders,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/8',
        icon: ReceiptText,
    },
    {
        label: 'Baru Bulan Ini',
        value: props.summary.registeredThisMonth,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
        icon: Clock3,
    },
]);

const loyaltyCards = computed(() => [
    {
        label: 'Poin Aktif',
        value: props.loyalty.totalPoints,
        tone: 'text-fuchsia-300',
        surface: 'border-fuchsia-400/15 bg-fuchsia-500/8',
        icon: Gem,
    },
    {
        label: 'Lifetime Points',
        value: props.loyalty.lifetimePoints,
        tone: 'text-cyan-300',
        surface: 'border-cyan-400/15 bg-cyan-500/8',
        icon: Crown,
    },
    {
        label: 'Tier Aktif',
        value: props.loyalty.activeTiers,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
        icon: BadgeCheck,
    },
]);

const kasbonCards = computed(() => [
    {
        label: 'Customer Berhutang',
        value: props.kasbon.customersWithDebt,
        tone: 'text-rose-300',
        surface: 'border-rose-400/15 bg-rose-500/8',
        icon: Users,
    },
    {
        label: 'Kasbon Aktif',
        value: props.kasbon.openKasbonOrders,
        tone: 'text-orange-300',
        surface: 'border-orange-400/15 bg-orange-500/8',
        icon: ReceiptText,
    },
    {
        label: 'Total Piutang',
        value: formatPrice(props.kasbon.totalOutstanding),
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
        icon: HandCoins,
    },
]);

const formatPrice = (value: unknown) => {
    const num = Number(value || 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (value: string | null | undefined) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const formatDateTime = (value: string | null | undefined) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const submitSearch = () => {
    router.get(
        route('customers.index'),
        {
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearSearch = () => {
    search.value = '';
    submitSearch();
};

const getChannelLabel = (value: string | null | undefined) => {
    if (!value) return 'manual';

    return value.replaceAll('_', ' ');
};

const getOrderStatusLabel = (value: string | null | undefined) => {
    if (!value) return 'unknown';

    return value.replaceAll('_', ' ');
};

const getOrderStatusClass = (value: string | null | undefined) => {
    switch (value) {
        case 'completed':
            return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300';
        case 'cancelled':
            return 'border-rose-500/20 bg-rose-500/10 text-rose-300';
        case 'payment_pending':
            return 'border-fuchsia-500/20 bg-fuchsia-500/10 text-fuchsia-300';
        case 'pending':
        case 'in_progress':
        case 'waiting_bar_approval':
        case 'ready':
            return 'border-amber-500/20 bg-amber-500/10 text-amber-300';
        default:
            return 'border-slate-700 bg-slate-800 text-slate-300';
    }
};

const getOrderTypeLabel = (value: string | null | undefined) => {
    if (!value) return 'order';

    if (value === 'dine_in') return 'dine in';
    if (value === 'takeaway') return 'takeaway';

    return value.replaceAll('_', ' ');
};
</script>

<template>
    <Head title="Customer Database" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <Users class="h-3.5 w-3.5" />
                    Menu #22-#23-#24-#25 Customer, Loyalty, Debt, & History
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Database Pelanggan
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-slate-400">
                        Pantau daftar pelanggan, membership, channel registrasi,
                        loyalty points, kasbon aktif, tier aktif, dan histori transaksi
                        pelanggan dari satu halaman.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <section class="grid gap-3 lg:grid-cols-4">
                <article
                    v-for="stat in summaryCards"
                    :key="stat.label"
                    :class="[
                        'rounded-[22px] border px-4 py-4 shadow-lg shadow-slate-950/10',
                        stat.surface,
                    ]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                            >
                                {{ stat.label }}
                            </p>
                            <p :class="['mt-2 text-3xl font-black', stat.tone]">
                                {{ stat.value }}
                            </p>
                        </div>
                        <component
                            :is="stat.icon"
                            class="h-5 w-5 text-slate-500"
                        />
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="grid gap-3 md:grid-cols-3">
                    <article
                        v-for="stat in loyaltyCards"
                        :key="stat.label"
                        :class="[
                            'rounded-[22px] border px-4 py-4 shadow-lg shadow-slate-950/10',
                            stat.surface,
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    {{ stat.label }}
                                </p>
                                <p :class="['mt-2 text-2xl font-black', stat.tone]">
                                    {{ stat.value }}
                                </p>
                            </div>
                            <component
                                :is="stat.icon"
                                class="h-5 w-5 text-slate-500"
                            />
                        </div>
                    </article>
                </div>

                <article
                    class="rounded-[22px] border border-amber-400/15 bg-amber-500/8 px-4 py-4 shadow-lg shadow-slate-950/10"
                >
                    <p
                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-300"
                    >
                        Tier Tertinggi Outlet
                    </p>
                    <p class="mt-2 text-2xl font-black text-white">
                        {{ loyalty.topTierName || 'Belum ada tier aktif' }}
                    </p>
                    <p class="mt-2 text-xs text-slate-400">
                        Member aktif saat ini: {{ loyalty.activeMembers }} customer
                    </p>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="grid gap-3 md:grid-cols-3">
                    <article
                        v-for="stat in kasbonCards"
                        :key="stat.label"
                        :class="[
                            'rounded-[22px] border px-4 py-4 shadow-lg shadow-slate-950/10',
                            stat.surface,
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    {{ stat.label }}
                                </p>
                                <p :class="['mt-2 text-2xl font-black', stat.tone]">
                                    {{ stat.value }}
                                </p>
                            </div>
                            <component
                                :is="stat.icon"
                                class="h-5 w-5 text-slate-500"
                            />
                        </div>
                    </article>
                </div>

                <article
                    class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
                >
                    <p
                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                    >
                        Customer Dengan Kasbon
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-if="topKasbonCustomers.length === 0"
                            class="rounded-2xl border border-dashed border-slate-800 bg-slate-950/40 px-4 py-4 text-[11px] text-slate-500"
                        >
                            Belum ada customer dengan kasbon aktif.
                        </div>
                        <article
                            v-for="customer in topKasbonCustomers"
                            :key="customer.id"
                            class="rounded-2xl border border-rose-500/15 bg-rose-500/6 px-4 py-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-white">
                                        {{ customer.name || 'Pelanggan POS' }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        {{ customer.phone || '-' }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full border border-rose-500/20 bg-rose-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-rose-300"
                                >
                                    {{ customer.kasbon_orders_count || 0 }} kasbon
                                </span>
                            </div>
                            <p class="mt-3 text-sm font-bold text-amber-300">
                                {{ formatPrice(customer.kasbon_total_due) }}
                            </p>
                            <p
                                v-if="customer.latest_kasbon_order?.order_number"
                                class="mt-1 text-[11px] text-slate-400"
                            >
                                Terakhir:
                                {{ customer.latest_kasbon_order.order_number }}
                            </p>
                        </article>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <article
                    class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 shadow-xl shadow-slate-950/15"
                >
                    <div class="border-b border-slate-800/80 px-5 py-4">
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Tier Membership
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Lihat threshold, diskon, dan jumlah member aktif per tier.
                        </p>
                    </div>

                    <div class="grid gap-4 p-5 lg:grid-cols-2">
                        <article
                            v-for="tier in tiers"
                            :key="tier.id"
                            class="rounded-[24px] border border-slate-800 bg-slate-950/70 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-black text-white">
                                        {{ tier.name }}
                                    </h3>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ tier.description || 'Tier loyalty aktif outlet.' }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-300"
                                >
                                    {{ tier.active_members_count || 0 }} member
                                </span>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                        Threshold
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-white">
                                        {{ tier.point_threshold || 0 }} poin
                                    </p>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                        Diskon
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-white">
                                        {{ Number(tier.discount_percent || 0) }}%
                                    </p>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                        Rate Poin
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-white">
                                        {{ tier.point_rate_per_amount || 0 }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <article
                    class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
                >
                    <p
                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                    >
                        Member Paling Loyal
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-if="topMembers.length === 0"
                            class="rounded-2xl border border-dashed border-slate-800 bg-slate-950/40 px-4 py-4 text-[11px] text-slate-500"
                        >
                            Belum ada member aktif yang bisa dirangking.
                        </div>
                        <article
                            v-for="member in topMembers"
                            :key="member.id"
                            class="rounded-2xl border border-fuchsia-500/15 bg-fuchsia-500/6 px-4 py-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-white">
                                        {{ member.name || 'Pelanggan POS' }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        {{ member.membership?.tier?.name || 'Member' }}
                                        • {{ member.phone || '-' }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full border border-fuchsia-500/20 bg-fuchsia-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-fuchsia-300"
                                >
                                    {{ member.membership?.lifetime_points || 0 }} pts
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-[11px] text-slate-400">
                                <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                                    Order:
                                    <span class="font-bold text-white">
                                        {{ member.orders_count || 0 }}
                                    </span>
                                </div>
                                <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                                    Poin aktif:
                                    <span class="font-bold text-white">
                                        {{ member.membership?.total_points || 0 }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>
            </section>

            <section
                class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
            >
                <div
                    class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Filter Customer
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Cari berdasarkan nama, telepon, atau email.
                        </p>
                    </div>
                    <form
                        class="flex w-full max-w-xl items-center gap-2"
                        @submit.prevent="submitSearch"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari customer..."
                                class="w-full rounded-2xl border border-slate-800 bg-slate-950 py-3 pl-10 pr-10 text-sm text-white placeholder:text-slate-500 focus:border-orange-500 focus:outline-none"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="clearSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 transition hover:text-white"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <button
                            type="submit"
                            class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-white"
                        >
                            Cari
                        </button>
                    </form>
                </div>
            </section>

            <section
                class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 shadow-xl shadow-slate-950/15"
            >
                <div
                    class="flex flex-col gap-2 border-b border-slate-800/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Daftar Customer
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Menampilkan
                            {{ customers.from ?? 0 }}-{{ customers.to ?? 0 }}
                            dari {{ customers.total }} customer.
                        </p>
                    </div>
                    <div class="rounded-full border border-slate-800 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold text-slate-400">
                        Data hidup dari outlet aktif
                    </div>
                </div>

                <div v-if="customers.data.length === 0" class="px-5 py-10">
                    <div
                        class="rounded-2xl border border-dashed border-slate-800 bg-slate-950/40 px-4 py-8 text-center text-sm text-slate-500"
                    >
                        Belum ada customer yang cocok dengan filter saat ini.
                    </div>
                </div>

                <div v-else class="grid gap-4 p-5 lg:grid-cols-2">
                    <article
                        v-for="customer in customers.data"
                        :key="customer.id"
                        class="rounded-[24px] border border-slate-800 bg-slate-950/70 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-lg font-black text-white">
                                    {{ customer.name || 'Pelanggan POS' }}
                                </h3>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ customer.phone || '-' }}
                                    <span v-if="customer.email">
                                        • {{ customer.email }}
                                    </span>
                                </p>
                            </div>
                            <span
                                class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-orange-300"
                            >
                                {{ getChannelLabel(customer.registered_via) }}
                            </span>
                        </div>

                        <div
                            v-if="Number(customer.kasbon_total_due || 0) > 0"
                            class="mt-4 rounded-2xl border border-rose-500/20 bg-rose-500/8 px-4 py-3"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-rose-300">
                                        Kasbon Aktif
                                    </p>
                                    <p class="mt-1 text-sm font-bold text-white">
                                        {{ customer.kasbon_orders_count || 0 }} transaksi belum lunas
                                    </p>
                                </div>
                                <p class="text-sm font-black text-amber-300">
                                    {{ formatPrice(customer.kasbon_total_due) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Membership
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    {{ customer.membership?.tier?.name || 'Belum Member' }}
                                </p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    {{ customer.membership?.total_points || 0 }} poin
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Total Order
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    {{ customer.orders_count || 0 }} transaksi
                                </p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Join {{ formatDate(customer.created_at) }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Total Belanja
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    {{ formatPrice(customer.total_spent) }}
                                </p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Lifetime spend
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3"
                        >
                            <div class="flex items-center gap-2">
                                <Wallet class="h-4 w-4 text-emerald-300" />
                                <p class="text-sm font-bold text-white">
                                    Riwayat Transaksi
                                </p>
                            </div>
                            <div
                                v-if="customer.recent_orders && customer.recent_orders.length > 0"
                                class="mt-3 space-y-2"
                            >
                                <article
                                    v-for="order in customer.recent_orders"
                                    :key="order.id"
                                    class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-3"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-white">
                                                {{ order.order_number }}
                                            </p>
                                            <p class="mt-1 text-[11px] text-slate-400">
                                                {{ formatDateTime(order.created_at) }}
                                                <span v-if="order.table?.name">
                                                    • {{ order.table.name }}
                                                </span>
                                                <span v-else>
                                                    • {{ getOrderTypeLabel(order.type) }}
                                                </span>
                                            </p>
                                        </div>
                                        <span
                                            :class="[
                                                'rounded-full border px-2 py-1 text-[9px] font-bold uppercase tracking-wider',
                                                getOrderStatusClass(order.status),
                                            ]"
                                        >
                                            {{ getOrderStatusLabel(order.status) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm font-bold text-emerald-300">
                                        {{ formatPrice(order.total_amount) }}
                                    </p>
                                </article>
                            </div>
                            <p v-else class="mt-3 text-[11px] text-slate-500">
                                Customer ini belum punya transaksi.
                            </p>
                        </div>
                    </article>
                </div>

                <div
                    v-if="customers.links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-slate-800/80 px-5 py-4"
                >
                    <template
                        v-for="link in customers.links"
                        :key="`${link.label}-${link.url}`"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            :class="[
                                'rounded-xl border px-3 py-2 text-xs font-bold transition',
                                link.active
                                    ? 'border-orange-500/20 bg-orange-500/10 text-orange-300'
                                    : 'border-slate-800 bg-slate-950 text-slate-300 hover:border-slate-700',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded-xl border border-slate-900 bg-slate-950/50 px-3 py-2 text-xs font-bold text-slate-600"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
