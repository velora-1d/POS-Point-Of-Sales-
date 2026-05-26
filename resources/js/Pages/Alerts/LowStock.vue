<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, BellRing, Boxes, PackageSearch } from '@lucide/vue';
import { computed } from 'vue';

interface AlertItem {
    id: string;
    type: 'product' | 'raw_material';
    name: string;
    context?: string | null;
    current_stock: number;
    minimum_stock: number;
    unit?: string | null;
    route: string;
    severity: number;
}

const props = defineProps<{
    summary: {
        total: number;
        products: number;
        raw_materials: number;
        critical: number;
    };
    items: AlertItem[];
    productItems: AlertItem[];
    rawMaterialItems: AlertItem[];
}>();

const summaryCards = computed(() => [
    {
        label: 'Total Alert',
        value: props.summary.total,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: BellRing,
    },
    {
        label: 'Kritis / Habis',
        value: props.summary.critical,
        tone: 'text-rose-300',
        surface: 'border-rose-400/15 bg-rose-500/8',
        icon: AlertTriangle,
    },
    {
        label: 'Produk Jadi',
        value: props.summary.products,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/8',
        icon: Boxes,
    },
    {
        label: 'Bahan Baku',
        value: props.summary.raw_materials,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/8',
        icon: PackageSearch,
    },
]);

const getItemTone = (item: AlertItem) => {
    if (item.current_stock <= 0) {
        return 'border-rose-500/20 bg-rose-500/10 text-rose-300';
    }

    return 'border-amber-500/20 bg-amber-500/10 text-amber-300';
};

const getItemLabel = (item: AlertItem) => {
    return item.current_stock <= 0 ? 'Kritis' : 'Menipis';
};
</script>

<template>
    <Head title="Alert Stok Menipis" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <BellRing class="h-3.5 w-3.5" />
                    Menu #32 Alert Stok Menipis
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Alert Stok Menipis
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs text-slate-400">
                        Pantau item yang stoknya sudah menyentuh batas minimum, baik dari produk jadi maupun bahan baku.
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
                class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 shadow-xl shadow-slate-950/15"
            >
                <div class="flex flex-col gap-2 border-b border-slate-800/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Prioritas Hari Ini
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Total {{ items.length }} item paling mendesak dari seluruh alert stok rendah.
                        </p>
                    </div>
                </div>

                <div v-if="!items.length" class="px-5 py-16 text-center text-sm text-slate-400">
                    Belum ada alert stok menipis. Semua stok utama masih aman.
                </div>

                <div v-else class="grid gap-4 px-5 py-5 xl:grid-cols-2">
                    <article
                        v-for="item in items"
                        :key="`${item.type}-${item.id}`"
                        class="rounded-[24px] border border-slate-800/80 bg-slate-950/60 p-5"
                    >
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-black text-white">
                                            {{ item.name }}
                                        </h3>
                                        <span
                                            :class="[
                                                'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]',
                                                getItemTone(item),
                                            ]"
                                        >
                                            {{ getItemLabel(item) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400">
                                        {{ item.type === 'product' ? 'Produk jadi' : 'Bahan baku' }}
                                        <span v-if="item.context">• {{ item.context }}</span>
                                    </p>
                                </div>

                                <Link
                                    :href="route(item.route)"
                                    class="inline-flex items-center gap-2 rounded-xl border border-orange-500/20 bg-orange-500/10 px-3 py-2 text-xs font-bold text-orange-300"
                                >
                                    Buka Menu
                                </Link>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        Stok Saat Ini
                                    </p>
                                    <p class="mt-2 text-xl font-black text-white">
                                        {{ item.current_stock }}
                                        <span class="text-sm font-semibold text-slate-400">
                                            {{ item.unit || 'pcs' }}
                                        </span>
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        Minimum Stok
                                    </p>
                                    <p class="mt-2 text-xl font-black text-white">
                                        {{ item.minimum_stock }}
                                        <span class="text-sm font-semibold text-slate-400">
                                            {{ item.unit || 'pcs' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-2">
                <section
                    class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 shadow-xl shadow-slate-950/15"
                >
                    <div class="border-b border-slate-800/80 px-5 py-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Produk Jadi Menipis
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ productItems.length }} item dari stok produk jadi.
                        </p>
                    </div>
                    <div class="space-y-3 px-5 py-5">
                        <article
                            v-for="item in productItems"
                            :key="item.id"
                            class="rounded-2xl border border-slate-800 bg-slate-950/60 px-4 py-3"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-white">{{ item.name }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ item.context || 'Tanpa kategori' }}
                                    </p>
                                </div>
                                <p class="text-xs font-semibold text-slate-300">
                                    {{ item.current_stock }}/{{ item.minimum_stock }} {{ item.unit }}
                                </p>
                            </div>
                        </article>
                        <p v-if="!productItems.length" class="text-sm text-slate-400">
                            Belum ada produk jadi yang menipis.
                        </p>
                    </div>
                </section>

                <section
                    class="rounded-[26px] border border-slate-800/80 bg-slate-900/92 shadow-xl shadow-slate-950/15"
                >
                    <div class="border-b border-slate-800/80 px-5 py-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300">
                            Bahan Baku Menipis
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ rawMaterialItems.length }} item dari inventori bahan baku.
                        </p>
                    </div>
                    <div class="space-y-3 px-5 py-5">
                        <article
                            v-for="item in rawMaterialItems"
                            :key="item.id"
                            class="rounded-2xl border border-slate-800 bg-slate-950/60 px-4 py-3"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-white">{{ item.name }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ item.context || 'Bahan baku' }}
                                    </p>
                                </div>
                                <p class="text-xs font-semibold text-slate-300">
                                    {{ item.current_stock }}/{{ item.minimum_stock }} {{ item.unit }}
                                </p>
                            </div>
                        </article>
                        <p v-if="!rawMaterialItems.length" class="text-sm text-slate-400">
                            Belum ada bahan baku yang menipis.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
