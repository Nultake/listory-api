<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\CollectionInvitationController;
use App\Http\Controllers\Api\V1\CollectionItemController;
use App\Http\Controllers\Api\V1\CollectionMemberController;
use App\Http\Controllers\Api\V1\GenreController;
use App\Http\Controllers\Api\V1\MediaItemController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\UserLibraryController;
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

        // Core resources
        Route::apiResource("media-items", MediaItemController::class);
        Route::apiResource("reviews", ReviewController::class);
        Route::get("genres", [GenreController::class, "index"]);
        Route::get("library", [UserLibraryController::class, "index"]);

        // Collections
        Route::apiResource("collections", CollectionController::class);
        Route::post("collections/{collection}/items", [CollectionItemController::class, "store"]);
        Route::delete("collections/{collection}/items/{mediaItem}", [CollectionItemController::class, "destroy"]);
        Route::get("collections/{collection}/members", [CollectionMemberController::class, "index"]);
        Route::delete("collections/{collection}/members/{member}", [CollectionMemberController::class, "destroy"]);
        Route::post("collections/{collection}/invitations", [CollectionInvitationController::class, "store"]);

        // Invitations (received by the authenticated user)
        Route::get("invitations", [CollectionInvitationController::class, "index"]);
        Route::post("invitations/{invitation}/accept", [CollectionInvitationController::class, "accept"]);
        Route::post("invitations/{invitation}/decline", [CollectionInvitationController::class, "decline"]);
    });
});
