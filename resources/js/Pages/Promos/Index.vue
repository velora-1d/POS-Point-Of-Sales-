<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Clock3,
    Layers3,
    Pencil,
    Percent,
    Plus,
    Search,
    Sparkles,
    TicketPercent,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface PromoRuleRow {
    id?: string;
    trigger: string;
    reference_id?: string | null;
    reference_value?: string | null;
}

interface PromoRow {
    id: string;
    name: string;
    code?: string | null;
    type: string;
    apply_method: string;
    discount_percent?: number | string | null;
    discount_amount?: number | string | null;
    max_discount_amount?: number | string | null;
    min_transaction_amount?: number | string | null;
    buy_quantity?: number | null;
    get_quantity?: number | null;
    can_stack?: boolean;
    usage_limit?: number | null;
    usage_count?: number | null;
    start_date?: string | null;
    end_date?: string | null;
    happy_hour_start?: string | null;
    happy_hour_end?: string | null;
    status: string;
    rules?: PromoRuleRow[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ProductOption {
    id: string;
    name: string;
    category_id?: string | null;
}

interface CategoryOption {
    id: string;
    name: string;
}

interface MembershipTierOption {
    id: string;
    name: string;
    tier?: string | null;
    discount_percent?: number | string | null;
}

const props = defineProps<{
    promos: {
        data: PromoRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total: number;
        active: number;
        auto_apply: number;
        stackable: number;
    };
    filters: {
        search?: string;
        status?: string;
        type?: string;
        apply_method?: string;
        per_page?: number;
    };
    referenceData: {
        products: ProductOption[];
        categories: CategoryOption[];
        membershipTiers: MembershipTierOption[];
        paymentMethods: string[];
        dayOptions: string[];
    };
    success?: string | null;
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const type = ref(props.filters.type || '');
const applyMethod = ref(props.filters.apply_method || '');
const modalMode = ref<'create' | 'edit' | null>(null);
const selectedPromo = ref<PromoRow | null>(null);

const typeOptions = [
    { value: 'percent', label: 'Persentase' },
    { value: 'nominal', label: 'Nominal' },
    { value: 'buy_x_get_y', label: 'Buy X Get Y' },
];

const applyMethodOptions = [
    { value: 'auto', label: 'Auto' },
    { value: 'manual', label: 'Manual Kode' },
    { value: 'both', label: 'Auto + Manual' },
];

const statusOptions = [
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
    { value: 'expired', label: 'Expired' },
    { value: 'limit_reached', label: 'Limit Habis' },
];

const triggerOptions = [
    { value: 'product', label: 'Produk' },
    { value: 'category', label: 'Kategori' },
    { value: 'transaction', label: 'Total Transaksi' },
    { value: 'time', label: 'Waktu / Hari' },
    { value: 'payment_method', label: 'Metode Bayar' },
    { value: 'member_tier', label: 'Tier Member' },
];

const paymentMethodLabels: Record<string, string> = {
    cash: 'Cash',
    qris: 'QRIS',
    card: 'Kartu',
    transfer: 'Transfer',
};

const dayLabels: Record<string, string> = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu',
};

const promoForm = useForm<{
    name: string;
    code: string;
    type: string;
    apply_method: string;
    discount_percent: number | string | null;
    discount_amount: number | string | null;
    max_discount_amount: number | string | null;
    min_transaction_amount: number | string | null;
    buy_quantity: number | string | null;
    get_quantity: number | string | null;
    can_stack: boolean;
    usage_limit: number | string | null;
    start_date: string;
    end_date: string;
    happy_hour_start: string;
    happy_hour_end: string;
    status: string;
    rules: PromoRuleRow[];
}>({
    name: '',
    code: '',
    type: 'percent',
    apply_method: 'auto',
    discount_percent: 10,
    discount_amount: null,
    max_discount_amount: null,
    min_transaction_amount: null,
    buy_quantity: null,
    get_quantity: null,
    can_stack: false,
    usage_limit: null,
    start_date: '',
    end_date: '',
    happy_hour_start: '',
    happy_hour_end: '',
    status: 'active',
    rules: [],
});

const productMap = computed(() => new Map(props.referenceData.products.map((item) => [item.id, item])));
const categoryMap = computed(() => new Map(props.referenceData.categories.map((item) => [item.id, item])));
const membershipTierMap = computed(() => new Map(props.referenceData.membershipTiers.map((item) => [item.id, item])));

const summaryCards = computed(() => [
    {
        label: 'Total Template',
        value: props.summary.total,
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
        icon: TicketPercent,
    },
    {
        label: 'Promo Aktif',
        value: props.summary.active,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: Sparkles,
    },
    {
        label: 'Auto Apply',
        value: props.summary.auto_apply,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Clock3,
    },
    {
        label: 'Bisa Stack',
        value: props.summary.stackable,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: Layers3,
    },
]);

const isModalOpen = computed(() => modalMode.value !== null);
const modalTitle = computed(() => (modalMode.value === 'edit' ? 'Edit Template Promo' : 'Template Promo Baru'));

const submitFilters = () => {
    router.get(
        route('promos.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            type: type.value || undefined,
            apply_method: applyMethod.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    type.value = '';
    applyMethod.value = '';
    submitFilters();
};

const resetPromoForm = () => {
    promoForm.reset();
    promoForm.name = '';
    promoForm.code = '';
    promoForm.type = 'percent';
    promoForm.apply_method = 'auto';
    promoForm.discount_percent = 10;
    promoForm.discount_amount = null;
    promoForm.max_discount_amount = null;
    promoForm.min_transaction_amount = null;
    promoForm.buy_quantity = null;
    promoForm.get_quantity = null;
    promoForm.can_stack = false;
    promoForm.usage_limit = null;
    promoForm.start_date = '';
    promoForm.end_date = '';
    promoForm.happy_hour_start = '';
    promoForm.happy_hour_end = '';
    promoForm.status = 'active';
    promoForm.rules = [];
    promoForm.clearErrors();
};

const getLocalDatetimeValue = () => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

    return date.toISOString().slice(0, 16);
};

const toDatetimeLocal = (value?: string | null) => {
    if (!value) return '';
    return value.replace(' ', 'T').slice(0, 16);
};

const openCreateModal = () => {
    selectedPromo.value = null;
    modalMode.value = 'create';
    resetPromoForm();
    promoForm.start_date = getLocalDatetimeValue();
};

const openEditModal = (promo: PromoRow) => {
    selectedPromo.value = promo;
    modalMode.value = 'edit';
    resetPromoForm();

    promoForm.name = promo.name;
    promoForm.code = promo.code || '';
    promoForm.type = promo.type;
    promoForm.apply_method = promo.apply_method;
    promoForm.discount_percent = promo.discount_percent || null;
    promoForm.discount_amount = promo.discount_amount || null;
    promoForm.max_discount_amount = promo.max_discount_amount || null;
    promoForm.min_transaction_amount = promo.min_transaction_amount || null;
    promoForm.buy_quantity = promo.buy_quantity || null;
    promoForm.get_quantity = promo.get_quantity || null;
    promoForm.can_stack = Boolean(promo.can_stack);
    promoForm.usage_limit = promo.usage_limit || null;
    promoForm.start_date = toDatetimeLocal(promo.start_date);
    promoForm.end_date = toDatetimeLocal(promo.end_date);
    promoForm.happy_hour_start = promo.happy_hour_start || '';
    promoForm.happy_hour_end = promo.happy_hour_end || '';
    promoForm.status = promo.status;
    promoForm.rules = (promo.rules || []).map((rule) => ({
        trigger: rule.trigger,
        reference_id: rule.reference_id || '',
        reference_value: rule.reference_value || '',
    }));
};

const closeModal = () => {
    modalMode.value = null;
    selectedPromo.value = null;
    resetPromoForm();
};

const addRule = () => {
    promoForm.rules.push({
        trigger: 'transaction',
        reference_id: '',
        reference_value: '',
    });
};

const removeRule = (index: number) => {
    promoForm.rules.splice(index, 1);
};

const getTriggerLabel = (value: string) => {
    return triggerOptions.find((option) => option.value === value)?.label || value;
};

const getRuleLabel = (value: string) => getTriggerLabel(value);

const getTypeLabel = (value: string) => {
    return typeOptions.find((option) => option.value === value)?.label || value;
};

const getApplyMethodLabel = (value: string) => {
    return applyMethodOptions.find((option) => option.value === value)?.label || value;
};

const formatPrice = (value: unknown) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
};

const formatDateTime = (value?: string | null) => {
    if (!value) return 'Tanpa batas';

    const normalized = value.includes(' ') ? value.replace(' ', 'T') : value;

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(normalized));
};

const statusClass = (promoStatus: string) => {
    if (promoStatus === 'active') return 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300';
    if (promoStatus === 'inactive') return 'border-slate-500/20 bg-slate-500/10 text-stone-600 dark:text-slate-300';
    if (promoStatus === 'expired') return 'border-rose-400/20 bg-rose-500/10 text-rose-300';
    return 'border-amber-400/20 bg-amber-500/10 text-amber-300';
};

const getRuleDescription = (rule: PromoRuleRow) => {
    switch (rule.trigger) {
        case 'product':
            return productMap.value.get(rule.reference_id || '')?.name || 'Produk terpilih';
        case 'category':
            return categoryMap.value.get(rule.reference_id || '')?.name || 'Kategori terpilih';
        case 'member_tier': {
            const tier = membershipTierMap.value.get(rule.reference_id || '');
            return tier ? `${tier.name} (${tier.tier || 'tier'})` : 'Tier member';
        }
        case 'payment_method':
            return paymentMethodLabels[rule.reference_value || ''] || 'Metode bayar';
        case 'time':
            return dayLabels[rule.reference_value || ''] || 'Happy hour / hari tertentu';
        case 'transaction':
            return promoForm.min_transaction_amount
                ? `Minimal ${formatPrice(promoForm.min_transaction_amount)}`
                : 'Threshold total transaksi';
        default:
            return 'Trigger promo';
    }
};

const getStoredRuleDescription = (promo: PromoRow, rule: PromoRuleRow) => {
    switch (rule.trigger) {
        case 'product':
            return productMap.value.get(rule.reference_id || '')?.name || 'Produk terpilih';
        case 'category':
            return categoryMap.value.get(rule.reference_id || '')?.name || 'Kategori terpilih';
        case 'member_tier': {
            const tier = membershipTierMap.value.get(rule.reference_id || '');
            return tier ? `${tier.name} (${tier.tier || 'tier'})` : 'Tier member';
        }
        case 'payment_method':
            return paymentMethodLabels[rule.reference_value || ''] || 'Metode bayar';
        case 'time':
            return dayLabels[rule.reference_value || ''] || 'Happy hour / hari tertentu';
        case 'transaction':
            return promo.min_transaction_amount
                ? `Minimal ${formatPrice(promo.min_transaction_amount)}`
                : 'Threshold total transaksi';
        default:
            return 'Trigger promo';
    }
};

const submitPromo = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (modalMode.value === 'edit' && selectedPromo.value) {
        promoForm.patch(route('promos.update', selectedPromo.value.id), options);
        return;
    }

    promoForm.post(route('promos.store'), options);
};
</script>

<template>
    <Head title="Template Promo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-stone-900 dark:text-white">
                        Template Promo & Diskon
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400">
                        Kelola template promo owner-level untuk diskon persen, nominal, atau buy X get Y beserta trigger, periode, limit pakai, dan stacking rule.
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

            <section class="grid gap-3 lg:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-2xl border p-4 shadow-[0_18px_50px_rgba(15,23,42,0.16)]"
                    :class="card.surface"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-3xl font-black" :class="card.tone">
                                {{ card.value }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/40 p-3 text-stone-900 dark:text-white">
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Cari promo</span>
                            <div class="flex items-center gap-2 rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/80 px-3">
                                <Search class="h-4 w-4 text-stone-400 dark:text-slate-500" />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Nama atau kode"
                                    class="w-full border-0 bg-transparent px-0 py-3 text-sm text-stone-900 dark:text-white placeholder:text-stone-400 dark:text-slate-500 focus:outline-none focus:ring-0"
                                    @keyup.enter="submitFilters"
                                />
                            </div>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Status</span>
                            <select
                                v-model="status"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/80 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua status</option>
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Jenis</span>
                            <select
                                v-model="type"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/80 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua jenis</option>
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Metode apply</span>
                            <select
                                v-model="applyMethod"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/80 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua metode</option>
                                <option
                                    v-for="option in applyMethodOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-stone-200 dark:border-white/10 px-4 py-3 text-sm font-semibold text-stone-800 dark:text-slate-200 transition hover:border-stone-200 dark:border-white/20 hover:bg-stone-100 dark:bg-white/5"
                            @click="clearFilters"
                        >
                            Reset Filter
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-orange-400"
                            @click="submitFilters"
                        >
                            Terapkan
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl border border-orange-400/20 bg-orange-500/10 px-4 py-3 text-sm font-bold text-orange-200 transition hover:bg-orange-500/15"
                            @click="openCreateModal"
                        >
                            <Plus class="h-4 w-4" />
                            Promo Baru
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/70 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex items-center justify-between border-b border-stone-200 dark:border-white/10 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300">
                            Daftar Template Promo
                        </h3>
                        <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">
                            Menampilkan {{ props.promos.from ?? 0 }} - {{ props.promos.to ?? 0 }} dari {{ props.promos.total }} promo.
                        </p>
                    </div>
                </div>

                <div v-if="!props.promos.data.length" class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400">
                    Belum ada template promo pada filter ini.
                </div>

                <div v-else class="divide-y divide-white/10">
                    <article
                        v-for="promo in props.promos.data"
                        :key="promo.id"
                        class="grid gap-4 px-5 py-5 xl:grid-cols-[1.25fr_0.9fr_0.8fr_1.2fr_auto]"
                    >
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-black text-stone-900 dark:text-white">{{ promo.name }}</h3>
                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="statusClass(promo.status)"
                                >
                                    {{ statusOptions.find((item) => item.value === promo.status)?.label || promo.status }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-stone-500 dark:text-slate-400">
                                <span class="rounded-full border border-stone-200 dark:border-white/10 bg-white/[0.03] px-2.5 py-1 font-semibold">
                                    {{ getTypeLabel(promo.type) }}
                                </span>
                                <span class="rounded-full border border-stone-200 dark:border-white/10 bg-white/[0.03] px-2.5 py-1 font-semibold">
                                    {{ getApplyMethodLabel(promo.apply_method) }}
                                </span>
                                <span
                                    v-if="promo.code"
                                    class="rounded-full border border-orange-400/20 bg-orange-500/10 px-2.5 py-1 font-semibold text-orange-200"
                                >
                                    {{ promo.code }}
                                </span>
                            </div>
                            <div class="text-sm text-stone-600 dark:text-slate-300">
                                <template v-if="promo.type === 'percent'">
                                    Diskon {{ Number(promo.discount_percent || 0) }}%
                                    <span v-if="promo.max_discount_amount" class="text-stone-400 dark:text-slate-500">
                                        • cap {{ formatPrice(promo.max_discount_amount) }}
                                    </span>
                                </template>
                                <template v-else-if="promo.type === 'nominal'">
                                    Potongan {{ formatPrice(promo.discount_amount) }}
                                </template>
                                <template v-else>
                                    Beli {{ promo.buy_quantity || 0 }} gratis {{ promo.get_quantity || 0 }}
                                </template>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm text-stone-600 dark:text-slate-300">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Trigger & Kondisi
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="rule in promo.rules || []"
                                    :key="`${promo.id}-${rule.id || rule.trigger}-${rule.reference_id || rule.reference_value || 'none'}`"
                                    class="rounded-full border border-sky-400/15 bg-sky-500/10 px-2.5 py-1 text-[11px] font-semibold text-sky-200"
                                >
                                    {{ getTriggerLabel(rule.trigger) }}: {{ getStoredRuleDescription(promo, rule) }}
                                </span>
                                <span
                                    v-if="promo.min_transaction_amount"
                                    class="rounded-full border border-amber-400/15 bg-amber-500/10 px-2.5 py-1 text-[11px] font-semibold text-amber-200"
                                >
                                    Min. transaksi {{ formatPrice(promo.min_transaction_amount) }}
                                </span>
                                <span
                                    v-if="!promo.rules?.length && !promo.min_transaction_amount"
                                    class="text-xs text-stone-400 dark:text-slate-500"
                                >
                                    Belum ada trigger tambahan
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm text-stone-600 dark:text-slate-300">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Periode
                            </p>
                            <p>{{ formatDateTime(promo.start_date) }}</p>
                            <p class="text-stone-400 dark:text-slate-500">s/d {{ formatDateTime(promo.end_date) }}</p>
                            <p v-if="promo.happy_hour_start || promo.happy_hour_end" class="text-xs text-orange-200">
                                Happy hour {{ promo.happy_hour_start || '--:--' }} - {{ promo.happy_hour_end || '--:--' }}
                            </p>
                        </div>

                        <div class="space-y-2 text-sm text-stone-600 dark:text-slate-300">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                Limit & Stacking
                            </p>
                            <p>
                                Limit:
                                <span class="font-semibold text-stone-900 dark:text-white">
                                    {{ promo.usage_limit ? `${promo.usage_count || 0}/${promo.usage_limit}` : 'Tanpa batas' }}
                                </span>
                            </p>
                            <p>
                                Stack:
                                <span :class="promo.can_stack ? 'text-emerald-300' : 'text-stone-400 dark:text-slate-500'">
                                    {{ promo.can_stack ? 'Bisa digabung promo lain' : 'Pilih nilai promo terbesar' }}
                                </span>
                            </p>
                        </div>

                        <div class="flex items-start justify-end">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-2xl border border-stone-200 dark:border-white/10 px-3 py-2 text-sm font-semibold text-stone-800 dark:text-slate-200 transition hover:border-orange-400/30 hover:bg-orange-500/10 hover:text-orange-100"
                                @click="openEditModal(promo)"
                            >
                                <Pencil class="h-4 w-4" />
                                Edit
                            </button>
                        </div>
                    </article>
                </div>

                <div
                    v-if="props.promos.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 dark:border-white/10 px-5 py-4"
                >
                    <p class="text-xs text-stone-400 dark:text-slate-500">
                        Pagination promo tetap aktif untuk menjaga list owner tetap ringan.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in props.promos.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                            :class="link.active
                                ? 'border-orange-400/30 bg-orange-500/15 text-orange-100'
                                : 'border-stone-200 dark:border-white/10 text-stone-600 dark:text-slate-300 hover:bg-stone-100 dark:bg-white/5'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </section>
        </div>

        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-slate-950/80 px-4 py-6 backdrop-blur-sm"
        >
            <div class="max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-3xl border border-stone-200 dark:border-white/10 bg-stone-100 dark:bg-slate-950 p-6 shadow-[0_30px_120px_rgba(15,23,42,0.6)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-black text-stone-900 dark:text-white">{{ modalTitle }}</h3>
                        <p class="mt-1 text-sm text-stone-500 dark:text-slate-400">
                            Susun tipe promo, trigger, periode, dan aturan stack sesuai brief fase promo.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-2xl border border-stone-200 dark:border-white/10 p-2 text-stone-500 dark:text-slate-400 transition hover:border-stone-200 dark:border-white/20 hover:text-stone-900 dark:text-white"
                        @click="closeModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form class="mt-6 space-y-6" @submit.prevent="submitPromo">
                    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="block xl:col-span-2">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Nama promo</span>
                            <input
                                v-model="promoForm.name"
                                type="text"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white placeholder:text-stone-400 dark:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                                placeholder="Contoh: Promo Jumat Berkah"
                            />
                            <p v-if="promoForm.errors.name" class="mt-2 text-xs text-rose-300">{{ promoForm.errors.name }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Jenis promo</span>
                            <select
                                v-model="promoForm.type"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Metode apply</span>
                            <select
                                v-model="promoForm.apply_method"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option
                                    v-for="option in applyMethodOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>
                    </section>

                    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label v-if="promoForm.apply_method !== 'auto'" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Kode promo</span>
                            <input
                                v-model="promoForm.code"
                                type="text"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm uppercase text-stone-900 dark:text-white placeholder:text-stone-400 dark:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                                placeholder="MENTAI10"
                            />
                            <p v-if="promoForm.errors.code" class="mt-2 text-xs text-rose-300">{{ promoForm.errors.code }}</p>
                        </label>

                        <label v-if="promoForm.type === 'percent'" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Diskon persen</span>
                            <input
                                v-model="promoForm.discount_percent"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="promoForm.errors.discount_percent" class="mt-2 text-xs text-rose-300">{{ promoForm.errors.discount_percent }}</p>
                        </label>

                        <label v-if="promoForm.type === 'percent'" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Max diskon</span>
                            <input
                                v-model="promoForm.max_discount_amount"
                                type="number"
                                min="0"
                                step="1000"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                placeholder="Opsional"
                            />
                        </label>

                        <label v-if="promoForm.type === 'nominal'" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Diskon nominal</span>
                            <input
                                v-model="promoForm.discount_amount"
                                type="number"
                                min="0"
                                step="1000"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="promoForm.errors.discount_amount" class="mt-2 text-xs text-rose-300">{{ promoForm.errors.discount_amount }}</p>
                        </label>

                        <label v-if="promoForm.type === 'buy_x_get_y'" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Buy quantity</span>
                            <input
                                v-model="promoForm.buy_quantity"
                                type="number"
                                min="1"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label v-if="promoForm.type === 'buy_x_get_y'" class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Get quantity</span>
                            <input
                                v-model="promoForm.get_quantity"
                                type="number"
                                min="1"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="promoForm.errors.buy_quantity" class="mt-2 text-xs text-rose-300">{{ promoForm.errors.buy_quantity }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Min transaksi</span>
                            <input
                                v-model="promoForm.min_transaction_amount"
                                type="number"
                                min="0"
                                step="1000"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                placeholder="Opsional"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Limit penggunaan</span>
                            <input
                                v-model="promoForm.usage_limit"
                                type="number"
                                min="1"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                placeholder="Kosong = tanpa batas"
                            />
                        </label>
                    </section>

                    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Mulai berlaku</span>
                            <input
                                v-model="promoForm.start_date"
                                type="datetime-local"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="promoForm.errors.start_date" class="mt-2 text-xs text-rose-300">{{ promoForm.errors.start_date }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Selesai berlaku</span>
                            <input
                                v-model="promoForm.end_date"
                                type="datetime-local"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="promoForm.errors.end_date" class="mt-2 text-xs text-rose-300">{{ promoForm.errors.end_date }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Happy hour mulai</span>
                            <input
                                v-model="promoForm.happy_hour_start"
                                type="time"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Happy hour selesai</span>
                            <input
                                v-model="promoForm.happy_hour_end"
                                type="time"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                        </label>
                    </section>

                    <section class="rounded-3xl border border-stone-200 dark:border-white/10 bg-white/[0.02] p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300">Trigger Kondisi</h4>
                                <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">
                                    Tambahkan rule produk, kategori, waktu, metode bayar, tier member, atau transaction threshold.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-2xl border border-orange-400/20 bg-orange-500/10 px-3 py-2 text-sm font-semibold text-orange-200"
                                @click="addRule"
                            >
                                <Plus class="h-4 w-4" />
                                Tambah Trigger
                            </button>
                        </div>

                        <div v-if="promoForm.errors.rules" class="mt-3 text-xs text-rose-300">
                            {{ promoForm.errors.rules }}
                        </div>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="(rule, index) in promoForm.rules"
                                :key="`${rule.trigger}-${index}`"
                                class="grid gap-3 rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-950/70 p-4 lg:grid-cols-[0.9fr_1fr_1fr_auto]"
                            >
                                <label class="block">
                                    <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Trigger</span>
                                    <select
                                        v-model="rule.trigger"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option
                                            v-for="option in triggerOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </label>

                                <label
                                    v-if="['product', 'category', 'member_tier'].includes(rule.trigger)"
                                    class="block"
                                >
                                    <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Referensi</span>
                                    <select
                                        v-model="rule.reference_id"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option value="">Pilih referensi</option>
                                        <option
                                            v-for="option in rule.trigger === 'product'
                                                ? props.referenceData.products
                                                : rule.trigger === 'category'
                                                    ? props.referenceData.categories
                                                    : props.referenceData.membershipTiers"
                                            :key="option.id"
                                            :value="option.id"
                                        >
                                            {{ option.name }}
                                        </option>
                                    </select>
                                </label>

                                <label
                                    v-else-if="rule.trigger === 'payment_method'"
                                    class="block"
                                >
                                    <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Metode bayar</span>
                                    <select
                                        v-model="rule.reference_value"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option value="">Pilih metode bayar</option>
                                        <option
                                            v-for="method in props.referenceData.paymentMethods"
                                            :key="method"
                                            :value="method"
                                        >
                                            {{ paymentMethodLabels[method] || method }}
                                        </option>
                                    </select>
                                </label>

                                <label
                                    v-else-if="rule.trigger === 'time'"
                                    class="block"
                                >
                                    <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">Hari aktif</span>
                                    <select
                                        v-model="rule.reference_value"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-3 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                                    >
                                        <option value="">Pilih hari atau pakai happy hour saja</option>
                                        <option
                                            v-for="day in props.referenceData.dayOptions"
                                            :key="day"
                                            :value="day"
                                        >
                                            {{ dayLabels[day] || day }}
                                        </option>
                                    </select>
                                </label>

                                <div v-else class="rounded-2xl border border-dashed border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/60 px-4 py-3 text-sm text-stone-500 dark:text-slate-400">
                                    {{ getRuleDescription(rule) }}
                                </div>

                                <div class="flex items-end justify-between gap-3">
                                    <div class="text-xs text-stone-400 dark:text-slate-500">
                                        {{ getRuleLabel(rule.trigger) }}
                                    </div>
                                    <button
                                        type="button"
                                        class="rounded-2xl border border-rose-400/20 bg-rose-500/10 px-3 py-2 text-xs font-semibold text-rose-200"
                                        @click="removeRule(index)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>

                            <div v-if="!promoForm.rules.length" class="rounded-2xl border border-dashed border-stone-200 dark:border-white/10 px-4 py-6 text-center text-sm text-stone-400 dark:text-slate-500">
                                Belum ada trigger tambahan. Promo tetap bisa disimpan dengan periode dan threshold dasar saja.
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-4 md:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-2xl border border-stone-200 dark:border-white/10 bg-white/[0.02] p-4">
                            <input
                                v-model="promoForm.can_stack"
                                type="checkbox"
                                class="mt-1 rounded border-stone-200 dark:border-white/20 bg-white dark:bg-slate-900 text-orange-500 focus:ring-orange-400"
                            />
                            <span>
                                <span class="block text-sm font-semibold text-stone-900 dark:text-white">Izinkan stacking</span>
                                <span class="mt-1 block text-xs text-stone-400 dark:text-slate-500">
                                    Jika aktif, promo ini bisa digabung dengan promo eligible lain. Jika nonaktif, sistem pilih promo bernilai terbesar.
                                </span>
                            </span>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400">Status promo</span>
                            <select
                                v-model="promoForm.status"
                                class="w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>
                    </section>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-stone-200 dark:border-white/10 pt-5">
                        <button
                            type="button"
                            class="rounded-2xl border border-stone-200 dark:border-white/10 px-4 py-3 text-sm font-semibold text-stone-600 dark:text-slate-300 transition hover:bg-stone-100 dark:bg-white/5"
                            @click="closeModal"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="promoForm.processing"
                        >
                            {{ promoForm.processing ? 'Menyimpan...' : 'Simpan Template Promo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
