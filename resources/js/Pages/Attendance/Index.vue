<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    Clock3,
    LogIn,
    LogOut,
    Pencil,
    Timer,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface OutletOption {
    id: string;
    name: string;
}

interface EmployeeOption {
    id: string;
    name: string;
    photo_url?: string | null;
    outlet_id?: string | null;
    role?: {
        id: string;
        name: string;
        type: string;
    } | null;
    outlet?: {
        id: string;
        name: string;
    } | null;
}

interface AttendanceRow {
    id: string;
    status: string;
    notes?: string | null;
    correction_reason?: string | null;
    corrected_by_name?: string | null;
    work_duration_label?: string | null;
    schedule_summary?: string | null;
    date?: string | null;
    clock_in?: string | null;
    clock_out?: string | null;
    corrected_at?: string | null;
    user?: {
        id: string;
        name: string;
        role?: {
            id: string;
            name: string;
            type: string;
        } | null;
        outlet?: {
            id: string;
            name: string;
        } | null;
    } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface AttendanceReportAbsenceRow {
    schedule_id: string;
    date?: string | null;
    shift_template_name?: string | null;
    shift_time?: string | null;
    user?: {
        id: string;
        name: string;
        role?: {
            id: string;
            name: string;
            type: string;
        } | null;
        outlet?: {
            id: string;
            name: string;
        } | null;
    } | null;
}

const props = defineProps<{
    summary: {
        today: number;
        late: number;
        completed: number;
        open: number;
    };
    attendanceReport?: {
        present: number;
        late: number;
        absent: number;
        leave: number;
        scheduled_days: number;
        recorded_days: number;
        employee_count: number;
        attendance_rate: number;
        absences: AttendanceReportAbsenceRow[];
    } | null;
    selfAttendance?: AttendanceRow | null;
    todaySchedule?: {
        id: string;
        schedule_date: string;
        shift_template_id: string;
        shift_template_name: string;
        start_time: string;
        end_time: string;
    } | null;
    recentAttendances: AttendanceRow[];
    todayEntries: AttendanceRow[];
    attendances: {
        data: AttendanceRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        status?: string;
        user_id?: string;
        outlet_id?: string;
        start_date?: string;
        end_date?: string;
        per_page?: number;
    };
    referenceData: {
        outlets: OutletOption[];
        employees: EmployeeOption[];
    };
    canManage: boolean;
    canClock: boolean;
    success?: string | null;
}>();

const statusFilter = ref(props.filters.status || '');
const userFilter = ref(props.filters.user_id || '');
const outletFilter = ref(props.filters.outlet_id || '');
const startDateFilter = ref(
    props.filters.start_date || new Date().toISOString().slice(0, 10),
);
const endDateFilter = ref(
    props.filters.end_date || new Date().toISOString().slice(0, 10),
);
const selectedAttendance = ref<AttendanceRow | null>(null);
const isCorrectionOpen = ref(false);

const clockInForm = useForm<{
    schedule_id: string;
    notes: string;
}>({
    schedule_id: props.todaySchedule?.id || '',
    notes: '',
});

const clockOutForm = useForm<{
    notes: string;
}>({
    notes: '',
});

const correctionForm = useForm<{
    clock_in: string;
    clock_out: string;
    notes: string;
    correction_reason: string;
    status: string;
    user_id: string;
    outlet_id: string;
    start_date: string;
    end_date: string;
}>({
    clock_in: '',
    clock_out: '',
    notes: '',
    correction_reason: '',
    status: props.filters.status || '',
    user_id: props.filters.user_id || '',
    outlet_id: props.filters.outlet_id || '',
    start_date:
        props.filters.start_date || new Date().toISOString().slice(0, 10),
    end_date: props.filters.end_date || new Date().toISOString().slice(0, 10),
});

const summaryCards = computed(() => [
    {
        label: 'Absensi Hari Ini',
        value: props.summary.today,
        tone: 'text-stone-900 dark:text-white',
        surface: 'border-stone-200 bg-white dark:border-white/10 dark:bg-white/[0.03]',
        icon: CalendarDays,
    },
    {
        label: 'Terlambat',
        value: props.summary.late,
        tone: 'text-rose-700 dark:text-rose-300',
        surface: 'border-rose-200 bg-rose-50 dark:border-rose-500/10 dark:bg-rose-500/10',
        icon: Timer,
    },
    {
        label: 'Sudah Clock Out',
        value: props.summary.completed,
        tone: 'text-emerald-700 dark:text-emerald-300',
        surface: 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/10 dark:bg-emerald-500/10',
        icon: CheckCircle2,
    },
    {
        label: 'Masih Berjalan',
        value: props.summary.open,
        tone: 'text-amber-700 dark:text-amber-300',
        surface: 'border-amber-200 bg-amber-50 dark:border-amber-500/10 dark:bg-amber-500/10',
        icon: Clock3,
    },
]);

const attendanceReport = computed(
    () =>
        props.attendanceReport ?? {
            present: 0,
            late: 0,
            absent: 0,
            leave: 0,
            scheduled_days: 0,
            recorded_days: 0,
            employee_count: 0,
            attendance_rate: 0,
            absences: [],
        },
);

const reportCards = computed(() => [
    {
        label: 'Hadir Tepat Waktu',
        value: attendanceReport.value.present,
        helper: `${attendanceReport.value.recorded_days} hari terekam`,
        tone: 'text-emerald-800 dark:text-emerald-300',
        surface: 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/10',
    },
    {
        label: 'Terlambat',
        value: attendanceReport.value.late,
        helper: 'Masuk di atas jam jadwal',
        tone: 'text-amber-800 dark:text-amber-300',
        surface: 'border-amber-300 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10',
    },
    {
        label: 'Absen',
        value: attendanceReport.value.absent,
        helper: 'Jadwal aktif tanpa absensi',
        tone: 'text-rose-800 dark:text-rose-300',
        surface: 'border-rose-300 bg-rose-50 dark:border-rose-500/20 dark:bg-rose-500/10',
    },
    {
        label: 'Cuti / Leave',
        value: attendanceReport.value.leave,
        helper: `${attendanceReport.value.employee_count} karyawan tercakup`,
        tone: 'text-sky-800 dark:text-sky-300',
        surface: 'border-sky-300 bg-sky-50 dark:border-sky-500/20 dark:bg-sky-500/10',
    },
    {
        label: 'Attendance Rate',
        value: `${attendanceReport.value.attendance_rate.toFixed(2)}%`,
        helper: `${attendanceReport.value.scheduled_days} jadwal pada periode ini`,
        tone: 'text-stone-900 dark:text-white',
        surface: 'border-stone-300 dark:border-white/20 bg-white dark:bg-slate-900',
    },
]);

const filteredEmployees = computed(() => {
    return props.referenceData.employees.filter((employee) => {
        if (!outletFilter.value) return true;

        return employee.outlet?.id === outletFilter.value;
    });
});

const filteredTodayEntries = computed(() => {
    return props.todayEntries.filter((attendance) => {
        if (
            outletFilter.value &&
            attendance.user?.outlet?.id !== outletFilter.value
        ) {
            return false;
        }

        if (userFilter.value && attendance.user?.id !== userFilter.value) {
            return false;
        }

        if (statusFilter.value && attendance.status !== statusFilter.value) {
            return false;
        }

        return true;
    });
});

const hasClockedIn = computed(() => Boolean(props.selfAttendance?.clock_in));
const hasClockedOut = computed(() => Boolean(props.selfAttendance?.clock_out));
const clockInGeneralError = computed(
    () => (clockInForm.errors as Record<string, string>)['attendance'] || '',
);
const clockOutGeneralError = computed(
    () => (clockOutForm.errors as Record<string, string>)['attendance'] || '',
);

const statusClass = (status: string) => {
    if (status === 'late') {
        return 'border-rose-400/20 bg-rose-500/10 text-rose-200';
    }

    return 'border-emerald-400/20 bg-emerald-500/10 text-emerald-200';
};

const formatDate = (value?: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(new Date(value));
};

const formatTime = (value?: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const formatDateTimeLocal = (value?: string | null) => {
    if (!value) return '';

    const date = new Date(value);
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);

    return local.toISOString().slice(0, 16);
};

const submitFilters = () => {
    router.get(
        route('attendance.index'),
        {
            status: statusFilter.value || undefined,
            user_id: userFilter.value || undefined,
            outlet_id: outletFilter.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    statusFilter.value = '';
    userFilter.value = '';
    outletFilter.value = '';
    startDateFilter.value = new Date().toISOString().slice(0, 10);
    endDateFilter.value = new Date().toISOString().slice(0, 10);
    submitFilters();
};

const submitClockIn = () => {
    clockInForm.post(route('attendance.clock-in'), {
        preserveScroll: true,
        onSuccess: () => clockInForm.reset('notes'),
    });
};

const submitClockOut = () => {
    clockOutForm.post(route('attendance.clock-out'), {
        preserveScroll: true,
        onSuccess: () => clockOutForm.reset(),
    });
};

const openCorrectionModal = (attendance: AttendanceRow) => {
    selectedAttendance.value = attendance;
    correctionForm.clock_in = formatDateTimeLocal(attendance.clock_in);
    correctionForm.clock_out = formatDateTimeLocal(attendance.clock_out);
    correctionForm.notes = attendance.notes || '';
    correctionForm.correction_reason = '';
    correctionForm.status = statusFilter.value;
    correctionForm.user_id = userFilter.value;
    correctionForm.outlet_id = outletFilter.value;
    correctionForm.start_date = startDateFilter.value;
    correctionForm.end_date = endDateFilter.value;
    correctionForm.clearErrors();
    isCorrectionOpen.value = true;
};

const closeCorrectionModal = () => {
    isCorrectionOpen.value = false;
    selectedAttendance.value = null;
    correctionForm.reset();
};

const submitCorrection = () => {
    if (!selectedAttendance.value) return;

    correctionForm.patch(
        route('attendance.update', selectedAttendance.value.id),
        {
            preserveScroll: true,
            onSuccess: () => closeCorrectionModal(),
        },
    );
};

const employeeStatusMap = computed(() => {
    const map = new Map<string, { status: string; record?: AttendanceRow }>();

    props.referenceData.employees.forEach((emp) => {
        map.set(emp.id, { status: 'absent' });
    });

    props.todayEntries.forEach((entry) => {
        if (entry.user?.id) {
            const status = entry.clock_out ? 'completed' : 'active';
            map.set(entry.user.id, { status, record: entry });
        }
    });

    return map;
});

const quickClockIn = (employeeId: string) => {
    router.post(
        route('attendance.clock-in'),
        {
            user_id: employeeId,
        },
        {
            preserveScroll: true,
        },
    );
};

const quickClockOut = (employeeId: string) => {
    router.post(
        route('attendance.clock-out'),
        {
            user_id: employeeId,
        },
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head title="Absensi Digital & Laporan Kehadiran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Absensi Digital
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Clock in/out mandiri, deteksi telat dari jadwal shift,
                        koreksi absensi, dan laporan kehadiran per periode untuk
                        supervisor atau owner.
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

            <!-- Tab Navigation Global -->
            <div
                class="flex w-full flex-wrap gap-1 rounded-2xl border border-stone-200 bg-stone-50 p-1 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/60"
            >
                <Link
                    :href="route('shifts.index')"
                    class="flex-1 min-w-[120px] rounded-xl py-2 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('shifts.index')
                            ? 'bg-orange-500 text-slate-955 shadow-md shadow-orange-500/20 border border-orange-600'
                            : 'text-stone-700 hover:bg-stone-200 hover:text-stone-955 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Shift Kasir (Laci Kas)
                </Link>
                <Link
                    :href="route('attendance.index')"
                    class="flex-1 min-w-[120px] rounded-xl py-2 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('attendance.index')
                            ? 'bg-orange-500 text-slate-955 shadow-md shadow-orange-500/20 border border-orange-600'
                            : 'text-stone-700 hover:bg-stone-200 hover:text-stone-955 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Absensi Karyawan
                </Link>
                <Link
                    :href="route('schedules.index')"
                    class="flex-1 min-w-[120px] rounded-xl py-2 text-center text-xs font-black uppercase tracking-wider transition duration-150"
                    :class="
                        route().current('schedules.index')
                            ? 'bg-orange-500 text-slate-955 shadow-md shadow-orange-500/20 border border-orange-600'
                            : 'text-stone-700 hover:bg-stone-200 hover:text-stone-955 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/5'
                    "
                >
                    Jadwal Shift Kerja
                </Link>
            </div>

            <section class="grid gap-3 lg:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-2xl border p-4 shadow-[0_18px_50px_rgba(15,23,42,0.16)]"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-3 text-3xl font-black"
                                :class="card.tone"
                            >
                                {{ card.value }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-stone-900 dark:border-white/10 dark:bg-slate-950/40 dark:text-white"
                        >
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
                <article
                    class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                            >
                                Absensi Cepat Karyawan
                            </h3>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                Pilih karyawan di bawah untuk menandai kehadiran
                                harian mereka secara cepat.
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-orange-200 dark:border-white/10 dark:bg-slate-950/50"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </div>

                    <div
                        class="mt-5 grid max-h-[500px] gap-3 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="employee in referenceData.employees"
                            :key="employee.id"
                            class="flex items-center justify-between rounded-2xl border border-stone-200 bg-white/[0.02] p-3 transition duration-150 hover:bg-white/[0.04] dark:border-white/10"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="aspect-[3/4] h-16 w-12 flex-shrink-0 overflow-hidden rounded-xl border border-stone-200 bg-white dark:border-white/10 dark:bg-slate-900"
                                >
                                    <img
                                        v-if="employee.photo_url"
                                        :src="employee.photo_url"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-white text-[9px] font-bold text-stone-400 dark:bg-slate-900 dark:text-slate-400"
                                    >
                                        3:4
                                    </div>
                                </div>
                                <div>
                                    <h4
                                        class="text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{ employee.name }}
                                    </h4>
                                    <p
                                        class="mt-0.5 text-[10px] uppercase tracking-wider text-stone-400 dark:text-slate-400"
                                    >
                                        {{ employee.role?.name || '-' }}
                                    </p>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <span
                                            v-if="
                                                employeeStatusMap.get(
                                                    employee.id,
                                                )?.status === 'active'
                                            "
                                            class="inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-500"
                                        ></span>
                                        <span
                                            class="text-[10px] font-medium text-stone-500 dark:text-slate-400"
                                        >
                                            {{
                                                employeeStatusMap.get(
                                                    employee.id,
                                                )?.status === 'completed'
                                                    ? 'Sudah Pulang'
                                                    : employeeStatusMap.get(
                                                            employee.id,
                                                        )?.status === 'active'
                                                      ? 'Sedang Bekerja'
                                                      : 'Belum Hadir'
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Tombol Hadir (Absent -> Active) -->
                                <button
                                    v-if="
                                        employeeStatusMap.get(employee.id)
                                            ?.status === 'absent'
                                    "
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-600 bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-850 transition duration-150 hover:bg-emerald-100 dark:border-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20 active:scale-[0.98]"
                                    @click="quickClockIn(employee.id)"
                                >
                                    <LogIn class="h-3.5 w-3.5" />
                                    Hadir
                                </button>

                                <!-- Tombol Pulang (Active -> Completed) -->
                                <button
                                    v-else-if="
                                        employeeStatusMap.get(employee.id)
                                            ?.status === 'active'
                                    "
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-600 bg-rose-50 px-3 py-2 text-xs font-black text-rose-855 transition duration-150 hover:bg-rose-100 dark:border-rose-500/25 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20 active:scale-[0.98]"
                                    @click="quickClockOut(employee.id)"
                                >
                                    <LogOut class="h-3.5 w-3.5" />
                                    Pulang
                                </button>

                                <!-- Status Selesai -->
                                <div
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-400 dark:border-white/5 dark:bg-slate-900 dark:text-slate-400"
                                >
                                    <CheckCircle2
                                        class="h-3.5 w-3.5 text-emerald-500"
                                    />
                                    Selesai
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-sky-200 dark:border-white/10 dark:bg-slate-950/50"
                        >
                            <CalendarDays class="h-5 w-5" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                            >
                                {{
                                    canManage
                                        ? 'Absensi Hari Ini'
                                        : 'Riwayat Saya'
                                }}
                            </h3>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                {{
                                    canManage
                                        ? 'Pantau siapa yang sudah hadir, telat, atau belum clock out.'
                                        : 'Ringkasan 7 absensi terakhir Anda.'
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article
                            v-for="attendance in canManage
                                ? filteredTodayEntries
                                : recentAttendances"
                            :key="attendance.id"
                            class="rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-4 dark:border-white/10"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-sm font-black text-stone-900 dark:text-white"
                                    >
                                        {{ attendance.user?.name || 'Saya' }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                    >
                                        {{
                                            attendance.user?.outlet?.name ||
                                            formatDate(attendance.date)
                                        }}
                                        <span v-if="attendance.user?.role?.name"
                                            >•
                                            {{
                                                attendance.user.role.name
                                            }}</span
                                        >
                                    </p>
                                </div>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="statusClass(attendance.status)"
                                >
                                    {{
                                        attendance.status === 'late'
                                            ? 'Terlambat'
                                            : 'Present'
                                    }}
                                </span>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                    >
                                        Clock In
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-stone-900 dark:text-white"
                                    >
                                        {{ formatTime(attendance.clock_in) }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                    >
                                        Clock Out
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-stone-900 dark:text-white"
                                    >
                                        {{ formatTime(attendance.clock_out) }}
                                    </p>
                                </div>
                            </div>
                            <p
                                class="mt-3 text-xs text-stone-500 dark:text-slate-400"
                            >
                                {{
                                    attendance.schedule_summary ||
                                    'Tanpa jadwal shift'
                                }}
                            </p>
                        </article>

                        <div
                            v-if="
                                !(canManage
                                    ? filteredTodayEntries.length
                                    : recentAttendances.length)
                            "
                            class="rounded-2xl border border-dashed border-stone-200 px-4 py-10 text-center text-sm text-stone-500 dark:border-white/10 dark:text-slate-400"
                        >
                            Belum ada data absensi untuk panel ini.
                        </div>
                    </div>
                </article>
            </section>

            <section
                class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div
                        class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-5"
                    >
                        <label v-if="canManage" class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                >Outlet</span
                            >
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua outlet</option>
                                <option
                                    v-for="outlet in referenceData.outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>

                        <label v-if="canManage" class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                >Karyawan</span
                            >
                            <select
                                v-model="userFilter"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua karyawan</option>
                                <option
                                    v-for="employee in filteredEmployees"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }} •
                                    {{ employee.role?.name || '-' }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                >Status</span
                            >
                            <select
                                v-model="statusFilter"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua status</option>
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                                <option value="leave">Leave</option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                >Dari tanggal</span
                            >
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900/80 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                >Sampai tanggal</span
                            >
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900/80 dark:text-white"
                            />
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border-2 border-stone-955 bg-stone-100 hover:bg-stone-200 px-4 py-3 text-sm font-black text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                            @click="clearFilters"
                        >
                            Reset Filter
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-4 py-3 text-sm font-black text-slate-950 transition active:scale-[0.98] shadow-md shadow-orange-500/10"
                            @click="submitFilters"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </section>

            <section
                v-if="canManage"
                class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            Laporan Kehadiran
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                        >
                            Rekap hadir, terlambat, absen, dan cuti berdasarkan
                            filter periode, outlet, dan karyawan.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border px-4 py-3 text-sm"
                        :class="
                            attendanceReport.absent > 0
                                ? 'border-amber-400/15 bg-amber-500/10 text-amber-200'
                                : 'border-emerald-400/15 bg-emerald-500/10 text-emerald-200'
                        "
                    >
                        {{
                            attendanceReport.absent > 0
                                ? `${attendanceReport.absent} jadwal terindikasi absen pada periode ini.`
                                : 'Tidak ada jadwal yang terindikasi absen pada periode ini.'
                        }}
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <article
                        v-for="card in reportCards"
                        :key="card.label"
                        class="rounded-2xl border p-4 shadow-[0_18px_50px_rgba(15,23,42,0.16)]"
                        :class="card.surface"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                        >
                            {{ card.label }}
                        </p>
                        <p class="mt-3 text-3xl font-black" :class="card.tone">
                            {{ card.value }}
                        </p>
                        <p
                            class="mt-2 text-xs text-stone-400 dark:text-slate-400"
                        >
                            {{ card.helper }}
                        </p>
                    </article>
                </div>

                <div
                    class="mt-5 rounded-2xl border border-stone-200 bg-white dark:border-white/10 dark:bg-slate-950/50"
                >
                    <div
                        class="flex items-center justify-between border-b border-stone-200 px-4 py-4 dark:border-white/10"
                    >
                        <div>
                            <h4
                                class="text-sm font-bold text-stone-900 dark:text-white"
                            >
                                Jadwal Tanpa Absensi
                            </h4>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                Dipakai sebagai indikator absen dari jadwal
                                aktif yang tidak memiliki catatan attendance.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="!attendanceReport.absences.length"
                        class="px-4 py-10 text-center text-sm text-stone-500 dark:text-slate-400"
                    >
                        Tidak ada jadwal tanpa absensi pada filter ini.
                    </div>

                    <div v-else class="divide-y divide-white/10">
                        <article
                            v-for="absence in attendanceReport.absences"
                            :key="absence.schedule_id"
                            class="grid gap-3 px-4 py-4 md:grid-cols-[0.9fr_0.8fr_1fr]"
                        >
                            <div>
                                <p
                                    class="text-sm font-black text-stone-900 dark:text-white"
                                >
                                    {{ absence.user?.name || '-' }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                >
                                    {{ absence.user?.outlet?.name || '-' }}
                                    <span v-if="absence.user?.role?.name"
                                        >• {{ absence.user.role.name }}</span
                                    >
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Tanggal
                                </p>
                                <p
                                    class="mt-1 text-sm text-stone-900 dark:text-white"
                                >
                                    {{ formatDate(absence.date) }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Jadwal
                                </p>
                                <p
                                    class="mt-1 text-sm text-stone-900 dark:text-white"
                                >
                                    {{
                                        absence.shift_template_name ||
                                        'Tanpa template'
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                >
                                    {{ absence.shift_time || '-' }}
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section
                class="rounded-3xl border border-stone-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex items-center justify-between border-b border-stone-200 px-5 py-4 dark:border-white/10"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            {{
                                canManage
                                    ? 'Daftar Absensi Tim'
                                    : 'Riwayat Absensi Saya'
                            }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                        >
                            Menampilkan {{ attendances.from ?? 0 }} -
                            {{ attendances.to ?? 0 }} dari
                            {{ attendances.total }} data.
                        </p>
                    </div>
                </div>

                <div
                    v-if="!attendances.data.length"
                    class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400"
                >
                    Belum ada data absensi pada filter ini.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="attendance in attendances.data"
                        :key="attendance.id"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[1fr_0.9fr_1fr_auto]"
                    >
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    class="text-base font-black text-stone-900 dark:text-white"
                                >
                                    {{ attendance.user?.name || 'User' }}
                                </h3>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="statusClass(attendance.status)"
                                >
                                    {{
                                        attendance.status === 'late'
                                            ? 'Terlambat'
                                            : 'Present'
                                    }}
                                </span>
                            </div>
                            <p
                                class="text-sm text-stone-600 dark:text-slate-300"
                            >
                                {{ attendance.user?.role?.name || '-' }} •
                                {{ attendance.user?.outlet?.name || '-' }}
                            </p>
                            <p
                                class="text-xs text-stone-400 dark:text-slate-400"
                            >
                                {{ formatDate(attendance.date) }}
                            </p>
                        </div>

                        <div
                            class="space-y-2 text-sm text-stone-600 dark:text-slate-300"
                        >
                            <p
                                class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-400"
                            >
                                Waktu
                            </p>
                            <p>
                                Clock in: {{ formatTime(attendance.clock_in) }}
                            </p>
                            <p>
                                Clock out:
                                {{ formatTime(attendance.clock_out) }}
                            </p>
                            <p class="text-stone-400 dark:text-slate-400">
                                {{ attendance.work_duration_label || '-' }}
                            </p>
                        </div>

                        <div
                            class="space-y-2 text-sm text-stone-600 dark:text-slate-300"
                        >
                            <p
                                class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-400"
                            >
                                Jadwal & Koreksi
                            </p>
                            <p>
                                {{
                                    attendance.schedule_summary ||
                                    'Tanpa jadwal shift'
                                }}
                            </p>
                            <p class="text-stone-400 dark:text-slate-400">
                                {{
                                    attendance.corrected_by_name
                                        ? `Dikoreksi oleh ${attendance.corrected_by_name}`
                                        : 'Belum ada koreksi'
                                }}
                            </p>
                            <p
                                v-if="attendance.correction_reason"
                                class="text-xs text-amber-200"
                            >
                                Alasan: {{ attendance.correction_reason }}
                            </p>
                            <p
                                v-if="attendance.notes"
                                class="text-xs text-stone-500 dark:text-slate-400"
                            >
                                Catatan: {{ attendance.notes }}
                            </p>
                        </div>

                        <div
                            v-if="canManage"
                            class="flex items-start justify-end"
                        >
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border-2 border-stone-950 bg-stone-50 hover:bg-stone-200 px-3.5 py-2 text-xs font-black text-stone-900 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                                @click="openCorrectionModal(attendance)"
                            >
                                <Pencil class="h-4 w-4" />
                                Koreksi
                            </button>
                        </div>
                    </article>
                </div>

                <div
                    v-if="attendances.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-5 py-4 dark:border-white/10"
                >
                    <p class="text-xs text-stone-400 dark:text-slate-400">
                        Riwayat absensi dipaginasi agar monitoring tetap ringan.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in attendances.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="rounded-xl border-2 px-3 py-2 text-xs font-black transition active:scale-[0.98]"
                            :class="
                                link.active
                                    ? 'border-orange-600 bg-orange-500 text-stone-955 shadow-sm'
                                    : link.url
                                      ? 'border-stone-955 bg-stone-50 text-stone-900 hover:bg-stone-200 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]'
                                      : 'cursor-not-allowed border-stone-200 bg-stone-100 text-stone-400 dark:border-white/5 dark:bg-white/[0.01] dark:text-slate-600'
                            "
                        >
                            <span v-html="link.label"></span>
                        </Link>
                    </div>
                </div>
            </section>
        </div>

        <div
            v-if="isCorrectionOpen && canManage"
            class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 px-4 py-6 backdrop-blur-sm dark:bg-slate-950/80"
        >
            <div
                class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-stone-200 bg-stone-100 p-6 shadow-[0_30px_120px_rgba(15,23,42,0.6)] dark:border-white/10 dark:bg-slate-950"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3
                            class="text-xl font-black text-stone-900 dark:text-white"
                        >
                            Koreksi Absensi
                        </h3>
                        <p
                            class="mt-1 text-sm text-stone-500 dark:text-slate-400"
                        >
                            Edit clock in/out dan simpan alasan koreksi untuk
                            audit supervisor atau owner.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-2xl border-2 border-stone-955 bg-stone-50 hover:bg-stone-200 p-2 text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                        @click="closeCorrectionModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitCorrection">
                    <section class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                >Clock In</span
                            >
                            <input
                                v-model="correctionForm.clock_in"
                                type="datetime-local"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="correctionForm.errors.clock_in"
                                class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                            >
                                {{ correctionForm.errors.clock_in }}
                            </p>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                >Clock Out</span
                            >
                            <input
                                v-model="correctionForm.clock_out"
                                type="datetime-local"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="correctionForm.errors.clock_out"
                                class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                            >
                                {{ correctionForm.errors.clock_out }}
                            </p>
                        </label>
                    </section>

                    <label class="block">
                        <span
                            class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                            >Catatan</span
                        >
                        <textarea
                            v-model="correctionForm.notes"
                            rows="3"
                            class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 placeholder:text-stone-500 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                        />
                        <p
                            v-if="correctionForm.errors.notes"
                            class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                        >
                            {{ correctionForm.errors.notes }}
                        </p>
                    </label>

                    <label class="block">
                        <span
                            class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                            >Alasan Koreksi</span
                        >
                        <textarea
                            v-model="correctionForm.correction_reason"
                            rows="3"
                            placeholder="Contoh: device offline saat datang, supervisor input ulang berdasarkan log shift."
                            class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 placeholder:text-stone-500 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                        />
                        <p
                            v-if="correctionForm.errors.correction_reason"
                            class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                        >
                            {{ correctionForm.errors.correction_reason }}
                        </p>
                    </label>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border-2 border-stone-955 bg-stone-100 hover:bg-stone-200 px-4 py-3 text-sm font-black text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                            @click="closeCorrectionModal"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="rounded-2xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-5 py-3 text-sm font-black text-slate-955 transition disabled:cursor-not-allowed disabled:opacity-60 shadow-md shadow-orange-500/10 active:scale-[0.98]"
                            :disabled="correctionForm.processing"
                        >
                            {{
                                correctionForm.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Koreksi'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
