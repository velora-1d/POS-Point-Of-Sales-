# RBAC — Role Matrix

✅ = Punya akses | ❌ = Tidak punya akses | ⚠️ = Butuh approval

## Auth & Order

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `auth:login` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `order:read` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `order:create` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `order:update` | ❌ | ✅ | ✅ | ✅ | ✅ |
| `order:cancel` | ❌ | ❌ | ⚠️ | ✅ | ✅ |
| `order:update_status` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `order:approve_edit` | ❌ | ✅ | ✅ | ✅ | ✅ |
| `order:split_bill` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `order:void` | ❌ | ❌ | ⚠️ | ✅ | ✅ |

## Payment

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `payment:create` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `payment:read` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `payment:refund` | ❌ | ❌ | ⚠️ | ✅ | ✅ |
| `payment:void` | ❌ | ❌ | ⚠️ | ✅ | ✅ |

## Kitchen & Meja

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `kitchen:read` | ✅ | ✅ | ❌ | ✅ | ✅ |
| `kitchen:update_status` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `kitchen:approve` | ❌ | ✅ | ❌ | ❌ | ❌ |
| `table:read` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `table:manage` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `table:qr_generate` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `reservation:read` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `reservation:create` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `reservation:update` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `reservation:cancel` | ❌ | ❌ | ✅ | ✅ | ✅ |

## Produk & Stok

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `product:read` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `product:create` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `product:update` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `product:delete` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `product:toggle_availability` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `stock:read` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `stock:add` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `stock:adjust` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `stock:write_off` | ❌ | ❌ | ❌ | ⚠️ | ✅ |
| `raw_material:read` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `raw_material:manage` | ❌ | ❌ | ❌ | ❌ | ✅ |

## Customer & Loyalty

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `customer:read` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `customer:create` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `customer:update` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `membership:read` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `membership:register` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `membership:manage` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `loyalty:redeem` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `loyalty:adjust` | ❌ | ❌ | ❌ | ❌ | ✅ |

## Kasbon, Cicilan & PO

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `kasbon:read` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `kasbon:create` | ❌ | ❌ | ⚠️ | ✅ | ✅ |
| `kasbon:pay` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `kasbon:write_off` | ❌ | ❌ | ❌ | ⚠️ | ✅ |
| `cicilan:manage` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `po:read` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `po:create` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `po:pay` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `po:cancel` | ❌ | ❌ | ⚠️ | ✅ | ✅ |

## Promo & Shift

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `promo:read` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `promo:create` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `promo:update` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `promo:toggle` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `promo:apply` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `shift:open` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `shift:close` | ❌ | ❌ | ✅ | ✅ | ✅ |
| `shift:read` | ❌ | ❌ | ✅ | ✅ | ✅ |

## Karyawan & Laporan

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `attendance:clock_in` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `attendance:clock_out` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `attendance:read` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `attendance:correct` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `employee:read` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `employee:manage` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `schedule:manage` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `approval:request` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `approval:approve` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `approval:reject` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `approval:rules_manage` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `report:sales` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `report:stock` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `report:finance` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `report:employee` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `report:export` | ❌ | ❌ | ❌ | ✅ | ✅ |

## Settings

| Permission | Kitchen | Bar | Kasir | Supervisor | Owner |
|------------|:-------:|:---:|:-----:|:----------:|:-----:|
| `outlet:manage` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `rbac:manage` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `payment_gateway:manage` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `integration:manage` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `notification:manage` | ❌ | ❌ | ❌ | ✅ | ✅ |
| `printer:manage` | ❌ | ❌ | ❌ | ✅ | ✅ |
