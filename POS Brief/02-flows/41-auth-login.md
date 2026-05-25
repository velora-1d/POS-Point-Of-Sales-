# Flow: Login & Logout

**Role**: Semua role

## Login

```
User buka aplikasi
  → Halaman login tampil
  → Input email + password
  → Klik Login
  → Sistem validasi credentials:
      → Salah → notif error, counter gagal bertambah
      → Gagal 5x dalam 10 menit → akun lock 15 menit
  → Berhasil → sistem load:
      → Role & permissions user
      → Outlet yang di-assign
      → Redirect ke halaman sesuai role:
          → Kitchen → Kitchen Display
          → Bar → Bar Display
          → Kasir → Halaman Order
          → Supervisor → Dashboard
          → Owner → Dashboard
```

## Cek Shift saat Login (Kasir)

```
Kasir berhasil login
  → Sistem cek: ada shift aktif?
  → Tidak ada → modal "Buka Shift Dulu"
  → Kasir input saldo awal kas → shift dibuka
  → Baru bisa akses halaman order
```

## Logout

```
User klik Logout
  → Konfirmasi dialog
  → Jika kasir & ada shift aktif → warning "Shift belum ditutup"
      → Pilih: tutup shift dulu / logout paksa
  → Token di-revoke di server
  → Redirect ke halaman login
```

## Session Expired

```
Token expired saat user aktif
  → Sistem coba refresh token otomatis (background)
  → Berhasil → user tidak sadar, lanjut kerja
  → Gagal (refresh token expired) → redirect ke login
  → Pesan: "Sesi kamu sudah berakhir, silakan login kembali"
```
