# Flow: Profile & Ganti Password

**Role**: Semua role

## Lihat Profile

```
User klik avatar / nama di navbar
  → Halaman profile tampil:
      → Nama, email, nomor HP
      → Role & outlet
      → Tanggal bergabung
      → Sesi aktif (device yang sedang login)
```

## Ganti Password

```
User buka Profile → Ganti Password
  → Input password lama
  → Input password baru (min 8 karakter, huruf + angka)
  → Konfirmasi password baru
  → Simpan
  → Semua sesi lain otomatis di-logout
  → Notif email konfirmasi perubahan password
```

## Ganti PIN Approval

```
User buka Profile → Ganti PIN Approval
  → Input password akun (verifikasi identitas)
  → Input PIN baru (4-6 digit)
  → Konfirmasi PIN baru
  → Simpan
  → PIN langsung aktif untuk approval berikutnya
```

## Kelola Sesi Aktif

```
User buka Profile → Sesi Aktif
  → Lihat semua device yang sedang login:
      → Browser / Tauri desktop
      → Waktu login terakhir
      → IP address
  → Klik "Logout dari device ini" untuk revoke sesi tertentu
  → Klik "Logout semua device" untuk revoke semua sesi
```
