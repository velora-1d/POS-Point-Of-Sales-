import {
  pgTable,
  uuid,
  varchar,
  text,
  boolean,
  timestamp,
  jsonb,
  pgEnum,
} from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";

// ─────────────────────────────────────────────
// ENUMS
// ─────────────────────────────────────────────

export const roleEnum = pgEnum("role_type", [
  "owner",
  "supervisor",
  "kasir",
  "bar",
  "kitchen",
]);

export const notifChannelEnum = pgEnum("notif_channel", [
  "app",
  "whatsapp",
  "email",
]);

// ─────────────────────────────────────────────
// OUTLETS
// ─────────────────────────────────────────────

export const outlets = pgTable("outlets", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: varchar("name", { length: 100 }).notNull(),
  address: text("address"),
  phone: varchar("phone", { length: 20 }),
  isActive: boolean("is_active").default(true).notNull(),
  settings: jsonb("settings").default({}), // konfigurasi per outlet (printer, notif, workflow, dll)
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// ROLES
// ─────────────────────────────────────────────

export const roles = pgTable("roles", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }),
  name: varchar("name", { length: 50 }).notNull(),
  type: roleEnum("type").notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// PERMISSIONS
// ─────────────────────────────────────────────

export const permissions = pgTable("permissions", {
  id: uuid("id").primaryKey().defaultRandom(),
  module: varchar("module", { length: 50 }).notNull(),   // e.g. "order", "stock", "report"
  action: varchar("action", { length: 50 }).notNull(),   // e.g. "create", "read", "update", "delete", "approve"
  description: text("description"),
});

export const rolePermissions = pgTable("role_permissions", {
  id: uuid("id").primaryKey().defaultRandom(),
  roleId: uuid("role_id").references(() => roles.id, { onDelete: "cascade" }).notNull(),
  permissionId: uuid("permission_id").references(() => permissions.id, { onDelete: "cascade" }).notNull(),
});

// ─────────────────────────────────────────────
// USERS (KARYAWAN)
// ─────────────────────────────────────────────

export const users = pgTable("users", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  roleId: uuid("role_id").references(() => roles.id).notNull(),
  name: varchar("name", { length: 100 }).notNull(),
  email: varchar("email", { length: 100 }).unique(),
  phone: varchar("phone", { length: 20 }),
  passwordHash: varchar("password_hash", { length: 255 }).notNull(),
  approvalPin: varchar("approval_pin", { length: 255 }), // PIN untuk approval transaksi
  isActive: boolean("is_active").default(true).notNull(),
  joinDate: timestamp("join_date"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// APPROVAL RULES
// ─────────────────────────────────────────────

export const approvalActionEnum = pgEnum("approval_action", [
  "refund",
  "void",
  "discount_manual",
  "order_cancel",
  "price_override",
  "kasbon_create",
  "po_create",
  "edit_order",
  "stock_write_off",
]);

export const approvalMechanismEnum = pgEnum("approval_mechanism", [
  "pin",
  "notif",
  "both",
]);

export const approvalRules = pgTable("approval_rules", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id, { onDelete: "cascade" }).notNull(),
  action: approvalActionEnum("action").notNull(),
  approverRoleId: uuid("approver_role_id").references(() => roles.id).notNull(),
  mechanism: approvalMechanismEnum("mechanism").default("both").notNull(),
  thresholdAmount: varchar("threshold_amount", { length: 20 }), // nominal trigger (nullable = selalu butuh approval)
  escalationMinutes: varchar("escalation_minutes", { length: 10 }).default("10"), // menit sebelum eskalasi
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// APPROVALS (LOG)
// ─────────────────────────────────────────────

export const approvalStatusEnum = pgEnum("approval_status", [
  "pending",
  "approved",
  "rejected",
  "escalated",
]);

export const approvals = pgTable("approvals", {
  id: uuid("id").primaryKey().defaultRandom(),
  outletId: uuid("outlet_id").references(() => outlets.id).notNull(),
  action: approvalActionEnum("action").notNull(),
  requestedBy: uuid("requested_by").references(() => users.id).notNull(),
  approvedBy: uuid("approved_by").references(() => users.id),
  status: approvalStatusEnum("status").default("pending").notNull(),
  referenceType: varchar("reference_type", { length: 50 }), // "order", "kasbon", "po", dll
  referenceId: uuid("reference_id"),                        // ID dari referenceType
  notes: text("notes"),
  rejectionReason: text("rejection_reason"),
  requestedAt: timestamp("requested_at").defaultNow().notNull(),
  resolvedAt: timestamp("resolved_at"),
});

// ─────────────────────────────────────────────
// NOTIFICATIONS
// ─────────────────────────────────────────────

export const notificationTypeEnum = pgEnum("notification_type", [
  "order_new",
  "order_edit",
  "order_cancel",
  "approval_request",
  "approval_result",
  "stock_low",
  "stock_expired",
  "kasbon_due",
  "po_due",
  "shift_closed",
  "shift_cash_diff",
]);

export const notifications = pgTable("notifications", {
  id: uuid("id").primaryKey().defaultRandom(),
  userId: uuid("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  type: notificationTypeEnum("type").notNull(),
  title: varchar("title", { length: 100 }).notNull(),
  body: text("body"),
  referenceType: varchar("reference_type", { length: 50 }),
  referenceId: uuid("reference_id"),
  isRead: boolean("is_read").default(false).notNull(),
  channel: notifChannelEnum("channel").default("app").notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// ─────────────────────────────────────────────
// RELATIONS
// ─────────────────────────────────────────────

export const outletsRelations = relations(outlets, ({ many }) => ({
  users: many(users),
  roles: many(roles),
  approvalRules: many(approvalRules),
  approvals: many(approvals),
}));

export const rolesRelations = relations(roles, ({ one, many }) => ({
  outlet: one(outlets, { fields: [roles.outletId], references: [outlets.id] }),
  permissions: many(rolePermissions),
  users: many(users),
}));

export const usersRelations = relations(users, ({ one, many }) => ({
  outlet: one(outlets, { fields: [users.outletId], references: [outlets.id] }),
  role: one(roles, { fields: [users.roleId], references: [roles.id] }),
  notifications: many(notifications),
}));

export const approvalsRelations = relations(approvals, ({ one }) => ({
  requestedBy: one(users, { fields: [approvals.requestedBy], references: [users.id] }),
  approvedBy: one(users, { fields: [approvals.approvedBy], references: [users.id] }),
  outlet: one(outlets, { fields: [approvals.outletId], references: [outlets.id] }),
}));
