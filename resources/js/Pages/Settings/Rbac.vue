<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    KeyRound,
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
    is_active: boolean;
}

interface RoleRow {
    id: string;
    name: string;
    type: string;
    type_label: string;
    is_active: boolean;
    users_count: number;
    permission_names: string[];
    permission_count: number;
    is_locked: boolean;
    is_custom: boolean;
}

interface EmployeeRow {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    is_active: boolean;
    role_id: string;
    role_name?: string | null;
    role_type?: string | null;
    join_date?: string | null;
}

interface PermissionGroup {
    key: string;
    label: string;
    permissions: Array<{
        id: string;
        name: string;
        description: string;
    }>;
}

const props = defineProps<{
    outlets: OutletOption[];
    selectedOutlet: OutletOption | null;
    summary: {
        total_roles: number;
        active_roles: number;
        custom_roles: number;
        total_users: number;
        permission_catalog_count: number;
    };
    roles: RoleRow[];
    employees: EmployeeRow[];
    permissionGroups: PermissionGroup[];
    roleTypeOptions: Array<{
        value: string;
        label: string;
    }>;
    defaultPermissionMatrix: Record<string, string[]>;
    filters: {
        outlet_id?: string | null;
    };
    success?: string | null;
}>();

const outletId = ref(props.filters.outlet_id || props.selectedOutlet?.id || '');
const roleSearch = ref('');
const employeeSearch = ref('');
const modalMode = ref<'create' | 'edit' | null>(null);
const selectedRole = ref<RoleRow | null>(null);

const roleForm = useForm<{
    outlet_id: string;
    name: string;
    type: string;
    permissions: string[];
    is_active: boolean;
}>({
    outlet_id: outletId.value,
    name: '',
    type: props.roleTypeOptions[0]?.value || 'supervisor',
    permissions: [],
    is_active: true,
});

const summaryCards = computed(() => [
    {
        label: 'Total Role',
        value: props.summary.total_roles,
        helper: `${props.summary.active_roles} aktif`,
        tone: 'text-white',
        surface: 'border-white/10 bg-white/[0.03]',
        icon: ShieldCheck,
    },
    {
        label: 'Role Custom',
        value: props.summary.custom_roles,
        helper: 'Role turunan di outlet aktif',
        tone: 'text-amber-300',
        surface: 'border-amber-400/15 bg-amber-500/10',
        icon: KeyRound,
    },
    {
        label: 'User Terkait',
        value: props.summary.total_users,
        helper: 'Karyawan di outlet terpilih',
        tone: 'text-sky-300',
        surface: 'border-sky-400/15 bg-sky-500/10',
        icon: Users,
    },
    {
        label: 'Permission Catalog',
        value: props.summary.permission_catalog_count,
        helper: 'Permission tersimpan untuk RBAC',
        tone: 'text-emerald-300',
        surface: 'border-emerald-400/15 bg-emerald-500/10',
        icon: UserCog,
    },
]);

const filteredRoles = computed(() => {
    const keyword = roleSearch.value.trim().toLowerCase();

    if (!keyword) return props.roles;

    return props.roles.filter((role) => {
        return role.name.toLowerCase().includes(keyword)
            || role.type_label.toLowerCase().includes(keyword);
    });
});

const filteredEmployees = computed(() => {
    const keyword = employeeSearch.value.trim().toLowerCase();

    if (!keyword) return props.employees;

    return props.employees.filter((employee) => {
        return employee.name.toLowerCase().includes(keyword)
            || employee.email.toLowerCase().includes(keyword)
            || (employee.role_name || '').toLowerCase().includes(keyword);
    });
});

const isModalOpen = computed(() => modalMode.value !== null);
const modalTitle = computed(() => modalMode.value === 'edit' ? 'Edit Role' : 'Tambah Role Custom');
const activeRoleOptions = computed(() => props.roles.filter((role) => role.is_active));

function statusBadgeClass(isActive: boolean) {
    return isActive
        ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
        : 'border-amber-400/20 bg-amber-500/10 text-amber-300';
}

function submitOutletFilter() {
    router.get(
        route('settings.rbac.index'),
        {
            outlet_id: outletId.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function resetRoleForm() {
    roleForm.clearErrors();
    roleForm.outlet_id = outletId.value || props.selectedOutlet?.id || '';
    roleForm.name = '';
    roleForm.type = props.roleTypeOptions[0]?.value || 'supervisor';
    roleForm.permissions = [...(props.defaultPermissionMatrix[roleForm.type] || [])];
    roleForm.is_active = true;
}

function applyDefaultPermissions() {
    roleForm.permissions = [...(props.defaultPermissionMatrix[roleForm.type] || [])];
}

function openCreateModal() {
    selectedRole.value = null;
    modalMode.value = 'create';
    resetRoleForm();
}

function openEditModal(role: RoleRow) {
    if (role.is_locked) return;

    selectedRole.value = role;
    modalMode.value = 'edit';
    roleForm.clearErrors();
    roleForm.outlet_id = outletId.value || props.selectedOutlet?.id || '';
    roleForm.name = role.name;
    roleForm.type = role.type;
    roleForm.permissions = [...role.permission_names];
    roleForm.is_active = role.is_active;
}

function closeModal() {
    modalMode.value = null;
    selectedRole.value = null;
    resetRoleForm();
}

function togglePermission(permissionName: string, checked: boolean) {
    const next = new Set(roleForm.permissions);

    if (checked) {
        next.add(permissionName);
    } else {
        next.delete(permissionName);
    }

    roleForm.permissions = Array.from(next.values());
}

function handlePermissionChange(permissionName: string, event: Event) {
    const target = event.target as HTMLInputElement;
    togglePermission(permissionName, target.checked);
}

function submitRole() {
    roleForm.outlet_id = outletId.value || props.selectedOutlet?.id || '';

    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (modalMode.value === 'edit' && selectedRole.value) {
        roleForm.patch(route('settings.rbac.update', selectedRole.value.id), options);
        return;
    }

    roleForm.post(route('settings.rbac.store'), options);
}

function updateEmployeeRole(employee: EmployeeRow, event: Event) {
    const target = event.target as HTMLSelectElement;
    const nextRoleId = target.value;

    if (!nextRoleId || nextRoleId === employee.role_id) {
        return;
    }

    const selectedOption = activeRoleOptions.value.find((role) => role.id === nextRoleId);
    const confirmed = window.confirm(
        `Ubah role ${employee.name} menjadi ${selectedOption?.name || 'role baru'}? User perlu login ulang agar akses baru sinkron.`,
    );

    if (!confirmed) {
        target.value = employee.role_id;
        return;
    }

    router.patch(
        route('settings.rbac.users.assign-role', employee.id),
        {
            outlet_id: outletId.value || props.selectedOutlet?.id || '',
            role_id: nextRoleId,
        },
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="User & RBAC" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div
                    class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300"
                >
                    <ShieldCheck class="h-3.5 w-3.5" />
                    Menu #55 User & RBAC
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">
                        User & RBAC
                    </h2>
                    <p class="mt-1 max-w-3xl text-xs text-slate-400">
                        Kelola role custom per outlet, atur permission matrix, dan reassign role user aktif
                        dari satu dashboard owner.
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

            <section class="rounded-[26px] border border-sky-500/15 bg-sky-500/[0.08] p-4 text-sm text-sky-100">
                <p class="font-semibold">
                    Permission di halaman ini mendefinisikan permukaan akses role.
                </p>
                <p class="mt-1 text-xs text-sky-100/80">
                    Beberapa aksi seperti refund, void, dan cancel order tetap bisa memerlukan approval flow
                    walaupun permission role-nya aktif. Detail threshold approval dilanjutkan di menu `#61`.
                </p>
            </section>

            <section class="rounded-[26px] border border-white/10 bg-slate-950/40 p-4">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr),auto]">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                            Outlet Scope
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Role dan assignment user di halaman ini selalu mengikuti outlet yang dipilih.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="outletId"
                            class="w-full min-w-[260px] rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                            @change="submitOutletFilter"
                        >
                            <option
                                v-for="outlet in outlets"
                                :key="outlet.id"
                                :value="outlet.id"
                            >
                                {{ outlet.name }}{{ outlet.is_active ? '' : ' (nonaktif)' }}
                            </option>
                        </select>
                        <Link
                            :href="route('employees.index', { outlet_id: outletId || undefined })"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-slate-200 transition hover:bg-white/[0.05]"
                        >
                            Buka Karyawan
                        </Link>
                    </div>
                </div>
            </section>

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
                            <p class="mt-1 text-xs text-slate-400">
                                {{ stat.helper }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <component :is="stat.icon" class="h-5 w-5 text-slate-200" />
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.15fr,0.85fr]">
                <div class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Role & Permissions
                            </p>
                            <h3 class="mt-1 text-xl font-black text-white">
                                Role outlet {{ selectedOutlet?.name || '-' }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-orange-400"
                            @click="openCreateModal"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Role
                        </button>
                    </div>

                    <label class="relative mt-4 block">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                        <input
                            v-model="roleSearch"
                            type="text"
                            placeholder="Cari role..."
                            class="w-full rounded-2xl border border-white/10 bg-white/[0.03] py-3 pl-10 pr-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                        >
                    </label>

                    <div
                        v-if="filteredRoles.length"
                        class="mt-4 space-y-3"
                    >
                        <article
                            v-for="role in filteredRoles"
                            :key="role.id"
                            class="rounded-[24px] border border-white/10 bg-white/[0.03] p-4"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-lg font-black text-white">
                                            {{ role.name }}
                                        </h4>
                                        <span
                                            class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                            :class="statusBadgeClass(role.is_active)"
                                        >
                                            {{ role.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                        <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-300">
                                            Base {{ role.type_label }}
                                        </span>
                                        <span
                                            v-if="role.is_locked"
                                            class="rounded-full border border-sky-400/20 bg-sky-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-sky-200"
                                        >
                                            System
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-400">
                                        {{ role.users_count }} user • {{ role.permission_count }} permission aktif
                                    </p>
                                </div>

                                <button
                                    v-if="!role.is_locked"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-white/[0.05]"
                                    @click="openEditModal(role)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                    Edit
                                </button>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-for="permissionName in role.permission_names.slice(0, 8)"
                                    :key="`${role.id}-${permissionName}`"
                                    class="rounded-full border border-orange-400/20 bg-orange-500/10 px-2.5 py-1 text-[11px] font-medium text-orange-100"
                                >
                                    {{ permissionName }}
                                </span>
                                <span
                                    v-if="role.permission_names.length > 8"
                                    class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px] text-slate-300"
                                >
                                    +{{ role.permission_names.length - 8 }} permission
                                </span>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-[24px] border border-dashed border-white/10 bg-white/[0.02] px-5 py-10 text-center"
                    >
                        <p class="text-lg font-semibold text-white">
                            Belum ada role untuk outlet ini.
                        </p>
                    </div>
                </div>

                <div class="space-y-5">
                    <section class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Permission Catalog
                            </p>
                            <h3 class="mt-1 text-xl font-black text-white">
                                Modul akses yang tersedia
                            </h3>
                        </div>

                        <div class="mt-4 space-y-3">
                            <article
                                v-for="group in permissionGroups"
                                :key="group.key"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-black text-white">
                                        {{ group.label }}
                                    </h4>
                                    <span class="text-xs text-slate-500">
                                        {{ group.permissions.length }} permission
                                    </span>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <div
                                        v-for="permission in group.permissions.slice(0, 3)"
                                        :key="permission.id"
                                        class="rounded-xl border border-white/10 bg-slate-950/35 px-3 py-2"
                                    >
                                        <p class="text-xs font-semibold text-orange-200">
                                            {{ permission.name }}
                                        </p>
                                        <p class="mt-1 text-[11px] text-slate-400">
                                            {{ permission.description }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>

                    <section class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                    Assign Role User
                                </p>
                                <h3 class="mt-1 text-xl font-black text-white">
                                    Reassign akses outlet
                                </h3>
                            </div>
                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-[11px] text-slate-300">
                                Login ulang diperlukan
                            </span>
                        </div>

                        <label class="relative mt-4 block">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                            <input
                                v-model="employeeSearch"
                                type="text"
                                placeholder="Cari user..."
                                class="w-full rounded-2xl border border-white/10 bg-white/[0.03] py-3 pl-10 pr-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                            >
                        </label>

                        <div class="mt-4 space-y-3">
                            <article
                                v-for="employee in filteredEmployees"
                                :key="employee.id"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm font-black text-white">
                                                {{ employee.name }}
                                            </h4>
                                            <span
                                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                                                :class="statusBadgeClass(employee.is_active)"
                                            >
                                                {{ employee.is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ employee.email }} • {{ employee.role_name || '-' }}
                                        </p>
                                    </div>

                                    <select
                                        :value="employee.role_id"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-950/60 px-3 py-2.5 text-sm text-white outline-none transition focus:border-orange-400/40 md:w-[220px]"
                                        @change="updateEmployeeRole(employee, $event)"
                                    >
                                        <option
                                            v-for="role in activeRoleOptions"
                                            :key="role.id"
                                            :value="role.id"
                                        >
                                            {{ role.name }} ({{ role.type_label }})
                                        </option>
                                    </select>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>
            </section>
        </div>

        <teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 px-4 py-8 backdrop-blur-sm"
            >
                <div class="w-full max-w-5xl rounded-[28px] border border-white/10 bg-slate-900 shadow-2xl shadow-black/40">
                    <div class="flex items-start justify-between border-b border-white/10 px-6 py-5">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                Role Builder
                            </p>
                            <h3 class="mt-1 text-xl font-black text-white">
                                {{ modalTitle }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-2 text-slate-300 transition hover:bg-white/[0.06]"
                            @click="closeModal"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form
                        class="space-y-5 px-6 py-5"
                        @submit.prevent="submitRole"
                    >
                        <div class="grid gap-4 md:grid-cols-[1.1fr,0.9fr]">
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-300">Nama role</span>
                                <input
                                    v-model="roleForm.name"
                                    type="text"
                                    class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                    placeholder="Kasir Senior"
                                >
                                <p
                                    v-if="roleForm.errors.name"
                                    class="mt-2 text-xs text-rose-300"
                                >
                                    {{ roleForm.errors.name }}
                                </p>
                            </label>

                            <div class="space-y-4">
                                <label class="block">
                                    <span class="text-xs font-semibold text-slate-300">Base role type</span>
                                    <select
                                        v-model="roleForm.type"
                                        class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                                    >
                                        <option
                                            v-for="option in roleTypeOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="roleForm.errors.type"
                                        class="mt-2 text-xs text-rose-300"
                                    >
                                        {{ roleForm.errors.type }}
                                    </p>
                                </label>

                                <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                    <input
                                        v-model="roleForm.is_active"
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-400"
                                    >
                                    <span class="text-sm text-slate-200">Role aktif dan bisa dipakai assign user</span>
                                </label>

                                <button
                                    type="button"
                                    class="w-full rounded-2xl border border-orange-400/30 bg-orange-500/10 px-4 py-3 text-sm font-semibold text-orange-200 transition hover:bg-orange-500/15"
                                    @click="applyDefaultPermissions"
                                >
                                    Reset ke default permission base role
                                </button>
                            </div>
                        </div>

                        <div class="rounded-[24px] border border-white/10 bg-slate-950/35 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">
                                        Permission Matrix
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Centang permission yang ingin aktif pada role ini.
                                    </p>
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs text-slate-300">
                                    {{ roleForm.permissions.length }} terpilih
                                </span>
                            </div>

                            <p
                                v-if="roleForm.errors.permissions"
                                class="mt-3 text-xs text-rose-300"
                            >
                                {{ roleForm.errors.permissions }}
                            </p>

                            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                <section
                                    v-for="group in permissionGroups"
                                    :key="group.key"
                                    class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                                >
                                    <h4 class="text-sm font-black text-white">
                                        {{ group.label }}
                                    </h4>
                                    <div class="mt-3 space-y-3">
                                        <label
                                            v-for="permission in group.permissions"
                                            :key="permission.id"
                                            class="flex items-start gap-3 rounded-xl border border-white/10 bg-slate-950/35 px-3 py-3"
                                        >
                                            <input
                                                :checked="roleForm.permissions.includes(permission.name)"
                                                type="checkbox"
                                                class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-400"
                                                @change="handlePermissionChange(permission.name, $event)"
                                            >
                                            <span>
                                                <span class="block text-sm font-semibold text-white">
                                                    {{ permission.name }}
                                                </span>
                                                <span class="mt-1 block text-xs text-slate-400">
                                                    {{ permission.description }}
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                </section>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/[0.05]"
                                @click="closeModal"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-2xl bg-orange-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="roleForm.processing"
                            >
                                {{ roleForm.processing ? 'Menyimpan...' : 'Simpan Role' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </teleport>
    </AuthenticatedLayout>
</template>
