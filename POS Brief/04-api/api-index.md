# POS Mentai — API Endpoints Index

Base URL: `/api/v1`
Auth: Bearer Token (JWT)
Format: REST API (JSON)

---

## Modules

| File | Modul | Endpoints |
|------|-------|-----------|
| `api-01-core.md` | Core — Outlets, Users, Roles, Permissions, Approvals, Notifications | ~35 |
| `api-02-customer.md` | Customer — Customers, Membership, Loyalty, Kasbon, Cicilan | ~32 |
| `api-03-product.md` | Product — Categories, Products, Variants, Multi Harga, Stock, Bahan Baku, Expired | ~38 |
| `api-04-promo.md` | Promo — Promos, Rules, Usage Logs, Apply Promo | ~18 |
| `api-05-order.md` | Order — Tables, Reservasi, Orders, Items, Payments, PO/DP, Online Orders | ~45 |
| `api-06-shift.md` | Shift & Absensi — Templates, Shifts, Cash Reports, Jadwal, Absensi | ~28 |
| `api-07-report.md` | Laporan & ERP — Dashboard, Penjualan, Stok, Keuangan, Karyawan, Export | ~30 |

**Total: ~226 endpoints**

---

## Role Access Summary

| Role | Akses |
|------|-------|
| `owner` | Full access semua endpoint |
| `supervisor` | Order, Shift, Absensi, Laporan terbatas, Approval |
| `kasir` | Order, Transaksi, Customer, Shift buka/tutup, Struk |
| `bar` | Kitchen display, Approval order, Order status |
| `kitchen` | Kitchen display (read), Update status masak |

---

## Auth Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | Login dengan email + password |
| POST | `/auth/logout` | Logout |
| POST | `/auth/refresh` | Refresh JWT token |
| GET | `/auth/me` | Data user yang sedang login |
| PUT | `/auth/me/pin` | Update approval PIN |

---

## Common Query Params

Semua endpoint list mendukung:
```
page: number (default 1)
limit: number (default 20, max 100)
sort: field_name
order: asc | desc
```

## Common Response Format

### Success
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 150
  }
}
```

### Error
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Field name is required",
    "details": [ ... ]
  }
}
```

### Error Codes
| Code | HTTP Status | Keterangan |
|------|-------------|------------|
| `UNAUTHORIZED` | 401 | Token tidak valid / expired |
| `FORBIDDEN` | 403 | Role tidak punya akses |
| `NOT_FOUND` | 404 | Data tidak ditemukan |
| `VALIDATION_ERROR` | 422 | Input tidak valid |
| `APPROVAL_REQUIRED` | 403 | Aksi butuh approval |
| `INSUFFICIENT_STOCK` | 400 | Stok tidak cukup |
| `SHIFT_NOT_ACTIVE` | 400 | Tidak ada shift aktif |
| `PROMO_INVALID` | 400 | Promo tidak valid / expired |
| `KASBON_LIMIT_EXCEEDED` | 400 | Melebihi limit kasbon |
