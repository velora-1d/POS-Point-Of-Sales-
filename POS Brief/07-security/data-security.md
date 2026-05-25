# Security — Data Security

## Enkripsi Data

### Data at Rest

| Data | Enkripsi |
|------|----------|
| Password user | Bcrypt (cost 12) |
| PIN approval | Bcrypt (cost 12) |
| API key payment gateway | AES-256 via Laravel `encrypt()` |
| Token WhatsApp/notifikasi | AES-256 via Laravel `encrypt()` |
| Database backup | AES-256 encrypted |

### Data in Transit

- Semua komunikasi client ↔ server via HTTPS (TLS 1.2+)
- WebSocket (Laravel Reverb) via WSS
- Koneksi database via SSL

---

## Data Isolation (Multi-Outlet)

```
Setiap query ke database WAJIB include outlet_id
  → Middleware CheckOutletAccess inject outlet_id ke semua query
  → User tidak bisa akses data outlet lain meskipun tahu ID-nya
  → Owner hanya bisa akses outlet yang dia miliki
```

Implementasi di base repository:
```php
// Semua query di-scope ke outlet aktif
protected function scopeToOutlet(Builder $query): Builder {
    return $query->where('outlet_id', auth()->user()->outlet_id);
}
```

---

## Backup & Recovery

| Setting | Value |
|---------|-------|
| Backup otomatis | Setiap hari jam 02.00 WIB |
| Retensi backup | 30 hari |
| Lokasi backup | S3-compatible storage (encrypted) |
| Point-in-time recovery | PostgreSQL WAL archiving |
| RTO (Recovery Time Objective) | < 4 jam |
| RPO (Recovery Point Objective) | < 24 jam |

---

## Soft Delete

Data penting tidak pernah dihapus permanen, hanya soft-delete:
- Orders
- Customers
- Products
- Employees
- Kasbon
- Approvals (tidak bisa dihapus sama sekali)

---

## Data Retention

| Data | Retensi |
|------|---------|
| Log transaksi | Selamanya (tidak dihapus) |
| Audit trail | Selamanya |
| Approval log | Selamanya |
| Notifikasi | 90 hari |
| Export file | 24 jam (lalu dihapus otomatis) |
| Session Redis | 8 jam |

---

## GDPR / Privacy (Customer Data)

- Customer bisa request hapus data via owner
- Hapus data customer: anonimisasi (nama → "Customer", HP → dihash)
- Riwayat transaksi tetap disimpan tapi customer_id di-nullify
- Tidak ada sharing data customer ke pihak ketiga selain payment gateway

---

## Monitoring & Alerting

| Event | Alert ke |
|-------|----------|
| Login gagal 5x | Email owner |
| Akses dari IP tidak dikenal | Email owner |
| Database connection error | Tim devops |
| Payment gateway timeout | Log + retry otomatis |
| Disk storage > 80% | Tim devops |
| Error rate API > 5% | Tim devops |

---

## Environment Separation

| Environment | Keterangan |
|-------------|------------|
| Production | Data real, akses terbatas |
| Staging | Data dummy, untuk QA |
| Development | Local, tidak ada data real |

Variabel sensitif (.env) tidak pernah di-commit ke repository.
Gunakan secret manager (AWS Secrets Manager / Vault) untuk production.
