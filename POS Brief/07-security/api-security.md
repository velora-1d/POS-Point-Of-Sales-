# Security — API Security

## Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| `POST /auth/login` | 5 request | 10 menit per IP |
| `POST /auth/refresh` | 10 request | 1 menit per user |
| `POST /*/approvals/*/pin` | 3 request | 5 menit per user |
| `POST /*/payments` | 20 request | 1 menit per user |
| `GET /*/reports/*` | 10 request | 1 menit per user |
| Default semua endpoint | 60 request | 1 menit per user |

Implementasi via Laravel `throttle` middleware + Redis.

---

## Input Validation

Semua input divalidasi server-side menggunakan Laravel Form Requests:

```php
// Contoh: CreateOrderRequest
class CreateOrderRequest extends FormRequest {
    public function rules(): array {
        return [
            'table_id'         => 'nullable|uuid|exists:tables,id',
            'customer_id'      => 'nullable|uuid|exists:customers,id',
            'type'             => 'required|in:dine_in,takeaway,online,po',
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.notes'    => 'nullable|string|max:255',
        ];
    }
}
```

Semua string di-sanitize, semua ID divalidasi UUID format.

---

## SQL Injection Prevention

- Semua query menggunakan Drizzle ORM / Laravel Eloquent (parameterized queries)
- Tidak ada raw query tanpa binding
- Input tidak pernah langsung di-interpolasi ke query

---

## XSS Prevention

- Vue 3 auto-escape semua output di template
- Content-Security-Policy header aktif
- Inertia.js tidak render raw HTML dari server kecuali eksplisit `v-html` (dihindari)

---

## CORS

```php
// config/cors.php
'allowed_origins' => [
    env('FRONTEND_URL'),    // domain web app
    'tauri://localhost',    // Tauri desktop
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-CSRF-TOKEN'],
'supports_credentials' => true,
```

---

## HTTPS & Headers

Header wajib di semua response:

```
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'; script-src 'self'; ...
Referrer-Policy: strict-origin-when-cross-origin
```

---

## Webhook Security (GoFood/GrabFood/Payment Gateway)

```
Setiap webhook masuk divalidasi:
  → Signature header dicek (HMAC-SHA256)
  → Timestamp dicek (tidak lebih dari 5 menit lalu)
  → IP whitelist provider (opsional)
  → Jika invalid → 401, log ke audit trail
```

---

## API Versioning

Semua endpoint di-prefix `/api/v1/`

Breaking changes → versi baru `/api/v2/`
Versi lama di-deprecate dengan header `Deprecation: true` dan sunset date.

---

## Sensitive Data

- Nomor HP customer: tampilkan sebagian (08xxx****789) di log
- Password: tidak pernah dikirim balik ke client
- API key payment gateway: disimpan di `.env`, tidak pernah expose ke frontend
- PIN approval: hashed bcrypt, tidak pernah di-log
