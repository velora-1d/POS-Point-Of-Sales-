<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Network, Printer, Radio, Send, Store } from '@lucide/vue';
import { computed, watch } from 'vue';

interface OutletOption {
    id: string;
    name: string;
    is_active: boolean;
    has_config: boolean;
    receipt_method: 'print' | 'whatsapp' | 'skip';
}

interface PrinterOption {
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
        settings: {
            default_receipt_method: 'print' | 'whatsapp' | 'skip';
        };
    } | null;
    summary: {
        total_outlets: number;
        configured_outlets: number;
        network_printers: number;
        whatsapp_defaults: number;
    };
    formDefaults: {
        outlet_id: string | null;
        printer_type: 'thermal' | 'dot_matrix';
        connection_type: 'usb' | 'network';
        device_name: string;
        ip_address: string;
        port: number | null;
        default_receipt_method: 'print' | 'whatsapp' | 'skip';
        has_config: boolean;
        metadata?: any;
    };
    printerOptions: {
        types: PrinterOption[];
        connections: PrinterOption[];
        receiptMethods: PrinterOption[];
    };
    preview: {
        outlet_name: string;
        printer_type: 'thermal' | 'dot_matrix';
        connection_type: 'usb' | 'network';
        device_label: string;
        receipt_method: 'print' | 'whatsapp' | 'skip';
        has_config: boolean;
        metadata?: any;
    };
    filters: {
        outlet_id?: string | null;
    };
    access: {
        canSelectOutlet: boolean;
        role?: string | null;
    };
    success?: string | null;
}>();

const printerForm = useForm({
    outlet_id: props.formDefaults.outlet_id || props.selectedOutlet?.id || '',
    printer_type: props.formDefaults.printer_type || 'thermal',
    connection_type: props.formDefaults.connection_type || 'usb',
    device_name: props.formDefaults.device_name || '',
    ip_address: props.formDefaults.ip_address || '',
    port: props.formDefaults.port ? String(props.formDefaults.port) : '',
    default_receipt_method: props.formDefaults.default_receipt_method || 'print',
    metadata: {
        receipt_template: props.formDefaults.metadata?.receipt_template || 'classic',
        receipt_font: props.formDefaults.metadata?.receipt_font || 'sans',
        receipt_color: props.formDefaults.metadata?.receipt_color || 'mono',
        receipt_footer: props.formDefaults.metadata?.receipt_footer || 'Terima kasih atas kunjungan Anda!',
        receipt_logo: props.formDefaults.metadata?.receipt_logo || null,
    }
});

watch(
    () => props.formDefaults,
    (defaults) => {
        printerForm.defaults({
            outlet_id: defaults.outlet_id || props.selectedOutlet?.id || '',
            printer_type: defaults.printer_type || 'thermal',
            connection_type: defaults.connection_type || 'usb',
            device_name: defaults.device_name || '',
            ip_address: defaults.ip_address || '',
            port: defaults.port ? String(defaults.port) : '',
            default_receipt_method: defaults.default_receipt_method || 'print',
            metadata: {
                receipt_template: defaults.metadata?.receipt_template || 'classic',
                receipt_font: defaults.metadata?.receipt_font || 'sans',
                receipt_color: defaults.metadata?.receipt_color || 'mono',
                receipt_footer: defaults.metadata?.receipt_footer || 'Terima kasih atas kunjungan Anda!',
                receipt_logo: defaults.metadata?.receipt_logo || null,
            }
        });

        printerForm.reset();
        printerForm.clearErrors();
    },
    { deep: true },
);

const summaryCards = computed(() => [
    {
        label: 'Outlet Dalam Scope',
        value: props.summary.total_outlets,
        helper: props.access.canSelectOutlet ? 'Owner bisa pilih outlet aktif' : 'Supervisor hanya outlet sendiri',
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
        icon: Store,
    },
    {
        label: 'Sudah Terkonfigurasi',
        value: props.summary.configured_outlets,
        helper: 'Outlet yang sudah punya data printer',
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: CheckCircle2,
    },
    {
        label: 'Printer Network',
        value: props.summary.network_printers,
        helper: 'Butuh IP dan port di jaringan outlet',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Network,
    },
    {
        label: 'Default WhatsApp',
        value: props.summary.whatsapp_defaults,
        helper: 'Outlet yang default struknya kirim WA',
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: Send,
    },
]);

const selectedOutletOption = computed(() => {
    return props.outlets.find((outlet) => outlet.id === printerForm.outlet_id) || null;
});

const isUsbConnection = computed(() => printerForm.connection_type === 'usb');

const previewReceiptMethodLabel = computed(() => {
    if (props.preview.receipt_method === 'whatsapp') return 'WhatsApp';
    if (props.preview.receipt_method === 'skip') return 'Skip';
    return 'Print';
});

function formatReceiptMethod(value: OutletOption['receipt_method']) {
    if (value === 'whatsapp') return 'WhatsApp';
    if (value === 'skip') return 'Skip';
    return 'Print';
}

function formatConnectionLabel(value: 'usb' | 'network') {
    return value === 'network' ? 'Network / LAN' : 'USB / Local Device';
}

function openSelectedOutlet(outletId: string) {
    router.get(
        route('settings.printer.index'),
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
    printerForm.put(route('settings.printer.update'), {
        preserveScroll: true,
        preserveState: true,
    });
}

function openTestPrint() {
    const outletId = printerForm.outlet_id || props.selectedOutlet?.id;

    if (!outletId) return;

    window.open(route('settings.printer.preview', { outlet_id: outletId }), '_blank', 'noopener');
}

const liveFontFamily = computed(() => {
    const font = printerForm.metadata.receipt_font;
    if (font === 'serif') return 'font-serif';
    if (font === 'mono') return 'font-mono';
    return 'font-sans';
});

const liveAccentColorText = computed(() => {
    const color = printerForm.metadata.receipt_color;
    if (color === 'orange') return 'text-orange-500';
    if (color === 'rose') return 'text-rose-500';
    if (color === 'emerald') return 'text-emerald-500';
    return 'text-slate-900';
});

const liveAccentBg = computed(() => {
    const color = printerForm.metadata.receipt_color;
    if (color === 'orange') return 'bg-orange-500';
    if (color === 'rose') return 'bg-rose-500';
    if (color === 'emerald') return 'bg-emerald-500';
    return 'bg-white dark:bg-slate-900';
});

const liveTemplateClass = computed(() => {
    const template = printerForm.metadata.receipt_template;
    if (template === 'modern') return 'rounded-[20px] border-slate-200 p-6 shadow-md bg-white text-slate-900';
    if (template === 'compact') return 'rounded-none border-dashed border-slate-300 p-3 max-w-[240px] text-[11px] bg-white text-slate-900 mx-auto';
    return 'rounded-none border-dashed border-slate-300 p-4 bg-white text-slate-900'; // classic
});

function handleLogoUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        
        if (file.size > 1024 * 1024) {
            alert('Ukuran file logo terlalu besar. Maksimal 1MB.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            if (e.target?.result) {
                printerForm.metadata.receipt_logo = e.target.result as string;
            }
        };
        reader.readAsDataURL(file);
    }
}

function clearLogo() {
    printerForm.metadata.receipt_logo = null;
    const input = document.getElementById('receipt_logo_input') as HTMLInputElement;
    if (input) {
        input.value = '';
    }
}
</script>

<template>
    <Head title="Konfigurasi Printer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-stone-900 dark:text-white">
Konfigurasi Printer & Struk
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400">
                        Simpan pengaturan printer per outlet, tentukan jalur koneksi, dan sinkronkan default metode
                        struk dengan setting outlet yang dipakai flow checkout.
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
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-stone-500 dark:text-slate-400">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-2xl font-black" :class="card.tone">{{ card.value }}</p>
                            <p class="mt-2 text-xs text-stone-500 dark:text-slate-400">{{ card.helper }}</p>
                        </div>
                        <div class="rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/40 p-3">
                            <component :is="card.icon" class="h-5 w-5 text-stone-900 dark:text-white" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                <article class="rounded-[28px] border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/20">
                    <div class="flex flex-col gap-4 border-b border-stone-200 dark:border-slate-800 pb-5">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Scope Outlet
                                </p>
                                <h3 class="mt-1 text-lg font-black text-stone-900 dark:text-white">Mapping device printer per outlet</h3>
                            </div>
                            <div
                                class="inline-flex items-center gap-2 self-start rounded-full border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/70 px-3 py-1 text-[11px] font-semibold text-stone-600 dark:text-slate-300"
                            >
                                <Radio class="h-3.5 w-3.5 text-emerald-300" />
                                {{ access.canSelectOutlet ? 'Owner mode' : 'Supervisor mode' }}
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-[1.05fr_0.95fr]">
                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                    Outlet aktif
                                </label>
                                <select
                                    :value="printerForm.outlet_id"
                                    :disabled="!access.canSelectOutlet"
                                    @change="openSelectedOutlet(($event.target as HTMLSelectElement).value)"
                                    class="w-full rounded-2xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/80 px-4 py-3 text-sm text-stone-900 dark:text-slate-100 outline-none transition focus:border-orange-500"
                                >
                                    <option
                                        v-for="outlet in outlets"
                                        :key="outlet.id"
                                        :value="outlet.id"
                                    >
                                        {{ outlet.name }}
                                    </option>
                                </select>
                                <p class="mt-2 text-xs text-stone-400 dark:text-slate-500">
                                    {{ access.canSelectOutlet ? 'Owner bisa berpindah outlet dari dropdown ini.' : 'Supervisor terkunci pada outlet yang ditugaskan.' }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                    Ringkasan outlet terpilih
                                </p>
                                <div class="mt-3 space-y-2 text-sm text-stone-800 dark:text-slate-200">
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
                                        <span>Config printer</span>
                                        <span class="text-stone-500 dark:text-slate-400">
                                            {{ props.formDefaults.has_config ? 'Sudah tersimpan' : 'Belum ada override' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>Default struk</span>
                                        <span class="text-stone-500 dark:text-slate-400">
                                            {{ formatReceiptMethod(selectedOutletOption?.receipt_method || 'print') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form class="mt-5 space-y-5" @submit.prevent="submitSave">
                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                    Tipe printer
                                </label>
                                <div class="space-y-3">
                                    <label
                                        v-for="option in printerOptions.types"
                                        :key="option.value"
                                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 p-4"
                                        :class="printerForm.printer_type === option.value ? 'border-orange-500/40 bg-orange-500/5' : ''"
                                    >
                                        <input
                                            v-model="printerForm.printer_type"
                                            type="radio"
                                            name="printer_type"
                                            :value="option.value"
                                            class="mt-1 h-4 w-4 border-stone-300 dark:border-slate-600 bg-stone-100 dark:bg-slate-950 text-orange-500 focus:ring-orange-500"
                                        />
                                        <div>
                                            <p class="text-sm font-semibold text-stone-900 dark:text-white">{{ option.label }}</p>
                                            <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">{{ option.description }}</p>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="printerForm.errors.printer_type" class="mt-2 text-xs text-rose-300">
                                    {{ printerForm.errors.printer_type }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                    Jalur koneksi
                                </label>
                                <div class="space-y-3">
                                    <label
                                        v-for="option in printerOptions.connections"
                                        :key="option.value"
                                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 p-4"
                                        :class="printerForm.connection_type === option.value ? 'border-sky-500/40 bg-sky-500/5' : ''"
                                    >
                                        <input
                                            v-model="printerForm.connection_type"
                                            type="radio"
                                            name="connection_type"
                                            :value="option.value"
                                            class="mt-1 h-4 w-4 border-stone-300 dark:border-slate-600 bg-stone-100 dark:bg-slate-950 text-sky-500 focus:ring-sky-500"
                                        />
                                        <div>
                                            <p class="text-sm font-semibold text-stone-900 dark:text-white">{{ option.label }}</p>
                                            <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">{{ option.description }}</p>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="printerForm.errors.connection_type" class="mt-2 text-xs text-rose-300">
                                    {{ printerForm.errors.connection_type }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div v-if="isUsbConnection">
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                    Nama device / queue printer
                                </label>
                                <input
                                    v-model="printerForm.device_name"
                                    type="text"
                                    placeholder="Contoh: EPSON-TM-T82X atau POS-USB-01"
                                    class="w-full rounded-2xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/80 px-4 py-3 text-sm text-stone-900 dark:text-slate-100 outline-none transition focus:border-orange-500"
                                />
                                <p class="mt-2 text-xs text-stone-400 dark:text-slate-500">
                                    Dipakai sebagai referensi device printer lokal pada aplikasi desktop.
                                </p>
                                <p v-if="printerForm.errors.device_name" class="mt-2 text-xs text-rose-300">
                                    {{ printerForm.errors.device_name }}
                                </p>
                            </div>

                            <template v-else>
                                <div>
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                        IP address printer
                                    </label>
                                    <input
                                        v-model="printerForm.ip_address"
                                        type="text"
                                        placeholder="Contoh: 192.168.10.25"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/80 px-4 py-3 text-sm text-stone-900 dark:text-slate-100 outline-none transition focus:border-orange-500"
                                    />
                                    <p v-if="printerForm.errors.ip_address" class="mt-2 text-xs text-rose-300">
                                        {{ printerForm.errors.ip_address }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                        Port printer
                                    </label>
                                    <input
                                        v-model="printerForm.port"
                                        type="number"
                                        min="1"
                                        max="65535"
                                        placeholder="9100"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/80 px-4 py-3 text-sm text-stone-900 dark:text-slate-100 outline-none transition focus:border-orange-500"
                                    />
                                    <p class="mt-2 text-xs text-stone-400 dark:text-slate-500">
                                        Port RAW printing umum biasanya `9100`.
                                    </p>
                                    <p v-if="printerForm.errors.port" class="mt-2 text-xs text-rose-300">
                                        {{ printerForm.errors.port }}
                                    </p>
                                </div>
                            </template>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                Default metode struk
                            </label>
                            <div class="grid gap-3 md:grid-cols-3">
                                <label
                                    v-for="option in printerOptions.receiptMethods"
                                    :key="option.value"
                                    class="flex cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 p-4"
                                    :class="printerForm.default_receipt_method === option.value ? 'border-emerald-500/40 bg-emerald-500/5' : ''"
                                >
                                    <input
                                        v-model="printerForm.default_receipt_method"
                                        type="radio"
                                        name="default_receipt_method"
                                        :value="option.value"
                                        class="mt-1 h-4 w-4 border-stone-300 dark:border-slate-600 bg-stone-100 dark:bg-slate-950 text-emerald-500 focus:ring-emerald-500"
                                    />
                                    <div>
                                        <p class="text-sm font-semibold text-stone-900 dark:text-white">{{ option.label }}</p>
                                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">{{ option.description }}</p>
                                    </div>
                                </label>
                            </div>
                            <p class="mt-2 text-xs text-stone-400 dark:text-slate-500">
                                Nilai ini ikut menyinkronkan `outlets.settings.default_receipt_method`.
                            </p>
                            <p v-if="printerForm.errors.default_receipt_method" class="mt-2 text-xs text-rose-300">
                                {{ printerForm.errors.default_receipt_method }}
                            </p>
                        </div>

                        <!-- Visual Receipt Customizer -->
                        <div class="border-t border-stone-200 dark:border-slate-800 pt-5 space-y-5">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Kustomisasi Visual Struk
                                </p>
                                <h3 class="mt-1 text-sm font-bold text-stone-900 dark:text-white">Desain & Format Struk</h3>
                                <p class="text-xs text-stone-500 dark:text-slate-400 mt-1">Atur logo, template, font, warna aksen, dan teks footer struk belanja secara kustom.</p>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <!-- Logo Upload -->
                                <div>
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                        Logo Struk (Upload)
                                    </label>
                                    <div class="flex items-center gap-4 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 p-4">
                                        <div v-if="printerForm.metadata.receipt_logo" class="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border border-stone-200 dark:border-slate-700 bg-white p-1">
                                            <img :src="printerForm.metadata.receipt_logo" class="h-full w-full object-contain filter grayscale" />
                                            <button 
                                                type="button" 
                                                @click="clearLogo" 
                                                class="absolute -right-1 -top-1 rounded-full bg-rose-500 p-0.5 text-white hover:bg-rose-400 shadow-md flex items-center justify-center"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div v-else class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-lg border border-dashed border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/50">
                                            <Printer class="h-6 w-6 text-slate-600" />
                                        </div>
                                        <div class="flex-1">
                                            <input 
                                                type="file" 
                                                id="receipt_logo_input" 
                                                accept="image/*" 
                                                class="hidden" 
                                                @change="handleLogoUpload"
                                            />
                                            <label 
                                                for="receipt_logo_input" 
                                                class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-semibold text-stone-800 dark:text-slate-200 transition hover:border-slate-500 hover:text-stone-900 dark:text-white"
                                            >
                                                Pilih Gambar
                                            </label>
                                            <p class="mt-1 text-[10px] text-stone-400 dark:text-slate-500">Maks. 1MB. Format PNG/JPG. Otomatis dikonversi ke base64.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Struk -->
                                <div>
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                        Template Layout
                                    </label>
                                    <select
                                        v-model="printerForm.metadata.receipt_template"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/80 px-4 py-3 text-sm text-stone-900 dark:text-slate-100 outline-none transition focus:border-orange-500"
                                    >
                                        <option value="classic">Classic (Sharp / 80mm-92mm)</option>
                                        <option value="modern">Modern (Rounded / Elegant)</option>
                                        <option value="compact">Compact (Minimalist / 58mm Thermal)</option>
                                    </select>
                                    <p class="mt-1.5 text-xs text-stone-400 dark:text-slate-500">
                                        Template menentukan lebar struk, padding, dan bentuk border struk.
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <!-- Font Dropdown -->
                                <div>
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                        Gaya Huruf (Font)
                                    </label>
                                    <select
                                        v-model="printerForm.metadata.receipt_font"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/80 px-4 py-3 text-sm text-stone-900 dark:text-slate-100 outline-none transition focus:border-orange-500"
                                    >
                                        <option value="sans">Sans-serif (Modern & Bersih)</option>
                                        <option value="serif">Serif (Klasik & Elegan)</option>
                                        <option value="mono">Monospace (Tipe Mesin Kasir / Kasir Jadul)</option>
                                    </select>
                                </div>

                                <!-- Color Accent Selector -->
                                <div>
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                        Warna Aksen Teks (Preview Digital)
                                    </label>
                                    <div class="flex items-center gap-3 mt-1.5">
                                        <button
                                            v-for="colorOpt in [
                                                { value: 'mono', bg: 'bg-stone-100 dark:bg-slate-950 border-stone-200 dark:border-slate-700', label: 'Hitam' },
                                                { value: 'orange', bg: 'bg-orange-500 border-orange-400', label: 'Oranye' },
                                                { value: 'rose', bg: 'bg-rose-500 border-rose-400', label: 'Merah Muda' },
                                                { value: 'emerald', bg: 'bg-emerald-500 border-emerald-400', label: 'Hijau' }
                                            ]"
                                            :key="colorOpt.value"
                                            type="button"
                                            @click="printerForm.metadata.receipt_color = colorOpt.value"
                                            class="h-9 w-9 rounded-full border-2 transition focus:outline-none"
                                            :class="[
                                                colorOpt.bg,
                                                printerForm.metadata.receipt_color === colorOpt.value ? 'ring-2 ring-white scale-110' : 'opacity-70 hover:opacity-100'
                                            ]"
                                            :title="colorOpt.label"
                                        />
                                    </div>
                                    <p class="mt-2 text-xs text-stone-400 dark:text-slate-500">Aksen warna untuk elemen penonjol teks struk.</p>
                                </div>
                            </div>

                            <!-- Custom Footer Text -->
                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                    Teks Kaki Struk (Footer)
                                </label>
                                <textarea
                                    v-model="printerForm.metadata.receipt_footer"
                                    rows="2"
                                    placeholder="Contoh: Terima kasih atas kunjungan Anda! Barang yang sudah dibeli tidak dapat ditukar."
                                    class="w-full rounded-2xl border border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/80 px-4 py-3 text-sm text-stone-900 dark:text-slate-100 outline-none transition focus:border-orange-500"
                                ></textarea>
                                <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">Pesan penutup di bagian paling bawah struk fisik atau digital.</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-stone-200 dark:border-slate-800 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs text-stone-400 dark:text-slate-500">
                                Preview test print memakai dialog print browser untuk simulasi layout struk.
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-950 px-4 py-3 text-sm font-semibold text-stone-800 dark:text-slate-200 transition hover:border-slate-500 hover:text-stone-900 dark:text-white"
                                    @click="openTestPrint"
                                >
                                    Test print preview
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-2xl bg-orange-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="printerForm.processing"
                                >
                                    {{ printerForm.processing ? 'Menyimpan...' : 'Simpan konfigurasi' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </article>

                <aside class="space-y-5">
                    <!-- Live Visual Receipt Preview -->
                    <article class="rounded-[28px] border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/20">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-orange-300">
                                    Live Receipt Preview
                                </p>
                                <h3 class="mt-1 text-lg font-black text-stone-900 dark:text-white">
                                    Simulasi desain struk live
                                </h3>
                                <p class="text-xs text-stone-500 dark:text-slate-400 mt-1">Perubahan pada form sebelah kiri akan langsung direfleksikan di bawah ini secara instan.</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl bg-white dark:bg-slate-950/80 p-4 border border-stone-200 dark:border-slate-800 flex items-center justify-center">
                            <!-- Paper sheet simulation -->
                            <div 
                                :class="[liveFontFamily, liveTemplateClass]" 
                                class="w-full transition-all duration-300 shadow-xl"
                            >
                                <div class="text-center">
                                    <img 
                                        v-if="printerForm.metadata.receipt_logo" 
                                        :src="printerForm.metadata.receipt_logo" 
                                        class="mx-auto max-h-12 max-w-full object-contain filter grayscale mb-2.5" 
                                    />
                                    <h4 class="font-black text-sm uppercase tracking-wider" :class="liveAccentColorText">
                                        {{ selectedOutlet?.name || 'Outlet Mentai' }}
                                    </h4>
                                    <p class="text-[9px] text-stone-400 dark:text-slate-500 mt-0.5">Jalan Raya Restoran Mentai No. 123</p>
                                    <p class="text-[9px] text-stone-400 dark:text-slate-500">Telp: 0812-3456-789</p>
                                </div>

                                <div class="my-3 border-t" :class="printerForm.metadata.receipt_template === 'compact' ? 'border-dotted border-slate-300' : 'border-dashed border-slate-400'"></div>

                                <div class="space-y-1.5 text-[11px] text-slate-700">
                                    <div class="flex justify-between">
                                        <span>No. Order</span>
                                        <span class="font-semibold text-slate-900">TRX-2026-0001</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Waktu</span>
                                        <span>01 Jun 2026 12:00</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Kasir</span>
                                        <span>Kasir Utama</span>
                                    </div>
                                </div>

                                <div class="my-3 border-t" :class="printerForm.metadata.receipt_template === 'compact' ? 'border-dotted border-slate-300' : 'border-dashed border-slate-400'"></div>

                                <div class="space-y-2 text-[11px]">
                                    <div class="flex justify-between items-start gap-4">
                                        <div class="text-slate-800">
                                            <span class="font-semibold">Mentai Rice Original</span>
                                            <p class="text-[9px] text-stone-400 dark:text-slate-500">Varian: Pedas Sedang</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-stone-400 dark:text-slate-500">1x</span>
                                            <span class="font-semibold text-slate-900 ml-2">28.000</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-start gap-4">
                                        <div class="text-slate-800">
                                            <span class="font-semibold">Chicken Katsu Mentai</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-stone-400 dark:text-slate-500">1x</span>
                                            <span class="font-semibold text-slate-900 ml-2">32.000</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-start gap-4">
                                        <div class="text-slate-800">
                                            <span class="font-semibold">Ocha Ice (Free Refill)</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-stone-400 dark:text-slate-500">2x</span>
                                            <span class="font-semibold text-slate-900 ml-2">16.000</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="my-3 border-t" :class="printerForm.metadata.receipt_template === 'compact' ? 'border-dotted border-slate-300' : 'border-dashed border-slate-400'"></div>

                                <div class="space-y-1.5 text-[11px]">
                                    <div class="flex justify-between text-slate-700">
                                        <span>Subtotal</span>
                                        <span>76.000</span>
                                    </div>
                                    <div class="flex justify-between text-slate-700">
                                        <span>Pajak (11%)</span>
                                        <span>8.360</span>
                                    </div>
                                    <div class="flex justify-between font-black text-xs pt-1.5 border-t border-slate-100">
                                        <span :class="liveAccentColorText">Total Akhir</span>
                                        <span :class="liveAccentColorText">84.360</span>
                                    </div>
                                </div>

                                <div class="my-3 border-t" :class="printerForm.metadata.receipt_template === 'compact' ? 'border-dotted border-slate-300' : 'border-dashed border-slate-400'"></div>

                                <div class="text-center text-[10px] text-stone-400 dark:text-slate-500 italic leading-relaxed">
                                    {{ printerForm.metadata.receipt_footer || 'Terima kasih atas kunjungan Anda!' }}
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-[28px] border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/20">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-300">
                                    Preview Runtime
                                </p>
                                <h3 class="mt-1 text-lg font-black text-stone-900 dark:text-white">
                                    Snapshot konfigurasi outlet
                                </h3>
                            </div>
                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/70 p-3">
                                <Printer class="h-5 w-5 text-stone-800 dark:text-slate-200" />
                            </div>
                        </div>

                        <div class="mt-5 rounded-[24px] border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/70 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-stone-500 dark:text-slate-400">Outlet</span>
                                <span class="text-sm font-semibold text-stone-900 dark:text-white">{{ preview.outlet_name }}</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-sm text-stone-500 dark:text-slate-400">Tipe</span>
                                <span class="text-sm font-semibold text-stone-900 dark:text-white">
                                    {{ preview.printer_type === 'dot_matrix' ? 'Dot Matrix' : 'Thermal' }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-sm text-stone-500 dark:text-slate-400">Koneksi</span>
                                <span class="text-sm font-semibold text-stone-900 dark:text-white">
                                    {{ formatConnectionLabel(preview.connection_type) }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-sm text-stone-500 dark:text-slate-400">Target device</span>
                                <span class="max-w-[55%] text-right text-sm font-semibold text-stone-900 dark:text-white">
                                    {{ preview.device_label }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-sm text-stone-500 dark:text-slate-400">Default struk</span>
                                <span class="text-sm font-semibold text-stone-900 dark:text-white">{{ previewReceiptMethodLabel }}</span>
                            </div>
                        </div>

                        <div class="mt-4 rounded-[24px] border border-dashed border-stone-200 dark:border-slate-700 bg-white dark:bg-slate-950/50 p-4 text-xs text-stone-500 dark:text-slate-400">
                            <p class="font-semibold text-stone-800 dark:text-slate-200">Catatan implementasi</p>
                            <ul class="mt-3 space-y-2">
                                <li>- Flow ini menyimpan preferensi printer per outlet.</li>
                                <li>- Direct print device belum dihubungkan; test print masih browser preview.</li>
                                <li>- Menu checkout tetap memakai `default_receipt_method` dari settings outlet.</li>
                            </ul>
                        </div>
                    </article>

                    <article class="rounded-[28px] border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/20">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-300">
                                    Status Outlet
                                </p>
                                <h3 class="mt-1 text-lg font-black text-stone-900 dark:text-white">
                                    Distribusi konfigurasi printer
                                </h3>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="outlet in outlets"
                                :key="outlet.id"
                                class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 p-4"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-stone-900 dark:text-white">{{ outlet.name }}</p>
                                        <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">
                                            {{ outlet.has_config ? 'Sudah ada config printer' : 'Masih pakai setting receipt outlet' }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                        :class="outlet.is_active
                                            ? 'border border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
                                            : 'border border-amber-400/20 bg-amber-500/10 text-amber-300'"
                                    >
                                        {{ outlet.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                                <div class="mt-3 text-xs text-stone-500 dark:text-slate-400">
                                    Default struk: {{ formatReceiptMethod(outlet.receipt_method) }}
                                </div>
                            </div>
                        </div>
                    </article>
                </aside>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
