<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_duas', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 15);
            $table->string('last_name', 15);
            $table->string('email');
            $table->string('gender', 20);
            $table->text('content');
            $table->string('type');
            $table->string('status')->default('active');
            $table->unsignedTinyInteger('required_completions')->default(1);
            $table->unsignedInteger('completion_count')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->foreignId('stripe_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->string('report_reason')->nullable();
            $table->text('report_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_visible']);
            $table->index('type');
            $table->index('created_at');
        });

        Schema::create('community_dua_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_dua_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['community_dua_id', 'user_id']);
        });

        Schema::create('community_dua_skips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_dua_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['community_dua_id', 'user_id']);
        });

        Schema::create('community_dua_queue_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('showing_type')->default('paid');
            $table->unsignedTinyInteger('pattern')->default(0);
            $table->foreignId('current_community_dua_id')->nullable()->constrained('community_duas')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_dua_queue_states');
        Schema::dropIfExists('community_dua_skips');
        Schema::dropIfExists('community_dua_completions');
        Schema::dropIfExists('community_duas');
    }
};
