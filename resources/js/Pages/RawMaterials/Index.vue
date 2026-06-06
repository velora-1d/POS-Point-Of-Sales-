<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    Boxes,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Warehouse,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface RawMaterialRow {
    id: string;
    name: string;
    unit?: string | null;
    quantity?: number;
    minimum_stock?: number;
    cost_per_unit?: number | string | null;
    track_expired?: boolean;
    expired_action?: string | null;
    expired_reminder_days?: number[] | null;
    is_active?: boolean;
    last_restocked_at?: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    materials: {
        data: RawMaterialRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total: number;
        healthy: number;
        low: number;
        inactive: number;
    };
    filters: {
        search?: string;
        status?: string;
        per_page?: number;
    };
    success?: string | null;
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const isMaterialModalOpen = ref(false);
const editingMaterial = ref<RawMaterialRow | null>(null);
const stockTarget = ref<RawMaterialRow | null>(null);
const stockMode = ref<'add' | 'adjust'>('add');

const materialForm = useForm({
    name: '',
    unit: 'gram',
    quantity: 0,
    minimum_stock: 0,
    cost_per_unit: 0,
    track_expired: false,
    expired_action: 'notify_only',
    is_active: true,
});

const stockForm = useForm({
    quantity: 0,
    batch_code: '',
    expired_date: '',
});

const needsExpiredStockDetails = computed(() => {
    return (
        stockMode.value === 'add' && Boolean(stockTarget.value?.track_expired)
    );
});

const summaryCards = computed(() => [
    {
        label: 'Bahan Aktif',
        value: props.summary.total,
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
        icon: Boxes,
    },
    {
        label: 'Stok Sehat',
        value: props.summary.healthy,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/8',
        icon: Warehouse,
    },
    {
        label: 'Stok Menipis',
        value: props.summary.low,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
        icon: AlertTriangle,
    },
    {
        label: 'Nonaktif',
        value: props.summary.inactive,
        tone: 'text-stone-600 dark:text-slate-300',
        surface: 'border-slate-400/15 bg-slate-500/8',
        icon: Archive,
    },
]);

const formatPrice = (value: unknown) => {
    const num = Number(value || 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDateTime = (value: string | null | undefined) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const getStockStateLabel = (material: RawMaterialRow) => {
    const quantity = Number(material.quantity || 0);
    const minimum = Number(material.minimum_stock || 0);

    if (!material.is_active) return 'Nonaktif';
    if (quantity <= 0) return 'Habis';
    if (quantity <= minimum) return 'Menipis';
    return 'Sehat';
};

const getStockStateClass = (material: RawMaterialRow) => {
    const quantity = Number(material.quantity || 0);
    const minimum = Number(material.minimum_stock || 0);

    if (!material.is_active) {
        return 'border-stone-300 dark:border-slate-600/40 bg-stone-100 dark:bg-slate-800/80 text-stone-600 dark:text-slate-300';
    }

    if (quantity <= 0) {
        return 'border-rose-500/20 bg-rose-500/10 text-rose-300';
    }

    if (quantity <= minimum) {
        return 'border-amber-500/20 bg-amber-500/10 text-amber-300';
    }

    return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300';
};

const submitFilters = () => {
    router.get(
        route('raw-materials.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const clearSearch = () => {
    search.value = '';
    submitFilters();
};

const openCreateModal = () => {
    isMaterialModalOpen.value = true;
    editingMaterial.value = null;
    materialForm.clearErrors();
    materialForm.reset();
    materialForm.unit = 'gram';
    materialForm.expired_action = 'notify_only';
    materialForm.is_active = true;
};

const openEditModal = (material: RawMaterialRow) => {
    isMaterialModalOpen.value = true;
    editingMaterial.value = material;
    materialForm.clearErrors();
    materialForm.name = material.name;
    materialForm.unit = material.unit || 'gram';
    materialForm.quantity = Number(material.quantity || 0);
    materialForm.minimum_stock = Number(material.minimum_stock || 0);
    materialForm.cost_per_unit = Number(material.cost_per_unit || 0);
    materialForm.track_expired = Boolean(material.track_expired);
    materialForm.expired_action = material.expired_action || 'notify_only';
    materialForm.is_active = Boolean(material.is_active);
};

const closeMaterialModal = () => {
    isMaterialModalOpen.value = false;
    editingMaterial.value = null;
    materialForm.clearErrors();
};

const submitMaterial = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            isMaterialModalOpen.value = false;
            editingMaterial.value = null;
        },
    };

    if (editingMaterial.value) {
        materialForm.patch(
            route('raw-materials.update', editingMaterial.value.id),
            options,
        );
        return;
    }

    materialForm.post(route('raw-materials.store'), options);
};

const openStockModal = (material: RawMaterialRow, mode: 'add' | 'adjust') => {
    stockTarget.value = material;
    stockMode.value = mode;
    stockForm.clearErrors();
    stockForm.quantity = mode === 'adjust' ? Number(material.quantity || 0) : 0;
    stockForm.batch_code = '';
    stockForm.expired_date = '';
};

const closeStockModal = () => {
    stockTarget.value = null;
    stockForm.clearErrors();
};

const submitStockAction = () => {
    if (!stockTarget.value) return;

    const routeName =
        stockMode.value === 'add'
            ? 'raw-materials.add-stock'
            : 'raw-materials.adjust-stock';

    stockForm.post(route(routeName, stockTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stockTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Inventori Bahan Baku" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Inventori Bahan Baku
                    </h2>
                    <p
                        class="mt-1 max-w-2xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Kelola bahan baku aktif, tambah stok, lakukan
                        adjustment, dan pantau item yang sudah mulai menipis
                        dari satu dashboard.
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
                    v-for="stat in summaryCards"
                    :key="stat.label"
                    :class="[
                        'rounded-[22px] border px-4 py-4 shadow-lg shadow-slate-950/10',
                        stat.surface,
                    ]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                {{ stat.label }}
                            </p>
                            <p :class="['mt-2 text-3xl font-black', stat.tone]">
                                {{ stat.value }}
                            </p>
                        </div>
                        <component
                            :is="stat.icon"
                            class="h-5 w-5 text-stone-400 dark:text-slate-500"
                        />
                    </div>
                </article>
            </section>

            <section
                class="rounded-[26px] border border-stone-200 bg-stone-50 p-4 shadow-xl shadow-slate-950/15 dark:border-slate-800/80 dark:bg-slate-900/92"
            >
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Filter Inventori
                        </p>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Cari nama bahan baku, sempitkan status stok, lalu
                            kelola per item.
                        </p>
                    </div>
                    <div
                        class="flex flex-col gap-2 lg:w-full lg:max-w-4xl lg:flex-row"
                    >
                        <form
                            class="grid flex-1 gap-2 lg:grid-cols-[minmax(0,1fr)_220px_110px]"
                            @submit.prevent="submitFilters"
                        >
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-slate-500"
                                />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cari bahan baku atau unit..."
                                    class="w-full rounded-2xl border border-stone-200 bg-stone-100 py-3 pl-10 pr-10 text-sm text-stone-900 placeholder:text-stone-400 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-500 dark:text-white"
                                />
                                <button
                                    v-if="search"
                                    type="button"
                                    @click="clearSearch"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-900 dark:text-slate-500 dark:text-white"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>

                            <select
                                v-model="status"
                                class="rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                            >
                                <option value="">Semua status</option>
                                <option value="healthy">Stok sehat</option>
                                <option value="low">Stok menipis</option>
                                <option value="out">Stok habis</option>
                                <option value="inactive">Nonaktif</option>
                            </select>

                            <button
                                type="submit"
                                class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-stone-900 dark:text-white"
                            >
                                Terapkan
                            </button>
                        </form>

                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-bold text-emerald-300"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Bahan
                        </button>
                    </div>
                </div>
            </section>

            <section
                class="rounded-[26px] border border-stone-200 bg-stone-50 shadow-xl shadow-slate-950/15 dark:border-slate-800/80 dark:bg-slate-900/92"
            >
                <div
                    class="flex flex-col gap-2 border-b border-stone-200 px-5 py-4 dark:border-slate-800/80 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Daftar Bahan Baku
                        </p>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Menampilkan {{ materials.from ?? 0 }}-{{
                                materials.to ?? 0
                            }}
                            dari {{ materials.total }} bahan baku.
                        </p>
                    </div>
                    <Link
                        :href="route('products.stock')"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-orange-300 transition hover:text-orange-200"
                    >
                        <Boxes class="h-4 w-4" />
                        Buka Stok Produk Jadi
                    </Link>
                </div>

                <div
                    v-if="!materials.data.length"
                    class="px-5 py-16 text-center text-sm text-stone-500 dark:text-slate-400"
                >
                    Belum ada bahan baku yang cocok dengan filter saat ini.
                </div>

                <div v-else class="grid gap-4 px-5 py-5 xl:grid-cols-2">
                    <article
                        v-for="material in materials.data"
                        :key="material.id"
                        class="rounded-[24px] border border-stone-200 bg-white p-5 dark:border-slate-800/80 dark:bg-slate-950/60"
                    >
                        <div class="flex flex-col gap-4">
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="space-y-2">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h3
                                            class="text-lg font-black text-stone-900 dark:text-white"
                                        >
                                            {{ material.name }}
                                        </h3>
                                        <span
                                            :class="[
                                                'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]',
                                                getStockStateClass(material),
                                            ]"
                                        >
                                            {{ getStockStateLabel(material) }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        Unit {{ material.unit || 'gram' }} •
                                        {{
                                            material.track_expired
                                                ? 'Track expired aktif'
                                                : 'Tanpa tracking expired'
                                        }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="openEditModal(material)"
                                        class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-bold text-stone-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="openStockModal(material, 'add')"
                                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-xs font-bold text-emerald-300"
                                    >
                                        <Plus class="h-3.5 w-3.5" />
                                        Tambah Stok
                                    </button>
                                    <button
                                        type="button"
                                        @click="
                                            openStockModal(material, 'adjust')
                                        "
                                        class="inline-flex items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-xs font-bold text-amber-300"
                                    >
                                        <RefreshCw class="h-3.5 w-3.5" />
                                        Adjustment
                                    </button>
                                </div>
                            </div>

                            <div
                                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
                            >
                                <div
                                    class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Stok Saat Ini
                                    </p>
                                    <p
                                        class="mt-2 text-xl font-black text-stone-900 dark:text-white"
                                    >
                                        {{ material.quantity ?? 0 }}
                                        <span
                                            class="text-sm font-semibold text-stone-500 dark:text-slate-400"
                                        >
                                            {{ material.unit || 'gram' }}
                                        </span>
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Minimum
                                    </p>
                                    <p
                                        class="mt-2 text-xl font-black text-stone-900 dark:text-white"
                                    >
                                        {{ material.minimum_stock ?? 0 }}
                                        <span
                                            class="text-sm font-semibold text-stone-500 dark:text-slate-400"
                                        >
                                            {{ material.unit || 'gram' }}
                                        </span>
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Cost per Unit
                                    </p>
                                    <p
                                        class="mt-2 text-xl font-black text-stone-900 dark:text-white"
                                    >
                                        {{
                                            formatPrice(material.cost_per_unit)
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Restock Terakhir
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-semibold text-stone-900 dark:text-white"
                                    >
                                        {{
                                            formatDateTime(
                                                material.last_restocked_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-if="materials.links.length > 3"
                    class="flex flex-wrap items-center gap-2 border-t border-stone-200 px-5 py-4 dark:border-slate-800/80"
                >
                    <Link
                        v-for="link in materials.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'rounded-xl border px-3 py-2 text-xs font-semibold transition',
                            link.active
                                ? 'border-orange-500/30 bg-orange-500/10 text-orange-300'
                                : 'border-stone-200 bg-white text-stone-500 hover:text-white dark:border-slate-800 dark:bg-slate-950/70 dark:text-slate-400',
                            !link.url && 'pointer-events-none opacity-50',
                        ]"
                    >
                        <span v-html="link.label"></span>
                    </Link>
                </div>
            </section>
        </div>

        <div
            v-if="isMaterialModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-white px-4 backdrop-blur-sm dark:bg-slate-950/80"
            @click.self="closeMaterialModal"
        >
            <div
                class="w-full max-w-2xl rounded-[28px] border border-stone-200 bg-stone-100 p-5 shadow-[0_30px_120px_rgba(2,6,23,0.7)] dark:border-slate-800/80 dark:bg-slate-950"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            {{
                                editingMaterial
                                    ? 'Edit Bahan Baku'
                                    : 'Tambah Bahan Baku'
                            }}
                        </p>
                        <h3
                            class="mt-1 text-lg font-black text-stone-900 dark:text-white"
                        >
                            {{
                                editingMaterial
                                    ? editingMaterial.name
                                    : 'Bahan baku baru'
                            }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeMaterialModal"
                        class="rounded-xl border border-stone-200 bg-white p-2 text-stone-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="submitMaterial">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Nama Bahan
                            </label>
                            <input
                                v-model="materialForm.name"
                                type="text"
                                class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="materialForm.errors.name"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ materialForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Unit
                            </label>
                            <input
                                v-model="materialForm.unit"
                                type="text"
                                class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="materialForm.errors.unit"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ materialForm.errors.unit }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Stok Awal
                            </label>
                            <input
                                v-model="materialForm.quantity"
                                type="number"
                                min="0"
                                class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="materialForm.errors.quantity"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ materialForm.errors.quantity }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Minimum Stok
                            </label>
                            <input
                                v-model="materialForm.minimum_stock"
                                type="number"
                                min="0"
                                class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="materialForm.errors.minimum_stock"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ materialForm.errors.minimum_stock }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Cost per Unit
                            </label>
                            <input
                                v-model="materialForm.cost_per_unit"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="materialForm.errors.cost_per_unit"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ materialForm.errors.cost_per_unit }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Expired Action
                            </label>
                            <select
                                v-model="materialForm.expired_action"
                                :disabled="!materialForm.track_expired"
                                class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none disabled:opacity-50 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            >
                                <option value="notify_only">Notify only</option>
                                <option value="deactivate">Deactivate</option>
                            </select>
                            <p
                                v-if="materialForm.errors.expired_action"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ materialForm.errors.expired_action }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label
                            class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-800 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                        >
                            <input
                                v-model="materialForm.track_expired"
                                type="checkbox"
                                class="h-4 w-4 rounded border-stone-200 bg-stone-100 text-orange-500 focus:ring-orange-500 dark:border-slate-700 dark:bg-slate-950"
                            />
                            Aktifkan tracking expired
                        </label>

                        <label
                            class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-800 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                        >
                            <input
                                v-model="materialForm.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-stone-200 bg-stone-100 text-orange-500 focus:ring-orange-500 dark:border-slate-700 dark:bg-slate-950"
                            />
                            Bahan baku aktif
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeMaterialModal"
                            class="rounded-2xl border border-stone-200 px-4 py-3 text-sm font-semibold text-stone-600 dark:border-slate-800 dark:text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="materialForm.processing"
                            class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-stone-900 disabled:opacity-60 dark:text-white"
                        >
                            {{
                                materialForm.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Bahan'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="stockTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-white px-4 backdrop-blur-sm dark:bg-slate-950/80"
            @click.self="closeStockModal"
        >
            <div
                class="w-full max-w-xl rounded-[28px] border border-stone-200 bg-stone-100 p-5 shadow-[0_30px_120px_rgba(2,6,23,0.7)] dark:border-slate-800/80 dark:bg-slate-950"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            {{
                                stockMode === 'add'
                                    ? 'Tambah Stok'
                                    : 'Adjustment Stok'
                            }}
                        </p>
                        <h3
                            class="mt-1 text-lg font-black text-stone-900 dark:text-white"
                        >
                            {{ stockTarget.name }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeStockModal"
                        class="rounded-xl border border-stone-200 bg-white p-2 text-stone-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form
                    class="mt-5 space-y-4"
                    @submit.prevent="submitStockAction"
                >
                    <div
                        class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-xs text-stone-500 dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-400"
                    >
                        Stok saat ini:
                        <span
                            class="font-bold text-stone-900 dark:text-white"
                            >{{ stockTarget.quantity ?? 0 }}</span
                        >
                        {{ stockTarget.unit || 'gram' }}
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                        >
                            {{
                                stockMode === 'add'
                                    ? 'Jumlah Tambahan'
                                    : 'Stok Hasil Adjustment'
                            }}
                        </label>
                        <input
                            v-model="stockForm.quantity"
                            type="number"
                            min="0"
                            class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                        />
                        <p
                            v-if="stockForm.errors.quantity"
                            class="mt-1 text-xs text-rose-300"
                        >
                            {{ stockForm.errors.quantity }}
                        </p>
                    </div>

                    <div
                        v-if="needsExpiredStockDetails"
                        class="grid gap-4 rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-slate-800 dark:bg-slate-900/70 md:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Batch Code
                            </label>
                            <input
                                v-model="stockForm.batch_code"
                                type="text"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                            />
                            <p
                                v-if="stockForm.errors.batch_code"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ stockForm.errors.batch_code }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Tanggal Expired
                            </label>
                            <input
                                v-model="stockForm.expired_date"
                                type="date"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                            />
                            <p
                                class="mt-1 text-[11px] text-stone-400 dark:text-slate-500"
                            >
                                Wajib diisi saat tambah stok bahan baku dengan
                                tracking expired.
                            </p>
                            <p
                                v-if="stockForm.errors.expired_date"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ stockForm.errors.expired_date }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeStockModal"
                            class="rounded-2xl border border-stone-200 px-4 py-3 text-sm font-semibold text-stone-600 dark:border-slate-800 dark:text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="stockForm.processing"
                            class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-stone-900 disabled:opacity-60 dark:text-white"
                        >
                            {{
                                stockForm.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Perubahan'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
