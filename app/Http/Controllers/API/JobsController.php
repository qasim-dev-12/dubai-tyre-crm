<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use App\Models\JobJourney;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;



class JobsController extends Controller
{
    /**
     * Display list of jobs
     */
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $term    = $request->get('term');
         $user = $request->user(); // ✅ correct for auth:api

      $query = Job::with(['serviceType', 'technician', 'payments'])->latest();
          // 🔥 If user has Technician role
     // 🔥 Restrict technician
    if ($user->roles()->where('slug', 'technician')->exists()) {

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

        return $query->paginate($perPage);
    }
public function updateStatus(Request $request, $id)
{
    $job = Job::findOrFail($id);

    $request->validate([
        'status' => 'required|string'
    ]);

    $flow = Job::STATUS_FLOW;

    $currentIndex = array_search($job->status, $flow);
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
public function destroy($id)
{
    $job = Job::findOrFail($id);
    $job->delete();

    return response()->json(['message' => 'Job deleted']);
}
public function show($id)
{
   $job = Job::with([
        'serviceType',
        'technician',
        'journeys',
        'payments'

    ])->findOrFail($id);

    return response()->json([
        'data' => $job
    ]);
}
public function addPayment(Request $request, $id)
{
    $job = Job::findOrFail($id);

  $request->validate([
    'amount' => 'required|numeric|min:1',
    'payment_method' => 'required|in:Cash,Bank Transfer,POS,POL',
    'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' // ✅ ADD
]);

    if ($job->payment_status === 'Paid') {
        return response()->json([
            'message' => 'Job already fully paid'
        ], 422);
    }

    if ($request->amount > $job->due_amount) {
        return response()->json([
            'message' => 'Amount exceeds due amount'
        ], 422);
    }
$receiptPath = null;

if ($request->hasFile('receipt')) {
    $receiptPath = $request->file('receipt')->store('receipts', 'public');
}
   $payment = $job->payments()->create([
    'amount' => $request->amount,
    'payment_method' => $request->payment_method,
    'reference_number' => $request->reference_number,
    'notes' => $request->notes,
    'receipt' => $receiptPath // ✅ ADD THIS
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

    return response()->json([
        'message' => 'Payment added successfully',
        'job' => $job,
        'payment' => $payment
    ]);
}


public function updateEta(Request $request, $id)
{
    $user = $request->user();
    $job  = Job::findOrFail($id);

    // Must be technician
    if (!$user->roles()->where('slug', 'technician')->exists()) {
        return response()->json([
            'message' => 'Only technicians can set ETA'
        ], 403);
    }

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

public function updatePayment(Request $request, $id)
{
    $payment = Payment::findOrFail($id);

    $request->validate([
        'amount' => 'required|numeric|min:1',
        'payment_method' => 'required',
        'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
    ]);

    $payment->amount = $request->amount;
    $payment->payment_method = $request->payment_method;
    $payment->reference_number = $request->reference_number;
    $payment->notes = $request->notes;

    // Handle receipt update
    if ($request->hasFile('receipt')) {

        // delete old file
        if ($payment->receipt && Storage::disk('public')->exists($payment->receipt)) {
            Storage::disk('public')->delete($payment->receipt);
        }

        $payment->receipt = $request->file('receipt')->store('receipts', 'public');
    }

    $payment->save();

    return response()->json(['message' => 'Payment updated']);
}
public function deletePayment($id)
{
    $payment = Payment::findOrFail($id);

    // delete receipt file
    if ($payment->receipt && Storage::disk('public')->exists($payment->receipt)) {
        Storage::disk('public')->delete($payment->receipt);
    }

    $payment->delete();

    return response()->json(['message' => 'Payment deleted']);
}


}
