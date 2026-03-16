<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("email_verification_codes", function (Blueprint $table) {
            $table->id();
            $table->foreignUuid("user_id")->constrained()->cascadeOnDelete();
            $table->string("code", 6);
            $table->timestamp("expires_at");
            $table->timestamp("created_at")->useCurrent();

            $table->index(["user_id", "code"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("email_verification_codes");
    }
};
