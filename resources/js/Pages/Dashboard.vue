<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowDownRight,
    ArrowUpRight,
    BellRing,
    ChevronRight,
    Clock,
    CreditCard,
    Minus,
    ShoppingBag,
    ShoppingCart,
    Star,
    TrendingDown,
    TrendingUp,
    Utensils,
    Wallet,
    Zap,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const page = usePage<any>();
const user = computed(() => page.props.auth.user);

const props = defineProps<{
    alerts?: {
        lowStock?: {
            summary?: { total?: number; products?: number; raw_materials?: number; critical?: number };
            items?: Array<{ id: string; type: string; name: string; context?: string | null; current_stock: number; minimum_stock: number; unit?: string | null; route: string; severity: number }>;
        };
        expired?: {
            summary?: { upcoming?: number; today?: number; expired?: number; critical?: number };
            items?: Array<{ id: string; trackable_type: string; name: string; context?: string | null; quantity: number; expired_at: string; days_left: number; status: string; route: string }>;
        };
    };
    finance?: {
        can_view?: boolean;
        summary?: {
            revenue?: number;
            orders?: number;
            settled_orders?: number;
            pending_orders?: number;
            avg_order_value?: number;
            total_discount?: number;
            yesterday_revenue?: number;
            yesterday_orders?: number;
            revenue_growth?: number;
            top_product?: { name?: string; quantity?: number; revenue?: number } | null;
            top_products?: Array<{ name: string; quantity: number; revenue: number }>;
            active_shift?: { cashier?: string | null; outlet?: string | null; opened_at?: string | null; opening_cash?: number; status?: string } | null;
        };
        breakdowns?: {
            payments?: Array<{ method: string; orders: number; amount: number }>;
            sources?: Array<{ source: string; orders: number; amount: number }>;
            hourly?: Array<{ hour: number; revenue: number }>;
        };
    } | null;
    filters?: { outlet_id?: string; as_of_date?: string };
    referenceData?: { outlets?: Array<{ id: string; name: string }> };
}>();

// ─── Computed ──────────────────────────────────────────────────────────────────

const lowStockSummary = computed(() => props.alerts?.lowStock?.summary ?? { total: 0, products: 0, raw_materials: 0, critical: 0 });
const lowStockItems = computed(() => props.alerts?.lowStock?.items ?? []);
const expiredSummary = computed(() => props.alerts?.expired?.summary ?? { upcoming: 0, today: 0, expired: 0, critical: 0 });
const expiredItems = computed(() => props.alerts?.expired?.items ?? []);
const canViewFinance = computed(() => Boolean(props.finance?.can_view));
const fs = computed(() => props.finance?.summary ?? {});
const paymentBreakdowns = computed(() => props.finance?.breakdowns?.payments ?? []);
const sourceBreakdowns = computed(() => props.finance?.breakdowns?.sources ?? []);
const hourlyData = computed(() => props.finance?.breakdowns?.hourly ?? []);
const topProducts = computed(() => fs.value.top_products ?? []);
const outlets = computed(() => props.referenceData?.outlets ?? []);
const canChooseOutlet = computed(() => outlets.value.length > 1);

const outletFilter = ref(props.filters?.outlet_id || '');

// Hourly chart: only show hours 06-23
const chartHours = computed(() => {
    const data = hourlyData.value;
    if (!data.length) return [];
    const visible = data.filter((d) => d.hour >= 6);
    const maxRev = Math.max(...visible.map((d) => d.revenue), 1);
    return visible.map((d) => ({
        ...d,
        pct: Math.round((d.revenue / maxRev) * 100),
        label: `${String(d.hour).padStart(2, '0')}:00`,
    }));
});

const totalPayments = computed(() => paymentBreakdowns.value.reduce((s, p) => s + p.amount, 0) || 1);

// ─── Formatters ────────────────────────────────────────────────────────────────

const fmt = (v: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(v || 0));

const fmtShort = (v: number) => {
    if (v >= 1_000_000) return `${(v / 1_000_000).toFixed(1)}jt`;
    if (v >= 1_000) return `${(v / 1_000).toFixed(0)}rb`;
    return String(v);
};

const fmtTime = (v?: string | null) => {
    if (!v) return '-';
    return new Intl.DateTimeFormat('id-ID', { timeStyle: 'short' }).format(new Date(v));
};

const payLabel: Record<string, string> = { cash: 'Cash', qris: 'QRIS', debit: 'Debit', ewallet: 'E-Wallet', kasbon: 'Kasbon' };
const srcLabel: Record<string, string> = { kasir: 'Kasir', qr_meja: 'QR Meja', gofood: 'GoFood', grabfood: 'GrabFood' };
const payIcon: Record<string, typeof Wallet> = { cash: Wallet, qris: ShoppingBag, debit: CreditCard, ewallet: Zap, kasbon: Clock };

// ─── Growth badge ───────────────────────────────────────────────────────────────

const growthPct = computed(() => fs.value.revenue_growth ?? 0);
const growthClass = computed(() => growthPct.value > 0 ? 'text-emerald-300 bg-emerald-500/10 border-emerald-400/20'
    : growthPct.value < 0 ? 'text-rose-300 bg-rose-500/10 border-rose-400/20'
    : 'text-slate-400 bg-white/[0.03] border-white/10');
const GrowthIcon = computed(() => growthPct.value > 0 ? TrendingUp : growthPct.value < 0 ? TrendingDown : Minus);

// ─── Actions ────────────────────────────────────────────────────────────────────

function applyFilter() {
    router.get(route('dashboard'), { outlet_id: outletFilter.value || undefined }, { preserveScroll: true, preserveState: true, replace: true });
}

const NOW = new Date();
const greet = NOW.getHours() < 12 ? 'Selamat pagi' : NOW.getHours() < 18 ? 'Selamat siang' : 'Selamat malam';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <div>
                    <p class="text-xs text-slate-500">{{ greet }},</p>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        {{ user?.name }}
                        <span class="ml-2 text-sm font-normal text-slate-400">· {{ user?.outlet || 'Semua Outlet' }}</span>
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Outlet filter -->
                    <div v-if="canChooseOutlet && canViewFinance" class="flex items-center gap-2">
                        <select
                            v-model="outletFilter"
                            class="rounded-2xl border border-white/10 bg-white/[0.04] px-3 py-2 text-sm text-white outline-none transition focus:border-orange-400/40"
                            @change="applyFilter"
                        >
                            <option value="">Semua Outlet</option>
                            <option v-for="o in outlets" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                    </div>
                    <span class="flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-400">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400" />
                        Live
                    </span>
                </div>
            </div>
        </template>

        <div class="space-y-5">

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- KPI ROW — Finance (owner/supervisor)                    -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <section v-if="canViewFinance">
                <!-- 6-card KPI grid -->
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

                    <!-- Revenue hari ini (besar) -->
                    <article class="relative col-span-2 overflow-hidden rounded-[24px] border border-emerald-400/20 bg-gradient-to-br from-emerald-500/20 via-emerald-500/8 to-slate-950 p-5 xl:col-span-2">
                        <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-500/15 blur-2xl" />
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-300">Revenue Hari Ini</p>
                        <p class="mt-2 text-3xl font-black text-white">{{ fmt(fs.revenue) }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-bold" :class="growthClass">
                                <component :is="GrowthIcon" class="h-3 w-3" />
                                {{ Math.abs(growthPct) }}% vs kemarin
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">Kemarin {{ fmt(fs.yesterday_revenue) }}</p>
                    </article>

                    <!-- Total Order -->
                    <article class="rounded-[24px] border border-white/10 bg-white/[0.03] p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Total Order</p>
                        <p class="mt-2 text-3xl font-black text-white">{{ fs.orders ?? 0 }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span class="rounded-full border border-emerald-400/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-300">
                                {{ fs.settled_orders ?? 0 }} selesai
                            </span>
                            <span v-if="(fs.pending_orders ?? 0) > 0" class="rounded-full border border-amber-400/20 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-300">
                                {{ fs.pending_orders }} pending
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Kemarin {{ fs.yesterday_orders ?? 0 }} order</p>
                    </article>

                    <!-- Avg Ticket -->
                    <article class="rounded-[24px] border border-white/10 bg-white/[0.03] p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Avg Ticket</p>
                        <p class="mt-2 text-2xl font-black text-white">{{ fmt(fs.avg_order_value) }}</p>
                        <p class="mt-2 text-xs text-slate-500">Per transaksi selesai</p>
                        <p class="mt-1 text-xs text-rose-300">Diskon {{ fmt(fs.total_discount) }}</p>
                    </article>

                    <!-- Produk terlaris -->
                    <article class="rounded-[24px] border border-sky-400/15 bg-sky-500/8 p-5">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-sky-300">Top Produk</p>
                            <Star class="h-3.5 w-3.5 text-sky-400" />
                        </div>
                        <p class="mt-2 text-base font-black text-white leading-tight">{{ fs.top_product?.name || '–' }}</p>
                        <p class="mt-2 text-xs text-sky-100/80">{{ fs.top_product?.quantity ?? 0 }} terjual · {{ fmt(fs.top_product?.revenue) }}</p>
                    </article>

                    <!-- Shift aktif -->
                    <article class="rounded-[24px] border p-5"
                        :class="fs.active_shift ? 'border-orange-400/20 bg-orange-500/8' : 'border-white/10 bg-white/[0.03]'"
                    >
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em]" :class="fs.active_shift ? 'text-orange-300' : 'text-slate-400'">Shift Aktif</p>
                        <p class="mt-2 text-base font-black text-white leading-tight">
                            {{ fs.active_shift?.cashier || 'Tidak Ada' }}
                        </p>
                        <p class="mt-2 text-xs" :class="fs.active_shift ? 'text-orange-100/80' : 'text-slate-500'">
                            {{ fs.active_shift ? `Buka ${fmtTime(fs.active_shift.opened_at)}` : 'Shift belum dibuka' }}
                        </p>
                    </article>
                </div>

                <!-- ─── Baris Chart + Breakdown ─────────────────────────── -->
                <div class="mt-4 grid gap-4 lg:grid-cols-[1.6fr_1fr_1fr]">

                    <!-- Hourly Revenue Chart (CSS bar chart) -->
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Revenue per Jam</p>
                                <p class="mt-0.5 text-xs text-slate-500">Hari ini · transaksi selesai</p>
                            </div>
                            <TrendingUp class="h-4 w-4 text-emerald-400" />
                        </div>
                        <div v-if="chartHours.length" class="mt-4">
                            <!-- Bar chart -->
                            <div class="flex h-24 items-end gap-px">
                                <div
                                    v-for="h in chartHours"
                                    :key="h.hour"
                                    class="group relative flex-1 cursor-default"
                                    :title="`${h.label}: ${fmt(h.revenue)}`"
                                >
                                    <div
                                        class="w-full rounded-t-sm transition-all duration-300"
                                        :class="h.revenue > 0 ? 'bg-emerald-500/70 group-hover:bg-emerald-400' : 'bg-white/5'"
                                        :style="`height: ${Math.max(h.pct, h.revenue > 0 ? 4 : 2)}%`"
                                    />
                                </div>
                            </div>
                            <!-- Hour labels (setiap 3 jam) -->
                            <div class="mt-1 flex justify-between px-0.5">
                                <span v-for="h in chartHours.filter((_, i) => i % 3 === 0)" :key="h.hour" class="text-[9px] text-slate-600">
                                    {{ String(h.hour).padStart(2, '0') }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="mt-6 text-center text-xs text-slate-500">Belum ada transaksi hari ini.</div>
                    </div>

                    <!-- Payment breakdown -->
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/50 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Metode Bayar</p>
                        <div v-if="paymentBreakdowns.length" class="mt-4 space-y-2.5">
                            <div v-for="item in paymentBreakdowns" :key="item.method" class="space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <component :is="payIcon[item.method] ?? Wallet" class="h-3 w-3 text-slate-400" />
                                        <span class="text-xs font-semibold text-slate-200">{{ payLabel[item.method] ?? item.method }}</span>
                                    </div>
                                    <span class="text-[11px] font-black text-emerald-300">{{ fmtShort(item.amount) }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-white/5">
                                    <div
                                        class="h-full rounded-full bg-emerald-500/70 transition-all duration-500"
                                        :style="`width: ${Math.round((item.amount / totalPayments) * 100)}%`"
                                    />
                                </div>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-xs text-slate-500">Belum ada pembayaran.</p>
                    </div>

                    <!-- Top 5 Products -->
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/50 p-5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">5 Produk Terlaris</p>
                            <Star class="h-3.5 w-3.5 text-amber-400" />
                        </div>
                        <div v-if="topProducts.length" class="mt-4 space-y-2.5">
                            <div v-for="(p, i) in topProducts" :key="i" class="flex items-center gap-2.5">
                                <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full text-[10px] font-black"
                                    :class="i === 0 ? 'bg-amber-500/20 text-amber-300' : 'bg-white/5 text-slate-500'"
                                >{{ i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-200">{{ p.name }}</p>
                                    <p class="text-[10px] text-slate-500">{{ p.quantity }} terjual</p>
                                </div>
                                <span class="flex-shrink-0 text-[11px] font-black text-sky-300">{{ fmtShort(p.revenue) }}</span>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-xs text-slate-500">Belum ada penjualan.</p>
                    </div>
                </div>

                <!-- Channel source + Quick actions -->
                <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_1.8fr]">
                    <!-- Source breakdown -->
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/50 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Channel Penjualan</p>
                        <div v-if="sourceBreakdowns.length" class="mt-4 grid gap-2">
                            <div
                                v-for="item in sourceBreakdowns"
                                :key="item.source"
                                class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.03] px-3.5 py-2.5"
                            >
                                <div>
                                    <p class="text-sm font-bold text-white">{{ srcLabel[item.source] ?? item.source }}</p>
                                    <p class="text-[11px] text-slate-500">{{ item.orders }} order</p>
                                </div>
                                <p class="text-sm font-black text-sky-300">{{ fmt(item.amount) }}</p>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-xs text-slate-500">Belum ada channel tercatat.</p>
                    </div>

                    <!-- Quick Access -->
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/50 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Akses Cepat</p>
                        <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                            <Link
                                :href="route('kasir.order')"
                                class="group flex flex-col items-center gap-2 rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4 text-center transition hover:-translate-y-0.5 hover:border-orange-400/40 hover:bg-orange-500/15"
                            >
                                <ShoppingCart class="h-6 w-6 text-orange-300" />
                                <span class="text-[11px] font-bold text-orange-200">Buat Order</span>
                            </Link>
                            <Link
                                :href="route('kitchen.display')"
                                class="group flex flex-col items-center gap-2 rounded-2xl border border-sky-400/20 bg-sky-500/10 p-4 text-center transition hover:-translate-y-0.5 hover:border-sky-400/40 hover:bg-sky-500/15"
                            >
                                <Utensils class="h-6 w-6 text-sky-300" />
                                <span class="text-[11px] font-bold text-sky-200">Kitchen</span>
                            </Link>
                            <Link
                                :href="route('stock-alerts.index')"
                                class="group flex flex-col items-center gap-2 rounded-2xl border border-amber-400/20 bg-amber-500/10 p-4 text-center transition hover:-translate-y-0.5 hover:border-amber-400/40 hover:bg-amber-500/15"
                            >
                                <AlertTriangle class="h-6 w-6 text-amber-300" />
                                <span class="text-[11px] font-bold text-amber-200">Alert Stok</span>
                            </Link>
                            <Link
                                :href="route('expired-tracking.index')"
                                class="group flex flex-col items-center gap-2 rounded-2xl border border-rose-400/20 bg-rose-500/10 p-4 text-center transition hover:-translate-y-0.5 hover:border-rose-400/40 hover:bg-rose-500/15"
                            >
                                <BellRing class="h-6 w-6 text-rose-300" />
                                <span class="text-[11px] font-bold text-rose-200">Expired</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Kasir/bar/kitchen: hanya tampilkan quick actions -->
            <section v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <Link :href="route('kasir.order')" class="flex flex-col items-center gap-3 rounded-[24px] border border-orange-400/20 bg-orange-500/10 py-6 text-center transition hover:bg-orange-500/15">
                    <ShoppingCart class="h-7 w-7 text-orange-300" />
                    <span class="text-sm font-bold text-orange-200">Buat Order</span>
                </Link>
                <Link :href="route('kitchen.display')" class="flex flex-col items-center gap-3 rounded-[24px] border border-sky-400/20 bg-sky-500/10 py-6 text-center transition hover:bg-sky-500/15">
                    <Utensils class="h-7 w-7 text-sky-300" />
                    <span class="text-sm font-bold text-sky-200">Kitchen</span>
                </Link>
                <Link :href="route('stock-alerts.index')" class="flex flex-col items-center gap-3 rounded-[24px] border border-amber-400/20 bg-amber-500/10 py-6 text-center transition hover:bg-amber-500/15">
                    <AlertTriangle class="h-7 w-7 text-amber-300" />
                    <span class="text-sm font-bold text-amber-200">Alert Stok</span>
                </Link>
                <Link :href="route('expired-tracking.index')" class="flex flex-col items-center gap-3 rounded-[24px] border border-rose-400/20 bg-rose-500/10 py-6 text-center transition hover:bg-rose-500/15">
                    <BellRing class="h-7 w-7 text-rose-300" />
                    <span class="text-sm font-bold text-rose-200">Expired</span>
                </Link>
            </section>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- ALERT SECTION — Stok + Expired                         -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="grid gap-4 lg:grid-cols-2">

                <!-- Stok Menipis -->
                <section class="rounded-[24px] border border-white/10 bg-slate-950/50 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/15">
                                <AlertTriangle class="h-4 w-4 text-amber-300" />
                            </div>
                            <div>
                                <p class="text-sm font-black text-white">Stok Menipis</p>
                                <p class="text-[11px] text-slate-500">{{ lowStockSummary.total }} alert · {{ lowStockSummary.critical }} kritis</p>
                            </div>
                        </div>
                        <Link :href="route('stock-alerts.index')" class="flex items-center gap-1 text-[11px] font-semibold text-orange-300 hover:text-orange-200">
                            Lihat semua <ChevronRight class="h-3 w-3" />
                        </Link>
                    </div>

                    <!-- Mini summary pills -->
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full border border-amber-400/20 bg-amber-500/10 px-2.5 py-1 text-[11px] font-semibold text-amber-200">
                            {{ lowStockSummary.products }} produk jadi
                        </span>
                        <span class="rounded-full border border-sky-400/20 bg-sky-500/10 px-2.5 py-1 text-[11px] font-semibold text-sky-200">
                            {{ lowStockSummary.raw_materials }} bahan baku
                        </span>
                        <span class="rounded-full border border-rose-400/20 bg-rose-500/10 px-2.5 py-1 text-[11px] font-semibold text-rose-200">
                            {{ lowStockSummary.critical }} kritis/habis
                        </span>
                    </div>

                    <div v-if="!lowStockItems.length" class="mt-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-300">
                        ✓ Semua stok masih aman.
                    </div>
                    <div v-else class="mt-3 space-y-2">
                        <article
                            v-for="item in lowStockItems.slice(0, 5)"
                            :key="`${item.type}-${item.id}`"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-white/5 bg-white/[0.02] px-3.5 py-2.5 transition hover:bg-white/[0.04]"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <p class="truncate text-xs font-bold text-white">{{ item.name }}</p>
                                    <span class="flex-shrink-0 rounded-full border px-1.5 py-0.5 text-[9px] font-bold"
                                        :class="item.severity >= 2 ? 'border-rose-400/20 bg-rose-500/10 text-rose-300' : 'border-amber-400/20 bg-amber-500/10 text-amber-300'"
                                    >
                                        {{ item.severity >= 2 ? 'KRITIS' : 'TIPIS' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ item.current_stock }}/{{ item.minimum_stock }} {{ item.unit || 'pcs' }}</p>
                            </div>
                            <Link :href="route(item.route)" class="flex-shrink-0 rounded-xl border border-orange-400/20 bg-orange-500/10 px-2.5 py-1.5 text-[10px] font-bold text-orange-300 hover:bg-orange-500/15">
                                Tindak
                            </Link>
                        </article>
                        <p v-if="lowStockItems.length > 5" class="mt-1 text-center text-[11px] text-slate-500">
                            +{{ lowStockItems.length - 5 }} item lainnya
                        </p>
                    </div>
                </section>

                <!-- Expired -->
                <section class="rounded-[24px] border border-white/10 bg-slate-950/50 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-500/15">
                                <BellRing class="h-4 w-4 text-rose-300" />
                            </div>
                            <div>
                                <p class="text-sm font-black text-white">Reminder Expired</p>
                                <p class="text-[11px] text-slate-500">{{ expiredSummary.critical }} perlu tindakan segera</p>
                            </div>
                        </div>
                        <Link :href="route('expired-tracking.index')" class="flex items-center gap-1 text-[11px] font-semibold text-orange-300 hover:text-orange-200">
                            Lihat semua <ChevronRight class="h-3 w-3" />
                        </Link>
                    </div>

                    <!-- Mini summary pills -->
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full border border-amber-400/20 bg-amber-500/10 px-2.5 py-1 text-[11px] font-semibold text-amber-200">
                            {{ expiredSummary.upcoming }} upcoming
                        </span>
                        <span class="rounded-full border border-orange-400/20 bg-orange-500/10 px-2.5 py-1 text-[11px] font-semibold text-orange-200">
                            {{ expiredSummary.today }} hari ini
                        </span>
                        <span class="rounded-full border border-rose-400/20 bg-rose-500/10 px-2.5 py-1 text-[11px] font-semibold text-rose-200">
                            {{ expiredSummary.expired }} sudah expired
                        </span>
                    </div>

                    <div v-if="!expiredItems.length" class="mt-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-300">
                        ✓ Tidak ada batch mendekati expired dalam 7 hari.
                    </div>
                    <div v-else class="mt-3 space-y-2">
                        <article
                            v-for="item in expiredItems.slice(0, 5)"
                            :key="item.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-white/5 bg-white/[0.02] px-3.5 py-2.5 transition hover:bg-white/[0.04]"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <p class="truncate text-xs font-bold text-white">{{ item.name }}</p>
                                    <span class="flex-shrink-0 rounded-full border px-1.5 py-0.5 text-[9px] font-bold"
                                        :class="item.status === 'expired'
                                            ? 'border-rose-400/20 bg-rose-500/10 text-rose-300'
                                            : item.status === 'today'
                                              ? 'border-orange-400/20 bg-orange-500/10 text-orange-300'
                                              : 'border-amber-400/20 bg-amber-500/10 text-amber-300'"
                                    >
                                        {{ item.status === 'expired' ? `+${Math.abs(item.days_left)}hr` : item.status === 'today' ? 'HARI INI' : `H-${item.days_left}` }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500">
                                    {{ item.trackable_type === 'product' ? 'Produk' : 'Bahan baku' }} · qty {{ item.quantity }}
                                </p>
                            </div>
                            <Link :href="route(item.route)" class="flex-shrink-0 rounded-xl border border-orange-400/20 bg-orange-500/10 px-2.5 py-1.5 text-[10px] font-bold text-orange-300 hover:bg-orange-500/15">
                                Tindak
                            </Link>
                        </article>
                        <p v-if="expiredItems.length > 5" class="mt-1 text-center text-[11px] text-slate-500">
                            +{{ expiredItems.length - 5 }} item lainnya
                        </p>
                    </div>
                </section>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
