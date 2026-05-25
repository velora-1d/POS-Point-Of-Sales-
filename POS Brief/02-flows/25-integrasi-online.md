# Flow: Integrasi GoFood & GrabFood

**Role**: Kitchen, Bar, Kasir, Owner

## Setup Integrasi

```
Owner buka Pengaturan → Integrasi
  → Connect GoFood / GrabFood
  → Input credentials API platform
  → Map produk POS ke menu platform
  → Aktifkan → order online mulai masuk
```

## Order Online Masuk

```
Customer order via GoFood/GrabFood
  → Webhook diterima sistem
  → Order otomatis masuk ke kitchen display
  → Label platform ditampilkan (GoFood/GrabFood)
  → Flow sama dengan order internal:
      → Kitchen proses → Bar approve → Ready
  → Status update sync ke platform
  → Customer di app platform lihat status real-time
```

## Laporan Order Online

```
Owner buka Laporan → Order Online
  → Filter per platform (GoFood/GrabFood)
  → Revenue per platform
  → Produk terlaris per platform
```
