<script setup lang="ts">
import AlertDialog from '@/Components/AlertDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Eye,
    KeyRound,
    Pencil,
    Plus,
    RotateCcw,
    Save,
    Search,
    ShieldCheck,
    Trash2,
    UserCog,
    Users,
    X,
} from '@lucide/vue';
import { type Component, computed, ref } from 'vue';

// ─── Permission icon + color helpers ──────────────────────────────────────────

const READ_ACTIONS = [
    'read',
    'login',
    'logout',
    'clock_in',
    'clock_out',
    'sales',
    'stock',
    'finance',
    'employee',
];
const CREATE_ACTIONS = [
    'create',
    'register',
    'open',
    'add',
    'request',
    'redeem',
    'pay',
    'export',
];
const UPDATE_ACTIONS = [
    'update',
    'update_status',
    'approve_edit',
    'approve',
    'correct',
    'adjust',
    'toggle',
    'apply',
    'toggle_availability',
    'manage',
    'close',
];
const DELETE_ACTIONS = [
    'delete',
    'cancel',
    'void',
    'refund',
    'reject',
    'write_off',
    'split_bill',
    'rules_manage',
    'qr_generate',
];

function getPermissionIcon(permName: string): Component {
    const action = permName.split(':')[1] ?? '';
    if (READ_ACTIONS.includes(action)) return Eye;
    if (CREATE_ACTIONS.includes(action)) return Plus;
    if (UPDATE_ACTIONS.includes(action)) return Pencil;
    if (DELETE_ACTIONS.includes(action)) return Trash2;
    return CheckCircle2;
}

function getPermissionActiveClass(permName: string): string {
    const action = permName.split(':')[1] ?? '';
    if (READ_ACTIONS.includes(action))
        return 'border-sky-300 bg-sky-50 text-sky-700 dark:border-sky-400/30 dark:bg-sky-500/20 dark:text-sky-300';
    if (CREATE_ACTIONS.includes(action))
        return 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-500/20 dark:text-emerald-300';
    if (UPDATE_ACTIONS.includes(action))
        return 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-400/30 dark:bg-amber-500/20 dark:text-amber-300';
    if (DELETE_ACTIONS.includes(action))
        return 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-400/30 dark:bg-rose-500/20 dark:text-rose-300';
    return 'border-orange-300 bg-orange-50 text-orange-700 dark:border-orange-400/30 dark:bg-orange-500/20 dark:text-orange-300';
}

// ─── Types ────────────────────────────────────────────────────────────────────

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
    roleTypeOptions: Array<{ value: string; label: string }>;
    defaultPermissionMatrix: Record<string, string[]>;
    permissionMatrix: Record<string, string[]>;
    filters: { outlet_id?: string | null };
    success?: string | null;
}>();

// ─── State ─────────────────────────────────────────────────────────────────────

const activeTab = ref<'accounts' | 'matrix'>('matrix'); // Default to matrix for better focus on role config

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
const selectedMatrixRoleId = ref(props.roles[0]?.id || '');
const outletId = ref(props.filters.outlet_id || props.selectedOutlet?.id || '');

const selectedMatrixRole = computed(() => {
    return (
        props.roles.find((r) => r.id === selectedMatrixRoleId.value) ||
        props.roles[0]
    );
});

const employeeSearch = ref('');
const modalMode = ref<'create' | 'edit' | null>(null);
const selectedRole = ref<RoleRow | null>(null);
const savingMatrix = ref(false);
const matrixSaved = ref(false);

// Matriks lokal: { role_id: Set<permissionName> }
const localMatrix = ref<Record<string, Set<string>>>(
    Object.fromEntries(
        Object.entries(props.permissionMatrix).map(([roleId, perms]) => [
            roleId,
            new Set(perms),
        ]),
    ),
);

// ─── Role Form ─────────────────────────────────────────────────────────────────

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

// ─── Computed ──────────────────────────────────────────────────────────────────

const filteredEmployees = computed(() => {
    const kw = employeeSearch.value.trim().toLowerCase();
    if (!kw) return props.employees;
    return props.employees.filter(
        (e) =>
            e.name.toLowerCase().includes(kw) ||
            e.email.toLowerCase().includes(kw) ||
            (e.role_name || '').toLowerCase().includes(kw),
    );
});

const editableRoles = computed(() => props.roles.filter((r) => !r.is_locked));
const activeRoleOptions = computed(() =>
    props.roles.filter((r) => r.is_active),
);

const isModalOpen = computed(() => modalMode.value !== null);
const modalTitle = computed(() =>
    modalMode.value === 'edit' ? 'Edit Role' : 'Tambah Role Baru',
);

const summaryCards = computed(() => [
    {
        label: 'Total Role',
        value: props.summary.total_roles,
        helper: `${props.summary.active_roles} aktif`,
        tone: 'text-stone-900 dark:text-white',
        surface: 'border-stone-200 bg-white dark:border-white/10 dark:bg-slate-900/40',
        icon: ShieldCheck,
    },
    {
        label: 'Role Custom',
        value: props.summary.custom_roles,
        helper: 'Role turunan per outlet',
        tone: 'text-amber-700 dark:text-amber-300',
        surface: 'border-amber-200 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10',
        icon: KeyRound,
    },
    {
        label: 'Karyawan',
        value: props.summary.total_users,
        helper: 'Di outlet terpilih',
        tone: 'text-sky-700 dark:text-sky-300',
        surface: 'border-sky-200 bg-sky-50 dark:border-sky-500/20 dark:bg-sky-500/10',
        icon: Users,
    },
    {
        label: 'Permission',
        value: props.summary.permission_catalog_count,
        helper: 'Jenis akses tersedia',
        tone: 'text-emerald-700 dark:text-emerald-300',
        surface: 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/10',
        icon: UserCog,
    },
]);

// ─── Role badge color ──────────────────────────────────────────────────────────

const roleColorMap: Record<string, string> = {
    owner: 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-500/15 dark:text-amber-200 dark:border-amber-500/20',
    supervisor: 'bg-sky-50 text-sky-800 border-sky-300 dark:bg-sky-500/15 dark:text-sky-200 dark:border-sky-500/20',
    kasir: 'bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-emerald-500/15 dark:text-emerald-200 dark:border-emerald-500/20',
    bar: 'bg-purple-50 text-purple-800 border-purple-300 dark:bg-purple-500/15 dark:text-purple-200 dark:border-purple-500/20',
    kitchen: 'bg-rose-50 text-rose-800 border-rose-300 dark:bg-rose-500/15 dark:text-rose-200 dark:border-rose-500/20',
};
function roleBadgeClass(type?: string | null) {
    return (
        roleColorMap[type || ''] ??
        'bg-slate-500/15 text-stone-800 dark:text-slate-200 border-slate-400/20'
    );
}

function hasPermission(roleId: string, permissionName: string): boolean {
    return localMatrix.value[roleId]?.has(permissionName) ?? false;
}

function togglePermissionInMatrix(roleId: string, permissionName: string) {
    const role = props.roles.find((r) => r.id === roleId);
    if (!role || role.is_locked) return;

    const set = localMatrix.value[roleId] ?? new Set<string>();
    if (set.has(permissionName)) {
        set.delete(permissionName);
    } else {
        set.add(permissionName);
    }
    localMatrix.value = { ...localMatrix.value, [roleId]: new Set(set) };
    matrixSaved.value = false;
}

function toggleGroupForRole(roleId: string, group: PermissionGroup) {
    const role = props.roles.find((r) => r.id === roleId);
    if (!role || role.is_locked) return;

    const set = localMatrix.value[roleId] ?? new Set<string>();
    const groupNames = group.permissions.map((p) => p.name);
    const allActive = groupNames.every((n) => set.has(n));

    if (allActive) {
        groupNames.forEach((n) => set.delete(n));
    } else {
        groupNames.forEach((n) => set.add(n));
    }
    localMatrix.value = { ...localMatrix.value, [roleId]: new Set(set) };
    matrixSaved.value = false;
}

function isGroupAllActive(roleId: string, group: PermissionGroup): boolean {
    const set = localMatrix.value[roleId];
    if (!set) return false;
    return group.permissions.every((p) => set.has(p.name));
}

function isGroupPartialActive(roleId: string, group: PermissionGroup): boolean {
    const set = localMatrix.value[roleId];
    if (!set) return false;
    const names = group.permissions.map((p) => p.name);
    return names.some((n) => set.has(n)) && !names.every((n) => set.has(n));
}

function resetRoleToDefault(roleId: string, roleType: string) {
    alertDialog.value = {
        show: true,
        title: 'Reset Hak Akses',
        message:
            'Apakah Anda yakin ingin me-reset semua hak akses role ini ke pengaturan standar sistem?',
        type: 'warning',
        onConfirm: () => {
            const defaults = props.defaultPermissionMatrix[roleType] ?? [];
            localMatrix.value = {
                ...localMatrix.value,
                [roleId]: new Set(defaults),
            };
            matrixSaved.value = false;
            closeAlertDialog();
        },
    };
}

// Map permission name to short label for CRUD columns
function getActionLabel(permName: string): string {
    const action = permName.split(':')[1] ?? '';
    switch (action) {
        case 'read':
            return 'Lihat';
        case 'create':
            return 'Tambah';
        case 'update':
            return 'Edit';
        case 'delete':
            return 'Hapus';
        case 'manage':
            return 'Kelola';
        default:
            return action.replace('_', ' ');
    }
}

function saveMatrix() {
    savingMatrix.value = true;
    matrixSaved.value = false;

    const roles = props.roles
        .filter((r) => !r.is_locked)
        .map((r) => ({
            role_id: r.id,
            permissions: Array.from(localMatrix.value[r.id] ?? []),
        }));

    router.put(
        route('settings.rbac.matrix.save'),
        { outlet_id: outletId.value, roles },
        {
            preserveScroll: true,
            onSuccess: () => {
                matrixSaved.value = true;
                savingMatrix.value = false;
            },
            onError: () => {
                savingMatrix.value = false;
            },
        },
    );
}

// ─── Outlet filter ──────────────────────────────────────────────────────────────

function submitOutletFilter() {
    router.get(
        route('settings.rbac.index'),
        { outlet_id: outletId.value || undefined },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

// ─── Role modal ─────────────────────────────────────────────────────────────────

function resetRoleForm() {
    roleForm.clearErrors();
    roleForm.outlet_id = outletId.value || props.selectedOutlet?.id || '';
    roleForm.name = '';
    roleForm.type = props.roleTypeOptions[0]?.value || 'supervisor';
    roleForm.permissions = [
        ...(props.defaultPermissionMatrix[roleForm.type] || []),
    ];
    roleForm.is_active = true;
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

function submitRole() {
    roleForm.outlet_id = outletId.value || props.selectedOutlet?.id || '';
    const options = { preserveScroll: true, onSuccess: () => closeModal() };
    if (modalMode.value === 'edit' && selectedRole.value) {
        roleForm.patch(
            route('settings.rbac.update', selectedRole.value.id),
            options,
        );
        return;
    }
    roleForm.post(route('settings.rbac.store'), options);
}

// ─── Employee role assign ───────────────────────────────────────────────────────

function deleteEmployee(employee: EmployeeRow) {
    alertDialog.value = {
        show: true,
        title: 'Hapus Akun Karyawan',
        message: `Apakah Anda yakin ingin menghapus akun ${employee.name}? Data historis transaksi mungkin akan terpengaruh.`,
        type: 'danger',
        onConfirm: () => {
            router.delete(route('employees.destroy', employee.id), {
                preserveScroll: true,
                onSuccess: () => closeAlertDialog(),
                onFinish: () => closeAlertDialog(),
            });
        },
    };
}

function openEmployeeManager() {
    router.get(route('employees.index'), {
        outlet_id: outletId.value || undefined,
    });
}
</script>

<template>
    <Head title="User & RBAC" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-950 dark:text-white"
                    >
                        Role & Hak Akses
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-sm font-semibold text-stone-700 dark:text-stone-300"
                    >
                        Kelola akun karyawan per outlet dan atur hak akses CRUD
                        setiap role secara visual dari satu dashboard.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <!-- Success alert -->
            <div
                v-if="success"
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-300"
            >
                {{ success }}
            </div>

            <!-- Summary cards -->
            <section class="grid gap-4 lg:grid-cols-4">
                <article
                    v-for="stat in summaryCards"
                    :key="stat.label"
                    :class="['rounded-[22px] border-2 px-4 py-4 shadow-sm transition-all duration-200 hover:shadow-md', stat.surface]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-stone-500 dark:text-stone-400"
                            >
                                {{ stat.label }}
                            </p>
                            <p :class="['mt-2 text-3xl font-black tracking-tight', stat.tone]">
                                {{ stat.value }}
                            </p>
                            <p
                                class="mt-1 text-xs font-semibold text-stone-600 dark:text-slate-400"
                            >
                                {{ stat.helper }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 dark:border-white/10 dark:bg-slate-950"
                        >
                            <component
                                :is="stat.icon"
                                class="h-5 w-5 text-stone-800 dark:text-slate-200"
                            />
                        </div>
                    </div>
                </article>
            </section>

            <!-- Outlet selector -->
            <section
                class="flex flex-col gap-3 rounded-[24px] border-2 border-stone-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-slate-950 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p
                        class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-orange-600 dark:text-orange-400"
                    >
                        Outlet
                    </p>
                    <p
                        class="mt-0.5 text-xs font-semibold text-stone-500 dark:text-slate-400"
                    >
                        Semua pengaturan di bawah mengikuti outlet yang dipilih.
                    </p>
                </div>
                <select
                    v-model="outletId"
                    class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm font-bold text-stone-950 outline-none transition focus:border-orange-500 dark:border-white/15 dark:bg-slate-950 dark:text-white sm:w-[280px]"
                    @change="submitOutletFilter"
                >
                    <option
                        v-for="outlet in outlets"
                        :key="outlet.id"
                        :value="outlet.id"
                    >
                        {{ outlet.name
                        }}{{ outlet.is_active ? '' : ' (nonaktif)' }}
                    </option>
                </select>
            </section>

            <!-- Tab navigation -->
            <div
                class="flex w-full items-center gap-2 rounded-2xl border-2 border-stone-200 bg-stone-100 p-1.5 dark:border-white/10 dark:bg-slate-950"
            >
                <button
                    type="button"
                    :class="[
                        'flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition-all duration-200',
                        activeTab === 'accounts'
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-250 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/5',
                    ]"
                    @click="activeTab = 'accounts'"
                >
                    <Users class="h-4.5 w-4.5" />
                    Akun Karyawan
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px] font-black"
                        :class="
                            activeTab === 'accounts'
                                ? 'bg-stone-950/20 text-stone-950'
                                : 'bg-stone-200 text-stone-700 dark:bg-slate-800 dark:text-slate-350'
                        "
                    >
                        {{ employees.length }}
                    </span>
                </button>
                <button
                    type="button"
                    :class="[
                        'flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition-all duration-200',
                        activeTab === 'matrix'
                            ? 'bg-orange-500 text-stone-950 shadow-md'
                            : 'text-stone-700 hover:text-stone-900 hover:bg-stone-200 dark:text-slate-300 dark:hover:text-white dark:hover:bg-white/5',
                    ]"
                    @click="activeTab = 'matrix'"
                >
                    <ShieldCheck class="h-4.5 w-4.5" />
                    Hak Akses Peran (RBAC)
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px] font-black"
                        :class="
                            activeTab === 'matrix'
                                ? 'bg-stone-950/20 text-stone-950'
                                : 'bg-stone-200 text-stone-700 dark:bg-slate-800 dark:text-slate-350'
                        "
                    >
                        {{ roles.length }} role
                    </span>
                </button>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- TAB 1: Akun Karyawan                                    -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <section
                v-if="activeTab === 'accounts'"
                class="rounded-[28px] border-2 border-stone-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p
                            class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-orange-600 dark:text-orange-400"
                        >
                            Akun Karyawan
                        </p>
                        <h3
                            class="mt-1 text-lg font-black text-stone-900 dark:text-white"
                        >
                            Daftar karyawan & role aktif
                        </h3>
                        <p
                            class="mt-0.5 text-xs font-semibold text-stone-500 dark:text-slate-400"
                        >
                            Ubah role karyawan langsung dari dropdown. Karyawan
                            perlu login ulang agar akses baru aktif.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-4 py-2.5 text-sm font-black text-slate-950 shadow-md transition hover:bg-orange-400"
                        @click="openEmployeeManager"
                    >
                        <Plus class="h-4 w-4" />
                        Kelola Karyawan Baru
                    </button>
                </div>

                <!-- Search -->
                <label class="relative mt-4 block">
                    <Search
                        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500 dark:text-slate-400"
                    />
                    <input
                        v-model="employeeSearch"
                        type="text"
                        placeholder="Cari nama, email, atau role karyawan..."
                        class="w-full rounded-2xl border-2 border-stone-300 bg-white py-3 pl-10 pr-4 text-sm font-bold text-stone-950 outline-none transition placeholder:text-stone-400 focus:border-orange-500 dark:border-white/15 dark:bg-slate-950 dark:text-white dark:focus:border-orange-500"
                    />
                </label>

                <!-- Employee list -->
                <div class="mt-4 space-y-3">
                    <div
                        v-for="employee in filteredEmployees"
                        :key="employee.id"
                        class="flex flex-col gap-4 rounded-2xl border-2 border-stone-200 bg-white p-4 transition hover:bg-stone-50 dark:border-white/10 dark:bg-slate-900/40 dark:hover:bg-slate-900/60 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <!-- Avatar + info -->
                        <div class="flex items-center gap-3.5">
                            <div
                                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-slate-700 text-sm font-black text-white"
                            >
                                {{ employee.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="text-sm font-black text-stone-900 dark:text-white"
                                    >
                                        {{ employee.name }}
                                    </p>
                                    <span
                                        class="rounded-full border-2 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.14em]"
                                        :class="
                                            employee.is_active
                                                ? 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-350'
                                                : 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-350'
                                        "
                                    >
                                        {{
                                            employee.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }}
                                    </span>
                                    <span
                                        v-if="employee.role_type"
                                        class="rounded-full border-2 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.14em]"
                                        :class="
                                            roleBadgeClass(employee.role_type)
                                        "
                                    >
                                        {{
                                            employee.role_name ||
                                            employee.role_type
                                        }}
                                    </span>
                                </div>
                                <div class="mt-1 flex flex-col gap-0.5">
                                    <p
                                        class="text-xs font-semibold text-stone-600 dark:text-slate-400"
                                    >
                                        {{ employee.email }}
                                    </p>
                                    <p
                                        v-if="employee.phone"
                                        class="flex items-center gap-1.5 text-[11px] font-extrabold text-emerald-700 dark:text-emerald-400"
                                    >
                                        <span
                                            class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-500"
                                        ></span>
                                        WhatsApp: {{ employee.phone }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-[10px] font-bold italic text-rose-600 dark:text-rose-400/70"
                                    >
                                        Nomor WA belum diisi
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 sm:flex-shrink-0">
                            <Link
                                :href="
                                    route('employees.index', {
                                        search: employee.email,
                                    })
                                "
                                class="inline-flex items-center gap-1.5 rounded-xl border-2 border-stone-300 bg-white px-3 py-2 text-xs font-black text-stone-700 transition hover:bg-stone-50 dark:border-white/10 dark:bg-slate-900 dark:text-slate-350 dark:hover:bg-slate-800"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                                Edit Data
                            </Link>
                            <button
                                type="button"
                                @click="deleteEmployee(employee)"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border-2 border-rose-300 bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-450 dark:hover:bg-rose-500/20"
                                title="Hapus Karyawan"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="!filteredEmployees.length"
                        class="rounded-[24px] border-2 border-dashed border-stone-300 bg-stone-50 py-10 text-center dark:border-white/10 dark:bg-slate-950/20"
                    >
                        <Users class="mx-auto h-8 w-8 text-stone-500 dark:text-slate-600" />
                        <p
                            class="mt-3 text-sm font-semibold text-stone-500 dark:text-slate-400"
                        >
                            Tidak ada karyawan ditemukan.
                        </p>
                    </div>
                </div>

                <!-- Role list -->
                <div
                    class="mt-6 border-t-2 border-stone-200 pt-5 dark:border-white/10"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p
                            class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-stone-500 dark:text-slate-400"
                        >
                            Role yang Tersedia
                        </p>
                        <span class="text-xs font-semibold text-stone-500 dark:text-slate-500"
                            >{{ roles.length }} role terdaftar</span
                        >
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2.5">
                        <div
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-center gap-2 rounded-2xl border-2 border-stone-200 bg-white px-3.5 py-2.5 dark:border-white/10 dark:bg-slate-900/40"
                        >
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="
                                    role.is_active
                                        ? 'bg-emerald-500'
                                        : 'bg-stone-400 dark:bg-slate-600'
                                "
                            />
                            <span
                                class="text-sm font-black text-stone-900 dark:text-white"
                                >{{ role.name }}</span
                            >
                            <span
                                class="text-[10px] font-bold text-stone-500 dark:text-slate-500"
                                >{{ role.users_count }} user</span
                            >
                            <button
                                v-if="!role.is_locked"
                                type="button"
                                class="ml-1 rounded-lg p-1 text-stone-400 transition hover:bg-stone-100 hover:text-orange-600 dark:text-slate-500"
                                title="Edit role"
                                @click="openEditModal(role)"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- TAB 2: Matriks Hak Akses Peran                          -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <section
                v-if="activeTab === 'matrix'"
                class="rounded-[28px] border-2 border-stone-200 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-slate-950/45"
            >
                <!-- Header Controls -->
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h3
                            class="text-lg font-black text-stone-900 dark:text-white"
                        >
                            Matriks Hak Akses Peran
                        </h3>
                        <p
                            class="mt-1 text-xs font-semibold text-stone-500 dark:text-slate-400"
                        >
                            Atur sub-akses CRUD tiap menu per peran. Read =
                            lihat, Create = tambah, Update = ubah, Delete =
                            hapus.
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-3">
                        <button
                            type="button"
                            @click="
                                Object.keys(localMatrix).forEach((id) =>
                                    resetRoleToDefault(
                                        id,
                                        props.roles.find((r) => r.id === id)
                                            ?.type || '',
                                    ),
                                )
                            "
                            class="inline-flex items-center gap-2 rounded-xl border-2 border-stone-300 bg-white px-4 py-2.5 text-sm font-extrabold text-stone-700 transition hover:bg-stone-50 dark:border-white/15 dark:bg-slate-900 dark:text-slate-350 dark:hover:bg-slate-800"
                        >
                            <RotateCcw class="h-4 w-4" />
                            RESET
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-400 px-5 py-2.5 text-sm font-black text-stone-950 transition disabled:opacity-50"
                            :disabled="savingMatrix"
                            @click="saveMatrix"
                        >
                            <Save class="h-4 w-4" />
                            {{
                                savingMatrix
                                    ? 'MENYIMPAN...'
                                    : 'SIMPAN HAK AKSES'
                            }}
                        </button>
                    </div>
                </div>

                <!-- Legends -->
                <div
                    class="mt-6 flex flex-wrap items-center gap-3 border-b-2 border-stone-200 pb-6 dark:border-white/10"
                >
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border-2 border-sky-350 bg-sky-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-sky-850 dark:border-sky-400/30 dark:bg-sky-500/10 dark:text-sky-450"
                    >
                        <Eye class="h-3 w-3" /> READ
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border-2 border-emerald-350 bg-emerald-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-emerald-850 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-450"
                    >
                        <Plus class="h-3 w-3" /> CREATE
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border-2 border-amber-350 bg-amber-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-amber-855 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-455"
                    >
                        <Pencil class="h-3 w-3" /> UPDATE
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border-2 border-rose-350 bg-rose-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-rose-850 dark:border-rose-400/30 dark:bg-rose-500/10 dark:text-rose-455"
                    >
                        <Trash2 class="h-3 w-3" /> DELETE
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border-2 border-stone-300 bg-stone-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-stone-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400"
                    >
                        <KeyRound class="h-3 w-3" /> DIKUNCI
                    </span>
                </div>

                <!-- Matrix Table -->
                <div
                    class="mt-6 overflow-x-auto rounded-2xl border-2 border-stone-300 bg-stone-100/50 dark:border-white/10 dark:bg-slate-900/50"
                >
                    <table class="w-full min-w-max border-collapse">
                        <thead>
                            <tr class="border-b border-stone-300 dark:border-white/10">
                                <th
                                    class="sticky left-0 z-10 min-w-[240px] bg-stone-100 px-5 py-5 text-left text-xs font-black text-stone-850 dark:bg-slate-900 dark:text-slate-350"
                                >
                                    Menu / Fitur Sidebar
                                </th>
                                <th
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="min-w-[160px] border-l-2 border-stone-300 px-3 py-5 text-center dark:border-white/10"
                                >
                                    <p
                                        class="text-sm font-black text-stone-950 dark:text-white"
                                    >
                                        {{ role.name }}
                                    </p>
                                    <p
                                        class="mt-1 text-[9px] font-extrabold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-400"
                                    >
                                        R C U D
                                    </p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                v-for="group in permissionGroups"
                                :key="group.key"
                            >
                                <!-- Group Header / Pembatas -->
                                <tr class="bg-indigo-50 border-y border-stone-300 dark:bg-indigo-950/40 dark:border-white/10">
                                    <td
                                        :colspan="roles.length + 1"
                                        class="sticky left-0 z-10 bg-indigo-50 px-5 py-3.5 text-[11.5px] font-black uppercase tracking-[0.22em] text-indigo-850 dark:bg-indigo-950/40 dark:text-indigo-300"
                                    >
                                        {{ group.label }}
                                    </td>
                                </tr>

                                <!-- Menu Items in Group -->
                                <tr
                                    v-for="permission in group.permissions.filter(
                                        (p) =>
                                            p.name.endsWith(':read') ||
                                            (!group.permissions.some((xp) =>
                                                xp.name.endsWith(':read'),
                                            ) &&
                                                p === group.permissions[0]),
                                    )"
                                    :key="permission.id"
                                    class="border-t border-stone-200 hover:bg-stone-50 dark:border-white/10 dark:hover:bg-slate-900/40"
                                >
                                    <!-- Sidebar Menu Name -->
                                    <td
                                        class="sticky left-0 z-10 bg-white px-5 py-4 border-r border-stone-300 dark:bg-slate-900 dark:border-white/10"
                                    >
                                        <p
                                            class="text-sm font-black text-stone-900 dark:text-white"
                                        >
                                            {{
                                                permission.description
                                                    .split(' ')
                                                    .slice(1)
                                                    .join(' ') || group.label
                                            }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-xs font-bold text-stone-500 dark:text-slate-455"
                                        >
                                            {{ group.key }}
                                        </p>
                                    </td>

                                    <!-- Action Buttons per Role -->
                                    <td
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="border-l border-stone-200 px-2 py-4 text-center dark:border-white/10"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-1.5"
                                        >
                                            <!-- R, C, U, D Buttons -->
                                            <template
                                                v-for="action in [
                                                    'read',
                                                    'create',
                                                    'update',
                                                    'delete',
                                                ]"
                                                :key="action"
                                            >
                                                <button
                                                    v-if="
                                                        group.permissions.find(
                                                            (p) =>
                                                                p.name.endsWith(
                                                                    ':' +
                                                                        action,
                                                                ) ||
                                                                (action ===
                                                                    'delete' &&
                                                                    p.name.endsWith(
                                                                        ':cancel',
                                                                    )),
                                                        )
                                                    "
                                                    type="button"
                                                    :disabled="role.is_locked"
                                                    @click="
                                                        togglePermissionInMatrix(
                                                            role.id,
                                                            group.permissions.find(
                                                                (p) =>
                                                                    p.name.endsWith(
                                                                        ':' +
                                                                            action,
                                                                    ) ||
                                                                    (action ===
                                                                        'delete' &&
                                                                        p.name.endsWith(
                                                                            ':cancel',
                                                                        )),
                                                            )!.name,
                                                        )
                                                    "
                                                    :class="[
                                                        'flex h-7 w-7 items-center justify-center rounded border transition shadow-sm',
                                                        role.is_locked
                                                            ? 'cursor-not-allowed border-stone-200 bg-stone-100 text-stone-400 opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'
                                                            : hasPermission(
                                                                    role.id,
                                                                    group.permissions.find(
                                                                        (p) =>
                                                                            p.name.endsWith(
                                                                                ':' +
                                                                                    action,
                                                                            ) ||
                                                                            (action ===
                                                                                'delete' &&
                                                                                p.name.endsWith(
                                                                                    ':cancel',
                                                                                )),
                                                                    )!.name,
                                                                )
                                                              ? getPermissionActiveClass(
                                                                    group.permissions.find(
                                                                        (p) =>
                                                                            p.name.endsWith(
                                                                                ':' +
                                                                                    action,
                                                                            ) ||
                                                                            (action ===
                                                                                'delete' &&
                                                                                p.name.endsWith(
                                                                                    ':cancel',
                                                                                )),
                                                                    )!.name,
                                                                )
                                                              : 'border-stone-300 bg-white text-stone-650 hover:border-orange-500 hover:bg-orange-50 hover:text-orange-650 dark:border-white/15 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800',
                                                    ]"
                                                >
                                                    <component
                                                        :is="
                                                            getPermissionIcon(
                                                                group.permissions.find(
                                                                    (p) =>
                                                                        p.name.endsWith(
                                                                            ':' +
                                                                                action,
                                                                        ) ||
                                                                        (action ===
                                                                            'delete' &&
                                                                            p.name.endsWith(
                                                                                ':cancel',
                                                                            )),
                                                                )!.name,
                                                            )
                                                        "
                                                        class="h-3.5 w-3.5"
                                                    />
                                                </button>
                                                <!-- Placeholder if action doesn't exist for this module -->
                                                <div
                                                    v-else
                                                    class="flex h-7 w-7 items-center justify-center"
                                                >
                                                    <div
                                                        class="h-1.5 w-3 rounded-full bg-stone-300 dark:bg-slate-800"
                                                    ></div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Extra specific actions (e.g. approve, refund) rendered as small chips below if they exist -->
                                        <div
                                            v-if="
                                                group.permissions.filter(
                                                    (p) =>
                                                        ![
                                                            'read',
                                                            'create',
                                                            'update',
                                                            'delete',
                                                            'cancel',
                                                        ].includes(
                                                            p.name.split(
                                                                ':',
                                                            )[1],
                                                        ),
                                                ).length > 0
                                            "
                                            class="mt-2 flex flex-wrap justify-center gap-1"
                                        >
                                            <button
                                                v-for="extraPerm in group.permissions.filter(
                                                    (p) =>
                                                        ![
                                                            'read',
                                                            'create',
                                                            'update',
                                                            'delete',
                                                            'cancel',
                                                        ].includes(
                                                            p.name.split(
                                                                ':',
                                                            )[1],
                                                        ),
                                                )"
                                                :key="extraPerm.id"
                                                type="button"
                                                :disabled="role.is_locked"
                                                @click="
                                                    togglePermissionInMatrix(
                                                        role.id,
                                                        extraPerm.name,
                                                    )
                                                "
                                                :class="[
                                                    'rounded border px-2 py-1 text-[9px] font-extrabold uppercase transition-all tracking-wide shadow-sm',
                                                    role.is_locked
                                                        ? 'bg-stone-100 border-stone-250 text-stone-400 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500'
                                                        : hasPermission(
                                                                role.id,
                                                                extraPerm.name,
                                                            )
                                                          ? 'border-orange-500 bg-orange-50 text-orange-700 font-black dark:border-orange-500/30 dark:bg-orange-500/20 dark:text-orange-300'
                                                          : 'border-stone-300 bg-white text-stone-600 hover:border-orange-450 hover:bg-orange-50/50 dark:border-white/10 dark:bg-slate-950 dark:text-slate-400 dark:hover:bg-slate-850',
                                                ]"
                                            >
                                                {{
                                                    extraPerm.name.split(':')[1]
                                                }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- Modal Tambah/Edit Role                                          -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 px-4 py-8 backdrop-blur-sm dark:bg-slate-950/80"
            >
                <div
                    class="w-full max-w-lg rounded-[28px] border border-stone-200 bg-white shadow-2xl shadow-black/50 dark:border-white/10 dark:bg-slate-900"
                >
                    <div
                        class="flex items-start justify-between border-b border-stone-200 px-6 py-5 dark:border-white/10"
                    >
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                            >
                                Role Builder
                            </p>
                            <h3
                                class="mt-1 text-xl font-black text-stone-900 dark:text-white"
                            >
                                {{ modalTitle }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="rounded-2xl border border-stone-200 bg-white/[0.03] p-2 text-stone-600 transition hover:bg-white/[0.06] dark:border-white/10 dark:text-slate-300"
                            @click="closeModal"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form
                        class="space-y-4 px-6 py-5"
                        @submit.prevent="submitRole"
                    >
                        <!-- Nama role -->
                        <label class="block">
                            <span
                                class="text-xs font-semibold text-stone-600 dark:text-slate-300"
                                >Nama Role</span
                            >
                            <input
                                v-model="roleForm.name"
                                type="text"
                                placeholder="Contoh: Kasir Senior, Supervisor Malam"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-400/40 dark:border-white/10 dark:text-white"
                            />
                            <p
                                v-if="roleForm.errors.name"
                                class="mt-1.5 text-xs text-rose-300"
                            >
                                {{ roleForm.errors.name }}
                            </p>
                        </label>

                        <!-- Base role type -->
                        <label class="block">
                            <span
                                class="text-xs font-semibold text-stone-600 dark:text-slate-300"
                                >Base Tipe Role</span
                            >
                            <p
                                class="mt-0.5 text-[11px] text-stone-400 dark:text-slate-500"
                            >
                                Menentukan hak akses default yang langsung
                                diterapkan saat role dibuat.
                            </p>
                            <select
                                v-model="roleForm.type"
                                class="mt-2 w-full rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-orange-400/40 dark:border-white/10 dark:text-white"
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
                                class="mt-1.5 text-xs text-rose-300"
                            >
                                {{ roleForm.errors.type }}
                            </p>
                        </label>

                        <!-- Status aktif -->
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 bg-white/[0.03] p-4 dark:border-white/10"
                        >
                            <input
                                v-model="roleForm.is_active"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-stone-200 bg-stone-100 text-orange-500 focus:ring-orange-400 dark:border-white/20 dark:bg-slate-950"
                            />
                            <div>
                                <span
                                    class="text-sm font-semibold text-stone-800 dark:text-slate-200"
                                    >Role aktif</span
                                >
                                <p
                                    class="mt-0.5 text-xs text-stone-500 dark:text-slate-400"
                                >
                                    Role aktif bisa langsung diassign ke
                                    karyawan.
                                </p>
                            </div>
                        </label>

                        <p
                            class="text-[11px] text-stone-400 dark:text-slate-500"
                        >
                            Setelah role dibuat, atur permission detailnya
                            secara visual di tab
                            <strong class="text-stone-600 dark:text-slate-300"
                                >Hak Akses Peran (RBAC)</strong
                            >.
                        </p>

                        <div
                            class="flex flex-col-reverse gap-3 border-t border-stone-200 pt-4 dark:border-white/10 sm:flex-row sm:justify-end"
                        >
                            <button
                                type="button"
                                class="rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-stone-600 transition hover:bg-white/[0.05] dark:border-white/10 dark:text-slate-300"
                                @click="closeModal"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="roleForm.processing"
                            >
                                {{
                                    roleForm.processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Role'
                                }}
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
