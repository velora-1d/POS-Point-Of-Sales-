import {
  pgTable,
  uuid,
  varchar,
  text,
  boolean,
  timestamp,
  integer,
  numeric,
  pgEnum,
} from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { outlets } from "./01-core";

// ─────────────────────────────────────────────
// ENUMS
// ─────────────────────────────────────────────

export const memberTierEnum = pgEnum("member_tier", [
  "bronze",
  "silver",
  "gold",
]);

export const pointSourceEnum = pgEnum("point_source", [
  "transaction",   // poin dari nominal transaksi
  "product",       // poin bonus dari produk tertentu
  "manual",        // poin manual oleh admin
]);

export const pointActionEnum = pgEnum("point_action", [
  "earn",
  "redeem_discount",
  "redeem_product",
  "expire",
  "adjustment",
]);

// ─────────────────────────────────────────────
// CUSTOMERS
// ─────────────────────────────────────────────

export const customers = pgTable("customers", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  name: varchar("name", { length: 100 }),
  phone: varchar("phone", { length: 20 }).notNull(), // identifier utama
  email: varchar("email", { length: 100 }),
  birthdate: timestamp("birthdate"),
  isActive: boolean("is_active").default(true).notNull(),
  registeredVia: varchar("registered_via", { length: 20 }).default("kasir"), // kasir | qr_meja | app
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// MEMBERSHIP TIERS (KONFIGURASI)
// ─────────────────────────────────────────────

export const membershipTiers = pgTable("membership_tiers", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  tier: memberTierEnum("tier").notNull(),
  name: varchar("name", { length: 50 }).notNull(),             // nama tampilan (bisa dikustomisasi)
  pointThreshold: integer("point_threshold").notNull(),         // min poin untuk masuk tier ini
  pointRatePerAmount: numeric("point_rate_per_amount", { precision: 10, scale: 4 }).notNull(), // poin per Rp X
  discountPercent: numeric("discount_percent", { precision: 5, scale: 2 }).default("0"),       // diskon otomatis %
  description: text("description"),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// MEMBERSHIPS (PER CUSTOMER)
// ─────────────────────────────────────────────

export const memberships = pgTable("memberships", {
  id: uuid("id").primaryKey().defaultRandom(),
  customerId: uuid("customer_id").references(() => customers.id, { onDelete: "cascade" }).notNull().unique(),
  tierId: uuid("tier_id").references(() => membershipTiers.id).notNull(),
  totalPoints: integer("total_points").default(0).notNull(),       // total poin aktif
  lifetimePoints: integer("lifetime_points").default(0).notNull(), // total poin sepanjang waktu (untuk tier threshold)
  isActive: boolean("is_active").default(true).notNull(),
  joinedAt: timestamp("joined_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// LOYALTY POINT LOGS
// ─────────────────────────────────────────────

export const loyaltyPointLogs = pgTable("loyalty_point_logs", {
  id: uuid("id").primaryKey().defaultRandom(),
  membershipId: uuid("membership_id").references(() => memberships.id, { onDelete: "cascade" }).notNull(),
  action: pointActionEnum("action").notNull(),
  source: pointSourceEnum("source"),
  points: integer("points").notNull(),           // positif = earn, negatif = redeem/expire
  referenceType: varchar("reference_type", { length: 50 }), // "order", "product", "manual"
  referenceId: uuid("reference_id"),
  notes: text("notes"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// LOYALTY REDEEM CATALOG (PRODUK GRATIS)
// ─────────────────────────────────────────────

export const loyaltyRedeemCatalog = pgTable("loyalty_redeem_catalog", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  productId: uuid("product_id").notNull(), // ref ke products (di modul produk)
  pointCost: integer("point_cost").notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// KASBON
// ─────────────────────────────────────────────

export const kasbonStatusEnum = pgEnum("kasbon_status", [
  "outstanding",
  "partial",
  "lunas",
  "written_off",
]);

export const kasbon = pgTable("kasbon", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  customerId: uuid("customer_id").references(() => customers.id).notNull(),
  orderId: uuid("order_id"),           // ref ke orders
  totalAmount: numeric("total_amount", { precision: 15, scale: 2 }).notNull(),
  paidAmount: numeric("paid_amount", { precision: 15, scale: 2 }).default("0").notNull(),
  remainingAmount: numeric("remaining_amount", { precision: 15, scale: 2 }).notNull(),
  dueDate: timestamp("due_date"),
  status: kasbonStatusEnum("status").default("outstanding").notNull(),
  notes: text("notes"),
  createdBy: uuid("created_by").notNull(), // ref ke users
  approvalId: uuid("approval_id"),         // ref ke approvals jika butuh approval
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

export const kasbonPayments = pgTable("kasbon_payments", {
  id: uuid("id").primaryKey().defaultRandom(),
  kasbonId: uuid("kasbon_id").references(() => kasbon.id, { onDelete: "cascade" }).notNull(),
  amount: numeric("amount", { precision: 15, scale: 2 }).notNull(),
  paymentMethod: varchar("payment_method", { length: 30 }).notNull(),
  notes: text("notes"),
  receivedBy: uuid("received_by").notNull(), // ref ke users
  paidAt: timestamp("paid_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// CICILAN
// ─────────────────────────────────────────────

export const cicilanStatusEnum = pgEnum("cicilan_status", [
  "active",
  "completed",
  "overdue",
  "cancelled",
]);

export const cicilan = pgTable("cicilan", {
  id: uuid("id").primaryKey().defaultRandom(),
  kasbonId: uuid("kasbon_id").references(() => kasbon.id, { onDelete: "cascade" }).notNull(),
  installmentNumber: integer("installment_number").notNull(),
  amount: numeric("amount", { precision: 15, scale: 2 }).notNull(),
  dueDate: timestamp("due_date").notNull(),
  paidAt: timestamp("paid_at"),
  status: cicilanStatusEnum("status").default("active").notNull(),
});

// ─────────────────────────────────────────────
// RELATIONS
// ─────────────────────────────────────────────

export const customersRelations = relations(customers, ({ one, many }) => ({
  outlet: one(outlets, { fields: [customers.outletId], references: [outlets.id] }),
  membership: one(memberships),
  kasbon: many(kasbon),
}));

export const membershipsRelations = relations(memberships, ({ one, many }) => ({
  customer: one(customers, { fields: [memberships.customerId], references: [customers.id] }),
  tier: one(membershipTiers, { fields: [memberships.tierId], references: [membershipTiers.id] }),
  pointLogs: many(loyaltyPointLogs),
}));

export const kasbonRelations = relations(kasbon, ({ one, many }) => ({
  customer: one(customers, { fields: [kasbon.customerId], references: [customers.id] }),
  outlet: one(outlets, { fields: [kasbon.outletId], references: [outlets.id] }),
  payments: many(kasbonPayments),
  cicilan: many(cicilan),
}));
