<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Link2,
    Palette,
    QrCode,
    RefreshCw,
    Store,
    X,
} from '@lucide/vue';
import AlertDialog from '@/Components/AlertDialog.vue';
import { computed, ref, watch } from 'vue';

interface OutletOption {
    id: string;
    name: string;
    is_active: boolean;
    has_config: boolean;
    active_tables_count: number;
}

interface TemplateOption {
    value: string;
    label: string;
    description: string;
}

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
        active_tables: number;
        public_qr_ready: number;
    };
    formDefaults: {
        outlet_id: string | null;
        base_url: string;
        store_slug: string;
        qr_template: 'classic_sharp' | 'modern_rounded' | 'branded_center';
        primary_color: string;
        has_config: boolean;
    };
    preview: {
        sample_table_name: string;
        sample_table_code: string;
        sample_url: string;
        qr_template: 'classic_sharp' | 'modern_rounded' | 'branded_center';
        primary_color: string;
    };
    regeneration: {
        active_tables_count: number;
        ready_qr_count: number;
        last_regenerated_at?: string | null;
    };
    qrOptions: {
        templates: TemplateOption[];
        colors: string[];
    };
    filters: {
        outlet_id?: string | null;
    };
    success?: string | null;
}>();

const alertDialog = ref({
    show: false,
    title: '',
    message: '',
    type: 'confirm' as 'info' | 'success' | 'warning' | 'danger' | 'confirm',
    onConfirm: () => {},
});

const closeAlertDialog = () => {
    alertDialog.value.show = false;
};

const qrForm = useForm({
    outlet_id: props.formDefaults.outlet_id || props.selectedOutlet?.id || '',
    store_slug: props.formDefaults.store_slug || '',
    qr_template: props.formDefaults.qr_template || 'classic_sharp',
    primary_color: props.formDefaults.primary_color || '#111827',
});

const regenerateForm = useForm({
    outlet_id: props.formDefaults.outlet_id || props.selectedOutlet?.id || '',
});

watch(
    () => props.formDefaults,
    (defaults) => {
        qrForm.defaults({
            outlet_id: defaults.outlet_id || props.selectedOutlet?.id || '',
            store_slug: defaults.store_slug || '',
            qr_template: defaults.qr_template || 'classic_sharp',
            primary_color: defaults.primary_color || '#111827',
        });

        qrForm.reset();
        qrForm.clearErrors();
        regenerateForm.outlet_id = defaults.outlet_id || props.selectedOutlet?.id || '';
    },
    { deep: true },
);

const summaryCards = computed(() => [
    {
        label: 'Total Outlet',
        value: props.summary.total_outlets,
        helper: `${props.summary.configured_outlets} outlet sudah punya config QR`,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: Store,
    },
    {
        label: 'Public QR Ready',
        value: props.summary.public_qr_ready,
        helper: 'Outlet dengan config scan URL publik aktif',
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: CheckCircle2,
    },
    {
        label: 'Active Tables',
        value: props.summary.active_tables,
        helper: 'Meja aktif lintas outlet',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: QrCode,
    },
    {
        label: 'Store Slug',
        value: qrForm.store_slug || '-',
        helper: 'Prefix URL publik untuk outlet terpilih',
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: Link2,
    },
]);

const selectedOutletOption = computed(() => {
    return props.outlets.find((outlet) => outlet.id === qrForm.outlet_id) || null;
});

const previewUrl = computed(() => {
    const baseUrl = props.formDefaults.base_url.replace(/\/$/, '');
    const slug = qrForm.store_slug || 'outlet';
    return `${baseUrl}/${slug}/${props.preview.sample_table_code}`;
});

const qrPreviewImage = computed(() => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=16&data=${encodeURIComponent(previewUrl.value)}`;
});

const lastRegeneratedLabel = computed(() => {
    if (!props.regeneration.last_regenerated_at) {
        return 'Belum pernah regenerate massal';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(props.regeneration.last_regenerated_at));
});

function openSelectedOutlet(outletId: string) {
    router.get(
        route('settings.table-qr.index'),
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

function submitSave() {
    qrForm.put(route('settings.table-qr.update'), {
        preserveScroll: true,
        preserveState: true,
    });
}

function submitRegenerate() {
    alertDialog.value = {
        show: true,
        title: 'Regenerate Kode QR',
        message: 'Bulk regenerate akan mengganti kode QR meja aktif untuk scan baru. Lanjutkan?',
        type: 'warning',
        onConfirm: () => {
            regenerateForm.post(route('settings.table-qr.regenerate'), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => closeAlertDialog(),
                onFinish: () => closeAlertDialog(),
            });
        },
    };
}
</script>

<template>
    <Head title="Konfigurasi QR Meja" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Konfigurasi QR Meja
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-slate-400">
                        Kelola store slug, template visual QR, warna utama, dan bulk regenerate link scan publik
                        untuk seluruh meja aktif pada outlet terpilih.
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

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-[24px] border p-4"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-2xl font-black" :class="card.tone">{{ card.value }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ card.helper }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <component :is="card.icon" class="h-5 w-5 text-white" />
                        </div>
                    </div>
                </article>
            </section>

            <div class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-[28px] border border-slate-800 bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/20">
                    <div class="grid gap-4 md:grid-cols-[1.05fr_0.95fr]">
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                Outlet aktif
                            </label>
                            <select
                                :value="qrForm.outlet_id"
                                @change="openSelectedOutlet(($event.target as HTMLSelectElement).value)"
                                class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-500"
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

                        <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                Ringkasan outlet
                            </p>
                            <div class="mt-3 space-y-2 text-sm text-slate-200">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Status outlet</span>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                        :class="selectedOutlet?.is_active
                                            ? 'border border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
                                            : 'border border-amber-400/20 bg-amber-500/10 text-amber-300'"
                                    >
                                        {{ selectedOutlet?.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Config QR</span>
                                    <span class="text-slate-400">
                                        {{ formDefaults.has_config ? 'Sudah tersimpan' : 'Masih default' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Meja aktif</span>
                                    <span class="text-slate-400">
                                        {{ selectedOutletOption?.active_tables_count || 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form class="mt-5 space-y-5" @submit.prevent="submitSave">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Base URL domain
                                </label>
                                <input
                                    :value="formDefaults.base_url"
                                    type="text"
                                    disabled
                                    class="w-full cursor-not-allowed rounded-2xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-sm text-slate-500"
                                />
                                <p class="mt-2 text-xs text-slate-500">
                                    Domain publik diambil dari konfigurasi aplikasi.
                                </p>
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Store slug / identifier
                                </label>
                                <input
                                    v-model="qrForm.store_slug"
                                    type="text"
                                    placeholder="contoh: mentai-central"
                                    class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-orange-500"
                                />
                                <p class="mt-2 text-xs text-slate-500">
                                    Dipakai pada URL scan: `{{ previewUrl }}`
                                </p>
                                <p v-if="qrForm.errors.store_slug" class="mt-2 text-xs text-rose-300">
                                    {{ qrForm.errors.store_slug }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                Template QR
                            </label>
                            <div class="grid gap-3 md:grid-cols-3">
                                <label
                                    v-for="option in qrOptions.templates"
                                    :key="option.value"
                                    class="flex cursor-pointer flex-col rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                                    :class="qrForm.qr_template === option.value ? 'border-orange-500/40 bg-orange-500/5' : ''"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-semibold text-white">{{ option.label }}</span>
                                        <input
                                            v-model="qrForm.qr_template"
                                            type="radio"
                                            name="qr_template"
                                            :value="option.value"
                                            class="h-4 w-4 border-slate-600 bg-slate-950 text-orange-500 focus:ring-orange-500"
                                        />
                                    </div>
                                    <div
                                        class="mt-3 flex h-24 items-center justify-center rounded-2xl border border-dashed border-slate-700 bg-white/95"
                                        :style="{ color: qrForm.primary_color }"
                                    >
                                        <QrCode class="h-12 w-12" />
                                    </div>
                                    <p class="mt-3 text-xs text-slate-400">{{ option.description }}</p>
                                </label>
                            </div>
                            <p v-if="qrForm.errors.qr_template" class="mt-2 text-xs text-rose-300">
                                {{ qrForm.errors.qr_template }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                Primary color
                            </label>
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    v-for="color in qrOptions.colors"
                                    :key="color"
                                    type="button"
                                    :aria-label="`Pilih warna ${color}`"
                                    class="h-9 w-9 rounded-full border-2 transition"
                                    :class="qrForm.primary_color === color ? 'border-white shadow-[0_0_0_4px_rgba(249,115,22,0.18)]' : 'border-transparent'"
                                    :style="{ backgroundColor: color }"
                                    @click="qrForm.primary_color = color"
                                ></button>
                                <input
                                    v-model="qrForm.primary_color"
                                    type="text"
                                    class="w-36 rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm uppercase text-slate-100 outline-none transition focus:border-orange-500"
                                />
                            </div>
                            <p v-if="qrForm.errors.primary_color" class="mt-2 text-xs text-rose-300">
                                {{ qrForm.errors.primary_color }}
                            </p>
                        </div>

                        <div class="flex justify-end border-t border-slate-800 pt-5">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-orange-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="qrForm.processing"
                            >
                                {{ qrForm.processing ? 'Menyimpan...' : 'Simpan konfigurasi QR' }}
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="space-y-5">
                    <section class="rounded-[28px] border border-slate-800 bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/20">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-300">
                                    Live Preview
                                </p>
                                <h3 class="mt-1 text-lg font-black text-white">
                                    Sample QR outlet
                                </h3>
                            </div>
                            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-3">
                                <Palette class="h-5 w-5 text-slate-200" />
                            </div>
                        </div>

                        <div class="mt-5 rounded-[24px] border border-slate-800 bg-white p-4">
                            <img
                                :src="qrPreviewImage"
                                :alt="`QR ${preview.sample_table_name}`"
                                class="mx-auto h-64 w-64 rounded-2xl object-contain"
                            />
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                Sample meja
                            </p>
                            <p class="mt-1 text-sm font-bold text-white">
                                {{ preview.sample_table_name }} • {{ preview.sample_table_code }}
                            </p>
                            <p class="mt-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                URL publik
                            </p>
                            <p class="mt-1 break-all text-xs text-slate-300">
                                {{ previewUrl }}
                            </p>
                        </div>
                    </section>

                    <section class="rounded-[28px] border border-slate-800 bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/20">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-300">
                                    Generate & Apply
                                </p>
                                <h3 class="mt-1 text-lg font-black text-white">
                                    Bulk regenerate QR meja
                                </h3>
                            </div>
                            <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-3">
                                <RefreshCw class="h-5 w-5 text-slate-200" />
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-950/60 p-4 text-sm text-slate-300">
                            <div class="flex items-center justify-between gap-3">
                                <span>Meja aktif outlet</span>
                                <span class="font-semibold text-white">{{ regeneration.active_tables_count }}</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span>Kode QR siap pakai</span>
                                <span class="font-semibold text-white">{{ regeneration.ready_qr_count }}</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span>Regenerate terakhir</span>
                                <span class="text-right text-xs text-slate-400">{{ lastRegeneratedLabel }}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-orange-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="regenerateForm.processing || !regenerateForm.outlet_id"
                            @click="submitRegenerate"
                        >
                            <RefreshCw class="h-4 w-4" />
                            {{ regenerateForm.processing ? 'Memproses...' : 'Bulk regenerate QR' }}
                        </button>

                        <p class="mt-3 text-[11px] leading-relaxed text-slate-500">
                            Aksi ini mengganti `qr_code` untuk meja aktif dan memutakhirkan link scan baru.
                            Token checkout internal tetap dipertahankan agar flow self-order yang sedang berjalan tidak ikut rusak.
                        </p>
                    </section>
                </aside>
            </div>
        </div>

        <AlertDialog
            :show="alertDialog.show"
            :title="alertDialog.title"
            :message="alertDialog.message"
            :type="alertDialog.type"
            @confirm="alertDialog.onConfirm"
            @cancel="closeAlertDialog"
        />
    </AuthenticatedLayout>
</template>
