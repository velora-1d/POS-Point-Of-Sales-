# 🎯 Master Prompt — UI/UX Design System: POS Kasir (63 Menu)

> **Gunakan prompt ini sebagai satu kesatuan konteks** saat memerintahkan AI coding agent (Cursor, Jules, Antigravity, Copilot, dll.) untuk mendesain atau membangun halaman/komponen mana pun dari sistem POS ini. Seluruh keputusan desain, token warna, struktur navigasi, dan RBAC sudah terdefinisi di sini.

---

## 1. PROJECT OVERVIEW

Kamu sedang membangun **POS Restoran / F&B** berbasis web yang disebut **PosKasir**. Ini adalah sistem manajemen transaksi, dapur, stok, dan laporan untuk bisnis F&B skala UMKM hingga multi-outlet.

**Target pengguna:** 4 role — Kitchen, Bar, Kasir, Owner  
**Platform:** Web app (desktop-first, responsive tablet)  
**Stack preferensi:** Next.js App Router + shadcn/ui + Tailwind CSS  
**Total modul:** 63 menu (termasuk Login), terbagi dalam 11 kategori

---

## 2. DESIGN PHILOSOPHY

Inspirasi utama: **Linear.app** (navigasi dense tapi rapi) + **Toast POS** (dark sidebar, konten terang, modul lengkap tapi ringan).

### Prinsip Desain

| Prinsip | Implementasi |
|---|---|
| **Warm & Trustworthy** | Warna amber sebagai brand, bukan cold blue/purple |
| **Information Dense** | Sidebar dengan 63 menu tetap terasa navigable, bukan overwhelming |
| **Role-Aware UI** | Menu yang tidak diakses role aktif tidak ditampilkan, bukan hanya di-disable |
| **Operational First** | Aksi paling sering (buat order, bayar, update status) harus ≤2 klik |
| **Zero Confusion** | Status badge, warna semantik, dan ikon harus self-explanatory tanpa tooltip |

### Estetika

- **Tone:** Refined utilitarian — bersih, padat, profesional; bukan minimalis kosong, bukan maksimalis ramai
- **Referensi visual:** Linear.app navigasi, Retool dashboard density, Moka POS konteks lokal
- **Avoid:** Purple gradient, Inter font generic, card shadow berlebihan, empty state generik

---

## 3. COLOR PALETTE (Design Tokens)

Gunakan token berikut secara konsisten di seluruh sistem. **Jangan substitusi warna di luar token ini.**

### Primary — Amber (Brand & CTA)

| Token | Hex | Penggunaan |
|---|---|---|
| `amber-50` | `#FAEEDA` | Background subtle, hover secondary |
| `amber-100` | `#FAC775` | Background hover |
| `amber-200` | `#EF9F27` | Accent, icon highlight |
| `amber-400` | `#BA7517` | **CTA / Primary button** |
| `amber-600` | `#854F0B` | Border pada elemen primary |
| `amber-800` | `#633806` | Teks di atas amber background |

### Neutral — Warm Gray (Surface & Text)

| Token | Hex | Penggunaan |
|---|---|---|
| `gray-0` | `#FAFAF8` | Page background |
| `gray-50` | `#F1EFE8` | Sidebar background, surface sekunder |
| `gray-200` | `#D3D1C7` | Border default |
| `gray-400` | `#888780` | Text muted, placeholder, label |
| `gray-600` | `#444441` | Text secondary |
| `gray-800` | `#2C2C2A` | Text primary |

### Semantic — Status

| State | Background | Text |
|---|---|---|
| **Success** | `#EAF3DE` | `#3B6D11` |
| **Danger** | `#FCEBEB` | `#A32D2D` |
| **Info** | `#E6F1FB` | `#185FA5` |
| **Warning** | `#FAEEDA` | `#633806` |
| **Draft/Netral** | `#F1EFE8` | `#5F5E5A` |

### Token Mapping Utama

```css
--color-bg-page:       #FAFAF8;
--color-bg-card:       #FFFFFF;
--color-bg-sidebar:    #F1EFE8;
--color-bg-sidebar-hover: #E8E5DB;
--color-border:        #D3D1C7;
--color-text-primary:  #2C2C2A;
--color-text-secondary:#888780;
--color-cta:           #BA7517;
--color-cta-hover:     #9E6213;
--color-cta-text:      #FFFFFF;
```

---

## 4. TYPOGRAPHY

```css
--font-display: 'Instrument Serif', Georgia, serif;   /* Heading halaman besar */
--font-ui:      'Geist', 'DM Sans', sans-serif;       /* Label, navigasi, body UI */
--font-mono:    'Geist Mono', 'Fira Code', monospace; /* Harga, angka, kode */
```

### Scale

| Role | Size | Weight |
|---|---|---|
| Page title | 20px | 500 |
| Section heading | 14px | 500 |
| Body / label | 13px | 400 |
| Caption / muted | 11px | 400 |
| Harga / nominal | 15px | 600, font-mono |

---

## 5. LAYOUT SYSTEM

### Shell Utama

```
┌─────────────────────────────────────────────────────────┐
│ TOPBAR (48px) — Logo, search global, notif, user menu   │
├────────────┬────────────────────────────────────────────┤
│            │                                            │
│  SIDEBAR   │            MAIN CONTENT AREA              │
│  (220px)   │                                            │
│            │  ┌─────────────────────────────────────┐  │
│  Navigasi  │  │  PAGE HEADER (breadcrumb + actions) │  │
│  per       │  ├─────────────────────────────────────┤  │
│  kategori  │  │                                     │  │
│  (collapse)│  │  CONTENT (table / form / grid)      │  │
│            │  │                                     │  │
│            │  └─────────────────────────────────────┘  │
└────────────┴────────────────────────────────────────────┘
```

### Sidebar Behavior

- Default: **expanded** di desktop (≥1280px), **collapsed** di tablet (768–1279px)
- Setiap kategori bisa **collapse/expand** independen
- Item aktif: `background: #F1EFE8`, `border-left: 2px solid #BA7517`, `color: #2C2C2A`
- Item hover: `background: #E8E5DB`
- **RBAC:** Menu yang tidak tersedia untuk role aktif **tidak dirender**, bukan hanya disembunyikan

---

## 6. RBAC — MENU AKSES PER ROLE

> Render hanya menu yang bernilai `1` untuk role aktif. Owner selalu full access.

### Matriks Akses

| # | Menu | Kitchen | Bar | Kasir | Owner |
|---|---|:---:|:---:|:---:|:---:|
| **LOGIN** | | | | | |
| 0 | Login (Email/PIN) | ✓ | ✓ | ✓ | ✓ |
| **ORDER & TRANSAKSI** | | | | | |
| 1 | Buat Order Baru | | | ✓ | ✓ |
| 2 | Daftar Order Aktif | ✓ | ✓ | ✓ | ✓ |
| 3 | Detail Order per Meja | ✓ | ✓ | ✓ | ✓ |
| 4 | Edit Order (approval flow) | | ✓ | ✓ | ✓ |
| 5 | Split Bill / Gabung Bill | | | ✓ | ✓ |
| 6 | Pembayaran (cash, QRIS, gateway) | | | ✓ | ✓ |
| 7 | Kasbon & Cicilan | | | ✓ | ✓ |
| 8 | PO / Down Payment | | | ✓ | ✓ |
| 9 | Diskon & Voucher | | | ✓ | ✓ |
| 10 | Struk (print / WA / skip) | | | ✓ | ✓ |
| 11 | Riwayat Transaksi | | | ✓ | ✓ |
| 12 | Reservasi / Book Meja | | | ✓ | ✓ |
| **KITCHEN DISPLAY** | | | | | |
| 13 | Antrian Order Real-time | ✓ | ✓ | | ✓ |
| 14 | Update Status Masak | ✓ | | | |
| 15 | Approval Order Selesai (kitchen→kasir) | | ✓ | | |
| 16 | Filter Kategori Pesanan | ✓ | ✓ | | |
| 17 | Riwayat Order Dapur | ✓ | ✓ | | ✓ |
| 18 | Estimasi Waktu Masak | ✓ | ✓ | | ✓ |
| **MEJA** | | | | | |
| 19 | Layout Meja Visual | ✓ | ✓ | ✓ | ✓ |
| 20 | Status Meja Real-time | ✓ | ✓ | ✓ | ✓ |
| 21 | QR Code per Meja | | | ✓ | ✓ |
| **PELANGGAN** | | | | | |
| 22 | Customer Database | ✓ | | ✓ | ✓ |
| 23 | Membership & Loyalty | | | ✓ | ✓ |
| 24 | Kasbon per Pelanggan | | | ✓ | ✓ |
| 25 | Riwayat Transaksi Pelanggan | | | ✓ | ✓ |
| **PRODUK & STOK** | | | | | |
| 26 | Katalog Produk | | | | ✓ |
| 27 | Varian & Multi Harga | | | | ✓ |
| 28 | Manajemen Stok Produk Jadi | | | | ✓ |
| 29 | Inventori Bahan Baku | | | | ✓ |
| 30 | HPP per Produk | | | | ✓ |
| 31 | Reminder Expired Product | | | | ✓ |
| 32 | Alert Stok Menipis | | | | ✓ |
| **PROMO** | | | | | |
| 33 | Template Promo | | | | ✓ |
| 34 | Diskon Otomatis | | | | ✓ |
| 35 | Voucher | | | | ✓ |
| **KARYAWAN & SHIFT** | | | | | |
| 36 | Data Karyawan | | | | ✓ |
| 37 | Jadwal Shift | | | | ✓ |
| 38 | Absensi Digital | | | ✓ | ✓ |
| 39 | Buka / Tutup Shift Kasir | | | ✓ | ✓ |
| 40 | Rekap Kas per Shift | | | ✓ | ✓ |
| 41 | Laporan Kehadiran | | | | ✓ |
| **ORDER ONLINE** | | | | | |
| 42 | Terima Order GoFood / GrabFood | ✓ | ✓ | ✓ | ✓ |
| 43 | Status Order Online | ✓ | ✓ | ✓ | ✓ |
| 44 | Riwayat Order Online | | | ✓ | ✓ |
| **LAPORAN & ERP** | | | | | |
| 45 | Dashboard Keuangan | | | | ✓ |
| 46 | Laporan Penjualan | | | | ✓ |
| 47 | Laporan Per Outlet | | | | ✓ |
| 48 | Laporan Per Kasir | | | | ✓ |
| 49 | Laporan Produk Terlaris | | | | ✓ |
| 50 | Laporan Stok & Inventori | | | | ✓ |
| 51 | Laporan Absensi & Shift | | | | ✓ |
| 52 | Pengeluaran Operasional | | | | ✓ |
| 53 | Export PDF & Excel | | | | ✓ |
| **PENGATURAN** | | | | | |
| 54 | Manajemen Outlet & Cabang | | | | ✓ |
| 55 | User & RBAC | | | | ✓ |
| 56 | Konfigurasi Payment Gateway | | | | ✓ |
| 57 | Konfigurasi Printer | | | | ✓ |
| 58 | Konfigurasi QR Meja | | | | ✓ |
| 59 | Notifikasi & Alert | | | | ✓ |
| 60 | Backup & Keamanan Data | | | | ✓ |
| 61 | Approval Rules (threshold nominal) | | | | ✓ |
| 62 | Integrasi GoFood / GrabFood | | | | ✓ |

**Jumlah akses per role:** Kitchen: ~14 menu · Bar: ~16 menu · Kasir: ~30 menu · Owner: 63 menu

---

## 7. KOMPONEN DESAIN STANDAR

Gunakan komponen berikut secara konsisten di semua halaman.

### Badge / Status

```tsx
// Pola warna badge
const badgeVariants = {
  selesai:    { bg: "#EAF3DE", color: "#3B6D11" },
  proses:     { bg: "#FAEEDA", color: "#633806" },
  dibatalkan: { bg: "#FCEBEB", color: "#A32D2D" },
  online:     { bg: "#E6F1FB", color: "#185FA5" },
  draft:      { bg: "#F1EFE8", color: "#5F5E5A" },
}
```

### Button

```tsx
// Primary CTA
<button className="bg-[#BA7517] text-white hover:bg-[#9E6213] px-4 py-2 rounded-md text-sm font-medium">
  Buat Order
</button>

// Secondary
<button className="bg-[#FAEEDA] text-[#633806] border border-[#EF9F27] px-4 py-2 rounded-md text-sm">
  Lihat Detail
</button>

// Ghost / destructive
<button className="text-[#A32D2D] hover:bg-[#FCEBEB] px-4 py-2 rounded-md text-sm">
  Batalkan
</button>
```

### Sidebar Nav Item

```tsx
// Active state
<div className="flex items-center gap-2 px-3 py-2 rounded-md
  bg-[#F1EFE8] border-l-2 border-[#BA7517] text-[#2C2C2A] text-sm font-medium">
  <Icon size={15} />
  <span>Nama Menu</span>
</div>

// Default state
<div className="flex items-center gap-2 px-3 py-2 rounded-md
  text-[#444441] text-sm hover:bg-[#E8E5DB] cursor-pointer transition-colors">
  <Icon size={15} />
  <span>Nama Menu</span>
</div>
```

### Card / Surface

```tsx
<div className="bg-white border border-[#D3D1C7] rounded-lg p-4 shadow-sm">
  {/* konten */}
</div>
```

### Table Pattern

- Header sticky, background `#F1EFE8`, font 11px uppercase
- Row hover: `background: #FAFAF8`
- Border: `1px solid #D3D1C7` bottom only per row
- Kolom nominal / harga: font-mono, rata kanan
- Kolom status: badge centered

---

## 8. HALAMAN — SPESIFIKASI PER MENU

### [0] Login

**Tujuan:** Entry point semua role. Mendukung login via Email+Password atau PIN 6 digit (untuk cashier mode cepat).

**Layout:**
- Halaman terpusat, card login di tengah layar
- Background: warm gradient tipis dari `#FAFAF8` ke `#F1EFE8`
- Logo / nama produk di atas card
- Dua tab: `Email & Password` | `PIN Cepat`
- PIN mode: 6 dot input, keyboard numpad custom
- Tombol login: `#BA7517`
- Di bawah card: versi aplikasi kecil

**Fields:**
- Email: text input + validasi format
- Password: password input + show/hide toggle
- PIN: 6-digit dot display + numpad virtual
- Remember device: checkbox

**Post-login:** Redirect ke halaman pertama sesuai role:
- Kitchen → Antrian Order Real-time (#13)
- Bar → Antrian Order Real-time (#13)
- Kasir → Daftar Order Aktif (#2)
- Owner → Dashboard Keuangan (#45)

---

### [1] Buat Order Baru

**Role:** Kasir, Owner

**Layout:** Split panel — kiri katalog produk (searchable grid), kanan ringkasan order (sticky)

**Komponen kiri:**
- Search bar produk di atas
- Filter kategori (chip/tab horizontal)
- Product card grid (2–3 kolom): nama, harga, foto thumbnail, tombol +
- Stok habis: card di-dim, badge "Habis"

**Komponen kanan (order summary):**
- Pilihan meja (dropdown / visual picker)
- Daftar item dipilih: nama, qty stepper, subtotal
- Section diskon/voucher (collapsible)
- Total + breakdown pajak
- CTA: `Simpan Draft` (ghost) | `Konfirmasi Order` (primary amber)

---

### [2] Daftar Order Aktif

**Role:** Semua

**Layout:** Table view + filter bar

**Filter:** Status (semua, pending, proses, selesai) · Meja · Kasir · Tanggal

**Kolom tabel:**
- No. Order | Meja | Item count | Total | Status badge | Kasir | Waktu | Aksi

**Aksi per row:** Lihat Detail · Cetak Struk · (Kasir/Owner: Edit, Batalkan)

---

### [3] Detail Order per Meja

**Role:** Semua

**Layout:** Card detail order, list item, timeline status, tombol aksi sesuai role

**Konten:**
- Header: No. order, meja, waktu, status badge
- Daftar item: nama, qty, harga satuan, subtotal
- Catatan dapur per item
- Timeline: Dipesan → Diproses → Selesai → Dibayar
- Footer: total, tombol aksi (role-aware)

---

### [4] Edit Order (Approval Flow)

**Role:** Bar, Kasir, Owner

**Behavior:**
- Kasir/Bar edit → trigger approval request ke Owner jika nilai perubahan > threshold (dari setting #61)
- Status flow: `Draft Edit` → `Menunggu Approval` → `Disetujui / Ditolak`
- UI: form edit order + approval status indicator banner di atas form

---

### [5] Split Bill / Gabung Bill

**Role:** Kasir, Owner

**Layout:** Dua panel — item order (kiri) + bill hasil split (kanan)

**Fitur Split:**
- Split rata (per kepala, input jumlah orang)
- Split manual (drag item ke bill tertentu)
- Gabung: pilih 2 order aktif dari meja berbeda → konfirmasi

---

### [6] Pembayaran

**Role:** Kasir, Owner

**Layout:** Modal full atau halaman dedicated, 3 step: Pilih Metode → Input / Konfirmasi → Struk

**Metode:**
- Tunai: input nominal, tampilkan kembalian real-time
- QRIS: generate QR + timer 5 menit + polling status
- Transfer / gateway: redirect atau input manual kode unik

**Post-bayar:** Auto-trigger struk (#10), update status meja (#20)

---

### [7] Kasbon & Cicilan

**Role:** Kasir, Owner

**Layout:** Form kasbon → link ke customer (#22) → input nominal → jadwal cicilan

**Table kasbon:** Nama pelanggan · Total · Terbayar · Sisa · Jatuh tempo · Status

---

### [8] PO / Down Payment

**Role:** Kasir, Owner

**Layout:** Form PO: pilih customer, item yang dipesan, nominal DP, tanggal pickup

**Status PO:** Draft → DP Diterima → Siap → Selesai → Batal

---

### [9] Diskon & Voucher

**Role:** Kasir, Owner

**Dalam konteks order:** Input field voucher code + validasi real-time, atau pilih dari daftar promo aktif

**UI:** Card promo aktif tersedia (dari template #33), input manual kode, preview potongan

---

### [10] Struk

**Role:** Kasir, Owner

**Trigger:** Otomatis setelah bayar, atau manual dari daftar transaksi

**Opsi:** Print thermal (ESC/POS) · Kirim WhatsApp (nomor customer) · Skip

**Preview struk:** Mini preview di modal sebelum aksi dipilih

---

### [11] Riwayat Transaksi

**Role:** Kasir, Owner

**Layout:** Table dengan filter komprehensif: tanggal range, metode bayar, kasir, status, nominal range

**Kolom:** No. · Tanggal · Meja · Kasir · Total · Metode · Status · Aksi (detail, cetak ulang)

**Export:** Tombol Export di page header (PDF/Excel, dari #53)

---

### [12] Reservasi / Book Meja

**Role:** Kasir, Owner

**Layout:** Kalender view (daily/weekly) + list reservasi hari ini

**Form booking:** Nama tamu · No. HP · Tanggal & jam · Jumlah tamu · Pilih meja · Catatan

**Status:** Pending · Dikonfirmasi · Check-in · Selesai · No-show

---

### [13] Antrian Order Real-time

**Role:** Kitchen, Bar, Owner

**Layout:** Card grid (Kanban style) per status: `Baru` | `Diproses` | `Siap`

**Card item:**
- Nomor order + meja (besar, mudah dibaca dari jarak 1–2 meter)
- Daftar item + catatan
- Timer sejak order masuk (warna berubah jika > threshold)
- Tombol update status

**Refresh:** Real-time via WebSocket / SSE

---

### [14] Update Status Masak

**Role:** Kitchen only

**Behavior:** Dalam card antrian (#13), tombol besar `Mulai Masak` → `Selesai`

**UI:** Konfirmasi satu tap, bukan modal panjang

---

### [15] Approval Order Selesai (kitchen→kasir)

**Role:** Bar only

**Context:** Bar bertugas sebagai quality check sebelum order dianggap siap ke kasir

**UI:** Card antrian + tombol `Approve Selesai` / `Kembalikan ke Dapur`

---

### [16] Filter Kategori Pesanan

**Role:** Kitchen, Bar

**UI:** Tab / chip filter horizontal di atas antrian: Semua · Makanan · Minuman · Snack (sesuai kategori produk)

---

### [17] Riwayat Order Dapur

**Role:** Kitchen, Bar, Owner

**Layout:** Table: No. Order · Item · Waktu Masuk · Waktu Selesai · Durasi · Status

---

### [18] Estimasi Waktu Masak

**Role:** Kitchen, Bar, Owner

**UI:** Di setiap card antrian, tampilkan estimasi dan aktual. Summary bar di atas: rata-rata waktu masak hari ini

---

### [19] Layout Meja Visual

**Role:** Semua

**Layout:** Canvas / grid visual denah meja. Drag-and-drop (Owner only untuk edit posisi)

**Status meja:**
- Kosong: border tipis, bg putih
- Terisi: bg `#FAEEDA`, badge jumlah tamu
- Reserved: bg `#E6F1FB`, badge jam reservasi
- Perlu perhatian (order lama): bg `#FCEBEB`

**Klik meja:** Kasir → langsung ke buat/detail order · Kitchen/Bar → lihat order meja

---

### [20] Status Meja Real-time

**Role:** Semua

**Embedded dalam** layout meja (#19) dan sidebar quick view

**Update:** Real-time, polling / WebSocket

---

### [21] QR Code per Meja

**Role:** Kasir, Owner

**Layout:** Table: Meja · QR preview kecil · URL · Aksi (download PNG, print, regenerate)

**Bulk action:** Print semua QR (PDF multi-page)

---

### [22] Customer Database

**Role:** Kitchen (view only), Kasir, Owner

**Layout:** Table + search + filter (member/non-member, kasbon aktif)

**Kolom:** Nama · HP · Poin · Total transaksi · Status kasbon · Aksi

**Detail customer:** Riwayat transaksi, kasbon, poin, preferensi

---

### [23] Membership & Loyalty

**Role:** Kasir, Owner

**Layout:** Konfigurasi tier membership + daftar member aktif

**Fitur:** Poin per transaksi, penukaran poin, badge tier (Bronze/Silver/Gold)

---

### [24] Kasbon per Pelanggan

**Role:** Kasir, Owner

**Layout:** Pilih customer → lihat/tambah kasbon → input cicilan

**Terintegrasi dengan** #7 (Kasbon & Cicilan) dan #22 (Customer DB)

---

### [25] Riwayat Transaksi Pelanggan

**Role:** Kasir, Owner

**Layout:** Sub-halaman di dalam detail customer (#22): timeline transaksi + filter

---

### [26–32] Produk & Stok

**Role:** Owner only

**[26] Katalog Produk:**
- Table + search + filter kategori
- Card/row: foto, nama, harga, stok, status aktif/nonaktif
- Aksi: edit, nonaktifkan, duplikasi

**[27] Varian & Multi Harga:**
- Dalam form edit produk: section varian (ukuran, level, topping) + harga per varian

**[28] Manajemen Stok Produk Jadi:**
- Table stok: produk, stok saat ini, minimum stok, satuan
- Aksi: adjust stok manual, lihat log perubahan

**[29] Inventori Bahan Baku:**
- CRUD bahan baku + stok + satuan + supplier
- Relasi ke HPP (#30)

**[30] HPP per Produk:**
- Form: pilih produk → input bahan baku yang digunakan (qty per porsi) → auto-hitung HPP
- Tampilkan margin otomatis (Harga Jual - HPP)

**[31] Reminder Expired Product:**
- Table: produk, tanggal expired, hari tersisa, status (aman/segera/kadaluarsa)
- Alert badge di sidebar jika ada produk < 7 hari

**[32] Alert Stok Menipis:**
- Table: produk, stok saat ini, minimum threshold, status
- Notifikasi push / badge sidebar

---

### [33–35] Promo

**Role:** Owner only

**[33] Template Promo:**
- Buat template: nama, tipe (persentase/nominal/buy X get Y), kondisi berlaku, periode

**[34] Diskon Otomatis:**
- Rule-based: jika total > X → diskon Y%, aktif di jam/hari tertentu, berlaku per kategori produk

**[35] Voucher:**
- Generate kode voucher: satu kali pakai / multi pakai, nominal/persentase, masa berlaku

---

### [36–41] Karyawan & Shift

**[36] Data Karyawan:** (Owner) CRUD karyawan: nama, role, PIN kasir, kontak, status aktif

**[37] Jadwal Shift:** (Owner) Kalender shift: assign karyawan ke shift pagi/siang/malam per outlet

**[38] Absensi Digital:** (Kasir, Owner) Clock-in/out, foto selfie opsional, GPS validasi opsional

**[39] Buka / Tutup Shift Kasir:** (Kasir, Owner) Form buka shift: input kas awal · Tutup shift: rekap + input kas akhir + selisih

**[40] Rekap Kas per Shift:** (Kasir, Owner) Summary: kas awal, total penjualan, pengeluaran, kas akhir, selisih

**[41] Laporan Kehadiran:** (Owner) Table absensi per periode: karyawan, jam masuk, jam keluar, durasi, status (hadir/terlambat/alpha)

---

### [42–44] Order Online

**[42] Terima Order GoFood / GrabFood:** (Semua) Notifikasi order masuk dari agregator, tombol Terima/Tolak, auto-print

**[43] Status Order Online:** (Semua) List order online real-time: platform, nomor, status driver, estimasi pickup

**[44] Riwayat Order Online:** (Kasir, Owner) Table riwayat + filter platform + rekonsiliasi

---

### [45–53] Laporan & ERP

**Role:** Owner only

**[45] Dashboard Keuangan:**
- KPI cards: Total hari ini, minggu ini, bulan ini
- Chart: line chart pendapatan 30 hari, pie chart metode bayar, bar chart top produk
- Quick filter: Hari ini / 7 hari / 30 hari / Custom

**[46] Laporan Penjualan:**
- Table detail + filter tanggal + group by (harian/mingguan/bulanan)

**[47] Laporan Per Outlet:**
- Perbandingan performa antar cabang (jika multi-outlet)

**[48] Laporan Per Kasir:**
- Performance per kasir: total transaksi, total nominal, rata-rata per transaksi

**[49] Laporan Produk Terlaris:**
- Ranking produk: qty terjual, total revenue, tren naik/turun

**[50] Laporan Stok & Inventori:**
- Mutasi stok: masuk, keluar, saldo, per periode

**[51] Laporan Absensi & Shift:**
- Rekapitulasi kehadiran semua karyawan per periode

**[52] Pengeluaran Operasional:**
- CRUD pengeluaran: kategori, nominal, deskripsi, bukti foto
- Summary: pie chart pengeluaran per kategori

**[53] Export PDF & Excel:**
- Available di setiap halaman laporan sebagai action di page header
- Format: PDF (untuk print) + Excel/CSV (untuk analisis lanjut)

---

### [54–62] Pengaturan

**Role:** Owner only

**[54] Manajemen Outlet & Cabang:**
- CRUD outlet: nama, alamat, kontak, jam operasional, status aktif

**[55] User & RBAC:**
- Daftar user + role assignment
- Edit role: toggle akses per menu (mengikuti matriks di bagian 6)

**[56] Konfigurasi Payment Gateway:**
- Setup Midtrans / Xendit / lainnya: API key, mode (sandbox/production), metode aktif

**[57] Konfigurasi Printer:**
- Tambah printer thermal: nama, IP/USB, ukuran kertas, test print

**[58] Konfigurasi QR Meja:**
- Prefix URL, template QR, regenerate massal

**[59] Notifikasi & Alert:**
- Toggle notifikasi: stok menipis, kasbon jatuh tempo, order online masuk
- Channel: in-app, WhatsApp (Fonnte), email

**[60] Backup & Keamanan Data:**
- Jadwal backup otomatis, download backup manual, log aktivitas user

**[61] Approval Rules:**
- Definisi threshold nominal yang memerlukan approval Owner untuk edit order / diskon manual

**[62] Integrasi GoFood / GrabFood:**
- Connect akun agregator: merchant ID, API key, outlet mapping, status aktif/nonaktif

---

## 9. POLA INTERAKSI UMUM

### Empty State

```
[Ilustrasi SVG ringan — relevan konteks]
Belum ada [nama data] di sini
[Tombol CTA kontekstual jika ada aksi]
```

### Loading State

- Skeleton loader untuk tabel dan list (bukan spinner global)
- Spinner hanya untuk aksi submit tombol

### Error State

- Toast notifikasi kanan bawah (danger/success/info) — auto-dismiss 4 detik
- Form validation: inline, merah `#A32D2D`, pesan spesifik

### Confirmation Destructive

- Modal konfirmasi untuk aksi tidak bisa di-undo (batalkan order, hapus data)
- Tombol konfirmasi: `bg-[#A32D2D] text-white`

### Mobile / Tablet

- Sidebar collapse jadi bottom nav bar (4 tab utama sesuai role)
- Table scroll horizontal
- Form full width

---

## 10. INSTRUKSI UNTUK AI CODING AGENT

Saat kamu diperintah untuk membangun halaman atau komponen mana pun dari sistem ini:

1. **Selalu rujuk token warna** dari bagian 3. Jangan invent warna baru.
2. **Selalu cek RBAC** dari bagian 6. Komponen yang dibangun harus menerima prop `role` dan hanya merender item yang tersedia untuk role tersebut.
3. **Gunakan pola komponen** dari bagian 7 (badge, button, sidebar item, card, table).
4. **Ikuti layout shell** dari bagian 5. Semua halaman duduk dalam shell yang sama.
5. **Ikuti spesifikasi halaman** dari bagian 8 untuk halaman yang diminta.
6. **Jangan invent fitur** yang tidak ada di daftar 63 menu. Kalau butuh fitur baru, flag terlebih dahulu.
7. **Output code harus:** TypeScript, functional components, Tailwind class, shadcn/ui untuk primitif (Dialog, Dropdown, Input, dll.).
8. **Selalu sertakan:** loading state, empty state, dan error state untuk setiap data-fetching component.

---

## 11. QUICK REFERENCE

```
Total menu : 63 (Login + 62 modul)
Role       : Kitchen | Bar | Kasir | Owner
Akses min  : Kitchen ~14 · Bar ~16 · Kasir ~30 · Owner 63
Stack      : Next.js App Router + Tailwind + shadcn/ui
Font UI    : Geist / DM Sans
Font Mono  : Geist Mono (harga, angka)
Brand CTA  : #BA7517 (Amber 400)
Page BG    : #FAFAF8 (Warm White)
Sidebar BG : #F1EFE8 (Warm Gray)
Border     : #D3D1C7
Text Prim  : #2C2C2A
```

---

*Generated for: PosKasir F&B POS System · v1.0 · 63 menu · 4 roles*
