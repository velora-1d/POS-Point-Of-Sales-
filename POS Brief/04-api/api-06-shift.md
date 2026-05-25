# API Endpoints — 06. Shift & Absensi

Base URL: `/api/v1`
Auth: Bearer Token (JWT)

---

## SHIFT TEMPLATES

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/shift-templates` | List template shift | owner, supervisor |
| POST | `/outlets/:outletId/shift-templates` | Buat template shift | owner |
| PUT | `/outlets/:outletId/shift-templates/:id` | Update template shift | owner |
| DELETE | `/outlets/:outletId/shift-templates/:id` | Hapus template shift | owner |

### POST /outlets/:outletId/shift-templates
```json
{
  "name": "Shift Pagi",
  "start_time": "07:00",
  "end_time": "15:00"
}
```

---

## SHIFTS (KASIR)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/shifts` | List semua shift | owner, supervisor |
| POST | `/outlets/:outletId/shifts/open` | Buka shift | kasir, supervisor |
| GET | `/outlets/:outletId/shifts/active` | Shift aktif saat ini | all |
| GET | `/outlets/:outletId/shifts/:id` | Detail shift | owner, supervisor, kasir |
| POST | `/outlets/:outletId/shifts/:id/close` | Tutup shift | kasir, supervisor |
| GET | `/outlets/:outletId/shifts/:id/summary` | Ringkasan shift berjalan | kasir, supervisor |

### POST /outlets/:outletId/shifts/open
```json
{
  "user_id": "uuid-kasir",
  "shift_template_id": "uuid-template",
  "opening_cash": "500000"
}
```

### POST /outlets/:outletId/shifts/:id/close
```json
{
  "actual_cash": "850000",
  "notes": "Shift berjalan lancar"
}
```
Response:
```json
{
  "shift_id": "uuid-shift",
  "opening_cash": "500000",
  "expected_cash": "875000",
  "actual_cash": "850000",
  "cash_difference": "-25000",
  "total_revenue": "1250000",
  "total_orders": 42,
  "breakdown": {
    "cash": "375000",
    "qris": "500000",
    "debit": "375000",
    "kasbon": "0"
  }
}
```

### GET /outlets/:outletId/shifts
```
Query params:
  - user_id: uuid
  - status: active | closed
  - start_date: ISO date
  - end_date: ISO date
  - page: number
  - limit: number
```

---

## SHIFT CASH REPORTS

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/shifts/:shiftId/cash-report` | Laporan kas shift | owner, supervisor, kasir |
| GET | `/outlets/:outletId/shifts/cash-reports` | Semua laporan kas | owner, supervisor |

---

## EMPLOYEE SCHEDULES (JADWAL)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/schedules` | List jadwal karyawan | owner, supervisor |
| POST | `/outlets/:outletId/schedules` | Buat jadwal | owner, supervisor |
| GET | `/outlets/:outletId/schedules/:id` | Detail jadwal | owner, supervisor |
| PUT | `/outlets/:outletId/schedules/:id` | Update jadwal | owner, supervisor |
| DELETE | `/outlets/:outletId/schedules/:id` | Hapus jadwal | owner, supervisor |
| GET | `/outlets/:outletId/schedules/today` | Jadwal hari ini | owner, supervisor, kasir |
| POST | `/outlets/:outletId/schedules/bulk` | Buat jadwal bulk (1 minggu) | owner, supervisor |

### POST /outlets/:outletId/schedules
```json
{
  "user_id": "uuid-user",
  "shift_template_id": "uuid-template",
  "schedule_date": "2024-02-01"
}
```

### POST /outlets/:outletId/schedules/bulk
```json
{
  "user_id": "uuid-user",
  "shift_template_id": "uuid-template",
  "dates": [
    "2024-02-01",
    "2024-02-02",
    "2024-02-03"
  ]
}
```

---

## ATTENDANCE (ABSENSI)

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | `/outlets/:outletId/attendance` | List absensi | owner, supervisor |
| POST | `/outlets/:outletId/attendance/clock-in` | Clock in | kasir, bar, kitchen |
| POST | `/outlets/:outletId/attendance/clock-out` | Clock out | kasir, bar, kitchen |
| PUT | `/outlets/:outletId/attendance/:id` | Koreksi absensi | owner, supervisor |
| GET | `/outlets/:outletId/attendance/today` | Absensi hari ini | owner, supervisor |
| GET | `/outlets/:outletId/users/:userId/attendance` | Riwayat absensi per karyawan | owner, supervisor |
| GET | `/outlets/:outletId/attendance/report` | Laporan kehadiran | owner |

### POST /outlets/:outletId/attendance/clock-in
```json
{
  "user_id": "uuid-user",
  "schedule_id": "uuid-schedule",
  "notes": null
}
```

### GET /outlets/:outletId/attendance
```
Query params:
  - user_id: uuid
  - status: present | late | absent | leave
  - start_date: ISO date
  - end_date: ISO date
  - page: number
  - limit: number
```

### GET /outlets/:outletId/attendance/report
```
Query params:
  - user_id: uuid (optional, semua karyawan jika tidak diisi)
  - month: number (1-12)
  - year: number
```
Response:
```json
{
  "user_id": "uuid-user",
  "name": "Budi Santoso",
  "month": 2,
  "year": 2024,
  "total_present": 20,
  "total_late": 2,
  "total_absent": 1,
  "total_leave": 1,
  "attendance_rate": "91.67%"
}
```
