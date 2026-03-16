<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("reviews", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignUuid("user_id")->constrained()->cascadeOnDelete();
            $table->foreignUuid("media_item_id")->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger("rating"); // 1-10
            $table->text("comment")->nullable();
            $table->boolean("has_spoiler")->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(["user_id", "media_item_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("reviews");
    }
};
