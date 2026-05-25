# Flow: Kasbon & Cicilan

**Role**: Kasir (buat), Owner/Supervisor (kelola)

## Buat Kasbon

```
Transaksi selesai → Kasir pilih metode bayar: Kasbon
  → Input nomor HP customer
  → Cek: customer terdaftar? (daftar dulu jika belum)
  → Cek saldo kasbon: sudah melebihi limit? → tolak/eskalasi
  → Total transaksi masuk sebagai hutang
  → Tentukan jatuh tempo
  → Jika nominal > threshold → trigger approval
  → Kasbon tercatat → notif ke owner/supervisor
```

## Buat Cicilan

```
Kasir pilih metode bayar: Cicilan
  → Input DP (minimal sesuai setting admin)
  → Sistem hitung sisa tagihan
  → Tentukan jumlah cicilan & interval
  → Jadwal cicilan otomatis dibuat sistem
  → Jika nominal > threshold → trigger approval
```

## Bayar Kasbon/Cicilan

```
Customer datang bayar
  → Kasir input nomor HP
  → Sistem tampil daftar kasbon/cicilan outstanding
  → Pilih tagihan yang dibayar
  → Input nominal bayar
  → Saldo hutang berkurang otomatis
  → Struk pembayaran dicetak/WA
```

## Reminder Jatuh Tempo

```
Sistem cek jadwal setiap hari
  → Ada kasbon/cicilan mendekati jatuh tempo?
  → Kirim reminder ke owner/kasir (notif app + WA/email)
  → Jika sudah lewat jatuh tempo → alert merah di dashboard
```
