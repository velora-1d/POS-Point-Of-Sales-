# Flow: Stok & Inventori

**Role**: Owner, Supervisor

## Tambah Stok Masuk

```
Supervisor buka menu Stok
  → Pilih produk / bahan baku
  → Input jumlah masuk
  → Input tanggal expired (jika track_expired)
  → Input batch code (opsional)
  → Simpan → stok bertambah, log tercatat
```

## Stock Opname

```
Owner/Supervisor lakukan opname
  → Buka menu Stok
  → Input jumlah aktual per produk/bahan baku
  → Sistem bandingkan dengan stok di sistem
  → Tampilkan selisih
  → Konfirmasi adjustment → stok diupdate, log tercatat
```

## Alert Stok Menipis

```
Stok produk/bahan baku <= minimum_stock
  → Alert muncul di dashboard
  → Notif ke owner/supervisor
```

## Stok Otomatis Berkurang

```
Order selesai (COMPLETED)
  → Stok produk berkurang sesuai qty terjual
  → Bahan baku berkurang sesuai resep
  → Log mutasi tercatat otomatis
```
