<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PriceVisibility;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_enquiry_only_product_hides_prices_and_cannot_be_added_to_cart(): void
    {
        [$productId, $weightId, $slug] = $this->createPriceVisibilityProduct('enquiry_only');

        $listing = $this->getJson('/api/products-by-category?category_slug=all-flowers&per_page=200');
        $listing->assertOk();
        $listedProduct = collect($listing->json('data.data'))->firstWhere('id', $productId);

        $this->assertNotNull($listedProduct);
        $this->assertFalse($listedProduct['show_price']);
        $this->assertFalse($listedProduct['can_purchase']);
        $this->assertNull($listedProduct['sell_price']);
        $this->assertNull($listedProduct['weights'][0]['sell_price']);

        $this->getJson('/api/product-details?product_slug='.$slug)
            ->assertOk()
            ->assertJsonPath('data.show_price', false)
            ->assertJsonPath('data.can_purchase', false)
            ->assertJsonPath('weights.0.sell_price', null);

        $cartSession = 'visibility-'.Str::random(10);
        $this->postJson('/api/add-to-cart', [
            'cart_session' => $cartSession,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 1,
        ])->assertStatus(422)->assertJsonPath('success', false);

        DB::table('carts')->insert([
            'cart_session' => $cartSession,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/get-cart?cart_session='.$cartSession)
            ->assertOk()
            ->assertJsonPath('cart_count', 0)
            ->assertJsonPath('totals.sub_total.amount', 0);

        $this->postJson('/api/store-whatsapp-order', [
            'cart_session' => $cartSession,
            'name' => 'Visibility Test Customer',
            'contact_number' => '9000000000',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_details_only_and_selection_modes_expose_prices_only_in_their_allowed_context(): void
    {
        [$detailsProductId, , $detailsSlug] = $this->createPriceVisibilityProduct('details_only');
        [, , $selectionSlug] = $this->createPriceVisibilityProduct('show_after_selection');

        $listing = $this->getJson('/api/products-by-category?category_slug=all-flowers&per_page=200');
        $detailsListing = collect($listing->json('data.data'))->firstWhere('id', $detailsProductId);
        $this->assertFalse($detailsListing['show_price']);
        $this->assertTrue($detailsListing['can_purchase']);
        $this->assertNull($detailsListing['sell_price']);

        $this->getJson('/api/product-details?product_slug='.$detailsSlug)
            ->assertOk()
            ->assertJsonPath('data.show_price', true)
            ->assertJsonPath('data.can_purchase', true)
            ->assertJsonPath('weights.0.sell_price', '321.00');

        $this->getJson('/api/product-details?product_slug='.$selectionSlug)
            ->assertOk()
            ->assertJsonPath('data.show_price', false)
            ->assertJsonPath('data.can_purchase', true)
            ->assertJsonPath('data.price_visibility', 'show_after_selection')
            ->assertJsonPath('weights.0.sell_price', '321.00');
    }

    public function test_scheduled_price_reveal_and_subscription_default_are_enforced(): void
    {
        [, , $slug] = $this->createPriceVisibilityProduct('enquiry_only', now()->subMinute());

        $this->getJson('/api/product-details?product_slug='.$slug)
            ->assertOk()
            ->assertJsonPath('data.price_visibility', 'show_everywhere')
            ->assertJsonPath('data.show_price', true)
            ->assertJsonPath('data.can_purchase', true)
            ->assertJsonPath('weights.0.sell_price', '321.00');

        DB::table('settings')->where('key', 'SUBSCRIPTION_PRICE_VISIBILITY_DEFAULT')->update(['value' => 'enquiry_only']);
        PriceVisibility::clearSettingsCache();
        Cache::forget('api_subscription_plans');

        $planId = DB::table('subscription_plans')->insertGetId([
            'title' => 'Private Price Plan '.Str::random(6),
            'slug' => 'private-price-plan-'.Str::lower(Str::random(8)),
            'business_type' => 'Corporate Offices',
            'subscription_type' => 'Premium Arrangements',
            'starting_price' => 14999,
            'billing_cycle' => 'Monthly',
            'is_featured' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/subscription-plans')->assertOk();
        $plan = collect($response->json('data'))->firstWhere('id', $planId);
        $this->assertNotNull($plan);
        $this->assertFalse($plan['show_price']);
        $this->assertNull($plan['starting_price']);
        $this->assertSame('Contact us for price', $plan['price_label']);
    }

    public function test_structured_flower_quantity_supports_presets_custom_pricing_and_base_unit_stock_checks(): void
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Lotus Quantity Test',
            'sku' => 'lotus-'.$suffix,
            'slug' => 'lotus-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $flowerUnitId = DB::table('measurement_units')->where('code', 'flower')->value('id');
        $inventoryPoolId = DB::table('product_inventory_pools')->insertGetId([
            'product_id' => $productId,
            'unit_id' => $flowerUnitId,
            'qty' => 2000,
            'track_stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '108 Lotus Flowers',
            'quantity_value' => 108,
            'quantity_unit' => 'flower',
            'unit_id' => $flowerUnitId,
            'inventory_pool_id' => $inventoryPoolId,
            'pricing_mode' => 'automatic',
            'unit_sell_price' => 5,
            'unit_list_price' => 6,
            'unit_cost_price' => 3,
            'sell_price' => 540,
            'list_price' => 648,
            'cost_price' => 324,
            'allow_custom_quantity' => 1,
            'minimum_custom_quantity' => 1,
            'maximum_custom_quantity' => 1008,
            'custom_quantity_step' => 1,
            'qty' => 999999,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $details = $this->getJson('/api/product-details?product_slug=lotus-'.$suffix)->assertOk();
        $details->assertJsonPath('weights.0.display_name', '108 Lotus Flowers')
            ->assertJsonPath('weights.0.is_structured', true)
            ->assertJsonPath('weights.0.allow_custom_quantity', true);
        $this->assertArrayNotHasKey('cost_price', $details->json('weights.0'));
        $this->assertArrayNotHasKey('unit_cost_price', $details->json('weights.0'));

        $presetSession = 'preset-'.$suffix;
        $this->postJson('/api/add-to-cart', [
            'cart_session' => $presetSession,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 1,
        ])->assertOk()->assertJsonPath('success', true);
        $presetCart = $this->getJson('/api/get-cart?cart_session='.$presetSession)->assertOk();
        $this->assertSame('108 Lotus Flowers', $presetCart->json('data.0.weight'));
        $this->assertEquals(540, $presetCart->json('totals.sub_total.amount'));

        $customSession = 'custom-'.$suffix;
        $this->postJson('/api/add-to-cart', [
            'cart_session' => $customSession,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 1,
            'custom_quantity' => 125,
        ])->assertOk()->assertJsonPath('success', true);
        $customCart = $this->getJson('/api/get-cart?cart_session='.$customSession)->assertOk();
        $this->assertSame('125 Flowers', $customCart->json('data.0.weight'));
        $this->assertEquals(625, $customCart->json('totals.sub_total.amount'));

        $this->postJson('/api/add-to-cart', [
            'cart_session' => 'invalid-'.$suffix,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 1,
            'custom_quantity' => 1009,
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->postJson('/api/add-to-cart', [
            'cart_session' => 'stock-'.$suffix,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 19,
        ])->assertOk()->assertJsonPath('success', false);
    }

    public function test_default_selling_option_is_consistent_in_listing_and_details(): void
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Default Option Test '.$suffix,
            'sku' => 'default-option-'.$suffix,
            'slug' => 'default-option-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $defaultId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 KG',
            'quantity_value' => 1,
            'quantity_unit' => 'kg',
            'sell_price' => 500,
            'list_price' => 550,
            'qty' => 10,
            'stock' => 1,
            'is_default' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '250 Grams',
            'quantity_value' => 250,
            'quantity_unit' => 'gram',
            'sell_price' => 200,
            'list_price' => 220,
            'qty' => 20,
            'stock' => 1,
            'is_default' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listingProduct = collect($this->getJson('/api/products-by-category?category_slug=all-flowers&per_page=200')
            ->assertOk()->json('data.data'))->firstWhere('id', $productId);
        $this->assertSame($defaultId, $listingProduct['default_weight_id']);
        $this->assertSame($defaultId, $listingProduct['weights'][0]['id']);
        $this->assertSame('1 KG', $listingProduct['default_weight_label']);

        $details = $this->getJson('/api/product-details?product_slug=default-option-'.$suffix)
            ->assertOk();
        $details->assertJsonPath('data.default_weight_id', $defaultId)
            ->assertJsonPath('weights.0.id', $defaultId)
            ->assertJsonPath('weights.1.id', $otherId)
            ->assertJsonPath('data.default_weight_label', '1 KG');
    }

    private function createPriceVisibilityProduct(string $mode, $visibleFrom = null): array
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Visibility Test Flower '.$suffix,
            'sku' => 'visibility-'.$suffix,
            'slug' => 'visibility-'.$suffix,
            'price_visibility' => $mode,
            'price_visible_from' => $visibleFrom,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 Kg',
            'sell_price' => 321,
            'list_price' => 350,
            'cost_price' => 200,
            'qty' => 10,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$productId, $weightId, 'visibility-'.$suffix];
    }

    public function test_kg_and_gram_options_use_one_shared_gram_inventory(): void
    {
        $suffix = Str::lower(Str::random(10));
        $now = now();
        $productId = DB::table('products')->insertGetId([
            'title' => 'Shared Weight Inventory '.$suffix,
            'sku' => 'shared-weight-'.$suffix,
            'slug' => 'shared-weight-'.$suffix,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $gramId = DB::table('measurement_units')->where('code', 'gram')->value('id');
        $kgId = DB::table('measurement_units')->where('code', 'kg')->value('id');
        $poolId = DB::table('product_inventory_pools')->insertGetId([
            'product_id' => $productId,
            'unit_id' => $gramId,
            'qty' => 1500,
            'track_stock' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $kgOptionId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 KG',
            'quantity_value' => 1,
            'quantity_unit' => 'kg',
            'unit_id' => $kgId,
            'inventory_pool_id' => $poolId,
            'pricing_mode' => 'manual',
            'sell_price' => 500,
            'list_price' => 550,
            'qty' => 999999,
            'stock' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $gramOptionId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '500 Grams',
            'quantity_value' => 500,
            'quantity_unit' => 'gram',
            'unit_id' => $gramId,
            'inventory_pool_id' => $poolId,
            'pricing_mode' => 'manual',
            'sell_price' => 275,
            'list_price' => 300,
            'qty' => 1,
            'stock' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $weights = collect($this->getJson('/api/product-details?product_slug=shared-weight-'.$suffix)
            ->assertOk()->json('weights'))->keyBy('id');
        $this->assertEquals(1500, $weights[$kgOptionId]['qty']);
        $this->assertEquals(1000, $weights[$kgOptionId]['required_stock']);
        $this->assertFalse($weights[$kgOptionId]['is_out_of_stock']);
        $this->assertEquals(500, $weights[$gramOptionId]['required_stock']);

        $this->postJson('/api/add-to-cart', [
            'cart_session' => 'kg-too-many-'.$suffix,
            'product_id' => $productId,
            'weight_id' => $kgOptionId,
            'quantity' => 2,
        ])->assertOk()->assertJsonPath('success', false);
        $this->postJson('/api/add-to-cart', [
            'cart_session' => 'grams-exact-'.$suffix,
            'product_id' => $productId,
            'weight_id' => $gramOptionId,
            'quantity' => 3,
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_product_details_prioritize_available_weights_and_cart_accepts_one(): void
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Availability Test Flower',
            'sku' => 'availability-'.$suffix,
            'slug' => 'availability-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outOfStockId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '100 Grams',
            'sell_price' => 100,
            'list_price' => 120,
            'qty' => 0,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $availableId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '250 Grams',
            'sell_price' => 200,
            'list_price' => 220,
            'qty' => 5,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/product-details?product_slug=availability-'.$suffix);
        $response->assertOk();
        $weights = collect($response->json('weights'));
        $availableIndex = $weights->search(fn ($weight) => $weight['id'] === $availableId);
        $outOfStockIndex = $weights->search(fn ($weight) => $weight['id'] === $outOfStockId);

        $this->assertNotFalse($availableIndex);
        $this->assertNotFalse($outOfStockIndex);
        $this->assertLessThan($outOfStockIndex, $availableIndex);
        $this->assertSame(1, $weights[$availableIndex]['stock']);
        $this->assertSame(5, $weights[$availableIndex]['qty']);

        $cartSession = 'test-'.$suffix;
        $this->postJson('/api/add-to-cart', [
            'cart_session' => $cartSession,
            'product_id' => $productId,
            'weight_id' => $availableId,
            'quantity' => 1,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('carts', [
            'cart_session' => $cartSession,
            'product_id' => $productId,
            'weight_id' => $availableId,
            'quantity' => 1,
        ]);
    }

    public function test_cart_rejects_inactive_weight(): void
    {
        $suffix = Str::lower(Str::random(10));
        $productId = DB::table('products')->insertGetId([
            'title' => 'Inactive Weight Test Flower',
            'sku' => 'inactive-'.$suffix,
            'slug' => 'inactive-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 Kg',
            'sell_price' => 300,
            'list_price' => 320,
            'qty' => 10,
            'stock' => 1,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/add-to-cart', [
            'cart_session' => 'test-'.$suffix,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 1,
        ])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_authenticated_cart_delete_ignores_foreign_guest_session(): void
    {
        $suffix = Str::lower(Str::random(10));
        $userData = [
            'name' => 'Cart Owner',
            'email' => 'cart-owner-'.$suffix.'@example.com',
            'password' => 'password',
        ];
        if(Schema::hasColumn('users', 'status')){
            $userData['status'] = 1;
        }
        $user = User::create($userData);
        $productId = DB::table('products')->insertGetId([
            'title' => 'Scoped Cart Flower',
            'sku' => 'scoped-'.$suffix,
            'slug' => 'scoped-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 Kg',
            'sell_price' => 300,
            'list_price' => 320,
            'qty' => 10,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $guestCartId = DB::table('carts')->insertGetId([
            'cart_session' => 'foreign-'.$suffix,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/delete-cart', [
            'cart_id' => $guestCartId,
            'cart_session' => 'foreign-'.$suffix,
        ])->assertOk()->assertJson(['success' => false]);

        $this->assertDatabaseHas('carts', [
            'id' => $guestCartId,
            'cart_session' => 'foreign-'.$suffix,
        ]);
    }

    public function test_customer_password_reset_requires_valid_token_and_active_user(): void
    {
        $suffix = Str::lower(Str::random(10));
        $user = User::create([
            'name' => 'Reset Test User',
            'email' => 'reset-user-'.$suffix.'@example.test',
            'mobile' => '90'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'OldUserPassword123!',
            'status' => 1,
        ]);

        $token = Password::broker('users')->createToken($user);

        $this->postJson('/api/password/update', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'NewUserPassword123!',
            'password_confirmation' => 'NewUserPassword123!',
        ])->assertOk()->assertJsonPath('success', false);

        $this->assertTrue(Hash::check('OldUserPassword123!', $user->fresh()->password));

        $this->postJson('/api/password/update', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewUserPassword123!',
            'password_confirmation' => 'NewUserPassword123!',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewUserPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $inactiveUser = User::create([
            'name' => 'Inactive Reset Test User',
            'email' => 'inactive-reset-user-'.$suffix.'@example.test',
            'mobile' => '91'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'InactiveUserPassword123!',
            'status' => 0,
        ]);
        $inactiveToken = Password::broker('users')->createToken($inactiveUser);

        $this->postJson('/api/password/update', [
            'email' => $inactiveUser->email,
            'token' => $inactiveToken,
            'password' => 'NewInactivePassword123!',
            'password_confirmation' => 'NewInactivePassword123!',
        ])->assertOk()->assertJsonPath('success', false);

        $this->assertTrue(Hash::check('InactiveUserPassword123!', $inactiveUser->fresh()->password));
    }

    public function test_customer_profile_and_password_update_workflows(): void
    {
        $suffix = Str::lower(Str::random(10));
        $user = User::create([
            'name' => 'Account Test User',
            'email' => 'account-user-'.$suffix.'@example.test',
            'mobile' => '92'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'OldUserPassword123!',
            'status' => 1,
        ]);
        $otherUser = User::create([
            'name' => 'Other Account Test User',
            'email' => 'other-account-user-'.$suffix.'@example.test',
            'mobile' => '93'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'OtherUserPassword123!',
            'status' => 1,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/update-profile', [
            'name' => 'Duplicate Email User',
            'email' => $otherUser->email,
            'mobile' => '12345',
        ])->assertStatus(404)->assertJsonPath('success', false);

        $newEmail = 'updated-account-user-'.$suffix.'@example.test';
        $newMobile = '94'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $this->postJson('/api/update-profile', [
            'name' => 'Updated Account User',
            'email' => $newEmail,
            'mobile' => $newMobile,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Account User',
            'email' => $newEmail,
            'mobile' => $newMobile,
        ]);

        $this->postJson('/api/update-user-password', [
            'current_password' => 'WrongUserPassword123!',
            'new_password' => 'NewUserPassword123!',
            'new_password_confirmation' => 'NewUserPassword123!',
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->postJson('/api/update-user-password', [
            'current_password' => 'OldUserPassword123!',
            'new_password' => 'NewUserPassword123!',
            'new_password_confirmation' => 'NewUserPassword123!',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewUserPassword123!', $user->fresh()->password));
    }
}
