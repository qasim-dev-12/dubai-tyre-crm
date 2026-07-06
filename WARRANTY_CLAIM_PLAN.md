# Plan: Battery Warranty Claim Module

## Business Rule
- Battery install (via existing "Job Completed" battery popup) already captures `warranty` (months) into `payments.battery_details` JSON.
- Customer can claim warranty **exactly once** per original install. Admin/technician creates a new job to replace the battery.
- The **replacement** battery is never claimable again, even if it also fails — no second claim on the same chain.
- Warranty countdown = `job_completed_at` (install date) + `warranty` months.
- **Nothing is deleted or overwritten.** The original job/payment (old battery install) stays exactly as-is, only flagged (`is_warranty_claimed`, `warranty_claimed_at`, `replacement_job_id`). The claim creates a brand-new job with its own new payment/battery record. Both are permanently linked and viewable: original → `replacement_job_id` points forward, new payment → `claim_of_payment_id` points back.
- When the technician completes the new replacement job and installs a battery, it goes through the **existing** battery-completion flow as-is — technician picks the battery from their own assigned stock, and it decrements `TechnicianBatteryStock` (`used_quantity +1`, `available_quantity -1`, `quantity -1`) + writes a `TechnicianBatteryMovement` audit row, same as any normal battery job. No special-case code needed here, just confirm/test it fires for a "Battery Warranty Claim" service-type job too.

## Data Model Changes

### `payments` table (new migration `add_warranty_fields_to_payments_table`)
| Column | Type | Purpose |
|---|---|---|
| `warranty_months` | int nullable | copied from `battery_details.warranty` for querying |
| `warranty_expires_at` | datetime nullable | `job.job_completed_at` + `warranty_months` |
| `is_warranty_claimed` | boolean default false | one-time claim flag |
| `warranty_claimed_at` | datetime nullable | when claimed |
| `replacement_job_id` | FK → jobs, nullable | the new job created to replace the battery |
| `claim_of_payment_id` | FK → payments (self), nullable | set on the **new** battery-install payment created from a claim; if not null, this install can NEVER be claimed again |

### `jobs` table (new migration `add_warranty_claim_source_to_jobs_table`)
| Column | Type | Purpose |
|---|---|---|
| `warranty_claim_source_payment_id` | FK → payments, nullable | set when job is created as a warranty-replacement job; read when technician completes it so the resulting payment gets `claim_of_payment_id` set |

## Backend

1. **Payment model** — add new columns to `$fillable`, cast `warranty_expires_at`/`warranty_claimed_at` to `datetime`, `is_warranty_claimed` to `boolean`.
2. **JobsController@addPayment** (battery completion path) — when saving:
   - `warranty_months = battery_details.warranty`
   - `warranty_expires_at = job.job_completed_at->addMonths(warranty_months)`
   - if `job.warranty_claim_source_payment_id` is set → save it into new payment's `claim_of_payment_id`
3. **New `WarrantyClaimController`**
   - `GET /api/warranty-claims` — list payments with `warranty_expires_at` not null, join job+client. Return computed `status`: `Active` / `Expired` / `Claimed`. Support search by mobile/vehicle/customer name.
   - `POST /api/warranty-claims/{payment}/claim` — guard: reject if `is_warranty_claimed` true OR `claim_of_payment_id` not null (already a replacement) OR expired. On success:
     - create new `Job` (copy client_id, name, mobile, area, vehicle_number, technician_id, service_type_id = "Battery Warranty Claim"), set `warranty_claim_source_payment_id = payment.id`
     - update original payment: `is_warranty_claimed = true`, `warranty_claimed_at = now()`, `replacement_job_id = newJob.id`
4. Route additions in `routes/api.php` under `auth:api`, gated by existing RBAC permission (reuse jobs permission or add `warranty-claims`).

## Frontend

1. **Job detail (`show.vue`)** — after a battery job is completed, show "Warranty: N days left" (or "Expired") computed from the payment's `warranty_expires_at`. Reuses existing battery payment history block.
2. **New page** `resources/js/pages/sales/warranty/index.vue` — table: Customer, Mobile, Vehicle, Battery (name/brand/type), Installed On, Warranty (months), Expires On, Days Left, Status badge, **Replacement Job** (link, once claimed), Action.
   - "Claim Warranty" button — enabled only when Status=Active and not already a replacement; opens confirm modal (optionally reassign technician) → calls claim API → toast + row updates to "Claimed" with a link to the new job, new job also appears normally in the Jobs list (old job is untouched and still visible there too).
3. **Router** — add route `/warranty-claims`; **sidebar menu** — add "Warranty Claims" item (admin/CSR roles).
4. **Permissions** — add to RBAC seed/UI same as other menu items.

## Sequencing (do in this order)
1. ✅ Migrations (payments + jobs columns) — done 2026-07-04
2. ✅ Payment model updates — done 2026-07-04 (also added Job::warrantyClaimSourcePayment relation)
3. ✅ `addPayment()` warranty computation + claim-chain propagation — done 2026-07-04
4. ✅ `WarrantyClaimController` + routes — done 2026-07-04. Note: replacement job created with price=0/due_amount=0/payment_status=Unpaid so the technician's battery-completion payment flow can still run; verify in step 6 that a $0-due job doesn't get rejected by addPayment()'s `amount > due_amount` check.
5. ✅ Warranty list page (frontend) + menu/route/permission — done 2026-07-04 (`resources/js/pages/sales/warranty/index.vue`, route `warranty-claims.index`, sidebar link gated by `$can('job-list')` for non-technicians)
6. ✅ Claim action (frontend + backend) end-to-end test — done 2026-07-04, run inside a rolled-back DB transaction. Found and fixed a real bug along the way: `addPayment()` and the battery popup in `show.vue` required amount>0 / full-payment, which would have blocked ever completing a free ($0 due) warranty-replacement job. Fixed both to skip the amount/payment-method requirement when `due_amount <= 0`. Full chain verified: install → Active → claim → replacement job (free, due=0) → second claim on original rejected (422) → replacement completed & stock decremented → claim on replacement rejected (422, "itself a warranty replacement").
7. ✅ Job detail warranty badge — done 2026-07-04 (`show.vue` payment history table: new "Warranty" column showing days-left/Expired/Claimed/Replacement-no-claim)
8. ✅ Covered by step 6's scripted end-to-end test (real controller code, rolled-back transaction) — full chain confirmed: install → Active in warranty tab → claim → replacement job → re-claim blocked → replacement completed (stock decremented) → claim on replacement blocked. Not separately click-tested in a live browser session (that would need a logged-in session); code paths and `npm run production` builds are verified clean instead.

## Explicitly Out of Scope (unless asked later)
- SMS/notification to customer on claim
- Editing/undoing a claim
- Warranty on non-battery products
