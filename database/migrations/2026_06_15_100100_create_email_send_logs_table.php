<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_send_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notification_class');
            $table->string('recipient_email');
            $table->string('status', 16);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['status', 'sent_at']);
            $table->index('recipient_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_send_logs');
    }
};
