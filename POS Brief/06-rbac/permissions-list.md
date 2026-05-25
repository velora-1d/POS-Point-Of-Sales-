# RBAC — Daftar Permissions

Format: `module:action`

---

## Auth
| Permission | Keterangan |
|------------|------------|
| `auth:login` | Login ke sistem |
| `auth:logout` | Logout |

## Orders
| Permission | Keterangan |
|------------|------------|
| `order:read` | Lihat daftar & detail order |
| `order:create` | Buat order baru |
| `order:update` | Edit order (item, catatan) |
| `order:cancel` | Batalkan order |
| `order:update_status` | Update status order |
| `order:approve_edit` | Approve edit pesanan dari customer |
| `order:split_bill` | Split / gabung bill |
| `order:void` | Void transaksi (butuh approval) |

## Payments
| Permission | Keterangan |
|------------|------------|
| `payment:create` | Proses pembayaran |
| `payment:read` | Lihat riwayat pembayaran |
| `payment:refund` | Refund pembayaran (butuh approval) |
| `payment:void` | Void pembayaran (butuh approval) |

## Kitchen
| Permission | Keterangan |
|------------|------------|
| `kitchen:read` | Lihat antrian kitchen display |
| `kitchen:update_status` | Update status masak |
| `kitchen:approve` | Approve order selesai (Bar role) |

## Tables
| Permission | Keterangan |
|------------|------------|
| `table:read` | Lihat layout & status meja |
| `table:manage` | Tambah/edit/hapus meja |
| `table:qr_generate` | Generate QR code meja |

## Reservations
| Permission | Keterangan |
|------------|------------|
| `reservation:read` | Lihat reservasi |
| `reservation:create` | Buat reservasi |
| `reservation:update` | Update reservasi |
| `reservation:cancel` | Batalkan reservasi |

## Products
| Permission | Keterangan |
|------------|------------|
| `product:read` | Lihat katalog produk |
| `product:create` | Tambah produk |
| `product:update` | Edit produk |
| `product:delete` | Nonaktifkan produk |
| `product:toggle_availability` | Toggle produk tersedia/tidak |

## Stock
| Permission | Keterangan |
|------------|------------|
| `stock:read` | Lihat stok |
| `stock:add` | Tambah stok masuk |
| `stock:adjust` | Adjustment stok manual |
| `stock:write_off` | Write-off stok expired/rusak |

## Raw Materials
| Permission | Keterangan |
|------------|------------|
| `raw_material:read` | Lihat bahan baku |
| `raw_material:manage` | Kelola bahan baku |

## Customers
| Permission | Keterangan |
|------------|------------|
| `customer:read` | Lihat customer database |
| `customer:create` | Tambah customer |
| `customer:update` | Edit data customer |

## Membership & Loyalty
| Permission | Keterangan |
|------------|------------|
| `membership:read` | Lihat data membership |
| `membership:register` | Daftarkan customer jadi member |
| `membership:manage` | Kelola tier & konfigurasi loyalty |
| `loyalty:redeem` | Proses redeem poin |
| `loyalty:adjust` | Adjustment poin manual |

## Kasbon & Cicilan
| Permission | Keterangan |
|------------|------------|
| `kasbon:read` | Lihat kasbon |
| `kasbon:create` | Buat kasbon baru |
| `kasbon:pay` | Terima pembayaran kasbon |
| `kasbon:write_off` | Write-off kasbon |
| `cicilan:manage` | Kelola jadwal cicilan |

## PO / Down Payment
| Permission | Keterangan |
|------------|------------|
| `po:read` | Lihat PO/DP |
| `po:create` | Buat PO/DP |
| `po:pay` | Proses pembayaran PO/DP |
| `po:cancel` | Batalkan PO/DP |

## Promos
| Permission | Keterangan |
|------------|------------|
| `promo:read` | Lihat daftar promo |
| `promo:create` | Buat promo baru |
| `promo:update` | Edit promo |
| `promo:toggle` | Aktifkan/nonaktifkan promo |
| `promo:apply` | Apply promo/voucher ke order |

## Shifts
| Permission | Keterangan |
|------------|------------|
| `shift:open` | Buka shift |
| `shift:close` | Tutup shift |
| `shift:read` | Lihat riwayat shift |

## Attendance
| Permission | Keterangan |
|------------|------------|
| `attendance:clock_in` | Clock in |
| `attendance:clock_out` | Clock out |
| `attendance:read` | Lihat absensi |
| `attendance:correct` | Koreksi absensi |

## Employees
| Permission | Keterangan |
|------------|------------|
| `employee:read` | Lihat data karyawan |
| `employee:manage` | Tambah/edit/nonaktifkan karyawan |
| `schedule:manage` | Kelola jadwal shift |

## Approvals
| Permission | Keterangan |
|------------|------------|
| `approval:request` | Request approval |
| `approval:approve` | Approve transaksi |
| `approval:reject` | Tolak transaksi |
| `approval:rules_manage` | Kelola approval rules |

## Reports
| Permission | Keterangan |
|------------|------------|
| `report:sales` | Laporan penjualan |
| `report:stock` | Laporan stok |
| `report:finance` | Laporan keuangan |
| `report:employee` | Laporan karyawan |
| `report:export` | Export laporan |

## Settings
| Permission | Keterangan |
|------------|------------|
| `outlet:manage` | Kelola outlet & cabang |
| `rbac:manage` | Kelola role & permissions |
| `payment_gateway:manage` | Konfigurasi payment gateway |
| `integration:manage` | Konfigurasi GoFood/GrabFood |
| `notification:manage` | Setting notifikasi |
| `printer:manage` | Konfigurasi printer |
