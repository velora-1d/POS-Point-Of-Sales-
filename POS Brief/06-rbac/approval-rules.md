# RBAC — Approval Rules

Daftar aksi yang membutuhkan approval, siapa approver-nya, dan mekanismenya.

---

## Tabel Approval Rules Default

| Aksi | Trigger Kondisi | Approver | Mekanisme | Eskalasi |
|------|----------------|----------|-----------|----------|
| Diskon manual | Di atas threshold (default: 20%) | Supervisor | PIN / Notif | Owner (10 menit) |
| Refund transaksi | Semua refund | Supervisor | PIN / Notif | Owner (10 menit) |
| Void transaksi | Semua void | Owner | Notif | - |
| Batalkan order aktif | Status IN_PROGRESS ke atas | Supervisor | PIN / Notif | Owner (10 menit) |
| Override harga produk | Harga lebih murah dari base | Supervisor | PIN | Owner (10 menit) |
| Buat kasbon | Di atas threshold (default: Rp 500.000) | Supervisor | Notif | Owner (10 menit) |
| Write-off kasbon | Semua | Owner | Notif | - |
| Batalkan PO/DP + refund DP | Ada DP yang harus direfund | Supervisor | Notif | Owner (10 menit) |
| Stock write-off | Semua | Supervisor | Notif | Owner (10 menit) |
| Override expired product | Perpanjang tanggal expired | Owner | PIN | - |
| Edit order sudah IN_PROGRESS | Status IN_PROGRESS | Supervisor | PIN / Notif | Owner (10 menit) |

---

## Mekanisme Detail

### PIN (Approver di Tempat)
```
Layar approval tampil di kasir
  → Approver datang ke perangkat kasir
  → Input PIN 4-6 digit
  → Sistem validasi: PIN cocok + role sesuai?
  → Valid → aksi dilanjutkan
  → Tidak valid (3x) → lock, harus notif manual ke owner
```

### Notif (Approver Remote)
```
Sistem kirim notif ke approver
  → Channel: app push notification + WA
  → Isi notif: jenis aksi, nominal, kasir yang request, outlet
  → Approver buka notif → tampil detail
  → Klik Approve atau Tolak
  → Jika Tolak → wajib isi alasan
  → Kasir dapat notif hasil real-time
```

### Eskalasi
```
Notif terkirim ke approver (Supervisor)
  → Tidak ada respons dalam X menit (default: 10 menit)
  → Sistem kirim ulang notif ke Owner
  → Jika Owner tidak respons dalam 10 menit lagi
  → Alert merah di dashboard Owner
  → Aksi tetap pending sampai ada keputusan
```

---

## Threshold yang Bisa Dikonfigurasi Owner

| Setting | Default | Keterangan |
|---------|---------|------------|
| `discount_approval_threshold` | 20% | Persen diskon manual yang trigger approval |
| `kasbon_approval_threshold` | Rp 500.000 | Nominal kasbon yang trigger approval |
| `po_approval_threshold` | Rp 1.000.000 | Nominal PO yang trigger approval |
| `escalation_minutes` | 10 menit | Waktu sebelum eskalasi ke level atas |
| `pin_max_attempts` | 3 kali | Maksimal salah PIN sebelum lock |

---

## Audit Trail

Semua approval dicatat permanen di tabel `approvals`:
- Jenis aksi
- User yang request
- User yang approve/tolak
- Waktu request & resolusi
- Alasan penolakan
- Reference ke transaksi terkait

**Tidak bisa dihapus atau diubah setelah dicatat.**
