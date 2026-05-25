# Flow: Customer Database

**Role**: Kasir, Supervisor, Owner

## Tambah Customer

```
Kasir input nomor HP
  → Sistem cek: sudah ada atau belum
  → Belum ada → form singkat: nama, HP, email (opsional)
  → Simpan → customer terdaftar
```

## Lihat Profil Customer

```
Kasir search by HP/nama
  → Lihat: data diri, riwayat transaksi, membership, kasbon outstanding
```

## Customer Database (Owner)

```
Owner buka Customer Database
  → List semua customer
  → Filter: tier member, ada kasbon, periode join
  → Export daftar customer
  → Lihat analitik: customer baru vs returning, rata-rata spending
```
