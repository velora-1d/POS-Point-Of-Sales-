# Flow: Multi Harga & Grosir

**Role**: Owner (setup), Kasir (apply)

## Setup Harga (Owner)

```
Owner buka Produk → Pilih produk → Harga
  → Tambah price tier:
      → Nama tier (Normal, Member, Grosir, custom)
      → Harga
      → Berlaku di outlet mana (semua / tertentu)
      → Happy hour: jam mulai & selesai (opsional)
  → Simpan
```

## Apply Harga saat Transaksi

```
Kasir buat order
  → Sistem detect customer:
      → Ada profil member? → apply harga tier member otomatis
      → Tidak ada → apply harga normal
  → Sistem cek waktu:
      → Masuk happy hour? → apply harga happy hour
  → Kasir bisa override manual:
      → Pilih tier harga
      → Jika override ke harga lebih murah → butuh approval
```

## Harga Per Outlet

```
Sistem detect outlet kasir login
  → Load harga yang berlaku untuk outlet tersebut
  → Otomatis, tidak perlu setting manual kasir
```
