<script setup lang="ts">
import {
    getFCMToken,
    messaging,
    onMessage,
    requestNotificationPermission,
} from '@/firebase';
import { Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BarChart3,
    Bell,
    BellRing,
    CalendarDays,
    ChefHat,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Home,
    LogOut,
    Menu,
    Moon,
    Package,
    Percent,
    RefreshCw,
    Search,
    Settings,
    ShoppingCart,
    Sun,
    Table,
    Users,
    Volume2,
    X,
    HelpCircle,
    BookOpen,
} from '@lucide/vue';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, provide, watch, type Ref } from 'vue';

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

interface SidebarPage {
    key: string;
    title: string;
    route?: string;
    menuIds: number[];
    aliases?: string[];
    routeResolver?: 'default' | 'antrian';
}

interface SidebarCategory {
    phase: string;
    flow: string;
    name: string;
    icon: any;
    pages: SidebarPage[];
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

// Guide (Panduan) Drawer States
import { guideData, getGuideKey } from '@/Components/guideData';

const isGuideOpen = ref(false);
const activeGuideSubTab = ref<string | null>(null);

provide('activeGuideSubTab', activeGuideSubTab);

watch(
    () => route().current(),
    () => {
        activeGuideSubTab.value = null;
    }
);

const currentGuide = computed(() => {
    const routeName = route().current() || 'dashboard';
    const key = getGuideKey(routeName, activeGuideSubTab.value);
    return guideData[key] || guideData['dashboard'];
});

const isSidebarCollapsed = ref(false);
if (typeof window !== 'undefined') {
    isSidebarCollapsed.value =
        localStorage.getItem('sidebar_collapsed') === 'true';
}

const toggleSidebarCollapse = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem(
        'sidebar_collapsed',
        isSidebarCollapsed.value ? 'true' : 'false',
    );
};

const isDarkMode = ref(false);

const initTheme = () => {
    isDarkMode.value = false;
    if (typeof window !== 'undefined') {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const toggleTheme = () => {
    isDarkMode.value = false;
    if (typeof window !== 'undefined') {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// List of all 62 menu items categorized into 10 categories matching menu-per-role.md
const menuData: MenuCategory[] = [
    {
        phase: 'Fase 2',
        flow: 'Meja & Order',
        name: 'Meja',
        icon: Table,
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
            {
                id: 7,
                name: 'Kasbon & Cicilan',
                status: 'ready',
                route: 'transactions.index',
            },
            {
                id: 8,
                name: 'PO / Down Payment',
                status: 'ready',
                route: 'transactions.index',
            },
            {
                id: 9,
                name: 'Diskon & Voucher',
                status: 'ready',
                route: 'kasir.order',
            },
            {
                id: 10,
                name: 'Struk (print / WA / skip)',
                status: 'ready',
                route: 'transactions.index',
            },
            {
                id: 11,
                name: 'Riwayat Transaksi',
                status: 'ready',
                route: 'transactions.index',
            },
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
            {
                id: 33,
                name: 'Template Promo',
                status: 'ready',
                route: 'promos.index',
            },
            {
                id: 34,
                name: 'Diskon Otomatis',
                status: 'ready',
                route: 'kasir.order',
            },
            { id: 35, name: 'Voucher', status: 'ready', route: 'kasir.order' },
        ],
    },
    {
        phase: 'Fase 7',
        flow: 'Karyawan & Shift',
        name: 'Karyawan & Shift',
        icon: CalendarDays,
        items: [
            {
                id: 36,
                name: 'Data Karyawan',
                status: 'ready',
                route: 'employees.index',
            },
            {
                id: 37,
                name: 'Jadwal Shift',
                status: 'ready',
                route: 'schedules.index',
            },
            {
                id: 38,
                name: 'Absensi Digital',
                status: 'ready',
                route: 'attendance.index',
            },
            {
                id: 39,
                name: 'Buka / Tutup Shift Kasir',
                status: 'ready',
                route: 'shifts.index',
            },
            {
                id: 40,
                name: 'Rekap Kas per Shift',
                status: 'ready',
                route: 'shifts.index',
            },
            {
                id: 41,
                name: 'Laporan Kehadiran',
                status: 'ready',
                route: 'attendance.index',
            },
        ],
    },
    /*
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
    */
    {
        phase: 'Fase 9',
        flow: 'Laporan & ERP',
        name: 'Laporan & ERP',
        icon: BarChart3,
        items: [
            {
                id: 45,
                name: 'Dashboard Keuangan',
                status: 'ready',
                route: 'dashboard',
            },
            {
                id: 46,
                name: 'Laporan Penjualan',
                status: 'ready',
                route: 'reports.sales.index',
            },
            {
                id: 47,
                name: 'Laporan Per Outlet',
                status: 'ready',
                route: 'reports.outlets.index',
            },
            {
                id: 48,
                name: 'Laporan Per Kasir',
                status: 'ready',
                route: 'reports.cashiers.index',
            },
            {
                id: 49,
                name: 'Laporan Produk Terlaris',
                status: 'ready',
                route: 'reports.top-products.index',
            },
            {
                id: 50,
                name: 'Laporan Stok & Inventori',
                status: 'ready',
                route: 'reports.inventory.index',
            },
            {
                id: 51,
                name: 'Laporan Absensi & Shift',
                status: 'ready',
                route: 'reports.attendance-shifts.index',
            },
            {
                id: 52,
                name: 'Pengeluaran Operasional',
                status: 'ready',
                route: 'reports.expenses.index',
            },
            {
                id: 53,
                name: 'Export PDF & Excel',
                status: 'ready',
                route: 'reports.exports.index',
            },
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
                status: 'ready',
                route: 'settings.outlets.index',
            },
            {
                id: 55,
                name: 'User & RBAC',
                status: 'ready',
                route: 'settings.rbac.index',
            },
            {
                id: 56,
                name: 'Konfigurasi Payment Gateway',
                status: 'ready',
                route: 'settings.payment-gateway.index',
            },
            {
                id: 57,
                name: 'Konfigurasi Printer',
                status: 'ready',
                route: 'settings.printer.index',
            },
            {
                id: 58,
                name: 'Konfigurasi QR Meja',
                status: 'ready',
                route: 'settings.table-qr.index',
            },
            {
                id: 59,
                name: 'Notifikasi & Alert',
                status: 'ready',
                route: 'settings.notifications.index',
            },
            {
                id: 60,
                name: 'Backup & Keamanan Data',
                status: 'ready',
                route: 'settings.backup-security.index',
            },
            {
                id: 61,
                name: 'Approval Rules',
                status: 'ready',
                route: 'settings.approval-rules.index',
            },
            {
                id: 62,
                name: 'Integrasi GoFood & GrabFood',
                status: 'ready',
                route: 'settings.online-integrations.index',
            },
        ],
    },
];

const menuItemsById = new Map(
    menuData
        .flatMap((category) => category.items)
        .map((item) => [item.id, item]),
);

const sidebarData: SidebarCategory[] = [
    {
        phase: 'Fase 2',
        flow: 'Meja & Order',
        name: 'Meja',
        icon: Table,
        pages: [
            {
                key: 'tables-layout',
                title: 'Operasional Meja',
                route: 'tables.layout',
                menuIds: [19, 20, 21, 12],
                aliases: ['layout meja', 'status meja', 'reservasi', 'qr meja'],
            },
            {
                key: 'settings-tables',
                title: 'Daftar Meja',
                route: 'settings.tables.index',
                menuIds: [19],
                aliases: ['kelola meja', 'daftar meja'],
            },
            {
                key: 'settings-table-qr',
                title: 'QR Meja',
                route: 'settings.table-qr.index',
                menuIds: [58],
                aliases: ['qr code meja', 'download qr'],
            },
        ],
    },
    {
        phase: 'Fase 2',
        flow: 'Meja & Order',
        name: 'Order & Transaksi',
        icon: ShoppingCart,
        pages: [
            {
                key: 'kasir-order',
                title: 'Kasir & Order Aktif',
                route: 'kasir.order',
                menuIds: [1, 2, 3, 4, 5, 6, 9, 34, 35],
                aliases: [
                    'buat order',
                    'split bill',
                    'pembayaran',
                    'diskon',
                    'voucher',
                ],
            },
            {
                key: 'transactions',
                title: 'Transaksi & Riwayat',
                route: 'transactions.index',
                menuIds: [7, 8, 10, 11],
                aliases: [
                    'kasbon',
                    'cicilan',
                    'down payment',
                    'struk',
                    'riwayat transaksi',
                ],
            },
            {
                key: 'settings-payment',
                title: 'Payment Gateway',
                route: 'settings.payment-gateway.index',
                menuIds: [56],
                aliases: [
                    'payment gateway',
                    'midtrans',
                    'qris',
                    'gateway pembayaran',
                ],
            },
            {
                key: 'settings-printer',
                title: 'Konfigurasi Printer',
                route: 'settings.printer.index',
                menuIds: [57],
                aliases: [
                    'setting printer',
                    'printer bluetooth',
                    'cetak struk',
                ],
            },
        ],
    },
    {
        phase: 'Fase 3',
        flow: 'Kitchen & Bar',
        name: 'Kitchen Display',
        icon: ChefHat,
        pages: [
            {
                key: 'kitchen-display',
                title: 'Kitchen Display',
                route: 'kitchen.display',
                menuIds: [13, 14, 16, 17, 18],
                aliases: [
                    'antrian order',
                    'status masak',
                    'riwayat dapur',
                    'estimasi masak',
                ],
                routeResolver: 'antrian',
            },
            {
                key: 'bar-display',
                title: 'Approval Bar',
                route: 'bar.display',
                menuIds: [15],
                aliases: ['approval order selesai'],
            },
        ],
    },
    {
        phase: 'Fase 4',
        flow: 'Pelanggan',
        name: 'Pelanggan',
        icon: Users,
        pages: [
            {
                key: 'customers',
                title: 'CRM Pelanggan',
                route: 'customers.index',
                menuIds: [22, 23, 24, 25],
                aliases: [
                    'customer',
                    'loyalty',
                    'kasbon pelanggan',
                    'riwayat pelanggan',
                ],
            },
            {
                key: 'membership-tiers',
                title: 'Kelola Tier Member',
                route: 'settings.membership-tiers.index',
                menuIds: [23],
                aliases: [
                    'tier member',
                    'atur poin',
                    'level member',
                    'membership tier',
                ],
            },
        ],
    },
    {
        phase: 'Fase 5',
        flow: 'Produk & Stok',
        name: 'Produk & Stok',
        icon: Package,
        pages: [
            {
                key: 'products',
                title: 'Katalog Produk',
                route: 'products.index',
                menuIds: [26, 27],
                aliases: ['varian', 'multi harga'],
            },
            {
                key: 'products-stock',
                title: 'Stok & HPP',
                route: 'products.stock',
                menuIds: [28, 30, 31, 32],
                aliases: ['hpp', 'expired', 'alert stok', 'resep'],
            },
            {
                key: 'raw-materials',
                title: 'Bahan Baku',
                route: 'raw-materials.index',
                menuIds: [29],
            },
        ],
    },
    {
        phase: 'Fase 6',
        flow: 'Promo & Diskon',
        name: 'Promo & Diskon',
        icon: Percent,
        pages: [
            {
                key: 'promos',
                title: 'Template Promo',
                route: 'promos.index',
                menuIds: [33],
            },
        ],
    },
    {
        phase: 'Fase 7',
        flow: 'Karyawan & Shift',
        name: 'Karyawan & Shift',
        icon: CalendarDays,
        pages: [
            {
                key: 'settings-outlets',
                title: 'Outlet & Cabang',
                route: 'settings.outlets.index',
                menuIds: [54],
                aliases: ['kelola cabang', 'tambah outlet'],
            },
            {
                key: 'employees',
                title: 'Data Karyawan',
                route: 'employees.index',
                menuIds: [36],
            },
            {
                key: 'settings-rbac',
                title: 'User & RBAC',
                route: 'settings.rbac.index',
                menuIds: [55],
                aliases: ['hak akses', 'role permission', 'kelola user'],
            },
            {
                key: 'shifts',
                title: 'Shift & Absensi',
                route: 'shifts.index',
                menuIds: [37, 38, 39, 40, 41],
                aliases: [
                    'buka shift',
                    'tutup shift',
                    'rekap kas',
                    'clock in',
                    'clock out',
                    'laporan kehadiran',
                    'jadwal shift',
                ],
            },
            {
                key: 'settings-approval',
                title: 'Approval Rules',
                route: 'settings.approval-rules.index',
                menuIds: [61],
                aliases: ['aturan approval', 'spv approval'],
            },
        ],
    },
    /*
    {
        phase: 'Fase 8',
        flow: 'Order Online',
        name: 'Order Online',
        icon: Globe,
        pages: [
            {
                key: 'online-orders',
                title: 'Inbox Order Online',
                route: 'online-orders.index',
                menuIds: [42, 43, 44],
                aliases: ['gofood', 'grabfood', 'riwayat order online'],
            },
            {
                key: 'settings-online',
                title: 'Integrasi Online',
                route: 'settings.online-integrations.index',
                menuIds: [62],
                aliases: [
                    'integrasi gofood',
                    'integrasi grabfood',
                    'grab merchant',
                    'go merchant',
                ],
            },
        ],
    },
    */
    {
        phase: 'Fase 9',
        flow: 'Laporan & ERP',
        name: 'Laporan & ERP',
        icon: BarChart3,
        pages: [
            {
                key: 'report-sales',
                title: 'Laporan Transaksi & Kas',
                route: 'reports.sales.index',
                menuIds: [46, 47, 48, 49, 52],
                aliases: [
                    'penjualan',
                    'outlet',
                    'kasir',
                    'produk terlaris',
                    'pengeluaran',
                ],
            },
            {
                key: 'report-inventory',
                title: 'Laporan Sumber Daya',
                route: 'reports.inventory.index',
                menuIds: [50, 51, 53],
                aliases: [
                    'inventori',
                    'stok',
                    'kehadiran',
                    'rekap shift',
                    'export data',
                ],
            },
            {
                key: 'settings-notifications',
                title: 'Keamanan & Notifikasi',
                route: 'settings.notifications.index',
                menuIds: [59, 60],
                aliases: [
                    'notifikasi',
                    'alert whatsapp',
                    'backup database',
                    'security log',
                ],
            },
        ],
    },
];

// Filter menu items based on RBAC and search query
const filteredSidebar = computed(() => {
    const query = searchQuery.value.toLowerCase();

    return sidebarData
        .map((category) => {
            // 1. RBAC Filter: Only include pages the user has access to
            let filteredPages = category.pages.filter((page) => {
                // If menuIds is empty, we assume it's a safe/public page, otherwise check access
                if (!page.menuIds || page.menuIds.length === 0) return true;
                return getReadyCount(page.menuIds) > 0;
            });

            // 2. Search Filter
            if (query) {
                filteredPages = filteredPages.filter((page) => {
                    const featureNames = page.menuIds
                        .map((menuId) => menuItemsById.get(menuId)?.name ?? '')
                        .join(' ');
                    const aliases = page.aliases?.join(' ') ?? '';
                    const haystack =
                        `${page.title} ${aliases} ${featureNames}`.toLowerCase();

                    return haystack.includes(query);
                });
            }

            return {
                ...category,
                pages: filteredPages,
                isOpenFiltered: filteredPages.length > 0,
            };
        })
        .filter((category) => category.pages.length > 0);
});

const activeCategory = computed(() => {
    return filteredSidebar.value.find((category) => isCategoryActive(category));
});

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

const resolveSidebarRoute = (pageItem: SidebarPage): string => {
    if (pageItem.routeResolver === 'antrian') {
        return getRouteForAntrian();
    }

    return pageItem.route ?? 'dashboard';
};

// Helper to check if a specific sub-page item is active, including nested sub-routes
const isSubPageActive = (pageItem: SidebarPage) => {
    if (pageItem.key === 'products-stock') {
        return (
            route().current('products.stock') ||
            route().current('products.hpp') ||
            route().current('stock-alerts.index') ||
            route().current('expired-tracking.index')
        );
    }
    if (pageItem.key === 'shifts') {
        return (
            route().current('shifts.index') ||
            route().current('attendance.index') ||
            route().current('schedules.index')
        );
    }
    if (pageItem.key === 'report-sales') {
        return (
            route().current('reports.sales.index') ||
            route().current('reports.outlets.index') ||
            route().current('reports.cashiers.index') ||
            route().current('reports.top-products.index') ||
            route().current('reports.expenses.index') ||
            route().current('reports.shift-finance.index')
        );
    }
    if (pageItem.key === 'report-inventory') {
        return (
            route().current('reports.inventory.index') ||
            route().current('reports.attendance-shifts.index') ||
            route().current('reports.exports.index')
        );
    }
    if (pageItem.key === 'settings-notifications') {
        return (
            route().current('settings.notifications.index') ||
            route().current('settings.backup-security.index')
        );
    }
    const resolved = resolveSidebarRoute(pageItem);
    return route().current(resolved);
};

// Helper to check if a category is active (has an active page inside it)
const isCategoryActive = (category: SidebarCategory) => {
    return category.pages.some((pageItem) => {
        return isSubPageActive(pageItem);
    });
};

const getReadyCount = (menuIds: number[]) => {
    return menuIds.filter((menuId) => isMenuReady(menuId)).length;
};

const isTestingPush = ref(false);

const triggerTestPushNotification = async () => {
    if (isTestingPush.value) return;
    isTestingPush.value = true;
    try {
        const response = await axios.post(route('settings.notifications.test-push'));
        alert(response.data.message || 'Tes push notifikasi berhasil dikirim!');
    } catch (err) {
        console.error('Error triggering test push notification:', err);
        let errMsg = 'Gagal mengirim tes push notifikasi. Pastikan izin notifikasi browser sudah aktif.';
        if (axios.isAxiosError(err)) {
            errMsg = err.response?.data?.message || errMsg;
        }
        alert(errMsg);
    } finally {
        isTestingPush.value = false;
    }
};

// --- GLOBAL ORDER AUDIO NOTIFICATIONS ---
const knownOrders = ref<Map<string, string>>(new Map());
const isInitialLoad = ref(true);
let orderAudioPollInterval: number | undefined;

const playChimeAndSpeakGlobal = (
    text: string,
    volume = 1.0,
    rate = 0.9,
    pitch = 1.05,
) => {
    try {
        const runChime = () => {
            const audio = new Audio('/notif_minpo.mp3');
            audio.volume = volume;
            audio.play().catch((err) => {
                console.warn('Gagal memutar audio bel notifikasi global:', err);
            });
        };
        runChime();
    } catch (e) {
        console.error('Error playing global audio chime:', e);
    }

    setTimeout(() => {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        utterance.volume = volume;
        utterance.rate = rate;
        utterance.pitch = pitch;

        const voices = window.speechSynthesis.getVoices();
        const idVoice = voices.find((v) => v.lang.includes('id'));
        if (idVoice) utterance.voice = idVoice;

        window.speechSynthesis.speak(utterance);
    }, 450);
};

const testVoiceGlobal = () => {
    playChimeAndSpeakGlobal(
        'Uji coba pengeras suara global. Halo mentai restoran.',
    );
};

const testAlarmGlobal = () => {
    try {
        const audioCtx = new (
            window.AudioContext || (window as any).webkitAudioContext
        )();

        const runAlarm = () => {
            const playTone = (
                freq: number,
                start: number,
                duration: number,
            ) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.frequency.setValueAtTime(freq, start);
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(0.2, start + 0.05);
                gain.gain.exponentialRampToValueAtTime(
                    0.0001,
                    start + duration,
                );
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start(start);
                osc.stop(start + duration);
            };
            playTone(587.33, audioCtx.currentTime, 0.4);
            playTone(440.0, audioCtx.currentTime + 0.15, 0.6);
        };

        if (audioCtx.state === 'suspended') {
            audioCtx
                .resume()
                .then(runAlarm)
                .catch(() => runAlarm());
        } else {
            runAlarm();
        }
    } catch (e) {
        console.error(e);
    }
};

interface AudioUpdateOrder {
    id: string;
    orderNumber: string;
    status: string;
    customerName?: string | null;
    tableLabel: string;
    source: string;
}

interface AudioUpdateResponse {
    orders: AudioUpdateOrder[];
    voiceSettings: {
        enabled: boolean;
        volume: number;
        rate: number;
        pitch: number;
    } | null;
}

const checkOrderAudioUpdates = async () => {
    try {
        const response = await axios.get<AudioUpdateResponse>(
            route('orders.audio-updates'),
        );
        const { orders, voiceSettings } = response.data;
        if (!voiceSettings || !voiceSettings.enabled) {
            return;
        }

        const isKitchenPage =
            route().current('kitchen.display') ||
            route().current('bar.display');

        orders.forEach((order) => {
            const previousStatus = knownOrders.value.get(order.id);

            if (previousStatus === undefined) {
                knownOrders.value.set(order.id, order.status);
                if (!isInitialLoad.value && !isKitchenPage) {
                    const customer =
                        order.customerName ||
                        (order.source === 'qr_meja'
                            ? 'Pelanggan Meja'
                            : 'Pelanggan');
                    const tableInfo =
                        order.tableLabel && order.tableLabel !== 'Takeaway'
                            ? `Meja ${order.tableLabel}`
                            : 'Takeaway';
                    const msg = `Pesanan baru masuk atas nama ${customer}, ${tableInfo}.`;
                    playChimeAndSpeakGlobal(
                        msg,
                        voiceSettings.volume,
                        voiceSettings.rate,
                        voiceSettings.pitch,
                    );
                }
            } else if (previousStatus !== order.status) {
                knownOrders.value.set(order.id, order.status);

                if (!isInitialLoad.value) {
                    let msg = '';
                    if (order.status === 'in_progress') {
                        msg = `Pesanan nomor ${order.orderNumber} mulai dimasak.`;
                    } else if (
                        order.status === 'waiting_bar_approval' ||
                        order.status === 'ready'
                    ) {
                        if (
                            previousStatus !== 'waiting_bar_approval' &&
                            previousStatus !== 'ready'
                        ) {
                            msg = `Pesanan nomor ${order.orderNumber} selesai dimasak.`;
                        }
                    } else if (order.status === 'completed') {
                        msg = `Pesanan nomor ${order.orderNumber} telah diterima pelanggan.`;
                    }

                    if (msg) {
                        playChimeAndSpeakGlobal(
                            msg,
                            voiceSettings.volume,
                            voiceSettings.rate,
                            voiceSettings.pitch,
                        );
                    }
                }
            }
        });

        const currentIds = new Set(orders.map((o) => o.id));
        knownOrders.value.forEach((_, id) => {
            if (!currentIds.has(id)) {
                knownOrders.value.delete(id);
            }
        });

        if (isInitialLoad.value) {
            isInitialLoad.value = false;
        }
    } catch (e) {
        console.error('Error fetching order audio updates:', e);
    }
};

const permissionStatus = ref<
    NotificationPermission | 'unsupported' | 'checking'
>('checking');

const checkNotificationPermission = () => {
    if (typeof window !== 'undefined') {
        if ('Notification' in window) {
            permissionStatus.value = Notification.permission;
            if (Notification.permission === 'granted') {
                syncFcmToken();
            }
        } else {
            permissionStatus.value = 'unsupported';
        }
    }
};

const syncFcmToken = async () => {
    try {
        const token = await getFCMToken();
        if (token) {
            const lastSyncedToken = localStorage.getItem(
                'last_synced_fcm_token',
            );
            if (lastSyncedToken !== token) {
                await axios.post(route('settings.notifications.fcm-token'), {
                    token: token,
                });
                localStorage.setItem('last_synced_fcm_token', token);
            }
        }
    } catch (err) {
        console.error('Error syncing FCM token:', err);
    }
};

const requestPermission = async () => {
    if (typeof window === 'undefined' || !('Notification' in window)) return;
    try {
        const token = await requestNotificationPermission();
        if (token) {
            await axios.post(route('settings.notifications.fcm-token'), {
                token: token,
            });
            localStorage.setItem('last_synced_fcm_token', token);
            permissionStatus.value = 'granted';
        } else {
            permissionStatus.value = Notification.permission;
        }
    } catch (err) {
        console.error('Error requesting notification permission:', err);
    }
};

onMounted(() => {
    initTheme();
    checkNotificationPermission();

    // Listen to Firebase Foreground Messages
    if (messaging) {
        onMessage(messaging, (payload) => {
            console.log('Foreground Message received: ', payload);

            const title = payload.notification?.title || 'Notifikasi Baru';
            const body =
                payload.notification?.body || 'Cek aplikasi untuk detailnya.';

            // 1. Play sound
            playChimeAndSpeakGlobal(body);

            // 2. Show native notification if permission granted (and even if tab is active)
            if (Notification.permission === 'granted') {
                new Notification(title, {
                    body: body,
                    icon: '/favicon.ico',
                });
            }
        });
    }

    if (typeof window !== 'undefined') {
        window.addEventListener('focus', checkNotificationPermission);
        document.addEventListener(
            'visibilitychange',
            checkNotificationPermission,
        );
    }
    if (user.value && user.value.outlet_id) {
        checkOrderAudioUpdates();
        orderAudioPollInterval = window.setInterval(
            checkOrderAudioUpdates,
            10000,
        );
    }
});

onBeforeUnmount(() => {
    if (orderAudioPollInterval) {
        window.clearInterval(orderAudioPollInterval);
    }
    if (typeof window !== 'undefined') {
        window.removeEventListener('focus', checkNotificationPermission);
        document.removeEventListener(
            'visibilitychange',
            checkNotificationPermission,
        );
    }
});
</script>

<template>
    <div
        class="flex h-screen flex-col overflow-hidden bg-[oklch(98.8%_0.003_106.5)] font-sans text-stone-900 antialiased selection:bg-orange-500 selection:text-white dark:bg-[oklch(70.9%_0.01_56.259)] dark:text-slate-100 lg:flex-row"
    >
        <!-- Blocking Overlay for Notification Permission -->
        <div
            v-if="
                permissionStatus !== 'granted' &&
                permissionStatus !== 'unsupported' &&
                permissionStatus !== 'checking'
            "
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-md dark:bg-slate-950/80"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl transition-all dark:border-slate-800 dark:bg-slate-900"
            >
                <!-- Glowing Header Section -->
                <div
                    class="relative bg-gradient-to-br from-orange-500 to-red-600 px-6 py-8 text-center text-white"
                >
                    <div
                        class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.15)_0%,transparent_70%)]"
                    ></div>
                    <div
                        class="relative z-10 mx-auto flex h-16 w-16 animate-pulse items-center justify-center rounded-full bg-white/20 backdrop-blur-md"
                    >
                        <BellRing class="h-8 w-8 text-white" />
                    </div>
                    <h3
                        class="relative z-10 mt-3 text-xl font-bold tracking-wide"
                    >
                        Izin Notifikasi Wajib
                    </h3>
                    <p class="relative z-10 mt-1 text-xs text-orange-50/90">
                        Untuk memantau pesanan mandiri (QR) & pesanan online
                        secara real-time.
                    </p>
                </div>

                <!-- Content Area -->
                <div class="p-6">
                    <!-- Case 1: Default / Request Permission -->
                    <div
                        v-if="permissionStatus === 'default'"
                        class="space-y-6"
                    >
                        <div
                            class="rounded-xl bg-orange-50 p-4 dark:bg-orange-950/20"
                        >
                            <h4
                                class="text-orange-850 dark:text-orange-350 text-xs font-semibold"
                            >
                                Mengapa notifikasi ini wajib?
                            </h4>
                            <p
                                class="text-orange-750/95 mt-1 text-xs leading-relaxed dark:text-orange-400/90"
                            >
                                Fitur ini menginfokan pesanan QR meja pelanggan,
                                pesanan online (GrabFood/GoFood), serta
                                peringatan stok/kasbon secara instan.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-600 dark:bg-orange-900/40 dark:text-orange-400"
                                >
                                    1
                                </div>
                                <p
                                    class="text-xs leading-relaxed text-stone-600 dark:text-slate-400"
                                >
                                    Klik tombol
                                    <span
                                        class="font-semibold text-stone-800 dark:text-slate-200"
                                        >"Aktifkan Notifikasi"</span
                                    >
                                    di bawah.
                                </p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-600 dark:bg-orange-900/40 dark:text-orange-400"
                                >
                                    2
                                </div>
                                <p
                                    class="text-xs leading-relaxed text-stone-600 dark:text-slate-400"
                                >
                                    Klik
                                    <span
                                        class="font-semibold text-stone-800 dark:text-slate-200"
                                        >"Izinkan"</span
                                    >
                                    pada pop-up konfirmasi browser di pojok kiri
                                    atas.
                                </p>
                            </div>
                        </div>

                        <button
                            @click="requestPermission"
                            type="button"
                            class="relative flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-red-600 px-4 py-3 text-sm font-semibold text-white shadow-md transition duration-150 hover:brightness-110 active:scale-[0.98]"
                        >
                            <span>Aktifkan Notifikasi</span>
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Case 2: Denied / Blocked by browser -->
                    <div
                        v-else-if="permissionStatus === 'denied'"
                        class="space-y-6"
                    >
                        <div
                            class="flex items-start gap-3 rounded-xl bg-red-50 p-4 dark:bg-red-950/20"
                        >
                            <AlertTriangle
                                class="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-400"
                            />
                            <div>
                                <h4
                                    class="text-xs font-semibold text-red-800 dark:text-red-300"
                                >
                                    Izin Notifikasi Diblokir
                                </h4>
                                <p
                                    class="mt-1 text-xs leading-relaxed text-red-700 dark:text-red-400"
                                >
                                    Akses diblokir karena izin notifikasi
                                    ditolak. Anda harus membukanya secara manual
                                    di setelan browser.
                                </p>
                            </div>
                        </div>

                        <div
                            class="space-y-3 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-slate-800 dark:bg-slate-900/50"
                        >
                            <h5
                                class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-slate-400"
                            >
                                Cara Mengaktifkan Kembali:
                            </h5>
                            <ol
                                class="space-y-2 text-xs text-stone-600 dark:text-slate-400"
                            >
                                <li class="flex items-start gap-2">
                                    <span
                                        class="font-bold text-stone-800 dark:text-slate-200"
                                        >1.</span
                                    >
                                    <span
                                        >Klik ikon <strong>Gembok</strong> 🔒 di
                                        sebelah kiri kolom alamat URL browser
                                        Anda.</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="font-bold text-stone-800 dark:text-slate-200"
                                        >2.</span
                                    >
                                    <span
                                        >Ubah opsi
                                        <strong>Notifikasi</strong> menjadi
                                        <strong>Izinkan</strong>.</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="font-bold text-stone-800 dark:text-slate-200"
                                        >3.</span
                                    >
                                    <span
                                        >Kembali ke tab ini dan sistem akan
                                        mendeteksi otomatis, atau klik tombol di
                                        bawah untuk memeriksa ulang.</span
                                    >
                                </li>
                            </ol>
                        </div>

                        <button
                            @click="checkNotificationPermission"
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 active:scale-[0.98] dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80"
                        >
                            <RefreshCw class="h-4 w-4" />
                            <span>Periksa Ulang Izin Notifikasi</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backdrop for mobile sidebar drawer -->
        <div
            v-if="isMobileOpen"
            @click="isMobileOpen = false"
            class="fixed inset-0 z-40 bg-stone-950/40 backdrop-blur-sm transition-opacity duration-300 dark:bg-slate-950/80 lg:hidden"
        ></div>

        <!-- Sidebar Navigation -->
        <aside
            :class="[
                'fixed bottom-0 left-0 top-0 z-40 flex flex-col border-r border-stone-200 bg-white/90 backdrop-blur-xl transition-all duration-300 dark:border-slate-800/80 dark:bg-slate-900/90 lg:translate-x-0',
                isSidebarCollapsed ? 'w-20' : 'w-80',
                isMobileOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo Section -->
            <div
                :class="[
                    'flex shrink-0 items-center gap-3 border-b border-stone-200 py-6 transition-all duration-300 dark:border-slate-800/60',
                    isSidebarCollapsed ? 'justify-center px-4' : 'px-6',
                ]"
            >
                <div
                    v-if="isSidebarCollapsed"
                    class="h-10 w-10 shrink-0 overflow-hidden rounded-xl border border-stone-200 bg-stone-100 p-1 backdrop-blur-sm dark:border-slate-700 dark:bg-white/10"
                >
                    <img
                        src="/images/pos_logo.png"
                        class="h-full w-full object-contain"
                        alt="Logo"
                    />
                </div>
                <div v-else class="flex items-center gap-3">
                    <div
                        class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-stone-200 bg-white p-1 shadow-lg shadow-orange-500/10 dark:border-slate-700"
                    >
                        <img
                            src="/images/pos_logo.png"
                            class="h-full w-full object-contain"
                            alt="Logo"
                        />
                    </div>
                    <div>
                        <h1
                            class="text-base font-extrabold leading-none tracking-wider text-stone-900 dark:text-white"
                        >
                            POS MENTAI
                        </h1>
                        <span
                            class="mt-1 block text-[10px] font-semibold uppercase tracking-widest text-orange-500 dark:text-orange-400"
                        >
                            Management Suite
                        </span>
                    </div>
                </div>
            </div>

            <!-- Search Area -->
            <div
                v-if="!isSidebarCollapsed"
                class="shrink-0 border-b border-stone-200 px-4 py-4 dark:border-slate-800"
            >
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-stone-400 dark:text-slate-500"
                    >
                        <Search class="h-4 w-4" />
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Cari halaman atau fitur..."
                        class="w-full rounded-[16px] border-2 border-stone-200 bg-stone-100/50 py-2.5 pl-10 pr-4 text-xs font-semibold text-stone-800 placeholder-stone-400 transition duration-200 focus:border-orange-500 focus:outline-none dark:border-white/10 dark:bg-slate-950/60 dark:text-slate-200 dark:placeholder-slate-500"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-stone-400 hover:text-slate-300 dark:text-slate-500"
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
                        prefetch
                        @click="isMobileOpen = false"
                        :title="
                            isSidebarCollapsed ? 'Dashboard Utama' : undefined
                        "
                        :class="[
                            'flex w-full items-center gap-3 rounded-xl transition-all duration-200 border-2 border-stone-950 dark:border-slate-800',
                            isSidebarCollapsed ? 'justify-center p-3' : 'px-3 py-2.5',
                            route().current('dashboard')
                                ? 'bg-orange-500 text-stone-950 dark:border-orange-400 font-black shadow-md shadow-orange-500/10'
                                : 'bg-white text-stone-900 hover:bg-stone-50 hover:text-stone-950 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900 dark:hover:text-white',
                        ]"
                    >
                        <Home
                            :class="[
                                'h-4.5 w-4.5 shrink-0 transition-colors duration-155',
                                route().current('dashboard')
                                    ? 'text-stone-950 font-black'
                                    : 'text-stone-700 dark:text-slate-300',
                            ]"
                        />
                        <span
                            v-if="!isSidebarCollapsed"
                            class="truncate text-xs font-bold uppercase tracking-[0.18em] transition duration-155"
                            :class="[
                                route().current('dashboard')
                                    ? 'text-stone-950 font-black'
                                    : 'text-stone-900 dark:text-slate-200',
                            ]"
                        >
                            Dashboard Utama
                        </span>
                    </Link>
                </div>

                <!-- Dynamic Categories -->
                <div
                    v-for="category in filteredSidebar"
                    :key="category.name"
                    class="px-2 space-y-1.5"
                >
                    <Link
                        :href="route(resolveSidebarRoute(category.pages[0]))"
                        prefetch
                        @click="isMobileOpen = false"
                        :title="isSidebarCollapsed ? category.name : undefined"
                        :class="[
                            'group flex w-full items-center gap-3 rounded-xl transition-all duration-200 border-2 border-stone-950 dark:border-slate-800',
                            isSidebarCollapsed ? 'justify-center p-3' : 'px-3 py-2.5',
                            isCategoryActive(category)
                                ? 'bg-orange-500 text-stone-950 dark:border-orange-400 font-black shadow-md shadow-orange-500/10'
                                : 'bg-white text-stone-900 hover:bg-stone-50 hover:text-stone-950 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900 dark:hover:text-white',
                        ]"
                    >
                        <component
                            :is="category.icon"
                            :class="[
                                'h-4.5 w-4.5 shrink-0 transition-colors duration-155',
                                isCategoryActive(category)
                                    ? 'text-stone-950 font-black'
                                    : 'text-stone-700 dark:text-slate-300 group-hover:text-stone-950 dark:group-hover:text-white',
                            ]"
                        />
                        <span
                            v-if="!isSidebarCollapsed"
                            :class="[
                                'truncate text-xs font-bold uppercase tracking-[0.18em] transition-colors duration-155',
                                isCategoryActive(category)
                                    ? 'text-stone-950 font-black'
                                    : 'text-stone-900 dark:text-slate-200 group-hover:text-stone-950 dark:group-hover:text-white',
                            ]"
                        >
                            {{ category.name }}
                        </span>
                    </Link>
                </div>
            </nav>

            <!-- User Profile & Session Section -->
            <div
                :class="[
                    'shrink-0 border-t border-stone-200 bg-stone-50/50 p-3 transition-all duration-300 dark:border-slate-800/60 dark:bg-slate-950/20',
                    isSidebarCollapsed
                        ? 'flex flex-col items-center gap-2'
                        : 'flex flex-col gap-2.5',
                ]"
            >
                <!-- Profile Row -->
                <div
                    :class="[
                        'flex items-center gap-2.5 transition-all duration-300',
                        isSidebarCollapsed
                            ? 'h-10 w-10 justify-center rounded-lg border border-stone-200 bg-white dark:border-slate-800/60 dark:bg-slate-900'
                            : 'w-full justify-between',
                    ]"
                >
                    <!-- Avatar & Info -->
                    <div
                        class="flex min-w-0 items-center gap-2.5"
                        v-if="!isSidebarCollapsed"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-orange-500/20 bg-gradient-to-tr from-orange-500/20 to-red-500/25 text-xs font-bold text-orange-500 shadow-inner dark:text-orange-400"
                        >
                            {{ getInitials(user?.name) }}
                        </div>
                        <div class="min-w-0">
                            <p
                                class="truncate text-xs font-bold leading-tight text-stone-900 dark:text-white"
                            >
                                {{ user?.name || 'Staff' }}
                            </p>
                            <p
                                class="mt-0.5 truncate text-[9px] leading-none text-stone-500 dark:text-slate-400"
                            >
                                <span
                                    class="mr-1 font-semibold uppercase text-orange-600 dark:text-orange-400"
                                    >{{ user?.role || 'Guest' }}</span
                                >
                                @ {{ user?.outlet || 'No Outlet' }}
                            </p>
                        </div>
                    </div>

                    <!-- Avatar only (when collapsed) -->
                    <div
                        v-else
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-orange-500/20 bg-gradient-to-tr from-orange-500/20 to-red-500/25 text-xs font-bold text-orange-500 dark:text-orange-400"
                        :title="`${user?.name || 'Staff'} (${user?.role || 'Guest'}) @ ${user?.outlet || 'No Outlet'}`"
                    >
                        {{ getInitials(user?.name) }}
                    </div>

                    <!-- Logout Button (Only if not collapsed) -->
                    <Link
                        v-if="!isSidebarCollapsed"
                        :href="route('logout')"
                        method="post"
                        as="button"
                        title="Keluar Sesi (Logout)"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-red-50/50 text-red-500 transition hover:bg-red-100 hover:text-red-600 active:scale-95 dark:border-red-950 dark:bg-red-950/20 dark:text-red-400 dark:hover:bg-red-900/30"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <!-- Utilitas Row (Test alarm, Test voice, Collapse button) -->
                <div
                    :class="[
                        'grid w-full gap-1.5',
                        isSidebarCollapsed ? 'grid-cols-1' : 'grid-cols-3',
                    ]"
                >

                    <!-- Test Push Notification -->
                    <button
                        @click="triggerTestPushNotification"
                        type="button"
                        title="Test Push Notifikasi"
                        :disabled="isTestingPush"
                        :class="[
                            'flex items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-500 transition hover:bg-stone-50 hover:text-stone-800 active:scale-95 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200',
                            isSidebarCollapsed ? 'h-10 w-10' : 'h-8.5',
                            isTestingPush ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    >
                        <RefreshCw
                            v-if="isTestingPush"
                            class="h-3.5 w-3.5 animate-spin text-orange-500 dark:text-orange-400"
                        />
                        <Bell
                            v-else
                            class="h-3.5 w-3.5 text-orange-500 dark:text-orange-400"
                        />
                    </button>

                    <!-- Test Voice -->
                    <button
                        @click="testVoiceGlobal"
                        type="button"
                        title="Test Suara"
                        :class="[
                            'flex items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-500 transition hover:bg-stone-50 hover:text-stone-800 active:scale-95 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200',
                            isSidebarCollapsed ? 'h-10 w-10' : 'h-8.5',
                        ]"
                    >
                        <Volume2
                            class="h-3.5 w-3.5 text-fuchsia-500 dark:text-fuchsia-400"
                        />
                    </button>

                    <!-- Collapse / Expand Sidebar -->
                    <button
                        @click="toggleSidebarCollapse"
                        type="button"
                        :title="
                            isSidebarCollapsed
                                ? 'Buka Sidebar'
                                : 'Tutup Sidebar'
                        "
                        :class="[
                            'flex items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-500 transition hover:bg-stone-50 hover:text-stone-800 active:scale-95 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200',
                            isSidebarCollapsed ? 'h-10 w-10' : 'h-8.5',
                        ]"
                    >
                        <component
                            :is="
                                isSidebarCollapsed
                                    ? ChevronsRight
                                    : ChevronsLeft
                            "
                            class="h-3.5 w-3.5"
                        />
                    </button>

                    <!-- Logout button (when collapsed) -->
                    <Link
                        v-if="isSidebarCollapsed"
                        :href="route('logout')"
                        method="post"
                        as="button"
                        title="Keluar Sesi (Logout)"
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-red-200 bg-red-50/50 text-red-500 transition hover:bg-red-100 hover:text-red-600 active:scale-95 dark:border-red-950 dark:bg-red-950/20 dark:text-red-400 dark:hover:bg-red-900/30"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div
            :class="[
                'flex h-screen min-w-0 flex-1 flex-col overflow-hidden transition-all duration-300',
                isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-80',
            ]"
        >
            <!-- Mobile Sticky Navigation Header -->
            <div
                class="sticky top-0 z-30 flex shrink-0 items-center justify-between border-b border-stone-200 bg-white/90 px-5 py-4 backdrop-blur-md dark:border-slate-800/60 dark:bg-slate-900 sm:px-6 lg:hidden"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-tr from-orange-500 to-red-600 text-sm font-black text-stone-900 shadow-md shadow-orange-500/10 dark:text-white"
                    >
                        M
                    </div>
                    <span
                        class="text-lg font-bold tracking-wider text-stone-900 dark:text-white"
                        >POS MENTAI</span
                    >
                </div>
                <div class="flex items-center gap-2">

                    <button
                        @click="triggerTestPushNotification"
                        type="button"
                        :disabled="isTestingPush"
                        class="rounded-lg p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white disabled:opacity-50"
                        title="Test Push Notifikasi"
                    >
                        <RefreshCw
                            v-if="isTestingPush"
                            class="h-5 w-5 animate-spin text-orange-500 dark:text-orange-400"
                        />
                        <Bell
                            v-else
                            class="h-5 w-5 text-orange-500 dark:text-orange-400"
                        />
                    </button>
                    <button
                        @click="testVoiceGlobal"
                        type="button"
                        class="rounded-lg p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                        title="Uji coba suara"
                    >
                        <Volume2
                            class="h-5 w-5 text-fuchsia-500 dark:text-fuchsia-400"
                        />
                    </button>
                    <button
                        @click="isMobileOpen = !isMobileOpen"
                        class="rounded-lg p-1 text-stone-500 transition duration-150 hover:bg-stone-100 hover:text-stone-900 focus:outline-none dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                    >
                        <Menu v-if="!isMobileOpen" class="h-6 w-6" />
                        <X v-else class="h-6 w-6" />
                    </button>
                </div>
            </div>

            <!-- Page Title Header (Slot) -->
            <header
                v-if="$slots.header"
                class="shrink-0 border-b border-stone-200 bg-white/40 px-5 py-6 dark:border-slate-900 dark:bg-slate-900/20 sm:px-6 lg:px-8 xl:px-10"
            >
                <slot name="header" />
            </header>

            <!-- Horizontal Page Tabs (for sub-menus) -->
            <div
                v-if="activeCategory && activeCategory.pages.length > 1"
                class="relative z-10 border-b border-stone-200 bg-white/60 px-5 py-2.5 dark:border-slate-900 dark:bg-slate-900/40 sm:px-6 lg:px-8 xl:px-10"
            >
                <div
                    class="no-scrollbar flex w-full items-center gap-2.5 overflow-x-auto"
                >
                    <Link
                        v-for="pageItem in activeCategory.pages"
                        :key="pageItem.key"
                        :href="route(pageItem.route ?? 'dashboard')"
                        prefetch
                        :class="[
                            'flex-1 cursor-pointer whitespace-nowrap px-4 py-2 text-center text-xs font-bold uppercase tracking-wider transition-all duration-200 rounded-full border-2 border-stone-950 dark:border-slate-800',
                            isSubPageActive(pageItem)
                                ? 'bg-orange-500 text-stone-950 dark:border-orange-400 font-black shadow-lg shadow-orange-500/15'
                                : 'bg-white text-stone-600 hover:bg-stone-50 hover:text-stone-950 dark:bg-slate-950 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white',
                        ]"
                    >
                        {{ pageItem.title }}
                    </Link>
                </div>
            </div>
            <div class="custom-scrollbar min-h-0 flex-1 overflow-y-auto">
                <!-- Main Content Area (Slot) -->
                <main class="w-full px-5 py-6 sm:px-6 lg:px-8 lg:py-8 xl:px-10">
                    <slot />
                </main>

                <!-- Sticky footer for minor branding info -->
                <footer
                    class="border-t border-stone-200 px-5 py-6 text-center text-xs text-stone-400 dark:border-slate-900/60 dark:text-slate-600 sm:px-6 lg:px-8 xl:px-10"
                >
                    POS Mentai &copy; 2026. Hak Cipta Dilindungi.
                </footer>
            </div>
        </div>

        <!-- Floating Action Button (FAB) Panduan -->
        <div class="fixed bottom-6 right-6 z-[60] flex items-center">
            <button
                @click="isGuideOpen = true"
                class="group flex items-center gap-2 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-3.5 text-stone-950 shadow-xl shadow-orange-500/20 hover:shadow-orange-500/40 hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 dark:focus:ring-offset-slate-950 cursor-pointer"
                title="Buka Panduan Fitur"
                id="btn-panduan-fab"
            >
                <HelpCircle class="h-5 w-5 text-stone-950 animate-pulse group-hover:rotate-12 transition-transform duration-200" />
                <span class="text-xs font-black uppercase tracking-wider text-stone-950">Panduan</span>
            </button>
        </div>

        <!-- Panduan Drawer (Right Side) -->
        <div v-if="isGuideOpen" class="fixed inset-0 z-[100] overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <!-- Backdrop overlay with blur effect -->
                <div
                    @click="isGuideOpen = false"
                    class="absolute inset-0 bg-stone-950/60 backdrop-blur-sm transition-opacity duration-300 ease-out cursor-pointer"
                ></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <!-- Drawer panel -->
                    <div
                        class="pointer-events-auto w-screen max-w-md transform transition-all duration-300 ease-in-out"
                        :class="isGuideOpen ? 'translate-x-0' : 'translate-x-full'"
                    >
                        <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-2xl dark:bg-slate-900 border-l border-stone-200 dark:border-slate-800/80">
                            <!-- Header -->
                            <div class="bg-gradient-to-r from-stone-900 to-stone-950 px-6 py-5 dark:from-slate-950 dark:to-slate-900 border-b border-stone-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="rounded-xl bg-orange-500/10 p-2 border border-orange-500/20">
                                            <BookOpen class="h-5 w-5 text-orange-500" />
                                        </div>
                                        <div>
                                            <h2 class="text-sm font-black uppercase tracking-wider text-white" id="slide-over-title">
                                                Panduan Fitur
                                            </h2>
                                            <p class="text-[10px] font-bold text-stone-400 dark:text-slate-400">
                                                Informasi & Alur Operasional
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        @click="isGuideOpen = false"
                                        class="rounded-xl border border-stone-800 p-2 text-stone-400 hover:bg-stone-800 hover:text-white transition-colors duration-150 cursor-pointer"
                                    >
                                        <X class="h-4.5 w-4.5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Body Content -->
                            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-7">
                                <!-- Feature Title Card -->
                                <div class="relative overflow-hidden rounded-2xl border border-stone-200/80 bg-stone-50/60 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                                    <div class="absolute -right-6 -bottom-6 opacity-5 dark:opacity-10">
                                        <BookOpen class="h-24 w-24 text-stone-900 dark:text-white" />
                                    </div>
                                    <span class="inline-block rounded-lg bg-orange-500/10 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-orange-500 dark:bg-orange-500/20">
                                        Fitur Aktif
                                    </span>
                                    <h3 class="mt-2 text-base font-black text-stone-950 dark:text-white">
                                        {{ currentGuide.title }}
                                    </h3>
                                    <p class="mt-2.5 text-xs leading-relaxed text-stone-600 dark:text-slate-400 font-medium">
                                        {{ currentGuide.description }}
                                    </p>
                                </div>

                                <!-- Flow Section -->
                                <div class="space-y-4">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-stone-400 dark:text-slate-500 flex items-center gap-1.5">
                                        <span>Alur & Langkah Penggunaan</span>
                                    </h4>
                                    <div class="relative pl-6 border-l-2 border-stone-200 dark:border-slate-800 ml-3 space-y-6">
                                        <div
                                            v-for="(step, idx) in currentGuide.flow"
                                            :key="idx"
                                            class="relative"
                                        >
                                            <!-- Step number badge -->
                                            <span class="absolute -left-9 top-0 flex h-6 w-6 items-center justify-center rounded-full bg-orange-500 text-[10px] font-black text-stone-950 border-2 border-white dark:border-slate-900 shadow-md">
                                                {{ idx + 1 }}
                                            </span>
                                            <p class="text-xs font-bold text-stone-800 dark:text-slate-300 leading-relaxed">
                                                {{ step }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Relations Section -->
                                <div class="space-y-3 pt-3 border-t border-stone-100 dark:border-slate-800/60">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-stone-400 dark:text-slate-500">
                                        Relasi Menu Terkait
                                    </h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="(rel, idx) in currentGuide.relations"
                                            :key="idx"
                                            class="inline-flex items-center gap-1 rounded-xl border border-stone-200 bg-stone-50 px-3 py-1.5 text-[10px] font-bold text-stone-700 dark:border-slate-800 dark:bg-slate-950/80 dark:text-slate-400"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                                            {{ rel }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="border-t border-stone-100 bg-stone-50/50 px-6 py-4.5 text-center text-[10px] font-bold text-stone-400 dark:border-slate-800/80 dark:bg-slate-950/30 dark:text-slate-500">
                                Butuh bantuan lebih lanjut? Hubungi Supervisor atau IT Support.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Custom styled scrollbar for sidebar */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.custom-scrollbar::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(120, 113, 108, 0.15);
    border-radius: 9999px;
}
.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.35);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(
        180deg,
        rgba(251, 146, 60, 0.48),
        rgba(239, 68, 68, 0.34)
    );
    border-radius: 9999px;
    border: 2px solid #f5f5f4;
    background-clip: padding-box;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    border-color: rgba(15, 23, 42, 0.7);
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(
        180deg,
        rgba(251, 146, 60, 0.72),
        rgba(239, 68, 68, 0.5)
    );
    border-color: #e7e5e4;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    border-color: rgba(15, 23, 42, 0.9);
}
</style>
