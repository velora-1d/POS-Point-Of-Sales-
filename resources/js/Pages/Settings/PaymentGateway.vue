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
    CreditCard,
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
    { value: 'ewallet', label: 'E-Wallet', description: 'OVO, GoPay, Dana, dll.' },
    { value: 'debit', label: 'Debit / Kartu', description: 'GPN, Visa, Mastercard' },
    { value: 'transfer', label: 'Transfer Bank', description: 'VA & Transfer Manual' },
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
    router.post(route('settings.payment-gateway.test'), {
        outlet_id: userOutletId,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isTesting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Monitoring Payment Gateway" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Monitoring Payment Gateway
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-slate-400">
                        Pusat pemantauan koneksi gateway pembayaran digital yang terhubung secara global melalui konfigurasi sistem.
                    </p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 p-4 pb-20 sm:px-6 lg:px-8">
            <!-- Main Status Card -->
            <div 
                class="overflow-hidden rounded-[32px] border border-white/10 bg-slate-950 shadow-2xl transition-all duration-500"
                :class="effectiveConfig.is_active ? 'ring-1 ring-emerald-500/20' : 'ring-1 ring-amber-500/20'"
            >
                <div class="relative flex flex-col md:flex-row">
                    <!-- Left: Status Illustration -->
                    <div 
                        class="flex w-full flex-col items-center justify-center p-8 md:w-1/3"
                        :class="effectiveConfig.is_active ? 'bg-emerald-500/5' : 'bg-amber-500/5'"
                    >
                        <div class="relative">
                            <div 
                                class="flex h-24 w-24 items-center justify-center rounded-full border-4 border-white/5 bg-slate-900 shadow-inner"
                                :class="effectiveConfig.is_active ? 'text-emerald-400 shadow-emerald-500/10' : 'text-amber-400 shadow-amber-500/10'"
                            >
                                <Zap v-if="effectiveConfig.is_active" class="h-12 w-12 animate-pulse" />
                                <Wifi v-else class="h-12 w-12 opacity-50" />
                            </div>
                            <div 
                                class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-slate-950 ring-4 ring-slate-900"
                                :class="effectiveConfig.is_active ? 'text-emerald-500' : 'text-amber-500'"
                            >
                                <CheckCircle2 v-if="effectiveConfig.is_active" class="h-5 w-5" />
                                <Info v-else class="h-5 w-5" />
                            </div>
                        </div>
                        <h3 class="mt-6 text-xl font-black tracking-tight text-white uppercase">
                            {{ effectiveConfig.is_active ? 'Gateway Siap' : 'Belum Dikonfigurasi' }}
                        </h3>
                        <p class="mt-2 text-center text-xs leading-relaxed text-slate-400">
                            {{ effectiveConfig.is_active 
                                ? 'Sistem telah terhubung dengan kredensial .env yang valid dan siap melayani transaksi.' 
                                : 'Lengkapi variabel PAKASIR_* di file .env server Bapak untuk mengaktifkan pembayaran digital.' 
                            }}
                        </p>
                    </div>

                    <!-- Right: Info Grid -->
                    <div class="flex flex-1 flex-col border-white/10 md:border-l">
                        <div class="grid flex-1 grid-cols-1 gap-px bg-white/5 sm:grid-cols-2">
                            <div class="bg-slate-950 p-6">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Provider Utama</p>
                                <div class="mt-3 flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-slate-950 font-bold">P</div>
                                    <p class="text-base font-bold text-white uppercase">{{ effectiveConfig.provider }}</p>
                                </div>
                            </div>
                            <div class="bg-slate-950 p-6">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Mode Sinkronisasi</p>
                                <div class="mt-3 flex items-center gap-3 text-emerald-400">
                                    <ShieldCheck class="h-5 w-5" />
                                    <p class="text-base font-bold uppercase">Absolut (.env)</p>
                                </div>
                            </div>
                            <div class="bg-slate-950 p-6 sm:col-span-2">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Base Endpoint URL</p>
                                <div class="mt-3 flex items-center gap-3 text-slate-300">
                                    <Globe class="h-5 w-5 shrink-0 opacity-50" />
                                    <code class="text-sm font-mono break-all">{{ effectiveConfig.base_url || '-' }}</code>
                                </div>
                            </div>
                            <div class="bg-slate-950 p-6">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Project Slug</p>
                                <p class="mt-3 text-base font-bold text-white">{{ effectiveConfig.project_slug || '-' }}</p>
                            </div>
                            <div class="bg-slate-950 p-6">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">API Credentials</p>
                                <div class="mt-3 flex items-center gap-2">
                                    <div :class="['h-2 w-2 rounded-full', effectiveConfig.has_api_key ? 'bg-emerald-500' : 'bg-slate-600']"></div>
                                    <p class="text-xs font-semibold" :class="effectiveConfig.has_api_key ? 'text-white' : 'text-slate-500'">
                                        {{ effectiveConfig.has_api_key ? 'Key & Secret Terbaca' : 'Kredensial Kosong' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Webhook area -->
                        <div class="border-t border-white/5 bg-slate-950 p-6">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Webhook Callback URL</p>
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3 text-slate-300">
                                    <LinkIcon class="h-5 w-5 shrink-0 opacity-50" />
                                    <code class="text-xs font-mono break-all">{{ effectiveConfig.callback_url || '-' }}</code>
                                </div>
                                <span class="inline-flex items-center rounded-lg bg-white/[0.03] px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500 border border-white/5">
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
                <form @submit.prevent="submitSave" class="flex flex-col justify-between rounded-[28px] border border-white/10 bg-slate-950/45 p-6 shadow-xl">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-400">Active Methods</p>
                        <h3 class="mt-1 text-xl font-black text-white">Metode Bayar Digital Aktif</h3>
                        <p class="mt-2 text-xs text-slate-400">
                            Centang metode pembayaran digital yang ingin diaktifkan di halaman kasir/order.
                        </p>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label 
                                v-for="method in availableMethods" 
                                :key="method.value"
                                class="flex items-start gap-4 rounded-2xl border p-4 cursor-pointer transition-all duration-200"
                                :class="form.active_payment_methods.includes(method.value)
                                    ? 'border-orange-500/35 bg-orange-500/5 hover:bg-orange-500/10'
                                    : 'border-white/5 bg-white/[0.01] hover:bg-white/[0.03]'"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.active_payment_methods.includes(method.value)"
                                    @change="toggleMethod(method.value)"
                                    class="mt-1 h-4 w-4 rounded border-slate-700 bg-slate-950 text-orange-500 focus:ring-orange-500"
                                />
                                <div>
                                    <span class="text-xs font-black uppercase tracking-wider text-white block">{{ method.label }}</span>
                                    <span class="text-[11px] text-slate-400 block mt-0.5">{{ method.description }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end border-t border-white/5 pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-2xl bg-orange-500 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Metode Pembayaran' }}
                        </button>
                    </div>
                </form>

                <!-- Big Test Button -->
                <div class="flex flex-col justify-between rounded-[28px] border border-white/10 bg-gradient-to-br from-slate-900 to-slate-950 p-6 shadow-xl ring-1 ring-white/5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-400">Verification</p>
                        <h3 class="mt-1 text-xl font-black text-white">Uji Koneksi</h3>
                        <p class="mt-4 text-xs leading-relaxed text-slate-400">
                            Klik tombol di bawah untuk melakukan ping real-time ke server Pakasir menggunakan kredensial yang ada di <code class="rounded bg-slate-800 px-1 text-emerald-300">.env</code>.
                        </p>
                    </div>

                    <button
                        @click="submitTest"
                        :disabled="isTesting || !effectiveConfig.is_active"
                        class="group relative mt-8 flex w-full items-center justify-center gap-3 overflow-hidden rounded-2xl bg-orange-500 py-4 text-sm font-black uppercase tracking-widest text-slate-950 transition-all hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <Activity 
                            class="h-5 w-5 transition-transform group-hover:scale-110" 
                            :class="{ 'animate-spin': isTesting }"
                        />
                        {{ isTesting ? 'Menghubungi Server...' : 'Uji Koneksi Sekarang' }}
                    </button>
                </div>
            </div>

            <!-- Note Footer -->
            <div class="flex items-start gap-4 rounded-3xl border border-blue-500/10 bg-blue-500/5 p-6 text-blue-300/80 shadow-inner">
                <Info class="h-6 w-6 shrink-0" />
                <div>
                    <h5 class="text-sm font-bold uppercase tracking-tight">Catatan Keamanan</h5>
                    <p class="mt-1 text-xs leading-relaxed">
                        Data API Key dan API Secret tidak pernah ditampilkan di antarmuka ini demi alasan keamanan. Sistem secara otomatis mengambil kredensial tersebut dari _Environment Variable_ server saat memproses transaksi nyata.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
