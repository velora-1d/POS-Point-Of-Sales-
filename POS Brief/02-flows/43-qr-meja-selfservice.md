# Flow: QR Meja — Customer Self-Service Lengkap

**Role**: Customer (publik, tanpa login)
**Trigger**: Customer scan QR code di meja

---

## Flow Lengkap

### 1. Scan & Identifikasi

```
Customer scan QR meja via kamera HP
  → Browser terbuka: halaman menu outlet
  → Meja terdeteksi otomatis dari QR
  → Modal input data customer:
      → Nama (wajib)
      → Nomor HP (wajib, untuk database & notif)
  → Sistem cek: nomor HP sudah terdaftar?
      → Ya → "Selamat datang kembali, {nama}!" + load poin member
      → Tidak → data disimpan sebagai customer baru
```

### 2. Pilih Menu & Pesan

```
Customer browse katalog menu
  → Filter per kategori
  → Lihat detail produk: nama, deskripsi, foto, harga
  → Pilih varian (jika ada)
  → Input catatan per item (opsional)
  → Tambah ke keranjang
  → Bisa tambah produk lain
  → Lihat keranjang: daftar item, subtotal
  → Sistem tampilkan promo eligible otomatis
  → Total setelah diskon
```

### 3. Checkout & Bayar

```
Customer klik "Pesan & Bayar"
  → Review order terakhir
  → Pilih metode bayar:
      → QRIS → generate QR dinamis → customer scan via e-wallet
      → GoPay / OVO / Dana / ShopeePay → redirect / deep link
  → Customer bayar via HP
  → Payment gateway konfirmasi pembayaran
  → Sistem terima webhook → pembayaran verified
```

### 4. Order Masuk ke Kasir

```
Pembayaran confirmed
  → Order muncul di halaman kasir: "Order Baru (QR Meja X) — LUNAS"
  → Notif bunyi + push notification ke kasir
  → Kasir review detail order
  → Kasir approve → order masuk kitchen display
  → Status: IN PROGRESS
```

### 5. Tracking Status (Customer)

```
Customer lihat halaman status order di HP
  → Real-time update:
      → ✅ Pembayaran diterima
      → 🍳 Sedang dimasak
      → ✅ Siap disajikan
  → Tidak perlu refresh manual (WebSocket)
```

### 6. Selesai

```
Makanan diantar ke meja
  → Kasir update: DELIVERED
  → Status di HP customer: "Pesanan sudah diantar!"
  → Poin loyalty ditambahkan (jika member)
  → Notif ke customer: "Terima kasih! Kamu dapat X poin"
```

---

## Download & Print QR Meja

```
Owner/Supervisor buka menu Meja
  → Pilih meja
  → Klik "QR Code"
  → Pilih aksi:
      → Download PNG → file gambar QR siap dipakai
      → Download PDF → 1 halaman A4 / ukuran custom (siap cetak)
      → Print langsung → kirim ke printer
  → QR berisi URL: https://pos.mentai.id/m/{outletSlug}/{tableCode}
```

---

## Keamanan QR Meja

```
QR code berisi token unik per meja
  → Token tidak expired (permanen selama meja aktif)
  → Jika meja dinonaktifkan → QR otomatis invalid
  → Generate ulang QR → token lama langsung invalid
  → Rate limit: max 10 order per meja per jam (anti-abuse)
```

---

## Pembayaran Gagal / Timeout

```
Customer tidak bayar dalam 10 menit
  → Payment request expired
  → Order tidak masuk ke sistem
  → Customer bisa coba order ulang dari awal
  → Notif: "Waktu pembayaran habis, silakan coba lagi"

Pembayaran gagal (saldo tidak cukup, dll)
  → Notif error dari payment gateway
  → Customer pilih metode lain atau coba ulang
  → Order tidak masuk sampai pembayaran confirmed
```
