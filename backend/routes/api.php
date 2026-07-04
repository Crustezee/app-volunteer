<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MigunaniController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrganizerApplicationController;
use App\Http\Controllers\Api\OrganizerCertificateController;
use App\Http\Controllers\Api\OrganizerDashboardController;
use App\Http\Controllers\Api\OrganizerEventController;
use App\Http\Controllers\Api\PublicCertificateController;
use App\Http\Controllers\Api\SavedEventController;
use App\Http\Controllers\Api\VolunteerApplicationController;
use App\Http\Controllers\Api\VolunteerCertificateController;
use App\Http\Controllers\Api\VolunteerDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::get('/health', [MigunaniController::class, 'health']);
    Route::get('/home', HomeController::class);
    Route::get('/categories', [MigunaniController::class, 'categories']);
    Route::get('/organizers', [MigunaniController::class, 'organizers']);
    Route::get('/organizers/{id}', [MigunaniController::class, 'organizer']);
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{idOrSlug}', [EventController::class, 'show']);
    Route::get('/certificates/verify/{credentialId}', [PublicCertificateController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/profile', [MigunaniController::class, 'profile']);
        Route::get('/volunteer/dashboard', VolunteerDashboardController::class);
        Route::get('/volunteer/applications', [VolunteerApplicationController::class, 'index']);
        Route::get('/volunteer/saved-events', [SavedEventController::class, 'index']);
        Route::get('/volunteer/certificates', [VolunteerCertificateController::class, 'index']);
        Route::get('/volunteer/certificates/{certificate}/download', [VolunteerCertificateController::class, 'download']);
        Route::get('/volunteer/certificates/{certificate}', [VolunteerCertificateController::class, 'show']);

        Route::prefix('/organizers/{organizer}')
            ->scopeBindings()
            ->group(function (): void {
                Route::get('/dashboard', OrganizerDashboardController::class);
                Route::get('/events', [OrganizerEventController::class, 'index']);
                Route::get('/events/{event}', [OrganizerEventController::class, 'show']);
                Route::get('/applications', [OrganizerApplicationController::class, 'index']);
                Route::get('/applications/{application}', [OrganizerApplicationController::class, 'show']);
                Route::get('/certificates', [OrganizerCertificateController::class, 'index']);
                Route::get('/certificates/{certificate}', [OrganizerCertificateController::class, 'show'])->withoutScopedBindings();

                Route::middleware('throttle:api-write')->group(function (): void {
                    Route::post('/events', [OrganizerEventController::class, 'store']);
                    Route::patch('/events/{event}', [OrganizerEventController::class, 'update']);
                    Route::patch('/applications/{application}/status', [OrganizerApplicationController::class, 'updateStatus']);
                    Route::post('/applications/{application}/certificate', [OrganizerCertificateController::class, 'store']);
                    Route::patch('/certificates/{certificate}/revoke', [OrganizerCertificateController::class, 'revoke'])->withoutScopedBindings();
                });
            });

        Route::middleware('throttle:api-write')->group(function (): void {
            Route::post('/auth/logout', [AuthController::class, 'logout']);
            Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read']);
            Route::post('/events/{event}/applications', [VolunteerApplicationController::class, 'store']);
            Route::put('/volunteer/saved-events/{event}', [SavedEventController::class, 'store']);
            Route::delete('/volunteer/saved-events/{event}', [SavedEventController::class, 'destroy']);
        });
    });
});
