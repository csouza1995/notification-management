<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel_name'); // email, sms, push, slack, etc
            $table->string('notification_type');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->json('payload')->nullable(); // Notification data
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Indexes for queries and reports
            $table->index(['user_id', 'created_at']);
            $table->index(['notification_type', 'status']);
            $table->index(['channel_name', 'status']);
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
