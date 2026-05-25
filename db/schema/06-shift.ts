import {
  pgTable,
  uuid,
  varchar,
  text,
  boolean,
  timestamp,
  numeric,
  pgEnum,
  time,
} from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { outlets, users } from "./01-core";

// ─────────────────────────────────────────────
// ENUMS
// ─────────────────────────────────────────────

export const shiftStatusEnum = pgEnum("shift_status", [
  "active",
  "closed",
]);

export const attendanceStatusEnum = pgEnum("attendance_status", [
  "present",
  "late",
  "absent",
  "leave",
]);

// ─────────────────────────────────────────────
// SHIFT TEMPLATES (JADWAL)
// ─────────────────────────────────────────────

export const shiftTemplates = pgTable("shift_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  name: varchar("name", { length: 50 }).notNull(),   // e.g. "Shift Pagi", "Shift Malam"
  startTime: time("start_time").notNull(),
  endTime: time("end_time").notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// SHIFTS (KASIR)
// ─────────────────────────────────────────────

export const shifts = pgTable("shifts", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  userId: uuid("user_id").references(() => users.id).notNull(),         // kasir yang jaga
  shiftTemplateId: uuid("shift_template_id").references(() => shiftTemplates.id),
  openedBy: uuid("opened_by").references(() => users.id).notNull(),
  closedBy: uuid("closed_by").references(() => users.id),
  openedAt: timestamp("opened_at").notNull(),
  closedAt: timestamp("closed_at"),
  status: shiftStatusEnum("status").default("active").notNull(),
  openingCash: numeric("opening_cash", { precision: 15, scale: 2 }).notNull(),  // saldo awal
  expectedCash: numeric("expected_cash", { precision: 15, scale: 2 }),          // ekspektasi sistem
  actualCash: numeric("actual_cash", { precision: 15, scale: 2 }),              // hitungan aktual kasir
  cashDifference: numeric("cash_difference", { precision: 15, scale: 2 }),      // selisih
  notes: text("notes"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// SHIFT CASH REPORTS (REKAP KAS PER SHIFT)
// ─────────────────────────────────────────────

export const shiftCashReports = pgTable("shift_cash_reports", {
  id: uuid("id").primaryKey().defaultRandom(),
  shiftId: uuid("shift_id").references(() => shifts.id, { onDelete: "cascade" }).notNull().unique(),
  totalOrders: numeric("total_orders", { precision: 10, scale: 0 }).default("0"),
  totalRevenue: numeric("total_revenue", { precision: 15, scale: 2 }).default("0"),
  totalCash: numeric("total_cash", { precision: 15, scale: 2 }).default("0"),
  totalQris: numeric("total_qris", { precision: 15, scale: 2 }).default("0"),
  totalDebit: numeric("total_debit", { precision: 15, scale: 2 }).default("0"),
  totalEwallet: numeric("total_ewallet", { precision: 15, scale: 2 }).default("0"),
  totalKasbon: numeric("total_kasbon", { precision: 15, scale: 2 }).default("0"),
  totalDiscount: numeric("total_discount", { precision: 15, scale: 2 }).default("0"),
  totalRefund: numeric("total_refund", { precision: 15, scale: 2 }).default("0"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// EMPLOYEE SCHEDULES (JADWAL KARYAWAN)
// ─────────────────────────────────────────────

export const employeeSchedules = pgTable("employee_schedules", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  userId: uuid("user_id").references(() => users.id).notNull(),
  shiftTemplateId: uuid("shift_template_id").references(() => shiftTemplates.id).notNull(),
  scheduleDate: timestamp("schedule_date").notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// ATTENDANCE (ABSENSI)
// ─────────────────────────────────────────────

export const attendance = pgTable("attendance", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  userId: uuid("user_id").references(() => users.id).notNull(),
  scheduleId: uuid("schedule_id").references(() => employeeSchedules.id),
  clockIn: timestamp("clock_in"),
  clockOut: timestamp("clock_out"),
  status: attendanceStatusEnum("status").default("present").notNull(),
  notes: text("notes"),
  date: timestamp("date").notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// RELATIONS
// ─────────────────────────────────────────────

export const shiftsRelations = relations(shifts, ({ one }) => ({
  outlet: one(outlets, { fields: [shifts.outletId], references: [outlets.id] }),
  user: one(users, { fields: [shifts.userId], references: [users.id] }),
  cashReport: one(shiftCashReports),
}));

export const attendanceRelations = relations(attendance, ({ one }) => ({
  outlet: one(outlets, { fields: [attendance.outletId], references: [outlets.id] }),
  user: one(users, { fields: [attendance.userId], references: [users.id] }),
  schedule: one(employeeSchedules, { fields: [attendance.scheduleId], references: [employeeSchedules.id] }),
}));

export const employeeSchedulesRelations = relations(employeeSchedules, ({ one, many }) => ({
  outlet: one(outlets, { fields: [employeeSchedules.outletId], references: [outlets.id] }),
  user: one(users, { fields: [employeeSchedules.userId], references: [users.id] }),
  shiftTemplate: one(shiftTemplates, { fields: [employeeSchedules.shiftTemplateId], references: [shiftTemplates.id] }),
  attendance: many(attendance),
}));
