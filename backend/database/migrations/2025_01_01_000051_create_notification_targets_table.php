<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->foreignUuid('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|sent|delivered|failed
            $table->timestamps();

            $table->unique(['notification_id', 'device_id']);
            $table->index(['notification_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_targets');
    }
};
