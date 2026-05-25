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
  time,
} from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { outlets } from "./01-core";

// ─────────────────────────────────────────────
// ENUMS
// ─────────────────────────────────────────────

export const promoTypeEnum = pgEnum("promo_type", [
  "percent",        // diskon persen
  "nominal",        // diskon nominal
  "buy_x_get_y",    // beli X gratis Y
]);

export const promoTriggerEnum = pgEnum("promo_trigger", [
  "product",        // per produk tertentu
  "category",       // per kategori
  "transaction",    // per total transaksi
  "time",           // per jam/hari (happy hour)
  "payment_method", // per metode bayar
  "member_tier",    // per tier member
]);

export const promoApplyEnum = pgEnum("promo_apply", [
  "auto",   // otomatis
  "manual", // kasir input kode
  "both",
]);

export const discountStatusEnum = pgEnum("discount_status", [
  "active",
  "inactive",
  "expired",
  "limit_reached",
]);

// ─────────────────────────────────────────────
// PROMOS
// ─────────────────────────────────────────────

export const promos = pgTable("promos", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  name: varchar("name", { length: 100 }).notNull(),
  code: varchar("code", { length: 50 }),              // kode voucher (null = tidak ada kode)
  type: promoTypeEnum("type").notNull(),
  applyMethod: promoApplyEnum("apply_method").default("both").notNull(),
  discountPercent: numeric("discount_percent", { precision: 5, scale: 2 }),
  discountAmount: numeric("discount_amount", { precision: 15, scale: 2 }),
  maxDiscountAmount: numeric("max_discount_amount", { precision: 15, scale: 2 }), // cap untuk promo persen
  minTransactionAmount: numeric("min_transaction_amount", { precision: 15, scale: 2 }),
  buyQuantity: integer("buy_quantity"),               // untuk buy X get Y
  getQuantity: integer("get_quantity"),               // untuk buy X get Y
  canStack: boolean("can_stack").default(false),      // bisa kombinasi dengan promo lain?
  usageLimit: integer("usage_limit"),                 // null = unlimited
  usageCount: integer("usage_count").default(0).notNull(),
  startDate: timestamp("start_date").notNull(),
  endDate: timestamp("end_date"),
  happyHourStart: time("happy_hour_start"),
  happyHourEnd: time("happy_hour_end"),
  status: discountStatusEnum("status").default("active").notNull(),
  createdBy: uuid("created_by").notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// PROMO RULES (TRIGGER KONDISI)
// ─────────────────────────────────────────────

export const promoRules = pgTable("promo_rules", {
  id: uuid("id").primaryKey().defaultRandom(),
  promoId: uuid("promo_id").references(() => promos.id, { onDelete: "cascade" }).notNull(),
  trigger: promoTriggerEnum("trigger").notNull(),
  referenceId: uuid("reference_id"),   // product_id / category_id / member_tier_id (nullable untuk transaction/time)
  referenceValue: varchar("reference_value", { length: 50 }), // untuk payment_method, day_of_week, dll
});

// ─────────────────────────────────────────────
// PROMO USAGE LOGS
// ─────────────────────────────────────────────

export const promoUsageLogs = pgTable("promo_usage_logs", {
  id: uuid("id").primaryKey().defaultRandom(),
  promoId: uuid("promo_id").references(() => promos.id).notNull(),
  orderId: uuid("order_id").notNull(),         // ref ke orders
  customerId: uuid("customer_id"),             // ref ke customers (nullable)
  discountAmount: numeric("discount_amount", { precision: 15, scale: 2 }).notNull(),
  appliedBy: varchar("applied_by", { length: 10 }).notNull(), // "auto" | "kasir"
  usedAt: timestamp("used_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// RELATIONS
// ─────────────────────────────────────────────

export const promosRelations = relations(promos, ({ one, many }) => ({
  outlet: one(outlets, { fields: [promos.outletId], references: [outlets.id] }),
  rules: many(promoRules),
  usageLogs: many(promoUsageLogs),
}));

export const promoRulesRelations = relations(promoRules, ({ one }) => ({
  promo: one(promos, { fields: [promoRules.promoId], references: [promos.id] }),
}));
