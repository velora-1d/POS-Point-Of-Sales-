<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    CalendarRange,
    Coins,
    CreditCard,
    Pencil,
    Plus,
    ReceiptText,
    Search,
    Trash2,
    TrendingDown,
    TrendingUp,
    Wallet,
    X,
    Save,
    CheckCircle2,
} from '@lucide/vue';
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

interface FinancialItemRow {
    id: string;
    category: string;
    description: string;
    amount: number;
    expense_date?: string | null;
    income_date?: string | null;
    notes?: string | null;
    outlet?: {
        id: string;
        name: string;
    } | null;
    creator_name?: string | null;
    updater_name?: string | null;
    created_at?: string | null;
}

const page = usePage<any>();
const props = defineProps<{
    summary: {
        total_sales: number;
        total_other_incomes: number;
        total_expenses: number;
        net_income: number;
    };
    expenses: {
        data: FinancialItemRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    incomes: {
        data: FinancialItemRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        start_date: string;
        end_date: string;
        outlet_id?: string;
        category?: string;
        search?: string;
        per_page?: number;
    };
    referenceData: {
        outlets: OutletOption[];
        expenseCategories: string[];
        incomeCategories: string[];
    };
    permissions: {
        canCreate: boolean;
        canEdit: boolean;
        canDelete: boolean;
    };
    success?: string | null;
}>();

const user = computed(() => page.props.auth.user);
const startDateFilter = ref(props.filters.start_date);
const endDateFilter = ref(props.filters.end_date);
const outletFilter = ref(props.filters.outlet_id || '');
const searchFilter = ref(props.filters.search || '');

// Tab state: 'expense' atau 'income'
const activeTab = ref<'expense' | 'income'>('expense');

// Modal form states
const expenseModalMode = ref<'create' | 'edit' | null>(null);
const selectedExpense = ref<FinancialItemRow | null>(null);

const incomeModalMode = ref<'create' | 'edit' | null>(null);
const selectedIncome = ref<FinancialItemRow | null>(null);

// Forms
const expenseForm = useForm({
    outlet_id: props.filters.outlet_id || props.referenceData.outlets[0]?.id || '',
    category: '',
    description: '',
    amount: 0,
    expense_date: props.filters.end_date,
    notes: '',
});

const incomeForm = useForm({
    outlet_id: props.filters.outlet_id || props.referenceData.outlets[0]?.id || '',
    category: '',
    description: '',
    amount: 0,
    income_date: props.filters.end_date,
    notes: '',
});

const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);

const selectedOutletLabel = computed(() => {
    if (!outletFilter.value) {
        return 'Semua outlet';
    }
    return props.referenceData.outlets.find((outlet) => outlet.id === outletFilter.value)?.name || 'Outlet aktif';
});

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

const submitFilters = () => {
    router.get(
        route('reports.expenses.index'),
        {
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
            outlet_id: outletFilter.value || undefined,
            search: searchFilter.value || undefined,
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
    startDateFilter.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
    endDateFilter.value = now.toISOString().slice(0, 10);
    outletFilter.value = '';
    searchFilter.value = '';
    submitFilters();
};

// --- Logika Pengeluaran (Expense CRUD) ---
const openCreateExpense = () => {
    expenseForm.reset();
    expenseForm.outlet_id = outletFilter.value || props.referenceData.outlets[0]?.id || '';
    expenseForm.expense_date = endDateFilter.value;
    expenseModalMode.value = 'create';
};

const openEditExpense = (expense: FinancialItemRow) => {
    selectedExpense.value = expense;
    expenseForm.outlet_id = expense.outlet?.id || props.referenceData.outlets[0]?.id || '';
    expenseForm.category = expense.category;
    expenseForm.description = expense.description;
    expenseForm.amount = expense.amount;
    expenseForm.expense_date = expense.expense_date || '';
    expenseForm.notes = expense.notes || '';
    expenseModalMode.value = 'edit';
};

const submitExpense = () => {
    if (expenseModalMode.value === 'create') {
        expenseForm.post(route('reports.expenses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                expenseModalMode.value = null;
                expenseForm.reset();
            },
        });
    } else if (expenseModalMode.value === 'edit' && selectedExpense.value) {
        expenseForm.patch(route('reports.expenses.update', selectedExpense.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                expenseModalMode.value = null;
                selectedExpense.value = null;
                expenseForm.reset();
            },
        });
    }
};

const deleteExpense = (expense: FinancialItemRow) => {
    if (confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')) {
        router.delete(route('reports.expenses.destroy', expense.id), {
            preserveScroll: true,
        });
    }
};

// --- Logika Pemasukan (Income CRUD) ---
const openCreateIncome = () => {
    incomeForm.reset();
    incomeForm.outlet_id = outletFilter.value || props.referenceData.outlets[0]?.id || '';
    incomeForm.income_date = endDateFilter.value;
    incomeModalMode.value = 'create';
};

const openEditIncome = (income: FinancialItemRow) => {
    selectedIncome.value = income;
    incomeForm.outlet_id = income.outlet?.id || props.referenceData.outlets[0]?.id || '';
    incomeForm.category = income.category;
    incomeForm.description = income.description;
    incomeForm.amount = income.amount;
    incomeForm.income_date = income.income_date || '';
    incomeForm.notes = income.notes || '';
    incomeModalMode.value = 'edit';
};

const submitIncome = () => {
    if (incomeModalMode.value === 'create') {
        incomeForm.post(route('reports.incomes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                incomeModalMode.value = null;
                incomeForm.reset();
            },
        });
    } else if (incomeModalMode.value === 'edit' && selectedIncome.value) {
        incomeForm.patch(route('reports.incomes.update', selectedIncome.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                incomeModalMode.value = null;
                selectedIncome.value = null;
                incomeForm.reset();
            },
        });
    }
};

const deleteIncome = (income: FinancialItemRow) => {
    if (confirm('Apakah Anda yakin ingin menghapus pemasukan ini?')) {
        router.delete(route('reports.incomes.destroy', income.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Keuangan & Arus Kas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-stone-900 dark:text-white flex items-center gap-2">
                        <Coins class="h-6 w-6 text-orange-400" />
                        Keuangan & Arus Kas
                    </h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                        Pantau ringkasan pemasukan penjualan, pemasukan operasional lain, dan pengeluaran.
                        <span class="font-semibold text-orange-300">{{ selectedOutletLabel }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-1.5 rounded-2xl border border-stone-200 dark:border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-bold text-stone-800 dark:text-slate-200 transition hover:border-stone-200 dark:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4 text-orange-400" />
                        Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Navigasi Menu Laporan Keuangan (Horizontal Tabs) -->
            <div class="flex flex-wrap border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl p-1 gap-1 max-w-4xl">
                <Link
                    :href="route('reports.sales.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150 text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]"
                >
                    Penjualan & Kas
                </Link>
                <Link
                    :href="route('reports.outlets.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150 text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]"
                >
                    Per Outlet
                </Link>
                <Link
                    :href="route('reports.cashiers.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150 text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]"
                >
                    Per Kasir
                </Link>
                <Link
                    :href="route('reports.top-products.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150 text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]"
                >
                    Terlaris
                </Link>
                <Link
                    :href="route('reports.expenses.index')"
                    class="flex-1 min-w-[120px] text-center py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.expenses.index') ? 'bg-orange-500 text-slate-950 shadow-lg shadow-orange-500/20' : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Keuangan
                </Link>
            </div>

            <!-- Panel Filter -->
            <section class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/40 p-5 shadow-lg">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                        <label v-if="canChooseOutlet" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500">Outlet</span>
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/60 px-3 py-3 text-xs text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua outlet</option>
                                <option v-for="outlet in referenceData.outlets" :key="outlet.id" :value="outlet.id">
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500">Dari tanggal</span>
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/60 px-3 py-3 text-xs text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500">Sampai tanggal</span>
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/60 px-3 py-3 text-xs text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500">Cari deskripsi</span>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-stone-400 dark:text-slate-500">
                                    <Search class="h-4 w-4" />
                                </span>
                                <input
                                    v-model="searchFilter"
                                    type="text"
                                    placeholder="Cari transaksi..."
                                    class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/60 py-3 pl-10 pr-3 text-xs text-stone-900 dark:text-white placeholder:text-stone-400 dark:text-slate-500 focus:border-orange-400 focus:outline-none"
                                />
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-2xl border border-stone-200 dark:border-white/10 px-4 py-3 text-xs font-semibold text-stone-800 dark:text-slate-200 transition hover:border-stone-200 dark:border-white/20 hover:bg-stone-100 dark:bg-white/5"
                            @click="clearFilters"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl bg-orange-500 px-5 py-3 text-xs font-bold text-slate-950 transition hover:bg-orange-400"
                            @click="submitFilters"
                        >
                            Filter
                        </button>
                    </div>
                </div>
            </section>

            <!-- Dashboard Keuangan Ringkasan -->
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- 1. Omzet Penjualan -->
                <article class="rounded-3xl border border-emerald-500/15 bg-white dark:bg-slate-950/40 p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Pemasukan Penjualan</span>
                            <p class="mt-2 text-2xl font-black text-emerald-300 leading-none">
                                {{ formatPrice(summary.total_sales) }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-emerald-500/10 p-2.5 text-emerald-300">
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-4 text-[10px] text-stone-400 dark:text-slate-500">
                        Otomatis dari POS (Order Lunas)
                    </p>
                </article>

                <!-- 2. Pemasukan Lainnya -->
                <article class="rounded-3xl border border-emerald-500/15 bg-white dark:bg-slate-950/40 p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Pemasukan Operasional</span>
                            <p class="mt-2 text-2xl font-black text-emerald-300 leading-none">
                                {{ formatPrice(summary.total_other_incomes) }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-emerald-500/10 p-2.5 text-emerald-300">
                            <Wallet class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-4 text-[10px] text-stone-400 dark:text-slate-500">
                        Dicatat manual oleh Owner/SPV
                    </p>
                </article>

                <!-- 3. Pengeluaran Operasional -->
                <article class="rounded-3xl border border-rose-500/15 bg-white dark:bg-slate-950/40 p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Pengeluaran Operasional</span>
                            <p class="mt-2 text-2xl font-black text-rose-300 leading-none">
                                {{ formatPrice(summary.total_expenses) }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-rose-500/10 p-2.5 text-rose-300">
                            <TrendingDown class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-4 text-[10px] text-stone-400 dark:text-slate-500">
                        Biaya belanja, gaji, sewa, dll.
                    </p>
                </article>

                <!-- 4. Keuntungan Bersih -->
                <article class="rounded-3xl border border-orange-500/20 bg-white dark:bg-slate-950/40 p-5 bg-gradient-to-br from-orange-950/15 to-transparent">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400 font-black">Keuntungan Bersih</span>
                            <p class="mt-2 text-2xl font-black text-orange-300 leading-none">
                                {{ formatPrice(summary.net_income) }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-orange-500/10 p-2.5 text-orange-400">
                            <Coins class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-4 text-[10px] text-stone-400 dark:text-slate-500">
                        Omzet + Pemasukan - Pengeluaran
                    </p>
                </article>
            </section>

            <!-- Tabs: Pengeluaran vs Pemasukan Lain -->
            <section class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/40 shadow-lg">
                <div class="flex flex-col gap-3 border-b border-stone-200 dark:border-white/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Tab Buttons -->
                    <div class="flex gap-2 bg-stone-50 dark:bg-slate-900/60 p-1 rounded-xl w-fit">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition"
                            :class="activeTab === 'expense' ? 'bg-orange-500 text-slate-950' : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200'"
                            @click="activeTab = 'expense'"
                        >
                            Pengeluaran
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition"
                            :class="activeTab === 'income' ? 'bg-orange-500 text-slate-950' : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200'"
                            @click="activeTab = 'income'"
                        >
                            Pemasukan Lainnya
                        </button>
                    </div>

                    <!-- Tombol Tambah Data -->
                    <button
                        v-if="permissions.canCreate"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-orange-500 hover:bg-orange-400 px-4 py-2 text-xs font-bold text-slate-950 transition"
                        @click="activeTab === 'expense' ? openCreateExpense() : openCreateIncome()"
                    >
                        <Plus class="h-4 w-4" />
                        Catat {{ activeTab === 'expense' ? 'Pengeluaran' : 'Pemasukan' }}
                    </button>
                </div>

                <!-- Konten Tab 1: Pengeluaran -->
                <div v-show="activeTab === 'expense'">
                    <div v-if="!expenses.data.length" class="px-5 py-12 text-center text-xs text-stone-400 dark:text-slate-500">
                        Tidak ada data pengeluaran operasional.
                    </div>
                    <div v-else class="divide-y divide-white/5">
                        <article
                            v-for="row in expenses.data"
                            :key="row.id"
                            class="grid gap-3 px-5 py-4 sm:grid-cols-2 md:grid-cols-4 items-center"
                        >
                            <div>
                                <h4 class="text-xs font-black text-stone-900 dark:text-white uppercase tracking-wider">{{ row.category }}</h4>
                                <p class="text-[11px] text-stone-500 dark:text-slate-400 mt-0.5">{{ row.description }}</p>
                                <p class="text-[10px] text-stone-400 dark:text-slate-500 mt-1">{{ formatDate(row.expense_date) }}</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-stone-400 dark:text-slate-500 uppercase">Outlet</span>
                                <p class="text-xs text-stone-600 dark:text-slate-300 font-semibold">{{ row.outlet?.name || '-' }}</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-stone-400 dark:text-slate-500 uppercase">Nominal</span>
                                <p class="text-xs text-rose-300 font-black">{{ formatPrice(row.amount) }}</p>
                            </div>
                            <div class="flex items-center sm:justify-end gap-2">
                                <button
                                    v-if="permissions.canEdit"
                                    type="button"
                                    class="rounded-lg border border-stone-200 dark:border-white/10 p-1.5 text-stone-500 dark:text-slate-400 transition hover:border-stone-200 dark:border-white/20 hover:text-stone-900 dark:text-white"
                                    @click="openEditExpense(row)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    v-if="permissions.canDelete"
                                    type="button"
                                    class="rounded-lg border border-rose-500/20 p-1.5 text-rose-400 transition hover:border-rose-500/40 hover:bg-rose-500/5"
                                    @click="deleteExpense(row)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </article>
                    </div>

                    <!-- Pagination -->
                    <div v-if="expenses.links.length > 3" class="flex items-center justify-between gap-3 border-t border-stone-200 dark:border-white/5 px-5 py-3">
                        <p class="text-[10px] text-stone-400 dark:text-slate-500">Menampilkan {{ expenses.from }} - {{ expenses.to }} dari {{ expenses.total }} pengeluaran.</p>
                        <div class="flex items-center gap-1">
                            <component
                                :is="link.url ? Link : 'span'"
                                v-for="link in expenses.links"
                                :key="link.label"
                                :href="link.url || undefined"
                                class="rounded-lg border px-2.5 py-1 text-[10px] font-semibold transition"
                                :class="link.active ? 'border-orange-400 bg-orange-500/10 text-orange-300' : link.url ? 'border-stone-200 dark:border-white/10 text-stone-600 dark:text-slate-300 hover:bg-white/[0.02]' : 'border-stone-200 dark:border-white/5 text-slate-600'"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>

                <!-- Konten Tab 2: Pemasukan Lainnya -->
                <div v-show="activeTab === 'income'">
                    <div v-if="!incomes.data.length" class="px-5 py-12 text-center text-xs text-stone-400 dark:text-slate-500">
                        Tidak ada data pemasukan operasional lainnya.
                    </div>
                    <div v-else class="divide-y divide-white/5">
                        <article
                            v-for="row in incomes.data"
                            :key="row.id"
                            class="grid gap-3 px-5 py-4 sm:grid-cols-2 md:grid-cols-4 items-center"
                        >
                            <div>
                                <h4 class="text-xs font-black text-stone-900 dark:text-white uppercase tracking-wider">{{ row.category }}</h4>
                                <p class="text-[11px] text-stone-500 dark:text-slate-400 mt-0.5">{{ row.description }}</p>
                                <p class="text-[10px] text-stone-400 dark:text-slate-500 mt-1">{{ formatDate(row.income_date) }}</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-stone-400 dark:text-slate-500 uppercase">Outlet</span>
                                <p class="text-xs text-stone-600 dark:text-slate-300 font-semibold">{{ row.outlet?.name || '-' }}</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-stone-400 dark:text-slate-500 uppercase">Nominal</span>
                                <p class="text-xs text-emerald-300 font-black">{{ formatPrice(row.amount) }}</p>
                            </div>
                            <div class="flex items-center sm:justify-end gap-2">
                                <button
                                    v-if="permissions.canEdit"
                                    type="button"
                                    class="rounded-lg border border-stone-200 dark:border-white/10 p-1.5 text-stone-500 dark:text-slate-400 transition hover:border-stone-200 dark:border-white/20 hover:text-stone-900 dark:text-white"
                                    @click="openEditIncome(row)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    v-if="permissions.canDelete"
                                    type="button"
                                    class="rounded-lg border border-rose-500/20 p-1.5 text-rose-400 transition hover:border-rose-500/40 hover:bg-rose-500/5"
                                    @click="deleteIncome(row)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </article>
                    </div>

                    <!-- Pagination -->
                    <div v-if="incomes.links.length > 3" class="flex items-center justify-between gap-3 border-t border-stone-200 dark:border-white/5 px-5 py-3">
                        <p class="text-[10px] text-stone-400 dark:text-slate-500">Menampilkan {{ incomes.from }} - {{ incomes.to }} dari {{ incomes.total }} pemasukan.</p>
                        <div class="flex items-center gap-1">
                            <component
                                :is="link.url ? Link : 'span'"
                                v-for="link in incomes.links"
                                :key="link.label"
                                :href="link.url || undefined"
                                class="rounded-lg border px-2.5 py-1 text-[10px] font-semibold transition"
                                :class="link.active ? 'border-orange-400 bg-orange-500/10 text-orange-300' : link.url ? 'border-stone-200 dark:border-white/10 text-stone-600 dark:text-slate-300 hover:bg-white/[0.02]' : 'border-stone-200 dark:border-white/5 text-slate-600'"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Modal Form: Pengeluaran -->
        <div v-if="expenseModalMode" class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-slate-950/80 px-4 py-6 backdrop-blur-sm">
            <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl border border-stone-200 dark:border-white/10 bg-stone-100 dark:bg-slate-950 p-6 shadow-2xl">
                <div class="flex items-start justify-between border-b border-stone-200 dark:border-white/10 pb-4">
                    <div>
                        <h3 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-1.5">
                            <CreditCard class="h-5 w-5 text-orange-400" />
                            {{ expenseModalMode === 'edit' ? 'Edit Pengeluaran' : 'Catat Pengeluaran' }}
                        </h3>
                    </div>
                    <button type="button" class="text-stone-500 dark:text-slate-400 hover:text-stone-900 dark:text-white" @click="expenseModalMode = null">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitExpense" class="mt-4 space-y-4">
                    <label v-if="canChooseOutlet" class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Outlet</span>
                        <select v-model="expenseForm.outlet_id" class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white" required>
                            <option v-for="outlet in referenceData.outlets" :key="outlet.id" :value="outlet.id">
                                {{ outlet.name }}
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Kategori</span>
                        <input
                            v-model="expenseForm.category"
                            type="text"
                            list="expense-categories"
                            placeholder="Belanja, Gaji, dll."
                            class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white placeholder:text-slate-600 focus:outline-none"
                            required
                        />
                        <datalist id="expense-categories">
                            <option v-for="cat in referenceData.expenseCategories" :key="cat" :value="cat" />
                        </datalist>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Deskripsi</span>
                        <input
                            v-model="expenseForm.description"
                            type="text"
                            placeholder="Beli bahan baku Mentai"
                            class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white placeholder:text-slate-600 focus:outline-none"
                            required
                        />
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Nominal (Rp)</span>
                            <input
                                v-model.number="expenseForm.amount"
                                type="number"
                                min="0.01"
                                step="any"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white focus:outline-none"
                                required
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Tanggal</span>
                            <input
                                v-model="expenseForm.expense_date"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white focus:outline-none"
                                required
                            />
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Catatan Lain</span>
                        <textarea
                            v-model="expenseForm.notes"
                            rows="2"
                            class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white focus:outline-none"
                        ></textarea>
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-stone-200 dark:border-white/5">
                        <button type="button" class="rounded-xl border border-stone-200 dark:border-white/10 px-4 py-2 text-xs text-stone-600 dark:text-slate-300 hover:bg-stone-100 dark:bg-white/5" @click="expenseModalMode = null">
                            <X class="h-3.5 w-3.5 inline mr-1" />
                            Batal
                        </button>
                        <button type="submit" class="rounded-xl bg-orange-500 hover:bg-orange-400 px-4 py-2 text-xs font-bold text-slate-950" :disabled="expenseForm.processing">
                            <Save class="h-3.5 w-3.5 inline mr-1" />
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Form: Pemasukan -->
        <div v-if="incomeModalMode" class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-slate-950/80 px-4 py-6 backdrop-blur-sm">
            <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl border border-stone-200 dark:border-white/10 bg-stone-100 dark:bg-slate-950 p-6 shadow-2xl">
                <div class="flex items-start justify-between border-b border-stone-200 dark:border-white/10 pb-4">
                    <div>
                        <h3 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-1.5">
                            <Wallet class="h-5 w-5 text-orange-400" />
                            {{ incomeModalMode === 'edit' ? 'Edit Pemasukan' : 'Catat Pemasukan' }}
                        </h3>
                    </div>
                    <button type="button" class="text-stone-500 dark:text-slate-400 hover:text-stone-900 dark:text-white" @click="incomeModalMode = null">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitIncome" class="mt-4 space-y-4">
                    <label v-if="canChooseOutlet" class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Outlet</span>
                        <select v-model="incomeForm.outlet_id" class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white" required>
                            <option v-for="outlet in referenceData.outlets" :key="outlet.id" :value="outlet.id">
                                {{ outlet.name }}
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Kategori</span>
                        <input
                            v-model="incomeForm.category"
                            type="text"
                            list="income-categories"
                            placeholder="Suntikan Modal, Penjualan Aset, dll."
                            class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white placeholder:text-slate-600 focus:outline-none"
                            required
                        />
                        <datalist id="income-categories">
                            <option v-for="cat in referenceData.incomeCategories" :key="cat" :value="cat" />
                        </datalist>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Deskripsi</span>
                        <input
                            v-model="incomeForm.description"
                            type="text"
                            placeholder="Suntikan modal owner untuk outlet"
                            class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white placeholder:text-slate-600 focus:outline-none"
                            required
                        />
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Nominal (Rp)</span>
                            <input
                                v-model.number="incomeForm.amount"
                                type="number"
                                min="0.01"
                                step="any"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white focus:outline-none"
                                required
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Tanggal</span>
                            <input
                                v-model="incomeForm.income_date"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white focus:outline-none"
                                required
                            />
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Catatan Lain</span>
                        <textarea
                            v-model="incomeForm.notes"
                            rows="2"
                            class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-xs text-stone-900 dark:text-white focus:outline-none"
                        ></textarea>
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-stone-200 dark:border-white/5">
                        <button type="button" class="rounded-xl border border-stone-200 dark:border-white/10 px-4 py-2 text-xs text-stone-600 dark:text-slate-300 hover:bg-stone-100 dark:bg-white/5" @click="incomeModalMode = null">
                            <X class="h-3.5 w-3.5 inline mr-1" />
                            Batal
                        </button>
                        <button type="submit" class="rounded-xl bg-orange-500 hover:bg-orange-400 px-4 py-2 text-xs font-bold text-slate-950" :disabled="incomeForm.processing">
                            <Save class="h-3.5 w-3.5 inline mr-1" />
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
