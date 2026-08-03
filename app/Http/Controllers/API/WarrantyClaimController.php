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
     * Claim the warranty on a battery install: flags the original payment
     * and creates a brand-new job for the technician to install a replacement.
     */
    public function claim(Request $request, $paymentId)
    {
        $request->validate([
            'technician_id' => 'nullable|exists:employees,id',
            'price' => 'required|numeric|min:0.01',
        ]);

        $payment = Payment::with('job.serviceType')->findOrFail($paymentId);
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

        $originalServiceTypeName = strtolower($originalJob->serviceType->name ?? '');
        if (str_contains($originalServiceTypeName, 'battery')) {
            $claimServiceTypeName = 'Battery Warranty Claim';
        } elseif (str_contains($originalServiceTypeName, 'tyre repair')) {
            $claimServiceTypeName = 'Tyre Repair Warranty Claim';
        } else {
            $claimServiceTypeName = null;
        }
        $serviceType = $claimServiceTypeName ? ServiceType::where('name', $claimServiceTypeName)->first() : null;

        $newJob = Job::create([
            'name' => $originalJob->name,
            'mobile' => $originalJob->mobile,
            'service_type_id' => $serviceType->id ?? $originalJob->service_type_id,
            'vehicle_number' => $originalJob->vehicle_number,
            'area' => $originalJob->area,
            'price' => $request->price,
            'technician_id' => $request->technician_id ?? $originalJob->technician_id,
            'status' => 'Assigned',
            'paid_amount' => 0,
            'due_amount' => $request->price,
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
}
