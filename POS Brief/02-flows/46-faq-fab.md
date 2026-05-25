# Flow: FAQ & Bantuan (FAB)

**Role**: Semua role
**Trigger**: Klik FAB (?) di pojok kanan bawah layar

---

## Behavior

```
FAB button muncul di semua halaman (global component)
  → Fixed position, pojok kanan bawah
  → Z-index di atas semua konten
  → Klik FAB → panel slide-in dari kanan
  → Panel otomatis load konten sesuai halaman aktif (context-aware)
  → Klik ✕ atau area luar panel → panel slide-out tutup
```

## Struktur Panel

```
┌─────────────────────────────┐
│  📖 FAQ — {Nama Menu}    ✕  │
│─────────────────────────────│
│ [📋 Penjelasan] [🔄 Penggunaan] │
│ [🔗 Relasi]    [🎧 Support]  │
│─────────────────────────────│
│                             │
│  KONTEN TAB AKTIF           │
│  (scrollable)               │
│                             │
└─────────────────────────────┘
```

## Tab 1 — Penjelasan
Deskripsi singkat menu yang sedang dibuka:
- Apa fungsi menu ini
- Siapa yang bisa akses
- Fitur utama yang tersedia

## Tab 2 — Penggunaan
Step-by-step cara pakai menu aktif:
- Numbered steps
- Singkat & jelas
- Sesuai flow yang sudah didokumentasikan

## Tab 3 — Relasi Menu
Daftar menu lain yang terhubung:
- Nama menu + icon
- Keterangan singkat relasinya
- Bisa klik untuk navigasi ke menu tersebut

## Tab 4 — Support
```
🎧 Butuh Bantuan?

Tim developer siap membantu
Senin–Sabtu, 09.00–17.00 WIB

┌─────────────────────────┐
│  💬 Chat via WhatsApp   │
└─────────────────────────┘
      085117776496

Respon dalam 1×24 jam
```

## Context Mapping (Halaman → Konten FAQ)

| Halaman | Judul FAQ |
|---------|-----------|
| /orders | Order & Transaksi |
| /kitchen | Kitchen Display |
| /products | Produk & Stok |
| /customers | Customer Database |
| /membership | Membership & Loyalty |
| /promos | Diskon & Promo |
| /kasbon | Kasbon & Cicilan |
| /po-orders | PO / Down Payment |
| /shifts | Manajemen Shift |
| /attendance | Absensi |
| /reports/* | Laporan & ERP |
| /tables | Manajemen Meja |
| /reservations | Reservasi |
| /employees | Karyawan |
| /settings/* | Pengaturan |
