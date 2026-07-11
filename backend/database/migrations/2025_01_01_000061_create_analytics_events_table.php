<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignUuid('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->foreignUuid('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('type'); // sent | delivered | received | opened | clicked | failed
            $table->string('country', 4)->nullable();
            $table->string('os_version')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['application_id', 'type', 'occurred_at']);
            $table->index(['notification_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
