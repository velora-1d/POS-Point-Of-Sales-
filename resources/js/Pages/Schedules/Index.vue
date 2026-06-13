<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Layers3,
    Plus,
    Save,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

interface OutletOption {
    id: string;
    name: string;
}

interface EmployeeRow {
    id: string;
    name: string;
    email: string;
    is_active: boolean;
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

interface ShiftTemplateRow {
    id: string;
    outlet_id: string;
    name: string;
    start_time: string;
    end_time: string;
    is_active: boolean;
}

interface ScheduleRow {
    id: string;
    schedule_date: string;
    user_id?: string;
    user?: EmployeeRow | null;
    shift_template_id?: string;
    shift_template?: ShiftTemplateRow | null;
    shiftTemplate?: ShiftTemplateRow | null;
}

const props = defineProps<{
    summary: {
        today: number;
        morning: number;
        evening: number;
        employees: number;
    };
    employees: EmployeeRow[];
    shiftTemplates: ShiftTemplateRow[];
    todaySchedules: ScheduleRow[];
    schedules: ScheduleRow[];
    filters: {
        week_start?: string;
        employee_id?: string;
        outlet_id?: string;
    };
    days: string[];
    referenceData: {
        outlets: OutletOption[];
    };
    canManage: boolean;
    success?: string | null;
}>();

const weekStart = ref(
    props.filters.week_start ||
        props.days[0] ||
        new Date().toISOString().slice(0, 10),
);
const employeeFilter = ref(props.filters.employee_id || '');
const outletFilter = ref(props.filters.outlet_id || '');

const dailyForm = useForm<{
    outlet_id: string;
    user_id: string;
    shift_template_id: string;
    schedule_date: string;
}>({
    outlet_id: outletFilter.value || props.referenceData.outlets[0]?.id || '',
    user_id: props.filters.employee_id || '',
    shift_template_id: '',
    schedule_date:
        props.filters.week_start ||
        props.days[0] ||
        new Date().toISOString().slice(0, 10),
});

const weeklyForm = useForm<{
    outlet_id: string;
    user_id: string;
    week_start: string;
    days: Array<string | null>;
}>({
    outlet_id: outletFilter.value || props.referenceData.outlets[0]?.id || '',
    user_id: props.filters.employee_id || '',
    week_start:
        props.filters.week_start ||
        props.days[0] ||
        new Date().toISOString().slice(0, 10),
    days: Array(7).fill(null),
});

const scheduleMap = computed(() => {
    const map = new Map<string, Map<string, ScheduleRow>>();

    props.schedules.forEach((schedule) => {
        const userId = schedule.user?.id || schedule.user_id;

        if (!userId) {
            return;
        }

        if (!map.has(userId)) {
            map.set(userId, new Map<string, ScheduleRow>());
        }

        const dateKey = schedule.schedule_date.slice(0, 10);
        map.get(userId)?.set(dateKey, schedule);
    });

    return map;
});

const gridDays = computed(() => {
    return props.days.map((date, index) => ({
        index,
        date,
        label: new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
        }).format(new Date(date)),
        shortDate: new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
        }).format(new Date(date)),
    }));
});

const assignmentDays = computed(() => {
    const start = new Date(
        weeklyForm.week_start ||
            props.days[0] ||
            new Date().toISOString().slice(0, 10),
    );

    return Array.from({ length: 7 }, (_, index) => {
        const current = new Date(start);
        current.setDate(start.getDate() + index);

        return {
            index,
            date: current.toISOString().slice(0, 10),
            label: new Intl.DateTimeFormat('id-ID', {
                weekday: 'long',
            }).format(current),
            shortDate: new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'short',
            }).format(current),
        };
    });
});

const summaryCards = computed(() => [
    {
        label: 'Shift Hari Ini',
        value: props.summary.today,
        tone: 'text-stone-900 dark:text-white',
        surface: 'border-stone-200 bg-white dark:border-white/10 dark:bg-white/[0.03]',
        icon: CalendarDays,
    },
    {
        label: 'Shift Pagi',
        value: props.summary.morning,
        tone: 'text-amber-700 dark:text-amber-300',
        surface: 'border-amber-200 bg-amber-50 dark:border-amber-500/10 dark:bg-amber-500/10',
        icon: Clock3,
    },
    {
        label: 'Shift Malam',
        value: props.summary.evening,
        tone: 'text-sky-700 dark:text-sky-300',
        surface: 'border-sky-200 bg-sky-50 dark:border-sky-500/10 dark:bg-sky-500/10',
        icon: Layers3,
    },
    {
        label: 'Karyawan Terjadwal',
        value: props.summary.employees,
        tone: 'text-emerald-700 dark:text-emerald-300',
        surface: 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/10 dark:bg-emerald-500/10',
        icon: Users,
    },
]);

const filterEmployeesByOutlet = (selectedOutletId: string) => {
    return props.employees.filter((employee) => {
        if (!selectedOutletId) return true;

        return employee.outlet?.id === selectedOutletId;
    });
};

const filterTemplatesByOutlet = (selectedOutletId: string) => {
    return props.shiftTemplates.filter((template) => {
        if (!selectedOutletId) return true;

        return template.outlet_id === selectedOutletId;
    });
};

const dailyEmployees = computed(() =>
    filterEmployeesByOutlet(dailyForm.outlet_id),
);
const dailyTemplates = computed(() =>
    filterTemplatesByOutlet(dailyForm.outlet_id),
);
const weeklyEmployees = computed(() =>
    filterEmployeesByOutlet(weeklyForm.outlet_id),
);
const weeklyTemplates = computed(() =>
    filterTemplatesByOutlet(weeklyForm.outlet_id),
);
const availableFormOutlets = computed(() => {
    const outletIds = new Set<string>();

    props.employees.forEach((employee) => {
        if (employee.outlet?.id) {
            outletIds.add(employee.outlet.id);
        }
    });

    props.shiftTemplates.forEach((template) => {
        outletIds.add(template.outlet_id);
    });

    return props.referenceData.outlets.filter((outlet) =>
        outletIds.has(outlet.id),
    );
});

const visibleEmployees = computed(() => {
    return props.employees.filter((employee) => {
        if (outletFilter.value && employee.outlet?.id !== outletFilter.value) {
            return false;
        }

        if (employeeFilter.value && employee.id !== employeeFilter.value) {
            return false;
        }

        return true;
    });
});

const visibleTodaySchedules = computed(() => {
    return props.todaySchedules.filter((schedule) => {
        const user = schedule.user;

        if (!user) {
            return false;
        }

        if (outletFilter.value && user.outlet?.id !== outletFilter.value) {
            return false;
        }

        if (employeeFilter.value && user.id !== employeeFilter.value) {
            return false;
        }

        return true;
    });
});

const weekRangeLabel = computed(() => {
    if (!gridDays.value.length) {
        return '-';
    }

    const first = new Date(gridDays.value[0].date);
    const last = new Date(gridDays.value[gridDays.value.length - 1].date);

    return `${new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
    }).format(first)} - ${new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(last)}`;
});

const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);

const formatDate = (value: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(new Date(value));
};

const formatTimeRange = (template?: ShiftTemplateRow | null) => {
    if (!template) return '-';

    return `${template.start_time.slice(0, 5)} - ${template.end_time.slice(0, 5)}`;
};

const getShiftTemplate = (schedule?: ScheduleRow | null) => {
    return schedule?.shiftTemplate || schedule?.shift_template || null;
};

const getScheduleForCell = (employeeId: string, date: string) => {
    return scheduleMap.value.get(employeeId)?.get(date) || null;
};

const getShiftTone = (template?: ShiftTemplateRow | null) => {
    const name = template?.name.toLowerCase() || '';

    if (name.includes('pagi')) {
        return 'border-amber-400/20 bg-amber-500/10 text-amber-200';
    }

    if (name.includes('malam')) {
        return 'border-sky-400/20 bg-sky-500/10 text-sky-200';
    }

    return 'border-emerald-400/20 bg-emerald-500/10 text-emerald-200';
};

const syncSelection = (
    selectedOutletId: string,
    currentUserId: string,
    currentTemplateId: string,
    updateUser: (value: string) => void,
    updateTemplate: (value: string) => void,
) => {
    const employeeExists = filterEmployeesByOutlet(selectedOutletId).some(
        (employee) => employee.id === currentUserId,
    );
    const templateExists = filterTemplatesByOutlet(selectedOutletId).some(
        (template) => template.id === currentTemplateId,
    );

    if (!employeeExists) {
        updateUser('');
    }

    if (!templateExists) {
        updateTemplate('');
    }
};

watch(
    () => dailyForm.outlet_id,
    (selectedOutletId) => {
        syncSelection(
            selectedOutletId,
            dailyForm.user_id,
            dailyForm.shift_template_id,
            (value) => {
                dailyForm.user_id = value;
            },
            (value) => {
                dailyForm.shift_template_id = value;
            },
        );
    },
);

watch(
    () => weeklyForm.outlet_id,
    (selectedOutletId) => {
        syncSelection(
            selectedOutletId,
            weeklyForm.user_id,
            '',
            (value) => {
                weeklyForm.user_id = value;
            },
            () => undefined,
        );

        weeklyForm.days = weeklyForm.days.map((shiftTemplateId) => {
            if (!shiftTemplateId) {
                return null;
            }

            return filterTemplatesByOutlet(selectedOutletId).some(
                (template) => template.id === shiftTemplateId,
            )
                ? shiftTemplateId
                : null;
        });
    },
);

const submitFilters = () => {
    router.get(
        route('schedules.index'),
        {
            week_start: weekStart.value || undefined,
            employee_id: employeeFilter.value || undefined,
            outlet_id: outletFilter.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    weekStart.value = props.days[0] || new Date().toISOString().slice(0, 10);
    employeeFilter.value = '';
    outletFilter.value = canChooseOutlet.value
        ? ''
        : props.referenceData.outlets[0]?.id || '';
    submitFilters();
};

const shiftWeek = (direction: number) => {
    const source = new Date(weekStart.value);
    source.setDate(source.getDate() + direction * 7);
    weekStart.value = source.toISOString().slice(0, 10);
    submitFilters();
};

const submitDaily = () => {
    dailyForm.post(route('schedules.store'), {
        preserveScroll: true,
    });
};

const submitWeekly = () => {
    weeklyForm.post(route('schedules.bulk-store'), {
        preserveScroll: true,
    });
};

const pagiTemplate = computed(() =>
    props.shiftTemplates.find((t) => t.name.toLowerCase().includes('pagi')),
);
const malamTemplate = computed(() =>
    props.shiftTemplates.find((t) => t.name.toLowerCase().includes('malam')),
);

const shiftTimesForm = useForm({
    pagi_start: pagiTemplate.value?.start_time?.slice(0, 5) || '08:00',
    pagi_end: pagiTemplate.value?.end_time?.slice(0, 5) || '17:00',
    malam_start: malamTemplate.value?.start_time?.slice(0, 5) || '15:00',
    malam_end: malamTemplate.value?.end_time?.slice(0, 5) || '24:00',
    outlet_id:
        props.filters.outlet_id || props.referenceData.outlets[0]?.id || '',
});

const submitUpdateShiftTimes = () => {
    shiftTimesForm.post(route('schedules.update-times'), {
        preserveScroll: true,
    });
};

const takeoverForm = useForm({
    outlet_id: '',
    user_id: '',
    shift_template_id: '',
    schedule_date: '',
    takeover_from_user_id: '',
});

const isTakeoverOpen = ref(false);
const activeTakeoverSchedule = ref<ScheduleRow | null>(null);

const openTakeoverModal = (schedule: ScheduleRow | null) => {
    if (!schedule) return;
    activeTakeoverSchedule.value = schedule;
    takeoverForm.outlet_id =
        schedule.user?.outlet?.id ||
        schedule.user?.outlet_id ||
        props.filters.outlet_id ||
        props.referenceData.outlets[0]?.id ||
        '';
    takeoverForm.schedule_date = schedule.schedule_date;
    takeoverForm.shift_template_id =
        schedule.shift_template?.id || schedule.shift_template_id || '';
    takeoverForm.takeover_from_user_id =
        schedule.user?.id || schedule.user_id || '';
    takeoverForm.user_id = '';
    isTakeoverOpen.value = true;
};

const submitTakeover = () => {
    takeoverForm.post(route('schedules.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isTakeoverOpen.value = false;
            activeTakeoverSchedule.value = null;
            takeoverForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Jadwal Shift" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Jadwal Shift Karyawan
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Assign shift per hari, bulk assign 1 minggu, dan lihat
                        jadwal aktif karyawan per outlet.
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

            <!-- Panel Set Jam Shift Sederhana -->
            <article
                v-if="canManage"
                class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="rounded-2xl border border-stone-200 bg-white p-3 text-orange-200 dark:border-white/10 dark:bg-slate-950/50"
                    >
                        <Clock3 class="h-5 w-5" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            Pengaturan Waktu Kerja Shift
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                        >
                            Atur jam mulai dan jam selesai untuk shift pagi dan
                            shift malam secara instan.
                        </p>
                    </div>
                </div>

                <form
                    class="mt-5 flex flex-wrap items-end gap-4"
                    @submit.prevent="submitUpdateShiftTimes"
                >
                    <div class="flex flex-1 flex-wrap gap-4">
                        <!-- Shift Pagi -->
                        <div
                            class="flex min-w-[240px] flex-1 items-center gap-2 rounded-2xl border border-stone-200 bg-white/[0.02] p-3 dark:border-white/10"
                        >
                            <span
                                class="w-20 text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                >Shift Pagi</span
                            >
                            <input
                                v-model="shiftTimesForm.pagi_start"
                                type="time"
                                class="rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-orange-400 focus:outline-none dark:border-white/10 dark:bg-slate-900 dark:text-white"
                            />
                            <span
                                class="text-xs text-stone-400 dark:text-slate-400"
                                >s/d</span
                            >
                            <input
                                v-model="shiftTimesForm.pagi_end"
                                type="time"
                                class="rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-orange-400 focus:outline-none dark:border-white/10 dark:bg-slate-900 dark:text-white"
                            />
                        </div>

                        <!-- Shift Malam -->
                        <div
                            class="flex min-w-[240px] flex-1 items-center gap-2 rounded-2xl border border-stone-200 bg-white/[0.02] p-3 dark:border-white/10"
                        >
                            <span
                                class="w-20 text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                >Shift Malam</span
                            >
                            <input
                                v-model="shiftTimesForm.malam_start"
                                type="time"
                                class="rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-orange-400 focus:outline-none dark:border-white/10 dark:bg-slate-900 dark:text-white"
                            />
                            <span
                                class="text-xs text-stone-400 dark:text-slate-400"
                                >s/d</span
                            >
                            <input
                                v-model="shiftTimesForm.malam_end"
                                type="time"
                                class="rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-orange-400 focus:outline-none dark:border-white/10 dark:bg-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                     <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-4 py-3 text-sm font-black text-slate-955 transition shadow-md shadow-orange-500/10 active:scale-[0.98]"
                        :disabled="shiftTimesForm.processing"
                    >
                        <Save class="h-4 w-4" />
                        {{
                            shiftTimesForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan Waktu'
                        }}
                    </button>
                </form>
            </article>

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

            <section
                class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div
                        class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Minggu mulai</span
                            >
                            <input
                                v-model="weekStart"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Filter Karyawan</span
                            >
                            <select
                                v-model="employeeFilter"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua karyawan</option>
                                <option
                                    v-for="employee in employees.filter(
                                        (item) =>
                                            !outletFilter ||
                                            item.outlet?.id === outletFilter,
                                    )"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }} •
                                    {{
                                        employee.outlet?.name || 'Tanpa outlet'
                                    }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Outlet</span
                            >
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option v-if="canChooseOutlet" value="">
                                    Semua outlet
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
                    </div>                     <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl border-2 border-stone-955 bg-stone-100 hover:bg-stone-200 px-4 py-3 text-sm font-black text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                            @click="shiftWeek(-1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Minggu Sebelumnya
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl border-2 border-stone-955 bg-stone-100 hover:bg-stone-200 px-4 py-3 text-sm font-black text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                            @click="shiftWeek(1)"
                        >
                            Minggu Berikutnya
                            <ChevronRight class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl border-2 border-stone-955 bg-stone-100 hover:bg-stone-200 px-4 py-3 text-sm font-black text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                            @click="clearFilters"
                        >
                            Reset
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

                <div
                    class="mt-4 flex flex-wrap items-center gap-3 text-xs text-stone-500 dark:text-slate-400"
                >
                    <span
                        class="rounded-full border border-stone-200 bg-white/[0.03] px-3 py-1.5 font-semibold dark:border-white/10"
                    >
                        Minggu aktif: {{ weekRangeLabel }}
                    </span>
                    <span
                        class="rounded-full border border-stone-200 bg-white/[0.03] px-3 py-1.5 font-semibold dark:border-white/10"
                    >
                        Total template shift: {{ shiftTemplates.length }}
                    </span>
                    <span
                        class="rounded-full border border-stone-200 bg-white/[0.03] px-3 py-1.5 font-semibold dark:border-white/10"
                    >
                        Karyawan tampil: {{ visibleEmployees.length }}
                    </span>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-5">
                    <article
                        class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3
                                    class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                                >
                                    Assign Harian
                                </h3>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                >
                                    Pilih outlet, karyawan, tanggal, lalu
                                    tempelkan template shift untuk satu hari.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-orange-200 bg-orange-50 p-3 text-orange-700 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-300"
                            >
                                <Plus class="h-5 w-5" />
                            </div>
                        </div>

                        <form
                            class="mt-5 space-y-4"
                            @submit.prevent="submitDaily"
                        >
                            <div
                                class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                            >
                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                        >Outlet</span
                                    >
                                    <select
                                        v-model="dailyForm.outlet_id"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option
                                            v-for="outlet in availableFormOutlets"
                                            :key="outlet.id"
                                            :value="outlet.id"
                                        >
                                            {{ outlet.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="dailyForm.errors.outlet_id"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ dailyForm.errors.outlet_id }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                        >Karyawan</span
                                    >
                                    <select
                                        v-model="dailyForm.user_id"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option value="">Pilih karyawan</option>
                                        <option
                                            v-for="employee in dailyEmployees"
                                            :key="employee.id"
                                            :value="employee.id"
                                        >
                                            {{ employee.name }} •
                                            {{ employee.role?.name || '-' }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="dailyForm.errors.user_id"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ dailyForm.errors.user_id }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                        >Tanggal</span
                                    >
                                    <input
                                        v-model="dailyForm.schedule_date"
                                        type="date"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    />
                                    <p
                                        v-if="dailyForm.errors.schedule_date"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ dailyForm.errors.schedule_date }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                        >Template Shift</span
                                    >
                                    <select
                                        v-model="dailyForm.shift_template_id"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option value="">Pilih template</option>
                                        <option
                                            v-for="template in dailyTemplates"
                                            :key="template.id"
                                            :value="template.id"
                                        >
                                            {{ template.name }} •
                                            {{ formatTimeRange(template) }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            dailyForm.errors.shift_template_id
                                        "
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ dailyForm.errors.shift_template_id }}
                                    </p>
                                </label>
                            </div>
                             <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="rounded-2xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-5 py-3 text-sm font-black text-slate-955 transition disabled:cursor-not-allowed disabled:opacity-60 shadow-md shadow-orange-500/10 active:scale-[0.98]"
                                    :disabled="dailyForm.processing"
                                >
                                    {{
                                        dailyForm.processing
                                            ? 'Menyimpan...'
                                            : 'Simpan Jadwal Harian'
                                    }}
                                </button>
                             </div>
                        </form>
                    </article>

                    <article
                        class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3
                                    class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                                >
                                    Bulk Assign Mingguan
                                </h3>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                >
                                    Pilih satu karyawan lalu isi template untuk
                                    7 hari dalam minggu aktif.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-sky-200 bg-sky-50 p-3 text-sky-700 dark:border-sky-400/20 dark:bg-sky-500/10 dark:text-sky-300"
                            >
                                <Layers3 class="h-5 w-5" />
                            </div>
                        </div>

                        <form
                            class="mt-5 space-y-4"
                            @submit.prevent="submitWeekly"
                        >
                            <div class="grid gap-4 md:grid-cols-3">
                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                        >Outlet</span
                                    >
                                    <select
                                        v-model="weeklyForm.outlet_id"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option
                                            v-for="outlet in availableFormOutlets"
                                            :key="outlet.id"
                                            :value="outlet.id"
                                        >
                                            {{ outlet.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="weeklyForm.errors.outlet_id"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ weeklyForm.errors.outlet_id }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                        >Karyawan</span
                                    >
                                    <select
                                        v-model="weeklyForm.user_id"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option value="">Pilih karyawan</option>
                                        <option
                                            v-for="employee in weeklyEmployees"
                                            :key="employee.id"
                                            :value="employee.id"
                                        >
                                            {{ employee.name }} •
                                            {{ employee.role?.name || '-' }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="weeklyForm.errors.user_id"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ weeklyForm.errors.user_id }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                        >Minggu Mulai</span
                                    >
                                    <input
                                        v-model="weeklyForm.week_start"
                                        type="date"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    />
                                    <p
                                        v-if="weeklyForm.errors.week_start"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ weeklyForm.errors.week_start }}
                                    </p>
                                </label>
                            </div>

                            <div
                                class="grid gap-3 lg:grid-cols-2 xl:grid-cols-4"
                            >
                                <label
                                    v-for="day in assignmentDays"
                                    :key="day.date"
                                    class="block rounded-2xl border-2 border-stone-300 bg-white p-4 dark:border-white/20 dark:bg-slate-950/40"
                                >
                                    <span
                                        class="block text-xs font-black uppercase tracking-[0.18em] text-stone-600 dark:text-slate-350"
                                    >
                                        {{ day.label }}
                                    </span>
                                    <span
                                        class="mt-1 block text-sm font-black text-stone-955 dark:text-white"
                                    >
                                        {{ day.shortDate }}
                                    </span>
                                    <select
                                        v-model="weeklyForm.days[day.index]"
                                        class="mt-3 w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option :value="null">
                                            Libur / Kosong
                                        </option>
                                        <option
                                            v-for="template in weeklyTemplates"
                                            :key="template.id"
                                            :value="template.id"
                                        >
                                            {{ template.name }} •
                                            {{ formatTimeRange(template) }}
                                        </option>
                                    </select>
                                </label>
                            </div>

                            <p
                                v-if="weeklyForm.errors.days"
                                class="text-xs text-rose-600 dark:text-rose-450 font-bold"
                            >
                                {{ weeklyForm.errors.days }}
                            </p>
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="rounded-2xl bg-sky-500 border border-sky-600 hover:bg-sky-600 px-5 py-3 text-sm font-black text-slate-955 transition disabled:cursor-not-allowed disabled:opacity-60 shadow-md shadow-sky-500/10 active:scale-[0.98]"
                                    :disabled="weeklyForm.processing"
                                >
                                    {{
                                        weeklyForm.processing
                                            ? 'Menyimpan...'
                                            : 'Simpan Bulk Mingguan'
                                    }}
                                </button>
                            </div>
                        </form>
                    </article>
                </div>

                <div class="space-y-5">
                    <article
                        class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-3 text-orange-200 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <Clock3 class="h-5 w-5" />
                            </div>
                            <div>
                                <h3
                                    class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                                >
                                    Jadwal Hari Ini
                                </h3>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                >
                                    {{
                                        formatDate(
                                            new Date()
                                                .toISOString()
                                                .slice(0, 10),
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="!visibleTodaySchedules.length"
                            class="mt-5 rounded-2xl border border-dashed border-stone-200 px-4 py-8 text-center text-sm text-stone-500 dark:border-white/10 dark:text-slate-400"
                        >
                            Belum ada jadwal aktif untuk filter hari ini.
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <article
                                v-for="schedule in visibleTodaySchedules"
                                :key="schedule.id"
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-4 dark:border-white/10"
                            >
                                <div
                                    class="flex flex-wrap items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-black text-stone-900 dark:text-white"
                                        >
                                            {{ schedule.user?.name || '-' }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                        >
                                            {{
                                                schedule.user?.outlet?.name ||
                                                'Tanpa outlet'
                                            }}
                                            •
                                            {{
                                                schedule.user?.role?.name || '-'
                                            }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                        :class="
                                            getShiftTone(
                                                getShiftTemplate(schedule),
                                            )
                                        "
                                    >
                                        {{
                                            getShiftTemplate(schedule)?.name ||
                                            'Tanpa template'
                                        }}
                                    </span>
                                </div>
                                <p
                                    class="mt-3 text-sm text-stone-600 dark:text-slate-300"
                                >
                                    {{
                                        formatTimeRange(
                                            getShiftTemplate(schedule),
                                        )
                                    }}
                                </p>
                            </article>
                        </div>
                    </article>

                    <article
                        class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-3 text-sky-200 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <Layers3 class="h-5 w-5" />
                            </div>
                            <div>
                                <h3
                                    class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                                >
                                    Template Shift Aktif
                                </h3>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                >
                                    Template dipakai juga untuk fondasi menu
                                    buka/tutup shift kasir berikutnya.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="!shiftTemplates.length"
                            class="mt-5 rounded-2xl border border-dashed border-stone-200 px-4 py-8 text-center text-sm text-stone-500 dark:border-white/10 dark:text-slate-400"
                        >
                            Belum ada template shift aktif pada outlet ini.
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <article
                                v-for="template in shiftTemplates.filter(
                                    (item) =>
                                        !outletFilter ||
                                        item.outlet_id === outletFilter,
                                )"
                                :key="template.id"
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-4 dark:border-white/10"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-black text-stone-900 dark:text-white"
                                        >
                                            {{ template.name }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                        >
                                            {{
                                                referenceData.outlets.find(
                                                    (outlet) =>
                                                        outlet.id ===
                                                        template.outlet_id,
                                                )?.name || 'Outlet'
                                            }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                        :class="getShiftTone(template)"
                                    >
                                        Aktif
                                    </span>
                                </div>
                                <p
                                    class="mt-3 text-sm text-stone-600 dark:text-slate-300"
                                >
                                    {{ formatTimeRange(template) }}
                                </p>
                            </article>
                        </div>
                    </article>
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
                            Grid Jadwal Mingguan
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                        >
                            Minggu {{ weekRangeLabel }}.
                        </p>
                    </div>
                    <span
                        class="rounded-full border border-stone-200 bg-white/[0.03] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-500 dark:border-white/10 dark:text-slate-400"
                    >
                        {{ visibleEmployees.length }} karyawan
                    </span>
                </div>

                <div
                    v-if="!visibleEmployees.length"
                    class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400"
                >
                    Tidak ada karyawan aktif untuk filter outlet / karyawan yang
                    dipilih.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10">
                        <thead class="bg-white/[0.03]">
                            <tr>
                                <th
                                    class="px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-400"
                                >
                                    Karyawan
                                </th>
                                <th
                                    v-for="day in gridDays"
                                    :key="day.date"
                                    class="min-w-[160px] px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-400"
                                >
                                    <div>{{ day.label }}</div>
                                    <div
                                        class="mt-1 text-stone-500 dark:text-slate-400"
                                    >
                                        {{ day.shortDate }}
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <tr
                                v-for="employee in visibleEmployees"
                                :key="employee.id"
                                class="align-top"
                            >
                                <td class="px-5 py-4">
                                    <p
                                        class="text-sm font-black text-stone-900 dark:text-white"
                                    >
                                        {{ employee.name }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                    >
                                        {{
                                            employee.outlet?.name ||
                                            'Tanpa outlet'
                                        }}
                                        • {{ employee.role?.name || '-' }}
                                    </p>
                                </td>
                                <td
                                    v-for="day in gridDays"
                                    :key="`${employee.id}-${day.date}`"
                                    class="px-4 py-4"
                                >
                                    <div
                                        v-if="
                                            getScheduleForCell(
                                                employee.id,
                                                day.date,
                                            )
                                        "
                                        class="group relative rounded-2xl border px-3 py-3"
                                        :class="
                                            getShiftTone(
                                                getShiftTemplate(
                                                    getScheduleForCell(
                                                        employee.id,
                                                        day.date,
                                                    ),
                                                ),
                                            )
                                        "
                                    >
                                        <p class="text-sm font-black">
                                            {{
                                                getShiftTemplate(
                                                    getScheduleForCell(
                                                        employee.id,
                                                        day.date,
                                                    ),
                                                )?.name || '-'
                                            }}
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] font-semibold uppercase tracking-[0.16em] opacity-80"
                                        >
                                            {{
                                                formatTimeRange(
                                                    getShiftTemplate(
                                                        getScheduleForCell(
                                                            employee.id,
                                                            day.date,
                                                        ),
                                                    ),
                                                )
                                            }}
                                        </p>
                                        <button
                                             v-if="canManage"
                                             type="button"
                                             class="mt-2 inline-flex w-full items-center justify-center gap-1 rounded-xl border border-stone-400 bg-stone-100 hover:bg-stone-200 px-2 py-1 text-[10px] font-black text-stone-955 transition duration-150 active:scale-[0.98] dark:border-white/20 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
                                             @click="
                                                 openTakeoverModal(
                                                     getScheduleForCell(
                                                         employee.id,
                                                         day.date,
                                                     ),
                                                 )
                                             "
                                         >
                                             <ArrowLeftRight class="h-3 w-3" />
                                             Ambil Alih
                                         </button>
                                    </div>
                                    <div
                                        v-else
                                        class="rounded-2xl border border-dashed border-stone-200 px-3 py-4 text-center text-xs font-semibold uppercase tracking-[0.16em] text-stone-400 dark:border-white/10 dark:text-slate-400"
                                    >
                                        Libur / Belum diassign
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Modal Takeover Shift -->
        <div
            v-if="isTakeoverOpen && activeTakeoverSchedule"
            class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 px-4 py-6 backdrop-blur-sm dark:bg-slate-950/80"
        >
            <div
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl border border-stone-200 bg-stone-100 p-6 shadow-[0_30px_120px_rgba(15,23,42,0.6)] dark:border-white/10 dark:bg-slate-950"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-stone-200 pb-4 dark:border-white/10"
                >
                    <div>
                        <h3
                            class="flex items-center gap-2 text-lg font-black text-stone-900 dark:text-white"
                        >
                            <ArrowLeftRight class="h-5 w-5 text-orange-400" />
                            Ambil Alih Shift Kerja
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Pindahkan tanggung jawab shift hari ini ke karyawan
                            pengganti.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-xl border-2 border-stone-955 bg-stone-50 hover:bg-stone-200 p-1.5 text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                        @click="isTakeoverOpen = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <!-- Info Shift Asal -->
                    <div
                        class="space-y-2 rounded-2xl border border-stone-200 bg-white/[0.02] p-4 dark:border-white/10"
                    >
                        <div>
                            <span
                                class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-400"
                                >Karyawan Terjadwal</span
                            >
                            <p
                                class="text-sm font-black text-stone-900 dark:text-white"
                            >
                                {{ activeTakeoverSchedule?.user?.name }}
                            </p>
                        </div>
                        <div
                            class="mt-2 grid grid-cols-2 gap-2 border-t border-stone-200 pt-2 dark:border-white/5"
                        >
                            <div>
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-400"
                                    >Shift</span
                                >
                                <p class="text-xs font-bold text-orange-200">
                                    {{
                                        activeTakeoverSchedule?.shift_template
                                            ?.name ||
                                        activeTakeoverSchedule?.shiftTemplate
                                            ?.name
                                    }}
                                </p>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-400"
                                    >Tanggal</span
                                >
                                <p
                                    class="text-xs font-bold text-stone-600 dark:text-slate-300"
                                >
                                    {{
                                        activeTakeoverSchedule
                                            ? formatDate(
                                                  activeTakeoverSchedule.schedule_date,
                                              )
                                            : ''
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Pilihan Karyawan Pengganti -->
                    <form @submit.prevent="submitTakeover" class="space-y-4">
                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Pilih Karyawan Pengganti</span
                            >
                            <select
                                v-model="takeoverForm.user_id"
                                class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900 dark:text-white"
                                required
                            >
                                <option value="">
                                    Pilih karyawan pengganti
                                </option>
                                <option
                                    v-for="employee in employees.filter(
                                        (e) =>
                                            e.id !==
                                            activeTakeoverSchedule?.user?.id,
                                    )"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }} ({{
                                        employee.role?.name || '-'
                                    }})
                                </option>
                            </select>
                        </label>

                        <div
                             class="flex items-center justify-end gap-3 border-t border-stone-200 pt-4 dark:border-white/10"
                         >
                             <button
                                 type="button"
                                 class="rounded-xl border-2 border-stone-955 bg-stone-100 hover:bg-stone-200 px-4 py-2.5 text-xs font-black text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                                 @click="isTakeoverOpen = false"
                             >
                                 Batal
                             </button>
                             <button
                                 type="submit"
                                 class="inline-flex items-center gap-1.5 rounded-xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-4 py-2.5 text-xs font-black text-stone-955 transition active:scale-[0.98] shadow-md shadow-orange-500/10"
                                 :disabled="takeoverForm.processing"
                             >
                                 <Save class="h-3.5 w-3.5" />
                                 {{
                                     takeoverForm.processing
                                         ? 'Menyimpan...'
                                         : 'Konfirmasi'
                                 }}
                             </button>
                         </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
