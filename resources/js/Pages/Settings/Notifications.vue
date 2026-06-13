<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BellRing,
    Box,
    CheckCircle2,
    Mail,
    ShoppingBag,
    Store,
    Volume2,
    Wallet,
} from '@lucide/vue';
import { computed, watch } from 'vue';

interface OutletOption {
    id: string;
    name: string;
    is_active: boolean;
    has_config: boolean;
}

interface ChannelOption {
    value: 'in_app' | 'whatsapp' | 'email';
    label: string;
    description: string;
}

type ChannelValue = ChannelOption['value'];

const props = defineProps<{
    outlets: OutletOption[];
    selectedOutlet: {
        id: string;
        name: string;
        is_active: boolean;
    } | null;
    summary: {
        total_outlets: number;
        configured_outlets: number;
        whatsapp_enabled: number;
        email_enabled: number;
    };
    formDefaults: {
        outlet_id: string | null;
        low_stock_enabled: boolean;
        low_stock_channels: ChannelValue[];
        kasbon_due_enabled: boolean;
        kasbon_due_channels: ChannelValue[];
        kasbon_due_threshold_days: number;
        online_order_enabled: boolean;
        online_order_channels: ChannelValue[];
        table_duration_alert_enabled?: boolean;
        table_duration_warning_minutes?: number;
        table_duration_danger_minutes?: number;
        has_config: boolean;
        metadata?: any;
    };
    alertOptions: {
        channels: ChannelOption[];
    };
    snapshots: {
        low_stock: {
            count: number;
            critical: number;
            items: Array<{
                id: string;
                type: string;
                name: string;
                context: string;
                current_stock: number;
                minimum_stock: number;
                unit: string;
            }>;
        };
        kasbon_due: {
            count: number;
            total_outstanding: number;
            items: Array<{
                id: string;
                order_number: string;
                customer_name: string;
                customer_phone?: string | null;
                outstanding_amount: number;
                age_days: number;
                created_at?: string | null;
            }>;
        };
        online_order: {
            count: number;
            today_orders: number;
            items: Array<{
                id: string;
                order_number: string;
                platform: string;
                status: string;
                customer_name?: string | null;
                total_amount: number;
                created_at?: string | null;
            }>;
        };
    };
    filters: {
        outlet_id?: string | null;
    };
    success?: string | null;
}>();

const form = useForm({
    outlet_id: props.formDefaults.outlet_id || props.selectedOutlet?.id || '',
    low_stock_enabled: Boolean(props.formDefaults.low_stock_enabled),
    low_stock_channels: [...props.formDefaults.low_stock_channels],
    kasbon_due_enabled: Boolean(props.formDefaults.kasbon_due_enabled),
    kasbon_due_channels: [...props.formDefaults.kasbon_due_channels],
    kasbon_due_threshold_days: String(
        props.formDefaults.kasbon_due_threshold_days || 3,
    ),
    online_order_enabled: Boolean(props.formDefaults.online_order_enabled),
    online_order_channels: [...props.formDefaults.online_order_channels],
    table_duration_alert_enabled: Boolean(
        props.formDefaults.table_duration_alert_enabled,
    ),
    table_duration_warning_minutes: String(
        props.formDefaults.table_duration_warning_minutes || 90,
    ),
    table_duration_danger_minutes: String(
        props.formDefaults.table_duration_danger_minutes || 180,
    ),
    metadata: {
        kitchen_voice: {
            enabled:
                props.formDefaults.metadata?.kitchen_voice?.enabled ?? true,
            volume: props.formDefaults.metadata?.kitchen_voice?.volume ?? 1.0,
            rate: props.formDefaults.metadata?.kitchen_voice?.rate ?? 0.9,
            pitch: props.formDefaults.metadata?.kitchen_voice?.pitch ?? 1.05,
        },
    },
});

watch(
    () => props.formDefaults,
    (defaults) => {
        form.defaults({
            outlet_id: defaults.outlet_id || props.selectedOutlet?.id || '',
            low_stock_enabled: Boolean(defaults.low_stock_enabled),
            low_stock_channels: [...defaults.low_stock_channels],
            kasbon_due_enabled: Boolean(defaults.kasbon_due_enabled),
            kasbon_due_channels: [...defaults.kasbon_due_channels],
            kasbon_due_threshold_days: String(
                defaults.kasbon_due_threshold_days || 3,
            ),
            online_order_enabled: Boolean(defaults.online_order_enabled),
            online_order_channels: [...defaults.online_order_channels],
            table_duration_alert_enabled: Boolean(
                defaults.table_duration_alert_enabled,
            ),
            table_duration_warning_minutes: String(
                defaults.table_duration_warning_minutes || 90,
            ),
            table_duration_danger_minutes: String(
                defaults.table_duration_danger_minutes || 180,
            ),
            metadata: {
                kitchen_voice: {
                    enabled: defaults.metadata?.kitchen_voice?.enabled ?? true,
                    volume: defaults.metadata?.kitchen_voice?.volume ?? 1.0,
                    rate: defaults.metadata?.kitchen_voice?.rate ?? 0.9,
                    pitch: defaults.metadata?.kitchen_voice?.pitch ?? 1.05,
                },
            },
        });

        form.reset();
        form.clearErrors();
    },
    { deep: true },
);

const testAlarmNotification = () => {
    try {
        const audioCtx = new (
            window.AudioContext || (window as any).webkitAudioContext
        )();
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        const playTone = (freq: number, start: number, duration: number) => {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.frequency.setValueAtTime(freq, start);
            gain.gain.setValueAtTime(0, start);
            gain.gain.linearRampToValueAtTime(0.2, start + 0.05);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(start);
            osc.stop(start + duration);
        };
        playTone(587.33, audioCtx.currentTime, 0.4);
        playTone(440.0, audioCtx.currentTime + 0.15, 0.6);
    } catch (e) {
        console.error(e);
    }
};

const testVoiceNotification = () => {
    if (!('speechSynthesis' in window)) {
        alert('Browser Anda tidak mendukung Text-to-Speech.');
        return;
    }

    try {
        const audioCtx = new (
            window.AudioContext || (window as any).webkitAudioContext
        )();
        const playTone = (freq: number, start: number, duration: number) => {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.frequency.setValueAtTime(freq, start);
            gain.gain.setValueAtTime(0, start);
            gain.gain.linearRampToValueAtTime(0.2, start + 0.05);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(start);
            osc.stop(start + duration);
        };
        playTone(587.33, audioCtx.currentTime, 0.4);
        playTone(440.0, audioCtx.currentTime + 0.15, 0.6);
    } catch (e) {
        console.error(e);
    }

    setTimeout(() => {
        const utterance = new SpeechSynthesisUtterance(
            'Uji coba pengeras suara dapur. Halo mentai restoran.',
        );
        utterance.lang = 'id-ID';
        utterance.volume = Number(form.metadata.kitchen_voice.volume);
        utterance.rate = Number(form.metadata.kitchen_voice.rate);
        utterance.pitch = Number(form.metadata.kitchen_voice.pitch);

        const voices = window.speechSynthesis.getVoices();
        const idVoice = voices.find((v) => v.lang.includes('id'));
        if (idVoice) utterance.voice = idVoice;

        window.speechSynthesis.speak(utterance);
    }, 400);
};

const summaryCards = computed(() => [
    {
        label: 'Total Outlet',
        value: props.summary.total_outlets,
        helper: `${props.summary.configured_outlets} outlet sudah punya setting notifikasi`,
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
        icon: Store,
    },
    {
        label: 'WhatsApp Ready',
        value: props.summary.whatsapp_enabled,
        helper: 'Outlet yang mengaktifkan channel WhatsApp',
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: BellRing,
    },
    {
        label: 'Email Ready',
        value: props.summary.email_enabled,
        helper: 'Outlet yang mengaktifkan channel email',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Mail,
    },
    {
        label: 'Threshold Kasbon',
        value: `${form.kasbon_due_threshold_days || 3} hari`,
        helper: 'Batas umur kasbon untuk masuk alert overdue',
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: Wallet,
    },
]);

function formatCurrency(value: number | string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

function formatDateTime(value?: string | null) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

function formatChannelLabel(value: ChannelValue) {
    if (value === 'in_app') return 'In-app';
    if (value === 'whatsapp') return 'WhatsApp';
    return 'Email';
}

function formatOrderStatus(value: string) {
    switch (value) {
        case 'in_progress':
            return 'Diproses';
        case 'waiting_bar_approval':
            return 'Menunggu bar';
        case 'ready':
            return 'Siap';
        default:
            return 'Pending';
    }
}

function openSelectedOutlet(outletId: string) {
    router.get(
        route('settings.notifications.index'),
        {
            outlet_id: outletId || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function toggleChannel(
    field:
        | 'low_stock_channels'
        | 'kasbon_due_channels'
        | 'online_order_channels',
    channel: ChannelValue,
    checked: boolean,
) {
    const next = new Set(form[field]);

    if (checked) {
        next.add(channel);
    } else {
        next.delete(channel);
    }

    form[field] = Array.from(next.values()) as ChannelValue[];
}

function submitSave() {
    form.put(route('settings.notifications.update'), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Notifikasi & Alert" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-stone-900 dark:text-white"
                    >
                        Notifikasi & Alert Sistem
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Kelola kanal notifikasi untuk alert stok menipis, kasbon
                        overdue, dan order online baru berdasarkan outlet aktif.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <!-- Tab Navigation Keamanan & Notifikasi -->
            <div
                class="flex w-full gap-1 rounded-2xl border-b border-stone-200 bg-stone-50 p-1 dark:border-slate-800 dark:bg-slate-900/40"
            >
                <Link
                    :href="route('settings.notifications.index')"
                    class="flex-1 rounded-xl py-2 text-center text-[10px] font-bold uppercase tracking-wider transition duration-150 sm:text-xs"
                    :class="
                        route().current('settings.notifications.index')
                            ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10'
                            : 'text-stone-500 hover:bg-white/[0.02] hover:text-stone-800 dark:text-slate-200 dark:text-slate-400'
                    "
                >
                    Notifikasi & Alert
                </Link>
                <Link
                    :href="route('settings.backup-security.index')"
                    class="flex-1 rounded-xl py-2 text-center text-[10px] font-bold uppercase tracking-wider transition duration-150 sm:text-xs"
                    :class="
                        route().current('settings.backup-security.index')
                            ? 'bg-orange-500 text-slate-950 shadow-md shadow-orange-500/10'
                            : 'text-stone-500 hover:bg-white/[0.02] hover:text-stone-800 dark:text-slate-200 dark:text-slate-400'
                    "
                >
                    Backup & Keamanan
                </Link>
            </div>

            <div
                v-if="success"
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/12 px-4 py-3 text-sm font-medium text-emerald-300"
            >
                {{ success }}
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-[24px] border p-4"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-[11px] font-semibold uppercase tracking-[0.24em] text-stone-500 dark:text-slate-400"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-3 text-2xl font-black"
                                :class="card.tone"
                            >
                                {{ card.value }}
                            </p>
                            <p
                                class="mt-2 text-xs text-stone-500 dark:text-slate-400"
                            >
                                {{ card.helper }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-950/40"
                        >
                            <component
                                :is="card.icon"
                                class="h-5 w-5 text-stone-900 dark:text-white"
                            />
                        </div>
                    </div>
                </article>
            </section>

            <div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
                <section
                    class="rounded-[28px] border border-stone-200 bg-stone-50 p-5 shadow-2xl shadow-slate-950/20 dark:border-slate-800 dark:bg-slate-900/70"
                >
                    <div class="grid gap-4 md:grid-cols-[1.05fr_0.95fr]">
                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                            >
                                Outlet aktif
                            </label>
                            <select
                                :value="form.outlet_id"
                                @change="
                                    openSelectedOutlet(
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                                class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-500 dark:border-slate-700 dark:bg-slate-950/80 dark:text-slate-100"
                            >
                                <option
                                    v-for="outlet in outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </div>

                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/60"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                            >
                                Ringkasan outlet
                            </p>
                            <div
                                class="mt-3 space-y-2 text-sm text-stone-800 dark:text-slate-200"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span>Status outlet</span>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                        :class="
                                            selectedOutlet?.is_active
                                                ? 'border border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
                                                : 'border border-amber-400/20 bg-amber-500/10 text-amber-300'
                                        "
                                    >
                                        {{
                                            selectedOutlet?.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span>Setting tersimpan</span>
                                    <span
                                        class="text-stone-500 dark:text-slate-400"
                                    >
                                        {{
                                            formDefaults.has_config
                                                ? 'Sudah ada override'
                                                : 'Masih default'
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span>Channel aktif</span>
                                    <span
                                        class="text-right text-stone-500 dark:text-slate-400"
                                    >
                                        {{
                                            [
                                                ...new Set([
                                                    ...form.low_stock_channels,
                                                    ...form.kasbon_due_channels,
                                                    ...form.online_order_channels,
                                                ]),
                                            ]
                                                .map((value) =>
                                                    formatChannelLabel(
                                                        value as ChannelValue,
                                                    ),
                                                )
                                                .join(', ') || '-'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submitSave">
                        <article
                            class="rounded-[24px] border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/55"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-3"
                                    >
                                        <Box class="h-5 w-5 text-amber-300" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-base font-black text-stone-900 dark:text-white"
                                        >
                                            Low Stock Notifications
                                        </h3>
                                        <p
                                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                        >
                                            Alert saat stok produk jadi atau
                                            bahan baku turun di bawah minimum.
                                        </p>
                                    </div>
                                </div>
                                <label
                                    class="inline-flex cursor-pointer items-center"
                                >
                                    <input
                                        v-model="form.low_stock_enabled"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="relative h-6 w-11 rounded-full bg-slate-700 transition after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-orange-500 peer-checked:after:translate-x-full"
                                    ></div>
                                </label>
                            </div>
                            <div
                                class="mt-4 border-t border-stone-200 pt-4 dark:border-slate-800"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                >
                                    Channels
                                </p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                    <label
                                        v-for="channel in alertOptions.channels"
                                        :key="`low-${channel.value}`"
                                        class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-3 dark:border-slate-800 dark:bg-slate-900/60"
                                    >
                                        <input
                                            :checked="
                                                form.low_stock_channels.includes(
                                                    channel.value,
                                                )
                                            "
                                            type="checkbox"
                                            class="mt-1 h-4 w-4 rounded border-stone-300 bg-stone-100 text-orange-500 focus:ring-orange-500 dark:border-slate-600 dark:bg-slate-950"
                                            @change="
                                                toggleChannel(
                                                    'low_stock_channels',
                                                    channel.value,
                                                    (
                                                        $event.target as HTMLInputElement
                                                    ).checked,
                                                )
                                            "
                                        />
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-stone-900 dark:text-white"
                                            >
                                                {{ channel.label }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                            >
                                                {{ channel.description }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </article>

                        <article
                            class="rounded-[24px] border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/55"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-3"
                                    >
                                        <Wallet class="h-5 w-5 text-rose-300" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-base font-black text-stone-900 dark:text-white"
                                        >
                                            Customer Debt Due
                                        </h3>
                                        <p
                                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                        >
                                            Karena schema aktif belum punya
                                            `due_date`, overdue kasbon dihitung
                                            dari umur order belum lunas.
                                        </p>
                                    </div>
                                </div>
                                <label
                                    class="inline-flex cursor-pointer items-center"
                                >
                                    <input
                                        v-model="form.kasbon_due_enabled"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="relative h-6 w-11 rounded-full bg-slate-700 transition after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-orange-500 peer-checked:after:translate-x-full"
                                    ></div>
                                </label>
                            </div>
                            <div
                                class="mt-4 grid gap-4 border-t border-stone-200 pt-4 dark:border-slate-800 md:grid-cols-[180px_minmax(0,1fr)]"
                            >
                                <div>
                                    <label
                                        class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                    >
                                        Threshold overdue
                                    </label>
                                    <input
                                        v-model="form.kasbon_due_threshold_days"
                                        type="number"
                                        min="1"
                                        max="30"
                                        class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-500 dark:border-slate-700 dark:bg-slate-950/80 dark:text-slate-100"
                                    />
                                    <p
                                        v-if="
                                            form.errors
                                                .kasbon_due_threshold_days
                                        "
                                        class="mt-2 text-xs text-rose-300"
                                    >
                                        {{
                                            form.errors
                                                .kasbon_due_threshold_days
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                    >
                                        Channels
                                    </p>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                        <label
                                            v-for="channel in alertOptions.channels"
                                            :key="`kasbon-${channel.value}`"
                                            class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-3 dark:border-slate-800 dark:bg-slate-900/60"
                                        >
                                            <input
                                                :checked="
                                                    form.kasbon_due_channels.includes(
                                                        channel.value,
                                                    )
                                                "
                                                type="checkbox"
                                                class="mt-1 h-4 w-4 rounded border-stone-300 bg-stone-100 text-orange-500 focus:ring-orange-500 dark:border-slate-600 dark:bg-slate-950"
                                                @change="
                                                    toggleChannel(
                                                        'kasbon_due_channels',
                                                        channel.value,
                                                        (
                                                            $event.target as HTMLInputElement
                                                        ).checked,
                                                    )
                                                "
                                            />
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-stone-900 dark:text-white"
                                                >
                                                    {{ channel.label }}
                                                </p>
                                                <p
                                                    class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                                >
                                                    {{ channel.description }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article
                            class="rounded-[24px] border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/55"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-2xl border border-sky-500/20 bg-sky-500/10 p-3"
                                    >
                                        <ShoppingBag
                                            class="h-5 w-5 text-sky-300"
                                        />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-base font-black text-stone-900 dark:text-white"
                                        >
                                            New Online Order
                                        </h3>
                                        <p
                                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                        >
                                            Alert untuk order GoFood/GrabFood
                                            yang masuk dan masih aktif hari ini.
                                        </p>
                                    </div>
                                </div>
                                <label
                                    class="inline-flex cursor-pointer items-center"
                                >
                                    <input
                                        v-model="form.online_order_enabled"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="relative h-6 w-11 rounded-full bg-slate-700 transition after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-orange-500 peer-checked:after:translate-x-full"
                                    ></div>
                                </label>
                            </div>
                            <div
                                class="mt-4 border-t border-stone-200 pt-4 dark:border-slate-800"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                >
                                    Channels
                                </p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                    <label
                                        v-for="channel in alertOptions.channels"
                                        :key="`online-${channel.value}`"
                                        class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-3 dark:border-slate-800 dark:bg-slate-900/60"
                                    >
                                        <input
                                            :checked="
                                                form.online_order_channels.includes(
                                                    channel.value,
                                                )
                                            "
                                            type="checkbox"
                                            class="mt-1 h-4 w-4 rounded border-stone-300 bg-stone-100 text-orange-500 focus:ring-orange-500 dark:border-slate-600 dark:bg-slate-950"
                                            @change="
                                                toggleChannel(
                                                    'online_order_channels',
                                                    channel.value,
                                                    (
                                                        $event.target as HTMLInputElement
                                                    ).checked,
                                                )
                                            "
                                        />
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-stone-900 dark:text-white"
                                            >
                                                {{ channel.label }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                            >
                                                {{ channel.description }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </article>

                        <article
                            class="rounded-[24px] border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/55"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-2xl border border-orange-500/20 bg-orange-500/10 p-3"
                                    >
                                        <BellRing
                                            class="h-5 w-5 text-orange-400"
                                        />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-base font-black text-stone-900 dark:text-white"
                                        >
                                            Alert Durasi Meja (Smart Timer)
                                        </h3>
                                        <p
                                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                        >
                                            Peringatan durasi pemakaian meja
                                            makan di tempat jika melampaui batas
                                            waktu.
                                        </p>
                                    </div>
                                </div>
                                <label
                                    class="inline-flex cursor-pointer items-center"
                                >
                                    <input
                                        v-model="
                                            form.table_duration_alert_enabled
                                        "
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="relative h-6 w-11 rounded-full bg-slate-700 transition after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-orange-500 peer-checked:after:translate-x-full"
                                    ></div>
                                </label>
                            </div>
                            <div
                                v-if="form.table_duration_alert_enabled"
                                class="mt-4 grid gap-4 border-t border-stone-200 pt-4 dark:border-slate-800 sm:grid-cols-2"
                            >
                                <div>
                                    <label
                                        class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                    >
                                        Threshold Warning (Kuning - Menit)
                                    </label>
                                    <input
                                        v-model="
                                            form.table_duration_warning_minutes
                                        "
                                        type="number"
                                        min="1"
                                        class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-500 dark:border-slate-700 dark:bg-slate-950/80 dark:text-slate-100"
                                    />
                                    <p
                                        v-if="
                                            form.errors
                                                .table_duration_warning_minutes
                                        "
                                        class="mt-2 text-xs text-rose-300"
                                    >
                                        {{
                                            form.errors
                                                .table_duration_warning_minutes
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                    >
                                        Threshold Danger (Merah - Menit)
                                    </label>
                                    <input
                                        v-model="
                                            form.table_duration_danger_minutes
                                        "
                                        type="number"
                                        min="1"
                                        class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-500 dark:border-slate-700 dark:bg-slate-950/80 dark:text-slate-100"
                                    />
                                    <p
                                        v-if="
                                            form.errors
                                                .table_duration_danger_minutes
                                        "
                                        class="mt-2 text-xs text-rose-300"
                                    >
                                        {{
                                            form.errors
                                                .table_duration_danger_minutes
                                        }}
                                    </p>
                                </div>
                            </div>
                        </article>

                        <article
                            class="rounded-[24px] border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/55"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-2xl border border-fuchsia-500/20 bg-fuchsia-500/10 p-3"
                                    >
                                        <Volume2
                                            class="h-5 w-5 text-fuchsia-300"
                                        />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-base font-black text-stone-900 dark:text-stone-900 dark:text-white"
                                        >
                                            Notifikasi Suara Dapur (TTS)
                                        </h3>
                                        <p
                                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                        >
                                            Browser di Kitchen Display System
                                            akan membunyikan bel dan membaca
                                            pesanan baru secara otomatis.
                                        </p>
                                    </div>
                                </div>
                                <label
                                    class="inline-flex cursor-pointer items-center"
                                >
                                    <input
                                        v-model="
                                            form.metadata.kitchen_voice.enabled
                                        "
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="relative h-6 w-11 rounded-full bg-slate-700 transition after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-orange-500 peer-checked:after:translate-x-full"
                                    ></div>
                                </label>
                            </div>

                            <div
                                v-if="form.metadata.kitchen_voice.enabled"
                                class="mt-4 grid gap-4 border-t border-stone-200 pt-4 dark:border-slate-800 sm:grid-cols-3"
                            >
                                <div>
                                    <label
                                        class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                    >
                                        Volume Suara ({{
                                            Math.round(
                                                form.metadata.kitchen_voice
                                                    .volume * 100,
                                            )
                                        }}%)
                                    </label>
                                    <input
                                        v-model.number="
                                            form.metadata.kitchen_voice.volume
                                        "
                                        type="range"
                                        min="0"
                                        max="1"
                                        step="0.1"
                                        class="w-full bg-stone-100 accent-orange-500 dark:bg-slate-800"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                    >
                                        Kecepatan Bicara ({{
                                            form.metadata.kitchen_voice.rate
                                        }}x)
                                    </label>
                                    <input
                                        v-model.number="
                                            form.metadata.kitchen_voice.rate
                                        "
                                        type="range"
                                        min="0.5"
                                        max="1.5"
                                        step="0.1"
                                        class="w-full bg-stone-100 accent-orange-500 dark:bg-slate-800"
                                    />
                                </div>
                                <div class="flex items-end gap-2">
                                    <button
                                        type="button"
                                        @click="testAlarmNotification"
                                        class="w-full rounded-xl border border-slate-800 bg-stone-50 py-2.5 text-xs font-semibold text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 dark:bg-slate-800 dark:bg-slate-900/40 dark:text-slate-300 dark:text-white"
                                    >
                                        Uji Coba Alarm
                                    </button>
                                    <button
                                        type="button"
                                        @click="testVoiceNotification"
                                        class="w-full rounded-xl border border-stone-200 bg-stone-50 py-2.5 text-xs font-semibold text-stone-900 transition hover:bg-stone-100 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900/60 dark:text-white"
                                    >
                                        Uji Coba Suara
                                    </button>
                                </div>
                            </div>
                        </article>

                        <div
                            class="flex justify-end border-t border-stone-200 pt-5 dark:border-slate-800"
                        >
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-orange-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                {{
                                    form.processing
                                        ? 'Menyimpan...'
                                        : 'Simpan setting notifikasi'
                                }}
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="space-y-5">
                    <section
                        class="rounded-[28px] border border-stone-200 bg-stone-50 p-5 shadow-2xl shadow-slate-950/20 dark:border-slate-800 dark:bg-slate-900/70"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-300"
                                >
                                    Alert Snapshot
                                </p>
                                <h3
                                    class="mt-1 text-lg font-black text-stone-900 dark:text-white"
                                >
                                    Kondisi alert outlet saat ini
                                </h3>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950/70"
                            >
                                <CheckCircle2
                                    class="h-5 w-5 text-stone-800 dark:text-slate-200"
                                />
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div
                                class="rounded-2xl border border-amber-500/20 bg-amber-500/8 p-4"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-bold text-amber-300"
                                        >
                                            Low Stock
                                        </p>
                                        <!-- prettier-ignore -->
                                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                            {{ snapshots.low_stock.count }} item di bawah minimum, {{ snapshots.low_stock.critical }} kritikal.
                                        </p>
                                    </div>
                                    <span
                                        class="text-xl font-black text-stone-900 dark:text-white"
                                        >{{ snapshots.low_stock.count }}</span
                                    >
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-rose-500/20 bg-rose-500/8 p-4"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-bold text-rose-300"
                                        >
                                            Kasbon Overdue
                                        </p>
                                        <!-- prettier-ignore -->
                                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                            {{ snapshots.kasbon_due.count }} order, total outstanding {{ formatCurrency(snapshots.kasbon_due.total_outstanding) }}.
                                        </p>
                                    </div>
                                    <span
                                        class="text-xl font-black text-stone-900 dark:text-white"
                                        >{{ snapshots.kasbon_due.count }}</span
                                    >
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-sky-500/20 bg-sky-500/8 p-4"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-bold text-sky-300"
                                        >
                                            Online Order Aktif
                                        </p>
                                        <!-- prettier-ignore -->
                                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                            {{ snapshots.online_order.count }} order aktif dari {{ snapshots.online_order.today_orders }} order online hari ini.
                                        </p>
                                    </div>
                                    <span
                                        class="text-xl font-black text-stone-900 dark:text-white"
                                        >{{
                                            snapshots.online_order.count
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[28px] border border-stone-200 bg-stone-50 p-5 shadow-2xl shadow-slate-950/20 dark:border-slate-800 dark:bg-slate-900/70"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-300"
                                >
                                    Preview Feed
                                </p>
                                <h3
                                    class="mt-1 text-lg font-black text-stone-900 dark:text-white"
                                >
                                    Contoh alert terdekat
                                </h3>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                >
                                    Low stock teratas
                                </p>
                                <div
                                    v-if="
                                        snapshots.low_stock.items.length === 0
                                    "
                                    class="mt-3 text-sm text-stone-400 dark:text-slate-500"
                                >
                                    Belum ada alert stok menipis di outlet ini.
                                </div>
                                <div v-else class="mt-3 space-y-3">
                                    <div
                                        v-for="item in snapshots.low_stock
                                            .items"
                                        :key="item.id"
                                        class="rounded-2xl border border-stone-200 bg-stone-50 p-3 dark:border-slate-800 dark:bg-slate-900/60"
                                    >
                                        <p
                                            class="text-sm font-semibold text-stone-900 dark:text-white"
                                        >
                                            {{ item.name }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                        >
                                            {{ item.context }} •
                                            {{ item.current_stock }}/{{
                                                item.minimum_stock
                                            }}
                                            {{ item.unit }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                >
                                    Kasbon overdue teratas
                                </p>
                                <div
                                    v-if="
                                        snapshots.kasbon_due.items.length === 0
                                    "
                                    class="mt-3 text-sm text-stone-400 dark:text-slate-500"
                                >
                                    Belum ada kasbon melewati threshold
                                    {{ form.kasbon_due_threshold_days }} hari.
                                </div>
                                <div v-else class="mt-3 space-y-3">
                                    <div
                                        v-for="item in snapshots.kasbon_due
                                            .items"
                                        :key="item.id"
                                        class="rounded-2xl border border-stone-200 bg-stone-50 p-3 dark:border-slate-800 dark:bg-slate-900/60"
                                    >
                                        <p
                                            class="text-sm font-semibold text-stone-900 dark:text-white"
                                        >
                                            {{ item.order_number }} •
                                            {{ item.customer_name }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                                        >
                                            {{
                                                formatCurrency(
                                                    item.outstanding_amount,
                                                )
                                            }}
                                            • umur {{ item.age_days }} hari •
                                            {{
                                                formatDateTime(item.created_at)
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/60"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-400 dark:text-slate-500"
                                >
                                    Order online terbaru
                                </p>
                                <div
                                    v-if="
                                        snapshots.online_order.items.length ===
                                        0
                                    "
                                    class="mt-3 text-sm text-stone-400 dark:text-slate-500"
                                >
                                    Belum ada order online aktif hari ini.
                                </div>
                                <div v-else class="mt-3 space-y-3">
                                    <div
                                        v-for="item in snapshots.online_order
                                            .items"
                                        :key="item.id"
                                        class="rounded-2xl border border-stone-200 bg-stone-50 p-3 dark:border-slate-800 dark:bg-slate-900/60"
                                    >
                                        <p
                                            class="text-sm font-semibold text-stone-900 dark:text-white"
                                        >
                                            {{ item.order_number }} •
                                            {{ item.platform?.toUpperCase() }}
                                        </p>
                                        <!-- prettier-ignore -->
                                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                                            {{ formatOrderStatus(item.status) }} • {{ formatCurrency(item.total_amount) }} • {{ formatDateTime(item.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
