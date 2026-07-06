<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobJourney;
use App\Models\Payment;
use App\Models\ServiceType;
use Illuminate\Http\Request;

class WarrantyClaimController extends Controller
{
    /**
     * List battery installs that carry a warranty, with computed status.
     */
    public function index(Request $request)
    {
        $term = $request->get('term');
        $perPage = $request->get('perPage', 15);

        $query = Payment::with(['job.serviceType', 'job.technician', 'job.client', 'replacementJob'])
            ->whereNotNull('warranty_expires_at');

        if ($term) {
            $query->whereHas('job', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%")
                    ->orWhere('vehicle_number', 'like', "%{$term}%");
            });
        }

        $payments = $query->latest()->paginate($perPage);

        $payments->getCollection()->transform(function (Payment $payment) {
            $payment->warranty_status = $this->resolveStatus($payment);
            return $payment;
        });

        return response()->json($payments);
    }

    /**
     * Claim the warranty on a battery install: flags the original payment
     * and creates a brand-new job for the technician to install a replacement.
     */
    public function claim(Request $request, $paymentId)
    {
        $request->validate([
            'technician_id' => 'nullable|exists:employees,id',
        ]);

        $payment = Payment::with('job')->findOrFail($paymentId);
        $originalJob = $payment->job;

        if (!$originalJob) {
            return response()->json(['message' => 'Original job not found for this payment'], 422);
        }

        if ($payment->is_warranty_claimed) {
            return response()->json(['message' => 'Warranty already claimed for this battery'], 422);
        }

        if ($payment->claim_of_payment_id) {
            return response()->json(['message' => 'This battery was itself a warranty replacement and is not eligible for another claim'], 422);
        }

        if (!$payment->warranty_expires_at || $payment->warranty_expires_at->isPast()) {
            return response()->json(['message' => 'Warranty has expired'], 422);
        }

        $serviceType = ServiceType::where('name', 'Battery Warranty Claim')->first();

        $newJob = Job::create([
            'name' => $originalJob->name,
            'mobile' => $originalJob->mobile,
            'service_type_id' => $serviceType->id ?? $originalJob->service_type_id,
            'vehicle_number' => $originalJob->vehicle_number,
            'area' => $originalJob->area,
            'price' => 0,
            'technician_id' => $request->technician_id ?? $originalJob->technician_id,
            'status' => 'Assigned',
            'paid_amount' => 0,
            'due_amount' => 0,
            'payment_status' => 'Unpaid',
            'client_id' => $originalJob->client_id,
            'warranty_claim_source_payment_id' => $payment->id,
        ]);

        JobJourney::create([
            'job_id' => $newJob->id,
            'status' => 'Job Created',
            'message' => 'Created from warranty claim on job #' . $originalJob->id,
            'user_id' => $request->user()->id,
        ]);

        $payment->is_warranty_claimed = true;
        $payment->warranty_claimed_at = now();
        $payment->replacement_job_id = $newJob->id;
        $payment->save();

        return response()->json([
            'message' => 'Warranty claimed, replacement job created',
            'job' => $newJob->load('serviceType', 'technician'),
            'payment' => $payment,
        ]);
    }

    private function resolveStatus(Payment $payment): string
    {
        if ($payment->is_warranty_claimed) {
            return 'Claimed';
        }
        if (!$payment->warranty_expires_at || $payment->warranty_expires_at->isPast()) {
            return 'Expired';
        }
        return 'Active';
    }
}
