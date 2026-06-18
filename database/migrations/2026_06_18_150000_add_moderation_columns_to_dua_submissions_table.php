<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->unsignedInteger('report_count')->default(0)->after('report_note');
            $table->string('status_before_report')->nullable()->after('report_count');
            $table->foreignId('moderated_by')->nullable()->after('status_before_report')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->string('moderation_action')->nullable()->after('moderated_at');
            $table->text('moderation_notes')->nullable()->after('moderation_action');
        });

        DB::table('dua_submissions')
            ->whereNotNull('reported_at')
            ->update(['report_count' => 1]);
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn([
                'report_count',
                'status_before_report',
                'moderated_at',
                'moderation_action',
                'moderation_notes',
            ]);
        });
    }
};
