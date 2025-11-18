<?php

namespace App\Http\Controllers;

use App\Models\Recipient;
use App\Models\Response as SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

class ResponseController extends Controller
{
    /**
     * Display survey information for respondent
     */
    public function show(string $token): JsonResponse
    {
        $recipient = Recipient::where('token', $token)
            ->with(['campaign:id,name,type,message_template', 'response'])
            ->first();

        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired link'
            ], 404);
        }

        // Check if already responded
        if ($recipient->hasResponded()) {
            return response()->json([
                'success' => false,
                'message' => 'You have already responded to this survey',
                'data' => [
                    'already_responded' => true,
                    'response' => $recipient->response
                ]
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'recipient' => [
                    'name' => $recipient->name,
                ],
                'campaign' => [
                    'name' => $recipient->campaign->name,
                    'type' => $recipient->campaign->type,
                    'message' => $recipient->campaign->message_template,
                ],
                'already_responded' => false
            ]
        ]);
    }

    /**
     * Store survey response
     */
    public function store(Request $request, string $token): JsonResponse
    {
        $recipient = Recipient::where('token', $token)
            ->with('campaign')
            ->first();

        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired link'
            ], 404);
        }

        // Check if already responded
        if ($recipient->hasResponded()) {
            return response()->json([
                'success' => false,
                'message' => 'You have already responded to this survey'
            ], 400);
        }

        // Validate based on campaign type
        $rules = [];

        if ($recipient->campaign->type === 'NPS') {
            $rules = [
                'score' => 'required|integer|min:0|max:10',
                'comment' => 'nullable|string|max:1000',
            ];
        } elseif (in_array($recipient->campaign->type, ['CSAT', 'CES'])) {
            $rules = [
                'score' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ];
        } else {
            $rules = [
                'answers' => 'required|array',
                'comment' => 'nullable|string|max:1000',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Sanitize comment to prevent XSS
            $sanitizedComment = $request->comment
                ? Purifier::clean($request->comment, 'comment')
                : null;

            // Create response
            $response = SurveyResponse::create([
                'tenant_id' => $recipient->tenant_id,
                'campaign_id' => $recipient->campaign_id,
                'recipient_id' => $recipient->id,
                'score' => $request->score ?? null,
                'answers' => $request->answers ?? null,
                'comment' => $sanitizedComment,
                'metadata' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'submitted_at' => now()->toIso8601String(),
                ],
            ]);

            // Mark recipient as responded
            $recipient->markAsResponded();

            // Check for alerts (low score)
            $this->checkAndTriggerAlerts($recipient->campaign, $response);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your response!',
                'data' => [
                    'response_id' => $response->id,
                    'category' => $response->getCategory(),
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Failed to submit survey response', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipient->id,
                'campaign_id' => $recipient->campaign_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit response. Please try again.',
            ], 500);
        }
    }

    /**
     * Check and trigger alerts for low scores
     */
    private function checkAndTriggerAlerts($campaign, $response): void
    {
        $alerts = $campaign->alerts()->where('enabled', true)->get();

        foreach ($alerts as $alert) {
            if ($alert->shouldTrigger($response)) {
                // Dispatch job to send alert notification
                \App\Jobs\SendAlertJob::dispatch($alert, $response);

                \Log::info('Alert triggered', [
                    'alert_id' => $alert->id,
                    'response_id' => $response->id,
                    'score' => $response->score,
                ]);
            }
        }
    }
}
