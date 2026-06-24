<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->json('faqs')->nullable()->after('content');
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->boolean('show_in_resources_filter')->default(true)->after('sort_order');
        });

        if (Schema::hasTable('blog_categories')) {
            \Illuminate\Support\Facades\DB::table('blog_categories')
                ->whereIn('slug', ['daily-duas', 'homepage'])
                ->update(['show_in_resources_filter' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('faqs');
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropColumn('show_in_resources_filter');
        });
    }
};
