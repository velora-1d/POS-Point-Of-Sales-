<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Minus,
    PackageCheck,
    Plus,
    QrCode,
    Search,
    ShoppingCart,
    Store,
    Trash2,
    UserRound,
    UtensilsCrossed,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    table: any;
    outlet: any;
    categories: any[];
}>();

const customerName = ref('');
const customerPhone = ref('');
const customerEmail = ref('');
const promoCode = ref('');
const orderNotes = ref('');
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

const submitCheckout = () => {
    if (
        !customerName.value ||
        !customerPhone.value ||
        cart.value.length === 0
    ) {
        return;
    }

    isSubmitting.value = true;
    checkoutError.value = '';

    router.post(
        route('self-service.checkout', props.table.qr_session_token),
        {
            customer_name: customerName.value,
            customer_phone: customerPhone.value,
            customer_email: customerEmail.value || null,
            promo_code: promoCode.value || null,
            notes: orderNotes.value || null,
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

<template>
    <Head :title="`QR Order ${table.name} - ${outlet.name}`" />

    <div
        class="h-screen overflow-y-auto bg-[radial-gradient(circle_at_top,_rgba(249,115,22,0.18),_transparent_35%),linear-gradient(180deg,#020617_0%,#0f172a_100%)] text-slate-100"
    >
        <div
            class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-5 sm:px-6 lg:px-8"
        >
            <section
                class="rounded-[28px] border border-white/10 bg-slate-950/60 p-5 shadow-[0_24px_80px_rgba(15,23,42,0.45)] backdrop-blur"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-2xl">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.24em] text-orange-300"
                        >
                            <QrCode class="h-3.5 w-3.5" />
                            QR Table Self-Order
                        </div>
                        <h1
                            class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl"
                        >
                            Pesan dari {{ table.name }}, bayar dulu, lalu tunggu
                            diproses.
                        </h1>
                        <p
                            class="mt-3 max-w-xl text-sm leading-6 text-slate-300"
                        >
                            Flow ini khusus customer meja. Pilih menu, isi data
                            singkat, lalu lanjutkan pembayaran QRIS agar pesanan
                            masuk ke sistem.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[360px]">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400"
                            >
                                Outlet
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <Store class="h-4 w-4 text-orange-300" />
                                <p class="text-sm font-bold text-white">
                                    {{ outlet.name }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/5 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400"
                            >
                                Meja Aktif
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <UtensilsCrossed
                                    class="h-4 w-4 text-emerald-300"
                                />
                                <p class="text-sm font-bold text-white">
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
                        class="rounded-[28px] border border-white/10 bg-slate-950/55 p-5 backdrop-blur"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                                >
                                    Langkah 1
                                </p>
                                <h2 class="mt-2 text-xl font-black text-white">
                                    Data pelanggan
                                </h2>
                                <p class="mt-1 text-sm text-slate-400">
                                    Nama dan nomor HP dipakai untuk identifikasi
                                    member dan status order.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-orange-500/20 bg-orange-500/10 p-3 text-orange-200"
                            >
                                <UserRound class="h-5 w-5" />
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <input
                                v-model="customerName"
                                type="text"
                                placeholder="Nama pelanggan"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20"
                            />
                            <input
                                v-model="customerPhone"
                                type="text"
                                placeholder="Nomor HP"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20"
                            />
                            <input
                                v-model="customerEmail"
                                type="email"
                                placeholder="Email (opsional)"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 md:col-span-2"
                            />
                            <input
                                v-model="promoCode"
                                type="text"
                                placeholder="Voucher / promo code (opsional)"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm uppercase text-slate-100 placeholder:text-slate-500 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 md:col-span-2"
                            />
                        </div>
                    </section>

                    <section
                        class="rounded-[28px] border border-white/10 bg-slate-950/55 p-5 backdrop-blur"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                                >
                                    Langkah 2
                                </p>
                                <h2 class="mt-2 text-xl font-black text-white">
                                    Pilih menu
                                </h2>
                                <p class="mt-1 text-sm text-slate-400">
                                    Semua item di halaman ini langsung mengambil
                                    data produk aktif dari database outlet.
                                </p>
                            </div>

                            <div class="relative w-full max-w-sm">
                                <Search
                                    class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                                />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari menu favorit..."
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900/80 py-3 pl-11 pr-4 text-sm text-slate-100 placeholder-slate-500 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20"
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
                                        ? 'border-orange-500/30 bg-orange-500/10 text-orange-200'
                                        : 'border-white/10 bg-slate-900/70 text-slate-400',
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
                                        ? 'border-orange-500/30 bg-orange-500/10 text-orange-200'
                                        : 'border-white/10 bg-slate-900/70 text-slate-400',
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
                                class="group overflow-hidden rounded-[24px] border border-white/10 bg-slate-900/70 text-left transition hover:-translate-y-0.5 hover:border-orange-500/30"
                            >
                                <div
                                    class="relative h-36 overflow-hidden border-b border-white/10 bg-slate-950"
                                >
                                    <img
                                        :src="getProductImage(product)"
                                        :alt="product.name"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    />
                                    <span
                                        class="absolute right-3 top-3 rounded-full border border-white/10 bg-slate-950/80 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-200"
                                    >
                                        {{ product.category_name }}
                                    </span>
                                </div>
                                <div class="space-y-3 p-4">
                                    <div>
                                        <h3
                                            class="text-sm font-black text-white"
                                        >
                                            {{ product.name }}
                                        </h3>
                                        <p
                                            class="mt-1 line-clamp-2 text-[12px] leading-5 text-slate-400"
                                        >
                                            {{
                                                product.description ||
                                                'Tidak ada deskripsi tambahan.'
                                            }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex items-center justify-between border-t border-white/10 pt-3"
                                    >
                                        <span
                                            class="text-sm font-black text-orange-300"
                                        >
                                            {{
                                                formatPrice(product.base_price)
                                            }}
                                        </span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-orange-500/20 bg-orange-500/10 text-orange-200"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div
                            v-else
                            class="mt-5 rounded-[24px] border border-dashed border-white/10 bg-slate-900/40 px-6 py-16 text-center"
                        >
                            <p class="text-sm font-bold text-slate-300">
                                Menu tidak ditemukan
                            </p>
                            <p class="mt-2 text-xs text-slate-500">
                                Coba ganti kategori atau kata pencarian.
                            </p>
                        </div>
                    </section>
                </div>

                <aside class="xl:sticky xl:top-5 xl:h-fit">
                    <section
                        class="rounded-[28px] border border-white/10 bg-slate-950/70 p-5 shadow-[0_24px_80px_rgba(15,23,42,0.4)] backdrop-blur"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                                >
                                    Langkah 3
                                </p>
                                <h2 class="mt-2 text-xl font-black text-white">
                                    Ringkasan & bayar
                                </h2>
                                <p class="mt-1 text-sm text-slate-400">
                                    Flow QR meja saat ini wajib bayar dulu
                                    dengan QRIS.
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-fuchsia-500/20 bg-fuchsia-500/10 p-3 text-fuchsia-200"
                            >
                                <PackageCheck class="h-5 w-5" />
                            </div>
                        </div>

                        <div
                            class="mt-5 grid gap-3 rounded-[24px] border border-white/10 bg-white/5 p-4 sm:grid-cols-2 xl:grid-cols-1"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Total Item
                                </p>
                                <p class="mt-2 text-lg font-black text-white">
                                    {{ cartItemCount }} item
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Metode Bayar
                                </p>
                                <div
                                    class="mt-2 inline-flex items-center gap-2 rounded-full border border-fuchsia-500/20 bg-fuchsia-500/10 px-3 py-1 text-xs font-bold text-fuchsia-200"
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
                                class="rounded-[22px] border border-white/10 bg-slate-900/80 p-4"
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
                                            class="mt-1 text-[11px] font-semibold uppercase tracking-wider text-orange-300"
                                        >
                                            {{ item.variant_name }}
                                        </p>
                                        <p
                                            v-if="item.notes"
                                            class="mt-1 text-[11px] italic text-slate-500"
                                        >
                                            "{{ item.notes }}"
                                        </p>
                                        <p
                                            class="mt-3 text-sm font-black text-slate-200"
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
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-red-500/20 bg-red-500/10 text-red-300 transition hover:bg-red-500/15"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="mt-4 flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="decreaseCartQty(index)"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-300"
                                    >
                                        <Minus class="h-4 w-4" />
                                    </button>
                                    <span
                                        class="w-8 text-center text-sm font-bold text-white"
                                    >
                                        {{ item.quantity }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="increaseCartQty(index)"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-300"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="cart.length === 0"
                                class="rounded-[22px] border border-dashed border-white/10 bg-slate-900/50 px-5 py-12 text-center"
                            >
                                <ShoppingCart
                                    class="mx-auto h-8 w-8 text-slate-600"
                                />
                                <p
                                    class="mt-3 text-sm font-bold text-slate-300"
                                >
                                    Keranjang masih kosong
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Tambahkan menu dulu sebelum lanjut checkout.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div
                                v-if="checkoutError"
                                class="rounded-[22px] border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm font-medium text-rose-200"
                            >
                                {{ checkoutError }}
                            </div>
                            <textarea
                                v-model="orderNotes"
                                rows="3"
                                placeholder="Catatan umum order, misal alat makan dipisah..."
                                class="w-full resize-none rounded-[22px] border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20"
                            ></textarea>

                            <div
                                class="rounded-[22px] border border-white/10 bg-slate-900/70 p-4"
                            >
                                <div
                                    class="flex items-center justify-between text-sm text-slate-400"
                                >
                                    <span>Subtotal</span>
                                    <span>{{ formatPrice(cartSubtotal) }}</span>
                                </div>
                                <div
                                    class="mt-3 flex items-center justify-between text-sm text-slate-500"
                                >
                                    <span>Estimasi diskon</span>
                                    <span>Dihitung saat checkout</span>
                                </div>
                                <div
                                    class="mt-3 flex items-center justify-between border-t border-white/10 pt-3 text-base font-black text-white"
                                >
                                    <span>Total sebelum promo final</span>
                                    <span class="text-orange-300">{{
                                        formatPrice(cartSubtotal)
                                    }}</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="submitCheckout"
                                :disabled="
                                    !customerName ||
                                    !customerPhone ||
                                    cart.length === 0 ||
                                    isSubmitting
                                "
                                class="flex w-full items-center justify-center gap-2 rounded-[22px] bg-gradient-to-r from-orange-500 via-red-500 to-fuchsia-500 px-5 py-4 text-sm font-black text-white shadow-[0_20px_40px_rgba(249,115,22,0.25)] transition hover:brightness-110 disabled:pointer-events-none disabled:opacity-50"
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-md rounded-[28px] border border-white/10 bg-slate-950 p-5 shadow-2xl"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-[11px] font-bold uppercase tracking-[0.22em] text-orange-300"
                            >
                                Pilih varian
                            </p>
                            <h3 class="mt-2 text-xl font-black text-white">
                                {{ selectedProduct?.name }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="variantModalOpen = false"
                            class="rounded-full border border-white/10 p-2 text-slate-400"
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
                                    ? 'border-orange-500/30 bg-orange-500/10 text-orange-100'
                                    : 'border-white/10 bg-slate-900 text-slate-300',
                            ]"
                        >
                            <span class="text-sm font-bold">
                                {{ variant.name }}
                            </span>
                            <span class="text-sm font-black">
                                +{{ formatPrice(variant.additional_price) }}
                            </span>
                        </button>
                    </div>

                    <div
                        class="mt-5 flex items-center justify-between rounded-2xl border border-white/10 bg-slate-900 p-3"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                            >
                                Jumlah
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
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
                                class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-300"
                            >
                                <Minus class="h-4 w-4" />
                            </button>
                            <span
                                class="w-8 text-center text-sm font-bold text-white"
                            >
                                {{ itemQuantity }}
                            </span>
                            <button
                                type="button"
                                @click="itemQuantity += 1"
                                class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-300"
                            >
                                <Plus class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <textarea
                        v-model="itemNotes"
                        rows="3"
                        placeholder="Catatan item, misal tanpa saus atau level pedas..."
                        class="mt-5 w-full resize-none rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20"
                    ></textarea>

                    <button
                        type="button"
                        @click="confirmVariantAdd"
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-[22px] bg-gradient-to-r from-orange-500 to-red-500 px-5 py-3.5 text-sm font-black text-white"
                    >
                        <PackageCheck class="h-4 w-4" />
                        Tambah ke keranjang
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
