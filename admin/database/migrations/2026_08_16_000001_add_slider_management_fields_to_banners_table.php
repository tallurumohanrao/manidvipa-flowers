<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (!Schema::hasColumn('banners', 'title')) {
                $table->string('title')->nullable()->after('id');
            }

            if (!Schema::hasColumn('banners', 'alt')) {
                $table->string('alt')->nullable()->after('title');
            }

            if (!Schema::hasColumn('banners', 'page')) {
                $table->string('page')->nullable()->after('button_text');
            }

            if (!Schema::hasColumn('banners', 'parent_div_class')) {
                $table->string('parent_div_class')->nullable()->after('page');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter(
            ['title', 'alt', 'page', 'parent_div_class'],
            fn ($column) => Schema::hasColumn('banners', $column)
        ));

        if ($columns) {
            Schema::table('banners', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
