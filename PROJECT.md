# POS Mentai

## Deskripsi
Sistem Point of Sale (POS) untuk Mentai Restaurant dengan fitur multi-outlet, manajemen stok, program loyalitas, kasbon, approval eskalasi, reservasi meja, split bill, resep HPP otomatis, real-time kitchen display, serta integrasi Tauri untuk aplikasi desktop dan WhatsApp API untuk notifikasi.

## Stack Teknologi
- Frontend : Vue 3 (Composition API) + Pinia + TailwindCSS + shadcn/vue + Vite + Tauri
- Backend  : Laravel 13 + Inertia.js (Laravel Sanctum, Horizon + Redis, Reverb)
- Database : PostgreSQL 16
- ORM      : Drizzle ORM (via custom adapter) + Drizzle Kit
- Auth     : Laravel Sanctum
- Hosting  : VPS / Cloud (DigitalOcean/AWS)

## Mode Arsitektur
[ ] Next.js Fullstack
[ ] Laravel 13 API + Next.js
[x] Lainnya: Laravel 13 + Inertia.js (Monolith SPA)

## Target Platform
[x] Web  [ ] Mobile  [x] Lainnya: Desktop (Tauri)

## Multi-tenant
[ ] Ya — strategi: ___
[x] Tidak (Multi-Outlet)

## Skala User
[x] Kecil  [ ] Menengah  [ ] Besar

## Tim
[x] Solo  [ ] Tim — jumlah: ___

## Repo
- Repo = https://github.com/velora-1d/POS-Point-Of-Sales-

## Progress Terakhir
- Perapihan layout global agar margin konten lebih konsisten dan area utama tidak terlalu sempit.
- Sidebar aktif diringkas untuk flow kasir tanpa menghapus menu `coming soon`.
- Kitchen Display System sudah memakai data order dari database, lengkap dengan lane status, timer, dan aksi update status.
- Halaman kasir order kini mendukung flow `pilih meja / takeaway -> customer -> pilih menu -> kirim ke dapur`.
- Integrasi customer pada order kasir ditambahkan: cari customer by HP/nama, pilih member existing, atau quick register dari transaksi.
- Order takeaway kini bisa dibuat tanpa meja, dan kartu order aktif menampilkan label layanan serta data customer lebih jelas.
- Menu #4 `Edit Order (approval flow)` sudah aktif untuk internal: kasir dapat edit order `pending`, dan order `in_progress` membutuhkan PIN approval supervisor/owner sebelum status di-reset ke `pending`.
- Menu #5 `Split Bill / Gabung Bill` sudah aktif: split item ke bill kedua dari detail order aktif, gabung beberapa order aktif dari meja yang sama, serta dukungan multi-bill aktif pada satu meja untuk flow kasir.
- Menu #6 `Pembayaran` sekarang aktif untuk kasir manual: order baru mendukung `bayar nanti`, `bayar sekarang + cash`, dan `bayar sekarang + QRIS gateway`; QRIS memakai state `payment_pending` lalu masuk kitchen setelah webhook `paid`.
- Payment gateway Pakasir sudah disambungkan via config service, checkout URL, endpoint bayar order aktif, dan callback publik `/api/v1/callback/pakasir`; pembayaran settlement untuk order aktif akan auto-complete saat lunas.
- Flow publik `QR meja self-service` sekarang sudah aktif secara minimum: route scan QR publik, input customer, pilih menu dari database, checkout QRIS pay-first, dan halaman status order customer setelah checkout.
- Order `qr_meja` menghitung harga ulang dari database di backend, tidak lagi percaya `unit_price` dari client; order publik baru hanya masuk operasional setelah webhook pembayaran sukses.
- File utama yang diubah: `resources/js/Layouts/AuthenticatedLayout.vue`, `resources/css/app.css`, `resources/js/Pages/Dashboard.vue`, `resources/js/Pages/Kasir/Order.vue`, `resources/js/Pages/Kitchen/Display.vue`, `app/Http/Controllers/OrderController.php`, `app/Http/Controllers/KitchenDisplayController.php`, `app/Http/Requests/UpdateKitchenOrderStatusRequest.php`, `app/Repositories/KitchenDisplayRepository.php`, `app/Services/KitchenDisplayService.php`, `app/Models/Customer.php`, `routes/web.php`.
- File pembayaran baru/diubah: `app/Http/Controllers/PaymentWebhookController.php`, `app/Http/Requests/StoreOrderRequest.php`, `app/Http/Requests/ProcessOrderPaymentRequest.php`, `app/Http/Requests/PakasirWebhookRequest.php`, `app/Services/OrderPaymentService.php`, `app/Services/PakasirService.php`, `config/services.php`.
- File self-service baru: `app/Http/Controllers/QrSelfServiceController.php`, `app/Http/Requests/StoreSelfServiceOrderRequest.php`, `resources/js/Pages/Public/QrOrderMenu.vue`, `resources/js/Pages/Public/QrOrderStatus.vue`.
- Task lanjutan: tambahkan tracking realtime customer, expiry/regen checkout QRIS, dan perluas flow pay-first yang sama ke channel WA/self-service non-meja.

## Last Updated
2026-05-25
