<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, EyeOff } from '@lucide/vue';

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
        }
    });
};
</script>

<template>
    <Head title="Log In - POS Mentai" />

    <div class="min-h-screen flex flex-col md:flex-row bg-slate-950 text-slate-100 font-sans selection:bg-orange-500 selection:text-white">
        
        <!-- Left Side: Cinematic Banner (Hidden on Mobile) -->
        <div class="hidden md:flex md:w-1/2 lg:w-3/5 relative overflow-hidden bg-slate-900 justify-center items-center">
            <!-- Background Image with Overlay -->
            <img 
                src="/images/login_banner.png" 
                alt="Mentai Restaurant Banner" 
                class="absolute inset-0 w-full h-full object-cover opacity-60 scale-105 transition-transform duration-10000 ease-out hover:scale-100"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-slate-950/70"></div>
            
            <!-- Floating Premium Card on Left Side -->
            <div class="relative z-10 p-12 max-w-lg text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs font-semibold tracking-wider uppercase mb-6 backdrop-blur-sm">
                    🔥 Premium Restaurant System
                </div>
                <h1 class="text-4xl lg:text-5xl font-black tracking-tight text-white mb-4 leading-tight">
                    Sistem Operasional <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-red-500">
                        POS Mentai
                    </span>
                </h1>
                <p class="text-slate-300 text-lg leading-relaxed mb-8">
                    Kelola meja, dapur, pesanan, dan keuangan restoran secara real-time dengan efisiensi tingkat tinggi.
                </p>
                <div class="flex gap-8 border-t border-slate-800/80 pt-8">
                    <div>
                        <p class="text-2xl font-bold text-white">40+</p>
                        <p class="text-xs text-slate-400 mt-1">Alur Kerja Terotomatisasi</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">100%</p>
                        <p class="text-xs text-slate-400 mt-1">Keamanan Isolasi Data</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">Real-time</p>
                        <p class="text-xs text-slate-400 mt-1">Kitchen & Bar Sync</p>
                    </div>
                </div>
            </div>
            
            <!-- Subtle corner brand light -->
            <div class="absolute top-8 left-8 z-10 flex items-center gap-2">
                <div class="h-8 w-8 rounded-lg bg-gradient-to-tr from-orange-500 to-red-600 flex items-center justify-center font-black text-white shadow-lg shadow-orange-500/20">M</div>
                <span class="font-bold text-lg tracking-wider text-white">MENTAI</span>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex-1 flex flex-col justify-center items-center px-6 py-12 md:px-12 lg:px-20 relative bg-slate-950">
            <!-- Ambient glowing spot -->
            <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-orange-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-red-600/5 rounded-full blur-3xl pointer-events-none"></div>

            <div class="w-full max-w-md relative z-10">
                <!-- Mobile brand logo (visible only on mobile) -->
                <div class="flex items-center gap-2 mb-8 md:hidden justify-center">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-orange-500 to-red-600 flex items-center justify-center font-black text-white shadow-lg">M</div>
                    <span class="font-bold text-2xl tracking-wider text-white">POS MENTAI</span>
                </div>

                <!-- Form Header -->
                <div class="text-center md:text-left mb-8">
                    <h2 class="text-3xl font-extrabold text-white tracking-tight">Selamat Datang</h2>
                    <p class="text-slate-400 mt-2">Silakan masuk menggunakan kredensial akun Anda.</p>
                </div>

                <!-- Status Banner -->
                <div v-if="status" class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-sm font-medium text-emerald-400">
                    {{ status }}
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email field -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-semibold text-slate-300">Alamat Email</label>
                        <div class="relative group">
                            <input
                                id="email"
                                type="email"
                                class="w-full bg-slate-900/60 border border-slate-800 rounded-xl px-4 py-3.5 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition duration-200"
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
                        <div class="flex justify-between items-center">
                            <label for="password" class="block text-sm font-semibold text-slate-300">Kata Sandi</label>
                            <a
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-orange-400 hover:text-orange-350 transition duration-150"
                            >
                                Lupa sandi?
                            </a>
                        </div>
                        <div class="relative group">
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full bg-slate-900/60 border border-slate-800 rounded-xl pl-4 pr-12 py-3.5 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition duration-200"
                                placeholder="••••••••"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition duration-150 focus:outline-none"
                            >
                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <!-- Remember me checkbox -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer select-none">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2.5 text-sm text-slate-400 hover:text-slate-300 transition duration-150">
                                Ingat sesi saya
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 py-4 px-6 rounded-xl text-white font-bold bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-orange-500/50 transition duration-250 shadow-lg shadow-orange-500/25 active:scale-[0.99] disabled:opacity-50 disabled:pointer-events-none"
                        :disabled="isSubmitting || form.processing"
                    >
                        <!-- Loading spinner -->
                        <svg v-if="isSubmitting || form.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ (isSubmitting || form.processing) ? 'Memproses Masuk...' : 'Masuk ke Dashboard' }}</span>
                    </button>
                </form>

                <!-- Footer branding -->
                <div class="mt-12 text-center text-xs text-slate-500 border-t border-slate-900/60 pt-6">
                    POS Mentai &copy; 2026. Hak Cipta Dilindungi.
                </div>
            </div>
        </div>
    </div>
</template>
