<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    Download,
    Lock,
    Shield,
    Store,
} from '@lucide/vue';
import { computed, watch } from 'vue';

interface OutletOption {
    id: string;
    name: string;
    is_active: boolean;
    has_config: boolean;
    last_backup_at?: string | null;
    last_backup_status?: string | null;
}

interface ActivityLog {
    id: string;
    actor_name: string;
    actor_role?: string | null;
    action: string;
    description: string;
    ip_address: string;
    status: string;
    created_at?: string | null;
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
        successful_backups: number;
        warning_logs: number;
    };
    formDefaults: {
        outlet_id: string | null;
        auto_backup_enabled: boolean;
        auto_backup_frequency: 'daily' | 'weekly' | 'monthly';
        auto_backup_time: string;
        retention_days: number;
        backup_channel: 'local_download' | 'cloud_storage' | 'hybrid';
        encryption_enabled: boolean;
        two_factor_required: boolean;
        has_config: boolean;
    };
    backupOptions: {
        frequencies: Array<{ value: string; label: string; description: string }>;
        channels: Array<{ value: string; label: string; description: string }>;
    };
    latestBackup: {
        status: string;
        performed_at?: string | null;
        file_name?: string | null;
        size_bytes: number;
        storage_label: string;
    };
    securityPosture: {
        encryption_status: boolean;
        two_factor_status: boolean;
        approval_pin_coverage: { total: number; secured: number };
        active_user_coverage: { total: number; secured: number };
        recent_warnings: number;
    };
    activityLogs: ActivityLog[];
    filters: {
        outlet_id?: string | null;
    };
    success?: string | null;
}>();

const form = useForm({
    outlet_id: props.formDefaults.outlet_id || props.selectedOutlet?.id || '',
    auto_backup_enabled: Boolean(props.formDefaults.auto_backup_enabled),
    auto_backup_frequency: props.formDefaults.auto_backup_frequency || 'daily',
    auto_backup_time: props.formDefaults.auto_backup_time || '03:00',
    retention_days: String(props.formDefaults.retention_days || 30),
    backup_channel: props.formDefaults.backup_channel || 'hybrid',
    encryption_enabled: Boolean(props.formDefaults.encryption_enabled),
    two_factor_required: Boolean(props.formDefaults.two_factor_required),
});

watch(
    () => props.formDefaults,
    (defaults) => {
        form.defaults({
            outlet_id: defaults.outlet_id || props.selectedOutlet?.id || '',
            auto_backup_enabled: Boolean(defaults.auto_backup_enabled),
            auto_backup_frequency: defaults.auto_backup_frequency || 'daily',
            auto_backup_time: defaults.auto_backup_time || '03:00',
            retention_days: String(defaults.retention_days || 30),
            backup_channel: defaults.backup_channel || 'hybrid',
            encryption_enabled: Boolean(defaults.encryption_enabled),
            two_factor_required: Boolean(defaults.two_factor_required),
        });

        form.reset();
        form.clearErrors();
    },
    { deep: true },
);

const summaryCards = computed(() => [
    {
        label: 'Total Outlet',
        value: props.summary.total_outlets,
        helper: `${props.summary.configured_outlets} outlet sudah punya konfigurasi backup`,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: Store,
    },
    {
        label: 'Backup Sukses',
        value: props.summary.successful_backups,
        helper: 'Outlet dengan status backup terakhir sukses',
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: CheckCircle2,
    },
    {
        label: 'Warning Log',
        value: props.summary.warning_logs,
        helper: 'Warning/error keamanan 7 hari terakhir',
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: AlertTriangle,
    },
    {
        label: 'Retensi Backup',
        value: `${form.retention_days || 30} hari`,
        helper: 'Target lama simpan arsip backup otomatis',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Clock3,
    },
]);

function formatDateTime(value?: string | null) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

function formatBytes(value: number) {
    if (!value) return '-';
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(2)} MB`;
}

function openSelectedOutlet(outletId: string) {
    router.get(
        route('settings.backup-security.index'),
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
    form.put(route('settings.backup-security.update'), {
        preserveScroll: true,
        preserveState: true,
    });
}

function selectBackupChannel(value: 'local_download' | 'cloud_storage' | 'hybrid') {
    form.backup_channel = value;
}

function downloadBackup() {
    if (!form.outlet_id) return;

    window.location.assign(
        route('settings.backup-security.download', {
            outlet_id: form.outlet_id,
        }),
    );
}
</script>

<template>
    <Head title="Backup & Keamanan Data" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <Shield class="h-3.5 w-3.5" />
                    Menu #60 Backup & Keamanan Data
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Backup & Keamanan Data
                    </h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Kelola jadwal backup, download arsip manual, dan audit aktivitas keamanan outlet.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border p-5"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-3xl font-black" :class="card.tone">
                                {{ card.value }}
                            </p>
                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                {{ card.helper }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <component :is="card.icon" class="h-5 w-5 text-slate-200" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-800/80 bg-slate-900/80 p-6">
                        <div class="flex flex-col gap-4 border-b border-slate-800/80 pb-5 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                    Pengaturan Outlet
                                </p>
                                <h3 class="mt-2 text-lg font-black text-white">
                                    Jadwal Backup Otomatis
                                </h3>
                                <p class="mt-1 text-sm text-slate-400">
                                    Konfigurasi per outlet untuk frekuensi backup, retensi, dan posture keamanan dasar.
                                </p>
                            </div>

                            <div class="w-full lg:w-72">
                                <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                    Pilih Outlet
                                </label>
                                <select
                                    :value="form.outlet_id"
                                    @change="openSelectedOutlet(($event.target as HTMLSelectElement).value)"
                                    class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
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
                        </div>

                        <div class="mt-6 grid gap-5 lg:grid-cols-2">
                            <div class="space-y-5 rounded-3xl border border-slate-800 bg-slate-950/50 p-5">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-bold text-white">Backup Otomatis</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-400">
                                            Aktifkan jadwal backup rutin untuk outlet terpilih.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="form.auto_backup_enabled = !form.auto_backup_enabled"
                                        class="rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider"
                                        :class="form.auto_backup_enabled ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 bg-slate-900 text-slate-400'"
                                    >
                                        {{ form.auto_backup_enabled ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                            Frekuensi
                                        </label>
                                        <select
                                            v-model="form.auto_backup_frequency"
                                            class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                        >
                                            <option
                                                v-for="option in backupOptions.frequencies"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                            Jam Backup
                                        </label>
                                        <input
                                            v-model="form.auto_backup_time"
                                            type="time"
                                            class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        Retensi Arsip
                                    </label>
                                    <input
                                        v-model="form.retention_days"
                                        type="number"
                                        min="7"
                                        max="365"
                                        class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                    />
                                </div>
                            </div>

                            <div class="space-y-5 rounded-3xl border border-slate-800 bg-slate-950/50 p-5">
                                <div>
                                    <p class="text-sm font-bold text-white">Kanal Penyimpanan</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-400">
                                        Tandai channel backup yang ingin diprioritaskan per outlet.
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    <button
                                        v-for="option in backupOptions.channels"
                                        :key="option.value"
                                        type="button"
                                        @click="selectBackupChannel(option.value as 'local_download' | 'cloud_storage' | 'hybrid')"
                                        class="w-full rounded-2xl border p-4 text-left transition"
                                        :class="form.backup_channel === option.value ? 'border-orange-500/25 bg-orange-500/10 text-orange-100' : 'border-slate-800 bg-slate-950 text-slate-300'"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-sm font-bold">{{ option.label }}</p>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                {{ option.value }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-xs leading-5 text-slate-400">
                                            {{ option.description }}
                                        </p>
                                    </button>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <button
                                        type="button"
                                        @click="form.encryption_enabled = !form.encryption_enabled"
                                        class="rounded-2xl border p-4 text-left transition"
                                        :class="form.encryption_enabled ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-200' : 'border-slate-800 bg-slate-950 text-slate-300'"
                                    >
                                        <p class="text-sm font-bold">Enkripsi</p>
                                        <p class="mt-2 text-xs text-slate-400">
                                            {{ form.encryption_enabled ? 'Aktif untuk backup & data transit' : 'Belum diwajibkan pada outlet ini' }}
                                        </p>
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.two_factor_required = !form.two_factor_required"
                                        class="rounded-2xl border p-4 text-left transition"
                                        :class="form.two_factor_required ? 'border-sky-500/25 bg-sky-500/10 text-sky-200' : 'border-slate-800 bg-slate-950 text-slate-300'"
                                    >
                                        <p class="text-sm font-bold">2FA Admin</p>
                                        <p class="mt-2 text-xs text-slate-400">
                                            {{ form.two_factor_required ? 'Akun admin outlet wajib 2FA' : 'Masih opsional untuk admin outlet' }}
                                        </p>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col gap-3 border-t border-slate-800/80 pt-5 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                @click="downloadBackup"
                                :disabled="!form.outlet_id"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-bold text-slate-200 transition hover:border-slate-600"
                            >
                                <Download class="h-4 w-4" />
                                Download Backup Manual
                            </button>
                            <button
                                type="button"
                                @click="submitSave"
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-5 py-3 text-sm font-bold text-white transition disabled:pointer-events-none disabled:opacity-50"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
                            </button>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-800/80 bg-slate-900/80">
                        <div class="flex items-center justify-between gap-4 border-b border-slate-800/80 px-6 py-5">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                    Log Aktivitas
                                </p>
                                <h3 class="mt-2 text-lg font-black text-white">
                                    Audit Keamanan Outlet
                                </h3>
                            </div>
                            <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                {{ activityLogs.length }} log terbaru
                            </span>
                        </div>

                        <div v-if="activityLogs.length" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                                <thead class="bg-slate-950/70 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                    <tr>
                                        <th class="px-6 py-3">Waktu</th>
                                        <th class="px-6 py-3">Aktor</th>
                                        <th class="px-6 py-3">Aktivitas</th>
                                        <th class="px-6 py-3">IP</th>
                                        <th class="px-6 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/80">
                                    <tr v-for="log in activityLogs" :key="log.id" class="bg-slate-900/40">
                                        <td class="px-6 py-4 text-slate-300">
                                            {{ formatDateTime(log.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-200">
                                            <div class="font-bold">{{ log.actor_name }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ log.actor_role || 'system' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-300">
                                            <div class="font-semibold text-white">
                                                {{ log.description }}
                                            </div>
                                            <div class="mt-1 text-xs uppercase tracking-wider text-slate-500">
                                                {{ log.action }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-400">{{ log.ip_address }}</td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                                :class="log.status === 'success'
                                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                                    : log.status === 'warning' || log.status === 'error'
                                                      ? 'border-amber-500/20 bg-amber-500/10 text-amber-300'
                                                      : 'border-slate-700 bg-slate-950 text-slate-400'"
                                            >
                                                {{ log.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="px-6 py-12 text-center">
                            <p class="text-sm font-semibold text-white">Belum ada log keamanan untuk outlet ini.</p>
                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Log akan bertambah saat owner menyimpan konfigurasi, mengunduh backup manual, atau melakukan perubahan sensitif lain.
                            </p>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-800/80 bg-slate-900/80 p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                    Status Backup
                                </p>
                                <h3 class="mt-2 text-lg font-black text-white">
                                    Backup Terakhir
                                </h3>
                            </div>
                            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-3">
                                <Download class="h-5 w-5 text-slate-200" />
                            </div>
                        </div>

                        <div class="mt-5 rounded-3xl border border-slate-800 bg-slate-950/50 p-5">
                            <div class="flex items-center gap-3">
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                    :class="latestBackup.status === 'success'
                                        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                        : latestBackup.status === 'not_started'
                                          ? 'border-slate-700 bg-slate-900 text-slate-400'
                                          : 'border-amber-500/20 bg-amber-500/10 text-amber-300'"
                                >
                                    {{ latestBackup.status }}
                                </span>
                                <p class="text-sm text-slate-400">
                                    {{ formatDateTime(latestBackup.performed_at) }}
                                </p>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-slate-300">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Nama File</span>
                                    <span class="text-right font-semibold text-white">
                                        {{ latestBackup.file_name || 'Belum ada arsip' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Ukuran</span>
                                    <span class="font-semibold text-white">
                                        {{ formatBytes(latestBackup.size_bytes) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Storage</span>
                                    <span class="font-semibold text-white">
                                        {{ latestBackup.storage_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-800/80 bg-slate-900/80 p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                    Postur Keamanan
                                </p>
                                <h3 class="mt-2 text-lg font-black text-white">
                                    Ringkasan Proteksi
                                </h3>
                            </div>
                            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-3">
                                <Lock class="h-5 w-5 text-slate-200" />
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div class="rounded-3xl border border-slate-800 bg-slate-950/50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-white">Enkripsi Backup</p>
                                    <span class="text-xs font-bold uppercase tracking-wider" :class="securityPosture.encryption_status ? 'text-emerald-300' : 'text-slate-500'">
                                        {{ securityPosture.encryption_status ? 'Aktif' : 'Off' }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-slate-400">
                                    Menandai kewajiban enkripsi untuk data transit dan arsip backup outlet.
                                </p>
                            </div>

                            <div class="rounded-3xl border border-slate-800 bg-slate-950/50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-white">2FA Admin</p>
                                    <span class="text-xs font-bold uppercase tracking-wider" :class="securityPosture.two_factor_status ? 'text-sky-300' : 'text-slate-500'">
                                        {{ securityPosture.two_factor_status ? 'Wajib' : 'Opsional' }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-slate-400">
                                    Kewajiban autentikasi dua langkah untuk akun admin yang mengelola outlet.
                                </p>
                            </div>

                            <div class="rounded-3xl border border-slate-800 bg-slate-950/50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-white">PIN Owner/Supervisor</p>
                                    <span class="text-xs font-bold text-white">
                                        {{ securityPosture.approval_pin_coverage.secured }}/{{ securityPosture.approval_pin_coverage.total }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-slate-400">
                                    Coverage akun approver yang sudah punya approval PIN aktif.
                                </p>
                            </div>

                            <div class="rounded-3xl border border-slate-800 bg-slate-950/50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-white">Warning 14 Hari</p>
                                    <span class="text-xs font-bold" :class="securityPosture.recent_warnings > 0 ? 'text-amber-300' : 'text-emerald-300'">
                                        {{ securityPosture.recent_warnings }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-slate-400">
                                    Jumlah log warning/error keamanan yang tercatat untuk outlet aktif.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
