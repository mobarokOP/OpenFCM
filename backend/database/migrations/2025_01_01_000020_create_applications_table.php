<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // public App ID used by SDK
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('package_name')->nullable();
            $table->string('fcm_project_id')->nullable();
            $table->text('fcm_service_account')->nullable(); // encrypted JSON
            $table->string('status')->default('active'); // active | paused
            $table->unsignedInteger('rate_limit')->default(1000); // msgs/min
            $table->timestamps();

            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
