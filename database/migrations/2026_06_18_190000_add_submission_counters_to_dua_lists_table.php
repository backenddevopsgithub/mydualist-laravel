<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_lists', function (Blueprint $table): void {
            $table->unsignedInteger('submissions_count')->default(0)->after('list_image_reminder_sent_at');
            $table->unsignedInteger('pending_submissions_count')->default(0)->after('submissions_count');
            $table->unsignedInteger('completed_submissions_count')->default(0)->after('pending_submissions_count');
            $table->unsignedInteger('hidden_submissions_count')->default(0)->after('completed_submissions_count');
            $table->unsignedInteger('archived_submissions_count')->default(0)->after('hidden_submissions_count');
            $table->unsignedInteger('reported_submissions_count')->default(0)->after('archived_submissions_count');
            $table->unsignedInteger('non_personal_submissions_count')->default(0)->after('reported_submissions_count');
        });
    }

    public function down(): void
    {
        Schema::table('dua_lists', function (Blueprint $table): void {
            $table->dropColumn([
                'submissions_count',
                'pending_submissions_count',
                'completed_submissions_count',
                'hidden_submissions_count',
                'archived_submissions_count',
                'reported_submissions_count',
                'non_personal_submissions_count',
            ]);
        });
    }
};
