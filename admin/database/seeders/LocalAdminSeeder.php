<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocalAdminSeeder extends Seeder
{
    /**
     * Create a local-only admin account for development setup.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $email = env('LOCAL_ADMIN_EMAIL', 'admin@manidvipa.local');
        $password = env('LOCAL_ADMIN_PASSWORD', 'LocalAdmin@123');
        $now = now();

        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');

        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'Super Admin',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $admin = Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Local Admin',
                'password' => Hash::make($password),
                'status' => 1,
            ]
        );

        DB::table('admin_role')->updateOrInsert(
            ['admin_id' => $admin->id, 'role_id' => $roleId],
            ['created_at' => $now, 'updated_at' => $now]
        );

        foreach (DB::table('permissions')->where('status', 1)->get() as $permission) {
            foreach (['view', 'create', 'edit', 'delete'] as $field) {
                $ability = $permission->{$field};

                if (! $ability) {
                    continue;
                }

                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission' => $ability],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $this->command?->info("Local admin ready: {$email}");
    }
}
