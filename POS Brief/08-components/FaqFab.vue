<template>
  <!-- FAB Button -->
  <button
    @click="togglePanel"
    class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-primary text-white shadow-lg flex items-center justify-center hover:bg-primary/90 transition-all"
  >
    <span class="text-2xl font-bold">?</span>
  </button>

  <!-- Overlay -->
  <Transition name="fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 bg-black/30 z-40"
      @click="closePanel"
    />
  </Transition>

  <!-- Slide-in Panel -->
  <Transition name="slide-right">
    <div
      v-if="isOpen"
      class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 flex flex-col"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <div class="flex items-center gap-2">
          <span class="text-lg">📖</span>
          <h2 class="font-semibold text-gray-800">FAQ — {{ currentFaq.title }}</h2>
        </div>
        <button @click="closePanel" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
      </div>

      <!-- Tab Buttons (di atas konten) -->
      <div class="grid grid-cols-4 gap-1 px-4 py-3 border-b bg-gray-50">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'flex flex-col items-center gap-1 py-2 px-1 rounded-lg text-xs font-medium transition-all',
            activeTab === tab.key
              ? 'bg-primary text-white'
              : 'text-gray-500 hover:bg-gray-200'
          ]"
        >
          <span class="text-base">{{ tab.icon }}</span>
          <span>{{ tab.label }}</span>
        </button>
      </div>

      <!-- Tab Content (scrollable) -->
      <div class="flex-1 overflow-y-auto px-5 py-4">

        <!-- Tab: Penjelasan -->
        <div v-if="activeTab === 'penjelasan'">
          <h3 class="font-semibold text-gray-800 mb-3">{{ currentFaq.title }}</h3>
          <p class="text-gray-600 text-sm leading-relaxed mb-4">{{ currentFaq.description }}</p>
          <div v-if="currentFaq.access?.length" class="mb-4">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Dapat Diakses Oleh</p>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="role in currentFaq.access"
                :key="role"
                class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full"
              >{{ role }}</span>
            </div>
          </div>
          <div v-if="currentFaq.features?.length">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Fitur Utama</p>
            <ul class="space-y-2">
              <li
                v-for="feature in currentFaq.features"
                :key="feature"
                class="flex gap-2 text-sm text-gray-600"
              >
                <span class="text-primary mt-0.5">•</span>
                <span>{{ feature }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Tab: Penggunaan -->
        <div v-if="activeTab === 'penggunaan'">
          <h3 class="font-semibold text-gray-800 mb-3">Cara Penggunaan</h3>
          <ol class="space-y-3">
            <li
              v-for="(step, index) in currentFaq.steps"
              :key="index"
              class="flex gap-3"
            >
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center font-bold">
                {{ index + 1 }}
              </span>
              <span class="text-sm text-gray-600 leading-relaxed">{{ step }}</span>
            </li>
          </ol>
        </div>

        <!-- Tab: Relasi Menu -->
        <div v-if="activeTab === 'relasi'">
          <h3 class="font-semibold text-gray-800 mb-3">Terhubung dengan Menu</h3>
          <div v-if="currentFaq.relations?.length" class="space-y-3">
            <div
              v-for="relation in currentFaq.relations"
              :key="relation.menu"
              class="flex gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50"
            >
              <span class="text-xl">{{ relation.icon }}</span>
              <div>
                <p class="text-sm font-medium text-gray-800">{{ relation.menu }}</p>
                <p class="text-xs text-gray-500">{{ relation.description }}</p>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400">Tidak ada relasi menu untuk halaman ini.</p>
        </div>

        <!-- Tab: Support -->
        <div v-if="activeTab === 'support'" class="text-center py-4">
          <div class="text-5xl mb-4">🎧</div>
          <h3 class="font-semibold text-gray-800 mb-2">Butuh Bantuan?</h3>
          <p class="text-sm text-gray-500 mb-1">Tim developer siap membantu</p>
          <p class="text-xs text-gray-400 mb-6">Senin–Sabtu, 09.00–17.00 WIB</p>
          <a
            href="https://wa.me/6285117776496"
            target="_blank"
            class="flex items-center justify-center gap-3 w-full py-3 px-4 bg-green-500 hover:bg-green-600 text-white rounded-xl font-medium transition-all mb-4"
          >
            <span class="text-xl">💬</span>
            Chat via WhatsApp
          </a>
          <p class="text-sm font-medium text-gray-700">085117776496</p>
          <p class="text-xs text-gray-400 mt-2">Respon dalam 1×24 jam</p>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { faqContent, defaultFaq } from './faq-content.js'

const isOpen = ref(false)
const activeTab = ref('penjelasan')

const tabs = [
  { key: 'penjelasan', icon: '📋', label: 'Penjelasan' },
  { key: 'penggunaan', icon: '🔄', label: 'Penggunaan' },
  { key: 'relasi',     icon: '🔗', label: 'Relasi' },
  { key: 'support',    icon: '🎧', label: 'Support' },
]

const togglePanel = () => {
  isOpen.value = !isOpen.value
  activeTab.value = 'penjelasan'
}

const closePanel = () => {
  isOpen.value = false
}

const page = usePage()
const currentRoute = computed(() => page.url)

const currentFaq = computed(() => {
  const url = currentRoute.value
  for (const [route, content] of Object.entries(faqContent)) {
    if (url.startsWith(route)) return content
  }
  return defaultFaq
})
</script>

<style scoped>
.slide-right-enter-active,
.slide-right-leave-active {
  transition: transform 0.3s ease;
}
.slide-right-enter-from,
.slide-right-leave-to {
  transform: translateX(100%);
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
