<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Bar Display - POS Mentai" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white font-sans">
                        Bar Display System (BDS)
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Antrian Pesanan Minuman Restoran secara Real-time
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 lg:p-8 text-center shadow-xl">
                <div class="h-14 w-14 rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 mx-auto mb-4">
                    ☕
                </div>
                <h3 class="text-2xl font-extrabold text-white mb-2">Monitor Antrian Bar</h3>
                <p class="text-slate-400 text-sm mb-6">
                    Menampilkan daftar pesanan minuman yang harus disiapkan oleh barista.
                </p>
                
                <div class="bg-slate-950 border border-slate-850 rounded-xl p-5 text-left space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="h-5 w-5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">✓</span>
                        <div>
                            <p class="text-xs font-bold text-slate-200">13. Antrian Order Real-time (Bar)</p>
                            <p class="text-[11px] text-slate-450 mt-0.5">Sinkronisasi instan pesanan minuman masuk dari kasir ke layar bar.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-slate-950/40 border border-slate-855 rounded-xl text-xs text-slate-500">
                    💡 Anda tidak perlu logout untuk berpindah menu. Cukup gunakan **Sidebar Navigasi** di sebelah kiri untuk berpindah ke modul lain atau kembali ke Dashboard.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
