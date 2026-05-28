<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ChevronLeft,
    Clock,
    CookingPot,
    Layers,
    Minus,
    Package,
    Plus,
    Receipt,
    Search,
    ShoppingCart,
    Trash2,
    User,
    Utensils,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';

// Define Props passed from OrderController
const props = defineProps<{
    tables: any[];
    categories: any[];
    activeOrders: any[];
    customers: any[];
    success?: string | null;
    paymentCheckout?: Record<string, any> | null;
}>();

type PaymentOption = 'pay_later' | 'pay_now';
type PaymentMethod = 'cash' | 'qris';

// Page state
const selectedTable = ref<any>(null);
const cart = ref<any[]>([]);
const orderNotes = ref('');
const isTakeawaySelection = computed(
    () => selectedTable.value?.mode === 'takeaway',
);
const customerSearchQuery = ref('');
const selectedCustomer = ref<any>(null);
const customerName = ref('');
const customerPhone = ref('');
const customerEmail = ref('');
const paymentOption = ref<PaymentOption>('pay_later');
const newOrderPaymentMethod = ref<PaymentMethod>('cash');
const newOrderCashReceived = ref('');
const newOrderPromoCode = ref('');
const newOrderApprovalPin = ref('');

// Product filtering
const activeCategory = ref<string>('all');
const productSearchQuery = ref('');

// Variant Modal
const variantModalOpen = ref(false);
const selectedProduct = ref<any>(null);
const selectedVariant = ref<any>(null);
const itemQuantity = ref(1);
const itemNotes = ref('');
const variantTarget = ref<'cart' | 'edit'>('cart');

// Edit order modal
const editOrderModalOpen = ref(false);
const editingOrder = ref<any>(null);
const editingItems = ref<any[]>([]);
const editOrderNotes = ref('');
const editApprovalPin = ref('');
const editCategory = ref<string>('all');
const editProductSearchQuery = ref('');
const isUpdatingOrder = ref(false);
const selectedManagedOrderId = ref<string | null>(null);

// Split bill modal
const splitBillModalOpen = ref(false);
const splitDraftItems = ref<any[]>([]);
const splitApprovalPin = ref('');
const isSplittingBill = ref(false);

// Merge bills
const mergeSelectionIds = ref<string[]>([]);
const mergeBillModalOpen = ref(false);
const mergeApprovalPin = ref('');
const isMergingBills = ref(false);
const paymentModalOpen = ref(false);
const paymentTargetOrder = ref<any>(null);
const existingPaymentMethod = ref<PaymentMethod>('cash');
const existingPaymentCashReceived = ref('');
const existingPaymentPromoCode = ref('');
const existingPaymentApprovalPin = ref('');
const isProcessingPayment = ref(false);
const kasbonModalOpen = ref(false);
const kasbonTargetOrder = ref<any>(null);
const kasbonForm = useForm({
    due_date: '',
    notes: '',
});
const paymentCheckoutModalOpen = ref(Boolean(props.paymentCheckout));
const activePaymentCheckout = ref<Record<string, any> | null>(
    props.paymentCheckout ?? null,
);

// Toast Message (Client side notification)
const localToast = ref('');
const showLocalToast = (msg: string) => {
    localToast.value = msg;
    setTimeout(() => {
        if (localToast.value === msg) localToast.value = '';
    }, 4000);
};

// Form submission loading
const isSubmitting = ref(false);

// Format pricing helper
const formatPrice = (value: any) => {
    const num = parseFloat(value);
    if (isNaN(num)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
};

const isReadyTable = (table: any) =>
    table?.status === 'available' || table?.status === 'reserved';

const getTableCardClass = (table: any) => {
    if (table.status === 'occupied') {
        return 'border-red-500/20 bg-red-950/20 text-red-100 hover:border-red-500/40 hover:shadow-red-500/5';
    }

    if (table.status === 'reserved') {
        return 'border-amber-500/20 bg-amber-950/20 text-amber-100 hover:border-amber-500/40 hover:shadow-amber-500/5';
    }

    return 'border-slate-800 bg-slate-950/60 text-slate-100 hover:-translate-y-0.5 hover:border-orange-500/40 hover:shadow-orange-500/5';
};

const getTableIconClass = (table: any) => {
    if (table.status === 'occupied') {
        return 'border-red-500/30 bg-red-500/10 text-red-400';
    }

    if (table.status === 'reserved') {
        return 'border-amber-500/30 bg-amber-500/10 text-amber-400';
    }

    return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400';
};

const getTableBadgeClass = (table: any) => {
    if (table.status === 'occupied') {
        return 'border-red-500/20 bg-red-500/10 text-red-400';
    }

    if (table.status === 'reserved') {
        return 'border-amber-500/20 bg-amber-500/10 text-amber-400';
    }

    return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400';
};

// Filtered products list
const allProducts = computed(() => {
    let list: any[] = [];
    props.categories.forEach((cat) => {
        cat.products.forEach((prod: any) => {
            list.push({
                ...prod,
                category_name: cat.name,
            });
        });
    });
    return list;
});

const filteredProducts = computed(() => {
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

const filteredEditProducts = computed(() => {
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

// Cart calculations
const cartSubtotal = computed(() => {
    return cart.value.reduce(
        (sum, item) => sum + item.unit_price * item.quantity,
        0,
    );
});

const cartTotal = computed(() => {
    return cartSubtotal.value; // Discount can be added in the future
});

const cartItemCount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

const newOrderCashChange = computed(() => {
    if (
        paymentOption.value !== 'pay_now' ||
        newOrderPaymentMethod.value !== 'cash'
    ) {
        return 0;
    }

    const received = Number(newOrderCashReceived.value || 0);
    return Math.max(0, received - cartTotal.value);
});

const editSubtotal = computed(() => {
    return editingItems.value.reduce(
        (sum, item) => sum + item.unit_price * item.quantity,
        0,
    );
});

const editItemCount = computed(() => {
    return editingItems.value.reduce((sum, item) => sum + item.quantity, 0);
});

const splitItemCount = computed(() => {
    return splitDraftItems.value.reduce(
        (sum, item) => sum + Number(item.split_quantity || 0),
        0,
    );
});

const filteredCustomers = computed(() => {
    const query = customerSearchQuery.value.trim().toLowerCase();
    if (!query) return [];

    return props.customers
        .filter((customer) => {
            return (
                customer.name?.toLowerCase().includes(query) ||
                customer.phone?.toLowerCase().includes(query)
            );
        })
        .slice(0, 6);
});

const showNewCustomerForm = computed(() => {
    return (
        customerSearchQuery.value.trim().length > 0 && !selectedCustomer.value
    );
});

const isPhoneLookup = (value: string) => {
    return /^[0-9+\-\s()]+$/.test(value);
};

watch(customerSearchQuery, (value) => {
    if (selectedCustomer.value) return;

    const trimmed = value.trim();
    if (!trimmed) return;

    if (isPhoneLookup(trimmed)) {
        if (!customerPhone.value) {
            customerPhone.value = trimmed;
        }
        return;
    }

    if (!customerName.value) {
        customerName.value = trimmed;
    }
});

onMounted(() => {
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

const getOrderServiceLabel = (order: any) => {
    if (order.type === 'takeaway') return 'Takeaway / Bungkus';
    if (order.table?.name) return order.table.name;
    return 'Dine In';
};

const getOrderServiceBadgeClass = (order: any) => {
    if (order.type === 'takeaway') {
        return 'border-orange-500/20 bg-orange-500/10 text-orange-300';
    }

    return 'border-sky-500/20 bg-sky-500/10 text-sky-300';
};

const getOrderCustomerPrimary = (order: any) => {
    return order.customer?.name || order.customer?.phone || 'Walk-in';
};

const getOrderCustomerSecondary = (order: any) => {
    const details = [
        order.customer?.name ? order.customer?.phone : null,
        order.customer?.membership?.tier?.name,
    ]
        .filter(Boolean)
        .join(' • ');

    return details;
};

const editOrderNeedsApproval = computed(
    () => editingOrder.value?.status === 'in_progress',
);

const canEditOrderStatus = (status?: string) => {
    return ['pending', 'in_progress'].includes((status || '').toLowerCase());
};

const tableActiveOrders = computed(() => {
    const table = selectedTable.value;
    if (!table || table.status !== 'occupied') return [];

    if (Array.isArray(table.active_orders) && table.active_orders.length > 0) {
        return table.active_orders;
    }

    return table.active_order ? [table.active_order] : [];
});

const selectedManagedOrder = computed(() => {
    const orders = tableActiveOrders.value;
    if (orders.length === 0) return null;

    return (
        orders.find(
            (order: any) => order.id === selectedManagedOrderId.value,
        ) || orders[0]
    );
});

const splitOrderNeedsApproval = computed(
    () => selectedManagedOrder.value?.status === 'in_progress',
);

const mergeSelectedOrders = computed(() => {
    return props.activeOrders.filter((order) =>
        mergeSelectionIds.value.includes(order.id),
    );
});

const mergeSelectionTableId = computed(() => {
    return mergeSelectedOrders.value[0]?.table?.id || null;
});

const canMergeSelectedOrders = computed(() => {
    if (mergeSelectedOrders.value.length < 2) return false;
    if (!mergeSelectionTableId.value) return false;

    return mergeSelectedOrders.value.every(
        (order) =>
            order.table?.id === mergeSelectionTableId.value &&
            canEditOrderStatus(order.status),
    );
});

const mergeNeedsApproval = computed(() => {
    return mergeSelectedOrders.value.some(
        (order) => order.status === 'in_progress',
    );
});

const existingPaymentCashChange = computed(() => {
    if (!paymentTargetOrder.value || existingPaymentMethod.value !== 'cash') {
        return 0;
    }

    const received = Number(existingPaymentCashReceived.value || 0);
    return Math.max(
        0,
        received - Number(paymentTargetOrder.value.total_amount),
    );
});

watch(
    () => props.paymentCheckout,
    (value) => {
        activePaymentCheckout.value = value ?? null;
        paymentCheckoutModalOpen.value = Boolean(value);
    },
    { immediate: true },
);

// Handle Table Select
const selectTable = (table: any) => {
    selectedTable.value = table;
    selectedManagedOrderId.value =
        table.active_orders?.[0]?.id || table.active_order?.id || null;
    // Clear cart if switching tables
    cart.value = [];
    orderNotes.value = '';
    resetCustomerSelection();
    resetNewOrderPaymentState();
    resetEditOrderState();
    resetSplitBillState();
    resetMergeBillState();
    closeKasbonModal();
    closePaymentModal();
};

const selectTakeawayOrder = () => {
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
    resetCustomerSelection();
    resetNewOrderPaymentState();
    resetEditOrderState();
    resetSplitBillState();
    resetMergeBillState();
    closeKasbonModal();
    closePaymentModal();
};

// Go back to table grid
const resetTableSelection = () => {
    selectedTable.value = null;
    selectedManagedOrderId.value = null;
    cart.value = [];
    orderNotes.value = '';
    resetCustomerSelection();
    resetNewOrderPaymentState();
    resetEditOrderState();
    resetSplitBillState();
    resetMergeBillState();
    closeKasbonModal();
    closePaymentModal();
};

const resetCustomerSelection = () => {
    customerSearchQuery.value = '';
    selectedCustomer.value = null;
    customerName.value = '';
    customerPhone.value = '';
    customerEmail.value = '';
};

const resetNewOrderPaymentState = () => {
    paymentOption.value = 'pay_later';
    newOrderPaymentMethod.value = 'cash';
    newOrderCashReceived.value = '';
    newOrderPromoCode.value = '';
    newOrderApprovalPin.value = '';
};

const selectCustomer = (customer: any) => {
    selectedCustomer.value = customer;
    customerSearchQuery.value = customer.phone || customer.name || '';
    customerName.value = customer.name || '';
    customerPhone.value = customer.phone || '';
    customerEmail.value = customer.email || '';
};

const cloneOrderItemForEdit = (item: any) => {
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

const resetEditOrderState = () => {
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

const resetSplitBillState = () => {
    splitBillModalOpen.value = false;
    splitDraftItems.value = [];
    splitApprovalPin.value = '';
};

const resetMergeBillState = () => {
    mergeBillModalOpen.value = false;
    mergeApprovalPin.value = '';
    mergeSelectionIds.value = [];
};

const getPaymentMeta = (order: any) => {
    return order?.metadata?.payment || {};
};

const getPromoMeta = (order: any) => {
    return order?.metadata?.promo || {};
};

const getAppliedPromos = (order: any) => {
    return getPromoMeta(order).applied_promos || [];
};

const isOrderPaid = (order: any) => {
    const payment = getPaymentMeta(order);

    return (
        payment.status === 'paid' ||
        Number(order?.paid_amount || 0) >= Number(order?.total_amount || 0)
    );
};

const getPaymentStatusLabel = (order: any) => {
    const payment = getPaymentMeta(order);

    if (isOrderPaid(order)) return 'Lunas';
    if (order?.status === 'payment_pending') return 'Menunggu QRIS';
    if (payment.status === 'pending') return 'Checkout aktif';
    return 'Belum bayar';
};

const getPaymentStatusClass = (order: any) => {
    if (isOrderPaid(order)) {
        return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300';
    }

    if (order?.status === 'payment_pending') {
        return 'border-fuchsia-500/20 bg-fuchsia-500/10 text-fuchsia-300';
    }

    return 'border-amber-500/20 bg-amber-500/10 text-amber-300';
};

const canOpenPaymentModal = (order: any) => {
    if (!order || isOrderPaid(order)) return false;

    return [
        'payment_pending',
        'waiting_bar_approval',
        'ready',
        'delivered',
    ].includes(order.status);
};

const getPaymentActionLabel = (order: any) => {
    if (order?.status === 'payment_pending') {
        return 'Lanjutkan Pembayaran';
    }

    return 'Bayar Tagihan';
};

const getPaymentActionHint = (order: any) => {
    if (order?.status === 'payment_pending') {
        return 'Order belum masuk dapur. Lunasi dulu agar lanjut ke proses kitchen.';
    }

    return 'Settlement akhir transaksi dine in / takeaway yang sudah siap ditutup.';
};

const canCloseAsKasbon = (order: any) => {
    if (!order || isOrderPaid(order)) return false;
    if (!order.customer?.id && !order.customer_id) return false;

    return ['waiting_bar_approval', 'ready', 'delivered'].includes(order.status);
};

const getKasbonActionHint = (order: any) => {
    if (!order?.customer?.id && !order?.customer_id) {
        return 'Kasbon butuh data customer aktif pada order ini.';
    }

    if (isOrderPaid(order)) {
        return 'Order ini sudah lunas, tidak bisa ditutup sebagai kasbon.';
    }

    if (!['waiting_bar_approval', 'ready', 'delivered'].includes(order?.status)) {
        return 'Kasbon dipakai setelah layanan siap ditutup dan masih ada sisa tagihan.';
    }

    return 'Tutup transaksi sebagai piutang customer, lalu cicilan dilanjutkan dari menu transaksi.';
};

const openPaymentModalForOrder = (order: any) => {
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
    existingPaymentMethod.value =
        order.status === 'payment_pending' ? 'qris' : 'cash';
    existingPaymentCashReceived.value = '';
    existingPaymentPromoCode.value = getPromoMeta(order).manual_code || '';
    existingPaymentApprovalPin.value = '';
    paymentModalOpen.value = true;
};

const openPaymentModal = () => {
    openPaymentModalForOrder(selectedManagedOrder.value);
};

const closePaymentModal = () => {
    paymentModalOpen.value = false;
    paymentTargetOrder.value = null;
    existingPaymentMethod.value = 'cash';
    existingPaymentCashReceived.value = '';
    existingPaymentPromoCode.value = '';
    existingPaymentApprovalPin.value = '';
    isProcessingPayment.value = false;
};

const openKasbonModalForOrder = (order: any) => {
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

const openKasbonModal = () => {
    openKasbonModalForOrder(selectedManagedOrder.value);
};

const closeKasbonModal = () => {
    kasbonModalOpen.value = false;
    kasbonTargetOrder.value = null;
    kasbonForm.reset();
    kasbonForm.clearErrors();
};

const openEditOrder = () => {
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

const closeEditOrder = () => {
    resetEditOrderState();
};

const openSplitBill = () => {
    const activeOrder = selectedManagedOrder.value;

    if (!activeOrder) {
        showLocalToast('Pilih order aktif yang ingin di-split.');
        return;
    }

    if (!canEditOrderStatus(activeOrder.status)) {
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

const closeSplitBill = () => {
    resetSplitBillState();
};

const setSplitQuantity = (index: number, nextValue: number) => {
    const current = splitDraftItems.value[index];
    if (!current) return;

    current.split_quantity = Math.max(0, Math.min(current.quantity, nextValue));
};

const submitSplitBill = () => {
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

const toggleMergeSelection = (orderId: string) => {
    if (mergeSelectionIds.value.includes(orderId)) {
        mergeSelectionIds.value = mergeSelectionIds.value.filter(
            (id) => id !== orderId,
        );
        return;
    }

    mergeSelectionIds.value = [...mergeSelectionIds.value, orderId];
};

const openMergeBill = () => {
    if (!canMergeSelectedOrders.value) {
        showLocalToast(
            'Pilih minimal dua order aktif dari meja yang sama untuk gabung bill.',
        );
        return;
    }

    mergeApprovalPin.value = '';
    mergeBillModalOpen.value = true;
};

const closeMergeBill = () => {
    mergeBillModalOpen.value = false;
    mergeApprovalPin.value = '';
};

const submitMergeBills = () => {
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

const addItemToCollection = (
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

// Handle Click Product in Catalog
const handleProductClick = (product: any) => {
    variantTarget.value = 'cart';
    if (product.variants && product.variants.length > 0) {
        selectedProduct.value = product;
        selectedVariant.value = product.variants[0]; // Default select first variant
        itemQuantity.value = 1;
        itemNotes.value = '';
        variantModalOpen.value = true;
    } else {
        // Add direct to cart (no variants)
        addToCartDirect(product);
    }
};

const handleEditProductClick = (product: any) => {
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

// Add product without variant to cart
const addToCartDirect = (product: any) => {
    addItemToCollection(cart.value, product);
    showLocalToast(`Ditambahkan ${product.name} ke keranjang.`);
};

// Confirm and Add variant product to cart
const confirmVariantAdd = () => {
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

// Increase quantity in cart
const increaseCartQty = (index: number) => {
    cart.value[index].quantity += 1;
};

// Decrease quantity in cart
const decreaseCartQty = (index: number) => {
    if (cart.value[index].quantity > 1) {
        cart.value[index].quantity -= 1;
    } else {
        cart.value.splice(index, 1);
    }
};

// Remove item from cart
const removeCartItem = (index: number) => {
    cart.value.splice(index, 1);
};

const increaseEditQty = (index: number) => {
    editingItems.value[index].quantity += 1;
};

const decreaseEditQty = (index: number) => {
    if (editingItems.value[index].quantity > 1) {
        editingItems.value[index].quantity -= 1;
        return;
    }

    editingItems.value.splice(index, 1);
};

const removeEditItem = (index: number) => {
    editingItems.value.splice(index, 1);
};

const submitEditOrder = () => {
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

const submitExistingPayment = () => {
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

const submitKasbon = () => {
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

// Submit Order to Backend
const submitOrder = () => {
    if (!selectedTable.value || cart.value.length === 0) return;

    isSubmitting.value = true;

    const payload = {
        table_id: isTakeawaySelection.value ? null : selectedTable.value.id,
        order_type: isTakeawaySelection.value ? 'takeaway' : 'dine_in',
        reservation_id: isTakeawaySelection.value
            ? null
            : selectedTable.value.active_reservation?.id ?? null,
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

// Get order status style helper
const getStatusClass = (status: string) => {
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
            return 'bg-slate-700 text-slate-350';
    }
};

// Map product images dynamically based on name/category if image_url is empty
const getProductImage = (product: any) => {
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

const openPaymentCheckout = () => {
    if (!activePaymentCheckout.value?.payment_url) return;

    window.open(
        activePaymentCheckout.value.payment_url,
        '_blank',
        'noopener,noreferrer',
    );
};
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
                        class="flex items-center gap-2 font-sans text-2xl font-black tracking-tight text-white"
                    >
                        <span>Layanan Transaksi Kasir</span>
                        <span
                            class="rounded-md border border-orange-500/20 bg-orange-500/10 px-2 py-0.5 text-xs font-semibold text-orange-400"
                        >
                            Menu 1 - 6
                        </span>
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Sistem pembuatan pesanan, pelacakan antrian aktif, dan
                        visualisasi detail meja secara real-time.
                    </p>
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

        <!-- Split Grid Layout -->
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <!-- LEFT PANEL: Table Selection OR Product Catalog -->
            <div class="space-y-6 lg:col-span-7 xl:col-span-8">
                <!-- VIEW 1: Grid of Tables (when no table selected) -->
                <div
                    v-if="!selectedTable"
                    class="rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl"
                >
                    <div class="mb-6 flex items-center justify-between">
                        <h3
                            class="flex items-center gap-2 text-lg font-bold text-white"
                        >
                            <Layers class="h-5 w-5 text-orange-500" />
                            <span>Pilih Meja Restoran (Visual Map)</span>
                        </h3>
                        <div class="flex items-center gap-3 text-xs">
                            <span
                                class="flex items-center gap-1.5 text-slate-400"
                            >
                                <span
                                    class="h-2.5 w-2.5 rounded-full bg-orange-400"
                                ></span>
                                Takeaway
                            </span>
                            <span
                                class="flex items-center gap-1.5 text-slate-400"
                            >
                                <span
                                    class="h-2.5 w-2.5 rounded-full bg-emerald-500"
                                ></span>
                                Available
                            </span>
                            <span
                                class="flex items-center gap-1.5 text-slate-400"
                            >
                                <span
                                    class="h-2.5 w-2.5 rounded-full bg-red-500"
                                ></span>
                                Occupied (Terisi)
                            </span>
                        </div>
                    </div>

                    <!-- Tables Grid -->
                    <div
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            @click="selectTakeawayOrder"
                            class="bg-orange-500/8 group relative cursor-pointer select-none rounded-2xl border border-orange-500/20 p-5 text-center text-orange-100 shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:border-orange-500/40 hover:shadow-orange-500/10"
                        >
                            <div
                                class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border border-orange-500/30 bg-orange-500/10 text-orange-300"
                            >
                                <Package class="h-4 w-4" />
                            </div>

                            <h4 class="text-base font-extrabold">Takeaway</h4>
                            <p
                                class="mt-1 text-[10px] uppercase tracking-wider text-orange-200/70"
                            >
                                Pesanan bungkus / dibawa pulang
                            </p>

                            <span
                                class="absolute right-3 top-3 rounded border border-orange-500/20 bg-orange-500/10 px-1.5 py-0.5 text-[8px] font-bold uppercase text-orange-300"
                            >
                                Bungkus
                            </span>
                        </div>

                        <div
                            v-for="table in tables"
                            :key="table.id"
                            @click="selectTable(table)"
                            :class="[
                                'group relative cursor-pointer select-none rounded-2xl border p-5 text-center shadow-md transition-all duration-200',
                                getTableCardClass(table),
                            ]"
                        >
                            <!-- Visual Table icon representation -->
                            <div
                                :class="[
                                    'mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border text-sm font-bold',
                                    getTableIconClass(table),
                                ]"
                            >
                                T
                            </div>

                            <h4 class="text-base font-extrabold">
                                {{ table.name }}
                            </h4>
                            <p
                                class="mt-1 text-[10px] uppercase tracking-wider text-slate-500"
                            >
                                Kapasitas: {{ table.capacity }} Pax
                            </p>

                            <!-- Mini badge status -->
                            <span
                                :class="[
                                    'absolute right-3 top-3 rounded border px-1.5 py-0.5 text-[8px] font-bold uppercase',
                                    getTableBadgeClass(table),
                                ]"
                            >
                                {{ table.status }}
                            </span>

                            <!-- Active Order Badge -->
                            <div
                                v-if="
                                    table.status === 'occupied' &&
                                    table.active_order
                                "
                                class="mt-3 border-t border-red-500/10 pt-3 text-left"
                            >
                                <p
                                    class="truncate text-[9px] font-bold uppercase text-red-400"
                                >
                                    {{ table.active_order.order_number }}
                                </p>
                                <p
                                    class="mt-0.5 text-[10px] font-bold text-slate-300"
                                >
                                    {{
                                        formatPrice(
                                            table.active_order.total_amount,
                                        )
                                    }}
                                </p>
                                <p
                                    v-if="table.active_orders?.length > 1"
                                    class="mt-1 text-[9px] font-semibold text-orange-300"
                                >
                                    {{ table.active_orders.length }} bill aktif
                                </p>
                            </div>
                            <div
                                v-else-if="table.active_reservation"
                                class="mt-3 border-t border-amber-500/10 pt-3 text-left"
                            >
                                <p
                                    class="truncate text-[9px] font-bold uppercase text-amber-300"
                                >
                                    Reservasi Aktif
                                </p>
                                <p
                                    class="mt-0.5 text-[10px] font-bold text-slate-200"
                                >
                                    {{ table.active_reservation.customer_name }}
                                </p>
                                <p class="mt-1 text-[9px] text-slate-400">
                                    {{ table.active_reservation.guest_count }}
                                    pax
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIEW 2: Product Catalog (when available table selected) -->
                <div
                    v-else-if="isReadyTable(selectedTable)"
                    class="space-y-6 rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl"
                >
                    <!-- Catalog Header with Back Button -->
                    <div
                        class="flex flex-col justify-between gap-4 border-b border-slate-800/60 pb-5 sm:flex-row sm:items-center"
                    >
                        <div class="flex items-center gap-3">
                            <button
                                @click="resetTableSelection"
                                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-800 bg-slate-950 text-slate-300 transition duration-150 hover:bg-slate-800"
                            >
                                <ChevronLeft class="h-5 w-5" />
                            </button>
                            <div>
                                <h3 class="text-base font-bold text-white">
                                    Pilih Produk Mentai
                                </h3>
                                <p class="mt-0.5 text-[10px] text-slate-400">
                                    Melayani pesanan untuk
                                    <span class="font-bold text-orange-400">{{
                                        selectedTable.name
                                    }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Search Bar -->
                        <div class="relative w-full sm:w-64">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"
                            >
                                <Search class="h-4 w-4" />
                            </span>
                            <input
                                type="text"
                                v-model="productSearchQuery"
                                placeholder="Cari makanan/minuman..."
                                class="border-slate-850 w-full rounded-xl border bg-slate-950/80 py-2 pl-9 pr-4 text-xs text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                        </div>
                    </div>

                    <!-- Category Tabs -->
                    <div
                        class="custom-scrollbar flex gap-2 overflow-x-auto pb-2"
                    >
                        <button
                            @click="activeCategory = 'all'"
                            :class="[
                                'whitespace-nowrap rounded-xl border px-4 py-2 text-xs font-semibold transition duration-150',
                                activeCategory === 'all'
                                    ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                                    : 'border-slate-850 bg-slate-950/60 text-slate-400 hover:bg-slate-800/40 hover:text-slate-200',
                            ]"
                        >
                            Semua Menu
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            @click="activeCategory = cat.id"
                            :class="[
                                'whitespace-nowrap rounded-xl border px-4 py-2 text-xs font-semibold transition duration-150',
                                activeCategory === cat.id
                                    ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                                    : 'border-slate-850 bg-slate-950/60 text-slate-400 hover:bg-slate-800/40 hover:text-slate-200',
                            ]"
                        >
                            {{ cat.name }}
                        </button>
                    </div>

                    <!-- Products Grid -->
                    <div
                        v-if="filteredProducts.length > 0"
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    >
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            @click="handleProductClick(product)"
                            class="hover:shadow-orange-500/2 group flex cursor-pointer flex-col justify-between overflow-hidden rounded-xl border border-slate-800/60 bg-slate-950/60 transition duration-150 hover:border-orange-500/30 hover:shadow-md"
                        >
                            <!-- Product Image Area -->
                            <div
                                class="relative h-28 w-full overflow-hidden border-b border-slate-800 bg-slate-900"
                            >
                                <img
                                    :src="getProductImage(product)"
                                    :alt="product.name"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                                <span
                                    class="text-slate-350 absolute right-2 top-2 rounded border border-slate-800 bg-slate-900/80 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider backdrop-blur-sm"
                                >
                                    {{ product.category_name }}
                                </span>
                            </div>

                            <!-- Product Details Area -->
                            <div
                                class="flex flex-1 flex-col justify-between space-y-3 p-3.5"
                            >
                                <div>
                                    <h4
                                        class="text-xs font-bold leading-tight text-white"
                                    >
                                        {{ product.name }}
                                    </h4>
                                    <p
                                        class="text-slate-450 mt-1 line-clamp-2 text-[10px]"
                                    >
                                        {{
                                            product.description ||
                                            'Tidak ada deskripsi.'
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="border-slate-850 flex items-center justify-between border-t pt-2.5"
                                >
                                    <span
                                        class="text-xs font-extrabold text-orange-400"
                                        >{{
                                            formatPrice(product.base_price)
                                        }}</span
                                    >
                                    <span
                                        class="flex h-6 w-6 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10 text-xs font-black text-orange-400"
                                        >+</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty products -->
                    <div
                        v-else
                        class="rounded-xl border border-dashed border-slate-800 py-12 text-center text-slate-500"
                    >
                        <Utensils
                            class="mx-auto mb-2 h-10 w-10 text-slate-600"
                        />
                        <p class="text-xs">
                            Tidak ada menu yang sesuai dengan filter pencarian.
                        </p>
                    </div>
                </div>

                <!-- VIEW 3: Detail Order per Meja (when occupied table selected) -->
                <div
                    v-else-if="selectedTable.status === 'occupied'"
                    class="space-y-6 rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl"
                >
                    <!-- Title Header -->
                    <div
                        class="flex items-center gap-3 border-b border-slate-800/60 pb-5"
                    >
                        <button
                            @click="resetTableSelection"
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-800 bg-slate-950 text-slate-300 transition duration-150 hover:bg-slate-800"
                        >
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <div>
                            <h3
                                class="flex items-center gap-2 text-base font-bold text-white"
                            >
                                <span
                                    >Detail Aktif:
                                    {{ selectedTable.name }}</span
                                >
                                <span
                                    class="rounded border border-red-500/20 bg-red-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-red-400"
                                >
                                    Occupied
                                </span>
                            </h3>
                            <p class="mt-0.5 text-[10px] text-slate-400">
                                Menampilkan rincian pesanan pelanggan di meja
                                saat ini.
                            </p>
                        </div>
                    </div>

                    <!-- Order Info Grid -->
                    <div
                        v-if="selectedManagedOrder"
                        class="border-slate-850 grid grid-cols-1 gap-4 rounded-xl border bg-slate-950/60 p-4 text-xs md:grid-cols-2 xl:grid-cols-5"
                    >
                        <div>
                            <p
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                Nomor Transaksi
                            </p>
                            <p class="mt-1 text-sm font-extrabold text-white">
                                {{ selectedManagedOrder.order_number }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                Pelanggan
                            </p>
                            <p class="mt-1 font-semibold text-white">
                                {{
                                    getOrderCustomerPrimary(
                                        selectedManagedOrder,
                                    )
                                }}
                            </p>
                            <p
                                v-if="
                                    getOrderCustomerSecondary(
                                        selectedManagedOrder,
                                    )
                                "
                                class="mt-0.5 text-[10px] text-slate-400"
                            >
                                {{
                                    getOrderCustomerSecondary(
                                        selectedManagedOrder,
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                Kasir Penanggung Jawab
                            </p>
                            <p
                                class="mt-1 flex items-center gap-1.5 font-semibold text-white"
                            >
                                <User class="text-slate-450 h-3.5 w-3.5" />
                                <span>{{
                                    selectedManagedOrder.cashier?.name ||
                                    'Kasir Restoran'
                                }}</span>
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                Waktu Mulai Pesan
                            </p>
                            <p
                                class="mt-1 flex items-center gap-1.5 font-semibold text-white"
                            >
                                <Clock class="text-slate-450 h-3.5 w-3.5" />
                                <span
                                    >{{
                                        new Date(
                                            selectedManagedOrder.created_at,
                                        ).toLocaleTimeString('id-ID', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })
                                    }}
                                    WIB</span
                                >
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                Status Pembayaran
                            </p>
                            <div class="mt-1 space-y-2">
                                <span
                                    :class="[
                                        'inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider',
                                        getPaymentStatusClass(
                                            selectedManagedOrder,
                                        ),
                                    ]"
                                >
                                    {{
                                        getPaymentStatusLabel(
                                            selectedManagedOrder,
                                        )
                                    }}
                                </span>
                                <p class="text-[10px] text-slate-400">
                                    {{
                                        isOrderPaid(selectedManagedOrder)
                                            ? 'Tagihan sudah diterima dan tercatat.'
                                            : getPaymentActionHint(
                                                  selectedManagedOrder,
                                              )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Ordered Items List -->
                    <div v-if="selectedManagedOrder" class="space-y-3">
                        <div
                            v-if="tableActiveOrders.length > 1"
                            class="custom-scrollbar flex gap-2 overflow-x-auto pb-1"
                        >
                            <button
                                v-for="order in tableActiveOrders"
                                :key="`managed-order-${order.id}`"
                                @click="selectedManagedOrderId = order.id"
                                :class="[
                                    'whitespace-nowrap rounded-xl border px-3 py-2 text-[11px] font-bold transition',
                                    selectedManagedOrderId === order.id
                                        ? 'border-orange-500/30 bg-orange-500/10 text-orange-300'
                                        : 'border-slate-800 bg-slate-950/70 text-slate-400 hover:text-slate-200',
                                ]"
                            >
                                {{ order.order_number }}
                            </button>
                        </div>
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-slate-400"
                        >
                            Daftar Item Makanan/Minuman
                        </h4>
                        <div
                            class="overflow-hidden rounded-xl border border-slate-800/80 bg-slate-950/20"
                        >
                            <table
                                class="w-full border-collapse text-left text-xs"
                            >
                                <thead>
                                    <tr
                                        class="border-slate-850 border-b bg-slate-950/80 text-slate-400"
                                    >
                                        <th class="p-3">Nama Menu</th>
                                        <th class="p-3 text-center">Jumlah</th>
                                        <th class="p-3 text-right">
                                            Harga Satuan
                                        </th>
                                        <th class="p-3 text-right">
                                            Total Harga
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-slate-850 divide-y">
                                    <tr
                                        v-for="item in selectedManagedOrder.items"
                                        :key="item.id"
                                        class="text-slate-200"
                                    >
                                        <td class="p-3">
                                            <div class="font-bold">
                                                {{ item.product?.name }}
                                            </div>
                                            <div
                                                v-if="item.variant"
                                                class="mt-0.5 text-[9px] font-semibold text-orange-400"
                                            >
                                                Varian: {{ item.variant.name }}
                                            </div>
                                            <div
                                                v-if="item.notes"
                                                class="text-slate-450 mt-0.5 text-[9px] italic"
                                            >
                                                "{{ item.notes }}"
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-center font-bold text-slate-100"
                                        >
                                            {{ item.quantity }}x
                                        </td>
                                        <td class="p-3 text-right">
                                            {{ formatPrice(item.unit_price) }}
                                        </td>
                                        <td
                                            class="p-3 text-right font-extrabold text-white"
                                        >
                                            {{ formatPrice(item.total_price) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes & Total summary -->
                    <div
                        v-if="selectedManagedOrder"
                        class="flex flex-col justify-between gap-4 border-t border-slate-800/50 pt-5 text-xs md:flex-row"
                    >
                        <div class="md:w-1/2">
                            <p
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                Catatan Khusus Pesanan
                            </p>
                            <p
                                class="text-slate-350 border-slate-850 mt-1 rounded-lg border bg-slate-950/50 p-3 italic"
                            >
                                {{
                                    selectedManagedOrder.notes ||
                                    'Tidak ada catatan khusus.'
                                }}
                            </p>
                        </div>
                        <div
                            class="border-slate-850 space-y-2 rounded-xl border bg-slate-950/50 p-4 md:w-1/3"
                        >
                            <div class="flex justify-between text-slate-400">
                                <span>Subtotal:</span>
                                <span>{{
                                    formatPrice(selectedManagedOrder.subtotal)
                                }}</span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Diskon:</span>
                                <span>{{
                                    formatPrice(
                                        selectedManagedOrder.discount_amount,
                                    )
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between border-t border-slate-800 pt-2 text-sm font-black text-white"
                            >
                                <span>Total Tagihan:</span>
                                <span class="text-orange-400">{{
                                    formatPrice(
                                        selectedManagedOrder.total_amount,
                                    )
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between border-t border-slate-800 pt-2 text-slate-400"
                            >
                                <span>Terbayar:</span>
                                <span>{{
                                    formatPrice(
                                        selectedManagedOrder.paid_amount,
                                    )
                                }}</span>
                            </div>
                            <div
                                v-if="getAppliedPromos(selectedManagedOrder).length"
                                class="space-y-2 border-t border-slate-800 pt-2"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    Promo Applied
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="promo in getAppliedPromos(selectedManagedOrder)"
                                        :key="promo.id"
                                        class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2 py-1 text-[10px] font-bold text-orange-200"
                                    >
                                        {{ promo.name }} • {{ formatPrice(promo.discount_amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div
                            class="border-slate-850 space-y-2 rounded-xl border bg-slate-950/50 p-4 md:w-1/3"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    Ringkasan Pembayaran
                                </p>
                                <span
                                    :class="[
                                        'rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                        getPaymentStatusClass(
                                            selectedManagedOrder,
                                        ),
                                    ]"
                                >
                                    {{
                                        getPaymentStatusLabel(
                                            selectedManagedOrder,
                                        )
                                    }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                {{
                                    getPaymentMeta(selectedManagedOrder).method
                                        ? `Metode terakhir: ${String(
                                              getPaymentMeta(
                                                  selectedManagedOrder,
                                              ).method,
                                          ).toUpperCase()}`
                                        : 'Belum ada metode pembayaran yang dipilih.'
                                }}
                            </p>
                            <button
                                v-if="
                                    getPaymentMeta(selectedManagedOrder)
                                        .checkout_url &&
                                    !isOrderPaid(selectedManagedOrder)
                                "
                                type="button"
                                @click="
                                    activePaymentCheckout = {
                                        payment_url:
                                            getPaymentMeta(selectedManagedOrder)
                                                .checkout_url,
                                        order_number:
                                            selectedManagedOrder.order_number,
                                        amount: selectedManagedOrder.total_amount,
                                        context:
                                            selectedManagedOrder.status ===
                                            'payment_pending'
                                                ? 'before_kitchen'
                                                : 'after_service',
                                    };
                                    paymentCheckoutModalOpen = true;
                                "
                                class="w-full rounded-xl border border-fuchsia-500/20 bg-fuchsia-500/10 px-3 py-2 text-[11px] font-bold text-fuchsia-200 transition hover:bg-fuchsia-500/15"
                            >
                                Buka Checkout QRIS Aktif
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: Cart OR Table Actions OR Active Orders list -->
            <div class="space-y-6 lg:col-span-5 xl:col-span-4">
                <!-- VIEW 1: Cart (when available table selected) -->
                <div
                    v-if="selectedTable && isReadyTable(selectedTable)"
                    class="flex min-h-[500px] flex-col rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl"
                >
                    <h3
                        class="mb-4 flex items-center gap-2 border-b border-slate-800/60 pb-4 text-lg font-bold text-white"
                    >
                        <ShoppingCart class="h-5 w-5 text-orange-500" />
                        <span>{{
                            isTakeawaySelection
                                ? 'Keranjang Takeaway'
                                : `Keranjang Meja: ${selectedTable.name}`
                        }}</span>
                    </h3>

                    <div
                        class="mb-4 grid gap-3 rounded-xl border border-slate-800/80 bg-slate-950/40 p-4 sm:grid-cols-3"
                    >
                        <div
                            class="rounded-xl border border-slate-800/80 bg-slate-950/80 p-3"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Tipe Layanan
                            </p>
                            <p class="mt-2 text-sm font-extrabold text-white">
                                {{
                                    isTakeawaySelection ? 'Takeaway' : 'Dine In'
                                }}
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                {{
                                    isTakeawaySelection
                                        ? 'Pesanan bungkus / pickup'
                                        : 'Pesanan makan di tempat'
                                }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-slate-800/80 bg-slate-950/80 p-3"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Tujuan Order
                            </p>
                            <p class="mt-2 text-sm font-extrabold text-white">
                                {{ selectedTable.name }}
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                {{
                                    isTakeawaySelection
                                        ? 'Ambil di counter kasir'
                                        : 'Kirim ke meja terpilih'
                                }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-slate-800/80 bg-slate-950/80 p-3"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Item Keranjang
                            </p>
                            <p class="mt-2 text-sm font-extrabold text-white">
                                {{ cartItemCount }} item
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                {{ cart.length }} jenis menu aktif
                            </p>
                        </div>
                    </div>

                    <div
                        class="mb-4 space-y-3 rounded-xl border border-slate-800/80 bg-slate-950/40 p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                                >
                                    Langkah 1 • Data Pelanggan
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Input nomor HP terlebih dulu, lalu pilih
                                    customer atau daftar singkat jika belum ada.
                                </p>
                            </div>
                            <button
                                v-if="
                                    selectedCustomer ||
                                    customerName ||
                                    customerPhone ||
                                    customerEmail
                                "
                                type="button"
                                @click="resetCustomerSelection"
                                class="rounded-lg border border-slate-700 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 transition hover:border-slate-600 hover:text-slate-200"
                            >
                                Reset
                            </button>
                        </div>

                        <div class="relative">
                            <input
                                v-model="customerSearchQuery"
                                type="text"
                                placeholder="Cari pelanggan by nomor HP atau nama..."
                                class="border-slate-850 w-full rounded-xl border bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />

                            <div
                                v-if="
                                    !selectedCustomer &&
                                    filteredCustomers.length > 0
                                "
                                class="absolute z-10 mt-2 w-full space-y-2 rounded-xl border border-slate-800 bg-slate-950 p-2 shadow-2xl"
                            >
                                <button
                                    v-for="customer in filteredCustomers"
                                    :key="customer.id"
                                    type="button"
                                    @click="selectCustomer(customer)"
                                    class="flex w-full items-start justify-between rounded-lg border border-slate-900 bg-slate-900/80 px-3 py-2 text-left transition hover:border-orange-500/20 hover:bg-slate-900"
                                >
                                    <div>
                                        <p class="text-xs font-bold text-white">
                                            {{
                                                customer.name || 'Pelanggan POS'
                                            }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-[11px] text-slate-400"
                                        >
                                            {{ customer.phone }}
                                        </p>
                                    </div>
                                    <span
                                        v-if="customer.membership?.tier?.name"
                                        class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-emerald-300"
                                    >
                                        {{ customer.membership.tier.name }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="selectedCustomer"
                            class="rounded-xl border border-emerald-500/15 bg-emerald-500/5 p-3"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p class="text-xs font-bold text-white">
                                        {{
                                            selectedCustomer.name ||
                                            'Pelanggan POS'
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[11px] text-slate-400"
                                    >
                                        {{ selectedCustomer.phone }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p
                                        v-if="
                                            selectedCustomer.membership?.tier
                                                ?.name
                                        "
                                        class="text-[10px] font-bold uppercase tracking-wider text-emerald-300"
                                    >
                                        {{
                                            selectedCustomer.membership.tier
                                                .name
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            selectedCustomer.membership
                                                ?.total_points !== undefined
                                        "
                                        class="mt-0.5 text-[11px] text-slate-400"
                                    >
                                        {{
                                            selectedCustomer.membership
                                                .total_points
                                        }}
                                        poin
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="showNewCustomerForm"
                            class="grid gap-3 rounded-xl border border-orange-500/15 bg-orange-500/5 p-3"
                        >
                            <p class="text-[11px] text-orange-200/80">
                                Customer belum ditemukan. Simpan sebagai
                                pelanggan baru dari transaksi ini.
                            </p>
                            <input
                                v-model="customerName"
                                type="text"
                                placeholder="Nama pelanggan"
                                class="border-slate-850 w-full rounded-xl border bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                            <input
                                v-model="customerPhone"
                                type="text"
                                placeholder="Nomor HP pelanggan"
                                class="border-slate-850 w-full rounded-xl border bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                            <input
                                v-model="customerEmail"
                                type="email"
                                placeholder="Email pelanggan (opsional)"
                                class="border-slate-850 w-full rounded-xl border bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                        </div>
                    </div>

                    <!-- Cart items list -->
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                            >
                                Langkah 2 • Ringkasan Menu
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Cek item, jumlah, varian, dan catatan per menu.
                            </p>
                        </div>
                        <span
                            class="rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-300"
                        >
                            {{ cartItemCount }} item
                        </span>
                    </div>
                    <div
                        class="custom-scrollbar max-h-[300px] flex-1 space-y-3 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="(item, index) in cart"
                            :key="index"
                            class="border-slate-850 flex items-start justify-between gap-3 rounded-xl border bg-slate-950/60 p-3 text-xs"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate font-bold leading-snug text-white"
                                >
                                    {{ item.product_name }}
                                </p>
                                <p
                                    v-if="item.variant_name"
                                    class="mt-0.5 text-[10px] font-semibold text-orange-400"
                                >
                                    Varian: {{ item.variant_name }}
                                </p>
                                <p
                                    v-if="item.notes"
                                    class="text-slate-450 mt-0.5 text-[9px] italic"
                                >
                                    "{{ item.notes }}"
                                </p>
                                <p class="mt-2 font-extrabold text-slate-300">
                                    {{
                                        formatPrice(
                                            item.unit_price * item.quantity,
                                        )
                                    }}
                                </p>
                            </div>

                            <!-- Control Quantity -->
                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    @click="decreaseCartQty(index)"
                                    class="border-slate-850 flex h-6 w-6 items-center justify-center rounded border bg-slate-900 text-slate-400 hover:text-white"
                                >
                                    <Minus class="h-3 w-3" />
                                </button>
                                <span
                                    class="w-5 text-center text-xs font-bold text-slate-200"
                                    >{{ item.quantity }}</span
                                >
                                <button
                                    @click="increaseCartQty(index)"
                                    class="border-slate-850 flex h-6 w-6 items-center justify-center rounded border bg-slate-900 text-slate-400 hover:text-white"
                                >
                                    <Plus class="h-3 w-3" />
                                </button>
                                <button
                                    @click="removeCartItem(index)"
                                    class="ml-1 flex h-6 w-6 items-center justify-center rounded border border-red-900/30 bg-red-950/20 text-red-400 hover:text-red-300"
                                >
                                    <Trash2 class="h-3 w-3" />
                                </button>
                            </div>
                        </div>

                        <!-- Empty cart state -->
                        <div
                            v-if="cart.length === 0"
                            class="border-slate-850 rounded-xl border border-dashed py-16 text-center text-xs text-slate-500"
                        >
                            Keranjang belanja kosong.<br />Silakan pilih makanan
                            di panel kiri.
                        </div>
                    </div>

                    <!-- Cart summary & notes -->
                    <div
                        class="mt-4 shrink-0 space-y-4 border-t border-slate-800/80 pt-4 text-xs"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                            >
                                Langkah 3 • Review & Kirim
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Tambahkan catatan dapur, lalu tentukan order
                                dibayar sekarang atau nanti.
                            </p>
                        </div>
                        <div>
                            <label
                                for="order-notes"
                                class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                                >Catatan Dapur (Order Notes)</label
                            >
                            <textarea
                                id="order-notes"
                                v-model="orderNotes"
                                :placeholder="
                                    isTakeawaySelection
                                        ? 'Contoh: Sambal dipisah, sendok plastik 2, tanpa daun bawang...'
                                        : 'Contoh: Meja 1 minta saus mentai dipanggang lebih garing...'
                                "
                                class="border-slate-850 h-16 w-full resize-none rounded-xl border bg-slate-950 p-3 text-xs text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            ></textarea>
                        </div>

                        <!-- Pricing Summary -->
                        <div
                            class="border-slate-850 space-y-2 rounded-xl border bg-slate-950/50 p-4"
                        >
                            <div class="space-y-2 pb-2">
                                <label
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Voucher / Promo Code
                                </label>
                                <input
                                    v-model="newOrderPromoCode"
                                    type="text"
                                    placeholder="Contoh: MENTAI10"
                                    class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs uppercase text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                />
                                <p class="text-[11px] leading-5 text-slate-500">
                                    Promo otomatis, tier member, happy hour, dan voucher diverifikasi server saat order disimpan.
                                </p>
                                <div class="pt-1">
                                    <label
                                        class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                                    >
                                        PIN Owner (Opsional)
                                    </label>
                                    <input
                                        v-model="newOrderApprovalPin"
                                        type="password"
                                        inputmode="numeric"
                                        placeholder="Isi jika diskon manual melewati threshold"
                                        class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                    />
                                    <p class="mt-2 text-[11px] leading-5 text-slate-500">
                                        Hanya diperlukan jika voucher atau diskon manual memicu approval owner.
                                    </p>
                                </div>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Subtotal:</span>
                                <span>{{ formatPrice(cartSubtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>Estimasi diskon:</span>
                                <span>Dihitung saat submit</span>
                            </div>
                            <div
                                class="flex justify-between border-t border-slate-800 pt-2 text-sm font-black text-white"
                            >
                                <span>Total sebelum promo final:</span>
                                <span class="text-orange-400">{{
                                    formatPrice(cartTotal)
                                }}</span>
                            </div>
                        </div>

                        <div
                            class="space-y-4 rounded-xl border border-slate-800/80 bg-slate-950/50 p-4"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                                >
                                    Langkah 4 • Opsi Pembayaran
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Kasir manual bisa langsung lunas atau tetap
                                    buka tagihan untuk dibayar nanti.
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    @click="paymentOption = 'pay_later'"
                                    :class="[
                                        'rounded-xl border p-3 text-left transition',
                                        paymentOption === 'pay_later'
                                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'
                                            : 'border-slate-800 bg-slate-950 text-slate-300 hover:border-slate-700',
                                    ]"
                                >
                                    <p class="text-xs font-bold">Bayar Nanti</p>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        Order langsung masuk dapur, pembayaran
                                        diselesaikan saat closing transaksi.
                                    </p>
                                </button>
                                <button
                                    type="button"
                                    @click="paymentOption = 'pay_now'"
                                    :class="[
                                        'rounded-xl border p-3 text-left transition',
                                        paymentOption === 'pay_now'
                                            ? 'border-orange-500/30 bg-orange-500/10 text-orange-100'
                                            : 'border-slate-800 bg-slate-950 text-slate-300 hover:border-slate-700',
                                    ]"
                                >
                                    <p class="text-xs font-bold">
                                        Bayar Sekarang
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        Cocok untuk cash langsung atau QRIS
                                        sebelum order lanjut ke operasional.
                                    </p>
                                </button>
                            </div>

                            <div
                                v-if="paymentOption === 'pay_now'"
                                class="space-y-3 rounded-xl border border-orange-500/15 bg-orange-500/5 p-4"
                            >
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <button
                                        type="button"
                                        @click="newOrderPaymentMethod = 'cash'"
                                        :class="[
                                            'rounded-xl border p-3 text-left transition',
                                            newOrderPaymentMethod === 'cash'
                                                ? 'border-orange-500/30 bg-slate-950 text-white'
                                                : 'border-slate-800 bg-slate-950/70 text-slate-300',
                                        ]"
                                    >
                                        <p class="text-xs font-bold">Cash</p>
                                        <p
                                            class="mt-1 text-[11px] text-slate-400"
                                        >
                                            Kasir input nominal diterima, lalu
                                            order langsung lanjut ke dapur.
                                        </p>
                                    </button>
                                    <button
                                        type="button"
                                        @click="newOrderPaymentMethod = 'qris'"
                                        :class="[
                                            'rounded-xl border p-3 text-left transition',
                                            newOrderPaymentMethod === 'qris'
                                                ? 'border-fuchsia-500/30 bg-slate-950 text-white'
                                                : 'border-slate-800 bg-slate-950/70 text-slate-300',
                                        ]"
                                    >
                                        <p class="text-xs font-bold">QRIS</p>
                                        <p
                                            class="mt-1 text-[11px] text-slate-400"
                                        >
                                            Buat checkout gateway. Order baru
                                            masuk dapur setelah webhook lunas
                                            diterima.
                                        </p>
                                    </button>
                                </div>

                                <div
                                    v-if="newOrderPaymentMethod === 'cash'"
                                    class="grid gap-3 sm:grid-cols-2"
                                >
                                    <div>
                                        <label
                                            class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                                        >
                                            Nominal Diterima
                                        </label>
                                        <input
                                            v-model="newOrderCashReceived"
                                            type="number"
                                            min="0"
                                            step="1000"
                                            placeholder="Contoh: 100000"
                                            class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                        />
                                    </div>
                                    <div
                                        class="rounded-xl border border-slate-800 bg-slate-950 p-4"
                                    >
                                        <p
                                            class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                                        >
                                            Estimasi Kembalian
                                        </p>
                                        <p
                                            class="mt-2 text-sm font-extrabold text-emerald-300"
                                        >
                                            {{
                                                formatPrice(newOrderCashChange)
                                            }}
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] text-slate-400"
                                        >
                                            Jika nominal kosong, sistem anggap
                                            pas sesuai total.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Action Buttons -->
                        <button
                            @click="submitOrder"
                            :disabled="cart.length === 0 || isSubmitting"
                            class="to-red-650 hover:to-red-750 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 px-5 py-3 font-bold text-white shadow-md shadow-orange-500/10 transition duration-200 hover:from-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500/50 active:scale-[0.99] disabled:pointer-events-none disabled:opacity-50"
                        >
                            <!-- Spinner loader -->
                            <svg
                                v-if="isSubmitting"
                                class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span>{{
                                isSubmitting
                                    ? 'Mengirim Order...'
                                    : paymentOption === 'pay_later'
                                      ? 'Kirim Order ke Dapur (Bayar Nanti)'
                                      : newOrderPaymentMethod === 'cash'
                                        ? 'Bayar Tunai & Kirim ke Dapur'
                                        : 'Buat Checkout QRIS'
                            }}</span>
                        </button>
                    </div>
                </div>

                <!-- VIEW 2: Actions & tracking for occupied table -->
                <div
                    v-else-if="
                        selectedTable && selectedTable.status === 'occupied'
                    "
                    class="space-y-4 rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl"
                >
                    <h3
                        class="flex items-center gap-2 border-b border-slate-800/60 pb-4 text-lg font-bold text-white"
                    >
                        <Receipt class="h-5 w-5 text-orange-500" />
                        <span>Manajemen Transaksi</span>
                    </h3>

                    <p class="text-xs leading-relaxed text-slate-400">
                        Meja ini sedang digunakan. Anda dapat melakukan
                        pembayaran kasir, edit pesanan, atau pembagian tagihan.
                    </p>

                    <div
                        v-if="selectedManagedOrder"
                        class="rounded-xl border border-slate-800/80 bg-slate-950/60 p-3"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                        >
                            Order Aktif Dipilih
                        </p>
                        <div
                            class="mt-2 flex items-center justify-between gap-3 text-xs"
                        >
                            <div>
                                <p class="font-bold text-white">
                                    {{ selectedManagedOrder.order_number }}
                                </p>
                                <p class="mt-0.5 text-slate-400">
                                    {{
                                        getOrderCustomerPrimary(
                                            selectedManagedOrder,
                                        )
                                    }}
                                </p>
                            </div>
                            <span
                                class="rounded-full border border-slate-700 bg-slate-900 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-300"
                            >
                                {{ tableActiveOrders.length }} bill aktif
                            </span>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="space-y-3 pt-2">
                        <div class="group relative">
                            <button
                                @click="openPaymentModal"
                                :disabled="
                                    !canOpenPaymentModal(selectedManagedOrder)
                                "
                                class="flex w-full items-center justify-between rounded-xl border border-orange-500/20 bg-orange-500/5 p-3.5 text-xs font-bold text-orange-400 transition duration-150 hover:bg-orange-500/10 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span>{{
                                    getPaymentActionLabel(selectedManagedOrder)
                                }}</span>
                                <span
                                    class="rounded border border-orange-500/20 bg-slate-900 px-2 py-0.5 text-[8px] font-bold uppercase tracking-wider text-orange-300"
                                >
                                    Menu #6 - Live
                                </span>
                            </button>
                            <p class="mt-1 px-1 text-[10px] text-slate-500">
                                {{
                                    canOpenPaymentModal(selectedManagedOrder)
                                        ? getPaymentActionHint(
                                              selectedManagedOrder,
                                          )
                                        : 'Order ini belum ada di tahap settlement atau sudah lunas.'
                                }}
                            </p>
                        </div>

                        <div class="group relative">
                            <button
                                @click="openKasbonModal"
                                :disabled="!canCloseAsKasbon(selectedManagedOrder)"
                                class="flex w-full items-center justify-between rounded-xl border border-amber-500/20 bg-amber-500/5 p-3.5 text-xs font-bold text-amber-300 transition duration-150 hover:bg-amber-500/10 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span>Tutup Sebagai Kasbon</span>
                                <span
                                    class="rounded border border-amber-500/20 bg-slate-900 px-2 py-0.5 text-[8px] font-bold uppercase tracking-wider text-amber-300"
                                >
                                    Menu #7 - Live
                                </span>
                            </button>
                            <p class="mt-1 px-1 text-[10px] text-slate-500">
                                {{ getKasbonActionHint(selectedManagedOrder) }}
                            </p>
                        </div>

                        <div class="group relative">
                            <button
                                @click="openEditOrder"
                                class="flex w-full items-center justify-between rounded-xl border border-sky-500/20 bg-sky-500/5 p-3.5 text-xs font-bold text-sky-300 transition duration-150 hover:bg-sky-500/10"
                            >
                                <span>Edit Order (Approval Flow)</span>
                                <span
                                    class="rounded border border-sky-500/20 bg-slate-900 px-2 py-0.5 text-[8px] font-bold uppercase tracking-wider text-sky-300"
                                >
                                    Menu #4 - Live
                                </span>
                            </button>
                            <p class="mt-1 px-1 text-[10px] text-slate-500">
                                Status `pending` bisa diedit langsung. Status
                                `in_progress` butuh PIN approval supervisor.
                            </p>
                        </div>

                        <div class="group relative">
                            <button
                                @click="openSplitBill"
                                class="flex w-full items-center justify-between rounded-xl border border-fuchsia-500/20 bg-fuchsia-500/5 p-3.5 text-xs font-bold text-fuchsia-300 transition duration-150 hover:bg-fuchsia-500/10"
                            >
                                <span>Split Bill</span>
                                <span
                                    class="rounded border border-fuchsia-500/20 bg-slate-900 px-2 py-0.5 text-[8px] font-bold uppercase tracking-wider text-fuchsia-300"
                                >
                                    Menu #5 - Live
                                </span>
                            </button>
                            <p class="mt-1 px-1 text-[10px] text-slate-500">
                                Pisahkan item tertentu ke bill kedua dari order
                                aktif yang dipilih.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- VIEW 3: List of Active Orders (when no table selected - Menu #2) -->
                <div
                    v-else
                    class="flex min-h-[500px] flex-col rounded-2xl border border-slate-800/80 bg-slate-900 p-6 shadow-xl"
                >
                    <h3
                        class="mb-4 flex items-center gap-2 border-b border-slate-800/60 pb-4 text-lg font-bold text-white"
                    >
                        <CookingPot class="h-5 w-5 text-orange-500" />
                        <span>Daftar Order Aktif (Menu #2)</span>
                    </h3>

                    <div
                        class="mb-4 rounded-xl border border-slate-800/80 bg-slate-950/50 p-3"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-300"
                                >
                                    Menu #5 • Gabung Bill
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Pilih minimal dua order aktif dari meja yang
                                    sama, lalu gabungkan jadi satu bill.
                                </p>
                            </div>
                            <button
                                @click="openMergeBill"
                                :disabled="!canMergeSelectedOrders"
                                class="rounded-xl border border-fuchsia-500/20 bg-fuchsia-500/10 px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-fuchsia-300 transition disabled:pointer-events-none disabled:opacity-40"
                            >
                                Gabung Bill
                            </button>
                        </div>
                    </div>

                    <!-- Order list -->
                    <div
                        class="custom-scrollbar max-h-[420px] flex-1 space-y-3 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="order in activeOrders"
                            :key="order.id"
                            class="border-slate-850 space-y-3 rounded-xl border bg-slate-950/60 p-4 transition duration-150 hover:border-slate-700"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-if="order.table?.id"
                                            :checked="
                                                mergeSelectionIds.includes(
                                                    order.id,
                                                )
                                            "
                                            :disabled="
                                                !canEditOrderStatus(
                                                    order.status,
                                                )
                                            "
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-fuchsia-500 focus:ring-fuchsia-500"
                                            @change="
                                                toggleMergeSelection(order.id)
                                            "
                                        />
                                        <span
                                            class="text-xs font-black text-white"
                                            >{{ order.order_number }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
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
                                                getOrderServiceBadgeClass(
                                                    order,
                                                ),
                                            ]"
                                        >
                                            {{ getOrderServiceLabel(order) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-[9px] font-semibold uppercase tracking-wider text-slate-500"
                                    >
                                        Total Bill
                                    </p>
                                    <p
                                        class="mt-1 font-extrabold text-orange-400"
                                    >
                                        {{ formatPrice(order.total_amount) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-2 text-[11px] text-slate-400 sm:grid-cols-2"
                            >
                                <div>
                                    <p
                                        class="text-[9px] font-semibold uppercase text-slate-500"
                                    >
                                        Layanan
                                    </p>
                                    <p class="mt-0.5 font-bold text-slate-200">
                                        {{ getOrderServiceLabel(order) }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] font-semibold uppercase text-slate-500"
                                    >
                                        Pelanggan
                                    </p>
                                    <p class="mt-0.5 font-bold text-slate-200">
                                        {{ getOrderCustomerPrimary(order) }}
                                    </p>
                                    <p
                                        v-if="getOrderCustomerSecondary(order)"
                                        class="mt-0.5 text-[10px] text-slate-500"
                                    >
                                        {{ getOrderCustomerSecondary(order) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-if="canOpenPaymentModal(order)"
                                    type="button"
                                    @click="openPaymentModalForOrder(order)"
                                    class="rounded-xl border border-orange-500/20 bg-orange-500/10 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-orange-300 transition hover:bg-orange-500/15"
                                >
                                    {{ getPaymentActionLabel(order) }}
                                </button>
                                <button
                                    v-if="canCloseAsKasbon(order)"
                                    type="button"
                                    @click="openKasbonModalForOrder(order)"
                                    class="rounded-xl border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-amber-300 transition hover:bg-amber-500/15"
                                >
                                    Tutup Kasbon
                                </button>
                                <span
                                    v-else-if="
                                        !canCloseAsKasbon(order) &&
                                        !isOrderPaid(order)
                                    "
                                    class="rounded-xl border border-slate-800 bg-slate-900/70 px-3 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    {{ getKasbonActionHint(order) }}
                                </span>
                            </div>

                            <!-- Footer detail items counter -->
                            <div
                                class="border-slate-850/60 flex items-center justify-between border-t pt-2 text-[10px] text-slate-500"
                            >
                                <span
                                    >{{ order.items?.length || 0 }} Jenis Menu
                                    Pesanan</span
                                >
                                <span
                                    >{{
                                        new Date(
                                            order.created_at,
                                        ).toLocaleTimeString('id-ID', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })
                                    }}
                                    WIB</span
                                >
                            </div>
                        </div>

                        <!-- Empty orders state -->
                        <div
                            v-if="activeOrders.length === 0"
                            class="border-slate-850 rounded-xl border border-dashed py-20 text-center text-xs text-slate-500"
                        >
                            Belum ada transaksi aktif saat ini.<br />Gunakan
                            panel peta meja untuk membuat baru.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="editOrderModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm"
            >
                <div
                    class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-800/80 px-6 py-5"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-sky-300"
                                >
                                    Menu #4
                                </span>
                                <span
                                    :class="[
                                        'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em]',
                                        getStatusClass(
                                            editingOrder?.status || '',
                                        ),
                                    ]"
                                >
                                    {{ editingOrder?.status }}
                                </span>
                            </div>
                            <h3 class="mt-3 text-xl font-black text-white">
                                Edit Order {{ editingOrder?.order_number }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-400">
                                Ubah item aktif, lalu sistem akan reset status
                                order kembali ke `pending` sesuai brief.
                            </p>
                        </div>
                        <button
                            @click="closeEditOrder"
                            class="text-slate-500 transition hover:text-slate-200"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div
                        class="grid min-h-0 flex-1 gap-0 lg:grid-cols-[1.2fr_0.8fr]"
                    >
                        <div
                            class="flex min-h-0 flex-col border-r border-slate-800/70"
                        >
                            <div
                                class="grid gap-3 border-b border-slate-800/70 px-6 py-4 sm:grid-cols-3"
                            >
                                <div
                                    class="rounded-xl border border-slate-800 bg-slate-950/60 p-3"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                    >
                                        Layanan
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-extrabold text-white"
                                    >
                                        {{ getOrderServiceLabel(editingOrder) }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-slate-800 bg-slate-950/60 p-3"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                    >
                                        Pelanggan
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-extrabold text-white"
                                    >
                                        {{
                                            getOrderCustomerPrimary(
                                                editingOrder,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-slate-800 bg-slate-950/60 p-3"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                    >
                                        Ringkasan
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-extrabold text-white"
                                    >
                                        {{ editItemCount }} item
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        {{ editingItems.length }} jenis menu
                                    </p>
                                </div>
                            </div>

                            <div class="border-b border-slate-800/70 px-6 py-4">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                                        >
                                            Tambah Menu
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            Pilih kategori, cari menu, lalu
                                            tambahkan ke draft edit.
                                        </p>
                                    </div>
                                    <div class="relative w-full max-w-xs">
                                        <span
                                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"
                                        >
                                            <Search class="h-4 w-4" />
                                        </span>
                                        <input
                                            v-model="editProductSearchQuery"
                                            type="text"
                                            placeholder="Cari menu edit..."
                                            class="border-slate-850 w-full rounded-xl border bg-slate-950 py-2 pl-9 pr-4 text-xs text-slate-200 placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="custom-scrollbar mt-3 flex gap-2 overflow-x-auto pb-1"
                                >
                                    <button
                                        @click="editCategory = 'all'"
                                        :class="[
                                            'whitespace-nowrap rounded-xl border px-3 py-2 text-[11px] font-semibold transition',
                                            editCategory === 'all'
                                                ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                                                : 'border-slate-800 bg-slate-950/60 text-slate-400 hover:text-slate-200',
                                        ]"
                                    >
                                        Semua
                                    </button>
                                    <button
                                        v-for="cat in categories"
                                        :key="`edit-${cat.id}`"
                                        @click="editCategory = cat.id"
                                        :class="[
                                            'whitespace-nowrap rounded-xl border px-3 py-2 text-[11px] font-semibold transition',
                                            editCategory === cat.id
                                                ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                                                : 'border-slate-800 bg-slate-950/60 text-slate-400 hover:text-slate-200',
                                        ]"
                                    >
                                        {{ cat.name }}
                                    </button>
                                </div>

                                <div
                                    class="custom-scrollbar mt-4 max-h-56 overflow-y-auto pr-1"
                                >
                                    <div
                                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
                                    >
                                        <button
                                            v-for="product in filteredEditProducts"
                                            :key="`edit-product-${product.id}`"
                                            type="button"
                                            @click="
                                                handleEditProductClick(product)
                                            "
                                            class="rounded-2xl border border-slate-800 bg-slate-950/60 p-3 text-left transition hover:border-orange-500/30 hover:bg-slate-950"
                                        >
                                            <p
                                                class="text-xs font-bold text-white"
                                            >
                                                {{ product.name }}
                                            </p>
                                            <p
                                                class="mt-1 line-clamp-2 text-[10px] text-slate-500"
                                            >
                                                {{
                                                    product.description ||
                                                    'Tidak ada deskripsi.'
                                                }}
                                            </p>
                                            <div
                                                class="mt-3 flex items-center justify-between"
                                            >
                                                <span
                                                    class="text-[11px] font-extrabold text-orange-400"
                                                >
                                                    {{
                                                        formatPrice(
                                                            product.base_price,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
                                                >
                                                    Tambah
                                                </span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="custom-scrollbar min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p
                                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                                        >
                                            Draft Perubahan
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            Ubah jumlah, hapus item, atau revisi
                                            catatan per menu.
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full border border-slate-700 bg-slate-950 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-300"
                                    >
                                        {{ editItemCount }} item
                                    </span>
                                </div>

                                <div
                                    v-for="(item, index) in editingItems"
                                    :key="`editing-${index}-${item.product_id}-${item.variant_id || 'base'}`"
                                    class="space-y-3 rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-bold text-white"
                                            >
                                                {{ item.product_name }}
                                            </p>
                                            <p
                                                v-if="item.variant_name"
                                                class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-orange-300"
                                            >
                                                {{ item.variant_name }}
                                            </p>
                                            <p
                                                class="mt-2 text-[11px] font-extrabold text-slate-300"
                                            >
                                                {{
                                                    formatPrice(
                                                        item.unit_price *
                                                            item.quantity,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <button
                                            @click="removeEditItem(index)"
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-red-900/30 bg-red-950/20 text-red-400 transition hover:text-red-300"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="decreaseEditQty(index)"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-800 bg-slate-900 text-slate-300"
                                        >
                                            <Minus class="h-3 w-3" />
                                        </button>
                                        <span
                                            class="w-7 text-center text-sm font-bold text-white"
                                        >
                                            {{ item.quantity }}
                                        </span>
                                        <button
                                            @click="increaseEditQty(index)"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-800 bg-slate-900 text-slate-300"
                                        >
                                            <Plus class="h-3 w-3" />
                                        </button>
                                    </div>

                                    <input
                                        v-model="item.notes"
                                        type="text"
                                        placeholder="Catatan item, misal saus dipisah..."
                                        class="border-slate-850 w-full rounded-xl border bg-slate-900 px-4 py-2.5 text-xs text-slate-200 placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                    />
                                </div>

                                <div
                                    v-if="editingItems.length === 0"
                                    class="rounded-2xl border border-dashed border-slate-800 py-12 text-center text-xs text-slate-500"
                                >
                                    Draft edit kosong. Tambahkan minimal satu
                                    menu.
                                </div>
                            </div>
                        </div>

                        <div
                            class="custom-scrollbar flex min-h-0 flex-col overflow-y-auto px-6 py-5"
                        >
                            <div class="space-y-4">
                                <div
                                    class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-orange-300"
                                    >
                                        Review Edit
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Setelah disimpan, order akan kembali ke
                                        lane `pending` di kitchen.
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400"
                                    >
                                        Catatan Order
                                    </label>
                                    <textarea
                                        v-model="editOrderNotes"
                                        rows="5"
                                        class="border-slate-850 w-full resize-none rounded-2xl border bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                        placeholder="Update catatan utama order untuk dapur..."
                                    ></textarea>
                                </div>

                                <div
                                    v-if="editOrderNeedsApproval"
                                    class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-300"
                                    >
                                        Approval Supervisor
                                    </p>
                                    <p
                                        class="mt-1 text-xs leading-relaxed text-amber-100/75"
                                    >
                                        Order ini sudah `in_progress`. Input PIN
                                        supervisor atau owner untuk melanjutkan
                                        edit.
                                    </p>
                                    <input
                                        v-model="editApprovalPin"
                                        type="password"
                                        inputmode="numeric"
                                        placeholder="Masukkan PIN approval"
                                        class="mt-3 w-full rounded-xl border border-amber-500/20 bg-slate-950 px-4 py-3 text-xs text-slate-100 placeholder-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                                    />
                                </div>

                                <div
                                    class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs text-slate-400"
                                    >
                                        <span>Total Item</span>
                                        <span>{{ editItemCount }}</span>
                                    </div>
                                    <div
                                        class="mt-2 flex items-center justify-between text-xs text-slate-400"
                                    >
                                        <span>Jenis Menu</span>
                                        <span>{{ editingItems.length }}</span>
                                    </div>
                                    <div
                                        class="mt-3 flex items-center justify-between border-t border-slate-800 pt-3 text-sm font-black text-white"
                                    >
                                        <span>Total Order Baru</span>
                                        <span class="text-orange-400">
                                            {{ formatPrice(editSubtotal) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-3">
                                <button
                                    @click="submitEditOrder"
                                    :disabled="
                                        editingItems.length === 0 ||
                                        isUpdatingOrder
                                    "
                                    class="w-full rounded-2xl bg-gradient-to-r from-sky-500 to-cyan-500 px-5 py-3 text-xs font-bold text-slate-950 transition hover:from-sky-400 hover:to-cyan-400 disabled:pointer-events-none disabled:opacity-50"
                                >
                                    {{
                                        isUpdatingOrder
                                            ? 'Menyimpan Edit...'
                                            : 'Simpan Perubahan Order'
                                    }}
                                </button>
                                <button
                                    @click="closeEditOrder"
                                    type="button"
                                    class="w-full rounded-2xl border border-slate-800 bg-slate-950 px-5 py-3 text-xs font-bold text-slate-300 transition hover:bg-slate-900"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="splitBillModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm"
            >
                <div
                    class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-800/80 px-6 py-5"
                    >
                        <div>
                            <span
                                class="rounded-full border border-fuchsia-500/20 bg-fuchsia-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-fuchsia-300"
                            >
                                Menu #5 • Split Bill
                            </span>
                            <h3 class="mt-3 text-xl font-black text-white">
                                Split {{ selectedManagedOrder?.order_number }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-400">
                                Tentukan item mana yang pindah ke bill kedua.
                                Bill asal tetap harus menyisakan minimal satu
                                item.
                            </p>
                        </div>
                        <button
                            @click="closeSplitBill"
                            class="text-slate-500 transition hover:text-slate-200"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div
                        class="custom-scrollbar flex-1 overflow-y-auto px-6 py-5"
                    >
                        <div class="space-y-3">
                            <div
                                v-for="(item, index) in splitDraftItems"
                                :key="`split-${item.order_item_id}`"
                                class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-white">
                                            {{ item.product_name }}
                                        </p>
                                        <p
                                            v-if="item.variant_name"
                                            class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-orange-300"
                                        >
                                            {{ item.variant_name }}
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] text-slate-500"
                                        >
                                            Total item awal: {{ item.quantity }}
                                        </p>
                                    </div>
                                    <span
                                        class="text-xs font-extrabold text-slate-300"
                                    >
                                        {{ formatPrice(item.total_price) }}
                                    </span>
                                </div>

                                <div
                                    class="mt-4 flex items-center justify-between gap-4"
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                        >
                                            Pindah ke Bill 2
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] text-slate-400"
                                        >
                                            Sisa di bill asal:
                                            {{
                                                item.quantity -
                                                item.split_quantity
                                            }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="
                                                setSplitQuantity(
                                                    index,
                                                    item.split_quantity - 1,
                                                )
                                            "
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900 text-slate-300"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </button>
                                        <span
                                            class="w-8 text-center text-sm font-bold text-white"
                                        >
                                            {{ item.split_quantity }}
                                        </span>
                                        <button
                                            @click="
                                                setSplitQuantity(
                                                    index,
                                                    item.split_quantity + 1,
                                                )
                                            "
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900 text-slate-300"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="splitOrderNeedsApproval"
                            class="mt-5 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-300"
                            >
                                Approval Supervisor
                            </p>
                            <p class="mt-1 text-xs text-amber-100/75">
                                Order sedang dimasak. Split bill butuh PIN
                                supervisor atau owner.
                            </p>
                            <input
                                v-model="splitApprovalPin"
                                type="password"
                                inputmode="numeric"
                                placeholder="Masukkan PIN approval"
                                class="mt-3 w-full rounded-xl border border-amber-500/20 bg-slate-950 px-4 py-3 text-xs text-slate-100 placeholder-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                            />
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-4 border-t border-slate-800/80 px-6 py-4"
                    >
                        <div class="text-xs text-slate-400">
                            {{ splitItemCount }} item akan dipindahkan ke bill
                            kedua.
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                @click="closeSplitBill"
                                type="button"
                                class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs font-bold text-slate-300"
                            >
                                Batal
                            </button>
                            <button
                                @click="submitSplitBill"
                                :disabled="
                                    splitItemCount === 0 || isSplittingBill
                                "
                                class="rounded-2xl bg-gradient-to-r from-fuchsia-500 to-pink-500 px-5 py-3 text-xs font-bold text-white disabled:pointer-events-none disabled:opacity-50"
                            >
                                {{
                                    isSplittingBill
                                        ? 'Memproses Split...'
                                        : 'Buat Split Bill'
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mergeBillModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm"
            >
                <div
                    class="relative w-full max-w-2xl rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl"
                >
                    <div class="border-b border-slate-800/80 px-6 py-5">
                        <span
                            class="rounded-full border border-fuchsia-500/20 bg-fuchsia-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-fuchsia-300"
                        >
                            Menu #5 • Gabung Bill
                        </span>
                        <h3 class="mt-3 text-xl font-black text-white">
                            Konfirmasi Gabung Bill
                        </h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Order terpilih akan digabung menjadi satu bill baru,
                            lalu order asal diarsipkan.
                        </p>
                    </div>

                    <div class="space-y-3 px-6 py-5">
                        <div
                            v-for="order in mergeSelectedOrders"
                            :key="`merge-${order.id}`"
                            class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p class="text-sm font-bold text-white">
                                        {{ order.order_number }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ order.table?.name }} •
                                        {{ getOrderCustomerPrimary(order) }}
                                    </p>
                                </div>
                                <span
                                    class="text-xs font-extrabold text-orange-400"
                                >
                                    {{ formatPrice(order.total_amount) }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="mergeNeedsApproval"
                            class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-300"
                            >
                                Approval Supervisor
                            </p>
                            <p class="mt-1 text-xs text-amber-100/75">
                                Ada order `in_progress` di pilihan ini. Gabung
                                bill butuh PIN supervisor atau owner.
                            </p>
                            <input
                                v-model="mergeApprovalPin"
                                type="password"
                                inputmode="numeric"
                                placeholder="Masukkan PIN approval"
                                class="mt-3 w-full rounded-xl border border-amber-500/20 bg-slate-950 px-4 py-3 text-xs text-slate-100 placeholder-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                            />
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 border-t border-slate-800/80 px-6 py-4"
                    >
                        <button
                            @click="closeMergeBill"
                            type="button"
                            class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs font-bold text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitMergeBills"
                            :disabled="
                                !canMergeSelectedOrders || isMergingBills
                            "
                            class="rounded-2xl bg-gradient-to-r from-fuchsia-500 to-pink-500 px-5 py-3 text-xs font-bold text-white disabled:pointer-events-none disabled:opacity-50"
                        >
                            {{
                                isMergingBills
                                    ? 'Memproses Merge...'
                                    : 'Konfirmasi Gabung Bill'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="kasbonModalOpen && kasbonTargetOrder"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-xl rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-800/80 px-6 py-5"
                    >
                        <div>
                            <span
                                class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-amber-300"
                            >
                                Menu #7 • Kasbon
                            </span>
                            <h3 class="mt-3 text-xl font-black text-white">
                                {{ kasbonTargetOrder.order_number }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-400">
                                Tutup transaksi sebagai piutang pelanggan dan lanjutkan cicilan dari menu transaksi.
                            </p>
                        </div>
                        <button
                            @click="closeKasbonModal"
                            class="text-slate-500 transition hover:text-slate-200"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <div
                            class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                    >
                                        Customer
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-white">
                                        {{
                                            kasbonTargetOrder.customer?.name ||
                                            'Customer tidak ditemukan'
                                        }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{
                                            kasbonTargetOrder.customer?.phone ||
                                            'Tanpa nomor HP'
                                        }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                    >
                                        Sisa Tagihan
                                    </p>
                                    <p class="mt-2 text-lg font-black text-amber-300">
                                        {{
                                            formatPrice(
                                                Number(
                                                    kasbonTargetOrder.total_amount,
                                                ) -
                                                    Number(
                                                        kasbonTargetOrder.paid_amount ||
                                                            0,
                                                    ),
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                                >
                                    Jatuh Tempo
                                </label>
                                <input
                                    v-model="kasbonForm.due_date"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs text-slate-200 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                                />
                                <p class="mt-2 text-[11px] text-slate-500">
                                    Opsional. Jika diisi, tanggal akan dipakai di daftar kasbon dan struk.
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                                >
                                    Catatan Kasbon
                                </label>
                                <textarea
                                    v-model="kasbonForm.notes"
                                    rows="4"
                                    placeholder="Contoh: pelanggan bayar 3 hari lagi"
                                    class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 border-t border-slate-800/80 px-6 py-4"
                    >
                        <button
                            @click="closeKasbonModal"
                            type="button"
                            class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs font-bold text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitKasbon"
                            :disabled="kasbonForm.processing"
                            class="rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-3 text-xs font-bold text-white disabled:pointer-events-none disabled:opacity-50"
                        >
                            {{
                                kasbonForm.processing
                                    ? 'Menyimpan Kasbon...'
                                    : 'Tutup Sebagai Kasbon'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="paymentModalOpen && paymentTargetOrder"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-xl rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-800/80 px-6 py-5"
                    >
                        <div>
                            <span
                                class="rounded-full border border-orange-500/20 bg-orange-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-orange-300"
                            >
                                Menu #6 • Pembayaran
                            </span>
                            <h3 class="mt-3 text-xl font-black text-white">
                                {{ paymentTargetOrder.order_number }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ getPaymentActionHint(paymentTargetOrder) }}
                            </p>
                        </div>
                        <button
                            @click="closePaymentModal"
                            class="text-slate-500 transition hover:text-slate-200"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <div
                            class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                    >
                                        Total Tagihan
                                    </p>
                                    <p
                                        class="mt-2 text-xl font-black text-white"
                                    >
                                        {{
                                            formatPrice(
                                                paymentTargetOrder.total_amount,
                                            )
                                        }}
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider',
                                        getPaymentStatusClass(
                                            paymentTargetOrder,
                                        ),
                                    ]"
                                >
                                    {{
                                        getPaymentStatusLabel(
                                            paymentTargetOrder,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                @click="existingPaymentMethod = 'cash'"
                                :class="[
                                    'rounded-2xl border p-4 text-left transition',
                                    existingPaymentMethod === 'cash'
                                        ? 'border-orange-500/30 bg-orange-500/10 text-white'
                                        : 'border-slate-800 bg-slate-950/70 text-slate-300',
                                ]"
                            >
                                <p class="text-sm font-bold">Cash</p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Input nominal tunai. Sistem akan catat lunas
                                    dan lanjut ke status berikutnya.
                                </p>
                            </button>
                            <button
                                type="button"
                                @click="existingPaymentMethod = 'qris'"
                                :class="[
                                    'rounded-2xl border p-4 text-left transition',
                                    existingPaymentMethod === 'qris'
                                        ? 'border-fuchsia-500/30 bg-fuchsia-500/10 text-white'
                                        : 'border-slate-800 bg-slate-950/70 text-slate-300',
                                ]"
                            >
                                <p class="text-sm font-bold">QRIS Gateway</p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Buat atau buka checkout QRIS. Status akan
                                    berubah otomatis saat webhook pembayaran
                                    masuk.
                                </p>
                            </button>
                        </div>

                        <div
                            v-if="existingPaymentMethod === 'cash'"
                            class="grid gap-3 sm:grid-cols-2"
                        >
                            <div>
                                <label
                                    class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                                >
                                    Nominal Diterima
                                </label>
                                <input
                                    v-model="existingPaymentCashReceived"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    placeholder="Contoh: 150000"
                                    class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                />
                            </div>
                            <div
                                class="rounded-xl border border-slate-800 bg-slate-950 p-4"
                            >
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    Estimasi Kembalian
                                </p>
                                <p
                                    class="mt-2 text-sm font-extrabold text-emerald-300"
                                >
                                    {{ formatPrice(existingPaymentCashChange) }}
                                </p>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Kosongkan jika pembayaran pas sesuai total
                                    tagihan.
                                </p>
                            </div>
                        </div>

                        <div
                            v-else
                            class="rounded-2xl border border-fuchsia-500/15 bg-fuchsia-500/5 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-300"
                            >
                                Catatan QRIS
                            </p>
                            <p
                                class="mt-1 text-xs leading-relaxed text-fuchsia-100/80"
                            >
                                {{
                                    paymentTargetOrder.status ===
                                    'payment_pending'
                                        ? 'Setelah QRIS lunas, order otomatis pindah ke lane pending agar bisa diproses kitchen.'
                                        : 'Setelah QRIS lunas, order otomatis ditutup sebagai completed.'
                                }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                        >
                            <label
                                class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                            >
                                Voucher / Promo Code
                            </label>
                            <input
                                v-model="existingPaymentPromoCode"
                                type="text"
                                placeholder="Kosongkan jika tidak pakai voucher"
                                class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs uppercase text-slate-200 placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                            <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                                Total tagihan akan divalidasi ulang dengan promo otomatis, metode bayar, tier member, dan voucher sebelum settlement diproses.
                            </p>
                            <div class="mt-4">
                                <label
                                    class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                                >
                                    PIN Owner (Opsional)
                                </label>
                                <input
                                    v-model="existingPaymentApprovalPin"
                                    type="password"
                                    inputmode="numeric"
                                    placeholder="Isi jika diskon manual melewati threshold"
                                    class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                />
                                <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                                    Approval owner hanya dipakai bila promo manual menghasilkan diskon di atas batas outlet.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 border-t border-slate-800/80 px-6 py-4"
                    >
                        <button
                            @click="closePaymentModal"
                            type="button"
                            class="rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-xs font-bold text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitExistingPayment"
                            :disabled="isProcessingPayment"
                            class="rounded-2xl bg-gradient-to-r from-orange-500 to-red-500 px-5 py-3 text-xs font-bold text-white disabled:pointer-events-none disabled:opacity-50"
                        >
                            {{
                                isProcessingPayment
                                    ? 'Memproses Pembayaran...'
                                    : existingPaymentMethod === 'cash'
                                      ? 'Simpan Pembayaran Cash'
                                      : 'Buat Checkout QRIS'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="paymentCheckoutModalOpen && activePaymentCheckout"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-800/80 px-6 py-5"
                    >
                        <div>
                            <span
                                class="rounded-full border border-fuchsia-500/20 bg-fuchsia-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-fuchsia-300"
                            >
                                Checkout QRIS Aktif
                            </span>
                            <h3 class="mt-3 text-xl font-black text-white">
                                {{ activePaymentCheckout.order_number }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-400">
                                {{
                                    activePaymentCheckout.context ===
                                    'before_kitchen'
                                        ? 'Selesaikan pembayaran dulu agar order masuk ke dapur.'
                                        : 'Selesaikan pembayaran agar transaksi otomatis ditutup.'
                                }}
                            </p>
                        </div>
                        <button
                            @click="paymentCheckoutModalOpen = false"
                            class="text-slate-500 transition hover:text-slate-200"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <div
                            class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Total Checkout
                            </p>
                            <p class="mt-2 text-2xl font-black text-white">
                                {{ formatPrice(activePaymentCheckout.amount) }}
                            </p>
                            <p class="mt-2 text-[11px] text-slate-400">
                                Checkout dibuka di halaman gateway Pakasir agar
                                customer bisa scan dan bayar dari perangkat
                                mereka.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="openPaymentCheckout"
                            class="w-full rounded-2xl bg-gradient-to-r from-fuchsia-500 to-pink-500 px-5 py-3 text-sm font-bold text-white"
                        >
                            Buka Checkout QRIS
                        </button>

                        <p class="text-center text-[11px] text-slate-500">
                            Jika belum lunas, kasir bisa buka ulang checkout ini
                            kapan saja dari detail order aktif.
                        </p>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- MODAL WINDOW: Selection of Product Variants & Note -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="variantModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/80 p-4 backdrop-blur-sm"
            >
                <div
                    class="relative w-full max-w-md space-y-6 rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl"
                >
                    <button
                        @click="variantModalOpen = false"
                        class="text-slate-550 hover:text-slate-350 absolute right-4 top-4"
                    >
                        <X class="h-5 w-5" />
                    </button>

                    <div>
                        <span
                            class="mb-2 inline-flex items-center gap-1.5 rounded border border-orange-500/20 bg-orange-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-orange-400"
                        >
                            Pilih Varian Menu
                        </span>
                        <h4 class="text-lg font-extrabold text-white">
                            {{ selectedProduct?.name }}
                        </h4>
                        <p class="text-slate-450 mt-1 text-xs">
                            {{ selectedProduct?.description }}
                        </p>
                    </div>

                    <!-- Variant Options Radio -->
                    <div class="space-y-2.5">
                        <label
                            class="block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                            >Pilihan Varian</label
                        >
                        <div class="grid grid-cols-1 gap-2">
                            <div
                                v-for="variant in selectedProduct?.variants"
                                :key="variant.id"
                                @click="selectedVariant = variant"
                                :class="[
                                    'flex cursor-pointer select-none items-center justify-between rounded-xl border p-3.5 transition',
                                    selectedVariant?.id === variant.id
                                        ? 'border-orange-500/40 bg-orange-500/5 text-orange-400'
                                        : 'border-slate-850 bg-slate-950/60 text-slate-300 hover:bg-slate-900',
                                ]"
                            >
                                <div
                                    class="flex items-center gap-2 text-xs font-bold"
                                >
                                    <span
                                        :class="[
                                            'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border',
                                            selectedVariant?.id === variant.id
                                                ? 'border-orange-500 text-orange-400'
                                                : 'border-slate-700',
                                        ]"
                                    >
                                        <span
                                            v-if="
                                                selectedVariant?.id ===
                                                variant.id
                                            "
                                            class="h-1.5 w-1.5 rounded-full bg-orange-500"
                                        ></span>
                                    </span>
                                    <span>{{ variant.name }}</span>
                                </div>
                                <span class="text-xs font-black">
                                    +
                                    {{ formatPrice(variant.additional_price) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Control -->
                    <div
                        class="flex items-center justify-between gap-4 border-t border-slate-800/80 pt-4"
                    >
                        <span
                            class="block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                            >Jumlah Item</span
                        >
                        <div class="flex items-center gap-3">
                            <button
                                @click="
                                    itemQuantity =
                                        itemQuantity > 1 ? itemQuantity - 1 : 1
                                "
                                class="border-slate-850 flex h-8 w-8 items-center justify-center rounded-lg border bg-slate-950 text-slate-400 hover:text-white"
                            >
                                <Minus class="h-4 w-4" />
                            </button>
                            <span
                                class="w-6 text-center text-base font-extrabold text-slate-100"
                                >{{ itemQuantity }}</span
                            >
                            <button
                                @click="itemQuantity += 1"
                                class="border-slate-850 flex h-8 w-8 items-center justify-center rounded-lg border bg-slate-950 text-slate-400 hover:text-white"
                            >
                                <Plus class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Notes field for kitchen -->
                    <div>
                        <label
                            for="item-notes"
                            class="mb-1.5 block text-[9px] font-bold uppercase tracking-wider text-slate-400"
                            >Catatan Khusus Menu</label
                        >
                        <input
                            id="item-notes"
                            type="text"
                            v-model="itemNotes"
                            placeholder="Contoh: level 3 pedas, extra mayones, dll..."
                            class="border-slate-850 w-full rounded-xl border bg-slate-950 px-4 py-3 text-xs text-slate-200 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        />
                    </div>

                    <!-- Confirm button -->
                    <button
                        @click="confirmVariantAdd"
                        class="to-red-650 hover:to-red-750 w-full rounded-xl bg-gradient-to-r from-orange-500 px-5 py-3 text-xs font-bold text-white shadow-md transition duration-150 hover:from-orange-600 active:scale-[0.99]"
                    >
                        Konfirmasi & Tambah ke Keranjang
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Custom Toast Notification -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="localToast"
                class="animate-pulse-subtle fixed bottom-6 right-6 z-50 flex w-full max-w-sm items-center gap-3 rounded-xl border border-orange-500/20 bg-slate-900 p-4 shadow-2xl backdrop-blur-xl"
            >
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10 text-orange-400"
                >
                    ✨
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold leading-normal text-white">
                        {{ localToast }}
                    </p>
                </div>
                <button
                    @click="localToast = ''"
                    class="hover:text-slate-350 shrink-0 rounded-lg p-0.5 text-slate-500 hover:bg-slate-800 focus:outline-none"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
