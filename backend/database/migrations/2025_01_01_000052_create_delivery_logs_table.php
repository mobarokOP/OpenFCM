<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->foreignUuid('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('status'); // sent|failed
            $table->string('fcm_message_id')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index(['notification_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_logs');
    }
};
