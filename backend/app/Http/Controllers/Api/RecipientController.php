<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Recipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class RecipientController extends Controller
{
    /**
     * Display a listing of recipients for a campaign.
     */
    public function index(Request $request, string $campaignId): JsonResponse
    {
        $user = auth('api')->user();

        $campaign = Campaign::where('tenant_id', $user->tenant_id)->find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $recipients = Recipient::where('tenant_id', $user->tenant_id)
            ->where('campaign_id', $campaignId)
            ->with('response')
            ->withCount('sends')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $recipients
        ]);
    }

    /**
     * Store a newly created recipient.
     */
    public function store(Request $request, string $campaignId): JsonResponse
    {
        $user = auth('api')->user();

        $campaign = Campaign::where('tenant_id', $user->tenant_id)->find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required_without:phone|email|nullable',
            'phone' => 'required_without:email|string|nullable',
            'external_id' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recipient = Recipient::create([
                'tenant_id' => $user->tenant_id,
                'campaign_id' => $campaign->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'external_id' => $request->external_id,
                'tags' => $request->tags ?? [],
                'status' => 'pending',
            ]);

            AuditLog::logAction('recipient_added', $user, [
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recipient added successfully',
                'data' => $recipient
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add recipient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified recipient.
     */
    public function show(string $campaignId, string $id): JsonResponse
    {
        $user = auth('api')->user();

        $recipient = Recipient::where('tenant_id', $user->tenant_id)
            ->where('campaign_id', $campaignId)
            ->with(['response', 'sends'])
            ->find($id);

        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $recipient
        ]);
    }

    /**
     * Update the specified recipient.
     */
    public function update(Request $request, string $campaignId, string $id): JsonResponse
    {
        $user = auth('api')->user();

        $recipient = Recipient::where('tenant_id', $user->tenant_id)
            ->where('campaign_id', $campaignId)
            ->find($id);

        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|nullable',
            'phone' => 'sometimes|string|nullable',
            'external_id' => 'sometimes|string|max:255',
            'tags' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recipient->update($request->only([
                'name',
                'email',
                'phone',
                'external_id',
                'tags',
            ]));

            AuditLog::logAction('recipient_updated', $user, [
                'campaign_id' => $campaignId,
                'recipient_id' => $recipient->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recipient updated successfully',
                'data' => $recipient
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update recipient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified recipient.
     */
    public function destroy(string $campaignId, string $id): JsonResponse
    {
        $user = auth('api')->user();

        $recipient = Recipient::where('tenant_id', $user->tenant_id)
            ->where('campaign_id', $campaignId)
            ->find($id);

        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient not found'
            ], 404);
        }

        try {
            $recipient->delete();

            AuditLog::logAction('recipient_deleted', $user, [
                'campaign_id' => $campaignId,
                'recipient_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recipient deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete recipient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload recipients from CSV file.
     */
    public function uploadCsv(Request $request, string $campaignId): JsonResponse
    {
        $user = auth('api')->user();

        $campaign = Campaign::where('tenant_id', $user->tenant_id)->find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $imported = 0;
            $errors = [];

            foreach ($records as $index => $record) {
                // Validate each row
                $rowValidator = Validator::make($record, [
                    'name' => 'required|string|max:255',
                    'email' => 'required_without:phone|email|nullable',
                    'phone' => 'required_without:email|string|nullable',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = [
                        'row' => $index + 2, // +2 because header is row 1 and arrays are 0-indexed
                        'errors' => $rowValidator->errors()->all()
                    ];
                    continue;
                }

                // Create recipient
                Recipient::create([
                    'tenant_id' => $user->tenant_id,
                    'campaign_id' => $campaign->id,
                    'name' => $record['name'],
                    'email' => $record['email'] ?? null,
                    'phone' => $record['phone'] ?? null,
                    'external_id' => $record['external_id'] ?? null,
                    'tags' => isset($record['tags']) ? json_decode($record['tags'], true) : [],
                    'status' => 'pending',
                ]);

                $imported++;
            }

            AuditLog::logAction('recipients_uploaded', $user, [
                'campaign_id' => $campaign->id,
                'total_imported' => $imported,
                'errors_count' => count($errors),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$imported} recipients",
                'data' => [
                    'imported' => $imported,
                    'errors' => $errors,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload CSV',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get CSV template for download
     */
    public function template(): JsonResponse
    {
        $template = [
            'headers' => ['name', 'email', 'phone', 'external_id', 'tags'],
            'example' => [
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'phone' => '+5511999999999',
                'external_id' => 'CUST001',
                'tags' => '["vip","premium"]'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $template
        ]);
    }
}
