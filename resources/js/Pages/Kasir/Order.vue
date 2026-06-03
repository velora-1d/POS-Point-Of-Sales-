<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Clock, ArrowLeftRight, ShoppingCart, CookingPot } from '@lucide/vue';
import { onMounted, onBeforeUnmount, watch, watchEffect } from 'vue';

// Sub-components
import ProductCatalog from './Components/ProductCatalog.vue';
import CartPanel from './Components/CartPanel.vue';
import OrderTracker from './Components/OrderTracker.vue';

// Modals
import TakeoverShiftModal from './Components/Modals/TakeoverShiftModal.vue';
import VariantModal from './Components/Modals/VariantModal.vue';
import EditOrderModal from './Components/Modals/EditOrderModal.vue';
import SplitBillModal from './Components/Modals/SplitBillModal.vue';
import MergeBillModal from './Components/Modals/MergeBillModal.vue';
import PaymentModal from './Components/Modals/PaymentModal.vue';
import KasbonModal from './Components/Modals/KasbonModal.vue';
import PaymentCheckoutModal from './Components/Modals/PaymentCheckoutModal.vue';

// Composable State
import {
    propsData,
    activeShift,
    timeRemainingText,
    showTakeoverModal,
    activeOrders,
    activeSubTab,
    checkShiftStatus,
    selectTable,
    selectTakeawayOrder,
    activePaymentCheckout,
    paymentCheckoutModalOpen,
    success,
} from '@/Composables/useOrderState';

const props = defineProps<{
    tables: any[];
    categories: any[];
    activeOrders: any[];
    customers: any[];
    promos: any[];
    activeShift?: any;
    cashiers?: any[];
    success?: string | null;
    paymentCheckout?: Record<string, any> | null;
}>();

// Map props to shared state composable
watchEffect(() => {
    propsData.value = props;
});

// Watch paymentCheckout from Inertia props to sync with composable
watch(
    () => props.paymentCheckout,
    (value) => {
        activePaymentCheckout.value = value ?? null;
        paymentCheckoutModalOpen.value = Boolean(value);
    },
    { immediate: true },
);

let shiftInterval: any = null;

onMounted(() => {
    checkShiftStatus();
    shiftInterval = setInterval(checkShiftStatus, 15000);

    const params = new URLSearchParams(window.location.search);
    const tableId = params.get('table_id');
    const mode = params.get('mode');

    if (mode === 'takeaway') {
        selectTakeawayOrder();
        return;
    }

    if (!tableId) return;

    const matchedTable = props.tables.find((table) => table.id === tableId);
    if (matchedTable) {
        selectTable(matchedTable);
    }
});

onBeforeUnmount(() => {
    if (shiftInterval) {
        clearInterval(shiftInterval);
    }
});
</script>

<template>
    <Head title="Kasir Order - POS Mentai" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
            >
                <div>
                    <h2
                        class="flex items-center gap-2 font-sans text-2xl font-black tracking-tight text-stone-900 dark:text-white"
                    >
                        <span>Layanan Transaksi Kasir</span>
                        <span
                            class="rounded-md border border-orange-500/20 bg-orange-500/10 px-2 py-0.5 text-xs font-semibold text-orange-600 dark:text-orange-400"
                        >
                            Menu 1 - 6
                        </span>
                    </h2>
                    <p class="mt-1 text-xs text-stone-550 dark:text-slate-400">
                        Sistem pembuatan pesanan, pelacakan antrian aktif, dan
                        visualisasi detail meja secara real-time.
                    </p>
                </div>

                <!-- Info Kasir Bertugas & Shift (Premium Design) -->
                <div v-if="activeShift" class="flex items-center gap-3 rounded-2xl border border-stone-200 dark:border-slate-800 bg-white dark:bg-slate-900/50 p-3 shadow-sm select-none">
                    <!-- Foto / Inisial Profile Karyawan -->
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-500 dark:text-orange-400 font-black text-sm">
                        {{ activeShift.user?.name ? activeShift.user.name.charAt(0).toUpperCase() : 'K' }}
                    </div>
                    <!-- Detail Nama & Info Shift -->
                    <div class="flex flex-col min-w-[120px]">
                        <span class="text-xs font-black tracking-tight text-stone-900 dark:text-white line-clamp-1 leading-tight">{{ activeShift.user?.name }}</span>
                        <div class="flex items-center gap-1.5 mt-1 text-[9px] font-semibold text-stone-500 dark:text-slate-400">
                            <span class="rounded bg-stone-100 dark:bg-slate-800 px-1 py-0.5 text-stone-600 dark:text-slate-300 font-sans tracking-wide">
                                {{ activeShift.shift_template?.name || 'Shift Aktif' }}
                            </span>
                            <span class="flex items-center gap-0.5 text-orange-500 dark:text-orange-400">
                                <Clock class="h-3 w-3 shrink-0" />
                                <span>{{ timeRemainingText }}</span>
                            </span>
                        </div>
                    </div>
                    <!-- Tombol Ganti Shift -->
                    <button
                        @click="showTakeoverModal = true"
                        type="button"
                        class="ml-2 flex h-9 items-center gap-1.5 rounded-xl border border-stone-200 dark:border-slate-700 bg-stone-100 dark:bg-slate-800 px-3 text-xs font-bold text-stone-700 dark:text-slate-200 transition-all hover:bg-stone-200 dark:hover:bg-slate-700 hover:text-stone-950 dark:hover:text-white"
                    >
                        <ArrowLeftRight class="h-3.5 w-3.5 shrink-0" />
                        <span>Ganti Shift</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Dynamic Success Alert -->
        <div
            v-if="success"
            class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/15 p-4 text-sm font-medium text-emerald-400"
        >
            <span
                class="h-2 w-2 animate-ping rounded-full bg-emerald-500"
            ></span>
            <span>{{ success }}</span>
        </div>

        <!-- Sub-tab Navigation (Premium Glassmorphism Switcher) -->
        <div class="mb-6 flex items-center justify-between rounded-2xl border border-stone-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/60 p-2 shadow-lg backdrop-blur-sm select-none">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="activeSubTab = 'new_order'"
                    :class="[
                        'flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all duration-200',
                        activeSubTab === 'new_order'
                            ? 'bg-orange-500 text-white shadow-md shadow-orange-500/10'
                            : 'text-stone-500 dark:text-slate-400 hover:text-stone-900 dark:hover:text-stone-800 dark:text-slate-200 hover:bg-stone-100 dark:hover:bg-stone-100 dark:bg-slate-800/40',
                    ]"
                >
                    <ShoppingCart class="h-4 w-4" />
                    <span>Transaksi Baru & Meja</span>
                </button>
                <button
                    type="button"
                    @click="activeSubTab = 'active_orders'"
                    :class="[
                        'flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all duration-200',
                        activeSubTab === 'active_orders'
                            ? 'bg-orange-500 text-white shadow-md shadow-orange-500/10'
                            : 'text-stone-550 dark:text-slate-400 hover:text-stone-900 dark:hover:text-stone-800 dark:text-slate-200 hover:bg-stone-100 dark:hover:bg-stone-100 dark:bg-slate-800/40',
                    ]"
                >
                    <CookingPot class="h-4 w-4" />
                    <span>Pelacakan Order Aktif</span>
                    <span 
                        v-if="activeOrders.length > 0" 
                        :class="[
                            'rounded-full px-2 py-0.5 text-[10px] font-bold transition-all duration-200',
                            activeSubTab === 'active_orders' ? 'bg-stone-200 dark:bg-white/20 text-white' : 'bg-orange-500/10 text-orange-650 dark:text-orange-400 border border-orange-500/20'
                        ]"
                    >
                        {{ activeOrders.length }}
                    </span>
                </button>
            </div>
            
            <!-- Quick stats indicator -->
            <div class="hidden sm:flex items-center gap-4 text-xs pr-2">
                <span class="flex items-center gap-1.5 text-stone-500 dark:text-slate-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Dapur Online</span>
                </span>
            </div>
        </div>

        <!-- Split Grid Layout -->
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <!-- LEFT PANEL: Catalog / Tracker -->
            <div class="space-y-6 lg:col-span-7 xl:col-span-8">
                <OrderTracker v-if="activeSubTab === 'active_orders'" />
                <ProductCatalog v-else />
            </div>

            <!-- RIGHT PANEL: Cart -->
            <div class="lg:col-span-5 xl:col-span-4">
                <CartPanel />
            </div>
        </div>

        <!-- MODALS LIST -->
        <TakeoverShiftModal />
        <VariantModal />
        <EditOrderModal />
        <SplitBillModal />
        <MergeBillModal />
        <PaymentModal />
        <KasbonModal />
        <PaymentCheckoutModal />
    </AuthenticatedLayout>
</template>
