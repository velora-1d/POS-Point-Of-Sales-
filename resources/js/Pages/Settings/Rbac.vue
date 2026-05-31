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

const activeTab = ref<'accounts' | 'matrix'>('accounts');
const outletId = ref(props.filters.outlet_id || props.selectedOutlet?.id || '');
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

// ─── Matrix helpers ─────────────────────────────────────────────────────────────

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
    const defaults = props.defaultPermissionMatrix[roleType] ?? [];
    localMatrix.value = { ...localMatrix.value, [roleId]: new Set(defaults) };
    matrixSaved.value = false;
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

function updateEmployeeRole(employee: EmployeeRow, event: Event) {
    const target = event.target as HTMLSelectElement;
    const nextRoleId = target.value;
    if (!nextRoleId || nextRoleId === employee.role_id) return;

    const selectedOption = activeRoleOptions.value.find((r) => r.id === nextRoleId);
    const confirmed = window.confirm(
        `Ubah role ${employee.name} menjadi "${selectedOption?.name ?? 'role baru'}"?\nUser perlu login ulang agar akses baru aktif.`,
    );
    if (!confirmed) { target.value = employee.role_id; return; }

    router.patch(
        route('settings.rbac.users.assign-role', employee.id),
        { outlet_id: outletId.value || props.selectedOutlet?.id || '', role_id: nextRoleId },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="User & RBAC" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div class="inline-flex items-center gap-2 self-start rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-orange-300">
                    <ShieldCheck class="h-3.5 w-3.5" />
                    Menu #55 User & RBAC
                </div>
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
                        @click="openCreateModal"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Role
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
                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ employee.email }}
                                    <span v-if="employee.phone"> · {{ employee.phone }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Role dropdown -->
                        <div class="flex items-center gap-2 sm:flex-shrink-0">
                            <PenLine class="h-3.5 w-3.5 flex-shrink-0 text-slate-500" />
                            <select
                                :value="employee.role_id"
                                class="rounded-2xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-400/40"
                                @change="updateEmployeeRole(employee, $event)"
                            >
                                <option v-for="role in activeRoleOptions" :key="role.id" :value="role.id">
                                    {{ role.name }}
                                </option>
                            </select>
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
            <!-- TAB 2: Matriks RBAC                                     -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <section v-if="activeTab === 'matrix'" class="rounded-[28px] border border-white/10 bg-slate-950/45 p-5">
                <!-- Header matrix -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300">Matriks Hak Akses Peran</p>
                        <h3 class="mt-1 text-lg font-black text-white">Atur akses per fitur untuk setiap role</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Klik ikon untuk mengaktifkan atau menonaktifkan akses. Setelah selesai klik <strong class="text-white">Simpan Hak Akses</strong>.
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <div v-if="matrixSaved" class="flex items-center gap-1.5 text-xs text-emerald-300">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            Tersimpan
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="savingMatrix"
                            @click="saveMatrix"
                        >
                            <Save class="h-4 w-4" />
                            {{ savingMatrix ? 'Menyimpan...' : 'Simpan Hak Akses' }}
                        </button>
                    </div>
                </div>

                <!-- Legend chips -->
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400">Legenda aksi:</span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-sky-400/20 bg-sky-500/10 px-2.5 py-1 text-[11px] font-semibold text-sky-200">
                        <Eye class="h-3 w-3" /> Lihat
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-emerald-400/20 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-200">
                        <Plus class="h-3 w-3" /> Tambah
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-amber-400/20 bg-amber-500/10 px-2.5 py-1 text-[11px] font-semibold text-amber-200">
                        <Pencil class="h-3 w-3" /> Ubah
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-rose-400/20 bg-rose-500/10 px-2.5 py-1 text-[11px] font-semibold text-rose-200">
                        <Trash2 class="h-3 w-3" /> Hapus/Aksi Kritis
                    </span>
                    <span class="ml-2 text-[11px] text-slate-500">Ikon berwarna = aktif · abu-abu = nonaktif</span>
                </div>

                <!-- Info owner -->
                <div class="mt-4 rounded-2xl border border-amber-400/15 bg-amber-500/8 px-4 py-3 text-xs text-amber-200">
                    <strong>Role Owner</strong> selalu memiliki semua akses penuh dan tidak bisa diubah dari halaman ini.
                </div>

                <!-- Matrix table -->
                <div class="mt-5 overflow-x-auto rounded-2xl border border-white/10">
                    <table class="w-full min-w-max border-collapse text-sm">
                        <!-- Role column headers -->
                        <thead>
                            <tr class="border-b border-white/10 bg-slate-950/60">
                                <th class="sticky left-0 z-10 min-w-[200px] bg-slate-950/90 px-4 py-4 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
                                    Fitur / Menu
                                </th>
                                <th
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="min-w-[130px] px-3 py-4 text-center"
                                >
                                    <div class="flex flex-col items-center gap-1.5">
                                        <span
                                            class="rounded-full border px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.12em]"
                                            :class="roleBadgeClass(role.type)"
                                        >
                                            {{ role.name }}
                                        </span>
                                        <span class="text-[10px] text-slate-500">{{ role.users_count }} user</span>
                                        <button
                                            v-if="!role.is_locked"
                                            type="button"
                                            class="mt-0.5 inline-flex items-center gap-1 rounded-lg border border-white/10 bg-white/[0.03] px-2 py-0.5 text-[10px] text-slate-400 transition hover:border-orange-400/30 hover:text-orange-300"
                                            :title="`Reset ${role.name} ke default`"
                                            @click="resetRoleToDefault(role.id, role.type)"
                                        >
                                            <RotateCcw class="h-2.5 w-2.5" />
                                            Reset
                                        </button>
                                        <span v-else class="text-[10px] text-slate-600">— terkunci —</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="group in permissionGroups" :key="group.key">
                                <!-- Group header row -->
                                <tr class="border-b border-white/5 bg-slate-900/60">
                                    <td class="sticky left-0 z-10 bg-slate-900/80 px-4 py-2.5">
                                        <span class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-300">
                                            {{ group.label }}
                                        </span>
                                    </td>
                                    <td
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="px-3 py-2 text-center"
                                    >
                                        <!-- Toggle semua permission dalam group ini -->
                                        <button
                                            v-if="!role.is_locked"
                                            type="button"
                                            :title="`Toggle semua ${group.label} untuk ${role.name}`"
                                            class="mx-auto flex h-5 w-5 items-center justify-center rounded-md transition"
                                            :class="isGroupAllActive(role.id, group)
                                                ? 'bg-orange-500 text-white'
                                                : isGroupPartialActive(role.id, group)
                                                    ? 'bg-orange-500/30 text-orange-200'
                                                    : 'border border-white/10 bg-white/[0.03] text-slate-600 hover:border-orange-400/30'"
                                            @click="toggleGroupForRole(role.id, group)"
                                        >
                                            <CheckCircle2 class="h-3 w-3" />
                                        </button>
                                        <span v-else class="mx-auto block h-5 w-5" />
                                    </td>
                                </tr>

                                <!-- Permission rows dalam group -->
                                <tr
                                    v-for="permission in group.permissions"
                                    :key="permission.id"
                                    class="border-b border-white/5 transition hover:bg-white/[0.02]"
                                >
                                    <!-- Permission name (frozen left) -->
                                    <td class="sticky left-0 z-10 bg-slate-950/90 px-4 py-3">
                                        <p class="text-xs font-semibold text-slate-200">{{ permission.description }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] text-slate-500">{{ permission.name }}</p>
                                    </td>

                                    <!-- Toggle per role -->
                                    <td
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="px-3 py-3 text-center"
                                    >
                                        <template v-if="role.is_locked">
                                            <!-- Owner: semua aktif, tidak bisa diubah -->
                                            <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/20">
                                                <CheckCircle2 class="h-4 w-4 text-amber-300" />
                                            </div>
                                        </template>
                                        <template v-else>
                                            <!-- Toggle button dengan warna berdasarkan action type -->
                                            <button
                                                type="button"
                                                class="mx-auto flex h-8 w-8 items-center justify-center rounded-xl border transition"
                                                :class="hasPermission(role.id, permission.name)
                                                    ? getPermissionActiveClass(permission.name)
                                                    : 'border-white/10 bg-white/[0.02] text-slate-600 hover:border-slate-500/40 hover:text-slate-400'"
                                                :title="`${hasPermission(role.id, permission.name) ? 'Nonaktifkan' : 'Aktifkan'}: ${permission.description} untuk ${role.name}`"
                                                @click="togglePermissionInMatrix(role.id, permission.name)"
                                            >
                                                <component
                                                    :is="getPermissionIcon(permission.name)"
                                                    class="h-3.5 w-3.5"
                                                />
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Bottom save button -->
                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="savingMatrix"
                        @click="saveMatrix"
                    >
                        <Save class="h-4 w-4" />
                        {{ savingMatrix ? 'Menyimpan...' : 'Simpan Hak Akses' }}
                    </button>
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
    </AuthenticatedLayout>
</template>
