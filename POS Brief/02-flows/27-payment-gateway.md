# Flow: Payment Gateway & QRIS

**Role**: Kasir (proses), Owner (konfigurasi)

## Setup Payment Gateway

```
Owner buka Pengaturan → Payment Gateway
  → Pilih provider (Midtrans / Xendit / dll)
  → Input API key + secret
  → Setup webhook URL
  → Test koneksi
  → Aktifkan metode bayar yang didukung
```

## Proses QRIS

```
Kasir pilih metode: QRIS
  → Sistem generate QR dinamis (via payment gateway)
  → Customer scan → bayar via e-wallet/bank
  → Payment gateway kirim webhook konfirmasi
  → Sistem terima → status pembayaran: PAID
  → Order selesai otomatis
```

## Proses E-Wallet (GoPay, OVO, dll)

```
Kasir pilih metode e-wallet
  → Input nomor HP customer
  → Kirim payment request
  → Customer terima notif di e-wallet
  → Customer approve → konfirmasi otomatis
```

## Timeout & Gagal Bayar

```
QR/request tidak dibayar dalam X menit
  → Status expired
  → Kasir bisa generate ulang
  → Order tetap aktif, tidak otomatis dibatalkan
```
