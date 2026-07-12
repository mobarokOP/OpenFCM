<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrective migration for environments that ran the original device_tags /
 * topic_subscriptions definitions, where the pivot primary key was a UUID with
 * no generated value — causing every ->attach() to fail. Pivots carry no
 * durable data (the feature could not have worked), so we rebuild them with an
 * auto-increment primary key. Works on both MySQL (prod) and SQLite (dev).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('device_tags');
        Schema::dropIfExists('topic_subscriptions');

        Schema::create('device_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'tag_id']);
            $table->index(['tag_id', 'value']);
        });

        Schema::create('topic_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->foreignUuid('device_id')->constrained('devices')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['topic_id', 'device_id']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        // No-op: this only reconciles the primary key definition.
    }
};
