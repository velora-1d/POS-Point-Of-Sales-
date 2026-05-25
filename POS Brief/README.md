# POS Mentai — Dokumentasi Lengkap

Dokumentasi sistem Point of Sale untuk Mentai Restaurant.

## Tech Stack
- **Backend**: Laravel 13 + Inertia.js
- **Frontend**: Vue 3 (Composition API)
- **Database**: PostgreSQL + Drizzle ORM
- **Desktop**: Tauri (wrapper web app)
- **Payment**: QRIS + Payment Gateway (Midtrans/Xendit)
- **Integrasi**: GoFood, GrabFood

## Struktur Folder

| Folder | Isi |
|--------|-----|
| `01-requirements` | Gap analysis fitur & menu per role |
| `02-flows` | Flow per menu/fitur (40+ file) |
| `03-database` | Schema database Drizzle ORM |
| `04-api` | Dokumentasi REST API endpoint |
| `05-tech-stack` | Arsitektur & tech stack detail |
| `06-rbac` | Role, permissions & approval matrix |
| `07-security` | Auth, API & data security strategy |

## Role Hierarchy
```
owner > supervisor > kasir > bar > kitchen
```

## Quick Links
- [Menu Per Role](01-requirements/menu-per-role.md)
- [API Index](04-api/api-index.md)
- [RBAC Matrix](06-rbac/role-matrix.md)
- [Database Schema](03-database/index.ts)
- [Arsitektur](05-tech-stack/architecture.md)
