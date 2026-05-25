// FAQ Content — semua menu
// Dipisah dari FaqFab.vue agar lebih mudah dikelola

export const faqContent = {

  // ─────────────────────────────────────────────
  // AUTH
  // ─────────────────────────────────────────────

  '/login': {
    title: 'Login',
    description: 'Halaman masuk ke sistem POS Mentai. Setiap role memiliki tampilan yang berbeda setelah login.',
    access: ['Semua role'],
    features: [
      'Login dengan email & password',
      'Auto redirect sesuai role setelah login',
      'Kasir otomatis diminta buka shift',
      'Session aktif 8 jam, refresh token 30 hari',
    ],
    steps: [
      'Masukkan email dan password',
      'Klik Login',
      'Sistem redirect sesuai role (Kitchen, Bar, Kasir, Owner)',
      'Kasir: input saldo awal kas untuk buka shift',
    ],
    relations: [
      { icon: '⏰', menu: 'Manajemen Shift', description: 'Kasir wajib buka shift setelah login' },
      { icon: '🔐', menu: 'RBAC', description: 'Role menentukan menu yang bisa diakses' },
    ],
  },

  '/profile': {
    title: 'Profil & Keamanan',
    description: 'Kelola data profil, ganti password, update PIN approval, dan lihat sesi aktif di semua device.',
    access: ['Semua role'],
    features: [
      'Lihat & edit data profil',
      'Ganti password',
      'Ganti PIN approval (4-6 digit)',
      'Lihat & revoke sesi aktif per device',
      'Logout semua device sekaligus',
    ],
    steps: [
      'Klik avatar / nama di navbar',
      'Pilih menu yang diinginkan',
      'Untuk ganti password: input password lama → baru → konfirmasi',
      'Untuk ganti PIN: verifikasi password dulu → input PIN baru',
    ],
    relations: [
      { icon: '✅', menu: 'Approval', description: 'PIN approval dipakai untuk transaksi yang butuh persetujuan' },
      { icon: '🔐', menu: 'RBAC', description: 'Role & akses ditentukan dari sini' },
    ],
  },

  // ─────────────────────────────────────────────
  // ORDER & TRANSAKSI
  // ─────────────────────────────────────────────

  '/orders': {
    title: 'Order & Transaksi',
    description: 'Modul utama untuk membuat dan mengelola pesanan. Tersedia 2 cara: kasir input manual atau customer self-order via QR meja.',
    access: ['Kasir', 'Supervisor', 'Owner'],
    features: [
      'Kasir bisa bayar sekarang atau bayar nanti',
      'QR meja: customer pesan & bayar sendiri via payment gateway',
      'Support semua metode pembayaran',
      'Diskon & promo otomatis terapply',
      'Split bill & gabung bill',
    ],
    steps: [
      'Pilih meja atau takeaway',
      'Input nomor HP pelanggan (opsional)',
      'Pilih produk + varian + catatan',
      'Cek promo eligible otomatis',
      'Pilih bayar sekarang atau bayar nanti',
      'Order masuk kitchen display otomatis',
    ],
    relations: [
      { icon: '🎁', menu: 'Membership & Loyalty', description: 'Poin otomatis ditambah setelah transaksi' },
      { icon: '📦', menu: 'Produk & Stok', description: 'Stok berkurang otomatis saat order completed' },
      { icon: '💰', menu: 'Laporan & ERP', description: 'Revenue masuk laporan penjualan' },
      { icon: '✅', menu: 'Approval', description: 'Refund, void, diskon besar butuh approval' },
      { icon: '🍳', menu: 'Kitchen Display', description: 'Order tampil real-time di layar kitchen' },
      { icon: '⏰', menu: 'Manajemen Shift', description: 'Transaksi tercatat ke shift aktif' },
    ],
  },

  '/orders/history': {
    title: 'Riwayat Transaksi',
    description: 'Lihat semua riwayat transaksi dengan filter lengkap. Bisa reprint struk dan lihat detail per transaksi.',
    access: ['Kasir', 'Supervisor', 'Owner'],
    features: [
      'Filter by tanggal, kasir, meja, status',
      'Lihat detail per transaksi',
      'Reprint struk (print ulang atau kirim WA)',
      'Void & refund dari sini',
    ],
    steps: [
      'Buka Riwayat Transaksi',
      'Filter sesuai kebutuhan',
      'Klik transaksi untuk lihat detail',
      'Klik Reprint untuk cetak ulang struk',
    ],
    relations: [
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Semua order tersimpan di sini' },
      { icon: '✅', menu: 'Approval', description: 'Refund & void butuh approval' },
      { icon: '💰', menu: 'Laporan', description: 'Data laporan bersumber dari riwayat ini' },
    ],
  },

  '/orders/split': {
    title: 'Split Bill & Gabung Bill',
    description: 'Pisahkan tagihan satu meja menjadi beberapa bill, atau gabungkan beberapa order menjadi satu tagihan.',
    access: ['Kasir', 'Supervisor', 'Owner'],
    features: [
      'Split bill: bagi item ke beberapa bill',
      'Split rata: bagi nominal secara merata',
      'Gabung bill: merge beberapa order jadi satu',
    ],
    steps: [
      'Buka detail order aktif',
      'Klik "Split Bill" atau "Gabung Bill"',
      'Untuk split: drag item ke bill masing-masing',
      'Konfirmasi → proses pembayaran terpisah',
    ],
    relations: [
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Split/gabung dari order aktif' },
      { icon: '💳', menu: 'Pembayaran', description: 'Setiap bill diproses bayar terpisah' },
    ],
  },

  '/reservations': {
    title: 'Reservasi Meja',
    description: 'Kelola pemesanan meja di muka. Customer bisa book meja untuk waktu tertentu.',
    access: ['Kasir', 'Supervisor', 'Owner'],
    features: [
      'Buat reservasi dengan data customer',
      'Assign meja saat customer tiba',
      'Status: pending, confirmed, arrived, cancelled',
      'Notif konfirmasi ke customer via WA',
    ],
    steps: [
      'Buka menu Reservasi → Buat Baru',
      'Input nama, HP, tanggal/jam, jumlah tamu',
      'Pilih meja (opsional, bisa assign nanti)',
      'Saat customer tiba → klik "Customer Tiba"',
      'Assign meja → buat order baru',
    ],
    relations: [
      { icon: '🪑', menu: 'Manajemen Meja', description: 'Meja di-reserved otomatis' },
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Order dibuat setelah customer tiba' },
      { icon: '👥', menu: 'Customer Database', description: 'Data customer tersimpan' },
    ],
  },

  // ─────────────────────────────────────────────
  // KITCHEN & BAR
  // ─────────────────────────────────────────────

  '/kitchen': {
    title: 'Kitchen Display',
    description: 'Tampilan real-time antrian pesanan untuk dapur. Dilengkapi timer, alert, dan suara notifikasi saat order masuk.',
    access: ['Kitchen', 'Bar'],
    features: [
      'Grid kartu order auto-sort by prioritas',
      'Suara notifikasi saat order baru masuk',
      'Timer waiting (PENDING) dan cooking (IN PROGRESS)',
      'Alert visual saat waktu hampir habis',
      'Filter per kategori menu',
    ],
    steps: [
      'Buka Kitchen Display',
      'Order baru masuk otomatis dengan bunyi notifikasi',
      'Klik "Mulai Masak" saat mulai proses',
      'Cooking timer mulai countdown',
      'Klik "Selesai" saat masakan siap',
      'Bar approve → status READY',
    ],
    relations: [
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Order dari kasir dan QR meja masuk ke sini' },
      { icon: '🍹', menu: 'Bar Approval', description: 'Bar approve sebelum status READY' },
      { icon: '🔔', menu: 'Notifikasi', description: 'Kasir & customer dapat notif saat READY' },
    ],
  },

  '/bar': {
    title: 'Bar Display & Approval',
    description: 'Tampilan order untuk bar. Bar bertugas approve order yang sudah selesai dari kitchen sebelum diantar ke customer.',
    access: ['Bar'],
    features: [
      'Lihat antrian order real-time',
      'Approve order selesai dari kitchen',
      'Kembalikan order ke kitchen jika ada masalah',
      'Filter per kategori',
    ],
    steps: [
      'Buka Bar Display',
      'Terima notif order selesai dari kitchen',
      'Review kartu order',
      'Klik "Approve" jika sudah benar → status READY',
      'Klik "Kembalikan" jika ada masalah',
    ],
    relations: [
      { icon: '🍳', menu: 'Kitchen Display', description: 'Kitchen kirim order selesai ke bar' },
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Status order update real-time' },
      { icon: '🔔', menu: 'Notifikasi', description: 'Kasir & customer dapat notif saat bar approve' },
    ],
  },

  // ─────────────────────────────────────────────
  // MEJA
  // ─────────────────────────────────────────────

  '/tables': {
    title: 'Manajemen Meja',
    description: 'Kelola layout meja restoran, status real-time, dan QR code per meja untuk self-order customer.',
    access: ['Semua (lihat)', 'Supervisor & Owner (kelola)'],
    features: [
      'Layout visual drag & drop',
      'Status meja real-time',
      'QR code unik per meja',
      'Download & print QR (PNG/PDF)',
      'Regenerate QR jika diperlukan',
    ],
    steps: [
      'Buka menu Meja',
      'Klik "Tambah Meja" → isi nama & kapasitas',
      'Atur posisi di layout visual',
      'Klik "QR Code" → download PNG atau PDF',
      'Print & tempel di meja fisik',
    ],
    relations: [
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Order terhubung ke meja' },
      { icon: '📅', menu: 'Reservasi', description: 'Meja bisa di-reserved' },
      { icon: '📱', menu: 'QR Self-Order', description: 'Customer scan QR untuk pesan & bayar' },
    ],
  },

  '/tables/qr': {
    title: 'QR Code Meja',
    description: 'Generate, download, dan print QR code per meja. Customer scan QR untuk pesan dan bayar langsung dari HP.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Generate QR otomatis per meja',
      'Download PNG resolusi tinggi',
      'Download PDF siap cetak',
      'Download semua QR sekaligus (ZIP)',
      'Print langsung ke printer',
      'Regenerate QR (token lama invalid)',
    ],
    steps: [
      'Buka menu Meja → pilih meja',
      'Klik "QR Code"',
      'Pilih Download PNG / PDF / Print',
      'Tempel QR di meja fisik',
    ],
    relations: [
      { icon: '🪑', menu: 'Manajemen Meja', description: 'QR terhubung ke meja tertentu' },
      { icon: '📱', menu: 'Self-Order', description: 'Customer scan untuk pesan & bayar' },
    ],
  },

  // ─────────────────────────────────────────────
  // PELANGGAN
  // ─────────────────────────────────────────────

  '/customers': {
    title: 'Customer Database',
    description: 'Database pelanggan terpusat. Simpan nama, nomor HP, riwayat transaksi, membership, dan kasbon per pelanggan.',
    access: ['Kasir', 'Supervisor', 'Owner'],
    features: [
      'Cari customer by nama atau nomor HP',
      'Lihat riwayat transaksi lengkap',
      'Status membership & saldo poin',
      'Kasbon outstanding per customer',
      'Registrasi member langsung',
    ],
    steps: [
      'Buka menu Customer',
      'Cari dengan nomor HP atau nama',
      'Klik customer untuk lihat profil',
      'Lihat riwayat transaksi, poin, kasbon',
    ],
    relations: [
      { icon: '🎁', menu: 'Membership & Loyalty', description: 'Setiap customer bisa jadi member' },
      { icon: '💳', menu: 'Kasbon & Cicilan', description: 'Kasbon tercatat per customer' },
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Semua transaksi terhubung ke customer' },
    ],
  },

  '/membership': {
    title: 'Membership & Loyalty',
    description: 'Program loyalitas dengan poin, tier Bronze/Silver/Gold, dan reward berupa diskon atau produk gratis.',
    access: ['Kasir (operasional)', 'Owner (konfigurasi)'],
    features: [
      'Poin dari nominal transaksi & item tertentu',
      'Tier Bronze, Silver, Gold',
      'Redeem poin → diskon atau produk gratis',
      'Benefit otomatis per tier',
      'Registrasi via kasir, QR meja',
    ],
    steps: [
      'Customer input nomor HP saat transaksi',
      'Kasir daftarkan jika belum member',
      'Poin otomatis ditambah setelah transaksi',
      'Redeem: kasir pilih "Gunakan Poin"',
      'Sistem potong poin & apply reward',
    ],
    relations: [
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Poin earned setiap transaksi selesai' },
      { icon: '🏷️', menu: 'Multi Harga', description: 'Tier member dapat harga khusus' },
      { icon: '🎫', menu: 'Diskon & Promo', description: 'Tier member trigger diskon otomatis' },
    ],
  },

  '/kasbon': {
    title: 'Kasbon & Cicilan',
    description: 'Kelola hutang pelanggan dan jadwal cicilan. Dilengkapi reminder jatuh tempo otomatis.',
    access: ['Kasir', 'Supervisor', 'Owner'],
    features: [
      'Kasbon: hutang bayar nanti',
      'Cicilan: jadwal otomatis',
      'Reminder jatuh tempo via app & WA',
      'Riwayat pembayaran per customer',
      'Laporan piutang outstanding',
    ],
    steps: [
      'Saat transaksi pilih metode "Kasbon"',
      'Input nomor HP & jatuh tempo',
      'Kasbon > limit → butuh approval supervisor',
      'Customer bayar → cari by HP → pilih tagihan → bayar',
    ],
    relations: [
      { icon: '👥', menu: 'Customer Database', description: 'Kasbon tercatat di profil customer' },
      { icon: '✅', menu: 'Approval', description: 'Kasbon di atas limit butuh approval' },
      { icon: '💰', menu: 'Laporan Keuangan', description: 'Kasbon masuk sebagai piutang' },
    ],
  },

  '/po-orders': {
    title: 'PO / Down Payment',
    description: 'Kelola pre-order dan uang muka. Stok otomatis di-reserve saat PO dibuat.',
    access: ['Kasir', 'Supervisor', 'Owner'],
    features: [
      'PO: pesan sekarang bayar saat ambil',
      'DP: bayar sebagian dulu',
      'Stok otomatis di-reserve',
      'Reminder pickup & pelunasan',
    ],
    steps: [
      'Buat order → pilih tipe PO atau DP',
      'Set tanggal pickup & nominal DP',
      'Stok otomatis di-reserve',
      'Customer ambil → cari PO by HP → lunasi',
    ],
    relations: [
      { icon: '📦', menu: 'Produk & Stok', description: 'Stok di-reserve otomatis' },
      { icon: '💳', menu: 'Kasbon', description: 'Sisa PO masuk sebagai outstanding' },
      { icon: '✅', menu: 'Approval', description: 'PO besar butuh notif owner' },
    ],
  },

  // ─────────────────────────────────────────────
  // PRODUK & STOK
  // ─────────────────────────────────────────────

  '/products': {
    title: 'Katalog Produk',
    description: 'Kelola semua produk yang dijual. Termasuk kategori, foto, harga dasar, dan HPP.',
    access: ['Supervisor (edit)', 'Owner (full)'],
    features: [
      'Tambah, edit, nonaktifkan produk',
      'Kategori & urutan tampil di menu',
      'Foto produk',
      'Toggle ketersediaan produk',
      'HPP per produk',
    ],
    steps: [
      'Buka menu Produk',
      'Klik "Tambah Produk"',
      'Isi nama, kategori, harga, HPP',
      'Upload foto',
      'Simpan → langsung tampil di menu kasir',
    ],
    relations: [
      { icon: '🏷️', menu: 'Varian & Multi Harga', description: 'Setiap produk bisa punya varian & multi harga' },
      { icon: '📦', menu: 'Stok', description: 'Stok terhubung ke produk' },
      { icon: '🧪', menu: 'Bahan Baku', description: 'Resep/ingredien terhubung ke produk' },
    ],
  },

  '/products/variants': {
    title: 'Varian & Multi Harga',
    description: 'Kelola varian produk (ukuran, tingkat kepedasan, dll) dan multi harga per tier customer, outlet, atau waktu.',
    access: ['Owner'],
    features: [
      'Varian dengan harga tambahan',
      'Harga per tier: Normal, Member, Grosir',
      'Harga per outlet (beda cabang beda harga)',
      'Happy hour: harga khusus per jam tertentu',
    ],
    steps: [
      'Buka produk → tab Varian',
      'Tambah varian: nama + harga tambahan',
      'Tab Harga → tambah price tier',
      'Set outlet & jadwal happy hour (opsional)',
    ],
    relations: [
      { icon: '🎁', menu: 'Membership', description: 'Harga tier member otomatis untuk member' },
      { icon: '🛒', menu: 'Order', description: 'Kasir pilih varian saat buat order' },
    ],
  },

  '/stocks': {
    title: 'Manajemen Stok',
    description: 'Pantau dan kelola stok produk jadi. Stok berkurang otomatis saat order selesai.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Lihat stok semua produk',
      'Tambah stok masuk',
      'Adjustment stok manual (opname)',
      'Alert stok menipis',
      'Riwayat mutasi stok',
    ],
    steps: [
      'Buka menu Stok',
      'Pilih produk',
      'Klik "Tambah Stok" untuk stok masuk',
      'Input jumlah & tanggal expired (jika perlu)',
      'Untuk opname: klik "Adjustment" → input jumlah aktual',
    ],
    relations: [
      { icon: '🛒', menu: 'Order', description: 'Stok berkurang otomatis saat order completed' },
      { icon: '⏰', menu: 'Expired Product', description: 'Stok masuk di-track tanggal expirednya' },
      { icon: '💰', menu: 'Laporan Stok', description: 'Mutasi stok masuk ke laporan' },
    ],
  },

  '/raw-materials': {
    title: 'Inventori Bahan Baku',
    description: 'Kelola stok bahan baku dapur. Terhubung ke resep produk sehingga bahan baku berkurang otomatis saat order.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Daftar bahan baku + satuan + stok',
      'Tambah stok bahan baku masuk',
      'Terhubung ke resep produk',
      'Alert stok menipis',
      'Tracking expired bahan baku',
    ],
    steps: [
      'Buka menu Bahan Baku',
      'Tambah bahan baku: nama, satuan, minimum stok',
      'Tambah stok masuk + tanggal expired',
      'Hubungkan ke resep produk di menu Produk',
    ],
    relations: [
      { icon: '🍽️', menu: 'Katalog Produk', description: 'Bahan baku terhubung ke resep produk' },
      { icon: '⏰', menu: 'Expired Product', description: 'Bahan baku di-track expired-nya' },
      { icon: '💰', menu: 'Laporan HPP', description: 'Biaya bahan baku masuk perhitungan HPP' },
    ],
  },

  '/expired': {
    title: 'Reminder Expired Product',
    description: 'Monitor produk dan bahan baku yang mendekati atau sudah expired. Notifikasi otomatis H-7, H-3, H-1.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Dashboard produk akan expired',
      'Notif H-7, H-3, H-1 via app & WA',
      'Alert saat sudah expired',
      'Setting: auto nonaktif atau notif saja',
      'Laporan kerugian expired',
    ],
    steps: [
      'Tanggal expired diinput saat stok masuk',
      'Sistem kirim reminder otomatis sesuai jadwal',
      'Buka menu Expired untuk lihat daftar',
      'Klik "Handle" untuk nonaktifkan / buang stok',
    ],
    relations: [
      { icon: '📦', menu: 'Stok', description: 'Expired tracking dari data stok masuk' },
      { icon: '🧪', menu: 'Bahan Baku', description: 'Bahan baku juga di-track expired-nya' },
      { icon: '💰', menu: 'Laporan', description: 'Kerugian expired masuk laporan' },
    ],
  },

  // ─────────────────────────────────────────────
  // PROMO
  // ─────────────────────────────────────────────

  '/promos': {
    title: 'Template Promo',
    description: 'Buat dan kelola template promo. Promo bisa otomatis terapply atau via kode voucher.',
    access: ['Owner'],
    features: [
      'Jenis: persen, nominal, buy X get Y',
      'Trigger: produk, kategori, total transaksi, jam, metode bayar, tier member',
      'Auto-apply atau manual kode',
      'Periode & batas penggunaan',
      'Stacking rules',
    ],
    steps: [
      'Buka menu Promo → Buat Promo Baru',
      'Pilih jenis & trigger kondisi',
      'Set periode & batas penggunaan',
      'Aktifkan → berlaku sesuai jadwal',
    ],
    relations: [
      { icon: '🛒', menu: 'Order', description: 'Promo otomatis terapply saat kondisi terpenuhi' },
      { icon: '🎁', menu: 'Membership', description: 'Tier member bisa trigger promo' },
      { icon: '💰', menu: 'Laporan Promo', description: 'Statistik penggunaan promo' },
    ],
  },

  '/promos/vouchers': {
    title: 'Diskon Otomatis & Voucher',
    description: 'Kelola voucher manual dan pantau diskon otomatis yang sedang aktif.',
    access: ['Supervisor (toggle)', 'Owner (full)'],
    features: [
      'Aktifkan / nonaktifkan promo',
      'Lihat promo aktif saat ini',
      'Validasi kode voucher',
      'Statistik per voucher',
    ],
    steps: [
      'Kasir input kode voucher saat transaksi',
      'Sistem validasi kode',
      'Diskon terapply otomatis',
      'Jika di atas threshold → butuh approval',
    ],
    relations: [
      { icon: '🎫', menu: 'Template Promo', description: 'Voucher dibuat dari template promo' },
      { icon: '✅', menu: 'Approval', description: 'Diskon besar butuh approval supervisor' },
    ],
  },

  // ─────────────────────────────────────────────
  // KARYAWAN & SHIFT
  // ─────────────────────────────────────────────

  '/employees': {
    title: 'Data Karyawan',
    description: 'Kelola data karyawan, role, outlet, dan PIN approval.',
    access: ['Supervisor (lihat)', 'Owner (full)'],
    features: [
      'Tambah & nonaktifkan karyawan',
      'Assign role & outlet',
      'Set password & PIN approval',
      'Lihat riwayat aktivitas',
    ],
    steps: [
      'Buka menu Karyawan → Tambah',
      'Isi nama, email, HP, role, outlet',
      'Set password awal & PIN approval',
      'Simpan → karyawan bisa langsung login',
    ],
    relations: [
      { icon: '🔐', menu: 'RBAC', description: 'Role menentukan akses menu' },
      { icon: '⏰', menu: 'Jadwal Shift', description: 'Karyawan terhubung ke jadwal shift' },
      { icon: '✅', menu: 'Approval', description: 'PIN approval untuk transaksi sensitif' },
    ],
  },

  '/schedules': {
    title: 'Jadwal Shift',
    description: 'Buat dan kelola jadwal shift karyawan. Bisa bulk assign untuk 1 minggu sekaligus.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Buat template shift (pagi, siang, malam)',
      'Assign shift per karyawan per hari',
      'Bulk assign 1 minggu sekaligus',
      'Lihat jadwal hari ini',
    ],
    steps: [
      'Buka menu Jadwal',
      'Pilih karyawan & tanggal',
      'Assign template shift',
      'Atau bulk assign untuk 1 minggu',
    ],
    relations: [
      { icon: '👤', menu: 'Karyawan', description: 'Jadwal terhubung ke data karyawan' },
      { icon: '🕐', menu: 'Absensi', description: 'Jadwal jadi acuan deteksi keterlambatan' },
      { icon: '⏰', menu: 'Shift Kasir', description: 'Template shift dipakai saat buka shift kasir' },
    ],
  },

  '/attendance': {
    title: 'Absensi Karyawan',
    description: 'Catat kehadiran dengan clock in/out digital. Supervisor bisa koreksi absensi.',
    access: ['Semua role (clock in/out)', 'Supervisor & Owner (kelola)'],
    features: [
      'Clock in & clock out digital',
      'Deteksi terlambat otomatis',
      'Koreksi absensi oleh supervisor',
      'Laporan kehadiran bulanan',
      'Export untuk payroll',
    ],
    steps: [
      'Buka aplikasi → klik Clock In',
      'Klik Clock Out saat selesai kerja',
      'Supervisor koreksi jika ada kesalahan',
    ],
    relations: [
      { icon: '📅', menu: 'Jadwal Shift', description: 'Jadwal jadi acuan kehadiran' },
      { icon: '💰', menu: 'Laporan Karyawan', description: 'Data kehadiran masuk laporan performa' },
    ],
  },

  '/shifts': {
    title: 'Manajemen Shift Kasir',
    description: 'Buka dan tutup shift kasir, hitung rekap kas, dan kelola serah terima antar kasir.',
    access: ['Kasir', 'Supervisor', 'Owner'],
    features: [
      'Buka shift dengan saldo awal kas',
      'Ringkasan transaksi real-time',
      'Tutup shift + hitung kas aktual',
      'Deteksi selisih kas otomatis',
      'Serah terima antar kasir',
    ],
    steps: [
      'Login → sistem minta buka shift',
      'Input saldo awal kas di laci',
      'Transaksi berjalan normal',
      'Tutup shift → hitung uang tunai aktual',
      'Konfirmasi → laporan shift tersimpan',
    ],
    relations: [
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Semua transaksi tercatat ke shift aktif' },
      { icon: '💰', menu: 'Laporan Keuangan', description: 'Rekap kas shift masuk laporan' },
      { icon: '👤', menu: 'Absensi', description: 'Shift tercatat sebagai jam kerja' },
    ],
  },

  // ─────────────────────────────────────────────
  // ORDER ONLINE
  // ─────────────────────────────────────────────

  '/online-orders': {
    title: 'Order Online (GoFood/GrabFood)',
    description: 'Terima dan kelola order dari GoFood dan GrabFood. Order masuk langsung ke kitchen display.',
    access: ['Kitchen', 'Bar', 'Kasir', 'Supervisor', 'Owner'],
    features: [
      'Terima order GoFood & GrabFood real-time',
      'Order masuk ke kitchen display otomatis',
      'Update status sync ke platform',
      'Riwayat order online',
      'Laporan per platform',
    ],
    steps: [
      'Setup integrasi di Pengaturan → Integrasi',
      'Order online masuk otomatis ke kitchen',
      'Flow sama dengan order internal',
      'Status update sync ke GoFood/GrabFood',
    ],
    relations: [
      { icon: '🍳', menu: 'Kitchen Display', description: 'Order online tampil di kitchen display' },
      { icon: '📦', menu: 'Stok', description: 'Stok berkurang sama seperti order internal' },
      { icon: '💰', menu: 'Laporan', description: 'Revenue online masuk laporan terpisah' },
    ],
  },

  // ─────────────────────────────────────────────
  // LAPORAN & ERP
  // ─────────────────────────────────────────────

  '/reports/sales': {
    title: 'Laporan Penjualan',
    description: 'Laporan revenue, order, diskon, dan tren penjualan dengan berbagai filter dan periode.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Filter periode, outlet, kasir, produk',
      'Chart tren harian/mingguan/bulanan',
      'Breakdown per metode bayar',
      'Top produk terlaris',
      'Perbandingan vs periode sebelumnya',
    ],
    steps: [
      'Buka Laporan → Penjualan',
      'Set periode & filter',
      'Klik Generate',
      'Drill down ke detail transaksi per hari',
      'Export PDF/Excel jika perlu',
    ],
    relations: [
      { icon: '🛒', menu: 'Order & Transaksi', description: 'Sumber data laporan penjualan' },
      { icon: '🎫', menu: 'Laporan Promo', description: 'Diskon dari promo tercatat di sini' },
    ],
  },

  '/reports/stock': {
    title: 'Laporan Stok & Inventori',
    description: 'Laporan mutasi stok, hasil opname, kerugian expired, dan HPP vs harga jual.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Mutasi stok per periode',
      'Laporan hasil opname',
      'Kerugian stok expired',
      'HPP vs margin per produk',
    ],
    steps: [
      'Buka Laporan → Stok',
      'Pilih jenis laporan',
      'Set periode & filter produk',
      'Generate & export',
    ],
    relations: [
      { icon: '📦', menu: 'Stok', description: 'Data dari mutasi stok' },
      { icon: '⏰', menu: 'Expired', description: 'Kerugian expired masuk laporan' },
    ],
  },

  '/reports/finance': {
    title: 'Laporan Keuangan',
    description: 'Mini ERP: ringkasan keuangan, piutang outstanding, pengeluaran operasional, dan laba rugi.',
    access: ['Owner'],
    features: [
      'Revenue, HPP, gross profit',
      'Piutang outstanding (kasbon + PO)',
      'Pengeluaran operasional',
      'Net profit estimasi',
      'Rekap kas per shift',
    ],
    steps: [
      'Buka Laporan → Keuangan',
      'Lihat ringkasan atau pilih laporan spesifik',
      'Set periode',
      'Export untuk keperluan akuntansi',
    ],
    relations: [
      { icon: '💳', menu: 'Kasbon & Cicilan', description: 'Piutang dari kasbon masuk laporan' },
      { icon: '📋', menu: 'PO/DP', description: 'DP pending masuk pendapatan tertunda' },
      { icon: '⏰', menu: 'Shift Kasir', description: 'Rekap kas shift masuk laporan' },
    ],
  },

  '/reports/employees': {
    title: 'Laporan Karyawan',
    description: 'Laporan kehadiran dan performa kasir per shift.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Kehadiran: hadir, terlambat, absen, cuti',
      'Persentase kehadiran per karyawan',
      'Performa kasir: transaksi, revenue, selisih kas',
      'Ranking kasir by performance',
      'Export untuk payroll',
    ],
    steps: [
      'Buka Laporan → Karyawan',
      'Pilih karyawan & bulan',
      'Lihat detail kehadiran & performa',
      'Export PDF/Excel',
    ],
    relations: [
      { icon: '🕐', menu: 'Absensi', description: 'Data dari clock in/out' },
      { icon: '⏰', menu: 'Shift Kasir', description: 'Performa kasir dari data shift' },
    ],
  },

  '/reports/members': {
    title: 'Laporan Member & Loyalty',
    description: 'Analitik pertumbuhan member, distribusi tier, dan penggunaan poin loyalty.',
    access: ['Owner'],
    features: [
      'Pertumbuhan member baru per bulan',
      'Distribusi tier Bronze/Silver/Gold',
      'Poin dikeluarkan vs diredeem',
      'Customer paling aktif',
      'Rata-rata spending per member',
    ],
    steps: [
      'Buka Laporan → Member',
      'Pilih jenis analitik',
      'Set periode',
      'Lihat insight & export',
    ],
    relations: [
      { icon: '🎁', menu: 'Membership & Loyalty', description: 'Data dari program loyalty' },
      { icon: '👥', menu: 'Customer Database', description: 'Profil customer jadi sumber data' },
    ],
  },

  '/reports/promos': {
    title: 'Laporan Promo & Diskon',
    description: 'Statistik penggunaan promo dan analitik efektivitas setiap promo yang berjalan.',
    access: ['Owner'],
    features: [
      'Berapa kali promo dipakai',
      'Total diskon diberikan per promo',
      'Revenue dari transaksi yang pakai promo',
      'ROI promo: revenue tambahan vs diskon',
    ],
    steps: [
      'Buka Laporan → Promo',
      'Pilih promo atau lihat semua',
      'Set periode',
      'Analisis efektivitas',
    ],
    relations: [
      { icon: '🎫', menu: 'Template Promo', description: 'Data dari pemakaian promo' },
      { icon: '🛒', menu: 'Laporan Penjualan', description: 'Revenue periode promo vs non-promo' },
    ],
  },

  '/reports/outlets': {
    title: 'Laporan Multi Outlet',
    description: 'Perbandingan performa semua outlet dalam satu tampilan.',
    access: ['Owner'],
    features: [
      'Revenue per outlet',
      'Jumlah order per outlet',
      'Produk terlaris per outlet',
      'Growth vs periode sebelumnya',
      'Chart perbandingan visual',
    ],
    steps: [
      'Buka Laporan → Multi Outlet',
      'Set periode',
      'Lihat perbandingan semua outlet',
      'Klik outlet tertentu untuk detail',
    ],
    relations: [
      { icon: '🏪', menu: 'Outlet & Cabang', description: 'Data dari semua outlet yang aktif' },
      { icon: '💰', menu: 'Laporan Penjualan', description: 'Revenue per outlet' },
    ],
  },

  '/expenses': {
    title: 'Pengeluaran Operasional',
    description: 'Catat dan pantau pengeluaran operasional restoran untuk laporan keuangan.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Input pengeluaran per kategori',
      'Riwayat pengeluaran',
      'Total per kategori per periode',
      'Masuk ke laporan laba rugi',
    ],
    steps: [
      'Buka menu Pengeluaran',
      'Klik "Catat Pengeluaran"',
      'Isi kategori, nominal, tanggal, keterangan',
      'Simpan → masuk laporan keuangan',
    ],
    relations: [
      { icon: '💰', menu: 'Laporan Keuangan', description: 'Pengeluaran masuk perhitungan net profit' },
    ],
  },

  // ─────────────────────────────────────────────
  // PENGATURAN
  // ─────────────────────────────────────────────

  '/settings/outlets': {
    title: 'Outlet & Cabang',
    description: 'Kelola semua outlet dan konfigurasi spesifik per outlet.',
    access: ['Owner'],
    features: [
      'Tambah outlet baru',
      'Konfigurasi workflow status order per outlet',
      'Toggle bar approval per outlet',
      'Assign karyawan per outlet',
      'Harga khusus per outlet',
    ],
    steps: [
      'Buka Pengaturan → Outlet',
      'Klik "Tambah Outlet"',
      'Isi nama, alamat, telepon',
      'Konfigurasi workflow & setting',
      'Assign karyawan',
    ],
    relations: [
      { icon: '👤', menu: 'Karyawan', description: 'Karyawan di-assign ke outlet' },
      { icon: '🏷️', menu: 'Multi Harga', description: 'Harga bisa beda per outlet' },
      { icon: '💰', menu: 'Laporan Multi Outlet', description: 'Performa per outlet' },
    ],
  },

  '/settings/rbac': {
    title: 'Role & Hak Akses (RBAC)',
    description: 'Kelola role karyawan dan hak akses per menu. Setiap role punya permission yang berbeda.',
    access: ['Owner'],
    features: [
      'Buat role custom',
      'Set permission per role',
      'Assign role ke karyawan',
      'Kelola approval rules',
    ],
    steps: [
      'Buka Pengaturan → Role & Akses',
      'Pilih role yang ingin diedit',
      'Toggle permission yang diizinkan',
      'Simpan → berlaku langsung',
    ],
    relations: [
      { icon: '👤', menu: 'Karyawan', description: 'Setiap karyawan punya role' },
      { icon: '✅', menu: 'Approval Rules', description: 'Setting siapa yang bisa approve apa' },
    ],
  },

  '/settings/payment': {
    title: 'Konfigurasi Payment Gateway',
    description: 'Setup dan kelola integrasi payment gateway untuk QRIS, GoPay, OVO, Dana, dan ShopeePay.',
    access: ['Owner'],
    features: [
      'Connect Midtrans / Xendit / dll',
      'Setup webhook URL',
      'Aktifkan/nonaktifkan metode bayar',
      'Test koneksi',
    ],
    steps: [
      'Buka Pengaturan → Payment Gateway',
      'Pilih provider',
      'Input API key & secret',
      'Setup webhook URL',
      'Test koneksi → aktifkan',
    ],
    relations: [
      { icon: '💳', menu: 'Pembayaran', description: 'Metode bayar di order dari sini' },
      { icon: '📱', menu: 'QR Self-Order', description: 'Customer bayar via payment gateway' },
    ],
  },

  '/settings/printer': {
    title: 'Konfigurasi Printer',
    description: 'Setup printer thermal atau dot matrix untuk cetak struk di kasir.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Support thermal & dot matrix',
      'Koneksi USB atau jaringan (IP)',
      'Test print',
      'Set default struk: print/WA/skip',
    ],
    steps: [
      'Buka Pengaturan → Printer',
      'Pilih jenis printer',
      'Set koneksi (USB/IP)',
      'Test print',
      'Set default behavior struk',
    ],
    relations: [
      { icon: '🧾', menu: 'Struk', description: 'Printer dipakai untuk cetak struk transaksi' },
    ],
  },

  '/settings/notifications': {
    title: 'Notifikasi & Alert',
    description: 'Kelola setting notifikasi per jenis event. Bisa via app, WhatsApp, atau email.',
    access: ['Supervisor', 'Owner'],
    features: [
      'Toggle per jenis notifikasi',
      'Pilih channel: app / WA / email',
      'Set penerima per jenis notif',
      'Setup WhatsApp Business API',
      'Edit template pesan WA',
    ],
    steps: [
      'Buka Pengaturan → Notifikasi',
      'Toggle notifikasi yang diinginkan',
      'Pilih channel pengiriman',
      'Setup WA jika belum',
      'Test kirim notifikasi',
    ],
    relations: [
      { icon: '💳', menu: 'Kasbon', description: 'Notif reminder jatuh tempo kasbon' },
      { icon: '⏰', menu: 'Expired', description: 'Notif produk akan expired' },
      { icon: '✅', menu: 'Approval', description: 'Notif approval request & hasil' },
    ],
  },

  '/settings/integrations': {
    title: 'Integrasi GoFood & GrabFood',
    description: 'Hubungkan POS Mentai dengan platform GoFood dan GrabFood untuk terima order online otomatis.',
    access: ['Owner'],
    features: [
      'Connect GoFood & GrabFood',
      'Map produk POS ke menu platform',
      'Order online masuk kitchen otomatis',
      'Status sync real-time ke platform',
    ],
    steps: [
      'Buka Pengaturan → Integrasi',
      'Pilih platform (GoFood/GrabFood)',
      'Input credentials API',
      'Map produk POS ke menu platform',
      'Aktifkan → order online mulai masuk',
    ],
    relations: [
      { icon: '🍳', menu: 'Kitchen Display', description: 'Order online masuk ke kitchen' },
      { icon: '💰', menu: 'Laporan Online', description: 'Revenue online masuk laporan' },
    ],
  },

  '/settings/approval': {
    title: 'Approval Rules',
    description: 'Konfigurasi transaksi mana yang butuh approval, siapa approver-nya, dan mekanismenya.',
    access: ['Owner'],
    features: [
      'Set jenis transaksi yang butuh approval',
      'Pilih approver per jenis transaksi',
      'Mekanisme: PIN di tempat atau notif remote',
      'Set threshold nominal',
      'Set waktu eskalasi',
    ],
    steps: [
      'Buka Pengaturan → Approval Rules',
      'Pilih jenis transaksi',
      'Set approver & mekanisme',
      'Set threshold & waktu eskalasi',
      'Simpan',
    ],
    relations: [
      { icon: '✅', menu: 'Approval', description: 'Rules ini dipakai saat approval request' },
      { icon: '🔐', menu: 'RBAC', description: 'Approver berdasarkan role' },
    ],
  },

  '/settings/backup': {
    title: 'Backup & Keamanan Data',
    description: 'Pantau status backup otomatis dan kelola keamanan data sistem.',
    access: ['Owner'],
    features: [
      'Backup otomatis harian (jam 02.00)',
      'Retensi backup 30 hari',
      'Manual backup kapan saja',
      'Enkripsi backup di cloud',
    ],
    steps: [
      'Buka Pengaturan → Backup',
      'Lihat status backup terakhir',
      'Klik "Backup Sekarang" untuk manual backup',
      'Hubungi support untuk restore',
    ],
    relations: [
      { icon: '🔐', menu: 'Keamanan', description: 'Backup adalah bagian dari strategi keamanan data' },
    ],
  },

}

// Default FAQ jika route tidak match
export const defaultFaq = {
  title: 'POS Mentai',
  description: 'Sistem Point of Sale lengkap untuk restoran. Navigasi ke halaman tertentu untuk melihat FAQ yang relevan.',
  access: [],
  features: [
    'Order & transaksi multi-channel',
    'Kitchen display real-time',
    'Membership & loyalty',
    'Laporan & mini ERP',
  ],
  steps: ['Pilih menu di navigasi untuk melihat FAQ spesifik halaman tersebut'],
  relations: [],
}
