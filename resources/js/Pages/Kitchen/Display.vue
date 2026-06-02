<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { BellRing, CheckCheck, Flame, ScanSearch, Volume2, VolumeX } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type KitchenOrderStatus =
    | 'pending'
    | 'in_progress'
    | 'waiting_bar_approval'
    | 'ready';
type KitchenLaneKey = 'queued' | 'cooking' | 'handoff';
type KitchenAction = 'start_cooking' | 'finish_cooking' | null;
const estimatePresets = [10, 15, 20, 30];

interface KitchenOrderItem {
    id: string;
    name: string;
    quantity: number;
    categoryId?: string | null;
    categoryName?: string | null;
    notes?: string | null;
}

interface KitchenOrderPayload {
    id: string;
    orderNumber: string;
    tableLabel: string;
    customerName?: string | null;
    source: string;
    status: KitchenOrderStatus;
    notes?: string | null;
    estimatedMinutes: number;
    pendingStartedAt?: string | null;
    cookingStartedAt?: string | null;
    updatedAt?: string | null;
    items: KitchenOrderItem[];
}

interface BoardConfig {
    waitingAlertSeconds: number;
    cookingWarningSeconds: number;
    defaultEstimatedMinutes: number;
    voiceSettings?: {
        enabled: boolean;
        volume: number;
        rate: number;
        pitch: number;
    };
}

interface KitchenHistoryEntry {
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

interface KitchenTicketView {
    id: string;
    orderNumber: string;
    tableLabel: string;
    customerLabel: string;
    sourceLabel: string;
    status: KitchenOrderStatus;
    lane: KitchenLaneKey;
    timerTitle: string;
    timerValue: string;
    timerTone: string;
    laneBorder: string;
    cardSurface: string;
    orderNotes?: string | null;
    items: KitchenOrderItem[];
    actionLabel: string | null;
    actionValue: KitchenAction;
    sortGroup: number;
    sortValue: number;
    statusBadge: string | null;
    statusBadgeClass: string;
    sourceBadgeClass: string;
    estimatedMinutes: number;
}

const props = defineProps<{
    orders: KitchenOrderPayload[];
    history: KitchenHistoryEntry[];
    boardConfig: BoardConfig;
    success?: string | null;
    error?: string | null;
}>();

const now = ref(Date.now());
const submittingOrderId = ref<string | null>(null);
const selectedCategoryId = ref<string>('all');

let clockInterval: number | undefined;
let pollInterval: number | undefined;
const knownOrderIds = ref<Set<string>>(new Set());

// State Audio Lokal
const isAudioBlocked = ref(false);
const localMute = ref(localStorage.getItem('kds_local_mute') === 'true');
const localVolume = ref(localStorage.getItem('kds_local_volume') !== null ? Number(localStorage.getItem('kds_local_volume')) : 1.0);

const toggleLocalMute = () => {
    localMute.value = !localMute.value;
    localStorage.setItem('kds_local_mute', String(localMute.value));
};

const handleVolumeChange = (e: Event) => {
    const val = Number((e.target as HTMLInputElement).value);
    localVolume.value = val;
    localStorage.setItem('kds_local_volume', String(val));
    if (val > 0 && localMute.value) {
        localMute.value = false;
        localStorage.setItem('kds_local_mute', 'false');
    }
};

const initAudioAndDismiss = () => {
    try {
        const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.frequency.setValueAtTime(600, audioCtx.currentTime);
        gain.gain.setValueAtTime(0, audioCtx.currentTime);
        gain.gain.linearRampToValueAtTime(0.05, audioCtx.currentTime + 0.05);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.2);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.2);
    } catch (e) {
        console.error(e);
    }
    isAudioBlocked.value = false;
};

onMounted(() => {
    clockInterval = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);

    // Populate initial orders
    props.orders.forEach((o) => knownOrderIds.value.add(o.id));

    // Cek autoplay terblokir
    try {
        const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
        if (audioCtx.state === 'suspended') {
            isAudioBlocked.value = true;
        }
    } catch (e) {
        console.warn('AudioContext not supported or blocked:', e);
    }

    // Start auto polling reload every 8 seconds
    pollInterval = window.setInterval(() => {
        router.reload({ only: ['orders', 'history'], preserveScroll: true } as any);
    }, 8000);
});

onBeforeUnmount(() => {
    if (clockInterval) {
        window.clearInterval(clockInterval);
    }
    if (pollInterval) {
        window.clearInterval(pollInterval);
    }
});

const announceNewOrder = (order: KitchenOrderPayload) => {
    const voiceConfig = props.boardConfig.voiceSettings;
    if (voiceConfig && !voiceConfig.enabled) return;

    const customer = order.customerName || (order.source === 'qr_meja' ? 'Pelanggan Meja' : 'Pelanggan');
    const tableInfo = order.tableLabel && order.tableLabel !== 'Takeaway' ? `Meja ${order.tableLabel}` : 'Takeaway';
    
    const itemsText = order.items.map(item => `${item.quantity} ${item.name}`).join(', ');
    const text = `Pesanan baru atas nama ${customer}, ${tableInfo}. Menu: ${itemsText}.`;
    
    playChimeAndSpeak(text, voiceConfig);
};

const playChimeAndSpeak = (text: string, voiceConfig: any) => {
    if (localMute.value) return;

    try {
        const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
        if (audioCtx.state === 'suspended') {
            isAudioBlocked.value = true;
            return;
        }
        const playTone = (freq: number, start: number, duration: number) => {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.frequency.setValueAtTime(freq, start);
            gain.gain.setValueAtTime(0, start);
            const targetGain = 0.2 * localVolume.value;
            gain.gain.linearRampToValueAtTime(targetGain, start + 0.05);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(start);
            osc.stop(start + duration);
        };
        playTone(587.33, audioCtx.currentTime, 0.4);
        playTone(440.00, audioCtx.currentTime + 0.15, 0.6);
    } catch (e) {
        console.error(e);
    }

    setTimeout(() => {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        
        const baseVolume = voiceConfig ? Number(voiceConfig.volume ?? 1.0) : 1.0;
        utterance.volume = baseVolume * localVolume.value;
        
        if (voiceConfig) {
            utterance.rate = Number(voiceConfig.rate ?? 0.9);
            utterance.pitch = Number(voiceConfig.pitch ?? 1.05);
        }
        
        const voices = window.speechSynthesis.getVoices();
        const idVoice = voices.find(v => v.lang.includes('id'));
        if (idVoice) utterance.voice = idVoice;
        
        window.speechSynthesis.speak(utterance);
    }, 450);
};

watch(
    () => props.orders,
    (newOrders) => {
        if (!newOrders) return;
        const newItems = newOrders.filter(o => !knownOrderIds.value.has(o.id));
        if (newItems.length > 0) {
            newItems.forEach((order) => {
                announceNewOrder(order);
                knownOrderIds.value.add(order.id);
            });
        }
        
        // Clean up knownOrderIds that are no longer in props.orders to save memory
        const currentIds = new Set(newOrders.map(o => o.id));
        knownOrderIds.value.forEach((id) => {
            if (!currentIds.has(id)) {
                knownOrderIds.value.delete(id);
            }
        });
    },
    { deep: true },
);

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
            return 'border-amber-400/20 bg-amber-500/12 text-amber-300';
        case 'waiting_bar_approval':
            return 'border-cyan-400/20 bg-cyan-500/12 text-cyan-300';
        case 'ready':
            return 'border-emerald-400/20 bg-emerald-500/12 text-emerald-300';
        default:
            return 'border-slate-700/70 bg-slate-800 text-slate-300';
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
        case 'kasir':
        default:
            return 'Kasir';
    }
};

const resolveCustomerLabel = (order: KitchenOrderPayload) => {
    if (order.customerName) return order.customerName;
    if (order.source === 'qr_meja') return 'Customer QR';
    if (order.source === 'gofood') return 'Customer GoFood';
    if (order.source === 'grabfood') return 'Customer GrabFood';

    return 'Walk-in';
};

const mappedTickets = computed<KitchenTicketView[]>(() => {
    const waitingThreshold = props.boardConfig.waitingAlertSeconds;
    const cookingThreshold = props.boardConfig.cookingWarningSeconds;

    return props.orders.map((order) => {
        const pendingBase =
            toTimestamp(order.pendingStartedAt) ??
            toTimestamp(order.updatedAt) ??
            now.value;
        const cookingBase =
            toTimestamp(order.cookingStartedAt) ??
            toTimestamp(order.updatedAt) ??
            now.value;
        const waitedSeconds = Math.max(
            0,
            Math.floor((now.value - pendingBase) / 1000),
        );
        const cookingElapsedSeconds = Math.max(
            0,
            Math.floor((now.value - cookingBase) / 1000),
        );
        const estimatedSeconds =
            (order.estimatedMinutes ||
                props.boardConfig.defaultEstimatedMinutes) * 60;
        const remainingSeconds = estimatedSeconds - cookingElapsedSeconds;

        const sourceLabel = resolveSourceLabel(order.source);
        const customerLabel = resolveCustomerLabel(order);

        if (order.status === 'pending') {
            const isWarning = waitedSeconds >= waitingThreshold;

            return {
                id: order.id,
                orderNumber: order.orderNumber,
                tableLabel: order.tableLabel,
                customerLabel,
                sourceLabel,
                status: order.status,
                lane: 'queued',
                timerTitle: 'Menunggu',
                timerValue: formatDuration(waitedSeconds),
                timerTone: isWarning ? 'text-amber-300' : 'text-sky-300',
                laneBorder: isWarning
                    ? 'border-amber-500/25'
                    : 'border-sky-500/25',
                cardSurface: isWarning
                    ? 'border-amber-500/20 bg-amber-500/[0.04]'
                    : 'border-slate-800/90 bg-slate-950/78',
                orderNotes: order.notes,
                items: order.items,
                actionLabel: 'Mulai Masak',
                actionValue: 'start_cooking',
                sortGroup: isWarning ? 3 : 4,
                sortValue: waitedSeconds,
                statusBadge: isWarning ? 'Warning' : null,
                statusBadgeClass:
                    'border-amber-400/20 bg-amber-500/12 text-amber-300',
                sourceBadgeClass:
                    sourceLabel === 'QR Meja'
                        ? 'border-fuchsia-400/20 bg-fuchsia-500/12 text-fuchsia-300'
                        : 'border-slate-700/70 bg-slate-800 text-slate-300',
                estimatedMinutes:
                    order.estimatedMinutes ||
                    props.boardConfig.defaultEstimatedMinutes,
            };
        }

        if (order.status === 'in_progress') {
            const isOverdue = remainingSeconds < 0;
            const isWarning =
                !isOverdue && remainingSeconds <= cookingThreshold;

            return {
                id: order.id,
                orderNumber: order.orderNumber,
                tableLabel: order.tableLabel,
                customerLabel,
                sourceLabel,
                status: order.status,
                lane: 'cooking',
                timerTitle: isOverdue ? 'Lewat' : 'Sisa',
                timerValue: formatDuration(Math.abs(remainingSeconds)),
                timerTone: isOverdue
                    ? 'text-rose-300'
                    : isWarning
                      ? 'text-amber-300'
                      : 'text-white',
                laneBorder: isOverdue
                    ? 'border-rose-500/25'
                    : 'border-amber-500/25',
                cardSurface: isOverdue
                    ? 'border-rose-500/25 bg-rose-500/[0.04]'
                    : isWarning
                      ? 'border-amber-500/20 bg-amber-500/[0.04]'
                      : 'border-slate-800/90 bg-slate-950/78',
                orderNotes: order.notes,
                items: order.items,
                actionLabel: 'Selesai',
                actionValue: 'finish_cooking',
                sortGroup: isOverdue ? 0 : isWarning ? 1 : 2,
                sortValue: remainingSeconds,
                statusBadge: isOverdue
                    ? 'Overdue'
                    : isWarning
                      ? 'Warning'
                      : null,
                statusBadgeClass: isOverdue
                    ? 'border-rose-400/20 bg-rose-500/12 text-rose-300'
                    : 'border-amber-400/20 bg-amber-500/12 text-amber-300',
                sourceBadgeClass:
                    sourceLabel === 'QR Meja'
                        ? 'border-fuchsia-400/20 bg-fuchsia-500/12 text-fuchsia-300'
                        : 'border-slate-700/70 bg-slate-800 text-slate-300',
                estimatedMinutes:
                    order.estimatedMinutes ||
                    props.boardConfig.defaultEstimatedMinutes,
            };
        }

        const isReady = order.status === 'ready';

        return {
            id: order.id,
            orderNumber: order.orderNumber,
            tableLabel: order.tableLabel,
            customerLabel,
            sourceLabel,
            status: order.status,
            lane: 'handoff',
            timerTitle: 'Status',
            timerValue: isReady ? 'Siap Pickup' : 'Menunggu Bar',
            timerTone: isReady ? 'text-emerald-300' : 'text-orange-300',
            laneBorder: 'border-emerald-500/25',
            cardSurface: isReady
                ? 'border-emerald-500/20 bg-emerald-500/[0.04]'
                : 'border-orange-500/20 bg-orange-500/[0.04]',
            orderNotes: order.notes,
            items: order.items,
            actionLabel: null,
            actionValue: null,
            sortGroup: isReady ? 6 : 5,
            sortValue: toTimestamp(order.updatedAt) ?? 0,
            statusBadge: isReady ? 'Ready' : 'Bar Approval',
            statusBadgeClass: isReady
                ? 'border-emerald-400/20 bg-emerald-500/12 text-emerald-300'
                : 'border-orange-400/20 bg-orange-500/12 text-orange-300',
            sourceBadgeClass:
                sourceLabel === 'QR Meja'
                    ? 'border-fuchsia-400/20 bg-fuchsia-500/12 text-fuchsia-300'
                    : 'border-slate-700/70 bg-slate-800 text-slate-300',
            estimatedMinutes:
                order.estimatedMinutes ||
                props.boardConfig.defaultEstimatedMinutes,
        };
    });
});

const sortedTickets = computed(() => {
    return [...mappedTickets.value].sort((left, right) => {
        if (left.sortGroup !== right.sortGroup) {
            return left.sortGroup - right.sortGroup;
        }

        if (left.lane === 'cooking') {
            return left.sortValue - right.sortValue;
        }

        return right.sortValue - left.sortValue;
    });
});

const categoryFilters = computed(() => {
    const categoryMap = new Map<string, string>();

    sortedTickets.value.forEach((ticket) => {
        ticket.items.forEach((item) => {
            if (item.categoryId && item.categoryName) {
                categoryMap.set(item.categoryId, item.categoryName);
            }
        });
    });

    return Array.from(categoryMap.entries())
        .map(([id, name]) => ({ id, name }))
        .sort((left, right) => left.name.localeCompare(right.name, 'id-ID'));
});

const filteredSortedTickets = computed(() => {
    if (selectedCategoryId.value === 'all') {
        return sortedTickets.value;
    }

    return sortedTickets.value
        .map((ticket) => {
            const filteredItems = ticket.items.filter(
                (item) => item.categoryId === selectedCategoryId.value,
            );

            if (filteredItems.length === 0) {
                return null;
            }

            return {
                ...ticket,
                items: filteredItems,
            };
        })
        .filter((ticket): ticket is KitchenTicketView => ticket !== null);
});

const queueTickets = computed(() =>
    filteredSortedTickets.value.filter((ticket) => ticket.lane === 'queued'),
);
const cookingTickets = computed(() =>
    filteredSortedTickets.value.filter((ticket) => ticket.lane === 'cooking'),
);
const handoffTickets = computed(() =>
    filteredSortedTickets.value.filter((ticket) => ticket.lane === 'handoff'),
);

const boardStats = computed(() => [
    {
        label: 'Total Tiket Aktif',
        value: String(filteredSortedTickets.value.length),
        hint: 'Semua lane',
        icon: ScanSearch,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
    },
    {
        label: 'Order Masuk',
        value: String(queueTickets.value.length),
        hint: 'Belum diproses',
        icon: BellRing,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/8',
    },
    {
        label: 'Sedang Dimasak',
        value: String(cookingTickets.value.length),
        hint: 'Station aktif',
        icon: Flame,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
    },
    {
        label: 'Handoff',
        value: String(handoffTickets.value.length),
        hint: 'Bar / pickup',
        icon: CheckCheck,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/8',
    },
]);

const queueColumns = computed(() => [
    {
        key: 'queued',
        title: 'Order Masuk',
        subtitle: 'Belum diproses',
        icon: BellRing,
        count: queueTickets.value.length,
        accent: 'text-sky-300',
        accentSurface: 'from-sky-500/16 via-cyan-500/10 to-transparent',
        border: 'border-sky-500/25',
        badge: 'border-sky-400/20 bg-sky-500/12 text-sky-300',
        tickets: queueTickets.value,
    },
    {
        key: 'cooking',
        title: 'Sedang Dimasak',
        subtitle: 'Countdown waktu masak',
        icon: Flame,
        count: cookingTickets.value.length,
        accent: 'text-amber-300',
        accentSurface: 'from-amber-500/16 via-orange-500/10 to-transparent',
        border: 'border-amber-500/25',
        badge: 'border-amber-400/20 bg-amber-500/12 text-amber-300',
        tickets: cookingTickets.value,
    },
    {
        key: 'handoff',
        title: 'Handoff',
        subtitle: 'Menunggu bar / pickup',
        icon: CheckCheck,
        count: handoffTickets.value.length,
        accent: 'text-emerald-300',
        accentSurface: 'from-emerald-500/16 via-lime-500/10 to-transparent',
        border: 'border-emerald-500/25',
        badge: 'border-emerald-400/20 bg-emerald-500/12 text-emerald-300',
        tickets: handoffTickets.value,
    },
]);

const submitKitchenAction = (
    orderId: string,
    action: Exclude<KitchenAction, null>,
) => {
    submittingOrderId.value = orderId;

    router.post(
        route('kitchen.orders.update-status', orderId),
        { action },
        {
            preserveScroll: true,
            onFinish: () => {
                submittingOrderId.value = null;
            },
        },
    );
};

const updateEstimate = (orderId: string, minutes: number) => {
    submittingOrderId.value = orderId;

    router.post(
        route('kitchen.orders.update-status', orderId),
        {
            action: 'set_estimate',
            estimate_minutes: minutes,
        },
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
    <Head title="Kitchen Display - POS Mentai" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between w-full">
                <div>
                    <div
                        class="bg-orange-500/8 mb-2 inline-flex items-center gap-2 rounded-full border border-orange-500/20 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-orange-400 shadow-[0_0_10px_rgba(251,146,60,0.65)]"
                        ></span>
                        Live Kitchen Board
                    </div>
                    <h2
                        class="font-display text-2xl font-black tracking-tight text-white lg:text-3xl"
                    >
                        Kitchen Display System
                    </h2>
                    <p
                        class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-400 lg:text-sm"
                    >
                        Monitor tiket masuk, masak, dan handoff secara
                        real-time.
                    </p>
                </div>

                <!-- Kontrol Volume Lokal -->
                <div class="flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/45 p-3 md:self-end">
                    <button
                        type="button"
                        @click="toggleLocalMute"
                        class="rounded-xl border border-slate-800 bg-slate-900 p-2 text-slate-400 hover:bg-slate-800 hover:text-white transition"
                        title="Toggle Mute Dapur"
                    >
                        <VolumeX v-if="localMute || localVolume === 0" class="h-4.5 w-4.5 text-rose-400 animate-pulse" />
                        <Volume2 v-else class="h-4.5 w-4.5 text-fuchsia-400" />
                    </button>
                    <div class="flex flex-col gap-1 w-28 md:w-36">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500">
                            Volume Lokal ({{ localMute ? 'Muted' : Math.round(localVolume * 100) + '%' }})
                        </span>
                        <input
                            type="range"
                            min="0"
                            max="1"
                            step="0.1"
                            :value="localMute ? 0 : localVolume"
                            @input="handleVolumeChange"
                            class="w-full h-1 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-orange-500"
                        />
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Banner Autoplay Terblokir -->
            <div
                v-if="isAudioBlocked"
                @click="initAudioAndDismiss"
                class="bg-amber-500/12 hover:bg-amber-500/18 flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-amber-500/25 px-5 py-4 text-amber-300 shadow-lg shadow-amber-950/10 transition group"
            >
                <div class="flex items-center gap-3">
                    <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-2 group-hover:scale-105 transition duration-200">
                        <VolumeX class="h-5 w-5 text-amber-400 animate-bounce" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Notifikasi Suara Terblokir Browser</h4>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Silakan klik area ini untuk mengaktifkan notifikasi suara bel dan pembacaan pesanan secara otomatis.
                        </p>
                    </div>
                </div>
                <span class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-black uppercase tracking-wider hover:bg-amber-500/20 transition">
                    Aktifkan
                </span>
            </div>

            <div
                v-if="success"
                class="bg-emerald-500/12 flex items-center gap-2 rounded-xl border border-emerald-500/20 px-4 py-3 text-sm font-medium text-emerald-300"
            >
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                <span>{{ success }}</span>
            </div>

            <div
                v-if="error"
                class="bg-rose-500/12 flex items-center gap-2 rounded-xl border border-rose-500/20 px-4 py-3 text-sm font-medium text-rose-300"
            >
                <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                <span>{{ error }}</span>
            </div>

            <section
                class="rounded-[22px] border border-slate-800/80 bg-slate-900/90 p-3 shadow-xl shadow-slate-950/15"
            >
                <div
                    class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between"
                >
                    <div class="space-y-3">
                        <div
                            class="flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500"
                        >
                            <span
                                class="bg-emerald-500/8 rounded-full border border-emerald-500/20 px-3 py-1 text-emerald-300"
                            >
                                Sinkronisasi Aktif
                            </span>
                            <span
                                class="rounded-full border border-slate-700/70 bg-slate-950/60 px-3 py-1"
                            >
                                Waiting Alert
                                {{
                                    Math.floor(
                                        boardConfig.waitingAlertSeconds / 60,
                                    )
                                }}
                                Menit
                            </span>
                            <span
                                class="rounded-full border border-slate-700/70 bg-slate-950/60 px-3 py-1"
                            >
                                Cooking Warning
                                {{
                                    Math.floor(
                                        boardConfig.cookingWarningSeconds / 60,
                                    )
                                }}
                                Menit
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div
                                class="flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500"
                            >
                                <span class="text-slate-300 font-bold">
                                    Filter Kategori Menu
                                </span>
                                <span class="text-slate-500">
                                    Tampilkan tiket berdasarkan kategori menu
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="selectedCategoryId = 'all'"
                                    :class="[
                                        'rounded-full border px-3 py-1.5 text-[11px] font-bold transition',
                                        selectedCategoryId === 'all'
                                            ? 'border-orange-500/30 bg-orange-500/12 text-orange-300'
                                            : 'border-slate-700/70 bg-slate-950/60 text-slate-400 hover:border-slate-600 hover:text-slate-200',
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
                                            ? 'border-orange-500/30 bg-orange-500/12 text-orange-300'
                                            : 'border-slate-700/70 bg-slate-950/60 text-slate-400 hover:border-slate-600 hover:text-slate-200',
                                    ]"
                                >
                                    {{ category.name }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:min-w-[620px]"
                    >
                        <div
                            v-for="stat in boardStats"
                            :key="stat.label"
                            :class="[
                                'rounded-2xl border px-3 py-2 shadow-lg shadow-slate-950/10',
                                stat.surface,
                            ]"
                        >
                            <div class="mb-1 flex items-center justify-between">
                                <span
                                    class="text-[9px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                                    >{{ stat.label }}</span
                                >
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
                            <p class="mt-0.5 text-[10px] text-slate-500">
                                {{ stat.hint }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                <article
                    v-for="lane in queueColumns"
                    :key="lane.key"
                    class="flex min-h-[38rem] flex-col gap-3"
                >
                    <div
                        :class="[
                            'relative overflow-hidden rounded-[22px] border bg-slate-900/95 p-4 shadow-xl shadow-slate-950/15',
                            lane.border,
                        ]"
                    >
                        <div
                            :class="[
                                'pointer-events-none absolute inset-x-0 top-0 h-16 bg-gradient-to-b opacity-90',
                                lane.accentSurface,
                            ]"
                        ></div>

                        <div
                            class="relative flex items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] shadow-inner shadow-slate-950/30"
                                >
                                    <component
                                        :is="lane.icon"
                                        :class="['h-5 w-5', lane.accent]"
                                    />
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-white">
                                        {{ lane.title }}
                                    </h3>
                                    <p class="text-[11px] text-slate-400">
                                        {{ lane.subtitle }}
                                    </p>
                                </div>
                            </div>

                            <div
                                :class="[
                                    'rounded-2xl border px-3 py-1.5 text-right shadow-lg shadow-slate-950/20',
                                    lane.badge,
                                ]"
                            >
                                <div
                                    class="text-[9px] font-semibold uppercase tracking-[0.22em]"
                                >
                                    Tiket
                                </div>
                                <div class="text-lg font-black">
                                    {{ lane.count }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-slate-900/88 min-h-0 flex-1 overflow-hidden rounded-[22px] border border-slate-800/80 p-3 shadow-2xl shadow-slate-950/20"
                    >
                        <div class="flex h-full min-h-0 flex-col">
                            <div
                                class="custom-scrollbar min-h-0 flex-1 space-y-3 overflow-y-auto pr-1"
                            >
                                <div
                                    v-for="ticket in lane.tickets"
                                    :key="ticket.id"
                                    :class="[
                                        'rounded-[22px] border p-4 shadow-lg shadow-slate-950/15 transition duration-200',
                                        ticket.cardSurface,
                                    ]"
                                >
                                    <div
                                        class="mb-3 flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500"
                                            >
                                                {{ ticket.orderNumber }}
                                            </p>
                                            <h4
                                                class="mt-1 truncate text-xl font-black text-white"
                                            >
                                                {{ ticket.tableLabel }}
                                            </h4>
                                            <p
                                                class="mt-1 text-sm text-slate-400"
                                            >
                                                {{ ticket.customerLabel }}
                                            </p>
                                        </div>
                                        <div
                                            class="rounded-2xl border border-slate-800 bg-slate-900/85 px-3 py-2 text-right"
                                        >
                                            <p
                                                class="text-[10px] uppercase tracking-[0.18em] text-slate-500"
                                            >
                                                {{ ticket.timerTitle }}
                                            </p>
                                            <p
                                                :class="[
                                                    'mt-1 text-2xl font-black',
                                                    ticket.timerTone,
                                                ]"
                                            >
                                                {{ ticket.timerValue }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="mb-3 flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                            :class="ticket.sourceBadgeClass"
                                        >
                                            {{ ticket.sourceLabel }}
                                        </span>
                                        <span
                                            v-if="ticket.statusBadge"
                                            class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                            :class="ticket.statusBadgeClass"
                                        >
                                            {{ ticket.statusBadge }}
                                        </span>
                                        <span
                                            class="rounded-full border border-violet-400/20 bg-violet-500/12 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-violet-200"
                                        >
                                            Est {{ ticket.estimatedMinutes }}m
                                        </span>
                                    </div>

                                    <div
                                        class="space-y-2 rounded-2xl border border-slate-800/80 bg-slate-900/65 p-3"
                                    >
                                        <div
                                            v-for="item in ticket.items"
                                            :key="item.id"
                                            class="flex items-start gap-3 border-b border-slate-800/70 pb-2 last:border-b-0 last:pb-0"
                                        >
                                            <span
                                                class="mt-0.5 inline-flex min-w-10 justify-center rounded-xl border border-slate-700/70 bg-slate-950 px-2 py-1 text-sm font-black text-white"
                                            >
                                                x{{ item.quantity }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="text-base font-bold leading-tight text-slate-100"
                                                >
                                                    {{ item.name }}
                                                </p>
                                                <p
                                                    v-if="item.notes"
                                                    class="mt-1 text-[11px] italic leading-relaxed text-slate-500"
                                                >
                                                    {{ item.notes }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="ticket.orderNotes"
                                        class="bg-orange-500/6 mt-3 rounded-2xl border border-orange-500/15 px-3 py-2 text-[11px] leading-relaxed text-orange-100/85"
                                    >
                                        <span
                                            class="font-bold uppercase tracking-[0.18em] text-orange-300"
                                            >Catatan order</span
                                        >
                                        <p class="mt-1">
                                            {{ ticket.orderNotes }}
                                        </p>
                                    </div>

                                    <div
                                        class="mt-3 rounded-2xl border border-violet-500/15 bg-violet-500/[0.04] p-3"
                                    >
                                        <div
                                            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div>
                                                <p
                                                    class="text-[11px] text-slate-400"
                                                >
                                                    Estimasi waktu masak per
                                                    order.
                                                </p>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="minutes in estimatePresets"
                                                    :key="`${ticket.id}-${minutes}`"
                                                    type="button"
                                                    @click="
                                                        updateEstimate(
                                                            ticket.id,
                                                            minutes,
                                                        )
                                                    "
                                                    :disabled="
                                                        submittingOrderId ===
                                                        ticket.id
                                                    "
                                                    :class="[
                                                        'rounded-full border px-3 py-1.5 text-[11px] font-bold transition disabled:pointer-events-none disabled:opacity-50',
                                                        ticket.estimatedMinutes ===
                                                        minutes
                                                            ? 'border-violet-400/30 bg-violet-500/14 text-violet-200'
                                                            : 'border-slate-700/70 bg-slate-950/60 text-slate-400 hover:border-violet-400/20 hover:text-slate-200',
                                                    ]"
                                                >
                                                    {{ minutes }}m
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        v-if="
                                            ticket.actionLabel &&
                                            ticket.actionValue
                                        "
                                        type="button"
                                        class="mt-3 flex w-full items-center justify-center rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-bold text-white transition duration-150 hover:border-orange-500/40 hover:bg-slate-900 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="
                                            submittingOrderId === ticket.id
                                        "
                                        @click="
                                            submitKitchenAction(
                                                ticket.id,
                                                ticket.actionValue,
                                            )
                                        "
                                    >
                                        <span
                                            v-if="
                                                submittingOrderId === ticket.id
                                            "
                                            >Memproses...</span
                                        >
                                        <span v-else>{{
                                            ticket.actionLabel
                                        }}</span>
                                    </button>
                                </div>

                                <div
                                    v-if="lane.tickets.length === 0"
                                    class="rounded-[22px] border border-dashed border-slate-800 bg-slate-950/50 px-5 py-12 text-center"
                                >
                                    <p class="text-sm font-bold text-slate-300">
                                        Lane kosong.
                                    </p>
                                    <p class="mt-2 text-xs text-slate-500">
                                        Tidak ada tiket aktif pada status ini.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section
                class="rounded-[22px] border border-slate-800/80 bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
            >
                <div
                    class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <h3 class="mt-1 text-lg font-black text-white">
                            Riwayat Order Dapur
                        </h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Log transisi terbaru untuk flow kitchen dan bar.
                        </p>
                    </div>
                    <span
                        class="rounded-full border border-slate-700/70 bg-slate-950/60 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-300"
                    >
                        {{ props.history.length }} log terbaru
                    </span>
                </div>

                <div
                    v-if="props.history.length === 0"
                    class="rounded-2xl border border-dashed border-slate-800 px-4 py-14 text-center text-xs text-slate-500"
                >
                    Riwayat dapur belum ada. Log akan muncul setelah ada
                    transisi status baru.
                </div>

                <div v-else class="grid gap-3 xl:grid-cols-2">
                    <article
                        v-for="entry in props.history"
                        :key="entry.id"
                        class="rounded-2xl border border-slate-800/80 bg-slate-950/65 p-4"
                    >
                        <div
                            class="flex flex-col gap-3 border-b border-slate-800/80 pb-3 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    {{ entry.orderNumber || 'Order' }}
                                </p>
                                <h4 class="mt-1 text-base font-black text-white">
                                    {{ entry.tableLabel }}
                                </h4>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    {{ entry.customerName || 'Walk-in' }}
                                </p>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                {{ formatHistoryDateTime(entry.createdAt) }}
                            </p>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-300"
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

                        <p class="mt-3 text-[11px] text-slate-400">
                            Oleh {{ entry.changedByName }}
                        </p>
                        <p
                            v-if="entry.notes"
                            class="mt-1 text-[11px] leading-relaxed text-slate-500"
                        >
                            {{ entry.notes }}
                        </p>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.font-display {
    font-family: 'Figtree', sans-serif;
}
</style>
