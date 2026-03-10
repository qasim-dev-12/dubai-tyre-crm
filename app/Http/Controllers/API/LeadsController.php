<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Job;
use App\Models\Client;

class LeadsController extends Controller
{
    /**
     * Display a listing of leads
     */
public function index(Request $request)
{
    $perPage = $request->get('perPage', 10);
    $status  = $request->get('status');
    $term    = $request->get('term');

    $query = Lead::with('serviceType')->latest();

    if ($status !== null && $status !== 'all') {
        $query->where('status', $status);
    }

    if ($term) {
        $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('mobile', 'like', "%{$term}%")
              ->orWhere('area', 'like', "%{$term}%")
              ->orWhere('vehicle_number', 'like', "%{$term}%")
              ->orWhere('status', 'like', "%{$term}%")
              ->orWhereHas('serviceType', function ($s) use ($term) {
                  $s->where('name', 'like', "%{$term}%");
              });
        });
    }

    // ✅ IMPORTANT: return paginator directly
    return $query->paginate($perPage);
}


    /**
     * Search leads
     */
    // public function search(Request $request)
    // {
    //     $term     = $request->get('term');
    //     $status   = $request->get('filterType');
    //     $perPage  = $request->get('perPage', 10);

    //     $query = Lead::with('serviceType');

    //     if ($term) {
    //         $query->where(function ($q) use ($term) {
    //             $q->where('name', 'like', "%$term%")
    //               ->orWhere('mobile', 'like', "%$term%");
    //         });
    //     }

    //     if ($status !== null && $status !== 'all') {
    //         $query->where('status', $status);
    //     }

    //     return response()->json(
    //         $query->latest()->paginate($perPage)
    //     );
    // }

    /**
     * Store a new lead
     */
   public function store(Request $request)
{
    $validated = $request->validate([
        'salutation'       => 'nullable|string|max:10',
        'name'             => 'required|string|max:255',
        'service_type_id'  => 'required|exists:service_types,id',
        'area'             => 'nullable|string|max:255',
        'price'          => 'nullable|numeric',
        'mobile'           => 'required|string|max:20',
      'status' => 'required|in:New,In Progress,Cancelled',
        'vehicle_number'  => 'required|string|max:255', // ✅
    ]);

    $validated['slug'] = Str::uuid(); // ✅ UNIQUE SLUG

    $lead = Lead::create($validated);

    return response()->json([
        'message' => 'Lead created successfully',
        'data'    => $lead
    ], 201);
}

    /**
     * Show a single lead
     */
   public function show($slug)
{
    $lead = Lead::with('serviceType')
        ->where('slug', $slug)
        ->firstOrFail();

    return response()->json([
        'data' => $lead
    ]);
}
public function update(Request $request, $slug)
{
    $lead = Lead::where('slug', $slug)->firstOrFail();

    $validated = $request->validate([
        'salutation'       => 'nullable|string|max:10',
        'name'             => 'required|string|max:255',
        'service_type_id'  => 'required|exists:service_types,id',
        'area'             => 'nullable|string|max:255',
        'price'          => 'nullable|numeric',
        'mobile'           => 'required|string|max:20',
        'status'           => 'required|in:New,In Progress,Converted,Cancelled',
        'vehicle_number'   => 'required|string|max:255',
    ]);

    $lead->update($validated);

    return response()->json([
        'message' => 'Lead updated successfully',
        'data'    => $lead
    ]);
}


    /**
     * Update a lead
     */


    /**
     * Delete a lead
     */
    public function destroy($slug)
    {
        $lead = Lead::where('slug', $slug)->firstOrFail();
        $lead->delete();

        return response()->json(true);
    }

    /**
     * Convert lead
     */
public function convert(Request $request, $slug)
{
    $lead = Lead::where('slug', $slug)->firstOrFail();

    $validated = $request->validate([
        'salutation'       => 'nullable|string|max:10',
        'name'             => 'required|string|max:255',
        'service_type_id'  => 'required|exists:service_types,id',
        'area'             => 'nullable|string|max:255',
        'price'            => 'nullable|numeric',
        'mobile'           => 'required|string|max:20',
        'vehicle_number'   => 'required|string|max:255',
        'technician_id'    => 'required|exists:employees,id',
        'location_url'     => 'nullable|string',


    ]);

    DB::beginTransaction();

    try {

        // ✅ 1. Update lead with edited values
        $lead->update([
            'salutation'      => $validated['salutation'],
            'name'            => $validated['name'],
            'service_type_id' => $validated['service_type_id'],
            'area'            => $validated['area'],
            'price'           => $validated['price'],
            'mobile'          => $validated['mobile'],
            'vehicle_number'  => $validated['vehicle_number'],
            'status'          => 'Converted',
        ]);

        // ✅ Create or get client using mobile as unique field
$client = Client::firstOrCreate(
    ['phone' => $validated['mobile']], // UNIQUE FIELD
    [
        'name'      => $validated['name'],
        'slug'      => Str::uuid(),
        'client_id' => 'CL-' . rand(1000,9999),
        'status'    => 1
    ]
);

$isNewClient = $client->wasRecentlyCreated;

        // ✅ 2. Create job using edited values
        $job = Job::create([

            'salutation'      => $validated['salutation'],
            'name'            => $validated['name'],
            'mobile'          => $validated['mobile'],
            'service_type_id' => $validated['service_type_id'],
            'area'            => $validated['area'],
            'price'           => $validated['price'],
            'vehicle_number'  => $validated['vehicle_number'],
            'technician_id'   => $validated['technician_id'],
            'location_url'    => $validated['location_url'] ?? null,
        //   'status' => 'DCC', // always start from first workflow step
'paid_amount' => 0,
'due_amount' => $validated['price'],
'payment_status' => 'Unpaid',
            'client_id' => $client->id,

        ]);

        DB::commit();

      return response()->json([
    'message'       => 'Lead converted successfully',
    'client_status' => $isNewClient ? 'created' : 'existing',
    'client_message'=> $isNewClient
                        ? 'Client created successfully'
                        : 'Client already exists',
    'client'        => $isNewClient ? null : $client, // only send data if existing
    'job'           => $job
]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Conversion failed',
            'error'   => $e->getMessage()
        ], 500);
    }
}


}
