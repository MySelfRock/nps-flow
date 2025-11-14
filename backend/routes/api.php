<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\RecipientController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\ResponseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public routes with rate limiting
    Route::middleware(['throttle:5,1'])->prefix('auth')->group(function () {
        Route::post('/signup', [AuthController::class, 'signup']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes (require JWT authentication)
    Route::middleware(['auth:api'])->group(function () {

        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        // Tenant-isolated routes
        Route::middleware(['tenant.isolation'])->group(function () {

            // Tenants
            Route::get('/tenants/me', function () {
                $user = auth('api')->user();
                return response()->json([
                    'success' => true,
                    'data' => $user->tenant
                ]);
            });

            // Users management (coming soon)
            // Route::apiResource('users', UserController::class);

            // Campaigns
            Route::apiResource('campaigns', CampaignController::class);
            Route::post('/campaigns/{id}/start', [CampaignController::class, 'start']);
            Route::post('/campaigns/{id}/stop', [CampaignController::class, 'stop']);

            // Recipients
            Route::prefix('campaigns/{campaign}')->group(function () {
                Route::get('/recipients/template', [RecipientController::class, 'template']);
                Route::post('/recipients/upload', [RecipientController::class, 'uploadCsv']);
                Route::apiResource('recipients', RecipientController::class);
            });

            // Reports & Dashboard
            Route::prefix('reports')->group(function () {
                Route::get('/nps', [ReportController::class, 'npsMetrics']);
                Route::get('/responses', [ReportController::class, 'responses']);
                Route::get('/export', [ReportController::class, 'export']);
            });

            // Alerts (coming soon)
            // Route::apiResource('alerts', AlertController::class);

            // Billing (coming soon)
            // Route::prefix('billing')->group(function () {
            //     Route::post('/subscribe', [BillingController::class, 'subscribe']);
            //     Route::get('/invoices', [BillingController::class, 'invoices']);
            //     Route::post('/cancel', [BillingController::class, 'cancel']);
            // });
        });
    });
});

// Public response page (no auth required) with rate limiting
Route::prefix('r')->middleware(['throttle:10,1'])->group(function () {
    Route::get('/{token}', [ResponseController::class, 'show']);
    Route::post('/{token}', [ResponseController::class, 'store']);
});
