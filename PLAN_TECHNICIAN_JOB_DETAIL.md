# Plan: Technician Job Detail — Status, ETA & Battery Completion

## What the Screenshots Show
- **3378**: Technician sees jobs list (SL, Name, Service, Area, Price, Mobile, Vehicle).
- **3379/3380**: Jobs with Status, Update Status, Action columns. ETA is editable inline on the list page now.

## What We're Building
Technician clicks **View Job** → goes to `/jobs/:id` (show.vue).  
On that page they can:
1. **Update ETA** (new — removed from list page)
2. **Change job status** (Next / Back buttons)
3. **Battery completion popup** — when "Job Completed" is clicked on a battery-type job  
   (service_type name contains "Battery"), show a modal to record:
   - Battery Type, Voltage, Capacity (Ah), Warranty
   - Amount, Payment Method, Receipt upload
   - Saves to payment history with `battery_details` JSON column

---

## Files Changed

| File | Change |
|------|--------|
| `database/migrations/2026_06_14_000000_add_battery_details_to_payments.php` | New — adds `battery_details` JSON column to payments |
| `app/Models/Payment.php` | Add `battery_details` to `$fillable` and `$casts` |
| `app/Http/Controllers/API/JobsController.php` | `addPayment()` accepts and stores `battery_details` |
| `resources/js/pages/sales/jobs/show.vue` | Add ETA input, status buttons, battery popup |
| `resources/js/pages/sales/jobs/index.vue` | Remove ETA inline input (moved to detail page) |

---

## Battery Job Detection
Service types that are battery-related: **"New Battery"**, **"Battery Warranty Claim"** (IDs 5, 6, 13, 14).  
Detection: `job.service_type?.name.toLowerCase().includes('battery')`

## Status Flow (unchanged)
`Assigned → DCC → On The Way → Reached → Job Started → Job Completed`

## Popup Logic
- If next status = "Job Completed" AND service type contains "battery" → show battery+payment popup
- On popup submit → POST status update first, then POST payment with battery_details
- If NOT a battery job → status update happens directly (no popup)

---

## Phase 2 Changes (2026-06-15)

### 1. Mandatory Fields in Battery Completion Form
All fields mandatory except `reference_number` and `notes`:
- `selected_stock_id`, `battery_name`, `battery_brand`, `battery_type`, `voltage`, `capacity`, `warranty`
- `amount`, `payment_method`, `receipt` (file upload)
- Frontend validation added in `submitBatteryCompletion()`
- Backend: `receipt` changed from `nullable` to `required`

### 2. Partial Payment → Pay Remaining Scenario
When `job.status === 'Job Completed'` but `job.payment_status !== 'Paid'`:
- Show "Pay Remaining" button in job detail
- Opens a simple payment modal (not battery popup): amount, method, receipt, ref, notes
- Shows current due amount as placeholder/hint
- After submission: `paid_amount`, `due_amount`, `payment_status` recalculated on backend
- Full payment → `payment_status = 'Paid'`, `due_amount = 0`
- Partial again → shows updated remaining due
- Backend `updatePayment()` now also recalculates job totals after edit

### 3. Battery Stock Decrement on Job Completion
When `addPayment()` receives `selected_stock_id` in `battery_details`:
- Decrement `TechnicianBatteryStock`:
  - `used_quantity += 1`
  - `available_quantity -= 1`
  - `quantity -= 1`
- Write `TechnicianBatteryMovement` audit record (type = 'job_used')

### 4. Rename `reserved_quantity` → `used_quantity`
- New migration: `2026_06_15_000000_rename_reserved_to_used_in_technician_battery_stocks.php`
- Update `TechnicianBatteryStock` model `$fillable`
- Update `TechnicianBatteryStockResource.php` key
- Update `InventoryAdjustmentController.php` references (2 lines)
- Update `batteries/index.vue`:
  - Table column: "Reserved" → "Used Batteries"
  - Summary card: "Reserved (In Use)" → "Used Batteries"
  - Data ref: `stock.reserved_quantity` → `stock.used_quantity`
  - Computed `totalReserved` → `totalUsed`

### What Was Already Done (don't redo)
- Battery completion popup UI and base form — DONE
- Battery dropdown loading from technician stock — DONE
- `battery_details` JSON column on payments — DONE
- Payment history table with battery info column — DONE
- `addPayment()` backend with `battery_details` — DONE
- `updatePayment()` and `deletePayment()` endpoints — DONE
- Edit payment modal (basic) — DONE
- Partial payment indicator in popup — DONE
