<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobJourney;
use App\Models\Payment;
use App\Models\ServiceType;
use App\Models\TechnicianBatteryStock;
use App\Models\TechnicianBatteryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;



class JobsController extends Controller
{
    /**
     * Display list of jobs
     */
    protected const IN_PROGRESS_STATUSES = ['DCC', 'On The Way', 'Reached', 'Job Started'];

    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $term    = $request->get('term');
        $user = $request->user(); // ✅ correct for auth:api

        $query = Job::with(['serviceType', 'technician', 'payments.replacementJob', 'warrantyClaimSourcePayment.job']);

        $isTechnician = $this->isTechnicianUser($user);
        if ($isTechnician) {
            if ($user->employee) {
                $query->where('technician_id', $user->employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // 🔎 Search support
        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('mobile', 'like', "%{$term}%")
                  ->orWhere('area', 'like', "%{$term}%")
                  ->orWhere('vehicle_number', 'like', "%{$term}%");
            });
        }

        // 🔎 Dedicated filters: name, service type, area
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->get('name') . '%');
        }
        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', $request->get('service_type_id'));
        }
        if ($request->filled('area')) {
            $query->where('area', 'like', '%' . $request->get('area') . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->get('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->get('price_max'));
        }

        // 📅 Date range filter (today / week / month / custom)
        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->get('date_filter'),
            $request->get('date_from'),
            $request->get('date_to')
        );
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        // 🛡️ Warranty filter: jobs with a warrantied battery-install payment (source jobs),
        // OR jobs created *from* a warranty claim (replacement jobs, even before they're completed
        // and have a payment of their own).
        if ($request->boolean('warranty')) {
            $query->where(function ($q) {
                $q->whereNotNull('warranty_claim_source_payment_id')
                    ->orWhereHas('payments', function ($p) {
                        $p->whereNotNull('warranty_expires_at');
                    });
            });

            // 🔋🛞 Warranty type: "Battery" / "New Battery" / "Battery Warranty Claim" all
            // contain "battery"; "Tyre Repair" / "Tyre Repair Warranty Claim" both contain
            // "tyre repair" — so a keyword match on the job's own service type name covers
            // both the original job and its warranty-claim replacement job.
            if ($request->filled('warranty_type')) {
                $keyword = $request->get('warranty_type') === 'tyre_repair' ? 'tyre repair' : 'battery';
                $query->whereHas('serviceType', function ($st) use ($keyword) {
                    $st->where('name', 'like', "%{$keyword}%");
                });
            }
        }

        // Stats reflect the current search/date scope, independent of the tab filter
        $statsQuery = clone $query;

        // 🗂️ Tab filter: new (untouched) / in_progress (started but not completed) / completed
        $tab = $request->get('tab');
        if ($tab === 'new') {
            $query->where('status', 'Assigned');
        } elseif ($tab === 'in_progress') {
            $query->whereIn('status', self::IN_PROGRESS_STATUSES);
        } elseif ($tab === 'completed') {
            $query->where('status', 'Job Completed');
        }

        // 🔝 New/uncompleted jobs first, completed jobs last (newest within each group first)
        $query->orderByRaw("CASE
                WHEN status = 'Assigned' THEN 0
                WHEN status IN ('" . implode("','", self::IN_PROGRESS_STATUSES) . "') THEN 1
                WHEN status = 'Job Completed' THEN 2
                ELSE 3
            END")
            ->latest();

        $result = $query->paginate($perPage)->toArray();

        $result['stats'] = [
            'new' => (clone $statsQuery)->where('status', 'Assigned')->count(),
            'in_progress' => (clone $statsQuery)->whereIn('status', self::IN_PROGRESS_STATUSES)->count(),
            'completed' => (clone $statsQuery)->where('status', 'Job Completed')->count(),
            'total' => (clone $statsQuery)->count(),
        ];

        return response()->json($result);
    }

    /**
     * Resolve a [start, end] Carbon range for the given named filter, or an explicit custom range.
     */
    protected function resolveDateRange($filter, $from, $to)
    {
        $now = now();

        switch ($filter) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'custom':
                if ($from && $to) {
                    return [
                        \Carbon\Carbon::parse($from)->startOfDay(),
                        \Carbon\Carbon::parse($to)->endOfDay(),
                    ];
                }
                return [null, null];
            default:
                return [null, null];
        }
    }

    /**
     * Store a newly created job
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'service_type_id' => 'required|exists:service_types,id',
            'vehicle_number' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'technician_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|in:Assigned,DCC,On The Way,Reached,Job Started,Job Completed',
            'brand' => 'nullable|string|max:255',
            'tyre_width' => 'nullable|string|max:10',
            'tyre_height' => 'nullable|string|max:10',
            'tyre_rim' => 'nullable|string|max:10',
            'size' => 'nullable|string|max:255',
            'buying_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'service_charges' => 'nullable|numeric|min:0',
            'paid_by' => 'nullable|in:' . implode(',', Job::PAID_BY_OPTIONS)
        ]);

        $serviceType = ServiceType::find($request->service_type_id);
        if ($serviceType && strtolower($serviceType->name) === 'new tyre') {
            $request->validate([
                'tyre_width' => 'required|string|max:10',
                'tyre_height' => 'required|string|max:10',
                'tyre_rim' => 'required|string|max:10',
            ]);
        }

        try {
            $job = Job::create([
                'name' => $request->name,
                'mobile' => $request->mobile,
                'service_type_id' => $request->service_type_id,
                'vehicle_number' => $request->vehicle_number,
                'area' => $request->area,
                'price' => $request->price,
                'technician_id' => $request->technician_id,
                'status' => $request->status ?? 'Assigned',
                'paid_amount' => 0,
                'due_amount' => $request->price ?? 0,
                'payment_status' => 'Unpaid',
                'paid_by' => $request->paid_by,
                'brand' => $request->brand,
                'tyre_width' => $request->tyre_width,
                'tyre_height' => $request->tyre_height,
                'tyre_rim' => $request->tyre_rim,
                'size' => $request->size ?? ($request->tyre_width && $request->tyre_height && $request->tyre_rim ? "{$request->tyre_width}/{$request->tyre_height}R{$request->tyre_rim}" : null),
                'buying_price' => $request->buying_price,
                'selling_price' => $request->selling_price,
                'service_charges' => $request->service_charges
            ]);

            JobJourney::create([
                'job_id' => $job->id,
                'status' => 'Job Created',
                'message' => 'Job created successfully',
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'message' => 'Job created successfully',
                'data' => $job->load('serviceType', 'technician')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing job's details (admin only route)
     */
    public function update(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'service_type_id' => 'required|exists:service_types,id',
            'vehicle_number' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'technician_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|in:' . implode(',', Job::STATUS_FLOW),
            'brand' => 'nullable|string|max:255',
            'tyre_width' => 'nullable|string|max:10',
            'tyre_height' => 'nullable|string|max:10',
            'tyre_rim' => 'nullable|string|max:10',
            'size' => 'nullable|string|max:255',
            'buying_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'service_charges' => 'nullable|numeric|min:0',
            'paid_by' => 'nullable|in:' . implode(',', Job::PAID_BY_OPTIONS)
        ]);

        $job->fill([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'service_type_id' => $request->service_type_id,
            'vehicle_number' => $request->vehicle_number,
            'area' => $request->area,
            'price' => $request->price,
            'technician_id' => $request->technician_id,
            'status' => $request->status ?? $job->status,
            'paid_by' => $request->paid_by,
            'brand' => $request->brand,
            'tyre_width' => $request->tyre_width,
            'tyre_height' => $request->tyre_height,
            'tyre_rim' => $request->tyre_rim,
            'size' => $request->size ?? ($request->tyre_width && $request->tyre_height && $request->tyre_rim ? "{$request->tyre_width}/{$request->tyre_height}R{$request->tyre_rim}" : null),
            'buying_price' => $request->buying_price,
            'selling_price' => $request->selling_price,
            'service_charges' => $request->service_charges,
        ]);

        // Keep due_amount consistent if the price changed
        $job->due_amount = $job->price - $job->paid_amount;

        $job->save();

        return response()->json([
            'message' => 'Job updated successfully',
            'data' => $job->load('serviceType', 'technician')
        ]);
    }

    /**
     * Update job status
     */
    public function updateStatus(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        $this->authorizeAssignedTechnician($request, $job);

        $request->validate([
            'status' => 'required|string|in:' . implode(',', Job::STATUS_FLOW)
        ]);

        $flow = Job::STATUS_FLOW;

        // Case-insensitive lookup so legacy/dirty status values (e.g. "assigned"
        // instead of "Assigned") don't permanently lock a job out of transitions.
        $currentIndex = array_search(strtolower(trim($job->status)), array_map('strtolower', $flow));
        $newIndex     = array_search($request->status, $flow);

        if ($newIndex === false) {
            return response()->json([
                'message' => 'Invalid status'
            ], 422);
        }

        // Allow only forward or backward ONE step
        if (!($newIndex === $currentIndex + 1 || $newIndex === $currentIndex - 1)) {
            return response()->json([
                'message' => 'Invalid transition'
            ], 422);
        }

        // Prevent revert if payment completed
        if ($job->payment_status === 'Paid' && $newIndex < $currentIndex) {
            return response()->json([
                'message' => 'Cannot revert after payment'
            ], 422);
        }

        $job->status = $request->status;

        // Handle timestamps
        switch ($request->status) {
            case 'On The Way':
                $job->on_the_way_at = now();
                break;
            case 'Reached':
                $job->reached_at = now();
                break;
            case 'Job Started':
                $job->job_started_at = now();
                break;
            case 'Job Completed':
                $job->job_completed_at = now();
                break;
        }

        $job->save();
        JobJourney::create([
            'job_id' => $job->id,
            'status' => $job->status,
            'message' => $job->status,
            'user_id' => $request->user()->id
        ]);

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $job
        ]);
    }

    /**
     * Delete a job
     */
    public function destroy(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        $this->authorizeAssignedTechnician($request, $job);

        $job->delete();

        return response()->json(['message' => 'Job deleted']);
    }

    /**
     * Show a single job
     */
    public function show(Request $request, $id)
    {
        $job = Job::with([
            'serviceType',
            'technician',
            'journeys',
            'payments.replacementJob',
            'warrantyClaimSourcePayment.job'
        ])->findOrFail($id);

        $this->authorizeAssignedTechnician($request, $job);

        return response()->json([
            'data' => $job
        ]);
    }

    /**
     * Add payment to job
     */
    protected const TYRE_REPAIR_WARRANTY_MONTHS = 2;

    public function addPayment(Request $request, $id)
    {
        $job = Job::with('serviceType')->findOrFail($id);

        $this->authorizeAssignedTechnician($request, $job);

        // Determine if this is a battery-completion payment (has battery_details with selected_stock_id)
        $batteryDetails = null;
        if ($request->battery_details) {
            $batteryDetails = is_string($request->battery_details)
                ? json_decode($request->battery_details, true)
                : $request->battery_details;
        }
        $isBatteryPayment = !empty($batteryDetails['selected_stock_id']);

        // Tyre Repair jobs get an automatic fixed warranty (no battery details involved)
        $isTyreRepairJob = str_contains(strtolower($job->serviceType->name ?? ''), 'tyre repair');

        // A job with nothing due (e.g. a free warranty-replacement battery job)
        // doesn't require a real payment — just the battery/receipt record.
        $noPaymentDue = $job->due_amount <= 0;

        $rules = [
            'amount' => $noPaymentDue ? 'nullable|numeric|min:0' : 'required|numeric|min:1',
            'payment_method' => $noPaymentDue ? 'nullable|in:Cash,Bank Transfer,POS,POL' : 'required|in:Cash,Bank Transfer,POS,POL',
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];

        $request->validate($rules);

        if ($job->payment_status === 'Paid') {
            return response()->json([
                'message' => 'Job already fully paid'
            ], 422);
        }

        if (!$noPaymentDue && $request->amount > $job->due_amount) {
            return response()->json([
                'message' => 'Amount exceeds due amount'
            ], 422);
        }

        $receiptPath = $request->file('receipt')->store('receipts', 'public');

        // Compute warranty expiry (install/repair date = job completion time)
        $warrantyMonths = null;
        $warrantyExpiresAt = null;
        if ($isBatteryPayment && !empty($batteryDetails['warranty'])) {
            $warrantyMonths = (int) $batteryDetails['warranty'];
        } elseif ($isTyreRepairJob) {
            $warrantyMonths = self::TYRE_REPAIR_WARRANTY_MONTHS;
        }
        if (!empty($warrantyMonths) && $warrantyMonths > 0) {
            $installDate = $job->job_completed_at ?? now();
            $warrantyExpiresAt = $installDate->copy()->addMonths($warrantyMonths);
        }

        $payment = $job->payments()->create([
            'amount' => $request->amount ?? 0,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'receipt' => $receiptPath,
            'battery_details' => $batteryDetails,
            'warranty_months' => $warrantyMonths,
            'warranty_expires_at' => $warrantyExpiresAt,
            // If this job was created from a warranty claim, this new battery install can never be claimed again
            'claim_of_payment_id' => $job->warranty_claim_source_payment_id,
        ]);

        $totalPaid = $job->payments()->sum('amount');

        $job->paid_amount = $totalPaid;
        $job->due_amount  = $job->price - $totalPaid;

        if ($job->due_amount <= 0) {
            $job->payment_status = 'Paid';
            $job->status = 'Job Completed';
        } elseif ($totalPaid > 0) {
            $job->payment_status = 'Partial';
        } else {
            $job->payment_status = 'Unpaid';
        }

        $job->save();

        // Decrement technician battery stock ONLY when job is fully paid (completed)
        if ($isBatteryPayment && $job->due_amount <= 0) {
            $stockId = $batteryDetails['selected_stock_id'];
            $stock = TechnicianBatteryStock::find($stockId);
            if ($stock) {
                $stock->used_quantity      = ($stock->used_quantity ?? 0) + 1;
                $stock->available_quantity = max(0, ($stock->available_quantity ?? 0) - 1);
                $stock->save();

                TechnicianBatteryMovement::create([
                    'technician_id' => $stock->technician_id,
                    'product_id'    => $stock->product_id,
                    'movement_type' => 'job_used',
                    'quantity'      => 1,
                    'job_id'        => $job->id,
                    'notes'         => 'Used in job #' . $job->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Payment added successfully',
            'job' => $job,
            'payment' => $payment
        ]);
    }

    /**
     * Update ETA for job
     */
    public function updateEta(Request $request, $id)
    {
        $user = $request->user();
        $job  = Job::findOrFail($id);

        // Must be technician
        if (!$this->isTechnicianUser($user)) {
            return response()->json([
                'message' => 'Only technicians can set ETA'
            ], 403);
        }

        $this->authorizeAssignedTechnician($request, $job);

        // Must have employee profile
        if (!$user->employee) {
            return response()->json([
                'message' => 'Employee record not found'
            ], 403);
        }

        // Must be assigned to this job
        if ($job->technician_id !== $user->employee->id) {
            return response()->json([
                'message' => 'You are not assigned to this job'
            ], 403);
        }

        // Optional: allow ETA only before reaching
        if (in_array($job->status, ['Reached', 'Job Started', 'Job Completed'])) {
            return response()->json([
                'message' => 'Cannot update ETA after reaching customer'
            ], 422);
        }

        $request->validate([
            'eta_minutes' => 'required|integer|min:1|max:300'
        ]);

        $minutes = $request->eta_minutes;

        $job->eta_minutes = $minutes;
        $job->eta_started_at = now();

        /* Calculate arrival time */
        $job->eta_time = now()->addMinutes($minutes)->format('H:i:s');

        $job->save();
        JobJourney::create([
            'job_id' => $job->id,
            'status' => 'ETA Updated',
            'message' => 'ETA set to '.$minutes.' minutes',
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'ETA updated successfully',
            'data' => $job
        ]);
    }

    /**
     * Update payment details and recalculate job totals
     */
    public function updatePayment(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
        ]);

        $payment->amount = $request->amount;
        $payment->payment_method = $request->payment_method;
        $payment->reference_number = $request->reference_number;
        $payment->notes = $request->notes;

        if ($request->hasFile('receipt')) {
            if ($payment->receipt && Storage::disk('public')->exists($payment->receipt)) {
                Storage::disk('public')->delete($payment->receipt);
            }
            $payment->receipt = $request->file('receipt')->store('receipts', 'public');
        }

        $payment->save();

        // Recalculate job payment totals
        $job = $payment->job;
        if ($job) {
            $totalPaid = $job->payments()->sum('amount');
            $job->paid_amount = $totalPaid;
            $job->due_amount  = $job->price - $totalPaid;
            if ($job->due_amount <= 0) {
                $job->payment_status = 'Paid';
            } elseif ($totalPaid > 0) {
                $job->payment_status = 'Partial';
            } else {
                $job->payment_status = 'Unpaid';
            }
            $job->save();
        }

        return response()->json(['message' => 'Payment updated']);
    }

    /**
     * Delete payment
     */
    public function deletePayment($id)
    {
        $payment = Payment::findOrFail($id);
        $job = $payment->job;

        if ($job) {
            $this->authorizeAssignedTechnician(request(), $job);
        }

        // delete receipt file
        if ($payment->receipt && Storage::disk('public')->exists($payment->receipt)) {
            Storage::disk('public')->delete($payment->receipt);
        }

        $payment->delete();

        // Recalculate job payment totals after deletion
        if ($job) {
            $totalPaid = $job->payments()->sum('amount');
            $job->paid_amount = $totalPaid;
            $job->due_amount  = $job->price - $totalPaid;
            if ($job->due_amount <= 0) {
                $job->payment_status = 'Paid';
            } elseif ($totalPaid > 0) {
                $job->payment_status = 'Partial';
            } else {
                $job->payment_status = 'Unpaid';
            }
            $job->save();
        }

        return response()->json(['message' => 'Payment deleted']);
    }

    /**
     * Ensure technicians only access their assigned jobs.
     */
    protected function authorizeAssignedTechnician(Request $request, Job $job)
    {
        $user = $request->user();

        if ($this->isTechnicianUser($user)) {
            if (!$user->employee || $job->technician_id !== $user->employee->id) {
                abort(403, 'You are not authorized to access this job');
            }
        }
    }
    protected function isTechnicianUser($user)
    {
        return $user && (
            $user->account_role == 0 ||
            $user->roles()->where('slug', 'technician')->exists()
        );
    }}
