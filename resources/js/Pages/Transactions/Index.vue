<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarClock,
    CheckCircle2,
    FileClock,
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
        status: 'all' | 'completed' | 'cancelled';
        payment_method:
            | 'all'
            | 'cash'
            | 'qris'
            | 'debit'
            | 'ewallet'
            | 'kasbon';
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
const selectedKasbonOrder = ref<(typeof props.kasbonOrders)[number] | null>(
    null,
);
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
        tone: 'text-orange-700 dark:text-orange-300 font-black',
        surface: 'border-2 border-orange-700 bg-orange-100 dark:border-orange-500 dark:bg-orange-950/80',
        icon: Wallet,
    },
    {
        label: 'Pre-Order Aktif',
        value: props.summary.preorder_count,
        helper: `DP terkumpul ${formatCurrency(props.summary.preorder_dp_collected)}`,
        tone: 'text-sky-700 dark:text-sky-300 font-black',
        surface: 'border-2 border-sky-700 bg-sky-100 dark:border-sky-500 dark:bg-sky-950/80',
        icon: FileClock,
    },
    {
        label: 'Transaksi Selesai Hari Ini',
        value: props.summary.completed_today,
        helper: `${props.summary.history_count} transaksi masuk riwayat`,
        tone: 'text-emerald-700 dark:text-emerald-300 font-black',
        surface: 'border-2 border-emerald-700 bg-emerald-100 dark:border-emerald-500 dark:bg-emerald-950/80',
        icon: CheckCircle2,
    },
]);

const allProducts = computed<ProductListItem[]>(() => {
    return props.referenceData.categories.flatMap((category) =>
        category.products.map((product) => ({
            ...product,
            category_id: category.id,
            category_name: category.name,
            unit_price: Number(
                product.prices?.[0]?.price ?? product.base_price ?? 0,
            ),
        })),
    );
});

const filteredProducts = computed(() => {
    let products = allProducts.value;

    if (activeCategory.value !== 'all') {
        products = products.filter(
            (product) => product.category_id === activeCategory.value,
        );
    }

    if (preOrderSearch.value) {
        const query = preOrderSearch.value.toLowerCase();
        products = products.filter((product) =>
            product.name.toLowerCase().includes(query),
        );
    }

    return products;
});

const preOrderSubtotal = computed(() =>
    preOrderForm.items.reduce(
        (sum, item) => sum + item.unit_price * item.quantity,
        0,
    ),
);
const preOrderEstimatedDiscount = computed(() => 0);
const preOrderTotal = computed(() =>
    Math.max(0, preOrderSubtotal.value - preOrderEstimatedDiscount.value),
);
const preOrderDpAmount = computed(() => {
    const total = preOrderTotal.value;
    const value = Number(preOrderForm.down_payment_value || 0);

    if (preOrderForm.down_payment_type === 'percentage') {
        return (Math.max(0, Math.min(100, value)) / 100) * total;
    }

    return Math.min(total, Math.max(0, value));
});
const preOrderRemaining = computed(() =>
    Math.max(0, preOrderTotal.value - preOrderDpAmount.value),
);

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
    if (status === 'completed')
        return 'border-2 border-emerald-700 bg-emerald-100 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300';
    if (status === 'cancelled')
        return 'border-2 border-rose-700 bg-rose-100 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300';
    return 'border-2 border-stone-200 bg-stone-100 text-black dark:border-white/10 dark:bg-slate-950 dark:text-slate-400';
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

    installmentForm.post(
        route('transactions.installments.store', selectedKasbonOrder.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeInstallmentModal();
            },
        },
    );
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
    const existing = preOrderForm.items.find(
        (item) => item.product_id === product.id && !item.variant_id,
    );

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
    preOrderForm
        .transform((data) => ({
            ...data,
            items: data.items.map((item) => ({
                product_id: item.product_id,
                variant_id: item.variant_id,
                quantity: item.quantity,
                unit_price: item.unit_price,
                notes: item.notes,
            })),
            down_payment_value: Number(data.down_payment_value || 0),
        }))
        .post(route('transactions.pre-orders.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closePreOrderModal();
            },
        });
}

function activatePreOrder(orderId: string) {
    router.post(
        route('transactions.pre-orders.activate', orderId),
        {},
        {
            preserveScroll: true,
        },
    );
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
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-black dark:text-white"
                    >
                        Transaksi & Histori Order
                    </h2>
                    <p class="mt-1 text-sm font-bold text-black dark:text-slate-300">
                        Kelola cicilan kasbon, pre-order dengan DP, preview
                        struk, dan riwayat transaksi outlet.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div
                v-if="success"
                class="flex items-center gap-2 rounded-2xl border-2 border-emerald-700 bg-emerald-100 px-4 py-3 text-sm font-black text-emerald-950 dark:border-emerald-500 dark:bg-emerald-950/80 dark:text-emerald-100"
            >
                <CheckCircle2 class="h-4.5 w-4.5 text-emerald-700 dark:text-emerald-500" />
                <span>{{ success }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border-2 p-5"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[11px] font-black uppercase tracking-[0.2em] text-black dark:text-slate-300"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-3 text-3xl font-black"
                                :class="card.tone"
                            >
                                {{ card.value }}
                            </p>
                            <p
                                class="mt-2 text-xs font-bold leading-5 text-black dark:text-slate-300"
                            >
                                {{ card.helper }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                        >
                            <component
                                :is="card.icon"
                                class="h-5 w-5 text-black dark:text-slate-200"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <section
                class="rounded-3xl border-2 border-stone-200 bg-stone-50/50 p-6 dark:border-white/10 dark:bg-slate-900/80"
            >
                <div
                    class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between"
                >
                    <div>
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-700 dark:text-orange-400"
                        >
                            Filter Riwayat
                        </p>
                        <h3
                            class="mt-2 text-lg font-black text-black dark:text-white"
                        >
                            Transaksi Selesai & Dokumen Struk
                        </h3>
                        <p
                            class="mt-1 text-sm font-bold text-black dark:text-slate-300"
                        >
                            <span>Shortcut riwayat transaksi dan preview/cetak
                            struk dari transaksi yang sudah selesai.</span>
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link
                            :href="route('kasir.order')"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border-2 border-stone-200 bg-white px-4 py-3 text-sm font-black text-black transition hover:bg-stone-100 dark:border-white/10 dark:bg-slate-950 dark:text-slate-200"
                        >
                            <ShoppingBag class="h-4 w-4" />
                            Buka Kasir & Voucher
                        </Link>
                        <button
                            type="button"
                            @click="openPreOrderModal"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border-2 border-stone-200 bg-orange-500 hover:bg-orange-400 px-5 py-3 text-sm font-black text-stone-950 transition dark:border-white/10"
                        >
                            <CalendarClock class="h-4 w-4" />
                            Buat Pre-Order / DP
                        </button>
                    </div>
                </div>

                <div
                    class="mt-6 grid gap-3 lg:grid-cols-[1.2fr_repeat(4,0.8fr)]"
                >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-black dark:text-slate-400"
                        />
                        <input
                            v-model="filterForm.search"
                            type="text"
                            placeholder="Cari nomor order / customer..."
                            class="w-full rounded-2xl border-2 border-stone-200 bg-white py-3 pl-10 pr-4 text-sm font-black text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100 placeholder-stone-600 dark:placeholder-slate-400"
                        />
                    </div>
                    <select
                        v-model="filterForm.status"
                        class="rounded-2xl border-2 border-stone-200 bg-white px-4 py-3 text-sm font-black text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                    >
                        <option value="all">Semua Status</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select
                        v-model="filterForm.payment_method"
                        class="rounded-2xl border-2 border-stone-200 bg-white px-4 py-3 text-sm font-black text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
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
                        class="rounded-2xl border-2 border-stone-200 bg-white px-4 py-3 text-sm font-black text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                    />
                    <input
                        v-model="filterForm.end_date"
                        type="date"
                        class="rounded-2xl border-2 border-stone-200 bg-white px-4 py-3 text-sm font-black text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                    />
                </div>

                <div class="mt-4 flex gap-3">
                    <button
                        type="button"
                        @click="applyFilters"
                        class="rounded-2xl border-2 border-stone-200 bg-orange-600 px-4 py-3 text-sm font-black text-white transition hover:bg-orange-700 dark:border-white/10"
                    >
                        Terapkan Filter
                    </button>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="rounded-2xl border-2 border-stone-200 bg-white px-4 py-3 text-sm font-black text-black transition hover:bg-stone-100 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300"
                    >
                        Reset
                    </button>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <section
                    class="rounded-3xl border-2 border-stone-200 bg-stone-50/50 dark:border-white/10 dark:bg-slate-900/80"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b-2 border-stone-200 px-6 py-5 dark:border-white/10"
                    >
                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-700 dark:text-orange-400"
                            >
                                Kasbon
                            </p>
                            <h3
                                class="mt-2 text-lg font-black text-black dark:text-white"
                            >
                                Kasbon & Cicilan
                            </h3>
                        </div>
                        <span
                            class="rounded-full border-2 border-stone-200 bg-stone-100 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-black dark:border-white/10 dark:bg-slate-950 dark:text-white"
                        >
                            {{ kasbonOrders.length }} order
                        </span>
                    </div>

                    <div class="divide-y-2 divide-black dark:divide-slate-700">
                        <div
                            v-for="order in kasbonOrders"
                            :key="order.id"
                            class="px-6 py-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p
                                        class="text-sm font-black text-black dark:text-white"
                                    >
                                        {{ order.order_number }} ·
                                        {{ order.customer_name }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs font-bold text-black dark:text-slate-300"
                                    >
                                        {{
                                            order.customer_phone ||
                                            'Tanpa nomor HP'
                                        }}
                                        · Ditutup kasbon
                                        {{ formatDateTime(order.closed_at) }}
                                    </p>
                                    <p
                                        class="mt-2 text-xs font-black text-red-600 dark:text-red-400"
                                    >
                                        Jatuh tempo:
                                        {{ formatDate(order.due_date) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-[11px] font-bold text-black dark:text-slate-400"
                                    >
                                        Sisa tagihan
                                    </p>
                                    <p
                                        class="mt-1 text-lg font-black text-orange-700 dark:text-orange-400"
                                    >
                                        {{
                                            formatCurrency(
                                                order.remaining_amount,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div
                                    class="rounded-2xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-black dark:text-slate-400"
                                    >
                                        Total
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-black text-black dark:text-white"
                                    >
                                        {{ formatCurrency(order.total_amount) }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-black dark:text-slate-400"
                                    >
                                        Terbayar
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-black text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{ formatCurrency(order.paid_amount) }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-black dark:text-slate-400"
                                    >
                                        Pembayaran Terakhir
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-black text-black dark:text-white"
                                    >
                                        {{
                                            order.last_payment
                                                ? formatCurrency(
                                                      order.last_payment.amount,
                                                  )
                                                : '-'
                                        }}
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] font-bold text-black dark:text-slate-400"
                                    >
                                        {{
                                            order.last_payment
                                                ? `${formatPaymentMethod(order.last_payment.method)} • ${formatDateTime(order.last_payment.created_at)}`
                                                : 'Belum ada cicilan'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    @click="openInstallmentModal(order)"
                                    class="rounded-2xl border-2 border-stone-200 bg-orange-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-orange-700 dark:border-white/10"
                                >
                                    Bayar Cicilan
                                </button>
                                <button
                                    type="button"
                                    @click="openReceipt(order.id)"
                                    class="rounded-2xl border-2 border-stone-200 bg-white px-4 py-2.5 text-xs font-black text-black transition hover:bg-stone-50 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                                >
                                    Preview Struk
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!kasbonOrders.length"
                            class="px-6 py-12 text-center"
                        >
                            <p
                                class="text-sm font-black text-black dark:text-white"
                            >
                                Belum ada kasbon aktif.
                            </p>
                            <p
                                class="mt-2 text-xs font-bold leading-5 text-black dark:text-slate-400"
                            >
                                Tutup order sebagai kasbon dari halaman kasir
                                untuk memunculkannya di sini.
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-3xl border-2 border-stone-200 bg-stone-50/50 dark:border-white/10 dark:bg-slate-900/80"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b-2 border-stone-200 px-6 py-5 dark:border-white/10"
                    >
                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-700 dark:text-orange-400"
                            >
                                Pre-Order
                            </p>
                            <h3
                                class="mt-2 text-lg font-black text-black dark:text-white"
                            >
                                Pre-Order / Down Payment
                            </h3>
                        </div>
                        <span
                            class="rounded-full border-2 border-stone-200 bg-stone-100 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-black dark:border-white/10 dark:bg-slate-950 dark:text-white"
                        >
                            {{ preOrders.length }} pre-order
                        </span>
                    </div>

                    <div class="divide-y-2 divide-black dark:divide-slate-700">
                        <div
                            v-for="order in preOrders"
                            :key="order.id"
                            class="px-6 py-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p
                                        class="text-sm font-black text-black dark:text-white"
                                    >
                                        {{ order.order_number }} ·
                                        {{ order.customer_name }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs font-bold text-black dark:text-slate-300"
                                    >
                                        Pickup
                                        {{ formatDateTime(order.pickup_at) }} ·
                                        {{ order.items_count }} item
                                    </p>
                                    <p
                                        class="mt-2 text-xs font-black text-black dark:text-slate-400"
                                    >
                                        DP
                                        {{
                                            order.dp_rule === 'percentage'
                                                ? `${order.dp_value}%`
                                                : formatCurrency(
                                                      order.dp_value || 0,
                                                  )
                                        }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-[11px] font-bold text-black dark:text-slate-400"
                                    >
                                        Sisa bayar
                                    </p>
                                    <p
                                        class="mt-1 text-lg font-black text-sky-700 dark:text-sky-400"
                                    >
                                        {{
                                            formatCurrency(
                                                order.remaining_amount,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div
                                    class="rounded-2xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-black dark:text-slate-400"
                                    >
                                        Total
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-black text-black dark:text-white"
                                    >
                                        {{ formatCurrency(order.total_amount) }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-black dark:text-slate-400"
                                    >
                                        DP Dibayar
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-black text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{ formatCurrency(order.paid_amount) }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl border-2 border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-900"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-black dark:text-slate-400"
                                    >
                                        Diskon
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-black text-black dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                order.discount_amount,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    @click="activatePreOrder(order.id)"
                                    class="rounded-2xl border-2 border-stone-200 bg-sky-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-sky-700 dark:border-white/10"
                                >
                                    Aktifkan ke Dapur
                                </button>
                                <button
                                    type="button"
                                    @click="openReceipt(order.id)"
                                    class="rounded-2xl border-2 border-stone-200 bg-white px-4 py-2.5 text-xs font-black text-black transition hover:bg-stone-50 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                                >
                                    Preview Struk
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!preOrders.length"
                            class="px-6 py-12 text-center"
                        >
                            <p
                                class="text-sm font-black text-black dark:text-white"
                            >
                                Belum ada pre-order aktif.
                            </p>
                            <p
                                class="mt-2 text-xs font-bold leading-5 text-black dark:text-slate-400"
                            >
                                Gunakan tombol "Buat Pre-Order / DP" untuk
                                menyimpan order pickup berikut down payment.
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <section
                class="rounded-3xl border-2 border-stone-200 bg-stone-50 dark:border-white/10 dark:bg-slate-900/80"
            >
                <div
                    class="flex items-center justify-between gap-4 border-b-2 border-stone-200 px-6 py-5 dark:border-white/10"
                >
                    <div>
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-700 dark:text-orange-400"
                        >
                            Struk & Histori
                        </p>
                        <h3
                            class="mt-2 text-lg font-black text-black dark:text-white"
                        >
                            Riwayat Transaksi & Struk
                        </h3>
                    </div>
                    <span
                        class="rounded-full border-2 border-stone-200 bg-stone-100 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-black dark:border-white/10 dark:bg-slate-950 dark:text-slate-400"
                    >
                        {{ historyOrders.length }} transaksi
                    </span>
                </div>

                <div v-if="historyOrders.length" class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y-2 divide-black dark:divide-slate-800 text-left text-sm"
                    >
                        <thead
                            class="bg-white text-[10px] font-black uppercase tracking-[0.18em] text-black dark:bg-slate-950/70 dark:text-slate-400 border-b-2 border-stone-200 dark:border-white/10"
                        >
                            <tr>
                                <th class="px-6 py-3">Order</th>
                                <th class="px-6 py-3">Pelanggan</th>
                                <th class="px-6 py-3">Nominal</th>
                                <th class="px-6 py-3">Payment</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-black dark:divide-slate-800/80">
                            <tr
                                v-for="order in historyOrders"
                                :key="order.id"
                                class="bg-stone-50 dark:bg-slate-900/40"
                            >
                                <td class="px-6 py-4">
                                    <div
                                        class="font-black text-black dark:text-white"
                                    >
                                        {{ order.order_number }}
                                    </div>
                                    <div
                                        class="mt-1 text-xs font-bold text-black dark:text-slate-400"
                                    >
                                        {{ order.service_label }} ·
                                        {{ order.items_count }} item ·
                                        {{ formatDateTime(order.updated_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="font-bold text-black dark:text-slate-200"
                                    >
                                        {{ order.customer_name }}
                                    </div>
                                    <div
                                        class="mt-1 text-xs font-bold text-black dark:text-slate-400"
                                    >
                                        {{
                                            order.customer_phone ||
                                            order.cashier_name ||
                                            '-'
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="font-black text-black dark:text-white"
                                    >
                                        {{ formatCurrency(order.total_amount) }}
                                    </div>
                                    <div
                                        class="mt-1 text-xs font-bold text-black dark:text-slate-400"
                                    >
                                        Bayar
                                        {{ formatCurrency(order.paid_amount) }}
                                        · Sisa
                                        {{
                                            formatCurrency(
                                                order.remaining_amount,
                                            )
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="font-bold text-black dark:text-slate-200"
                                    >
                                        {{
                                            formatPaymentMethod(
                                                order.payment_method,
                                            )
                                        }}
                                    </div>
                                    <div
                                        class="mt-1 text-xs font-bold text-black dark:text-slate-400"
                                    >
                                        {{ order.payment_status || 'n/a' }} ·
                                        {{ order.payment_logs_count }} log
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                        :class="
                                            historyStatusClass(order.status)
                                        "
                                    >
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            @click="openReceipt(order.id)"
                                            class="rounded-xl border-2 border-stone-200 bg-stone-100 px-3 py-2 text-[11px] font-black text-black transition hover:bg-stone-200 dark:border-white/10 dark:bg-slate-950 dark:text-slate-200"
                                        >
                                            Preview Struk
                                        </button>
                                        <button
                                            v-if="order.has_kasbon"
                                            type="button"
                                            @click="
                                                openInstallmentModal(
                                                    kasbonOrders.find(
                                                        (entry) =>
                                                            entry.id ===
                                                            order.id,
                                                    ) || {
                                                        id: order.id,
                                                        order_number:
                                                            order.order_number,
                                                        customer_name:
                                                            order.customer_name,
                                                        customer_phone:
                                                            order.customer_phone,
                                                        cashier_name:
                                                            order.cashier_name,
                                                        total_amount:
                                                            order.total_amount,
                                                        paid_amount:
                                                            order.paid_amount,
                                                        remaining_amount:
                                                            order.remaining_amount,
                                                        due_date: null,
                                                        closed_at:
                                                            order.updated_at,
                                                        last_payment: null,
                                                    },
                                                )
                                            "
                                            class="rounded-xl border-2 border-orange-700 bg-orange-100 px-3 py-2 text-[11px] font-black text-orange-850 transition hover:bg-orange-200 dark:border-orange-500 dark:bg-orange-500/10 dark:text-orange-300 dark:hover:bg-orange-950"
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
                    <p
                        class="text-sm font-black text-black dark:text-white"
                    >
                        Belum ada transaksi sesuai filter.
                    </p>
                    <p
                        class="mt-2 text-xs font-bold leading-5 text-black dark:text-slate-500"
                    >
                        Coba ubah rentang tanggal, metode pembayaran, atau kata
                        kunci pencarian.
                    </p>
                </div>
            </section>
        </div>

        <div
            v-if="installmentModalOpen && selectedKasbonOrder"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm dark:bg-slate-950/85"
        >
            <div
                class="w-full max-w-lg rounded-3xl border-2 border-stone-200 bg-white shadow-2xl dark:border-white/10 dark:bg-slate-900"
            >
                <div
                    class="border-b-2 border-stone-200 px-6 py-5 dark:border-white/10"
                >
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-700 dark:text-orange-400"
                    >
                        Pembayaran Cicilan
                    </p>
                    <h3
                        class="mt-2 text-xl font-black text-black dark:text-white"
                    >
                        {{ selectedKasbonOrder.order_number }} ·
                        {{ selectedKasbonOrder.customer_name }}
                    </h3>
                    <p class="mt-1 text-xs font-bold text-black dark:text-slate-400">
                        Sisa tagihan
                        {{
                            formatCurrency(selectedKasbonOrder.remaining_amount)
                        }}
                    </p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label
                            class="mb-2 block text-[10px] font-black uppercase tracking-[0.18em] text-black dark:text-slate-400"
                        >
                            Metode Pembayaran
                        </label>
                        <select
                            v-model="installmentForm.payment_method"
                            class="w-full rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
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
                        <label
                            class="mb-2 block text-[10px] font-black uppercase tracking-[0.18em] text-black dark:text-slate-400"
                        >
                            Nominal Cicilan
                        </label>
                        <input
                            v-model="installmentForm.amount"
                            type="number"
                            min="1"
                            step="1000"
                            class="w-full rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-2 block text-[10px] font-black uppercase tracking-[0.18em] text-black dark:text-slate-400"
                        >
                            Catatan
                        </label>
                        <textarea
                            v-model="installmentForm.notes"
                            rows="3"
                            class="w-full rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                        ></textarea>
                    </div>
                </div>
                <div
                    class="flex justify-end gap-3 border-t-2 border-stone-200 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        @click="closeInstallmentModal"
                        class="rounded-2xl border-2 border-stone-200 bg-stone-100 px-4 py-3 text-sm font-black text-black transition hover:bg-stone-200 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitInstallment"
                        :disabled="installmentForm.processing"
                        class="rounded-2xl border-2 border-stone-200 bg-orange-600 px-4 py-3 text-sm font-black text-white transition hover:bg-orange-700 disabled:pointer-events-none disabled:opacity-50"
                    >
                        {{
                            installmentForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan Cicilan'
                        }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="preOrderModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm dark:bg-slate-950/85"
        >
            <div
                class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border-2 border-stone-200 bg-white shadow-2xl dark:border-white/10 dark:bg-slate-900"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b-2 border-stone-200 px-6 py-5 dark:border-white/10"
                >
                    <div>
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-700 dark:text-orange-400"
                        >
                            Pre-Order Baru
                        </p>
                        <h3
                            class="mt-2 text-xl font-black text-black dark:text-white"
                        >
                            Buat Pre-Order / Down Payment
                        </h3>
                        <p
                            class="mt-1 text-xs font-bold text-black dark:text-slate-400"
                        >
                            Flow ini menyimpan order pickup/delivery future
                            dengan DP cash dan aktivasi manual saat siap dikirim
                            ke dapur.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closePreOrderModal"
                        class="font-black text-black transition hover:text-stone-700 dark:text-slate-400 dark:hover:text-white"
                    >
                        Tutup
                    </button>
                </div>

                <div
                    class="grid min-h-0 flex-1 gap-0 xl:grid-cols-[1.1fr_0.9fr]"
                >
                    <div
                        class="flex min-h-0 flex-col border-r-2 border-stone-200 dark:border-white/10"
                    >
                        <div
                            class="grid gap-3 border-b-2 border-stone-200 px-6 py-5 dark:border-white/10 sm:grid-cols-2"
                        >
                            <input
                                v-model="preOrderForm.customer_name"
                                type="text"
                                placeholder="Nama customer"
                                class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                            />
                            <input
                                v-model="preOrderForm.customer_phone"
                                type="text"
                                placeholder="Nomor HP customer"
                                class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                            />
                            <input
                                v-model="preOrderForm.customer_email"
                                type="email"
                                placeholder="Email customer (opsional)"
                                class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                            />
                            <input
                                v-model="preOrderForm.pickup_datetime"
                                type="datetime-local"
                                class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                            />
                            <input
                                v-model="preOrderForm.promo_code"
                                type="text"
                                placeholder="Voucher / promo code"
                                class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold uppercase text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100 sm:col-span-2"
                            />
                            <textarea
                                v-model="preOrderForm.notes"
                                rows="3"
                                placeholder="Catatan khusus pre-order"
                                class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100 sm:col-span-2"
                            ></textarea>
                        </div>

                        <div
                            class="border-b-2 border-stone-200 px-6 py-4 dark:border-white/10"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="activeCategory = 'all'"
                                        :class="
                                            activeCategory === 'all'
                                                ? 'border-2 border-orange-700 bg-orange-100 text-orange-850 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-300 font-black'
                                                : 'border-2 border-stone-200 bg-stone-100 text-black dark:border-white/10 dark:bg-slate-950 dark:text-slate-400 font-bold'
                                        "
                                        class="rounded-xl px-3 py-2 text-[11px] transition"
                                    >
                                        Semua Menu
                                    </button>
                                    <button
                                        v-for="category in referenceData.categories"
                                        :key="category.id"
                                        type="button"
                                        @click="activeCategory = category.id"
                                        :class="
                                            activeCategory === category.id
                                                ? 'border-2 border-orange-700 bg-orange-100 text-orange-850 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-300 font-black'
                                                : 'border-2 border-stone-200 bg-stone-100 text-black dark:border-white/10 dark:bg-slate-950 dark:text-slate-400 font-bold'
                                        "
                                        class="rounded-xl px-3 py-2 text-[11px] transition"
                                    >
                                        {{ category.name }}
                                    </button>
                                </div>
                                <input
                                    v-model="preOrderSearch"
                                    type="text"
                                    placeholder="Cari menu..."
                                    class="w-full max-w-xs rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                                />
                            </div>
                        </div>

                        <div
                            class="custom-scrollbar grid flex-1 gap-3 overflow-y-auto px-6 py-5 sm:grid-cols-2"
                        >
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                type="button"
                                @click="addProduct(product)"
                                class="rounded-2xl border-2 border-stone-200 bg-white p-4 text-left transition hover:border-orange-600 dark:border-white/10 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-sm font-bold text-black dark:text-white"
                                >
                                    {{ product.name }}
                                </p>
                                <p
                                    class="mt-2 text-xs font-bold text-black dark:text-slate-550"
                                >
                                    {{ product.category_name }}
                                </p>
                                <p
                                    class="mt-3 text-sm font-black text-orange-700 dark:text-orange-400"
                                >
                                    {{ formatCurrency(product.unit_price) }}
                                </p>
                            </button>
                        </div>
                    </div>

                    <div class="flex min-h-0 flex-col">
                        <div
                            class="border-b-2 border-stone-200 px-6 py-5 dark:border-white/10"
                        >
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-700 dark:text-orange-400"
                            >
                                Ringkasan Pre-Order
                            </p>
                            <h4
                                class="mt-2 text-lg font-black text-black dark:text-white"
                            >
                                {{ preOrderForm.items.length }} item terpilih
                            </h4>
                        </div>

                        <div
                            class="custom-scrollbar flex-1 space-y-3 overflow-y-auto px-6 py-5"
                        >
                            <div
                                v-for="(item, index) in preOrderForm.items"
                                :key="`${item.product_id}-${index}`"
                                class="rounded-2xl border-2 border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/60"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-bold text-black dark:text-white"
                                        >
                                            {{ item.product_name }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs font-bold text-black dark:text-slate-550"
                                        >
                                            {{
                                                formatCurrency(item.unit_price)
                                            }}
                                            / item
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="adjustPreOrderQty(index, -999)"
                                        class="text-xs font-black text-rose-700 dark:text-rose-400"
                                    >
                                        Hapus
                                    </button>
                                </div>
                                <div
                                    class="mt-4 flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            @click="
                                                adjustPreOrderQty(index, -1)
                                            "
                                            class="rounded-lg border-2 border-stone-200 px-2 py-1 font-black text-black bg-stone-100 dark:border-white/10 dark:text-slate-300"
                                        >
                                            -
                                        </button>
                                        <span
                                            class="text-sm font-black text-black dark:text-white"
                                            >{{ item.quantity }}</span
                                        >
                                        <button
                                            type="button"
                                            @click="adjustPreOrderQty(index, 1)"
                                            class="rounded-lg border-2 border-stone-200 px-2 py-1 font-black text-black bg-stone-100 dark:border-white/10 dark:text-slate-300"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <span
                                        class="text-sm font-black text-orange-700 dark:text-orange-400"
                                    >
                                        {{
                                            formatCurrency(
                                                item.unit_price * item.quantity,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="!preOrderForm.items.length"
                                class="rounded-2xl border-2 border-dashed border-stone-200 px-4 py-10 text-center text-sm font-bold text-black dark:border-white/10 dark:text-slate-500"
                            >
                                Pilih produk dari panel kiri untuk menambahkan
                                item pre-order.
                            </div>
                        </div>

                        <div
                            class="space-y-4 border-t-2 border-stone-200 px-6 py-5 dark:border-white/10"
                        >
                            <div class="grid gap-3 sm:grid-cols-2">
                                <select
                                    v-model="preOrderForm.down_payment_type"
                                    class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                                >
                                    <option value="percentage">
                                        DP Persentase
                                    </option>
                                    <option value="fixed">
                                        DP Nominal Tetap
                                    </option>
                                </select>
                                <input
                                    v-model="preOrderForm.down_payment_value"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="rounded-2xl border-2 border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-black focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-slate-950 dark:text-slate-100"
                                />
                            </div>

                            <div
                                class="rounded-2xl border-2 border-stone-200 bg-white p-4 text-sm dark:border-white/10 dark:bg-slate-950/60"
                            >
                                <div
                                    class="flex justify-between font-bold text-black dark:text-slate-400"
                                >
                                    <span>Subtotal</span>
                                    <span>{{
                                        formatCurrency(preOrderSubtotal)
                                    }}</span>
                                </div>
                                <div
                                    class="mt-2 flex justify-between font-bold text-black dark:text-slate-400"
                                >
                                    <span>Estimasi diskon</span>
                                    <span>{{
                                        formatCurrency(
                                            preOrderEstimatedDiscount,
                                        )
                                    }}</span>
                                </div>
                                <div
                                    class="mt-2 flex justify-between font-black text-black dark:text-white"
                                >
                                    <span>Total</span>
                                    <span class="font-black text-orange-700 dark:text-orange-400">{{
                                        formatCurrency(preOrderTotal)
                                    }}</span>
                                </div>
                                <div
                                    class="mt-4 rounded-2xl border-2 border-orange-700 bg-orange-100 dark:border-orange-500/20 dark:bg-orange-500/10 p-3"
                                >
                                    <div
                                        class="flex justify-between font-black text-orange-900 dark:text-orange-100"
                                    >
                                        <span>DP dikumpulkan</span>
                                        <span class="font-black">{{
                                            formatCurrency(preOrderDpAmount)
                                        }}</span>
                                    </div>
                                    <div
                                        class="mt-2 flex justify-between font-bold text-black dark:text-slate-350"
                                    >
                                        <span>Sisa tagihan</span>
                                        <span class="font-black">{{
                                            formatCurrency(preOrderRemaining)
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="closePreOrderModal"
                                    class="rounded-2xl border-2 border-stone-200 bg-stone-100 px-4 py-3 text-sm font-black text-black transition hover:bg-stone-200 dark:border-slate-600 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="submitPreOrder"
                                    :disabled="
                                        preOrderForm.processing ||
                                        !preOrderForm.items.length
                                    "
                                    class="rounded-2xl border-2 border-stone-200 bg-orange-600 px-4 py-3 text-sm font-black text-white transition hover:bg-orange-700 disabled:pointer-events-none disabled:opacity-50"
                                >
                                    {{
                                        preOrderForm.processing
                                            ? 'Menyimpan...'
                                            : 'Simpan Pre-Order'
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
