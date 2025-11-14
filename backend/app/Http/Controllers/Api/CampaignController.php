<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendCampaignJob;
use App\Models\AuditLog;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns for the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $campaigns = Campaign::where('tenant_id', $user->tenant_id)
            ->with('creator:id,name')
            ->withCount(['recipients', 'responses'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:NPS,CSAT,CES,CUSTOM',
            'message_template' => 'nullable|array',
            'sender_email' => 'nullable|email',
            'sender_name' => 'nullable|string|max:255',
            'scheduled_at' => 'nullable|date|after:now',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $campaign = Campaign::create([
                'tenant_id' => $user->tenant_id,
                'name' => $request->name,
                'type' => $request->type,
                'message_template' => $request->message_template ?? $this->getDefaultTemplate($request->type),
                'sender_email' => $request->sender_email ?? config('mail.from.address'),
                'sender_name' => $request->sender_name ?? config('mail.from.name'),
                'scheduled_at' => $request->scheduled_at,
                'status' => 'draft',
                'settings' => $request->settings ?? [],
                'created_by' => $user->id,
            ]);

            AuditLog::logAction('campaign_created', $user, [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'type' => $campaign->type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $campaign
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified campaign.
     */
    public function show(string $id): JsonResponse
    {
        $user = auth('api')->user();

        $campaign = Campaign::where('tenant_id', $user->tenant_id)
            ->with(['creator:id,name,email', 'recipients', 'responses'])
            ->withCount(['recipients', 'responses'])
            ->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        // Add calculated metrics
        $data = $campaign->toArray();
        $data['nps_score'] = $campaign->getNPSScore();
        $data['response_rate'] = $campaign->getResponseRate();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = auth('api')->user();

        $campaign = Campaign::where('tenant_id', $user->tenant_id)->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        // Don't allow editing sent campaigns
        if ($campaign->isSent()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit a sent campaign'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:NPS,CSAT,CES,CUSTOM',
            'message_template' => 'sometimes|array',
            'sender_email' => 'sometimes|email',
            'sender_name' => 'sometimes|string|max:255',
            'scheduled_at' => 'sometimes|date|after:now',
            'settings' => 'sometimes|array',
            'status' => 'sometimes|in:draft,scheduled,paused',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $campaign->update($request->only([
                'name',
                'type',
                'message_template',
                'sender_email',
                'sender_name',
                'scheduled_at',
                'settings',
                'status',
            ]));

            AuditLog::logAction('campaign_updated', $user, [
                'campaign_id' => $campaign->id,
                'changes' => $request->only([
                    'name', 'type', 'status', 'scheduled_at'
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => $campaign
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = auth('api')->user();

        $campaign = Campaign::where('tenant_id', $user->tenant_id)->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        // Don't allow deleting sent campaigns
        if ($campaign->isSent()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a sent campaign'
            ], 403);
        }

        try {
            $campaignName = $campaign->name;

            $campaign->delete();

            AuditLog::logAction('campaign_deleted', $user, [
                'campaign_id' => $id,
                'campaign_name' => $campaignName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start campaign (begin sending)
     */
    public function start(string $id): JsonResponse
    {
        $user = auth('api')->user();

        $campaign = Campaign::where('tenant_id', $user->tenant_id)->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        if (!$campaign->canBeSent()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be sent in its current status'
            ], 403);
        }

        // Check if has recipients
        if ($campaign->recipients()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign has no recipients'
            ], 422);
        }

        try {
            // Dispatch job to start sending emails
            SendCampaignJob::dispatch($campaign);

            AuditLog::logAction('campaign_started', $user, [
                'campaign_id' => $campaign->id,
                'recipients_count' => $campaign->recipients()->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign started successfully',
                'data' => $campaign
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop/pause campaign
     */
    public function stop(string $id): JsonResponse
    {
        $user = auth('api')->user();

        $campaign = Campaign::where('tenant_id', $user->tenant_id)->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        try {
            $campaign->update(['status' => 'paused']);

            AuditLog::logAction('campaign_paused', $user, [
                'campaign_id' => $campaign->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign paused successfully',
                'data' => $campaign
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get default template for campaign type
     */
    private function getDefaultTemplate(string $type): array
    {
        $templates = [
            'NPS' => [
                'subject' => 'Avalie sua experiência conosco',
                'body' => 'Olá {{name}},\n\nEm uma escala de 0 a 10, quanto você recomendaria nossa empresa a um amigo?\n\nClique no link para responder: {{link}}',
            ],
            'CSAT' => [
                'subject' => 'Como foi sua experiência?',
                'body' => 'Olá {{name}},\n\nQual o seu nível de satisfação com nosso serviço?\n\nClique no link para responder: {{link}}',
            ],
            'CES' => [
                'subject' => 'Quão fácil foi?',
                'body' => 'Olá {{name}},\n\nQuão fácil foi resolver seu problema conosco?\n\nClique no link para responder: {{link}}',
            ],
            'CUSTOM' => [
                'subject' => 'Sua opinião é importante',
                'body' => 'Olá {{name}},\n\nGostaríamos de ouvir sua opinião.\n\nClique no link para responder: {{link}}',
            ],
        ];

        return $templates[$type] ?? $templates['CUSTOM'];
    }
}
