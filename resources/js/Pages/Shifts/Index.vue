<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    Clock3,
    LogIn,
    LogOut,
    Wallet,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const page = usePage<any>();
const isOwner = computed(() => page.props.auth?.user?.role?.type === 'owner');

interface OutletOption {
    id: string;
    name: string;
}

interface CashierOption {
    id: string;
    name: string;
    role?: string | null;
}

interface ShiftTemplateOption {
    id: string;
    outlet_id: string;
    name: string;
    start_time: string;
    end_time: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ShiftSummary {
    total_orders: number;
    total_revenue: number;
    total_discount: number;
    active_orders: number;
    expected_cash: number;
    breakdown: {
        cash: number;
        qris: number;
        debit: number;
        ewallet: number;
        kasbon: number;
    };
}

interface ShiftCashRecap {
    total_shifts: number;
    total_orders: number;
    total_revenue: number;
    expected_cash: number;
    actual_cash: number;
    cash_difference: number;
    difference_count: number;
}

interface ShiftRow {
    id: string;
    status: string;
    opened_at?: string | null;
    closed_at?: string | null;
    opening_cash: number;
    expected_cash?: number | null;
    actual_cash?: number | null;
    cash_difference?: number | null;
    notes?: string | null;
    can_close?: boolean;
    user?: {
        id: string;
        name: string;
        role?: string | null;
    } | null;
    outlet?: {
        id: string;
        name: string;
    } | null;
    shift_template?: {
        id: string;
        name: string;
        start_time: string;
        end_time: string;
    } | null;
    summary: ShiftSummary;
}

const props = defineProps<{
    activeShift?: ShiftRow | null;
    lastClosedShift?: ShiftRow | null;
    todaySchedule?: {
        id: string;
        shift_template_id: string;
        shift_template_name: string;
        start_time: string;
        end_time: string;
    } | null;
    shiftTemplates: ShiftTemplateOption[];
    history: {
        data: ShiftRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    cashRecap: ShiftCashRecap;
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
        cashiers: CashierOption[];
    };
    canManage: boolean;
    canRead: boolean;
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

const openShiftForm = useForm<{
    shift_template_id: string;
    opening_cash: number | string;
    notes: string;
}>({
    shift_template_id: props.todaySchedule?.shift_template_id || '',
    opening_cash:
        props.lastClosedShift?.actual_cash ??
        props.lastClosedShift?.expected_cash ??
        0,
    notes: '',
});

const closeShiftForm = useForm<{
    actual_cash: number | string;
    notes: string;
}>({
    actual_cash:
        props.activeShift?.summary.expected_cash ??
        props.activeShift?.opening_cash ??
        0,
    notes: '',
});

const actualQris = ref<number | string>('');
const actualDebit = ref<number | string>('');
const actualEwallet = ref<number | string>('');
const userNotes = ref('');
const hasNonCashDiff = ref(false);

watch(
    () => props.activeShift,
    (newShift: ShiftRow | null | undefined) => {
        if (newShift) {
            closeShiftForm.actual_cash =
                newShift.summary.expected_cash ?? newShift.opening_cash ?? 0;
            actualQris.value = newShift.summary.breakdown.qris ?? 0;
            actualDebit.value = newShift.summary.breakdown.debit ?? 0;
            actualEwallet.value = newShift.summary.breakdown.ewallet ?? 0;
        }
    },
    { immediate: true },
);

const summaryCards = computed(() => {
    const active = props.activeShift;
    const lastClosed = props.lastClosedShift;

    return [
        {
            label: 'Shift Aktif',
            value: active ? 'Aktif' : 'Tidak Ada',
            tone: active
                ? 'text-emerald-700 dark:text-emerald-300'
                : 'text-stone-900 dark:text-slate-300',
            surface: active
                ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/10 dark:bg-emerald-500/10'
                : 'border-stone-200 bg-white dark:border-white/10 dark:bg-white/[0.03]',
            icon: CheckCircle2,
        },
        {
            label: 'Order Terbayar',
            value: active?.summary.total_orders ?? 0,
            tone: 'text-stone-900 dark:text-white',
            surface: 'border-stone-200 bg-white dark:border-white/10 dark:bg-white/[0.03]',
            icon: CalendarDays,
        },
        {
            label: 'Revenue Shift',
            value: formatPrice(active?.summary.total_revenue ?? 0),
            tone: 'text-sky-700 dark:text-sky-300',
            surface: 'border-sky-200 bg-sky-50 dark:border-sky-500/10 dark:bg-sky-500/10',
            icon: Wallet,
        },
        {
            label: 'Carry Over Saran',
            value: formatPrice(
                lastClosed?.actual_cash ?? lastClosed?.expected_cash ?? 0,
            ),
            tone: 'text-amber-700 dark:text-amber-300',
            surface: 'border-amber-200 bg-amber-50 dark:border-amber-500/10 dark:bg-amber-500/10',
            icon: Clock3,
        },
    ];
});

const canChooseOutlet = computed(() => props.referenceData.outlets.length > 1);
const canChooseCashier = computed(
    () => props.referenceData.cashiers.length > 1,
);
const openShiftGeneralError = computed(
    () => (openShiftForm.errors as Record<string, string>)['shift'] || '',
);
const closeShiftGeneralError = computed(
    () => (closeShiftForm.errors as Record<string, string>)['shift'] || '',
);

const cashRecapCards = computed(() => [
    {
        label: 'Shift Tertutup',
        value: props.cashRecap.total_shifts,
        helper: `${props.cashRecap.total_orders} order terbayar`,
        tone: 'text-stone-900 dark:text-white',
        surface: 'border-stone-200 bg-white dark:border-white/10 dark:bg-white/[0.03]',
    },
    {
        label: 'Revenue Tersimpan',
        value: formatPrice(props.cashRecap.total_revenue),
        helper: 'Akumulasi laporan cash report',
        tone: 'text-sky-700 dark:text-sky-300',
        surface: 'border-sky-200 bg-sky-50 dark:border-sky-500/10 dark:bg-sky-500/10',
    },
    {
        label: 'Ekspektasi Kas',
        value: formatPrice(props.cashRecap.expected_cash),
        helper: 'Saldo awal + cash masuk',
        tone: 'text-emerald-700 dark:text-emerald-300',
        surface: 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/10 dark:bg-emerald-500/10',
    },
    {
        label: 'Kas Aktual',
        value: formatPrice(props.cashRecap.actual_cash),
        helper: 'Input saat tutup shift',
        tone: 'text-amber-700 dark:text-amber-300',
        surface: 'border-amber-200 bg-amber-50 dark:border-amber-500/10 dark:bg-amber-500/10',
    },
    {
        label: 'Selisih Bersih',
        value: formatPrice(props.cashRecap.cash_difference),
        helper: `${props.cashRecap.difference_count} shift perlu follow up`,
        tone: differenceClass(props.cashRecap.cash_difference),
        surface:
            props.cashRecap.difference_count > 0
                ? 'border-rose-200 bg-rose-50 dark:border-rose-500/10 dark:bg-rose-500/10'
                : 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/10 dark:bg-emerald-500/10',
    },
]);

const currentTemplateLabel = computed(() => {
    if (props.activeShift?.shift_template) {
        return `${props.activeShift.shift_template.name} • ${props.activeShift.shift_template.start_time.slice(0, 5)} - ${props.activeShift.shift_template.end_time.slice(0, 5)}`;
    }

    if (props.todaySchedule) {
        return `${props.todaySchedule.shift_template_name} • ${props.todaySchedule.start_time.slice(0, 5)} - ${props.todaySchedule.end_time.slice(0, 5)}`;
    }

    return 'Tanpa template shift';
});

const formatPrice = (value: number | string | null | undefined) => {
    const amount = Number(value || 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDateTime = (value?: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const statusClass = (status: string) => {
    return status === 'active'
        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:text-emerald-200'
        : 'border-slate-500/20 bg-slate-500/10 text-stone-600 dark:text-slate-300';
};

const differenceClass = (difference?: number | null) => {
    const value = Number(difference || 0);

    if (value === 0) {
        return 'text-emerald-600 dark:text-emerald-300';
    }

    return value > 0 ? 'text-sky-600 dark:text-sky-300' : 'text-rose-600 dark:text-rose-300';
};

const hasCashDifference = (shift: ShiftRow) => {
    return (
        shift.status === 'closed' &&
        Math.abs(Number(shift.cash_difference || 0)) >= 1
    );
};

const submitFilters = () => {
    router.get(
        route('shifts.index'),
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

const submitOpenShift = () => {
    if (isOwner.value) {
        if (
            !confirm(
                'Anda masuk sebagai Owner. Apakah Anda yakin ingin membuka shift kasir baru secara manual?',
            )
        ) {
            return;
        }
    }
    openShiftForm.post(route('shifts.open'), {
        preserveScroll: true,
    });
};

const submitCloseShift = () => {
    if (!props.activeShift) return;

    if (isOwner.value) {
        if (
            !confirm(
                'Anda masuk sebagai Owner. Apakah Anda yakin ingin menutup shift kasir aktif ini secara manual?',
            )
        ) {
            return;
        }
    }

    const expectedQris = Number(props.activeShift.summary.breakdown.qris || 0);
    const expectedDebit = Number(
        props.activeShift.summary.breakdown.debit || 0,
    );
    const expectedEwallet = Number(
        props.activeShift.summary.breakdown.ewallet || 0,
    );

    if (!hasNonCashDiff.value) {
        actualQris.value = expectedQris;
        actualDebit.value = expectedDebit;
        actualEwallet.value = expectedEwallet;
    }

    const diffQris = Number(actualQris.value || 0) - expectedQris;
    const diffDebit = Number(actualDebit.value || 0) - expectedDebit;
    const diffEwallet = Number(actualEwallet.value || 0) - expectedEwallet;

    let verificationText = `[Verifikasi Non-Cash]\n`;
    verificationText += `- QRIS: Aktual Rp ${Number(actualQris.value || 0).toLocaleString('id-ID')} vs Ekspektasi Rp ${expectedQris.toLocaleString('id-ID')} (Selisih: Rp ${diffQris.toLocaleString('id-ID')})\n`;
    verificationText += `- Debit: Aktual Rp ${Number(actualDebit.value || 0).toLocaleString('id-ID')} vs Ekspektasi Rp ${expectedDebit.toLocaleString('id-ID')} (Selisih: Rp ${diffDebit.toLocaleString('id-ID')})\n`;
    verificationText += `- E-Wallet: Aktual Rp ${Number(actualEwallet.value || 0).toLocaleString('id-ID')} vs Ekspektasi Rp ${expectedEwallet.toLocaleString('id-ID')} (Selisih: Rp ${diffEwallet.toLocaleString('id-ID')})\n`;

    if (userNotes.value.trim()) {
        closeShiftForm.notes = `${verificationText}\nCatatan Tambahan:\n${userNotes.value.trim()}`;
    } else {
        closeShiftForm.notes = verificationText;
    }

    closeShiftForm.post(route('shifts.close', props.activeShift.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Shift Kasir & Rekap Kas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Shift Kasir & Rekap Kas
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Buka shift dengan saldo awal, pantau ringkasan transaksi
                        real-time, lalu tutup shift dan baca rekap kas per
                        periode langsung dari histori shift.
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
                                class="mt-3 text-2xl font-black"
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

            <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                <article
                    class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                            >
                                {{
                                    activeShift
                                        ? 'Shift Berjalan'
                                        : 'Buka Shift Baru'
                                }}
                            </h3>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                {{
                                    activeShift
                                        ? 'Ringkasan shift aktif outlet saat ini.'
                                        : 'Kasir wajib buka shift sebelum membuat transaksi baru.'
                                }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-orange-200 dark:border-white/10 dark:bg-slate-950/50"
                        >
                            <Wallet class="h-5 w-5" />
                        </div>
                    </div>

                    <div v-if="activeShift" class="mt-5 space-y-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] p-4 dark:border-white/10"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="text-base font-black text-stone-900 dark:text-white"
                                    >
                                        {{ activeShift.user?.name || '-' }}
                                    </p>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                        :class="statusClass(activeShift.status)"
                                    >
                                        {{ activeShift.status }}
                                    </span>
                                </div>
                                <p
                                    class="mt-2 text-sm text-stone-600 dark:text-slate-300"
                                >
                                    {{ activeShift.outlet?.name || '-' }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                                >
                                    Dibuka:
                                    {{ formatDateTime(activeShift.opened_at) }}
                                </p>
                                <p
                                    class="mt-3 text-sm text-stone-600 dark:text-slate-300"
                                >
                                    {{ currentTemplateLabel }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] p-4 dark:border-white/10"
                            >
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div
                                        class="rounded-2xl border border-stone-200 bg-white px-3 py-3 dark:border-white/10 dark:bg-slate-950/50"
                                    >
                                        <p
                                            class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                        >
                                            Saldo Awal
                                        </p>
                                        <p
                                            class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                        >
                                            {{
                                                formatPrice(
                                                    activeShift.opening_cash,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div
                                        class="rounded-2xl border border-stone-200 bg-white px-3 py-3 dark:border-white/10 dark:bg-slate-950/50"
                                    >
                                        <p
                                            class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                        >
                                            Ekspektasi Cash
                                        </p>
                                        <p
                                            class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                        >
                                            {{
                                                formatPrice(
                                                    activeShift.summary
                                                        .expected_cash,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="mt-3 rounded-2xl border border-stone-200 bg-white px-3 py-3 dark:border-white/10 dark:bg-slate-950/50"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                    >
                                        Order Belum Final
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{ activeShift.summary.active_orders }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Order Terbayar
                                </p>
                                <p
                                    class="mt-2 text-2xl font-black text-stone-900 dark:text-white"
                                >
                                    {{ activeShift.summary.total_orders }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Revenue
                                </p>
                                <p
                                    class="mt-2 text-2xl font-black text-sky-300"
                                >
                                    {{
                                        formatPrice(
                                            activeShift.summary.total_revenue,
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Cash Masuk
                                </p>
                                <p
                                    class="mt-2 text-2xl font-black text-emerald-300"
                                >
                                    {{
                                        formatPrice(
                                            activeShift.summary.breakdown.cash,
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    QRIS Masuk
                                </p>
                                <p
                                    class="mt-2 text-2xl font-black text-orange-300"
                                >
                                    {{
                                        formatPrice(
                                            activeShift.summary.breakdown.qris,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/50"
                        >
                            <div class="grid gap-3 md:grid-cols-3">
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                    >
                                        Debit
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{
                                            formatPrice(
                                                activeShift.summary.breakdown
                                                    .debit,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                    >
                                        E-Wallet
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{
                                            formatPrice(
                                                activeShift.summary.breakdown
                                                    .ewallet,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                    >
                                        Kasbon
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        {{
                                            formatPrice(
                                                activeShift.summary.breakdown
                                                    .kasbon,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form
                            v-if="canManage && activeShift.can_close"
                            class="rounded-2xl border border-stone-200 bg-white/[0.03] p-4 dark:border-white/10"
                            @submit.prevent="submitCloseShift"
                        >
                            <div class="flex items-center gap-2 text-sky-200">
                                <LogOut class="h-4 w-4" />
                                <p class="text-sm font-bold">Tutup Shift</p>
                            </div>
                            <!-- Verification Grid for Cash and Payments -->
                            <div class="mt-4 space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <!-- Cash -->
                                    <div
                                        class="space-y-3 rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/40"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <p
                                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500 dark:text-slate-400"
                                            >
                                                Uang Tunai (Cash)
                                            </p>
                                            <span
                                                class="rounded border border-stone-200 bg-stone-100 px-2 py-0.5 text-[10px] font-bold uppercase text-stone-400 dark:border-white/5 dark:bg-slate-950 dark:text-slate-400"
                                                >Tunai</span
                                            >
                                        </div>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <span
                                                    class="text-[10px] font-medium text-stone-400 dark:text-slate-400"
                                                    >Ekspektasi Sistem</span
                                                >
                                                <p
                                                    class="mt-1 text-sm font-black text-stone-900 dark:text-white"
                                                >
                                                    {{
                                                        formatPrice(
                                                            activeShift.summary
                                                                .expected_cash,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                            <label class="block">
                                                <span
                                                    class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-400"
                                                    >Aktual di Laci</span
                                                >
                                                <input
                                                    v-model="
                                                        closeShiftForm.actual_cash
                                                    "
                                                    type="number"
                                                    min="0"
                                                    step="1000"
                                                    class="w-full rounded-xl border-2 border-stone-300 bg-white px-3 py-2 text-xs text-stone-950 focus:border-sky-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                                />
                                                <p
                                                    v-if="
                                                        closeShiftForm.errors
                                                            .actual_cash
                                                    "
                                                    class="mt-1 text-[10px] text-rose-600 dark:text-rose-450 font-bold"
                                                >
                                                    {{
                                                        closeShiftForm.errors
                                                            .actual_cash
                                                    }}
                                                </p>
                                            </label>
                                        </div>
                                        <div
                                            class="flex items-center justify-between border-t border-stone-200 pt-2 dark:border-white/5"
                                        >
                                            <span
                                                class="text-[9px] font-bold uppercase text-stone-400 dark:text-slate-400"
                                                >Selisih Cash</span
                                            >
                                            <p
                                                class="text-sm font-black"
                                                :class="
                                                    differenceClass(
                                                        Number(
                                                            closeShiftForm.actual_cash ||
                                                                0,
                                                        ) -
                                                            Number(
                                                                activeShift
                                                                    .summary
                                                                    .expected_cash ||
                                                                    0,
                                                            ),
                                                    )
                                                "
                                            >
                                                {{
                                                    formatPrice(
                                                        Number(
                                                            closeShiftForm.actual_cash ||
                                                                0,
                                                        ) -
                                                            Number(
                                                                activeShift
                                                                    .summary
                                                                    .expected_cash ||
                                                                    0,
                                                            ),
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Quick Summary Non-Cash Info -->
                                    <div
                                        class="flex flex-col justify-between rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/40"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <p
                                                class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500 dark:text-slate-400"
                                            >
                                                Total Pembayaran Digital
                                            </p>
                                            <span
                                                class="rounded border border-stone-200 bg-stone-100 px-2 py-0.5 text-[10px] font-bold uppercase text-stone-400 dark:border-white/5 dark:bg-slate-950 dark:text-slate-400"
                                                >Digital</span
                                            >
                                        </div>
                                        <div
                                            class="grid grid-cols-3 gap-2 py-2"
                                        >
                                            <div>
                                                <span
                                                    class="text-[9px] font-bold uppercase text-stone-400 dark:text-slate-400"
                                                    >QRIS</span
                                                >
                                                <p
                                                    class="mt-0.5 text-xs font-bold text-stone-900 dark:text-white"
                                                >
                                                    {{
                                                        formatPrice(
                                                            activeShift.summary
                                                                .breakdown.qris,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                            <div>
                                                <span
                                                    class="text-[9px] font-bold uppercase text-stone-400 dark:text-slate-400"
                                                    >Debit</span
                                                >
                                                <p
                                                    class="mt-0.5 text-xs font-bold text-stone-900 dark:text-white"
                                                >
                                                    {{
                                                        formatPrice(
                                                            activeShift.summary
                                                                .breakdown
                                                                .debit,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                            <div>
                                                <span
                                                    class="text-[9px] font-bold uppercase text-stone-400 dark:text-slate-400"
                                                    >E-Wallet</span
                                                >
                                                <p
                                                    class="mt-0.5 text-xs font-bold text-stone-900 dark:text-white"
                                                >
                                                    {{
                                                        formatPrice(
                                                            activeShift.summary
                                                                .breakdown
                                                                .ewallet,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 border-t border-stone-200 pt-2 dark:border-white/5"
                                        >
                                            <input
                                                v-model="hasNonCashDiff"
                                                type="checkbox"
                                                class="rounded border-stone-200 bg-white text-sky-500 focus:ring-sky-400 dark:border-white/20 dark:bg-slate-900"
                                            />
                                            <span
                                                class="text-[10px] font-semibold text-slate-300"
                                                >Ada selisih pada mesin
                                                EDC/QRIS</span
                                            >
                                        </label>
                                    </div>
                                </div>

                                <!-- Grid Input Non-Cash (Hanya muncul jika checkbox dicentang) -->
                                <div
                                    v-if="hasNonCashDiff"
                                    class="grid gap-4 border-t border-stone-200 pt-4 dark:border-white/5 sm:grid-cols-3"
                                >
                                    <!-- QRIS -->
                                    <div
                                        class="space-y-3 rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/40"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-500 dark:text-slate-400"
                                            >
                                                QRIS
                                            </p>
                                            <p
                                                class="mt-0.5 text-[11px] text-stone-500 dark:text-slate-400"
                                            >
                                                Sistem:
                                                {{
                                                    formatPrice(
                                                        activeShift.summary
                                                            .breakdown.qris,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <label class="block">
                                            <span
                                                class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.1em] text-stone-400 dark:text-slate-400"
                                                >Aktual EDC</span
                                            >
                                            <input
                                                v-model="actualQris"
                                                type="number"
                                                min="0"
                                                step="1000"
                                                class="w-full rounded-xl border-2 border-stone-300 bg-white px-3 py-2 text-xs text-stone-950 focus:border-sky-500 focus:outline-none dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                            />
                                        </label>
                                        <div
                                            class="flex items-center justify-between border-t border-stone-200 pt-2 dark:border-white/5"
                                        >
                                            <span
                                                class="text-[9px] font-bold uppercase text-stone-400 dark:text-slate-400"
                                                >Selisih QRIS</span
                                            >
                                            <p
                                                class="text-xs font-black"
                                                :class="
                                                    differenceClass(
                                                        Number(
                                                            actualQris || 0,
                                                        ) -
                                                            Number(
                                                                activeShift
                                                                    .summary
                                                                    .breakdown
                                                                    .qris || 0,
                                                            ),
                                                    )
                                                "
                                            >
                                                {{
                                                    formatPrice(
                                                        Number(
                                                            actualQris || 0,
                                                        ) -
                                                            Number(
                                                                activeShift
                                                                    .summary
                                                                    .breakdown
                                                                    .qris || 0,
                                                            ),
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Debit -->
                                    <div
                                        class="space-y-3 rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/40"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-500 dark:text-slate-400"
                                            >
                                                Debit (EDC)
                                            </p>
                                            <p
                                                class="mt-0.5 text-[11px] text-stone-500 dark:text-slate-400"
                                            >
                                                Sistem:
                                                {{
                                                    formatPrice(
                                                        activeShift.summary
                                                            .breakdown.debit,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <label class="block">
                                            <span
                                                class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.1em] text-stone-400 dark:text-slate-400"
                                                >Aktual Kartu</span
                                            >
                                            <input
                                                v-model="actualDebit"
                                                type="number"
                                                min="0"
                                                step="1000"
                                                class="w-full rounded-xl border-2 border-stone-300 bg-white px-3 py-2 text-xs text-stone-955 focus:border-sky-500 focus:outline-none dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                            />
                                        </label>
                                        <div
                                            class="flex items-center justify-between border-t border-stone-200 pt-2 dark:border-white/5"
                                        >
                                            <span
                                                class="text-[9px] font-bold uppercase text-stone-400 dark:text-slate-400"
                                                >Selisih Debit</span
                                            >
                                            <p
                                                class="text-xs font-black"
                                                :class="
                                                    differenceClass(
                                                        Number(
                                                            actualDebit || 0,
                                                        ) -
                                                            Number(
                                                                activeShift
                                                                    .summary
                                                                    .breakdown
                                                                    .debit || 0,
                                                            ),
                                                    )
                                                "
                                            >
                                                {{
                                                    formatPrice(
                                                        Number(
                                                            actualDebit || 0,
                                                        ) -
                                                            Number(
                                                                activeShift
                                                                    .summary
                                                                    .breakdown
                                                                    .debit || 0,
                                                            ),
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- E-Wallet -->
                                    <div
                                        class="space-y-3 rounded-2xl border border-stone-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/40"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-500 dark:text-slate-400"
                                            >
                                                E-Wallet
                                            </p>
                                            <p
                                                class="mt-0.5 text-[11px] text-stone-500 dark:text-slate-400"
                                            >
                                                Sistem:
                                                {{
                                                    formatPrice(
                                                        activeShift.summary
                                                            .breakdown.ewallet,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <label class="block">
                                            <span
                                                class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.1em] text-stone-400 dark:text-slate-400"
                                                >Aktual Dompet</span
                                            >
                                            <input
                                                v-model="actualEwallet"
                                                type="number"
                                                min="0"
                                                step="1000"
                                                class="w-full rounded-xl border-2 border-stone-300 bg-white px-3 py-2 text-xs text-stone-955 focus:border-sky-500 focus:outline-none dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                            />
                                        </label>
                                        <div
                                            class="flex items-center justify-between border-t border-stone-200 pt-2 dark:border-white/5"
                                        >
                                            <span
                                                class="text-[9px] font-bold uppercase text-stone-400 dark:text-slate-400"
                                                >Selisih E-Wallet</span
                                            >
                                            <p
                                                class="text-xs font-black"
                                                :class="
                                                    differenceClass(
                                                        Number(
                                                            actualEwallet || 0,
                                                        ) -
                                                            Number(
                                                                activeShift
                                                                    .summary
                                                                    .breakdown
                                                                    .ewallet ||
                                                                    0,
                                                            ),
                                                    )
                                                "
                                            >
                                                {{
                                                    formatPrice(
                                                        Number(
                                                            actualEwallet || 0,
                                                        ) -
                                                            Number(
                                                                activeShift
                                                                    .summary
                                                                    .breakdown
                                                                    .ewallet ||
                                                                    0,
                                                            ),
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <label class="mt-4 block">
                                <span
                                    class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                    >Catatan Tambahan Shift</span
                                >
                                <textarea
                                    v-model="userNotes"
                                    rows="3"
                                    placeholder="Catatan kendala, kondisi mesin kasir, atau selisih uang jika ada"
                                    class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 placeholder:text-stone-500 focus:border-sky-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                />
                                <p
                                    v-if="closeShiftForm.errors.notes"
                                    class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                >
                                    {{ closeShiftForm.errors.notes }}
                                </p>
                                <p
                                    v-if="closeShiftGeneralError"
                                    class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                >
                                    {{ closeShiftGeneralError }}
                                </p>
                            </label>

                            <div class="mt-4 flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-sky-500 border border-sky-600 hover:bg-sky-600 px-5 py-3 text-sm font-black text-stone-955 transition disabled:cursor-not-allowed disabled:opacity-60 shadow-md shadow-sky-500/20 active:scale-[0.98]"
                                    :disabled="closeShiftForm.processing"
                                >
                                    <LogOut class="h-4 w-4" />
                                    {{
                                        closeShiftForm.processing
                                            ? 'Menyimpan...'
                                            : 'Tutup Shift Sekarang'
                                    }}
                                </button>
                            </div>
                        </form>

                        <div
                            v-else-if="canManage && !activeShift.can_close"
                            class="rounded-2xl border border-amber-400/15 bg-amber-500/10 px-4 py-4 text-sm text-amber-200"
                        >
                            Shift aktif saat ini dibuka oleh kasir lain. Kasir
                            biasa tidak bisa menutup shift milik user lain.
                        </div>
                    </div>

                    <form
                        v-else-if="canManage"
                        class="mt-5 space-y-5"
                        @submit.prevent="submitOpenShift"
                    >
                        <div
                            class="rounded-2xl border border-stone-200 bg-white/[0.03] p-4 dark:border-white/10"
                        >
                            <p
                                class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-400"
                            >
                                Saran Jadwal Hari Ini
                            </p>
                            <div v-if="todaySchedule" class="mt-3 space-y-1">
                                <p
                                    class="text-base font-black text-stone-900 dark:text-white"
                                >
                                    {{ todaySchedule.shift_template_name }}
                                </p>
                                <p
                                    class="text-sm text-stone-600 dark:text-slate-300"
                                >
                                    {{ todaySchedule.start_time.slice(0, 5) }} -
                                    {{ todaySchedule.end_time.slice(0, 5) }}
                                </p>
                            </div>
                            <p
                                v-else
                                class="mt-3 text-sm text-stone-500 dark:text-slate-400"
                            >
                                Tidak ada jadwal shift terpasang hari ini. Anda
                                tetap bisa buka shift manual.
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span
                                    class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                    >Template Shift</span
                                >
                                <select
                                    v-model="openShiftForm.shift_template_id"
                                    class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                >
                                    <option value="">
                                        Manual / Tanpa Template
                                    </option>
                                    <option
                                        v-for="template in shiftTemplates"
                                        :key="template.id"
                                        :value="template.id"
                                    >
                                        {{ template.name }} •
                                        {{ template.start_time.slice(0, 5) }} -
                                        {{ template.end_time.slice(0, 5) }}
                                    </option>
                                </select>
                                <p
                                    v-if="
                                        openShiftForm.errors.shift_template_id
                                    "
                                    class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                >
                                    {{ openShiftForm.errors.shift_template_id }}
                                </p>
                            </label>

                            <label class="block">
                                <span
                                    class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                    >Saldo Awal Kas</span
                                >
                                <input
                                    v-model="openShiftForm.opening_cash"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                />
                                <p
                                    v-if="openShiftForm.errors.opening_cash"
                                    class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                >
                                    {{ openShiftForm.errors.opening_cash }}
                                </p>
                            </label>
                        </div>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-800 dark:text-stone-200"
                                >Catatan Buka Shift</span
                            >
                            <textarea
                                v-model="openShiftForm.notes"
                                rows="3"
                                placeholder="Contoh: carry over dari shift pagi, laci kas sudah dihitung bersama."
                                class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 placeholder:text-stone-500 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="openShiftForm.errors.notes"
                                class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                            >
                                {{ openShiftForm.errors.notes }}
                            </p>
                            <p
                                v-if="openShiftGeneralError"
                                class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                            >
                                {{ openShiftGeneralError }}
                            </p>
                        </label>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-5 py-3 text-sm font-black text-slate-955 transition disabled:cursor-not-allowed disabled:opacity-60 shadow-md shadow-orange-500/20 active:scale-[0.98]"
                                :disabled="openShiftForm.processing"
                            >
                                <LogIn class="h-4 w-4" />
                                {{
                                    openShiftForm.processing
                                        ? 'Membuka...'
                                        : 'Buka Shift Sekarang'
                                }}
                            </button>
                        </div>
                    </form>

                    <div
                        v-else
                        class="mt-5 rounded-2xl border border-dashed border-stone-200 px-4 py-10 text-center text-sm text-stone-500 dark:border-white/10 dark:text-slate-400"
                    >
                        Role ini hanya bisa memantau shift dan tidak bisa
                        buka/tutup shift.
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-sky-200 dark:border-white/10 dark:bg-slate-950/50"
                        >
                            <LogOut class="h-5 w-5" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                            >
                                Shift Terakhir
                            </h3>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                Referensi saldo carry over dan histori tutup
                                shift paling baru.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="!lastClosedShift"
                        class="mt-5 rounded-2xl border border-dashed border-stone-200 px-4 py-10 text-center text-sm text-stone-500 dark:border-white/10 dark:text-slate-400"
                    >
                        Belum ada histori shift tertutup pada outlet/filter ini.
                    </div>

                    <div v-else class="mt-5 space-y-4">
                        <div
                            class="rounded-2xl border border-stone-200 bg-white/[0.03] p-4 dark:border-white/10"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <p
                                    class="text-base font-black text-stone-900 dark:text-white"
                                >
                                    {{ lastClosedShift.user?.name || '-' }}
                                </p>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="statusClass(lastClosedShift.status)"
                                >
                                    {{ lastClosedShift.status }}
                                </span>
                            </div>
                            <p
                                class="mt-1 text-sm text-stone-600 dark:text-slate-300"
                            >
                                {{ lastClosedShift.outlet?.name || '-' }}
                            </p>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                Ditutup:
                                {{ formatDateTime(lastClosedShift.closed_at) }}
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Saldo Awal
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{
                                        formatPrice(
                                            lastClosedShift.opening_cash,
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Ekspektasi
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{
                                        formatPrice(
                                            lastClosedShift.expected_cash,
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Aktual
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{
                                        formatPrice(lastClosedShift.actual_cash)
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                                >
                                    Selisih
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold"
                                    :class="
                                        differenceClass(
                                            lastClosedShift.cash_difference,
                                        )
                                    "
                                >
                                    {{
                                        formatPrice(
                                            lastClosedShift.cash_difference,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-stone-200 bg-white px-4 py-4 dark:border-white/10 dark:bg-slate-950/50"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-400"
                            >
                                Ringkasan Revenue
                            </p>
                            <p class="mt-2 text-lg font-black text-sky-300">
                                {{
                                    formatPrice(
                                        lastClosedShift.summary.total_revenue,
                                    )
                                }}
                            </p>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                {{ lastClosedShift.summary.total_orders }} order
                                terbayar • Cash
                                {{
                                    formatPrice(
                                        lastClosedShift.summary.breakdown.cash,
                                    )
                                }}
                                • QRIS
                                {{
                                    formatPrice(
                                        lastClosedShift.summary.breakdown.qris,
                                    )
                                }}
                            </p>
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
                        <label v-if="canChooseOutlet" class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Outlet</span
                            >
                            <select
                                v-model="outletFilter"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
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

                        <label v-if="canChooseCashier" class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Kasir</span
                            >
                            <select
                                v-model="userFilter"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua kasir</option>
                                <option
                                    v-for="cashier in referenceData.cashiers"
                                    :key="cashier.id"
                                    :value="cashier.id"
                                >
                                    {{ cashier.name
                                    }}{{
                                        cashier.role ? ` • ${cashier.role}` : ''
                                    }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Status</span
                            >
                            <select
                                v-model="statusFilter"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua status</option>
                                <option value="active">Active</option>
                                <option value="closed">Closed</option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Dari tanggal</span
                            >
                            <input
                                v-model="startDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
                            />
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Sampai tanggal</span
                            >
                            <input
                                v-model="endDateFilter"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm text-stone-900 focus:border-orange-400 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-900/80 dark:text-white"
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
                class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                        >
                            Rekap Kas per Shift
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                        >
                            Akumulasi semua shift tertutup sesuai filter kasir,
                            outlet, dan periode.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border px-4 py-3 text-sm"
                        :class="
                            props.cashRecap.difference_count > 0
                                ? 'border-amber-400/15 bg-amber-500/10 text-amber-200'
                                : 'border-emerald-400/15 bg-emerald-500/10 text-emerald-200'
                        "
                    >
                        {{
                            props.cashRecap.difference_count > 0
                                ? `${props.cashRecap.difference_count} shift punya selisih kas dan perlu follow up.`
                                : 'Belum ada selisih kas pada shift tertutup di filter ini.'
                        }}
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <article
                        v-for="card in cashRecapCards"
                        :key="card.label"
                        class="rounded-2xl border p-4 shadow-[0_18px_50px_rgba(15,23,42,0.16)]"
                        :class="card.surface"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                        >
                            {{ card.label }}
                        </p>
                        <p class="mt-3 text-2xl font-black" :class="card.tone">
                            {{ card.value }}
                        </p>
                        <p
                            class="mt-2 text-xs text-stone-400 dark:text-slate-400"
                        >
                            {{ card.helper }}
                        </p>
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
                            Riwayat Shift
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                        >
                            Menampilkan {{ history.from ?? 0 }} -
                            {{ history.to ?? 0 }} dari
                            {{ history.total }} shift. Shift dengan selisih kas
                            diberi highlight khusus.
                        </p>
                    </div>
                </div>

                <div
                    v-if="!history.data.length"
                    class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400"
                >
                    Belum ada data shift pada filter ini.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="shift in history.data"
                        :key="shift.id"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[1fr_0.95fr_1fr]"
                        :class="
                            hasCashDifference(shift)
                                ? 'border-l-4 border-amber-400/70 bg-amber-500/[0.04]'
                                : ''
                        "
                    >
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    class="text-base font-black text-stone-900 dark:text-white"
                                >
                                    {{ shift.user?.name || '-' }}
                                </h3>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="statusClass(shift.status)"
                                >
                                    {{ shift.status }}
                                </span>
                                <span
                                    v-if="hasCashDifference(shift)"
                                    class="rounded-full border border-amber-400/20 bg-amber-500/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-amber-200"
                                >
                                    Selisih Kas
                                </span>
                            </div>
                            <p
                                class="text-sm text-stone-600 dark:text-slate-300"
                            >
                                {{ shift.outlet?.name || '-' }}
                            </p>
                            <p
                                class="text-xs text-stone-400 dark:text-slate-400"
                            >
                                {{
                                    shift.shift_template?.name ||
                                    'Tanpa template'
                                }}
                            </p>
                        </div>

                        <div
                            class="space-y-2 text-sm text-stone-600 dark:text-slate-300"
                        >
                            <p
                                class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-400"
                            >
                                Waktu & Cash
                            </p>
                            <p>Buka: {{ formatDateTime(shift.opened_at) }}</p>
                            <p>Tutup: {{ formatDateTime(shift.closed_at) }}</p>
                            <p>Awal: {{ formatPrice(shift.opening_cash) }}</p>
                            <p>Aktual: {{ formatPrice(shift.actual_cash) }}</p>
                        </div>

                        <div
                            class="space-y-2 text-sm text-stone-600 dark:text-slate-300"
                        >
                            <p
                                class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-400"
                            >
                                Ringkasan
                            </p>
                            <p>
                                Revenue:
                                {{ formatPrice(shift.summary.total_revenue) }}
                            </p>
                            <p>
                                Order terbayar: {{ shift.summary.total_orders }}
                            </p>
                            <p>
                                Cash:
                                {{ formatPrice(shift.summary.breakdown.cash) }}
                            </p>
                            <p
                                class="font-semibold"
                                :class="differenceClass(shift.cash_difference)"
                            >
                                Selisih:
                                {{ formatPrice(shift.cash_difference) }}
                            </p>
                        </div>
                    </article>
                </div>

                <div
                    v-if="history.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-5 py-4 dark:border-white/10"
                >
                    <p class="text-xs text-stone-400 dark:text-slate-400">
                        Riwayat shift dipaginasi agar monitoring outlet tetap
                        ringan.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in history.links"
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
    </AuthenticatedLayout>
</template>
