<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('key');
            $table->timestamps();

            $table->unique(['application_id', 'key']);
        });

        Schema::create('device_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'tag_id']);
            $table->index(['tag_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tags');
        Schema::dropIfExists('tags');
    }
};
