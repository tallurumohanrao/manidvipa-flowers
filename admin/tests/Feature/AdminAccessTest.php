<?php

namespace Tests\Feature;

use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use App\Notifications\Admin\ResetAdminPassword;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_from_admin_panel(): void
    {
        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    public function test_disabled_administrator_cannot_log_in(): void
    {
        $admin = Admin::create([
            'name' => 'Disabled Test Admin',
            'email' => 'disabled-'.Str::uuid().'@example.test',
            'password' => Hash::make('AdminPassword123!'),
            'status' => 0,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class)->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'AdminPassword123!',
        ])->assertRedirect()->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_sidebar_and_routes_respect_role_permissions(): void
    {
        $admin = $this->createAdminWithAbilities(['products_view']);

        $response = $this->actingAs($admin, 'admin')->get('/admin');

        $response->assertOk();
        $response->assertSee(route('admin.products.index'), false);
        $response->assertDontSee(route('admin.orders.index'), false);
        $this->actingAs($admin, 'admin')->get('/admin/permissions')->assertForbidden();
        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->post('/admin/clear')
            ->assertForbidden();
    }

    public function test_admin_create_page_has_complete_form_data(): void
    {
        $admin = $this->createAdminWithAbilities(['admins_view', 'admins_create']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins/create')
            ->assertOk()
            ->assertSee('roles[]', false);
    }

    public function test_removed_blank_and_get_mutation_routes_are_not_available(): void
    {
        $this->get('/admin/clear')->assertStatus(405);

        $admin = $this->createAdminWithAbilities(['featuredproducts_view']);
        $this->actingAs($admin, 'admin')->get('/admin/featuredproducts/create')->assertStatus(405);
    }

    public function test_admin_password_reset_uses_admin_routes_and_blocks_disabled_admins(): void
    {
        Notification::fake();

        $disabledAdmin = Admin::create([
            'name' => 'Disabled Reset Admin',
            'email' => 'disabled-reset-'.Str::uuid().'@example.test',
            'password' => Hash::make('AdminPassword123!'),
            'status' => 0,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('admin.password.email'), ['email' => $disabledAdmin->email])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();

        $activeAdmin = Admin::create([
            'name' => 'Active Reset Admin',
            'email' => 'active-reset-'.Str::uuid().'@example.test',
            'password' => Hash::make('AdminPassword123!'),
            'status' => 1,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('admin.password.email'), ['email' => $activeAdmin->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($activeAdmin, ResetAdminPassword::class, function ($notification) use ($activeAdmin) {
            return Str::contains($notification->toMail($activeAdmin)->actionUrl, '/admin/password/reset/')
                && Str::contains($notification->toMail($activeAdmin)->actionUrl, urlencode($activeAdmin->email));
        });

        $this->get(route('admin.password.reset', [
            'token' => 'sample-token',
            'email' => $activeAdmin->email,
        ]))
            ->assertOk()
            ->assertSee('sample-token', false)
            ->assertSee('/admin/password/reset', false)
            ->assertSee($activeAdmin->email, false);
    }

    public function test_admin_profile_and_password_update_workflows(): void
    {
        $admin = Admin::create([
            'name' => 'Profile Test Admin',
            'email' => 'profile-admin-'.Str::uuid().'@example.test',
            'password' => Hash::make('OldAdminPassword123!'),
            'status' => 1,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.account.edit'))
            ->assertOk()
            ->assertSee($admin->email, false);

        $updatedEmail = 'updated-profile-admin-'.Str::uuid().'@example.test';

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patch(route('admin.account.update', $admin), [
                'name' => 'Updated Profile Admin',
                'email' => $updatedEmail,
                'old_image' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'name' => 'Updated Profile Admin',
            'email' => $updatedEmail,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin->fresh(), 'admin')
            ->post(route('admin.changepassword'), [
                'current_password' => 'WrongPassword123!',
                'new_password' => 'NewAdminPassword123!',
                'new_password_confirmation' => 'NewAdminPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['current_password'], null, 'changePasswordForm');

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin->fresh(), 'admin')
            ->post(route('admin.changepassword'), [
                'current_password' => 'OldAdminPassword123!',
                'new_password' => 'NewAdminPassword123!',
                'new_password_confirmation' => 'NewAdminPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewAdminPassword123!', $admin->fresh()->password));
    }

    public function test_permissions_module_and_super_admin_grant_exist(): void
    {
        $this->assertDatabaseHas('permissions', [
            'module' => 'Permissions',
            'view' => 'permissions_view',
            'status' => 1,
        ]);

        $superAdminRoleIds = DB::table('roles')->where('name', 'Super Admin')->pluck('id');
        $this->assertTrue(
            DB::table('role_permissions')
                ->whereIn('role_id', $superAdminRoleIds)
                ->where('permission', 'permissions_view')
                ->exists()
        );
    }

    public function test_every_visible_admin_section_and_available_create_form_loads(): void
    {
        $abilities = DB::table('permissions')
            ->where('status', 1)
            ->get(['view', 'create', 'edit', 'delete'])
            ->flatMap(fn ($permission) => [$permission->view, $permission->create, $permission->edit, $permission->delete])
            ->filter()
            ->unique()
            ->values()
            ->all();
        $admin = $this->createAdminWithAbilities($abilities);
        $this->actingAs($admin, 'admin');

        $permissions = DB::table('permissions')->where('status', 1)->where('menu_status', 1)->get();
        foreach ($permissions as $permission) {
            $indexRoute = 'admin.'.$permission->route_name.'.index';
            if (Route::has($indexRoute)) {
                $response = $this->get(route($indexRoute));
                $this->assertSame(200, $response->getStatusCode(), $indexRoute.' failed to load.');
            }

            $createRoute = 'admin.'.$permission->route_name.'.create';
            if ($permission->create && Route::has($createRoute)) {
                $response = $this->get(route($createRoute));
                $this->assertSame(200, $response->getStatusCode(), $createRoute.' failed to load.');
            }
        }

        $editableTables = [
            'addons' => 'addons',
            'banners' => 'banners',
            'orderstatuses' => 'order_statuses',
            'paymentstatuses' => 'payment_statuses',
            'shippingstatuses' => 'shipping_statuses',
            'shippingprices' => 'shipping_prices',
            'subscriptionplans' => 'subscription_plans',
            'coupons' => 'coupons',
            'units' => 'weights',
            'categories' => 'categories',
            'products' => 'products',
            'roles' => 'roles',
            'seo' => 'seo_urls',
            'users' => 'users',
            'admins' => 'admins',
            'faqs' => 'faqs',
            'permissions' => 'permissions',
            'posts' => 'posts',
            'services' => 'services',
            'pages' => 'pages',
            'contentblocks' => 'content_blocks',
            'clients' => 'clients',
            'testimonials' => 'testimonials',
        ];

        foreach ($editableTables as $routeName => $table) {
            $editRoute = 'admin.'.$routeName.'.edit';
            if (! $permissions->pluck('route_name')->contains($routeName) || ! Route::has($editRoute) || ! Schema::hasTable($table)) {
                continue;
            }

            $id = DB::table($table)->value('id');
            if ($id) {
                $response = $this->get(route($editRoute, [$id]));
                $this->assertSame(200, $response->getStatusCode(), $editRoute.' failed to load.');
            }
        }

        $orderId = DB::table('orders')->where('order_status_id', '<>', 1)->value('id');
        if ($orderId) {
            $response = $this->get(route('admin.orders.show', [$orderId]));
            $this->assertSame(200, $response->getStatusCode(), 'admin.orders.show failed to load.');
        }

        $enquiryId = DB::table('subscription_enquiries')->value('id');
        if ($enquiryId) {
            $response = $this->get(route('admin.subscriptionenquiries.show', [$enquiryId]));
            $this->assertSame(200, $response->getStatusCode(), 'admin.subscriptionenquiries.show failed to load.');
        }
    }

    public function test_status_template_lists_show_friendly_order_number_preview(): void
    {
        $admin = $this->createAdminWithAbilities([
            'orderstatuses_view',
            'shippingstatuses_view',
            'paymentstatuses_view',
        ]);
        $this->actingAs($admin, 'admin');

        foreach (['orderstatuses', 'shippingstatuses', 'paymentstatuses'] as $routeName) {
            $this->get(route('admin.'.$routeName.'.index'))
                ->assertOk()
                ->assertSee('Order No')
                ->assertSee('#125');
        }
    }

    public function test_admin_page_update_clears_static_page_cache(): void
    {
        $suffix = Str::lower(Str::random(10));
        $slug = 'cache-page-'.$suffix;
        $pageId = DB::table('pages')->insertGetId([
            'name' => 'Cache Page '.$suffix,
            'slug' => $slug,
            'description' => '<p>Old page content</p>',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::put('api_static_page_'.$slug, (object) [
            'name' => 'Cache Page '.$suffix,
            'description' => '<p>Cached old page content</p>',
        ], now()->addHour());

        $admin = $this->createAdminWithAbilities(['pages_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->put(route('admin.pages.update', ['page' => $pageId]), [
                'name' => 'Cache Page '.$suffix,
                'description' => '<p>New page content</p>',
                'status' => 1,
                'FormButton' => 'SAVE',
            ])
            ->assertRedirect(route('admin.pages.index'));

        $this->assertFalse(Cache::has('api_static_page_'.$slug));
    }

    public function test_refund_policy_static_page_uses_existing_refund_return_page(): void
    {
        DB::table('pages')->updateOrInsert(
            ['slug' => 'refund-return-policy'],
            [
                'name' => 'Refund & Return Policy',
                'description' => '<p>Refund page alias content</p>',
                'status' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget('api_static_page_refund-return-policy');

        $this->getJson('/api/static-page?page_name=refund-policy')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Refund & Return Policy')
            ->assertJsonPath('data.description', '<p>Refund page alias content</p>');
    }

    public function test_public_faq_api_returns_enabled_faqs_and_admin_changes_clear_cache(): void
    {
        $suffix = Str::lower(Str::random(10));
        $faqId = DB::table('faqs')->insertGetId([
            'question' => 'SEO FAQ active question '.$suffix,
            'answer' => '<p>SEO FAQ active answer '.$suffix.'</p>',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('faqs')->insert([
            'question' => 'SEO FAQ disabled question '.$suffix,
            'answer' => '<p>SEO FAQ disabled answer '.$suffix.'</p>',
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget('api_faqs');

        $this->getJson('/api/faqs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['question' => 'SEO FAQ active question '.$suffix])
            ->assertJsonMissing(['question' => 'SEO FAQ disabled question '.$suffix]);

        Cache::put('api_faqs', collect([(object) ['question' => 'Old cached FAQ']]), now()->addHour());

        $admin = $this->createAdminWithAbilities(['faqs_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->put(route('admin.faqs.update', ['faq' => $faqId]), [
                'question' => 'SEO FAQ updated question '.$suffix,
                'answer' => '<p>SEO FAQ updated answer '.$suffix.'</p>',
                'status' => 1,
                'FormButton' => 'SAVE',
            ])
            ->assertRedirect(route('admin.faqs.index'));

        $this->assertFalse(Cache::has('api_faqs'));
    }

    public function test_products_list_shows_display_quantity_only(): void
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Quantity Display Test Flower '.$suffix,
            'sku' => 'quantity-display-'.$suffix,
            'slug' => 'quantity-display-'.$suffix,
            'qty' => '5 KG',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_weights')->insert([
            [
                'product_id' => $productId,
                'name' => '1 KG',
                'sell_price' => 100,
                'list_price' => 120,
                'qty' => 5,
                'stock' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $productId,
                'name' => 'Each Bunch',
                'sell_price' => 180,
                'list_price' => 200,
                'qty' => 100,
                'stock' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $admin = $this->createAdminWithAbilities(['products_view']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', ['title' => 'Quantity Display Test Flower '.$suffix]))
            ->assertOk()
            ->assertSee('5 KG')
            ->assertDontSee('1 KG - 5 KG')
            ->assertDontSee('Each Bunch - 100 bunches')
            ->assertDontSee('Needs attention')
            ->assertDontSee('Total tracked:');
    }

    public function test_products_list_derives_simple_quantity_unit_from_stock_weight(): void
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Bunch Quantity Test Flower '.$suffix,
            'sku' => 'bunch-quantity-'.$suffix,
            'slug' => 'bunch-quantity-'.$suffix,
            'qty' => '100',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_weights')->insert([
            'product_id' => $productId,
            'name' => 'Each Bunch',
            'sell_price' => 180,
            'list_price' => 200,
            'qty' => 100,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createAdminWithAbilities(['products_view']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', ['title' => 'Bunch Quantity Test Flower '.$suffix]))
            ->assertOk()
            ->assertSee('100 bunches')
            ->assertDontSee('Each Bunch - 100 bunches');
    }

    public function test_product_display_quantity_with_units_can_be_saved_and_listed(): void
    {
        $suffix = Str::lower(Str::random(10));
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Quantity Test Category '.$suffix,
            'title' => 'Quantity Test Category '.$suffix,
            'slug' => 'quantity-test-category-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'title' => 'Quantity Save Test Flower '.$suffix,
            'sku' => 'quantity-save-'.$suffix,
            'slug' => 'quantity-save-'.$suffix,
            'qty' => '10',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_product')->insert([
            'product_id' => $productId,
            'category_id' => $categoryId,
        ]);

        DB::table('seo_urls')->insert([
            'url' => '/quantity-save-'.$suffix,
            'page_title' => 'Quantity Save Test Flower',
            'meta_keywords' => 'old quantity save keyword',
            'meta_description' => 'Quantity Save Test Flower',
            'robots' => 'index,follow',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createAdminWithAbilities(['products_view', 'products_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->put(route('admin.products.update', ['product' => $productId]), [
                'title' => 'Quantity Save Test Flower '.$suffix,
                'sku' => 'quantity-save-'.$suffix,
                'qty' => '100 bunches',
                'product_category' => [$categoryId],
                'priority' => 1,
                'status' => 1,
                'seo' => [
                    'old_url' => '/quantity-save-'.$suffix,
                    'url' => 'quantity-save-'.$suffix,
                    'page_title' => 'Quantity Save Test Flower',
                    'meta_keywords' => 'quantity save test, flowers',
                    'meta_description' => 'Quantity Save Test Flower',
                    'schema_markup' => '{"@context":"https://schema.org","@type":"Product","name":"Quantity Save Test Flower"}',
                    'robots' => 'index,follow',
                ],
                'FormButton' => 'SAVE',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'qty' => '100 bunches',
        ]);

        $this->assertDatabaseHas('seo_urls', [
            'url' => '/product-details/quantity-save-'.$suffix,
            'page_title' => 'Quantity Save Test Flower',
            'meta_keywords' => 'quantity save test, flowers',
            'schema_markup' => '{"@context":"https://schema.org","@type":"Product","name":"Quantity Save Test Flower"}',
        ]);

        $this->assertDatabaseMissing('seo_urls', [
            'url' => '/quantity-save-'.$suffix,
            'page_title' => 'Quantity Save Test Flower',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', ['title' => 'Quantity Save Test Flower '.$suffix]))
            ->assertOk()
            ->assertSee('100 bunches');
    }

    public function test_seo_metadata_api_resolves_frontend_paths_from_legacy_admin_urls(): void
    {
        $suffix = Str::lower(Str::random(10));

        DB::table('seo_urls')->insert([
            'url' => '/legacy-product-seo-'.$suffix,
            'page_title' => 'Legacy Product SEO '.$suffix,
            'meta_keywords' => 'legacy product seo',
            'meta_description' => 'Legacy product SEO description '.$suffix,
            'schema_markup' => '{"@context":"https://schema.org","@type":"Product","name":"Legacy Product SEO"}',
            'robots' => 'index,follow',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('seo_urls')->insert([
            'url' => '/legacy-category-seo-'.$suffix,
            'page_title' => 'Legacy Category SEO '.$suffix,
            'meta_keywords' => 'legacy category seo',
            'meta_description' => 'Legacy category SEO description '.$suffix,
            'robots' => 'index,follow',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/seo-meta-data?url='.urlencode('/product-details/legacy-product-seo-'.$suffix))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.page_title', 'Legacy Product SEO '.$suffix)
            ->assertJsonPath('data.schema_markup', '{"@context":"https://schema.org","@type":"Product","name":"Legacy Product SEO"}');

        $this->getJson('/api/seo-meta-data?url='.urlencode('/products/legacy-category-seo-'.$suffix))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.page_title', 'Legacy Category SEO '.$suffix);
    }

    public function test_daily_price_voice_preview_does_not_update_prices(): void
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Voice Jasmine Test Flower '.$suffix,
            'sku' => 'voice-jasmine-'.$suffix,
            'slug' => 'voice-jasmine-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 KG',
            'sell_price' => 100,
            'list_price' => 140,
            'cost_price' => 70,
            'qty' => 10,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createAdminWithAbilities(['dailyprices_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->postJson(route('admin.dailyprices.voice.preview'), [
                'command' => 'Update Voice Jasmine Test Flower '.$suffix.' price to 250',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('rows.0.weight_id', $weightId)
            ->assertJsonPath('rows.0.new_sell_price', '250.00')
            ->assertJsonPath('rows.0.new_list_price', '140.00');

        $this->assertDatabaseHas('product_weights', [
            'id' => $weightId,
            'sell_price' => 100,
            'list_price' => 140,
        ]);
    }

    public function test_daily_price_voice_apply_updates_prices_and_logs_change(): void
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Voice Rose Test Flower '.$suffix,
            'sku' => 'voice-rose-'.$suffix,
            'slug' => 'voice-rose-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => 'Each Bunch',
            'sell_price' => 200,
            'list_price' => 260,
            'cost_price' => 120,
            'qty' => 20,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createAdminWithAbilities(['dailyprices_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->postJson(route('admin.dailyprices.voice.apply'), [
                'command' => 'Increase Voice Rose Test Flower '.$suffix.' prices by 10 percent',
                'weight_ids' => [$weightId],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('updated', 1)
            ->assertJsonPath('rows.0.new_sell_price', '220.00')
            ->assertJsonPath('rows.0.new_list_price', '260.00');

        $this->assertDatabaseHas('product_weights', [
            'id' => $weightId,
            'sell_price' => 220,
            'list_price' => 260,
        ]);

        $this->assertDatabaseHas('price_update_logs', [
            'product_weight_id' => $weightId,
            'update_source' => 'voice',
            'old_sell_price' => 200,
            'new_sell_price' => 220,
        ]);
    }

    public function test_daily_price_voice_category_command_can_preview_list_price_changes(): void
    {
        $suffix = Str::lower(Str::random(10));
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Voice Bouquets Test '.$suffix,
            'title' => 'Voice Bouquets Test '.$suffix,
            'slug' => 'voice-bouquets-test-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'title' => 'Voice Category Flower '.$suffix,
            'sku' => 'voice-category-'.$suffix,
            'slug' => 'voice-category-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_product')->insert([
            'product_id' => $productId,
            'category_id' => $categoryId,
        ]);

        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => 'Premium Bunch',
            'sell_price' => 100,
            'list_price' => 150,
            'cost_price' => 60,
            'qty' => 10,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createAdminWithAbilities(['dailyprices_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->postJson(route('admin.dailyprices.voice.preview'), [
                'command' => 'Reduce category Voice Bouquets Test '.$suffix.' prices by 5 percent',
                'apply_list_price' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('rows.0.weight_id', $weightId)
            ->assertJsonPath('rows.0.new_sell_price', '95.00')
            ->assertJsonPath('rows.0.new_list_price', '142.50');
    }

    public function test_daily_price_status_api_reports_updated_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:15:00', config('app.timezone')));
        $updatedAt = Carbon::parse('2026-09-14 08:30:00', config('app.timezone'));

        DB::table('price_update_logs')->insert([
            'product_title' => 'Status Test Flower',
            'weight_name' => '1 KG',
            'old_sell_price' => 100,
            'new_sell_price' => 120,
            'old_list_price' => 130,
            'new_list_price' => 150,
            'update_source' => 'manual',
            'notes' => 'Status API test',
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);

        $this->getJson('/api/daily-price-status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_updated_today', true)
            ->assertJsonPath('data.status_text', 'Prices Updated Today')
            ->assertJsonPath('data.status_type', 'updated')
            ->assertJsonPath('data.updated_time', '08:30 AM')
            ->assertJsonPath('data.last_updated_relative', 'today');

        Carbon::setTestNow();
    }

    public function test_daily_price_status_api_reports_not_updated_today_with_relative_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:15:00', config('app.timezone')));
        $updatedAt = Carbon::parse('2026-09-10 08:30:00', config('app.timezone'));

        DB::table('price_update_logs')->insert([
            'product_title' => 'Old Status Test Flower',
            'weight_name' => '1 KG',
            'old_sell_price' => 100,
            'new_sell_price' => 120,
            'old_list_price' => 130,
            'new_list_price' => 150,
            'update_source' => 'manual',
            'notes' => 'Old status API test',
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);

        $this->getJson('/api/daily-price-status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_updated_today', false)
            ->assertJsonPath('data.status_text', 'Prices Not Updated Today')
            ->assertJsonPath('data.status_type', 'not_updated')
            ->assertJsonPath('data.updated_time', null)
            ->assertJsonPath('data.last_updated_relative', '2 days ago');

        Carbon::setTestNow();
    }

    public function test_daily_price_status_api_reports_yesterday_relative_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-14 09:15:00', config('app.timezone')));
        $updatedAt = Carbon::parse('2026-09-13 08:30:00', config('app.timezone'));

        DB::table('price_update_logs')->insert([
            'product_title' => 'Yesterday Status Test Flower',
            'weight_name' => '1 KG',
            'old_sell_price' => 100,
            'new_sell_price' => 120,
            'old_list_price' => 130,
            'new_list_price' => 150,
            'update_source' => 'manual',
            'notes' => 'Yesterday status API test',
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);

        $this->getJson('/api/daily-price-status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_updated_today', false)
            ->assertJsonPath('data.status_text', 'Prices Not Updated Today')
            ->assertJsonPath('data.status_type', 'not_updated')
            ->assertJsonPath('data.updated_time', null)
            ->assertJsonPath('data.last_updated_relative', '1 day ago');

        Carbon::setTestNow();
    }

    private function createAdminWithAbilities(array $abilities): Admin
    {
        $role = Role::create([
            'name' => 'Test Role '.Str::uuid(),
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
            'name' => 'Access Test Admin',
            'email' => 'access-'.Str::uuid().'@example.test',
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
