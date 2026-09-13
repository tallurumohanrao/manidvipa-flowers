<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'accepted_by_admin_id')) {
                $table->unsignedBigInteger('accepted_by_admin_id')->nullable()->after('order_status_id')->index();
            }
            if (! Schema::hasColumn('orders', 'packing_admin_id')) {
                $table->unsignedBigInteger('packing_admin_id')->nullable()->after('accepted_by_admin_id')->index();
            }
            if (! Schema::hasColumn('orders', 'delivery_admin_id')) {
                $table->unsignedBigInteger('delivery_admin_id')->nullable()->after('packing_admin_id')->index();
            }
            if (! Schema::hasColumn('orders', 'packing_status')) {
                $table->string('packing_status', 50)->default('not_started')->after('delivery_admin_id')->index();
            }
            if (! Schema::hasColumn('orders', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('packing_status');
            }
            if (! Schema::hasColumn('orders', 'packing_started_at')) {
                $table->timestamp('packing_started_at')->nullable()->after('accepted_at');
            }
            if (! Schema::hasColumn('orders', 'packed_at')) {
                $table->timestamp('packed_at')->nullable()->after('packing_started_at');
            }
            if (! Schema::hasColumn('orders', 'ready_for_dispatch_at')) {
                $table->timestamp('ready_for_dispatch_at')->nullable()->after('packed_at');
            }
            if (! Schema::hasColumn('orders', 'delivery_assigned_at')) {
                $table->timestamp('delivery_assigned_at')->nullable()->after('ready_for_dispatch_at');
            }
            if (! Schema::hasColumn('orders', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable()->after('delivery_assigned_at');
            }
            if (! Schema::hasColumn('orders', 'out_for_delivery_at')) {
                $table->timestamp('out_for_delivery_at')->nullable()->after('dispatched_at');
            }
            if (! Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('out_for_delivery_at');
            }
        });

        if (! Schema::hasTable('order_workflow_events')) {
            Schema::create('order_workflow_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('admin_id')->nullable()->index();
                $table->string('event_type', 80)->index();
                $table->string('from_value')->nullable();
                $table->string('to_value')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureStatus('order_statuses', 'Processing', 'Order #{{order.id}} is being processed', 'Your order #{{order.id}} is being processed.');
        $this->ensureStatus('shipping_statuses', 'Out for Delivery', 'Order #{{order.id}} is out for delivery', 'Your order #{{order.id}} is out for delivery.');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_workflow_events');

        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'delivered_at',
                'out_for_delivery_at',
                'dispatched_at',
                'delivery_assigned_at',
                'ready_for_dispatch_at',
                'packed_at',
                'packing_started_at',
                'accepted_at',
                'packing_status',
                'delivery_admin_id',
                'packing_admin_id',
                'accepted_by_admin_id',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function ensureStatus(string $table, string $name, string $subject, string $bodyHtml): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columns = Schema::getColumnListing($table);
        if (! in_array('name', $columns, true)) {
            return;
        }

        $row = DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($row) {
            return;
        }

        $insert = ['name' => $name];
        if (in_array('subject', $columns, true)) {
            $insert['subject'] = $subject;
        }
        if (in_array('body_html', $columns, true)) {
            $insert['body_html'] = $bodyHtml;
        }
        if (in_array('created_at', $columns, true)) {
            $insert['created_at'] = now();
        }
        if (in_array('updated_at', $columns, true)) {
            $insert['updated_at'] = now();
        }

        DB::table($table)->insert($insert);
    }
};
