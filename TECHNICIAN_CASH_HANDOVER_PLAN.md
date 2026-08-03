# Technician Cash Handover & Reconciliation — Plan

## Scope
**Cash only, for now.** POS, POL, and Bank Transfer job payments settle directly to a bank/merchant account without ever passing through the technician's hand, so they need no handover/reconciliation step — they'll be wired into the Cashbook ledger separately, later. This plan only makes **Cash** flow from technician → Accounts → ledger.

## Requirement (from user, in own words)
- Technicians collect job payments (cash, POS, POL, bank transfer) directly from customers. Cash physically stays with the technician.
- Periodically (daily/weekly/monthly — technician's choice, not a fixed cutoff) the technician hands the cash over to a designated person (the **Accounts** role).
- The Accounts person must be able to pull up a technician's completed jobs, search/filter them, and cross-check the amount the technician is handing over against what the system says he actually collected.
- Technician can hand over the amount in **2–3 partial installments** rather than one lump sum.
- Technician may **hold back part of the cash** for company-related expenses he pays out of pocket (fuel, etc.) — this is not stealing, it's a legitimate deduction and must reduce what he owes.
- Once Accounts has physically received cash (possibly from several technicians over several days), he deposits it into the company bank account and **uploads the deposit receipt** to the system as proof — this becomes a ledger entry.
- The dashboard must show the cash the company currently has.

## Builds on top of
This plan extends `TechnicianCashController` / `technician/summary.vue`, documented in `TECHNICIAN_JOB_SUMMARY_PLAN.md`. That feature already computes, per technician per period: cash collected, job-cost deductions (tyre/battery fronted by technician), and manual deductions (Fuel/Commission/Payout/Other) → **Cash In Hand**. This plan turns "Cash In Hand" from a read-only report into an actual money-owed balance that gets paid down and banked.

## Decisions made (confirmed with user or taken as best-fit default)
1. **Submission flow**: technician declares a handover in the app (amount + note) → status `Pending`. Accounts cross-checks it against the technician's job/cash summary, then marks it `Received` once the physical cash is actually counted and handed over (can adjust the amount if it doesn't match what was counted, with a note).
2. **Partial payments = running balance, not a closed period.** Each technician has one continuously-accumulating **Outstanding Balance** = lifetime Cash In Hand (all-time, same formula as the existing daily/weekly/monthly summary, just unbounded range) minus everything already marked `Received`. Technician can submit against this balance whenever, in as many installments as needed, no fixed cutoff. This matches "daily or monthly" being the technician's own choice and "2-3 times partial payment" naturally.
3. **Expense hold-back**: already solved by the existing `TechnicianCashEntry` (category=Fuel/Other) manual deductions — those already reduce Cash In Hand today. No new mechanism needed; the Outstanding Balance simply inherits it.
4. **The ledger updates the moment cash is Received, not when it's later banked.** Reusing the existing Cashbook ledger (`Account` / `AccountTransaction`, already powering the Dashboard's cash figures) instead of a parallel ledger:
   - `Account` is just a named ledger bucket (`bank_name`/`account_number` are plain text fields, not enforced as a real bank) — so we add one pseudo-account, **"Cash In Hand"**, representing physical cash the company holds but hasn't banked yet.
   - When Accounts marks a submission `Received`, that immediately creates a credit `AccountTransaction` on **Cash In Hand** — it counts as company cash on the Dashboard right away, matching "when the technician submits to the main person, it goes to the cash."
   - When Accounts later physically deposits that cash into a real bank account, we reuse the **existing Transfer Balance feature** (`TransferBalanceController` / `BalanceTansfer` — already does debit-one-account/credit-another as a linked pair) to move it from Cash In Hand to the real bank `Account`. Total company cash is unchanged by this step (it already counted); only the bucket it sits in changes. We add a receipt-upload field to this transfer so the bank deposit slip attaches to it.
5. **Who reconciles**: existing **Accounts** role (already seeded in `RoleSeeder`) plus Super Admin. No new role.

## What exists today (reuse, do not rebuild)
- `TechnicianCashController::summary()` — per-technician, per-period totals + `jobs_completed` audit trail (job name, date, price, amount collected, payment method, paid_by, deduction). This is the cross-check screen's data source — just needs an unbounded/all-time call for the running balance, and a technician-picker for the Accounts view (already supports `technician_id` param for non-technician callers).
- `TechnicianCashEntry` — manual Fuel/Commission/Payout/Other deductions, already netted into `cash_in_hand`.
- Cashbook `Account` (just a named ledger bucket — `bank_name`/`account_number` are plain labels, not enforced as a real bank) + `AccountTransaction` (`type` 0=debit/1=credit, `reason`, `receipt_no`, `note`, `status`) — the real ledger. `Account::availableBalance()` = credits − debits, already summed for reports.
- `BalanceTansfer` / `TransferBalanceController` — already moves money between two `Account`s as a linked debit+credit pair. This is the "deposit Cash In Hand into the bank" mechanism, reused as-is (plus a receipt field).
- `DashboardController` — reads `AccountTransaction`/`Payment` tables for cash figures already; once Cash In Hand is a real `Account`, its `availableBalance()` shows up in existing cash totals automatically.
- Roles: `Accounts` role already seeded (`RoleSeeder.php`).

## Gaps — what's new
1. No persistent "amount owed by technician" — today's summary is a stateless read of a date range, nothing tracks what's already been handed over.
2. No handover/submission workflow (technician declare → Accounts confirm).
3. No "Cash In Hand" pseudo-account, and nothing creates a ledger entry when Accounts receives cash from a technician.
4. No receipt-upload field on `BalanceTansfer`/`AccountTransaction` for the eventual bank deposit slip.
5. No Accounts-facing "which technicians owe money, how much, since when" reconciliation list.

## Proposed data model
### `technician_cash_submissions` (new)
One row per technician-declared handover installment.
| column | notes |
|---|---|
| `technician_id` | FK employees |
| `amount` | declared amount |
| `submission_date` | |
| `status` | `Pending` \| `Received` \| `Rejected` |
| `received_amount` | nullable — filled if Accounts adjusts the count on physical receipt |
| `note` | technician's note |
| `review_note` | Accounts' note (e.g. reason for adjustment/rejection) |
| `submitted_by` / `received_by` | FK users |
| `received_at` | nullable timestamp |
| `transaction_id` | nullable FK `account_transactions` — the Cash In Hand credit created on Receive |

### Cash In Hand `Account` (new seed row, no new table)
A single `accounts` row (e.g. `bank_name` = "Cash In Hand", flagged/named so it's excluded from "real bank" pickers where that distinction matters) that Received submissions credit into and bank deposits debit out of. Its `availableBalance()` at any moment = physical cash the company is currently holding but hasn't banked.

### `account_transactions` / `balance_transfers` (existing — small extension)
Add a nullable `receipt_path` column so a bank-deposit transfer (Cash In Hand → real bank `Account`, via `TransferBalanceController`) can carry an uploaded deposit-slip image, same idea as `Account.image_path` today.

## Outstanding balance formula
```
outstanding_balance(technician) =
    all_time Cash In Hand (existing summary() formula, unbounded range)
    − SUM(technician_cash_submissions.received_amount WHERE status = 'Received')
```
Exposed via a new `TechnicianCashController::outstanding()`-style endpoint (or a param on `summary()`), used by both the technician's "Submit Cash" panel and the Accounts reconciliation list.

## Proposed flow
1. **Technician** (`summary.vue`, new panel): sees Outstanding Balance, submits a handover amount (≤ balance) with an optional note → creates `Pending` submission.
2. **Accounts** (new page `technician-cash/reconcile`): list of technicians with outstanding balance + pending submissions. Drill into a technician → reuses the existing jobs/cash audit trail (search/filter by date, job, payment method) to visually cross-check → marks a submission `Received` (can override the counted amount) or `Rejected`. Marking `Received` immediately creates a credit `AccountTransaction` on the **Cash In Hand** account and stamps `transaction_id` on the submission — this is the moment it becomes company cash.
3. **Accounts** (existing Cashbook → Transfer Balance page, `transfer-balances/create.vue`): whenever ready to make a bank trip, does a normal balance transfer **from Cash In Hand to a real bank `Account`**, now with a receipt upload attached. No new page needed — just the `receipt_path` field added to the existing transfer form/model.
4. **Dashboard**: Cash In Hand's `availableBalance()` already flows into whatever cash totals the Dashboard reads from `Account`/`AccountTransaction` — plus a small "Outstanding with technicians" figure (sum of all technicians' unreceived balances) so Accounts can see what's still out in the field vs. already in hand.

## API endpoints (new)
- `GET /api/technician-cash-outstanding` — all-time balance (own, or `technician_id` param for Accounts).
- `POST /api/technician-cash-submissions` — technician submits a handover.
- `GET /api/technician-cash-submissions` — list/filter (mine, or all for Accounts) by status.
- `PATCH /api/technician-cash-submissions/{id}/receive` — Accounts marks Received (+ optional amount override) → creates the Cash In Hand credit transaction.
- `PATCH /api/technician-cash-submissions/{id}/reject` — Accounts marks Rejected (+ reason).
- `GET /api/dashboard/technician-cash-outstanding` — sum of all technicians' outstanding balances, for the Dashboard card.
- (Bank deposit itself uses the **existing** `POST /api/balance-transfers` — just gains a receipt file field.)

## UI pages (new)
- Extend `resources/js/pages/technician/summary.vue` with an Outstanding Balance stat + "Submit Cash" form + this technician's own submission history/status.
- `resources/js/pages/technician-cash/reconcile.vue` (Accounts) — technician list with balances → drill-in cross-check view (reuse the jobs-completed table pattern from `summary.vue`) → receive/reject actions.
- `resources/js/pages/cashbook/transfer-balances/create.vue` (existing, small edit) — add receipt upload field for the Cash In Hand → bank deposit case.
- Dashboard: new "Outstanding with Technicians" stat next to the existing cash/account totals (which already include Cash In Hand once it's a seeded `Account`).

## Permissions
New `can:` gates following the existing pattern (see `BalanceController`/`AccountController` middleware): `technician-cash-reconcile`, assigned to `Accounts` + `Super Admin` roles via `PermissionSeeder`/`RoleSeeder`. Bank deposit reuses the existing `account-transfer-balance-create` permission.

## Open questions before implementation
1. Should a `Rejected` submission let the technician resubmit, or does it just get archived and the technician re-declares a fresh amount? (Leaning: archive it, outstanding balance is unaffected since only `Received` counts against it — technician just submits again.)
2. When Accounts overrides the counted amount on `Received` (physical count ≠ declared), should the difference be visible/flagged anywhere (e.g. a small "discrepancy" badge), or is the `review_note` enough?
3. Receipt upload — image only, or also PDF? (Existing `Account.image_path` pattern is image-only; deposit slips are sometimes PDF.)
4. Does every technician need this, or only tyre/battery field technicians who currently go through `TechnicianCashController`? (Assuming same population as the existing Job Summary feature.)

## Build order (once approved)
1. Migration: `technician_cash_submissions`; migration: add `receipt_path` to `account_transactions`; seed the "Cash In Hand" `Account`.
2. Model + `outstanding()` endpoint on `TechnicianCashController` (or a new controller).
3. Submission CRUD + receive/reject endpoints (receive creates the Cash In Hand credit transaction), permissions/roles wiring.
4. `summary.vue` Submit Cash panel.
5. Accounts `reconcile.vue` page, sidebar/router entry.
6. Receipt upload field on `transfer-balances/create.vue` + `TransferBalanceController::store`.
7. Dashboard "Outstanding with Technicians" stat.
