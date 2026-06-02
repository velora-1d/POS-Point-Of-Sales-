<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Boxes,
    Layers3,
    Search,
    Tags,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface ProductRow {
    id: string;
    name: string;
    description?: string | null;
    image_url?: string | null;
    base_price?: number | string | null;
    hpp?: number | string | null;
    is_available?: boolean;
    is_active?: boolean;
    track_stock?: boolean;
    track_expired?: boolean;
    category?: {
        id: string;
        name: string;
    } | null;
    variants?: Array<{
        id: string;
        name: string;
        additional_price?: number | string | null;
    }>;
    prices?: Array<{
        id: string;
        tier?: string | null;
        tier_label?: string | null;
        price?: number | string | null;
    }>;
    active_variants_count?: number;
    active_prices_count?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    products: {
        data: ProductRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total: number;
        available: number;
        withVariants: number;
        withMultiPrice: number;
    };
    categories: Array<{
        id: string;
        name: string;
    }>;
    filters: {
        search?: string;
        category_id?: string | null;
        per_page?: number;
    };
}>();

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');

const summaryCards = computed(() => [
    {
        label: 'Total Produk',
        value: props.summary.total,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: Boxes,
    },
    {
        label: 'Siap Dijual',
        value: props.summary.available,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/8',
        icon: BadgeCheck,
    },
    {
        label: 'Punya Varian',
        value: props.summary.withVariants,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/8',
        icon: Layers3,
    },
    {
        label: 'Multi Harga',
        value: props.summary.withMultiPrice,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
        icon: Tags,
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

const submitFilters = () => {
    router.get(
        route('products.index'),
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
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
</script>

<template>
    <Head title="Katalog Produk" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Katalog Produk & Varian
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-slate-400">
                        Pantau produk aktif, kategori, varian, dan multi harga
                        outlet dari satu halaman katalog internal.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-5">
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
                        </div>
                        <component :is="stat.icon" class="h-5 w-5 text-slate-500" />
                    </div>
                </article>
            </section>

            <section
                class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Filter Produk
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Cari produk berdasarkan nama/deskripsi atau sempitkan per kategori.
                        </p>
                    </div>
                    <form
                        class="grid w-full gap-2 lg:max-w-3xl lg:grid-cols-[minmax(0,1fr)_220px_110px]"
                        @submit.prevent="submitFilters"
                    >
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama atau deskripsi produk..."
                                class="w-full rounded-2xl border border-slate-800 bg-slate-950 py-3 pl-10 pr-10 text-sm text-white placeholder:text-slate-500 focus:border-orange-500 focus:outline-none"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="clearSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 transition hover:text-white"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <select
                            v-model="categoryId"
                            class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-white focus:border-orange-500 focus:outline-none"
                        >
                            <option value="">Semua kategori</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>

                        <button
                            type="submit"
                            class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-white"
                        >
                            Terapkan
                        </button>
                    </form>
                </div>
            </section>

            <section
                class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 shadow-xl shadow-slate-950/15"
            >
                <div class="flex flex-col gap-2 border-b border-slate-800/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Daftar Produk
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Menampilkan {{ products.from ?? 0 }}-{{ products.to ?? 0 }}
                            dari {{ products.total }} produk.
                        </p>
                    </div>
                    <div class="rounded-full border border-slate-800 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold text-slate-400">
                        Data katalog outlet aktif
                    </div>
                </div>

                <div v-if="products.data.length === 0" class="px-5 py-10">
                    <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-950/40 px-4 py-8 text-center text-sm text-slate-500">
                        Belum ada produk yang cocok dengan filter saat ini.
                    </div>
                </div>

                <div v-else class="grid gap-4 p-5 lg:grid-cols-2">
                    <article
                        v-for="product in products.data"
                        :key="product.id"
                        class="rounded-[24px] border border-slate-800 bg-slate-950/70 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-lg font-black text-white">
                                        {{ product.name }}
                                    </h3>
                                    <span
                                        class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-orange-300"
                                    >
                                        {{ product.category?.name || 'Tanpa kategori' }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs leading-relaxed text-slate-400">
                                    {{ product.description || 'Belum ada deskripsi produk.' }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'rounded-full border px-2 py-1 text-[10px] font-bold uppercase tracking-wider',
                                    product.is_available && product.is_active
                                        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                        : 'border-rose-500/20 bg-rose-500/10 text-rose-300',
                                ]"
                            >
                                {{ product.is_available && product.is_active ? 'aktif' : 'off' }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Harga Dasar
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    {{ formatPrice(product.base_price) }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    HPP
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    {{ formatPrice(product.hpp) }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Status Teknis
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    {{ product.track_stock ? 'Track stock' : 'No stock' }}
                                </p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    {{ product.track_expired ? 'Track expired' : 'No expired' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-white">
                                        Varian Produk
                                    </p>
                                    <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-sky-300">
                                        {{ product.active_variants_count || 0 }}
                                    </span>
                                </div>
                                <div v-if="product.variants && product.variants.length > 0" class="mt-3 space-y-2">
                                    <div
                                        v-for="variant in product.variants"
                                        :key="variant.id"
                                        class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-[11px]"
                                    >
                                        <span class="font-semibold text-slate-200">
                                            {{ variant.name }}
                                        </span>
                                        <span class="font-bold text-sky-300">
                                            +{{ formatPrice(variant.additional_price) }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="mt-3 text-[11px] text-slate-500">
                                    Produk ini belum punya varian aktif.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-white">
                                        Multi Harga
                                    </p>
                                    <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-300">
                                        {{ product.active_prices_count || 0 }}
                                    </span>
                                </div>
                                <div v-if="product.prices && product.prices.length > 0" class="mt-3 space-y-2">
                                    <div
                                        v-for="price in product.prices"
                                        :key="price.id"
                                        class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-[11px]"
                                    >
                                        <div>
                                            <p class="font-semibold text-slate-200">
                                                {{ price.tier_label || price.tier || 'Harga' }}
                                            </p>
                                        </div>
                                        <span class="font-bold text-amber-300">
                                            {{ formatPrice(price.price) }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="mt-3 text-[11px] text-slate-500">
                                    Produk ini baru memakai satu harga dasar.
                                </p>
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-if="products.links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-slate-800/80 px-5 py-4"
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
                                    : 'border-slate-800 bg-slate-950 text-slate-300 hover:border-slate-700',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded-xl border border-slate-900 bg-slate-950/50 px-3 py-2 text-xs font-bold text-slate-600"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
