<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->boolean('active')->default(true);
            $table->string('source')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'key', 'active']);
            $table->index(['key', 'active']);
            $table->unique(['key', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_entitlements');
    }
};
