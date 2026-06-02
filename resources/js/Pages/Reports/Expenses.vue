<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    CalendarRange,
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

interface ExpenseRow {
    id: string;
    category: string;
    description: string;
    amount: number;
    expense_date?: string | null;
    notes?: string | null;
    outlet?: {
        id: string;
        name: string;
    } | null;
    creator_name?: string | null;
    updater_name?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
}

interface CategoryRow {
    category: string;
    current_amount: number;
    current_count: number;
    previous_amount: number;
    difference_amount: number;
    growth_percentage: number | null;
}

const page = usePage<any>();
const props = defineProps<{
    summary: {
        total_expenses: number;
        previous_total_expenses: number;
        growth_amount: number;
        growth_percentage: number | null;
        average_daily_expense: number;
        entries_count: number;
        categories_count: number;
        largest_category?: {
            name: string;
            amount: number;
        } | null;
    };
    categoryBreakdown: CategoryRow[];
    expenses: {
        data: ExpenseRow[];
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
        categories: string[];
    };
    period: {
        current: {
            start_date: string;
            end_date: string;
        };
        previous: {
            start_date: string;
            end_date: string;
        };
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
const categoryFilter = ref(props.filters.category || '');
const searchFilter = ref(props.filters.search || '');
const modalMode = ref<'create' | 'edit' | null>(null);
const selectedExpense = ref<ExpenseRow | null>(null);

const expenseForm = useForm({
    outlet_id: props.filters.outlet_id || props.referenceData.outlets[0]?.id || '',
    category: '',
    description: '',
    amount: 0,
    expense_date: props.filters.end_date,
    notes: '',
});

const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);
const isModalOpen = computed(() => modalMode.value !== null);
const modalTitle = computed(() => modalMode.value === 'edit' ? 'Edit Pengeluaran' : 'Catat Pengeluaran');

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

const categoryLabel = (value: string) => {
    return value
        .split(' ')
        .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
        .join(' ');
};

const growthLabel = (value: number | null) => {
    if (value === null) {
        return 'Belum ada basis pembanding';
    }

    const prefix = value > 0 ? '+' : '';

    return `${prefix}${value.toFixed(1)}%`;
};

const growthClass = (value: number | null) => {
    if (value === null) {
        return 'text-slate-300';
    }

    if (value > 0.001) {
        return 'text-rose-300';
    }

    if (value < -0.001) {
        return 'text-emerald-300';
    }

    return 'text-slate-300';
};

const summaryCards = computed(() => [
    {
        label: 'Total Pengeluaran',
        value: formatPrice(props.summary.total_expenses),
        helper: `${props.summary.entries_count} transaksi pada periode aktif`,
        icon: Wallet,
        tone: 'text-rose-300',
        surface: 'border-rose-400/15 bg-rose-500/10',
    },
    {
        label: 'Vs Periode Sebelumnya',
        value: formatPrice(props.summary.growth_amount),
        helper: growthLabel(props.summary.growth_percentage),
        icon: props.summary.growth_amount > 0 ? TrendingUp : TrendingDown,
        tone: props.summary.growth_amount > 0 ? 'text-amber-300' : 'text-emerald-300',
        surface: props.summary.growth_amount > 0
            ? 'border-amber-400/15 bg-amber-500/10'
            : 'border-emerald-400/15 bg-emerald-500/10',
    },
    {
        label: 'Rata-rata Harian',
        value: formatPrice(props.summary.average_daily_expense),
        helper: `${props.summary.categories_count} kategori aktif`,
        icon: BarChart3,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
    },
    {
        label: 'Kategori Terbesar',
        value: props.summary.largest_category
            ? categoryLabel(props.summary.largest_category.name)
            : '-',
        helper: props.summary.largest_category
            ? formatPrice(props.summary.largest_category.amount)
            : 'Belum ada pengeluaran',
        icon: CreditCard,
        tone: 'text-violet-300',
        surface: 'border-violet-400/15 bg-violet-500/10',
    },
]);

const submitFilters = () => {
    router.get(
        route('reports.expenses.index'),
        {
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
            outlet_id: outletFilter.value || undefined,
            category: categoryFilter.value || undefined,
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

    startDateFilter.value = currentDateInput(new Date(now.getFullYear(), now.getMonth(), 1));
    endDateFilter.value = currentDateInput(now);
    outletFilter.value = '';
    categoryFilter.value = '';
    searchFilter.value = '';
    submitFilters();
};

const resetForm = () => {
    expenseForm.clearErrors();
    expenseForm.reset();
    expenseForm.outlet_id = props.filters.outlet_id || props.referenceData.outlets[0]?.id || '';
    expenseForm.expense_date = props.filters.end_date;
    expenseForm.amount = 0;
};

const openCreateModal = () => {
    modalMode.value = 'create';
    selectedExpense.value = null;
    resetForm();
};

const openEditModal = (expense: ExpenseRow) => {
    modalMode.value = 'edit';
    selectedExpense.value = expense;
    expenseForm.clearErrors();
    expenseForm.outlet_id = expense.outlet?.id || props.filters.outlet_id || props.referenceData.outlets[0]?.id || '';
    expenseForm.category = expense.category;
    expenseForm.description = expense.description;
    expenseForm.amount = expense.amount;
    expenseForm.expense_date = expense.expense_date || props.filters.end_date;
    expenseForm.notes = expense.notes || '';
};

const closeModal = () => {
    modalMode.value = null;
    selectedExpense.value = null;
    expenseForm.clearErrors();
};

const submitExpense = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    };

    if (modalMode.value === 'edit' && selectedExpense.value) {
        expenseForm.patch(route('reports.expenses.update', selectedExpense.value.id), options);
        return;
    }

    expenseForm.post(route('reports.expenses.store'), options);
};

const deleteExpense = (expense: ExpenseRow) => {
    if (!window.confirm(`Hapus pengeluaran "${expense.description}"?`)) {
        return;
    }

    router.delete(route('reports.expenses.destroy', expense.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengeluaran Operasional" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Pengeluaran Operasional
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Catat pengeluaran outlet dan pantau perubahan biaya untuk periode
                        <span class="font-semibold text-orange-300">
                            {{ formatDate(period.current.start_date) }} - {{ formatDate(period.current.end_date) }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-300">
                        {{ user?.role || '-' }}
                    </span>
                    <Link
                        :href="route('reports.attendance-shifts.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-2.5 text-xs font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4" />
                        Laporan Absensi & Shift
                    </Link>
                    <button
                        v-if="permissions.canCreate"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl border border-orange-500/20 bg-orange-500/10 px-4 py-2.5 text-xs font-semibold text-orange-100 transition hover:border-orange-400/40 hover:bg-orange-500/15"
                        @click="openCreateModal"
                    >
                        <Plus class="h-4 w-4" />
                        Catat Pengeluaran
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tab Navigation Laporan Transaksi & Kas -->
            <div class="flex flex-wrap border-b border-slate-800 bg-slate-900/40 rounded-2xl p-1 gap-1 max-w-4xl">
                <Link
                    :href="route('reports.sales.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.sales.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Penjualan & Kas
                </Link>
                <Link
                    :href="route('reports.outlets.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.outlets.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Per Outlet
                </Link>
                <Link
                    :href="route('reports.cashiers.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.cashiers.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Per Kasir
                </Link>
                <Link
                    :href="route('reports.top-products.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.top-products.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Produk Terlaris
                </Link>
                <Link
                    :href="route('reports.expenses.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.expenses.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Pengeluaran
                </Link>
            </div>

            <div
                v-if="success"
                class="rounded-2xl border border-emerald-500/20 bg-emerald-500/12 px-4 py-3 text-sm font-medium text-emerald-300"
            >
                {{ success }}
            </div>

            <section class="grid gap-4 lg:grid-cols-[1.8fr_1fr]">
                <div class="rounded-[28px] border border-white/10 bg-slate-950/75 p-5 shadow-2xl shadow-slate-950/40">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-orange-300/85">
                                Filter Pengeluaran
                            </p>
                            <h3 class="mt-2 text-lg font-black text-white">
                                Fokus ke outlet, kategori, dan histori transaksi biaya
                            </h3>
                        </div>
                        <div class="rounded-2xl border border-orange-500/20 bg-orange-500/10 p-3 text-orange-200">
                            <Search class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Tanggal mulai</span>
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            />
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Tanggal akhir</span>
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            />
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Outlet</span>
                            <select
                                v-model="outletFilter"
                                :disabled="!canChooseOutlet"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option value="">
                                    {{ canChooseOutlet ? 'Semua outlet' : 'Outlet scope aktif' }}
                                </option>
                                <option v-for="outlet in referenceData.outlets" :key="outlet.id" :value="outlet.id">
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Kategori</span>
                            <select
                                v-model="categoryFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            >
                                <option value="">
                                    Semua kategori
                                </option>
                                <option v-for="category in referenceData.categories" :key="category" :value="category">
                                    {{ categoryLabel(category) }}
                                </option>
                            </select>
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Cari deskripsi</span>
                            <input
                                v-model="searchFilter"
                                type="text"
                                placeholder="Cari kategori / catatan"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                            />
                        </label>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400"
                            @click="submitFilters"
                        >
                            <Search class="h-4 w-4" />
                            Terapkan Filter
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                            @click="clearFilters"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/10 bg-slate-950/75 p-5 shadow-2xl shadow-slate-950/40">
                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-400">
                        Pembanding
                    </p>
                    <div class="mt-4 space-y-4 text-sm text-slate-300">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="font-semibold text-white">
                                Periode aktif
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ formatDate(period.current.start_date) }} - {{ formatDate(period.current.end_date) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <p class="font-semibold text-white">
                                Periode sebelumnya
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ formatDate(period.previous.start_date) }} - {{ formatDate(period.previous.end_date) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-amber-400/15 bg-amber-500/10 p-4 text-amber-100">
                            Supervisor bisa mencatat pengeluaran, tetapi edit dan hapus hanya tersedia untuk owner.
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-[28px] border p-5 shadow-xl shadow-slate-950/30"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-300/80">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-2xl font-black text-white">
                                {{ card.value }}
                            </p>
                            <p class="mt-2 text-sm text-slate-300/85">
                                {{ card.helper }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-3" :class="card.tone">
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="rounded-[30px] border border-white/10 bg-slate-950/80 p-5 shadow-2xl shadow-slate-950/40">
                <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-orange-300/85">
                            Breakdown Kategori
                        </p>
                        <h3 class="mt-2 text-lg font-black text-white">
                            Total pengeluaran per kategori dan delta vs periode sebelumnya
                        </h3>
                    </div>
                    <div class="rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-semibold text-slate-300">
                        {{ categoryBreakdown.length }} kategori
                    </div>
                </div>

                <div v-if="categoryBreakdown.length === 0" class="mt-6 rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-10 text-center text-sm text-slate-400">
                    Belum ada pengeluaran pada periode yang dipilih.
                </div>

                <div v-else class="mt-6 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="category in categoryBreakdown"
                        :key="category.category"
                        class="rounded-[26px] border border-white/10 bg-white/[0.03] p-5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">
                                    {{ categoryLabel(category.category) }}
                                </p>
                                <p class="mt-3 text-2xl font-black text-white">
                                    {{ formatPrice(category.current_amount) }}
                                </p>
                                <p class="mt-2 text-sm text-slate-400">
                                    {{ category.current_count }} transaksi
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-3">
                                <ReceiptText class="h-5 w-5 text-orange-200" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-sm">
                            <span class="text-slate-400">Sebelumnya</span>
                            <span class="font-semibold text-slate-200">{{ formatPrice(category.previous_amount) }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm">
                            <span class="text-slate-400">Delta nominal</span>
                            <span class="font-semibold" :class="growthClass(category.growth_percentage)">
                                {{ formatPrice(category.difference_amount) }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-sm">
                            <span class="text-slate-400">Growth</span>
                            <span class="font-semibold" :class="growthClass(category.growth_percentage)">
                                {{ growthLabel(category.growth_percentage) }}
                            </span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-[30px] border border-white/10 bg-slate-950/80 p-5 shadow-2xl shadow-slate-950/40">
                <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-orange-300/85">
                            Histori Pengeluaran
                        </p>
                        <h3 class="mt-2 text-lg font-black text-white">
                            Daftar transaksi biaya operasional
                        </h3>
                    </div>
                    <div class="rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-semibold text-slate-300">
                        {{ expenses.total }} total data
                    </div>
                </div>

                <div v-if="expenses.data.length === 0" class="mt-6 rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-10 text-center text-sm text-slate-400">
                    Belum ada pengeluaran yang cocok dengan filter aktif.
                </div>

                <div v-else class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Deskripsi</th>
                                <th class="px-4 py-3">Outlet</th>
                                <th class="px-4 py-3">Nominal</th>
                                <th class="px-4 py-3">Audit</th>
                                <th class="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr
                                v-for="expense in expenses.data"
                                :key="expense.id"
                                class="align-top transition hover:bg-white/[0.03]"
                            >
                                <td class="px-4 py-4 text-slate-300">
                                    {{ formatDate(expense.expense_date) }}
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-xs font-semibold text-slate-200">
                                        {{ categoryLabel(expense.category) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-200">
                                    <p class="font-semibold text-white">
                                        {{ expense.description }}
                                    </p>
                                    <p v-if="expense.notes" class="mt-1 text-xs text-slate-400">
                                        {{ expense.notes }}
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-slate-300">
                                    {{ expense.outlet?.name || '-' }}
                                </td>
                                <td class="px-4 py-4 text-sm font-semibold text-rose-300">
                                    {{ formatPrice(expense.amount) }}
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-400">
                                    <p>Dibuat: {{ expense.creator_name || '-' }}</p>
                                    <p class="mt-1">Update: {{ expense.updater_name || expense.creator_name || '-' }}</p>
                                    <p class="mt-1 text-slate-500">{{ formatDateTime(expense.updated_at || expense.created_at) }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-if="permissions.canEdit"
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.06]"
                                            @click="openEditModal(expense)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                            Edit
                                        </button>
                                        <button
                                            v-if="permissions.canDelete"
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-xs font-semibold text-rose-200 transition hover:border-rose-400/40 hover:bg-rose-500/15"
                                            @click="deleteExpense(expense)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="expenses.links?.length > 3"
                    class="mt-5 flex flex-wrap items-center gap-2"
                >
                    <Link
                        v-for="link in expenses.links"
                        :key="`${link.label}-${link.url}`"
                        :href="link.url || '#'"
                        class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                        :class="link.active
                            ? 'border-orange-400/40 bg-orange-500/15 text-orange-100'
                            : link.url
                                ? 'border-white/10 bg-white/[0.03] text-slate-300 hover:border-white/20 hover:bg-white/[0.05]'
                                : 'cursor-not-allowed border-white/5 bg-white/[0.02] text-slate-600'"
                        v-html="link.label"
                    />
                </div>
            </section>
        </div>

        <teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 px-4 py-8 backdrop-blur-sm"
            >
                <div class="w-full max-w-2xl rounded-[30px] border border-white/10 bg-slate-950 p-6 shadow-2xl shadow-black/40">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-orange-300/85">
                                Form Pengeluaran
                            </p>
                            <h3 class="mt-2 text-xl font-black text-white">
                                {{ modalTitle }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-2 text-slate-300 transition hover:border-white/20 hover:bg-white/[0.06]"
                            @click="closeModal"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Outlet</span>
                            <select
                                v-model="expenseForm.outlet_id"
                                :disabled="!canChooseOutlet"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option
                                    v-for="outlet in referenceData.outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                            <p v-if="expenseForm.errors.outlet_id" class="text-xs text-rose-300">
                                {{ expenseForm.errors.outlet_id }}
                            </p>
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Tanggal</span>
                            <input
                                v-model="expenseForm.expense_date"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            />
                            <p v-if="expenseForm.errors.expense_date" class="text-xs text-rose-300">
                                {{ expenseForm.errors.expense_date }}
                            </p>
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Kategori</span>
                            <input
                                v-model="expenseForm.category"
                                type="text"
                                list="expense-categories"
                                placeholder="contoh: operasional"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                            />
                            <p v-if="expenseForm.errors.category" class="text-xs text-rose-300">
                                {{ expenseForm.errors.category }}
                            </p>
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Nominal</span>
                            <input
                                v-model="expenseForm.amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            />
                            <p v-if="expenseForm.errors.amount" class="text-xs text-rose-300">
                                {{ expenseForm.errors.amount }}
                            </p>
                        </label>
                    </div>

                    <div class="mt-4 grid gap-4">
                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Deskripsi</span>
                            <input
                                v-model="expenseForm.description"
                                type="text"
                                placeholder="Contoh: Beli gas LPG"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                            />
                            <p v-if="expenseForm.errors.description" class="text-xs text-rose-300">
                                {{ expenseForm.errors.description }}
                            </p>
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Catatan</span>
                            <textarea
                                v-model="expenseForm.notes"
                                rows="4"
                                placeholder="Catatan tambahan jika diperlukan"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                            />
                            <p v-if="expenseForm.errors.notes" class="text-xs text-rose-300">
                                {{ expenseForm.errors.notes }}
                            </p>
                        </label>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                            @click="closeModal"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="expenseForm.processing"
                            @click="submitExpense"
                        >
                            <Plus class="h-4 w-4" />
                            {{ modalMode === 'edit' ? 'Simpan Perubahan' : 'Simpan Pengeluaran' }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        <datalist id="expense-categories">
            <option v-for="category in referenceData.categories" :key="category" :value="category" />
        </datalist>
    </AuthenticatedLayout>
</template>
