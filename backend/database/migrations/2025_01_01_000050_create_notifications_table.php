<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('image_url')->nullable();
            $table->string('large_icon')->nullable();
            $table->string('small_icon')->nullable();
            $table->string('deep_link')->nullable();
            $table->json('data')->nullable();
            $table->unsignedInteger('ttl')->default(2419200);
            $table->string('priority')->default('high');
            $table->string('collapse_key')->nullable();
            $table->string('channel_id')->nullable();

            $table->json('audience');            // {type,value}
            $table->string('status')->default('draft'); // draft|queued|scheduled|sending|sent|canceled|failed
            $table->unsignedInteger('recipients_count')->default(0);

            // aggregate counters
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
