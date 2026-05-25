# Flow: Notifikasi & Alert

**Role**: Semua (sesuai jenis notif)

## Jenis Notifikasi

| Notifikasi | Penerima | Channel |
|------------|----------|---------|
| Order baru masuk | Kitchen, Bar | App + bunyi |
| Edit pesanan customer | Kasir, Bar | App |
| Order siap (READY) | Kasir, Customer | App + WA |
| Approval request | Supervisor, Owner | App + WA |
| Hasil approval | Kasir | App |
| Stok menipis | Owner, Supervisor | App + WA |
| Produk akan expired | Owner, Supervisor | App + WA + Email |
| Kasbon jatuh tempo | Owner, Kasir | App + WA |
| PO pickup reminder | Owner, Kasir | App + WA |
| Shift ditutup | Owner, Supervisor | App |
| Selisih kas shift | Owner, Supervisor | App + WA |

## Setting Notifikasi

```
Owner buka Pengaturan → Notifikasi
  → Toggle per jenis notifikasi
  → Pilih channel (app / WA / email)
  → Set siapa yang terima per jenis notif
```
