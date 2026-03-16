<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("collection_media_item", function (Blueprint $table) {
            $table->foreignUuid("collection_id")->constrained()->cascadeOnDelete();
            $table->foreignUuid("media_item_id")->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(["collection_id", "media_item_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("collection_media_item");
    }
};
