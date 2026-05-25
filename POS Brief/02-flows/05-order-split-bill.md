# Flow: Split Bill & Gabung Bill

**Role**: Kasir, Supervisor

## Split Bill

```
Kasir buka detail order
  → Klik "Split Bill"
  → Pilih item yang masuk ke bill masing-masing
      → Drag item ke bill 1 / bill 2
      → Atau input nominal split rata
  → Sistem buat 2 order baru dari order asal
  → Masing-masing order diproses pembayaran terpisah
```

## Gabung Bill

```
Kasir pilih 2+ order aktif dari meja yang sama
  → Klik "Gabung Bill"
  → Konfirmasi penggabungan
  → Sistem merge semua item ke 1 order
  → Order asal di-archive
  → Proses pembayaran dari 1 order gabungan
```
