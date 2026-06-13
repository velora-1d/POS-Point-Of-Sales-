<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Crown,
    Eye,
    HandCoins,
    Search,
    Users,
    Wallet,
    X,
} from '@lucide/vue';
import { ref } from 'vue';

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
const isTierModalOpen = ref(false);
const selectedCustomerForDetail = ref<CustomerRow | null>(null);

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
            return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
        case 'cancelled':
            return 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300';
        case 'payment_pending':
            return 'border-fuchsia-500/20 bg-fuchsia-500/10 text-fuchsia-700 dark:text-fuchsia-300';
        case 'pending':
        case 'in_progress':
        case 'waiting_bar_approval':
        case 'ready':
            return 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300';
        default:
            return 'border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-800 text-stone-600 dark:text-slate-300';
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
    <Head title="Database Pelanggan - POS Mentai" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-stone-900 dark:text-white"
                    >
                        Database Pelanggan
                    </h2>
                    <p
                        class="mt-1 max-w-2xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Kelola data keanggotaan member, akumulasi loyalty
                        points, status limit kasbon, serta riwayat belanja
                        pelanggan outlet secara terpusat.
                    </p>
                </div>
                <div>
                    <button
                        type="button"
                        @click="isTierModalOpen = true"
                        class="inline-flex items-center gap-2 rounded-2xl border border-orange-500/20 bg-orange-500/10 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-orange-300 transition hover:bg-orange-500/15"
                    >
                        <Crown class="h-4 w-4" />
                        Benefit & Info Tier
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Simplified KPI Statistics Grid -->
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Customer -->
                <article
                    class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_18px_50px_rgba(15,23,42,0.16)] dark:border-white/10 dark:bg-slate-950/40"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500"
                            >
                                Total Pelanggan
                            </p>
                            <p
                                class="mt-3 text-3xl font-black text-stone-900 dark:text-white"
                            >
                                {{ summary.total }}
                            </p>
                            <p
                                class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                            >
                                Tercatat di semua cabang
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-stone-600 dark:border-white/10 dark:bg-slate-950/40 dark:text-slate-300"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                </article>

                <!-- Top Tier / Level Teraktif -->
                <article
                    class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_18px_50px_rgba(15,23,42,0.16)] dark:border-white/10 dark:bg-slate-950/40"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500"
                            >
                                Tier Teraktif
                            </p>
                            <p
                                class="mt-3 max-w-[170px] truncate text-2xl font-black text-amber-600 dark:text-amber-300"
                            >
                                {{ loyalty.topTierName || 'Belum Ada' }}
                            </p>
                            <p
                                class="mt-1.5 text-[11px] text-stone-500 dark:text-slate-400"
                            >
                                {{ loyalty.activeMembers }} member berstatus
                                aktif
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-amber-400 dark:border-white/10 dark:bg-slate-950/40"
                        >
                            <Crown class="h-5 w-5" />
                        </div>
                    </div>
                </article>

                <!-- Kasbon Customers count -->
                <article
                    class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_18px_50px_rgba(15,23,42,0.16)] dark:border-white/10 dark:bg-slate-950/40"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500"
                            >
                                Pelanggan Kasbon
                            </p>
                            <p class="mt-3 text-3xl font-black text-rose-600 dark:text-rose-300">
                                {{ kasbon.customersWithDebt }}
                            </p>
                            <p
                                class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                            >
                                {{ kasbon.openKasbonOrders }} bill kasbon
                                berjalan
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-rose-400 dark:border-white/10 dark:bg-slate-950/40"
                        >
                            <Wallet class="h-5 w-5" />
                        </div>
                    </div>
                </article>

                <!-- Total Outstanding kasbon -->
                <article
                    class="rounded-3xl border border-rose-500/15 bg-rose-500/[0.04] p-5 shadow-[0_18px_50px_rgba(15,23,42,0.16)]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.2em] text-rose-600 dark:text-rose-400"
                            >
                                Total Piutang Toko
                            </p>
                            <p class="mt-3 text-2xl font-black text-rose-600 dark:text-rose-300">
                                {{ formatPrice(kasbon.totalOutstanding) }}
                            </p>
                            <p
                                class="mt-1.5 text-[11px] text-stone-500 dark:text-slate-400"
                            >
                                Menunggu pelunasan kasir
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-rose-500/15 bg-rose-500/10 dark:bg-rose-950/60 p-3 text-rose-600 dark:text-rose-300"
                        >
                            <HandCoins class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <!-- Search Filter Bar -->
            <section
                class="rounded-3xl border border-stone-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-slate-950/50"
            >
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-bold uppercase tracking-wider text-orange-400"
                        >
                            Cari Pelanggan
                        </p>
                        <p
                            class="mt-0.5 text-[11px] text-stone-500 dark:text-slate-400"
                        >
                            Temukan member berdasar nama, telepon, atau alamat
                            email.
                        </p>
                    </div>
                    <form
                        class="flex w-full max-w-lg items-center gap-2"
                        @submit.prevent="submitSearch"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-slate-500"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama atau nomor HP..."
                                class="w-full rounded-2xl border border-stone-200 bg-white py-2.5 pl-10 pr-9 text-xs text-stone-900 placeholder:text-stone-400 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900 dark:text-slate-500 dark:text-white"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="clearSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-900 dark:text-slate-500 dark:text-white"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        <button
                            type="submit"
                            class="rounded-2xl bg-orange-500 px-4 py-2.5 text-xs font-bold text-slate-950 transition hover:bg-orange-400"
                        >
                            Cari
                        </button>
                    </form>
                </div>
            </section>

            <!-- Database Main Data Table -->
            <section
                class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl dark:border-white/10 dark:bg-slate-950/30"
            >
                <div
                    class="flex flex-col gap-3 border-b border-stone-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-slate-950/40 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-wider text-stone-800 dark:text-slate-200"
                        >
                            Database & Registrasi Member
                        </h3>
                        <p
                            class="mt-0.5 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Menampilkan {{ customers.from ?? 0 }} -
                            {{ customers.to ?? 0 }} dari
                            {{ customers.total }} pelanggan.
                        </p>
                    </div>
                    <div
                        class="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-[10px] text-stone-500 dark:border-white/10 dark:bg-slate-900/60 dark:text-slate-400"
                    >
                        Data sinkron dari seluruh outlet aktif
                    </div>
                </div>

                <div
                    v-if="customers.data.length === 0"
                    class="px-5 py-12 text-center"
                >
                    <p class="text-sm text-stone-400 dark:text-slate-500">
                        Tidak ada pelanggan yang cocok dengan pencarian Anda.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr
                                class="border-b border-stone-200 bg-white text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:border-white/10 dark:bg-slate-950/20 dark:text-slate-400"
                            >
                                <th class="px-5 py-3">Pelanggan / Kontak</th>
                                <th class="px-5 py-3">Membership Tier</th>
                                <th class="px-5 py-3">Status Kasbon</th>
                                <th class="px-5 py-3 text-right">Pembelian</th>
                                <th class="px-5 py-3 text-right">
                                    Total Belanja
                                </th>
                                <th class="px-5 py-3">Join Date</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-white/5 text-xs text-stone-600 dark:text-slate-300"
                        >
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                class="transition hover:bg-white/[0.02]"
                            >
                                <!-- Name & Contact -->
                                <td class="px-5 py-3.5">
                                    <div
                                        class="text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{ customer.name || 'Pelanggan POS' }}
                                    </div>
                                    <div
                                        class="mt-0.5 text-[11px] text-stone-500 dark:text-slate-400"
                                    >
                                        {{ customer.phone || '-' }}
                                    </div>
                                    <div
                                        class="text-[10px] text-stone-400 dark:text-slate-500"
                                        v-if="customer.email"
                                    >
                                        {{ customer.email }}
                                    </div>
                                </td>

                                <!-- Membership Tier & Points -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                            :class="
                                                customer.membership?.tier?.name
                                                    ? 'border border-orange-500/20 bg-orange-500/10 text-orange-700 dark:text-orange-300'
                                                    : 'bg-stone-100 text-stone-500 dark:bg-slate-800 dark:text-slate-400'
                                            "
                                        >
                                            {{
                                                customer.membership?.tier
                                                    ?.name || 'Walk-In'
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                        v-if="customer.membership"
                                    >
                                        {{
                                            customer.membership?.total_points ||
                                            0
                                        }}
                                        poin aktif
                                    </div>
                                </td>

                                <!-- Kasbon Status -->
                                <td class="px-5 py-3.5">
                                    <div
                                        v-if="
                                            Number(
                                                customer.kasbon_total_due || 0,
                                            ) > 0
                                        "
                                    >
                                        <span
                                            class="rounded-full border border-rose-500/20 bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:text-rose-300"
                                        >
                                            {{
                                                formatPrice(
                                                    customer.kasbon_total_due,
                                                )
                                            }}
                                        </span>
                                        <div
                                            class="mt-1 text-[10px] text-stone-500 dark:text-slate-400"
                                        >
                                            {{ customer.kasbon_orders_count }}
                                            bill belum lunas
                                        </div>
                                    </div>
                                    <span
                                        v-else
                                        class="text-[11px] text-stone-400 dark:text-slate-500"
                                        >Lunas / Tidak ada</span
                                    >
                                </td>

                                <!-- Total Orders -->
                                <td
                                    class="px-5 py-3.5 text-right font-semibold text-stone-900 dark:text-white"
                                >
                                    {{ customer.orders_count || 0 }}x order
                                </td>

                                <!-- Total Spent -->
                                <td
                                    class="px-5 py-3.5 text-right font-bold text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ formatPrice(customer.total_spent) }}
                                </td>

                                <!-- Join Date -->
                                <td
                                    class="px-5 py-3.5 text-stone-500 dark:text-slate-400"
                                >
                                    {{ formatDate(customer.created_at) }}
                                </td>

                                <!-- Action Buttons -->
                                <td class="px-5 py-3.5 text-center">
                                    <button
                                        type="button"
                                        @click="
                                            selectedCustomerForDetail = customer
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-[11px] font-bold text-stone-800 transition hover:border-stone-300 hover:text-stone-900 dark:border-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:text-white"
                                    >
                                        <Eye
                                            class="h-3.5 w-3.5 text-stone-500 dark:text-slate-400"
                                        />
                                        Detail Riwayat
                                    </button>
                                </td>
                            </tr>
                            <!-- Empty rows to fill up to exactly 20 rows -->
                            <tr
                                v-for="i in Math.max(0, 20 - customers.data.length)"
                                :key="'empty-customer-' + i"
                                class="border-b border-stone-200/50 dark:border-white/5 h-[72px]"
                            >
                                <td class="px-5 py-3.5 text-stone-300 dark:text-slate-700 italic">
                                    Slot Kosong {{ customers.data.length + i }}
                                </td>
                                <td class="px-5 py-3.5 text-stone-300 dark:text-slate-700">-</td>
                                <td class="px-5 py-3.5 text-stone-300 dark:text-slate-700">-</td>
                                <td class="px-5 py-3.5 text-right text-stone-300 dark:text-slate-700">-</td>
                                <td class="px-5 py-3.5 text-right text-stone-300 dark:text-slate-700">-</td>
                                <td class="px-5 py-3.5 text-stone-300 dark:text-slate-700">-</td>
                                <td class="px-5 py-3.5 text-center text-stone-300 dark:text-slate-700">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="customers.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-slate-950/20"
                >
                    <p class="text-[11px] text-stone-400 dark:text-slate-500">
                        Gunakan tombol paginasi untuk melihat lebih banyak data
                        pelanggan.
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <template
                            v-for="link in customers.links"
                            :key="`${link.label}-${link.url}`"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="rounded-xl border px-3 py-2 text-xs font-bold transition"
                                :class="
                                    link.active
                                        ? 'border-orange-500/30 bg-orange-500/10 text-orange-300'
                                        : 'border-stone-200 text-stone-600 hover:bg-stone-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'
                                "
                            >
                                <span v-html="link.label"></span>
                            </Link>
                            <span
                                v-else
                                class="cursor-not-allowed rounded-xl border border-stone-200 bg-white/[0.01] px-3 py-2 text-xs font-bold text-slate-600 dark:border-white/5"
                            >
                                <span v-html="link.label"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </section>
        </div>

        <!-- POPUP MODAL 1: Benefit & Info Tier Membership -->
        <teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="isTierModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/80"
                >
                    <div
                        class="w-full max-w-2xl rounded-3xl border border-stone-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-slate-900"
                    >
                        <div
                            class="mb-4 flex items-center justify-between border-b border-stone-200 pb-4 dark:border-white/10"
                        >
                            <div class="flex items-center gap-2">
                                <Crown class="h-5 w-5 text-orange-400" />
                                <h3
                                    class="text-lg font-black text-stone-900 dark:text-stone-900 dark:text-white"
                                >
                                    Benefit & Aturan Tier Membership
                                </h3>
                            </div>
                            <button
                                @click="isTierModalOpen = false"
                                class="text-stone-400 transition hover:text-stone-900 dark:text-slate-500 dark:text-white"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <div
                            class="custom-scrollbar max-h-[60vh] space-y-4 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="tier in tiers"
                                :key="tier.id"
                                class="flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/50 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <h4
                                        class="flex items-center gap-2 text-base font-extrabold text-stone-900 dark:text-white"
                                    >
                                        <BadgeCheck
                                            class="h-4.5 w-4.5 text-orange-400"
                                        />
                                        {{ tier.name }}
                                    </h4>
                                    <p
                                        class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        {{
                                            tier.description ||
                                            'Tier loyalty aktif untuk diskon pembelian pelanggan.'
                                        }}
                                    </p>
                                </div>
                                <div class="flex gap-4 text-xs">
                                    <div
                                        class="min-w-[80px] rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-right dark:border-white/5 dark:bg-slate-900/60"
                                    >
                                        <p
                                            class="text-[8px] font-semibold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                        >
                                            Syarat Poin
                                        </p>
                                        <p
                                            class="mt-0.5 font-bold text-stone-900 dark:text-white"
                                        >
                                            {{ tier.point_threshold }} pts
                                        </p>
                                    </div>
                                    <div
                                        class="min-w-[80px] rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-right dark:border-white/5 dark:bg-slate-900/60"
                                    >
                                        <p
                                            class="text-[8px] font-semibold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                        >
                                            Diskon
                                        </p>
                                        <p
                                            class="mt-0.5 font-bold text-emerald-400"
                                        >
                                            {{
                                                Number(
                                                    tier.discount_percent || 0,
                                                )
                                            }}%
                                        </p>
                                    </div>
                                    <div
                                        class="min-w-[80px] rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-right dark:border-white/5 dark:bg-slate-900/60"
                                    >
                                        <p
                                            class="text-[8px] font-semibold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                        >
                                            Member
                                        </p>
                                        <p
                                            class="mt-0.5 font-bold text-amber-300"
                                        >
                                            {{ tier.active_members_count || 0 }}
                                            orang
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </teleport>

        <!-- POPUP MODAL 2: Detail Transaksi & Kasbon Pelanggan -->
        <teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="selectedCustomerForDetail"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/80"
                >
                    <div
                        class="flex max-h-[85vh] w-full max-w-3xl flex-col rounded-3xl border border-stone-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-slate-900"
                    >
                        <div
                            class="mb-4 flex shrink-0 items-center justify-between border-b border-stone-200 pb-4 dark:border-white/10"
                        >
                            <div>
                                <h3
                                    class="text-lg font-black text-stone-900 dark:text-white"
                                >
                                    {{
                                        selectedCustomerForDetail.name ||
                                        'Pelanggan POS'
                                    }}
                                </h3>
                                <p
                                    class="mt-0.5 text-xs text-stone-500 dark:text-slate-400"
                                >
                                    {{ selectedCustomerForDetail.phone || '-' }}
                                    <span
                                        v-if="selectedCustomerForDetail.email"
                                    >
                                        •
                                        {{
                                            selectedCustomerForDetail.email
                                        }}</span
                                    >
                                </p>
                            </div>
                            <button
                                @click="selectedCustomerForDetail = null"
                                class="text-stone-400 transition hover:text-stone-900 dark:text-slate-500 dark:text-white"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <div
                            class="custom-scrollbar flex-1 space-y-5 overflow-y-auto pr-1"
                        >
                            <!-- Detail Info Grid -->
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div
                                    class="rounded-2xl border border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-950/50"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                    >
                                        Level Keanggotaan
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-extrabold text-stone-900 dark:text-white"
                                    >
                                        {{
                                            selectedCustomerForDetail.membership
                                                ?.tier?.name ||
                                            'Walk-in / Walk-in'
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-stone-500 dark:text-slate-400"
                                        v-if="
                                            selectedCustomerForDetail.membership
                                        "
                                    >
                                        {{
                                            selectedCustomerForDetail.membership
                                                ?.total_points || 0
                                        }}
                                        poin aktif
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl border border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-950/50"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                    >
                                        Volume Transaksi
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-extrabold text-stone-900 dark:text-white"
                                    >
                                        {{
                                            selectedCustomerForDetail.orders_count ||
                                            0
                                        }}
                                        kali belanja
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        Total spent:
                                        {{
                                            formatPrice(
                                                selectedCustomerForDetail.total_spent,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl border bg-white p-3 dark:bg-slate-950/50"
                                    :class="
                                        Number(
                                            selectedCustomerForDetail.kasbon_total_due ||
                                                0,
                                        ) > 0
                                            ? 'border-rose-500/20 bg-rose-500/5'
                                            : 'border-stone-200 dark:border-white/10'
                                    "
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                    >
                                        Saldo Kasbon Aktif
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-extrabold"
                                        :class="
                                            Number(
                                                selectedCustomerForDetail.kasbon_total_due ||
                                                    0,
                                            ) > 0
                                                ? 'text-rose-700 dark:text-rose-300'
                                                : 'text-stone-900 dark:text-white'
                                        "
                                    >
                                        {{
                                            formatPrice(
                                                selectedCustomerForDetail.kasbon_total_due,
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        {{
                                            selectedCustomerForDetail.kasbon_orders_count ||
                                            0
                                        }}
                                        bill kasbon berjalan
                                    </p>
                                </div>
                            </div>

                            <!-- Detail Riwayat Order -->
                            <div>
                                <h4
                                    class="mb-3 text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                >
                                    Histori Transaksi Terakhir
                                </h4>
                                <div
                                    v-if="
                                        selectedCustomerForDetail.recent_orders &&
                                        selectedCustomerForDetail.recent_orders
                                            .length > 0
                                    "
                                    class="space-y-2"
                                >
                                    <div
                                        v-for="order in selectedCustomerForDetail.recent_orders"
                                        :key="order.id"
                                        class="flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-3.5 transition hover:border-stone-200 dark:border-white/10 dark:border-white/5 dark:bg-slate-950/30"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-bold text-stone-900 dark:text-white"
                                            >
                                                {{ order.order_number }}
                                            </p>
                                            <p
                                                class="mt-1.5 text-[10px] text-stone-400 dark:text-slate-500"
                                            >
                                                {{
                                                    formatDateTime(
                                                        order.created_at,
                                                    )
                                                }}
                                                <span v-if="order.table?.name">
                                                    •
                                                    {{ order.table.name }}</span
                                                >
                                                <span v-else>
                                                    •
                                                    {{
                                                        getOrderTypeLabel(
                                                            order.type,
                                                        )
                                                    }}</span
                                                >
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p
                                                class="text-xs font-black text-emerald-700 dark:text-emerald-300"
                                            >
                                                {{
                                                    formatPrice(
                                                        order.total_amount,
                                                    )
                                                }}
                                            </p>
                                            <span
                                                :class="[
                                                    getOrderStatusClass(
                                                        order.status,
                                                    ),
                                                    'mt-1.5 inline-block rounded-md border px-2 py-0.5 text-[8px] font-black uppercase tracking-wider',
                                                ]"
                                            >
                                                {{
                                                    getOrderStatusLabel(
                                                        order.status,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="rounded-2xl border border-dashed border-stone-200 py-6 text-center text-xs italic text-stone-400 dark:border-white/10 dark:text-slate-500"
                                >
                                    Belum ada catatan transaksi untuk pelanggan
                                    ini.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </teleport>
    </AuthenticatedLayout>
</template>
