<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("genre_media_item", function (Blueprint $table) {
            $table->foreignUuid("genre_id")->constrained()->cascadeOnDelete();
            $table->foreignUuid("media_item_id")->constrained()->cascadeOnDelete();
            $table->primary(["genre_id", "media_item_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("genre_media_item");
    }
};
