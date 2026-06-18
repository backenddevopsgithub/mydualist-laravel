<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('dua_lists', function (Blueprint $table): void {
            $table->index('created_at');
            $table->index('user_id');
            $table->index('occasion');
        });

        Schema::table('dua_submissions', function (Blueprint $table): void {
            $table->index('created_at');
            $table->index('gender');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });

        Schema::table('dua_lists', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['occasion']);
        });

        Schema::table('dua_submissions', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['gender']);
        });
    }
};
