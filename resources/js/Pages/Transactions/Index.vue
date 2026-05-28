<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarClock,
    CheckCircle2,
    CreditCard,
    FileClock,
    Receipt,
    Search,
    ShoppingBag,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface PaymentMethodOption {
    value: 'cash' | 'qris' | 'debit' | 'ewallet';
    label: string;
}

interface ProductOption {
    id: string;
    name: string;
    base_price?: number | string | null;
    prices?: Array<{ price: number | string }>;
}

interface CategoryOption {
    id: string;
    name: string;
    products: ProductOption[];
}

interface ProductListItem extends ProductOption {
    category_id: string;
    category_name: string;
    unit_price: number;
}

const props = defineProps<{
    summary: {
        history_count: number;
        completed_today: number;
        kasbon_count: number;
        kasbon_outstanding: number;
        preorder_count: number;
        preorder_dp_collected: number;
    };
    kasbonOrders: Array<{
        id: string;
        order_number: string;
        customer_name: string;
        customer_phone?: string | null;
        cashier_name?: string | null;
        total_amount: number;
        paid_amount: number;
        remaining_amount: number;
        due_date?: string | null;
        closed_at?: string | null;
        last_payment?: {
            amount: number;
            method?: string | null;
            created_at?: string | null;
        } | null;
    }>;
    preOrders: Array<{
        id: string;
        order_number: string;
        customer_name: string;
        customer_phone?: string | null;
        pickup_at?: string | null;
        subtotal: number;
        discount_amount: number;
        total_amount: number;
        paid_amount: number;
        remaining_amount: number;
        items_count: number;
        dp_rule?: string | null;
        dp_value?: number | string | null;
        dp_amount: number;
    }>;
    historyOrders: Array<{
        id: string;
        order_number: string;
        status: string;
        service_label: string;
        customer_name: string;
        customer_phone?: string | null;
        cashier_name?: string | null;
        subtotal: number;
        discount_amount: number;
        total_amount: number;
        paid_amount: number;
        remaining_amount: number;
        payment_method?: string | null;
        payment_status?: string | null;
        receipt_method?: string | null;
        created_at?: string | null;
        updated_at?: string | null;
        items_count: number;
        payment_logs_count: number;
        has_kasbon: boolean;
    }>;
    filters: {
        search: string;
        status: 'all' | 'completed' | 'cancelled' | 'scheduled';
        payment_method: 'all' | 'cash' | 'qris' | 'debit' | 'ewallet' | 'kasbon';
        start_date: string;
        end_date: string;
    };
    referenceData: {
        paymentMethods: PaymentMethodOption[];
        categories: CategoryOption[];
    };
    success?: string | null;
}>();

const filterForm = useForm({
    search: props.filters.search || '',
    status: props.filters.status || 'all',
    payment_method: props.filters.payment_method || 'all',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const installmentModalOpen = ref(false);
const selectedKasbonOrder = ref<(typeof props.kasbonOrders)[number] | null>(null);
const installmentForm = useForm({
    payment_method: 'cash' as 'cash' | 'qris' | 'debit' | 'ewallet',
    amount: '',
    notes: '',
});

const preOrderModalOpen = ref(false);
const activeCategory = ref<string>('all');
const preOrderSearch = ref('');
const preOrderForm = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    pickup_datetime: '',
    notes: '',
    promo_code: '',
    down_payment_type: 'percentage' as 'percentage' | 'fixed',
    down_payment_value: '50',
    payment_method: 'cash' as 'cash',
    items: [] as Array<{
        product_id: string;
        variant_id: string | null;
        quantity: number;
        unit_price: number;
        notes: string | null;
        product_name: string;
    }>,
});

const summaryCards = computed(() => [
    {
        label: 'Kasbon Aktif',
        value: props.summary.kasbon_count,
        helper: `${formatCurrency(props.summary.kasbon_outstanding)} outstanding`,
        tone: 'text-orange-300',
        surface: 'border-orange-400/15 bg-orange-500/10',
        icon: Wallet,
    },
    {
        label: 'Pre-Order Aktif',
        value: props.summary.preorder_count,
        helper: `DP terkumpul ${formatCurrency(props.summary.preorder_dp_collected)}`,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: FileClock,
    },
    {
        label: 'Transaksi Selesai Hari Ini',
        value: props.summary.completed_today,
        helper: `${props.summary.history_count} transaksi masuk riwayat`,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: CheckCircle2,
    },
]);

const allProducts = computed<ProductListItem[]>(() => {
    return props.referenceData.categories.flatMap((category) =>
        category.products.map((product) => ({
            ...product,
            category_id: category.id,
            category_name: category.name,
            unit_price: Number(product.prices?.[0]?.price ?? product.base_price ?? 0),
        })),
    );
});

const filteredProducts = computed(() => {
    let products = allProducts.value;

    if (activeCategory.value !== 'all') {
        products = products.filter((product) => product.category_id === activeCategory.value);
    }

    if (preOrderSearch.value) {
        const query = preOrderSearch.value.toLowerCase();
        products = products.filter((product) => product.name.toLowerCase().includes(query));
    }

    return products;
});

const preOrderSubtotal = computed(() =>
    preOrderForm.items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0),
);
const preOrderEstimatedDiscount = computed(() => 0);
const preOrderTotal = computed(() => Math.max(0, preOrderSubtotal.value - preOrderEstimatedDiscount.value));
const preOrderDpAmount = computed(() => {
    const total = preOrderTotal.value;
    const value = Number(preOrderForm.down_payment_value || 0);

    if (preOrderForm.down_payment_type === 'percentage') {
        return Math.max(0, Math.min(100, value)) / 100 * total;
    }

    return Math.min(total, Math.max(0, value));
});
const preOrderRemaining = computed(() => Math.max(0, preOrderTotal.value - preOrderDpAmount.value));

function formatCurrency(value: number | string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

function formatDateTime(value?: string | null) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

function formatDate(value?: string | null) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

function formatPaymentMethod(value?: string | null) {
    if (!value) return '-';
    if (value === 'qris') return 'QRIS';
    if (value === 'ewallet') return 'E-Wallet';
    return value.toUpperCase();
}

function historyStatusClass(status: string) {
    if (status === 'completed') return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300';
    if (status === 'cancelled') return 'border-rose-500/20 bg-rose-500/10 text-rose-300';
    return 'border-slate-700 bg-slate-950 text-slate-400';
}

function applyFilters() {
    router.get(route('transactions.index'), filterForm.data(), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.search = '';
    filterForm.status = 'all';
    filterForm.payment_method = 'all';
    filterForm.start_date = props.filters.start_date || '';
    filterForm.end_date = props.filters.end_date || '';
    applyFilters();
}

function openInstallmentModal(order: (typeof props.kasbonOrders)[number]) {
    selectedKasbonOrder.value = order;
    installmentForm.reset();
    installmentForm.clearErrors();
    installmentForm.payment_method = 'cash';
    installmentForm.amount = String(order.remaining_amount);
    installmentForm.notes = '';
    installmentModalOpen.value = true;
}

function closeInstallmentModal() {
    installmentModalOpen.value = false;
    selectedKasbonOrder.value = null;
}

function submitInstallment() {
    if (!selectedKasbonOrder.value) return;

    installmentForm.post(route('transactions.installments.store', selectedKasbonOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeInstallmentModal();
        },
    });
}

function openPreOrderModal() {
    preOrderForm.reset();
    preOrderForm.clearErrors();
    preOrderForm.down_payment_type = 'percentage';
    preOrderForm.down_payment_value = '50';
    preOrderForm.payment_method = 'cash';
    preOrderForm.items = [];
    preOrderModalOpen.value = true;
}

function closePreOrderModal() {
    preOrderModalOpen.value = false;
}

function addProduct(product: ProductListItem) {
    const existing = preOrderForm.items.find((item) => item.product_id === product.id && !item.variant_id);

    if (existing) {
        existing.quantity += 1;
        return;
    }

    preOrderForm.items.push({
        product_id: product.id,
        variant_id: null,
        quantity: 1,
        unit_price: Number(product.unit_price || 0),
        notes: null,
        product_name: product.name,
    });
}

function adjustPreOrderQty(index: number, delta: number) {
    const item = preOrderForm.items[index];
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
        preOrderForm.items.splice(index, 1);
    }
}

function submitPreOrder() {
    preOrderForm.transform((data) => ({
        ...data,
        items: data.items.map((item) => ({
            product_id: item.product_id,
            variant_id: item.variant_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            notes: item.notes,
        })),
        down_payment_value: Number(data.down_payment_value || 0),
    })).post(route('transactions.pre-orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closePreOrderModal();
        },
    });
}

function activatePreOrder(orderId: string) {
    router.post(route('transactions.pre-orders.activate', orderId), {}, {
        preserveScroll: true,
    });
}

function openReceipt(orderId: string) {
    window.open(route('transactions.receipt.show', orderId), '_blank');
}
</script>

<template>
    <Head title="Kasbon, Pre-Order, dan Riwayat Transaksi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <Receipt class="h-3.5 w-3.5" />
                    Menu #7, #8, #10, #11
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Kasbon, Pre-Order, dan Riwayat Transaksi
                    </h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Kelola cicilan kasbon, pre-order dengan DP, preview struk, dan riwayat transaksi outlet.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div
                v-if="success"
                class="flex items-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-300"
            >
                <CheckCircle2 class="h-4 w-4" />
                <span>{{ success }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border p-5"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-3xl font-black" :class="card.tone">
                                {{ card.value }}
                            </p>
                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                {{ card.helper }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <component :is="card.icon" class="h-5 w-5 text-slate-200" />
                        </div>
                    </div>
                </div>
            </div>

            <section class="rounded-3xl border border-slate-800/80 bg-slate-900/80 p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">
                            Filter Riwayat
                        </p>
                        <h3 class="mt-2 text-lg font-black text-white">
                            Transaksi Selesai & Dokumen Struk
                        </h3>
                        <p class="mt-1 text-sm text-slate-400">
                            Menu #11 untuk riwayat transaksi dan Menu #10 untuk preview/cetak struk dari transaksi yang sudah selesai.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link
                            :href="route('kasir.order')"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-200 transition hover:border-slate-600"
                        >
                            <ShoppingBag class="h-4 w-4" />
                            Buka Kasir & Voucher
                        </Link>
                        <button
                            type="button"
                            @click="openPreOrderModal"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-5 py-3 text-sm font-bold text-white transition"
                        >
                            <CalendarClock class="h-4 w-4" />
                            Buat Pre-Order / DP
                        </button>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 lg:grid-cols-[1.2fr_repeat(4,0.8fr)]">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-500" />
                        <input
                            v-model="filterForm.search"
                            type="text"
                            placeholder="Cari nomor order / customer..."
                            class="w-full rounded-2xl border border-slate-800 bg-slate-950 py-3 pl-10 pr-4 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        />
                    </div>
                    <select
                        v-model="filterForm.status"
                        class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    >
                        <option value="all">Semua Status</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select
                        v-model="filterForm.payment_method"
                        class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    >
                        <option value="all">Semua Metode</option>
                        <option value="cash">Cash</option>
                        <option value="qris">QRIS</option>
                        <option value="debit">Debit</option>
                        <option value="ewallet">E-Wallet</option>
                        <option value="kasbon">Kasbon</option>
                    </select>
                    <input
                        v-model="filterForm.start_date"
                        type="date"
                        class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    />
                    <input
                        v-model="filterForm.end_date"
                        type="date"
                        class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    />
                </div>

                <div class="mt-4 flex gap-3">
                    <button
                        type="button"
                        @click="applyFilters"
                        class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-orange-600"
                    >
                        Terapkan Filter
                    </button>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-300 transition hover:border-slate-600"
                    >
                        Reset
                    </button>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <section class="rounded-3xl border border-slate-800/80 bg-slate-900/80">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-800/80 px-6 py-5">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Menu #7
                            </p>
                            <h3 class="mt-2 text-lg font-black text-white">
                                Kasbon & Cicilan
                            </h3>
                        </div>
                        <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ kasbonOrders.length }} order
                        </span>
                    </div>

                    <div class="divide-y divide-slate-800/80">
                        <div
                            v-for="order in kasbonOrders"
                            :key="order.id"
                            class="px-6 py-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black text-white">
                                        {{ order.order_number }} · {{ order.customer_name }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ order.customer_phone || 'Tanpa nomor HP' }} · Ditutup kasbon {{ formatDateTime(order.closed_at) }}
                                    </p>
                                    <p class="mt-2 text-xs text-slate-500">
                                        Jatuh tempo: {{ formatDate(order.due_date) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] text-slate-500">Sisa tagihan</p>
                                    <p class="mt-1 text-lg font-black text-orange-300">
                                        {{ formatCurrency(order.remaining_amount) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Total</p>
                                    <p class="mt-2 text-sm font-bold text-white">{{ formatCurrency(order.total_amount) }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Terbayar</p>
                                    <p class="mt-2 text-sm font-bold text-emerald-300">{{ formatCurrency(order.paid_amount) }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Pembayaran Terakhir</p>
                                    <p class="mt-2 text-sm font-bold text-white">
                                        {{ order.last_payment ? formatCurrency(order.last_payment.amount) : '-' }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-500">
                                        {{ order.last_payment ? `${formatPaymentMethod(order.last_payment.method)} • ${formatDateTime(order.last_payment.created_at)}` : 'Belum ada cicilan' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    @click="openInstallmentModal(order)"
                                    class="rounded-2xl bg-orange-500 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-orange-600"
                                >
                                    Bayar Cicilan
                                </button>
                                <button
                                    type="button"
                                    @click="openReceipt(order.id)"
                                    class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs font-bold text-slate-300 transition hover:border-slate-600"
                                >
                                    Preview Struk
                                </button>
                            </div>
                        </div>

                        <div v-if="!kasbonOrders.length" class="px-6 py-12 text-center">
                            <p class="text-sm font-semibold text-white">Belum ada kasbon aktif.</p>
                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Tutup order sebagai kasbon dari halaman kasir untuk memunculkannya di sini.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-800/80 bg-slate-900/80">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-800/80 px-6 py-5">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Menu #8
                            </p>
                            <h3 class="mt-2 text-lg font-black text-white">
                                Pre-Order / Down Payment
                            </h3>
                        </div>
                        <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ preOrders.length }} pre-order
                        </span>
                    </div>

                    <div class="divide-y divide-slate-800/80">
                        <div
                            v-for="order in preOrders"
                            :key="order.id"
                            class="px-6 py-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black text-white">
                                        {{ order.order_number }} · {{ order.customer_name }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Pickup {{ formatDateTime(order.pickup_at) }} · {{ order.items_count }} item
                                    </p>
                                    <p class="mt-2 text-xs text-slate-500">
                                        DP {{ order.dp_rule === 'percentage' ? `${order.dp_value}%` : formatCurrency(order.dp_value || 0) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] text-slate-500">Sisa bayar</p>
                                    <p class="mt-1 text-lg font-black text-sky-300">
                                        {{ formatCurrency(order.remaining_amount) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Total</p>
                                    <p class="mt-2 text-sm font-bold text-white">{{ formatCurrency(order.total_amount) }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">DP Dibayar</p>
                                    <p class="mt-2 text-sm font-bold text-emerald-300">{{ formatCurrency(order.paid_amount) }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Diskon</p>
                                    <p class="mt-2 text-sm font-bold text-white">{{ formatCurrency(order.discount_amount) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    @click="activatePreOrder(order.id)"
                                    class="rounded-2xl bg-sky-500 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-sky-600"
                                >
                                    Aktifkan ke Dapur
                                </button>
                                <button
                                    type="button"
                                    @click="openReceipt(order.id)"
                                    class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs font-bold text-slate-300 transition hover:border-slate-600"
                                >
                                    Preview Struk
                                </button>
                            </div>
                        </div>

                        <div v-if="!preOrders.length" class="px-6 py-12 text-center">
                            <p class="text-sm font-semibold text-white">Belum ada pre-order aktif.</p>
                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Gunakan tombol "Buat Pre-Order / DP" untuk menyimpan order pickup berikut down payment.
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="rounded-3xl border border-slate-800/80 bg-slate-900/80">
                <div class="flex items-center justify-between gap-4 border-b border-slate-800/80 px-6 py-5">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">
                            Menu #10 & #11
                        </p>
                        <h3 class="mt-2 text-lg font-black text-white">
                            Riwayat Transaksi & Struk
                        </h3>
                    </div>
                    <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        {{ historyOrders.length }} transaksi
                    </span>
                </div>

                <div v-if="historyOrders.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                        <thead class="bg-slate-950/70 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Order</th>
                                <th class="px-6 py-3">Pelanggan</th>
                                <th class="px-6 py-3">Nominal</th>
                                <th class="px-6 py-3">Payment</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            <tr
                                v-for="order in historyOrders"
                                :key="order.id"
                                class="bg-slate-900/40"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-black text-white">{{ order.order_number }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ order.service_label }} · {{ order.items_count }} item · {{ formatDateTime(order.updated_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-200">{{ order.customer_name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ order.customer_phone || order.cashier_name || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white">{{ formatCurrency(order.total_amount) }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Bayar {{ formatCurrency(order.paid_amount) }} · Sisa {{ formatCurrency(order.remaining_amount) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-200">{{ formatPaymentMethod(order.payment_method) }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ order.payment_status || 'n/a' }} · {{ order.payment_logs_count }} log
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                        :class="historyStatusClass(order.status)"
                                    >
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            @click="openReceipt(order.id)"
                                            class="rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-[11px] font-bold text-slate-200 transition hover:border-slate-600"
                                        >
                                            Preview Struk
                                        </button>
                                        <button
                                            v-if="order.has_kasbon"
                                            type="button"
                                            @click="openInstallmentModal(kasbonOrders.find((entry) => entry.id === order.id) || {
                                                id: order.id,
                                                order_number: order.order_number,
                                                customer_name: order.customer_name,
                                                customer_phone: order.customer_phone,
                                                cashier_name: order.cashier_name,
                                                total_amount: order.total_amount,
                                                paid_amount: order.paid_amount,
                                                remaining_amount: order.remaining_amount,
                                                due_date: null,
                                                closed_at: order.updated_at,
                                                last_payment: null,
                                            })"
                                            class="rounded-xl border border-orange-500/20 bg-orange-500/10 px-3 py-2 text-[11px] font-bold text-orange-300 transition hover:bg-orange-500/15"
                                        >
                                            Bayar Cicilan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-12 text-center">
                    <p class="text-sm font-semibold text-white">Belum ada transaksi sesuai filter.</p>
                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        Coba ubah rentang tanggal, metode pembayaran, atau kata kunci pencarian.
                    </p>
                </div>
            </section>
        </div>

        <div
            v-if="installmentModalOpen && selectedKasbonOrder"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl">
                <div class="border-b border-slate-800/80 px-6 py-5">
                    <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">Pembayaran Cicilan</p>
                    <h3 class="mt-2 text-xl font-black text-white">
                        {{ selectedKasbonOrder.order_number }} · {{ selectedKasbonOrder.customer_name }}
                    </h3>
                    <p class="mt-1 text-xs text-slate-400">
                        Sisa tagihan {{ formatCurrency(selectedKasbonOrder.remaining_amount) }}
                    </p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            Metode Pembayaran
                        </label>
                        <select
                            v-model="installmentForm.payment_method"
                            class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        >
                            <option
                                v-for="method in referenceData.paymentMethods"
                                :key="method.value"
                                :value="method.value"
                            >
                                {{ method.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            Nominal Cicilan
                        </label>
                        <input
                            v-model="installmentForm.amount"
                            type="number"
                            min="1"
                            step="1000"
                            class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            Catatan
                        </label>
                        <textarea
                            v-model="installmentForm.notes"
                            rows="3"
                            class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        ></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-800/80 px-6 py-4">
                    <button
                        type="button"
                        @click="closeInstallmentModal"
                        class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-300 transition hover:border-slate-600"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitInstallment"
                        :disabled="installmentForm.processing"
                        class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-orange-600 disabled:pointer-events-none disabled:opacity-50"
                    >
                        {{ installmentForm.processing ? 'Menyimpan...' : 'Simpan Cicilan' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="preOrderModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
        >
            <div class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-800/80 px-6 py-5">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">Menu #8</p>
                        <h3 class="mt-2 text-xl font-black text-white">Buat Pre-Order / Down Payment</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Flow ini menyimpan order pickup/delivery future dengan DP cash dan aktivasi manual saat siap dikirim ke dapur.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closePreOrderModal"
                        class="text-slate-500 transition hover:text-slate-200"
                    >
                        Tutup
                    </button>
                </div>

                <div class="grid min-h-0 flex-1 gap-0 xl:grid-cols-[1.1fr_0.9fr]">
                    <div class="flex min-h-0 flex-col border-r border-slate-800/70">
                        <div class="grid gap-3 border-b border-slate-800/70 px-6 py-5 sm:grid-cols-2">
                            <input
                                v-model="preOrderForm.customer_name"
                                type="text"
                                placeholder="Nama customer"
                                class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                            <input
                                v-model="preOrderForm.customer_phone"
                                type="text"
                                placeholder="Nomor HP customer"
                                class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                            <input
                                v-model="preOrderForm.customer_email"
                                type="email"
                                placeholder="Email customer (opsional)"
                                class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                            <input
                                v-model="preOrderForm.pickup_datetime"
                                type="datetime-local"
                                class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                            <input
                                v-model="preOrderForm.promo_code"
                                type="text"
                                placeholder="Voucher / promo code"
                                class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm uppercase text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 sm:col-span-2"
                            />
                            <textarea
                                v-model="preOrderForm.notes"
                                rows="3"
                                placeholder="Catatan khusus pre-order"
                                class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 sm:col-span-2"
                            ></textarea>
                        </div>

                        <div class="border-b border-slate-800/70 px-6 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="activeCategory = 'all'"
                                        :class="activeCategory === 'all' ? 'border-orange-500/30 bg-orange-500/10 text-orange-300' : 'border-slate-800 bg-slate-950 text-slate-400'"
                                        class="rounded-xl border px-3 py-2 text-[11px] font-bold transition"
                                    >
                                        Semua Menu
                                    </button>
                                    <button
                                        v-for="category in referenceData.categories"
                                        :key="category.id"
                                        type="button"
                                        @click="activeCategory = category.id"
                                        :class="activeCategory === category.id ? 'border-orange-500/30 bg-orange-500/10 text-orange-300' : 'border-slate-800 bg-slate-950 text-slate-400'"
                                        class="rounded-xl border px-3 py-2 text-[11px] font-bold transition"
                                    >
                                        {{ category.name }}
                                    </button>
                                </div>
                                <input
                                    v-model="preOrderSearch"
                                    type="text"
                                    placeholder="Cari menu..."
                                    class="w-full max-w-xs rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                />
                            </div>
                        </div>

                        <div class="custom-scrollbar grid flex-1 gap-3 overflow-y-auto px-6 py-5 sm:grid-cols-2">
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                type="button"
                                @click="addProduct(product)"
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 text-left transition hover:border-orange-500/20"
                            >
                                <p class="text-sm font-bold text-white">{{ product.name }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ product.category_name }}</p>
                                <p class="mt-3 text-sm font-black text-orange-300">{{ formatCurrency(product.unit_price) }}</p>
                            </button>
                        </div>
                    </div>

                    <div class="flex min-h-0 flex-col">
                        <div class="border-b border-slate-800/70 px-6 py-5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">Ringkasan Pre-Order</p>
                            <h4 class="mt-2 text-lg font-black text-white">{{ preOrderForm.items.length }} item terpilih</h4>
                        </div>

                        <div class="custom-scrollbar flex-1 space-y-3 overflow-y-auto px-6 py-5">
                            <div
                                v-for="(item, index) in preOrderForm.items"
                                :key="`${item.product_id}-${index}`"
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-white">{{ item.product_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ formatCurrency(item.unit_price) }} / item</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="adjustPreOrderQty(index, -999)"
                                        class="text-xs font-bold text-rose-300"
                                    >
                                        Hapus
                                    </button>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            @click="adjustPreOrderQty(index, -1)"
                                            class="rounded-lg border border-slate-700 px-2 py-1 text-slate-300"
                                        >
                                            -
                                        </button>
                                        <span class="text-sm font-black text-white">{{ item.quantity }}</span>
                                        <button
                                            type="button"
                                            @click="adjustPreOrderQty(index, 1)"
                                            class="rounded-lg border border-slate-700 px-2 py-1 text-slate-300"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <span class="text-sm font-black text-orange-300">
                                        {{ formatCurrency(item.unit_price * item.quantity) }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="!preOrderForm.items.length" class="rounded-2xl border border-dashed border-slate-800 px-4 py-10 text-center text-sm text-slate-500">
                                Pilih produk dari panel kiri untuk menambahkan item pre-order.
                            </div>
                        </div>

                        <div class="space-y-4 border-t border-slate-800/70 px-6 py-5">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <select
                                    v-model="preOrderForm.down_payment_type"
                                    class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                >
                                    <option value="percentage">DP Persentase</option>
                                    <option value="fixed">DP Nominal Tetap</option>
                                </select>
                                <input
                                    v-model="preOrderForm.down_payment_value"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                />
                            </div>

                            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 text-sm">
                                <div class="flex justify-between text-slate-400">
                                    <span>Subtotal</span>
                                    <span>{{ formatCurrency(preOrderSubtotal) }}</span>
                                </div>
                                <div class="mt-2 flex justify-between text-slate-400">
                                    <span>Estimasi diskon</span>
                                    <span>{{ formatCurrency(preOrderEstimatedDiscount) }}</span>
                                </div>
                                <div class="mt-2 flex justify-between text-white">
                                    <span>Total</span>
                                    <span class="font-black text-orange-300">{{ formatCurrency(preOrderTotal) }}</span>
                                </div>
                                <div class="mt-4 rounded-2xl border border-orange-500/20 bg-orange-500/10 p-3">
                                    <div class="flex justify-between text-orange-100">
                                        <span>DP dikumpulkan</span>
                                        <span class="font-black">{{ formatCurrency(preOrderDpAmount) }}</span>
                                    </div>
                                    <div class="mt-2 flex justify-between text-slate-300">
                                        <span>Sisa tagihan</span>
                                        <span class="font-bold">{{ formatCurrency(preOrderRemaining) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="closePreOrderModal"
                                    class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-300 transition hover:border-slate-600"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="submitPreOrder"
                                    :disabled="preOrderForm.processing || !preOrderForm.items.length"
                                    class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-orange-600 disabled:pointer-events-none disabled:opacity-50"
                                >
                                    {{ preOrderForm.processing ? 'Menyimpan...' : 'Simpan Pre-Order' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
