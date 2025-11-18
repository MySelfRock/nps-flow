<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get NPS metrics and trends
     */
    public function npsMetrics(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        // Create cache key based on tenant and filters
        $cacheKey = 'nps_metrics:' . $user->tenant_id . ':'
            . md5(json_encode($request->only(['campaign_id', 'start_date', 'end_date'])));

        // Cache for 5 minutes (300 seconds)
        return response()->json([
            'success' => true,
            'data' => Cache::remember($cacheKey, 300, function () use ($request, $user) {
                return $this->calculateNPSMetrics($request, $user);
            })
        ]);
    }

    /**
     * Calculate NPS metrics (extracted for caching)
     */
    private function calculateNPSMetrics(Request $request, $user): array
    {
        $query = Campaign::where('tenant_id', $user->tenant_id)
            ->where('type', 'NPS');

        // Filter by campaign_id if provided
        if ($request->has('campaign_id')) {
            $query->where('id', $request->campaign_id);
        }

        // Filter by date range if provided
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $campaigns = $query->withCount(['responses', 'recipients'])->get();

        // Calculate overall metrics using withCount (no N+1 queries)
        $totalRecipients = $campaigns->sum('recipients_count');
        $totalResponses = $campaigns->sum('responses_count');
        $totalResponseRate = $totalRecipients > 0
            ? ($totalResponses / $totalRecipients) * 100
            : 0;

        // Calculate overall NPS
        $allResponses = Response::whereIn('campaign_id', $campaigns->pluck('id'))
            ->whereNotNull('score')
            ->select('campaign_id', 'score')
            ->get();

        $promoters = $allResponses->where('score', '>=', 9)->count();
        $passives = $allResponses->whereBetween('score', [7, 8])->count();
        $detractors = $allResponses->where('score', '<=', 6)->count();
        $total = $allResponses->count();

        $overallNPS = $total > 0
            ? (($promoters - $detractors) / $total) * 100
            : null;

        // Get score distribution
        $scoreDistribution = $allResponses->groupBy('score')
            ->map(fn($group) => $group->count())
            ->sortKeys()
            ->toArray();

        // Group responses by campaign for efficient NPS calculation
        $responsesByCampaign = $allResponses->groupBy('campaign_id');

        // Get trends (by month for the last 6 months)
        $trendsData = Response::whereIn('campaign_id', $campaigns->pluck('id'))
            ->whereNotNull('score')
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_TRUNC(\'month\', created_at) as month'),
                DB::raw('COUNT(*) as total_responses'),
                DB::raw('SUM(CASE WHEN score >= 9 THEN 1 ELSE 0 END) as promoters'),
                DB::raw('SUM(CASE WHEN score <= 6 THEN 1 ELSE 0 END) as detractors')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                $nps = $item->total_responses > 0
                    ? (($item->promoters - $item->detractors) / $item->total_responses) * 100
                    : null;

                return [
                    'month' => $item->month,
                    'nps' => $nps,
                    'total_responses' => $item->total_responses,
                ];
            });

        // Get top detractor comments (most recent)
        $detractorComments = Response::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('score', '<=', 6)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->with('recipient:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'score' => $r->score,
                'comment' => $r->comment,
                'recipient_name' => $r->recipient->name ?? 'Unknown',
                'created_at' => $r->created_at,
            ]);

        // Campaign-level breakdown (fully optimized - no N+1 queries)
        $campaignBreakdown = $campaigns->map(function ($campaign) use ($responsesByCampaign) {
            // Get responses for this campaign from pre-loaded collection
            $campaignResponses = $responsesByCampaign->get($campaign->id, collect());

            $npsScore = null;
            if ($campaignResponses->count() > 0) {
                $campaignPromoters = $campaignResponses->where('score', '>=', 9)->count();
                $campaignDetractors = $campaignResponses->where('score', '<=', 6)->count();
                $npsScore = (($campaignPromoters - $campaignDetractors) / $campaignResponses->count()) * 100;
            }

            $responseRate = $campaign->recipients_count > 0
                ? ($campaign->responses_count / $campaign->recipients_count) * 100
                : 0;

            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'nps_score' => $npsScore !== null ? round($npsScore, 2) : null,
                'response_rate' => round($responseRate, 2),
                'total_recipients' => $campaign->recipients_count,
                'total_responses' => $campaign->responses_count,
                'status' => $campaign->status,
                'created_at' => $campaign->created_at,
            ];
        });

        return [
            'overall' => [
                'nps_score' => $overallNPS !== null ? round($overallNPS, 2) : null,
                'total_recipients' => $totalRecipients,
                'total_responses' => $totalResponses,
                'response_rate' => round($totalResponseRate, 2),
                'promoters' => $promoters,
                'passives' => $passives,
                'detractors' => $detractors,
                'promoters_percentage' => $total > 0 ? round(($promoters / $total) * 100, 2) : 0,
                'passives_percentage' => $total > 0 ? round(($passives / $total) * 100, 2) : 0,
                'detractors_percentage' => $total > 0 ? round(($detractors / $total) * 100, 2) : 0,
            ],
            'score_distribution' => $scoreDistribution,
            'trends' => $trendsData,
            'detractor_comments' => $detractorComments,
            'campaigns' => $campaignBreakdown,
            'cached_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get filtered responses with detailed information
     */
    public function responses(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $query = Response::where('responses.tenant_id', $user->tenant_id)
            ->join('campaigns', 'responses.campaign_id', '=', 'campaigns.id')
            ->join('recipients', 'responses.recipient_id', '=', 'recipients.id')
            ->select('responses.*');

        // Filter by campaign
        if ($request->has('campaign_id')) {
            $query->where('responses.campaign_id', $request->campaign_id);
        }

        // Filter by campaign type
        if ($request->has('campaign_type')) {
            $query->where('campaigns.type', $request->campaign_type);
        }

        // Filter by score range
        if ($request->has('min_score')) {
            $query->where('responses.score', '>=', $request->min_score);
        }
        if ($request->has('max_score')) {
            $query->where('responses.score', '<=', $request->max_score);
        }

        // Filter by category (promoter, passive, detractor)
        if ($request->has('category')) {
            switch ($request->category) {
                case 'promoter':
                    $query->where('responses.score', '>=', 9);
                    break;
                case 'passive':
                    $query->whereBetween('responses.score', [7, 8]);
                    break;
                case 'detractor':
                    $query->where('responses.score', '<=', 6);
                    break;
            }
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('responses.created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('responses.created_at', '<=', $request->end_date);
        }

        // Filter by recipient tags
        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : [$request->tags];
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('recipients.tags', $tag);
                }
            });
        }

        // Search in comments
        if ($request->has('search')) {
            $query->where('responses.comment', 'ILIKE', '%' . $request->search . '%');
        }

        // Only responses with comments
        if ($request->has('has_comment') && $request->has_comment) {
            $query->whereNotNull('responses.comment')
                ->where('responses.comment', '!=', '');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortFields = ['created_at', 'score'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy('responses.' . $sortBy, $sortOrder);
        }

        // Paginate results with validation (max 100 per page)
        $perPage = min((int)$request->get('per_page', 50), 100);
        $responses = $query->with(['campaign:id,name,type', 'recipient:id,name,email,tags'])
            ->paginate($perPage);

        // Transform data
        $responses->getCollection()->transform(function ($response) {
            return [
                'id' => $response->id,
                'score' => $response->score,
                'comment' => $response->comment,
                'category' => $response->getCategory(),
                'answers' => $response->answers,
                'metadata' => $response->metadata,
                'created_at' => $response->created_at,
                'campaign' => [
                    'id' => $response->campaign->id,
                    'name' => $response->campaign->name,
                    'type' => $response->campaign->type,
                ],
                'recipient' => [
                    'id' => $response->recipient->id,
                    'name' => $response->recipient->name,
                    'email' => $response->recipient->email,
                    'tags' => $response->recipient->tags,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $responses
        ]);
    }

    /**
     * Export data to CSV or PDF
     */
    public function export(Request $request): mixed
    {
        $user = auth('api')->user();

        $format = $request->get('format', 'csv'); // csv or json
        $type = $request->get('type', 'responses'); // responses or summary

        if ($type === 'responses') {
            return $this->exportResponses($request, $user, $format);
        } elseif ($type === 'summary') {
            return $this->exportSummary($request, $user, $format);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid export type'
        ], 400);
    }

    /**
     * Export responses to CSV
     */
    private function exportResponses(Request $request, $user, string $format)
    {
        $query = Response::where('responses.tenant_id', $user->tenant_id)
            ->join('campaigns', 'responses.campaign_id', '=', 'campaigns.id')
            ->join('recipients', 'responses.recipient_id', '=', 'recipients.id')
            ->select('responses.*');

        // Apply same filters as responses() method
        if ($request->has('campaign_id')) {
            $query->where('responses.campaign_id', $request->campaign_id);
        }
        if ($request->has('start_date')) {
            $query->where('responses.created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('responses.created_at', '<=', $request->end_date);
        }
        if ($request->has('category')) {
            switch ($request->category) {
                case 'promoter':
                    $query->where('responses.score', '>=', 9);
                    break;
                case 'passive':
                    $query->whereBetween('responses.score', [7, 8]);
                    break;
                case 'detractor':
                    $query->where('responses.score', '<=', 6);
                    break;
            }
        }

        $responses = $query->with(['campaign:id,name,type', 'recipient:id,name,email'])
            ->orderBy('responses.created_at', 'desc')
            ->get();

        if ($format === 'json') {
            $data = $responses->map(function ($response) {
                return [
                    'campaign_name' => $response->campaign->name,
                    'campaign_type' => $response->campaign->type,
                    'recipient_name' => $response->recipient->name,
                    'recipient_email' => $response->recipient->email,
                    'score' => $response->score,
                    'category' => $response->getCategory(),
                    'comment' => $response->comment,
                    'submitted_at' => $response->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        // CSV format
        $filename = 'responses_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($responses) {
            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv($file, [
                'Campaign',
                'Type',
                'Recipient Name',
                'Recipient Email',
                'Score',
                'Category',
                'Comment',
                'Submitted At'
            ]);

            // CSV rows
            foreach ($responses as $response) {
                fputcsv($file, [
                    $response->campaign->name,
                    $response->campaign->type,
                    $response->recipient->name,
                    $response->recipient->email,
                    $response->score,
                    $response->getCategory(),
                    $response->comment,
                    $response->created_at->toIso8601String(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export NPS summary to CSV/JSON
     */
    private function exportSummary(Request $request, $user, string $format)
    {
        $campaigns = Campaign::where('tenant_id', $user->tenant_id)
            ->where('type', 'NPS')
            ->with(['responses', 'recipients'])
            ->get();

        if ($format === 'json') {
            $data = $campaigns->map(function ($campaign) {
                return [
                    'campaign_name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_recipients' => $campaign->recipients()->count(),
                    'total_responses' => $campaign->responses()->count(),
                    'response_rate' => round($campaign->getResponseRate(), 2),
                    'nps_score' => $campaign->getNPSScore() !== null ? round($campaign->getNPSScore(), 2) : null,
                    'created_at' => $campaign->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        // CSV format
        $filename = 'nps_summary_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($campaigns) {
            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv($file, [
                'Campaign Name',
                'Status',
                'Total Recipients',
                'Total Responses',
                'Response Rate (%)',
                'NPS Score',
                'Created At'
            ]);

            // CSV rows
            foreach ($campaigns as $campaign) {
                fputcsv($file, [
                    $campaign->name,
                    $campaign->status,
                    $campaign->recipients()->count(),
                    $campaign->responses()->count(),
                    round($campaign->getResponseRate(), 2),
                    $campaign->getNPSScore() !== null ? round($campaign->getNPSScore(), 2) : 'N/A',
                    $campaign->created_at->toIso8601String(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
