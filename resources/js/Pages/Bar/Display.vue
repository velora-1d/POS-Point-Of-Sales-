<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCheck, CupSoda, PackageCheck, ScanSearch } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface BarOrderItem {
    id: string;
    name: string;
    quantity: number;
    categoryId?: string | null;
    categoryName?: string | null;
    notes?: string | null;
}

interface BarOrderPayload {
    id: string;
    orderNumber: string;
    tableLabel: string;
    customerName?: string | null;
    source: string;
    status: 'waiting_bar_approval' | 'ready';
    notes?: string | null;
    estimatedMinutes: number;
    updatedAt?: string | null;
    items: BarOrderItem[];
}

interface BarHistoryEntry {
    id: string;
    orderNumber?: string | null;
    tableLabel: string;
    customerName?: string | null;
    fromStatus?: string | null;
    toStatus: string;
    changedByName: string;
    changedByType: string;
    notes?: string | null;
    createdAt?: string | null;
}

const props = defineProps<{
    orders: BarOrderPayload[];
    history: BarHistoryEntry[];
    success?: string | null;
    error?: string | null;
}>();

const now = ref(Date.now());
const submittingOrderId = ref<string | null>(null);
const selectedCategoryId = ref<string>('all');

let clockInterval: number | undefined;

onMounted(() => {
    clockInterval = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onBeforeUnmount(() => {
    if (clockInterval) {
        window.clearInterval(clockInterval);
    }
});

const toTimestamp = (value?: string | null) => {
    if (!value) return null;

    const parsed = new Date(value).getTime();

    return Number.isNaN(parsed) ? null : parsed;
};

const formatDuration = (seconds: number) => {
    const safeSeconds = Math.max(0, Math.floor(seconds));
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const remainingSeconds = safeSeconds % 60;

    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
    }

    return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
};

const formatHistoryDateTime = (value?: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const resolveStatusLabel = (status?: string | null) => {
    switch (status) {
        case 'pending':
            return 'Pending';
        case 'in_progress':
            return 'Mulai Masak';
        case 'waiting_bar_approval':
            return 'Menunggu Bar';
        case 'ready':
            return 'Ready';
        case 'delivered':
            return 'Diantar';
        case 'completed':
            return 'Selesai';
        default:
            return status || '-';
    }
};

const resolveHistoryTone = (status: string) => {
    switch (status) {
        case 'in_progress':
            return 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:border-amber-400/20 dark:bg-amber-500/12 dark:text-amber-300';
        case 'waiting_bar_approval':
            return 'border-cyan-500/20 bg-cyan-500/10 text-cyan-700 dark:border-cyan-400/20 dark:bg-cyan-500/12 dark:text-cyan-300';
        case 'ready':
            return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-500/12 dark:text-emerald-300';
        default:
            return 'border-stone-200 dark:border-slate-700/70 bg-stone-100 dark:bg-slate-800 text-stone-600 dark:text-slate-300';
    }
};

const resolveSourceLabel = (source: string) => {
    switch (source) {
        case 'qr_meja':
            return 'QR Meja';
        case 'gofood':
            return 'GoFood';
        case 'grabfood':
            return 'GrabFood';
        case 'shopeefood':
            return 'ShopeeFood';
        case 'maximfood':
            return 'MaximFood';
        case 'kasir':
        default:
            return 'Kasir';
    }
};

const mappedOrders = computed(() =>
    props.orders.map((order) => {
        const updatedBase = toTimestamp(order.updatedAt) ?? now.value;
        const waitingSeconds = Math.max(
            0,
            Math.floor((now.value - updatedBase) / 1000),
        );

        return {
            ...order,
            sourceLabel: resolveSourceLabel(order.source),
            customerLabel: order.customerName || 'Walk-in',
            waitingLabel: formatDuration(waitingSeconds),
        };
    }),
);

const categoryFilters = computed(() => {
    const categoryMap = new Map<string, string>();

    mappedOrders.value.forEach((order) => {
        order.items.forEach((item) => {
            if (item.categoryId && item.categoryName) {
                categoryMap.set(item.categoryId, item.categoryName);
            }
        });
    });

    return Array.from(categoryMap.entries())
        .map(([id, name]) => ({ id, name }))
        .sort((left, right) => left.name.localeCompare(right.name, 'id-ID'));
});

const filteredOrders = computed(() => {
    if (selectedCategoryId.value === 'all') {
        return mappedOrders.value;
    }

    return mappedOrders.value
        .map((order) => {
            const filteredItems = order.items.filter(
                (item) => item.categoryId === selectedCategoryId.value,
            );

            if (filteredItems.length === 0) {
                return null;
            }

            return {
                ...order,
                items: filteredItems,
            };
        })
        .filter(
            (order): order is (typeof mappedOrders.value)[number] =>
                order !== null,
        );
});

const waitingApprovalOrders = computed(() =>
    filteredOrders.value.filter(
        (order) => order.status === 'waiting_bar_approval',
    ),
);

const readyOrders = computed(() =>
    filteredOrders.value.filter((order) => order.status === 'ready'),
);

const boardStats = computed(() => [
    {
        label: 'Total Tiket',
        value: String(filteredOrders.value.length),
        hint: 'Semua tiket bar',
        icon: ScanSearch,
        tone: 'text-stone-850 dark:text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
    },
    {
        label: 'Butuh Approval',
        value: String(waitingApprovalOrders.value.length),
        hint: 'Menunggu finalisasi',
        icon: CupSoda,
        tone: 'text-cyan-700 dark:text-cyan-300',
        surface: 'border-cyan-400/15 bg-cyan-500/8',
    },
    {
        label: 'Ready Pickup',
        value: String(readyOrders.value.length),
        hint: 'Siap disajikan',
        icon: CheckCheck,
        tone: 'text-emerald-700 dark:text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/8',
    },
]);

const approveReady = (orderId: string) => {
    submittingOrderId.value = orderId;

    router.post(
        route('bar.orders.approve', orderId),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                submittingOrderId.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Bar Display - POS Mentai" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <div
                        class="mb-2 inline-flex items-center gap-2 rounded-full border border-cyan-500/20 bg-cyan-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-300"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-cyan-300"
                        ></span>
                        Bar Approval Board
                    </div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Bar Display System (BDS)
                    </h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                        Finalisasi tiket dari kitchen sebelum status order masuk
                        ke ready.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <div
                v-if="success"
                class="flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/12 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-300"
            >
                <span class="h-2 w-2 rounded-full bg-emerald-450 dark:bg-emerald-400"></span>
                <span>{{ success }}</span>
            </div>

            <div
                v-if="error"
                class="flex items-center gap-2 rounded-xl border border-rose-500/20 bg-rose-500/12 px-4 py-3 text-sm font-medium text-rose-700 dark:text-rose-300"
            >
                <span class="h-2 w-2 rounded-full bg-rose-450 dark:bg-rose-400"></span>
                <span>{{ error }}</span>
            </div>

            <section
                class="rounded-[22px] border-2 border-black bg-stone-50 p-4 dark:border-slate-800/80 dark:bg-slate-900/90"
            >
                <div
                    class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between"
                >
                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="selectedCategoryId = 'all'"
                                :class="[
                                    'rounded-full border px-3 py-1.5 text-[11px] font-bold transition',
                                    selectedCategoryId === 'all'
                                        ? 'border-cyan-500/30 bg-cyan-500/12 text-cyan-700 dark:text-cyan-200'
                                        : 'border-stone-200 bg-white text-stone-500 hover:border-stone-300 hover:text-stone-800 dark:border-slate-600 dark:border-slate-700/70 dark:bg-slate-950/60 dark:text-slate-200 dark:text-slate-400',
                                ]"
                            >
                                Semua Kategori
                            </button>
                            <button
                                v-for="category in categoryFilters"
                                :key="category.id"
                                type="button"
                                @click="selectedCategoryId = category.id"
                                :class="[
                                    'rounded-full border px-3 py-1.5 text-[11px] font-bold transition',
                                    selectedCategoryId === category.id
                                        ? 'border-cyan-500/30 bg-cyan-500/12 text-cyan-700 dark:text-cyan-200'
                                        : 'border-stone-200 bg-white text-stone-500 hover:border-stone-300 hover:text-stone-800 dark:border-slate-600 dark:border-slate-700/70 dark:bg-slate-950/60 dark:text-slate-200 dark:text-slate-400',
                                ]"
                            >
                                {{ category.name }}
                            </button>
                        </div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                        >
                            Filter kategori pesanan untuk board bar.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <div
                            v-for="stat in boardStats"
                            :key="stat.label"
                            :class="[
                                'rounded-2xl border-2 border-black px-3 py-2 dark:border-slate-800',
                                stat.surface,
                            ]"
                        >
                            <div class="mb-1 flex items-center justify-between">
                                <span
                                    class="text-[9px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    {{ stat.label }}
                                </span>
                                <component
                                    :is="stat.icon"
                                    :class="['h-3.5 w-3.5', stat.tone]"
                                />
                            </div>
                            <div
                                :class="[
                                    'text-sm font-black lg:text-base',
                                    stat.tone,
                                ]"
                            >
                                {{ stat.value }}
                            </div>
                            <p
                                class="mt-0.5 text-[10px] text-stone-400 dark:text-slate-500"
                            >
                                {{ stat.hint }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 xl:grid-cols-2">
                <section
                    class="rounded-[24px] border-2 border-black bg-stone-50 p-4 dark:border-slate-800/90"
                >
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3
                                class="mt-1 text-lg font-black text-stone-900 dark:text-stone-900 dark:text-white"
                            >
                                Approval Order Selesai
                            </h3>
                            <p
                                class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                            >
                                Order dari kitchen masuk ke sini sebelum
                                ditandai siap.
                            </p>
                        </div>
                        <span
                            class="rounded-full border border-cyan-500/20 bg-cyan-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-cyan-700 dark:text-cyan-200"
                        >
                            {{ waitingApprovalOrders.length }} tiket
                        </span>
                    </div>

                    <div
                        class="custom-scrollbar space-y-3 overflow-y-auto pr-1 max-h-[1400px]"
                    >
                        <article
                            v-for="order in waitingApprovalOrders"
                            :key="order.id"
                            class="rounded-[18px] border-2 border-black bg-white p-4 transition duration-200 hover:-translate-y-0.5"
                        >
                            <div
                                class="flex flex-col gap-3 border-b border-stone-200 pb-3 dark:border-slate-800/80 lg:flex-row lg:items-start lg:justify-between"
                            >
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="text-sm font-black text-stone-900 dark:text-white"
                                        >
                                            {{ order.orderNumber }}
                                        </p>
                                        <span
                                            class="rounded-full border border-stone-200 bg-stone-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-stone-600 dark:border-slate-700/70 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            {{ order.tableLabel }}
                                        </span>
                                        <span
                                            class="rounded-full border border-fuchsia-400/20 bg-fuchsia-500/12 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-fuchsia-700 dark:text-fuchsia-300"
                                        >
                                            {{ order.sourceLabel }}
                                        </span>
                                    </div>
                                    <p
                                        class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                    >
                                        {{ order.customerLabel }}
                                    </p>
                                </div>
                                <div class="text-left lg:text-right">
                                    <p
                                        class="text-[9px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Menunggu Finalisasi
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-black text-cyan-700 dark:text-cyan-200"
                                    >
                                        {{ order.waitingLabel }}
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                    >
                                        Est {{ order.estimatedMinutes }} menit
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="rounded-xl border border-stone-200 bg-white px-3 py-2 dark:border-slate-800/70 dark:bg-slate-950/60"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-bold text-stone-900 dark:text-white"
                                            >
                                                {{ item.name }}
                                            </p>
                                            <p
                                                v-if="item.notes"
                                                class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                            >
                                                {{ item.notes }}
                                            </p>
                                        </div>
                                        <span
                                            class="text-xs font-bold text-cyan-700 dark:text-cyan-200"
                                        >
                                            x{{ item.quantity }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-3 flex flex-col gap-3 border-t border-stone-200 pt-3 dark:border-slate-800/80 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <p
                                    class="text-[11px] text-stone-500 dark:text-slate-400"
                                >
                                    {{
                                        order.notes ||
                                        'Tidak ada catatan tambahan di level order.'
                                    }}
                                </p>
                                <button
                                    type="button"
                                    @click="approveReady(order.id)"
                                    :disabled="submittingOrderId === order.id"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-black bg-cyan-400 hover:bg-cyan-300 px-4 py-2.5 text-xs font-black text-black active:translate-y-0.5 disabled:pointer-events-none disabled:opacity-50 transition"
                                >
                                    <PackageCheck class="h-4 w-4" />
                                    <span>
                                        {{
                                            submittingOrderId === order.id
                                                ? 'Memproses...'
                                                : 'Approve Jadi Ready'
                                        }}
                                    </span>
                                </button>
                            </div>
                        </article>

                        <!-- Empty Rows to fill up to 10 slots -->
                        <div
                            v-for="i in Math.max(0, 10 - waitingApprovalOrders.length)"
                            :key="'empty-wait-' + i"
                            class="rounded-[18px] border-2 border-dashed border-stone-200 bg-white/20 p-4 h-28 flex flex-col items-center justify-center dark:border-slate-800/80 dark:bg-slate-900/10"
                        >
                            <div class="h-6 w-6 rounded-full border border-dashed border-stone-300 flex items-center justify-center text-[10px] font-black text-stone-300 dark:border-slate-800 dark:text-slate-700">
                                {{ waitingApprovalOrders.length + i }}
                            </div>
                            <span class="mt-2 text-[9px] font-semibold uppercase tracking-[0.2em] text-stone-300 dark:text-slate-600">
                                Slot Kosong
                            </span>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-[24px] border-2 border-black bg-stone-50 p-4 dark:border-slate-800/90"
                >
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300"
                            >
                                Ready Board
                            </p>
                            <h3
                                class="mt-1 text-lg font-black text-stone-900 dark:text-white"
                            >
                                Siap Disajikan / Pickup
                            </h3>
                            <p
                                class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                            >
                                Tiket yang sudah selesai dan bisa dilanjutkan ke
                                service atau closing pembayaran.
                            </p>
                        </div>
                        <span
                            class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300"
                        >
                            {{ readyOrders.length }} tiket
                        </span>
                    </div>

                    <div
                        class="custom-scrollbar space-y-3 overflow-y-auto pr-1 max-h-[1400px]"
                    >
                        <article
                            v-for="order in readyOrders"
                            :key="order.id"
                            class="rounded-[18px] border-2 border-black bg-white p-4 transition duration-200 hover:-translate-y-0.5"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="text-sm font-black text-stone-900 dark:text-white"
                                        >
                                            {{ order.orderNumber }}
                                        </p>
                                        <span
                                            class="rounded-full border border-stone-200 bg-stone-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-stone-600 dark:border-slate-700/70 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            {{ order.tableLabel }}
                                        </span>
                                    </div>
                                    <p
                                        class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                    >
                                        {{ order.customerLabel }} •
                                        {{ order.sourceLabel }}
                                    </p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p
                                        class="text-[9px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Ready Sejak
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-black text-emerald-700 dark:text-emerald-300"
                                    >
                                        {{ order.waitingLabel }}
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                    >
                                        Est {{ order.estimatedMinutes }} menit
                                    </p>
                                </div>
                            </div>
                        </article>

                        <!-- Empty Rows to fill up to 10 slots -->
                        <div
                            v-for="i in Math.max(0, 10 - readyOrders.length)"
                            :key="'empty-ready-' + i"
                            class="rounded-[18px] border-2 border-dashed border-stone-200 bg-white/20 p-4 h-28 flex flex-col items-center justify-center dark:border-slate-800/80 dark:bg-slate-900/10"
                        >
                            <div class="h-6 w-6 rounded-full border border-dashed border-stone-300 flex items-center justify-center text-[10px] font-black text-stone-300 dark:border-slate-800 dark:text-slate-700">
                                {{ readyOrders.length + i }}
                            </div>
                            <span class="mt-2 text-[9px] font-semibold uppercase tracking-[0.2em] text-stone-300 dark:text-slate-600">
                                Slot Kosong
                            </span>
                        </div>
                    </div>
                </section>
            </div>

            <section
                class="rounded-[22px] border border-stone-200 bg-stone-50 p-4 dark:border-slate-800/80 dark:bg-slate-900/92"
            >
                <div
                    class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <h3
                            class="mt-1 text-lg font-black text-stone-900 dark:text-stone-900 dark:text-white"
                        >
                            Riwayat Order Dapur
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Riwayat transisi terbaru yang relevan untuk lane
                            kitchen dan bar.
                        </p>
                    </div>
                    <span
                        class="rounded-full border border-stone-200 bg-white px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-stone-600 dark:border-slate-700/70 dark:bg-slate-950/60 dark:text-slate-300"
                    >
                        {{ props.history.length }} log terbaru
                    </span>
                </div>

                <div
                    v-if="props.history.length === 0"
                    class="rounded-2xl border border-dashed border-stone-200 px-4 py-14 text-center text-xs text-stone-400 dark:border-slate-800 dark:text-slate-500"
                >
                    Riwayat belum ada. Log akan muncul setelah ada transisi
                    status baru.
                </div>

                <div v-else class="grid gap-3 xl:grid-cols-2">
                    <article
                        v-for="entry in props.history"
                        :key="entry.id"
                        class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800/80 dark:bg-slate-950/65"
                    >
                        <div
                            class="flex flex-col gap-3 border-b border-stone-200 pb-3 dark:border-slate-800/80 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    {{ entry.orderNumber || 'Order' }}
                                </p>
                                <h4
                                    class="mt-1 text-base font-black text-stone-900 dark:text-white"
                                >
                                    {{ entry.tableLabel }}
                                </h4>
                                <p
                                    class="mt-1 text-[11px] text-stone-500 dark:text-slate-400"
                                >
                                    {{ entry.customerName || 'Walk-in' }}
                                </p>
                            </div>
                            <p
                                class="text-[11px] text-stone-400 dark:text-slate-500"
                            >
                                {{ formatHistoryDateTime(entry.createdAt) }}
                            </p>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-stone-600 dark:text-slate-300"
                            >
                                {{ resolveStatusLabel(entry.fromStatus) }}
                            </span>
                            <span class="text-slate-600">→</span>
                            <span
                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                :class="resolveHistoryTone(entry.toStatus)"
                            >
                                {{ resolveStatusLabel(entry.toStatus) }}
                            </span>
                        </div>

                        <p
                            class="mt-3 text-[11px] text-stone-500 dark:text-slate-400"
                        >
                            Oleh {{ entry.changedByName }}
                        </p>
                        <p
                            v-if="entry.notes"
                            class="mt-1 text-[11px] leading-relaxed text-stone-400 dark:text-slate-500"
                        >
                            {{ entry.notes }}
                        </p>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
