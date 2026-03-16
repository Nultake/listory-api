<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("collection_invitations", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignUuid("collection_id")->constrained()->cascadeOnDelete();
            $table->foreignUuid("inviter_id")->constrained("users")->cascadeOnDelete();
            $table->foreignUuid("invitee_id")->constrained("users")->cascadeOnDelete();
            $table->string("status")->default("pending"); // pending, accepted, declined
            $table->text("message")->nullable();
            $table->timestamps();

            $table->unique(["collection_id", "invitee_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("collection_invitations");
    }
};
