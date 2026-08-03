# Technician Job Summary — Plan

## Requirement (from user, in own words)
- Technician's job summary is **auto-fetched** from jobs/payments he has already done — he never manually enters job data.
- Must show totals per payment method: **Cash, POS, POL, Bank Transfer**.
- Must show **Cash In Hand** = cash he physically has left after deductions.
- **Paid By** (Company vs a technician group BT10–BT110) decides who gets reimbursed for the **tyre/battery cost only** (`Job.buying_price`) — NOT the whole job/selling price. Example given by user: tyre cost 2000 + installation 1000 = job price 3000 → only 2000 is deducted, the 1000 install fee stays as real Cash In Hand. **Final rule, locked in 2026-07-09** after two rounds of correction (see history below):
  - `Company` paid for the tyre/battery → fine, no deduction from technician. Tracked separately as a **Company Deduction** (admin-visible, cost-of-goods/`buying_price` only, doesn't touch technician's cash).
  - A technician group (BT-code) or the technician's own pocket fronted the tyre/battery → `buying_price` is subtracted from the technician's Cash In Hand as a **Cash Deduction**, capped at whatever was actually collected as **Cash** on that job (Bank Transfer/POS/POL never passed through his hand, so there's nothing physical to deduct — that portion shows `Cash: 0` in the audit trail and is reconciled bank-side, outside this feature).
  - **Fuel** deductions: already handled by the existing manual "Add Deduction" entries (category=Fuel) — technician logs it, it's summed into `manual_deductions` and subtracted from Cash In Hand. No change needed.
  - **Commission** per job: NOT implemented yet — explicitly deferred by user ("we will calculate commission afterwards"). Will eventually need its own deduction bucket subtracted from Cash In Hand, same shape as the Fuel/cash-deduction handling. **TODO, not started.**
  - `create.vue` already captures `buying_price` (tyre cost) + `selling_price`/`service_charges` (→ `job.price`) on New Tyre jobs — verified 2026-07-09, no gap found, no changes needed there.
- Must support viewing the summary by **Daily / Weekly / Monthly / Yearly / Custom date range** — technician (and admin, via `technician_id` param) can switch the period and see totals recomputed for that window, not just a single fixed day. **Status: implemented** (`period=day|week|month|year|custom` on `resolveRange()`, Period dropdown + date/range pickers in `summary.vue`) — remaining work is verifying its correctness, see gap #3/#4 below.

## What exists today
- `Job.paid_by` (`Company` or `BT10`..`BT110`) — set at job creation (`create.vue`), shown on `show.vue`.
- `TechnicianCashController::summary()` — auto-computes, over a resolved date range:
  - `payment_totals` (grouped by `payment_method`, **only for methods that have payments** — this is bug #1 below)
  - `cash_in_hand = total_cash_collected − (manual entries + job_cash_deductions)`
  - `job_cash_deductions` / `job_cash_deduction_jobs` (jobs where `paid_by` is a BT-code)
  - `company_deductions` / `company_deduction_jobs` (jobs where `paid_by` = Company)
  - `resolveRange()` supports `period=day|week|month|year|custom`
- `technician/summary.vue` — Period selector, stat boxes, Cash In Hand banner, Cash/Company deduction tables, manual "Add Deduction" (Fuel/Commission/Payout/Other, unrelated to job costs).

## Bugs / gaps reported (screenshots 3788, 3789)
1. **Payment method boxes incomplete** — only methods that actually appear in payments render a box (e.g. only "Total Cash" shows). POS, POL, Bank Transfer never render when their total is 0, so they look "missing" instead of showing `0`.
2. **Cash In Hand not obviously a stat** — it's only in the green banner text, not a standalone box like the others, easy to miss.
3. **Weekly (and by extension monthly/yearly) view shows no job-level list** — only the aggregated totals + the two deduction tables (which only list jobs that *caused* a deduction). There's no "Jobs completed this period" table showing every job (name, date, price, payment method, paid_by), so it's not verifiable at a glance that the range actually picked up all jobs done that week — it just looks like a copy of the daily numbers.
4. User perception that weekly "is not performing the deductions" — needs to be re-verified against real multi-day data once #3 (a visible per-job list) exists, since right now there's no way to see which jobs the week's total is built from.

## Step-by-step fix plan — ALL DONE (2026-07-09)
1. **DONE** — Payment method boxes always show all 4 (Cash, Bank Transfer, POS, POL) with `0` fallback. Backend: `TechnicianCashController::PAYMENT_METHODS` constant, `summary()` maps over it instead of only grouping actual payments. Frontend: `summary.vue` iterates a fixed `paymentMethods` array instead of `v-for` over the response object's keys.
2. **DONE** — Added a dedicated green "Cash In Hand" stat box in the top stats row (kept the banner too — it shows the Collected − Deductions formula, the box is the at-a-glance number).
3. **DONE** — Added `jobs_completed` to the summary response (per-job: name, date, price, amount collected, payment methods, paid_by, deduction_type/amount) + a "Jobs Completed" table in `summary.vue` rendering it.
4. **DONE** — Verified with a scripted test (`DB::beginTransaction()` → seed jobs across a week incl. one deliberately outside the range → call the real `TechnicianCashController::summary()` → assert → `DB::rollBack()`, nothing persisted). Per-method totals, POL correctly showing 0 instead of being absent, `company_deductions` only counting the Company job, and the out-of-range job correctly excluded from `jobs_completed` — all confirmed.

## Deduction-amount history (two corrections, now settled)
1. **Original:** deducted `buying_price` only.
2. **2026-07-09, correction #1:** screenshot 3791 (job price 130, buying_price 80, paid_by BT10, all Cash) still showed Cash In Hand 50 — user expected 0. Changed to deduct the **full amount collected**, not just `buying_price`.
3. **2026-07-09, correction #2 (current/final):** user clarified with the tyre/install example above — deduction must be **`buying_price` (tyre cost) only**, not the whole job price. Correction #1 was too aggressive; reverted to `buying_price`, capped at the Cash actually collected on that job (`min(buying_price, cash_collected_for_job)`). This also explains screenshot 3791 in hindsight: that test job's `buying_price` (80) was just entered too low for a same-value job — the formula itself is correct.

Re-verified with the same scripted rolled-back-transaction approach using the user's own numbers (tyre 2000 + install 1000 = job 3000, Cash, BT20): `job_cash_deductions` = 2000 (not 3000), `cash_in_hand` = 1100 (the 1000 install fee stays with the technician, plus an unrelated 100 job). All 8 assertions pass. A Bank-Transfer-paid BT job still correctly contributes 0 to the cash deduction (unchanged from correction #1's finding).

`npm run production` builds clean (only pre-existing Sass deprecation warnings, no errors) — note: this round only touched the PHP controller, no `.vue` changes, so no rebuild was needed for this fix specifically.

## Open TODO
- **Commission per job** — deferred by user, not implemented. When ready: needs a commission amount/rate source (per-job? per-technician flat rate?) and a new deduction bucket subtracted from Cash In Hand alongside the existing tyre-cost and manual-Fuel deductions.
