<?php

namespace Tests\Feature;

use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Models\User;
use App\Traits\ShippingChargeTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShippingChargeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_shipping_rules_use_distance_order_amount_and_free_shipping(): void
    {
        $this->seedShippingPrices();

        $calculator = new class {
            use ShippingChargeTrait {
                calculateShippingCharge as public calculate;
            }
        };

        $paidDelivery = $calculator->calculate(4.5, 500);
        $this->assertTrue($paidDelivery['available']);
        $this->assertSame('Upto 5 KM', $paidDelivery['title']);
        $this->assertSame(30.0, $paidDelivery['amount']);

        $freeDelivery = $calculator->calculate(4.5, 1300);
        $this->assertTrue($freeDelivery['available']);
        $this->assertSame('Free Shipping', $freeDelivery['title']);
        $this->assertSame(0.0, $freeDelivery['amount']);

        $belowMinimum = $calculator->calculate(4.5, 100);
        $this->assertFalse($belowMinimum['available']);
        $this->assertStringContainsString('Minimum order amount', $belowMinimum['message']);

        $outsideServiceArea = $calculator->calculate(61, 1300);
        $this->assertFalse($outsideServiceArea['available']);
        $this->assertStringContainsString('Current service range is up to 60 KM', $outsideServiceArea['message']);
    }

    public function test_cart_and_order_store_apply_same_delivery_charge(): void
    {
        Config::set('VAT_AMOUNT', 0);
        $this->seedShippingPrices();
        $this->mockDistance(4.5);

        $suffix = Str::lower(Str::random(10));
        $cartSession = 'shipping-cart-'.$suffix;
        $user = User::create([
            'name' => 'Shipping Charge User',
            'email' => 'shipping-charge-'.$suffix.'@example.test',
            'mobile' => '96'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => Hash::make('UserPassword123!'),
            'status' => 1,
        ]);
        $addressId = $this->createAddress($user->id, $suffix);
        $product = $this->createCartProduct($cartSession, $user->id, 500, $suffix);

        Sanctum::actingAs($user);

        $this->getJson('/api/get-cart?cart_session='.$cartSession.'&address_id='.$addressId)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('totals.shipping.title', 'Upto 5 KM')
            ->assertJsonPath('totals.shipping.amount', 30)
            ->assertJsonPath('totals.total.amount', 530);

        $response = $this->postJson('/api/store-order?'.http_build_query([
            'address_id' => $addressId,
            'cart_session' => $cartSession,
            'payment_method' => 'cod',
            'serve_date' => now()->addDay()->toDateString(),
            'serve_time_slot' => '6-9',
            'serve_time_slot_label' => '6 AM - 9 AM',
        ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $orderId = $response->json('data.order_id');
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'sub_total' => 500,
            'amount' => 530,
        ]);
        $this->assertDatabaseHas('order_lineitems', [
            'order_id' => $orderId,
            'title' => 'Upto 5 KM',
            'amount' => 30,
            'weight' => 3,
        ]);
        $this->assertDatabaseHas('order_shippings', [
            'order_id' => $orderId,
            'name' => 'Upto 5 KM',
            'amount' => 30,
        ]);
        $this->assertDatabaseHas('product_weights', [
            'id' => $product['weight_id'],
            'qty' => 4,
        ]);
    }

    public function test_order_store_blocks_unserviceable_delivery_distance(): void
    {
        Config::set('VAT_AMOUNT', 0);
        $this->seedShippingPrices();
        $this->mockDistance(61);

        $suffix = Str::lower(Str::random(10));
        $cartSession = 'shipping-outside-'.$suffix;
        $user = User::create([
            'name' => 'Outside Area User',
            'email' => 'outside-area-'.$suffix.'@example.test',
            'mobile' => '97'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => Hash::make('UserPassword123!'),
            'status' => 1,
        ]);
        $addressId = $this->createAddress($user->id, $suffix);
        $product = $this->createCartProduct($cartSession, $user->id, 1300, $suffix);

        Sanctum::actingAs($user);

        $this->postJson('/api/store-order?'.http_build_query([
            'address_id' => $addressId,
            'cart_session' => $cartSession,
            'payment_method' => 'cod',
            'serve_date' => now()->addDay()->toDateString(),
            'serve_time_slot' => '6-9',
            'serve_time_slot_label' => '6 AM - 9 AM',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('distance', 61);

        $this->assertDatabaseHas('product_weights', [
            'id' => $product['weight_id'],
            'qty' => 5,
        ]);
    }

    private function seedShippingPrices(): void
    {
        DB::table('shipping_prices')->delete();
        $now = now();

        DB::table('shipping_prices')->insert([
            [
                'name' => 'freeshipping',
                'title' => 'Free Shipping',
                'from_km' => null,
                'to_km' => null,
                'min_order_amount' => 1200,
                'max_order_amount' => 100000,
                'shipping_amount' => 0,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'upto5km',
                'title' => 'Upto 5 KM',
                'from_km' => 0,
                'to_km' => 5,
                'min_order_amount' => 300,
                'max_order_amount' => 100000,
                'shipping_amount' => 30,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '5to10km',
                'title' => '5 to 10 KM',
                'from_km' => 5,
                'to_km' => 10,
                'min_order_amount' => 1,
                'max_order_amount' => 100000,
                'shipping_amount' => 60,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '50to60km',
                'title' => '50 to 60 KM',
                'from_km' => 50,
                'to_km' => 60,
                'min_order_amount' => 1,
                'max_order_amount' => 100000,
                'shipping_amount' => 500,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    private function mockDistance(float $distance): void
    {
        $this->app->bind(CartController::class, fn () => new class($distance) extends CartController {
            public function __construct(private float $distance)
            {
            }

            public function getDistance($address_id): array
            {
                return ['status' => 'success', 'distance' => $this->distance];
            }
        });

        $this->app->bind(OrderController::class, fn () => new class($distance) extends OrderController {
            public function __construct(private float $distance)
            {
            }

            public function getDistance($address_id): array
            {
                return ['status' => 'success', 'distance' => $this->distance];
            }
        });
    }

    private function createAddress(int $userId, string $suffix): int
    {
        return DB::table('addresses')->insertGetId([
            'user_id' => $userId,
            'full_name' => 'Shipping User',
            'email' => 'shipping-address-'.$suffix.'@example.test',
            'phone_number' => '9876543210',
            'address_line1' => 'Test Address Line 1',
            'address_line2' => 'Test Address Line 2',
            'landmark' => 'Near Test Landmark',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'country' => 'India',
            'pincode' => '500038',
            'address_type' => 1,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCartProduct(string $cartSession, int $userId, int $sellPrice, string $suffix): array
    {
        $productId = DB::table('products')->insertGetId([
            'title' => 'Shipping Test Flower '.$suffix,
            'sku' => 'shipping-test-'.$suffix,
            'slug' => 'shipping-test-'.$suffix,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 Kg',
            'sell_price' => $sellPrice,
            'list_price' => $sellPrice + 20,
            'qty' => 5,
            'stock' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('carts')->insert([
            'user_id' => $userId,
            'cart_session' => $cartSession,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'product_id' => $productId,
            'weight_id' => $weightId,
        ];
    }
}
