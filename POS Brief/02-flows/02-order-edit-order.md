# Flow: Edit Order

**Role**: Kasir, Bar, Supervisor (internal) | Customer via QR meja
**Trigger**: Ada perubahan pesanan sebelum selesai dimasak

## Flow Internal (Kasir/Bar)

```
Kasir/Bar buka detail order aktif
  → Klik "Edit Order"
  → Tambah item / ubah jumlah / hapus item / ubah catatan
  → Jika order sudah IN PROGRESS → butuh approval supervisor
  → Simpan perubahan
  → Status order reset ke PENDING
  → Kitchen display update otomatis
```

## Flow Customer via QR Meja

```
Customer scan QR meja
  → Lihat pesanan aktif
  → Klik "Edit Pesanan" (hanya jika status masih PENDING)
  → Ubah item / jumlah / catatan
  → Submit perubahan
  → Status: PENDING EDIT APPROVAL
  → Notif otomatis ke kasir & bar: "Perubahan pesanan meja X"
  → Kasir/Bar review perubahan
  → Approve → order diupdate, kembali ke PENDING
  → Tolak → order kembali ke versi sebelumnya
  → Notif hasil ke customer
```

## Error Cases

| Kondisi | Handling |
|---------|---------|
| Order sudah IN PROGRESS | Butuh approval supervisor |
| Order sudah READY | Tidak bisa diedit |
| Produk tambahan stok habis | Tidak bisa ditambahkan |
