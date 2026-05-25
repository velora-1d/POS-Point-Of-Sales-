# Flow: Reservasi Meja

**Role**: Kasir, Supervisor, Owner

## Buat Reservasi

```
Kasir buka menu Reservasi
  → Klik "Reservasi Baru"
  → Input data:
      → Nama & nomor HP customer
      → Tanggal & jam reservasi
      → Jumlah tamu
      → Pilih meja (opsional, bisa assign nanti)
      → Catatan (anniversary, alergi, dll)
  → Status: PENDING
  → Notif konfirmasi ke customer via WA (opsional)
```

## Hari H

```
Customer datang
  → Kasir cari reservasi by nama/HP
  → Klik "Customer Tiba"
  → Status: ARRIVED
  → Assign meja → status meja: OCCUPIED
  → Kasir buat order baru linked ke reservasi
```

## Pembatalan

```
Customer cancel atau no-show
  → Kasir update status: CANCELLED / NO_SHOW
  → Meja kembali: AVAILABLE
```
