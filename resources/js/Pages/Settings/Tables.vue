<script setup lang="ts">
import AlertDialog from '@/Components/AlertDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    Download,
    ExternalLink,
    Home,
    Pencil,
    Plus,
    QrCode,
    Settings,
    Trash2,
    Trees,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface TableRow {
    id: string;
    name: string;
    capacity: number | null;
    status: string;
    is_active: boolean;
    category: 'indoor' | 'outdoor';
    public_qr_url?: string | null;
}

const props = defineProps<{
    tables: TableRow[];
    success?: string | null;
}>();

const alertDialog = ref({
    show: false,
    title: '',
    message: '',
    type: 'confirm' as 'info' | 'success' | 'warning' | 'danger' | 'confirm',
    onConfirm: () => {},
});

const closeAlertDialog = () => {
    alertDialog.value.show = false;
};

const modalMode = ref<'create' | 'edit' | null>(null);
const selectedTable = ref<TableRow | null>(null);
const selectedQrTable = ref<TableRow | null>(null);

const tableForm = useForm({
    name: '',
    capacity: 2,
    category: 'indoor' as 'indoor' | 'outdoor',
});

const isModalOpen = computed(() => modalMode.value !== null);
const isQrModalOpen = computed(() => selectedQrTable.value !== null);
const modalTitle = computed(() =>
    modalMode.value === 'edit' ? 'Edit Meja' : 'Tambah Meja Baru',
);

const indoorTables = computed(() =>
    props.tables.filter((t) => (t.category || 'indoor') === 'indoor'),
);
const outdoorTables = computed(() =>
    props.tables.filter((t) => t.category === 'outdoor'),
);

const tableQrImage = computed(() => {
    if (!selectedQrTable.value?.public_qr_url) return '';
    return `https://api.qrserver.com/v1/create-qr-code/?size=350x350&margin=16&data=${encodeURIComponent(selectedQrTable.value.public_qr_url)}`;
});

function openCreateModal(defaultCategory?: 'indoor' | 'outdoor') {
    modalMode.value = 'create';
    selectedTable.value = null;
    tableForm.reset();
    tableForm.clearErrors();
    if (defaultCategory) {
        tableForm.category = defaultCategory;
    }
}

function openEditModal(table: TableRow) {
    modalMode.value = 'edit';
    selectedTable.value = table;
    tableForm.name = table.name;
    tableForm.capacity = table.capacity || 2;
    tableForm.category = table.category || 'indoor';
    tableForm.clearErrors();
}

function openQrModal(table: TableRow) {
    selectedQrTable.value = table;
}

function closeQrModal() {
    selectedQrTable.value = null;
}

function closeModal() {
    modalMode.value = null;
    selectedTable.value = null;
    tableForm.reset();
}

function submitTable() {
    if (modalMode.value === 'create') {
        tableForm.post(route('settings.tables.store'), {
            onSuccess: () => closeModal(),
        });
    } else if (modalMode.value === 'edit' && selectedTable.value) {
        tableForm.patch(
            route('settings.tables.update', selectedTable.value.id),
            {
                onSuccess: () => closeModal(),
            },
        );
    }
}

function deleteTable(table: TableRow) {
    alertDialog.value = {
        show: true,
        title: 'Hapus Meja',
        message: `Apakah Anda yakin ingin menghapus meja ${table.name}?`,
        type: 'danger',
        onConfirm: () => {
            tableForm.delete(route('settings.tables.destroy', table.id), {
                onSuccess: () => closeAlertDialog(),
                onFinish: () => closeAlertDialog(),
            });
        },
    };
}

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'occupied':
            return 'border-rose-400/20 bg-rose-500/10 text-rose-300';
        case 'reserved':
            return 'border-amber-400/20 bg-amber-500/10 text-amber-300';
        default:
            return 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300';
    }
}
</script>

<template>
    <Head title="Manajemen Meja" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <Settings class="h-3.5 w-3.5" />
                    Manajemen Meja & Kapasitas
                </div>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2
                            class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                        >
                            Daftar Meja Outlet
                        </h2>
                        <p
                            class="mt-1 text-xs text-stone-500 dark:text-slate-400"
                        >
                            Tambah, edit, atau hapus daftar meja dan kelola
                            pembagian area serta QR Code pelanggan.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div
                v-if="success"
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/12 px-4 py-3 text-sm font-medium text-emerald-300"
            >
                {{ success }}
            </div>

            <!-- Layout 2 Kolom: Indoor vs Outdoor -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Kolom Kiri: Indoor Area -->
                <section
                    class="space-y-4 rounded-[28px] border border-stone-200 bg-white/50 p-6 dark:border-white/10 dark:bg-slate-900/20"
                >
                    <div
                        class="flex items-center justify-between border-b border-stone-200 pb-4 dark:border-white/10"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="rounded-xl bg-orange-500/10 p-2 text-orange-500"
                            >
                                <Home class="h-5 w-5" />
                            </div>
                            <div>
                                <h3
                                    class="text-lg font-black text-stone-900 dark:text-white"
                                >
                                    Area Indoor
                                </h3>
                                <p
                                    class="text-[11px] text-stone-500 dark:text-slate-400"
                                >
                                    {{ indoorTables.length }} meja terdaftar
                                </p>
                            </div>
                        </div>
                        <button
                            @click="openCreateModal('indoor')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-orange-500/10 px-3 py-1.5 text-xs font-bold text-orange-500 transition hover:bg-orange-500/20"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Tambah Meja
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <article
                            v-for="table in indoorTables"
                            :key="table.id"
                            class="group relative overflow-hidden rounded-[24px] border border-stone-200 bg-white p-5 transition hover:border-orange-500/30 dark:border-white/10 dark:bg-slate-900/40"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500"
                                    >
                                        {{ table.id.substring(0, 8) }}
                                    </p>
                                    <h3
                                        class="mt-1 text-xl font-black text-stone-900 dark:text-white"
                                    >
                                        {{ table.name }}
                                    </h3>
                                </div>
                                <span
                                    :class="[
                                        'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider',
                                        getStatusBadgeClass(table.status),
                                    ]"
                                >
                                    {{ table.status === 'occupied' ? 'Terisi' : (table.status === 'reserved' ? 'Reservasi' : 'Kosong') }}
                                </span>
                            </div>

                            <div
                                class="mt-4 flex items-center gap-2 text-stone-500 dark:text-slate-400"
                                >
                                <Users class="h-4 w-4" />
                                <span class="text-sm font-semibold"
                                    >{{ table.capacity || '-' }} Orang</span
                                >
                            </div>

                            <div
                                class="mt-6 flex items-center gap-2 opacity-0 transition group-hover:opacity-100"
                            >
                                <button
                                    @click="openEditModal(table)"
                                    class="flex-1 rounded-xl border border-stone-200 bg-stone-100 py-2 text-xs font-bold text-stone-800 transition hover:bg-stone-200 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                                >
                                    <div
                                        class="flex items-center justify-center gap-1.5"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                        Edit
                                    </div>
                                </button>
                                <button
                                    v-if="table.public_qr_url"
                                    @click="openQrModal(table)"
                                    class="flex h-8 w-8 items-center justify-center rounded-xl border border-stone-200 bg-stone-100 text-stone-600 transition hover:bg-stone-200 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                                    title="Tampilkan QR Code"
                                >
                                    <QrCode class="h-4 w-4" />
                                </button>
                                <button
                                    @click="deleteTable(table)"
                                    class="flex h-8 w-8 items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 transition hover:bg-rose-500/20"
                                    title="Hapus Meja"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </article>

                        <div
                            v-if="!indoorTables.length"
                            class="col-span-2 rounded-[24px] border border-dashed border-stone-200 bg-stone-50/50 p-8 text-center text-stone-500 dark:border-slate-800/80 dark:text-slate-400"
                        >
                            <Home class="mx-auto mb-2 h-8 w-8 text-stone-400" />
                            <p class="text-sm font-bold">
                                Belum ada meja Indoor
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Kolom Kanan: Outdoor Area -->
                <section
                    class="space-y-4 rounded-[28px] border border-stone-200 bg-white/50 p-6 dark:border-white/10 dark:bg-slate-900/20"
                >
                    <div
                        class="flex items-center justify-between border-b border-stone-200 pb-4 dark:border-white/10"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="rounded-xl bg-orange-500/10 p-2 text-orange-500"
                            >
                                <Trees class="h-5 w-5" />
                            </div>
                            <div>
                                <h3
                                    class="text-lg font-black text-stone-900 dark:text-white"
                                >
                                    Area Outdoor
                                </h3>
                                <p
                                    class="text-[11px] text-stone-500 dark:text-slate-400"
                                >
                                    {{ outdoorTables.length }} meja terdaftar
                                </p>
                            </div>
                        </div>
                        <button
                            @click="openCreateModal('outdoor')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-orange-500/10 px-3 py-1.5 text-xs font-bold text-orange-500 transition hover:bg-orange-500/20"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Tambah Meja
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <article
                            v-for="table in outdoorTables"
                            :key="table.id"
                            class="group relative overflow-hidden rounded-[24px] border border-stone-200 bg-white p-5 transition hover:border-orange-500/30 dark:border-white/10 dark:bg-slate-900/40"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500"
                                    >
                                        {{ table.id.substring(0, 8) }}
                                    </p>
                                    <h3
                                        class="mt-1 text-xl font-black text-stone-900 dark:text-white"
                                    >
                                        {{ table.name }}
                                    </h3>
                                </div>
                                <span
                                    :class="[
                                        'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider',
                                        getStatusBadgeClass(table.status),
                                    ]"
                                >
                                    {{ table.status === 'occupied' ? 'Terisi' : (table.status === 'reserved' ? 'Reservasi' : 'Kosong') }}
                                </span>
                            </div>

                            <div
                                class="mt-4 flex items-center gap-2 text-stone-500 dark:text-slate-400"
                                >
                                <Users class="h-4 w-4" />
                                <span class="text-sm font-semibold"
                                    >{{ table.capacity || '-' }} Orang</span
                                >
                            </div>

                            <div
                                class="mt-6 flex items-center gap-2 opacity-0 transition group-hover:opacity-100"
                            >
                                <button
                                    @click="openEditModal(table)"
                                    class="flex-1 rounded-xl border border-stone-200 bg-stone-100 py-2 text-xs font-bold text-stone-800 transition hover:bg-stone-200 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                                >
                                    <div
                                        class="flex items-center justify-center gap-1.5"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                        Edit
                                    </div>
                                </button>
                                <button
                                    v-if="table.public_qr_url"
                                    @click="openQrModal(table)"
                                    class="flex h-8 w-8 items-center justify-center rounded-xl border border-stone-200 bg-stone-100 text-stone-600 transition hover:bg-stone-200 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                                    title="Tampilkan QR Code"
                                >
                                    <QrCode class="h-4 w-4" />
                                </button>
                                <button
                                    @click="deleteTable(table)"
                                    class="flex h-8 w-8 items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 transition hover:bg-rose-500/20"
                                    title="Hapus Meja"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </article>

                        <div
                            v-if="!outdoorTables.length"
                            class="col-span-2 rounded-[24px] border border-dashed border-stone-200 bg-stone-50/50 p-8 text-center text-stone-500 dark:border-slate-800/80 dark:text-slate-400"
                        >
                            <Trees
                                class="mx-auto mb-2 h-8 w-8 text-stone-400"
                            />
                            <p class="text-sm font-bold">
                                Belum ada meja Outdoor
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Modal CRUD -->
        <teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/75 px-4 py-8 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-lg rounded-[28px] border border-stone-200 bg-white shadow-2xl dark:border-white/10 dark:bg-slate-900"
                >
                    <div
                        class="flex items-start justify-between border-b border-stone-200 px-6 py-5 dark:border-white/10"
                    >
                        <h3
                            class="text-xl font-black text-stone-900 dark:text-white"
                        >
                            {{ modalTitle }}
                        </h3>
                        <button
                            @click="closeModal"
                            class="rounded-xl p-2 text-stone-500 hover:bg-stone-100 hover:text-white dark:hover:bg-white/5"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitTable" class="space-y-5 p-6">
                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-widest text-stone-400 dark:text-slate-500"
                            >
                                Nama Meja
                            </label>
                            <input
                                v-model="tableForm.name"
                                type="text"
                                placeholder="Contoh: Meja 01, VIP 1"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-white"
                                required
                            />
                            <p
                                v-if="tableForm.errors.name"
                                class="mt-1 text-xs text-rose-400"
                            >
                                {{ tableForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-widest text-stone-400 dark:text-slate-500"
                            >
                                Kapasitas (Orang)
                            </label>
                            <input
                                v-model="tableForm.capacity"
                                type="number"
                                min="1"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-white"
                            />
                            <p
                                v-if="tableForm.errors.capacity"
                                class="mt-1 text-xs text-rose-400"
                            >
                                {{ tableForm.errors.capacity }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-widest text-stone-400 dark:text-slate-500"
                            >
                                Area Lokasi
                            </label>
                            <select
                                v-model="tableForm.category"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-stone-100 px-4 py-3 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-white"
                                required
                            >
                                <option value="indoor">
                                    Indoor (Dalam Ruangan)
                                </option>
                                <option value="outdoor">
                                    Outdoor (Luar Ruangan)
                                </option>
                            </select>
                            <p
                                v-if="tableForm.errors.category"
                                class="mt-1 text-xs text-rose-400"
                            >
                                {{ tableForm.errors.category }}
                            </p>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 rounded-2xl border border-stone-200 bg-stone-100 py-3 text-sm font-bold text-stone-600 transition hover:bg-stone-200 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="tableForm.processing"
                                class="flex-1 rounded-2xl bg-orange-500 py-3 text-sm font-bold text-stone-950 transition hover:bg-orange-400 disabled:opacity-50"
                            >
                                {{
                                    tableForm.processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Meja'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </teleport>

        <!-- Modal QR Code -->
        <teleport to="body">
            <div
                v-if="isQrModalOpen && selectedQrTable"
                class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/75 px-4 py-8 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-sm space-y-5 rounded-[28px] border border-stone-200 bg-white p-6 text-center shadow-2xl dark:border-white/10 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-stone-200 pb-3 dark:border-white/10"
                    >
                        <h3
                            class="text-lg font-black text-stone-900 dark:text-white"
                        >
                            QR Code {{ selectedQrTable.name }}
                        </h3>
                        <button
                            @click="closeQrModal"
                            class="rounded-xl p-2 text-stone-500 hover:bg-stone-100 dark:hover:bg-white/5"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div
                        class="flex flex-col items-center justify-center rounded-2xl border border-stone-200 bg-white p-4"
                    >
                        <img
                            :src="tableQrImage"
                            alt="Table QR Code"
                            class="h-60 w-60 object-contain"
                        />
                        <p
                            class="mt-3 select-all break-all text-[10px] font-semibold text-stone-500"
                        >
                            {{ selectedQrTable.public_qr_url }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 pt-2">
                        <a
                            :href="tableQrImage + '&download=1'"
                            download
                            target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-orange-500 py-3 text-sm font-bold text-stone-950 transition hover:bg-orange-400"
                        >
                            <Download class="h-4 w-4" />
                            Unduh QR Code
                        </a>
                        <a
                            v-if="selectedQrTable.public_qr_url"
                            :href="selectedQrTable.public_qr_url"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-stone-200 bg-stone-100 py-3 text-sm font-bold text-stone-800 transition hover:bg-stone-200 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                        >
                            <ExternalLink class="h-4 w-4" />
                            Buka Link Menu
                        </a>
                    </div>
                </div>
            </div>
        </teleport>

        <AlertDialog
            :show="alertDialog.show"
            :title="alertDialog.title"
            :message="alertDialog.message"
            :type="alertDialog.type"
            @confirm="alertDialog.onConfirm"
            @cancel="closeAlertDialog"
        />
    </AuthenticatedLayout>
</template>
