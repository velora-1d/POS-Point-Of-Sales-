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
    return props.reportTypes.find((item) => item.value === reportType.value) || props.reportTypes[0];
});

const activeFormatNote = computed(() => {
    return format.value === 'excel' ? props.notes.excel : props.notes.pdf;
});

const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);

const hasFilter = (filterKey: string) => activeReport.value?.filters.includes(filterKey) ?? false;

const buildDownloadUrl = () => {
    const query = new URLSearchParams();

    query.set('report_type', reportType.value);
    query.set('format', format.value);
    query.set('start_date', startDate.value);
    query.set('end_date', endDate.value);

    if (outletId.value) query.set('outlet_id', outletId.value);
    if (userId.value && hasFilter('user_id')) query.set('user_id', userId.value);
    if (categoryId.value && hasFilter('category_id')) query.set('category_id', categoryId.value);
    if (expenseCategory.value && hasFilter('category')) query.set('category', expenseCategory.value);
    if (source.value && hasFilter('source')) query.set('source', source.value);
    if (paymentMethod.value && hasFilter('payment_method')) query.set('payment_method', paymentMethod.value);
    if (inventoryType.value && hasFilter('type')) query.set('type', inventoryType.value);
    if (inventoryStatus.value && hasFilter('status')) query.set('status', inventoryStatus.value);
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
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Export PDF & Excel
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Export report aktif `#46-#52` ke PDF native backend atau CSV yang kompatibel dibuka di Excel.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-300">
                        {{ user?.role || '-' }}
                    </span>
                    <Link
                        :href="route('reports.expenses.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-2.5 text-xs font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4" />
                        Laporan Aktif
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="grid gap-4 lg:grid-cols-[1.8fr_1fr]">
                <div class="rounded-[28px] border border-white/10 bg-slate-950/75 p-5 shadow-2xl shadow-slate-950/40">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-orange-300/85">
                                Konfigurasi Export
                            </p>
                            <h3 class="mt-2 text-lg font-black text-white">
                                Pilih report, format, dan filter yang ingin diunduh
                            </h3>
                        </div>
                        <div class="rounded-2xl border border-orange-500/20 bg-orange-500/10 p-3 text-orange-200">
                            <Download class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Jenis Report</span>
                            <select
                                v-model="reportType"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
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

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Format</span>
                            <select
                                v-model="format"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
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

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Tanggal mulai</span>
                            <input
                                v-model="startDate"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            />
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Tanggal akhir</span>
                            <input
                                v-model="endDate"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            />
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Outlet</span>
                            <select
                                v-model="outletId"
                                :disabled="!canChooseOutlet"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40 disabled:cursor-not-allowed disabled:opacity-60"
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

                        <label
                            v-if="hasFilter('user_id')"
                            class="space-y-2 text-xs font-semibold text-slate-300"
                        >
                            <span>Karyawan / Kasir</span>
                            <select
                                v-model="userId"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            >
                                <option value="">
                                    Semua
                                </option>
                                <option
                                    v-for="employee in referenceData.employees"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }}
                                </option>
                            </select>
                        </label>

                        <label
                            v-if="hasFilter('category_id')"
                            class="space-y-2 text-xs font-semibold text-slate-300"
                        >
                            <span>Kategori Produk</span>
                            <select
                                v-model="categoryId"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            >
                                <option value="">
                                    Semua kategori
                                </option>
                                <option
                                    v-for="category in referenceData.productCategories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </label>

                        <label
                            v-if="hasFilter('category')"
                            class="space-y-2 text-xs font-semibold text-slate-300"
                        >
                            <span>Kategori Pengeluaran</span>
                            <input
                                v-model="expenseCategory"
                                type="text"
                                list="expense-categories"
                                placeholder="contoh: operasional"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                            />
                        </label>

                        <label
                            v-if="hasFilter('source')"
                            class="space-y-2 text-xs font-semibold text-slate-300"
                        >
                            <span>Source Penjualan</span>
                            <select
                                v-model="source"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            >
                                <option value="">
                                    Semua source
                                </option>
                                <option
                                    v-for="item in referenceData.sources"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>

                        <label
                            v-if="hasFilter('payment_method')"
                            class="space-y-2 text-xs font-semibold text-slate-300"
                        >
                            <span>Metode Bayar</span>
                            <select
                                v-model="paymentMethod"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
                            >
                                <option value="">
                                    Semua metode
                                </option>
                                <option
                                    v-for="item in referenceData.paymentMethods"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>

                        <label
                            v-if="hasFilter('type')"
                            class="space-y-2 text-xs font-semibold text-slate-300"
                        >
                            <span>Tipe Inventori</span>
                            <select
                                v-model="inventoryType"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
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

                        <label
                            v-if="hasFilter('status')"
                            class="space-y-2 text-xs font-semibold text-slate-300"
                        >
                            <span>Status Inventori</span>
                            <select
                                v-model="inventoryStatus"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40"
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
                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Pencarian</span>
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cari order, catatan, atau deskripsi"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900/80 py-3 pl-11 pr-4 text-sm text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                                />
                            </div>
                        </label>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400"
                            @click="downloadReport"
                        >
                            <Download class="h-4 w-4" />
                            Download Export
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-[28px] border border-violet-400/15 bg-violet-500/10 p-5 shadow-lg shadow-violet-950/20">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl border border-violet-300/20 bg-violet-500/10 p-3 text-violet-100">
                                <FileText v-if="format === 'pdf'" class="h-5 w-5" />
                                <FileSpreadsheet v-else class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-violet-200/85">
                                    Catatan Format
                                </p>
                                <p class="mt-2 text-sm leading-6 text-violet-50/90">
                                    {{ activeFormatNote }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-amber-400/15 bg-amber-500/10 p-5 shadow-lg shadow-amber-950/20">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl border border-amber-300/20 bg-amber-500/10 p-3 text-amber-100">
                                <Printer class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-amber-200/85">
                                    Scope Export
                                </p>
                                <p class="mt-2 text-sm leading-6 text-amber-50/90">
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
