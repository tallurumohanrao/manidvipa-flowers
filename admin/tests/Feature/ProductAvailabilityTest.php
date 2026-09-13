<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
