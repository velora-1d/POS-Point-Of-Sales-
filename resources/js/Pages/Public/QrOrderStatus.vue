<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    ChefHat,
    Clock3,
    PackageCheck,
    QrCode,
    ReceiptText,
    RefreshCcw,
    Store,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    table: any;
    outlet: any;
    order: any;
    success?: string | null;
    paymentCheckout?: Record<string, any> | null;
}>();

const activeCheckout = ref<Record<string, any> | null>(
    props.paymentCheckout ?? null,
);

watch(
    () => props.paymentCheckout,
    (value) => {
        activeCheckout.value = value ?? null;
    },
    { immediate: true },
);

const formatPrice = (value: any) => {
    const num = parseFloat(value);
    if (Number.isNaN(num)) return 'Rp 0';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
};

const paymentMeta = computed(() => props.order?.metadata?.payment || {});

const orderStatusLabel = computed(() => {
    switch (props.order.status) {
        case 'payment_pending':
            return 'Menunggu pembayaran';
        case 'pending':
            return 'Pembayaran diterima, antre diproses';
        case 'in_progress':
            return 'Sedang dimasak';
        case 'waiting_bar_approval':
            return 'Menunggu finishing';
        case 'ready':
            return 'Siap disajikan';
        case 'delivered':
            return 'Sudah diantar';
        case 'completed':
            return 'Selesai';
        default:
            return props.order.status;
    }
});

const statusClass = computed(() => {
    switch (props.order.status) {
        case 'payment_pending':
            return 'border-fuchsia-500/20 bg-fuchsia-500/10 text-fuchsia-200';
        case 'pending':
            return 'border-orange-500/20 bg-orange-500/10 text-orange-200';
        case 'in_progress':
            return 'border-amber-500/20 bg-amber-500/10 text-amber-200';
        case 'waiting_bar_approval':
            return 'border-cyan-500/20 bg-cyan-500/10 text-cyan-200';
        case 'ready':
            return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-200';
        case 'delivered':
        case 'completed':
            return 'border-slate-200/15 bg-slate-200/10 text-slate-100';
        default:
            return 'border-white/10 bg-white/5 text-slate-200';
    }
});

const checkoutUrl = computed(() => {
    return (
        activeCheckout.value?.payment_url ||
        paymentMeta.value?.checkout_url ||
        null
    );
});

const paymentStatusLabel = computed(() => {
    if (paymentMeta.value?.status === 'paid') return 'Lunas';
    if (props.order.status === 'payment_pending') return 'Belum selesai bayar';
    return 'Menunggu konfirmasi';
});

const openCheckout = () => {
    if (!checkoutUrl.value) return;

    window.open(checkoutUrl.value, '_blank', 'noopener,noreferrer');
};

const refreshPage = () => {
    router.get(
        route('self-service.orders.status', {
            tableToken: props.table.qr_session_token,
            orderNumber: props.order.order_number,
        }),
        {},
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
};
</script>

<template>
    <Head :title="`Status ${order.order_number} - ${outlet.name}`" />

    <div
        class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(217,70,239,0.18),_transparent_30%),linear-gradient(180deg,#020617_0%,#111827_100%)] text-slate-100"
    >
        <div
            class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-5 sm:px-6 lg:px-8"
        >
            <section
                class="rounded-[28px] border border-white/10 bg-slate-950/65 p-5 shadow-[0_24px_80px_rgba(15,23,42,0.45)] backdrop-blur"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-fuchsia-500/20 bg-fuchsia-500/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.24em] text-fuchsia-200"
                        >
                            <ReceiptText class="h-3.5 w-3.5" />
                            Status Order QR Meja
                        </div>
                        <h1
                            class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl"
                        >
                            {{ order.order_number }}
                        </h1>
                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            {{ orderStatusLabel }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[360px]">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Outlet
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <Store class="h-4 w-4 text-orange-300" />
                                <p class="text-sm font-bold text-white">
                                    {{ outlet.name }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Meja
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <ChefHat class="h-4 w-4 text-emerald-300" />
                                <p class="text-sm font-bold text-white">
                                    {{ table.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div
                v-if="success"
                class="rounded-[24px] border border-emerald-500/20 bg-emerald-500/10 px-5 py-4 text-sm font-semibold text-emerald-200"
            >
                {{ success }}
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="space-y-6">
                    <section
                        class="rounded-[28px] border border-white/10 bg-slate-950/60 p-5 backdrop-blur"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.22em] text-fuchsia-200"
                                >
                                    Progres
                                </p>
                                <h2 class="mt-2 text-xl font-black text-white">
                                    Status transaksi & dapur
                                </h2>
                            </div>
                            <span
                                :class="[
                                    'rounded-full border px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em]',
                                    statusClass,
                                ]"
                            >
                                {{ orderStatusLabel }}
                            </span>
                        </div>

                        <div
                            class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
                        >
                            <div
                                class="rounded-2xl border border-white/10 bg-white/5 p-4"
                            >
                                <QrCode class="h-5 w-5 text-fuchsia-200" />
                                <p
                                    class="mt-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Pembayaran
                                </p>
                                <p class="mt-2 text-sm font-black text-white">
                                    {{ paymentStatusLabel }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/5 p-4"
                            >
                                <Clock3 class="h-5 w-5 text-orange-200" />
                                <p
                                    class="mt-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Waktu Order
                                </p>
                                <p class="mt-2 text-sm font-black text-white">
                                    {{
                                        new Date(
                                            order.created_at,
                                        ).toLocaleTimeString('id-ID', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/5 p-4"
                            >
                                <PackageCheck
                                    class="h-5 w-5 text-emerald-200"
                                />
                                <p
                                    class="mt-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Pelanggan
                                </p>
                                <p class="mt-2 text-sm font-black text-white">
                                    {{ order.customer?.name || 'Pelanggan QR' }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/5 p-4"
                            >
                                <CheckCircle2 class="h-5 w-5 text-cyan-200" />
                                <p
                                    class="mt-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Total
                                </p>
                                <p class="mt-2 text-sm font-black text-white">
                                    {{ formatPrice(order.total_amount) }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[28px] border border-white/10 bg-slate-950/60 p-5 backdrop-blur"
                    >
                        <h2 class="text-xl font-black text-white">
                            Rincian pesanan
                        </h2>
                        <div class="mt-5 space-y-3">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="rounded-[24px] border border-white/10 bg-slate-900/70 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-white">
                                            {{ item.product?.name }}
                                        </p>
                                        <p
                                            v-if="item.variant?.name"
                                            class="mt-1 text-[11px] font-semibold uppercase tracking-wider text-orange-300"
                                        >
                                            {{ item.variant.name }}
                                        </p>
                                        <p
                                            v-if="item.notes"
                                            class="mt-1 text-[11px] italic text-slate-500"
                                        >
                                            "{{ item.notes }}"
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p
                                            class="text-sm font-black text-white"
                                        >
                                            {{ item.quantity }}x
                                        </p>
                                        <p
                                            class="mt-1 text-[12px] text-slate-400"
                                        >
                                            {{ formatPrice(item.total_price) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section
                        class="rounded-[28px] border border-white/10 bg-slate-950/70 p-5 backdrop-blur"
                    >
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.22em] text-fuchsia-200"
                        >
                            Aksi
                        </p>
                        <div class="mt-4 space-y-3">
                            <button
                                type="button"
                                @click="refreshPage"
                                class="flex w-full items-center justify-center gap-2 rounded-[22px] border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-slate-100 transition hover:bg-white/10"
                            >
                                <RefreshCcw class="h-4 w-4" />
                                Refresh Status
                            </button>
                            <button
                                v-if="
                                    checkoutUrl &&
                                    order.status === 'payment_pending'
                                "
                                type="button"
                                @click="openCheckout"
                                class="flex w-full items-center justify-center gap-2 rounded-[22px] bg-gradient-to-r from-fuchsia-500 to-pink-500 px-5 py-3 text-sm font-black text-white"
                            >
                                <QrCode class="h-4 w-4" />
                                Buka Checkout QRIS
                            </button>
                        </div>
                    </section>

                    <section
                        class="rounded-[28px] border border-white/10 bg-slate-950/70 p-5 backdrop-blur"
                    >
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.22em] text-fuchsia-200"
                        >
                            Catatan Sistem
                        </p>
                        <div
                            class="mt-4 space-y-3 text-sm leading-6 text-slate-300"
                        >
                            <p>
                                Jika pembayaran belum selesai, order belum
                                diproses dapur.
                            </p>
                            <p>
                                Setelah status berubah ke pembayaran diterima,
                                kasir dan kitchen akan melihat order ini di
                                sistem operasional.
                            </p>
                            <p>
                                Halaman ini bisa dibuka ulang dari link QR meja
                                yang sama selama order masih aktif.
                            </p>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>
