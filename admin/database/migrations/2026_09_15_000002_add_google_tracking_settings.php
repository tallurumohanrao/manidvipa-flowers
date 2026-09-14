<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = [
            [
                'label' => 'Google Analytics Measurement ID',
                'key' => 'GOOGLE_ANALYTICS_ID',
                'type' => 'Developer',
                'input' => 'text',
                'sort_order' => '90',
                'status' => '1',
            ],
            [
                'label' => 'Google Search Console Verification Code',
                'key' => 'GOOGLE_SEARCH_CONSOLE_VERIFICATION',
                'type' => 'Developer',
                'input' => 'text',
                'sort_order' => '91',
                'status' => '1',
            ],
        ];

        foreach ($settings as $setting) {
            $existing = DB::table('settings')->where('key', $setting['key'])->exists();
            $timestamps = [];

            if (Schema::hasColumn('settings', 'updated_at')) {
                $timestamps['updated_at'] = now();
            }

            if ($existing) {
                DB::table('settings')
                    ->where('key', $setting['key'])
                    ->update(array_merge($setting, $timestamps));

                continue;
            }

            if (Schema::hasColumn('settings', 'created_at')) {
                $timestamps['created_at'] = now();
            }

            DB::table('settings')->insert(array_merge($setting, [
                'value' => '',
            ], $timestamps));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->whereIn('key', [
                'GOOGLE_ANALYTICS_ID',
                'GOOGLE_SEARCH_CONSOLE_VERIFICATION',
            ])
            ->delete();
    }
};
