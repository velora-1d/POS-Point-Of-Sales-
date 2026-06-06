<script setup lang="ts">
import { AlertCircle, CheckCircle2, HelpCircle, Info } from '@lucide/vue';
import { computed } from 'vue';

interface Props {
    show?: boolean;
    title?: string;
    message?: string;
    type?: 'info' | 'success' | 'warning' | 'danger' | 'confirm';
    confirmText?: string;
    cancelText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    title: 'Konfirmasi',
    message: '',
    type: 'confirm',
    confirmText: 'Ya, Lanjutkan',
    cancelText: 'Batal',
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const iconComponent = computed(() => {
    switch (props.type) {
        case 'success':
            return CheckCircle2;
        case 'warning':
            return AlertCircle;
        case 'danger':
            return AlertCircle;
        case 'info':
            return Info;
        default:
            return HelpCircle;
    }
});

const iconClass = computed(() => {
    switch (props.type) {
        case 'success':
            return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
        case 'warning':
            return 'text-amber-400 bg-amber-500/10 border-amber-500/20';
        case 'danger':
            return 'text-rose-400 bg-rose-500/10 border-rose-500/20';
        case 'info':
            return 'text-sky-400 bg-sky-500/10 border-sky-500/20';
        default:
            return 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20';
    }
});

const buttonClass = computed(() => {
    switch (props.type) {
        case 'danger':
            return 'bg-rose-500 hover:bg-rose-600 text-white';
        case 'warning':
            return 'bg-amber-500 hover:bg-amber-600 text-slate-950';
        case 'success':
            return 'bg-emerald-500 hover:bg-emerald-600 text-white';
        default:
            return 'bg-orange-500 hover:bg-orange-600 text-slate-950';
    }
});
</script>

<template>
    <teleport to="body">
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="props.show"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-[32px] border border-white/10 bg-slate-900 shadow-2xl shadow-black/50"
                    @click.stop
                >
                    <div class="p-8">
                        <!-- Icon Header -->
                        <div class="flex justify-center">
                            <div
                                :class="[
                                    'flex h-16 w-16 scale-110 items-center justify-center rounded-2xl border transition-all duration-500',
                                    iconClass,
                                ]"
                            >
                                <component
                                    :is="iconComponent"
                                    class="h-8 w-8"
                                />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="mt-6 text-center">
                            <h3
                                class="text-xl font-black uppercase tracking-tight tracking-widest text-white"
                            >
                                {{ props.title }}
                            </h3>
                            <p
                                class="mt-3 text-sm leading-relaxed text-slate-400"
                            >
                                {{ props.message }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 flex flex-col gap-3">
                            <button
                                type="button"
                                @click="emit('confirm')"
                                :class="[
                                    'w-full rounded-2xl py-4 text-sm font-black uppercase tracking-widest shadow-lg transition',
                                    buttonClass,
                                ]"
                            >
                                {{ props.confirmText }}
                            </button>
                            <button
                                v-if="
                                    props.type === 'confirm' ||
                                    props.type === 'danger' ||
                                    props.type === 'warning'
                                "
                                type="button"
                                @click="emit('cancel')"
                                class="w-full rounded-2xl border border-white/10 bg-white/5 py-4 text-sm font-bold text-slate-300 transition hover:bg-white/10 hover:text-white"
                            >
                                {{ props.cancelText }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>
