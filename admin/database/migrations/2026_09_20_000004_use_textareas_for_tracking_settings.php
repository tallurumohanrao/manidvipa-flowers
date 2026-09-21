<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TRACKING_KEYS = [
        'GOOGLE_ANALYTICS_ID',
        'GOOGLE_SEARCH_CONSOLE_VERIFICATION',
        'GOOGLE_TAG_MANAGER_ID',
        'META_PIXEL_ID',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->whereIn('key', self::TRACKING_KEYS)
            ->update(['input' => 'textarea']);

        Cache::forget('settings');
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->whereIn('key', self::TRACKING_KEYS)
            ->update(['input' => 'text']);

        Cache::forget('settings');
    }
};
