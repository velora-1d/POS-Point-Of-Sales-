<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    BadgeCheck,
    CreditCard,
    Eye,
    EyeOff,
    Globe,
    Power,
    Store,
    Wifi,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

interface OutletOption {
    id: string;
    name: string;
    is_active: boolean;
    has_override: boolean;
    gateway_active: boolean;
}

interface EffectiveConfig {
    source: 'outlet' | 'env' | 'missing';
    provider: string;
    is_active: boolean;
    base_url?: string;
    project_slug?: string;
    callback_url?: string;
    active_payment_methods: string[];
    has_api_key: boolean;
    has_api_secret: boolean;
}

interface GatewayMethod {
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
        override_outlets: number;
        active_overrides: number;
        qris_ready_outlets: number;
    };
    formDefaults: {
        outlet_id: string | null;
        provider: string;
        is_active: boolean;
        base_url: string;
        project_slug: string;
        callback_url: string;
        api_key: string;
        api_secret: string;
        active_payment_methods: string[];
        has_stored_api_key: boolean;
        has_stored_api_secret: boolean;
    };
    effectiveConfig: EffectiveConfig;
    gatewayOptions: {
        providers: Array<{
            value: string;
            label: string;
            description: string;
        }>;
        methods: GatewayMethod[];
    };
    filters: {
        outlet_id?: string | null;
    };
    success?: string | null;
}>();

const gatewayForm = useForm({
    outlet_id: props.formDefaults.outlet_id || props.selectedOutlet?.id || '',
    provider: props.formDefaults.provider || 'pakasir',
    is_active: Boolean(props.formDefaults.is_active),
    base_url: props.formDefaults.base_url || '',
    project_slug: props.formDefaults.project_slug || '',
    callback_url: props.formDefaults.callback_url || '',
    api_key: '',
    api_secret: '',
    active_payment_methods: [...(props.formDefaults.active_payment_methods || ['qris'])],
});

watch(
    () => props.formDefaults,
    (defaults) => {
        gatewayForm.defaults({
            outlet_id: defaults.outlet_id || props.selectedOutlet?.id || '',
            provider: defaults.provider || 'pakasir',
            is_active: Boolean(defaults.is_active),
            base_url: defaults.base_url || '',
            project_slug: defaults.project_slug || '',
            callback_url: defaults.callback_url || '',
            api_key: '',
            api_secret: '',
            active_payment_methods: [...(defaults.active_payment_methods || ['qris'])],
        });

        gatewayForm.reset();
        gatewayForm.clearErrors();
        gatewayForm.active_payment_methods = [...(defaults.active_payment_methods || ['qris'])];
    },
    { deep: true },
);

const showApiKey = ref(false);
const showApiSecret = ref(false);

const summaryCards = computed(() => [
    {
        label: 'Total Outlet',
        value: props.summary.total_outlets,
        helper: 'Cabang terdaftar di workspace',
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: Store,
    },
    {
        label: 'Override Outlet',
        value: props.summary.override_outlets,
        helper: 'Outlet dengan config gateway sendiri',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Globe,
    },
    {
        label: 'Gateway Aktif',
        value: props.summary.active_overrides,
        helper: 'Override outlet yang sedang aktif',
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: Power,
    },
    {
        label: 'QRIS Ready',
        value: props.summary.qris_ready_outlets,
        helper: 'Outlet override yang sudah enable QRIS',
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: CreditCard,
    },
]);

const selectedOutletOption = computed(() => {
    return props.outlets.find((outlet) => outlet.id === gatewayForm.outlet_id) || null;
});
const testConnectionError = computed(() => {
    return ((gatewayForm.errors as Record<string, string>)['test_connection']) || '';
});

const effectiveSourceLabel = computed(() => {
    switch (props.effectiveConfig.source) {
        case 'outlet':
            return 'Override outlet';
        case 'env':
            return 'Fallback env';
        default:
            return 'Belum siap';
    }
});

const effectiveSourceClass = computed(() => {
    switch (props.effectiveConfig.source) {
        case 'outlet':
            return 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300';
        case 'env':
            return 'border-sky-400/20 bg-sky-500/10 text-sky-300';
        default:
            return 'border-amber-400/20 bg-amber-500/10 text-amber-300';
    }
});

const selectedMethodSet = computed(() => new Set(gatewayForm.active_payment_methods));

function sourceHint() {
    if (props.effectiveConfig.source === 'outlet') {
        return 'Runtime checkout outlet ini memakai override yang tersimpan di database.';
    }

    if (props.effectiveConfig.source === 'env') {
        return 'Runtime checkout outlet ini masih fallback ke env global project.';
    }

    return 'Outlet ini belum punya konfigurasi gateway yang siap dipakai.';
}

function toggleMethod(method: string, checked: boolean) {
    const next = new Set(gatewayForm.active_payment_methods);

    if (checked) {
        next.add(method);
    } else {
        next.delete(method);
    }

    gatewayForm.active_payment_methods = Array.from(next.values());
}

function handleMethodChange(method: string, event: Event) {
    const target = event.target as HTMLInputElement;
    toggleMethod(method, target.checked);
}

function submitSave() {
    gatewayForm.put(route('settings.payment-gateway.update'), {
        preserveScroll: true,
        preserveState: true,
    });
}

function submitTest() {
    gatewayForm.post(route('settings.payment-gateway.test'), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Konfigurasi Payment Gateway" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Konfigurasi Payment Gateway
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-slate-400">
                        Kelola override gateway per outlet, webhook callback, dan readiness metode bayar digital
                        tanpa mengubah fallback env global project.
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

            <section class="rounded-[26px] border border-sky-500/15 bg-sky-500/[0.08] p-4 text-sm text-sky-100">
                <p class="font-semibold">
                    Gateway digital yang benar-benar terhubung saat ini baru QRIS via Pakasir.
                </p>
                <p class="mt-1 text-xs text-sky-100/80">
                    Toggle `E-Wallet`, `Debit`, dan `Transfer` di halaman ini disimpan sebagai readiness outlet,
                    tetapi flow kasir aktif masih menggunakan `cash` manual dan `qris` gateway.
                </p>
            </section>

            <section class="rounded-[26px] border border-white/10 bg-slate-950/40 p-4">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr),auto]">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                            Outlet Scope
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Konfigurasi tersimpan dan runtime gateway selalu dievaluasi per outlet terpilih.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="gatewayForm.outlet_id"
                            class="w-full min-w-[260px] rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                            @change="router.get(route('settings.payment-gateway.index'), { outlet_id: gatewayForm.outlet_id || undefined }, { preserveScroll: true, replace: true })"
                        >
                            <option
                                v-for="outlet in outlets"
                                :key="outlet.id"
                                :value="outlet.id"
                            >
                                {{ outlet.name }}{{ outlet.is_active ? '' : ' (nonaktif)' }}
                            </option>
                        </select>
                        <Link
                            :href="route('settings.outlets.index')"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-slate-200 transition hover:bg-white/[0.05]"
                        >
                            Buka Outlet
                        </Link>
                    </div>
                </div>
            </section>

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
                            <p class="mt-1 text-xs text-slate-400">
                                {{ stat.helper }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <component :is="stat.icon" class="h-5 w-5 text-slate-200" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[0.95fr,1.05fr]">
                <div class="space-y-5">
                    <section class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                    Runtime Status
                                </p>
                                <h3 class="mt-1 text-xl font-black text-white">
                                    {{ selectedOutlet?.name || 'Outlet belum dipilih' }}
                                </h3>
                            </div>
                            <span
                                class="rounded-full border px-3 py-1 text-[11px] font-semibold"
                                :class="effectiveSourceClass"
                            >
                                {{ effectiveSourceLabel }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Provider
                                </p>
                                <p class="mt-2 text-lg font-black text-white">
                                    {{ effectiveConfig.provider?.toUpperCase() || '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ sourceHint() }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Status Aktif
                                </p>
                                <p class="mt-2 text-lg font-black" :class="effectiveConfig.is_active ? 'text-emerald-300' : 'text-amber-300'">
                                    {{ effectiveConfig.is_active ? 'Aktif' : 'Tidak aktif' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    API key {{ effectiveConfig.has_api_key ? 'siap' : 'belum tersedia' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-[24px] border border-white/10 bg-slate-950/35 p-4">
                            <div class="grid gap-3">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                        Base URL
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-white break-all">
                                        {{ effectiveConfig.base_url || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                        Project Slug
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-white">
                                        {{ effectiveConfig.project_slug || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                        Webhook Callback
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-white break-all">
                                        {{ effectiveConfig.callback_url || '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    v-for="method in effectiveConfig.active_payment_methods"
                                    :key="method"
                                    class="rounded-full border border-orange-400/20 bg-orange-500/10 px-2.5 py-1 text-[11px] font-medium text-orange-100"
                                >
                                    {{ method.toUpperCase() }}
                                </span>
                                <span
                                    v-if="!effectiveConfig.active_payment_methods.length"
                                    class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px] text-slate-300"
                                >
                                    Belum ada metode gateway aktif
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Outlet Snapshot
                            </p>
                            <h3 class="mt-1 text-xl font-black text-white">
                                Status cabang terpilih
                            </h3>
                        </div>

                        <div class="mt-4 rounded-[24px] border border-white/10 bg-white/[0.03] p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                    :class="selectedOutletOption?.is_active
                                        ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
                                        : 'border-amber-400/20 bg-amber-500/10 text-amber-300'"
                                >
                                    {{ selectedOutletOption?.is_active ? 'Outlet aktif' : 'Outlet nonaktif' }}
                                </span>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                    :class="selectedOutletOption?.has_override
                                        ? 'border-sky-400/20 bg-sky-500/10 text-sky-300'
                                        : 'border-white/10 bg-white/[0.04] text-slate-300'"
                                >
                                    {{ selectedOutletOption?.has_override ? 'Punya override' : 'Masih fallback env / kosong' }}
                                </span>
                            </div>
                            <p class="mt-3 text-sm text-slate-300">
                                Outlet {{ selectedOutletOption?.name || '-' }}{{ selectedOutletOption?.gateway_active ? ' sedang menyalakan gateway override.' : ' belum menyalakan gateway override.' }}
                            </p>
                        </div>
                    </section>
                </div>

                <section class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                            Form Konfigurasi
                        </p>
                        <h3 class="mt-1 text-xl font-black text-white">
                            Override payment gateway per outlet
                        </h3>
                    </div>

                    <form
                        class="mt-5 space-y-5"
                        @submit.prevent="submitSave"
                    >
                        <div class="grid gap-4 md:grid-cols-[1fr,1fr]">
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">Provider</span>
                                <select
                                    v-model="gatewayForm.provider"
                                    class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                >
                                    <option
                                        v-for="provider in gatewayOptions.providers"
                                        :key="provider.value"
                                        :value="provider.value"
                                    >
                                        {{ provider.label }}
                                    </option>
                                </select>
                                <p class="mt-2 text-xs text-slate-500">
                                    Provider yang tersedia saat ini baru Pakasir.
                                </p>
                                <p v-if="gatewayForm.errors.provider" class="mt-2 text-xs text-rose-300">
                                    {{ gatewayForm.errors.provider }}
                                </p>
                            </label>

                            <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <input
                                    v-model="gatewayForm.is_active"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-400"
                                >
                                <span>
                                    <span class="block text-sm font-semibold text-white">
                                        Aktifkan override gateway outlet
                                    </span>
                                    <span class="mt-1 block text-xs text-slate-400">
                                        Jika dimatikan, outlet akan fallback ke env global bila tersedia.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-[1.2fr,0.8fr]">
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">Base URL gateway</span>
                                <input
                                    v-model="gatewayForm.base_url"
                                    type="url"
                                    class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                    placeholder="https://app.pakasir.com"
                                >
                                <p v-if="gatewayForm.errors.base_url" class="mt-2 text-xs text-rose-300">
                                    {{ gatewayForm.errors.base_url }}
                                </p>
                            </label>

                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">Project slug</span>
                                <input
                                    v-model="gatewayForm.project_slug"
                                    type="text"
                                    class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                    placeholder="slug-project-pakasir"
                                >
                                <p v-if="gatewayForm.errors.project_slug" class="mt-2 text-xs text-rose-300">
                                    {{ gatewayForm.errors.project_slug }}
                                </p>
                            </label>
                        </div>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-300">Webhook callback URL</span>
                            <input
                                v-model="gatewayForm.callback_url"
                                type="url"
                                class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                placeholder="https://domain-app/api/v1/callback/pakasir"
                            >
                            <p class="mt-2 text-xs text-slate-500">
                                Gunakan URL publik aplikasi yang dipakai gateway untuk mengirim webhook pembayaran.
                            </p>
                            <p v-if="gatewayForm.errors.callback_url" class="mt-2 text-xs text-rose-300">
                                {{ gatewayForm.errors.callback_url }}
                            </p>
                        </label>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">API key</span>
                                <div class="relative mt-2">
                                    <input
                                        v-model="gatewayForm.api_key"
                                        :type="showApiKey ? 'text' : 'password'"
                                        class="w-full rounded-2xl border border-white/10 bg-white/[0.03] pl-4 pr-12 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                        placeholder="Isi untuk menyimpan / mengganti API key"
                                    >
                                    <button
                                        type="button"
                                        @click="showApiKey = !showApiKey"
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-500 hover:text-slate-350"
                                    >
                                        <component :is="showApiKey ? EyeOff : Eye" class="h-4 w-4" />
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ formDefaults.has_stored_api_key ? 'API key terenkripsi sudah tersimpan. Kosongkan jika tidak ingin mengganti.' : 'Belum ada API key terenkripsi tersimpan di DB.' }}
                                </p>
                                <p v-if="gatewayForm.errors.api_key" class="mt-2 text-xs text-rose-300">
                                    {{ gatewayForm.errors.api_key }}
                                </p>
                            </label>

                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">API secret</span>
                                <div class="relative mt-2">
                                    <input
                                        v-model="gatewayForm.api_secret"
                                        :type="showApiSecret ? 'text' : 'password'"
                                        class="w-full rounded-2xl border border-white/10 bg-white/[0.03] pl-4 pr-12 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                        placeholder="Opsional, disimpan terenkripsi"
                                    >
                                    <button
                                        type="button"
                                        @click="showApiSecret = !showApiSecret"
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-500 hover:text-slate-350"
                                    >
                                        <component :is="showApiSecret ? EyeOff : Eye" class="h-4 w-4" />
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ formDefaults.has_stored_api_secret ? 'API secret terenkripsi sudah tersimpan. Kosongkan jika tidak ingin mengganti.' : 'Belum ada API secret terenkripsi tersimpan di DB.' }}
                                </p>
                                <p v-if="gatewayForm.errors.api_secret" class="mt-2 text-xs text-rose-300">
                                    {{ gatewayForm.errors.api_secret }}
                                </p>
                            </label>
                        </div>

                        <div class="rounded-[24px] border border-white/10 bg-slate-950/35 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                        Metode Bayar Gateway
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Pilih metode digital yang dianggap aktif untuk outlet ini.
                                    </p>
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs text-slate-300">
                                    {{ gatewayForm.active_payment_methods.length }} aktif
                                </span>
                            </div>

                            <p v-if="gatewayForm.errors.active_payment_methods" class="mt-3 text-xs text-rose-300">
                                {{ gatewayForm.errors.active_payment_methods }}
                            </p>
                            <p v-if="testConnectionError" class="mt-3 text-xs text-rose-300">
                                {{ testConnectionError }}
                            </p>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <label
                                    v-for="method in gatewayOptions.methods"
                                    :key="method.value"
                                    class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                >
                                    <input
                                        :checked="selectedMethodSet.has(method.value)"
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-400"
                                        @change="handleMethodChange(method.value, $event)"
                                    >
                                    <span>
                                        <span class="block text-sm font-semibold text-white">
                                            {{ method.label }}
                                        </span>
                                        <span class="mt-1 block text-xs text-slate-400">
                                            {{ method.description }}
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <Wifi class="h-4 w-4" />
                                Uji koneksi hanya mengecek reachability endpoint dasar gateway dari server aplikasi.
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-slate-200 transition hover:bg-white/[0.05] disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="gatewayForm.processing"
                                    @click="submitTest"
                                >
                                    <Activity class="h-4 w-4" />
                                    Uji Koneksi
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-orange-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="gatewayForm.processing"
                                >
                                    <BadgeCheck class="h-4 w-4" />
                                    Simpan Konfigurasi
                                </button>
                            </div>
                        </div>
                    </form>
                </section>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
