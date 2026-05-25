# Flow: Approval Transaksi

**Role**: Kasir (request), Supervisor/Owner (approve)

## Trigger Approval

```
Kasir melakukan aksi yang butuh approval:
  → Refund/void transaksi
  → Diskon manual di atas threshold
  → Pembatalan order aktif (IN PROGRESS)
  → Override harga produk
  → Kasbon/cicilan di atas limit
  → Edit pesanan customer
  → PO/DP nominal besar
  → Sistem otomatis detect → tampil "Butuh Approval"
```

## Mekanisme PIN (Approver di Tempat)

```
Layar approval muncul di kasir
  → Approver datang ke kasir
  → Input PIN approval
  → Sistem validasi PIN + role
  → Valid → aksi dilanjutkan
  → Tidak valid → tolak
```

## Mekanisme Notif (Approver Remote)

```
Sistem kirim notif ke approver (app + WA)
  → Detail aksi yang butuh approval
  → Approver review → Approve / Tolak + alasan
  → Kasir dapat notif hasil real-time
  → Tidak ada respons dalam X menit → eskalasi ke level atas
```

## Log Approval

```
Semua approval tercatat:
  → Jenis aksi, siapa request, siapa approve/tolak
  → Waktu, alasan penolakan
  → Tidak bisa dihapus / diubah (audit trail)
```
