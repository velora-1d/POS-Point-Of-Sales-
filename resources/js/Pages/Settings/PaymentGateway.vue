<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Activity,
    CheckCircle2,
    Globe,
    Info,
    Link as LinkIcon,
    ShieldCheck,
    Wifi,
    Zap,
} from '@lucide/vue';
import { ref } from 'vue';

interface EffectiveConfig {
    source: 'env';
    provider: string;
    is_active: boolean;
    base_url?: string;
    project_slug?: string;
    callback_url?: string;
    active_payment_methods: string[];
    has_api_key: boolean;
    has_api_secret: boolean;
}

const props = defineProps<{
    effectiveConfig: EffectiveConfig;
    success?: string | null;
}>();

const isTesting = ref(false);
const page = usePage<any>();
const userOutletId = page.props.auth.user?.outlet_id;

const form = useForm({
    outlet_id: userOutletId || '',
    provider: 'pakasir',
    is_active: true,
    active_payment_methods: [...props.effectiveConfig.active_payment_methods],
});

const availableMethods = [
    { value: 'qris', label: 'QRIS', description: 'Pembayaran instan kode QR' },
    {
        value: 'ewallet',
        label: 'E-Wallet',
        description: 'OVO, GoPay, Dana, dll.',
    },
    {
        value: 'debit',
        label: 'Debit / Kartu',
        description: 'GPN, Visa, Mastercard',
    },
    {
        value: 'transfer',
        label: 'Transfer Bank',
        description: 'VA & Transfer Manual',
    },
];

function toggleMethod(methodValue: string) {
    const idx = form.active_payment_methods.indexOf(methodValue);
    if (idx >= 0) {
        if (form.active_payment_methods.length > 1) {
            form.active_payment_methods.splice(idx, 1);
        }
    } else {
        form.active_payment_methods.push(methodValue);
    }
}

function submitSave() {
    form.put(route('settings.payment-gateway.update'), {
        preserveScroll: true,
    });
}

function submitTest() {
    isTesting.value = true;
    router.post(
        route('settings.payment-gateway.test'),
        {
            outlet_id: userOutletId,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isTesting.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Monitoring Payment Gateway" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Monitoring Payment Gateway
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Pusat pemantauan koneksi gateway pembayaran digital yang
                        terhubung secara global melalui konfigurasi sistem.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6 pb-20">
            <div
                v-if="success"
                class="flex items-center gap-2 rounded-2xl border-2 border-emerald-500 bg-emerald-50/50 p-4 text-sm font-black text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
            >
                <CheckCircle2 class="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-450" />
                <span>{{ success }}</span>
            </div>

            <div
                v-if="$page.props.errors && Object.keys($page.props.errors).length > 0"
                class="flex items-start gap-2.5 rounded-2xl border-2 border-rose-500 bg-rose-50/50 p-4 text-sm font-black text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 animate-in fade-in duration-200"
            >
                <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-rose-600 animate-pulse"></div>
                <div class="flex-1 space-y-1">
                    <p v-for="(err, key) in $page.props.errors" :key="key">
                        {{ err }}
                    </p>
                </div>
            </div>

            <!-- Main Status Card -->
            <div
                class="overflow-hidden rounded-[32px] border-2 border-stone-200 bg-white shadow-2xl transition-all duration-500 dark:border-white/10 dark:bg-slate-950"
                :class="
                    effectiveConfig.is_active
                        ? 'ring-2 ring-emerald-500/10'
                        : 'ring-2 ring-amber-500/10'
                "
            >
                <div class="relative flex flex-col md:flex-row">
                    <!-- Left: Status Illustration -->
                    <div
                        class="flex w-full flex-col items-center justify-center p-8 md:w-1/3"
                        :class="
                            effectiveConfig.is_active
                                ? 'bg-emerald-500/5'
                                : 'bg-amber-500/5'
                        "
                    >
                        <div class="relative">
                            <div
                                class="flex h-24 w-24 items-center justify-center rounded-full border-4 border-stone-200 bg-white shadow-inner dark:border-white/5 dark:bg-slate-900"
                                :class="
                                    effectiveConfig.is_active
                                        ? 'text-emerald-500 dark:text-emerald-450 shadow-emerald-500/10'
                                        : 'text-amber-500 dark:text-amber-450 shadow-amber-500/10'
                                "
                            >
                                <Zap
                                    v-if="effectiveConfig.is_active"
                                    class="h-12 w-12 animate-pulse"
                                />
                                <Wifi v-else class="h-12 w-12 opacity-50" />
                            </div>
                            <div
                                class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-stone-100 ring-4 ring-slate-900 dark:bg-slate-950"
                                :class="
                                    effectiveConfig.is_active
                                        ? 'text-emerald-500'
                                        : 'text-amber-500'
                                "
                            >
                                <CheckCircle2
                                    v-if="effectiveConfig.is_active"
                                    class="h-5 w-5"
                                />
                                <Info v-else class="h-5 w-5" />
                            </div>
                        </div>
                        <h3
                            class="mt-6 text-xl font-black uppercase tracking-tight text-stone-900 dark:text-white"
                        >
                            {{
                                effectiveConfig.is_active
                                    ? 'Gateway Siap'
                                    : 'Belum Dikonfigurasi'
                            }}
                        </h3>
                        <p
                            class="mt-2 text-center text-xs leading-relaxed text-stone-500 dark:text-slate-400"
                        >
                            {{
                                effectiveConfig.is_active
                                    ? 'Sistem telah terhubung dengan kredensial .env yang valid dan siap melayani transaksi.'
                                    : 'Lengkapi variabel PAKASIR_* di file .env server Bapak untuk mengaktifkan pembayaran digital.'
                            }}
                        </p>
                    </div>

                    <!-- Right: Info Grid -->
                    <div
                        class="flex flex-1 flex-col border-stone-200 dark:border-white/10 md:border-l-2"
                    >
                        <div
                            class="grid flex-1 grid-cols-1 gap-px bg-stone-200 dark:bg-white/5 sm:grid-cols-2"
                        >
                            <div class="bg-white p-6 dark:bg-slate-950">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Provider Utama
                                </p>
                                <div class="mt-3 flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 font-bold text-stone-950"
                                    >
                                        P
                                    </div>
                                    <p
                                        class="text-base font-bold uppercase text-stone-900 dark:text-white"
                                    >
                                        {{ effectiveConfig.provider }}
                                    </p>
                                </div>
                            </div>
                            <div class="bg-white p-6 dark:bg-slate-950">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Mode Sinkronisasi
                                </p>
                                <div
                                    class="mt-3 flex items-center gap-3 text-emerald-600 dark:text-emerald-450"
                                >
                                    <ShieldCheck class="h-5 w-5" />
                                    <p class="text-base font-bold uppercase">
                                        Absolut (.env)
                                    </p>
                                </div>
                            </div>
                            <div
                                class="bg-white p-6 dark:bg-slate-950 sm:col-span-2"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Base Endpoint URL
                                </p>
                                <div
                                    class="mt-3 flex items-center gap-3 text-stone-700 dark:text-slate-350"
                                >
                                    <Globe
                                        class="h-5 w-5 shrink-0 opacity-50"
                                    />
                                    <code class="break-all font-mono text-sm">{{
                                        effectiveConfig.base_url || '-'
                                    }}</code>
                                </div>
                            </div>
                            <div class="bg-white p-6 dark:bg-slate-950">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Project Slug
                                </p>
                                <p
                                    class="mt-3 text-base font-bold text-stone-900 dark:text-white"
                                >
                                    {{ effectiveConfig.project_slug || '-' }}
                                </p>
                            </div>
                            <div class="bg-white p-6 dark:bg-slate-950">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    API Credentials
                                </p>
                                <div class="mt-3 flex items-center gap-2">
                                    <div
                                        :class="[
                                            'h-2 w-2 rounded-full',
                                            effectiveConfig.has_api_key
                                                ? 'bg-emerald-500'
                                                : 'bg-stone-400 dark:bg-slate-600',
                                        ]"
                                    ></div>
                                    <p
                                        class="text-xs font-semibold"
                                        :class="
                                            effectiveConfig.has_api_key
                                                ? 'text-stone-900 dark:text-white'
                                                : 'text-stone-400 dark:text-slate-500'
                                        "
                                    >
                                        {{
                                            effectiveConfig.has_api_key
                                                ? 'Key & Secret Terbaca'
                                                : 'Kredensial Kosong'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Webhook area -->
                        <div
                            class="border-t-2 border-stone-200 bg-white p-6 dark:border-white/5 dark:bg-slate-950"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                            >
                                Webhook Callback URL
                            </p>
                            <div
                                class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div
                                    class="flex items-center gap-3 text-stone-700 dark:text-slate-350"
                                >
                                    <LinkIcon
                                        class="h-5 w-5 shrink-0 opacity-50"
                                    />
                                    <code class="break-all font-mono text-xs">{{
                                        effectiveConfig.callback_url || '-'
                                    }}</code>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-lg border-2 border-stone-200 bg-stone-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-stone-500 dark:border-white/5 dark:bg-white/[0.03] dark:text-slate-500"
                                >
                                    Endpoint Aktif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Area -->
            <div class="grid gap-6 lg:grid-cols-[1fr,320px]">
                <!-- Methods Settings Form -->
                <form
                    @submit.prevent="submitSave"
                    class="flex flex-col justify-between rounded-[28px] border-2 border-stone-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
                >
                    <div>
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-400"
                        >
                            Active Methods
                        </p>
                        <h3
                            class="mt-1 text-xl font-black text-stone-900 dark:text-white"
                        >
                            Metode Bayar Digital Aktif
                        </h3>
                        <p
                            class="mt-2 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Centang metode pembayaran digital yang ingin
                            diaktifkan di halaman kasir/order.
                        </p>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label
                                v-for="method in availableMethods"
                                :key="method.value"
                                class="flex cursor-pointer items-start gap-4 rounded-2xl border-2 p-4 transition-all duration-200"
                                :class="
                                    form.active_payment_methods.includes(
                                        method.value,
                                    )
                                        ? 'border-orange-500 bg-orange-500/5 hover:bg-orange-500/10'
                                        : 'border-stone-200 bg-white hover:bg-stone-50 dark:border-white/10 dark:bg-slate-900/60 dark:hover:bg-slate-900'
                                "
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        form.active_payment_methods.includes(
                                            method.value,
                                        )
                                    "
                                    @change="toggleMethod(method.value)"
                                    class="mt-1 h-4 w-4 rounded border-stone-200 bg-stone-100 text-orange-500 focus:ring-orange-500 dark:border-slate-700 dark:bg-slate-950"
                                />
                                <div>
                                    <span
                                        class="block text-xs font-black uppercase tracking-wider text-stone-900 dark:text-white"
                                        >{{ method.label }}</span
                                    >
                                    <span
                                        class="mt-0.5 block text-[11px] text-stone-500 dark:text-slate-400"
                                        >{{ method.description }}</span
                                    >
                                </div>
                            </label>
                        </div>
                    </div>

                    <div
                        class="mt-8 flex justify-end border-t-2 border-stone-200 pt-4 dark:border-white/5"
                    >
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-2xl bg-orange-500 px-6 py-3 text-sm font-semibold text-stone-950 transition hover:bg-orange-400 disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Metode Pembayaran'
                            }}
                        </button>
                    </div>
                </form>

                <!-- Big Test Button -->
                <div
                    class="flex flex-col justify-between rounded-[28px] border-2 border-stone-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
                >
                    <div>
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-600 dark:text-orange-400"
                        >
                            Verification
                        </p>
                        <h3
                            class="mt-1 text-xl font-black text-stone-900 dark:text-white"
                        >
                            Uji Koneksi
                        </h3>
                        <p
                            class="mt-4 text-xs leading-relaxed text-stone-500 dark:text-slate-400"
                        >
                            Klik tombol di bawah untuk melakukan ping real-time
                            ke server Pakasir menggunakan kredensial yang ada di
                            <code
                                class="rounded bg-stone-100 dark:bg-slate-800 px-1.5 py-0.5 text-emerald-600 dark:text-emerald-400 font-bold"
                                >.env</code>.
                        </p>
                        <p
                            v-if="$page.props.errors.test_connection"
                            class="mt-3 text-xs font-black text-rose-600 dark:text-rose-400"
                        >
                            Error: {{ $page.props.errors.test_connection }}
                        </p>
                    </div>

                    <button
                        @click="submitTest"
                        :disabled="isTesting || !effectiveConfig.is_active"
                        class="group relative mt-8 flex w-full items-center justify-center gap-3 overflow-hidden rounded-2xl bg-orange-500 py-4 text-sm font-black uppercase tracking-widest text-stone-950 transition-all hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <Activity
                            class="h-5 w-5 transition-transform group-hover:scale-110"
                            :class="{ 'animate-spin': isTesting }"
                        />
                        {{
                            isTesting
                                ? 'Menghubungi Server...'
                                : 'Uji Koneksi Sekarang'
                        }}
                    </button>
                </div>
            </div>

            <!-- Note Footer -->
            <div
                class="flex items-start gap-4 rounded-3xl border border-blue-500/20 bg-blue-500/5 p-6 text-blue-700 dark:text-blue-300 shadow-inner"
            >
                <Info class="h-6 w-6 shrink-0" />
                <div>
                    <h5 class="text-sm font-bold uppercase tracking-tight">
                        Catatan Keamanan
                    </h5>
                    <p class="mt-1 text-xs leading-relaxed">
                        Data API Key dan API Secret tidak pernah ditampilkan di
                        antarmuka ini demi alasan keamanan. Sistem secara
                        otomatis mengambil kredensial tersebut dari _Environment
                        Variable_ server saat memproses transaksi nyata.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
