<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('is_personal_dua');
            $table->unsignedInteger('locked_at_quota')->nullable()->after('is_locked');
            $table->string('locked_reason')->nullable()->after('locked_at_quota');
            $table->timestamp('unlocked_at')->nullable()->after('locked_reason');
            $table->foreignId('unlock_purchase_id')
                ->nullable()
                ->after('unlocked_at')
                ->constrained('billing_purchases')
                ->nullOnDelete();

            $table->index(['dua_list_id', 'is_locked', 'status']);
            $table->index(['dua_list_id', 'unlocked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropIndex(['dua_list_id', 'is_locked', 'status']);
            $table->dropIndex(['dua_list_id', 'unlocked_at']);
            $table->dropConstrainedForeignId('unlock_purchase_id');
            $table->dropColumn([
                'is_locked',
                'locked_at_quota',
                'locked_reason',
                'unlocked_at',
            ]);
        });
    }
};
