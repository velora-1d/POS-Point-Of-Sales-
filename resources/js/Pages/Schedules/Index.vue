<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Layers3,
    Plus,
    Users,
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

const weekStart = ref(props.filters.week_start || props.days[0] || new Date().toISOString().slice(0, 10));
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
    schedule_date: props.filters.week_start || props.days[0] || new Date().toISOString().slice(0, 10),
});

const weeklyForm = useForm<{
    outlet_id: string;
    user_id: string;
    week_start: string;
    days: Array<string | null>;
}>({
    outlet_id: outletFilter.value || props.referenceData.outlets[0]?.id || '',
    user_id: props.filters.employee_id || '',
    week_start: props.filters.week_start || props.days[0] || new Date().toISOString().slice(0, 10),
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
    const start = new Date(weeklyForm.week_start || props.days[0] || new Date().toISOString().slice(0, 10));

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
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: CalendarDays,
    },
    {
        label: 'Shift Pagi',
        value: props.summary.morning,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: Clock3,
    },
    {
        label: 'Shift Malam',
        value: props.summary.evening,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Layers3,
    },
    {
        label: 'Karyawan Terjadwal',
        value: props.summary.employees,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
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

const dailyEmployees = computed(() => filterEmployeesByOutlet(dailyForm.outlet_id));
const dailyTemplates = computed(() => filterTemplatesByOutlet(dailyForm.outlet_id));
const weeklyEmployees = computed(() => filterEmployeesByOutlet(weeklyForm.outlet_id));
const weeklyTemplates = computed(() => filterTemplatesByOutlet(weeklyForm.outlet_id));
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

    return props.referenceData.outlets.filter((outlet) => outletIds.has(outlet.id));
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
    const employeeExists = filterEmployeesByOutlet(selectedOutletId).some((employee) => employee.id === currentUserId);
    const templateExists = filterTemplatesByOutlet(selectedOutletId).some((template) => template.id === currentTemplateId);

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

            return filterTemplatesByOutlet(selectedOutletId).some((template) => template.id === shiftTemplateId)
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
    outletFilter.value = canChooseOutlet.value ? '' : props.referenceData.outlets[0]?.id || '';
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
</script>

<template>
    <Head title="Jadwal Shift" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Jadwal Shift Karyawan
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-slate-400">
                        Assign shift per hari, bulk assign 1 minggu, dan lihat jadwal aktif karyawan per outlet.
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

            <section class="grid gap-3 lg:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-2xl border p-4 shadow-[0_18px_50px_rgba(15,23,42,0.16)]"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-3xl font-black" :class="card.tone">
                                {{ card.value }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3 text-white">
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Minggu mulai</span>
                            <input
                                v-model="weekStart"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Filter Karyawan</span>
                            <select
                                v-model="employeeFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua karyawan</option>
                                <option
                                    v-for="employee in employees.filter((item) => !outletFilter || item.outlet?.id === outletFilter)"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }} • {{ employee.outlet?.name || 'Tanpa outlet' }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Outlet</span>
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option v-if="canChooseOutlet" value="">Semua outlet</option>
                                <option
                                    v-for="outlet in referenceData.outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/5"
                            @click="shiftWeek(-1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Minggu Sebelumnya
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/5"
                            @click="shiftWeek(1)"
                        >
                            Minggu Berikutnya
                            <ChevronRight class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/5"
                            @click="clearFilters"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-orange-400"
                            @click="submitFilters"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1.5 font-semibold">
                        Minggu aktif: {{ weekRangeLabel }}
                    </span>
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1.5 font-semibold">
                        Total template shift: {{ shiftTemplates.length }}
                    </span>
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1.5 font-semibold">
                        Karyawan tampil: {{ visibleEmployees.length }}
                    </span>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-5">
                    <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                    Assign Harian
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    Pilih outlet, karyawan, tanggal, lalu tempelkan template shift untuk satu hari.
                                </p>
                            </div>
                            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-3 text-orange-200">
                                <Plus class="h-5 w-5" />
                            </div>
                        </div>

                        <form class="mt-5 space-y-4" @submit.prevent="submitDaily">
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Outlet</span>
                                    <select
                                        v-model="dailyForm.outlet_id"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option
                                            v-for="outlet in availableFormOutlets"
                                            :key="outlet.id"
                                            :value="outlet.id"
                                        >
                                            {{ outlet.name }}
                                        </option>
                                    </select>
                                    <p v-if="dailyForm.errors.outlet_id" class="mt-2 text-xs text-rose-300">{{ dailyForm.errors.outlet_id }}</p>
                                </label>

                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Karyawan</span>
                                    <select
                                        v-model="dailyForm.user_id"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option value="">Pilih karyawan</option>
                                        <option
                                            v-for="employee in dailyEmployees"
                                            :key="employee.id"
                                            :value="employee.id"
                                        >
                                            {{ employee.name }} • {{ employee.role?.name || '-' }}
                                        </option>
                                    </select>
                                    <p v-if="dailyForm.errors.user_id" class="mt-2 text-xs text-rose-300">{{ dailyForm.errors.user_id }}</p>
                                </label>

                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tanggal</span>
                                    <input
                                        v-model="dailyForm.schedule_date"
                                        type="date"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    />
                                    <p v-if="dailyForm.errors.schedule_date" class="mt-2 text-xs text-rose-300">{{ dailyForm.errors.schedule_date }}</p>
                                </label>

                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Template Shift</span>
                                    <select
                                        v-model="dailyForm.shift_template_id"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option value="">Pilih template</option>
                                        <option
                                            v-for="template in dailyTemplates"
                                            :key="template.id"
                                            :value="template.id"
                                        >
                                            {{ template.name }} • {{ formatTimeRange(template) }}
                                        </option>
                                    </select>
                                    <p v-if="dailyForm.errors.shift_template_id" class="mt-2 text-xs text-rose-300">{{ dailyForm.errors.shift_template_id }}</p>
                                </label>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="dailyForm.processing"
                                >
                                    {{ dailyForm.processing ? 'Menyimpan...' : 'Simpan Jadwal Harian' }}
                                </button>
                            </div>
                        </form>
                    </article>

                    <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                    Bulk Assign Mingguan
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    Pilih satu karyawan lalu isi template untuk 7 hari dalam minggu aktif.
                                </p>
                            </div>
                            <div class="rounded-2xl border border-sky-400/20 bg-sky-500/10 p-3 text-sky-200">
                                <Layers3 class="h-5 w-5" />
                            </div>
                        </div>

                        <form class="mt-5 space-y-4" @submit.prevent="submitWeekly">
                            <div class="grid gap-4 md:grid-cols-3">
                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Outlet</span>
                                    <select
                                        v-model="weeklyForm.outlet_id"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option
                                            v-for="outlet in availableFormOutlets"
                                            :key="outlet.id"
                                            :value="outlet.id"
                                        >
                                            {{ outlet.name }}
                                        </option>
                                    </select>
                                    <p v-if="weeklyForm.errors.outlet_id" class="mt-2 text-xs text-rose-300">{{ weeklyForm.errors.outlet_id }}</p>
                                </label>

                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Karyawan</span>
                                    <select
                                        v-model="weeklyForm.user_id"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option value="">Pilih karyawan</option>
                                        <option
                                            v-for="employee in weeklyEmployees"
                                            :key="employee.id"
                                            :value="employee.id"
                                        >
                                            {{ employee.name }} • {{ employee.role?.name || '-' }}
                                        </option>
                                    </select>
                                    <p v-if="weeklyForm.errors.user_id" class="mt-2 text-xs text-rose-300">{{ weeklyForm.errors.user_id }}</p>
                                </label>

                                <label class="block">
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Minggu Mulai</span>
                                    <input
                                        v-model="weeklyForm.week_start"
                                        type="date"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    />
                                    <p v-if="weeklyForm.errors.week_start" class="mt-2 text-xs text-rose-300">{{ weeklyForm.errors.week_start }}</p>
                                </label>
                            </div>

                            <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                                <label
                                    v-for="day in assignmentDays"
                                    :key="day.date"
                                    class="block rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                >
                                    <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ day.label }}
                                    </span>
                                    <span class="mt-1 block text-sm font-bold text-white">
                                        {{ day.shortDate }}
                                    </span>
                                    <select
                                        v-model="weeklyForm.days[day.index]"
                                        class="mt-3 w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option :value="null">Libur / Kosong</option>
                                        <option
                                            v-for="template in weeklyTemplates"
                                            :key="template.id"
                                            :value="template.id"
                                        >
                                            {{ template.name }} • {{ formatTimeRange(template) }}
                                        </option>
                                    </select>
                                </label>
                            </div>

                            <p v-if="weeklyForm.errors.days" class="text-xs text-rose-300">
                                {{ weeklyForm.errors.days }}
                            </p>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="rounded-2xl bg-sky-500 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="weeklyForm.processing"
                                >
                                    {{ weeklyForm.processing ? 'Menyimpan...' : 'Simpan Bulk Mingguan' }}
                                </button>
                            </div>
                        </form>
                    </article>
                </div>

                <div class="space-y-5">
                    <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3 text-orange-200">
                                <Clock3 class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                    Jadwal Hari Ini
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ formatDate(new Date().toISOString().slice(0, 10)) }}
                                </p>
                            </div>
                        </div>

                        <div v-if="!visibleTodaySchedules.length" class="mt-5 rounded-2xl border border-dashed border-white/10 px-4 py-8 text-center text-sm text-slate-400">
                            Belum ada jadwal aktif untuk filter hari ini.
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <article
                                v-for="schedule in visibleTodaySchedules"
                                :key="schedule.id"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-white">{{ schedule.user?.name || '-' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ schedule.user?.outlet?.name || 'Tanpa outlet' }} • {{ schedule.user?.role?.name || '-' }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                        :class="getShiftTone(getShiftTemplate(schedule))"
                                    >
                                        {{ getShiftTemplate(schedule)?.name || 'Tanpa template' }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm text-slate-300">
                                    {{ formatTimeRange(getShiftTemplate(schedule)) }}
                                </p>
                            </article>
                        </div>
                    </article>

                    <article class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3 text-sky-200">
                                <Layers3 class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                    Template Shift Aktif
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    Template dipakai juga untuk fondasi menu buka/tutup shift kasir berikutnya.
                                </p>
                            </div>
                        </div>

                        <div v-if="!shiftTemplates.length" class="mt-5 rounded-2xl border border-dashed border-white/10 px-4 py-8 text-center text-sm text-slate-400">
                            Belum ada template shift aktif pada outlet ini.
                        </div>

                        <div v-else class="mt-5 space-y-3">
                            <article
                                v-for="template in shiftTemplates.filter((item) => !outletFilter || item.outlet_id === outletFilter)"
                                :key="template.id"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-white">{{ template.name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ referenceData.outlets.find((outlet) => outlet.id === template.outlet_id)?.name || 'Outlet' }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                        :class="getShiftTone(template)"
                                    >
                                        Aktif
                                    </span>
                                </div>
                                <p class="mt-3 text-sm text-slate-300">
                                    {{ formatTimeRange(template) }}
                                </p>
                            </article>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-950/70 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                            Grid Jadwal Mingguan
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Minggu {{ weekRangeLabel }}.
                        </p>
                    </div>
                    <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                        {{ visibleEmployees.length }} karyawan
                    </span>
                </div>

                <div v-if="!visibleEmployees.length" class="px-5 py-10 text-center text-sm text-slate-400">
                    Tidak ada karyawan aktif untuk filter outlet / karyawan yang dipilih.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10">
                        <thead class="bg-white/[0.03]">
                            <tr>
                                <th class="px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    Karyawan
                                </th>
                                <th
                                    v-for="day in gridDays"
                                    :key="day.date"
                                    class="min-w-[160px] px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500"
                                >
                                    <div>{{ day.label }}</div>
                                    <div class="mt-1 text-slate-400">{{ day.shortDate }}</div>
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
                                    <p class="text-sm font-black text-white">{{ employee.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ employee.outlet?.name || 'Tanpa outlet' }} • {{ employee.role?.name || '-' }}
                                    </p>
                                </td>
                                <td
                                    v-for="day in gridDays"
                                    :key="`${employee.id}-${day.date}`"
                                    class="px-4 py-4"
                                >
                                    <div
                                        v-if="getScheduleForCell(employee.id, day.date)"
                                        class="rounded-2xl border px-3 py-3"
                                        :class="getShiftTone(getShiftTemplate(getScheduleForCell(employee.id, day.date)))"
                                    >
                                        <p class="text-sm font-black">
                                            {{ getShiftTemplate(getScheduleForCell(employee.id, day.date))?.name || '-' }}
                                        </p>
                                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.16em] opacity-80">
                                            {{ formatTimeRange(getShiftTemplate(getScheduleForCell(employee.id, day.date))) }}
                                        </p>
                                    </div>
                                    <div
                                        v-else
                                        class="rounded-2xl border border-dashed border-white/10 px-3 py-4 text-center text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"
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
    </AuthenticatedLayout>
</template>
