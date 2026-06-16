<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dua_list_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('community_dua_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('requires_payment_method')->index();
            $table->string('payment_intent_id')->nullable()->unique();
            $table->unsignedInteger('amount_minor');
            $table->string('currency', 8);
            $table->string('idempotency_key')->unique();
            $table->timestamp('fulfilled_at')->nullable()->index();
            $table->timestamp('refunded_at')->nullable()->index();
            $table->timestamp('disputed_at')->nullable()->index();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['billing_product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_purchases');
    }
};
