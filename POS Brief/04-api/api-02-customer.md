# API Endpoints — 02. Customer

Base URL: `/api/v1`
Auth: Bearer Token (JWT)

---

## CUSTOMERS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/customers` | List semua customer | owner, supervisor, kasir |
| POST | `/outlets/:outletId/customers` | Tambah customer baru | owner, supervisor, kasir |
| GET | `/outlets/:outletId/customers/:id` | Detail customer | owner, supervisor, kasir |
| PUT | `/outlets/:outletId/customers/:id` | Update data customer | owner, supervisor, kasir |
| DELETE | `/outlets/:outletId/customers/:id` | Nonaktifkan customer | owner |
| GET | `/outlets/:outletId/customers/search` | Cari customer by HP/nama | owner, supervisor, kasir |
| GET | `/outlets/:outletId/customers/:id/transactions` | Riwayat transaksi customer | owner, supervisor, kasir |

### POST /outlets/:outletId/customers
```json
{
  "name": "Andi Wijaya",
  "phone": "08123456789",
  "email": "andi@email.com",
  "birthdate": "1990-05-15",
  "registered_via": "kasir"
}
```

### GET /outlets/:outletId/customers/search
```
Query params:
  - q: string (nomor HP atau nama)
  - limit: number (default 10)
```

---

## MEMBERSHIP TIERS (KONFIGURASI)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/membership-tiers` | List tier konfigurasi | owner, supervisor |
| POST | `/outlets/:outletId/membership-tiers` | Buat tier baru | owner |
| PUT | `/outlets/:outletId/membership-tiers/:id` | Update konfigurasi tier | owner |
| DELETE | `/outlets/:outletId/membership-tiers/:id` | Nonaktifkan tier | owner |

### POST /outlets/:outletId/membership-tiers
```json
{
  "tier": "gold",
  "name": "Gold Member",
  "point_threshold": 1000,
  "point_rate_per_amount": 0.001,
  "discount_percent": "10.00",
  "description": "Diskon 10% untuk semua transaksi"
}
```

---

## MEMBERSHIPS (PER CUSTOMER)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/customers/:customerId/membership` | Detail membership customer | owner, supervisor, kasir |
| POST | `/outlets/:outletId/customers/:customerId/membership` | Daftarkan customer jadi member | owner, supervisor, kasir |
| PUT | `/outlets/:outletId/customers/:customerId/membership` | Update membership | owner |
| POST | `/outlets/:outletId/customers/:customerId/membership/adjust-points` | Adjustment poin manual | owner |
| POST | `/outlets/:outletId/customers/:customerId/membership/reset-points` | Reset poin | owner |
| GET | `/outlets/:outletId/customers/:customerId/membership/point-logs` | Riwayat poin | owner, supervisor, kasir |

### POST /outlets/:outletId/customers/:customerId/membership/adjust-points
```json
{
  "points": 100,
  "action": "adjustment",
  "notes": "Koreksi poin transaksi tanggal 1 Jan"
}
```

---

## LOYALTY REDEEM CATALOG

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/loyalty-catalog` | List produk yang bisa diredeem | owner, supervisor, kasir |
| POST | `/outlets/:outletId/loyalty-catalog` | Tambah produk ke katalog redeem | owner |
| PUT | `/outlets/:outletId/loyalty-catalog/:id` | Update katalog redeem | owner |
| DELETE | `/outlets/:outletId/loyalty-catalog/:id` | Hapus dari katalog | owner |
| POST | `/outlets/:outletId/loyalty-catalog/redeem` | Redeem poin → produk gratis | kasir |

### POST /outlets/:outletId/loyalty-catalog/redeem
```json
{
  "customer_id": "uuid-customer",
  "catalog_id": "uuid-catalog",
  "order_id": "uuid-order"
}
```

---

## KASBON

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/kasbon` | List kasbon (filter: status, customer) | owner, supervisor, kasir |
| POST | `/outlets/:outletId/kasbon` | Buat kasbon baru | kasir |
| GET | `/outlets/:outletId/kasbon/:id` | Detail kasbon | owner, supervisor, kasir |
| PUT | `/outlets/:outletId/kasbon/:id` | Update kasbon | owner, supervisor |
| POST | `/outlets/:outletId/kasbon/:id/pay` | Bayar kasbon | kasir |
| POST | `/outlets/:outletId/kasbon/:id/write-off` | Write-off kasbon (butuh approval) | owner |
| GET | `/outlets/:outletId/kasbon/outstanding` | Semua kasbon belum lunas | owner, supervisor, kasir |
| GET | `/outlets/:outletId/kasbon/due-today` | Kasbon jatuh tempo hari ini | owner, supervisor, kasir |
| GET | `/outlets/:outletId/customers/:customerId/kasbon` | Kasbon per customer | owner, supervisor, kasir |

### POST /outlets/:outletId/kasbon
```json
{
  "customer_id": "uuid-customer",
  "order_id": "uuid-order",
  "total_amount": "150000",
  "due_date": "2024-02-01",
  "notes": "Pelanggan tetap"
}
```

### POST /outlets/:outletId/kasbon/:id/pay
```json
{
  "amount": "75000",
  "payment_method": "cash",
  "notes": "Bayar sebagian"
}
```

---

## CICILAN

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/kasbon/:kasbonId/cicilan` | List jadwal cicilan | owner, supervisor, kasir |
| POST | `/outlets/:outletId/kasbon/:kasbonId/cicilan` | Buat jadwal cicilan | kasir |
| PUT | `/outlets/:outletId/kasbon/:kasbonId/cicilan/:id` | Update jadwal cicilan | owner, supervisor |
| POST | `/outlets/:outletId/kasbon/:kasbonId/cicilan/:id/pay` | Bayar cicilan | kasir |
| GET | `/outlets/:outletId/cicilan/due-today` | Cicilan jatuh tempo hari ini | owner, supervisor, kasir |

### POST /outlets/:outletId/kasbon/:kasbonId/cicilan
```json
{
  "installments": [
    { "installment_number": 1, "amount": "50000", "due_date": "2024-02-01" },
    { "installment_number": 2, "amount": "50000", "due_date": "2024-03-01" },
    { "installment_number": 3, "amount": "50000", "due_date": "2024-04-01" }
  ]
}
```
