# Flow: PO / Down Payment Order

**Role**: Kasir, Customer (via QR), Owner/Supervisor (kelola)

## Buat PO/DP

```
Kasir / customer (QR meja) buat order
  → Pilih metode: PO atau DP
  → PO:
      → Tentukan tanggal pengambilan
      → Tidak perlu bayar sekarang
      → Stok produk di-reserve otomatis
  → DP:
      → Input nominal DP (min sesuai setting)
      → Proses pembayaran DP
      → Sistem catat sisa tagihan
  → Jika nominal > threshold → notif ke owner/supervisor
  → Notif konfirmasi ke customer (WA/struk)
```

## Reminder Pickup/Pelunasan

```
Sistem cek jadwal setiap hari
  → H-1 sebelum pickup → reminder ke kasir & owner
  → Hari H → alert di dashboard
```

## Pelunasan

```
Customer datang
  → Kasir input nomor HP / kode PO
  → Sistem tampil detail PO/DP outstanding
  → Customer bayar sisa tagihan (metode bebas)
  → Status PO/DP → LUNAS
  → Stok di-release dari reserved
  → Struk pelunasan dicetak/WA
```

## Batalkan PO/DP

```
Kasir / supervisor batalkan PO
  → Wajib isi alasan
  → Jika sudah ada DP → refund? (butuh approval)
  → Status PO → CANCELLED
  → Stok di-release dari reserved
  → Notif ke customer
```
