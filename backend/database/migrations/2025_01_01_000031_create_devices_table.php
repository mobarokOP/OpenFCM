<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->text('fcm_token');
            $table->string('platform')->default('android');
            $table->string('app_version')->nullable();
            $table->string('os_version')->nullable();
            $table->string('language', 8)->nullable();
            $table->string('country', 4)->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('notification_permission')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index('application_id');
            $table->index(['application_id', 'active']);
            $table->index('external_id');
            $table->unique(['application_id', 'fcm_token'], 'devices_app_token_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
