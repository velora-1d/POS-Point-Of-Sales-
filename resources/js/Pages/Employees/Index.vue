<script setup lang="ts">
import AlertDialog from '@/Components/AlertDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarDays,
    Eye,
    EyeOff,
    Pencil,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
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
    photo_url?: string | null;
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
const showPassword = ref(false);
const showPin = ref(false);

const employeeForm = useForm<{
    _method?: string;
    name: string;
    email: string;
    phone: string;
    outlet_id: string;
    role_id: string;
    join_date: string;
    password: string;
    approval_pin: string;
    is_active: boolean;
    photo: File | string | null;
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
    photo: null,
});

const photoPreview = ref<string | null>(null);

const handlePhotoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        employeeForm.photo = file;

        const reader = new FileReader();
        reader.onload = (event) => {
            photoPreview.value = event.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const summaryCards = computed(() => [
    {
        label: 'Total Karyawan',
        value: props.summary.total,
        tone: 'text-stone-900 dark:text-white',
        surface: 'border-stone-300 bg-white dark:border-white/20 dark:bg-white/[0.03]',
        icon: Users,
    },
    {
        label: 'Aktif',
        value: props.summary.active,
        tone: 'text-emerald-800 dark:text-emerald-300',
        surface: 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/10',
        icon: BadgeCheck,
    },
    {
        label: 'Supervisor',
        value: props.summary.supervisor,
        tone: 'text-sky-850 dark:text-sky-300',
        surface: 'border-sky-300 bg-sky-50 dark:border-sky-500/20 dark:bg-sky-500/10',
        icon: ShieldCheck,
    },
    {
        label: 'Kasir',
        value: props.summary.kasir,
        tone: 'text-amber-800 dark:text-amber-300',
        surface: 'border-amber-300 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10',
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
    employeeForm.photo = null;
    photoPreview.value = null;
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
    employeeForm.outlet_id =
        employee.outlet?.id || props.referenceData.outlets[0]?.id || '';
    employeeForm.role_id = employee.role?.id || '';
    employeeForm.join_date = employee.join_date
        ? employee.join_date.slice(0, 10)
        : new Date().toISOString().slice(0, 10);
    employeeForm.is_active = Boolean(employee.is_active);
    employeeForm.password = '';
    employeeForm.approval_pin = '';
    photoPreview.value = null;
};

const closeModal = () => {
    modalMode.value = null;
    selectedEmployee.value = null;
    resetEmployeeForm();
};

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

const deleteEmployee = (employee: EmployeeRow) => {
    if (!props.canManage) return;

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
};

const submitEmployee = () => {
    if (modalMode.value === 'edit' && selectedEmployee.value) {
        employeeForm._method = 'PATCH';
        employeeForm.post(
            route('employees.update', selectedEmployee.value.id),
            {
                preserveScroll: true,
                onSuccess: () => closeModal(),
            },
        );

        return;
    }

    employeeForm.post(route('employees.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const getStatusClass = (isActive: boolean) => {
    return isActive
        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 dark:bg-emerald-500/20'
        : 'border-slate-500/20 bg-slate-500/10 text-stone-600 dark:text-slate-300';
};
</script>

<template>
    <Head title="Data Karyawan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        Data Karyawan
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-xs text-stone-500 dark:text-slate-400"
                    >
                        Kelola data karyawan, role, outlet, password awal, PIN
                        approval, dan status aktif. Supervisor bisa lihat, owner
                        bisa full manage.
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
                            <p
                                class="text-[11px] font-black uppercase tracking-[0.22em] text-stone-700 dark:text-stone-300"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-3 text-3xl font-black"
                                :class="card.tone"
                            >
                                {{ card.value }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-stone-900 dark:border-white/10 dark:bg-slate-950/40 dark:text-white"
                        >
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <section
                class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div
                        class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-4"
                    >
                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                >Cari karyawan</span
                            >
                            <div
                                class="flex items-center gap-2 rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 dark:border-white/20 dark:bg-slate-900/80"
                            >
                                <Search
                                    class="h-4 w-4 text-stone-600 dark:text-slate-400"
                                />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Nama, email, phone"
                                    class="w-full border-0 bg-transparent px-0 py-3 text-sm text-stone-950 placeholder:text-stone-500 focus:outline-none focus:ring-0 dark:text-white dark:placeholder:text-slate-500"
                                    @keyup.enter="submitFilters"
                                />
                            </div>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                >Status</span
                            >
                            <select
                                v-model="status"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900/80 dark:text-white"
                            >
                                <option value="">Semua status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                >Role</span
                            >
                            <select
                                v-model="roleType"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900/80 dark:text-white"
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
                            <span
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                >Outlet</span
                            >
                            <select
                                v-model="outletId"
                                class="w-full rounded-2xl border-2 border-stone-300 bg-stone-50 px-3 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900/80 dark:text-white"
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
                            class="rounded-2xl border-2 border-stone-950 bg-stone-100 hover:bg-stone-200 px-4 py-3 text-sm font-black text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                            @click="clearFilters"
                        >
                            Reset Filter
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-4 py-3 text-sm font-black text-slate-950 transition active:scale-[0.98] shadow-md shadow-orange-500/10"
                            @click="submitFilters"
                        >
                            Terapkan
                        </button>
                        <button
                            v-if="canManage"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-2xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-4 py-3 text-sm font-black text-slate-950 transition active:scale-[0.98] shadow-md shadow-orange-500/10"
                            @click="openCreateModal"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Karyawan
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 lg:grid-cols-[1.5fr_1fr]">
                <div
                    class="rounded-3xl border border-stone-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                >
                    <div
                        class="flex items-center justify-between border-b border-stone-200 px-5 py-4 dark:border-white/10"
                    >
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                            >
                                Daftar Karyawan
                            </h3>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                Menampilkan {{ employees.from ?? 0 }} -
                                {{ employees.to ?? 0 }} dari
                                {{ employees.total }} karyawan.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="!employees.data.length"
                        class="px-5 py-10 text-center text-sm text-stone-500 dark:text-slate-400"
                    >
                        Belum ada data karyawan pada filter ini.
                    </div>

                    <div v-else class="divide-y divide-white/10">
                        <article
                            v-for="employee in employees.data"
                            :key="employee.id"
                            class="grid gap-4 px-5 py-5 xl:grid-cols-[1fr_0.8fr_0.9fr_auto]"
                        >
                            <div class="flex gap-4">
                                <div
                                    class="aspect-[3/4] h-16 w-12 flex-shrink-0 overflow-hidden rounded-xl border border-stone-200 bg-white dark:border-white/10 dark:bg-slate-900"
                                >
                                    <img
                                        v-if="employee.photo_url"
                                        :src="employee.photo_url"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-white text-[9px] font-bold text-stone-400 dark:bg-slate-900 dark:text-slate-400"
                                    >
                                        NO PIC
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h3
                                            class="text-base font-black text-stone-900 dark:text-white"
                                        >
                                            {{ employee.name }}
                                        </h3>
                                        <span
                                            class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.18em]"
                                            :class="
                                                getStatusClass(
                                                    employee.is_active,
                                                )
                                            "
                                        >
                                            {{
                                                employee.is_active
                                                    ? 'Aktif'
                                                    : 'Nonaktif'
                                            }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-xs text-stone-500 dark:text-slate-400"
                                    >
                                        {{ employee.email }}
                                    </p>
                                    <p
                                        v-if="employee.phone"
                                        class="flex items-center gap-1.5 text-xs font-bold text-emerald-400"
                                    >
                                        <span
                                            class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        WA: {{ employee.phone }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-[10px] italic text-rose-400/70"
                                    >
                                        No. WA belum diisi
                                    </p>
                                </div>
                            </div>

                            <div
                                class="space-y-2 text-sm text-stone-600 dark:text-slate-300"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-400"
                                >
                                    Role & Outlet
                                </p>
                                <p>{{ employee.role?.name || '-' }}</p>
                                <p class="text-stone-400 dark:text-slate-400">
                                    {{ employee.outlet?.name || '-' }}
                                </p>
                            </div>

                            <div
                                class="space-y-2 text-sm text-stone-600 dark:text-slate-300"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400 dark:text-slate-400"
                                >
                                    Join & Approval
                                </p>
                                <p>{{ formatDate(employee.join_date) }}</p>
                                <p class="text-stone-400 dark:text-slate-400">
                                    PIN approval tersimpan aman
                                </p>
                            </div>

                            <div
                                v-if="canManage"
                                class="flex items-start justify-end gap-2"
                            >
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl border-2 border-stone-950 bg-stone-50 hover:bg-stone-200 px-3 py-2 text-xs font-black text-stone-900 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                                    @click="openEditModal(employee)"
                                >
                                    <Pencil class="h-4 w-4" />
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-500 bg-rose-50 text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20 active:scale-[0.98]"
                                    @click="deleteEmployee(employee)"
                                    title="Hapus Akun Karyawan"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="employees.links.length > 3"
                        class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-5 py-4 dark:border-white/10"
                    >
                        <p class="text-xs text-stone-400 dark:text-slate-400">
                            List karyawan dipaginasi agar data owner tetap
                            ringan.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Link
                                v-for="link in employees.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="rounded-xl border-2 px-3 py-2 text-xs font-black transition active:scale-[0.98]"
                                :class="
                                    link.active
                                        ? 'border-orange-600 bg-orange-500 text-stone-955 shadow-sm'
                                        : link.url
                                          ? 'border-stone-955 bg-stone-50 text-stone-900 hover:bg-stone-200 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]'
                                          : 'cursor-not-allowed border-stone-200 bg-stone-100 text-stone-400 dark:border-white/5 dark:bg-white/[0.01] dark:text-slate-600'
                                "
                            >
                                <span v-html="link.label"></span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-stone-200 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-950/70"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-2xl border border-stone-200 bg-white p-3 text-orange-200 dark:border-white/10 dark:bg-slate-950/50"
                        >
                            <CalendarDays class="h-5 w-5" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-[0.22em] text-stone-600 dark:text-slate-300"
                            >
                                Breakdown Role
                            </h3>
                            <p
                                class="mt-1 text-xs text-stone-400 dark:text-slate-400"
                            >
                                Fondasi untuk menu shift, absensi, dan approval
                                berikutnya.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article
                            v-for="role in roleBreakdown"
                            :key="role.type"
                            class="flex items-center justify-between rounded-2xl border border-stone-200 bg-white/[0.03] px-4 py-3 dark:border-white/10"
                        >
                            <div>
                                <p
                                    class="text-sm font-bold text-stone-900 dark:text-white"
                                >
                                    {{ role.name }}
                                </p>
                                <p
                                    class="text-[11px] uppercase tracking-[0.18em] text-stone-400 dark:text-slate-400"
                                >
                                    {{ role.type }}
                                </p>
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
            class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 px-4 py-6 backdrop-blur-sm dark:bg-slate-950/80"
        >
            <div
                class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-3xl border border-stone-200 bg-stone-100 p-6 shadow-[0_30px_120px_rgba(15,23,42,0.6)] dark:border-white/10 dark:bg-slate-950"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3
                            class="text-xl font-black text-stone-900 dark:text-white"
                        >
                            {{ modalTitle }}
                        </h3>
                        <p
                            class="mt-1 text-sm text-stone-500 dark:text-slate-400"
                        >
                            Isi data login, role, outlet, dan PIN approval
                            karyawan.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-2xl border-2 border-stone-955 bg-stone-50 hover:bg-stone-200 p-2 text-stone-955 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:bg-white/[0.08]"
                        @click="closeModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitEmployee">
                    <div class="grid gap-6 md:grid-cols-[180px_1fr]">
                        <!-- Kolom Kiri: Upload Foto 3:4 -->
                        <div class="space-y-2">
                            <span
                                class="block text-xs font-semibold uppercase tracking-[0.2em] text-stone-500 dark:text-slate-400"
                                >Foto Karyawan</span
                            >
                            <div
                                class="relative flex aspect-[3/4] w-full flex-col items-center justify-center overflow-hidden rounded-2xl border border-dashed border-stone-200 bg-white transition hover:border-orange-500/50 dark:border-white/20 dark:bg-slate-900"
                            >
                                <img
                                    v-if="photoPreview"
                                    :src="photoPreview"
                                    class="absolute inset-0 h-full w-full object-cover"
                                />
                                <img
                                    v-else-if="selectedEmployee?.photo_url"
                                    :src="selectedEmployee.photo_url"
                                    class="absolute inset-0 h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center p-4 text-center text-stone-400 dark:text-slate-400"
                                >
                                    <Plus class="mb-2 h-6 w-6" />
                                    <span class="text-xs font-bold"
                                        >Pilih Foto</span
                                    >
                                </div>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="absolute inset-0 cursor-pointer opacity-0"
                                    @change="handlePhotoChange"
                                />
                            </div>
                            <p
                                class="text-center text-[9px] uppercase tracking-wider text-stone-400 dark:text-slate-400"
                            >
                                Format JPG/PNG (3:4)
                            </p>
                            <p
                                v-if="employeeForm.errors.photo"
                                class="mt-2 text-center text-xs text-rose-600 dark:text-rose-450 font-bold"
                            >
                                {{ employeeForm.errors.photo }}
                            </p>
                        </div>

                        <!-- Kolom Kanan: Form input -->
                        <div class="space-y-5">
                            <section class="grid gap-4 md:grid-cols-2">
                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                        >Nama</span
                                    >
                                    <input
                                        v-model="employeeForm.name"
                                        type="text"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 placeholder:text-stone-500 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    />
                                    <p
                                        v-if="employeeForm.errors.name"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ employeeForm.errors.name }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                        >Email Login</span
                                    >
                                    <input
                                        v-model="employeeForm.email"
                                        type="email"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 placeholder:text-stone-500 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    />
                                    <p
                                        v-if="employeeForm.errors.email"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ employeeForm.errors.email }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                    >
                                        No. HP / WhatsApp
                                        <span class="text-rose-650">*</span>
                                    </span>
                                    <input
                                        v-model="employeeForm.phone"
                                        type="text"
                                        placeholder="Wajib untuk koordinasi (Contoh: 0812...)"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 placeholder:text-stone-500 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                        required
                                    />
                                    <p
                                        v-if="employeeForm.errors.phone"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ employeeForm.errors.phone }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                        >Tanggal Bergabung</span
                                    >
                                    <input
                                        v-model="employeeForm.join_date"
                                        type="date"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    />
                                    <p
                                        v-if="employeeForm.errors.join_date"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ employeeForm.errors.join_date }}
                                    </p>
                                </label>
                            </section>

                            <section class="grid gap-4 md:grid-cols-2">
                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                        >Outlet</span
                                    >
                                    <select
                                        v-model="employeeForm.outlet_id"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-950 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option
                                            v-for="outlet in referenceData.outlets"
                                            :key="outlet.id"
                                            :value="outlet.id"
                                        >
                                            {{ outlet.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="employeeForm.errors.outlet_id"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ employeeForm.errors.outlet_id }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                        >Role</span
                                    >
                                    <select
                                        v-model="employeeForm.role_id"
                                        class="w-full rounded-2xl border-2 border-stone-300 bg-white px-4 py-3 text-sm text-stone-955 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
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
                                    <p
                                        v-if="employeeForm.errors.role_id"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ employeeForm.errors.role_id }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                    >
                                        {{
                                            modalMode === 'edit'
                                                ? 'Password Baru'
                                                : 'Password Awal'
                                        }}
                                    </span>
                                    <div class="relative">
                                        <input
                                            v-model="employeeForm.password"
                                            :type="
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            class="w-full rounded-2xl border-2 border-stone-300 bg-white py-3 pl-4 pr-12 text-sm text-stone-955 placeholder:text-stone-500 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                            :placeholder="
                                                modalMode === 'edit'
                                                    ? 'Kosongkan jika tidak diganti'
                                                    : 'Minimal 8 karakter'
                                            "
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                showPassword = !showPassword
                                            "
                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-500 hover:text-stone-700 dark:text-slate-400"
                                        >
                                            <component
                                                :is="
                                                    showPassword ? EyeOff : Eye
                                                "
                                                class="h-4 w-4"
                                            />
                                        </button>
                                    </div>
                                    <p
                                        v-if="employeeForm.errors.password"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ employeeForm.errors.password }}
                                    </p>
                                </label>

                                <label class="block">
                                    <span
                                        class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-stone-700 dark:text-stone-300"
                                    >
                                        {{
                                            modalMode === 'edit'
                                                ? 'Reset PIN Approval'
                                                : 'PIN Approval'
                                        }}
                                    </span>
                                    <div class="relative">
                                        <input
                                            v-model="employeeForm.approval_pin"
                                            :type="
                                                showPin ? 'text' : 'password'
                                            "
                                            inputmode="numeric"
                                            maxlength="6"
                                            class="w-full rounded-2xl border-2 border-stone-300 bg-white py-3 pl-4 pr-12 text-sm text-stone-955 placeholder:text-stone-500 focus:border-orange-500 focus:outline-none focus:ring-0 dark:border-white/20 dark:bg-slate-900 dark:text-white"
                                            :placeholder="
                                                modalMode === 'edit'
                                                    ? 'Kosongkan jika tidak diganti'
                                                    : '6 digit angka'
                                            "
                                        />
                                        <button
                                            type="button"
                                            @click="showPin = !showPin"
                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-stone-500 hover:text-stone-700 dark:text-slate-400"
                                        >
                                            <component
                                                :is="showPin ? EyeOff : Eye"
                                                class="h-4 w-4"
                                            />
                                        </button>
                                    </div>
                                    <p
                                        v-if="employeeForm.errors.approval_pin"
                                        class="mt-2 text-xs text-rose-600 dark:text-rose-450 font-bold"
                                    >
                                        {{ employeeForm.errors.approval_pin }}
                                    </p>
                                </label>
                            </section>
                        </div>
                    </div>

                    <label
                        class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-white/[0.02] p-4 dark:border-white/10"
                    >
                        <input
                            v-model="employeeForm.is_active"
                            type="checkbox"
                            class="mt-1 rounded border-stone-200 bg-white text-orange-500 focus:ring-orange-400 dark:border-white/20 dark:bg-slate-900"
                        />
                        <span>
                            <span
                                class="block text-sm font-semibold text-stone-900 dark:text-white"
                            >
                                Karyawan aktif</span
                            >
                            <span
                                class="mt-1 block text-xs text-stone-400 dark:text-slate-400"
                            >
                                Nonaktifkan untuk karyawan resign atau tidak
                                boleh login lagi. Data historis tetap tersimpan.
                            </span>
                        </span>
                    </label>

                    <div
                        class="flex flex-wrap items-center justify-end gap-3 border-t border-stone-200 pt-5 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="rounded-2xl border-2 border-stone-950 bg-stone-100 hover:bg-stone-200 px-4 py-3 text-sm font-black text-stone-950 transition active:scale-[0.98] dark:border-white/20 dark:bg-white/[0.03] dark:text-slate-300 dark:hover:bg-white/[0.08]"
                            @click="closeModal"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="rounded-2xl bg-orange-500 border border-orange-600 hover:bg-orange-600 px-5 py-3 text-sm font-black text-slate-955 transition disabled:cursor-not-allowed disabled:opacity-60 shadow-md shadow-orange-500/10 active:scale-[0.98]"
                            :disabled="employeeForm.processing"
                        >
                            {{
                                employeeForm.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Karyawan'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

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
