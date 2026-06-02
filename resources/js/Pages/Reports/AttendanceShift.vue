<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeftRight,
    BarChart3,
    CalendarRange,
    Clock3,
    Search,
    UserRound,
    Users,
    Wallet,
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

interface EmployeeRow {
    id: string;
    name: string;
    outlet?: {
        id: string;
        name: string;
    } | null;
    attendance: {
        scheduled_days: number;
        attendance_days: number;
        matched_schedules: number;
        late_days: number;
        completed_days: number;
        missing_days: number;
        unscheduled_days: number;
        attendance_rate: number | null;
        missing_dates: Array<{
            date?: string | null;
            shift_template_name?: string | null;
        }>;
    };
    shift: {
        total_shifts: number;
        active_shifts: number;
        closed_shifts: number;
        total_orders: number;
        total_revenue: number;
        expected_cash: number;
        actual_cash: number;
        cash_difference: number;
        difference_count: number;
    };
}

interface MissingAttendanceRow {
    id: string;
    date?: string | null;
    employee_name: string;
    outlet_name: string;
    shift_template_name: string;
    start_time?: string | null;
    end_time?: string | null;
}

interface ShiftAnomalyRow {
    id: string;
    opened_at?: string | null;
    employee_name: string;
    outlet_name: string;
    shift_template_name: string;
    total_revenue: number;
    expected_cash: number;
    actual_cash: number;
    cash_difference: number;
}

const page = usePage<any>();
const props = defineProps<{
    summary: {
        employees_monitored: number;
        total_schedules: number;
        total_attendances: number;
        late_count: number;
        completed_count: number;
        missing_attendances: number;
        unscheduled_attendances: number;
        attendance_rate: number | null;
        total_shifts: number;
        active_shifts: number;
        closed_shifts: number;
        total_shift_orders: number;
        total_shift_revenue: number;
        expected_cash: number;
        actual_cash: number;
        cash_difference: number;
        difference_count: number;
    };
    employees: EmployeeRow[];
    missingAttendances: MissingAttendanceRow[];
    shiftAnomalies: ShiftAnomalyRow[];
    filters: {
        start_date: string;
        end_date: string;
        outlet_id?: string;
        user_id?: string;
    };
    referenceData: {
        outlets: OutletOption[];
        employees: EmployeeOption[];
    };
    period: {
        start_date: string;
        end_date: string;
    };
    limitations: {
        attendance: string;
        shift: string;
    };
}>();

const user = computed(() => page.props.auth.user);
const startDateFilter = ref(props.filters.start_date);
const endDateFilter = ref(props.filters.end_date);
const outletFilter = ref(props.filters.outlet_id || '');
const employeeFilter = ref(props.filters.user_id || '');
const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);
const canChooseEmployee = computed(() => props.referenceData.employees.length > 0);

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

const formatPercent = (value: number | null | undefined) => {
    if (value === null || value === undefined) {
        return 'Tanpa jadwal';
    }

    return `${value.toFixed(1)}%`;
};

const formatShiftTime = (startTime?: string | null, endTime?: string | null) => {
    if (!startTime && !endTime) {
        return 'Jam shift belum tersedia';
    }

    return `${startTime || '--:--'} - ${endTime || '--:--'}`;
};

const cashDifferenceClass = (value: number) => {
    if (value > 0.009) {
        return 'text-emerald-300';
    }

    if (value < -0.009) {
        return 'text-rose-300';
    }

    return 'text-slate-300';
};

const cashDifferenceSurfaceClass = (value: number) => {
    if (value > 0.009) {
        return 'border-emerald-400/15 bg-emerald-500/10';
    }

    if (value < -0.009) {
        return 'border-rose-400/15 bg-rose-500/10';
    }

    return 'border-slate-700/60 bg-slate-900/70';
};

const submitFilters = () => {
    router.get(
        route('reports.attendance-shifts.index'),
        {
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
            outlet_id: outletFilter.value || undefined,
            user_id: employeeFilter.value || undefined,
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
    employeeFilter.value = '';
    submitFilters();
};

const summaryCards = computed(() => [
    {
        label: 'Karyawan Terpantau',
        value: props.summary.employees_monitored,
        helper: `${props.summary.total_schedules} jadwal aktif di periode ini`,
        icon: Users,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
    },
    {
        label: 'Kehadiran Tercatat',
        value: props.summary.total_attendances,
        helper: `${formatPercent(props.summary.attendance_rate)} dari jadwal aktif`,
        icon: CalendarRange,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
    },
    {
        label: 'Telat & Missing',
        value: `${props.summary.late_count} / ${props.summary.missing_attendances}`,
        helper: `${props.summary.completed_count} attendance sudah clock out`,
        icon: AlertTriangle,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
    },
    {
        label: 'Shift Tertutup',
        value: props.summary.closed_shifts,
        helper: `${props.summary.active_shifts} shift masih aktif`,
        icon: Clock3,
        tone: 'text-violet-300',
        surface: 'border-violet-400/15 bg-violet-500/10',
    },
    {
        label: 'Revenue Shift',
        value: formatPrice(props.summary.total_shift_revenue),
        helper: `${props.summary.total_shift_orders} order dari shift closed`,
        icon: Wallet,
        tone: 'text-fuchsia-300',
        surface: 'border-fuchsia-400/15 bg-fuchsia-500/10',
    },
    {
        label: 'Selisih Kas',
        value: formatPrice(props.summary.cash_difference),
        helper: `${props.summary.difference_count} shift ada selisih`,
        icon: ArrowLeftRight,
        tone: 'text-rose-300',
        surface: 'border-rose-400/15 bg-rose-500/10',
    },
]);
</script>

<template>
    <Head title="Laporan Absensi & Shift" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Laporan Absensi & Shift
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Ringkasan operasional karyawan untuk periode
                        <span class="font-semibold text-orange-300">
                            {{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-300">
                        {{ user?.role || '-' }}
                    </span>
                    <Link
                        :href="route('attendance.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-2.5 text-xs font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/[0.05]"
                    >
                        <CalendarRange class="h-4 w-4" />
                        Absensi Harian
                    </Link>
                    <Link
                        :href="route('shifts.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-orange-500/20 bg-orange-500/10 px-4 py-2.5 text-xs font-semibold text-orange-100 transition hover:border-orange-400/40 hover:bg-orange-500/15"
                    >
                        <Clock3 class="h-4 w-4" />
                        Rekap Shift
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tab Navigation Laporan Sumber Daya -->
            <div class="flex flex-wrap border-b border-slate-800 bg-slate-900/40 rounded-2xl p-1 gap-1 max-w-2xl">
                <Link
                    :href="route('reports.inventory.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.inventory.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Stok & Inventori
                </Link>
                <Link
                    :href="route('reports.attendance-shifts.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.attendance-shifts.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Absensi & Shift
                </Link>
                <Link
                    :href="route('reports.exports.index')"
                    class="flex-1 min-w-[120px] text-center py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-xl transition duration-150"
                    :class="route().current('reports.exports.index') ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.02]'"
                >
                    Export Data
                </Link>
            </div>

            <section class="grid gap-4 lg:grid-cols-[1.8fr_1fr]">
                <div class="rounded-[28px] border border-white/10 bg-slate-950/75 p-5 shadow-2xl shadow-slate-950/40">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-orange-300/85">
                                Filter Laporan
                            </p>
                            <h3 class="mt-2 text-lg font-black text-white">
                                Pantau kehadiran, missing schedule, dan selisih kas shift
                            </h3>
                        </div>
                        <div class="rounded-2xl border border-orange-500/20 bg-orange-500/10 p-3 text-orange-200">
                            <Search class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                                <option
                                    v-for="outlet in referenceData.outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>

                        <label class="space-y-2 text-xs font-semibold text-slate-300">
                            <span>Karyawan</span>
                            <select
                                v-model="employeeFilter"
                                :disabled="!canChooseEmployee"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-400/40 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option value="">
                                    Semua karyawan
                                </option>
                                <option
                                    v-for="employee in referenceData.employees"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.role ? `${employee.name} - ${employee.role}` : employee.name }}
                                </option>
                            </select>
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
                            Reset Periode
                        </button>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="rounded-[28px] border border-emerald-400/15 bg-emerald-500/10 p-5 shadow-lg shadow-emerald-950/20">
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-emerald-200/85">
                            Catatan Absensi
                        </p>
                        <p class="mt-3 text-sm leading-6 text-emerald-50/90">
                            {{ limitations.attendance }}
                        </p>
                    </div>
                    <div class="rounded-[28px] border border-violet-400/15 bg-violet-500/10 p-5 shadow-lg shadow-violet-950/20">
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-violet-200/85">
                            Catatan Shift
                        </p>
                        <p class="mt-3 text-sm leading-6 text-violet-50/90">
                            {{ limitations.shift }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
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
                            Rekap Per Karyawan
                        </p>
                        <h3 class="mt-2 text-lg font-black text-white">
                            Gabungan performa absensi dan shift pada periode aktif
                        </h3>
                    </div>
                    <div class="rounded-full border border-white/10 bg-white/[0.03] px-4 py-2 text-xs font-semibold text-slate-300">
                        {{ employees.length }} baris data
                    </div>
                </div>

                <div v-if="employees.length === 0" class="mt-6 rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-10 text-center text-sm text-slate-400">
                    Belum ada data absensi atau shift pada periode yang dipilih.
                </div>

                <div v-else class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">
                                <th class="px-4 py-3">Karyawan</th>
                                <th class="px-4 py-3">Absensi</th>
                                <th class="px-4 py-3">Shift</th>
                                <th class="px-4 py-3">Keuangan Shift</th>
                                <th class="px-4 py-3">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr
                                v-for="employee in employees"
                                :key="employee.id"
                                class="align-top transition hover:bg-white/[0.03]"
                            >
                                <td class="px-4 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-2 text-orange-200">
                                            <UserRound class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-white">
                                                {{ employee.name }}
                                            </p>
                                            <p class="mt-1 text-xs text-slate-400">
                                                {{ employee.outlet?.name || 'Tanpa outlet' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-200">
                                    <p>
                                        {{ employee.attendance.matched_schedules }} hadir dari
                                        {{ employee.attendance.scheduled_days }} jadwal
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Rate {{ formatPercent(employee.attendance.attendance_rate) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Telat {{ employee.attendance.late_days }} • Missing {{ employee.attendance.missing_days }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Unscheduled check-in {{ employee.attendance.unscheduled_days }}
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-slate-200">
                                    <p>
                                        {{ employee.shift.total_shifts }} shift
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Closed {{ employee.shift.closed_shifts }} • Active {{ employee.shift.active_shifts }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ employee.shift.total_orders }} order dari shift closed
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-slate-200">
                                    <p class="font-semibold text-white">
                                        {{ formatPrice(employee.shift.total_revenue) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Expected {{ formatPrice(employee.shift.expected_cash) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Actual {{ formatPrice(employee.shift.actual_cash) }}
                                    </p>
                                    <p class="mt-2 inline-flex rounded-full border px-3 py-1 text-[11px] font-semibold" :class="cashDifferenceSurfaceClass(employee.shift.cash_difference)">
                                        <span :class="cashDifferenceClass(employee.shift.cash_difference)">
                                            Selisih {{ formatPrice(employee.shift.cash_difference) }}
                                        </span>
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-300">
                                    <p v-if="employee.attendance.missing_days > 0" class="font-semibold text-amber-300">
                                        {{ employee.attendance.missing_days }} jadwal belum ada absensi.
                                    </p>
                                    <p v-else class="font-semibold text-emerald-300">
                                        Tidak ada jadwal missing.
                                    </p>
                                    <p
                                        v-for="missing in employee.attendance.missing_dates"
                                        :key="`${employee.id}-${missing.date}-${missing.shift_template_name}`"
                                        class="mt-1 text-slate-400"
                                    >
                                        {{ formatDate(missing.date) }} • {{ missing.shift_template_name || 'Tanpa template' }}
                                    </p>
                                    <p v-if="employee.shift.difference_count > 0" class="mt-2 text-rose-300">
                                        {{ employee.shift.difference_count }} shift punya selisih kas.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[30px] border border-white/10 bg-slate-950/80 p-5 shadow-2xl shadow-slate-950/40">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-amber-300/85">
                                Missing Attendance
                            </p>
                            <h3 class="mt-2 text-lg font-black text-white">
                                Jadwal tanpa clock-in
                            </h3>
                        </div>
                        <div class="rounded-2xl border border-amber-400/15 bg-amber-500/10 p-3 text-amber-200">
                            <AlertTriangle class="h-5 w-5" />
                        </div>
                    </div>

                    <div v-if="missingAttendances.length === 0" class="mt-6 rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-5 py-8 text-sm text-slate-400">
                        Tidak ada jadwal missing pada periode ini.
                    </div>

                    <div v-else class="mt-6 space-y-3">
                        <div
                            v-for="item in missingAttendances"
                            :key="item.id"
                            class="rounded-3xl border border-white/10 bg-white/[0.03] p-4"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-semibold text-white">
                                        {{ item.employee_name }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ item.outlet_name }} • {{ item.shift_template_name }}
                                    </p>
                                </div>
                                <div class="text-right text-xs text-slate-300">
                                    <p>{{ formatDate(item.date) }}</p>
                                    <p class="mt-1 text-slate-500">
                                        {{ formatShiftTime(item.start_time, item.end_time) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[30px] border border-white/10 bg-slate-950/80 p-5 shadow-2xl shadow-slate-950/40">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-rose-300/85">
                                Anomali Shift
                            </p>
                            <h3 class="mt-2 text-lg font-black text-white">
                                Shift dengan selisih kas
                            </h3>
                        </div>
                        <div class="rounded-2xl border border-rose-400/15 bg-rose-500/10 p-3 text-rose-200">
                            <BarChart3 class="h-5 w-5" />
                        </div>
                    </div>

                    <div v-if="shiftAnomalies.length === 0" class="mt-6 rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-5 py-8 text-sm text-slate-400">
                        Tidak ada shift closed dengan selisih kas pada periode ini.
                    </div>

                    <div v-else class="mt-6 space-y-3">
                        <div
                            v-for="item in shiftAnomalies"
                            :key="item.id"
                            class="rounded-3xl border border-white/10 bg-white/[0.03] p-4"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-semibold text-white">
                                        {{ item.employee_name }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ item.outlet_name }} • {{ item.shift_template_name }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ formatDate(item.opened_at) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-white">
                                        {{ formatPrice(item.total_revenue) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Expected {{ formatPrice(item.expected_cash) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Actual {{ formatPrice(item.actual_cash) }}
                                    </p>
                                    <p class="mt-2 text-sm font-semibold" :class="cashDifferenceClass(item.cash_difference)">
                                        Selisih {{ formatPrice(item.cash_difference) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
