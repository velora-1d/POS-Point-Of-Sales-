# API Endpoints — 04. Promo

Base URL: `/api/v1`
Auth: Bearer Token (JWT)

---

## PROMOS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/promos` | List semua promo | owner, supervisor |
| POST | `/outlets/:outletId/promos` | Buat promo baru | owner |
| GET | `/outlets/:outletId/promos/:id` | Detail promo | owner, supervisor |
| PUT | `/outlets/:outletId/promos/:id` | Update promo | owner |
| DELETE | `/outlets/:outletId/promos/:id` | Hapus promo | owner |
| PUT | `/outlets/:outletId/promos/:id/toggle` | Aktifkan / nonaktifkan promo | owner, supervisor |
| POST | `/outlets/:outletId/promos/:id/duplicate` | Duplikat template promo | owner |
| GET | `/outlets/:outletId/promos/active` | Promo aktif saat ini | kasir, bar |
| POST | `/outlets/:outletId/promos/validate` | Validasi kode voucher | kasir |
| GET | `/outlets/:outletId/promos/:id/stats` | Statistik penggunaan promo | owner |

### POST /outlets/:outletId/promos
```json
{
  "name": "Happy Hour Sore",
  "code": null,
  "type": "percent",
  "apply_method": "auto",
  "discount_percent": "20.00",
  "discount_amount": null,
  "max_discount_amount": "50000",
  "min_transaction_amount": "50000",
  "buy_quantity": null,
  "get_quantity": null,
  "can_stack": false,
  "usage_limit": null,
  "start_date": "2024-01-01T00:00:00Z",
  "end_date": null,
  "happy_hour_start": "14:00",
  "happy_hour_end": "17:00",
  "rules": [
    {
      "trigger": "time",
      "reference_id": null,
      "reference_value": "weekday"
    }
  ]
}
```

### POST /outlets/:outletId/promos/validate
```json
{
  "code": "DISKON50",
  "order_items": [
    { "product_id": "uuid", "quantity": 2, "unit_price": "25000" }
  ],
  "customer_id": "uuid-customer",
  "total_amount": "50000"
}
```
Response:
```json
{
  "valid": true,
  "promo_id": "uuid-promo",
  "discount_amount": "10000",
  "message": "Promo berhasil divalidasi"
}
```

---

## PROMO RULES

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/promos/:promoId/rules` | List rules per promo | owner |
| PUT | `/outlets/:outletId/promos/:promoId/rules` | Update semua rules promo | owner |

### PUT /outlets/:outletId/promos/:promoId/rules
```json
{
  "rules": [
    { "trigger": "category", "reference_id": "uuid-category" },
    { "trigger": "member_tier", "reference_id": "uuid-tier-gold" }
  ]
}
```

---

## PROMO USAGE LOGS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/promos/:promoId/usage-logs` | Riwayat pemakaian promo | owner |
| GET | `/outlets/:outletId/promos/usage-logs` | Semua log pemakaian promo | owner |

### GET /outlets/:outletId/promos/usage-logs
```
Query params:
  - promo_id: uuid
  - customer_id: uuid
  - start_date: ISO date
  - end_date: ISO date
  - page: number
  - limit: number
```

---

## APPLY PROMO (saat transaksi)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| POST | `/outlets/:outletId/orders/:orderId/apply-promo` | Apply promo ke order | kasir |
| DELETE | `/outlets/:outletId/orders/:orderId/remove-promo/:promoId` | Hapus promo dari order | kasir |
| GET | `/outlets/:outletId/orders/:orderId/eligible-promos` | Cek promo yang eligible untuk order | kasir |

### POST /outlets/:outletId/orders/:orderId/apply-promo
```json
{
  "promo_id": "uuid-promo",
  "apply_method": "manual",
  "code": "DISKON50"
}
```

### GET /outlets/:outletId/orders/:orderId/eligible-promos
Response:
```json
{
  "promos": [
    {
      "id": "uuid-promo",
      "name": "Happy Hour Sore",
      "type": "percent",
      "discount_amount": "10000",
      "can_stack": false
    }
  ]
}
```
