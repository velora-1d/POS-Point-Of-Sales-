<script setup lang="ts">
import { AlertTriangle, Bell, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface TableAlert {
    id: string;
    name: string;
    occupiedAt: string;
    capacity: number | null;
    currentGuests: number;
    remainingCapacity: number | null;
}

const props = defineProps<{
    tables: any[];
    alertSettings?: {
        enabled: boolean;
        warning_minutes: number;
        danger_minutes: number;
    };
}>();

const dismissedIds = ref<Set<string>>(new Set());
const currentMinutes = ref<Record<string, number>>({});
let interval: ReturnType<typeof setInterval> | null = null;

const alertSettings = computed(() => ({
    enabled: props.alertSettings?.enabled ?? true,
    warning_minutes: props.alertSettings?.warning_minutes ?? 90,
    danger_minutes: props.alertSettings?.danger_minutes ?? 180,
}));

const updateMinutes = () => {
    const now = Date.now();
    props.tables.forEach((table) => {
        if (table.occupied_at) {
            const occupiedAt = new Date(table.occupied_at).getTime();
            currentMinutes.value[table.id] = Math.floor(
                (now - occupiedAt) / 60000,
            );
        }
    });
};

onMounted(() => {
    updateMinutes();
    interval = setInterval(updateMinutes, 30000); // update tiap 30 detik
});

onBeforeUnmount(() => {
    if (interval) clearInterval(interval);
});

const alertTables = computed<TableAlert[]>(() => {
    if (!alertSettings.value.enabled) return [];

    return props.tables
        .filter((table) => {
            if (
                table.status !== 'occupied' ||
                !table.occupied_at ||
                dismissedIds.value.has(table.id)
            )
                return false;

            const minutes = currentMinutes.value[table.id] ?? 0;
            return minutes >= alertSettings.value.warning_minutes;
        })
        .map((table) => ({
            id: table.id,
            name: table.name,
            occupiedAt: table.occupied_at,
            capacity: table.capacity ?? null,
            currentGuests: table.current_guests ?? 0,
            remainingCapacity: table.remaining_capacity ?? null,
        }));
});

const getTimerStatus = (tableId: string): 'warning' | 'danger' => {
    const minutes = currentMinutes.value[tableId] ?? 0;
    return minutes >= alertSettings.value.danger_minutes ? 'danger' : 'warning';
};

const formatDuration = (tableId: string): string => {
    const minutes = currentMinutes.value[tableId] ?? 0;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h > 0) return `${h}j ${m}m`;
    return `${m}m`;
};

const dismiss = (tableId: string) => {
    dismissedIds.value.add(tableId);
};
</script>

<template>
    <!-- Toast Container — muncul di pojok kanan bawah -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="alertTables.length > 0"
            class="fixed bottom-6 right-6 z-50 flex max-w-sm flex-col gap-3"
        >
            <!-- Header badge -->
            <div
                class="flex items-center gap-2 self-end rounded-full border border-amber-500/30 bg-amber-500/15 px-3 py-1.5 text-xs font-bold text-amber-200 backdrop-blur"
            >
                <Bell class="h-3.5 w-3.5 animate-pulse" />
                {{ alertTables.length }} Meja Perlu Perhatian
            </div>

            <!-- Alert card per meja -->
            <div
                v-for="table in alertTables"
                :key="table.id"
                :class="[
                    'relative rounded-[20px] border p-4 shadow-2xl backdrop-blur transition',
                    getTimerStatus(table.id) === 'danger'
                        ? 'border-rose-500/30 bg-rose-950/70'
                        : 'border-amber-500/30 bg-amber-950/70',
                ]"
            >
                <!-- Dismiss -->
                <button
                    type="button"
                    @click="dismiss(table.id)"
                    class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full border border-white/10 text-slate-400 hover:text-white"
                >
                    <X class="h-3.5 w-3.5" />
                </button>

                <div class="flex items-start gap-3 pr-6">
                    <div
                        :class="[
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border',
                            getTimerStatus(table.id) === 'danger'
                                ? 'border-rose-500/30 bg-rose-500/15 text-rose-300'
                                : 'border-amber-500/30 bg-amber-500/15 text-amber-300',
                        ]"
                    >
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black text-white">
                            {{ table.name }}
                        </p>
                        <p
                            :class="[
                                'mt-0.5 text-xs font-bold',
                                getTimerStatus(table.id) === 'danger'
                                    ? 'text-rose-300'
                                    : 'text-amber-300',
                            ]"
                        >
                            ⏱ {{ formatDuration(table.id) }}
                            {{
                                getTimerStatus(table.id) === 'danger'
                                    ? '— Durasi sangat lama!'
                                    : '— Perhatikan meja ini'
                            }}
                        </p>
                        <div
                            v-if="table.capacity"
                            class="mt-2 flex items-center gap-2"
                        >
                            <div
                                class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-800"
                            >
                                <div
                                    class="h-full rounded-full bg-orange-400 transition-all duration-500"
                                    :style="{
                                        width:
                                            Math.min(
                                                100,
                                                Math.round(
                                                    ((table.currentGuests ??
                                                        0) /
                                                        table.capacity) *
                                                        100,
                                                ),
                                            ) + '%',
                                    }"
                                />
                            </div>
                            <span
                                class="shrink-0 text-[10px] font-bold text-slate-400"
                                >{{ table.currentGuests }}/{{
                                    table.capacity
                                }}
                                Orang</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
