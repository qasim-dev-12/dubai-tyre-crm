<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\TechnicianCashEntry;
use Illuminate\Http\Request;

class TechnicianCashController extends Controller
{
    protected const PAYMENT_METHODS = ['Cash', 'Bank Transfer', 'POS', 'POL'];


    // Resolve which technician's data to work with: technician sees only their own; other roles may pass technician_id
    protected function resolveTechnicianId(Request $request)
    {
        $user = $request->user();
        if ($user->employee && $user->roles()->where('slug', 'technician')->exists() && $user->account_role != 1) {
            return $user->employee->id;
        }
        return $request->get('technician_id');
    }

    // Resolve a [start, end] Carbon range from a period keyword (day/week/month/year/custom)
    protected function resolveRange(Request $request)
    {
        $period = $request->get('period', 'day');
        $now = now();

        switch ($period) {
            case 'week':
                $anchor = $request->get('date') ? \Carbon\Carbon::parse($request->get('date')) : $now;
                return [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()];
            case 'month':
                $anchor = $request->get('date') ? \Carbon\Carbon::parse($request->get('date')) : $now;
                return [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()];
            case 'year':
                $anchor = $request->get('date') ? \Carbon\Carbon::parse($request->get('date')) : $now;
                return [$anchor->copy()->startOfYear(), $anchor->copy()->endOfYear()];
            case 'custom':
                $from = $request->get('from');
                $to = $request->get('to');
                if ($from && $to) {
                    return [\Carbon\Carbon::parse($from)->startOfDay(), \Carbon\Carbon::parse($to)->endOfDay()];
                }
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'day':
            default:
                $date = $request->get('date', $now->toDateString());
                return [\Carbon\Carbon::parse($date)->startOfDay(), \Carbon\Carbon::parse($date)->endOfDay()];
        }
    }

    public function index(Request $request)
    {
        $technicianId = $this->resolveTechnicianId($request);
        [$start, $end] = $this->resolveRange($request);

        $entries = TechnicianCashEntry::where('technician_id', $technicianId)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->latest()
            ->get();

        return response()->json(['data' => $entries]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'nullable|exists:jobs,id',
            'entry_date' => 'required|date',
            'category' => 'required|in:Fuel,Commission,Payout,Other',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255',
        ]);

        $validated['technician_id'] = $this->resolveTechnicianId($request);

        $entry = TechnicianCashEntry::create($validated);

        return response()->json(['message' => 'Entry added', 'data' => $entry], 201);
    }

    public function destroy(Request $request, $id)
    {
        $entry = TechnicianCashEntry::findOrFail($id);
        $technicianId = $this->resolveTechnicianId($request);

        if ($entry->technician_id != $technicianId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $entry->delete();

        return response()->json(['message' => 'Entry deleted']);
    }

    // Daily job summary: totals collected per payment method + cash/company deductions = net cash in hand.
    // Fully derived from Jobs/Payments already recorded — technician never enters job data by hand here.
    public function summary(Request $request)
    {
        $technicianId = $this->resolveTechnicianId($request);
        [$start, $end] = $this->resolveRange($request);

        $payments = Payment::with('job')
            ->whereHas('job', function ($q) use ($technicianId) {
                $q->where('technician_id', $technicianId);
            })->whereBetween('created_at', [$start, $end])->get();

        // Always report all 4 methods (0 if unused) so the UI never "loses" a box.
        $byMethodRaw = $payments->groupBy('payment_method')->map(fn ($g) => $g->sum('amount'));
        $byMethod = collect(self::PAYMENT_METHODS)->mapWithKeys(
            fn ($m) => [$m => (float) ($byMethodRaw[$m] ?? 0)]
        );

        // Who fronted the TYRE/BATTERY (buying_price, i.e. cost of goods only — not the whole job/selling
        // price, e.g. tyre cost 2000 + install 1000 = job price 3000, only the 2000 is reimbursed) decides
        // who gets it back before the technician hands over his cash.
        // - paid_by = 'Company'  -> company already covered the cost: tracked as a Company Deduction
        //                           (admin-visible, cost-of-goods only) for reporting — technician's cash is untouched.
        // - paid_by = a BT-code  -> that technician group/pocket fronted the cost: owed back to them, subtracted
        //                           from the technician's Cash In Hand. Capped at what was actually collected as
        //                           CASH on that job — Bank Transfer/POS/POL never passed through his hand, so
        //                           there's nothing physical to deduct (reconciled bank-side, outside this feature).
        // - paid_by = null       -> nothing to reconcile (legacy job / no goods cost).
        // NOTE: Fuel is deducted separately via manual TechnicianCashEntry (category=Fuel) — already wired below.
        // Technician commission-per-job is NOT deducted here yet (deferred — commission calc not built yet).
        $collectedCashByJob = $payments->where('payment_method', 'Cash')
            ->groupBy('job_id')->map(fn ($g) => $g->sum('amount'));

        $jobs = $payments->pluck('job')->filter()->unique('id');

        $cashDeductionJobs = $jobs->filter(fn ($j) => $j->paid_by && $j->paid_by !== 'Company' && $j->buying_price);
        $companyDeductionJobs = $jobs->filter(fn ($j) => $j->paid_by === 'Company');

        $jobCashDeductions = (float) $cashDeductionJobs->sum(
            fn ($j) => min($j->buying_price, $collectedCashByJob[$j->id] ?? 0)
        );
        $companyDeductions = (float) $companyDeductionJobs->sum(fn ($j) => $j->buying_price ?? 0);

        $entries = TechnicianCashEntry::where('technician_id', $technicianId)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $byCategory = $entries->groupBy('category')->map(fn ($g) => $g->sum('amount'));

        $totalCash = (float) ($byMethod['Cash'] ?? 0);
        $manualDeductions = (float) $entries->sum('amount');
        $totalDeductions = $manualDeductions + $jobCashDeductions;
        $cashInHand = $totalCash - $totalDeductions;

        // Per-job audit trail for the selected range: every job with a payment in-range,
        // what was collected on it, and how it was reconciled (Cash/Company/None deduction).
        $jobsCompleted = $payments->groupBy('job_id')->map(function ($group) {
            $job = $group->first()->job;
            $deductionType = $job->paid_by === 'Company'
                ? 'Company'
                : ($job->paid_by ? 'Cash' : 'None');

            return [
                'id' => $job->id,
                'name' => $job->name,
                'date' => $group->max('created_at')?->toDateString(),
                'price' => $job->price,
                'amount_collected' => $group->sum('amount'),
                'payment_methods' => $group->pluck('payment_method')->unique()->values(),
                'paid_by' => $job->paid_by,
                'deduction_type' => $deductionType,
                'deduction_amount' => $deductionType === 'Cash'
                    ? min($job->buying_price ?? 0, (float) $group->where('payment_method', 'Cash')->sum('amount'))
                    : ($deductionType === 'Company' ? ($job->buying_price ?? 0) : 0),
            ];
        })->values();

        return response()->json([
            'period' => $request->get('period', 'day'),
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'payment_totals' => $byMethod,
            'deduction_totals' => $byCategory,
            'total_collected' => $payments->sum('amount'),
            'total_cash_collected' => $totalCash,
            'manual_deductions' => $manualDeductions,
            'job_cash_deductions' => $jobCashDeductions,
            'job_cash_deduction_jobs' => $cashDeductionJobs
                ->filter(fn ($j) => ($collectedCashByJob[$j->id] ?? 0) > 0)
                ->values()->map(fn ($j) => [
                    'id' => $j->id, 'name' => $j->name, 'paid_by' => $j->paid_by,
                    'amount' => min($j->buying_price, $collectedCashByJob[$j->id]),
                ]),
            'total_deductions' => $totalDeductions,
            'cash_in_hand' => $cashInHand,
            'company_deductions' => $companyDeductions,
            'company_deduction_jobs' => $companyDeductionJobs->values()->map(fn ($j) => [
                'id' => $j->id, 'name' => $j->name, 'paid_by' => $j->paid_by, 'amount' => $j->buying_price,
            ]),
            'jobs_completed' => $jobsCompleted,
            'entries' => $entries,
        ]);
    }
}
