<script setup lang="ts">
import AlertDialog from '@/Components/AlertDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BarChart3,
    ChevronDown,
    LayoutDashboard,
    Pencil,
    Plus,
    Search,
    Settings,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface OutletRow {
    id: string;
    name: string;
    address?: string | null;
    phone?: string | null;
    is_active: boolean;
    settings: {
        workflow_statuses: string[];
        default_receipt_method: 'print' | 'whatsapp' | 'skip';
        bar_approval_enabled: boolean;
        customer_can_view_status: boolean;
        customer_can_edit_order: boolean;
        tax_percentage: number;
        tax_is_inclusive: boolean;
    };
    stats: {
        active_employees: number;
        total_employees: number;
        active_tables: number;
        total_tables: number;
        monthly_orders: number;
        monthly_revenue: number;
        average_ticket: number;
    };
}

interface OutletFormPayload {
    name: string;
    address: string;
    phone: string;
    workflow_statuses: string;
    default_receipt_method: 'print' | 'whatsapp' | 'skip';
    bar_approval_enabled: boolean;
    customer_can_view_status: boolean;
    customer_can_edit_order: boolean;
    tax_percentage: number;
    tax_is_inclusive: boolean;
}

const props = defineProps<{
    outlets: {
        data: OutletRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total_outlets: number;
        active_outlets: number;
        inactive_outlets: number;
        active_employees: number;
        monthly_orders: number;
        monthly_revenue: number;
    };
    comparison: {
        leaderboard: Array<{
            id: string;
            name: string;
            active_employees: number;
            monthly_orders: number;
            monthly_revenue: number;
            revenue_share: number;
        }>;
    };
    filters: {
        search?: string;
        status?: string;
        per_page?: number;
    };
    period: {
        start_date: string;
        end_date: string;
        label: string;
    };
    success?: string | null;
}>();

const alertDialog = ref({
    show: false,
    title: '',
    message: '',
    type: 'confirm' as 'info' | 'success' | 'warning' | 'danger' | 'confirm',
    onConfirm: () => {},
});

const closeAlertDialog = () => {
    alertDialog.value.show = false;
};

const defaultWorkflowStatuses = [
    'pending',
    'in_progress',
    'waiting_bar_approval',
    'ready',
    'completed',
];

const defaultWorkflowText = defaultWorkflowStatuses.join('\n');
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const modalMode = ref<'create' | 'edit' | null>(null);
const selectedOutlet = ref<OutletRow | null>(null);

const expandedOutlets = ref<Record<string, boolean>>({});
function toggleExpand(id: string) {
    expandedOutlets.value[id] = !expandedOutlets.value[id];
}
const availableStatuses = [
    {
        key: 'pending',
        label: 'Antrean (Pesanan Baru Masuk)',
        desc: 'Tahap awal saat pesanan baru diterima dari kasir atau QR order.',
    },
    {
        key: 'in_progress',
        label: 'Dapur (Sedang Dimasak)',
        desc: 'Pesanan sedang diproses/dimasak oleh staf kitchen atau bar.',
    },
    {
        key: 'waiting_bar_approval',
        label: 'Verifikasi (Persetujuan Bar)',
        desc: 'Pesanan bar membutuhkan persetujuan manual supervisor/kasir.',
    },
    {
        key: 'ready',
        label: 'Siap Saji (Siap Diantar)',
        desc: 'Makanan/minuman selesai dibuat dan siap diantar ke meja.',
    },
    {
        key: 'completed',
        label: 'Selesai',
        desc: 'Seluruh makanan/minuman sudah disajikan dan transaksi ditutup.',
    },
];

const selectedStatuses = ref<string[]>([
    'pending',
    'in_progress',
    'waiting_bar_approval',
    'ready',
    'completed',
]);

const outletForm = useForm<OutletFormPayload>({
    name: '',
    address: '',
    phone: '',
    workflow_statuses: defaultWorkflowText,
    default_receipt_method: 'print',
    bar_approval_enabled: false,
    customer_can_view_status: true,
    customer_can_edit_order: false,
    tax_percentage: 0,
    tax_is_inclusive: false,
});

const summaryCards = computed(() => [
    {
        label: 'Total Outlet',
        value: props.summary.total_outlets,
        helper: `${props.summary.active_outlets} aktif`,
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
        icon: LayoutDashboard,
    },
    {
        label: 'Outlet Nonaktif',
        value: props.summary.inactive_outlets,
        helper: 'Cabang yang sedang di-hold',
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: Settings,
    },
    {
        label: 'Karyawan Aktif',
        value: props.summary.active_employees,
        helper: 'Akun aktif lintas outlet',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Users,
    },
    {
        label: 'Revenue MTD',
        value: formatCurrency(props.summary.monthly_revenue),
        helper: `${props.summary.monthly_orders} order settled`,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: BarChart3,
    },
]);

const isModalOpen = computed(() => modalMode.value !== null);
const modalTitle = computed(() =>
    modalMode.value === 'edit' ? 'Edit Outlet' : 'Tambah Outlet',
);

function formatCurrency(value: number | string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

function formatReceiptMethod(
    value: OutletRow['settings']['default_receipt_method'],
) {
    if (value === 'whatsapp') return 'WhatsApp';
    if (value === 'skip') return 'Skip';
    return 'Print';
}

function statusBadgeClass(isActive: boolean) {
    return isActive
        ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
        : 'border-amber-400/20 bg-amber-500/10 text-amber-300';
}

function submitFilters() {
    router.get(
        route('settings.outlets.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function clearFilters() {
    search.value = '';
    status.value = '';
    submitFilters();
}

function resetOutletForm() {
    outletForm.clearErrors();
    outletForm.name = '';
    outletForm.address = '';
    outletForm.phone = '';
    outletForm.workflow_statuses = defaultWorkflowText;
    outletForm.default_receipt_method = 'print';
    outletForm.bar_approval_enabled = false;
    outletForm.customer_can_view_status = true;
    outletForm.customer_can_edit_order = false;
    outletForm.tax_percentage = 0;
    outletForm.tax_is_inclusive = false;
    selectedStatuses.value = [
        'pending',
        'in_progress',
        'waiting_bar_approval',
        'ready',
        'completed',
    ];
}

function openCreateModal() {
    modalMode.value = 'create';
    selectedOutlet.value = null;
    resetOutletForm();
}

function openEditModal(outlet: OutletRow) {
    modalMode.value = 'edit';
    selectedOutlet.value = outlet;
    outletForm.clearErrors();
    outletForm.name = outlet.name;
    outletForm.address = outlet.address || '';
    outletForm.phone = outlet.phone || '';
    outletForm.workflow_statuses = outlet.settings.workflow_statuses.join('\n');
    outletForm.default_receipt_method = outlet.settings.default_receipt_method;
    outletForm.bar_approval_enabled = outlet.settings.bar_approval_enabled;
    outletForm.customer_can_view_status =
        outlet.settings.customer_can_view_status;
    outletForm.customer_can_edit_order =
        outlet.settings.customer_can_edit_order;
    outletForm.tax_percentage = outlet.settings.tax_percentage || 0;
    outletForm.tax_is_inclusive = outlet.settings.tax_is_inclusive || false;
    selectedStatuses.value = [...outlet.settings.workflow_statuses];
}

function closeModal() {
    modalMode.value = null;
    selectedOutlet.value = null;
    resetOutletForm();
}

function submitOutlet() {
    const ordered = availableStatuses
        .map((s) => s.key)
        .filter((k) => selectedStatuses.value.includes(k));

    outletForm.workflow_statuses = ordered.join('\n');

    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (modalMode.value === 'edit' && selectedOutlet.value) {
        outletForm.patch(
            route('settings.outlets.update', selectedOutlet.value.id),
            options,
        );
        return;
    }

    outletForm.post(route('settings.outlets.store'), options);
}

function toggleOutletStatus(outlet: OutletRow) {
    const nextStatus = !outlet.is_active;

    alertDialog.value = {
        show: true,
        title: nextStatus ? 'Aktifkan Outlet' : 'Nonaktifkan Outlet',
        message: nextStatus
            ? `Aktifkan kembali outlet "${outlet.name}"?`
            : `Nonaktifkan outlet "${outlet.name}"?`,
        type: nextStatus ? 'success' : 'warning',
        onConfirm: () => {
            router.patch(
                route('settings.outlets.update-status', outlet.id),
                { is_active: nextStatus },
                {
                    preserveScroll: true,
                    onSuccess: () => closeAlertDialog(),
                    onFinish: () => closeAlertDialog(),
                },
            );
        },
    };
}
</script>

<template>
    <Head title="Manajemen Outlet & Cabang" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Manajemen Outlet & Cabang
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Kelola outlet aktif, setting operasional dasar per
                        cabang, dan lihat snapshot performa bulan berjalan dari
                        satu dashboard owner.
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
                class="rounded-[26px] border border-sky-500/15 bg-sky-500/[0.08] p-4 text-sm text-sky-100"
            >
                <p class="font-semibold">
                    Setting workflow di halaman ini disimpan per outlet untuk
                    kebutuhan operasional dan onboarding.
                </p>
                <p class="mt-1 text-xs text-sky-100/80">
                    Engine order aktif saat ini masih mengikuti status sistem
                    global yang sudah berjalan di kasir, kitchen, dan bar.
                    Konfigurasi ini dipakai sebagai baseline setting outlet.
                </p>
            </section>

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
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                {{ stat.label }}
                            </p>
                            <p :class="['mt-2 text-3xl font-black', stat.tone]">
                                {{ stat.value }}
                            </p>
                            <p
                                class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                            >
                                {{ stat.helper }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-950/40"
                        >
                            <component
                                :is="stat.icon"
                                class="h-5 w-5 text-stone-800 dark:text-slate-200"
                            />
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.3fr,0.7fr]">
                <div
                    class="rounded-[26px] border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/40"
                >
                    <div
                        class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                            >
                                Filter Outlet
                            </p>
                            <p
                                class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                            >
                                Cari nama, alamat, atau telepon outlet yang
                                ingin dicek.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-orange-400"
                            @click="openCreateModal"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Outlet
                        </button>
                    </div>

                    <div
                        class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr),180px,auto]"
                    >
                        <label class="relative block">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-slate-500"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari outlet / cabang..."
                                class="w-full rounded-2xl border border-stone-200 bg-white/[0.03] py-3 pl-10 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-orange-400/40 dark:border-white/10 dark:text-slate-500 dark:text-white"
                                @keyup.enter="submitFilters"
                            />
                        </label>

                        <select
                            v-model="status"
                            class="w-full rounded-2xl border border-stone-200 bg-white px-3 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-400/40 dark:border-white/10 dark:bg-slate-950/80 dark:text-white"
                            style="color-scheme: dark"
                            @change="submitFilters"
                        >
                            <option
                                value=""
                                class="bg-stone-100 text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                            >
                                Semua status
                            </option>
                            <option
                                value="active"
                                class="bg-stone-100 text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                            >
                                Aktif
                            </option>
                            <option
                                value="inactive"
                                class="bg-stone-100 text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                            >
                                Nonaktif
                            </option>
                        </select>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-2xl border border-orange-400/30 bg-orange-500/10 px-4 py-3 text-sm font-semibold text-orange-200 transition hover:bg-orange-500/15"
                                @click="submitFilters"
                            >
                                Terapkan
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-stone-600 transition hover:bg-white/[0.05] dark:border-white/10 dark:text-slate-300"
                                @click="clearFilters"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-[26px] border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/40"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                            >
                                Perbandingan Outlet
                            </p>
                            <p
                                class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                            >
                                {{ period.label }}
                            </p>
                        </div>
                        <Link
                            :href="route('reports.outlets.index')"
                            class="rounded-2xl border border-stone-200 bg-white/[0.03] px-3 py-2 text-xs font-semibold text-stone-800 transition hover:bg-white/[0.05] dark:border-white/10 dark:text-slate-200"
                        >
                            Buka Laporan
                        </Link>
                    </div>

                    <div
                        v-if="comparison.leaderboard.length"
                        class="mt-4 space-y-3"
                    >
                        <article
                            v-for="(outlet, index) in comparison.leaderboard"
                            :key="outlet.id"
                            class="rounded-2xl border border-stone-200 bg-white/[0.03] p-3 dark:border-white/10"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p
                                        class="text-xs font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Top {{ index + 1 }}
                                    </p>
                                    <h3
                                        class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{ outlet.name }}
                                    </h3>
                                    <p
                                        class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        {{ outlet.monthly_orders }} order •
                                        {{ outlet.active_employees }} karyawan
                                        aktif
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-sm font-black text-emerald-300"
                                    >
                                        {{
                                            formatCurrency(
                                                outlet.monthly_revenue,
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="text-[11px] text-stone-400 dark:text-slate-500"
                                    >
                                        share {{ outlet.revenue_share }}%
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-2xl border border-dashed border-stone-200 bg-white/[0.02] px-4 py-5 text-sm text-stone-500 dark:border-white/10 dark:text-slate-400"
                    >
                        Belum ada data revenue settled untuk periode ini.
                    </div>
                </div>
            </section>

            <section
                class="rounded-[28px] border border-stone-200 bg-white p-5 dark:border-white/10 dark:bg-slate-950/45"
            >
                <div
                    class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between"
                >
                    <div>
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                        >
                            Daftar Outlet
                        </p>
                        <h3
                            class="mt-1 text-xl font-black text-stone-900 dark:text-white"
                        >
                            Snapshot cabang aktif dan nonaktif
                        </h3>
                    </div>
                    <p class="text-xs text-stone-400 dark:text-slate-500">
                        Menampilkan {{ outlets.from ?? 0 }}-{{
                            outlets.to ?? 0
                        }}
                        dari {{ outlets.total }} outlet
                    </p>
                </div>

                <div
                    v-if="outlets.data.length"
                    class="mt-5 grid gap-4 xl:grid-cols-2"
                >
                    <article
                        v-for="outlet in outlets.data"
                        :key="outlet.id"
                        class="flex flex-col justify-between rounded-[24px] border border-stone-200 bg-white/[0.03] p-4 transition-all duration-300 hover:border-orange-500/20 dark:border-white/10"
                    >
                        <!-- Top: Main Info and Revenue -->
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4
                                        class="text-lg font-black text-stone-900 dark:text-white"
                                    >
                                        {{ outlet.name }}
                                    </h4>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                        :class="
                                            statusBadgeClass(outlet.is_active)
                                        "
                                    >
                                        {{
                                            outlet.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }}
                                    </span>
                                </div>
                                <p
                                    class="text-sm leading-snug text-stone-600 dark:text-slate-300"
                                >
                                    {{
                                        outlet.address ||
                                        'Alamat outlet belum diisi.'
                                    }}
                                </p>
                                <p
                                    class="text-xs text-stone-400 dark:text-slate-500"
                                >
                                    Telepon: {{ outlet.phone || '-' }}
                                </p>
                            </div>

                            <!-- Performance Snapshot (MTD Revenue & Employees) -->
                            <div class="min-w-[150px] text-left sm:text-right">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Revenue MTD
                                </p>
                                <p
                                    class="mt-0.5 text-xl font-black text-emerald-500 dark:text-emerald-300"
                                >
                                    {{
                                        formatCurrency(
                                            outlet.stats.monthly_revenue,
                                        )
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                >
                                    {{ outlet.stats.active_employees }} dari
                                    {{ outlet.stats.total_employees }} Karyawan
                                    Aktif
                                </p>
                            </div>
                        </div>

                        <!-- Accordion Trigger & Quick Actions -->
                        <div
                            class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-200/60 pt-4 dark:border-white/5"
                        >
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-white/[0.03] px-3.5 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100 dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/[0.06]"
                                @click="toggleExpand(outlet.id)"
                            >
                                <span>Detail Operasional</span>
                                <ChevronDown
                                    class="h-3.5 w-3.5 text-stone-500 transition-transform duration-300 dark:text-slate-400"
                                    :class="{
                                        'rotate-180':
                                            expandedOutlets[outlet.id],
                                    }"
                                />
                            </button>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-white/[0.03] px-3 py-2 text-xs font-semibold text-stone-800 transition hover:bg-stone-100 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/[0.05]"
                                    @click="openEditModal(outlet)"
                                >
                                    <Pencil class="h-3 w-3" />
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                                    :class="
                                        outlet.is_active
                                            ? 'border-amber-400/30 bg-amber-500/10 text-amber-300 hover:bg-amber-500/15'
                                            : 'border-emerald-400/30 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/15'
                                    "
                                    @click="toggleOutletStatus(outlet)"
                                >
                                    {{
                                        outlet.is_active
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan'
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- Accordion Content -->
                        <div
                            v-if="expandedOutlets[outlet.id]"
                            class="mt-4 space-y-4 border-t border-dashed border-stone-200 pt-4 transition-all duration-300 dark:border-white/10"
                        >
                            <!-- Secondary Stats Grid -->
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div
                                    class="rounded-xl border border-stone-200/60 bg-stone-50/50 p-3 dark:border-white/5 dark:bg-slate-950/20"
                                >
                                    <p
                                        class="text-[9px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                    >
                                        Fasilitas Meja
                                    </p>
                                    <p
                                        class="mt-1 text-base font-black text-stone-900 dark:text-white"
                                    >
                                        {{ outlet.stats.active_tables }} Aktif
                                    </p>
                                    <p
                                        class="text-[10px] text-stone-400 dark:text-slate-500"
                                    >
                                        dari total
                                        {{ outlet.stats.total_tables }} meja
                                        terdaftar
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-stone-200/60 bg-stone-50/50 p-3 dark:border-white/5 dark:bg-slate-950/20"
                                >
                                    <p
                                        class="text-[9px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                    >
                                        Average Ticket
                                    </p>
                                    <p
                                        class="mt-1 text-base font-black text-emerald-500 dark:text-emerald-300"
                                    >
                                        {{
                                            formatCurrency(
                                                outlet.stats.average_ticket,
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="text-[10px] text-stone-400 dark:text-slate-500"
                                    >
                                        dari
                                        {{ outlet.stats.monthly_orders }}
                                        transaksi terselesaikan
                                    </p>
                                </div>
                            </div>

                            <!-- Settings Summary -->
                            <div
                                class="space-y-3 rounded-xl border border-stone-200/60 bg-stone-50/30 p-4 dark:border-white/5 dark:bg-slate-950/30"
                            >
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="text-stone-500 dark:text-slate-400"
                                        >Default Struk:</span
                                    >
                                    <span
                                        class="font-bold text-stone-800 dark:text-white"
                                    >
                                        {{
                                            formatReceiptMethod(
                                                outlet.settings
                                                    .default_receipt_method,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-2 pt-1">
                                    <span
                                        class="rounded-full border border-stone-200 bg-white px-2.5 py-0.5 text-[10px] text-stone-600 dark:border-white/10 dark:bg-slate-900/60 dark:text-slate-300"
                                    >
                                        Bar Approval:
                                        {{
                                            outlet.settings.bar_approval_enabled
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }}
                                    </span>
                                    <span
                                        class="rounded-full border border-stone-200 bg-white px-2.5 py-0.5 text-[10px] text-stone-600 dark:border-white/10 dark:bg-slate-900/60 dark:text-slate-300"
                                    >
                                        Pelacakan Status:
                                        {{
                                            outlet.settings
                                                .customer_can_view_status
                                                ? 'Aktif'
                                                : 'Mati'
                                        }}
                                    </span>
                                    <span
                                        class="rounded-full border border-stone-200 bg-white px-2.5 py-0.5 text-[10px] text-stone-600 dark:border-white/10 dark:bg-slate-900/60 dark:text-slate-300"
                                    >
                                        Edit Order Pelanggan:
                                        {{
                                            outlet.settings
                                                .customer_can_edit_order
                                                ? 'Diizinkan'
                                                : 'Dilarang'
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="border-t border-stone-200/60 pt-2 dark:border-white/5"
                                >
                                    <p
                                        class="text-[9px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                    >
                                        Alur Kerja (Workflow Status)
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="statusItem in outlet.settings
                                                .workflow_statuses"
                                            :key="`${outlet.id}-${statusItem}`"
                                            class="rounded-full border border-orange-500/10 bg-orange-500/5 px-2 py-0.5 text-[10px] font-medium text-orange-600 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-100"
                                        >
                                            {{ statusItem }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="mt-5 rounded-[24px] border border-dashed border-stone-200 bg-white/[0.02] px-5 py-10 text-center dark:border-white/10"
                >
                    <p
                        class="text-lg font-semibold text-stone-900 dark:text-white"
                    >
                        Tidak ada outlet yang cocok dengan filter.
                    </p>
                    <p class="mt-2 text-sm text-stone-500 dark:text-slate-400">
                        Coba ganti kata kunci pencarian atau reset filter
                        status.
                    </p>
                </div>

                <div
                    v-if="outlets.links.length > 3"
                    class="mt-5 flex flex-wrap items-center gap-2"
                >
                    <Link
                        v-for="link in outlets.links"
                        :key="`${link.label}-${link.url}`"
                        :href="link.url || '#'"
                        class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                        :class="
                            link.active
                                ? 'border-orange-400/40 bg-orange-500/15 text-orange-100'
                                : link.url
                                  ? 'border-stone-200 bg-white/[0.03] text-stone-600 hover:border-stone-200 hover:bg-white/[0.05] dark:border-white/10 dark:border-white/20 dark:text-slate-300'
                                  : 'cursor-not-allowed border-stone-200 bg-white/[0.02] text-slate-600 dark:border-white/5'
                        "
                    >
                        <span v-html="link.label" />
                    </Link>
                </div>
            </section>
        </div>

        <teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-white px-4 py-8 backdrop-blur-sm dark:bg-slate-950/75"
            >
                <div
                    class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-[28px] border border-stone-200 bg-white shadow-2xl shadow-black/40 dark:border-white/10 dark:bg-slate-900"
                >
                    <div
                        class="flex flex-shrink-0 items-start justify-between border-b border-stone-200 px-6 py-5 dark:border-white/10"
                    >
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                            >
                                Pengaturan Outlet
                            </p>
                            <h3
                                class="mt-1 text-xl font-black text-stone-900 dark:text-white"
                            >
                                {{ modalTitle }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="rounded-2xl border border-stone-200 bg-white/[0.03] p-2 text-stone-600 transition hover:bg-white/[0.06] dark:border-white/10 dark:text-slate-300"
                            @click="closeModal"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form
                        class="flex-1 space-y-6 overflow-y-auto px-6 py-5"
                        @submit.prevent="submitOutlet"
                    >
                        <!-- Section 1: Informasi Dasar Cabang -->
                        <div
                            class="space-y-4 border-b border-stone-200 pb-6 dark:border-white/10"
                        >
                            <h4
                                class="text-xs font-bold uppercase tracking-[0.2em] text-orange-400"
                            >
                                Informasi Dasar Cabang
                            </h4>
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="block">
                                    <span
                                        class="text-xs font-semibold text-stone-600 dark:text-slate-300"
                                        >Nama outlet</span
                                    >
                                    <input
                                        v-model="outletForm.name"
                                        type="text"
                                        class="mt-2 w-full rounded-2xl border border-stone-200 bg-white/[0.03] px-3 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-400/40 dark:border-white/10 dark:text-white"
                                        placeholder="Mentai Sudirman"
                                    />
                                    <p
                                        v-if="outletForm.errors.name"
                                        class="mt-2 text-xs text-rose-300"
                                    >
                                        {{ outletForm.errors.name }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="text-xs font-semibold text-stone-600 dark:text-slate-300"
                                        >Nomor telepon</span
                                    >
                                    <input
                                        v-model="outletForm.phone"
                                        type="text"
                                        class="mt-2 w-full rounded-2xl border border-stone-200 bg-white/[0.03] px-3 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-400/40 dark:border-white/10 dark:text-white"
                                        placeholder="08xxxxxxxxxx"
                                    />
                                    <p
                                        v-if="outletForm.errors.phone"
                                        class="mt-2 text-xs text-rose-300"
                                    >
                                        {{ outletForm.errors.phone }}
                                    </p>
                                </label>
                            </div>

                            <label class="block">
                                <span
                                    class="text-xs font-semibold text-stone-600 dark:text-slate-300"
                                    >Alamat outlet</span
                                >
                                <textarea
                                    v-model="outletForm.address"
                                    rows="3"
                                    class="mt-2 w-full rounded-2xl border border-stone-200 bg-white/[0.03] px-3 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-400/40 dark:border-white/10 dark:text-white"
                                    placeholder="Alamat lengkap cabang"
                                />
                                <p
                                    v-if="outletForm.errors.address"
                                    class="mt-2 text-xs text-rose-300"
                                >
                                    {{ outletForm.errors.address }}
                                </p>
                            </label>
                        </div>

                        <!-- Section 2: Alur & Aturan Operasional -->
                        <div class="space-y-4">
                            <h4
                                class="text-xs font-bold uppercase tracking-[0.2em] text-orange-400"
                            >
                                Alur & Aturan Operasional
                            </h4>
                            <div class="grid gap-5 md:grid-cols-[1.1fr,0.9fr]">
                                <!-- Left side: Simplified Workflow Statuses Checkbox List -->
                                <div class="block space-y-3">
                                    <div>
                                        <span
                                            class="text-xs font-semibold text-stone-600 dark:text-slate-300"
                                            >Status Alur Kerja (Workflow)</span
                                        >
                                        <p
                                            class="mt-1 text-[10px] text-stone-400 dark:text-slate-500"
                                        >
                                            Pilih status alur kerja yang ingin
                                            diaktifkan di outlet ini.
                                        </p>
                                    </div>

                                    <div
                                        class="max-h-[300px] space-y-2 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-950/40"
                                    >
                                        <div
                                            v-for="status in availableStatuses"
                                            :key="status.key"
                                            class="flex cursor-pointer select-none items-start gap-3 rounded-xl p-2.5 transition hover:bg-stone-50 dark:hover:bg-white/[0.02]"
                                        >
                                            <input
                                                v-model="selectedStatuses"
                                                :value="status.key"
                                                type="checkbox"
                                                class="mt-1 h-4 w-4 rounded border-stone-200 bg-stone-100 text-orange-500 focus:ring-orange-400 dark:border-white/20 dark:bg-slate-950"
                                                :id="`workflow-${status.key}`"
                                            />
                                            <label
                                                :for="`workflow-${status.key}`"
                                                class="cursor-pointer text-xs leading-snug text-stone-800 dark:text-slate-200"
                                            >
                                                <span class="block font-bold">{{
                                                    status.label
                                                }}</span>
                                                <span
                                                    class="mt-0.5 block text-[10px] text-stone-400 dark:text-slate-500"
                                                    >{{ status.desc }}</span
                                                >
                                            </label>
                                        </div>
                                    </div>
                                    <p
                                        v-if="
                                            outletForm.errors.workflow_statuses
                                        "
                                        class="mt-2 text-xs text-rose-300"
                                    >
                                        {{
                                            outletForm.errors.workflow_statuses
                                        }}
                                    </p>
                                </div>

                                <!-- Right side: Config settings -->
                                <div class="space-y-4">
                                    <label class="block">
                                        <span
                                            class="text-xs font-semibold text-stone-600 dark:text-slate-300"
                                            >Default struk</span
                                        >
                                        <select
                                            v-model="
                                                outletForm.default_receipt_method
                                            "
                                            class="mt-2 w-full rounded-2xl border border-stone-200 bg-white px-3 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-400/40 dark:border-white/10 dark:bg-slate-950/80 dark:text-white"
                                            style="color-scheme: dark"
                                        >
                                            <option
                                                value="print"
                                                class="bg-stone-100 text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                                            >
                                                Print
                                            </option>
                                            <option
                                                value="whatsapp"
                                                class="bg-stone-100 text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                                            >
                                                WhatsApp
                                            </option>
                                            <option
                                                value="skip"
                                                class="bg-stone-100 text-stone-900 dark:bg-slate-950 dark:text-slate-100"
                                            >
                                                Skip
                                            </option>
                                        </select>
                                        <p
                                            v-if="
                                                outletForm.errors
                                                    .default_receipt_method
                                            "
                                            class="mt-2 text-xs text-rose-300"
                                        >
                                            {{
                                                outletForm.errors
                                                    .default_receipt_method
                                            }}
                                        </p>
                                    </label>

                                    <div
                                        class="space-y-3.5 rounded-2xl border border-stone-200 bg-white/[0.03] p-4 dark:border-white/10"
                                    >
                                        <label
                                            class="flex cursor-pointer select-none items-start gap-3"
                                        >
                                            <input
                                                v-model="
                                                    outletForm.bar_approval_enabled
                                                "
                                                type="checkbox"
                                                class="mt-1 h-4 w-4 rounded border-stone-200 bg-stone-100 text-orange-500 focus:ring-orange-400 dark:border-white/20 dark:bg-slate-950"
                                            />
                                            <span
                                                class="text-xs leading-snug text-stone-800 dark:text-slate-200"
                                            >
                                                <span class="block font-bold"
                                                    >Bar approval</span
                                                >
                                                <span
                                                    class="text-[10px] text-stone-400 dark:text-slate-500"
                                                    >Order bar butuh approval
                                                    manual untuk siap</span
                                                >
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer select-none items-start gap-3"
                                        >
                                            <input
                                                v-model="
                                                    outletForm.customer_can_view_status
                                                "
                                                type="checkbox"
                                                class="mt-1 h-4 w-4 rounded border-stone-200 bg-stone-100 text-orange-500 focus:ring-orange-400 dark:border-white/20 dark:bg-slate-950"
                                            />
                                            <span
                                                class="text-xs leading-snug text-stone-800 dark:text-slate-200"
                                            >
                                                <span class="block font-bold"
                                                    >Customer view status</span
                                                >
                                                <span
                                                    class="text-[10px] text-stone-400 dark:text-slate-500"
                                                    >Pelanggan bisa melacak
                                                    status masak pesanan</span
                                                >
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer select-none items-start gap-3"
                                        >
                                            <input
                                                v-model="
                                                    outletForm.customer_can_edit_order
                                                "
                                                type="checkbox"
                                                class="mt-1 h-4 w-4 rounded border-stone-200 bg-stone-100 text-orange-500 focus:ring-orange-400 dark:border-white/20 dark:bg-slate-950"
                                            />
                                            <span
                                                class="text-xs leading-snug text-stone-800 dark:text-slate-200"
                                            >
                                                <span class="block font-bold"
                                                    >Customer edit order</span
                                                >
                                                <span
                                                    class="text-[10px] text-stone-400 dark:text-slate-500"
                                                    >Pelanggan boleh edit
                                                    pesanan (sebelum
                                                    dimasak)</span
                                                >
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Pengaturan Pajak -->
                        <div class="space-y-4 rounded-3xl border border-stone-200 bg-stone-50/50 p-6 dark:border-white/10 dark:bg-white/[0.02]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-[0.2em] text-orange-400">
                                        Pengaturan Pajak (PB1/VAT)
                                    </h4>
                                    <p class="mt-1 text-[11px] text-stone-500 dark:text-slate-500">
                                        Atur persentase pajak dan tentukan siapa yang menanggung beban pajak.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 rounded-2xl bg-white p-1.5 dark:bg-slate-950/40 border border-stone-200 dark:border-white/10">
                                    <button
                                        type="button"
                                        @click="outletForm.tax_is_inclusive = false"
                                        :class="[
                                            'px-3 py-1.5 text-[10px] font-bold rounded-xl transition',
                                            !outletForm.tax_is_inclusive
                                                ? 'bg-orange-500 text-slate-950 shadow-sm'
                                                : 'text-stone-500 dark:text-slate-400 hover:bg-stone-100 dark:hover:bg-white/5'
                                        ]"
                                    >
                                        Eksklusif
                                    </button>
                                    <button
                                        type="button"
                                        @click="outletForm.tax_is_inclusive = true"
                                        :class="[
                                            'px-3 py-1.5 text-[10px] font-bold rounded-xl transition',
                                            outletForm.tax_is_inclusive
                                                ? 'bg-emerald-500 text-white shadow-sm'
                                                : 'text-stone-500 dark:text-slate-400 hover:bg-stone-100 dark:hover:bg-white/5'
                                        ]"
                                    >
                                        Inklusif
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-6 md:grid-cols-2 items-end">
                                <label class="block">
                                    <span class="text-xs font-semibold text-stone-600 dark:text-slate-300">Persentase Pajak (%)</span>
                                    <div class="relative mt-2">
                                        <input
                                            v-model="outletForm.tax_percentage"
                                            type="number"
                                            step="0.1"
                                            min="0"
                                            max="100"
                                            class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-400/40 dark:border-white/10 dark:bg-slate-950/80 dark:text-white"
                                            placeholder="Contoh: 10"
                                        />
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-stone-400">%</span>
                                    </div>
                                    <p v-if="outletForm.errors.tax_percentage" class="mt-2 text-xs text-rose-300">
                                        {{ outletForm.errors.tax_percentage }}
                                    </p>
                                </label>

                                <div class="rounded-2xl border border-dashed border-stone-300 dark:border-white/10 p-3 bg-white/40 dark:bg-black/20">
                                    <div v-if="!outletForm.tax_is_inclusive" class="flex items-start gap-2">
                                        <div class="mt-1 h-2 w-2 rounded-full bg-orange-500 shrink-0"></div>
                                        <p class="text-[11px] leading-relaxed text-stone-600 dark:text-slate-400">
                                            <strong class="text-stone-900 dark:text-white">Eksklusif:</strong> Pajak ditambahkan di atas harga produk. Pelanggan membayar lebih dari harga menu.
                                        </p>
                                    </div>
                                    <div v-else class="flex items-start gap-2">
                                        <div class="mt-1 h-2 w-2 rounded-full bg-emerald-500 shrink-0"></div>
                                        <p class="text-[11px] leading-relaxed text-stone-600 dark:text-slate-400">
                                            <strong class="text-stone-900 dark:text-white">Inklusif:</strong> Harga menu sudah termasuk pajak. Pendapatan bersih Anda akan dipotong nilai pajak.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer buttons -->
                        <div
                            class="flex flex-shrink-0 flex-col-reverse gap-3 border-t border-stone-200 pt-5 dark:border-white/10 sm:flex-row sm:justify-end"
                        >
                            <button
                                type="button"
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-stone-600 transition hover:bg-white/[0.05] dark:border-white/10 dark:text-slate-300"
                                @click="closeModal"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="outletForm.processing"
                            >
                                {{
                                    outletForm.processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Outlet'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </teleport>

        <AlertDialog
            :show="alertDialog.show"
            :title="alertDialog.title"
            :message="alertDialog.message"
            :type="alertDialog.type"
            @confirm="alertDialog.onConfirm"
            @cancel="closeAlertDialog"
        />
    </AuthenticatedLayout>
</template>
