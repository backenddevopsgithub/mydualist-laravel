<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->string('report_reason')->nullable()->after('reported_at');
            $table->text('report_note')->nullable()->after('report_reason');
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropColumn(['report_reason', 'report_note']);
        });
    }
};
