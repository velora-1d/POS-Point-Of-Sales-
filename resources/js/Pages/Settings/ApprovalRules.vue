<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Percent, Receipt } from '@lucide/vue';
import { computed, watch } from 'vue';

interface OutletOption {
    id: string;
    name: string;
    is_active: boolean;
    has_config: boolean;
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
        manual_discount_enabled: number;
        order_edit_enabled: number;
    };
    formDefaults: {
        outlet_id: string | null;
        manual_discount_enabled: boolean;
        manual_discount_threshold: number;
        order_edit_enabled: boolean;
        order_edit_threshold: number;
        has_config: boolean;
    };
    filters: {
        outlet_id?: string | null;
    };
    success?: string | null;
}>();

const form = useForm({
    outlet_id: props.formDefaults.outlet_id || props.selectedOutlet?.id || '',
    manual_discount_enabled: Boolean(
        props.formDefaults.manual_discount_enabled,
    ),
    manual_discount_threshold: String(
        props.formDefaults.manual_discount_threshold || 50000,
    ),
    order_edit_enabled: Boolean(props.formDefaults.order_edit_enabled),
    order_edit_threshold: String(
        props.formDefaults.order_edit_threshold || 150000,
    ),
});

watch(
    () => props.formDefaults,
    (defaults) => {
        form.defaults({
            outlet_id: defaults.outlet_id || props.selectedOutlet?.id || '',
            manual_discount_enabled: Boolean(defaults.manual_discount_enabled),
            manual_discount_threshold: String(
                defaults.manual_discount_threshold || 50000,
            ),
            order_edit_enabled: Boolean(defaults.order_edit_enabled),
            order_edit_threshold: String(
                defaults.order_edit_threshold || 150000,
            ),
        });

        form.reset();
        form.clearErrors();
    },
    { deep: true },
);

const summaryCards = computed(() => [
    {
        label: 'Outlet Terkonfigurasi',
        value: `${props.summary.configured_outlets}/${props.summary.total_outlets}`,
        helper: 'Jumlah outlet yang sudah punya threshold approval tersimpan',
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
        icon: CheckCircle2,
    },
    {
        label: 'Manual Discount',
        value: props.summary.manual_discount_enabled,
        helper: 'Outlet yang mengaktifkan approval diskon manual',
        tone: 'text-orange-300',
        surface: 'border-orange-400/15 bg-orange-500/10',
        icon: Percent,
    },
    {
        label: 'Edit Order',
        value: props.summary.order_edit_enabled,
        helper: 'Outlet yang mengaktifkan threshold edit order',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Receipt,
    },
]);

function formatCurrency(value: number | string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

function openSelectedOutlet(outletId: string) {
    router.get(
        route('settings.approval-rules.index'),
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
    form.put(route('settings.approval-rules.update'), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Approval Rules" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Aturan Approval Transaksi
                    </h2>
                    <p class="mt-1 text-sm text-stone-500 dark:text-slate-400">
                        Definisikan threshold nominal yang mewajibkan approval
                        owner untuk diskon manual dan edit order.
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

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <section
                    class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-slate-800/80 dark:bg-slate-900/80"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-stone-200 pb-5 dark:border-slate-800/80 lg:flex-row lg:items-end lg:justify-between"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300"
                            >
                                Konfigurasi Outlet
                            </p>
                            <h3
                                class="mt-2 text-lg font-black text-stone-900 dark:text-white"
                            >
                                Threshold Approval Operasional
                            </h3>
                            <p
                                class="mt-1 text-sm text-stone-500 dark:text-slate-400"
                            >
                                Jika nominal melebihi batas, kasir wajib mengisi
                                PIN owner sebelum aksi diproses.
                            </p>
                        </div>

                        <div class="w-full lg:w-72">
                            <label
                                class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                Pilih Outlet
                            </label>
                            <select
                                :value="form.outlet_id"
                                @change="
                                    openSelectedOutlet(
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                                class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
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

                    <div class="mt-6 space-y-5">
                        <div
                            class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950/50"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4
                                        class="text-base font-black text-stone-900 dark:text-white"
                                    >
                                        Manual Discount Threshold
                                    </h4>
                                    <p
                                        class="mt-1 text-sm leading-6 text-stone-500 dark:text-slate-400"
                                    >
                                        Berlaku saat kasir memakai voucher atau
                                        diskon manual. Sistem menghitung nominal
                                        diskon manual yang benar-benar applied.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="
                                        form.manual_discount_enabled =
                                            !form.manual_discount_enabled
                                    "
                                    class="rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider"
                                    :class="
                                        form.manual_discount_enabled
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                            : 'border-stone-200 bg-white text-stone-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400'
                                    "
                                >
                                    {{
                                        form.manual_discount_enabled
                                            ? 'Aktif'
                                            : 'Nonaktif'
                                    }}
                                </button>
                            </div>

                            <div class="mt-5">
                                <label
                                    class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Threshold Diskon Manual
                                </label>
                                <input
                                    v-model="form.manual_discount_threshold"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                                />
                                <p
                                    class="mt-2 text-xs leading-5 text-stone-400 dark:text-slate-500"
                                >
                                    Preview limit:
                                    {{
                                        formatCurrency(
                                            form.manual_discount_threshold,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950/50"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4
                                        class="text-base font-black text-stone-900 dark:text-white"
                                    >
                                        Edit Order Threshold
                                    </h4>
                                    <p
                                        class="mt-1 text-sm leading-6 text-stone-500 dark:text-slate-400"
                                    >
                                        Berlaku saat kasir mengubah order. Jika
                                        total order hasil edit melewati batas,
                                        sistem mewajibkan PIN owner.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="
                                        form.order_edit_enabled =
                                            !form.order_edit_enabled
                                    "
                                    class="rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider"
                                    :class="
                                        form.order_edit_enabled
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                            : 'border-stone-200 bg-white text-stone-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400'
                                    "
                                >
                                    {{
                                        form.order_edit_enabled
                                            ? 'Aktif'
                                            : 'Nonaktif'
                                    }}
                                </button>
                            </div>

                            <div class="mt-5">
                                <label
                                    class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Threshold Total Order Setelah Edit
                                </label>
                                <input
                                    v-model="form.order_edit_threshold"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                                />
                                <p
                                    class="mt-2 text-xs leading-5 text-stone-400 dark:text-slate-500"
                                >
                                    Preview limit:
                                    {{
                                        formatCurrency(
                                            form.order_edit_threshold,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-6 flex justify-end border-t border-stone-200 pt-5 dark:border-slate-800/80"
                    >
                        <button
                            type="button"
                            @click="submitSave"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-5 py-3 text-sm font-bold text-stone-900 transition disabled:pointer-events-none disabled:opacity-50 dark:text-white"
                        >
                            {{
                                form.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Approval Rules'
                            }}
                        </button>
                    </div>
                </section>

                <section class="space-y-6">
                    <div
                        class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-slate-800/80 dark:bg-slate-900/80"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300"
                        >
                            Cara Kerja
                        </p>
                        <h3
                            class="mt-2 text-lg font-black text-stone-900 dark:text-white"
                        >
                            Lock Before Commit
                        </h3>
                        <p
                            class="mt-3 text-sm leading-7 text-stone-500 dark:text-slate-400"
                        >
                            Runtime order sekarang mengecek threshold sebelum
                            diskon manual atau edit order disimpan. Jika limit
                            terlampaui, kasir wajib memasukkan PIN owner yang
                            valid.
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-stone-200 bg-stone-50 p-6 dark:border-slate-800/80 dark:bg-slate-900/80"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300"
                        >
                            Scope Runtime
                        </p>
                        <div class="mt-4 space-y-4">
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    Diskon Manual
                                </p>
                                <p
                                    class="mt-2 text-xs leading-6 text-stone-500 dark:text-slate-400"
                                >
                                    Diterapkan pada create order baru dan
                                    settlement tagihan existing ketika voucher
                                    manual dipakai.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    Edit Order
                                </p>
                                <p
                                    class="mt-2 text-xs leading-6 text-stone-500 dark:text-slate-400"
                                >
                                    Diterapkan saat total order hasil edit
                                    mencapai atau melewati nominal threshold
                                    outlet.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/50"
                            >
                                <p
                                    class="text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    PIN Supervisor Lama
                                </p>
                                <p
                                    class="mt-2 text-xs leading-6 text-stone-500 dark:text-slate-400"
                                >
                                    Untuk order status
                                    <code
                                        class="rounded bg-stone-100 px-1.5 py-0.5 text-[11px] text-orange-300 dark:bg-slate-950"
                                        >in_progress</code
                                    >, rule supervisor tetap berlaku bila
                                    threshold owner tidak terpenuhi.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
