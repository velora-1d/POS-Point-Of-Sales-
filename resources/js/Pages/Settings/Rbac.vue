<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Eye,
    KeyRound,
    PenLine,
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
import AlertDialog from '@/Components/AlertDialog.vue';
import { type Component, computed, ref } from 'vue';

// ─── Permission icon + color helpers ──────────────────────────────────────────

const READ_ACTIONS = ['read','login','logout','clock_in','clock_out','sales','stock','finance','employee'];
const CREATE_ACTIONS = ['create','register','open','add','request','redeem','pay','export'];
const UPDATE_ACTIONS = ['update','update_status','approve_edit','approve','correct','adjust','toggle','apply','toggle_availability','manage','close'];
const DELETE_ACTIONS = ['delete','cancel','void','refund','reject','write_off','split_bill','rules_manage','qr_generate'];

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
    if (READ_ACTIONS.includes(action)) return 'border-sky-400/30 bg-sky-500/20 text-sky-300';
    if (CREATE_ACTIONS.includes(action)) return 'border-emerald-400/30 bg-emerald-500/20 text-emerald-300';
    if (UPDATE_ACTIONS.includes(action)) return 'border-amber-400/30 bg-amber-500/20 text-amber-300';
    if (DELETE_ACTIONS.includes(action)) return 'border-rose-400/30 bg-rose-500/20 text-rose-300';
    return 'border-orange-400/30 bg-orange-500/20 text-orange-300';
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
    return props.roles.find(r => r.id === selectedMatrixRoleId.value) || props.roles[0];
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
        ])
    )
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
const activeRoleOptions = computed(() => props.roles.filter((r) => r.is_active));

const isModalOpen = computed(() => modalMode.value !== null);
const modalTitle = computed(() => (modalMode.value === 'edit' ? 'Edit Role' : 'Tambah Role Baru'));

const summaryCards = computed(() => [
    { label: 'Total Role', value: props.summary.total_roles, helper: `${props.summary.active_roles} aktif`, tone: 'text-white', surface: 'border-white/10 bg-white/[0.03]', icon: ShieldCheck },
    { label: 'Role Custom', value: props.summary.custom_roles, helper: 'Role turunan per outlet', tone: 'text-amber-300', surface: 'border-amber-400/15 bg-amber-500/10', icon: KeyRound },
    { label: 'Karyawan', value: props.summary.total_users, helper: 'Di outlet terpilih', tone: 'text-sky-300', surface: 'border-sky-400/15 bg-sky-500/10', icon: Users },
    { label: 'Permission', value: props.summary.permission_catalog_count, helper: 'Jenis akses tersedia', tone: 'text-emerald-300', surface: 'border-emerald-400/15 bg-emerald-500/10', icon: UserCog },
]);

// ─── Role badge color ──────────────────────────────────────────────────────────

const roleColorMap: Record<string, string> = {
    owner:      'bg-amber-500/15 text-amber-200 border-amber-400/20',
    supervisor: 'bg-sky-500/15 text-sky-200 border-sky-400/20',
    kasir:      'bg-emerald-500/15 text-emerald-200 border-emerald-400/20',
    bar:        'bg-purple-500/15 text-purple-200 border-purple-400/20',
    kitchen:    'bg-rose-500/15 text-rose-200 border-rose-400/20',
};
function roleBadgeClass(type?: string | null) {
    return roleColorMap[type || ''] ?? 'bg-slate-500/15 text-slate-200 border-slate-400/20';
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
        message: 'Apakah Anda yakin ingin me-reset semua hak akses role ini ke pengaturan standar sistem?',
        type: 'warning',
        onConfirm: () => {
            const defaults = props.defaultPermissionMatrix[roleType] ?? [];
            localMatrix.value = { ...localMatrix.value, [roleId]: new Set(defaults) };
            matrixSaved.value = false;
            closeAlertDialog();
        },
    };
}

// Map permission name to short label for CRUD columns
function getActionLabel(permName: string): string {
    const action = permName.split(':')[1] ?? '';
    switch (action) {
        case 'read': return 'Lihat';
        case 'create': return 'Tambah';
        case 'update': return 'Edit';
        case 'delete': return 'Hapus';
        case 'manage': return 'Kelola';
        default: return action.replace('_', ' ');
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
    roleForm.permissions = [...(props.defaultPermissionMatrix[roleForm.type] || [])];
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
        roleForm.patch(route('settings.rbac.update', selectedRole.value.id), options);
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
    router.get(route('employees.index'), { outlet_id: outletId.value || undefined });
}
</script>

<template>
    <Head title="User & RBAC" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white">Role & Hak Akses</h2>
                    <p class="mt-1 max-w-3xl text-xs text-slate-400">
                        Kelola akun karyawan per outlet dan atur hak akses CRUD setiap role secara visual dari satu dashboard.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <!-- Success alert -->
            <div v-if="success" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-300">
                {{ success }}
            </div>

            <!-- Summary cards -->
            <section class="grid gap-3 lg:grid-cols-4">
                <article
                    v-for="stat in summaryCards"
                    :key="stat.label"
                    :class="['rounded-[22px] border px-4 py-4', stat.surface]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">{{ stat.label }}</p>
                            <p :class="['mt-2 text-3xl font-black', stat.tone]">{{ stat.value }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ stat.helper }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <component :is="stat.icon" class="h-5 w-5 text-slate-200" />
                        </div>
                    </div>
                </article>
            </section>

            <!-- Outlet selector -->
            <section class="flex flex-col gap-3 rounded-[24px] border border-white/10 bg-slate-950/40 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">Outlet</p>
                    <p class="mt-0.5 text-xs text-slate-400">Semua pengaturan di bawah mengikuti outlet yang dipilih.</p>
                </div>
                <select
                    v-model="outletId"
                    class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none transition focus:border-orange-400/40 sm:w-[280px]"
                    @change="submitOutletFilter"
                >
                    <option v-for="outlet in outlets" :key="outlet.id" :value="outlet.id">
                        {{ outlet.name }}{{ outlet.is_active ? '' : ' (nonaktif)' }}
                    </option>
                </select>
            </section>

            <!-- Tab navigation -->
            <div class="flex items-center gap-1 rounded-2xl border border-white/10 bg-slate-950/40 p-1.5">
                <button
                    type="button"
                    :class="[
                        'flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                        activeTab === 'accounts'
                            ? 'bg-white text-slate-900 shadow-md'
                            : 'text-slate-400 hover:text-white',
                    ]"
                    @click="activeTab = 'accounts'"
                >
                    <Users class="h-4 w-4" />
                    Akun Karyawan
                    <span class="rounded-full bg-slate-200/20 px-2 py-0.5 text-[11px] font-bold" :class="activeTab === 'accounts' ? 'bg-slate-900/10 text-slate-900' : ''">
                        {{ employees.length }}
                    </span>
                </button>
                <button
                    type="button"
                    :class="[
                        'flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                        activeTab === 'matrix'
                            ? 'bg-white text-slate-900 shadow-md'
                            : 'text-slate-400 hover:text-white',
                    ]"
                    @click="activeTab = 'matrix'"
                >
                    <ShieldCheck class="h-4 w-4" />
                    Hak Akses Peran (RBAC)
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold" :class="activeTab === 'matrix' ? 'bg-slate-900/10 text-slate-900' : 'bg-slate-200/20'">
                        {{ roles.length }} role
                    </span>
                </button>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- TAB 1: Akun Karyawan                                    -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <section v-if="activeTab === 'accounts'" class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">Akun Karyawan</p>
                        <h3 class="mt-1 text-lg font-black text-white">Daftar karyawan & role aktif</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Ubah role karyawan langsung dari dropdown. Karyawan perlu login ulang agar akses baru aktif.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-orange-400"
                        @click="openEmployeeManager"
                    >
                        <Plus class="h-4 w-4" />
                        Kelola Karyawan Baru
                    </button>
                </div>

                <!-- Search -->
                <label class="relative mt-4 block">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                    <input
                        v-model="employeeSearch"
                        type="text"
                        placeholder="Cari nama, email, atau role karyawan..."
                        class="w-full rounded-2xl border border-white/10 bg-white/[0.03] py-3 pl-10 pr-4 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-orange-400/40"
                    />
                </label>

                <!-- Employee list -->
                <div class="mt-4 space-y-2.5">
                    <div
                        v-for="employee in filteredEmployees"
                        :key="employee.id"
                        class="flex flex-col gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4 transition hover:bg-white/[0.05] sm:flex-row sm:items-center sm:justify-between"
                    >
                        <!-- Avatar + info -->
                        <div class="flex items-center gap-3.5">
                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-slate-700 text-sm font-black text-white">
                                {{ employee.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-black text-white">{{ employee.name }}</p>
                                    <span
                                        class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.14em]"
                                        :class="employee.is_active
                                            ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
                                            : 'border-amber-400/20 bg-amber-500/10 text-amber-300'"
                                    >
                                        {{ employee.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <span
                                        v-if="employee.role_type"
                                        class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.14em]"
                                        :class="roleBadgeClass(employee.role_type)"
                                    >
                                        {{ employee.role_name || employee.role_type }}
                                    </span>
                                </div>
                                <div class="mt-1 flex flex-col gap-0.5">
                                    <p class="text-xs text-slate-400">{{ employee.email }}</p>
                                    <p v-if="employee.phone" class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-400/90">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        WhatsApp: {{ employee.phone }}
                                    </p>
                                    <p v-else class="text-[10px] italic text-rose-400/70">Nomor WA belum diisi</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 sm:flex-shrink-0">
                            <Link
                                :href="route('employees.index', { search: employee.email })"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-300 transition hover:bg-white/10"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                                Edit Data
                            </Link>
                            <button
                                type="button"
                                @click="deleteEmployee(employee)"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 transition hover:bg-rose-500/20"
                                title="Hapus Karyawan"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="!filteredEmployees.length" class="rounded-[24px] border border-dashed border-white/10 bg-white/[0.02] py-10 text-center">
                        <Users class="mx-auto h-8 w-8 text-slate-600" />
                        <p class="mt-3 text-sm font-semibold text-slate-400">Tidak ada karyawan ditemukan.</p>
                    </div>
                </div>

                <!-- Role list -->
                <div class="mt-6 border-t border-white/10 pt-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">Role yang Tersedia</p>
                        <span class="text-xs text-slate-500">{{ roles.length }} role terdaftar</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2.5">
                        <div
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-3.5 py-2.5"
                        >
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="role.is_active ? 'bg-emerald-400' : 'bg-slate-600'"
                            />
                            <span class="text-sm font-semibold text-white">{{ role.name }}</span>
                            <span class="text-[10px] text-slate-500">{{ role.users_count }} user</span>
                            <button
                                v-if="!role.is_locked"
                                type="button"
                                class="ml-1 rounded-lg p-1 text-slate-500 transition hover:bg-white/[0.06] hover:text-orange-300"
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
            <section v-if="activeTab === 'matrix'" class="rounded-[28px] border border-white/10 bg-slate-950/45 p-6 shadow-xl">
                <!-- Header Controls -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-white">Matriks Hak Akses Peran</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Atur sub-akses CRUD tiap menu per peran. Read = lihat, Create = tambah, Update = ubah, Delete = hapus.
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-3">
                        <button
                            type="button"
                            @click="Object.keys(localMatrix).forEach(id => resetRoleToDefault(id, props.roles.find(r => r.id === id)?.type || ''))"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                        >
                            <RotateCcw class="h-4 w-4" />
                            RESET
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-black text-slate-950 shadow-[0_0_20px_rgba(249,115,22,0.3)] transition hover:bg-orange-400 disabled:opacity-50"
                            :disabled="savingMatrix"
                            @click="saveMatrix"
                        >
                            <Save class="h-4 w-4" />
                            {{ savingMatrix ? 'MENYIMPAN...' : 'SIMPAN HAK AKSES' }}
                        </button>
                    </div>
                </div>

                <!-- Legends -->
                <div class="mt-6 flex flex-wrap items-center gap-3 border-b border-white/10 pb-6">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-400/30 bg-sky-500/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-sky-400">
                        <Eye class="h-3 w-3" /> READ
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-emerald-400">
                        <Plus class="h-3 w-3" /> CREATE
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/30 bg-amber-500/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-amber-400">
                        <Pencil class="h-3 w-3" /> UPDATE
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-400/30 bg-rose-500/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-rose-400">
                        <Trash2 class="h-3 w-3" /> DELETE
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-600 bg-slate-800 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">
                        <KeyRound class="h-3 w-3" /> DIKUNCI
                    </span>
                </div>

                <!-- Matrix Table -->
                <div class="mt-6 overflow-x-auto rounded-2xl border border-white/5 bg-slate-900/50">
                    <table class="w-full min-w-max border-collapse">
                        <thead>
                            <tr>
                                <th class="sticky left-0 z-10 min-w-[240px] bg-slate-900 px-5 py-5 text-left text-xs font-black text-slate-300">
                                    Menu / Fitur Sidebar
                                </th>
                                <th
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="min-w-[160px] border-l border-white/5 px-3 py-5 text-center"
                                >
                                    <p class="text-sm font-black text-white">{{ role.name }}</p>
                                    <p class="mt-1 text-[9px] font-black uppercase tracking-[0.2em] text-slate-500">R C U D</p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="group in permissionGroups" :key="group.key">
                                <!-- Group Header / Pembatas -->
                                <tr class="bg-indigo-950/20">
                                    <td
                                        :colspan="roles.length + 1"
                                        class="sticky left-0 z-10 px-5 py-3 text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400"
                                    >
                                        {{ group.label }}
                                    </td>
                                </tr>

                                <!-- Menu Items in Group -->
                                <tr
                                    v-for="permission in group.permissions.filter(p => p.name.endsWith(':read') || (!group.permissions.some(xp => xp.name.endsWith(':read')) && p === group.permissions[0]))"
                                    :key="permission.id"
                                    class="border-t border-white/5 hover:bg-white/[0.02]"
                                >
                                    <!-- Sidebar Menu Name -->
                                    <td class="sticky left-0 z-10 bg-slate-900 px-5 py-4">
                                        <p class="text-sm font-bold text-white">{{ permission.description.split(' ').slice(1).join(' ') || group.label }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ group.key }}</p>
                                    </td>

                                    <!-- Action Buttons per Role -->
                                    <td
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="border-l border-white/5 px-2 py-4 text-center"
                                    >
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- R, C, U, D Buttons -->
                                            <template v-for="action in ['read', 'create', 'update', 'delete']" :key="action">
                                                <button
                                                    v-if="group.permissions.find(p => p.name.endsWith(':' + action) || (action === 'delete' && p.name.endsWith(':cancel')))"
                                                    type="button"
                                                    :disabled="role.is_locked"
                                                    @click="togglePermissionInMatrix(role.id, group.permissions.find(p => p.name.endsWith(':' + action) || (action === 'delete' && p.name.endsWith(':cancel')))!.name)"
                                                    :class="[
                                                        'flex h-7 w-7 items-center justify-center rounded border transition',
                                                        role.is_locked
                                                            ? 'border-slate-700 bg-slate-800 text-slate-500 cursor-not-allowed opacity-50'
                                                            : hasPermission(role.id, group.permissions.find(p => p.name.endsWith(':' + action) || (action === 'delete' && p.name.endsWith(':cancel')))!.name)
                                                                ? getPermissionActiveClass(group.permissions.find(p => p.name.endsWith(':' + action) || (action === 'delete' && p.name.endsWith(':cancel')))!.name)
                                                                : 'border-white/10 bg-white/5 text-slate-600 hover:border-slate-500 hover:text-slate-400'
                                                    ]"
                                                >
                                                    <component
                                                        :is="getPermissionIcon(group.permissions.find(p => p.name.endsWith(':' + action) || (action === 'delete' && p.name.endsWith(':cancel')))!.name)"
                                                        class="h-3.5 w-3.5"
                                                    />
                                                </button>
                                                <!-- Placeholder if action doesn't exist for this module -->
                                                <div v-else class="h-7 w-7 flex items-center justify-center">
                                                    <div class="h-1 w-2 rounded-full bg-slate-800/50"></div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Extra specific actions (e.g. approve, refund) rendered as small chips below if they exist -->
                                        <div
                                            v-if="group.permissions.filter(p => !['read','create','update','delete','cancel'].includes(p.name.split(':')[1])).length > 0"
                                            class="mt-2 flex flex-wrap justify-center gap-1"
                                        >
                                            <button
                                                v-for="extraPerm in group.permissions.filter(p => !['read','create','update','delete','cancel'].includes(p.name.split(':')[1]))"
                                                :key="extraPerm.id"
                                                type="button"
                                                :disabled="role.is_locked"
                                                @click="togglePermissionInMatrix(role.id, extraPerm.name)"
                                                :class="[
                                                    'rounded px-1.5 py-0.5 text-[9px] font-bold uppercase transition',
                                                    role.is_locked
                                                        ? 'bg-slate-800 text-slate-500'
                                                        : hasPermission(role.id, extraPerm.name)
                                                            ? 'bg-orange-500/20 text-orange-400 border border-orange-500/30'
                                                            : 'bg-white/5 text-slate-500 hover:bg-white/10'
                                                ]"
                                            >
                                                {{ extraPerm.name.split(':')[1] }}
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-8 backdrop-blur-sm"
            >
                <div class="w-full max-w-lg rounded-[28px] border border-white/10 bg-slate-900 shadow-2xl shadow-black/50">
                    <div class="flex items-start justify-between border-b border-white/10 px-6 py-5">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">Role Builder</p>
                            <h3 class="mt-1 text-xl font-black text-white">{{ modalTitle }}</h3>
                        </div>
                        <button
                            type="button"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-2 text-slate-300 transition hover:bg-white/[0.06]"
                            @click="closeModal"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form class="space-y-4 px-6 py-5" @submit.prevent="submitRole">
                        <!-- Nama role -->
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-300">Nama Role</span>
                            <input
                                v-model="roleForm.name"
                                type="text"
                                placeholder="Contoh: Kasir Senior, Supervisor Malam"
                                class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                            />
                            <p v-if="roleForm.errors.name" class="mt-1.5 text-xs text-rose-300">{{ roleForm.errors.name }}</p>
                        </label>

                        <!-- Base role type -->
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-300">Base Tipe Role</span>
                            <p class="mt-0.5 text-[11px] text-slate-500">Menentukan hak akses default yang langsung diterapkan saat role dibuat.</p>
                            <select
                                v-model="roleForm.type"
                                class="mt-2 w-full rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white outline-none transition focus:border-orange-400/40"
                            >
                                <option v-for="option in roleTypeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="roleForm.errors.type" class="mt-1.5 text-xs text-rose-300">{{ roleForm.errors.type }}</p>
                        </label>

                        <!-- Status aktif -->
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <input
                                v-model="roleForm.is_active"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-400"
                            />
                            <div>
                                <span class="text-sm font-semibold text-slate-200">Role aktif</span>
                                <p class="mt-0.5 text-xs text-slate-400">Role aktif bisa langsung diassign ke karyawan.</p>
                            </div>
                        </label>

                        <p class="text-[11px] text-slate-500">
                            Setelah role dibuat, atur permission detailnya secara visual di tab <strong class="text-slate-300">Hak Akses Peran (RBAC)</strong>.
                        </p>

                        <div class="flex flex-col-reverse gap-3 border-t border-white/10 pt-4 sm:flex-row sm:justify-end">
                            <button type="button" class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/[0.05]" @click="closeModal">
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="roleForm.processing"
                            >
                                {{ roleForm.processing ? 'Menyimpan...' : 'Simpan Role' }}
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
