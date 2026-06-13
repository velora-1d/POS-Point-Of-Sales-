<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    Boxes,
    PackageCheck,
    Search,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface ProductStockRow {
    id: string;
    name: string;
    description?: string | null;
    track_stock?: boolean;
    track_expired?: boolean;
    expired_action?: string | null;
    category?: {
        name?: string | null;
    } | null;
    stock?: {
        current_stock?: number;
        minimum_stock?: number;
        unit?: string | null;
        last_restocked_at?: string | null;
    } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    products: {
        data: ProductStockRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        tracked: number;
        healthy: number;
        low: number;
        out: number;
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
const selectedProduct = ref<ProductStockRow | null>(null);

const stockForm = useForm({
    current_stock: 0,
    minimum_stock: 5,
    unit: 'pcs',
    batch_code: '',
    expired_date: '',
});

const needsExpiredDetails = computed(() => {
    if (!selectedProduct.value?.track_expired) {
        return false;
    }

    return (
        Number(stockForm.current_stock || 0) >
        Number(selectedProduct.value.stock?.current_stock || 0)
    );
});

const summaryCards = computed(() => [
    {
        label: 'Produk Tracked',
        value: props.summary.tracked,
        tone: 'text-stone-900 dark:text-white',
        surface: 'border-stone-200 bg-white dark:border-white/10 dark:bg-white/[0.03]',
        icon: Boxes,
    },
    {
        label: 'Stok Sehat',
        value: props.summary.healthy,
        tone: 'text-emerald-600 dark:text-emerald-300',
        surface: 'border-emerald-200 bg-emerald-50 dark:border-emerald-400/15 dark:bg-emerald-500/8',
        icon: PackageCheck,
    },
    {
        label: 'Stok Menipis',
        value: props.summary.low,
        tone: 'text-amber-600 dark:text-amber-300',
        surface: 'border-amber-200 bg-amber-50 dark:border-amber-400/15 dark:bg-amber-500/8',
        icon: AlertTriangle,
    },
    {
        label: 'Stok Habis',
        value: props.summary.out,
        tone: 'text-rose-600 dark:text-rose-300',
        surface: 'border-rose-200 bg-rose-50 dark:border-rose-400/15 dark:bg-rose-500/8',
        icon: Archive,
    },
]);

const submitFilters = () => {
    router.get(
        route('products.stock'),
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

const getStockStateLabel = (product: ProductStockRow) => {
    const current = Number(product.stock?.current_stock || 0);
    const minimum = Number(product.stock?.minimum_stock || 0);

    if (current <= 0) return 'Habis';
    if (current <= minimum) return 'Menipis';
    return 'Sehat';
};

const getStockStateClass = (product: ProductStockRow) => {
    const current = Number(product.stock?.current_stock || 0);
    const minimum = Number(product.stock?.minimum_stock || 0);

    if (current <= 0) {
        return 'border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300';
    }

    if (current <= minimum) {
        return 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300';
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300';
};

const openStockModal = (product: ProductStockRow) => {
    selectedProduct.value = product;
    stockForm.clearErrors();
    stockForm.current_stock = Number(product.stock?.current_stock || 0);
    stockForm.minimum_stock = Number(product.stock?.minimum_stock || 5);
    stockForm.unit = product.stock?.unit || 'pcs';
    stockForm.batch_code = '';
    stockForm.expired_date = '';
};

const closeStockModal = () => {
    selectedProduct.value = null;
    stockForm.clearErrors();
};

const submitStockUpdate = () => {
    if (!selectedProduct.value) return;

    stockForm.patch(route('products.stock.update', selectedProduct.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            selectedProduct.value = null;
        },
    });
};
</script>

<template>
    <Head title="Manajemen Stok Produk Jadi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Manajemen Stok Produk Jadi
                    </h2>
                    <p
                        class="mt-1 max-w-2xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Pantau stok produk yang memakai tracking stock, lihat
                        status sehat/menipis/habis, lalu update stok manual dari
                        dashboard outlet.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <!-- Tab Navigation Global -->
            <div
                class="flex w-full gap-1 rounded-2xl border-b border-stone-200 bg-stone-50 p-1 dark:border-slate-800 dark:bg-slate-900/40"
            >
                <Link
                    :href="route('products.stock')"
                    class="flex-1 rounded-xl py-2 text-center text-[10px] font-bold uppercase tracking-wider transition duration-150 sm:text-xs"
                    :class="
                        route().current('products.stock')
                            ? 'bg-orange-400 text-black border-2 border-black font-extrabold cursor-pointer'
                            : 'text-stone-500 hover:bg-stone-200/50 hover:text-stone-800 dark:text-slate-400 dark:hover:bg-slate-800/40 dark:hover:text-slate-200 border-2 border-transparent cursor-pointer'
                    "
                >
                    Stok Produk Jadi
                </Link>
                <Link
                    :href="route('products.hpp')"
                    class="flex-1 rounded-xl py-2 text-center text-[10px] font-bold uppercase tracking-wider transition duration-150 sm:text-xs"
                    :class="
                        route().current('products.hpp')
                            ? 'bg-orange-400 text-black border-2 border-black font-extrabold cursor-pointer'
                            : 'text-stone-500 hover:bg-stone-200/50 hover:text-stone-800 dark:text-slate-400 dark:hover:bg-slate-800/40 dark:hover:text-slate-200 border-2 border-transparent cursor-pointer'
                    "
                >
                    HPP & Resep
                </Link>
                <Link
                    :href="route('stock-alerts.index')"
                    class="flex-1 rounded-xl py-2 text-center text-[10px] font-bold uppercase tracking-wider transition duration-150 sm:text-xs"
                    :class="
                        route().current('stock-alerts.index')
                            ? 'bg-orange-400 text-black border-2 border-black font-extrabold cursor-pointer'
                            : 'text-stone-500 hover:bg-stone-200/50 hover:text-stone-800 dark:text-slate-400 dark:hover:bg-slate-800/40 dark:hover:text-slate-200 border-2 border-transparent cursor-pointer'
                    "
                >
                    Alert Stok Menipis
                </Link>
                <Link
                    :href="route('expired-tracking.index')"
                    class="flex-1 rounded-xl py-2 text-center text-[10px] font-bold uppercase tracking-wider transition duration-150 sm:text-xs"
                    :class="
                        route().current('expired-tracking.index')
                            ? 'bg-orange-400 text-black border-2 border-black font-extrabold cursor-pointer'
                            : 'text-stone-500 hover:bg-stone-200/50 hover:text-stone-800 dark:text-slate-400 dark:hover:bg-slate-800/40 dark:hover:text-slate-200 border-2 border-transparent cursor-pointer'
                    "
                >
                    Reminder Expired
                </Link>
            </div>

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
                <form
                    class="grid gap-2 lg:grid-cols-[minmax(0,1fr)_220px_110px]"
                    @submit.prevent="submitFilters"
                >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-slate-500"
                        />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari produk tracked stock..."
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
                        <option value="">Semua status stok</option>
                        <option value="healthy">Stok sehat</option>
                        <option value="low">Stok menipis</option>
                        <option value="out">Stok habis</option>
                    </select>

                    <button
                        type="submit"
                        class="rounded-2xl bg-orange-500 hover:bg-orange-400 px-4 py-3 text-sm font-bold text-stone-950 transition"
                    >
                        Terapkan
                    </button>
                </form>
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
                            Daftar Stok Produk
                        </p>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Menampilkan {{ products.from ?? 0 }}-{{
                                products.to ?? 0
                            }}
                            dari {{ products.total }} produk tracked stock.
                        </p>
                    </div>
                    <Link
                        :href="route('products.index')"
                        class="rounded-full border border-stone-200 bg-white px-3 py-1 text-[11px] font-semibold text-stone-500 transition hover:border-stone-200 hover:text-stone-900 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-950/70 dark:text-slate-400 dark:text-white"
                    >
                        Buka Katalog Produk
                    </Link>
                </div>

                <div v-if="products.data.length === 0" class="px-5 py-10">
                    <div
                        class="rounded-2xl border border-dashed border-stone-200 bg-white px-4 py-8 text-center text-sm text-stone-400 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-500"
                    >
                        Belum ada produk tracked stock yang cocok dengan filter
                        saat ini.
                    </div>
                </div>

                <div v-else class="grid gap-4 p-5 lg:grid-cols-2">
                    <article
                        v-for="product in products.data"
                        :key="product.id"
                        class="rounded-[24px] border border-stone-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/70"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="truncate text-lg font-black text-stone-900 dark:text-white"
                                    >
                                        {{ product.name }}
                                    </h3>
                                    <span
                                        class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-300"
                                    >
                                        {{
                                            product.category?.name ||
                                            'Tanpa kategori'
                                        }}
                                    </span>
                                </div>
                                <p
                                    class="mt-2 text-xs text-stone-500 dark:text-slate-400"
                                >
                                    {{
                                        product.description ||
                                        'Produk tracked stock tanpa deskripsi.'
                                    }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'rounded-full border px-2 py-1 text-[10px] font-bold uppercase tracking-wider',
                                    getStockStateClass(product),
                                ]"
                            >
                                {{ getStockStateLabel(product) }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Stok Saat Ini
                                </p>
                                <p
                                    class="mt-2 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{ product.stock?.current_stock ?? 0 }}
                                    {{ product.stock?.unit || 'pcs' }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Minimum Stok
                                </p>
                                <p
                                    class="mt-2 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{ product.stock?.minimum_stock ?? 0 }}
                                    {{ product.stock?.unit || 'pcs' }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500"
                                >
                                    Restock Terakhir
                                </p>
                                <p
                                    class="mt-2 text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{
                                        formatDateTime(
                                            product.stock?.last_restocked_at,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="openStockModal(product)"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-orange-500 hover:bg-orange-400 px-4 py-3 text-sm font-bold text-stone-950 transition"
                        >
                            Update Stok
                        </button>
                    </article>
                </div>

                <div
                    v-if="products.links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-stone-200 px-5 py-4 dark:border-slate-800/80"
                >
                    <template
                        v-for="link in products.links"
                        :key="`${link.label}-${link.url}`"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            :class="[
                                'rounded-xl border px-3 py-2 text-xs font-bold transition',
                                link.active
                                    ? 'border-orange-500/20 bg-orange-500/10 text-orange-300'
                                    : 'border-stone-200 bg-stone-100 text-stone-600 hover:border-stone-200 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300',
                            ]"
                        >
                            <span v-html="link.label"></span>
                        </Link>
                        <span
                            v-else
                            class="rounded-xl border border-slate-900 bg-white px-3 py-2 text-xs font-bold text-slate-600 dark:bg-slate-950/50"
                        >
                            <span v-html="link.label"></span>
                        </span>
                    </template>
                </div>
            </section>
        </div>

        <div
            v-if="selectedProduct"
            class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 px-4 py-6 backdrop-blur-sm dark:bg-slate-950/85"
        >
            <div
                class="w-full max-w-xl rounded-[28px] border border-stone-200 bg-stone-100 p-5 shadow-[0_30px_120px_rgba(2,6,23,0.7)] dark:border-slate-800/80 dark:bg-slate-950"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.22em] text-orange-300"
                        >
                            <Boxes class="h-3.5 w-3.5" />
                            Update Stok Produk
                        </div>
                        <h3
                            class="mt-3 text-2xl font-black text-stone-900 dark:text-white"
                        >
                            {{ selectedProduct.name }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Ubah stok saat ini, minimum stok, satuan, dan input
                            tanggal expired jika ada stok masuk baru.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeStockModal"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-600 transition hover:border-stone-200 hover:text-stone-900 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:text-white"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form
                    class="mt-5 space-y-4"
                    @submit.prevent="submitStockUpdate"
                >
                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="block">
                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                Stok Saat Ini
                            </span>
                            <input
                                v-model="stockForm.current_stock"
                                type="number"
                                min="0"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="stockForm.errors.current_stock"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ stockForm.errors.current_stock }}
                            </p>
                        </label>

                        <label class="block">
                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                Minimum Stok
                            </span>
                            <input
                                v-model="stockForm.minimum_stock"
                                type="number"
                                min="0"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="stockForm.errors.minimum_stock"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ stockForm.errors.minimum_stock }}
                            </p>
                        </label>

                        <label class="block">
                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                Satuan
                            </span>
                            <input
                                v-model="stockForm.unit"
                                type="text"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            />
                            <p
                                v-if="stockForm.errors.unit"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ stockForm.errors.unit }}
                            </p>
                        </label>
                    </div>

                    <div
                        v-if="selectedProduct?.track_expired"
                        class="grid gap-4 rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-slate-800 dark:bg-slate-900/60 md:grid-cols-2"
                    >
                        <label class="block">
                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                Batch Code
                            </span>
                            <input
                                v-model="stockForm.batch_code"
                                type="text"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                            />
                            <p
                                v-if="stockForm.errors.batch_code"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ stockForm.errors.batch_code }}
                            </p>
                        </label>

                        <label class="block">
                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                Tanggal Expired
                            </span>
                            <input
                                v-model="stockForm.expired_date"
                                type="date"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                            />
                            <p
                                class="mt-1 text-[11px] text-stone-400 dark:text-slate-500"
                            >
                                {{
                                    needsExpiredDetails
                                        ? 'Wajib diisi karena stok produk ini bertambah.'
                                        : 'Opsional jika tidak ada stok masuk baru.'
                                }}
                            </p>
                            <p
                                v-if="stockForm.errors.expired_date"
                                class="mt-1 text-xs text-rose-300"
                            >
                                {{ stockForm.errors.expired_date }}
                            </p>
                        </label>
                    </div>

                    <div
                        class="flex flex-col gap-2 pt-2 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            @click="closeStockModal"
                            class="inline-flex items-center justify-center rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-bold text-stone-800 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                        >
                            Tutup
                        </button>
                        <button
                            type="submit"
                            :disabled="stockForm.processing"
                            class="inline-flex items-center justify-center rounded-2xl bg-orange-500 hover:bg-orange-400 px-4 py-3 text-sm font-bold text-stone-950 transition disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
