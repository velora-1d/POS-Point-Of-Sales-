# Flow: Manajemen Shift Kasir

**Role**: Kasir, Supervisor

## Buka Shift

```
Kasir / supervisor login
  → Sistem deteksi belum ada shift aktif
  → Wajib buka shift sebelum transaksi
  → Input saldo awal kas (uang tunai di laci)
  → Pilih template shift (opsional)
  → Shift dimulai → tercatat: siapa, jam mulai, saldo awal
```

## Ringkasan Shift Berjalan

```
Kasir klik "Ringkasan Shift"
  → Lihat real-time:
      → Total transaksi
      → Total revenue
      → Breakdown per metode bayar
      → Jumlah order
```

## Tutup Shift

```
Kasir / supervisor tutup shift
  → Hitung uang tunai aktual di laci
  → Input jumlah aktual
  → Sistem hitung selisih:
      → Ekspektasi = saldo awal + total cash masuk - cash keluar
      → Selisih = ekspektasi - aktual
  → Jika selisih > threshold → notif khusus ke owner/supervisor
  → Shift ditutup → notif otomatis ke owner/supervisor
  → Laporan shift tersimpan
```

## Pergantian Shift

```
Kasir A tutup shift
  → Laporan shift A tersimpan
  → Kasir B login → buka shift baru
  → Input saldo awal (bisa carry over dari shift sebelumnya)
```
