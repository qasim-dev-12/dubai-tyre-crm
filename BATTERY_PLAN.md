# Battery Assignment to Technician — Implementation Plan

## Current State (What's Broken)

### Bug 1: Old battery data disappears on re-assignment
**File**: `app/Http/Controllers/API/InventoryAdjustmentController.php` — `update()` method (lines 196–281)

When an admin edits an existing inventory adjustment (re-assigns batteries to the same technician):
- Old `adjustment_products` rows are deleted (line 236)
- New `adjustment_products` rows are created (lines 265–271)
- **BUT**: `technician_battery_stocks` table is NEVER updated

So the technician's stock record goes stale — it keeps the quantity from the original assignment and never reflects the edit. The `store()` method does this correctly using `firstOrNew()` (lines 120–135); the `update()` method is simply missing that block entirely.

### Bug 2: Technician login shows incomplete/no battery data
The `TechnicianBatteryStockController@index` filters by role correctly, but because `update()` never syncs `technician_battery_stocks`, the data shown to the technician is wrong or empty after any re-assignment.

### Missing: TechnicianBatteryMovement model
`Employee.php` and `Product.php` both reference `technicianBatteryMovements()` hasMany, but the model file and its migration do not exist. This audit trail is needed for full history.

---

## Implementation Steps

### Step 1 — Fix the `update()` bug in InventoryAdjustmentController
**File**: `app/Http/Controllers/API/InventoryAdjustmentController.php`

**What to do**:
Before deleting old adjustment products, **reverse their effect** on `technician_battery_stocks`. Then after saving new adjustment products, **apply the new quantities** — exactly the same logic as `store()`.

Pseudocode for the fix:
```
// STEP A: Reverse old stock effect
foreach old adjustmentProducts as oldProduct:
    batteryStock = TechnicianBatteryStock::where(technician_id, product_id)->first()
    if batteryStock exists:
        if oldProduct.adjust_type == 'Increment':   // old was an increase → subtract it back
            batteryStock.quantity -= oldProduct.adjust_qty
            batteryStock.available_quantity -= oldProduct.adjust_qty
        else:                                        // old was a decrease → add it back
            batteryStock.quantity += oldProduct.adjust_qty
            batteryStock.available_quantity += oldProduct.adjust_qty
        batteryStock.save()

// STEP B: Delete old adjustment products (existing line 236)
$adjustment->adjustmentProducts->each->delete()

// STEP C: Save new adjustment products + apply new stock effect (mirror store() logic)
foreach new products in request:
    save new AdjustmentProduct row
    batteryStock = TechnicianBatteryStock::firstOrNew([technician_id, product_id])
    if adjustType == 'Increment':
        batteryStock.quantity += qty
        batteryStock.available_quantity += qty
    else:
        batteryStock.quantity -= qty
        batteryStock.available_quantity -= qty
    batteryStock.save()
```

**Also handle technician change**: If the admin changes which technician an adjustment belongs to, the old technician's stock must be reversed too.

---

### Step 2 — Create TechnicianBatteryMovement migration + model
**New files**:
- `database/migrations/xxxx_create_technician_battery_movements_table.php`
- `app/Models/TechnicianBatteryMovement.php`

**Table columns**:
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| technician_id | FK → employees.id | |
| product_id | FK → products.id | |
| adjustment_id | FK → inventory_adjustments.id nullable | source adjustment |
| movement_type | string | 'assign', 'reverse', 'consumed' |
| quantity | decimal(10,2) | |
| note | text nullable | |
| created_at / updated_at | timestamps | |

**Model relationships**:
- `technician()` belongsTo Employee
- `product()` belongsTo Product
- `adjustment()` belongsTo InventoryAdjustment

**When to write a movement record**:
- On `store()`: write a `assign` movement
- On `update()`: write a `reverse` movement for old quantities, `assign` movement for new
- On `destroy()`: write a `reverse` movement

---

### Step 3 — Verify TechnicianBatteryStockController returns correct data
**File**: `app/Http/Controllers/API/TechnicianBatteryStockController.php`

Check:
- `index()` correctly filters by `auth()->user()->employee->id` for technician role
- Eager-loads `product` and `product.brand` so the Vue page can display brand name, battery type, voltage, capacity
- Returns zero-quantity rows too (so technician can see items were assigned even if used up)

If the eager load is missing, add: `->with(['product', 'product.brand'])`

---

### Step 4 — Verify / fix the Technician Battery Vue page
**File**: `resources/js/pages/technician/batteries/index.vue`

Check:
- Calls `/api/technician-battery-stocks` on mount
- Displays all columns: Brand, Product Name, Battery Type, Voltage, Capacity, Assigned Qty, Available Qty
- Shows a proper "No batteries assigned" empty state instead of blank screen
- Handles pagination if many products assigned

If columns are missing from the API response, fix the `TechnicianBatteryStockResource.php` to include them.

---

### Step 5 — Fix `destroy()` in InventoryAdjustmentController
**File**: `app/Http/Controllers/API/InventoryAdjustmentController.php` — `destroy()` method

Currently when an adjustment is deleted, the `technician_battery_stocks` quantities are NOT reversed. Add the same reversal logic as Step 1 — STEP A before deleting.

---

### Step 6 — (Optional) Add movement history tab on technician battery page
Once `TechnicianBatteryMovement` exists, add a simple history section to the technician battery Vue page showing when batteries were assigned, reversed, or consumed. This gives the technician a clear audit trail.

---

## File Checklist

| # | File | Action |
|---|------|--------|
| 1 | `app/Http/Controllers/API/InventoryAdjustmentController.php` | Fix `update()` and `destroy()` to sync `technician_battery_stocks` |
| 2 | `database/migrations/xxxx_create_technician_battery_movements_table.php` | Create new migration |
| 3 | `app/Models/TechnicianBatteryMovement.php` | Create new model |
| 4 | `app/Http/Controllers/API/TechnicianBatteryStockController.php` | Verify eager loads and filtering |
| 5 | `app/Http/Resources/TechnicianBatteryStockResource.php` | Verify all fields are returned |
| 6 | `resources/js/pages/technician/batteries/index.vue` | Verify all columns shown + empty state |

---

## Implementation Order

```
Step 1  →  Step 5  →  Step 3  →  Step 4  →  Step 2  →  Step 6 (optional)
(core bug fix)         (API verify)  (UI verify)  (audit trail)
```

Steps 1 and 5 are the most critical — fixing these will immediately stop old data from disappearing and make the technician view show correct quantities.
