<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, MessageSquare, Printer } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    order: {
        id: string;
        order_number: string;
        status: string;
        subtotal: number;
        discount_amount: number;
        total_amount: number;
        paid_amount: number;
        remaining_amount: number;
        receipt_method: 'print' | 'whatsapp' | 'skip';
        receipt_phone?: string | null;
        created_at?: string | null;
        updated_at?: string | null;
        payment_method?: string | null;
        payment_status?: string | null;
        customer?: {
            name: string;
            phone?: string | null;
        } | null;
        cashier?: string | null;
        table?: string | null;
        pre_order?: Record<string, any> | null;
        kasbon?: Record<string, any> | null;
        items: Array<{
            id: string;
            product_name: string;
            variant_name?: string | null;
            quantity: number;
            unit_price: number;
            total_price: number;
        }>;
        payment_logs: Array<{
            id: string;
            payment_type: string;
            payment_method?: string | null;
            amount: number;
            notes?: string | null;
            created_at?: string | null;
            user_name?: string | null;
        }>;
    };
    outlet: {
        name?: string | null;
        address?: string | null;
        phone?: string | null;
        receipt_metadata?: {
            receipt_template?: 'classic' | 'modern' | 'compact' | null;
            receipt_font?: 'sans' | 'serif' | 'mono' | null;
            receipt_color?: 'mono' | 'orange' | 'rose' | 'emerald' | null;
            receipt_footer?: string | null;
            receipt_logo?: string | null;
        } | null;
    };
    whatsappLink: string;
    success?: string | null;
}>();

const receiptTemplate = computed(
    () => props.outlet.receipt_metadata?.receipt_template || 'classic',
);
const receiptFont = computed(
    () => props.outlet.receipt_metadata?.receipt_font || 'sans',
);
const receiptColor = computed(
    () => props.outlet.receipt_metadata?.receipt_color || 'mono',
);
const receiptFooterText = computed(
    () =>
        props.outlet.receipt_metadata?.receipt_footer ||
        'Terima kasih atas kunjungan Anda!',
);
const receiptLogo = computed(
    () => props.outlet.receipt_metadata?.receipt_logo || null,
);

const receiptFontClass = computed(() => {
    if (receiptFont.value === 'serif') return 'font-serif';
    if (receiptFont.value === 'mono') return 'font-mono';
    return 'font-sans';
});

const receiptAccentColorText = computed(() => {
    if (receiptColor.value === 'orange') return 'text-orange-600';
    if (receiptColor.value === 'rose') return 'text-rose-600';
    if (receiptColor.value === 'emerald') return 'text-emerald-600';
    return 'text-slate-900';
});

const receiptAccentBg = computed(() => {
    if (receiptColor.value === 'orange') return 'bg-orange-500 text-white';
    if (receiptColor.value === 'rose') return 'bg-rose-500 text-white';
    if (receiptColor.value === 'emerald') return 'bg-emerald-500 text-white';
    return 'bg-white dark:bg-slate-900 text-white';
});

const receiptPaperClass = computed(() => {
    if (receiptTemplate.value === 'modern') {
        return 'rounded-[24px] border border-slate-200 bg-white text-slate-900 shadow-2xl p-8';
    }
    if (receiptTemplate.value === 'compact') {
        return 'rounded-none border border-dotted border-slate-300 bg-white text-slate-900 max-w-[400px] mx-auto p-4 text-[11px]';
    }
    // classic
    return 'rounded-none border border-dashed border-slate-200 bg-white text-slate-900 p-8';
});

const receiptDividerClass = computed(() => {
    if (receiptTemplate.value === 'compact') {
        return 'border-t border-dotted border-slate-300';
    }
    return 'border-t border-dashed border-slate-200';
});

function formatCurrency(value: number | string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

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

function formatPaymentMethod(value?: string | null) {
    if (!value) return '-';
    if (value === 'qris') return 'QRIS';
    if (value === 'ewallet') return 'E-Wallet';
    return value.toUpperCase();
}

function markReceipt(method: 'print' | 'whatsapp' | 'skip') {
    router.post(
        route('transactions.receipt.mark', props.order.id),
        {
            receipt_method: method,
            receipt_phone:
                props.order.receipt_phone ||
                props.order.customer?.phone ||
                null,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

function printReceipt() {
    markReceipt('print');
    window.print();
}

function sendWhatsapp() {
    markReceipt('whatsapp');
    if (props.whatsappLink) {
        window.open(props.whatsappLink, '_blank');
    }
}

function skipReceipt() {
    markReceipt('skip');
}
</script>

<template>
    <Head :title="`Struk ${order.order_number}`" />

    <div
        class="min-h-screen bg-[#0b1220] px-4 py-8 text-stone-900 dark:text-slate-100"
    >
        <div class="mx-auto flex max-w-5xl flex-col gap-6">
            <div
                v-if="success"
                class="flex items-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-300 print:hidden"
            >
                <CheckCircle2 class="h-4 w-4" />
                <span>{{ success }}</span>
            </div>

            <div class="flex items-center justify-between gap-4 print:hidden">
                <div>
                    <p
                        class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300"
                    >
                        Struk Transaksi
                    </p>
                    <h1
                        class="mt-2 text-2xl font-black text-stone-900 dark:text-white"
                    >
                        Struk {{ order.order_number }}
                    </h1>
                    <p class="mt-1 text-sm text-stone-500 dark:text-slate-400">
                        Preview struk transaksi, pre-order, atau kasbon sebelum
                        print / kirim WhatsApp.
                    </p>
                </div>
                <Link
                    :href="route('transactions.index')"
                    class="rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm font-bold text-stone-600 transition hover:border-stone-300 dark:border-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300"
                >
                    Kembali ke Transaksi
                </Link>
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.72fr_0.28fr]">
                <div
                    :class="[receiptFontClass, receiptPaperClass]"
                    class="shadow-2xl transition-all duration-300 print:border-0 print:shadow-none"
                >
                    <div :class="receiptDividerClass" class="pb-6 text-center">
                        <!-- Logo kustom outlet -->
                        <img
                            v-if="receiptLogo"
                            :src="receiptLogo"
                            class="mx-auto mb-4 max-h-16 max-w-[160px] object-contain grayscale filter print:filter-none"
                            alt="Logo Outlet"
                        />
                        <div
                            v-else
                            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"
                        >
                            <CheckCircle2 class="h-7 w-7" />
                        </div>
                        <h2
                            class="text-xl font-black"
                            :class="receiptAccentColorText"
                        >
                            {{ outlet.name || 'POS Mentai' }}
                        </h2>
                        <p
                            class="mt-1.5 text-sm text-stone-400 dark:text-slate-500"
                        >
                            {{ outlet.address || '-' }}
                        </p>
                        <p
                            class="mt-0.5 text-sm text-stone-400 dark:text-slate-500"
                        >
                            {{ outlet.phone || '-' }}
                        </p>
                        <p
                            class="mt-3 text-xs font-bold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                        >
                            {{ order.order_number }} ·
                            {{
                                formatDateTime(
                                    order.updated_at || order.created_at,
                                )
                            }}
                        </p>
                    </div>

                    <div class="space-y-6 px-8 py-8">
                        <div
                            class="grid gap-3 text-sm text-slate-600 sm:grid-cols-2"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                                >
                                    Pelanggan
                                </p>
                                <p class="mt-2 font-semibold text-slate-900">
                                    {{ order.customer?.name || 'Walk-in' }}
                                </p>
                                <p class="mt-1">
                                    {{ order.customer?.phone || '-' }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                                >
                                    Kasir / Layanan
                                </p>
                                <p class="mt-2 font-semibold text-slate-900">
                                    {{ order.cashier || 'Kasir Restoran' }}
                                </p>
                                <p class="mt-1">
                                    {{ order.table || 'Takeaway / Counter' }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="overflow-hidden rounded-2xl border border-slate-200"
                        >
                            <table
                                class="min-w-full divide-y divide-slate-200 text-left text-sm"
                            >
                                <thead
                                    class="bg-slate-50 text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    <tr>
                                        <th class="px-4 py-3">Item</th>
                                        <th class="px-4 py-3 text-center">
                                            Qty
                                        </th>
                                        <th class="px-4 py-3 text-right">
                                            Harga
                                        </th>
                                        <th class="px-4 py-3 text-right">
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <tr
                                        v-for="item in order.items"
                                        :key="item.id"
                                    >
                                        <td class="px-4 py-3">
                                            <div
                                                class="font-semibold text-slate-900"
                                            >
                                                {{ item.product_name }}
                                            </div>
                                            <div
                                                v-if="item.variant_name"
                                                class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                                            >
                                                Varian: {{ item.variant_name }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center font-bold text-slate-900"
                                        >
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{
                                                formatCurrency(item.unit_price)
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-bold text-slate-900"
                                        >
                                            {{
                                                formatCurrency(item.total_price)
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                        >
                            <div
                                class="flex justify-between text-sm text-slate-600"
                            >
                                <span>Subtotal</span>
                                <span>{{
                                    formatCurrency(order.subtotal)
                                }}</span>
                            </div>
                            <div
                                class="mt-2 flex justify-between text-sm text-slate-600"
                            >
                                <span>Diskon</span>
                                <span>{{
                                    formatCurrency(order.discount_amount)
                                }}</span>
                            </div>
                            <div
                                class="mt-3 flex justify-between border-t border-slate-200 pt-3 text-base font-black"
                                :class="receiptAccentColorText"
                            >
                                <span>Total</span>
                                <span>{{
                                    formatCurrency(order.total_amount)
                                }}</span>
                            </div>
                            <div
                                class="mt-2 flex justify-between text-sm text-slate-600"
                            >
                                <span>Terbayar</span>
                                <span>{{
                                    formatCurrency(order.paid_amount)
                                }}</span>
                            </div>
                            <div
                                class="mt-2 flex justify-between text-sm"
                                :class="
                                    order.remaining_amount > 0
                                        ? 'text-orange-600'
                                        : 'text-emerald-700'
                                "
                            >
                                <span>Sisa</span>
                                <span class="font-bold">{{
                                    formatCurrency(order.remaining_amount)
                                }}</span>
                            </div>
                        </div>

                        <div
                            v-if="order.payment_logs.length"
                            class="rounded-2xl border border-slate-200 p-5"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                            >
                                Riwayat Pembayaran
                            </p>
                            <div class="mt-4 space-y-3">
                                <div
                                    v-for="log in order.payment_logs"
                                    :key="log.id"
                                    class="flex items-start justify-between gap-3 border-b border-dashed border-slate-200 pb-3 last:border-0 last:pb-0"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            {{
                                                log.payment_type.replace(
                                                    '_',
                                                    ' ',
                                                )
                                            }}
                                            ·
                                            {{
                                                formatPaymentMethod(
                                                    log.payment_method,
                                                )
                                            }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                                        >
                                            {{ formatDateTime(log.created_at) }}
                                            · {{ log.user_name || 'System' }}
                                        </p>
                                        <p
                                            v-if="log.notes"
                                            class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                                        >
                                            {{ log.notes }}
                                        </p>
                                    </div>
                                    <span
                                        class="text-sm font-black text-slate-900"
                                        >{{ formatCurrency(log.amount) }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="order.pre_order || order.kasbon"
                            class="grid gap-4 sm:grid-cols-2"
                        >
                            <div
                                v-if="order.pre_order"
                                class="rounded-2xl border border-slate-200 p-4"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                                >
                                    Pre-Order
                                </p>
                                <p
                                    class="mt-2 text-sm font-semibold text-slate-900"
                                >
                                    Pickup
                                    {{
                                        formatDateTime(
                                            order.pre_order.pickup_at,
                                        )
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                                >
                                    DP
                                    {{
                                        order.pre_order.down_payment_type ===
                                        'percentage'
                                            ? `${order.pre_order.down_payment_value}%`
                                            : formatCurrency(
                                                  order.pre_order
                                                      .down_payment_value || 0,
                                              )
                                    }}
                                </p>
                            </div>
                            <div
                                v-if="order.kasbon"
                                class="rounded-2xl border border-slate-200 p-4"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-500 dark:text-slate-400"
                                >
                                    Kasbon
                                </p>
                                <p
                                    class="mt-2 text-sm font-semibold text-slate-900"
                                >
                                    Jatuh tempo
                                    {{
                                        order.kasbon.due_date
                                            ? formatDateTime(
                                                  order.kasbon.due_date,
                                              )
                                            : '-'
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-stone-400 dark:text-slate-500"
                                >
                                    Status
                                    {{
                                        order.kasbon.is_active
                                            ? 'masih aktif'
                                            : 'sudah lunas'
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer kustom struk dari konfigurasi printer outlet -->
                        <div
                            class="mt-2 pt-4 text-center text-xs italic leading-relaxed text-stone-400 dark:text-slate-500"
                            :class="receiptDividerClass"
                        >
                            {{ receiptFooterText }}
                        </div>
                    </div>
                </div>

                <div class="space-y-6 print:hidden">
                    <section
                        class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-slate-800/80 dark:bg-slate-900/80"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300"
                        >
                            Aksi Struk
                        </p>
                        <div class="mt-5 space-y-3">
                            <button
                                type="button"
                                @click="printReceipt"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-orange-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-orange-600"
                            >
                                <Printer class="h-4 w-4" />
                                Cetak Struk
                            </button>
                            <button
                                type="button"
                                @click="sendWhatsapp"
                                :disabled="!whatsappLink"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-bold text-emerald-200 transition hover:bg-emerald-500/15 disabled:pointer-events-none disabled:opacity-50"
                            >
                                <MessageSquare class="h-4 w-4" />
                                Kirim WhatsApp
                            </button>
                            <button
                                type="button"
                                @click="skipReceipt"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm font-bold text-stone-600 transition hover:border-stone-300 dark:border-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300"
                            >
                                Lewati Struk
                            </button>
                        </div>
                    </section>

                    <section
                        class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-slate-800/80 dark:bg-slate-900/80"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300"
                        >
                            Ringkasan Payment
                        </p>
                        <div
                            class="mt-4 space-y-3 text-sm text-stone-600 dark:text-slate-300"
                        >
                            <div class="flex justify-between">
                                <span class="text-stone-400 dark:text-slate-500"
                                    >Metode terakhir</span
                                >
                                <span
                                    class="font-semibold text-stone-900 dark:text-white"
                                    >{{
                                        formatPaymentMethod(
                                            order.payment_method,
                                        )
                                    }}</span
                                >
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-400 dark:text-slate-500"
                                    >Status payment</span
                                >
                                <span
                                    class="font-semibold text-stone-900 dark:text-white"
                                    >{{ order.payment_status || '-' }}</span
                                >
                            </div>
                            <div class="flex justify-between">
                                <span class="text-stone-400 dark:text-slate-500"
                                    >Receipt method</span
                                >
                                <span
                                    class="font-semibold text-stone-900 dark:text-white"
                                    >{{ order.receipt_method }}</span
                                >
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
