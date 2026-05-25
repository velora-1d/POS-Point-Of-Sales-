# Flow: Membership & Loyalty

**Role**: Kasir (operasional), Owner/Supervisor (konfigurasi)

## Registrasi Member

```
Kasir / customer (QR meja)
  → Input nomor HP
  → Cek sistem: sudah member atau belum
  → Belum → isi nama (opsional)
  → Akun member dibuat → tier default: Bronze
```

## Akumulasi Poin

```
Transaksi selesai
  → Sistem cek: customer member atau tidak
  → Jika member:
      → Hitung poin dari nominal transaksi (rate diatur admin)
      → Hitung poin dari item tertentu (jika ada)
      → Total poin ditambahkan ke akun member
      → Notif ke customer: "Kamu dapat X poin"
  → Cek threshold tier → naik tier jika memenuhi syarat
      → Notif ke customer: "Selamat, kamu naik ke Silver!"
```

## Redeem Poin

```
Saat transaksi
  → Kasir / customer input nomor HP
  → Sistem tampil saldo poin
  → Pilih redeem:
      → Diskon → sistem hitung potongan otomatis
      → Produk gratis → pilih produk dari katalog redeem
  → Poin terpotong → transaksi dilanjutkan
  → Tampil di struk: poin digunakan & sisa poin
```

## Benefit Tier Otomatis

```
Customer transaksi
  → Sistem detect tier member
  → Benefit tier terapply otomatis:
      → Harga tier (nyambung ke multi harga)
      → Diskon otomatis (nyambung ke promo)
  → Tampil di struk: "Member Gold — diskon 10% applied"
```

## Konfigurasi (Owner)

```
Owner buka menu Membership
  → Edit threshold tier (Bronze/Silver/Gold)
  → Edit rate poin per transaksi
  → Edit rate poin per produk tertentu
  → Edit benefit per tier
  → Kelola katalog redeem produk gratis
  → Lihat statistik member
```
