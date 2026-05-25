# API Endpoints — 05. Order

Base URL: `/api/v1`
Auth: Bearer Token (JWT)

---

## TABLES (MEJA)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/tables` | List semua meja + status | all |
| POST | `/outlets/:outletId/tables` | Tambah meja | owner |
| GET | `/outlets/:outletId/tables/:id` | Detail meja | all |
| PUT | `/outlets/:outletId/tables/:id` | Update meja | owner |
| DELETE | `/outlets/:outletId/tables/:id` | Hapus meja | owner |
| GET | `/outlets/:outletId/tables/:id/qr` | Generate / ambil QR code meja | owner, supervisor |
| PUT | `/outlets/:outletId/tables/layout` | Update posisi layout visual | owner |

### POST /outlets/:outletId/tables
```json
{
  "name": "Meja 1",
  "capacity": 4,
  "position_x": 100,
  "position_y": 200
}
```

---

## RESERVATIONS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/reservations` | List reservasi | owner, supervisor, kasir |
| POST | `/outlets/:outletId/reservations` | Buat reservasi | kasir |
| GET | `/outlets/:outletId/reservations/:id` | Detail reservasi | owner, supervisor, kasir |
| PUT | `/outlets/:outletId/reservations/:id` | Update reservasi | kasir |
| PUT | `/outlets/:outletId/reservations/:id/status` | Update status reservasi | kasir |
| DELETE | `/outlets/:outletId/reservations/:id` | Batalkan reservasi | kasir, supervisor |

### POST /outlets/:outletId/reservations
```json
{
  "table_id": "uuid-table",
  "customer_id": "uuid-customer",
  "customer_name": "Andi",
  "customer_phone": "08123456789",
  "guest_count": 4,
  "reserved_at": "2024-02-01T19:00:00Z",
  "notes": "Anniversary"
}
```

---

## ORDERS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/orders` | List semua order | all |
| POST | `/outlets/:outletId/orders` | Buat order baru | kasir, bar |
| GET | `/outlets/:outletId/orders/:id` | Detail order | all |
| PUT | `/outlets/:outletId/orders/:id` | Update order (tambah/edit item) | kasir, bar |
| PUT | `/outlets/:outletId/orders/:id/status` | Update status order | kasir, bar, kitchen |
| POST | `/outlets/:outletId/orders/:id/cancel` | Batalkan order (butuh approval jika in_progress) | kasir, supervisor |
| GET | `/outlets/:outletId/orders/active` | Order aktif saat ini | all |
| GET | `/outlets/:outletId/orders/kitchen` | Antrian order untuk kitchen display | kitchen, bar |
| GET | `/outlets/:outletId/orders/:id/receipt` | Generate data struk | kasir |
| POST | `/outlets/:outletId/orders/:id/receipt/send-wa` | Kirim struk ke WA customer | kasir |
| POST | `/outlets/:outletId/orders/:id/split` | Split bill | kasir |
| POST | `/outlets/:outletId/orders/merge` | Gabung bill | kasir |

### POST /outlets/:outletId/orders
```json
{
  "table_id": "uuid-table",
  "customer_id": "uuid-customer",
  "type": "dine_in",
  "source": "kasir",
  "items": [
    {
      "product_id": "uuid-product",
      "variant_id": "uuid-variant",
      "quantity": 2,
      "notes": "Tidak pedas",
      "price_tier": "normal"
    }
  ],
  "notes": "Extra napkin"
}
```

### PUT /outlets/:outletId/orders/:id/status
```json
{
  "status": "in_progress",
  "notes": "Mulai dimasak"
}
```

### POST /outlets/:outletId/orders/:id/cancel
```json
{
  "reason": "Customer berubah pikiran",
  "approval_id": "uuid-approval"
}
```

### GET /outlets/:outletId/orders
```
Query params:
  - status: order_status
  - table_id: uuid
  - cashier_id: uuid
  - source: kasir | qr_meja | gofood | grabfood
  - start_date: ISO date
  - end_date: ISO date
  - page: number
  - limit: number
```

---

## ORDER ITEMS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| POST | `/outlets/:outletId/orders/:orderId/items` | Tambah item ke order | kasir, bar |
| PUT | `/outlets/:outletId/orders/:orderId/items/:id` | Update item order | kasir, bar |
| DELETE | `/outlets/:outletId/orders/:orderId/items/:id` | Hapus item dari order | kasir, bar |
| POST | `/outlets/:outletId/orders/:orderId/items/edit-request` | Customer request edit via QR (butuh approval) | customer (via QR) |

### POST /outlets/:outletId/orders/:orderId/items/edit-request
```json
{
  "items": [
    {
      "product_id": "uuid-product",
      "quantity": 3,
      "notes": "Tambah extra sauce"
    }
  ],
  "reason": "Salah pesan tadi"
}
```

---

## ORDER STATUS LOGS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/orders/:orderId/status-logs` | Riwayat perubahan status order | owner, supervisor, kasir |

---

## PAYMENTS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| POST | `/outlets/:outletId/orders/:orderId/payments` | Proses pembayaran | kasir |
| GET | `/outlets/:outletId/orders/:orderId/payments` | List pembayaran per order | kasir, owner |
| POST | `/outlets/:outletId/payments/:id/refund` | Refund pembayaran (butuh approval) | kasir |
| POST | `/outlets/:outletId/payments/:id/void` | Void pembayaran (butuh approval) | kasir |

### POST /outlets/:outletId/orders/:orderId/payments
```json
{
  "method": "qris",
  "amount": "75000",
  "reference_number": "REF-12345"
}
```

### POST /outlets/:outletId/payments/:id/refund
```json
{
  "amount": "75000",
  "reason": "Pesanan tidak sesuai",
  "approval_id": "uuid-approval"
}
```

---

## PO / DOWN PAYMENT

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/po-orders` | List semua PO/DP | owner, supervisor, kasir |
| POST | `/outlets/:outletId/po-orders` | Buat PO/DP baru | kasir |
| GET | `/outlets/:outletId/po-orders/:id` | Detail PO/DP | owner, supervisor, kasir |
| PUT | `/outlets/:outletId/po-orders/:id` | Update PO/DP | kasir, supervisor |
| POST | `/outlets/:outletId/po-orders/:id/pay-dp` | Bayar DP | kasir |
| POST | `/outlets/:outletId/po-orders/:id/pay-remaining` | Lunasi sisa PO | kasir |
| POST | `/outlets/:outletId/po-orders/:id/cancel` | Batalkan PO (cek refund DP) | kasir, supervisor |
| GET | `/outlets/:outletId/po-orders/due-today` | PO jatuh tempo hari ini | owner, supervisor, kasir |
| GET | `/outlets/:outletId/po-orders/outstanding` | PO belum lunas | owner, supervisor, kasir |

### POST /outlets/:outletId/po-orders
```json
{
  "customer_id": "uuid-customer",
  "items": [
    { "product_id": "uuid-product", "quantity": 5, "unit_price": "25000" }
  ],
  "dp_amount": "62500",
  "pickup_date": "2024-02-15T12:00:00Z",
  "notes": "Acara ulang tahun"
}
```

### POST /outlets/:outletId/po-orders/:id/cancel
```json
{
  "reason": "Customer batal",
  "refund_dp": true,
  "approval_id": "uuid-approval"
}
```

---

## ONLINE ORDERS (GOFOOD / GRABFOOD)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/online-orders` | List order online masuk | all |
| GET | `/outlets/:outletId/online-orders/:id` | Detail order online | all |
| PUT | `/outlets/:outletId/online-orders/:id/status` | Update status order online | kasir, kitchen |
| POST | `/outlets/:outletId/online-orders/webhook/gofood` | Webhook GoFood | system |
| POST | `/outlets/:outletId/online-orders/webhook/grabfood` | Webhook GrabFood | system |

### GET /outlets/:outletId/online-orders
```
Query params:
  - platform: gofood | grabfood
  - status: order_status
  - start_date: ISO date
  - end_date: ISO date
  - page: number
  - limit: number
```

---

## QR MEJA — SELF ORDER (PUBLIC, TANPA AUTH)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/public/menu/:outletSlug/:tableCode` | Load menu outlet via QR meja | None |
| POST | `/public/menu/:outletSlug/:tableCode/session` | Buat sesi QR (isi nama & HP customer) | None |
| GET | `/public/menu/:outletSlug/:tableCode/products` | List produk aktif untuk tampilan customer | None |
| POST | `/public/menu/:outletSlug/:tableCode/order` | Submit order + buat payment request | None |
| GET | `/public/menu/:outletSlug/:tableCode/payment/:paymentId` | Cek status pembayaran | None |
| GET | `/public/menu/:outletSlug/:tableCode/order/:orderId/status` | Track status order real-time | None |

### POST /public/menu/:outletSlug/:tableCode/session
```json
{
  "name": "Andi",
  "phone": "08123456789"
}
```
Response:
```json
{
  "session_token": "abc123",
  "customer": {
    "name": "Andi",
    "phone": "08123456789",
    "is_member": true,
    "tier": "silver",
    "points": 250
  }
}
```

### POST /public/menu/:outletSlug/:tableCode/order
```json
{
  "session_token": "abc123",
  "items": [
    {
      "product_id": "uuid-product",
      "variant_id": "uuid-variant",
      "quantity": 2,
      "notes": "Tidak pedas"
    }
  ],
  "payment_method": "qris",
  "promo_code": null
}
```
Response:
```json
{
  "order_id": "uuid-order",
  "payment_id": "uuid-payment",
  "payment_url": "https://payment-gateway.com/pay/xxx",
  "qr_code_url": "https://...",
  "expires_at": "2024-01-01T12:10:00Z",
  "total_amount": "75000",
  "discount_amount": "0"
}
```

### GET /public/menu/:outletSlug/:tableCode/payment/:paymentId
Response:
```json
{
  "status": "paid",
  "order_id": "uuid-order",
  "paid_at": "2024-01-01T12:05:00Z"
}
```

---

## KITCHEN TIMER

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| POST | `/outlets/:outletId/orders/:id/start-cooking` | Kitchen mulai masak (start cooking timer) | kitchen |
| POST | `/outlets/:outletId/orders/:id/finish-cooking` | Kitchen selesai masak | kitchen |
| GET | `/outlets/:outletId/kitchen/settings` | Ambil setting kitchen (threshold timer, suara) | kitchen, bar |
| PUT | `/outlets/:outletId/kitchen/settings` | Update setting kitchen | owner, supervisor |

### POST /outlets/:outletId/orders/:id/start-cooking
Response:
```json
{
  "order_id": "uuid-order",
  "status": "in_progress",
  "cooking_started_at": "2024-01-01T12:00:00Z",
  "estimated_time_minutes": 15,
  "estimated_done_at": "2024-01-01T12:15:00Z"
}
```

### PUT /outlets/:outletId/kitchen/settings
```json
{
  "waiting_alert_threshold_minutes": 3,
  "cooking_warning_threshold_minutes": 2,
  "notification_sound_url": "https://storage/.../bell.mp3",
  "notification_volume": 0.8,
  "default_cooking_time_by_category": {
    "uuid-kategori-makanan": 15,
    "uuid-kategori-minuman": 5
  }
}
```
