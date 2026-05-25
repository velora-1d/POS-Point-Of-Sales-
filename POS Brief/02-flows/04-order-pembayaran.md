# Flow: Pembayaran

**Role**: Kasir, Supervisor
**Trigger**: Order status DELIVERED, customer siap bayar

## Flow Utama

```
Kasir buka detail order
  → Klik "Proses Pembayaran"
  → Sistem tampil total tagihan:
      → Subtotal
      → Diskon (otomatis + manual)
      → Total akhir
  → Pilih metode bayar:
      → Cash → input nominal diterima → hitung kembalian
      → QRIS → generate QR → tunggu konfirmasi
      → Payment gateway → redirect / scan
      → Kasbon → cek limit customer → catat hutang
      → Cicilan → input DP + jadwal cicilan
  → Konfirmasi bayar
  → Status order: COMPLETED
  → Poin loyalty ditambahkan (jika member)
  → Pilih metode struk: print / WA / skip
```

## Split Payment

```
Customer mau bayar dengan 2 metode
  → Kasir klik "Split Payment"
  → Input nominal per metode
      → Misal: Cash Rp 50.000 + QRIS Rp 30.000
  → Total harus sama dengan tagihan
  → Proses masing-masing metode
```

## Refund / Void

```
Kasir klik "Refund" pada transaksi
  → Input alasan
  → Jika nominal > threshold → trigger approval
  → Approver PIN/notif → setujui
  → Uang dikembalikan
  → Stok di-restore
  → Poin loyalty dipotong kembali (jika ada)
```
