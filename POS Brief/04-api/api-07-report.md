# API Endpoints — 07. Laporan & ERP

Base URL: `/api/v1`
Auth: Bearer Token (JWT)
Role: owner (semua), supervisor (sebagian)

---

## DASHBOARD

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/dashboard` | Ringkasan dashboard hari ini | owner, supervisor |
| GET | `/outlets/:outletId/dashboard/summary` | Summary keuangan (harian/mingguan/bulanan) | owner |
| GET | `/outlets/:outletId/dashboard/comparison` | Perbandingan periode | owner |

### GET /outlets/:outletId/dashboard
Response:
```json
{
  "today": {
    "revenue": "3500000",
    "orders": 87,
    "avg_order_value": "40230",
    "top_product": "Mentai Rice",
    "active_shift": { "cashier": "Budi", "opened_at": "07:30" }
  },
  "alerts": {
    "low_stock": 3,
    "expired_soon": 1,
    "pending_approval": 2,
    "kasbon_due": 1
  }
}
```

---

## LAPORAN PENJUALAN

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/reports/sales` | Laporan penjualan | owner |
| GET | `/outlets/:outletId/reports/sales/by-product` | Penjualan per produk | owner |
| GET | `/outlets/:outletId/reports/sales/by-category` | Penjualan per kategori | owner |
| GET | `/outlets/:outletId/reports/sales/by-cashier` | Penjualan per kasir | owner, supervisor |
| GET | `/outlets/:outletId/reports/sales/by-hour` | Penjualan per jam (heatmap) | owner |
| GET | `/outlets/:outletId/reports/sales/by-payment` | Breakdown per metode bayar | owner |
| GET | `/outlets/:outletId/reports/sales/online` | Penjualan order online (GoFood/GrabFood) | owner |

### GET /outlets/:outletId/reports/sales
```
Query params:
  - start_date: ISO date (required)
  - end_date: ISO date (required)
  - group_by: day | week | month
```
Response:
```json
{
  "period": { "start": "2024-01-01", "end": "2024-01-31" },
  "total_revenue": "85000000",
  "total_orders": 2100,
  "total_discount": "3500000",
  "net_revenue": "81500000",
  "data": [
    { "date": "2024-01-01", "revenue": "3000000", "orders": 75 }
  ]
}
```

---

## LAPORAN PRODUK & STOK

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/reports/products/top-selling` | Produk terlaris | owner |
| GET | `/outlets/:outletId/reports/products/slow-moving` | Produk kurang laku | owner |
| GET | `/outlets/:outletId/reports/stock/movement` | Mutasi stok periode | owner |
| GET | `/outlets/:outletId/reports/stock/opname` | Laporan stok opname | owner |
| GET | `/outlets/:outletId/reports/stock/expired-loss` | Kerugian karena expired | owner |
| GET | `/outlets/:outletId/reports/stock/hpp` | Laporan HPP vs harga jual | owner |

---

## LAPORAN KEUANGAN

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/reports/finance/summary` | Ringkasan keuangan | owner |
| GET | `/outlets/:outletId/reports/finance/receivables` | Piutang (kasbon + PO outstanding) | owner |
| GET | `/outlets/:outletId/reports/finance/expenses` | Pengeluaran operasional | owner |
| GET | `/outlets/:outletId/reports/finance/profit-loss` | Laba rugi sederhana | owner |
| GET | `/outlets/:outletId/reports/finance/shifts` | Rekap kas per shift | owner, supervisor |

---

## LAPORAN KARYAWAN

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/reports/employees/attendance` | Laporan kehadiran | owner |
| GET | `/outlets/:outletId/reports/employees/performance` | Performa kasir (transaksi per shift) | owner |

---

## LAPORAN PROMO & DISKON

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/reports/promos/usage` | Pemakaian promo | owner |
| GET | `/outlets/:outletId/reports/promos/effectiveness` | Efektivitas promo (revenue vs diskon) | owner |

---

## LAPORAN MEMBER & LOYALTY

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/reports/members/growth` | Pertumbuhan member | owner |
| GET | `/outlets/:outletId/reports/members/tier-distribution` | Distribusi tier member | owner |
| GET | `/outlets/:outletId/reports/members/loyalty-usage` | Pemakaian poin loyalty | owner |

---

## LAPORAN MULTI OUTLET

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/reports/outlets/comparison` | Perbandingan performa antar outlet | owner |
| GET | `/reports/outlets/summary` | Summary semua outlet | owner |

---

## EXPORT

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| POST | `/outlets/:outletId/reports/export` | Export laporan (PDF/Excel) | owner |

### POST /outlets/:outletId/reports/export
```json
{
  "report_type": "sales",
  "format": "excel",
  "start_date": "2024-01-01",
  "end_date": "2024-01-31",
  "params": {
    "group_by": "day"
  }
}
```
Response:
```json
{
  "download_url": "https://...",
  "expires_at": "2024-02-01T12:00:00Z"
}
```

---

## PENGELUARAN OPERASIONAL

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/expenses` | List pengeluaran | owner, supervisor |
| POST | `/outlets/:outletId/expenses` | Catat pengeluaran | owner, supervisor |
| PUT | `/outlets/:outletId/expenses/:id` | Update pengeluaran | owner |
| DELETE | `/outlets/:outletId/expenses/:id` | Hapus pengeluaran | owner |

### POST /outlets/:outletId/expenses
```json
{
  "category": "operasional",
  "description": "Beli gas LPG",
  "amount": "50000",
  "date": "2024-01-15",
  "notes": null
}
```
