# API Endpoints — 03. Product

Base URL: `/api/v1`
Auth: Bearer Token (JWT)

---

## CATEGORIES

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/categories` | List semua kategori | all |
| POST | `/outlets/:outletId/categories` | Buat kategori baru | owner |
| PUT | `/outlets/:outletId/categories/:id` | Update kategori | owner |
| DELETE | `/outlets/:outletId/categories/:id` | Nonaktifkan kategori | owner |
| PUT | `/outlets/:outletId/categories/reorder` | Ubah urutan kategori | owner |

---

## PRODUCTS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/products` | List semua produk | all |
| POST | `/outlets/:outletId/products` | Tambah produk baru | owner |
| GET | `/outlets/:outletId/products/:id` | Detail produk | all |
| PUT | `/outlets/:outletId/products/:id` | Update produk | owner |
| DELETE | `/outlets/:outletId/products/:id` | Nonaktifkan produk | owner |
| PUT | `/outlets/:outletId/products/:id/availability` | Toggle tampil di menu | owner, supervisor |
| GET | `/outlets/:outletId/products/menu` | Produk aktif untuk tampilan menu | all |

### POST /outlets/:outletId/products
```json
{
  "category_id": "uuid-category",
  "name": "Mentai Rice",
  "description": "Nasi dengan saus mentai",
  "image_url": "https://...",
  "base_price": "25000",
  "hpp": "10000",
  "track_stock": true,
  "track_expired": false,
  "expired_action": "notify_only",
  "expired_reminder_days": [7, 3, 1],
  "sort_order": 1
}
```

### GET /outlets/:outletId/products
```
Query params:
  - category_id: uuid
  - is_available: boolean
  - track_expired: boolean
  - q: string (nama produk)
  - page: number
  - limit: number
```

---

## PRODUCT VARIANTS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/products/:productId/variants` | List varian produk | all |
| POST | `/outlets/:outletId/products/:productId/variants` | Tambah varian | owner |
| PUT | `/outlets/:outletId/products/:productId/variants/:id` | Update varian | owner |
| DELETE | `/outlets/:outletId/products/:productId/variants/:id` | Hapus varian | owner |

### POST /outlets/:outletId/products/:productId/variants
```json
{
  "name": "Extra Spicy",
  "additional_price": "2000"
}
```

---

## PRODUCT PRICES (MULTI HARGA)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/products/:productId/prices` | List semua price tier produk | owner, supervisor |
| POST | `/outlets/:outletId/products/:productId/prices` | Tambah price tier | owner |
| PUT | `/outlets/:outletId/products/:productId/prices/:id` | Update price tier | owner |
| DELETE | `/outlets/:outletId/products/:productId/prices/:id` | Hapus price tier | owner |
| GET | `/outlets/:outletId/products/:productId/prices/active` | Harga aktif saat ini (berdasarkan waktu & outlet) | kasir, bar, kitchen |

### POST /outlets/:outletId/products/:productId/prices
```json
{
  "tier": "member",
  "tier_label": "Harga Member",
  "price": "22000",
  "outlet_id": null,
  "happy_hour_start": "14:00",
  "happy_hour_end": "17:00"
}
```

---

## STOCK (PRODUK JADI)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/stocks` | List stok semua produk | owner, supervisor |
| GET | `/outlets/:outletId/stocks/:productId` | Stok per produk | owner, supervisor, kasir |
| POST | `/outlets/:outletId/stocks/:productId/add` | Tambah stok masuk | owner, supervisor |
| POST | `/outlets/:outletId/stocks/:productId/adjust` | Adjustment stok manual | owner, supervisor |
| GET | `/outlets/:outletId/stocks/low` | Produk stok menipis | owner, supervisor |
| GET | `/outlets/:outletId/stocks/:productId/logs` | Riwayat mutasi stok | owner, supervisor |

### POST /outlets/:outletId/stocks/:productId/add
```json
{
  "quantity": 50,
  "notes": "Restock dari supplier",
  "expired_date": "2024-06-01",
  "batch_code": "BATCH-001"
}
```

### POST /outlets/:outletId/stocks/:productId/adjust
```json
{
  "quantity": 48,
  "notes": "Koreksi hasil stock opname"
}
```

---

## RAW MATERIALS (BAHAN BAKU)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/raw-materials` | List bahan baku | owner, supervisor |
| POST | `/outlets/:outletId/raw-materials` | Tambah bahan baku | owner |
| GET | `/outlets/:outletId/raw-materials/:id` | Detail bahan baku | owner, supervisor |
| PUT | `/outlets/:outletId/raw-materials/:id` | Update bahan baku | owner |
| DELETE | `/outlets/:outletId/raw-materials/:id` | Nonaktifkan bahan baku | owner |
| POST | `/outlets/:outletId/raw-materials/:id/add` | Tambah stok bahan baku | owner, supervisor |
| POST | `/outlets/:outletId/raw-materials/:id/adjust` | Adjustment stok bahan baku | owner, supervisor |
| GET | `/outlets/:outletId/raw-materials/low` | Bahan baku menipis | owner, supervisor |

### POST /outlets/:outletId/raw-materials
```json
{
  "name": "Mentai Sauce",
  "unit": "gram",
  "quantity": 5000,
  "minimum_stock": 500,
  "cost_per_unit": "0.05",
  "track_expired": true,
  "expired_action": "notify_only",
  "expired_reminder_days": [7, 3, 1]
}
```

---

## PRODUCT INGREDIENTS (RESEP)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/products/:productId/ingredients` | List bahan baku per produk | owner |
| PUT | `/outlets/:outletId/products/:productId/ingredients` | Update resep produk | owner |

### PUT /outlets/:outletId/products/:productId/ingredients
```json
{
  "ingredients": [
    { "raw_material_id": "uuid-bahan", "quantity": 30 },
    { "raw_material_id": "uuid-bahan-2", "quantity": 150 }
  ]
}
```

---

## EXPIRED TRACKING

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/expired` | List semua tracking expired | owner, supervisor |
| GET | `/outlets/:outletId/expired/upcoming` | Produk/bahan akan expired | owner, supervisor |
| GET | `/outlets/:outletId/expired/today` | Expired hari ini | owner, supervisor |
| POST | `/outlets/:outletId/expired/:id/handle` | Tandai sudah ditangani | owner, supervisor |
| GET | `/outlets/:outletId/expired/report` | Laporan kerugian expired | owner |

### POST /outlets/:outletId/expired/:id/handle
```json
{
  "action": "deactivate",
  "notes": "Produk dibuang karena sudah expired"
}
```

### GET /outlets/:outletId/expired/upcoming
```
Query params:
  - days: number (default 7, tampilkan yang expired dalam X hari)
  - type: "product" | "raw_material" | "all"
```
