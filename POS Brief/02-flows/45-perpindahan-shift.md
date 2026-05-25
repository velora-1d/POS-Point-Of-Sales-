# Flow: Perpindahan Shift (Serah Terima Kasir)

**Role**: Kasir, Supervisor

---

## Skenario Perpindahan Shift

```
Kasir A (shift pagi) → serah terima → Kasir B (shift siang)
```

---

## Flow Lengkap

### 1. Kasir A Tutup Shift

```
Kasir A klik "Tutup Shift"
  → Sistem tampil ringkasan shift berjalan:
      → Total order: X transaksi
      → Total revenue: Rp X
      → Breakdown per metode bayar
      → Total kas masuk (cash)
  → Kasir A hitung uang tunai di laci
  → Input jumlah aktual
  → Sistem hitung selisih:
      → Ekspektasi = saldo awal + total cash masuk - cash keluar
      → Aktual = input kasir A
      → Selisih = ekspektasi - aktual
  → Jika selisih > threshold → notif khusus ke supervisor/owner
  → Input catatan serah terima (opsional)
  → Konfirmasi tutup shift
  → Laporan shift A tersimpan & terkunci
  → Notif ke supervisor/owner: "Shift A ditutup oleh {nama}"
```

### 2. Serah Terima Fisik (Offline)

```
Kasir A & B lakukan serah terima fisik:
  → Hitung uang tunai bersama
  → Saldo akhir shift A = saldo awal shift B
  → Catatan serah terima (opsional, bisa difoto)
```

### 3. Kasir B Buka Shift Baru

```
Kasir B login
  → Sistem deteksi tidak ada shift aktif
  → Modal "Buka Shift"
  → Input saldo awal kas (hasil serah terima dari Kasir A)
  → Pilih template shift (Shift Siang, dll)
  → Konfirmasi buka shift
  → Shift B dimulai
  → Notif ke supervisor/owner: "Shift B dibuka oleh {nama}"
```

### 4. Order Aktif yang Belum Selesai

```
Ada order dari shift A yang masih IN PROGRESS saat serah terima
  → Order TIDAK dibatalkan
  → Order otomatis masuk ke shift B
  → Kitchen tetap proses tanpa gangguan
  → Pendapatan dari order tersebut tercatat di shift B
  → Di laporan: ada flag "carry over dari shift sebelumnya"
```

---

## Laporan Perpindahan Shift

```
Supervisor/Owner lihat laporan shift
  → Laporan Shift A: revenue, kas, selisih, waktu tutup
  → Laporan Shift B: saldo awal (carry over), revenue, dst
  → Bisa lihat history serah terima per hari
```

---

## Edge Cases

| Kondisi | Handling |
|---------|---------|
| Kasir A lupa tutup shift | Supervisor bisa force-close shift dari dashboard |
| Kasir B login sebelum A tutup | Tidak bisa buka shift baru, sistem blokir |
| Selisih kas besar | Notif merah ke supervisor/owner, shift tetap bisa ditutup tapi tercatat |
| Listrik mati saat shift aktif | Shift tetap aktif, buka ulang aplikasi → shift resume otomatis |
