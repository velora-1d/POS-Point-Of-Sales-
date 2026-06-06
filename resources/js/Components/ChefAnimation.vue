<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        state: 'waiting' | 'cooking' | 'ready' | 'idle';
    }>(),
    {
        state: 'idle',
    },
);

const stateLabel = computed(() => {
    switch (props.state) {
        case 'waiting':
            return 'Menyiapkan bahan...';
        case 'cooking':
            return 'Sedang dimasak...';
        case 'ready':
            return 'Pesanan siap disajikan!';
        case 'idle':
        default:
            return 'Dapur santai';
    }
});
</script>

<template>
    <div class="flex flex-col items-center justify-center p-4 text-center">
        <div
            class="relative flex h-32 w-32 items-center justify-center overflow-hidden rounded-full border border-stone-200/40 bg-slate-100/5 shadow-inner dark:border-slate-800/80 dark:bg-slate-900/60"
        >
            <!-- State 1: COOKING (Koki Memasak Wajan Api) -->
            <svg
                v-if="state === 'cooking'"
                class="h-24 w-24"
                viewBox="0 0 100 100"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Api Dapur -->
                <g class="anim-flame">
                    <path
                        d="M42 78C42 78 45 68 50 68C55 68 58 78 58 78C58 78 55 84 50 84C45 84 42 78 42 78Z"
                        fill="#F97316"
                        opacity="0.85"
                    />
                    <path
                        d="M46 76C46 76 48 70 50 70C52 70 54 76 54 76C54 76 52 80 50 80C48 80 46 76 46 76Z"
                        fill="#EF4444"
                        opacity="0.95"
                    />
                </g>

                <!-- Badan Koki -->
                <rect
                    x="35"
                    y="45"
                    width="30"
                    height="28"
                    rx="8"
                    fill="#E2E8F0"
                    class="dark:fill-slate-700"
                />
                <!-- Wajah -->
                <circle cx="50" cy="38" r="12" fill="#FDBA74" />
                <!-- Mata Ceria -->
                <path
                    d="M44 38C44 38 46 36 47 38"
                    stroke="#1E293B"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    class="dark:stroke-white"
                />
                <path
                    d="M53 38C53 38 55 36 56 38"
                    stroke="#1E293B"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    class="dark:stroke-white"
                />
                <!-- Senyum -->
                <path
                    d="M47 43C47 43 49 46 53 43"
                    stroke="#1E293B"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    class="dark:stroke-white"
                />

                <!-- Topi Koki -->
                <path
                    d="M42 27C38 27 38 20 44 18C44 14 56 14 56 18C62 20 62 27 58 27H42Z"
                    fill="#FFFFFF"
                    stroke="#E2E8F0"
                    stroke-width="1"
                />
                <rect
                    x="43"
                    y="25"
                    width="14"
                    height="4"
                    fill="#FFFFFF"
                    stroke="#E2E8F0"
                    stroke-width="0.5"
                />

                <!-- Wajan (Pan) & Makanan yang melompat -->
                <g class="anim-pan">
                    <!-- Wajan -->
                    <path
                        d="M26 65H58L55 69H29L26 65Z"
                        fill="#1E293B"
                        class="dark:fill-slate-800"
                    />
                    <rect
                        x="58"
                        y="65"
                        width="16"
                        height="2"
                        rx="1"
                        fill="#475569"
                    />
                    <!-- Makanan Melayang -->
                    <circle
                        cx="34"
                        cy="56"
                        r="2"
                        fill="#F59E0B"
                        class="anim-food-1"
                    />
                    <circle
                        cx="42"
                        cy="52"
                        r="1.5"
                        fill="#10B981"
                        class="anim-food-2"
                    />
                    <circle
                        cx="48"
                        cy="57"
                        r="2.5"
                        fill="#EF4444"
                        class="anim-food-3"
                    />
                    <circle
                        cx="52"
                        cy="50"
                        r="2"
                        fill="#F59E0B"
                        class="anim-food-4"
                    />
                </g>
            </svg>

            <!-- State 2: WAITING (Koki Memotong Bahan) -->
            <svg
                v-else-if="state === 'waiting'"
                class="h-24 w-24"
                viewBox="0 0 100 100"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Badan Koki -->
                <rect
                    x="35"
                    y="45"
                    width="30"
                    height="28"
                    rx="8"
                    fill="#E2E8F0"
                    class="dark:fill-slate-700"
                />
                <!-- Wajah -->
                <circle cx="50" cy="38" r="12" fill="#FDBA74" />
                <!-- Kacamata/Mata fokus -->
                <circle
                    cx="46"
                    cy="38"
                    r="2"
                    fill="#1E293B"
                    class="dark:fill-white"
                />
                <circle
                    cx="54"
                    cy="38"
                    r="2"
                    fill="#1E293B"
                    class="dark:fill-white"
                />
                <path
                    d="M48 42C48 42 50 43 52 42"
                    stroke="#1E293B"
                    stroke-width="1"
                    class="dark:stroke-white"
                />
                <!-- Topi Koki -->
                <path
                    d="M42 27C38 27 38 20 44 18C44 14 56 14 56 18C62 20 62 27 58 27H42Z"
                    fill="#FFFFFF"
                    stroke="#E2E8F0"
                    stroke-width="1"
                />
                <rect
                    x="43"
                    y="25"
                    width="14"
                    height="4"
                    fill="#FFFFFF"
                    stroke="#E2E8F0"
                    stroke-width="0.5"
                />

                <!-- Talenan & Wortel -->
                <rect
                    x="25"
                    y="70"
                    width="40"
                    height="4"
                    rx="1"
                    fill="#B45309"
                />
                <path d="M35 68H48L51 70H32L35 68Z" fill="#F97316" />
                <!-- Wortel -->
                <rect
                    x="51"
                    y="68"
                    width="4"
                    height="2"
                    rx="0.5"
                    fill="#10B981"
                />
                <!-- Daun Wortel -->

                <!-- Pisau Mencacah -->
                <g class="anim-knife">
                    <path d="M42 52L54 58L52 64L40 58L42 52Z" fill="#94A3B8" />
                    <rect
                        x="54"
                        y="58"
                        width="8"
                        height="2"
                        rx="0.5"
                        fill="#475569"
                        transform="rotate(25 54 58)"
                    />
                </g>
            </svg>

            <!-- State 3: READY (Koki Menyajikan Hidangan) -->
            <svg
                v-else-if="state === 'ready'"
                class="h-24 w-24"
                viewBox="0 0 100 100"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Efek Bintang Latar Belakang -->
                <g class="anim-stars">
                    <path
                        d="M15 30L17 33L20 34L17 35L15 38L13 35L10 34L13 33L15 30Z"
                        fill="#F59E0B"
                        opacity="0.6"
                    />
                    <path
                        d="M85 25L86.5 27.5L89 28.3L86.5 29.1L85 31.6L83.5 29.1L81 28.3L83.5 27.5L85 25Z"
                        fill="#F59E0B"
                        opacity="0.7"
                    />
                </g>

                <!-- Badan Koki -->
                <rect
                    x="35"
                    y="45"
                    width="30"
                    height="28"
                    rx="8"
                    fill="#E2E8F0"
                    class="dark:fill-slate-700"
                />
                <!-- Wajah -->
                <circle cx="50" cy="38" r="12" fill="#FDBA74" />
                <!-- Mata Berkedip Ceria -->
                <path
                    d="M43 38C43 38 45 40 47 38"
                    stroke="#1E293B"
                    stroke-width="2"
                    stroke-linecap="round"
                    class="dark:stroke-white"
                />
                <path
                    d="M53 38C53 38 55 40 57 38"
                    stroke="#1E293B"
                    stroke-width="2"
                    stroke-linecap="round"
                    class="dark:stroke-white"
                />
                <!-- Senyum Lebar -->
                <path
                    d="M45 43C45 43 49 48 55 43"
                    stroke="#1E293B"
                    stroke-width="2"
                    stroke-linecap="round"
                    class="dark:stroke-white"
                />
                <!-- Blush Pipi -->
                <circle cx="41" cy="42" r="1.5" fill="#FDA4AF" />
                <circle cx="59" cy="42" r="1.5" fill="#FDA4AF" />

                <!-- Topi Koki -->
                <path
                    d="M42 27C38 27 38 20 44 18C44 14 56 14 56 18C62 20 62 27 58 27H42Z"
                    fill="#FFFFFF"
                    stroke="#E2E8F0"
                    stroke-width="1"
                />
                <rect
                    x="43"
                    y="25"
                    width="14"
                    height="4"
                    fill="#FFFFFF"
                    stroke="#E2E8F0"
                    stroke-width="0.5"
                />

                <!-- Mangkuk Mentai Cantik disajikan -->
                <g class="anim-plate">
                    <path
                        d="M30 67H70C70 67 68 76 50 76C32 76 30 67 30 67Z"
                        fill="#EF4444"
                    />
                    <!-- Bowl Merah -->
                    <ellipse cx="50" cy="67" rx="20" ry="4" fill="#F59E0B" />
                    <!-- Mentai sauce -->
                    <circle cx="44" cy="65" r="2" fill="#10B981" />
                    <!-- Garnish -->
                    <circle cx="56" cy="66" r="1.5" fill="#1E293B" />
                    <!-- Nori -->
                    <!-- Uap Panas -->
                    <path
                        d="M42 59C42 59 43 56 42 54"
                        stroke="#E2E8F0"
                        stroke-width="1"
                        stroke-linecap="round"
                        class="anim-steam-1"
                    />
                    <path
                        d="M50 58C50 58 51 55 50 53"
                        stroke="#E2E8F0"
                        stroke-width="1"
                        stroke-linecap="round"
                        class="anim-steam-2"
                    />
                    <path
                        d="M58 59C58 59 59 56 58 54"
                        stroke="#E2E8F0"
                        stroke-width="1"
                        stroke-linecap="round"
                        class="anim-steam-3"
                    />
                </g>
            </svg>

            <!-- State 4: IDLE (Koki Istirahat/Tidur Zzz) -->
            <svg
                v-else
                class="h-24 w-24"
                viewBox="0 0 100 100"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Badan Koki (Menunduk sedikit) -->
                <rect
                    x="35"
                    y="47"
                    width="30"
                    height="26"
                    rx="8"
                    fill="#CBD5E1"
                    class="dark:fill-slate-800"
                />
                <!-- Wajah -->
                <circle cx="50" cy="40" r="12" fill="#FDBA74" />
                <!-- Mata Tertutup (Tidur) -->
                <path
                    d="M43 40H47"
                    stroke="#475569"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    class="dark:stroke-slate-400"
                />
                <path
                    d="M53 40H57"
                    stroke="#475569"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    class="dark:stroke-slate-400"
                />
                <!-- Mulut Kecil Tenang -->
                <circle
                    cx="50"
                    cy="45"
                    r="1"
                    fill="#475569"
                    class="dark:fill-slate-400"
                />

                <!-- Topi Koki (Miring) -->
                <g transform="rotate(-5 50 25)">
                    <path
                        d="M42 27C38 27 38 20 44 18C44 14 56 14 56 18C62 20 62 27 58 27H42Z"
                        fill="#E2E8F0"
                        stroke="#CBD5E1"
                        stroke-width="1"
                    />
                    <rect
                        x="43"
                        y="25"
                        width="14"
                        height="4"
                        fill="#E2E8F0"
                        stroke="#CBD5E1"
                        stroke-width="0.5"
                    />
                </g>

                <!-- Huruf Zzz Melayang -->
                <text
                    x="68"
                    y="32"
                    font-size="8"
                    font-weight="bold"
                    fill="#3B82F6"
                    class="anim-zzz-1 dark:fill-sky-400"
                >
                    Z
                </text>
                <text
                    x="74"
                    y="24"
                    font-size="10"
                    font-weight="bold"
                    fill="#3B82F6"
                    class="anim-zzz-2 dark:fill-sky-400"
                >
                    z
                </text>
                <text
                    x="82"
                    y="16"
                    font-size="12"
                    font-weight="bold"
                    fill="#3B82F6"
                    class="anim-zzz-3 dark:fill-sky-400"
                >
                    z
                </text>

                <!-- Lengan Koki bersedekap lipat meja -->
                <rect
                    x="30"
                    y="68"
                    width="40"
                    height="6"
                    rx="2"
                    fill="#94A3B8"
                    class="dark:fill-slate-700"
                />
            </svg>
        </div>
        <p
            class="mt-3 text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500 dark:text-slate-400"
        >
            {{ stateLabel }}
        </p>
    </div>
</template>

<style scoped>
/* Keyframe Animasi 1: COOKING (Wajan Api Melompat) */
.anim-pan {
    animation: toss 1.6s ease-in-out infinite;
    transform-origin: 50% 67px;
}
@keyframes toss {
    0%,
    100% {
        transform: translateY(0) rotate(0deg);
    }
    40% {
        transform: translateY(-4px) rotate(-3deg);
    }
    50% {
        transform: translateY(-5px) rotate(1deg);
    }
    80% {
        transform: translateY(1px) rotate(2deg);
    }
}

.anim-flame {
    animation: flameBurn 0.5s ease-in-out infinite alternate;
    transform-origin: 50% 84px;
}
@keyframes flameBurn {
    0% {
        transform: scaleY(0.9) scaleX(0.95);
    }
    100% {
        transform: scaleY(1.15) scaleX(1.05);
    }
}

.anim-food-1 {
    animation: foodJump1 1.6s ease-in-out infinite;
}
.anim-food-2 {
    animation: foodJump2 1.6s ease-in-out infinite;
}
.anim-food-3 {
    animation: foodJump3 1.6s ease-in-out infinite;
}
.anim-food-4 {
    animation: foodJump4 1.6s ease-in-out infinite;
}

@keyframes foodJump1 {
    0%,
    100%,
    80% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    42% {
        opacity: 1;
    }
    50% {
        transform: translateY(-16px) translateX(-3px);
    }
}
@keyframes foodJump2 {
    0%,
    100%,
    82% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    44% {
        opacity: 1;
    }
    52% {
        transform: translateY(-20px) translateX(2px);
    }
}
@keyframes foodJump3 {
    0%,
    100%,
    78% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    40% {
        opacity: 1;
    }
    48% {
        transform: translateY(-14px) translateX(-1px);
    }
}
@keyframes foodJump4 {
    0%,
    100%,
    84% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    46% {
        opacity: 1;
    }
    54% {
        transform: translateY(-18px) translateX(4px);
    }
}

/* Keyframe Animasi 2: WAITING (Pisau Mencacah) */
.anim-knife {
    animation: chop 0.4s ease-in-out infinite alternate;
    transform-origin: 54px 58px;
}
@keyframes chop {
    0% {
        transform: translateY(-2px) rotate(0deg);
    }
    100% {
        transform: translateY(3px) rotate(-10deg);
    }
}

/* Keyframe Animasi 3: READY (Uap Mangkuk Saji & Bintang) */
.anim-stars {
    animation: pulseStars 2s ease-in-out infinite alternate;
}
@keyframes pulseStars {
    0% {
        opacity: 0.3;
        transform: scale(0.95);
    }
    100% {
        opacity: 1;
        transform: scale(1.05);
    }
}

.anim-steam-1 {
    animation: steamRise 1.4s ease-in-out infinite;
}
.anim-steam-2 {
    animation: steamRise 1.4s ease-in-out infinite 0.4s;
}
.anim-steam-3 {
    animation: steamRise 1.4s ease-in-out infinite 0.8s;
}

@keyframes steamRise {
    0% {
        transform: translateY(2px) scaleX(0.8);
        opacity: 0;
    }
    30% {
        opacity: 0.6;
    }
    100% {
        transform: translateY(-10px) scaleX(1.2);
        opacity: 0;
    }
}

/* Keyframe Animasi 4: IDLE (Tidur Zzz) */
.anim-zzz-1 {
    animation: zzzFloat 2.4s ease-in-out infinite;
}
.anim-zzz-2 {
    animation: zzzFloat 2.4s ease-in-out infinite 0.8s;
}
.anim-zzz-3 {
    animation: zzzFloat 2.4s ease-in-out infinite 1.6s;
}

@keyframes zzzFloat {
    0% {
        transform: translate(0, 0) scale(0.6);
        opacity: 0;
    }
    20% {
        opacity: 0.8;
    }
    100% {
        transform: translate(6px, -12px) scale(1.2);
        opacity: 0;
    }
}
</style>
