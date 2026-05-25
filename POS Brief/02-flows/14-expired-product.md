# Flow: Reminder Expired Product

**Role**: Owner, Supervisor

## Input Expired saat Stok Masuk

```
Admin input stok baru
  → Isi tanggal expired (wajib jika track_expired = true)
  → Input batch code (opsional)
  → Sistem simpan & mulai track
```

## Sistem Kirim Reminder

```
Sistem cek setiap hari otomatis
  → Ada produk/bahan baku mendekati expired?
      → H-7 → reminder pertama
      → H-3 → reminder kedua
      → H-1 → reminder mendesak
  → Kirim ke: notif app + WA/email owner/supervisor
  → Tampil di dashboard: daftar produk akan expired
```

## Produk Sudah Expired

```
Tanggal expired terlewati
  → Alert merah muncul di dashboard
  → Notif ke owner/supervisor
  → Behavior sesuai setting:
      → "auto_deactivate" → produk langsung nonaktif dari menu
      → "notify_only" → notif saja, admin yang decide
  → Admin konfirmasi tindakan:
      → Nonaktifkan produk
      → Buang stok (catat sebagai kerugian)
      → Override expired (butuh approval + alasan)
```
