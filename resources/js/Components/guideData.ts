export interface GuideItem {
    title: string;
    description: string;
    flow: string[];
    relations: string[];
}

export const guideData: Record<string, GuideItem> = {
    // 1. Dashboard
    'dashboard': {
        title: 'Dashboard Utama',
        description: 'Pusat pemantauan performa outlet secara real-time. Menampilkan ringkasan omset harian, jumlah transaksi, rata-rata penjualan, performa produk terlaris, serta status operasional kasir dan shift yang sedang berjalan.',
        flow: [
            'Buka dashboard untuk memantau ringkasan omset dan grafik penjualan hari ini.',
            'Periksa kartu informasi "Shift Aktif" untuk melihat kasir yang sedang bertugas.',
            'Pantau widget alert untuk mengetahui jika ada produk dengan stok menipis (Low Stock) atau mendekati tanggal kedaluwarsa (Expired).'
        ],
        relations: ['Kasir & Order Aktif', 'Transaksi & Riwayat', 'Laporan Transaksi & Kas', 'Stok & HPP']
    },

    // 2. Meja
    'tables.layout': {
        title: 'Operasional Meja',
        description: 'Tampilan visual layout meja restoran untuk melacak meja kosong, terisi, atau reservasi. Memudahkan kasir dan waiter untuk melayani pelanggan dine-in langsung dari peta meja.',
        flow: [
            'Pilih area atau lantai meja pada opsi filter atas.',
            'Perhatikan warna meja: Hijau (Kosong), Merah (Terisi), Kuning (Reservasi/Booked).',
            'Klik pada salah satu meja untuk membuka detail pemesanan, menambah menu, atau langsung memproses checkout pembayaran.',
            'Gunakan fitur pindah meja jika pelanggan ingin pindah lokasi duduk.'
        ],
        relations: ['Daftar Meja', 'Kasir & Order Aktif', 'QR Meja']
    },
    'settings.tables.index': {
        title: 'Kelola Daftar Meja',
        description: 'Menu administrasi untuk mendaftarkan meja fisik restoran ke dalam sistem. Di sini Anda bisa mengelompokkan meja berdasarkan lantai/area dan kapasitas kursi.',
        flow: [
            'Klik tombol "Tambah Meja" di kanan atas.',
            'Masukkan nomor/nama meja, tentukan kapasitas maksimal kursi, serta pilih area/lantai.',
            'Simpan meja baru. Meja tersebut akan otomatis muncul pada peta layout meja operasional.'
        ],
        relations: ['Operasional Meja', 'QR Meja']
    },
    'settings.table-qr.index': {
        title: 'Konfigurasi QR Meja',
        description: 'Fitur untuk membuat, mengunduh, dan mencetak QR Code unik bagi setiap meja. Pelanggan dapat men-scan QR ini menggunakan smartphone untuk memesan makanan secara mandiri (Self-Service).',
        flow: [
            'Pilih meja dari daftar yang tersedia.',
            'Klik tombol "Generate/Download QR Code" untuk mengunduh gambar QR.',
            'Cetak QR Code dan letakkan pada meja fisik restoran Anda.',
            'Aktifkan opsi "Self-Service Ordering" jika ingin mengizinkan checkout mandiri via QR.'
        ],
        relations: ['Operasional Meja', 'Kelola Daftar Meja']
    },

    // 3. Kasir & Order Aktif (Sub-tabs)
    'kasir.order#new_order': {
        title: 'Kasir - Buat Order Baru',
        description: 'Halaman kerja utama kasir untuk meng-input pesanan pelanggan. Dilengkapi dengan filter kategori produk, kolom pencarian cepat, pemilihan tipe order (Dine-in / Takeaway / Delivery), serta input diskon/voucher.',
        flow: [
            'Pilih kategori produk atau cari nama menu menggunakan kolom pencarian.',
            'Klik produk untuk memasukkannya ke keranjang belanja.',
            'Untuk dine-in, pilih nomor meja aktif. Untuk takeaway/delivery, masukkan nama pelanggan.',
            'Terapkan diskon manual atau voucher promo jika ada.',
            'Klik "Bayar Sekarang" untuk memproses pembayaran langsung, atau "Simpan Order" untuk bayar nanti.'
        ],
        relations: ['Kasir - Order Aktif', 'Transaksi & Riwayat', 'Operasional Meja', 'Template Promo']
    },
    'kasir.order#active_orders': {
        title: 'Kasir - Order Aktif & Antrean',
        description: 'Daftar transaksi berjalan yang belum dibayar atau sedang dalam proses penyajian di dapur. Kasir dapat memantau status pesanan, melakukan split bill (pisah tagihan), merge bill (gabung tagihan), atau cetak bill sementara.',
        flow: [
            'Pilih sub-tab "Order Aktif" di halaman kasir.',
            'Cari order berdasarkan nomor meja, nama pelanggan, atau ID transaksi.',
            'Klik transaksi untuk melihat detail item, menambah pesanan baru, melakukan split bill, atau memproses pembayaran.',
            'Gunakan tombol "Cetak Bill" untuk memberikan struk tagihan sementara kepada pelanggan.'
        ],
        relations: ['Kasir - Buat Order Baru', 'Transaksi & Riwayat', 'Kitchen Display']
    },

    // 4. Transaksi & Riwayat
    'transactions.index': {
        title: 'Transaksi & Riwayat',
        description: 'Daftar riwayat seluruh transaksi penjualan yang telah selesai, dibatalkan, atau masih berstatus kasbon (piutang). Di halaman ini Anda dapat memproses refund (void), cicilan kasbon, dan mencetak ulang struk pembayaran belanja.',
        flow: [
            'Gunakan filter tanggal atau kolom cari untuk menemukan struk transaksi masa lalu.',
            'Klik baris transaksi untuk melihat detail belanjaan, cara pembayaran, dan petugas kasir yang melayani.',
            'Pilih "Cetak Struk" untuk mencetak ulang tanda terima pembayaran.',
            'Gunakan tombol "Void/Refund" untuk membatalkan transaksi salah input (memerlukan approval supervisor jika dikonfigurasi).',
            'Untuk transaksi kasbon, klik "Bayar Kasbon/Cicilan" untuk mencatat pembayaran piutang pelanggan.'
        ],
        relations: ['Kasir & Order Aktif', 'Laporan Transaksi & Kas', 'Aturan Approval SPV']
    },

    // 5. Payment & Printer
    'settings.payment-gateway.index': {
        title: 'Pengaturan Payment Gateway',
        description: 'Konfigurasi integrasi gerbang pembayaran otomatis seperti QRIS, e-wallet (GoPay, OVO, Dana), dan transfer bank. Memungkinkan verifikasi status pembayaran pelanggan secara otomatis dan real-time tanpa perlu cek mutasi manual.',
        flow: [
            'Pilih penyedia payment gateway (misalnya Midtrans atau LinkAja/QRIS Dinamis).',
            'Masukkan API Key dan kode Merchant ID yang Anda miliki.',
            'Lakukan tes koneksi pembayaran untuk memastikan setup integrasi sudah berhasil.',
            'Aktifkan metode pembayaran otomatis pada halaman kasir.'
        ],
        relations: ['Kasir - Buat Order Baru', 'Transaksi & Riwayat']
    },
    'settings.printer.index': {
        title: 'Konfigurasi Printer Kasir',
        description: 'Pengaturan koneksi printer thermal menggunakan jaringan LAN, Bluetooth, atau USB. Digunakan untuk memisahkan pencetakan struk kasir, tiket dapur (kitchen ticket), dan tiket minuman (bar ticket).',
        flow: [
            'Pilih tipe printer (Kasir / Dapur / Bar).',
            'Pilih jenis koneksi (Bluetooth / LAN / USB).',
            'Masukkan alamat IP printer jika menggunakan koneksi LAN, atau pairing bluetooth jika nirkabel.',
            'Klik "Test Print" untuk memastikan koneksi printer berjalan dengan baik.',
            'Tentukan format struk (lebar kertas 58mm atau 80mm).'
        ],
        relations: ['Kasir & Order Aktif', 'Kitchen Display', 'Approval Bar']
    },

    // 6. Kitchen & Bar Display
    'kitchen.display': {
        title: 'Kitchen Display System (KDS)',
        description: 'Layar monitor digital di area dapur yang menampilkan antrean pesanan makanan secara real-time. Membantu chef melacak urutan memasak, durasi persiapan, dan memberi notifikasi ke pelayan saat makanan siap saji.',
        flow: [
            'Layar menampilkan kartu pesanan berdasarkan waktu masuk (antrean terlama di posisi paling awal).',
            'Klik "Mulai Masak" ketika hidangan mulai diproses oleh kru dapur.',
            'Klik "Selesai" jika makanan sudah matang dan siap disajikan.',
            'Gunakan filter kategori jika ingin membatasi layar KDS hanya untuk stasiun makanan tertentu.'
        ],
        relations: ['Kasir - Order Aktif', 'Approval Bar', 'Konfigurasi Printer']
    },
    'bar.display': {
        title: 'Approval Bar (KDS Minuman)',
        description: 'Layar monitor digital di area bar yang menampilkan antrean pesanan minuman dan hidangan dingin secara real-time. Membantu barista memproses orderan minuman secara cepat dan akurat.',
        flow: [
            'Pantau pesanan minuman baru yang masuk di layar Bar.',
            'Klik "Proses" untuk mulai meracik minuman.',
            'Klik "Selesai" untuk memperbarui status pesanan menjadi siap saji.',
            'Pesanan yang telah selesai akan mengirimkan notifikasi siap antar kepada pelayan.'
        ],
        relations: ['Kasir - Order Aktif', 'Kitchen Display', 'Konfigurasi Printer']
    },

    // 7. Pelanggan & CRM
    'customers.index': {
        title: 'CRM Pelanggan',
        description: 'Database pelanggan terpusat untuk menyimpan informasi kontak, riwayat belanja, catatan kasbon, serta akumulasi poin loyalitas. Membantu Anda merancang strategi pemasaran yang lebih personal.',
        flow: [
            'Klik "Tambah Pelanggan" untuk mendaftarkan member baru.',
            'Ketik nama, nomor telepon, dan email pelanggan.',
            'Lihat detail profil member untuk mengecek poin loyalitas yang terkumpul serta total belanja mereka.',
            'Gunakan filter pelanggan kasbon untuk melacak member yang memiliki piutang berjalan.'
        ],
        relations: ['Kelola Tier Member', 'Kasir - Buat Order Baru', 'Transaksi & Riwayat']
    },
    'settings.membership-tiers.index': {
        title: 'Kelola Tier Member',
        description: 'Pengaturan level loyalitas pelanggan (misalnya: Bronze, Silver, Gold). Di sini Anda dapat menentukan syarat minimum belanja untuk naik tingkat dan besaran rasio penukaran poin belanja.',
        flow: [
            'Klik "Tambah Tier" atau pilih tier yang sudah ada untuk diedit.',
            'Tentukan batas minimum poin atau nominal transaksi tahunan untuk mencapai tier tersebut.',
            'Atur rasio perolehan poin (contoh: Belanja Rp 10.000 dapat 1 poin) dan diskon khusus tier tersebut.',
            'Simpan pengaturan untuk langsung diimplementasikan pada sistem CRM.'
        ],
        relations: ['CRM Pelanggan', 'Template Promo']
    },

    // 8. Produk & Stok
    'products.index': {
        title: 'Katalog Produk',
        description: 'Manajemen master data produk makanan, minuman, dan merchandise. Menu ini digunakan untuk mengatur nama menu, foto, harga jual multi-outlet, varian produk (misal: Level Pedas, Toping), serta link resep bahan baku.',
        flow: [
            'Klik "Tambah Produk" di halaman katalog.',
            'Isi info dasar seperti nama produk, deskripsi, kategori, dan upload foto terbaik.',
            'Pilih tab "Varian" jika menu memiliki pilihan ukuran atau toping tambahan.',
            'Atur "Multi Harga" jika harga menu berbeda antara dine-in, takeaway, atau gofood/grabfood.',
            'Hubungkan dengan "Resep" untuk pemotongan bahan baku otomatis.'
        ],
        relations: ['Stok & HPP', 'Bahan Baku', 'Template Promo']
    },
    'products.stock': {
        title: 'Stok & HPP Produk',
        description: 'Menu untuk melihat status persediaan produk jadi, mencatat penyesuaian stok (stock opname), menghitung Nilai Persediaan, dan melacak Harga Pokok Penjualan (HPP) produk secara periodik.',
        flow: [
            'Periksa daftar stok produk untuk mendeteksi barang yang habis.',
            'Klik "Penyesuaian Stok" untuk mencocokkan jumlah stok sistem dengan stok fisik di gudang.',
            'Masukkan jumlah stok baru dan alasan penyesuaian (misal: Rusak, Selisih Opname).',
            'Lihat nilai HPP rata-rata yang dihitung otomatis dari riwayat pembelian bahan baku.'
        ],
        relations: ['Katalog Produk', 'Bahan Baku', 'Alert Stok Minimum']
    },
    'products.hpp': {
        title: 'HPP & Resep Bahan Baku',
        description: 'Menu khusus untuk merumuskan bahan baku penyusun sebuah menu produk (resep). Sistem otomatis menghitung total Harga Pokok Penjualan (HPP) berdasarkan harga beli bahan baku terbaru.',
        flow: [
            'Pilih menu makanan dari daftar produk.',
            'Masukkan daftar bahan baku yang digunakan beserta takaran porsinya (gram/ml).',
            'Sistem akan otomatis menghitung total HPP dan margin laba kotor produk.',
            'Perbarui resep jika ada perubahan komposisi atau porsi.'
        ],
        relations: ['Katalog Produk', 'Stok & HPP Produk', 'Kelola Bahan Baku']
    },
    'stock-alerts.index': {
        title: 'Alert Stok Minimum',
        description: 'Daftar produk jadi atau bahan baku yang jumlah persediaannya sudah berada di bawah batas aman (stok minimum). Membantu Anda melakukan re-order tepat waktu ke supplier.',
        flow: [
            'Layar menampilkan barang dengan stok kritis (warna merah) dan menipis (warna kuning).',
            'Pilih barang untuk membuat surat pesanan atau pengadaan stok baru.',
            'Sesuaikan batas stok minimum pada detail produk/bahan baku jika dirasa kurang ideal.'
        ],
        relations: ['Stok & HPP Produk', 'Kelola Bahan Baku']
    },
    'expired-tracking.index': {
        title: 'Expired Tracking',
        description: 'Fitur pelacakan masa kedaluwarsa bahan baku dan barang jadi. Menggunakan metode penomoran batch (FIFO) untuk memastikan kesegaran hidangan dan meminimalkan kerugian akibat bahan basi.',
        flow: [
            'Periksa daftar batch bahan baku beserta tanggal kedaluwarsanya.',
            'Gunakan filter untuk melihat bahan baku yang akan kedaluwarsa dalam 7 hari kedepan.',
            'Lakukan tindakan buang (waste) atau diskon produk sebelum kedaluwarsa.'
        ],
        relations: ['Stok & HPP Produk', 'Kelola Bahan Baku']
    },
    'raw-materials.index': {
        title: 'Kelola Bahan Baku',
        description: 'Manajemen persediaan bahan baku mentah (seperti beras, mentai sauce, salmon). Digunakan untuk mencatat stok masuk dari supplier, mengontrol stok minimum, dan melacak penyusutan bahan baku dapur.',
        flow: [
            'Daftarkan bahan baku baru beserta unit satuannya (gram, kg, liter, pcs).',
            'Klik "Tambah Stok/Pembelian" untuk mencatat pasokan barang masuk dari supplier.',
            'Tentukan batas stok aman (stok minimum) untuk memicu peringatan otomatis saat bahan baku menipis.',
            'Lakukan stock opname bahan baku secara berkala untuk mencatat sisa bahan mentah.'
        ],
        relations: ['Katalog Produk', 'Stok & HPP Produk', 'Alert Stok Minimum']
    },

    // 9. Promo & Diskon
    'promos.index': {
        title: 'Template Promo & Diskon',
        description: 'Fitur pembuatan promo penjualan untuk menarik pelanggan. Anda dapat membuat diskon persentase, diskon nominal, voucher belanja gratis, promo beli 1 gratis 1, serta mengatur tanggal aktif promo.',
        flow: [
            'Klik "Buat Promo Baru".',
            'Tentukan nama promo dan tipe (Diskon Persen / Diskon Nominal / Free Item).',
            'Atur aturan kelayakan promo (misal: minimal belanja Rp 50.000, atau hanya berlaku hari Jumat).',
            'Tentukan tanggal mulai dan berakhirnya promo.',
            'Klik simpan. Promo akan otomatis aktif dan dapat dipilih di layar kasir.'
        ],
        relations: ['Kasir - Buat Order Baru', 'CRM Pelanggan']
    },

    // 10. Karyawan & Shift
    'settings.outlets.index': {
        title: 'Manajemen Outlet & Cabang',
        description: 'Pengaturan data outlet/cabang bisnis Anda. Digunakan oleh pemilik bisnis untuk mendaftarkan alamat cabang baru, menetapkan nomor telepon outlet, dan melacak performa antar cabang.',
        flow: [
            'Klik "Tambah Outlet" untuk membuka form pendaftaran cabang baru.',
            'Isi data nama outlet, alamat lengkap, kontak, serta kapasitas meja.',
            'Gunakan toggle aktif/nonaktif untuk mengontrol status operasional outlet di sistem.'
        ],
        relations: ['Data Karyawan', 'Laporan Penjualan Per Outlet']
    },
    'employees.index': {
        title: 'Data Karyawan & Staff',
        description: 'Database karyawan yang mencakup informasi biodata, jabatan (Kasir, Barista, Kitchen, Supervisor, Admin), nomor telepon, dan status kepegawaian aktif di setiap outlet.',
        flow: [
            'Klik "Tambah Karyawan" untuk menambahkan staff baru.',
            'Masukkan nama karyawan, email, nomor HP, jabatan, dan tempatkan di outlet kerja mereka.',
            'Simpan data staff untuk mempermudah alokasi shift dan penjadwalan kerja.'
        ],
        relations: ['User & RBAC', 'Shift & Absensi', 'Jadwal Kerja Karyawan']
    },
    'settings.rbac.index#accounts': {
        title: 'RBAC - Kelola Akun Karyawan',
        description: 'Pemberian akses login bagi staff ke dalam aplikasi POS. Anda dapat membuat username, PIN login kasir cepat (4-6 digit), dan password akun untuk setiap karyawan yang terdaftar.',
        flow: [
            'Pilih nama karyawan dari daftar yang belum memiliki akun.',
            'Tentukan username login dan password/PIN unik.',
            'Pilih jenis role (Admin, Kasir, Supervisor, Chef) untuk menentukan template hak akses awal mereka.',
            'Kirim kredensial login kepada karyawan terkait secara aman.'
        ],
        relations: ['Data Karyawan & Staff', 'RBAC - Matrix Hak Akses']
    },
    'settings.rbac.index#matrix': {
        title: 'RBAC - Matrix Hak Akses',
        description: 'Pengaturan wewenang detail untuk setiap peran (Role Permission). Di sini Anda bisa menentukan secara spesifik menu apa saja yang boleh dibuka oleh Kasir, Kitchen, atau Supervisor (misalnya: hanya Supervisor yang boleh melakukan Void).',
        flow: [
            'Buka tab "Matrix Hak Akses".',
            'Centang kotak fitur pada kolom role yang sesuai (misal: centang opsi "Void Transaksi" hanya untuk kolom Supervisor).',
            'Klik "Simpan Matrix" untuk memperbarui hak akses secara real-time bagi semua pengguna.'
        ],
        relations: ['RBAC - Kelola Akun Karyawan', 'Aturan Approval SPV']
    },
    'shifts.index': {
        title: 'Shift & Rekap Kasir',
        description: 'Prosedur penting untuk menjaga kecocokan uang di laci kasir (cash drawer). Sebelum bertugas, kasir wajib melakukan "Buka Shift" dengan meng-input modal awal. Di akhir kerja, kasir melakukan "Tutup Shift" dengan menghitung uang fisik.',
        flow: [
            'Di awal hari, klik "Buka Shift", lalu masukkan jumlah uang modal awal laci kasir.',
            'Selama bertugas, sistem mencatat semua uang masuk dari transaksi cash/non-cash.',
            'Di akhir jam kerja, klik "Tutup Shift", hitung uang cash fisik di laci Anda, lalu masukkan nominalnya ke sistem.',
            'Periksa jika ada selisih (over/short) antara pencatatan sistem dan uang fisik, lalu isi catatan penjelas.',
            'Cetak struk rekap shift/kasir.'
        ],
        relations: ['Kasir & Order Aktif', 'Absensi Karyawan', 'Laporan Kinerja Kasir']
    },
    'attendance.index': {
        title: 'Absensi & Kehadiran',
        description: 'Menu pencatatan waktu masuk (Clock In) dan waktu pulang (Clock Out) staff menggunakan PIN kasir, foto selfie, atau deteksi lokasi GPS untuk memastikan kedisiplinan kerja.',
        flow: [
            'Karyawan memilih namanya pada layar absensi umum.',
            'Masukkan PIN keamanan pribadi.',
            'Klik "Clock In" saat mulai bekerja (sistem mencatat waktu persis).',
            'Klik "Clock Out" di akhir shift kerja untuk mengakhiri pencatatan waktu kehadiran.'
        ],
        relations: ['Data Karyawan & Staff', 'Jadwal Kerja Karyawan', 'Laporan Shift & Kehadiran']
    },
    'schedules.index': {
        title: 'Jadwal Kerja Karyawan',
        description: 'Fitur pembuatan jadwal shift kerja mingguan atau bulanan bagi staff outlet. Membantu mengontrol pembagian kerja agar operasional outlet selalu berjalan optimal.',
        flow: [
            'Pilih nama karyawan dan tanggal kalender kerja.',
            'Tentukan shift kerja (misal: Shift Pagi, Shift Sore, atau Libur).',
            'Gunakan tombol "Salin Jadwal" untuk menduplikasi jadwal ke minggu berikutnya secara cepat.',
            'Simpan jadwal. Jadwal ini akan membatasi jam absensi karyawan (mencegah Clock In terlalu awal).'
        ],
        relations: ['Data Karyawan & Staff', 'Absensi & Kehadiran']
    },
    'settings.approval-rules.index': {
        title: 'Aturan Approval Supervisor',
        description: 'Konfigurasi kontrol otorisasi transaksi sensitif seperti diskon besar, void (pembatalan order yang sudah dicetak), refund, dan retur barang. Menghindari potensi kecurangan (fraud) di level operasional.',
        flow: [
            'Pilih jenis tindakan yang membutuhkan izin (contoh: Void Transaksi).',
            'Aktifkan status "Butuh Approval".',
            'Pilih tingkatan role yang berhak memberi approval (misal: Supervisor atau Manajer).',
            'Tentukan apakah approval menggunakan input PIN cepat atau otorisasi remote.'
        ],
        relations: ['Transaksi & Riwayat', 'RBAC - Matrix Hak Akses']
    },

    // 11. Laporan & ERP (Tabs as routes)
    'reports.sales.index': {
        title: 'Laporan Penjualan & Kas',
        description: 'Laporan keuangan komprehensif yang menyajikan data penjualan kotor, diskon, pajak, pembatalan (void), net sales, serta rincian metode pembayaran (Tunai, QRIS, Kartu) dalam periode waktu tertentu.',
        flow: [
            'Tentukan filter rentang tanggal laporan (hari ini, 7 hari terakhir, atau kustom).',
            'Lihat grafik tren omset untuk menganalisis puncak penjualan.',
            'Periksa tabel metode pembayaran untuk mempermudah rekonsiliasi kas bank.',
            'Gunakan tombol "Ekspor PDF/Excel" jika membutuhkan laporan fisik.'
        ],
        relations: ['Transaksi & Riwayat', 'Laporan Per Outlet', 'Laporan Kinerja Kasir']
    },
    'reports.outlets.index': {
        title: 'Laporan Penjualan Per Outlet',
        description: 'Laporan perbandingan kinerja keuangan antar cabang outlet. Memudahkan pemilik bisnis memantau outlet mana yang memiliki kontribusi omset terbesar.',
        flow: [
            'Tentukan periode tanggal laporan.',
            'Bandingkan performa penjualan kotor dan bersih antar cabang pada tabel data.',
            'Analisis tingkat pertumbuhan omset masing-masing outlet.'
        ],
        relations: ['Laporan Penjualan & Kas', 'Manajemen Outlet & Cabang']
    },
    'reports.cashiers.index': {
        title: 'Laporan Kinerja Kasir',
        description: 'Laporan rekapitulasi penjualan berdasarkan staff kasir yang bertugas. Berguna untuk mengukur produktivitas staff dan melakukan audit pertanggungjawaban uang laci kasir.',
        flow: [
            'Pilih periode laporan kasir.',
            'Lihat total nominal transaksi yang ditangani oleh masing-masing kasir.',
            'Analisis jika ada selisih kas berulang pada staff tertentu.'
        ],
        relations: ['Laporan Penjualan & Kas', 'Shift & Rekap Kasir']
    },
    'reports.top-products.index': {
        title: 'Laporan Produk Terlaris',
        description: 'Analisis menu terlaris (Top Selling) berdasarkan kuantitas penjualan dan nilai omset. Membantu Anda mengidentifikasi produk favorit pelanggan untuk perencanaan stok dan promo.',
        flow: [
            'Pilih rentang tanggal analisis produk.',
            'Lihat grafik 10 produk teratas dengan volume penjualan tertinggi.',
            'Gunakan data ini untuk merancang paket bundling menu makanan.'
        ],
        relations: ['Laporan Penjualan & Kas', 'Katalog Produk']
    },
    'reports.expenses.index#expense': {
        title: 'Laporan Pengeluaran (Biaya)',
        description: 'Pencatatan pengeluaran biaya operasional outlet di luar HPP produk, seperti biaya sewa tempat, gaji staff harian, tagihan listrik, internet, dan pembelian inventori non-dapur.',
        flow: [
            'Pilih tab "Pengeluaran" pada menu keuangan.',
            'Klik "Catat Pengeluaran Baru", masukkan kategori biaya, nominal uang, serta unggah bukti kuitansi.',
            'Pantau rekap total pengeluaran bulanan untuk menjaga efisiensi anggaran belanja outlet.'
        ],
        relations: ['Laporan Pemasukan Lain-lain', 'Laporan Penjualan & Kas']
    },
    'reports.expenses.index#income': {
        title: 'Laporan Pemasukan Lain-lain',
        description: 'Pencatatan pendapatan tambahan outlet yang bukan bersumber dari penjualan menu makanan utama, misalnya pendapatan sewa space iklan, bunga bank, atau penjualan kardus bekas.',
        flow: [
            'Pilih tab "Pemasukan Lain-lain".',
            'Klik "Catat Pemasukan Baru", isi deskripsi sumber dana dan nominal uang.',
            'Simpan data untuk melengkapi laporan arus kas laba-rugi bersih outlet.'
        ],
        relations: ['Laporan Pengeluaran (Biaya)', 'Laporan Penjualan & Kas']
    },
    'reports.inventory.index': {
        title: 'Laporan Penggunaan Stok',
        description: 'Laporan terperinci mengenai mutasi keluar-masuk bahan baku, konsumsi bahan baku berdasarkan resep menu terjual, jumlah barang terbuang (waste/rusak), dan sisa nilai aset inventori.',
        flow: [
            'Pilih filter periode tanggal.',
            'Periksa jumlah pemakaian bahan baku riil vs estimasi resep sistem.',
            'Analisis persentase penyusutan (waste) untuk mengevaluasi efisiensi pengolahan bahan dapur.'
        ],
        relations: ['Laporan Shift Kehadiran', 'Stok & HPP Produk', 'Kelola Bahan Baku']
    },
    'reports.attendance-shifts.index': {
        title: 'Laporan Kehadiran Karyawan',
        description: 'Rekapitulasi total jam kerja, keterlambatan, jumlah absen, dan keaktifan shift masing-masing staff. Mempermudah HRD dalam melakukan rekapitulasi penggajian (payroll).',
        flow: [
            'Pilih periode rekap absensi staff.',
            'Lihat detail total hari kerja, jam masuk terlambat, dan cuti karyawan.',
            'Unduh laporan kehadiran untuk lampiran penggajian bulanan.'
        ],
        relations: ['Laporan Penggunaan Stok', 'Data Karyawan & Staff', 'Absensi & Kehadiran']
    },
    'reports.exports.index': {
        title: 'Ekspor & Impor Laporan',
        description: 'Menu untuk mengunduh laporan secara massal dalam format Excel/CSV atau meng-import data katalog produk awal untuk mempercepat setup database toko baru.',
        flow: [
            'Pilih jenis data yang ingin diekspor (Transaksi / Stok / Bahan Baku).',
            'Pilih format file (.xlsx atau .csv) dan klik "Unduh Laporan".',
            'Untuk impor data, unduh template file, isi sesuai petunjuk, lalu upload kembali ke sistem.'
        ],
        relations: ['Laporan Penjualan & Kas', 'Laporan Penggunaan Stok']
    },

    // 12. Keamanan & Notifikasi
    'settings.notifications.index': {
        title: 'Keamanan & Notifikasi WhatsApp',
        description: 'Pusat pengaturan notifikasi instan untuk keamanan dan operasional. Anda bisa mengaktifkan ringkasan omset otomatis via WhatsApp ke nomor owner, serta melihat log aktivitas keamanan database.',
        flow: [
            'Masukkan nomor WhatsApp utama pemilik bisnis.',
            'Pilih jenis notifikasi yang ingin dikirimkan secara otomatis (contoh: Laporan Tutup Shift, Notifikasi Void Transaksi).',
            'Aktifkan notifikasi dan lakukan kirim pesan uji coba (Test Send).',
            'Lihat log keamanan sistem untuk mendeteksi percobaan login tidak sah.'
        ],
        relations: ['Laporan Penjualan & Kas', 'Aturan Approval SPV']
    },
    'settings.backup-security.index': {
        title: 'Backup & Keamanan Data',
        description: 'Pengaturan pencadangan database sistem POS secara otomatis. Menghindari risiko kehilangan data akibat kerusakan server fisik atau kendala teknis lainnya.',
        flow: [
            'Atur jadwal backup otomatis (Harian / Mingguan / Bulanan).',
            'Pilih lokasi penyimpanan cadangan (Google Drive, Cloud Storage, atau Unduh Lokal).',
            'Klik "Jalankan Backup Sekarang" untuk membuat salinan database instan.'
        ],
        relations: ['Keamanan & Notifikasi WhatsApp']
    },

    // 13. Integrasi Online
    'settings.online-integrations.index': {
        title: 'Integrasi GoFood & GrabFood',
        description: 'Fitur sinkronisasi menu dan pesanan otomatis dengan platform pesan antar online pihak ketiga (GoFood, GrabFood, ShopeeFood). Membantu memproses seluruh orderan online langsung dari satu layar kasir POS.',
        flow: [
            'Pilih platform online yang ingin diintegrasikan.',
            'Masukkan kredensial Merchant API Key dari platform merchant Anda.',
            'Sinkronkan nama produk kasir dengan menu online platform.',
            'Aktifkan status integrasi agar pesanan online otomatis masuk ke antrean kasir POS Anda.'
        ],
        relations: ['Kasir & Order Aktif', 'Katalog Produk']
    }
};

// Help helper function to match the current route and sub-tab to guideData keys
export function getGuideKey(route: string, subTab: string | null = null): string {
    if (subTab) {
        const keyWithSubTab = `${route}#${subTab}`;
        if (guideData[keyWithSubTab]) {
            return keyWithSubTab;
        }
    }
    if (guideData[route]) {
        return route;
    }
    return 'dashboard';
}
