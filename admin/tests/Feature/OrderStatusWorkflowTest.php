<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderStatusWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_order_filters_and_checkout_status_detail_load(): void
    {
        $checkoutStatusId = $this->statusId('order_statuses', 'Checkout');
        $pendingShippingId = $this->statusId('shipping_statuses', 'Pending');
        $order = $this->createOrder([
            'order_status_id' => $checkoutStatusId,
            'shipping_status_id' => $pendingShippingId,
        ]);
        $admin = $this->createAdminWithAbilities(['orders_view']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.index', [
                'orderId' => $order['order_id'],
                'orderStatus' => $checkoutStatusId,
                'shippingStatus' => $pendingShippingId,
            ]))
            ->assertOk()
            ->assertSee((string) $order['order_id']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.show', $order['order_id']))
            ->assertOk()
            ->assertSee('Checkout');
    }

    public function test_admin_non_cancel_status_update_does_not_cancel_shipping_or_return_stock(): void
    {
        $pendingStatusId = $this->statusId('order_statuses', 'Pending');
        $acceptedStatusId = $this->statusId('order_statuses', 'Accepted');
        $pendingShippingId = $this->statusId('shipping_statuses', 'Pending');
        $order = $this->createOrder([
            'order_status_id' => $pendingStatusId,
            'shipping_status_id' => $pendingShippingId,
            'weight_quantity' => 8,
            'order_quantity' => 2,
        ]);
        $admin = $this->createAdminWithAbilities(['orders_view', 'orders_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateBooking', $order['order_id']), [
                'order_status' => $acceptedStatusId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('html', 'Accepted');

        $this->assertDatabaseHas('orders', [
            'id' => $order['order_id'],
            'order_status_id' => $acceptedStatusId,
        ]);
        $this->assertDatabaseHas('order_shippings', [
            'order_id' => $order['order_id'],
            'shipping_status_id' => $pendingShippingId,
        ]);
        $this->assertDatabaseHas('product_weights', [
            'id' => $order['weight_id'],
            'qty' => 8,
        ]);
    }

    public function test_admin_cancel_status_cancels_shipping_and_returns_stock_once(): void
    {
        $pendingStatusId = $this->statusId('order_statuses', 'Pending');
        $cancelledStatusId = $this->statusId('order_statuses', 'Cancelled');
        $pendingShippingId = $this->statusId('shipping_statuses', 'Pending');
        $cancelledShippingId = $this->statusId('shipping_statuses', 'Cancelled');
        $order = $this->createOrder([
            'order_status_id' => $pendingStatusId,
            'shipping_status_id' => $pendingShippingId,
            'weight_quantity' => 8,
            'order_quantity' => 2,
        ]);
        $admin = $this->createAdminWithAbilities(['orders_view', 'orders_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateBooking', $order['order_id']), [
                'order_status' => $cancelledStatusId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('html', 'Cancelled');

        $this->assertDatabaseHas('orders', [
            'id' => $order['order_id'],
            'order_status_id' => $cancelledStatusId,
        ]);
        $this->assertDatabaseHas('order_shippings', [
            'order_id' => $order['order_id'],
            'shipping_status_id' => $cancelledShippingId,
        ]);
        $this->assertDatabaseHas('product_weights', [
            'id' => $order['weight_id'],
            'qty' => 10,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateBooking', $order['order_id']), [
                'order_status' => $cancelledStatusId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_weights', [
            'id' => $order['weight_id'],
            'qty' => 10,
        ]);
    }

    public function test_admin_status_updates_validate_selected_values(): void
    {
        $order = $this->createOrder();
        $admin = $this->createAdminWithAbilities(['orders_view', 'orders_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateBooking', $order['order_id']), [
                'order_status' => 999999,
            ])
            ->assertStatus(422);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateShipping', $order['order_id']), [
                'shipping_status' => 999999,
            ])
            ->assertStatus(422);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updatePayment', $order['order_id']), [
                'payment_status' => 'Not A Real Status',
                'payment_amount' => 10,
            ])
            ->assertStatus(422);
    }

    public function test_admin_shipping_status_update_changes_delivery_status_only(): void
    {
        $pendingOrderStatusId = $this->statusId('order_statuses', 'Pending');
        $pendingShippingId = $this->statusId('shipping_statuses', 'Pending');
        $dispatchedShippingId = $this->statusId('shipping_statuses', 'Dispatched');
        $order = $this->createOrder([
            'order_status_id' => $pendingOrderStatusId,
            'shipping_status_id' => $pendingShippingId,
        ]);
        $admin = $this->createAdminWithAbilities(['orders_view', 'orders_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateShipping', $order['order_id']), [
                'shipping_status' => $dispatchedShippingId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('html', 'Dispatched');

        $this->assertDatabaseHas('order_shippings', [
            'order_id' => $order['order_id'],
            'shipping_status_id' => $dispatchedShippingId,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order['order_id'],
            'order_status_id' => $pendingOrderStatusId,
        ]);
    }

    public function test_admin_workflow_assigns_staff_updates_statuses_and_logs_timeline(): void
    {
        $acceptedOrderStatusId = $this->statusId('order_statuses', 'Accepted');
        $pendingShippingId = $this->statusId('shipping_statuses', 'Pending');
        $outForDeliveryShippingId = $this->statusId('shipping_statuses', 'Out for Delivery');
        $order = $this->createOrder([
            'order_status_id' => $this->statusId('order_statuses', 'Pending'),
            'shipping_status_id' => $pendingShippingId,
        ]);
        $admin = $this->createAdminWithAbilities(['orders_view', 'orders_edit']);
        $packingAdmin = $this->createStandaloneAdmin('Packing Staff');
        $deliveryAdmin = $this->createStandaloneAdmin('Delivery Staff');

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateWorkflow', $order['order_id']), [
                'order_status' => $acceptedOrderStatusId,
                'accepted_by_admin_id' => $admin->id,
                'packing_admin_id' => $packingAdmin->id,
                'packing_status' => 'ready_for_dispatch',
                'delivery_admin_id' => $deliveryAdmin->id,
                'shipping_status' => $outForDeliveryShippingId,
                'workflow_note' => 'Packed and handed to delivery staff.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'id' => $order['order_id'],
            'order_status_id' => $acceptedOrderStatusId,
            'accepted_by_admin_id' => $admin->id,
            'packing_admin_id' => $packingAdmin->id,
            'delivery_admin_id' => $deliveryAdmin->id,
            'packing_status' => 'ready_for_dispatch',
        ]);
        $this->assertDatabaseHas('order_shippings', [
            'order_id' => $order['order_id'],
            'shipping_status_id' => $outForDeliveryShippingId,
        ]);
        $this->assertDatabaseHas('order_workflow_events', [
            'order_id' => $order['order_id'],
            'event_type' => 'packing_staff',
            'to_value' => $packingAdmin->name,
        ]);
        $this->assertDatabaseHas('order_workflow_events', [
            'order_id' => $order['order_id'],
            'event_type' => 'delivery_staff',
            'to_value' => $deliveryAdmin->name,
        ]);
        $this->assertDatabaseHas('order_workflow_events', [
            'order_id' => $order['order_id'],
            'event_type' => 'packing_status',
            'to_value' => 'Ready for Dispatch',
        ]);
        $this->assertDatabaseHas('order_workflow_events', [
            'order_id' => $order['order_id'],
            'event_type' => 'shipping_status',
            'to_value' => 'Out for Delivery',
        ]);
        $this->assertDatabaseHas('order_workflow_events', [
            'order_id' => $order['order_id'],
            'event_type' => 'workflow_note',
            'note' => 'Packed and handed to delivery staff.',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.show', $order['order_id']))
            ->assertOk()
            ->assertSee('Order Workflow Timeline')
            ->assertSee($packingAdmin->name)
            ->assertSee($deliveryAdmin->name)
            ->assertSee('Ready for Dispatch')
            ->assertSee('Out for Delivery');
    }

    public function test_customer_cancel_order_uses_real_statuses_and_returns_stock_once(): void
    {
        $suffix = Str::lower(Str::random(10));
        $user = User::create([
            'name' => 'Order Status User',
            'email' => 'order-status-user-'.$suffix.'@example.test',
            'mobile' => '95'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'OrderStatusPassword123!',
            'status' => 1,
        ]);
        $pendingStatusId = $this->statusId('order_statuses', 'Pending');
        $cancelledStatusId = $this->statusId('order_statuses', 'Cancelled');
        $pendingShippingId = $this->statusId('shipping_statuses', 'Pending');
        $cancelledShippingId = $this->statusId('shipping_statuses', 'Cancelled');
        $order = $this->createOrder([
            'user_id' => $user->id,
            'order_status_id' => $pendingStatusId,
            'shipping_status_id' => $pendingShippingId,
            'weight_quantity' => 3,
            'order_quantity' => 2,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/cancel-order?order_id='.$order['order_id'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'id' => $order['order_id'],
            'order_status_id' => $cancelledStatusId,
        ]);
        $this->assertDatabaseHas('order_shippings', [
            'order_id' => $order['order_id'],
            'shipping_status_id' => $cancelledShippingId,
        ]);
        $this->assertDatabaseHas('product_weights', [
            'id' => $order['weight_id'],
            'qty' => 5,
        ]);

        $this->postJson('/api/cancel-order?order_id='.$order['order_id'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_weights', [
            'id' => $order['weight_id'],
            'qty' => 5,
        ]);
    }

    public function test_whatsapp_order_reserves_stock_and_cancel_returns_it_once(): void
    {
        $suffix = Str::lower(Str::random(10));
        $cartSession = 'whatsapp-stock-'.$suffix;
        $now = now();
        $cancelledStatusId = $this->statusId('order_statuses', 'Cancelled');

        $productId = DB::table('products')->insertGetId([
            'title' => 'WhatsApp Stock Test Flower '.$suffix,
            'sku' => 'whatsapp-stock-'.$suffix,
            'slug' => 'whatsapp-stock-'.$suffix,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 Kg',
            'sell_price' => 300,
            'list_price' => 320,
            'qty' => 3,
            'stock' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('carts')->insert([
            'cart_session' => $cartSession,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'quantity' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->postJson('/api/store-whatsapp-order', [
            'cart_session' => $cartSession,
            'name' => 'WhatsApp Stock Customer',
            'email' => 'whatsapp-stock-'.$suffix.'@example.test',
            'contact_number' => '9876543210',
            'serve_date' => now()->addDay()->toDateString(),
            'serve_time_slot_label' => '9:00 AM - 10:00 AM',
        ])->assertOk()->assertJsonPath('success', true);

        $orderId = $response->json('data.order_id');
        $this->assertDatabaseHas('product_weights', [
            'id' => $weightId,
            'qty' => 1,
        ]);
        $this->assertDatabaseMissing('carts', [
            'cart_session' => $cartSession,
        ]);

        $admin = $this->createAdminWithAbilities(['orders_view', 'orders_edit']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateBooking', $orderId), [
                'order_status' => $cancelledStatusId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_weights', [
            'id' => $weightId,
            'qty' => 3,
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.updateBooking', $orderId), [
                'order_status' => $cancelledStatusId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_weights', [
            'id' => $weightId,
            'qty' => 3,
        ]);
    }

    private function createOrder(array $overrides = []): array
    {
        $suffix = Str::lower(Str::random(10));
        $orderStatusId = $overrides['order_status_id'] ?? $this->statusId('order_statuses', 'Pending');
        $shippingStatusId = $overrides['shipping_status_id'] ?? $this->statusId('shipping_statuses', 'Pending');
        $weightQuantity = $overrides['weight_quantity'] ?? 10;
        $orderQuantity = $overrides['order_quantity'] ?? 1;
        $now = now();

        $productId = DB::table('products')->insertGetId([
            'title' => 'Order Status Test Flower '.$suffix,
            'sku' => 'order-status-'.$suffix,
            'slug' => 'order-status-'.$suffix,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $weightId = DB::table('product_weights')->insertGetId([
            'product_id' => $productId,
            'name' => '1 Kg',
            'sell_price' => 300,
            'list_price' => 320,
            'qty' => $weightQuantity,
            'stock' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_encrypt_key' => Str::random(40),
            'user_id' => $overrides['user_id'] ?? null,
            'name' => 'Order Status Test Customer',
            'email' => 'order-status-'.$suffix.'@example.test',
            'contact_number' => '9876543210',
            'serve_date' => now()->addDay()->toDateString(),
            'amount' => 300 * $orderQuantity,
            'sub_total' => 300 * $orderQuantity,
            'order_status_id' => $orderStatusId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('order_products')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'weight_id' => $weightId,
            'product_title' => 'Order Status Test Flower '.$suffix,
            'weight' => '1 Kg',
            'sku' => 'order-status-'.$suffix,
            'amount' => 300 * $orderQuantity,
            'sell_price' => 300,
            'list_price' => 320,
            'quantity' => $orderQuantity,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('order_shippings')->insert([
            'name' => 'Local Delivery',
            'amount' => 0,
            'order_id' => $orderId,
            'shipping_status_id' => $shippingStatusId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('order_payments')->insert([
            'payment_method' => 'Cash On Delivery',
            'payment_status' => 'Pending',
            'payment_amount' => null,
            'order_id' => $orderId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'order_id' => $orderId,
            'product_id' => $productId,
            'weight_id' => $weightId,
        ];
    }

    private function statusId(string $table, string $name): int
    {
        $id = DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');
        if($id){
            return (int) $id;
        }

        return DB::table($table)->insertGetId([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAdminWithAbilities(array $abilities): Admin
    {
        $role = Role::create([
            'name' => 'Order Status Test Role '.Str::uuid(),
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
            'name' => 'Order Status Test Admin',
            'email' => 'order-status-admin-'.Str::uuid().'@example.test',
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

    private function createStandaloneAdmin(string $name): Admin
    {
        return Admin::create([
            'name' => $name.' '.Str::uuid(),
            'email' => Str::slug($name).'-'.Str::uuid().'@example.test',
            'password' => Hash::make('AdminPassword123!'),
            'status' => 1,
        ]);
    }
}
