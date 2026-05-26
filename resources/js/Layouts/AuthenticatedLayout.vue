<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    CalendarDays,
    ChefHat,
    ChevronDown,
    ChevronRight,
    Globe,
    LayoutDashboard,
    LogOut,
    Menu,
    Package,
    Percent,
    Search,
    Settings,
    ShoppingCart,
    TableProperties,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface MenuItem {
    id: number;
    name: string;
    status: string;
    route?: string;
}

interface MenuCategory {
    phase: string;
    flow: string;
    name: string;
    icon: any;
    items: MenuItem[];
    isOpenFiltered?: boolean;
}

const page = usePage<any>();
const user = computed(() => page.props.auth.user);
const menuProgress = computed(() => {
    const progress = page.props.menuProgress ?? {};

    return {
        readyMenuIds: Array.isArray(progress.readyMenuIds)
            ? progress.readyMenuIds
            : [],
    };
});

const isMobileOpen = ref(false);
const searchQuery = ref('');
const toastMessage = ref('');

// List of all 62 menu items categorized into 10 categories matching menu-per-role.md
const menuData: MenuCategory[] = [
    {
        phase: 'Fase 2',
        flow: 'Meja & Order',
        name: 'Meja',
        icon: TableProperties,
        items: [
            {
                id: 19,
                name: 'Layout Meja Visual',
                status: 'ready',
                route: 'tables.layout',
            },
            {
                id: 20,
                name: 'Status Meja Real-time',
                status: 'ready',
                route: 'tables.layout',
            },
            {
                id: 21,
                name: 'QR Code per Meja',
                status: 'ready',
                route: 'tables.layout',
            },
            {
                id: 12,
                name: 'Reservasi / Book Meja',
                status: 'ready',
                route: 'tables.layout',
            },
        ],
    },
    {
        phase: 'Fase 2',
        flow: 'Meja & Order',
        name: 'Order & Transaksi',
        icon: ShoppingCart,
        items: [
            {
                id: 1,
                name: 'Buat Order Baru',
                status: 'ready',
                route: 'kasir.order',
            },
            {
                id: 2,
                name: 'Daftar Order Aktif',
                status: 'ready',
                route: 'kasir.order',
            },
            {
                id: 3,
                name: 'Detail Order per Meja',
                status: 'ready',
                route: 'kasir.order',
            },
            {
                id: 4,
                name: 'Edit Order (approval flow)',
                status: 'ready',
                route: 'kasir.order',
            },
            {
                id: 5,
                name: 'Split Bill / Gabung Bill',
                status: 'ready',
                route: 'kasir.order',
            },
            {
                id: 6,
                name: 'Pembayaran (cash, QRIS, gateway)',
                status: 'ready',
                route: 'kasir.order',
            },
            { id: 7, name: 'Kasbon & Cicilan', status: 'coming_soon' },
            { id: 8, name: 'PO / Down Payment', status: 'coming_soon' },
            { id: 9, name: 'Diskon & Voucher', status: 'coming_soon' },
            {
                id: 10,
                name: 'Struk (print / WA / skip)',
                status: 'coming_soon',
            },
            { id: 11, name: 'Riwayat Transaksi', status: 'coming_soon' },
        ],
    },
    {
        phase: 'Fase 3',
        flow: 'Kitchen & Bar',
        name: 'Kitchen Display',
        icon: ChefHat,
        items: [
            {
                id: 13,
                name: 'Antrian Order Real-time',
                status: 'ready',
                route: 'kitchen.display',
            },
            {
                id: 14,
                name: 'Update Status Masak',
                status: 'ready',
                route: 'kitchen.display',
            },
            {
                id: 15,
                name: 'Approval Order Selesai (kitchen->kasir)',
                status: 'ready',
                route: 'bar.display',
            },
            {
                id: 16,
                name: 'Filter Kategori Pesanan',
                status: 'ready',
                route: 'kitchen.display',
            },
            {
                id: 17,
                name: 'Riwayat Order Dapur',
                status: 'ready',
                route: 'kitchen.display',
            },
            {
                id: 18,
                name: 'Estimasi Waktu Masak',
                status: 'ready',
                route: 'kitchen.display',
            },
        ],
    },
    {
        phase: 'Fase 4',
        flow: 'Pelanggan',
        name: 'Pelanggan',
        icon: Users,
        items: [
            {
                id: 22,
                name: 'Customer Database',
                status: 'ready',
                route: 'customers.index',
            },
            {
                id: 23,
                name: 'Membership & Loyalty',
                status: 'ready',
                route: 'customers.index',
            },
            {
                id: 24,
                name: 'Kasbon per Pelanggan',
                status: 'ready',
                route: 'customers.index',
            },
            {
                id: 25,
                name: 'Riwayat Transaksi Pelanggan',
                status: 'ready',
                route: 'customers.index',
            },
        ],
    },
    {
        phase: 'Fase 5',
        flow: 'Produk & Stok',
        name: 'Produk & Stok',
        icon: Package,
        items: [
            {
                id: 26,
                name: 'Katalog Produk',
                status: 'ready',
                route: 'products.index',
            },
            {
                id: 27,
                name: 'Varian & Multi Harga',
                status: 'ready',
                route: 'products.index',
            },
            {
                id: 28,
                name: 'Manajemen Stok Produk Jadi',
                status: 'ready',
                route: 'products.stock',
            },
            {
                id: 29,
                name: 'Inventori Bahan Baku',
                status: 'ready',
                route: 'raw-materials.index',
            },
            {
                id: 30,
                name: 'HPP per Produk',
                status: 'ready',
                route: 'products.hpp',
            },
            {
                id: 31,
                name: 'Reminder Expired Product',
                status: 'ready',
                route: 'expired-tracking.index',
            },
            {
                id: 32,
                name: 'Alert Stok Menipis',
                status: 'ready',
                route: 'stock-alerts.index',
            },
        ],
    },
    {
        phase: 'Fase 6',
        flow: 'Promo & Diskon',
        name: 'Promo & Diskon',
        icon: Percent,
        items: [
            { id: 33, name: 'Template Promo', status: 'ready', route: 'promos.index' },
            { id: 34, name: 'Diskon Otomatis', status: 'ready', route: 'kasir.order' },
            { id: 35, name: 'Voucher', status: 'ready', route: 'kasir.order' },
        ],
    },
    {
        phase: 'Fase 7',
        flow: 'Karyawan & Shift',
        name: 'Karyawan & Shift',
        icon: CalendarDays,
        items: [
            { id: 36, name: 'Data Karyawan', status: 'ready', route: 'employees.index' },
            { id: 37, name: 'Jadwal Shift', status: 'ready', route: 'schedules.index' },
            { id: 38, name: 'Absensi Digital', status: 'ready', route: 'attendance.index' },
            { id: 39, name: 'Buka / Tutup Shift Kasir', status: 'ready', route: 'shifts.index' },
            { id: 40, name: 'Rekap Kas per Shift', status: 'ready', route: 'shifts.index' },
            { id: 41, name: 'Laporan Kehadiran', status: 'ready', route: 'attendance.index' },
        ],
    },
    {
        phase: 'Fase 8',
        flow: 'Order Online',
        name: 'Order Online',
        icon: Globe,
        items: [
            {
                id: 42,
                name: 'Terima Order GoFood / GrabFood',
                status: 'ready',
                route: 'online-orders.index',
            },
            {
                id: 43,
                name: 'Status Order Online',
                status: 'ready',
                route: 'online-orders.index',
            },
            {
                id: 44,
                name: 'Riwayat Order Online',
                status: 'ready',
                route: 'online-orders.index',
            },
        ],
    },
    {
        phase: 'Fase 9',
        flow: 'Laporan & ERP',
        name: 'Laporan & ERP',
        icon: BarChart3,
        items: [
            { id: 45, name: 'Dashboard Keuangan', status: 'ready', route: 'dashboard' },
            { id: 46, name: 'Laporan Penjualan', status: 'ready', route: 'reports.sales.index' },
            { id: 47, name: 'Laporan Per Outlet', status: 'coming_soon' },
            { id: 48, name: 'Laporan Per Kasir', status: 'coming_soon' },
            { id: 49, name: 'Laporan Produk Terlaris', status: 'coming_soon' },
            { id: 50, name: 'Laporan Stok & Inventori', status: 'coming_soon' },
            { id: 51, name: 'Laporan Absensi & Shift', status: 'coming_soon' },
            { id: 52, name: 'Pengeluaran Operasional', status: 'coming_soon' },
            { id: 53, name: 'Export PDF & Excel', status: 'coming_soon' },
        ],
    },
    {
        phase: 'Fase 10',
        flow: 'Pengaturan',
        name: 'Pengaturan',
        icon: Settings,
        items: [
            {
                id: 54,
                name: 'Manajemen Outlet & Cabang',
                status: 'coming_soon',
            },
            { id: 55, name: 'User & RBAC', status: 'coming_soon' },
            {
                id: 56,
                name: 'Konfigurasi Payment Gateway',
                status: 'coming_soon',
            },
            { id: 57, name: 'Konfigurasi Printer', status: 'coming_soon' },
            { id: 58, name: 'Konfigurasi QR Meja', status: 'coming_soon' },
            { id: 59, name: 'Notifikasi & Alert', status: 'coming_soon' },
            { id: 60, name: 'Backup & Keamanan Data', status: 'coming_soon' },
            { id: 61, name: 'Approval Rules', status: 'coming_soon' },
            {
                id: 62,
                name: 'Integrasi GoFood & GrabFood',
                status: 'coming_soon',
            },
        ],
    },
];

// Reactive states for collapsed categories
const collapsedCategories = ref<Record<string, boolean>>({
    Meja: false,
    'Order & Transaksi': false,
    'Kitchen Display': false,
    Pelanggan: true,
    'Produk & Stok': true,
    'Promo & Diskon': true,
    'Karyawan & Shift': true,
    'Order Online': true,
    'Laporan & ERP': true,
    Pengaturan: true,
});

const toggleCategory = (name: string) => {
    collapsedCategories.value[name] = !collapsedCategories.value[name];
};

// Filter menu items based on search query
const filteredMenu = computed(() => {
    if (!searchQuery.value) return menuData;
    const query = searchQuery.value.toLowerCase();
    return menuData
        .map((category) => {
            const filteredItems = category.items.filter((item) =>
                item.name.toLowerCase().includes(query),
            );
            return {
                ...category,
                items: filteredItems,
                isOpenFiltered: filteredItems.length > 0,
            };
        })
        .filter((category) => category.items.length > 0);
});

// Check if category should be expanded
const isCategoryExpanded = (categoryName: string, isFilteredOpen?: boolean) => {
    if (searchQuery.value) {
        return isFilteredOpen ?? false;
    }
    return collapsedCategories.value[categoryName] !== true;
};

// Show Toast notification when user clicks on Coming Soon items
const triggerComingSoonToast = (itemName: string) => {
    toastMessage.value = `Fitur "${itemName}" sedang dalam tahap pengembangan dan akan segera hadir!`;
    // Auto clear toast after 4 seconds
    setTimeout(() => {
        if (toastMessage.value.includes(itemName)) {
            toastMessage.value = '';
        }
    }, 4000);
};

// Get initials for user avatar
const getInitials = (name?: string) => {
    if (!name) return 'ST';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

// Resolve route for kitchen/bar dynamic antrian link
const getRouteForAntrian = () => {
    const roleName = user.value?.role?.toLowerCase();
    if (roleName === 'bar') {
        return 'bar.display';
    }
    return 'kitchen.display';
};

const isMenuReady = (menuId: number) => {
    return menuProgress.value.readyMenuIds.includes(menuId);
};

const resolveItemRoute = (item: MenuItem): string => {
    if ([13, 16, 17, 18].includes(item.id)) {
        return getRouteForAntrian();
    }

    return item.route ?? 'dashboard';
};
</script>

<template>
    <div
        class="flex h-screen flex-col overflow-hidden bg-slate-950 font-sans text-slate-100 antialiased selection:bg-orange-500 selection:text-white lg:flex-row"
    >
        <!-- Backdrop for mobile sidebar drawer -->
        <div
            v-if="isMobileOpen"
            @click="isMobileOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm transition-opacity duration-300 lg:hidden"
        ></div>

        <!-- Sidebar Navigation -->
        <aside
            :class="[
                'fixed bottom-0 left-0 top-0 z-40 flex w-80 flex-col border-r border-slate-800/80 bg-slate-900/90 backdrop-blur-xl transition-transform duration-300 lg:translate-x-0',
                isMobileOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo Section -->
            <div
                class="flex shrink-0 items-center gap-3 border-b border-slate-800/60 px-6 py-6"
            >
                <div
                    class="to-red-650 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-orange-500 text-lg font-black text-white shadow-lg shadow-orange-500/20"
                >
                    M
                </div>
                <div>
                    <h1
                        class="text-base font-extrabold leading-none tracking-wider text-white"
                    >
                        POS MENTAI
                    </h1>
                    <span
                        class="mt-1 block text-[10px] font-semibold uppercase tracking-widest text-orange-400"
                        >Management Suite</span
                    >
                </div>
            </div>

            <!-- Search Area -->
            <div class="border-slate-850 shrink-0 border-b px-4 py-4">
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"
                    >
                        <Search class="h-4 w-4" />
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Cari dari 62 menu..."
                        class="border-slate-850 w-full rounded-xl border bg-slate-950/60 py-2.5 pl-10 pr-4 text-sm text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="hover:text-slate-350 absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Menu Categories & Items List (Scrollable) -->
            <nav
                class="custom-scrollbar flex-1 space-y-4 overflow-y-auto px-3 py-4"
            >
                <!-- Static Link to Main Dashboard -->
                <div class="px-2">
                    <Link
                        :href="route('dashboard')"
                        @click="isMobileOpen = false"
                        :class="[
                            'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition duration-150',
                            route().current('dashboard')
                                ? 'border border-orange-500/20 bg-gradient-to-r from-orange-500/10 to-red-500/5 text-orange-400'
                                : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-200',
                        ]"
                    >
                        <LayoutDashboard class="h-4 w-4 shrink-0" />
                        <span>Dashboard Utama</span>
                        <span
                            v-if="route().current('dashboard')"
                            class="ms-auto h-1.5 w-1.5 rounded-full bg-orange-500"
                        ></span>
                    </Link>
                </div>

                <!-- Divider -->
                <div class="mx-2 h-px bg-slate-800/40"></div>

                <div class="px-2">
                    <div
                        class="rounded-xl border border-slate-800/60 bg-slate-950/40 px-4 py-3"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300"
                        >
                            Urutan Brief
                        </p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">
                            Baca menu dari meja, lanjut order, masuk kitchen,
                            lalu hubungkan ke pelanggan. Fase lain tetap
                            ditampilkan untuk tracking progress.
                        </p>
                    </div>
                </div>

                <!-- Dynamic Categories -->
                <div
                    v-for="category in filteredMenu"
                    :key="category.name"
                    class="space-y-1.5"
                >
                    <!-- Category Header Accordion -->
                    <div
                        @click="toggleCategory(category.name)"
                        class="group flex cursor-pointer select-none items-center justify-between rounded-lg px-3 py-2 transition duration-150 hover:bg-slate-800/30"
                    >
                        <div
                            class="flex min-w-0 items-center gap-2.5 text-slate-400 transition duration-150 group-hover:text-slate-200"
                        >
                            <component
                                :is="category.icon"
                                class="h-4.5 w-4.5 shrink-0"
                            />
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-orange-300"
                                    >
                                        {{ category.phase }}
                                    </span>
                                    <span
                                        class="truncate text-xs font-bold uppercase tracking-wider"
                                        >{{ category.name }}</span
                                    >
                                </div>
                                <p class="mt-0.5 text-[10px] text-slate-500">
                                    {{ category.flow }}
                                </p>
                            </div>
                        </div>
                        <span class="group-hover:text-slate-350 text-slate-500">
                            <ChevronDown
                                v-if="
                                    isCategoryExpanded(
                                        category.name,
                                        category.isOpenFiltered,
                                    )
                                "
                                class="h-4 w-4"
                            />
                            <ChevronRight v-else class="h-4 w-4" />
                        </span>
                    </div>

                    <!-- Category Items -->
                    <div
                        v-show="
                            isCategoryExpanded(
                                category.name,
                                category.isOpenFiltered,
                            )
                        "
                        class="ml-5 space-y-1 border-l border-slate-800/40 pl-4"
                    >
                        <template v-for="item in category.items" :key="item.id">
                            <!-- Ready Item Link -->
                            <Link
                                v-if="isMenuReady(item.id) && item.route"
                                :href="route(resolveItemRoute(item))"
                                @click="isMobileOpen = false"
                                :class="[
                                    'group/item flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs transition duration-150',
                                    route().current(
                                        resolveItemRoute(item),
                                    )
                                        ? 'bg-orange-500/10 font-semibold text-orange-400'
                                        : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-200',
                                ]"
                            >
                                <span class="flex items-center gap-2 truncate">
                                    <span
                                        class="font-mono text-[10px] text-slate-500 group-hover/item:text-slate-400"
                                        >{{ item.id }}</span
                                    >
                                    <span>{{ item.name }}</span>
                                </span>
                                <span
                                    class="scale-90 rounded-full border border-emerald-500/20 bg-emerald-500/15 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-emerald-400"
                                >
                                    Ready
                                </span>
                            </Link>

                            <!-- Coming Soon Item Button -->
                            <button
                                v-else
                                @click="triggerComingSoonToast(item.name)"
                                class="group/item flex w-full cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-left text-xs text-slate-500 transition duration-150 hover:bg-slate-800/20 hover:text-slate-400"
                            >
                                <span class="flex items-center gap-2 truncate">
                                    <span
                                        class="font-mono text-[10px] text-slate-600 group-hover/item:text-slate-500"
                                        >{{ item.id }}</span
                                    >
                                    <span>{{ item.name }}</span>
                                </span>
                                <span
                                    class="scale-90 rounded-full border border-slate-700/40 bg-slate-800 px-1.5 py-0.5 text-[9px] font-medium text-slate-500"
                                >
                                    Soon
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </nav>

            <!-- User Profile & Session Section -->
            <div
                class="shrink-0 border-t border-slate-800/60 bg-slate-950/20 p-4"
            >
                <div
                    class="mb-3 flex items-center gap-3 rounded-xl border border-slate-800/60 bg-slate-900 p-2"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-orange-500/20 bg-gradient-to-tr from-orange-500/20 to-red-500/25 font-bold text-orange-400 shadow-inner"
                    >
                        {{ getInitials(user?.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-bold leading-snug text-white"
                        >
                            {{ user?.name || 'Staff POS' }}
                        </p>
                        <div class="mt-0.5 flex items-center gap-1.5">
                            <span
                                class="py-0.2 rounded border border-orange-500/20 bg-orange-500/10 px-1.5 text-[9px] font-semibold uppercase tracking-wider text-orange-400"
                            >
                                {{ user?.role || 'Guest' }}
                            </span>
                            <span class="truncate text-[9px] text-slate-400">
                                @ {{ user?.outlet || 'No Outlet' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Logout Link -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-900/35 bg-red-950/30 px-4 py-3 text-xs font-bold text-red-400 transition duration-150 hover:border-red-900/50 hover:bg-red-900/20 active:scale-[0.98]"
                >
                    <LogOut class="h-4 w-4" />
                    <span>Keluar Sesi (Logout)</span>
                </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div
            class="flex h-screen min-w-0 flex-1 flex-col overflow-hidden lg:pl-80"
        >
            <!-- Mobile Sticky Navigation Header -->
            <div
                class="sticky top-0 z-30 flex shrink-0 items-center justify-between border-b border-slate-800/60 bg-slate-900 px-5 py-4 backdrop-blur-md sm:px-6 lg:hidden"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="to-red-650 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-tr from-orange-500 text-sm font-black text-white shadow-md shadow-orange-500/10"
                    >
                        M
                    </div>
                    <span class="text-lg font-bold tracking-wider text-white"
                        >POS MENTAI</span
                    >
                </div>
                <button
                    @click="isMobileOpen = !isMobileOpen"
                    class="rounded-lg p-1 text-slate-400 transition duration-150 hover:bg-slate-800 hover:text-white focus:outline-none"
                >
                    <Menu v-if="!isMobileOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>

            <!-- Page Title Header (Slot) -->
            <header
                v-if="$slots.header"
                class="sticky top-0 z-20 hidden shrink-0 border-b border-slate-900 bg-slate-900/20 px-5 py-6 backdrop-blur-md sm:px-6 lg:block lg:px-8 xl:px-10"
            >
                <slot name="header" />
            </header>

            <div class="custom-scrollbar min-h-0 flex-1 overflow-y-auto">
                <!-- Main Content Area (Slot) -->
                <main class="w-full px-5 py-6 sm:px-6 lg:px-8 lg:py-8 xl:px-10">
                    <slot />
                </main>

                <!-- Sticky footer for minor branding info -->
                <footer
                    class="border-t border-slate-900/60 px-5 py-6 text-center text-xs text-slate-600 sm:px-6 lg:px-8 xl:px-10"
                >
                    POS Mentai &copy; 2026. Hak Cipta Dilindungi.
                </footer>
            </div>
        </div>

        <!-- Custom Glassmorphism Toast Notification for Coming Soon clicks -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toastMessage"
                class="animate-pulse-subtle fixed bottom-6 right-6 z-50 flex w-full max-w-sm items-start gap-3 rounded-xl border border-orange-500/20 bg-slate-900/95 p-4 shadow-2xl shadow-orange-500/5 backdrop-blur-xl"
            >
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10 text-orange-400"
                >
                    🔥
                </div>
                <div class="min-w-0 flex-1">
                    <p
                        class="text-xs font-bold uppercase tracking-wider text-white"
                    >
                        Coming Soon
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-300">
                        {{ toastMessage }}
                    </p>
                </div>
                <button
                    @click="toastMessage = ''"
                    class="hover:text-slate-350 shrink-0 rounded-lg p-0.5 text-slate-500 hover:bg-slate-800 focus:outline-none"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </Transition>
    </div>
</template>

<style>
/* Custom styled scrollbar for sidebar */
.custom-scrollbar::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.35);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(
        180deg,
        rgba(251, 146, 60, 0.48),
        rgba(239, 68, 68, 0.34)
    );
    border-radius: 9999px;
    border: 2px solid rgba(15, 23, 42, 0.7);
    background-clip: padding-box;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(
        180deg,
        rgba(251, 146, 60, 0.72),
        rgba(239, 68, 68, 0.5)
    );
    border-color: rgba(15, 23, 42, 0.9);
}

/* Subtle glowing micro animation for active card */
@keyframes pulseSubtle {
    0%,
    100% {
        border-color: rgba(249, 115, 22, 0.2);
    }
    50% {
        border-color: rgba(249, 115, 22, 0.4);
    }
}
.animate-pulse-subtle {
    animation: pulseSubtle 3s infinite ease-in-out;
}
</style>
