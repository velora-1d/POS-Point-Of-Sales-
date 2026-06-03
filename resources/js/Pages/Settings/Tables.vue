<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    Pencil,
    Plus,
    Settings,
    Trash2,
    Users,
    X,
} from '@lucide/vue';
import AlertDialog from '@/Components/AlertDialog.vue';
import { computed, ref } from 'vue';

interface TableRow {
    id: string;
    name: string;
    capacity: number | null;
    status: string;
    is_active: boolean;
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

const tableForm = useForm({
    name: '',
    capacity: 2,
});

const isModalOpen = computed(() => modalMode.value !== null);
const modalTitle = computed(() => (modalMode.value === 'edit' ? 'Edit Meja' : 'Tambah Meja Baru'));

function openCreateModal() {
    modalMode.value = 'create';
    selectedTable.value = null;
    tableForm.reset();
    tableForm.clearErrors();
}

function openEditModal(table: TableRow) {
    modalMode.value = 'edit';
    selectedTable.value = table;
    tableForm.name = table.name;
    tableForm.capacity = table.capacity || 2;
    tableForm.clearErrors();
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
        tableForm.patch(route('settings.tables.update', selectedTable.value.id), {
            onSuccess: () => closeModal(),
        });
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
                        <h2 class="text-2xl font-black tracking-tight text-stone-900 dark:text-white">
Daftar Meja Outlet
                        </h2>
                        <p class="mt-1 text-xs text-stone-500 dark:text-slate-400">
                            Tambah, edit, atau hapus daftar meja yang tersedia di outlet Anda.
                        </p>
                    </div>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-4 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-orange-400"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Meja
                    </button>
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

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <article
                    v-for="table in tables"
                    :key="table.id"
                    class="group relative overflow-hidden rounded-[24px] border border-stone-200 dark:border-white/10 bg-stone-50 dark:bg-slate-900/40 p-5 transition hover:border-orange-500/30"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400 dark:text-slate-500">
                                {{ table.id.substring(0, 8) }}
                            </p>
                            <h3 class="mt-1 text-xl font-black text-stone-900 dark:text-white">
                                {{ table.name }}
                            </h3>
                        </div>
                        <span
                            :class="[
                                'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider',
                                getStatusBadgeClass(table.status),
                            ]"
                        >
                            {{ table.status }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-2 text-stone-500 dark:text-slate-400">
                        <Users class="h-4 w-4" />
                        <span class="text-sm font-semibold">{{ table.capacity || '-' }} Pax</span>
                    </div>

                    <div class="mt-6 flex items-center gap-2 opacity-0 transition group-hover:opacity-100">
                        <button
                            @click="openEditModal(table)"
                            class="flex-1 rounded-xl border border-stone-200 dark:border-white/10 bg-stone-100 dark:bg-white/5 py-2 text-xs font-bold text-stone-800 dark:text-slate-200 transition hover:bg-stone-200 dark:bg-white/10"
                        >
                            <div class="flex items-center justify-center gap-1.5">
                                <Pencil class="h-3.5 w-3.5" />
                                Edit
                            </div>
                        </button>
                        <button
                            @click="deleteTable(table)"
                            class="flex h-8 w-8 items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 transition hover:bg-rose-500/20"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </article>

                <button
                    @click="openCreateModal"
                    class="flex min-h-[160px] flex-col items-center justify-center rounded-[24px] border border-dashed border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-950/20 p-5 text-stone-400 dark:text-slate-500 transition hover:border-orange-500/40 hover:text-orange-300"
                >
                    <Plus class="mb-2 h-8 w-8" />
                    <span class="text-sm font-bold">Tambah Meja Baru</span>
                </button>
            </div>
        </div>

        <!-- Modal CRUD -->
        <teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-slate-950/75 px-4 py-8 backdrop-blur-sm"
            >
                <div class="w-full max-w-lg rounded-[28px] border border-stone-200 dark:border-white/10 bg-white dark:bg-slate-900 shadow-2xl">
                    <div class="flex items-start justify-between border-b border-stone-200 dark:border-white/10 px-6 py-5">
                        <h3 class="text-xl font-black text-stone-900 dark:text-white">
                            {{ modalTitle }}
                        </h3>
                        <button
                            @click="closeModal"
                            class="rounded-xl p-2 text-stone-500 dark:text-slate-400 hover:bg-stone-100 dark:bg-white/5 hover:text-stone-900 dark:text-white"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitTable" class="p-6 space-y-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-stone-400 dark:text-slate-500">
                                Nama Meja
                            </label>
                            <input
                                v-model="tableForm.name"
                                type="text"
                                placeholder="Contoh: Meja 01, VIP 1"
                                class="mt-2 w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-100 dark:bg-white/5 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                                required
                            />
                            <p v-if="tableForm.errors.name" class="mt-1 text-xs text-rose-400">
                                {{ tableForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-stone-400 dark:text-slate-500">
                                Kapasitas (Pax)
                            </label>
                            <input
                                v-model="tableForm.capacity"
                                type="number"
                                min="1"
                                class="mt-2 w-full rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-100 dark:bg-white/5 px-4 py-3 text-sm text-stone-900 dark:text-white focus:border-orange-500 focus:outline-none"
                            />
                            <p v-if="tableForm.errors.capacity" class="mt-1 text-xs text-rose-400">
                                {{ tableForm.errors.capacity }}
                            </p>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 rounded-2xl border border-stone-200 dark:border-white/10 bg-stone-100 dark:bg-white/5 py-3 text-sm font-bold text-stone-600 dark:text-slate-300 transition hover:bg-stone-200 dark:bg-white/10"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="tableForm.processing"
                                class="flex-1 rounded-2xl bg-orange-500 py-3 text-sm font-bold text-slate-950 transition hover:bg-orange-400 disabled:opacity-50"
                            >
                                {{ tableForm.processing ? 'Menyimpan...' : 'Simpan Meja' }}
                            </button>
                        </div>
                    </form>
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
