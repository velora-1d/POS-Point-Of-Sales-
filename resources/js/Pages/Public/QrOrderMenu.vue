<script setup lang="ts">
import { requestNotificationPermission } from '@/firebase';
import { Head, router } from '@inertiajs/vue3';
import {
    Minus,
    Moon,
    PackageCheck,
    Plus,
    QrCode,
    Search,
    ShoppingCart,
    Store,
    Sun,
    Trash2,
    UserRound,
    Users,
    UtensilsCrossed,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    table: any;
    outlet: any;
    categories: any[];
    tableFull?: boolean;
}>();

const customerName = ref('');
const customerPhone = ref('');
const customerEmail = ref('');
const promoCode = ref('');
const orderNotes = ref('');
const guestsCount = ref(1);
const activeCategory = ref<string>('all');
const searchQuery = ref('');
const cart = ref<any[]>([]);
const variantModalOpen = ref(false);
const selectedProduct = ref<any>(null);
const selectedVariant = ref<any>(null);
const itemQuantity = ref(1);
const itemNotes = ref('');
const isSubmitting = ref(false);
const checkoutError = ref('');

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    if (
        savedTheme === 'dark' ||
        (!savedTheme &&
            window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
});

const formatPrice = (value: any) => {
    const num = parseFloat(value);
    if (Number.isNaN(num)) return 'Rp 0';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
};

const allProducts = computed(() => {
    let list: any[] = [];

    props.categories.forEach((category) => {
        category.products.forEach((product: any) => {
            list.push({
                ...product,
                category_name: category.name,
            });
        });
    });

    return list;
});

const filteredProducts = computed(() => {
    let list = allProducts.value;

    if (activeCategory.value !== 'all') {
        list = list.filter(
            (product) => product.category_id === activeCategory.value,
        );
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        list = list.filter(
            (product) =>
                product.name.toLowerCase().includes(query) ||
                product.description?.toLowerCase().includes(query),
        );
    }

    return list;
});

const cartSubtotal = computed(() => {
    return cart.value.reduce(
        (sum, item) => sum + item.quantity * item.unit_price,
        0,
    );
});

const cartTax = computed(() => {
    const taxPercentage = Number(props.outlet?.settings?.tax_percentage || 0);
    if (taxPercentage <= 0) return 0;

    const isInclusive = !!props.outlet?.settings?.tax_is_inclusive;
    const taxableAmount = cartSubtotal.value; // QR menu doesn't have real-time discount yet

    if (isInclusive) {
        return taxableAmount - taxableAmount / (1 + taxPercentage / 100);
    } else {
        return taxableAmount * (taxPercentage / 100);
    }
});

const cartTotal = computed(() => {
    const isInclusive = !!props.outlet?.settings?.tax_is_inclusive;
    const taxableAmount = cartSubtotal.value;

    if (isInclusive) {
        return taxableAmount;
    } else {
        return taxableAmount + cartTax.value;
    }
});

const cartItemCount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

const addItemToCart = (
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
    const variantId = variant?.id ?? null;
    const existingIndex = cart.value.findIndex(
        (item) =>
            item.product_id === product.id && item.variant_id === variantId,
    );

    if (existingIndex > -1) {
        cart.value[existingIndex].quantity += quantity;
        if (notes) {
            cart.value[existingIndex].notes = notes;
        }

        return;
    }

    cart.value.push({
        product_id: product.id,
        product_name: product.name,
        variant_id: variantId,
        variant_name: variant?.name ?? null,
        quantity,
        unit_price: unitPrice,
        notes,
    });
};

const handleProductClick = (product: any) => {
    if (product.variants?.length) {
        selectedProduct.value = product;
        selectedVariant.value = product.variants[0];
        itemQuantity.value = 1;
        itemNotes.value = '';
        variantModalOpen.value = true;
        return;
    }

    addItemToCart(product);
};

const confirmVariantAdd = () => {
    if (!selectedProduct.value) return;

    addItemToCart(
        selectedProduct.value,
        itemQuantity.value,
        selectedVariant.value,
        itemNotes.value,
    );

    variantModalOpen.value = false;
    selectedProduct.value = null;
    selectedVariant.value = null;
    itemQuantity.value = 1;
    itemNotes.value = '';
};

const increaseCartQty = (index: number) => {
    cart.value[index].quantity += 1;
};

const decreaseCartQty = (index: number) => {
    if (cart.value[index].quantity > 1) {
        cart.value[index].quantity -= 1;
        return;
    }

    cart.value.splice(index, 1);
};

const removeCartItem = (index: number) => {
    cart.value.splice(index, 1);
};

const getProductImage = (product: any) => {
    if (product.image_url) return product.image_url;

    const name = product.name.toLowerCase();
    if (name.includes('gyoza')) return '/images/gyoza.png';
    if (name.includes('ocha') || name.includes('tea'))
        return '/images/iced_ocha.png';
    return '/images/salmon_mentai.png';
};

const remainingCapacity = computed(
    () => props.table.remaining_capacity ?? null,
);

const capacityPercent = computed(() => {
    if (!props.table.capacity || props.table.capacity <= 0) return 0;
    const used = props.table.current_guests ?? 0;
    return Math.min(100, Math.round((used / props.table.capacity) * 100));
});

const maxGuests = computed(() => {
    if (remainingCapacity.value === null) return 100;
    return Math.max(1, remainingCapacity.value);
});

const submitCheckout = async () => {
    if (
        !customerName.value ||
        !customerPhone.value ||
        guestsCount.value < 1 ||
        cart.value.length === 0
    ) {
        return;
    }

    if (
        remainingCapacity.value !== null &&
        guestsCount.value > remainingCapacity.value
    ) {
        checkoutError.value = `Sisa kapasitas meja hanya ${remainingCapacity.value} orang lagi.`;
        return;
    }

    isSubmitting.value = true;
    checkoutError.value = '';

    // Request notification permission before checkout
    let fcmToken = null;
    try {
        fcmToken = await requestNotificationPermission();
    } catch (e) {
        console.warn('Failed to get FCM token for customer:', e);
    }

    router.post(
        route('self-service.checkout', props.table.qr_session_token),
        {
            customer_name: customerName.value,
            customer_phone: customerPhone.value,
            customer_email: customerEmail.value || null,
            guests_count: guestsCount.value,
            promo_code: promoCode.value || null,
            notes: orderNotes.value || null,
            fcm_token: fcmToken,
            items: cart.value.map((item) => ({
                product_id: item.product_id,
                variant_id: item.variant_id,
                quantity: item.quantity,
                unit_price: item.unit_price,
                notes: item.notes || null,
            })),
        },
        {
            onError: (errors) => {
                checkoutError.value =
                    errors.guests_count ||
                    errors.promo_code ||
                    errors.customer_name ||
                    errors.customer_phone ||
                    errors.error ||
                    'Checkout gagal diproses. Periksa lagi data dan voucher Anda.';
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};
</script>

<<template>
    <Head :title="`QR Order ${table.name} - ${outlet.name}`" />

    <div
        class="h-screen overflow-y-auto bg-[radial-gradient(circle_at_top,_rgba(249,115,22,0.06),_transparent_45%),linear-gradient(180deg,#f8fafc_0%,#f1f5f9_100%)] text-slate-800 transition-colors duration-200 dark:bg-[radial-gradient(circle_at_top,_rgba(249,115,22,0.18),_transparent_35%),linear-gradient(180deg,#020617_0%,#0f172a_100%)] dark:text-slate-100"
    >
        <!-- MEJA PENUH: tampilkan halaman blokir -->
        <div
            v-if="tableFull"
            class="flex min-h-screen flex-col items-center justify-center px-6 text-center"
        >
            <div
                class="w-full max-w-md rounded-[32px] border border-rose-200 bg-white p-10 shadow-xl backdrop-blur dark:border-rose-500/20 dark:bg-rose-950/30"
            >
                <div
                    class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    <Users class="h-10 w-10" />
                </div>
                <h1 class="mt-6 text-2xl font-black text-slate-900 dark:text-white">
                    Meja Sudah Penuh
                </h1>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                    Kapasitas
                    <strong class="text-slate-900 dark:text-white">{{ table.name }}</strong> sudah
                    penuh ({{ table.capacity }} Orang). Silakan hubungi kasir atau
                    tunggu hingga ada tempat kosong.
                </p>
                <div
                    class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 dark:border-white/10 dark:bg-white/5"
                >
                    <p
                        class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400"
                    >
                        Kapasitas Meja
                    </p>
                    <p class="mt-2 text-3xl font-black text-rose-600 dark:text-rose-300">
                        {{ table.current_guests ?? 0 }} /
                        {{ table.capacity }} Orang
                    </p>
                </div>
            </div>
        </div>

        <!-- KONTEN NORMAL -->
        <div
            v-else
            class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-5 sm:px-6 lg:px-8"
        >
            <section
                class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.03)] backdrop-blur dark:border-white/10 dark:bg-slate-950/60 dark:shadow-[0_24px_80px_rgba(15,23,42,0.45)]"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-2xl">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.24em] text-orange-700 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-300"
                        >
                            <QrCode class="h-3.5 w-3.5" />
                            QR Table Self-Order
                        </div>
                        <h1
                            class="mt-4 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                        >
                            Pesan dari {{ table.name }}, bayar dulu, lalu tunggu
                            diproses.
                        </h1>
                        <p
                            class="mt-3 max-w-xl text-sm leading-6 text-slate-600 dark:text-slate-300"
                        >
                            Flow ini khusus customer meja. Pilih menu, isi data
                            singkat, lalu lanjutkan pembayaran QRIS agar pesanan
                            masuk ke sistem.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[360px]">
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-550 dark:text-slate-400"
                            >
                                Outlet
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <Store class="h-4 w-4 text-orange-600 dark:text-orange-300" />
                                <p class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ outlet.name }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-550 dark:text-slate-400"
                            >
                                Meja Aktif
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <UtensilsCrossed
                                    class="h-4 w-4 text-emerald-600 dark:text-emerald-300"
                                />
                                <p class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ table.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                <div class="space-y-6">
                    <section
                        class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] backdrop-blur dark:border-white/10 dark:bg-slate-950/55"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-600 dark:text-orange-300"
                                >
                                    Langkah 1
                                </p>
                                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">
                                    Data pelanggan
                                </h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    Nama dan nomor HP dipakai untuk identifikasi
                                    member dan status order.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-orange-200 bg-orange-50 p-3 text-orange-600 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-200"
                            >
                                <UserRound class="h-5 w-5" />
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <!-- Info Kapasitas Meja -->
                            <div
                                v-if="table.capacity"
                                class="flex items-center justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 md:col-span-2 dark:border-emerald-500/15 dark:bg-emerald-500/8"
                            >
                                <div class="flex items-center gap-2">
                                    <Users
                                        class="h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-300"
                                    />
                                    <span
                                        class="text-xs font-bold text-emerald-800 dark:text-emerald-200"
                                    >
                                        Kapasitas {{ table.name }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-2 w-28 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"
                                    >
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="
                                                capacityPercent >= 90
                                                    ? 'bg-rose-500 dark:bg-rose-400'
                                                    : capacityPercent >= 60
                                                      ? 'bg-amber-500 dark:bg-amber-400'
                                                      : 'bg-emerald-500 dark:bg-emerald-400'
                                            "
                                            :style="{
                                                width: capacityPercent + '%',
                                            }"
                                        />
                                    </div>
                                    <span
                                        class="whitespace-nowrap text-xs font-black text-slate-900 dark:text-white"
                                    >
                                        {{ table.current_guests ?? 0 }} /
                                        {{ table.capacity }} Orang
                                    </span>
                                </div>
                            </div>

                            <input
                                v-model="customerName"
                                type="text"
                                placeholder="Nama pelanggan"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-orange-400"
                            />
                            <input
                                v-model="customerPhone"
                                type="text"
                                placeholder="Nomor HP"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-orange-400"
                            />
                            <input
                                v-model="customerEmail"
                                type="email"
                                placeholder="Email (opsional)"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-orange-400 md:col-span-2"
                            />

                            <!-- Input Jumlah Orang - WAJIB diisi -->
                            <div class="md:col-span-2">
                                <label
                                    class="mb-2 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-550 dark:text-slate-400"
                                >
                                    Berapa orang dalam grup Anda?
                                    <span class="text-rose-500 dark:text-rose-400">*</span>
                                    <span
                                        v-if="remainingCapacity !== null"
                                        class="ml-2 normal-case tracking-normal text-emerald-600 dark:text-emerald-400"
                                    >
                                        (sisa: {{ remainingCapacity }} tempat)
                                    </span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        @click="
                                            guestsCount = Math.max(
                                                1,
                                                guestsCount - 1,
                                            )
                                        "
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:border-orange-300 hover:text-orange-600 dark:border-white/10 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-orange-500/30 dark:hover:text-orange-300"
                                    >
                                        <Minus class="h-4 w-4" />
                                    </button>
                                    <input
                                        v-model.number="guestsCount"
                                        type="number"
                                        :min="1"
                                        :max="maxGuests"
                                        class="w-20 rounded-2xl border-2 border-slate-200 bg-white px-3 py-3 text-center text-sm font-black text-slate-900 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-slate-900/80 dark:text-white dark:focus:border-orange-400"
                                    />
                                    <button
                                        type="button"
                                        @click="
                                            guestsCount = Math.min(
                                                maxGuests,
                                                guestsCount + 1,
                                            )
                                        "
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:border-orange-300 hover:text-orange-600 dark:border-white/10 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-orange-500/30 dark:hover:text-orange-300"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </button>
                                    <span class="text-sm text-slate-600 dark:text-slate-400"
                                        >orang</span
                                    >
                                </div>
                            </div>

                            <input
                                v-model="promoCode"
                                type="text"
                                placeholder="Voucher / promo code (opsional)"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm uppercase text-slate-900 placeholder:text-slate-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-orange-400 md:col-span-2"
                            />
                        </div>
                    </section>

                    <section
                        class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] backdrop-blur dark:border-white/10 dark:bg-slate-950/55"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-600 dark:text-orange-300"
                                >
                                    Langkah 2
                                </p>
                                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">
                                    Pilih menu
                                </h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    Semua item di halaman ini langsung mengambil
                                    data produk aktif dari database outlet.
                                </p>
                            </div>

                            <div class="relative w-full max-w-sm">
                                <Search
                                    class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                                />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari menu favorit..."
                                    class="w-full rounded-2xl border-2 border-slate-200 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-orange-400"
                                />
                            </div>
                        </div>

                        <div
                            class="custom-scrollbar mt-5 flex gap-2 overflow-x-auto pb-1"
                        >
                            <button
                                type="button"
                                @click="activeCategory = 'all'"
                                :class="[
                                    'whitespace-nowrap rounded-full border px-4 py-2 text-xs font-bold transition',
                                    activeCategory === 'all'
                                        ? 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-200'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-white/10 dark:bg-slate-900/70 dark:text-slate-400',
                                ]"
                            >
                                Semua Menu
                            </button>
                            <button
                                v-for="category in categories"
                                :key="category.id"
                                type="button"
                                @click="activeCategory = category.id"
                                :class="[
                                    'whitespace-nowrap rounded-full border px-4 py-2 text-xs font-bold transition',
                                    activeCategory === category.id
                                        ? 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-200'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-white/10 dark:bg-slate-900/70 dark:text-slate-400',
                                ]"
                            >
                                {{ category.name }}
                            </button>
                        </div>

                        <div
                            v-if="filteredProducts.length"
                            class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                        >
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                type="button"
                                @click="handleProductClick(product)"
                                class="group overflow-hidden rounded-[24px] border border-slate-200 bg-white text-left transition hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-md dark:border-white/10 dark:bg-slate-900/70 dark:hover:border-orange-500/30"
                            >
                                <div
                                    class="relative aspect-square w-full overflow-hidden border-b border-slate-200 bg-slate-50 dark:border-b-white/10 dark:bg-slate-950"
                                >
                                    <img
                                        :src="getProductImage(product)"
                                        :alt="product.name"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    />
                                    <span
                                        class="absolute right-3 top-3 rounded-full border border-slate-200 bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-200"
                                    >
                                        {{ product.category_name }}
                                    </span>
                                </div>
                                <div class="space-y-3 p-4">
                                    <div>
                                        <h3
                                            class="text-sm font-black text-slate-900 dark:text-white"
                                        >
                                            {{ product.name }}
                                        </h3>
                                        <p
                                            class="mt-1 line-clamp-2 text-[12px] leading-5 text-slate-600 dark:text-slate-400"
                                        >
                                            {{
                                                product.description ||
                                                'Tidak ada deskripsi tambahan.'
                                            }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-white/10"
                                    >
                                        <span
                                            class="text-sm font-black text-orange-600 dark:text-orange-300"
                                        >
                                            {{
                                                formatPrice(product.base_price)
                                            }}
                                        </span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-orange-200 bg-orange-50 text-orange-600 group-hover:bg-orange-100 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-200"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div
                            v-else
                            class="mt-5 rounded-[24px] border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center dark:border-white/10 dark:bg-slate-900/40"
                        >
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                Menu tidak ditemukan
                            </p>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-500">
                                Coba ganti kategori atau kata pencarian.
                            </p>
                        </div>
                    </section>
                </div>

                <aside class="xl:sticky xl:top-5 xl:h-fit">
                    <section
                        class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] backdrop-blur dark:border-white/10 dark:bg-slate-950/70 dark:shadow-[0_24px_80px_rgba(15,23,42,0.4)]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-600 dark:text-orange-300"
                                >
                                    Langkah 3
                                </p>
                                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">
                                    Ringkasan & bayar
                                </h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    Flow QR meja saat ini wajib bayar dulu
                                    dengan QRIS.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-fuchsia-200 bg-fuchsia-50 p-3 text-fuchsia-600 dark:border-fuchsia-500/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-200"
                            >
                                <PackageCheck class="h-5 w-5" />
                            </div>
                        </div>

                        <div
                            class="mt-5 grid gap-3 rounded-[24px] border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 xl:grid-cols-1 dark:border-white/10 dark:bg-white/5"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-550 dark:text-slate-400"
                                >
                                    Total Item
                                </p>
                                <p class="mt-2 text-lg font-black text-slate-900 dark:text-white">
                                    {{ cartItemCount }} item
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-550 dark:text-slate-400"
                                >
                                    Metode Bayar
                                </p>
                                <div
                                    class="mt-2 inline-flex items-center gap-2 rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-1 text-xs font-bold text-fuchsia-700 dark:border-fuchsia-500/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-200"
                                >
                                    <QrCode class="h-3.5 w-3.5" />
                                    QRIS Gateway
                                </div>
                            </div>
                        </div>

                        <div
                            class="custom-scrollbar mt-5 max-h-[320px] space-y-3 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="(item, index) in cart"
                                :key="`${item.product_id}-${item.variant_id || 'base'}-${index}`"
                                class="rounded-[22px] border border-slate-200 bg-slate-50/50 p-4 dark:border-white/10 dark:bg-slate-900/80"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-bold text-slate-900 dark:text-white"
                                        >
                                            {{ item.product_name }}
                                        </p>
                                        <p
                                            v-if="item.variant_name"
                                            class="mt-1 text-[11px] font-semibold uppercase tracking-wider text-orange-600 dark:text-orange-300"
                                        >
                                            {{ item.variant_name }}
                                        </p>
                                        <p
                                            v-if="item.notes"
                                            class="mt-1 text-[11px] italic text-slate-500 dark:text-slate-400"
                                        >
                                            "{{ item.notes }}"
                                        </p>
                                        <p
                                            class="mt-3 text-sm font-black text-slate-800 dark:text-slate-200"
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
                                        type="button"
                                        @click="removeCartItem(index)"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300 dark:hover:bg-red-500/15"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="mt-4 flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="decreaseCartQty(index)"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300"
                                    >
                                        <Minus class="h-4 w-4" />
                                    </button>
                                    <span
                                        class="w-8 text-center text-sm font-bold text-slate-900 dark:text-white"
                                    >
                                        {{ item.quantity }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="increaseCartQty(index)"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="cart.length === 0"
                                class="rounded-[22px] border border-dashed border-slate-200 bg-slate-50 px-5 py-12 text-center dark:border-white/10 dark:bg-slate-900/50"
                            >
                                <ShoppingCart
                                    class="mx-auto h-8 w-8 text-slate-400 dark:text-slate-600"
                                />
                                <p
                                    class="mt-3 text-sm font-bold text-slate-600 dark:text-slate-300"
                                >
                                    Keranjang masih kosong
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-500">
                                    Tambahkan menu dulu sebelum lanjut checkout.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div
                                v-if="checkoutError"
                                class="rounded-[22px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200"
                            >
                                {{ checkoutError }}
                            </div>
                            <textarea
                                v-model="orderNotes"
                                rows="3"
                                placeholder="Catatan umum order, misal alat makan dipisah..."
                                class="w-full resize-none rounded-[22px] border-2 border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-orange-400"
                            ></textarea>

                            <div
                                class="rounded-[22px] border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-slate-900/70"
                            >
                                <div
                                    class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400"
                                >
                                    <span>Subtotal</span>
                                    <span>{{ formatPrice(cartSubtotal) }}</span>
                                </div>
                                <div
                                    v-if="cartTax > 0"
                                    class="mt-3 flex items-center justify-between text-sm text-slate-600 dark:text-slate-400"
                                >
                                    <span>
                                        Pajak ({{
                                            outlet?.settings?.tax_percentage ||
                                            0
                                        }}%):
                                        <span
                                            v-if="
                                                outlet?.settings
                                                    ?.tax_is_inclusive
                                            "
                                            class="ml-1 text-[10px] italic opacity-70"
                                            >(Inklusif)</span
                                        >
                                    </span>
                                    <span>{{ formatPrice(cartTax) }}</span>
                                </div>
                                <div
                                    class="mt-3 flex items-center justify-between text-sm text-slate-500"
                                >
                                    <span>Estimasi diskon</span>
                                    <span>Dihitung saat checkout</span>
                                </div>
                                <div
                                    class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3 text-base font-black text-slate-900 dark:border-white/10 dark:text-white"
                                >
                                    <span>Total Pembayaran</span>
                                    <span class="text-orange-600 dark:text-orange-300">{{
                                        formatPrice(cartTotal)
                                    }}</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="submitCheckout"
                                :disabled="
                                    !customerName ||
                                    !customerPhone ||
                                    guestsCount < 1 ||
                                    cart.length === 0 ||
                                    isSubmitting
                                "
                                class="flex w-full items-center justify-center gap-2 rounded-[22px] bg-orange-600 hover:bg-orange-500 px-5 py-4 text-sm font-black text-white transition disabled:pointer-events-none disabled:opacity-50 dark:bg-orange-500 dark:hover:bg-orange-400 dark:text-stone-950"
                            >
                                <QrCode class="h-4 w-4" />
                                <span>
                                    {{
                                        isSubmitting
                                            ? 'Menyiapkan Checkout...'
                                            : 'Pesan & Bayar dengan QRIS'
                                    }}
                                </span>
                            </button>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
        <!-- /KONTEN NORMAL -->

        <!-- Modal Varian -->
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm dark:bg-slate-950/85"
            >
                <div
                    class="w-full max-w-md rounded-[28px] border border-slate-200 bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-slate-950"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-600 dark:text-orange-300"
                            >
                                Pilih varian
                            </p>
                            <h3 class="mt-2 text-xl font-black text-slate-900 dark:text-white">
                                {{ selectedProduct?.name }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="variantModalOpen = false"
                            class="rounded-full border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 dark:border-white/10 dark:text-slate-400 dark:hover:bg-slate-900"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="mt-5 space-y-2">
                        <button
                            v-for="variant in selectedProduct?.variants"
                            :key="variant.id"
                            type="button"
                            @click="selectedVariant = variant"
                            :class="[
                                'flex w-full items-center justify-between rounded-2xl border p-3 text-left transition',
                                selectedVariant?.id === variant.id
                                    ? 'border-orange-300 bg-orange-50 text-orange-900 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-100'
                                    : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 dark:border-white/10 dark:bg-slate-900 dark:text-slate-300',
                            ]"
                        >
                            <span class="text-sm font-bold">
                                {{ variant.name }}
                            </span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">
                                +{{ formatPrice(variant.additional_price) }}
                            </span>
                        </button>
                    </div>

                    <div
                        class="mt-5 flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-slate-900"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-550 dark:text-slate-400"
                            >
                                Jumlah
                            </p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Atur kuantitas sebelum ditambahkan.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="
                                    itemQuantity =
                                        itemQuantity > 1 ? itemQuantity - 1 : 1
                                "
                                class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300"
                            >
                                <Minus class="h-4 w-4" />
                            </button>
                            <span
                                class="w-8 text-center text-sm font-bold text-slate-900 dark:text-white"
                            >
                                {{ itemQuantity }}
                            </span>
                            <button
                                type="button"
                                @click="itemQuantity += 1"
                                class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-white/10 dark:bg-slate-950 dark:text-slate-300"
                            >
                                <Plus class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <textarea
                        v-model="itemNotes"
                        rows="3"
                        placeholder="Catatan item, misal tanpa saus atau level pedas..."
                        class="mt-5 w-full resize-none rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-orange-400"
                    ></textarea>

                    <button
                        type="button"
                        @click="confirmVariantAdd"
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-[22px] bg-orange-600 hover:bg-orange-500 px-5 py-3.5 text-sm font-black text-white transition dark:bg-orange-500 dark:hover:bg-orange-400 dark:text-stone-950"
                    >
                        <PackageCheck class="h-4 w-4" />
                        Tambah ke keranjang
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Floating Theme Toggle -->
        <button
            type="button"
            @click="toggleTheme"
            class="fixed bottom-6 right-6 z-50 flex h-12 w-12 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg transition hover:bg-slate-50 dark:border-white/10 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
            title="Toggle Dark/Light Mode"
        >
            <Sun v-if="isDark" class="h-5 w-5" />
            <Moon v-else class="h-5 w-5" />
        </button>
    </div>
</template>
