<script setup lang="ts">
import {
    activeOrders,
    formatPrice,
    getOrderCustomerPrimary,
    getOrderServiceBadgeClass,
    getOrderServiceLabel,
    getStatusClass,
    selectedManagedOrderId,
} from '@/Composables/useOrderState';
import { CookingPot } from '@lucide/vue';
</script>

<template>
    <div
        class="flex min-h-[600px] flex-col rounded-2xl border border-stone-200 bg-white p-6 shadow-xl dark:border-slate-800/80 dark:bg-slate-900"
    >
        <div class="mb-6 flex items-center justify-between">
            <h3
                class="flex items-center gap-2 text-lg font-bold text-stone-900 dark:text-white"
            >
                <CookingPot class="h-5 w-5 text-orange-500" />
                <span>Daftar Order Dapur Aktif</span>
            </h3>
            <span
                class="rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-xs font-bold text-orange-600 dark:bg-orange-500/10 dark:text-orange-400"
            >
                {{ activeOrders.length }} Order Aktif
            </span>
        </div>

        <!-- Grid Order Dapur Aktif (2 Kolom) -->
        <div
            class="custom-scrollbar grid max-h-[500px] flex-1 grid-cols-1 gap-4 overflow-y-auto pr-1 md:grid-cols-2"
        >
            <div
                v-for="order in activeOrders"
                :key="order.id"
                @click="selectedManagedOrderId = order.id"
                :class="[
                    'flex cursor-pointer flex-col justify-between gap-3 rounded-xl border border-stone-200 bg-stone-50 p-4 transition duration-150 hover:border-orange-500/40 dark:border-slate-800 dark:bg-slate-950/60',
                    selectedManagedOrderId === order.id
                        ? 'border-orange-500 bg-orange-500/[0.02] shadow-lg shadow-orange-500/[0.02] dark:bg-orange-500/[0.02]'
                        : '',
                ]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs font-black text-stone-900 dark:text-white"
                                >{{ order.order_number }}</span
                            >
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span
                                :class="[
                                    'rounded border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                    getStatusClass(order.status),
                                ]"
                            >
                                {{ order.status }}
                            </span>
                            <span
                                :class="[
                                    'rounded border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                    getOrderServiceBadgeClass(order),
                                ]"
                            >
                                {{ getOrderServiceLabel(order) }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p
                            class="text-[9px] font-semibold uppercase tracking-wider text-stone-500 dark:text-slate-500"
                        >
                            Total Bill
                        </p>
                        <p
                            class="mt-0.5 text-sm font-extrabold text-orange-600 dark:text-orange-400"
                        >
                            {{ formatPrice(order.total_amount) }}
                        </p>
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 gap-2 border-t border-stone-200 pt-2 text-[11px] text-stone-500 dark:border-slate-900 dark:text-slate-400"
                >
                    <div>
                        <p
                            class="text-[9px] font-semibold uppercase text-stone-400 dark:text-slate-500"
                        >
                            Meja/Layanan
                        </p>
                        <p
                            class="mt-0.5 font-bold text-stone-800 dark:text-slate-200"
                        >
                            {{ order.table?.name ?? 'Takeaway' }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold uppercase text-stone-400 dark:text-slate-500"
                        >
                            Pelanggan
                        </p>
                        <p
                            class="mt-0.5 truncate font-bold text-stone-800 dark:text-slate-200"
                        >
                            {{ getOrderCustomerPrimary(order) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="activeOrders.length === 0"
                class="col-span-2 rounded-xl border border-dashed border-stone-200 py-20 text-center text-xs text-stone-500 dark:border-slate-800 dark:text-slate-500"
            >
                Belum ada transaksi aktif saat ini.
            </div>
        </div>
    </div>
</template>
