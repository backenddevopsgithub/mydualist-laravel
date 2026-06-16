<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_purchase_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_purchase_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->string('stripe_event_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['billing_purchase_id', 'stripe_event_id'], 'billing_purchase_events_purchase_stripe_unique');
            $table->unique('idempotency_key', 'billing_purchase_events_idempotency_unique');
            $table->index(['billing_purchase_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_purchase_events');
    }
};
