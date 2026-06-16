<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedInteger('external_product_id')->unique();
            $table->string('name');
            $table->string('scope');
            $table->boolean('stackable')->default(false);
            $table->boolean('requires_authentication')->default(true);
            $table->unsignedInteger('amount_minor');
            $table->string('currency', 8);
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['scope', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_products');
    }
};
