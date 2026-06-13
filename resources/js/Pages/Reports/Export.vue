<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    CalendarRange,
    Download,
    FileSpreadsheet,
    FileText,
    Printer,
    Search,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface OutletOption {
    id: string;
    name: string;
}

interface EmployeeOption {
    id: string;
    name: string;
    role?: string | null;
}

interface CategoryOption {
    id: string;
    name: string;
}

interface FilterOption {
    value: string;
    label: string;
}

interface ReportType {
    value: string;
    label: string;
    filters: string[];
}

const page = usePage<any>();
const props = defineProps<{
    defaults: {
        report_type: string;
        format: string;
        start_date: string;
        end_date: string;
        outlet_id?: string;
        user_id?: string;
        category_id?: string;
        category?: string;
        source?: string;
        payment_method?: string;
        type?: string;
        status?: string;
        search?: string;
    };
    referenceData: {
        outlets: OutletOption[];
        employees: EmployeeOption[];
        productCategories: CategoryOption[];
        expenseCategories: string[];
        sources: FilterOption[];
        paymentMethods: FilterOption[];
        inventoryTypes: FilterOption[];
        inventoryStatuses: FilterOption[];
    };
    reportTypes: ReportType[];
    formats: FilterOption[];
    notes: {
        excel: string;
        pdf: string;
    };
}>();

const user = computed(() => page.props.auth.user);
const reportType = ref(props.defaults.report_type);
const format = ref(props.defaults.format);
const startDate = ref(props.defaults.start_date);
const endDate = ref(props.defaults.end_date);
const outletId = ref(props.defaults.outlet_id || '');
const userId = ref(props.defaults.user_id || '');
const categoryId = ref(props.defaults.category_id || '');
const expenseCategory = ref(props.defaults.category || '');
const source = ref(props.defaults.source || '');
const paymentMethod = ref(props.defaults.payment_method || '');
const inventoryType = ref(props.defaults.type || 'all');
const inventoryStatus = ref(props.defaults.status || 'all');
const search = ref(props.defaults.search || '');

const activeReport = computed(() => {
    return (
        props.reportTypes.find((item) => item.value === reportType.value) ||
        props.reportTypes[0]
    );
});

const activeFormatNote = computed(() => {
    return format.value === 'excel' ? props.notes.excel : props.notes.pdf;
});

const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);

const hasFilter = (filterKey: string) =>
    activeReport.value?.filters.includes(filterKey) ?? false;

const buildDownloadUrl = () => {
    const query = new URLSearchParams();

    query.set('report_type', reportType.value);
    query.set('format', format.value);
    query.set('start_date', startDate.value);
    query.set('end_date', endDate.value);

    if (outletId.value) query.set('outlet_id', outletId.value);
    if (userId.value && hasFilter('user_id'))
        query.set('user_id', userId.value);
    if (categoryId.value && hasFilter('category_id'))
        query.set('category_id', categoryId.value);
    if (expenseCategory.value && hasFilter('category'))
        query.set('category', expenseCategory.value);
    if (source.value && hasFilter('source')) query.set('source', source.value);
    if (paymentMethod.value && hasFilter('payment_method'))
        query.set('payment_method', paymentMethod.value);
    if (inventoryType.value && hasFilter('type'))
        query.set('type', inventoryType.value);
    if (inventoryStatus.value && hasFilter('status'))
        query.set('status', inventoryStatus.value);
    if (search.value && hasFilter('search')) query.set('search', search.value);

    return `${route('reports.exports.download')}?${query.toString()}`;
};

const downloadReport = () => {
    window.open(buildDownloadUrl(), '_blank');
};

const resetOptionalFilters = () => {
    userId.value = '';
    categoryId.value = '';
    expenseCategory.value = '';
    source.value = '';
    paymentMethod.value = '';
    inventoryType.value = 'all';
    inventoryStatus.value = 'all';
    search.value = '';
};

const onReportTypeChange = () => {
    resetOptionalFilters();
};
</script>

<template>
    <Head title="Export PDF & Excel" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"
            >
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Export PDF & Excel
                    </h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400 font-semibold">
                        Export report aktif `#46-#52` ke PDF native backend atau CSV yang kompatibel dibuka di Excel.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span
                        class="rounded-full border-2 border-stone-200 bg-stone-555/5 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-stone-600 dark:border-white/10 dark:text-slate-300"
                    >
                        {{ user?.role || '-' }}
                    </span>
                    <Link
                        :href="route('reports.expenses.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border-2 border-stone-200 bg-white/[0.03] px-4 py-2.5 text-xs font-bold text-stone-800 transition hover:border-stone-200 hover:bg-white/[0.05] dark:border-white/10 dark:text-slate-200"
                    >
                        <CalendarRange class="h-4 w-4 text-orange-500" />
                        Laporan Aktif
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

            <!-- Configuration & Format Notes Layout -->
            <section class="grid gap-6 lg:grid-cols-[1.8fr_1fr]">
                <!-- Configuration Form -->
                <div
                    class="rounded-3xl border-2 border-stone-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.24em] text-orange-600 dark:text-orange-400"
                            >
                                Konfigurasi Export
                            </p>
                            <h3
                                class="mt-1 text-sm font-black text-stone-900 dark:text-white"
                            >
                                Pilih report, format, dan filter yang ingin diunduh
                            </h3>
                        </div>
                        <div
                            class="rounded-2xl border-2 border-stone-200 bg-stone-50 p-2 text-stone-605 dark:border-white/10 dark:bg-slate-900/60 dark:text-stone-300"
                        >
                            <Download class="h-4 w-4" />
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Jenis Report</span>
                            <select
                                v-model="reportType"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                                @change="onReportTypeChange"
                            >
                                <option
                                    v-for="report in reportTypes"
                                    :key="report.value"
                                    :value="report.value"
                                >
                                    {{ report.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Format</span>
                            <select
                                v-model="format"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option
                                    v-for="item in formats"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Tanggal mulai</span>
                            <input
                                v-model="startDate"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Tanggal akhir</span>
                            <input
                                v-model="endDate"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Outlet</span>
                            <select
                                v-model="outletId"
                                :disabled="!canChooseOutlet"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option value="">
                                    {{ canChooseOutlet ? 'Semua outlet' : 'Outlet scope aktif' }}
                                </option>
                                <option
                                    v-for="outlet in referenceData.outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>

                        <label v-if="hasFilter('user_id')" class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Karyawan / Kasir</span>
                            <select
                                v-model="userId"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option value="">Semua</option>
                                <option
                                    v-for="employee in referenceData.employees"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }}
                                </option>
                            </select>
                        </label>

                        <label v-if="hasFilter('category_id')" class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Kategori Produk</span>
                            <select
                                v-model="categoryId"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option value="">Semua kategori</option>
                                <option
                                    v-for="category in referenceData.productCategories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </label>

                        <label v-if="hasFilter('category')" class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Kategori Pengeluaran</span>
                            <input
                                v-model="expenseCategory"
                                type="text"
                                list="expense-categories"
                                placeholder="contoh: operasional"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-555/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 placeholder:text-stone-400 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            />
                        </label>

                        <label v-if="hasFilter('source')" class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Source Penjualan</span>
                            <select
                                v-model="source"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option value="">Semua source</option>
                                <option
                                    v-for="item in referenceData.sources"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>

                        <label v-if="hasFilter('payment_method')" class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Metode Bayar</span>
                            <select
                                v-model="paymentMethod"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option value="">Semua metode</option>
                                <option
                                    v-for="item in referenceData.paymentMethods"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>

                        <label v-if="hasFilter('type')" class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Tipe Inventori</span>
                            <select
                                v-model="inventoryType"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option
                                    v-for="item in referenceData.inventoryTypes"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>

                        <label v-if="hasFilter('status')" class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Status Inventori</span>
                            <select
                                v-model="inventoryStatus"
                                class="w-full rounded-2xl border-2 border-stone-200 bg-stone-500/5 px-3 py-2.5 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                            >
                                <option
                                    v-for="item in referenceData.inventoryStatuses"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div v-if="hasFilter('search')" class="mt-4">
                        <label class="block">
                            <span class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Pencarian</span>
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-450 dark:text-slate-500"
                                />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cari order, catatan, atau deskripsi"
                                    class="w-full rounded-2xl border-2 border-stone-200 bg-stone-555/5 py-2.5 pl-11 pr-4 text-xs font-bold text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 placeholder:text-stone-400 dark:border-white/10 dark:bg-slate-900/60 dark:text-white"
                                />
                            </div>
                        </label>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border-2 border-transparent bg-orange-500 px-5 py-2.5 text-xs font-black uppercase tracking-wider text-stone-950 transition hover:bg-orange-400 flex items-center gap-2"
                            @click="downloadReport"
                        >
                            <Download class="h-4 w-4" />
                            Download Export
                        </button>
                    </div>
                </div>

                <!-- Alert Notes side panels -->
                <div class="space-y-4">
                    <!-- Format notes panel -->
                    <div
                        class="rounded-3xl border-2 border-violet-300 bg-violet-50 p-5 text-sm text-stone-900 shadow-xl dark:border-violet-550/20 dark:bg-violet-950/20 dark:text-violet-100"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="rounded-2xl border-2 border-violet-250 bg-violet-100/50 p-2.5 text-violet-850 dark:border-violet-500/20 dark:bg-violet-950/20 dark:text-violet-300"
                            >
                                <FileText v-if="format === 'pdf'" class="h-5 w-5" />
                                <FileSpreadsheet v-else class="h-5 w-5" />
                            </div>
                            <div class="flex-1">
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.24em] text-violet-850 dark:text-violet-400"
                                >
                                    Catatan Format
                                </p>
                                <p class="mt-2 text-stone-605 dark:text-violet-200 font-medium leading-relaxed">
                                    {{ activeFormatNote }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Scope Notes Panel -->
                    <div
                        class="rounded-3xl border-2 border-amber-300 bg-amber-50 p-5 text-sm text-stone-900 shadow-xl dark:border-amber-550/20 dark:bg-amber-950/20 dark:text-amber-100"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="rounded-2xl border-2 border-amber-250 bg-amber-100/50 p-2.5 text-amber-700 dark:border-amber-500/20 dark:bg-amber-950/20 dark:text-amber-300"
                            >
                                <Printer class="h-5 w-5" />
                            </div>
                            <div class="flex-1">
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.24em] text-amber-800 dark:text-amber-400"
                                >
                                    Scope Export
                                </p>
                                <p class="mt-2 text-stone-605 dark:text-amber-200 font-medium leading-relaxed">
                                    Menu ini mengekspor report aktif `#46-#52` saja. Report owner-only seperti per outlet tetap mengikuti hak akses dari service asalnya.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <datalist id="expense-categories">
            <option
                v-for="category in referenceData.expenseCategories"
                :key="category"
                :value="category"
            />
        </datalist>
    </AuthenticatedLayout>
</template>
