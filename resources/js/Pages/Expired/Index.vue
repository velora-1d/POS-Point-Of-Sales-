<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BellRing,
    CalendarClock,
    PackageMinus,
    ShieldAlert,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface ExpiryItem {
    id: string;
    trackable_type: 'product' | 'raw_material';
    name: string;
    context?: string | null;
    quantity: number;
    batch_code?: string | null;
    expired_at: string;
    days_left: number;
    status: 'upcoming' | 'today' | 'expired';
    reminder_days?: number[];
    reminder_hit?: boolean;
    expired_action?: string | null;
    route: string;
}

const props = defineProps<{
    summary: {
        upcoming: number;
        today: number;
        expired: number;
        critical: number;
    };
    items: ExpiryItem[];
    upcomingItems: ExpiryItem[];
    todayItems: ExpiryItem[];
    expiredItems: ExpiryItem[];
    filters: {
        days?: number;
        type?: string;
        status?: string;
    };
    success?: string | null;
}>();

const days = ref(props.filters.days || 7);
const type = ref(props.filters.type || 'all');
const status = ref(props.filters.status || 'all');
const selectedItem = ref<ExpiryItem | null>(null);

const actionForm = useForm({
    action: 'acknowledge',
    notes: '',
});

const summaryCards = computed(() => [
    {
        label: 'Akan Expired',
        value: props.summary.upcoming,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
        icon: CalendarClock,
    },
    {
        label: 'Expired Hari Ini',
        value: props.summary.today,
        tone: 'text-orange-300',
        surface: 'border-orange-400/15 bg-orange-500/8',
        icon: BellRing,
    },
    {
        label: 'Sudah Expired',
        value: props.summary.expired,
        tone: 'text-rose-300',
        surface: 'border-rose-400/15 bg-rose-500/8',
        icon: AlertTriangle,
    },
    {
        label: 'Butuh Tindakan',
        value: props.summary.critical,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: ShieldAlert,
    },
]);

const submitFilters = () => {
    router.get(
        route('expired-tracking.index'),
        {
            days: days.value,
            type: type.value !== 'all' ? type.value : undefined,
            status: status.value !== 'all' ? status.value : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const formatDate = (value: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const getStatusClass = (item: ExpiryItem) => {
    if (item.status === 'expired') {
        return 'border-rose-500/20 bg-rose-500/10 text-rose-300';
    }

    if (item.status === 'today') {
        return 'border-orange-500/20 bg-orange-500/10 text-orange-300';
    }

    return 'border-amber-500/20 bg-amber-500/10 text-amber-300';
};

const getStatusLabel = (item: ExpiryItem) => {
    if (item.status === 'expired') {
        return `Lewat ${Math.abs(item.days_left)} hari`;
    }

    if (item.status === 'today') {
        return 'Expired hari ini';
    }

    return `H-${item.days_left}`;
};

const openActionModal = (item: ExpiryItem) => {
    selectedItem.value = item;
    actionForm.clearErrors();
    actionForm.action = item.status === 'expired' ? 'deactivate' : 'acknowledge';
    actionForm.notes = '';
};

const closeActionModal = () => {
    selectedItem.value = null;
    actionForm.clearErrors();
};

const submitAction = () => {
    if (!selectedItem.value) return;

    actionForm.post(route('expired-tracking.handle', selectedItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            selectedItem.value = null;
        },
    });
};
</script>

<template>
    <Head title="Reminder Expired Product" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <CalendarClock class="h-3.5 w-3.5" />
                    Menu #31 Reminder Expired Product
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Reminder Expired
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-slate-400">
                        Pantau batch stok yang mendekati tanggal expired, tandai tindakan admin, dan cek item yang sudah lewat masa aman.
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
                    v-for="stat in summaryCards"
                    :key="stat.label"
                    :class="[
                        'rounded-[22px] border px-4 py-4 shadow-lg shadow-slate-950/10',
                        stat.surface,
                    ]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                {{ stat.label }}
                            </p>
                            <p :class="['mt-2 text-3xl font-black', stat.tone]">
                                {{ stat.value }}
                            </p>
                        </div>
                        <component :is="stat.icon" class="h-5 w-5 text-slate-500" />
                    </div>
                </article>
            </section>

            <section
                class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
            >
                <form
                    class="grid gap-2 lg:grid-cols-[160px_200px_200px_110px]"
                    @submit.prevent="submitFilters"
                >
                    <input
                        v-model="days"
                        type="number"
                        min="1"
                        max="30"
                        class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-white focus:border-orange-500 focus:outline-none"
                    />

                    <select
                        v-model="type"
                        class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-white focus:border-orange-500 focus:outline-none"
                    >
                        <option value="all">Semua tipe</option>
                        <option value="product">Produk jadi</option>
                        <option value="raw_material">Bahan baku</option>
                    </select>

                    <select
                        v-model="status"
                        class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-white focus:border-orange-500 focus:outline-none"
                    >
                        <option value="all">Semua status</option>
                        <option value="upcoming">Akan expired</option>
                        <option value="today">Expired hari ini</option>
                        <option value="expired">Sudah expired</option>
                    </select>

                    <button
                        type="submit"
                        class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-white"
                    >
                        Terapkan
                    </button>
                </form>
            </section>

            <section
                class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 shadow-xl shadow-slate-950/15"
            >
                <div class="flex flex-col gap-2 border-b border-slate-800/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Daftar Reminder Aktif
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Menampilkan {{ items.length }} reminder sesuai filter aktif.
                        </p>
                    </div>
                    <Link
                        :href="route('stock-alerts.index')"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-orange-300 transition hover:text-orange-200"
                    >
                        <PackageMinus class="h-4 w-4" />
                        Buka Alert Stok Menipis
                    </Link>
                </div>

                <div
                    v-if="!items.length"
                    class="px-5 py-16 text-center text-sm text-slate-400"
                >
                    Belum ada reminder expired sesuai filter saat ini.
                </div>

                <div
                    v-else
                    class="grid gap-4 px-5 py-5 xl:grid-cols-2"
                >
                    <article
                        v-for="item in items"
                        :key="item.id"
                        class="rounded-[24px] border border-slate-800/80 bg-slate-950/60 p-5"
                    >
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-black text-white">
                                            {{ item.name }}
                                        </h3>
                                        <span
                                            :class="[
                                                'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]',
                                                getStatusClass(item),
                                            ]"
                                        >
                                            {{ getStatusLabel(item) }}
                                        </span>
                                        <span
                                            v-if="item.reminder_hit"
                                            class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-sky-300"
                                        >
                                            Reminder aktif
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400">
                                        {{ item.trackable_type === 'product' ? 'Produk jadi' : 'Bahan baku' }}
                                        • {{ item.context }}
                                        <span v-if="item.batch_code">• Batch {{ item.batch_code }}</span>
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        :href="route(item.route)"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-bold text-slate-200"
                                    >
                                        Buka Sumber
                                    </Link>
                                    <button
                                        type="button"
                                        @click="openActionModal(item)"
                                        class="inline-flex items-center gap-2 rounded-xl border border-orange-500/20 bg-orange-500/10 px-3 py-2 text-xs font-bold text-orange-300"
                                    >
                                        Tindak Lanjut
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        Tanggal Expired
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-white">
                                        {{ formatDate(item.expired_at) }}
                                    </p>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        Qty Batch
                                    </p>
                                    <p class="mt-2 text-xl font-black text-white">
                                        {{ item.quantity }}
                                    </p>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        Default Action
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-white">
                                        {{ item.expired_action || 'notify_only' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>

        <div
            v-if="selectedItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 backdrop-blur-sm"
            @click.self="closeActionModal"
        >
            <div class="w-full max-w-xl rounded-[28px] border border-slate-800/80 bg-slate-950 p-5 shadow-[0_30px_120px_rgba(2,6,23,0.7)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Tindak Lanjut Expired
                        </p>
                        <h3 class="mt-1 text-lg font-black text-white">
                            {{ selectedItem.name }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeActionModal"
                        class="rounded-xl border border-slate-800 bg-slate-900 p-2 text-slate-400"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="submitAction">
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-slate-300">
                            Action
                        </label>
                        <select
                            v-model="actionForm.action"
                            class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-500 focus:outline-none"
                        >
                            <option value="acknowledge">Acknowledge</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="dispose">Dispose / Buang stok</option>
                            <option value="override">Override expired</option>
                        </select>
                        <p v-if="actionForm.errors.action" class="mt-1 text-xs text-rose-300">
                            {{ actionForm.errors.action }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-semibold text-slate-300">
                            Catatan
                        </label>
                        <textarea
                            v-model="actionForm.notes"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-500 focus:outline-none"
                        ></textarea>
                        <p v-if="actionForm.errors.notes" class="mt-1 text-xs text-rose-300">
                            {{ actionForm.errors.notes }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeActionModal"
                            class="rounded-2xl border border-slate-800 px-4 py-3 text-sm font-semibold text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="actionForm.processing"
                            class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-white disabled:opacity-60"
                        >
                            {{ actionForm.processing ? 'Menyimpan...' : 'Simpan Tindakan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
