# Flow: Status Order Workflow

**Role**: Kitchen, Bar, Kasir, Supervisor, Owner, Customer (read)
**Trigger**: Order masuk dari kasir atau QR meja

## Alur Status Default

```
PENDING → IN PROGRESS → WAITING BAR APPROVAL → READY → DELIVERED → COMPLETED
```

## Detail Per Status

```
PENDING
  → Tampil di kitchen display
  → Notif bunyi kitchen & bar
  → Customer bisa lihat via QR

IN PROGRESS
  → Kitchen klik "Mulai Masak"
  → Timer estimasi berjalan
  → Tidak bisa diedit customer

WAITING BAR APPROVAL
  → Kitchen klik "Selesai"
  → Notif ke bar
  → Bar cek & approve

READY
  → Bar approve
  → Notif ke kasir & customer: "Pesanan siap!"

DELIVERED
  → Kasir konfirmasi sudah diantar ke meja

COMPLETED
  → Setelah pembayaran selesai
```

## Pembatalan Order

```
Kasir / supervisor klik batalkan order
  → Wajib isi alasan
  → Jika IN PROGRESS → butuh approval supervisor
  → Status: CANCELLED
  → Stok di-restore otomatis
  → Tercatat di log pembatalan
```

## Kustomisasi Workflow (per Outlet)

```
Owner buka Pengaturan → Status Order
  → Edit nama & urutan tahapan
  → Toggle bar approval (aktif/tidak)
  → Toggle customer bisa lihat status
  → Toggle customer bisa edit pesanan
```
