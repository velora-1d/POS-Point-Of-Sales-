# Flow: Kitchen Display

**Role**: Kitchen, Bar

---

## Layout Tampilan

Grid kartu, auto-sort by prioritas (overdue → warning → normal):

```
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ 🔴 OVERDUE      │ │ 🟡 #002         │ │ 🟢 #003         │
│ #001 Meja 3     │ │ Meja 5 • Andi   │ │ Meja 2 • Budi   │
│ Siti            │ │                 │ │                 │
│ ─────────────── │ │ ─────────────── │ │ ─────────────── │
│ Nasi Goreng x2  │ │ Mie Ayam x1     │ │ Ayam Bakar x1   │
│ Ayam Bakar x1   │ │ Es Teh x2       │ │ Es Jeruk x1     │
│ ─────────────── │ │ ─────────────── │ │ ─────────────── │
│ ⏱ 02:15 lewat  │ │ ⏱ 04:50 sisa   │ │ ⏱ 12:00 sisa   │
│ [Selesai]       │ │ [Mulai Masak]   │ │ [Mulai Masak]   │
└─────────────────┘ └─────────────────┘ └─────────────────┘
```

Setiap kartu menampilkan:
- Nomor order + nama meja + nama pelanggan
- Daftar item yang dipesan + jumlah + catatan
- Timer + status warna
- Tombol aksi (Mulai Masak / Selesai)

---

## Suara Notifikasi

```
Order baru masuk (WebSocket event dari Reverb)
  → Suara notifikasi berbunyi 1x otomatis
  → Web Audio API (browser native, tanpa library)
  → File suara bisa dikustomisasi owner (upload MP3/WAV)
  → Volume diatur di setting kitchen display
  → Tidak ada suara untuk event lain (tidak mengganggu)
```

---

## Timer — 2 Status

### Timer 1: Waiting Timer (PENDING)
```
Order masuk → timer PENDING mulai otomatis
  → Hitung berapa lama order belum diproses kitchen
  → Tampil di kartu: "⏳ Menunggu X menit"
  → Threshold diatur owner (default: 3 menit)
  → Lewat threshold → border kartu jadi KUNING
```

### Timer 2: Cooking Timer (IN PROGRESS)
```
Kitchen klik "Mulai Masak"
  → Timer PENDING berhenti
  → Cooking timer mulai countdown dari estimasi waktu
  → Estimasi waktu bisa dari:
      → Kasir input manual saat buat order
      → Atau default per kategori (diatur owner di setting)
  → Tampil di kartu: "⏱ X:XX sisa"
```

---

## Alert Rules (Tidak Mengganggu Kartu Lain)

| Kondisi | Visual | Suara |
|---------|--------|-------|
| Order baru masuk | Kartu baru muncul di grid | 🔔 Bunyi 1x |
| Waiting > threshold (default 3 menit) | Border kartu 🟡 kuning | ❌ Tidak ada |
| Cooking timer < 2 menit sisa | Border kartu 🔴 merah + kedip subtle | ❌ Tidak ada |
| Cooking timer = 0 (overdue) | Kartu pindah pojok kiri atas + label OVERDUE + border merah solid | 🔔 Bunyi 1x |

Kedip subtle = animasi CSS pulse pelan, tidak ganggu baca konten kartu lain.

---

## Auto-Sort Kartu

```
Urutan kartu otomatis:
  1. OVERDUE (paling lama lewat estimasi)
  2. IN PROGRESS warning (< 2 menit sisa)
  3. IN PROGRESS normal (masih banyak waktu)
  4. PENDING warning (waiting > threshold)
  5. PENDING normal (baru masuk)
```

---

## Update Status Masak

```
Kitchen pilih kartu order
  → Klik "Mulai Masak"
  → Status: IN PROGRESS
  → Cooking timer mulai countdown
  → Kartu update real-time

Kitchen selesai masak
  → Klik "Selesai"
  → Status: WAITING BAR APPROVAL
  → Kartu pindah ke section Bar (jika bar approval aktif)
  → Notif ke bar
```

---

## Bar Approval

```
Bar terima notif order selesai
  → Review kartu order di display
  → Klik "Approve" → status: READY
  → Klik "Kembalikan" → status: IN PROGRESS (ada masalah)
  → Notif ke kasir & customer
```

---

## Filter & Setting Kitchen Display

```
Kitchen bisa filter tampilan:
  → Per kategori (makanan / minuman / semua)
  → Per status (pending / in progress / semua)

Owner setting kitchen display:
  → Threshold waiting alert (default: 3 menit)
  → Threshold cooking warning (default: 2 menit sisa)
  → Default estimasi waktu per kategori produk
  → Upload file suara notifikasi
  → Setting volume
```

---

## Order Online (GoFood/GrabFood)

```
Order online masuk
  → Tampil di grid dengan label badge platform (GoFood/GrabFood)
  → Flow timer & alert sama dengan order internal
  → Status update sync ke platform secara otomatis
```

---

## Implementasi Teknis

```
Real-time: Laravel Reverb WebSocket
  → Channel: outlet.{outletId}.kitchen
  → Event: OrderCreated, OrderStatusChanged

Timer: Vue 3 composable (useOrderTimer)
  → setInterval per kartu (1 detik)
  → Reactive countdown
  → Auto cleanup saat kartu dihapus dari grid

Suara: Web Audio API
  → AudioContext.decodeAudioData()
  → Tidak perlu library tambahan
  → Fallback: HTML5 Audio element

Auto-sort: Vue computed property
  → Re-sort otomatis setiap ada perubahan status/timer
```
