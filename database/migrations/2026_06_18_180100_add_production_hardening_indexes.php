<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_library_items', function (Blueprint $table): void {
            $table->index('uploaded_by');
            $table->index('created_at');
        });

        Schema::table('admin_exports', function (Blueprint $table): void {
            $table->index(['status', 'updated_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('media', function (Blueprint $table): void {
            $table->index('collection_name');
        });
    }

    public function down(): void
    {
        Schema::table('media_library_items', function (Blueprint $table): void {
            $table->dropIndex(['uploaded_by']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('admin_exports', function (Blueprint $table): void {
            $table->dropIndex(['status', 'updated_at']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('media', function (Blueprint $table): void {
            $table->dropIndex(['collection_name']);
        });
    }
};
