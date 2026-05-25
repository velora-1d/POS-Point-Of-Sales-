# Flow: Setting RBAC & User Management

**Role**: Owner only

## Kelola Role

```
Owner buka Pengaturan → Role & Akses
  → Lihat semua role yang ada
  → Buat role custom (misal: "Kasir Senior")
  → Set base role type: kasir/bar/kitchen/supervisor
  → Assign permissions per role
  → Simpan
```

## Assign Role ke Karyawan

```
Owner buka Karyawan → Pilih karyawan
  → Ubah role
  → Konfirmasi → akses langsung berubah
  → Karyawan perlu logout & login ulang
```

## Approval Rules Setting

```
Owner buka Pengaturan → Approval Rules
  → Pilih jenis transaksi
  → Set: butuh approval / tidak
  → Set approver role
  → Set mekanisme (PIN / notif / keduanya)
  → Set threshold nominal
  → Set waktu eskalasi
  → Simpan
```
