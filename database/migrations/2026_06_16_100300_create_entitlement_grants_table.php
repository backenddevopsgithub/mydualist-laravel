<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlement_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dua_list_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('entitlement_key')->index();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_stackable')->default(false);
            $table->string('dedupe_key')->nullable()->unique();
            $table->foreignId('source_purchase_id')->nullable()->constrained('billing_purchases')->nullOnDelete();
            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'entitlement_key']);
            $table->index(['dua_list_id', 'entitlement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlement_grants');
    }
};
