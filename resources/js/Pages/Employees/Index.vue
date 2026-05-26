<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarDays,
    Pencil,
    Plus,
    Search,
    ShieldCheck,
    UserCog,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface OutletOption {
    id: string;
    name: string;
}

interface RoleOption {
    id: string;
    outlet_id: string;
    name: string;
    type: string;
}

interface EmployeeRow {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    join_date?: string | null;
    is_active: boolean;
    role?: {
        id: string;
        name: string;
        type: string;
    } | null;
    outlet?: {
        id: string;
        name: string;
    } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    employees: {
        data: EmployeeRow[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total: number;
        active: number;
        supervisor: number;
        kasir: number;
    };
    roleBreakdown: Array<{
        type: string;
        name: string;
        total_users: number | string;
    }>;
    filters: {
        search?: string;
        status?: string;
        role_type?: string;
        outlet_id?: string;
        per_page?: number;
    };
    referenceData: {
        outlets: OutletOption[];
        roles: RoleOption[];
    };
    canManage: boolean;
    success?: string | null;
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const roleType = ref(props.filters.role_type || '');
const outletId = ref(props.filters.outlet_id || '');
const modalMode = ref<'create' | 'edit' | null>(null);
const selectedEmployee = ref<EmployeeRow | null>(null);

const employeeForm = useForm<{
    name: string;
    email: string;
    phone: string;
    outlet_id: string;
    role_id: string;
    join_date: string;
    password: string;
    approval_pin: string;
    is_active: boolean;
}>({
    name: '',
    email: '',
    phone: '',
    outlet_id: props.referenceData.outlets[0]?.id || '',
    role_id: '',
    join_date: '',
    password: '',
    approval_pin: '',
    is_active: true,
});

const summaryCards = computed(() => [
    {
        label: 'Total Karyawan',
        value: props.summary.total,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: Users,
    },
    {
        label: 'Aktif',
        value: props.summary.active,
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: BadgeCheck,
    },
    {
        label: 'Supervisor',
        value: props.summary.supervisor,
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: ShieldCheck,
    },
    {
        label: 'Kasir',
        value: props.summary.kasir,
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: UserCog,
    },
]);

const roleTypeOptions = computed(() => {
    const uniqueTypes = new Map<string, { value: string; label: string }>();

    props.referenceData.roles.forEach((role) => {
        if (!uniqueTypes.has(role.type)) {
            uniqueTypes.set(role.type, {
                value: role.type,
                label: role.name,
            });
        }
    });

    return Array.from(uniqueTypes.values());
});

const filteredRoleOptions = computed(() => {
    const selectedOutletId = employeeForm.outlet_id;

    return props.referenceData.roles.filter((role) => {
        if (!selectedOutletId) return true;

        return role.outlet_id === selectedOutletId;
    });
});

const modalTitle = computed(() => {
    return modalMode.value === 'edit' ? 'Edit Karyawan' : 'Tambah Karyawan';
});

const isModalOpen = computed(() => modalMode.value !== null);

const formatDate = (value?: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(new Date(value));
};

const submitFilters = () => {
    router.get(
        route('employees.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            role_type: roleType.value || undefined,
            outlet_id: outletId.value || undefined,
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
    roleType.value = '';
    outletId.value = '';
    submitFilters();
};

const resetEmployeeForm = () => {
    employeeForm.reset();
    employeeForm.name = '';
    employeeForm.email = '';
    employeeForm.phone = '';
    employeeForm.outlet_id = props.referenceData.outlets[0]?.id || '';
    employeeForm.role_id = '';
    employeeForm.join_date = new Date().toISOString().slice(0, 10);
    employeeForm.password = '';
    employeeForm.approval_pin = '';
    employeeForm.is_active = true;
    employeeForm.clearErrors();
};

const openCreateModal = () => {
    if (!props.canManage) return;

    selectedEmployee.value = null;
    modalMode.value = 'create';
    resetEmployeeForm();
};

const openEditModal = (employee: EmployeeRow) => {
    if (!props.canManage) return;

    selectedEmployee.value = employee;
    modalMode.value = 'edit';
    resetEmployeeForm();
    employeeForm.name = employee.name;
    employeeForm.email = employee.email;
    employeeForm.phone = employee.phone || '';
    employeeForm.outlet_id = employee.outlet?.id || props.referenceData.outlets[0]?.id || '';
    employeeForm.role_id = employee.role?.id || '';
    employeeForm.join_date = employee.join_date ? employee.join_date.slice(0, 10) : new Date().toISOString().slice(0, 10);
    employeeForm.is_active = Boolean(employee.is_active);
    employeeForm.password = '';
    employeeForm.approval_pin = '';
};

const closeModal = () => {
    modalMode.value = null;
    selectedEmployee.value = null;
    resetEmployeeForm();
};

const submitEmployee = () => {
    if (modalMode.value === 'edit' && selectedEmployee.value) {
        employeeForm.patch(route('employees.update', selectedEmployee.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });

        return;
    }

    employeeForm.post(route('employees.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const getStatusClass = (isActive: boolean) => {
    return isActive
        ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
        : 'border-slate-500/20 bg-slate-500/10 text-slate-300';
};
</script>

<template>
    <Head title="Data Karyawan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <Users class="h-3.5 w-3.5" />
                    Menu #36 Data Karyawan
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        Data Karyawan
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-slate-400">
                        Kelola data karyawan, role, outlet, password awal, PIN approval, dan status aktif. Supervisor bisa lihat, owner bisa full manage.
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
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                {{ card.label }}
                            </p>
                            <p class="mt-3 text-3xl font-black" :class="card.tone">
                                {{ card.value }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3 text-white">
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Cari karyawan</span>
                            <div class="flex items-center gap-2 rounded-2xl border border-white/10 bg-slate-900/80 px-3">
                                <Search class="h-4 w-4 text-slate-500" />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Nama, email, phone"
                                    class="w-full border-0 bg-transparent px-0 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:ring-0"
                                    @keyup.enter="submitFilters"
                                />
                            </div>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Status</span>
                            <select
                                v-model="status"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Role</span>
                            <select
                                v-model="roleType"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua role</option>
                                <option
                                    v-for="role in roleTypeOptions"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Outlet</span>
                            <select
                                v-model="outletId"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-3 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Semua outlet</option>
                                <option
                                    v-for="outlet in referenceData.outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/5"
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
                            v-if="canManage"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl border border-orange-400/20 bg-orange-500/10 px-4 py-3 text-sm font-bold text-orange-200 transition hover:bg-orange-500/15"
                            @click="openCreateModal"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Karyawan
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 lg:grid-cols-[1.5fr_1fr]">
                <div class="rounded-3xl border border-white/10 bg-slate-950/70 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                Daftar Karyawan
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Menampilkan {{ employees.from ?? 0 }} - {{ employees.to ?? 0 }} dari {{ employees.total }} karyawan.
                            </p>
                        </div>
                    </div>

                    <div v-if="!employees.data.length" class="px-5 py-10 text-center text-sm text-slate-400">
                        Belum ada data karyawan pada filter ini.
                    </div>

                    <div v-else class="divide-y divide-white/10">
                        <article
                            v-for="employee in employees.data"
                            :key="employee.id"
                            class="grid gap-4 px-5 py-5 xl:grid-cols-[1fr_0.8fr_0.9fr_auto]"
                        >
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-black text-white">{{ employee.name }}</h3>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                        :class="getStatusClass(employee.is_active)"
                                    >
                                        {{ employee.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-300">{{ employee.email }}</p>
                                <p class="text-xs text-slate-500">{{ employee.phone || '-' }}</p>
                            </div>

                            <div class="space-y-2 text-sm text-slate-300">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    Role & Outlet
                                </p>
                                <p>{{ employee.role?.name || '-' }}</p>
                                <p class="text-slate-500">{{ employee.outlet?.name || '-' }}</p>
                            </div>

                            <div class="space-y-2 text-sm text-slate-300">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    Join & Approval
                                </p>
                                <p>{{ formatDate(employee.join_date) }}</p>
                                <p class="text-slate-500">PIN approval tersimpan aman</p>
                            </div>

                            <div v-if="canManage" class="flex items-start justify-end">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-white/10 px-3 py-2 text-sm font-semibold text-slate-200 transition hover:border-orange-400/30 hover:bg-orange-500/10 hover:text-orange-100"
                                    @click="openEditModal(employee)"
                                >
                                    <Pencil class="h-4 w-4" />
                                    Edit
                                </button>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="employees.links.length > 3"
                        class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 px-5 py-4"
                    >
                        <p class="text-xs text-slate-500">
                            List karyawan dipaginasi agar data owner tetap ringan.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Link
                                v-for="link in employees.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                                :class="link.active
                                    ? 'border-orange-400/30 bg-orange-500/15 text-orange-100'
                                    : 'border-white/10 text-slate-300 hover:bg-white/5'"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3 text-orange-200">
                            <CalendarDays class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-300">
                                Breakdown Role
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Fondasi untuk menu shift, absensi, dan approval berikutnya.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article
                            v-for="role in roleBreakdown"
                            :key="role.type"
                            class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3"
                        >
                            <div>
                                <p class="text-sm font-bold text-white">{{ role.name }}</p>
                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">{{ role.type }}</p>
                            </div>
                            <span class="text-lg font-black text-orange-300">
                                {{ Number(role.total_users || 0) }}
                            </span>
                        </article>
                    </div>
                </div>
            </section>
        </div>

        <div
            v-if="isModalOpen && canManage"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6 backdrop-blur-sm"
        >
            <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-3xl border border-white/10 bg-slate-950 p-6 shadow-[0_30px_120px_rgba(15,23,42,0.6)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-black text-white">{{ modalTitle }}</h3>
                        <p class="mt-1 text-sm text-slate-400">
                            Isi data login, role, outlet, dan PIN approval karyawan.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-2xl border border-white/10 p-2 text-slate-400 transition hover:border-white/20 hover:text-white"
                        @click="closeModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitEmployee">
                    <section class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Nama</span>
                            <input
                                v-model="employeeForm.name"
                                type="text"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="employeeForm.errors.name" class="mt-2 text-xs text-rose-300">{{ employeeForm.errors.name }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Email Login</span>
                            <input
                                v-model="employeeForm.email"
                                type="email"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="employeeForm.errors.email" class="mt-2 text-xs text-rose-300">{{ employeeForm.errors.email }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">No. HP</span>
                            <input
                                v-model="employeeForm.phone"
                                type="text"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="employeeForm.errors.phone" class="mt-2 text-xs text-rose-300">{{ employeeForm.errors.phone }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tanggal Bergabung</span>
                            <input
                                v-model="employeeForm.join_date"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            />
                            <p v-if="employeeForm.errors.join_date" class="mt-2 text-xs text-rose-300">{{ employeeForm.errors.join_date }}</p>
                        </label>
                    </section>

                    <section class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Outlet</span>
                            <select
                                v-model="employeeForm.outlet_id"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option
                                    v-for="outlet in referenceData.outlets"
                                    :key="outlet.id"
                                    :value="outlet.id"
                                >
                                    {{ outlet.name }}
                                </option>
                            </select>
                            <p v-if="employeeForm.errors.outlet_id" class="mt-2 text-xs text-rose-300">{{ employeeForm.errors.outlet_id }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Role</span>
                            <select
                                v-model="employeeForm.role_id"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white focus:border-orange-400 focus:outline-none focus:ring-0"
                            >
                                <option value="">Pilih role</option>
                                <option
                                    v-for="role in filteredRoleOptions"
                                    :key="role.id"
                                    :value="role.id"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                            <p v-if="employeeForm.errors.role_id" class="mt-2 text-xs text-rose-300">{{ employeeForm.errors.role_id }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                {{ modalMode === 'edit' ? 'Password Baru' : 'Password Awal' }}
                            </span>
                            <input
                                v-model="employeeForm.password"
                                type="password"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                                :placeholder="modalMode === 'edit' ? 'Kosongkan jika tidak diganti' : 'Minimal 8 karakter'"
                            />
                            <p v-if="employeeForm.errors.password" class="mt-2 text-xs text-rose-300">{{ employeeForm.errors.password }}</p>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                {{ modalMode === 'edit' ? 'Reset PIN Approval' : 'PIN Approval' }}
                            </span>
                            <input
                                v-model="employeeForm.approval_pin"
                                type="password"
                                inputmode="numeric"
                                maxlength="6"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-0"
                                :placeholder="modalMode === 'edit' ? 'Kosongkan jika tidak diganti' : '6 digit angka'"
                            />
                            <p v-if="employeeForm.errors.approval_pin" class="mt-2 text-xs text-rose-300">{{ employeeForm.errors.approval_pin }}</p>
                        </label>
                    </section>

                    <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.02] p-4">
                        <input
                            v-model="employeeForm.is_active"
                            type="checkbox"
                            class="mt-1 rounded border-white/20 bg-slate-900 text-orange-500 focus:ring-orange-400"
                        />
                        <span>
                            <span class="block text-sm font-semibold text-white">Karyawan aktif</span>
                            <span class="mt-1 block text-xs text-slate-500">
                                Nonaktifkan untuk karyawan resign atau tidak boleh login lagi. Data historis tetap tersimpan.
                            </span>
                        </span>
                    </label>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-white/10 pt-5">
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/5"
                            @click="closeModal"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="employeeForm.processing"
                        >
                            {{ employeeForm.processing ? 'Menyimpan...' : 'Simpan Karyawan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
