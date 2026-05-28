<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BarChart3,
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

const outletForm = useForm<OutletFormPayload>({
    name: '',
    address: '',
    phone: '',
    workflow_statuses: defaultWorkflowText,
    default_receipt_method: 'print',
    bar_approval_enabled: false,
    customer_can_view_status: true,
    customer_can_edit_order: false,
});

const summaryCards = computed(() => [
    {
        label: 'Total Outlet',
        value: props.summary.total_outlets,
        helper: `${props.summary.active_outlets} aktif`,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
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
const modalTitle = computed(() => (modalMode.value === 'edit' ? 'Edit Outlet' : 'Tambah Outlet'));

function formatCurrency(value: number | string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

function formatReceiptMethod(value: OutletRow['settings']['default_receipt_method']) {
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
    outletForm.customer_can_view_status = outlet.settings.customer_can_view_status;
    outletForm.customer_can_edit_order = outlet.settings.customer_can_edit_order;
}

function closeModal() {
    modalMode.value = null;
    selectedOutlet.value = null;
    resetOutletForm();
}

function submitOutlet() {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (modalMode.value === 'edit' && selectedOutlet.value) {
        outletForm.patch(route('settings.outlets.update', selectedOutlet.value.id), options);
        return;
    }

    outletForm.post(route('settings.outlets.store'), options);
}

function toggleOutletStatus(outlet: OutletRow) {
    const nextStatus = !outlet.is_active;
    const confirmed = window.confirm(
        nextStatus
            ? `Aktifkan kembali outlet ${outlet.name}?`
            : `Nonaktifkan outlet ${outlet.name}?`,
    );

    if (!confirmed) return;

    router.patch(
        route('settings.outlets.update-status', outlet.id),
        { is_active: nextStatus },
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Manajemen Outlet & Cabang" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <Settings class="h-3.5 w-3.5" />
                    Menu #54 Manajemen Outlet & Cabang
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Manajemen Outlet & Cabang
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-slate-400">
                        Kelola outlet aktif, setting operasional dasar per cabang, dan lihat snapshot
                        performa bulan berjalan dari satu dashboard owner.
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

            <section class="rounded-[26px] border border-sky-500/15 bg-sky-500/[0.08] p-4 text-sm text-sky-100">
                <p class="font-semibold">
                    Setting workflow di halaman ini disimpan per outlet untuk kebutuhan operasional dan onboarding.
                </p>
                <p class="mt-1 text-xs text-sky-100/80">
                    Engine order aktif saat ini masih mengikuti status sistem global yang sudah berjalan di kasir, kitchen,
                    dan bar. Konfigurasi ini dipakai sebagai baseline setting outlet.
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
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                {{ stat.label }}
                            </p>
                            <p :class="['mt-2 text-3xl font-black', stat.tone]">
                                {{ stat.value }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ stat.helper }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <component :is="stat.icon" class="h-5 w-5 text-slate-200" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.3fr,0.7fr]">
                <div class="rounded-[26px] border border-white/10 bg-slate-950/40 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Filter Outlet
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Cari nama, alamat, atau telepon outlet yang ingin dicek.
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

                    <div class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr),180px,auto]">
                        <label class="relative block">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari outlet / cabang..."
                                class="w-full rounded-2xl border border-white/10 bg-white/[0.03] py-3 pl-10 pr-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                                @keyup.enter="submitFilters"
                            >
                        </label>

                        <select
                            v-model="status"
                            class="w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                            @change="submitFilters"
                        >
                            <option value="">
                                Semua status
                            </option>
                            <option value="active">
                                Aktif
                            </option>
                            <option value="inactive">
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
                                class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/[0.05]"
                                @click="clearFilters"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-[26px] border border-white/10 bg-slate-950/40 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Perbandingan Outlet
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ period.label }}
                            </p>
                        </div>
                        <Link
                            :href="route('reports.outlets.index')"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-white/[0.05]"
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
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">
                                        Top {{ index + 1 }}
                                    </p>
                                    <h3 class="mt-1 text-sm font-bold text-white">
                                        {{ outlet.name }}
                                    </h3>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ outlet.monthly_orders }} order • {{ outlet.active_employees }} karyawan aktif
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-emerald-300">
                                        {{ formatCurrency(outlet.monthly_revenue) }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        share {{ outlet.revenue_share }}%
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-2xl border border-dashed border-white/10 bg-white/[0.02] px-4 py-5 text-sm text-slate-400"
                    >
                        Belum ada data revenue settled untuk periode ini.
                    </div>
                </div>
            </section>

            <section class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                            Daftar Outlet
                        </p>
                        <h3 class="mt-1 text-xl font-black text-white">
                            Snapshot cabang aktif dan nonaktif
                        </h3>
                    </div>
                    <p class="text-xs text-slate-500">
                        Menampilkan {{ outlets.from ?? 0 }}-{{ outlets.to ?? 0 }} dari {{ outlets.total }} outlet
                    </p>
                </div>

                <div
                    v-if="outlets.data.length"
                    class="mt-5 grid gap-4 xl:grid-cols-2"
                >
                    <article
                        v-for="outlet in outlets.data"
                        :key="outlet.id"
                        class="rounded-[24px] border border-white/10 bg-white/[0.03] p-4"
                    >
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-lg font-black text-white">
                                        {{ outlet.name }}
                                    </h4>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                        :class="statusBadgeClass(outlet.is_active)"
                                    >
                                        {{ outlet.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-slate-300">
                                    {{ outlet.address || 'Alamat outlet belum diisi.' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Telepon: {{ outlet.phone || '-' }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-white/[0.05]"
                                    @click="openEditModal(outlet)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="rounded-2xl border px-3 py-2 text-xs font-semibold transition"
                                    :class="outlet.is_active
                                        ? 'border-amber-400/30 bg-amber-500/10 text-amber-200 hover:bg-amber-500/15'
                                        : 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200 hover:bg-emerald-500/15'"
                                    @click="toggleOutletStatus(outlet)"
                                >
                                    {{ outlet.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Tim Aktif
                                </p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ outlet.stats.active_employees }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    dari {{ outlet.stats.total_employees }} user
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Meja Aktif
                                </p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ outlet.stats.active_tables }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    dari {{ outlet.stats.total_tables }} meja
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Average Ticket
                                </p>
                                <p class="mt-2 text-sm font-black text-emerald-300">
                                    {{ formatCurrency(outlet.stats.average_ticket) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ outlet.stats.monthly_orders }} order settled
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        Setting Outlet
                                    </p>
                                    <p class="mt-1 text-sm text-slate-300">
                                        Default struk {{ formatReceiptMethod(outlet.settings.default_receipt_method) }}
                                    </p>
                                </div>
                                <p class="text-sm font-black text-emerald-300">
                                    {{ formatCurrency(outlet.stats.monthly_revenue) }}
                                </p>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px] text-slate-300">
                                    Bar approval {{ outlet.settings.bar_approval_enabled ? 'aktif' : 'mati' }}
                                </span>
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px] text-slate-300">
                                    Status customer {{ outlet.settings.customer_can_view_status ? 'visible' : 'hidden' }}
                                </span>
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px] text-slate-300">
                                    Edit customer {{ outlet.settings.customer_can_edit_order ? 'boleh' : 'off' }}
                                </span>
                            </div>

                            <div class="mt-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Workflow Tersimpan
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-for="statusItem in outlet.settings.workflow_statuses"
                                        :key="`${outlet.id}-${statusItem}`"
                                        class="rounded-full border border-orange-400/20 bg-orange-500/10 px-2.5 py-1 text-[11px] font-medium text-orange-100"
                                    >
                                        {{ statusItem }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="mt-5 rounded-[24px] border border-dashed border-white/10 bg-white/[0.02] px-5 py-10 text-center"
                >
                    <p class="text-lg font-semibold text-white">
                        Tidak ada outlet yang cocok dengan filter.
                    </p>
                    <p class="mt-2 text-sm text-slate-400">
                        Coba ganti kata kunci pencarian atau reset filter status.
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
                <div class="w-full max-w-3xl rounded-[28px] border border-white/10 bg-slate-900 shadow-2xl shadow-black/40">
                    <div class="flex items-start justify-between border-b border-white/10 px-6 py-5">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Pengaturan Outlet
                            </p>
                            <h3 class="mt-1 text-xl font-black text-white">
                                {{ modalTitle }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-2 text-slate-300 transition hover:bg-white/[0.06]"
                            @click="closeModal"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form
                        class="space-y-5 px-6 py-5"
                        @submit.prevent="submitOutlet"
                    >
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">Nama outlet</span>
                                <input
                                    v-model="outletForm.name"
                                    type="text"
                                    class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                    placeholder="Mentai Sudirman"
                                >
                                <p
                                    v-if="outletForm.errors.name"
                                    class="mt-2 text-xs text-rose-300"
                                >
                                    {{ outletForm.errors.name }}
                                </p>
                            </label>

                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">Nomor telepon</span>
                                <input
                                    v-model="outletForm.phone"
                                    type="text"
                                    class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                    placeholder="08xxxxxxxxxx"
                                >
                                <p
                                    v-if="outletForm.errors.phone"
                                    class="mt-2 text-xs text-rose-300"
                                >
                                    {{ outletForm.errors.phone }}
                                </p>
                            </label>
                        </div>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-300">Alamat outlet</span>
                            <textarea
                                v-model="outletForm.address"
                                rows="3"
                                class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                placeholder="Alamat lengkap cabang"
                            />
                            <p
                                v-if="outletForm.errors.address"
                                class="mt-2 text-xs text-rose-300"
                            >
                                {{ outletForm.errors.address }}
                            </p>
                        </label>

                        <div class="grid gap-4 md:grid-cols-[1.1fr,0.9fr]">
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">Workflow status outlet</span>
                                <textarea
                                    v-model="outletForm.workflow_statuses"
                                    rows="6"
                                    class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 font-mono text-sm text-white outline-none transition focus:border-orange-400/40"
                                    placeholder="Satu baris satu status"
                                />
                                <p class="mt-2 text-xs text-slate-500">
                                    Satu baris satu status. Maksimal 10 status.
                                </p>
                                <p
                                    v-if="outletForm.errors.workflow_statuses"
                                    class="mt-2 text-xs text-rose-300"
                                >
                                    {{ outletForm.errors.workflow_statuses }}
                                </p>
                            </label>

                            <div class="space-y-4">
                                <label class="block">
                                    <span class="text-xs font-semibold text-slate-300">Default struk</span>
                                    <select
                                        v-model="outletForm.default_receipt_method"
                                        class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                    >
                                        <option value="print">
                                            Print
                                        </option>
                                        <option value="whatsapp">
                                            WhatsApp
                                        </option>
                                        <option value="skip">
                                            Skip
                                        </option>
                                    </select>
                                    <p
                                        v-if="outletForm.errors.default_receipt_method"
                                        class="mt-2 text-xs text-rose-300"
                                    >
                                        {{ outletForm.errors.default_receipt_method }}
                                    </p>
                                </label>

                                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 space-y-3">
                                    <label class="flex items-start gap-3">
                                        <input
                                            v-model="outletForm.bar_approval_enabled"
                                            type="checkbox"
                                            class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-400"
                                        >
                                        <span class="text-sm text-slate-200">Aktifkan bar approval</span>
                                    </label>
                                    <label class="flex items-start gap-3">
                                        <input
                                            v-model="outletForm.customer_can_view_status"
                                            type="checkbox"
                                            class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-400"
                                        >
                                        <span class="text-sm text-slate-200">Customer bisa lihat status order</span>
                                    </label>
                                    <label class="flex items-start gap-3">
                                        <input
                                            v-model="outletForm.customer_can_edit_order"
                                            type="checkbox"
                                            class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-400"
                                        >
                                        <span class="text-sm text-slate-200">Customer bisa edit pesanan</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/[0.05]"
                                @click="closeModal"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="outletForm.processing"
                            >
                                {{ outletForm.processing ? 'Menyimpan...' : 'Simpan Outlet' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </teleport>
    </AuthenticatedLayout>
</template>
