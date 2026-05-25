# Flow: Diskon Otomatis & Template Promo

**Role**: Kasir (apply), Owner (konfigurasi)

## Buat Template Promo (Owner)

```
Owner buka menu Promo → Buat Promo Baru
  → Isi detail:
      → Nama promo
      → Jenis: persen / nominal / buy X get Y
      → Trigger: produk / kategori / total transaksi / jam / metode bayar / tier member
      → Periode berlaku
      → Batas penggunaan (opsional)
      → Bisa stack dengan promo lain?
  → Simpan → aktif sesuai jadwal
```

## Diskon Otomatis saat Transaksi

```
Kasir input order
  → Sistem scan semua promo aktif
  → Cek kondisi terpenuhi:
      → Produk/kategori cocok? → apply
      → Total transaksi memenuhi threshold? → apply
      → Jam sekarang masuk happy hour? → apply
      → Metode bayar cocok? → apply setelah bayar
      → Tier member cocok? → apply
  → Semua promo yang trigger tampil di layar kasir
  → Total diskon dihitung otomatis
```

## Diskon Manual (Kasir Input Kode)

```
Kasir input kode voucher
  → Sistem validasi: kode valid? masih berlaku? belum habis limit?
  → Valid → diskon terapply
  → Jika diskon di atas threshold → trigger approval
  → Tidak valid → notif error ke kasir
```

## Stacking Rules

```
Jika ada 2+ promo eligible:
  → Cek masing-masing promo: can_stack = true/false
  → Jika semua bisa stack → apply semua
  → Jika ada yang tidak bisa stack → apply yang nilai terbesar
  → Tampilkan detail diskon per promo di struk
```
