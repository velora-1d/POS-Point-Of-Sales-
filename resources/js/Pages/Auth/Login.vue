<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const isSubmitting = ref(false);
const showPassword = ref(false);

const submit = () => {
    isSubmitting.value = true;
    form.post(route('login'), {
        onFinish: () => {
            isSubmitting.value = false;
            form.reset('password');
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Log In - POS Mentai" />

    <div
        class="flex min-h-screen flex-col bg-stone-100 font-sans text-black selection:bg-orange-500 selection:text-white md:flex-row"
    >
        <!-- Left Side: Cinematic Banner (Hidden on Mobile) -->
        <div
            class="relative hidden items-center justify-center overflow-hidden bg-stone-200 md:flex md:w-1/2 lg:w-3/5"
        >
            <!-- Background Image with Overlay -->
            <img
                src="/images/login_banner.png"
                alt="Mentai Restaurant Banner"
                class="duration-10000 absolute inset-0 h-full w-full scale-105 object-cover opacity-80 transition-transform ease-out hover:scale-100"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-stone-200 via-stone-200/40 to-stone-200/70"
            ></div>

            <!-- Floating Premium Card on Left Side -->
            <div class="relative z-10 max-w-lg p-12 text-left">
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border-2 border-orange-500 bg-orange-100 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-orange-700"
                >
                    🔥 Premium Restaurant System
                </div>
                <h1
                    class="mb-4 text-4xl font-black leading-tight tracking-tight text-black lg:text-5xl"
                >
                    Sistem Operasional <br />
                    <span
                        class="bg-gradient-to-r from-orange-600 to-red-700 bg-clip-text text-transparent"
                    >
                        POS Mentai
                    </span>
                </h1>
                <p class="mb-8 text-lg font-bold leading-relaxed text-stone-700">
                    Kelola meja, dapur, pesanan, dan keuangan restoran secara
                    real-time dengan efisiensi tingkat tinggi.
                </p>
                <div class="flex gap-8 border-t-2 border-stone-300 pt-8">
                    <div>
                        <p class="text-2xl font-black text-black">40+</p>
                        <p class="mt-1 text-xs font-bold text-stone-500">
                            Alur Kerja Terotomatisasi
                        </p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-black">100%</p>
                        <p class="mt-1 text-xs font-bold text-stone-500">
                            Keamanan Isolasi Data
                        </p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-black">Real-time</p>
                        <p class="mt-1 text-xs font-bold text-stone-500">
                            Kitchen & Bar Sync
                        </p>
                    </div>
                </div>
            </div>

            <!-- Subtle corner brand light -->
            <div class="absolute left-8 top-8 z-10 flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white p-1 shadow-xl"
                >
                    <img
                        src="/images/pos_logo.png"
                        class="h-full w-full object-contain"
                        alt="Logo"
                    />
                </div>
                <span class="text-xl font-black tracking-wider text-black"
                    >POS MENTAI</span
                >
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div
            class="relative flex flex-1 flex-col items-center justify-center bg-white px-6 py-12 md:px-12 lg:px-20"
        >
            <!-- Ambient glowing spot -->
            <div
                class="pointer-events-none absolute right-1/4 top-1/4 h-96 w-96 rounded-full bg-orange-500/10 blur-3xl"
            ></div>
            <div
                class="pointer-events-none absolute bottom-1/4 left-1/4 h-96 w-96 rounded-full bg-red-500/5 blur-3xl"
            ></div>

            <div class="relative z-10 w-full max-w-md">
                <!-- Mobile brand logo (visible only on mobile) -->
                <div
                    class="mb-8 flex items-center justify-center gap-3 md:hidden"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-white p-1 shadow-lg"
                    >
                        <img
                            src="/images/pos_logo.png"
                            class="h-full w-full object-contain"
                            alt="Logo"
                        />
                    </div>
                    <span class="text-2xl font-black tracking-wider text-black"
                        >POS MENTAI</span
                    >
                </div>

                <!-- Form Header -->
                <div class="mb-8 text-center md:text-left">
                    <h2
                        class="text-3xl font-black tracking-tight text-black"
                    >
                        Selamat Datang
                    </h2>
                    <p class="mt-2 font-bold text-stone-500">
                        Silakan masuk menggunakan kredensial akun Anda.
                    </p>
                </div>

                <!-- Status Banner -->
                <div
                    v-if="status"
                    class="mb-6 rounded-xl border-2 border-emerald-700 bg-emerald-50 p-4 text-sm font-bold text-emerald-800"
                >
                    {{ status }}
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email field -->
                    <div class="space-y-2">
                        <label
                            for="email"
                            class="block text-sm font-black text-black"
                            >Alamat Email</label
                        >
                        <div class="group relative">
                            <input
                                id="email"
                                type="email"
                                class="w-full rounded-xl border-2 border-stone-200 bg-stone-50 px-4 py-3.5 text-black placeholder-stone-400 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 font-bold"
                                placeholder="nama@restoran.com"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <!-- Password field -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label
                                for="password"
                                class="block text-sm font-black text-black"
                                >Kata Sandi</label
                            >
                            <a
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-black text-orange-600 transition duration-150 hover:text-orange-700 underline underline-offset-4"
                            >
                                Lupa sandi?
                            </a>
                        </div>
                        <div class="group relative">
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full rounded-xl border-2 border-stone-200 bg-stone-50 py-3.5 pl-4 pr-12 text-black placeholder-stone-400 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 font-bold"
                                placeholder="••••••••"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-stone-400 transition duration-150 hover:text-black focus:outline-none"
                            >
                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError
                            class="mt-1"
                            :message="form.errors.password"
                        />
                    </div>

                    <!-- Remember me checkbox -->
                    <div class="flex items-center justify-between">
                        <label
                            class="flex cursor-pointer select-none items-center"
                        >
                            <Checkbox
                                name="remember"
                                v-model:checked="form.remember"
                            />
                            <span
                                class="ms-2.5 text-sm font-bold text-stone-500 transition duration-150 hover:text-black"
                            >
                                Ingat sesi saya
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="duration-250 flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-600 px-6 py-4 font-black text-white shadow-lg shadow-orange-500/20 transition focus:outline-none focus:ring-2 focus:ring-orange-500/50 active:scale-[0.99] disabled:pointer-events-none disabled:opacity-50"
                        :disabled="isSubmitting || form.processing"
                    >
                        <!-- Loading spinner -->
                        <svg
                            v-if="isSubmitting || form.processing"
                            class="-ml-1 mr-2 h-5 w-5 animate-spin text-white"
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
                            isSubmitting || form.processing
                                ? 'Memproses Masuk...'
                                : 'Masuk ke Dashboard'
                        }}</span>
                    </button>
                </form>

                <!-- Footer branding -->
                <div
                    class="mt-12 border-t-2 border-stone-100 pt-6 text-center text-xs font-bold text-stone-400"
                >
                    POS Mentai &copy; 2026. Hak Cipta Dilindungi.
                </div>
            </div>
        </div>
    </div>
</template>
