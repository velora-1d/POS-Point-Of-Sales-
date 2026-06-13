<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ArrowLeftRight, Clock, CookingPot, ShoppingCart } from '@lucide/vue';
import { onBeforeUnmount, onMounted, watch, watchEffect, inject, type Ref } from 'vue';

// Sub-components
import CartPanel from './Components/CartPanel.vue';
import OrderTracker from './Components/OrderTracker.vue';
import ProductCatalog from './Components/ProductCatalog.vue';
import TableTimerToast from './Components/TableTimerToast.vue';

// Modals
import EditOrderModal from './Components/Modals/EditOrderModal.vue';
import KasbonModal from './Components/Modals/KasbonModal.vue';
import MergeBillModal from './Components/Modals/MergeBillModal.vue';
import PaymentCheckoutModal from './Components/Modals/PaymentCheckoutModal.vue';
import PaymentModal from './Components/Modals/PaymentModal.vue';
import SplitBillModal from './Components/Modals/SplitBillModal.vue';
import TakeoverShiftModal from './Components/Modals/TakeoverShiftModal.vue';
import VariantModal from './Components/Modals/VariantModal.vue';

// Composable State
import {
    activePaymentCheckout,
    activeSubTab,
    checkShiftStatus,
    paymentCheckoutModalOpen,
    propsData,
    selectTable,
    selectTakeawayOrder,
    showTakeoverModal,
    timeRemainingText,
    isShiftExpired,
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
    alertSettings?: any;
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

const activeGuideSubTab = inject<Ref<string | null> | null>('activeGuideSubTab', null);
if (activeGuideSubTab) {
    watch(activeSubTab, (val) => {
        activeGuideSubTab.value = val;
    }, { immediate: true });
}

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
                            class="rounded-full border-2 border-stone-200 bg-stone-500/5 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-stone-600 dark:border-white/10 dark:text-slate-350"
                        >
                            POS Utama
                        </span>
                    </h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-slate-400 font-semibold">
                        Sistem pembuatan pesanan, pelacakan antrian aktif, dan visualisasi detail meja secara real-time.
                    </p>
                </div>

                <!-- Info Kasir Bertugas & Shift (Premium Design) -->
                <div
                    v-if="activeShift"
                    class="flex select-none items-center gap-3 rounded-2xl border-2 border-stone-200 bg-white p-3 shadow-md dark:border-white/10 dark:bg-slate-950"
                >
                    <!-- Foto / Inisial Profile Karyawan -->
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl border-2 border-stone-200 bg-orange-500 text-sm font-black text-stone-950 dark:border-white/10"
                    >
                        {{
                            activeShift.user?.name
                                ? activeShift.user.name.charAt(0).toUpperCase()
                                : 'K'
                        }}
                    </div>
                    <!-- Detail Nama & Info Shift -->
                    <div class="flex flex-col">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                class="line-clamp-1 text-xs font-black leading-tight tracking-tight text-stone-900 dark:text-white"
                                >{{ activeShift.user?.name }}</span
                            >
                            <span
                                v-if="activeShift.user?.role"
                                class="rounded-full border-2 border-orange-200 bg-orange-50 dark:bg-orange-500/10 dark:border-orange-500/20 px-2 py-0.5 text-[8px] font-black uppercase text-orange-700 dark:text-orange-400"
                            >
                                {{ activeShift.user.role }}
                            </span>
                        </div>
                        <div
                            class="mt-1 flex flex-wrap items-center gap-1.5 text-[9px] font-black"
                        >
                            <span
                                class="rounded-full border-2 border-stone-200 bg-stone-50 px-2.5 py-0.5 font-sans tracking-wide text-stone-700 dark:border-white/10 dark:bg-slate-900/60 dark:text-slate-300"
                            >
                                {{ activeShift.shift_template?.name || 'Shift Hack' }}
                            </span>
                            <span
                                class="flex items-center gap-0.5 rounded-full border-2 px-2.5 py-0.5 font-black uppercase tracking-wider text-white"
                                :class="isShiftExpired ? 'bg-rose-600 dark:bg-rose-700 animate-pulse border-rose-700' : 'bg-emerald-600 dark:bg-emerald-700 border-emerald-700'"
                            >
                                <Clock class="h-2.5 w-2.5 shrink-0" />
                                <span>{{ timeRemainingText }}</span>
                            </span>
                        </div>
                    </div>
                    <!-- Tombol Ganti Shift -->
                    <button
                        @click="showTakeoverModal = true"
                        type="button"
                        class="ml-2 flex h-9 items-center gap-1.5 rounded-2xl border-2 border-transparent bg-orange-500 px-3.5 text-xs font-black text-stone-950 transition hover:bg-orange-400"
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
            class="mb-6 flex items-center gap-2 rounded-2xl border-2 border-emerald-300 bg-emerald-50 p-4 text-sm font-bold text-emerald-800"
        >
            <span
                class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-600"
            ></span>
            <span>{{ success }}</span>
        </div>

        <!-- Sub-tab Navigation (Premium Glassmorphism Switcher) -->
        <div
            class="mb-6 flex w-full select-none items-center justify-between gap-2 rounded-2xl border-2 border-stone-200 bg-stone-100 p-1.5 dark:border-white/10 dark:bg-slate-950"
        >
            <button
                type="button"
                @click="activeSubTab = 'new_order'"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-black uppercase tracking-wider transition duration-150"
                :class="
                    activeSubTab === 'new_order'
                        ? 'bg-orange-500 text-stone-950 shadow-md'
                        : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                "
            >
                <ShoppingCart class="h-4 w-4" />
                <span>Transaksi Baru & Meja</span>
            </button>
            <button
                type="button"
                @click="activeSubTab = 'active_orders'"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-black uppercase tracking-wider transition duration-150"
                :class="
                    activeSubTab === 'active_orders'
                        ? 'bg-orange-500 text-stone-950 shadow-md'
                        : 'text-stone-700 hover:text-stone-950 hover:bg-stone-200 dark:text-slate-350 dark:hover:text-white dark:hover:bg-white/5'
                "
            >
                <CookingPot class="h-4 w-4" />
                <span>Pelacakan Order Aktif</span>
                <span
                    v-if="activeOrders.length > 0"
                    class="rounded-full px-2.5 py-0.5 text-[10px] font-black"
                    :class="
                        activeSubTab === 'active_orders'
                            ? 'bg-white text-orange-600'
                            : 'bg-orange-500 text-stone-950'
                    "
                >
                    {{ activeOrders.length }}
                </span>
            </button>
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
        <TableTimerToast :tables="tables" :alertSettings="alertSettings" />
    </AuthenticatedLayout>
</template>
