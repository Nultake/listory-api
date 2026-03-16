<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Mail\EmailVerificationCode as VerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            "name" => $request->validated("name"),
            "email" => $request->validated("email"),
            "password" => $request->validated("password"),
        ]);

        $this->sendVerificationCode($user);

        $token = $user->createToken("auth-token")->plainTextToken;

        return response()->json([
            "user" => new UserResource($user),
            "token" => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->validated())) {
            return response()->json([
                "message" => "Invalid credentials.",
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken("auth-token")->plainTextToken;

        return response()->json([
            "user" => new UserResource($user),
            "token" => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $user->currentAccessToken();
        $token->delete();

        return response()->json([
            "message" => "Logged out successfully.",
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            "user" => new UserResource($request->user()),
        ]);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return response()->json([
                "message" => "Email already verified.",
            ]);
        }

        $verification = EmailVerificationCode::query()
            ->where("user_id", $user->id)
            ->where("code", $request->validated("code"))
            ->where("expires_at", ">", now())
            ->first();

        if (! $verification) {
            return response()->json([
                "message" => "Invalid or expired verification code.",
            ], 422);
        }

        $user->email_verified_at = now();
        $user->save();

        EmailVerificationCode::query()
            ->where("user_id", $user->id)
            ->delete();

        return response()->json([
            "message" => "Email verified successfully.",
            "user" => new UserResource($user),
        ]);
    }

    public function resendVerificationCode(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return response()->json([
                "message" => "Email already verified.",
            ]);
        }

        $this->sendVerificationCode($user);

        return response()->json([
            "message" => "Verification code sent.",
        ]);
    }

    private function sendVerificationCode(User $user): void
    {
        EmailVerificationCode::query()
            ->where("user_id", $user->id)
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);

        EmailVerificationCode::create([
            "user_id" => $user->id,
            "code" => $code,
            "expires_at" => now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($code));
    }
}
