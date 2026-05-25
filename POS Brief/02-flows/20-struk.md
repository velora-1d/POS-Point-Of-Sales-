# Flow: Struk & Printer

**Role**: Kasir

## Pilih Metode Struk

```
Transaksi selesai
  → Sistem tanya metode struk:
      → Print fisik → kirim ke printer thermal/dot matrix
      → Kirim WA → konfirmasi nomor HP customer
      → Skip → tidak ada struk
  → Default behavior sesuai setting outlet
```

## Isi Struk

```
Struk berisi:
  → Nama outlet + alamat
  → Nomor order + tanggal/jam
  → Daftar item + qty + harga
  → Varian / catatan per item
  → Subtotal
  → Diskon (detail per promo)
  → Total
  → Metode bayar + nominal diterima + kembalian
  → Poin loyalty earned (jika member)
  → Saldo poin sekarang (jika member)
  → Kasir + shift
```

## Reprint Struk

```
Kasir buka Riwayat Transaksi
  → Pilih transaksi
  → Klik Reprint
  → Pilih metode: print / WA
```
