# API Endpoints — 01. Core

Base URL: `/api/v1`
Auth: Bearer Token (JWT)
Role Access: owner > supervisor > kasir > bar > kitchen

---

## OUTLETS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets` | List semua outlet | owner |
| POST | `/outlets` | Tambah outlet baru | owner |
| GET | `/outlets/:id` | Detail outlet | owner, supervisor |
| PUT | `/outlets/:id` | Update outlet | owner |
| DELETE | `/outlets/:id` | Nonaktifkan outlet | owner |
| GET | `/outlets/:id/settings` | Ambil setting outlet | owner, supervisor |
| PUT | `/outlets/:id/settings` | Update setting outlet | owner |

### POST /outlets
```json
{
  "name": "Mentai Sudirman",
  "address": "Jl. Sudirman No. 1",
  "phone": "021-1234567",
  "settings": {
    "receipt_default": "print",
    "order_workflow": ["pending", "in_progress", "waiting_bar_approval", "ready", "delivered", "completed"],
    "bar_approval_enabled": true,
    "customer_can_view_status": true,
    "customer_can_edit_order": true,
    "expired_action": "notify_only"
  }
}
```

---

## ROLES

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/roles` | List semua role | owner |
| POST | `/outlets/:outletId/roles` | Buat role baru | owner |
| GET | `/outlets/:outletId/roles/:id` | Detail role | owner |
| PUT | `/outlets/:outletId/roles/:id` | Update role | owner |
| DELETE | `/outlets/:outletId/roles/:id` | Hapus role | owner |

---

## PERMISSIONS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/permissions` | List semua permission tersedia | owner |
| GET | `/outlets/:outletId/roles/:roleId/permissions` | Permission aktif per role | owner |
| PUT | `/outlets/:outletId/roles/:roleId/permissions` | Update permission role | owner |

### PUT /outlets/:outletId/roles/:roleId/permissions
```json
{
  "permissions": [
    "order:create",
    "order:read",
    "order:update",
    "payment:create",
    "shift:open",
    "shift:close"
  ]
}
```

---

## USERS (KARYAWAN)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/users` | List karyawan | owner, supervisor |
| POST | `/outlets/:outletId/users` | Tambah karyawan | owner |
| GET | `/outlets/:outletId/users/:id` | Detail karyawan | owner, supervisor |
| PUT | `/outlets/:outletId/users/:id` | Update karyawan | owner |
| DELETE | `/outlets/:outletId/users/:id` | Nonaktifkan karyawan | owner |
| PUT | `/outlets/:outletId/users/:id/pin` | Update approval PIN | owner, self |
| POST | `/auth/login` | Login | all |
| POST | `/auth/logout` | Logout | all |
| POST | `/auth/refresh` | Refresh token | all |
| GET | `/auth/me` | Data user aktif | all |

### POST /outlets/:outletId/users
```json
{
  "name": "Budi Santoso",
  "email": "budi@mentai.id",
  "phone": "08123456789",
  "role_id": "uuid-role",
  "password": "password123",
  "approval_pin": "1234",
  "join_date": "2024-01-01"
}
```

---

## APPROVAL RULES

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/approval-rules` | List semua approval rules | owner |
| POST | `/outlets/:outletId/approval-rules` | Buat approval rule | owner |
| PUT | `/outlets/:outletId/approval-rules/:id` | Update approval rule | owner |
| DELETE | `/outlets/:outletId/approval-rules/:id` | Hapus approval rule | owner |

### POST /outlets/:outletId/approval-rules
```json
{
  "action": "discount_manual",
  "approver_role_id": "uuid-role-supervisor",
  "mechanism": "both",
  "threshold_amount": "100000",
  "escalation_minutes": "10"
}
```

---

## APPROVALS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/approvals` | List approval (filter: pending/resolved) | owner, supervisor |
| GET | `/outlets/:outletId/approvals/:id` | Detail approval | owner, supervisor, kasir |
| POST | `/outlets/:outletId/approvals` | Request approval | kasir, bar |
| POST | `/outlets/:outletId/approvals/:id/approve` | Approve via notif | owner, supervisor |
| POST | `/outlets/:outletId/approvals/:id/reject` | Reject via notif | owner, supervisor |
| POST | `/outlets/:outletId/approvals/:id/pin` | Approve via PIN di tempat | owner, supervisor |

### POST /outlets/:outletId/approvals/:id/approve
```json
{
  "notes": "Disetujui karena customer reguler"
}
```

### POST /outlets/:outletId/approvals/:id/pin
```json
{
  "pin": "1234"
}
```

---

## NOTIFICATIONS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/notifications` | List notifikasi user aktif | all |
| PUT | `/notifications/:id/read` | Tandai sudah dibaca | all |
| PUT | `/notifications/read-all` | Tandai semua sudah dibaca | all |
| DELETE | `/notifications/:id` | Hapus notifikasi | all |
| GET | `/notifications/unread-count` | Jumlah notif belum dibaca | all |
