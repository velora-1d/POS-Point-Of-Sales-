# Arsitektur & Tech Stack — POS Mentai

## Overview

```
┌─────────────────────────────────────────────────────────┐
│                     CLIENT LAYER                         │
│                                                         │
│   Browser (Web App)    Desktop (Tauri)    QR Meja       │
│   Vue 3 + Inertia      Tauri wrapper      Vue (public)  │
└────────────────────────┬────────────────────────────────┘
                         │ HTTP / WebSocket
┌────────────────────────▼────────────────────────────────┐
│                    BACKEND LAYER                         │
│                                                         │
│              Laravel 13 + Inertia.js                    │
│         REST API + Server-Side Rendering                 │
└──────┬──────────┬──────────┬───────────┬────────────────┘
       │          │          │           │
┌──────▼──┐  ┌───▼────┐  ┌──▼──────┐  ┌▼──────────────┐
│PostgreSQL│  │ Redis  │  │Storage  │  │ External APIs  │
│(Drizzle) │  │(Cache/ │  │(Files/  │  │GoFood,GrabFood │
│          │  │Queue)  │  │Exports) │  │Payment Gateway │
└──────────┘  └────────┘  └─────────┘  └───────────────┘
```

## Tech Stack Detail

### Backend
| Komponen | Teknologi | Keterangan |
|----------|-----------|------------|
| Framework | Laravel 13 | PHP backend |
| Frontend Bridge | Inertia.js | SPA tanpa API separation |
| ORM | Drizzle (via custom adapter) | Type-safe queries |
| Auth | Laravel Sanctum | Token-based auth |
| Queue | Laravel Horizon + Redis | Job async (notif, export) |
| WebSocket | Laravel Reverb | Real-time kitchen display, order status |
| Cache | Redis | Session, rate limit, promo cache |

### Frontend
| Komponen | Teknologi | Keterangan |
|----------|-----------|------------|
| Framework | Vue 3 | Composition API |
| State | Pinia | Global state management |
| UI | TailwindCSS + shadcn/vue | Component library |
| Build | Vite | Fast bundler |
| Desktop | Tauri | Wrap web app jadi desktop |

### Database
| Komponen | Teknologi |
|----------|-----------|
| Primary DB | PostgreSQL 16 |
| Schema | Drizzle ORM (TypeScript) |
| Migration | Drizzle Kit |

### Infrastructure
| Komponen | Teknologi |
|----------|-----------|
| Hosting | VPS / Cloud (DigitalOcean/AWS) |
| Storage | S3-compatible (foto produk, export) |
| CDN | Cloudflare |
| SSL | Let's Encrypt |

---

## Struktur Folder Laravel

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Order/
│   │   ├── Product/
│   │   ├── Customer/
│   │   ├── Shift/
│   │   ├── Report/
│   │   └── Setting/
│   ├── Middleware/
│   │   ├── CheckRole.php
│   │   ├── CheckOutletAccess.php
│   │   └── CheckShiftActive.php
│   └── Requests/
├── Models/
├── Services/
│   ├── OrderService.php
│   ├── PaymentService.php
│   ├── LoyaltyService.php
│   ├── PromoService.php
│   └── NotificationService.php
├── Jobs/
│   ├── SendWhatsAppNotification.php
│   ├── ProcessExpiredProducts.php
│   └── GenerateReport.php
└── Events/
    ├── OrderStatusChanged.php
    └── NewOrderCreated.php

resources/
└── js/
    ├── Pages/
    │   ├── Kitchen/
    │   ├── Bar/
    │   ├── Kasir/
    │   ├── Owner/
    │   └── Auth/
    ├── Components/
    ├── Layouts/
    └── Composables/
```

---

## Real-time dengan Laravel Reverb

```
Order dibuat oleh kasir
  → Event: NewOrderCreated
  → Broadcast ke channel: outlet.{outletId}.kitchen
  → Kitchen display Vue subscribe ke channel
  → Order muncul real-time tanpa refresh
```

Channel yang digunakan:
- `outlet.{id}.kitchen` — antrian kitchen
- `outlet.{id}.orders` — update status order
- `outlet.{id}.alerts` — notifikasi alert

---

## Tauri Desktop App

```
Web app berjalan di browser embedded Tauri
  → Akses lokal printer (thermal/dot matrix) via Tauri API
  → Auto-update built-in
  → Offline cache terbatas (antrian print)
  → Platform: Windows, macOS, Linux
```

---

## Kitchen Display — Setting Schema (di outlets.settings JSONB)

```json
{
  "kitchen": {
    "waiting_alert_threshold_minutes": 3,
    "cooking_warning_threshold_minutes": 2,
    "notification_sound_url": "https://storage/.../bell.mp3",
    "notification_volume": 0.8,
    "default_cooking_time_by_category": {
      "uuid-kategori-makanan": 15,
      "uuid-kategori-minuman": 5
    }
  }
}
```
