<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table): void {
            $table->timestamp('owner_notified_at')->nullable()->after('completion_notified_at');
            $table->string('submission_batch_key', 64)->nullable()->index()->after('owner_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table): void {
            $table->dropColumn(['owner_notified_at', 'submission_batch_key']);
        });
    }
};
