<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Coins,
    Crown,
    Info,
    Pencil,
    Percent,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

interface MembershipTier {
    id: string;
    outlet_id: string;
    tier: string;
    name: string;
    point_threshold: number;
    point_rate_per_amount: number | string;
    discount_percent: number | string;
    description?: string | null;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
    memberships_count?: number; // count of users in this tier
}

const props = defineProps<{
    tiers: MembershipTier[];
    outlet_id: string;
    errors?: Record<string, string>;
}>();

const page = usePage<any>();
const flashSuccess = computed(() => page.props.flash?.success);

const isFormModalOpen = ref(false);
const formMode = ref<'create' | 'edit'>('create');
const editingTierId = ref<string | null>(null);

const form = useForm({
    name: '',
    tier: '',
    point_threshold: 0,
    point_rate_per_amount: 0.0001,
    discount_percent: 0,
    description: '',
    is_active: true,
});

// Auto-generate tier key/slug from name if creating or if tier key matches old slug
watch(
    () => form.name,
    (newName) => {
        if (formMode.value === 'create' || !form.tier) {
            form.tier = newName
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)+/g, '');
        }
    },
);

const openCreateModal = () => {
    formMode.value = 'create';
    editingTierId.value = null;
    form.reset();
    form.clearErrors();
    isFormModalOpen.value = true;
};

const openEditModal = (tierItem: MembershipTier) => {
    formMode.value = 'edit';
    editingTierId.value = tierItem.id;
    form.clearErrors();
    form.name = tierItem.name;
    form.tier = tierItem.tier;
    form.point_threshold = tierItem.point_threshold;
    form.point_rate_per_amount = Number(tierItem.point_rate_per_amount);
    form.discount_percent = Number(tierItem.discount_percent);
    form.description = tierItem.description || '';
    form.is_active = Boolean(tierItem.is_active);
    isFormModalOpen.value = true;
};

const saveTier = () => {
    if (formMode.value === 'create') {
        form.post(route('settings.membership-tiers.store'), {
            onSuccess: () => {
                isFormModalOpen.value = false;
                form.reset();
            },
        });
    } else if (editingTierId.value) {
        form.patch(
            route('settings.membership-tiers.update', editingTierId.value),
            {
                onSuccess: () => {
                    isFormModalOpen.value = false;
                    form.reset();
                },
            },
        );
    }
};

const deleteTier = (tierItem: MembershipTier) => {
    if (
        confirm(
            `Apakah Anda yakin ingin menghapus tier "${tierItem.name}"? Pelanggan dengan tier ini tidak akan otomatis terhapus, namun tidak akan memiliki tier lagi.`,
        )
    ) {
        form.delete(route('settings.membership-tiers.destroy', tierItem.id));
    }
};

// Helper to illustrate point system in Form Modal
const pointIllustration = computed(() => {
    const rate = Number(form.point_rate_per_amount || 0);
    if (rate <= 0) return 'Pelanggan tidak mendapatkan poin dari transaksi.';
    const exampleAmount = 10000;
    const pointsGained = exampleAmount * rate;
    return `Setiap pembelanjaan Rp ${exampleAmount.toLocaleString('id-ID')}, pelanggan akan memperoleh ${pointsGained.toLocaleString('id-ID', { maximumFractionDigits: 2 })} poin.`;
});
</script>

<template>
    <Head title="Kelola Tier Membership - POS Mentai" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-stone-900 dark:text-white"
                    >
                        Kelola Tier Membership
                    </h2>
                    <p
                        class="mt-1 max-w-2xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Atur tingkatan member, diskon otomatis, syarat
                        pencapaian poin minimum, dan rasio perolehan poin
                        belanja.
                    </p>
                </div>
                <div>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-4 py-2.5 text-xs font-bold text-slate-950 transition hover:bg-orange-400"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Tier Baru
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Alert Messages from Session -->
            <div
                v-if="flashSuccess"
                class="flex items-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-300"
            >
                <BadgeCheck class="h-5 w-5" />
                {{ flashSuccess }}
            </div>

            <div
                v-if="(form.errors as any).error"
                class="flex items-center gap-2 rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4 text-xs font-bold text-rose-300"
            >
                <Info class="h-5 w-5" />
                {{ (form.errors as any).error }}
            </div>

            <!-- Tiers Grid Layout -->
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="tierItem in tiers"
                    :key="tierItem.id"
                    class="flex min-h-[220px] flex-col justify-between rounded-3xl border p-5 shadow-2xl transition duration-300"
                    :class="[
                        tierItem.is_active
                            ? 'border-stone-200 bg-white hover:border-orange-500/30 dark:border-white/10 dark:bg-slate-950/40'
                            : 'border-stone-200 bg-white opacity-60 dark:border-white/5 dark:bg-slate-950/10',
                    ]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <Crown
                                    class="h-5 w-5"
                                    :class="[
                                        tierItem.tier === 'gold'
                                            ? 'text-amber-400'
                                            : tierItem.tier === 'silver'
                                              ? 'text-stone-600 dark:text-slate-300'
                                              : 'text-orange-400',
                                    ]"
                                />
                                <h3
                                    class="text-base font-extrabold text-stone-900 dark:text-white"
                                >
                                    {{ tierItem.name }}
                                </h3>
                            </div>
                            <span
                                class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                :class="[
                                    tierItem.is_active
                                        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
                                        : 'border-stone-200 bg-stone-100 text-stone-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400',
                                ]"
                            >
                                {{ tierItem.is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </div>

                        <p
                            class="mt-2 line-clamp-2 text-xs text-stone-500 dark:text-slate-400"
                        >
                            {{
                                tierItem.description ||
                                'Tidak ada deskripsi tier.'
                            }}
                        </p>

                        <!-- Tier Specs -->
                        <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                            <div
                                class="rounded-xl border border-stone-200 bg-stone-50 p-2 dark:border-white/5 dark:bg-slate-900/60"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                >
                                    Syarat Poin
                                </p>
                                <p
                                    class="mt-0.5 flex items-center gap-1 font-extrabold text-stone-900 dark:text-white"
                                >
                                    <Coins class="h-3.5 w-3.5 text-amber-400" />
                                    {{ tierItem.point_threshold }} pts
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-stone-200 bg-stone-50 p-2 dark:border-white/5 dark:bg-slate-900/60"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                >
                                    Diskon Transaksi
                                </p>
                                <p
                                    class="mt-0.5 flex items-center gap-1 font-extrabold text-emerald-400"
                                >
                                    <Percent class="h-3.5 w-3.5" />
                                    {{ Number(tierItem.discount_percent) }}%
                                </p>
                            </div>
                            <div
                                class="col-span-2 rounded-xl border border-stone-200 bg-stone-50 p-2 dark:border-white/5 dark:bg-slate-900/60"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-stone-400 dark:text-slate-500"
                                >
                                    Rasio Poin per Rp 10.000 Belanja
                                </p>
                                <p class="mt-0.5 font-extrabold text-amber-300">
                                    {{
                                        (
                                            10000 *
                                            Number(
                                                tierItem.point_rate_per_amount,
                                            )
                                        ).toLocaleString('id-ID')
                                    }}
                                    pts
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div
                        class="mt-4 flex shrink-0 items-center justify-between border-t border-stone-200 pt-4 dark:border-white/5"
                    >
                        <span
                            class="text-[10px] text-stone-400 dark:text-slate-500"
                        >
                            ID:
                            <code
                                class="font-mono text-stone-500 dark:text-slate-400"
                                >{{ tierItem.tier }}</code
                            >
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openEditModal(tierItem)"
                                class="inline-flex items-center gap-1 rounded-xl border border-stone-200 bg-white px-2.5 py-1.5 text-[10px] font-bold text-stone-800 transition hover:border-slate-600 hover:text-stone-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:text-white"
                            >
                                <Pencil class="h-3 w-3" />
                                Edit
                            </button>
                            <button
                                type="button"
                                @click="deleteTier(tierItem)"
                                class="inline-flex items-center gap-1 rounded-xl border border-rose-500/20 bg-rose-500/10 px-2.5 py-1.5 text-[10px] font-bold text-rose-300 transition hover:bg-rose-500/20"
                            >
                                <Trash2 class="h-3 w-3" />
                                Hapus
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Empty State -->
                <div
                    v-if="tiers.length === 0"
                    class="col-span-full rounded-3xl border border-dashed border-stone-200 bg-white p-12 text-center dark:border-white/10 dark:bg-slate-950/20"
                >
                    <Crown class="mx-auto mb-3 h-10 w-10 text-slate-600" />
                    <p
                        class="text-sm font-bold text-stone-500 dark:text-slate-400"
                    >
                        Belum ada tier membership terdaftar.
                    </p>
                    <p class="mt-1 text-xs text-stone-400 dark:text-slate-500">
                        Tambahkan tier member pertama Anda untuk mengaktifkan
                        sistem poin outlet.
                    </p>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-orange-400"
                    >
                        <Plus class="h-4 w-4" />
                        Buat Tier Pertama
                    </button>
                </div>
            </section>
        </div>

        <!-- FORM MODAL (Create & Edit) -->
        <teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="isFormModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-white p-4 backdrop-blur-sm dark:bg-slate-950/80"
                >
                    <div
                        class="w-full max-w-lg rounded-3xl border border-stone-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-slate-900"
                    >
                        <div
                            class="mb-4 flex items-center justify-between border-b border-stone-200 pb-4 dark:border-white/10"
                        >
                            <div class="flex items-center gap-2">
                                <Crown class="h-5 w-5 text-orange-400" />
                                <h3
                                    class="text-lg font-black text-stone-900 dark:text-white"
                                >
                                    {{
                                        formMode === 'create'
                                            ? 'Tambah Tier Membership Baru'
                                            : 'Edit Tier Membership'
                                    }}
                                </h3>
                            </div>
                            <button
                                @click="isFormModalOpen = false"
                                class="text-stone-400 transition hover:text-stone-900 dark:text-slate-500 dark:text-white"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="saveTier" class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                    >Nama Tier</label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Contoh: Platinum VIP"
                                    required
                                    class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-3.5 py-2.5 text-xs text-stone-900 placeholder-slate-600 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="mt-1 text-[10px] font-bold text-rose-400"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Tier Key -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                >
                                    Kode / ID Tier (Unique Slug)
                                </label>
                                <input
                                    v-model="form.tier"
                                    type="text"
                                    placeholder="Contoh: platinum-vip"
                                    required
                                    class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-3.5 py-2.5 text-xs text-stone-900 placeholder-slate-600 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                                />
                                <p
                                    class="mt-1 text-[10px] text-stone-400 dark:text-slate-500"
                                >
                                    Digunakan sistem sebagai pengenal database
                                    unik (huruf kecil, angka, dan strip).
                                </p>
                                <p
                                    v-if="form.errors.tier"
                                    class="mt-1 text-[10px] font-bold text-rose-400"
                                >
                                    {{ form.errors.tier }}
                                </p>
                            </div>

                            <!-- Specs Grid -->
                            <div class="grid gap-3 sm:grid-cols-2">
                                <!-- Point Threshold -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                        >Syarat Minimum Poin</label
                                    >
                                    <input
                                        v-model.number="form.point_threshold"
                                        type="number"
                                        min="0"
                                        required
                                        class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-3.5 py-2.5 text-xs text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                                    />
                                    <p
                                        v-if="form.errors.point_threshold"
                                        class="mt-1 text-[10px] font-bold text-rose-400"
                                    >
                                        {{ form.errors.point_threshold }}
                                    </p>
                                </div>

                                <!-- Discount Percent -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                        >Persentase Diskon (%)</label
                                    >
                                    <input
                                        v-model.number="form.discount_percent"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        required
                                        class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-3.5 py-2.5 text-xs text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                                    />
                                    <p
                                        v-if="form.errors.discount_percent"
                                        class="mt-1 text-[10px] font-bold text-rose-400"
                                    >
                                        {{ form.errors.discount_percent }}
                                    </p>
                                </div>

                                <!-- Point Rate per Amount -->
                                <div class="col-span-2">
                                    <label
                                        class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                        >Rasio Perolehan Poin (per Rp 1)</label
                                    >
                                    <input
                                        v-model.number="
                                            form.point_rate_per_amount
                                        "
                                        type="number"
                                        min="0"
                                        max="1"
                                        step="0.000001"
                                        required
                                        class="w-full rounded-2xl border border-stone-200 bg-stone-100 px-3.5 py-2.5 text-xs text-stone-900 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                                    />
                                    <div
                                        class="mt-1.5 flex items-start gap-2 rounded-xl border border-orange-500/20 bg-orange-500/10 p-3"
                                    >
                                        <Info
                                            class="mt-0.5 h-4 w-4 shrink-0 text-orange-400"
                                        />
                                        <p
                                            class="text-[10px] font-semibold leading-relaxed text-orange-300"
                                        >
                                            {{ pointIllustration }}
                                        </p>
                                    </div>
                                    <p
                                        v-if="form.errors.point_rate_per_amount"
                                        class="mt-1 text-[10px] font-bold text-rose-400"
                                    >
                                        {{ form.errors.point_rate_per_amount }}
                                    </p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                                    >Deskripsi Benefit</label
                                >
                                <textarea
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Contoh: Potongan 15% setiap pembelian dine-in, dsb."
                                    class="w-full resize-none rounded-2xl border border-stone-200 bg-stone-100 px-3.5 py-2.5 text-xs text-stone-900 placeholder-slate-600 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/10 dark:bg-slate-950 dark:text-white"
                                ></textarea>
                                <p
                                    v-if="form.errors.description"
                                    class="mt-1 text-[10px] font-bold text-rose-400"
                                >
                                    {{ form.errors.description }}
                                </p>
                            </div>

                            <!-- Status Active Toggle -->
                            <div
                                class="mt-3 flex items-center justify-between border-t border-stone-200 py-2 dark:border-white/5"
                            >
                                <div>
                                    <p
                                        class="text-xs font-bold uppercase tracking-wider text-stone-900 dark:text-white"
                                    >
                                        Status Aktif
                                    </p>
                                    <p
                                        class="text-[10px] text-stone-400 dark:text-slate-500"
                                    >
                                        Tier non-aktif tidak akan dihitung dalam
                                        sistem loyalty pelanggan.
                                    </p>
                                </div>
                                <label
                                    class="relative inline-flex cursor-pointer items-center"
                                >
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="peer h-6 w-11 rounded-full bg-stone-100 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] focus:outline-none peer-checked:bg-orange-500 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-slate-800"
                                    ></div>
                                </label>
                            </div>

                            <!-- Form Actions -->
                            <div
                                class="mt-4 flex justify-end gap-2 border-t border-stone-200 pt-4 dark:border-white/10"
                            >
                                <button
                                    type="button"
                                    @click="isFormModalOpen = false"
                                    class="rounded-xl border border-stone-200 bg-stone-100 px-4 py-2.5 text-xs font-bold text-stone-600 hover:text-stone-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:text-white"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-xl bg-orange-500 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-orange-400 disabled:opacity-50"
                                >
                                    {{
                                        form.processing
                                            ? 'Menyimpan...'
                                            : formMode === 'create'
                                              ? 'Tambah Tier'
                                              : 'Simpan Perubahan'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </teleport>
    </AuthenticatedLayout>
</template>
