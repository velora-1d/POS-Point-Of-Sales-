<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    ArrowUpRight,
    Armchair,
    Clock3,
    Copy,
    ExternalLink,
    Printer,
    QrCode,
    RefreshCw,
    Sparkles,
    Users,
    X,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface TableOrderSummary {
    id: string;
    order_number: string;
    status: string;
    total_amount: number | string;
    created_at: string;
    customer?: {
        name?: string | null;
        phone?: string | null;
    } | null;
}

interface TablePayload {
    id: string;
    name: string;
    capacity?: number | null;
    status: 'available' | 'occupied' | 'reserved';
    category?: string | null;
    qr_code?: string | null;
    qr_session_token?: string | null;
    public_qr_url?: string | null;
    position_x?: number | null;
    position_y?: number | null;
    active_reservation?: TableReservationPayload | null;
    active_order?: TableOrderSummary | null;
    active_orders?: TableOrderSummary[];
}

interface TableReservationPayload {
    id: string;
    status: 'booked' | 'cancelled' | 'completed';
    customer_name: string;
    customer_phone: string;
    guest_count: number;
    reserved_for: string;
    notes?: string | null;
    table?: {
        id: string;
        name: string;
    } | null;
}

const props = defineProps<{
    tables: TablePayload[];
    reservations: TableReservationPayload[];
    summary: {
        total: number;
        available: number;
        occupied: number;
        reserved: number;
    };
    success?: string | null;
    error?: string | null;
}>();

const selectedCategory = ref<'indoor' | 'outdoor'>('indoor');
const filteredTables = computed(() => {
    return props.tables.filter((t) => t.category === selectedCategory.value);
});

const POLLING_INTERVAL_MS = 8000;
const LIVE_CHANGE_HIGHLIGHT_MS = 4000;

const isRefreshing = ref(false);
const lastUpdatedAt = ref(new Date());
const changedTableIds = ref<string[]>([]);
const hasLiveChanges = ref(false);
const reservationModalOpen = ref(false);
const selectedQrTable = ref<{
    id: string;
    name: string;
    qrLabel: string | null;
    qrUrl: string | null;
} | null>(null);
const qrActionMessage = ref('');

let refreshIntervalId: ReturnType<typeof setInterval> | null = null;
let highlightTimeoutId: ReturnType<typeof setTimeout> | null = null;
let qrMessageTimeoutId: ReturnType<typeof setTimeout> | null = null;

const reservationForm = useForm({
    table_id: '',
    customer_id: null as string | null,
    customer_name: '',
    customer_phone: '',
    guest_count: 2,
    reserved_for: '',
    notes: '',
});

const formatPrice = (value: unknown) => {
    const num = Number(value || 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDateTime = (value: string | null | undefined) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const currentDateTimeInput = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
};

const hasManualPositions = computed(() =>
    filteredTables.value.some(
        (table) => table.position_x !== null || table.position_y !== null,
    ),
);

const generatedSlots = [
    [12, 12], [37, 12], [62, 12], [87, 12],
    [12, 38], [37, 38], [62, 38], [87, 38],
    [12, 64], [37, 64], [62, 64], [87, 64],
    [12, 90], [37, 90], [62, 90], [87, 90],
];

const tableCoordinates = computed(() => {
    const positionedTables = filteredTables.value.filter(
        (table) =>
            table.position_x !== null &&
            table.position_x !== undefined &&
            table.position_y !== null &&
            table.position_y !== undefined,
    );

    if (!hasManualPositions.value || positionedTables.length === 0) {
        return filteredTables.value.map((table, index) => {
            const [x, y] = generatedSlots[index % generatedSlots.length];

            return {
                ...table,
                x,
                y,
            };
        });
    }

    const xs = positionedTables.map((table) => Number(table.position_x));
    const ys = positionedTables.map((table) => Number(table.position_y));
    const minX = Math.min(...xs);
    const maxX = Math.max(...xs);
    const minY = Math.min(...ys);
    const maxY = Math.max(...ys);

    return filteredTables.value.map((table, index) => {
        if (
            table.position_x === null ||
            table.position_x === undefined ||
            table.position_y === null ||
            table.position_y === undefined
        ) {
            const [x, y] = generatedSlots[index % generatedSlots.length];

            return {
                ...table,
                x,
                y,
            };
        }

        const xRange = Math.max(1, maxX - minX);
        const yRange = Math.max(1, maxY - minY);

        return {
            ...table,
            x: 8 + ((Number(table.position_x) - minX) / xRange) * 76,
            y: 10 + ((Number(table.position_y) - minY) / yRange) * 76,
        };
    });
});

const tableCards = computed(() =>
    tableCoordinates.value.map((table) => {
        const activeOrders =
            table.active_orders && table.active_orders.length > 0
                ? table.active_orders
                : table.active_order
                  ? [table.active_order]
                  : [];

        const latestOrder = activeOrders[0] || null;
        const activeReservation = table.active_reservation || null;
        const customerLabel =
            latestOrder?.customer?.name ||
            latestOrder?.customer?.phone ||
            activeReservation?.customer_name ||
            (table.status === 'occupied' ? 'Walk-in' : 'Belum ada order');

        return {
            ...table,
            activeOrders,
            latestOrder,
            activeReservation,
            customerLabel,
            qrLabel: table.qr_code || table.qr_session_token || null,
            kasirUrl: `${route('kasir.order')}?table_id=${table.id}`,
            qrUrl: table.public_qr_url
                ? table.public_qr_url
                : table.qr_session_token
                ? route('self-service.menu', table.qr_session_token)
                : null,
        };
    }),
);

const qrReadyCount = computed(
    () => tableCards.value.filter((table) => table.qrUrl).length,
);

const reservationCards = computed(() =>
    props.reservations.map((reservation) => ({
        ...reservation,
        tableName: reservation.table?.name || 'Meja',
        kasirUrl: reservation.table?.id
            ? `${route('kasir.order')}?table_id=${reservation.table.id}`
            : route('kasir.order'),
    })),
);

const reservationTableOptions = computed(() =>
    tableCards.value.filter(
        (table) => table.status !== 'occupied' && !table.activeReservation,
    ),
);

const statCards = computed(() => [
    {
        label: 'Total Meja',
        value: props.summary.total,
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
    },
    {
        label: 'Siap Dipakai',
        value: props.summary.available,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/8',
    },
    {
        label: 'Sedang Terpakai',
        value: props.summary.occupied,
        tone: 'text-rose-300',
        surface: 'border-rose-400/15 bg-rose-500/8',
    },
    {
        label: 'Reserved',
        value: props.summary.reserved,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
    },
]);

const lastUpdatedLabel = computed(() =>
    new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(lastUpdatedAt.value),
);

const qrPreviewUrl = computed(() => {
    if (!selectedQrTable.value?.qrUrl) {
        return null;
    }

    return `https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=16&data=${encodeURIComponent(selectedQrTable.value.qrUrl)}`;
});

const getTableSnapshot = (table: TablePayload) =>
    JSON.stringify({
        status: table.status,
        activeReservationId: table.active_reservation?.id ?? null,
        activeOrderId: table.active_order?.id ?? null,
        activeOrderCount: table.active_orders?.length ?? 0,
        latestOrderStatus:
            table.active_orders?.[0]?.status ?? table.active_order?.status ?? null,
    });

const refreshTableStatuses = () => {
    if (isRefreshing.value || typeof document === 'undefined') {
        return;
    }

    if (document.visibilityState === 'hidden') {
        return;
    }

    isRefreshing.value = true;

    router.get(
        route('tables.layout'),
        {},
        {
            only: ['tables', 'reservations', 'summary'],
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => {
                isRefreshing.value = false;
                lastUpdatedAt.value = new Date();
            },
        },
    );
};

watch(
    () => props.tables,
    (currentTables, previousTables) => {
        if (!previousTables || previousTables.length === 0) {
            return;
        }

        const previousMap = new Map(
            previousTables.map((table) => [table.id, getTableSnapshot(table)]),
        );

        const updatedIds = currentTables
            .filter((table) => previousMap.get(table.id) !== getTableSnapshot(table))
            .map((table) => table.id);

        if (updatedIds.length === 0) {
            return;
        }

        changedTableIds.value = updatedIds;
        hasLiveChanges.value = true;

        if (highlightTimeoutId) {
            clearTimeout(highlightTimeoutId);
        }

        highlightTimeoutId = setTimeout(() => {
            changedTableIds.value = [];
            hasLiveChanges.value = false;
        }, LIVE_CHANGE_HIGHLIGHT_MS);
    },
);

onMounted(() => {
    refreshIntervalId = setInterval(refreshTableStatuses, POLLING_INTERVAL_MS);
});

onBeforeUnmount(() => {
    if (refreshIntervalId) {
        clearInterval(refreshIntervalId);
    }

    if (highlightTimeoutId) {
        clearTimeout(highlightTimeoutId);
    }

    if (qrMessageTimeoutId) {
        clearTimeout(qrMessageTimeoutId);
    }
});

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'occupied':
            return 'Occupied';
        case 'reserved':
            return 'Reserved';
        case 'available':
        default:
            return 'Available';
    }
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'occupied':
            return 'border-rose-400/20 bg-rose-500/12 text-rose-300';
        case 'reserved':
            return 'border-amber-400/20 bg-amber-500/12 text-amber-300';
        case 'available':
        default:
            return 'border-emerald-400/20 bg-emerald-500/12 text-emerald-300';
    }
};

const isTableChanged = (tableId: string) => changedTableIds.value.includes(tableId);

const showQrActionMessage = (message: string) => {
    qrActionMessage.value = message;

    if (qrMessageTimeoutId) {
        clearTimeout(qrMessageTimeoutId);
    }

    qrMessageTimeoutId = setTimeout(() => {
        qrActionMessage.value = '';
    }, 2500);
};

const openQrPreview = (table: {
    id: string;
    name: string;
    qrLabel: string | null;
    qrUrl: string | null;
}) => {
    if (!table.qrUrl) {
        showQrActionMessage(`QR ${table.name} belum aktif.`);
        return;
    }

    selectedQrTable.value = table;
    qrActionMessage.value = '';
};

const closeQrPreview = () => {
    selectedQrTable.value = null;
    qrActionMessage.value = '';
};

const copySelectedQrLink = async () => {
    if (!selectedQrTable.value?.qrUrl || typeof navigator === 'undefined') {
        return;
    }

    await navigator.clipboard.writeText(selectedQrTable.value.qrUrl);
    showQrActionMessage(`Link ${selectedQrTable.value.name} berhasil disalin.`);
};

const printSelectedQr = () => {
    if (
        !selectedQrTable.value ||
        !selectedQrTable.value.qrUrl ||
        !qrPreviewUrl.value ||
        typeof window === 'undefined'
    ) {
        return;
    }

    const printWindow = window.open('', '_blank', 'noopener,noreferrer,width=520,height=760');

    if (!printWindow) {
        showQrActionMessage('Popup print diblokir browser.');
        return;
    }

    printWindow.document.write(`
        <html>
            <head>
                <title>QR ${selectedQrTable.value.name}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 24px; color: #0f172a; }
                    .card { border: 2px solid #e2e8f0; border-radius: 20px; padding: 24px; text-align: center; }
                    h1 { margin: 0 0 8px; font-size: 28px; }
                    p { margin: 6px 0; color: #475569; }
                    img { width: 280px; height: 280px; margin: 20px auto; display: block; }
                    .link { margin-top: 12px; word-break: break-all; font-size: 12px; color: #64748b; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h1>${selectedQrTable.value.name}</h1>
                    <p>Scan untuk buka menu self-service meja</p>
                    <img src="${qrPreviewUrl.value}" alt="QR ${selectedQrTable.value.name}" />
                    <p><strong>Kode QR:</strong> ${selectedQrTable.value.qrLabel ?? '-'}</p>
                    <p class="link">${selectedQrTable.value.qrUrl}</p>
                </div>
                <script>
                    window.onload = function () {
                        window.print();
                    };
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
};

const openReservationModal = (tableId: string | null = null) => {
    reservationForm.reset();
    reservationForm.clearErrors();
    reservationForm.table_id =
        tableId ||
        reservationTableOptions.value[0]?.id ||
        '';
    reservationForm.customer_id = null;
    reservationForm.customer_name = '';
    reservationForm.customer_phone = '';
    reservationForm.guest_count = 2;
    reservationForm.reserved_for = currentDateTimeInput();
    reservationForm.notes = '';
    reservationModalOpen.value = true;
};

const closeReservationModal = () => {
    reservationModalOpen.value = false;
    reservationForm.clearErrors();
};

const submitReservation = () => {
    reservationForm.post(route('table-reservations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            reservationModalOpen.value = false;
            reservationForm.reset();
        },
    });
};

const cancelReservation = (reservationId: string) => {
    router.patch(
        route('table-reservations.update-status', reservationId),
        {
            status: 'cancelled',
        },
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head title="Layout Meja Visual" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-stone-900 dark:text-white">
                        Peta Meja Outlet
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-stone-500 dark:text-slate-400">
                        Pantau posisi, kapasitas, status meja, reservasi aktif,
                        dan QR self-service per meja, lalu lompat langsung ke
                        flow kasir dengan refresh otomatis.
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

            <div
                v-if="error"
                class="rounded-xl border border-rose-500/20 bg-rose-500/12 px-4 py-3 text-sm font-medium text-rose-300"
            >
                {{ error }}
            </div>

            <div
                v-if="qrActionMessage"
                class="rounded-xl border border-sky-500/20 bg-sky-500/12 px-4 py-3 text-sm font-medium text-sky-300"
            >
                {{ qrActionMessage }}
            </div>

            <section class="grid gap-3 lg:grid-cols-4">
                <article
                    v-for="stat in statCards"
                    :key="stat.label"
                    :class="[
                        'rounded-[22px] border px-4 py-4 shadow-lg shadow-slate-950/10',
                        stat.surface,
                    ]"
                >
                    <p
                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                    >
                        {{ stat.label }}
                    </p>
                    <p :class="['mt-2 text-3xl font-black', stat.tone]">
                        {{ stat.value }}
                    </p>
                </article>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
                <section
                    class="overflow-hidden rounded-[26px] border border-stone-200 dark:border-slate-800/80 bg-stone-50 dark:bg-slate-900/92 shadow-2xl shadow-slate-950/20"
                >
                    <div
                        class="border-b border-stone-200 dark:border-slate-800/80 bg-[radial-gradient(circle_at_top_right,_rgba(249,115,22,0.14),_transparent_38%),linear-gradient(180deg,rgba(15,23,42,0.95),rgba(2,6,23,0.98))] px-5 py-4"
                    >
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                                >
                                    Arena Outlet
                                </p>
                                <h3 class="mt-1 text-lg font-black text-stone-900 dark:text-white">
                                    Visual map meja aktif
                                </h3>
                                <div class="mt-3 flex items-center gap-1 rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 p-1 w-fit">
                                    <button
                                        type="button"
                                        @click="selectedCategory = 'indoor'"
                                        :class="[
                                            'rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider transition-all duration-200',
                                            selectedCategory === 'indoor'
                                                ? 'bg-orange-500 text-white shadow-md'
                                                : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200',
                                        ]"
                                    >
                                        Indoor Area
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectedCategory = 'outdoor'"
                                        :class="[
                                            'rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider transition-all duration-200',
                                            selectedCategory === 'outdoor'
                                                ? 'bg-orange-500 text-white shadow-md'
                                                : 'text-stone-500 dark:text-slate-400 hover:text-stone-800 dark:text-slate-200',
                                        ]"
                                    >
                                        Outdoor Area
                                    </button>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    :href="route('settings.tables.index')"
                                    class="inline-flex items-center gap-1 rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300 transition hover:bg-orange-500/20"
                                >
                                    <Settings class="h-3.5 w-3.5" />
                                    Manajemen Meja
                                </Link>
                                <button
                                    type="button"
                                    @click="refreshTableStatuses"
                                    :disabled="isRefreshing"
                                    class="inline-flex items-center gap-1 rounded-full border border-stone-200 dark:border-slate-700/80 bg-white dark:bg-slate-950/70 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-stone-800 dark:text-slate-200 transition hover:border-orange-500/30 hover:text-stone-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <RefreshCw
                                        :class="[
                                            'h-3.5 w-3.5',
                                            isRefreshing ? 'animate-spin' : '',
                                        ]"
                                    />
                                    Refresh
                                </button>
                                <div
                                    class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-300"
                                >
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span
                                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                        ></span>
                                        <span
                                            class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"
                                        ></span>
                                    </span>
                                    Live tiap 8 detik
                                </div>
                                <p class="text-[11px] text-stone-400 dark:text-slate-500">
                                    Update terakhir {{ lastUpdatedLabel }}
                                </p>
                            </div>
                        </div>
                        <p class="mt-3 text-[11px] text-stone-400 dark:text-slate-500">
                            {{
                                hasManualPositions
                                    ? 'Menggunakan koordinat posisi meja yang tersimpan.'
                                    : 'Posisi meja memakai fallback grid visual.'
                            }}
                        </p>
                    </div>

                    <div class="p-4">
                        <div
                            class="relative min-h-[36rem] overflow-hidden rounded-[24px] border border-stone-200 dark:border-slate-800 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.08),_transparent_32%),linear-gradient(180deg,#020617_0%,#111827_100%)]"
                        >
                            <div
                                class="pointer-events-none absolute inset-0 opacity-20"
                                style="
                                    background-image:
                                        linear-gradient(
                                            rgba(148, 163, 184, 0.16) 1px,
                                            transparent 1px
                                        ),
                                        linear-gradient(
                                            90deg,
                                            rgba(148, 163, 184, 0.16) 1px,
                                            transparent 1px
                                        );
                                    background-size: 64px 64px;
                                "
                            ></div>

                            <div
                                :class="[
                                    hasManualPositions
                                        ? 'absolute inset-0'
                                        : 'relative z-10 grid h-full content-start gap-6 overflow-y-auto p-6 grid-cols-[repeat(auto-fill,minmax(180px,1fr))]',
                                ]"
                            >
                                <div
                                    v-for="table in tableCards"
                                    :key="table.id"
                                    :class="[
                                        'transition duration-300',
                                        hasManualPositions
                                            ? 'absolute w-[170px] -translate-x-1/2 -translate-y-1/2'
                                            : 'w-full',
                                        isTableChanged(table.id)
                                            ? 'scale-[1.03]'
                                            : '',
                                    ]"
                                    :style="
                                        hasManualPositions
                                            ? {
                                                  left: `${table.x}%`,
                                                  top: `${table.y}%`,
                                              }
                                            : {}
                                    "
                                >
                                <div
                                    :class="[
                                        'rounded-[22px] border border-stone-200 dark:border-slate-700/80 bg-white dark:bg-slate-950/90 p-3 shadow-[0_20px_50px_rgba(2,6,23,0.45)] backdrop-blur transition duration-300',
                                        isTableChanged(table.id)
                                            ? 'border-sky-400/40 shadow-[0_0_0_1px_rgba(56,189,248,0.18),0_20px_50px_rgba(14,165,233,0.18)]'
                                            : '',
                                    ]"
                                >
                                    <div
                                        class="mb-3 flex items-start justify-between gap-3"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                            >
                                                {{ table.name }}
                                            </p>
                                            <p
                                                class="mt-1 text-lg font-black text-stone-900 dark:text-white"
                                            >
                                                {{
                                                    table.activeOrders.length
                                                        ? table.activeOrders
                                                              .length
                                                        : table.capacity || '-'
                                                }}
                                            </p>
                                        </div>
                                        <span
                                            :class="[
                                                'rounded-full border px-2 py-1 text-[9px] font-bold uppercase tracking-wider',
                                                getStatusClass(table.status),
                                            ]"
                                        >
                                            {{ getStatusLabel(table.status) }}
                                        </span>
                                    </div>

                                    <div
                                        v-if="isTableChanged(table.id)"
                                        class="mb-3 inline-flex items-center gap-1 rounded-full border border-sky-400/20 bg-sky-500/10 px-2 py-1 text-[9px] font-bold uppercase tracking-[0.18em] text-sky-300"
                                    >
                                        <Activity class="h-3 w-3" />
                                        Perubahan baru
                                    </div>

                                    <div
                                        class="rounded-2xl border border-stone-200 dark:border-slate-800/80 bg-stone-50 dark:bg-slate-900/70 px-3 py-2"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                            >
                                                Kapasitas
                                            </span>
                                            <span
                                                class="flex items-center gap-1 text-[11px] font-bold text-stone-800 dark:text-slate-200"
                                            >
                                                <Users class="h-3.5 w-3.5" />
                                                {{ table.capacity || '-' }}
                                            </span>
                                        </div>

                                        <div
                                            class="mt-2 flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                            >
                                                Customer
                                            </span>
                                            <span
                                                class="max-w-[84px] truncate text-[11px] font-bold text-stone-600 dark:text-slate-300"
                                            >
                                                {{ table.customerLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="mt-3 flex items-center justify-between gap-2 text-[11px] text-stone-500 dark:text-slate-400"
                                    >
                                        <div
                                            v-if="table.latestOrder"
                                            class="min-w-0"
                                        >
                                            <p class="truncate font-bold text-stone-900 dark:text-white">
                                                {{
                                                    table.latestOrder
                                                        .order_number
                                                }}
                                            </p>
                                            <p class="mt-0.5 text-stone-400 dark:text-slate-500">
                                                {{
                                                    formatPrice(
                                                        table.latestOrder
                                                            .total_amount,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div v-else class="text-stone-400 dark:text-slate-500">
                                            Siap untuk order baru
                                        </div>
                                    </div>

                                    <div
                                        v-if="table.activeReservation"
                                        class="mt-3 rounded-2xl border border-amber-500/20 bg-amber-500/8 px-3 py-2"
                                    >
                                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-amber-300">
                                            Reservasi Aktif
                                        </p>
                                        <p class="mt-1 truncate text-[11px] font-bold text-stone-900 dark:text-white">
                                            {{ table.activeReservation.customer_name }}
                                        </p>
                                        <p class="mt-0.5 text-[10px] text-stone-500 dark:text-slate-400">
                                            {{ formatDateTime(table.activeReservation.reserved_for) }}
                                            • {{ table.activeReservation.guest_count }} pax
                                        </p>
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <Link
                                            :href="table.kasirUrl"
                                            class="inline-flex items-center justify-center gap-1 rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-3 py-2 text-[11px] font-bold text-stone-900 dark:text-white"
                                        >
                                            <span>Kasir</span>
                                            <ArrowUpRight class="h-3.5 w-3.5" />
                                        </Link>
                                        <button
                                            v-if="table.qrUrl"
                                            type="button"
                                            @click="openQrPreview(table)"
                                            class="inline-flex items-center justify-center gap-1 rounded-xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-[11px] font-bold text-stone-800 dark:text-slate-200"
                                        >
                                            <QrCode class="h-3.5 w-3.5" />
                                            <span>QR</span>
                                        </button>
                                        <div
                                            v-else
                                            class="inline-flex items-center justify-center rounded-xl border border-dashed border-stone-200 dark:border-slate-800 px-3 py-2 text-[11px] font-bold text-slate-600"
                                        >
                                            QR Off
                                        </div>
                                    </div>

                                    <button
                                        v-if="table.status !== 'occupied' && !table.activeReservation"
                                        type="button"
                                        @click="openReservationModal(table.id)"
                                        class="mt-2 inline-flex w-full items-center justify-center gap-1 rounded-xl border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-[11px] font-bold text-amber-300"
                                    >
                                        <Clock3 class="h-3.5 w-3.5" />
                                        Book Meja
                                    </button>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="space-y-4">
                    <section
                        class="rounded-[26px] border border-stone-200 dark:border-slate-800/80 bg-stone-50 dark:bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Legenda Status
                        </p>
                        <div class="mt-4 space-y-3">
                            <div
                                class="rounded-2xl border border-orange-500/20 bg-orange-500/8 px-3 py-3"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold text-orange-300">
                                            QR per Meja
                                        </p>
                                        <p class="mt-1 text-[11px] text-stone-500 dark:text-slate-400">
                                            {{ qrReadyCount }} dari
                                            {{ summary.total }} meja sudah punya
                                            QR self-service aktif.
                                        </p>
                                    </div>
                                    <div
                                        class="rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300"
                                    >
                                        {{ qrReadyCount }}/{{ summary.total }}
                                    </div>
                                </div>
                            </div>
                            <div
                                class="rounded-2xl border border-sky-500/20 bg-sky-500/8 px-3 py-3"
                            >
                                <p class="text-xs font-bold text-sky-300">
                                    Live Status
                                </p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-slate-400">
                                    Status meja disegarkan otomatis tiap 8
                                    detik dan perubahan baru diberi highlight.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-emerald-500/20 bg-emerald-500/8 px-3 py-3"
                            >
                                <p class="text-xs font-bold text-emerald-300">
                                    Available
                                </p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-slate-400">
                                    Meja siap dipakai untuk order baru.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-rose-500/20 bg-rose-500/8 px-3 py-3"
                            >
                                <p class="text-xs font-bold text-rose-300">
                                    Occupied
                                </p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-slate-400">
                                    Ada order aktif dan bisa langsung dikelola.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-amber-500/20 bg-amber-500/8 px-3 py-3"
                            >
                                <p class="text-xs font-bold text-amber-300">
                                    Reserved
                                </p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-slate-400">
                                    Meja ditahan untuk booking atau kebutuhan
                                    operasional khusus.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[26px] border border-stone-200 dark:border-slate-800/80 bg-stone-50 dark:bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                            >
                                Reservasi Aktif
                            </p>
                            <button
                                type="button"
                                @click="openReservationModal()"
                                class="inline-flex items-center gap-1 rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-amber-300"
                            >
                                <Clock3 class="h-3.5 w-3.5" />
                                Book Meja
                            </button>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div
                                v-if="reservationCards.length === 0"
                                class="rounded-2xl border border-dashed border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/40 px-4 py-4 text-[11px] text-stone-400 dark:text-slate-500"
                            >
                                Belum ada reservasi aktif untuk outlet ini.
                            </div>
                            <article
                                v-for="reservation in reservationCards"
                                :key="reservation.id"
                                class="rounded-2xl border border-amber-500/15 bg-amber-500/6 px-4 py-3"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-stone-900 dark:text-white">
                                            {{ reservation.tableName }}
                                        </p>
                                        <p class="mt-1 truncate text-[11px] font-semibold text-amber-300">
                                            {{ reservation.customer_name }}
                                        </p>
                                        <p class="mt-1 text-[10px] text-stone-500 dark:text-slate-400">
                                            {{ formatDateTime(reservation.reserved_for) }}
                                            • {{ reservation.guest_count }} pax
                                        </p>
                                        <p class="mt-1 text-[10px] text-stone-400 dark:text-slate-500">
                                            {{ reservation.customer_phone }}
                                        </p>
                                        <p
                                            v-if="reservation.notes"
                                            class="mt-2 text-[10px] leading-relaxed text-stone-400 dark:text-slate-500"
                                        >
                                            {{ reservation.notes }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-amber-300"
                                    >
                                        booked
                                    </span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <Link
                                        :href="reservation.kasirUrl"
                                        class="inline-flex items-center justify-center gap-1 rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-3 py-2 text-[11px] font-bold text-stone-900 dark:text-white"
                                    >
                                        <ArrowUpRight class="h-3.5 w-3.5" />
                                        Kasir
                                    </Link>
                                    <button
                                        type="button"
                                        @click="cancelReservation(reservation.id)"
                                        class="inline-flex items-center justify-center gap-1 rounded-xl border border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-950 px-3 py-2 text-[11px] font-bold text-stone-800 dark:text-slate-200"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                        Batalkan
                                    </button>
                                </div>
                            </article>
                        </div>
                    </section>

                    <section
                        class="rounded-[26px] border border-stone-200 dark:border-slate-800/80 bg-stone-50 dark:bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Tindakan Cepat
                        </p>
                        <div class="mt-4 space-y-3">
                            <button
                                type="button"
                                @click="openReservationModal()"
                                class="flex w-full items-center justify-between rounded-2xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm font-bold text-amber-200 transition hover:border-amber-500/30"
                            >
                                <span>Buat Reservasi Meja</span>
                                <Clock3 class="h-4 w-4 text-amber-300" />
                            </button>
                            <Link
                                :href="`${route('kasir.order')}?mode=takeaway`"
                                class="flex items-center justify-between rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/70 px-4 py-3 text-sm font-bold text-stone-900 dark:text-white transition hover:border-orange-500/30"
                            >
                                <span>Masuk Mode Takeaway</span>
                                <ArrowUpRight class="h-4 w-4 text-orange-300" />
                            </Link>
                            <div
                                class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 px-4 py-3"
                            >
                                <div class="flex items-center gap-2">
                                    <Armchair class="h-4 w-4 text-sky-300" />
                                    <p class="text-sm font-bold text-stone-900 dark:text-white">
                                        Shortcut meja ke kasir
                                    </p>
                                </div>
                                <p class="mt-2 text-[11px] leading-relaxed text-stone-500 dark:text-slate-400">
                                    Klik tombol `Kasir` pada kartu meja untuk
                                    langsung buka flow order sesuai meja yang
                                    dipilih.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 px-4 py-3"
                            >
                                <div class="flex items-center gap-2">
                                    <QrCode class="h-4 w-4 text-orange-300" />
                                    <p class="text-sm font-bold text-stone-900 dark:text-white">
                                        Preview QR Meja
                                    </p>
                                </div>
                                <p class="mt-2 text-[11px] leading-relaxed text-stone-500 dark:text-slate-400">
                                    Klik tombol `QR` pada kartu meja untuk buka
                                    preview, salin link, buka menu publik, atau
                                    print QR meja.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 px-4 py-3"
                            >
                                <div class="flex items-center gap-2">
                                    <Clock3 class="h-4 w-4 text-emerald-300" />
                                    <p class="text-sm font-bold text-stone-900 dark:text-white">
                                        Posisi fallback aktif
                                    </p>
                                </div>
                                <p class="mt-2 text-[11px] leading-relaxed text-stone-500 dark:text-slate-400">
                                    Jika koordinat meja belum diisi, sistem
                                    tetap menampilkan visual map dengan susunan
                                    otomatis.
                                </p>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>

        <div
            v-if="reservationModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-slate-950/85 px-4 py-6 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-2xl rounded-[28px] border border-stone-200 dark:border-slate-800/80 bg-stone-100 dark:bg-slate-950 p-5 shadow-[0_30px_120px_rgba(2,6,23,0.7)]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.22em] text-amber-300"
                        >
                            <Clock3 class="h-3.5 w-3.5" />
                            Reservasi / Book Meja
                        </div>
                        <h3 class="mt-3 text-2xl font-black text-stone-900 dark:text-white">
                            Buat Reservasi Baru
                        </h3>
                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                            Simpan jadwal booking meja lengkap dengan nama customer,
                            jam kedatangan, jumlah pax, dan catatan.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeReservationModal"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-stone-600 dark:text-slate-300 transition hover:border-stone-200 dark:border-slate-700 hover:text-stone-900 dark:text-white"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="submitReservation">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Meja
                            </span>
                            <select
                                v-model="reservationForm.table_id"
                                class="mt-2 w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-amber-500 focus:outline-none"
                            >
                                <option value="" disabled>Pilih meja</option>
                                <option
                                    v-for="table in reservationTableOptions"
                                    :key="table.id"
                                    :value="table.id"
                                >
                                    {{ table.name }} • {{ table.capacity || '-' }} pax
                                </option>
                            </select>
                            <p
                                v-if="reservationForm.errors.table_id"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ reservationForm.errors.table_id }}
                            </p>
                        </label>

                        <label class="block">
                            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Jadwal Datang
                            </span>
                            <input
                                v-model="reservationForm.reserved_for"
                                type="datetime-local"
                                class="mt-2 w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-amber-500 focus:outline-none"
                            />
                            <p
                                v-if="reservationForm.errors.reserved_for"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ reservationForm.errors.reserved_for }}
                            </p>
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Nama Customer
                            </span>
                            <input
                                v-model="reservationForm.customer_name"
                                type="text"
                                class="mt-2 w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-amber-500 focus:outline-none"
                                placeholder="Nama pemesan"
                            />
                            <p
                                v-if="reservationForm.errors.customer_name"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ reservationForm.errors.customer_name }}
                            </p>
                        </label>

                        <label class="block">
                            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Nomor Telepon
                            </span>
                            <input
                                v-model="reservationForm.customer_phone"
                                type="text"
                                class="mt-2 w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-amber-500 focus:outline-none"
                                placeholder="08xxxxxxxxxx"
                            />
                            <p
                                v-if="reservationForm.errors.customer_phone"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ reservationForm.errors.customer_phone }}
                            </p>
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[180px_minmax(0,1fr)]">
                        <label class="block">
                            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Jumlah Pax
                            </span>
                            <input
                                v-model="reservationForm.guest_count"
                                type="number"
                                min="1"
                                max="50"
                                class="mt-2 w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-amber-500 focus:outline-none"
                            />
                            <p
                                v-if="reservationForm.errors.guest_count"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ reservationForm.errors.guest_count }}
                            </p>
                        </label>

                        <label class="block">
                            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Catatan
                            </span>
                            <textarea
                                v-model="reservationForm.notes"
                                rows="4"
                                class="mt-2 w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-amber-500 focus:outline-none"
                                placeholder="Contoh: datang jam 19.30, minta dekat jendela, alergi seafood, dsb."
                            ></textarea>
                            <p
                                v-if="reservationForm.errors.notes"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ reservationForm.errors.notes }}
                            </p>
                        </label>
                    </div>

                    <div
                        v-if="reservationTableOptions.length === 0"
                        class="rounded-2xl border border-dashed border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/50 px-4 py-3 text-sm text-stone-400 dark:text-slate-500"
                    >
                        Tidak ada meja yang bisa dibooking saat ini. Semua meja
                        sedang dipakai atau sudah punya reservasi aktif.
                    </div>

                    <div class="flex flex-col gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="closeReservationModal"
                            class="inline-flex items-center justify-center rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm font-bold text-stone-800 dark:text-slate-200"
                        >
                            Tutup
                        </button>
                        <button
                            type="submit"
                            :disabled="reservationForm.processing || reservationTableOptions.length === 0"
                            class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-3 text-sm font-bold text-stone-900 dark:text-white disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Simpan Reservasi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="selectedQrTable"
            class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-slate-950/85 px-4 py-6 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-[28px] border border-stone-200 dark:border-slate-800/80 bg-stone-100 dark:bg-slate-950 p-5 shadow-[0_30px_120px_rgba(2,6,23,0.7)]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.22em] text-orange-300"
                        >
                            <QrCode class="h-3.5 w-3.5" />
                            QR Code Per Meja
                        </div>
                        <h3 class="mt-3 text-2xl font-black text-stone-900 dark:text-white">
                            {{ selectedQrTable.name }}
                        </h3>
                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                            Scan untuk buka flow order self-service meja ini.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeQrPreview"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-stone-600 dark:text-slate-300 transition hover:border-stone-200 dark:border-slate-700 hover:text-stone-900 dark:text-white"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div
                    class="mt-5 rounded-[24px] border border-stone-200 dark:border-slate-800 bg-white p-4"
                >
                    <img
                        v-if="qrPreviewUrl"
                        :src="qrPreviewUrl"
                        :alt="`QR ${selectedQrTable.name}`"
                        class="mx-auto h-72 w-72 rounded-2xl object-contain"
                    />
                </div>

                <div
                    class="mt-4 rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 px-4 py-3"
                >
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                        Kode QR Meja
                    </p>
                    <p class="mt-1 break-all text-sm font-bold text-stone-900 dark:text-white">
                        {{ selectedQrTable.qrLabel || '-' }}
                    </p>
                    <p class="mt-3 text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                        Link Menu Publik
                    </p>
                    <p class="mt-1 break-all text-xs text-stone-600 dark:text-slate-300">
                        {{ selectedQrTable.qrUrl }}
                    </p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <button
                        type="button"
                        @click="copySelectedQrLink"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-3 text-xs font-bold text-stone-800 dark:text-slate-200"
                    >
                        <Copy class="h-4 w-4" />
                        Salin Link
                    </button>
                    <a
                        :href="selectedQrTable.qrUrl || '#'"
                        target="_blank"
                        rel="noreferrer"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-orange-500/20 bg-orange-500/10 px-3 py-3 text-xs font-bold text-orange-300"
                    >
                        <ExternalLink class="h-4 w-4" />
                        Buka Menu
                    </a>
                    <button
                        type="button"
                        @click="printSelectedQr"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-3 text-xs font-bold text-emerald-300"
                    >
                        <Printer class="h-4 w-4" />
                        Print QR
                    </button>
                </div>

                <p class="mt-4 text-[11px] leading-relaxed text-stone-400 dark:text-slate-500">
                    Preview QR memakai generator gambar eksternal berbasis link
                    menu publik yang sudah aktif di sistem.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
