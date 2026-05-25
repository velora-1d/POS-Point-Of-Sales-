# Components

## FaqFab.vue

Komponen FAQ floating action button. Taruh di layout utama agar muncul di semua halaman.

### Cara Pakai

```vue
<!-- resources/js/Layouts/AppLayout.vue -->
<template>
  <div>
    <slot />
    <!-- FAQ FAB — global, muncul di semua halaman -->
    <FaqFab />
  </div>
</template>

<script setup>
import FaqFab from '@/Components/FaqFab.vue'
</script>
```

### Tambah Konten FAQ Menu Baru

Di dalam `FaqFab.vue`, tambahkan entry baru di object `faqContent`:

```js
'/nama-route': {
  title: 'Nama Menu',
  description: 'Deskripsi singkat menu ini.',
  access: ['Role 1', 'Role 2'],
  features: ['Fitur 1', 'Fitur 2'],
  steps: ['Langkah 1', 'Langkah 2'],
  relations: [
    { icon: '🛒', menu: 'Nama Menu Lain', description: 'Keterangan relasi' },
  ],
},
```
