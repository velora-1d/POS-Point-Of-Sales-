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

export const priceTierEnum = pgEnum("price_tier", [
  "normal",
  "member",
  "grosir",
  "custom", // nama bebas diatur admin
]);

export const stockLogTypeEnum = pgEnum("stock_log_type", [
  "in",           // stok masuk
  "out",          // stok keluar (terjual)
  "adjustment",   // penyesuaian manual
  "write_off",    // dibuang (expired/rusak)
  "reserved",     // di-reserve untuk PO
  "unreserved",   // di-release dari PO
]);

export const expiredActionEnum = pgEnum("expired_action", [
  "auto_deactivate", // otomatis nonaktif
  "notify_only",     // notif saja, admin yang decide
]);

// ─────────────────────────────────────────────
// CATEGORIES
// ─────────────────────────────────────────────

export const categories = pgTable("categories", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  name: varchar("name", { length: 100 }).notNull(),
  description: text("description"),
  sortOrder: integer("sort_order").default(0),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// PRODUCTS
// ─────────────────────────────────────────────

export const products = pgTable("products", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  categoryId: uuid("category_id").references(() => categories.id),
  name: varchar("name", { length: 100 }).notNull(),
  description: text("description"),
  imageUrl: varchar("image_url", { length: 255 }),
  basePrice: numeric("base_price", { precision: 15, scale: 2 }).notNull(),
  hpp: numeric("hpp", { precision: 15, scale: 2 }),             // Harga Pokok Penjualan
  isAvailable: boolean("is_available").default(true).notNull(), // tampil di menu atau tidak
  isActive: boolean("is_active").default(true).notNull(),
  trackStock: boolean("track_stock").default(true).notNull(),
  trackExpired: boolean("track_expired").default(false).notNull(),
  expiredAction: expiredActionEnum("expired_action").default("notify_only"),
  expiredReminderDays: integer("expired_reminder_days").array(), // [7, 3, 1]
  sortOrder: integer("sort_order").default(0),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// PRODUCT VARIANTS
// ─────────────────────────────────────────────

export const productVariants = pgTable("product_variants", {
  id: uuid("id").primaryKey().defaultRandom(),
  productId: uuid("product_id").references(() => products.id, { onDelete: "cascade" }).notNull(),
  name: varchar("name", { length: 100 }).notNull(), // e.g. "Pedas", "Large", "Extra Topping"
  additionalPrice: numeric("additional_price", { precision: 15, scale: 2 }).default("0").notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// PRODUCT PRICES (MULTI HARGA)
// ─────────────────────────────────────────────

export const productPrices = pgTable("product_prices", {
  id: uuid("id").primaryKey().defaultRandom(),
  productId: uuid("product_id").references(() => products.id, { onDelete: "cascade" }).notNull(),
  outletId: uuid("outlet_id").references(() => outlets.id), // null = berlaku semua outlet
  tier: priceTierEnum("tier").notNull(),
  tierLabel: varchar("tier_label", { length: 50 }),          // nama custom tier
  price: numeric("price", { precision: 15, scale: 2 }).notNull(),
  happyHourStart: time("happy_hour_start"),                   // null = tidak ada happy hour
  happyHourEnd: time("happy_hour_end"),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// STOCK (PRODUK JADI)
// ─────────────────────────────────────────────

export const stocks = pgTable("stocks", {
  id: uuid("id").primaryKey().defaultRandom(),
  productId: uuid("product_id").references(() => products.id, { onDelete: "cascade" }).notNull().unique(),
  quantity: numeric("quantity", { precision: 15, scale: 3 }).default("0").notNull(),
  reservedQuantity: numeric("reserved_quantity", { precision: 15, scale: 3 }).default("0").notNull(), // untuk PO
  minimumStock: numeric("minimum_stock", { precision: 15, scale: 3 }).default("0"),
  unit: varchar("unit", { length: 20 }).default("pcs"),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

export const stockLogs = pgTable("stock_logs", {
  id: uuid("id").primaryKey().defaultRandom(),
  productId: uuid("product_id").references(() => products.id).notNull(),
  type: stockLogTypeEnum("type").notNull(),
  quantity: numeric("quantity", { precision: 15, scale: 3 }).notNull(),
  quantityBefore: numeric("quantity_before", { precision: 15, scale: 3 }).notNull(),
  quantityAfter: numeric("quantity_after", { precision: 15, scale: 3 }).notNull(),
  referenceType: varchar("reference_type", { length: 50 }), // "order", "po", "manual"
  referenceId: uuid("reference_id"),
  notes: text("notes"),
  createdBy: uuid("created_by").notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// RAW MATERIALS (BAHAN BAKU)
// ─────────────────────────────────────────────

export const rawMaterials = pgTable("raw_materials", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  name: varchar("name", { length: 100 }).notNull(),
  unit: varchar("unit", { length: 20 }).notNull(),
  quantity: numeric("quantity", { precision: 15, scale: 3 }).default("0").notNull(),
  minimumStock: numeric("minimum_stock", { precision: 15, scale: 3 }).default("0"),
  costPerUnit: numeric("cost_per_unit", { precision: 15, scale: 2 }),
  trackExpired: boolean("track_expired").default(false).notNull(),
  expiredAction: expiredActionEnum("expired_action").default("notify_only"),
  expiredReminderDays: integer("expired_reminder_days").array(),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// PRODUCT INGREDIENTS (RESEP)
// ─────────────────────────────────────────────

export const productIngredients = pgTable("product_ingredients", {
  id: uuid("id").primaryKey().defaultRandom(),
  productId: uuid("product_id").references(() => products.id, { onDelete: "cascade" }).notNull(),
  rawMaterialId: uuid("raw_material_id").references(() => rawMaterials.id).notNull(),
  quantity: numeric("quantity", { precision: 15, scale: 3 }).notNull(), // jumlah bahan per 1 produk
});

// ─────────────────────────────────────────────
// EXPIRED TRACKING
// ─────────────────────────────────────────────

export const expiredTracking = pgTable("expired_tracking", {
  id: uuid("id").primaryKey().defaultRandom(),
  referenceType: varchar("reference_type", { length: 20 }).notNull(), // "product" | "raw_material"
  referenceId: uuid("reference_id").notNull(),
  batchCode: varchar("batch_code", { length: 50 }),
  quantity: numeric("quantity", { precision: 15, scale: 3 }).notNull(),
  expiredDate: timestamp("expired_date").notNull(),
  isHandled: boolean("is_handled").default(false).notNull(), // sudah ditindaklanjuti atau belum
  handledAt: timestamp("handled_at"),
  handledBy: uuid("handled_by"),
  handledNotes: text("handled_notes"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// RELATIONS
// ─────────────────────────────────────────────

export const productsRelations = relations(products, ({ one, many }) => ({
  outlet: one(outlets, { fields: [products.outletId], references: [outlets.id] }),
  category: one(categories, { fields: [products.categoryId], references: [categories.id] }),
  variants: many(productVariants),
  prices: many(productPrices),
  stock: one(stocks),
  stockLogs: many(stockLogs),
  ingredients: many(productIngredients),
}));

export const rawMaterialsRelations = relations(rawMaterials, ({ one, many }) => ({
  outlet: one(outlets, { fields: [rawMaterials.outletId], references: [outlets.id] }),
  ingredients: many(productIngredients),
}));

export const stocksRelations = relations(stocks, ({ one }) => ({
  product: one(products, { fields: [stocks.productId], references: [products.id] }),
}));
