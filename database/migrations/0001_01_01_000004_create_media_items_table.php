<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("media_items", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("title");
            $table->string("type"); // game, film, series
            $table->text("description")->nullable();
            $table->string("cover_image")->nullable();
            $table->date("release_date")->nullable();
            $table->string("external_id")->nullable();
            $table->string("external_source")->nullable();
            $table->json("metadata")->nullable();
            $table->foreignUuid("created_by")->constrained("users");
            $table->timestamps();
            $table->softDeletes();

            $table->index("type");
            $table->index(["external_id", "external_source"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("media_items");
    }
};
