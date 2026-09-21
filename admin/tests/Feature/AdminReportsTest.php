<?php

namespace Tests\Feature;

use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_all_report_pages_and_csv_exports_load(): void
    {
        $admin = $this->createAdminWithAbilities(['reports_view']);
        $this->actingAs($admin, 'admin');

        foreach (['sales', 'orders', 'inventory', 'products', 'customers'] as $report) {
            $this->get(route('admin.reports.'.$report, ['from' => now()->subDays(7)->toDateString(), 'to' => now()->toDateString()]))
                ->assertOk();
            $this->get(route('admin.reports.export', ['report' => $report, 'from' => now()->subDays(7)->toDateString(), 'to' => now()->toDateString()]))
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_reports_require_the_reports_permission(): void
    {
        $admin = $this->createAdminWithAbilities([]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.reports.sales'))
            ->assertForbidden();
    }

    private function createAdminWithAbilities(array $abilities): Admin
    {
        $role = Role::create([
            'name' => 'Report Test Role '.Str::uuid(),
            'status' => 1,
        ]);

        foreach ($abilities as $ability) {
            DB::table('role_permissions')->insert([
                'role_id' => $role->id,
                'permission' => $ability,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $admin = Admin::create([
            'name' => 'Report Test Admin',
            'email' => 'report-'.Str::uuid().'@example.test',
            'password' => Hash::make('AdminPassword123!'),
            'status' => 1,
        ]);

        DB::table('admin_role')->insert([
            'admin_id' => $admin->id,
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $admin;
    }
}
