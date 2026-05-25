# Flow: Laporan Penjualan

**Role**: Owner, Supervisor

## Akses Laporan

```
Owner buka menu Laporan → Penjualan
  → Pilih periode: harian / mingguan / bulanan / custom
  → Filter opsional: outlet, kasir, kategori, produk
  → Klik Generate
```

## Tampilan Laporan

```
Laporan menampilkan:
  → Total revenue
  → Total order
  → Rata-rata nilai order
  → Total diskon diberikan
  → Net revenue (revenue - diskon)
  → Chart tren per hari/minggu/bulan
  → Breakdown per metode bayar (cash, QRIS, e-wallet)
  → Top 10 produk terlaris
  → Perbandingan vs periode sebelumnya
```

## Drill Down

```
Owner klik tanggal tertentu di chart
  → Lihat detail transaksi hari itu
  → Filter per kasir / outlet
  → Klik transaksi → detail order lengkap
```

## Export

```
Klik Export → pilih PDF atau Excel
  → Sistem generate file (background job)
  → Notif saat file siap → download
  → File tersedia 24 jam
```
