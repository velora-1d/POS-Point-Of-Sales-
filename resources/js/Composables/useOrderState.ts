import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

export type PaymentOption = 'pay_later' | 'pay_now';
export type PaymentMethod =
    | 'cash'
    | 'qris'
    | 'ewallet'
    | 'debit'
    | 'transfer'
    | 'online_platform';

// Props reference
export const propsData = ref<any>({});

export const tables = computed(() => propsData.value.tables || []);
export const categories = computed(() => propsData.value.categories || []);
export const activeOrders = computed(() => propsData.value.activeOrders || []);
export const customers = computed(() => propsData.value.customers || []);
export const promos = computed(() => propsData.value.promos || []);
export const activeShift = computed(() => propsData.value.activeShift || null);
export const cashiers = computed(() => propsData.value.cashiers || []);
export const paymentCheckout = computed(
    () => propsData.value.paymentCheckout || null,
);
export const success = computed(() => propsData.value.success || null);

// Shared UI & Shift State
export const showTakeoverModal = ref(false);
export const isShiftExpired = ref(false);
export const timeRemainingText = ref('');
export const showNoShiftBlocker = computed(() => {
    const page = usePage<any>();
    const userRole = page.props.auth?.user?.role?.type || 'kasir';
    return (
        (userRole === 'kasir' || userRole === 'supervisor') &&
        !activeShift.value
    );
});

export const takeoverForm = useForm({
    active_shift_id: '',
    actual_cash: 0,
    notes: '',
    next_user_id: '',
    next_password_or_pin: '',
});

// Toast Message (Client side notification)
export const localToast = ref('');
export const showLocalToast = (msg: string) => {
    localToast.value = msg;
    setTimeout(() => {
        if (localToast.value === msg) localToast.value = '';
    }, 4000);
};

// Checkout Promo Selection States
export const selectedNewOrderPromoCode = ref('');
export const selectedExistingPaymentPromoCode = ref('');
export const isCustomNewOrderPromo = ref(false);
export const isCustomExistingPaymentPromo = ref(false);

// Active Order & Table Selection State
export const selectedTable = ref<any>(null);
export const selectedTableCategory = ref<'indoor' | 'outdoor'>('indoor');
export const cart = ref<any[]>([]);
export const orderNotes = ref('');
export const guestsCount = ref(1);
export const isTakeawaySelection = computed(
    () => selectedTable.value?.mode === 'takeaway',
);
export const isOnlineSelection = computed(
    () => selectedTable.value?.mode === 'online',
);

// Computed: sisa kapasitas meja yang dipilih
export const tableRemainingCapacity = computed<number | null>(() => {
    if (!selectedTable.value || selectedTable.value.mode) return null;
    if (selectedTable.value.remaining_capacity === undefined) return null;
    return selectedTable.value.remaining_capacity as number | null;
});

// Computed: berapa menit meja sudah occupied
export const tableOccupiedMinutes = computed<number | null>(() => {
    if (!selectedTable.value?.occupied_at) return null;
    const occupiedAt = new Date(selectedTable.value.occupied_at);
    const now = new Date();
    return Math.floor((now.getTime() - occupiedAt.getTime()) / 60000);
});

// Customer Selection State
export const customerSearchQuery = ref('');
export const selectedCustomer = ref<any>(null);
export const customerName = ref('');
export const customerPhone = ref('');
export const customerEmail = ref('');
export const isForcedNewCustomer = ref(false);

// New Order Payment Options
export const paymentOption = ref<PaymentOption>('pay_later');
export const newOrderPaymentMethod = ref<PaymentMethod>('cash');
export const newOrderCashReceived = ref('');
export const newOrderPromoCode = ref('');
export const newOrderApprovalPin = ref('');

// Product Filtering
export const activeCategory = ref<string>('all');
export const productSearchQuery = ref('');
export const activeSubTab = ref<'new_order' | 'active_orders'>('new_order');

// Variant Modal State
export const variantModalOpen = ref(false);
export const selectedProduct = ref<any>(null);
export const selectedVariant = ref<any>(null);
export const itemQuantity = ref(1);
export const itemNotes = ref('');
export const variantTarget = ref<'cart' | 'edit'>('cart');

// Edit Order Modal State
export const editOrderModalOpen = ref(false);
export const editingOrder = ref<any>(null);
export const editingItems = ref<any[]>([]);
export const editOrderNotes = ref('');
export const editApprovalPin = ref('');
export const editCategory = ref<string>('all');
export const editProductSearchQuery = ref('');
export const isUpdatingOrder = ref(false);
export const selectedManagedOrderId = ref<string | null>(null);

// Split Bill State
export const splitBillModalOpen = ref(false);
export const splitDraftItems = ref<any[]>([]);
export const splitApprovalPin = ref('');
export const isSplittingBill = ref(false);

// Merge Bills State
export const mergeSelectionIds = ref<string[]>([]);
export const mergeBillModalOpen = ref(false);
export const mergeApprovalPin = ref('');
export const isMergingBills = ref(false);

// Payment Modal State
export const paymentModalOpen = ref(false);
export const paymentTargetOrder = ref<any>(null);
export const existingPaymentMethod = ref<PaymentMethod>('cash');
export const existingPaymentCashReceived = ref('');
export const existingPaymentPromoCode = ref('');
export const existingPaymentApprovalPin = ref('');

export const showNewOrderApprovalPin = ref(false);
export const showEditApprovalPin = ref(false);
export const showSplitApprovalPin = ref(false);
export const showMergeApprovalPin = ref(false);
export const showExistingPaymentApprovalPin = ref(false);
export const isProcessingPayment = ref(false);

// Kasbon Modal State
export const kasbonModalOpen = ref(false);
export const kasbonTargetOrder = ref<any>(null);
export const kasbonForm = useForm({
    due_date: '',
    notes: '',
});

// QRIS Payment Status Checkout Modal
export const paymentCheckoutModalOpen = ref(false);
export const activePaymentCheckout = ref<Record<string, any> | null>(null);

export const isSubmitting = ref(false);
export const isRefreshingOrder = ref(false);
export const isDeliveringOrder = ref(false);

// Format pricing helper
export const formatPrice = (value: any) => {
    const num = parseFloat(value);
    if (isNaN(num)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
};

export const getPaymentMethodConfig = (method: string) => {
    switch (method) {
        case 'qris':
            return {
                method: 'qris',
                label: 'QRIS',
                desc: 'Buat checkout gateway. Order baru masuk dapur setelah webhook lunas diterima.',
                existingDesc:
                    'Buat atau buka checkout QRIS. Status akan berubah otomatis saat webhook pembayaran masuk.',
                colorClass:
                    'border-fuchsia-500 bg-fuchsia-500/10 text-fuchsia-700 dark:text-white ring-2 ring-fuchsia-500/20 font-bold',
                textClass:
                    'text-fuchsia-600 dark:text-fuchsia-300 hover:text-fuchsia-700 dark:hover:text-fuchsia-200',
                textRawClass: 'text-fuchsia-600 dark:text-fuchsia-300',
                borderClass:
                    'border-fuchsia-500/25 bg-fuchsia-500/5 dark:bg-fuchsia-500/10',
                borderInnerClass: 'border-fuchsia-500/20',
                iconBgClass:
                    'bg-fuchsia-500/10 text-fuchsia-600 dark:text-fuchsia-300',
                spinnerClass: 'text-fuchsia-500 dark:text-fuchsia-400',
                activeLabel: 'QRIS Gateway Sedang Aktif',
                showText: 'Tampilkan QR Code',
                gradientClass: 'bg-gradient-to-r from-fuchsia-500 to-pink-500',
                buttonClass:
                    'border-fuchsia-500/20 bg-fuchsia-500/10 text-fuchsia-700 dark:text-fuchsia-200 hover:bg-fuchsia-500/15',
            };
        case 'ewallet':
            return {
                method: 'ewallet',
                label: 'E-Wallet',
                desc: 'Bayar menggunakan e-wallet (OVO, GoPay, Dana, dll) via gateway.',
                existingDesc:
                    'Bayar menggunakan e-wallet. Status akan berubah otomatis saat webhook pembayaran masuk.',
                colorClass:
                    'border-blue-500 bg-blue-500/10 text-blue-700 dark:text-white ring-2 ring-blue-500/20 font-bold',
                textClass:
                    'text-blue-600 dark:text-blue-300 hover:text-blue-700 dark:hover:text-blue-200',
                textRawClass: 'text-blue-600 dark:text-blue-300',
                borderClass:
                    'border-blue-500/25 bg-blue-500/5 dark:bg-blue-500/10',
                borderInnerClass: 'border-blue-500/20',
                iconBgClass: 'bg-blue-500/10 text-blue-600 dark:text-blue-300',
                spinnerClass: 'text-blue-500 dark:text-blue-400',
                activeLabel: 'E-Wallet Gateway Sedang Aktif',
                showText: 'Tampilkan QR / Link',
                gradientClass: 'bg-gradient-to-r from-blue-500 to-indigo-500',
                buttonClass:
                    'border-blue-500/20 bg-blue-500/10 text-blue-700 dark:text-blue-200 hover:bg-blue-500/15',
            };
        case 'debit':
            return {
                method: 'debit',
                label: 'Debit Card',
                desc: 'Bayar menggunakan kartu debit/kredit via gateway.',
                existingDesc:
                    'Bayar menggunakan kartu debit/kredit. Status akan berubah otomatis saat webhook pembayaran masuk.',
                colorClass:
                    'border-emerald-500 bg-emerald-500/10 text-emerald-700 dark:text-white ring-2 ring-emerald-500/20 font-bold',
                textClass:
                    'text-emerald-600 dark:text-emerald-300 hover:text-emerald-700 dark:hover:text-emerald-200',
                textRawClass: 'text-emerald-600 dark:text-emerald-300',
                borderClass:
                    'border-emerald-500/25 bg-emerald-500/5 dark:bg-emerald-500/10',
                borderInnerClass: 'border-emerald-500/20',
                iconBgClass:
                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
                spinnerClass: 'text-emerald-500 dark:text-emerald-400',
                activeLabel: 'Debit Gateway Sedang Aktif',
                showText: 'Tampilkan QR / Link',
                gradientClass: 'bg-gradient-to-r from-emerald-500 to-teal-500',
                buttonClass:
                    'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-200 hover:bg-emerald-500/15',
            };
        case 'transfer':
            return {
                method: 'transfer',
                label: 'Transfer / VA',
                desc: 'Bayar menggunakan Virtual Account bank transfer via gateway.',
                existingDesc:
                    'Bayar menggunakan Virtual Account bank transfer. Status akan berubah otomatis saat webhook pembayaran masuk.',
                colorClass:
                    'border-indigo-500 bg-indigo-500/10 text-indigo-700 dark:text-white ring-2 ring-indigo-500/20 font-bold',
                textClass:
                    'text-indigo-600 dark:text-indigo-300 hover:text-indigo-700 dark:hover:text-indigo-200',
                textRawClass: 'text-indigo-600 dark:text-indigo-300',
                borderClass:
                    'border-indigo-500/25 bg-indigo-500/5 dark:bg-indigo-500/10',
                borderInnerClass: 'border-indigo-500/20',
                iconBgClass:
                    'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300',
                spinnerClass: 'text-indigo-500 dark:text-indigo-400',
                activeLabel: 'Transfer Gateway Sedang Aktif',
                showText: 'Tampilkan QR / Link',
                gradientClass: 'bg-gradient-to-r from-indigo-500 to-violet-500',
                buttonClass:
                    'border-indigo-500/20 bg-indigo-500/10 text-indigo-700 dark:text-indigo-200 hover:bg-indigo-500/15',
            };
        default:
            return {
                method: 'qris',
                label: 'Digital Payment',
                desc: 'Buat checkout digital via gateway.',
                existingDesc: 'Buat checkout digital via gateway.',
                colorClass:
                    'border-slate-500 bg-slate-500/10 text-slate-700 dark:text-white ring-2 ring-slate-500/20 font-bold',
                textClass:
                    'text-slate-600 dark:text-slate-300 hover:text-slate-700 dark:hover:text-slate-200',
                textRawClass: 'text-slate-600 dark:text-slate-300',
                borderClass:
                    'border-slate-500/25 bg-slate-500/5 dark:bg-slate-500/10',
                borderInnerClass: 'border-slate-500/20',
                iconBgClass:
                    'bg-slate-500/10 text-slate-600 dark:text-slate-300',
                spinnerClass: 'text-slate-500 dark:text-slate-400',
                activeLabel: 'Gateway Sedang Aktif',
                showText: 'Tampilkan QR / Link',
                gradientClass: 'bg-gradient-to-r from-slate-500 to-slate-600',
                buttonClass:
                    'border-slate-500/20 bg-slate-500/10 text-slate-700 dark:text-slate-200 hover:bg-slate-500/15',
            };
    }
};

export const activePaymentConfig = computed(() => {
    return getPaymentMethodConfig(
        activePaymentCheckout.value?.method ?? 'qris',
    );
});

// Computed properties for calculations
export const allProducts = computed(() => {
    const list: any[] = [];
    categories.value.forEach((cat: any) => {
        cat.products.forEach((prod: any) => {
            list.push({
                ...prod,
                category_name: cat.name,
            });
        });
    });
    return list;
});

// Computed payment methods from page auth props
export const activePaymentMethods = computed<string[]>(() => {
    const page = usePage<any>();
    return (page.props.auth?.payment_methods as string[]) ?? ['qris'];
});

// Table card styling helpers (moved from original Order.vue)
export const getTableCardClass = (table: any) => {
    if (table.status === 'occupied') {
        return 'bg-red-50 dark:bg-red-950/10 border-red-500/25 text-red-700 dark:text-red-200 hover:-translate-y-0.5 hover:border-red-500/45 hover:shadow-red-500/10';
    }
    if (table.status === 'reserved') {
        return 'bg-amber-50 dark:bg-amber-950/10 border-amber-500/25 text-amber-800 dark:text-amber-200 hover:-translate-y-0.5 hover:border-amber-500/45 hover:shadow-amber-500/10';
    }
    return 'bg-emerald-50 dark:bg-emerald-950/10 border-emerald-500/25 text-emerald-700 dark:text-emerald-200 hover:-translate-y-0.5 hover:border-emerald-500/45 hover:shadow-emerald-500/10';
};

export const getTableIconClass = (table: any) => {
    if (table.status === 'occupied') {
        return 'border-red-500/25 bg-red-500/10 text-red-600 dark:text-red-300';
    }
    if (table.status === 'reserved') {
        return 'border-amber-500/25 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }
    return 'border-emerald-500/25 bg-emerald-500/10 text-emerald-600 dark:text-emerald-300';
};

export const getTableBadgeClass = (table: any) => {
    if (table.status === 'occupied') {
        return 'border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-300';
    }
    if (table.status === 'reserved') {
        return 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }
    return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:text-emerald-300';
};

// Promo change handlers for cart and payment modal
export const handleNewOrderPromoChange = (e: Event) => {
    const val = (e.target as HTMLSelectElement).value;
    if (val === 'custom') {
        isCustomNewOrderPromo.value = true;
        selectedNewOrderPromoCode.value = 'custom';
        newOrderPromoCode.value = '';
    } else {
        isCustomNewOrderPromo.value = false;
        selectedNewOrderPromoCode.value = val;
        newOrderPromoCode.value = val;
    }
};

export const handleExistingPaymentPromoChange = (e: Event) => {
    const val = (e.target as HTMLSelectElement).value;
    if (val === 'custom') {
        isCustomExistingPaymentPromo.value = true;
        selectedExistingPaymentPromoCode.value = 'custom';
        existingPaymentPromoCode.value = '';
    } else {
        isCustomExistingPaymentPromo.value = false;
        selectedExistingPaymentPromoCode.value = val;
        existingPaymentPromoCode.value = val;
    }
};

// editOrderNeedsApproval alias (based on editing order status)
export const editOrderNeedsApproval = computed(
    () => editingOrder.value?.status === 'in_progress',
);

export const filteredProducts = computed(() => {
    let list = allProducts.value;
    if (activeCategory.value !== 'all') {
        list = list.filter((p) => p.category_id === activeCategory.value);
    }
    if (productSearchQuery.value) {
        const query = productSearchQuery.value.toLowerCase();
        list = list.filter(
            (p) =>
                p.name.toLowerCase().includes(query) ||
                p.description?.toLowerCase().includes(query),
        );
    }
    return list;
});

export const filteredEditProducts = computed(() => {
    let list = allProducts.value;
    if (editCategory.value !== 'all') {
        list = list.filter((p) => p.category_id === editCategory.value);
    }
    if (editProductSearchQuery.value) {
        const query = editProductSearchQuery.value.toLowerCase();
        list = list.filter(
            (p) =>
                p.name.toLowerCase().includes(query) ||
                p.description?.toLowerCase().includes(query),
        );
    }
    return list;
});

export const cartSubtotal = computed(() => {
    return cart.value.reduce(
        (sum, item) => sum + item.unit_price * item.quantity,
        0,
    );
});

export const cartDiscount = computed(() => {
    if (!newOrderPromoCode.value) return 0;
    const promo = promos.value?.find(
        (p: any) =>
            p.code.toUpperCase() === newOrderPromoCode.value.toUpperCase(),
    );
    if (!promo) return 0;
    const minTransaction = Number(promo.min_transaction_amount || 0);
    if (cartSubtotal.value < minTransaction) return 0;

    let discount = 0;
    const sub = cartSubtotal.value;
    if (Number(promo.discount_percent) > 0) {
        discount = (sub * Number(promo.discount_percent)) / 100;
    } else if (Number(promo.discount_amount) > 0) {
        discount = Number(promo.discount_amount);
    }
    return Math.min(discount, sub);
});

export const newOrderPromoWarning = computed(() => {
    if (!newOrderPromoCode.value) return '';
    const promo = promos.value?.find(
        (p: any) =>
            p.code.toUpperCase() === newOrderPromoCode.value.toUpperCase(),
    );
    if (!promo) return 'Kode voucher tidak valid';
    const minTransaction = Number(promo.min_transaction_amount || 0);
    if (cartSubtotal.value < minTransaction) {
        const gap = minTransaction - cartSubtotal.value;
        return `Min. transaksi untuk voucher ini adalah Rp ${minTransaction.toLocaleString('id-ID')}. Kurang Rp ${gap.toLocaleString('id-ID')} lagi.`;
    }
    return '';
});

export const cartTotal = computed(() => {
    return Math.max(0, cartSubtotal.value - cartDiscount.value);
});

export const cartItemCount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

export const newOrderCashChange = computed(() => {
    if (
        paymentOption.value !== 'pay_now' ||
        newOrderPaymentMethod.value !== 'cash'
    ) {
        return 0;
    }
    const received = Number(newOrderCashReceived.value || 0);
    return Math.max(0, received - cartTotal.value);
});

export const editSubtotal = computed(() => {
    return editingItems.value.reduce(
        (sum, item) => sum + item.unit_price * item.quantity,
        0,
    );
});

export const editItemCount = computed(() => {
    return editingItems.value.reduce((sum, item) => sum + item.quantity, 0);
});

export const splitItemCount = computed(() => {
    return splitDraftItems.value.reduce(
        (sum, item) => sum + Number(item.split_quantity || 0),
        0,
    );
});

export const filteredCustomers = computed(() => {
    const query = customerSearchQuery.value.trim().toLowerCase();
    if (!query) return [];
    return customers.value
        .filter((customer: any) => {
            return (
                customer.name?.toLowerCase().includes(query) ||
                customer.phone?.toLowerCase().includes(query)
            );
        })
        .slice(0, 6);
});

export const showNewCustomerForm = computed(() => {
    return (
        isForcedNewCustomer.value ||
        (customerSearchQuery.value.trim().length > 0 && !selectedCustomer.value)
    );
});

export const tableActiveOrders = computed(() => {
    const table = selectedTable.value;
    if (!table || table.status !== 'occupied') return [];
    if (Array.isArray(table.active_orders) && table.active_orders.length > 0) {
        return table.active_orders;
    }
    return table.active_order ? [table.active_order] : [];
});

export const selectedManagedOrder = computed(() => {
    const orders = tableActiveOrders.value;
    if (orders.length === 0) return null;
    return (
        orders.find(
            (order: any) => order.id === selectedManagedOrderId.value,
        ) || orders[0]
    );
});

export const splitOrderNeedsApproval = computed(
    () => selectedManagedOrder.value?.status === 'in_progress',
);

export const mergeSelectedOrders = computed(() => {
    return activeOrders.value.filter((order: any) =>
        mergeSelectionIds.value.includes(order.id),
    );
});

export const mergeSelectionTableId = computed(() => {
    return mergeSelectedOrders.value[0]?.table?.id || null;
});

export const canMergeSelectedOrders = computed(() => {
    if (mergeSelectedOrders.value.length < 2) return false;
    if (!mergeSelectionTableId.value) return false;
    return mergeSelectedOrders.value.every(
        (order: any) =>
            order.table?.id === mergeSelectionTableId.value &&
            canEditOrderStatus(order.status),
    );
});

export const mergeNeedsApproval = computed(() => {
    return mergeSelectedOrders.value.some(
        (order: any) => order.status === 'in_progress',
    );
});

export const existingPaymentDiscount = computed(() => {
    if (!paymentTargetOrder.value || !existingPaymentPromoCode.value) return 0;
    const promo = promos.value?.find(
        (p: any) =>
            p.code.toUpperCase() ===
            existingPaymentPromoCode.value.toUpperCase(),
    );
    if (!promo) return 0;
    const sub = Number(paymentTargetOrder.value.subtotal || 0);
    const minTransaction = Number(promo.min_transaction_amount || 0);
    if (sub < minTransaction) return 0;

    let discount = 0;
    if (Number(promo.discount_percent) > 0) {
        discount = (sub * Number(promo.discount_percent)) / 100;
    } else if (Number(promo.discount_amount) > 0) {
        discount = Number(promo.discount_amount);
    }
    return Math.min(discount, sub);
});

export const existingPaymentPromoWarning = computed(() => {
    if (!paymentTargetOrder.value || !existingPaymentPromoCode.value) return '';
    const promo = promos.value?.find(
        (p: any) =>
            p.code.toUpperCase() ===
            existingPaymentPromoCode.value.toUpperCase(),
    );
    if (!promo) return 'Kode voucher tidak valid';
    const sub = Number(paymentTargetOrder.value.subtotal || 0);
    const minTransaction = Number(promo.min_transaction_amount || 0);
    if (sub < minTransaction) {
        const gap = minTransaction - sub;
        return `Min. transaksi untuk voucher ini adalah Rp ${minTransaction.toLocaleString('id-ID')}. Total belanja Rp ${sub.toLocaleString('id-ID')} (kurang Rp ${gap.toLocaleString('id-ID')}).`;
    }
    return '';
});

export const existingPaymentTotal = computed(() => {
    if (!paymentTargetOrder.value) return 0;
    const sub = Number(paymentTargetOrder.value.subtotal || 0);
    return Math.max(0, sub - existingPaymentDiscount.value);
});

export const existingPaymentCashChange = computed(() => {
    if (!paymentTargetOrder.value || existingPaymentMethod.value !== 'cash') {
        return 0;
    }
    const received = Number(existingPaymentCashReceived.value || 0);
    return Math.max(0, received - existingPaymentTotal.value);
});

// Helper Functions
export const isReadyTable = (table: any) =>
    table?.status === 'available' || table?.status === 'reserved';

export const isPhoneLookup = (value: string) => {
    return /^[0-9+\-\s()]+$/.test(value);
};

export const getOrderServiceLabel = (order: any) => {
    if (order.type === 'takeaway') return 'Takeaway / Bungkus';
    if (order.type === 'online') {
        const names: Record<string, string> = {
            gofood: 'GoFood',
            grabfood: 'GrabFood',
            shopeefood: 'ShopeeFood',
            maximfood: 'MaximFood',
        };
        return `Online - ${names[order.source] || 'Platform'}`;
    }
    if (order.table?.name) return order.table.name;
    return 'Dine In';
};

export const getOrderServiceBadgeClass = (order: any) => {
    if (order.type === 'takeaway') {
        return 'border-orange-500/20 bg-orange-500/10 text-orange-300';
    }
    if (order.type === 'online') {
        return 'border-blue-500/20 bg-blue-500/10 text-blue-300';
    }
    return 'border-sky-500/20 bg-sky-500/10 text-sky-300';
};

export const getOrderCustomerPrimary = (order: any) => {
    return order.customer?.name || order.customer?.phone || 'Walk-in';
};

export const getOrderCustomerSecondary = (order: any) => {
    return [
        order.customer?.name ? order.customer?.phone : null,
        order.customer?.membership?.tier?.name,
    ]
        .filter(Boolean)
        .join(' • ');
};

export const canEditOrderStatus = (status?: string) => {
    return ['pending', 'in_progress'].includes((status || '').toLowerCase());
};

export const canSplitOrderStatus = (status?: string) => {
    return [
        'pending',
        'in_progress',
        'waiting_bar_approval',
        'ready',
        'delivered',
    ].includes((status || '').toLowerCase());
};

export const getPaymentMeta = (order: any) => {
    return order?.metadata?.payment || {};
};

export const getPromoMeta = (order: any) => {
    return order?.metadata?.promo || {};
};

export const getAppliedPromos = (order: any) => {
    return getPromoMeta(order).applied_promos || [];
};

export const isOrderPaid = (order: any) => {
    const payment = getPaymentMeta(order);
    return (
        payment.status === 'paid' ||
        Number(order?.paid_amount || 0) >= Number(order?.total_amount || 0)
    );
};

export const hasPendingBeforeKitchenPayment = (order: any) => {
    const payment = getPaymentMeta(order);
    return payment.status === 'pending' && payment.context === 'before_kitchen';
};

export const getPaymentStatusLabel = (order: any) => {
    const payment = getPaymentMeta(order);
    if (isOrderPaid(order)) return 'Lunas';
    if (hasPendingBeforeKitchenPayment(order)) {
        const method = payment.method ? payment.method.toUpperCase() : 'QRIS';
        return `Menunggu ${method}`;
    }
    if (payment.status === 'pending') return 'Checkout aktif';
    return 'Belum bayar';
};

export const getPaymentStatusClass = (order: any) => {
    if (isOrderPaid(order)) {
        return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300';
    }
    if (hasPendingBeforeKitchenPayment(order)) {
        const payment = getPaymentMeta(order);
        if (payment.method === 'ewallet') {
            return 'border-blue-500/20 bg-blue-500/10 text-blue-300';
        } else if (payment.method === 'debit') {
            return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300';
        } else if (payment.method === 'transfer') {
            return 'border-indigo-500/20 bg-indigo-500/10 text-indigo-300';
        }
        return 'border-fuchsia-500/20 bg-fuchsia-500/10 text-fuchsia-300';
    }
    return 'border-amber-500/20 bg-amber-500/10 text-amber-300';
};

export const canOpenPaymentModal = (order: any) => {
    if (!order || isOrderPaid(order)) return false;
    return (
        ['waiting_bar_approval', 'ready', 'delivered'].includes(order.status) ||
        hasPendingBeforeKitchenPayment(order)
    );
};

export const getPaymentActionLabel = (order: any) => {
    if (hasPendingBeforeKitchenPayment(order)) {
        return 'Lanjutkan Pembayaran';
    }
    return 'Bayar Tagihan';
};

export const getPaymentActionHint = (order: any) => {
    if (hasPendingBeforeKitchenPayment(order)) {
        return 'Order belum masuk dapur. Lunasi dulu agar lanjut ke proses kitchen.';
    }
    return 'Settlement akhir transaksi dine in / takeaway yang sudah siap ditutup.';
};

export const canCloseAsKasbon = (order: any) => {
    if (!order || isOrderPaid(order)) return false;
    if (!order.customer?.id && !order.customer_id) return false;
    return ['waiting_bar_approval', 'ready', 'delivered'].includes(
        order.status,
    );
};

export const getKasbonActionHint = (order: any) => {
    if (!order?.customer?.id && !order?.customer_id) {
        return 'Kasbon butuh data customer aktif pada order ini.';
    }
    if (isOrderPaid(order)) {
        return 'Order ini sudah lunas, tidak bisa ditutup sebagai kasbon.';
    }
    if (
        !['waiting_bar_approval', 'ready', 'delivered'].includes(order?.status)
    ) {
        return 'Kasbon dipakai setelah layanan siap ditutup dan masih ada sisa tagihan.';
    }
    return 'Tutup transaksi sebagai piutang customer, lalu cicilan dilanjutkan dari menu transaksi.';
};

// Reset State Actions
export const resetCustomerSelection = () => {
    customerSearchQuery.value = '';
    selectedCustomer.value = null;
    customerName.value = '';
    customerPhone.value = '';
    customerEmail.value = '';
    isForcedNewCustomer.value = false;
};

export const resetNewOrderPaymentState = () => {
    paymentOption.value = 'pay_later';
    newOrderPaymentMethod.value = 'cash';
    newOrderCashReceived.value = '';
    newOrderPromoCode.value = '';
    newOrderApprovalPin.value = '';
    selectedNewOrderPromoCode.value = '';
    isCustomNewOrderPromo.value = false;
};

export const resetEditOrderState = () => {
    editOrderModalOpen.value = false;
    editingOrder.value = null;
    editingItems.value = [];
    editOrderNotes.value = '';
    editApprovalPin.value = '';
    editCategory.value = 'all';
    editProductSearchQuery.value = '';
    variantModalOpen.value = false;
    selectedProduct.value = null;
    selectedVariant.value = null;
    itemQuantity.value = 1;
    itemNotes.value = '';
};

export const resetSplitBillState = () => {
    splitBillModalOpen.value = false;
    splitDraftItems.value = [];
    splitApprovalPin.value = '';
};

export const resetMergeBillState = () => {
    mergeBillModalOpen.value = false;
    mergeApprovalPin.value = '';
    mergeSelectionIds.value = [];
};

// Business Operations
export const selectTable = (table: any) => {
    selectedTable.value = table;
    selectedManagedOrderId.value =
        table.active_orders?.[0]?.id || table.active_order?.id || null;
    cart.value = [];
    orderNotes.value = '';
    guestsCount.value = 1;
    resetCustomerSelection();
    resetNewOrderPaymentState();
    resetEditOrderState();
    resetSplitBillState();
    resetMergeBillState();
    closeKasbonModal();
    closePaymentModal();
};

export const selectTakeawayOrder = () => {
    selectedTable.value = {
        id: null,
        name: 'Takeaway / Bungkus',
        status: 'available',
        capacity: null,
        mode: 'takeaway',
    };
    selectedManagedOrderId.value = null;
    cart.value = [];
    orderNotes.value = '';
    guestsCount.value = 1;
    resetCustomerSelection();
    resetNewOrderPaymentState();
    resetEditOrderState();
    resetSplitBillState();
    resetMergeBillState();
    closeKasbonModal();
    closePaymentModal();
};

export const selectOnlineOrder = (
    source: 'gofood' | 'grabfood' | 'shopeefood' | 'maximfood',
) => {
    const names: Record<string, string> = {
        gofood: 'GoFood',
        grabfood: 'GrabFood',
        shopeefood: 'ShopeeFood',
        maximfood: 'MaximFood',
    };
    selectedTable.value = {
        id: null,
        name: `Online - ${names[source] || 'Platform'}`,
        status: 'available',
        capacity: null,
        mode: 'online',
        source: source,
    };
    selectedManagedOrderId.value = null;
    cart.value = [];
    orderNotes.value = '';
    guestsCount.value = 1;
    resetCustomerSelection();

    // Default online order to paid on platform
    paymentOption.value = 'pay_now';
    newOrderPaymentMethod.value = 'online_platform';
    newOrderCashReceived.value = '';
    newOrderPromoCode.value = '';
    newOrderApprovalPin.value = '';

    resetEditOrderState();
    resetSplitBillState();
    resetMergeBillState();
    closeKasbonModal();
    closePaymentModal();
};

export const resetTableSelection = () => {
    selectedTable.value = null;
    selectedManagedOrderId.value = null;
    cart.value = [];
    orderNotes.value = '';
    resetCustomerSelection();
    resetNewOrderPaymentState();
    resetEditOrderState();
};

export const selectCustomer = (customer: any) => {
    selectedCustomer.value = customer;
    customerSearchQuery.value = customer.phone || customer.name || '';
    customerName.value = customer.name || '';
    customerPhone.value = customer.phone || '';
    customerEmail.value = customer.email || '';
};

export const cloneOrderItemForEdit = (item: any) => {
    return {
        product_id: item.product_id ?? item.product?.id,
        product_name: item.product_name ?? item.product?.name ?? 'Menu',
        variant_id: item.variant_id ?? item.variant?.id ?? null,
        variant_name: item.variant_name ?? item.variant?.name ?? null,
        quantity: Number(item.quantity || 1),
        unit_price: Number(item.unit_price || 0),
        notes: item.notes || '',
    };
};

export const openPaymentModalForOrder = (order: any) => {
    if (!order) {
        showLocalToast('Pilih order aktif yang ingin dibayar.');
        return;
    }
    if (!canOpenPaymentModal(order)) {
        showLocalToast(
            'Order ini belum masuk tahap pembayaran atau sudah lunas.',
        );
        return;
    }

    paymentTargetOrder.value = order;
    existingPaymentMethod.value = hasPendingBeforeKitchenPayment(order)
        ? getPaymentMeta(order).method || 'qris'
        : 'cash';
    existingPaymentCashReceived.value = '';
    const code = getPromoMeta(order).manual_code || '';
    existingPaymentPromoCode.value = code;
    if (code) {
        const match = promos.value?.some((p: any) => p.code === code);
        if (match) {
            selectedExistingPaymentPromoCode.value = code;
            isCustomExistingPaymentPromo.value = false;
        } else {
            selectedExistingPaymentPromoCode.value = 'custom';
            isCustomExistingPaymentPromo.value = true;
        }
    } else {
        selectedExistingPaymentPromoCode.value = '';
        isCustomExistingPaymentPromo.value = false;
    }
    existingPaymentApprovalPin.value = '';
    paymentModalOpen.value = true;
};

export const openPaymentModal = () => {
    openPaymentModalForOrder(selectedManagedOrder.value);
};

export const closePaymentModal = () => {
    paymentModalOpen.value = false;
    paymentTargetOrder.value = null;
    existingPaymentMethod.value = 'cash';
    existingPaymentCashReceived.value = '';
    existingPaymentPromoCode.value = '';
    selectedExistingPaymentPromoCode.value = '';
    isCustomExistingPaymentPromo.value = false;
    existingPaymentApprovalPin.value = '';
    isProcessingPayment.value = false;
};

export const openKasbonModalForOrder = (order: any) => {
    if (!order) {
        showLocalToast('Pilih order aktif yang ingin ditutup sebagai kasbon.');
        return;
    }
    if (!canCloseAsKasbon(order)) {
        showLocalToast(getKasbonActionHint(order));
        return;
    }
    kasbonTargetOrder.value = order;
    kasbonForm.reset();
    kasbonForm.clearErrors();
    kasbonForm.due_date = '';
    kasbonForm.notes = '';
    kasbonModalOpen.value = true;
};

export const openKasbonModal = () => {
    openKasbonModalForOrder(selectedManagedOrder.value);
};

export const closeKasbonModal = () => {
    kasbonModalOpen.value = false;
    kasbonTargetOrder.value = null;
    kasbonForm.reset();
    kasbonForm.clearErrors();
};

export const openEditOrder = () => {
    const activeOrder = selectedManagedOrder.value;
    if (!activeOrder) {
        showLocalToast('Order aktif tidak ditemukan.');
        return;
    }
    if (!canEditOrderStatus(activeOrder.status)) {
        showLocalToast('Order dengan status ini belum bisa diedit.');
        return;
    }
    editingOrder.value = activeOrder;
    editingItems.value = activeOrder.items.map(cloneOrderItemForEdit);
    editOrderNotes.value = activeOrder.notes || '';
    editApprovalPin.value = '';
    editCategory.value = 'all';
    editProductSearchQuery.value = '';
    editOrderModalOpen.value = true;
};

export const closeEditOrder = () => {
    resetEditOrderState();
};

export const cancelActiveOrder = (order: any) => {
    if (!order) {
        showLocalToast('Order tidak ditemukan.');
        return;
    }
    if (
        confirm(
            `Apakah Anda yakin ingin membatalkan order ${order.order_number}?`,
        )
    ) {
        router.post(
            route('order.cancel', order.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    showLocalToast('Order berhasil dibatalkan.');
                    selectedManagedOrderId.value = null;
                },
                onError: (errors: any) => {
                    showLocalToast(errors.error || 'Gagal membatalkan order.');
                },
            },
        );
    }
};

export const deliverOrder = (order: any) => {
    if (!order) return;
    isDeliveringOrder.value = true;
    router.post(
        route('order.deliver', order.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isDeliveringOrder.value = false;
                resetTableSelection();
                showLocalToast('Pesanan berhasil disajikan.');
            },
            onError: (errors: any) => {
                isDeliveringOrder.value = false;
                showLocalToast(errors.error || 'Gagal menyajikan pesanan.');
            },
        },
    );
};

export const openSplitBill = () => {
    const activeOrder = selectedManagedOrder.value;
    if (!activeOrder) {
        showLocalToast('Pilih order aktif yang ingin di-split.');
        return;
    }
    if (!canSplitOrderStatus(activeOrder.status)) {
        showLocalToast('Order dengan status ini belum bisa di-split.');
        return;
    }
    splitDraftItems.value = activeOrder.items.map((item: any) => ({
        order_item_id: item.id,
        product_name: item.product?.name || 'Menu',
        variant_name: item.variant?.name || null,
        quantity: Number(item.quantity || 0),
        split_quantity: 0,
        notes: item.notes || '',
        total_price: Number(item.total_price || 0),
    }));
    splitApprovalPin.value = '';
    splitBillModalOpen.value = true;
};

export const closeSplitBill = () => {
    resetSplitBillState();
};

export const setSplitQuantity = (index: number, nextValue: number) => {
    const current = splitDraftItems.value[index];
    if (!current) return;
    current.split_quantity = Math.max(0, Math.min(current.quantity, nextValue));
};

export const submitSplitBill = () => {
    if (!selectedManagedOrder.value) return;
    isSplittingBill.value = true;
    router.post(
        route('order.split-bill', selectedManagedOrder.value.id),
        {
            approval_pin: splitApprovalPin.value || null,
            item_splits: splitDraftItems.value.map((item) => ({
                order_item_id: item.order_item_id,
                quantity: item.split_quantity,
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSplittingBill.value = false;
                resetTableSelection();
                showLocalToast('Split bill berhasil dibuat.');
            },
            onError: (errors) => {
                isSplittingBill.value = false;
                showLocalToast(
                    errors.approval_pin ||
                        errors.error ||
                        'Gagal melakukan split bill.',
                );
            },
        },
    );
};

export const toggleMergeSelection = (orderId: string) => {
    if (mergeSelectionIds.value.includes(orderId)) {
        mergeSelectionIds.value = mergeSelectionIds.value.filter(
            (id) => id !== orderId,
        );
        return;
    }
    mergeSelectionIds.value = [...mergeSelectionIds.value, orderId];
};

export const openMergeBill = () => {
    if (!canMergeSelectedOrders.value) {
        showLocalToast(
            'Pilih minimal dua order aktif dari meja yang sama untuk gabung bill.',
        );
        return;
    }
    mergeApprovalPin.value = '';
    mergeBillModalOpen.value = true;
};

export const closeMergeBill = () => {
    mergeBillModalOpen.value = false;
    mergeApprovalPin.value = '';
};

export const submitMergeBills = () => {
    if (!canMergeSelectedOrders.value) return;
    isMergingBills.value = true;
    router.post(
        route('orders.merge-bills'),
        {
            order_ids: mergeSelectionIds.value,
            approval_pin: mergeApprovalPin.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isMergingBills.value = false;
                resetTableSelection();
                resetMergeBillState();
                showLocalToast('Gabung bill berhasil dibuat.');
            },
            onError: (errors) => {
                isMergingBills.value = false;
                showLocalToast(
                    errors.approval_pin ||
                        errors.error ||
                        'Gagal melakukan gabung bill.',
                );
            },
        },
    );
};

export const addItemToCollection = (
    collection: any[],
    product: any,
    quantity = 1,
    variant: any = null,
    notes = '',
) => {
    const basePrice = parseFloat(product.base_price);
    const variantPrice = variant
        ? parseFloat(variant.additional_price || '0')
        : 0;
    const unitPrice = basePrice + variantPrice;
    const variantId = variant ? variant.id : null;
    const existingIndex = collection.findIndex(
        (item) =>
            item.product_id === product.id && item.variant_id === variantId,
    );

    if (existingIndex > -1) {
        collection[existingIndex].quantity += quantity;
        if (notes) {
            collection[existingIndex].notes = notes;
        }
        return;
    }

    collection.push({
        product_id: product.id,
        product_name: product.name,
        variant_id: variantId,
        variant_name: variant ? variant.name : null,
        quantity,
        unit_price: unitPrice,
        notes,
    });
};

export const handleProductClick = (product: any) => {
    variantTarget.value = 'cart';
    if (product.variants && product.variants.length > 0) {
        selectedProduct.value = product;
        selectedVariant.value = product.variants[0];
        itemQuantity.value = 1;
        itemNotes.value = '';
        variantModalOpen.value = true;
    } else {
        addToCartDirect(product);
    }
};

export const handleEditProductClick = (product: any) => {
    variantTarget.value = 'edit';
    if (product.variants && product.variants.length > 0) {
        selectedProduct.value = product;
        selectedVariant.value = product.variants[0];
        itemQuantity.value = 1;
        itemNotes.value = '';
        variantModalOpen.value = true;
    } else {
        addItemToCollection(editingItems.value, product);
        showLocalToast(`Ditambahkan ${product.name} ke draft edit.`);
    }
};

export const addToCartDirect = (product: any) => {
    addItemToCollection(cart.value, product);
    showLocalToast(`Ditambahkan ${product.name} ke keranjang.`);
};

export const confirmVariantAdd = () => {
    if (!selectedProduct.value) return;
    const targetCollection =
        variantTarget.value === 'edit' ? editingItems.value : cart.value;

    addItemToCollection(
        targetCollection,
        selectedProduct.value,
        itemQuantity.value,
        selectedVariant.value,
        itemNotes.value,
    );

    showLocalToast(
        variantTarget.value === 'edit'
            ? `Ditambahkan ${selectedProduct.value.name} ke draft edit.`
            : `Ditambahkan ${selectedProduct.value.name} ke keranjang.`,
    );
    variantModalOpen.value = false;
    selectedProduct.value = null;
    selectedVariant.value = null;
    itemQuantity.value = 1;
    itemNotes.value = '';
};

export const increaseCartQty = (index: number) => {
    cart.value[index].quantity += 1;
};

export const decreaseCartQty = (index: number) => {
    if (cart.value[index].quantity > 1) {
        cart.value[index].quantity -= 1;
    } else {
        cart.value.splice(index, 1);
    }
};

export const removeCartItem = (index: number) => {
    cart.value.splice(index, 1);
};

export const increaseEditQty = (index: number) => {
    editingItems.value[index].quantity += 1;
};

export const decreaseEditQty = (index: number) => {
    if (editingItems.value[index].quantity > 1) {
        editingItems.value[index].quantity -= 1;
        return;
    }
    editingItems.value.splice(index, 1);
};

export const removeEditItem = (index: number) => {
    editingItems.value.splice(index, 1);
};

export const submitEditOrder = () => {
    if (!editingOrder.value || editingItems.value.length === 0) return;
    isUpdatingOrder.value = true;

    const payload = {
        notes: editOrderNotes.value || null,
        approval_pin: editApprovalPin.value || null,
        items: editingItems.value.map((item) => ({
            product_id: item.product_id,
            variant_id: item.variant_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            notes: item.notes || null,
        })),
    };

    router.patch(route('order.update', editingOrder.value.id), payload, {
        preserveScroll: true,
        onSuccess: () => {
            isUpdatingOrder.value = false;
            resetTableSelection();
            showLocalToast('Perubahan order berhasil dikirim ke dapur.');
        },
        onError: (errors) => {
            isUpdatingOrder.value = false;
            showLocalToast(
                errors.approval_pin ||
                    errors.error ||
                    'Gagal memperbarui order. Periksa kembali isian edit.',
            );
        },
    });
};

export const submitExistingPayment = () => {
    if (!paymentTargetOrder.value) return;
    isProcessingPayment.value = true;

    router.post(
        route('order.pay', paymentTargetOrder.value.id),
        {
            payment_method: existingPaymentMethod.value,
            promo_code: existingPaymentPromoCode.value || null,
            approval_pin: existingPaymentApprovalPin.value || null,
            cash_received:
                existingPaymentMethod.value === 'cash' &&
                existingPaymentCashReceived.value
                    ? Number(existingPaymentCashReceived.value)
                    : null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessingPayment.value = false;
                closePaymentModal();
                resetTableSelection();
                showLocalToast('Proses pembayaran order berhasil dikirim.');
            },
            onError: (errors) => {
                isProcessingPayment.value = false;
                showLocalToast(
                    errors.promo_code ||
                        errors.approval_pin ||
                        errors.cash_received ||
                        errors.error ||
                        'Gagal memproses pembayaran order.',
                );
            },
        },
    );
};

export const refreshCurrentOrder = () => {
    if (isRefreshingOrder.value || !paymentTargetOrder.value) return;
    isRefreshingOrder.value = true;
    router.reload({
        only: ['activeOrders', 'tables'],
        preserveScroll: true,
        onFinish: () => {
            isRefreshingOrder.value = false;
            showLocalToast('Status pembayaran berhasil diperbarui.');

            if (paymentTargetOrder.value) {
                const updated = activeOrders.value.find(
                    (o: any) => o.id === paymentTargetOrder.value.id,
                );
                if (updated) {
                    paymentTargetOrder.value = updated;
                    if (isOrderPaid(updated)) {
                        closePaymentModal();
                    }
                }
            }
        },
    } as any);
};

export const submitKasbon = () => {
    if (!kasbonTargetOrder.value) return;
    kasbonForm.post(
        route('transactions.kasbon.close', kasbonTargetOrder.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeKasbonModal();
                resetTableSelection();
                showLocalToast('Order berhasil ditutup sebagai kasbon.');
            },
            onError: (errors) => {
                showLocalToast(
                    errors.customer ||
                        errors.order ||
                        errors.due_date ||
                        errors.notes ||
                        errors.error ||
                        'Gagal menutup order sebagai kasbon.',
                );
            },
        },
    );
};

export const submitOrder = () => {
    if (!selectedTable.value || cart.value.length === 0) return;
    isSubmitting.value = true;

    const payload = {
        table_id:
            isTakeawaySelection.value || isOnlineSelection.value
                ? null
                : selectedTable.value.id,
        order_type: isOnlineSelection.value
            ? 'online'
            : isTakeawaySelection.value
              ? 'takeaway'
              : 'dine_in',
        guests_count:
            !isTakeawaySelection.value && !isOnlineSelection.value
                ? Math.max(1, guestsCount.value)
                : 1,
        source: isOnlineSelection.value ? selectedTable.value.source : 'kasir',
        reservation_id:
            isTakeawaySelection.value || isOnlineSelection.value
                ? null
                : (selectedTable.value.active_reservation?.id ?? null),
        customer_id: selectedCustomer.value?.id ?? null,
        customer_name: customerName.value || null,
        customer_phone: customerPhone.value || null,
        customer_email: customerEmail.value || null,
        promo_code: newOrderPromoCode.value || null,
        approval_pin: newOrderApprovalPin.value || null,
        payment_option: paymentOption.value,
        payment_method:
            paymentOption.value === 'pay_now'
                ? newOrderPaymentMethod.value
                : null,
        cash_received:
            paymentOption.value === 'pay_now' &&
            newOrderPaymentMethod.value === 'cash' &&
            newOrderCashReceived.value
                ? Number(newOrderCashReceived.value)
                : null,
        notes: orderNotes.value,
        items: cart.value.map((item) => ({
            product_id: item.product_id,
            variant_id: item.variant_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            notes: item.notes,
        })),
    };

    router.post(route('order.store'), payload, {
        onSuccess: () => {
            isSubmitting.value = false;
            resetTableSelection();
            showLocalToast('Order baru berhasil diproses.');
        },
        onError: (errors) => {
            isSubmitting.value = false;
            if (errors.error) {
                showLocalToast(errors.error);
            } else {
                showLocalToast(
                    errors.promo_code ||
                        errors.approval_pin ||
                        'Gagal menyimpan pesanan. Silakan periksa isian.',
                );
            }
        },
    });
};

export const getStatusClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'payment_pending':
            return 'bg-fuchsia-500/10 text-fuchsia-300 border-fuchsia-500/20';
        case 'pending':
            return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
        case 'in_progress':
            return 'bg-amber-500/10 text-amber-400 border-amber-500/20 animate-pulse';
        case 'ready':
            return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'completed':
            return 'bg-slate-500/10 text-slate-400 border-slate-700/20';
        default:
            return 'bg-slate-700 text-slate-300';
    }
};

export const getProductImage = (product: any) => {
    if (product.image_url) return product.image_url;
    const name = product.name.toLowerCase();
    if (name.includes('salmon') && name.includes('mentai')) {
        return '/images/salmon_mentai.png';
    } else if (name.includes('beef') && name.includes('mentai')) {
        return '/images/salmon_mentai.png';
    } else if (name.includes('gyoza')) {
        return '/images/gyoza.png';
    } else if (
        name.includes('ocha') ||
        name.includes('tea') ||
        name.includes('drink')
    ) {
        return '/images/iced_ocha.png';
    }
    return '/images/salmon_mentai.png';
};

export const openPaymentCheckout = () => {
    if (!activePaymentCheckout.value?.payment_url) return;
    window.open(
        activePaymentCheckout.value.payment_url,
        '_blank',
        'noopener,noreferrer',
    );
};

export const submitTakeover = () => {
    const page = usePage<any>();
    const isOwner = page.props.auth?.user?.role?.type === 'owner';
    if (isOwner) {
        if (
            !confirm(
                'Anda login sebagai Owner. Apakah Anda yakin ingin melakukan serah terima & ambil alih shift ini secara manual?',
            )
        ) {
            return;
        }
    }
    takeoverForm.active_shift_id = activeShift.value?.id || '';
    takeoverForm.post(route('shifts.takeover'), {
        onSuccess: () => {
            showTakeoverModal.value = false;
            isShiftExpired.value = false;
            takeoverForm.reset();
        },
    });
};

export const checkShiftStatus = () => {
    if (!activeShift.value || !activeShift.value.shift_template) {
        isShiftExpired.value = false;
        timeRemainingText.value = 'Tidak ada shift aktif';
        return;
    }

    const now = new Date();
    const endTimeStr = activeShift.value.shift_template.end_time;
    if (!endTimeStr) return;

    const [hours, minutes] = endTimeStr.split(':').map(Number);
    const endTime = new Date();
    endTime.setHours(hours, minutes, 0, 0);

    const diff = endTime.getTime() - now.getTime();

    if (diff <= 0) {
        isShiftExpired.value = true;
        showTakeoverModal.value = true;
        timeRemainingText.value = 'Shift Selesai!';
    } else {
        const diffMins = Math.floor(diff / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const mins = diffMins % 60;
        timeRemainingText.value = `${diffHours}j ${mins}m sisa`;
    }
};
