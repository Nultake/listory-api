<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix("v1")->group(function () {
    // Auth (public)
    Route::middleware("throttle:auth")->group(function () {
        Route::post("auth/register", [AuthController::class, "register"]);
        Route::post("auth/login", [AuthController::class, "login"]);
        Route::post("auth/google", [AuthController::class, "google"]);
    });

    // Auth (protected)
    Route::middleware("auth:sanctum")->group(function () {
        Route::post("auth/logout", [AuthController::class, "logout"]);
        Route::get("auth/me", [AuthController::class, "me"]);

        Route::middleware("throttle:verification")->group(function () {
            Route::post("auth/verify-email", [AuthController::class, "verifyEmail"]);
            Route::post("auth/resend-verification", [AuthController::class, "resendVerificationCode"]);
        });
    });
});
