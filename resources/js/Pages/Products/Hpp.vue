<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Calculator,
    Coins,
    CookingPot,
    PackageOpen,
    Plus,
    Search,
    Wallet,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface IngredientRow {
    id?: string;
    quantity?: number | string;
    raw_material_id?: string;
    raw_material?: {
        id: string;
        name: string;
        unit?: string | null;
        cost_per_unit?: number | string | null;
        quantity?: number;
        minimum_stock?: number;
    } | null;
}

interface ProductHppRow {
    id: string;
    name: string;
    description?: string | null;
    base_price?: number | string | null;
    hpp?: number | string | null;
    calculated_hpp?: number | string | null;
    gross_margin?: number | string | null;
    ingredients_count?: number;
    category?: {
        name?: string | null;
    } | null;
    ingredients?: IngredientRow[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface RawMaterialOption {
    id: string;
    name: string;
    unit?: string | null;
    cost_per_unit?: number | string | null;
    quantity?: number;
    minimum_stock?: number;
}

const props = defineProps<{
    products: {
        data: ProductHppRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total: number;
        with_recipe: number;
        synced_hpp: number;
        healthy_margin: number;
    };
    rawMaterials: RawMaterialOption[];
    filters: {
        search?: string;
        recipe_status?: string;
        per_page?: number;
    };
    success?: string | null;
}>();

const search = ref(props.filters.search || '');
const recipeStatus = ref(props.filters.recipe_status || '');
const selectedProduct = ref<ProductHppRow | null>(null);

const hppForm = useForm<{
    ingredients: Array<{
        raw_material_id: string;
        quantity: number;
    }>;
}>({
    ingredients: [],
});

const rawMaterialMap = computed(() => {
    return new Map(
        props.rawMaterials.map((material) => [material.id, material]),
    );
});

const summaryCards = computed(() => [
    {
        label: 'Total Produk',
        value: props.summary.total,
        tone: 'text-stone-900 dark:text-white',
        surface: 'border-stone-200 bg-white dark:border-white/10 dark:bg-white/[0.03]',
        icon: PackageOpen,
    },
    {
        label: 'Punya Resep',
        value: props.summary.with_recipe,
        tone: 'text-emerald-600 dark:text-emerald-300',
        surface: 'border-emerald-200 bg-emerald-50 dark:border-emerald-400/15 dark:bg-emerald-500/8',
        icon: CookingPot,
    },
    {
        label: 'HPP Sinkron',
        value: props.summary.synced_hpp,
        tone: 'text-sky-600 dark:text-sky-300',
        surface: 'border-sky-200 bg-sky-50 dark:border-sky-400/15 dark:bg-sky-500/8',
        icon: Calculator,
    },
    {
        label: 'Margin Positif',
        value: props.summary.healthy_margin,
        tone: 'text-amber-600 dark:text-amber-300',
        surface: 'border-amber-200 bg-amber-50 dark:border-amber-400/15 dark:bg-amber-500/8',
        icon: Coins,
    },
]);

const submitFilters = () => {
    router.get(
        route('products.hpp'),
        {
            search: search.value || undefined,
            recipe_status: recipeStatus.value || undefined,
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

const formatPrice = (value: unknown) => {
    const num = Number(value || 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
};

const formatNumber = (value: unknown) => {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
};

const openRecipeModal = (product: ProductHppRow) => {
    selectedProduct.value = product;
    hppForm.clearErrors();
    hppForm.ingredients = (product.ingredients || []).map((ingredient) => ({
        raw_material_id:
            ingredient.raw_material_id || ingredient.raw_material?.id || '',
        quantity: Number(ingredient.quantity || 0),
    }));

    if (!hppForm.ingredients.length) {
        hppForm.ingredients = [
            {
                raw_material_id: '',
                quantity: 1,
            },
        ];
    }
};

const closeRecipeModal = () => {
    selectedProduct.value = null;
    hppForm.clearErrors();
};

const addIngredientRow = () => {
    hppForm.ingredients.push({
        raw_material_id: '',
        quantity: 1,
    });
};

const removeIngredientRow = (index: number) => {
    hppForm.ingredients.splice(index, 1);

    if (!hppForm.ingredients.length) {
        addIngredientRow();
    }
};

const getRawMaterial = (rawMaterialId: string) => {
    return rawMaterialMap.value.get(rawMaterialId) || null;
};

const getRecipeEstimate = computed(() => {
    return hppForm.ingredients.reduce((total, ingredient) => {
        const rawMaterial = getRawMaterial(ingredient.raw_material_id);

        return (
            total +
            Number(ingredient.quantity || 0) *
                Number(rawMaterial?.cost_per_unit || 0)
        );
    }, 0);
});

const submitRecipe = () => {
    if (!selectedProduct.value) return;

    hppForm.put(
        route('products.ingredients.update', selectedProduct.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedProduct.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="HPP per Produk" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        HPP per Produk
                    </h2>
                    <p
                        class="mt-1 max-w-2xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Susun resep bahan baku per produk, hitung HPP otomatis
                        dari cost bahan, lalu pantau margin jual per item.
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
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Filter HPP
                        </p>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Cari produk lalu fokuskan pada item yang sudah punya
                            resep atau belum punya resep.
                        </p>
                    </div>
                    <form
                        class="grid w-full gap-2 lg:max-w-3xl lg:grid-cols-[minmax(0,1fr)_220px_110px]"
                        @submit.prevent="submitFilters"
                    >
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-slate-500"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama atau deskripsi produk..."
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
                            v-model="recipeStatus"
                            class="rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                        >
                            <option value="">Semua resep</option>
                            <option value="with_recipe">Sudah ada resep</option>
                            <option value="without_recipe">
                                Belum ada resep
                            </option>
                        </select>

                        <button
                            type="submit"
                            class="rounded-2xl bg-orange-500 hover:bg-orange-400 px-4 py-3 text-sm font-bold text-stone-950 transition"
                        >
                            Terapkan
                        </button>
                    </form>
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
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-600 dark:text-orange-300"
                        >
                            Daftar Produk & HPP
                        </p>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Menampilkan {{ products.from ?? 0 }}-{{
                                products.to ?? 0
                            }}
                            dari {{ products.total }} produk.
                        </p>
                    </div>
                    <Link
                        :href="route('raw-materials.index')"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-300 dark:hover:text-orange-200"
                    >
                        <Wallet class="h-4 w-4" />
                        Buka Inventori Bahan Baku
                    </Link>
                </div>

                <div
                    v-if="!products.data.length"
                    class="px-5 py-16 text-center text-sm text-stone-500 dark:text-slate-400"
                >
                    Belum ada produk yang cocok dengan filter HPP saat ini.
                </div>

                <div v-else class="grid gap-4 px-5 py-5 xl:grid-cols-2">
                    <article
                        v-for="product in products.data"
                        :key="product.id"
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
                                            {{ product.name }}
                                        </h3>
                                        <span
                                            class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-orange-600 dark:text-orange-300"
                                        >
                                            {{ product.ingredients_count || 0 }}
                                            bahan
                                        </span>
                                    </div>
                                    <p
                                        class="text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        {{
                                            product.category?.name ||
                                            'Tanpa kategori'
                                        }}
                                        <span v-if="product.description"
                                            >• {{ product.description }}</span
                                        >
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    @click="openRecipeModal(product)"
                                    class="inline-flex items-center gap-2 rounded-xl border border-orange-500/20 bg-orange-500/10 px-3 py-2 text-xs font-bold text-orange-600 dark:text-orange-300"
                                >
                                    <CookingPot class="h-3.5 w-3.5" />
                                    Atur Resep
                                </button>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div
                                    class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Harga Jual
                                    </p>
                                    <p
                                        class="mt-2 text-xl font-black text-stone-900 dark:text-white"
                                    >
                                        {{ formatPrice(product.base_price) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        HPP Tersimpan
                                    </p>
                                    <p
                                        class="mt-2 text-xl font-black text-stone-900 dark:text-white"
                                    >
                                        {{ formatPrice(product.hpp) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                    >
                                        Margin Kotor
                                    </p>
                                    <p
                                        :class="[
                                            'mt-2 text-xl font-black',
                                            Number(product.gross_margin || 0) >=
                                            0
                                                ? 'text-emerald-600 dark:text-emerald-300'
                                                : 'text-rose-600 dark:text-rose-300',
                                        ]"
                                    >
                                        {{ formatPrice(product.gross_margin) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/50"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                                >
                                    Ringkasan Resep
                                </p>
                                <div
                                    v-if="product.ingredients?.length"
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    <span
                                        v-for="ingredient in product.ingredients"
                                        :key="ingredient.id"
                                        class="rounded-full border border-stone-200 bg-stone-100 px-3 py-1 text-[11px] font-medium text-stone-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300"
                                    >
                                        {{ ingredient.raw_material?.name }} •
                                        {{ formatNumber(ingredient.quantity) }}
                                        {{
                                            ingredient.raw_material?.unit ||
                                            'unit'
                                        }}
                                    </span>
                                </div>
                                <p
                                    v-else
                                    class="mt-2 text-xs text-stone-500 dark:text-slate-400"
                                >
                                    Produk ini belum punya resep bahan baku.
                                </p>
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-if="products.links.length > 3"
                    class="flex flex-wrap items-center gap-2 border-t border-stone-200 px-5 py-4 dark:border-slate-800/80"
                >
                    <Link
                        v-for="link in products.links"
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
            v-if="selectedProduct"
            class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 px-4 backdrop-blur-sm dark:bg-slate-950/80"
            @click.self="closeRecipeModal"
        >
            <div
                class="w-full max-w-4xl rounded-[28px] border border-stone-200 bg-stone-100 p-5 shadow-[0_30px_120px_rgba(2,6,23,0.7)] dark:border-slate-800/80 dark:bg-slate-950"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                        >
                            Atur Resep & HPP
                        </p>
                        <h3
                            class="mt-1 text-lg font-black text-stone-900 dark:text-white"
                        >
                            {{ selectedProduct.name }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Harga jual
                            {{ formatPrice(selectedProduct.base_price) }} • HPP
                            estimasi {{ formatPrice(getRecipeEstimate) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeRecipeModal"
                        class="rounded-xl border border-stone-200 bg-white p-2 text-stone-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="submitRecipe">
                    <div
                        v-for="(ingredient, index) in hppForm.ingredients"
                        :key="index"
                        class="grid gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-slate-800 dark:bg-slate-900/70 lg:grid-cols-[minmax(0,1fr)_180px_120px]"
                    >
                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Bahan Baku
                            </label>
                            <select
                                v-model="ingredient.raw_material_id"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                            >
                                <option value="">Pilih bahan baku</option>
                                <option
                                    v-for="material in rawMaterials"
                                    :key="material.id"
                                    :value="material.id"
                                >
                                    {{ material.name }} •
                                    {{ formatPrice(material.cost_per_unit) }}/{{
                                        material.unit
                                    }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300"
                            >
                                Qty Pakai
                            </label>
                            <input
                                v-model="ingredient.quantity"
                                type="number"
                                min="0.01"
                                step="0.01"
                                class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                            />
                            <p
                                v-if="
                                    getRawMaterial(ingredient.raw_material_id)
                                "
                                class="mt-1 text-[11px] text-stone-400 dark:text-slate-500"
                            >
                                Sisa stok:
                                {{
                                    formatNumber(
                                        getRawMaterial(
                                            ingredient.raw_material_id,
                                        )?.quantity,
                                    )
                                }}
                                {{
                                    getRawMaterial(ingredient.raw_material_id)
                                        ?.unit
                                }}
                            </p>
                        </div>

                        <div class="flex items-end">
                            <button
                                type="button"
                                @click="removeIngredientRow(index)"
                                class="w-full rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm font-bold text-rose-300"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="hppForm.errors.ingredients"
                        class="text-sm text-rose-300"
                    >
                        {{ hppForm.errors.ingredients }}
                    </p>

                    <div
                        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/50"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500"
                            >
                                Estimasi HPP Otomatis
                            </p>
                            <p
                                class="mt-1 text-2xl font-black text-emerald-300"
                            >
                                {{ formatPrice(getRecipeEstimate) }}
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="addIngredientRow"
                            class="inline-flex items-center gap-2 rounded-2xl border border-sky-500/20 bg-sky-500/10 px-4 py-3 text-sm font-bold text-sky-300"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Baris
                        </button>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeRecipeModal"
                            class="rounded-2xl border border-stone-200 px-4 py-3 text-sm font-semibold text-stone-600 dark:border-slate-800 dark:text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="hppForm.processing"
                            class="rounded-2xl bg-orange-500 hover:bg-orange-400 px-4 py-3 text-sm font-bold text-stone-950 transition disabled:opacity-60"
                        >
                            {{
                                hppForm.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Resep'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
