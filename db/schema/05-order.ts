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
  jsonb,
} from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { outlets, users } from "./01-core";
import { customers } from "./02-customer";
import { products, productVariants } from "./03-product";
import { promos } from "./04-promo";

// ─────────────────────────────────────────────
// ENUMS
// ─────────────────────────────────────────────

export const orderStatusEnum = pgEnum("order_status", [
  "pending",
  "in_progress",
  "waiting_bar_approval",
  "ready",
  "delivered",
  "completed",
  "cancelled",
  "pending_edit_approval", // customer edit via QR, tunggu approval
]);

export const orderSourceEnum = pgEnum("order_source", [
  "kasir",
  "qr_meja",
  "gofood",
  "grabfood",
]);

export const orderTypeEnum = pgEnum("order_type", [
  "dine_in",
  "takeaway",
  "online",
  "po",         // pre-order
]);

export const paymentMethodEnum = pgEnum("payment_method", [
  "cash",
  "qris",
  "debit",
  "gopay",
  "ovo",
  "dana",
  "shopeepay",
  "kasbon",
  "cicilan",
  "dp",         // down payment (PO)
]);

export const paymentStatusEnum = pgEnum("payment_status", [
  "pending",
  "partial",    // DP sudah dibayar
  "paid",
  "refunded",
  "voided",
]);

export const receiptMethodEnum = pgEnum("receipt_method", [
  "print",
  "whatsapp",
  "skip",
]);

// ─────────────────────────────────────────────
// TABLES (MEJA)
// ─────────────────────────────────────────────

export const tableStatusEnum = pgEnum("table_status", [
  "available",
  "occupied",
  "reserved",
]);

export const tables = pgTable("tables", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  name: varchar("name", { length: 50 }).notNull(),     // e.g. "Meja 1", "VIP 1"
  capacity: integer("capacity"),
  category: varchar("category", { length: 30 }).default("indoor").notNull(),
  qrCode: varchar("qr_code", { length: 255 }),         // QR code unik per meja
  barcodeTracking: varchar("barcode_tracking", { length: 100 }),
  qrSessionToken: varchar("qr_session_token", { length: 100 }).unique(), // token unik per meja untuk QR
  positionX: integer("position_x"),                    // untuk layout visual
  positionY: integer("position_y"),
  status: tableStatusEnum("status").default("available").notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// RESERVATIONS
// ─────────────────────────────────────────────

export const reservationStatusEnum = pgEnum("reservation_status", [
  "pending",
  "confirmed",
  "arrived",
  "cancelled",
  "no_show",
]);

export const reservations = pgTable("reservations", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  tableId: uuid("table_id").references(() => tables.id),
  customerId: uuid("customer_id").references(() => customers.id),
  customerName: varchar("customer_name", { length: 100 }),
  customerPhone: varchar("customer_phone", { length: 20 }),
  guestCount: integer("guest_count"),
  reservedAt: timestamp("reserved_at").notNull(),
  status: reservationStatusEnum("status").default("pending").notNull(),
  notes: text("notes"),
  createdBy: uuid("created_by").references(() => users.id),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// ORDERS
// ─────────────────────────────────────────────

export const orders = pgTable("orders", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  shiftId: uuid("shift_id"),                              // ref ke shifts
  tableId: uuid("table_id").references(() => tables.id),
  customerId: uuid("customer_id").references(() => customers.id),
  cashierId: uuid("cashier_id").references(() => users.id),
  orderNumber: varchar("order_number", { length: 20 }).notNull(), // e.g. "ORD-20240101-001"
  source: orderSourceEnum("source").default("kasir").notNull(),
  type: orderTypeEnum("type").default("dine_in").notNull(),
  status: orderStatusEnum("status").default("pending").notNull(),
  subtotal: numeric("subtotal", { precision: 15, scale: 2 }).notNull(),
  discountAmount: numeric("discount_amount", { precision: 15, scale: 2 }).default("0").notNull(),
  totalAmount: numeric("total_amount", { precision: 15, scale: 2 }).notNull(),
  paidAmount: numeric("paid_amount", { precision: 15, scale: 2 }).default("0").notNull(),
  notes: text("notes"),
  estimatedTime: integer("estimated_time"),               // estimasi waktu masak (menit) — diset kasir atau default kategori
  cookingStartedAt: timestamp("cooking_started_at"),      // saat kitchen klik "Mulai Masak"
  pendingStartedAt: timestamp("pending_started_at"),      // saat order masuk (untuk waiting timer)
  receiptMethod: receiptMethodEnum("receipt_method"),
  receiptPhone: varchar("receipt_phone", { length: 20 }), // untuk kirim WA
  isPrinted: boolean("is_printed").default(false),
  externalOrderId: varchar("external_order_id", { length: 100 }), // GoFood/GrabFood order ID
  externalPlatform: varchar("external_platform", { length: 20 }), // "gofood" | "grabfood"
  payLater: boolean("pay_later").default(false).notNull(),       // kasir: bayar nanti saat DELIVERED
  qrSessionToken: varchar("qr_session_token", { length: 100 }), // token sesi QR meja (customer self-service)
  metadata: jsonb("metadata").default({}),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// ORDER ITEMS
// ─────────────────────────────────────────────

export const orderItems = pgTable("order_items", {
  id: uuid("id").primaryKey().defaultRandom(),
  orderId: uuid("order_id").references(() => orders.id, { onDelete: "cascade" }).notNull(),
  productId: uuid("product_id").references(() => products.id).notNull(),
  variantId: uuid("variant_id").references(() => productVariants.id),
  quantity: integer("quantity").notNull(),
  unitPrice: numeric("unit_price", { precision: 15, scale: 2 }).notNull(),
  totalPrice: numeric("total_price", { precision: 15, scale: 2 }).notNull(),
  notes: text("notes"),                                    // catatan khusus (tidak pedas, dll)
  priceTier: varchar("price_tier", { length: 30 }),        // tier harga yang dipakai
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// ORDER STATUS LOGS
// ─────────────────────────────────────────────

export const orderStatusLogs = pgTable("order_status_logs", {
  id: uuid("id").primaryKey().defaultRandom(),
  orderId: uuid("order_id").references(() => orders.id, { onDelete: "cascade" }).notNull(),
  fromStatus: orderStatusEnum("from_status"),
  toStatus: orderStatusEnum("to_status").notNull(),
  changedBy: uuid("changed_by"),                           // user id (null = system/customer)
  changedByType: varchar("changed_by_type", { length: 20 }), // "user" | "customer" | "system"
  notes: text("notes"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// ORDER DISCOUNTS (RELASI ORDER ↔ PROMO)
// ─────────────────────────────────────────────

export const orderDiscounts = pgTable("order_discounts", {
  id: uuid("id").primaryKey().defaultRandom(),
  orderId: uuid("order_id").references(() => orders.id, { onDelete: "cascade" }).notNull(),
  promoId: uuid("promo_id").references(() => promos.id),
  discountType: varchar("discount_type", { length: 20 }).notNull(), // "promo" | "member_tier" | "manual"
  discountAmount: numeric("discount_amount", { precision: 15, scale: 2 }).notNull(),
  appliedBy: uuid("applied_by"),                           // null = auto
  approvalId: uuid("approval_id"),                         // jika butuh approval
});

// ─────────────────────────────────────────────
// PAYMENTS
// ─────────────────────────────────────────────

export const payments = pgTable("payments", {
  id: uuid("id").primaryKey().defaultRandom(),
  orderId: uuid("order_id").references(() => orders.id).notNull(),
  method: paymentMethodEnum("method").notNull(),
  amount: numeric("amount", { precision: 15, scale: 2 }).notNull(),
  status: paymentStatusEnum("status").default("pending").notNull(),
  referenceNumber: varchar("reference_number", { length: 100 }), // no ref payment gateway
  gatewayResponse: jsonb("gateway_response"),
  processedBy: uuid("processed_by").references(() => users.id),
  processedAt: timestamp("processed_at"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// PO / DOWN PAYMENT ORDERS
// ─────────────────────────────────────────────

export const poStatusEnum = pgEnum("po_status", [
  "pending",
  "dp_paid",
  "completed",
  "cancelled",
]);

export const poOrders = pgTable("po_orders", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  orderId: uuid("order_id").references(() => orders.id),
  customerId: uuid("customer_id").references(() => customers.id),
  totalAmount: numeric("total_amount", { precision: 15, scale: 2 }).notNull(),
  dpAmount: numeric("dp_amount", { precision: 15, scale: 2 }).default("0").notNull(),
  remainingAmount: numeric("remaining_amount", { precision: 15, scale: 2 }).notNull(),
  pickupDate: timestamp("pickup_date"),
  status: poStatusEnum("status").default("pending").notNull(),
  notes: text("notes"),
  createdBy: uuid("created_by").references(() => users.id),
  approvalId: uuid("approval_id"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// SPLIT BILLS
// ─────────────────────────────────────────────

export const splitBills = pgTable("split_bills", {
  id: uuid("id").primaryKey().defaultRandom(),
  originalOrderId: uuid("original_order_id").references(() => orders.id).notNull(),
  splitOrderId: uuid("split_order_id").references(() => orders.id).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// RELATIONS
// ─────────────────────────────────────────────

export const ordersRelations = relations(orders, ({ one, many }) => ({
  outlet: one(outlets, { fields: [orders.outletId], references: [outlets.id] }),
  table: one(tables, { fields: [orders.tableId], references: [tables.id] }),
  customer: one(customers, { fields: [orders.customerId], references: [customers.id] }),
  cashier: one(users, { fields: [orders.cashierId], references: [users.id] }),
  items: many(orderItems),
  statusLogs: many(orderStatusLogs),
  discounts: many(orderDiscounts),
  payments: many(payments),
}));

export const orderItemsRelations = relations(orderItems, ({ one }) => ({
  order: one(orders, { fields: [orderItems.orderId], references: [orders.id] }),
  product: one(products, { fields: [orderItems.productId], references: [products.id] }),
  variant: one(productVariants, { fields: [orderItems.variantId], references: [productVariants.id] }),
}));

export const tablesRelations = relations(tables, ({ one, many }) => ({
  outlet: one(outlets, { fields: [tables.outletId], references: [outlets.id] }),
  orders: many(orders),
  reservations: many(reservations),
}));

export const poOrdersRelations = relations(poOrders, ({ one }) => ({
  outlet: one(outlets, { fields: [poOrders.outletId], references: [outlets.id] }),
  customer: one(customers, { fields: [poOrders.customerId], references: [customers.id] }),
  order: one(orders, { fields: [poOrders.orderId], references: [orders.id] }),
}));
