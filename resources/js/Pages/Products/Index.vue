<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Boxes,
    Layers3,
    Pencil,
    Plus,
    Search,
    Tags,
    Trash2,
    X,
    Clock,
    AlertTriangle,
    FlaskConical,
    Camera,
    Image as ImageIcon,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface VariantFormItem {
    id?: string;
    name: string;
    additional_price: number;
    is_active: boolean;
}

interface PriceFormItem {
    id?: string;
    tier: string;
    tier_label?: string;
    price: number;
    happy_hour_start?: string;
    happy_hour_end?: string;
    is_active: boolean;
}

interface IngredientFormItem {
    raw_material_id: string;
    quantity: number;
}

interface RawMaterial {
    id: string;
    name: string;
    unit: string;
    cost_per_unit: number | string;
}

interface ProductRow {
    id: string;
    name: string;
    description?: string | null;
    image_url?: string | null;
    base_price: number | string;
    hpp: number | string;
    is_available: boolean;
    is_active: boolean;
    track_stock: boolean;
    track_expired: boolean;
    expired_action: string | null;
    expired_reminder_days: number[] | null;
    sort_order: number;
    category?: {
        id: string;
        name: string;
    } | null;
    category_id?: string;
    variants?: VariantFormItem[];
    prices?: PriceFormItem[];
    ingredients?: Array<{
        raw_material_id: string;
        quantity: number | string;
        raw_material?: RawMaterial;
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
    rawMaterials: RawMaterial[];
    priceTiers: Array<{
        id: string;
        name: string;
    }>;
    filters: {
        search?: string;
        category_id?: string | null;
        per_page?: number;
    };
    success?: string | null;
}>();

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');
const isProductModalOpen = ref(false);
const editingProduct = ref<ProductRow | null>(null);
const activeTab = ref<'basic' | 'variants' | 'prices' | 'recipe'>('basic');
const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const productForm = useForm({
    category_id: '',
    name: '',
    description: '',
    image: null as File | null,
    base_price: 0,
    hpp: 0,
    is_available: true,
    is_active: true,
    track_stock: false,
    track_expired: false,
    expired_action: 'notify_only',
    expired_reminder_days: [7, 3, 1],
    sort_order: 0,
    variants: [] as VariantFormItem[],
    prices: [] as PriceFormItem[],
    ingredients: [] as IngredientFormItem[],
});

const summaryCards = computed(() => [
    {
        label: 'Total Produk',
        value: props.summary.total,
        tone: 'text-white',
        surface: 'border-stone-200 dark:border-white/10 bg-white/[0.03]',
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

const calculatedHppFromRecipe = computed(() => {
    let total = 0;
    productForm.ingredients.forEach(item => {
        const material = props.rawMaterials.find(m => m.id === item.raw_material_id);
        if (material) {
            total += Number(material.cost_per_unit || 0) * Number(item.quantity || 0);
        }
    });
    return total;
});

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

const openCreateModal = () => {
    isProductModalOpen.value = true;
    editingProduct.value = null;
    activeTab.value = 'basic';
    imagePreview.value = null;
    productForm.clearErrors();
    productForm.reset();
    productForm.variants = [];
    productForm.prices = [];
    productForm.ingredients = [];
    if (props.categories.length > 0) {
        productForm.category_id = props.categories[0].id;
    }
};

const openEditModal = (product: ProductRow) => {
    isProductModalOpen.value = true;
    editingProduct.value = product;
    activeTab.value = 'basic';
    imagePreview.value = (product.image_url as string) || null;
    productForm.clearErrors();
    productForm.category_id = product.category_id || product.category?.id || '';
    productForm.name = product.name;
    productForm.description = product.description || '';
    productForm.image = null;
    productForm.base_price = Number(product.base_price || 0);
    productForm.hpp = Number(product.hpp || 0);
    productForm.is_available = Boolean(product.is_available);
    productForm.is_active = Boolean(product.is_active);
    productForm.track_stock = Boolean(product.track_stock);
    productForm.track_expired = Boolean(product.track_expired);
    productForm.expired_action = product.expired_action || 'notify_only';
    productForm.expired_reminder_days = product.expired_reminder_days || [7, 3, 1];
    productForm.sort_order = product.sort_order || 0;

    // Map Variants
    productForm.variants = (product.variants || []).map(v => ({
        id: v.id,
        name: v.name,
        additional_price: Number(v.additional_price || 0),
        is_active: Boolean(v.is_active)
    }));

    // Map Prices
    productForm.prices = (product.prices || []).map(p => ({
        id: p.id,
        tier: p.tier,
        tier_label: p.tier_label || '',
        price: Number(p.price || 0),
        happy_hour_start: p.happy_hour_start || '',
        happy_hour_end: p.happy_hour_end || '',
        is_active: Boolean(p.is_active)
    }));

    // Map Ingredients
    productForm.ingredients = (product.ingredients || []).map(i => ({
        raw_material_id: i.raw_material_id,
        quantity: Number(i.quantity || 0)
    }));
};

const closeProductModal = () => {
    isProductModalOpen.value = false;
    editingProduct.value = null;
    imagePreview.value = null;
    productForm.clearErrors();
};

const onFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        productForm.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const openFilePicker = () => {
    fileInput.value?.click();
};

const addVariantRow = () => {
    productForm.variants.push({
        name: '',
        additional_price: 0,
        is_active: true
    });
};

const removeVariantRow = (index: number) => {
    productForm.variants.splice(index, 1);
};

const addPriceRow = () => {
    productForm.prices.push({
        tier: 'member',
        tier_label: '',
        price: Number(productForm.base_price),
        is_active: true
    });
};

const removePriceRow = (index: number) => {
    productForm.prices.splice(index, 1);
};

const addIngredientRow = () => {
    if (props.rawMaterials.length > 0) {
        productForm.ingredients.push({
            raw_material_id: props.rawMaterials[0].id,
            quantity: 1
        });
    } else {
        alert('Anda belum memiliki data bahan baku aktif. Silakan tambah bahan baku di menu Inventori terlebih dahulu.');
    }
};

const removeIngredientRow = (index: number) => {
    productForm.ingredients.splice(index, 1);
};

const applyCalculatedHpp = () => {
    productForm.hpp = calculatedHppFromRecipe.value;
};

const submitProduct = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            isProductModalOpen.value = false;
            editingProduct.value = null;
            imagePreview.value = null;
        },
    };

    if (editingProduct.value) {
        productForm
            .transform((data) => ({
                ...data,
                _method: 'patch',
            }))
            .post(route('products.update', editingProduct.value.id), options);
        return;
    }

    productForm.post(route('products.store'), options);
};

const deleteProduct = (product: ProductRow) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus produk "${product.name}"?`)) {
        return;
    }

    router.delete(route('products.destroy', product.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Katalog Produk" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-stone-900 dark:text-white">
                        Katalog Produk & Varian
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-stone-500 dark:text-slate-400">
                        Pantau produk aktif, kategori, varian, dan multi harga
                        outlet dari satu halaman katalog internal.
                    </p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-stone-900 dark:text-white shadow-lg shadow-orange-500/20 transition hover:from-orange-600 hover:to-red-600"
                >
                    <Plus class="h-4 w-4" />
                    Tambah Produk
                </button>
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
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-500">
                                {{ stat.label }}
                            </p>
                            <p :class="['mt-2 text-3xl font-black', stat.tone]">
                                {{ stat.value }}
                            </p>
                        </div>
                        <component :is="stat.icon" class="h-5 w-5 text-stone-400 dark:text-slate-500" />
                    </div>
                </article>
            </section>

            <section
                class="rounded-[26px] border border-stone-200 dark:border-slate-800/80 bg-stone-50 dark:bg-slate-900/92 p-4 shadow-xl shadow-slate-950/15"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Filter Produk
                        </p>
                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                            Cari produk berdasarkan nama/deskripsi atau sempitkan per kategori.
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
                                class="w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 py-3 pl-10 pr-10 text-sm text-stone-900 dark:text-white placeholder:text-stone-400 dark:text-slate-500 focus:border-orange-500 focus:outline-none"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="clearSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 dark:text-slate-500 transition hover:text-stone-900 dark:text-white"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <select
                            v-model="categoryId"
                            class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
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
                            class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 text-sm font-bold text-stone-900 dark:text-white"
                        >
                            Terapkan
                        </button>
                    </form>
                </div>
            </section>

            <section
                class="rounded-[26px] border border-stone-200 dark:border-slate-800/80 bg-stone-50 dark:bg-slate-900/92 shadow-xl shadow-slate-950/15"
            >
                <div class="flex flex-col gap-2 border-b border-stone-200 dark:border-slate-800/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Daftar Produk
                        </p>
                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                            Menampilkan {{ products.from ?? 0 }}-{{ products.to ?? 0 }}
                            dari {{ products.total }} produk.
                        </p>
                    </div>
                    <div class="rounded-full border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/70 px-3 py-1 text-[11px] font-semibold text-stone-500 dark:text-slate-400">
                        Data katalog outlet aktif
                    </div>
                </div>

                <div v-if="products.data.length === 0" class="px-5 py-10">
                    <div class="rounded-2xl border border-dashed border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/40 px-4 py-8 text-center text-sm text-stone-400 dark:text-slate-500">
                        Belum ada produk yang cocok dengan filter saat ini.
                    </div>
                </div>

                <div v-else class="grid gap-4 p-5 lg:grid-cols-2">
                    <article
                        v-for="product in products.data"
                        :key="product.id"
                        class="rounded-[24px] border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/70 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex gap-4 min-w-0">
                                <div class="relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                                    <img
                                        v-if="product.image_url"
                                        :src="product.image_url"
                                        class="h-full w-full object-cover"
                                        :alt="product.name"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center text-slate-700">
                                        <ImageIcon class="h-8 w-8" />
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-lg font-black text-stone-900 dark:text-white">
                                            {{ product.name }}
                                        </h3>
                                        <span
                                            class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-orange-300"
                                        >
                                            {{ product.category?.name || 'Tanpa kategori' }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs leading-relaxed text-stone-500 dark:text-slate-400 line-clamp-2">
                                        {{ product.description || 'Belum ada deskripsi produk.' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
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
                                <div class="flex gap-1">
                                    <button
                                        @click="openEditModal(product)"
                                        class="rounded-lg border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 text-stone-500 dark:text-slate-400 transition hover:border-sky-500/50 hover:text-sky-400"
                                        title="Edit Produk"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        @click="deleteProduct(product)"
                                        class="rounded-lg border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 text-stone-500 dark:text-slate-400 transition hover:border-rose-500/50 hover:text-rose-400"
                                        title="Hapus Produk"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500">
                                    Harga Dasar
                                </p>
                                <p class="mt-2 text-sm font-bold text-stone-900 dark:text-white">
                                    {{ formatPrice(product.base_price) }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500">
                                    HPP
                                </p>
                                <p class="mt-2 text-sm font-bold text-stone-900 dark:text-white">
                                    {{ formatPrice(product.hpp) }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 px-3 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-stone-400 dark:text-slate-500">
                                    Status Teknis
                                </p>
                                <p class="mt-2 text-sm font-bold text-stone-900 dark:text-white">
                                    {{ product.track_stock ? 'Track stock' : 'No stock' }}
                                </p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-slate-400">
                                    {{ product.track_expired ? 'Track expired' : 'No expired' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-stone-900 dark:text-white">
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
                                        class="flex items-center justify-between rounded-xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/70 px-3 py-2 text-[11px]"
                                    >
                                        <span class="font-semibold text-stone-800 dark:text-slate-200">
                                            {{ variant.name }}
                                        </span>
                                        <span class="font-bold text-sky-300">
                                            +{{ formatPrice(variant.additional_price) }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="mt-3 text-[11px] text-stone-400 dark:text-slate-500">
                                    Produk ini belum punya varian aktif.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/70 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-stone-900 dark:text-white">
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
                                        class="flex items-center justify-between rounded-xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/70 px-3 py-2 text-[11px]"
                                    >
                                        <div>
                                            <p class="font-semibold text-stone-800 dark:text-slate-200">
                                                {{ price.tier_label || price.tier || 'Harga' }}
                                            </p>
                                        </div>
                                        <span class="font-bold text-amber-300">
                                            {{ formatPrice(price.price) }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="mt-3 text-[11px] text-stone-400 dark:text-slate-500">
                                    Produk ini baru memakai satu harga dasar.
                                </p>
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-if="products.links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-stone-200 dark:border-slate-800/80 px-5 py-4"
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
                                    : 'border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 text-stone-600 dark:text-slate-300 hover:border-stone-200 dark:border-slate-700',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded-xl border border-slate-900 bg-white dark:bg-slate-950/50 px-3 py-2 text-xs font-bold text-slate-600"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </section>
        </div>

        <!-- Create/Edit Modal -->
        <div
            v-if="isProductModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-slate-950/80 px-4 backdrop-blur-sm"
            @click.self="closeProductModal"
        >
            <div class="w-full max-w-4xl max-h-[90vh] flex flex-col rounded-[28px] border border-stone-200 dark:border-slate-800/80 bg-stone-100 dark:bg-slate-950 shadow-[0_30px_120px_rgba(2,6,23,0.7)]">
                <!-- Header Modal -->
                <div class="flex items-start justify-between gap-4 p-5 border-b border-stone-200 dark:border-slate-800/80">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            {{ editingProduct ? 'Edit Produk' : 'Tambah Produk Baru' }}
                        </p>
                        <h3 class="mt-1 text-lg font-black text-stone-900 dark:text-white">
                            {{ editingProduct ? editingProduct.name : 'Data produk outlet' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeProductModal"
                        class="rounded-xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-2 text-stone-500 dark:text-slate-400"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-stone-200 dark:border-slate-800/80 px-5 overflow-x-auto no-scrollbar">
                    <button
                        v-for="tab in [
                            { id: 'basic', label: 'Info Dasar', icon: Boxes },
                            { id: 'variants', label: 'Varian Produk', icon: Layers3 },
                            { id: 'prices', label: 'Multi Harga', icon: Tags },
                            { id: 'recipe', label: 'Resep / Bahan Baku', icon: FlaskConical },
                        ]"
                        :key="tab.id"
                        @click="activeTab = tab.id as any"
                        :class="[
                            'flex items-center gap-2 border-b-2 px-4 py-4 text-xs font-bold transition whitespace-nowrap',
                            activeTab === tab.id
                                ? 'border-orange-500 text-orange-500'
                                : 'border-transparent text-stone-400 dark:text-slate-500 hover:text-stone-600 dark:text-slate-300'
                        ]"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                        <span
                            v-if="tab.id === 'variants' && productForm.variants.length > 0"
                            class="rounded-full bg-orange-500/20 px-1.5 py-0.5 text-[10px] text-orange-500"
                        >
                            {{ productForm.variants.length }}
                        </span>
                        <span
                            v-if="tab.id === 'prices' && productForm.prices.length > 0"
                            class="rounded-full bg-orange-500/20 px-1.5 py-0.5 text-[10px] text-orange-500"
                        >
                            {{ productForm.prices.length }}
                        </span>
                        <span
                            v-if="tab.id === 'recipe' && productForm.ingredients.length > 0"
                            class="rounded-full bg-orange-500/20 px-1.5 py-0.5 text-[10px] text-orange-500"
                        >
                            {{ productForm.ingredients.length }}
                        </span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto p-5 custom-scrollbar">
                    <form @submit.prevent="submitProduct" id="productForm">
                        <!-- Basic Info Tab -->
                        <div v-show="activeTab === 'basic'" class="space-y-6">
                            <div class="flex flex-col gap-6 md:flex-row">
                                <!-- Image Upload Section -->
                                <div class="flex flex-col items-center gap-3 md:w-48">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500 w-full text-center">
                                        Gambar Produk
                                    </label>
                                    <div
                                        class="group relative h-40 w-40 overflow-hidden rounded-3xl border-2 border-dashed border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition hover:border-orange-500/50"
                                        @click="openFilePicker"
                                    >
                                        <img
                                            v-if="imagePreview"
                                            :src="imagePreview"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full flex-col items-center justify-center gap-2 text-slate-600">
                                            <Camera class="h-8 w-8" />
                                            <span class="text-[10px] font-bold uppercase">Klik Upload</span>
                                        </div>
                                        <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-slate-950/40 opacity-0 transition group-hover:opacity-100 cursor-pointer">
                                            <Camera class="h-6 w-6 text-stone-900 dark:text-white" />
                                        </div>
                                    </div>
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        class="hidden"
                                        accept="image/*"
                                        @change="onFileChange"
                                    />
                                    <p class="text-center text-[10px] text-stone-400 dark:text-slate-500">
                                        Format: JPG, PNG, WebP (Max 2MB)
                                    </p>
                                    <p v-if="productForm.errors.image" class="text-center text-[10px] text-rose-300">
                                        {{ productForm.errors.image }}
                                    </p>
                                </div>

                                <div class="flex-1 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300">
                                            Nama Produk
                                        </label>
                                        <input
                                            v-model="productForm.name"
                                            type="text"
                                            placeholder="Masukkan nama produk..."
                                            class="w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                        />
                                        <p v-if="productForm.errors.name" class="mt-1 text-xs text-rose-300">
                                            {{ productForm.errors.name }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300">
                                            Kategori
                                        </label>
                                        <select
                                            v-model="productForm.category_id"
                                            class="w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                        >
                                            <option value="" disabled>Pilih kategori...</option>
                                            <option
                                                v-for="category in categories"
                                                :key="category.id"
                                                :value="category.id"
                                            >
                                                {{ category.name }}
                                            </option>
                                        </select>
                                        <p v-if="productForm.errors.category_id" class="mt-1 text-xs text-rose-300">
                                            {{ productForm.errors.category_id }}
                                        </p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300">
                                            Deskripsi (Opsional)
                                        </label>
                                        <textarea
                                            v-model="productForm.description"
                                            rows="2"
                                            placeholder="Masukkan deskripsi produk..."
                                            class="w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                        ></textarea>
                                        <p v-if="productForm.errors.description" class="mt-1 text-xs text-rose-300">
                                            {{ productForm.errors.description }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300">
                                            Harga Dasar
                                        </label>
                                        <input
                                            v-model="productForm.base_price"
                                            type="number"
                                            min="0"
                                            class="w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none font-bold text-orange-400"
                                        />
                                        <p v-if="productForm.errors.base_price" class="mt-1 text-xs text-rose-300">
                                            {{ productForm.errors.base_price }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-semibold text-stone-600 dark:text-slate-300">
                                            HPP Estimasi (Manual)
                                        </label>
                                        <div class="relative">
                                            <input
                                                v-model="productForm.hpp"
                                                type="number"
                                                min="0"
                                                class="w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                            />
                                            <button
                                                v-if="productForm.ingredients.length > 0"
                                                type="button"
                                                @click="applyCalculatedHpp"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg bg-orange-500/20 px-2 py-1 text-[10px] font-bold text-orange-400"
                                            >
                                                Gunakan Hitungan Resep
                                            </button>
                                        </div>
                                        <p v-if="productForm.errors.hpp" class="mt-1 text-xs text-rose-300">
                                            {{ productForm.errors.hpp }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                                <label class="flex items-center gap-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-[11px] text-stone-800 dark:text-slate-200 cursor-pointer">
                                    <input
                                        v-model="productForm.is_available"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-950 text-orange-500 focus:ring-orange-500"
                                    />
                                    Siap Dijual
                                </label>

                                <label class="flex items-center gap-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-[11px] text-stone-800 dark:text-slate-200 cursor-pointer">
                                    <input
                                        v-model="productForm.is_active"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-950 text-orange-500 focus:ring-orange-500"
                                    />
                                    Produk Aktif
                                </label>

                                <label class="flex items-center gap-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-[11px] text-stone-800 dark:text-slate-200 cursor-pointer">
                                    <input
                                        v-model="productForm.track_stock"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-950 text-orange-500 focus:ring-orange-500"
                                    />
                                    Track Stok
                                </label>

                                <label class="flex items-center gap-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-[11px] text-stone-800 dark:text-slate-200 cursor-pointer">
                                    <input
                                        v-model="productForm.track_expired"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-950 text-orange-500 focus:ring-orange-500"
                                    />
                                    Track Expired
                                </label>

                                <div v-if="productForm.track_expired" class="sm:col-span-2">
                                    <select
                                        v-model="productForm.expired_action"
                                        class="w-full rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 text-[11px] text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                    >
                                        <option value="notify_only">Notify only (Hanya peringatan)</option>
                                        <option value="auto_deactivate">Auto deactivate (Otomatis off)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Variants Tab -->
                        <div v-show="activeTab === 'variants'" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-bold text-stone-900 dark:text-white">
Kelola Varian Produk</h4>
                                <button
                                    type="button"
                                    @click="addVariantRow"
                                    class="inline-flex items-center gap-2 rounded-xl border border-sky-500/20 bg-sky-500/10 px-3 py-1.5 text-[11px] font-bold text-sky-300 transition hover:bg-sky-500/20"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    Tambah Varian
                                </button>
                            </div>

                            <div v-if="productForm.variants.length === 0" class="rounded-2xl border border-dashed border-stone-200 dark:border-slate-800 p-8 text-center text-xs text-stone-400 dark:text-slate-500">
                                Belum ada varian ditambahkan. Produk ini akan dijual sebagai item tunggal.
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(variant, index) in productForm.variants"
                                    :key="index"
                                    class="group relative rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/50 p-4 transition hover:border-stone-200 dark:border-slate-700"
                                >
                                    <div class="grid gap-4 sm:grid-cols-[1fr_200px_100px_40px]">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Nama Varian
                                            </label>
                                            <input
                                                v-model="variant.name"
                                                type="text"
                                                placeholder="Contoh: Pedas, Large, Topping..."
                                                class="w-full rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-3 py-2 text-xs text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Tambahan Harga (Rp)
                                            </label>
                                            <input
                                                v-model="variant.additional_price"
                                                type="number"
                                                min="0"
                                                class="w-full rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-3 py-2 text-xs text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Status
                                            </label>
                                            <label class="flex items-center h-[34px] gap-2 text-[10px] text-stone-600 dark:text-slate-300 cursor-pointer">
                                                <input
                                                    v-model="variant.is_active"
                                                    type="checkbox"
                                                    class="h-3.5 w-3.5 rounded border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-950 text-sky-500 focus:ring-sky-500"
                                                />
                                                Aktif
                                            </label>
                                        </div>
                                        <div class="flex items-end justify-end">
                                            <button
                                                type="button"
                                                @click="removeVariantRow(index)"
                                                class="flex h-[34px] w-full items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 transition hover:bg-rose-500/20"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Multi Price Tab -->
                        <div v-show="activeTab === 'prices'" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-bold text-stone-900 dark:text-white">
Kelola Multi Harga & Tier</h4>
                                <button
                                    type="button"
                                    @click="addPriceRow"
                                    class="inline-flex items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/10 px-3 py-1.5 text-[11px] font-bold text-amber-300 transition hover:bg-amber-500/20"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    Tambah Tier Harga
                                </button>
                            </div>

                            <div v-if="productForm.prices.length === 0" class="rounded-2xl border border-dashed border-stone-200 dark:border-slate-800 p-8 text-center text-xs text-stone-400 dark:text-slate-500">
                                Belum ada multi harga. Produk hanya menggunakan Harga Dasar.
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(price, index) in productForm.prices"
                                    :key="index"
                                    class="group relative rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/50 p-4 transition hover:border-stone-200 dark:border-slate-700"
                                >
                                    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-[160px_1fr_160px_100px_40px]">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Tier
                                            </label>
                                            <select
                                                v-model="price.tier"
                                                class="w-full rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-3 py-2 text-xs text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                            >
                                                <option
                                                    v-for="tier in priceTiers"
                                                    :key="tier.id"
                                                    :value="tier.id"
                                                >
                                                    {{ tier.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Label Kustom (Opsional)
                                            </label>
                                            <input
                                                v-model="price.tier_label"
                                                type="text"
                                                placeholder="Contoh: Member Gold, Promo Jumat..."
                                                class="w-full rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-3 py-2 text-xs text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Harga Final (Rp)
                                            </label>
                                            <input
                                                v-model="price.price"
                                                type="number"
                                                min="0"
                                                class="w-full rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-3 py-2 text-xs text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none font-bold text-amber-300"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Status
                                            </label>
                                            <label class="flex items-center h-[34px] gap-2 text-[10px] text-stone-600 dark:text-slate-300 cursor-pointer">
                                                <input
                                                    v-model="price.is_active"
                                                    type="checkbox"
                                                    class="h-3.5 w-3.5 rounded border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-950 text-amber-500 focus:ring-amber-500"
                                                />
                                                Aktif
                                            </label>
                                        </div>
                                        <div class="flex items-end justify-end">
                                            <button
                                                type="button"
                                                @click="removePriceRow(index)"
                                                class="flex h-[34px] w-full items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 transition hover:bg-rose-500/20"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>

                                        <!-- Happy Hour Row -->
                                        <div class="sm:col-span-2 md:col-span-5 flex flex-wrap items-center gap-4 mt-1">
                                            <div class="flex items-center gap-2 text-[10px] font-bold text-stone-400 dark:text-slate-500">
                                                <Clock class="h-3 w-3" />
                                                SET HAPPY HOUR:
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    v-model="price.happy_hour_start"
                                                    type="time"
                                                    class="rounded-lg border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-2 py-1 text-[10px] text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                                />
                                                <span class="text-[10px] text-slate-600">S/D</span>
                                                <input
                                                    v-model="price.happy_hour_end"
                                                    type="time"
                                                    class="rounded-lg border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-2 py-1 text-[10px] text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                                />
                                                <button
                                                    v-if="price.happy_hour_start || price.happy_hour_end"
                                                    type="button"
                                                    @click="price.happy_hour_start = ''; price.happy_hour_end = ''"
                                                    class="text-[10px] text-rose-400 underline underline-offset-2"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                            <p class="text-[10px] text-stone-400 dark:text-slate-500 italic">
                                                *Kosongkan jika berlaku 24 jam.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recipe Tab -->
                        <div v-show="activeTab === 'recipe'" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-bold text-stone-900 dark:text-white">
Bahan Baku (Resep)</h4>
                                    <p class="text-[10px] text-stone-400 dark:text-slate-500 mt-0.5">
                                        Tentukan pemakaian bahan baku untuk menghitung HPP otomatis.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="addIngredientRow"
                                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5 text-[11px] font-bold text-emerald-300 transition hover:bg-emerald-500/20"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    Tambah Bahan
                                </button>
                            </div>

                            <div v-if="productForm.ingredients.length === 0" class="rounded-2xl border border-dashed border-stone-200 dark:border-slate-800 p-8 text-center text-xs text-stone-400 dark:text-slate-500">
                                Belum ada bahan baku yang didaftarkan untuk produk ini.
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(item, index) in productForm.ingredients"
                                    :key="index"
                                    class="group relative rounded-2xl border border-stone-200 dark:border-slate-800 bg-stone-50 dark:bg-slate-900/50 p-4 transition hover:border-stone-200 dark:border-slate-700"
                                >
                                    <div class="grid gap-4 sm:grid-cols-[1fr_120px_140px_40px]">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Pilih Bahan Baku
                                            </label>
                                            <select
                                                v-model="item.raw_material_id"
                                                class="w-full rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-3 py-2 text-xs text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                            >
                                                <option
                                                    v-for="material in rawMaterials"
                                                    :key="material.id"
                                                    :value="material.id"
                                                >
                                                    {{ material.name }} ({{ material.unit }})
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Pemakaian
                                            </label>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    v-model="item.quantity"
                                                    type="number"
                                                    step="0.001"
                                                    min="0.001"
                                                    class="w-full rounded-xl border border-stone-200 dark:border-slate-800 bg-stone-100 dark:bg-slate-950 px-3 py-2 text-xs text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                                />
                                                <span class="text-[10px] font-bold text-stone-400 dark:text-slate-500">
                                                    {{ rawMaterials.find(m => m.id === item.raw_material_id)?.unit || 'u' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                                Subtotal Biaya
                                            </label>
                                            <div class="flex items-center h-[34px] px-3 rounded-xl bg-white dark:bg-slate-950/50 border border-stone-200 dark:border-slate-800 text-[11px] font-bold text-emerald-400">
                                                {{ formatPrice(Number(rawMaterials.find(m => m.id === item.raw_material_id)?.cost_per_unit || 0) * Number(item.quantity || 0)) }}
                                            </div>
                                        </div>
                                        <div class="flex items-end justify-end">
                                            <button
                                                type="button"
                                                @click="removeIngredientRow(index)"
                                                class="flex h-[34px] w-full items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 transition hover:bg-rose-500/20"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- HPP Calculation Summary -->
                                <div class="mt-6 rounded-2xl border-2 border-emerald-500/20 bg-emerald-500/5 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">
                                                Estimasi HPP Resep
                                            </p>
                                            <h5 class="text-2xl font-black text-stone-900 dark:text-white mt-1">
                                                {{ formatPrice(calculatedHppFromRecipe) }}
                                            </h5>
                                        </div>
                                        <button
                                            type="button"
                                            @click="applyCalculatedHpp"
                                            class="rounded-xl bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 transition hover:bg-emerald-400"
                                        >
                                            Gunakan Nilai Ini
                                        </button>
                                    </div>
                                    <p class="mt-2 text-[10px] text-stone-500 dark:text-slate-400 leading-relaxed italic">
                                        *HPP dihitung berdasarkan (Cost per Unit bahan baku) x (Pemakaian).
                                        Klik tombol "Gunakan Nilai Ini" untuk mengupdate HPP di Tab Info Dasar.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer Modal -->
                <div class="flex items-center justify-between p-5 border-t border-stone-200 dark:border-slate-800/80 bg-white dark:bg-slate-950/50 rounded-b-[28px]">
                    <div class="flex items-center gap-2">
                        <div
                            v-if="Object.keys(productForm.errors).length > 0"
                            class="flex flex-col gap-1 text-xs font-bold text-rose-400"
                        >
                            <div class="flex items-center gap-2">
                                <AlertTriangle class="h-4 w-4" />
                                Ada {{ Object.keys(productForm.errors).length }} error validasi.
                            </div>
                            <p class="font-medium text-[10px] text-rose-300/80 ml-6">
                                * {{ Object.values(productForm.errors)[0] }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="closeProductModal"
                            class="rounded-2xl border border-stone-200 dark:border-slate-800 px-6 py-3 text-sm font-semibold text-stone-600 dark:text-slate-300 transition hover:bg-white dark:bg-slate-900"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            form="productForm"
                            :disabled="productForm.processing"
                            class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-8 py-3 text-sm font-bold text-stone-900 dark:text-white shadow-lg shadow-orange-500/20 disabled:opacity-60"
                        >
                            {{ productForm.processing ? 'Menyimpan...' : 'Simpan Produk' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
}
</style>
