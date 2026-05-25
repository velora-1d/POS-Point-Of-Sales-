# Flow: Buat Order Baru

**Role**: Kasir, Supervisor, Owner
**Trigger**: Kasir input manual ATAU customer scan QR meja

---

## Flow 1 — Order via Kasir (Bayar Sekarang)

```
Kasir buka halaman order baru
  → Pilih meja / takeaway
  → Cek customer: input nomor HP (opsional)
      → Jika member → harga tier otomatis terapply
  → Pilih produk + varian + catatan + jumlah
  → Sistem cek promo eligible → tampilkan diskon
  → Review order + total
  → Pilih "Bayar Sekarang"
  → Proses pembayaran: cash / QRIS / gateway / kasbon / cicilan
  → Pembayaran confirmed
  → Order masuk kitchen display
  → Status: IN PROGRESS
```

## Flow 2 — Order via Kasir (Bayar Nanti)

```
Kasir buat order (sama seperti di atas)
  → Pilih "Bayar Nanti"
  → Order langsung masuk kitchen display
  → Status: PENDING → IN PROGRESS
  → Kitchen proses → Bar approve → READY → DELIVERED
  → Saat DELIVERED → kasir proses pembayaran
  → Pembayaran confirmed → Status: COMPLETED
```

## Flow 3 — Order via QR Meja (Customer Self-Service)

```
Customer scan QR meja
  → Halaman menu outlet terbuka di HP customer
  → Input data: nama + nomor HP (wajib, untuk database)
      → Jika nomor HP sudah terdaftar → load profil member otomatis
  → Pilih produk + varian + catatan + jumlah
  → Review order + total
  → Sistem cek promo eligible → tampilkan diskon
  → Pilih metode bayar: QRIS / e-wallet (payment gateway)
  → Bayar via HP
  → Pembayaran confirmed oleh payment gateway
  → BARU order masuk ke kasir sebagai "Order Confirmed"
  → Notif ke kasir: "Ada order baru dari meja X — sudah dibayar"
  → Kasir review & approve
  → Order masuk kitchen display
  → Kitchen proses → Bar approve → READY
  → Customer lihat status real-time via HP (QR meja)
```

## Perbedaan Utama 2 Channel

| | Order via Kasir | Order via QR Meja |
|--|----------------|-------------------|
| Bayar | Sekarang atau nanti | Wajib bayar dulu |
| Approval | Tidak perlu | Kasir approve |
| Data customer | Opsional | Wajib nama + HP |
| Metode bayar | Semua metode | Payment gateway only |

## Error Cases

| Kondisi | Handling |
|---------|---------|
| Stok habis | Produk disabled, tidak bisa dipilih |
| Meja sudah occupied | Warning, kasir bisa override |
| Shift belum dibuka | Redirect ke buka shift dulu |
| Produk expired | Produk tidak tampil di menu |
| Pembayaran gateway timeout | Notif ke customer, order tidak masuk |
| Pembayaran gagal | Customer coba ulang, order belum masuk |
