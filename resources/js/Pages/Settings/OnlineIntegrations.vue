<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Eye, EyeOff, Link2, Store } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

interface MappingRow {
    outlet_id: string;
    outlet_name: string;
    outlet_active: boolean;
    external_outlet_id: string;
    is_mapped: boolean;
    integration_active: boolean;
}

interface PlatformState {
    label: string;
    description: string;
    webhook_url: string;
    formDefaults: {
        is_active: boolean;
        merchant_id: string;
        environment: 'sandbox' | 'production';
        api_key: string;
        api_secret: string;
        has_stored_api_key: boolean;
        has_stored_api_secret: boolean;
        outlet_mappings: MappingRow[];
    };
    summary: {
        active_outlets: number;
        mapped_outlets: number;
        last_synced_at?: string | null;
    };
}

const props = defineProps<{
    summary: {
        total_outlets: number;
        active_integrations: number;
        mapped_outlets: number;
    };
    platforms: {
        gofood: PlatformState;
        grabfood: PlatformState;
    };
    success?: string | null;
}>();

function buildPlatformForm(state: PlatformState) {
    return useForm({
        is_active: Boolean(state.formDefaults.is_active),
        merchant_id: state.formDefaults.merchant_id || '',
        environment: state.formDefaults.environment || 'production',
        api_key: '',
        api_secret: '',
        outlet_mappings: state.formDefaults.outlet_mappings.map((mapping) => ({
            outlet_id: mapping.outlet_id,
            external_outlet_id: mapping.external_outlet_id || '',
        })),
    });
}

const gofoodForm = buildPlatformForm(props.platforms.gofood);
const grabfoodForm = buildPlatformForm(props.platforms.grabfood);

function resetPlatformForm(
    form: ReturnType<typeof useForm>,
    state: PlatformState,
) {
    form.defaults({
        is_active: Boolean(state.formDefaults.is_active),
        merchant_id: state.formDefaults.merchant_id || '',
        environment: state.formDefaults.environment || 'production',
        api_key: '',
        api_secret: '',
        outlet_mappings: state.formDefaults.outlet_mappings.map((mapping) => ({
            outlet_id: mapping.outlet_id,
            external_outlet_id: mapping.external_outlet_id || '',
        })),
    });

    form.reset();
    form.clearErrors();
}

watch(
    () => props.platforms,
    (platforms) => {
        resetPlatformForm(gofoodForm, platforms.gofood);
        resetPlatformForm(grabfoodForm, platforms.grabfood);
    },
    { deep: true },
);

const showApiKeys = ref<Record<string, boolean>>({});
const showApiSecrets = ref<Record<string, boolean>>({});

const toggleApiKey = (key: string) => {
    showApiKeys.value[key] = !showApiKeys.value[key];
};
const toggleApiSecret = (key: string) => {
    showApiSecrets.value[key] = !showApiSecrets.value[key];
};

const summaryCards = computed(() => [
    {
        label: 'Total Outlet',
        value: props.summary.total_outlets,
        helper: 'Outlet yang tersedia untuk mapping integrasi online order',
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
        icon: Store,
    },
    {
        label: 'Integrasi Aktif',
        value: props.summary.active_integrations,
        helper: 'Jumlah outlet-platform dengan status aktif',
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: CheckCircle2,
    },
    {
        label: 'Outlet Termapping',
        value: props.summary.mapped_outlets,
        helper: 'Jumlah outlet yang sudah punya external outlet ID',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Link2,
    },
]);

const platformList = computed(() => [
    {
        key: 'gofood' as const,
        config: props.platforms.gofood,
        form: gofoodForm,
    },
    {
        key: 'grabfood' as const,
        config: props.platforms.grabfood,
        form: grabfoodForm,
    },
]);

function formatDateTime(value?: string | null) {
    if (!value) return 'Belum ada webhook masuk';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

function copyWebhook(url: string) {
    if (!navigator?.clipboard) return;
    navigator.clipboard.writeText(url);
}

function submitPlatform(platform: 'gofood' | 'grabfood') {
    const form = platform === 'gofood' ? gofoodForm : grabfoodForm;

    form.put(route('settings.online-integrations.update', platform), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Integrasi GoFood & GrabFood" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Integrasi GoFood & GrabFood
                    </h2>
                    <p class="mt-1 text-sm text-stone-500 dark:text-slate-400">
                        Hubungkan akun agregator, simpan merchant credential,
                        dan map outlet POS ke outlet eksternal.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border p-5"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-3 text-3xl font-black"
                                :class="card.tone"
                            >
                                {{ card.value }}
                            </p>
                            <p
                                class="mt-2 text-xs leading-5 text-stone-500 dark:text-slate-400"
                            >
                                {{ card.helper }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-950/40"
                        >
                            <component
                                :is="card.icon"
                                class="h-5 w-5 text-stone-800 dark:text-slate-200"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6">
                <section
                    v-for="entry in platformList"
                    :key="entry.key"
                    class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-slate-800/80 dark:bg-slate-900/80"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-stone-200 pb-5 dark:border-slate-800/80 xl:flex-row xl:items-start xl:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-3">
                                <h3
                                    class="text-xl font-black text-stone-900 dark:text-white"
                                >
                                    {{ entry.config.label }}
                                </h3>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                    :class="
                                        entry.form.is_active
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                            : 'border-stone-200 bg-stone-100 text-stone-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400'
                                    "
                                >
                                    {{
                                        entry.form.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </span>
                            </div>
                            <p
                                class="mt-2 text-sm text-stone-500 dark:text-slate-400"
                            >
                                {{ entry.config.description }}
                            </p>
                            <p
                                class="mt-3 text-xs text-stone-400 dark:text-slate-500"
                            >
                                Webhook terakhir:
                                {{
                                    formatDateTime(
                                        entry.config.summary.last_synced_at,
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            class="grid gap-3 text-sm text-stone-600 dark:text-slate-300 sm:grid-cols-3"
                        >
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Outlet Aktif
                                </p>
                                <p
                                    class="mt-2 text-lg font-black text-stone-900 dark:text-white"
                                >
                                    {{ entry.config.summary.active_outlets }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Outlet Mapped
                                </p>
                                <p
                                    class="mt-2 text-lg font-black text-stone-900 dark:text-white"
                                >
                                    {{ entry.config.summary.mapped_outlets }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="copyWebhook(entry.config.webhook_url)"
                                class="rounded-2xl border border-stone-200 bg-white px-4 py-3 text-left transition hover:border-stone-200 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Webhook URL
                                </p>
                                <p
                                    class="mt-2 truncate text-xs font-semibold text-orange-300"
                                >
                                    {{ entry.config.webhook_url }}
                                </p>
                                <p
                                    class="mt-1 text-[11px] text-stone-400 dark:text-slate-500"
                                >
                                    Klik untuk copy
                                </p>
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                        <div
                            class="space-y-5 rounded-3xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950/50"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        Status Integrasi
                                    </p>
                                    <p
                                        class="mt-1 text-xs leading-5 text-stone-500 dark:text-slate-400"
                                    >
                                        Toggle aktif/nonaktif berlaku untuk
                                        seluruh mapping outlet pada platform
                                        ini.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="
                                        entry.form.is_active =
                                            !entry.form.is_active
                                    "
                                    class="rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider"
                                    :class="
                                        entry.form.is_active
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                            : 'border-stone-200 bg-white text-stone-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400'
                                    "
                                >
                                    {{
                                        entry.form.is_active
                                            ? 'Aktif'
                                            : 'Nonaktif'
                                    }}
                                </button>
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Merchant ID
                                </label>
                                <input
                                    v-model="entry.form.merchant_id"
                                    type="text"
                                    class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Environment
                                </label>
                                <select
                                    v-model="entry.form.environment"
                                    class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                                >
                                    <option value="production">
                                        Production
                                    </option>
                                    <option value="sandbox">Sandbox</option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    API Key
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="entry.form.api_key"
                                        :type="
                                            showApiKeys[entry.key]
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="Kosongkan jika tetap pakai credential tersimpan"
                                        class="w-full rounded-2xl border border-stone-200 bg-stone-100 py-3 pl-4 pr-12 text-sm text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                                    />
                                    <button
                                        type="button"
                                        @click="toggleApiKey(entry.key)"
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-400 hover:text-slate-300 dark:text-slate-500"
                                    >
                                        <component
                                            :is="
                                                showApiKeys[entry.key]
                                                    ? EyeOff
                                                    : Eye
                                            "
                                            class="h-4 w-4"
                                        />
                                    </button>
                                </div>
                                <p
                                    class="mt-2 text-[11px] text-stone-400 dark:text-slate-500"
                                >
                                    {{
                                        entry.config.formDefaults
                                            .has_stored_api_key
                                            ? 'Credential tersimpan tersedia.'
                                            : 'Belum ada API key tersimpan.'
                                    }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    API Secret
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="entry.form.api_secret"
                                        :type="
                                            showApiSecrets[entry.key]
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="Opsional, isi jika provider memerlukan secret"
                                        class="w-full rounded-2xl border border-stone-200 bg-stone-100 py-3 pl-4 pr-12 text-sm text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                                    />
                                    <button
                                        type="button"
                                        @click="toggleApiSecret(entry.key)"
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-400 hover:text-slate-300 dark:text-slate-500"
                                    >
                                        <component
                                            :is="
                                                showApiSecrets[entry.key]
                                                    ? EyeOff
                                                    : Eye
                                            "
                                            class="h-4 w-4"
                                        />
                                    </button>
                                </div>
                                <p
                                    class="mt-2 text-[11px] text-stone-400 dark:text-slate-500"
                                >
                                    {{
                                        entry.config.formDefaults
                                            .has_stored_api_secret
                                            ? 'Secret tersimpan tersedia.'
                                            : 'Belum ada secret tersimpan.'
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950/50"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-sm font-bold text-stone-900 dark:text-white"
                                    >
                                        Outlet Mapping
                                    </p>
                                    <p
                                        class="mt-1 text-xs leading-5 text-stone-500 dark:text-slate-400"
                                    >
                                        Map setiap outlet POS ke external outlet
                                        ID dari platform aggregator.
                                    </p>
                                </div>
                                <span
                                    class="rounded-full border border-stone-200 bg-stone-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400"
                                >
                                    {{ entry.form.outlet_mappings.length }}
                                    outlet
                                </span>
                            </div>

                            <div class="mt-5 space-y-3">
                                <div
                                    v-for="(mapping, index) in entry.form
                                        .outlet_mappings"
                                    :key="mapping.outlet_id"
                                    class="grid gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-slate-800 dark:bg-slate-900/40 lg:grid-cols-[0.9fr_1.1fr_auto]"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-bold text-stone-900 dark:text-white"
                                        >
                                            {{
                                                entry.config.formDefaults
                                                    .outlet_mappings[index]
                                                    ?.outlet_name
                                            }}
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] text-stone-400 dark:text-slate-500"
                                        >
                                            Status outlet POS:
                                            {{
                                                entry.config.formDefaults
                                                    .outlet_mappings[index]
                                                    ?.outlet_active
                                                    ? 'aktif'
                                                    : 'nonaktif'
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                        >
                                            External Outlet ID
                                        </label>
                                        <input
                                            v-model="mapping.external_outlet_id"
                                            type="text"
                                            placeholder="Contoh: GF-OUT-001"
                                            class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                                        />
                                    </div>
                                    <div class="flex items-end">
                                        <span
                                            class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                            :class="
                                                mapping.external_outlet_id
                                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                                    : 'border-stone-200 bg-stone-100 text-stone-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-500'
                                            "
                                        >
                                            {{
                                                mapping.external_outlet_id
                                                    ? 'Mapped'
                                                    : 'Pending'
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-6 flex justify-end border-t border-stone-200 pt-5 dark:border-slate-800/80"
                            >
                                <button
                                    type="button"
                                    @click="submitPlatform(entry.key)"
                                    :disabled="entry.form.processing"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-orange-500 hover:bg-orange-400 px-5 py-3 text-sm font-bold text-stone-950 transition disabled:pointer-events-none disabled:opacity-50"
                                >
                                    {{
                                        entry.form.processing
                                            ? `Menyimpan ${entry.config.label}...`
                                            : `Simpan ${entry.config.label}`
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
