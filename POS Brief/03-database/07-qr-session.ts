import {
  pgTable,
  uuid,
  varchar,
  boolean,
  timestamp,
  pgEnum,
} from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { outlets } from "./01-core";
import { customers } from "./02-customer";
import { tables } from "./05-order";

// ─────────────────────────────────────────────
// ENUMS
// ─────────────────────────────────────────────

export const qrSessionStatusEnum = pgEnum("qr_session_status", [
  "active",     // sesi aktif, customer bisa pesan
  "ordered",    // order sudah dibuat, menunggu bayar
  "paid",       // sudah bayar, order masuk kasir
  "expired",    // sesi expired (tidak bayar dalam X menit)
]);

// ─────────────────────────────────────────────
// QR SESSIONS
// Sesi customer yang scan QR meja
// ─────────────────────────────────────────────

export const qrSessions = pgTable("qr_sessions", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  tableId: uuid("table_id").references(() => tables.id).notNull(),
  customerId: uuid("customer_id").references(() => customers.id), // null jika customer baru
  sessionToken: varchar("session_token", { length: 100 }).notNull().unique(),
  customerName: varchar("customer_name", { length: 100 }).notNull(),
  customerPhone: varchar("customer_phone", { length: 20 }).notNull(),
  status: qrSessionStatusEnum("status").default("active").notNull(),
  orderId: uuid("order_id"),       // diisi setelah order dibuat
  paymentId: uuid("payment_id"),   // diisi setelah payment request dibuat
  expiresAt: timestamp("expires_at").notNull(), // sesi expired dalam X menit
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// RELATIONS
// ─────────────────────────────────────────────

export const qrSessionsRelations = relations(qrSessions, ({ one }) => ({
  outlet: one(outlets, { fields: [qrSessions.outletId], references: [outlets.id] }),
  table: one(tables, { fields: [qrSessions.tableId], references: [tables.id] }),
  customer: one(customers, { fields: [qrSessions.customerId], references: [customers.id] }),
}));
