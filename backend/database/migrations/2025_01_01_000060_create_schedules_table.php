<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->timestamp('send_at');
            $table->string('timezone')->nullable();
            $table->string('recurring')->nullable(); // null | daily | weekly | monthly (cron-ish)
            $table->string('status')->default('pending'); // pending | dispatched | canceled
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
