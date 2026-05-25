# Security — Auth Strategy

## Authentication

### Laravel Sanctum (Token-based)

```
User login
  → POST /auth/login { email, password }
  → Validasi credentials
  → Generate access token (expiry: 8 jam) + refresh token (expiry: 30 hari)
  → Token disimpan: HttpOnly cookie (web) / local storage (Tauri)
  → Setiap request kirim: Authorization: Bearer {token}
```

### Token Refresh

```
Access token expired
  → Client kirim refresh token → POST /auth/refresh
  → Sistem validasi refresh token
  → Generate access token baru
  → Refresh token di-rotate (token lama invalid)
```

### Multi-Device Session

```
Setiap login = token baru
  → Owner bisa lihat semua sesi aktif
  → Bisa revoke sesi tertentu (logout paksa)
  → Logout semua device sekaligus
```

---

## Authorization

### Middleware Stack per Request

```
Request masuk
  → auth:sanctum → cek token valid
  → CheckRole → cek role user sesuai permission
  → CheckOutletAccess → cek user punya akses ke outlet ini
  → CheckShiftActive → cek ada shift aktif (khusus endpoint transaksi)
```

### CheckRole Middleware

```php
// Contoh implementasi
class CheckRole {
    public function handle($request, Closure $next, ...$permissions) {
        $user = $request->user();
        foreach ($permissions as $permission) {
            if ($user->role->permissions->contains('name', $permission)) {
                return $next($request);
            }
        }
        return response()->json(['error' => 'FORBIDDEN'], 403);
    }
}
```

### CheckOutletAccess Middleware

```
User hanya bisa akses data outlet yang di-assign kepadanya
  → Outlet ID di URL dicek vs outlet_id user
  → Owner bisa akses semua outlet miliknya
  → Kasir/kitchen/bar hanya bisa akses 1 outlet tempat dia bertugas
```

---

## Password Security

| Setting | Value |
|---------|-------|
| Hashing | Bcrypt (cost factor: 12) |
| Min length | 8 karakter |
| Kompleksitas | Huruf + angka |
| Reset via | Email (link expire 1 jam) |
| PIN approval | 4-6 digit, hashed terpisah |

---

## Session & Cookie

| Setting | Value |
|---------|-------|
| Session driver | Redis |
| Cookie | HttpOnly, Secure, SameSite=Strict |
| CSRF | Laravel CSRF token untuk form submission |
| Session timeout | 8 jam inaktif |

---

## Brute Force Protection

```
Login gagal 5x dalam 10 menit
  → Account lock 15 menit
  → Notif email ke user
  → Log ke audit trail

PIN approval gagal 3x
  → PIN lock, harus approval via notif
  → Notif ke owner
```

---

## Audit Trail

Semua aksi penting dicatat:
- Login / logout
- Perubahan data sensitif (karyawan, harga, setting)
- Semua approval / penolakan
- Export laporan
- Perubahan RBAC / permissions

```
audit_logs table:
  - user_id
  - action
  - module
  - reference_id
  - old_value (JSON)
  - new_value (JSON)
  - ip_address
  - user_agent
  - created_at
```
