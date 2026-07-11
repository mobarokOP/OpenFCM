<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// App end-users (identified by external_id), scoped to an application.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('external_id');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
