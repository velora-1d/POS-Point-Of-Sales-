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
        class="flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 selection:bg-orange-500 selection:text-white md:flex-row"
    >
        <!-- Left Side: Cinematic Banner (Hidden on Mobile) -->
        <div
            class="relative hidden items-center justify-center overflow-hidden bg-slate-900 md:flex md:w-1/2 lg:w-3/5"
        >
            <!-- Background Image with Overlay -->
            <img
                src="/images/login_banner.png"
                alt="Mentai Restaurant Banner"
                class="duration-10000 absolute inset-0 h-full w-full scale-105 object-cover opacity-60 transition-transform ease-out hover:scale-100"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-slate-950/70"
            ></div>

            <!-- Floating Premium Card on Left Side -->
            <div class="relative z-10 max-w-lg p-12 text-left">
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-orange-400 backdrop-blur-sm"
                >
                    🔥 Premium Restaurant System
                </div>
                <h1
                    class="mb-4 text-4xl font-black leading-tight tracking-tight text-white lg:text-5xl"
                >
                    Sistem Operasional <br />
                    <span
                        class="bg-gradient-to-r from-orange-400 to-red-500 bg-clip-text text-transparent"
                    >
                        POS Mentai
                    </span>
                </h1>
                <p class="mb-8 text-lg leading-relaxed text-slate-300">
                    Kelola meja, dapur, pesanan, dan keuangan restoran secara
                    real-time dengan efisiensi tingkat tinggi.
                </p>
                <div class="flex gap-8 border-t border-slate-800/80 pt-8">
                    <div>
                        <p class="text-2xl font-bold text-white">40+</p>
                        <p class="mt-1 text-xs text-slate-400">
                            Alur Kerja Terotomatisasi
                        </p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">100%</p>
                        <p class="mt-1 text-xs text-slate-400">
                            Keamanan Isolasi Data
                        </p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">Real-time</p>
                        <p class="mt-1 text-xs text-slate-400">
                            Kitchen & Bar Sync
                        </p>
                    </div>
                </div>
            </div>

            <!-- Subtle corner brand light -->
            <div class="absolute left-8 top-8 z-10 flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white p-1 shadow-lg shadow-orange-500/20"
                >
                    <img
                        src="/images/pos_logo.png"
                        class="h-full w-full object-contain"
                        alt="Logo"
                    />
                </div>
                <span class="text-xl font-bold tracking-wider text-white"
                    >POS MENTAI</span
                >
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div
            class="relative flex flex-1 flex-col items-center justify-center bg-slate-950 px-6 py-12 md:px-12 lg:px-20"
        >
            <!-- Ambient glowing spot -->
            <div
                class="pointer-events-none absolute right-1/4 top-1/4 h-96 w-96 rounded-full bg-orange-600/10 blur-3xl"
            ></div>
            <div
                class="pointer-events-none absolute bottom-1/4 left-1/4 h-96 w-96 rounded-full bg-red-600/5 blur-3xl"
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
                    <span class="text-2xl font-bold tracking-wider text-white"
                        >POS MENTAI</span
                    >
                </div>

                <!-- Form Header -->
                <div class="mb-8 text-center md:text-left">
                    <h2
                        class="text-3xl font-extrabold tracking-tight text-white"
                    >
                        Selamat Datang
                    </h2>
                    <p class="mt-2 text-slate-400">
                        Silakan masuk menggunakan kredensial akun Anda.
                    </p>
                </div>

                <!-- Status Banner -->
                <div
                    v-if="status"
                    class="mb-6 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm font-medium text-emerald-400"
                >
                    {{ status }}
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email field -->
                    <div class="space-y-2">
                        <label
                            for="email"
                            class="block text-sm font-semibold text-slate-300"
                            >Alamat Email</label
                        >
                        <div class="group relative">
                            <input
                                id="email"
                                type="email"
                                class="w-full rounded-xl border border-slate-800 bg-slate-900/60 px-4 py-3.5 text-slate-100 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
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
                                class="block text-sm font-semibold text-slate-300"
                                >Kata Sandi</label
                            >
                            <a
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-orange-400 transition duration-150 hover:text-orange-300"
                            >
                                Lupa sandi?
                            </a>
                        </div>
                        <div class="group relative">
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full rounded-xl border border-slate-800 bg-slate-900/60 py-3.5 pl-4 pr-12 text-slate-100 placeholder-slate-500 transition duration-200 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                placeholder="••••••••"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition duration-150 hover:text-slate-200 focus:outline-none"
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
                                class="ms-2.5 text-sm text-slate-400 transition duration-150 hover:text-slate-300"
                            >
                                Ingat sesi saya
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="duration-250 flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-400 px-6 py-4 font-bold text-stone-950 transition focus:outline-none focus:ring-2 focus:ring-orange-500/50 active:scale-[0.99] disabled:pointer-events-none disabled:opacity-50"
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
                    class="mt-12 border-t border-slate-900/60 pt-6 text-center text-xs text-slate-500"
                >
                    POS Mentai &copy; 2026. Hak Cipta Dilindungi.
                </div>
            </div>
        </div>
    </div>
</template>
