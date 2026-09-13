<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateTemplate(
            'order_statuses',
            'Checkout',
            'Order #{{order.id}} is in checkout',
            'Your order #{{order.id}} is in checkout.',
            ['Your Order is in Checkout {{order.id}}'],
            ['Your Order is in Checkout {{order.id}}']
        );

        $this->updateTemplate(
            'order_statuses',
            'Pending',
            'Order #{{order.id}} is pending',
            'Your order #{{order.id}} is pending.',
            ['Your Order is in Pending {{order.id}}'],
            ['Your Order is in Pending {{order.id}}']
        );

        $this->updateTemplate(
            'order_statuses',
            'Accepted',
            'Order #{{order.id}} is accepted',
            'Your order #{{order.id}} is accepted.',
            ['Your Order is Accepted {{order.id}}'],
            ['Your Order is&nbsp;Accepted {{order.id}}']
        );

        $this->updateTemplate(
            'order_statuses',
            'Completed',
            'Order #{{order.id}} is completed - Manidvipa Flowers',
            'Your order #{{order.id}} has been completed successfully.',
            ['Thank you for your order #{{order.id}} - Sri Sri Parinaya'],
            ['Your Order has been placed successfully. Order Id #{{order.id}}.']
        );

        $this->updateTemplate(
            'order_statuses',
            'Cancelled',
            'Order #{{order.id}} is cancelled',
            'Your order #{{order.id}} is cancelled.',
            ['Your Order is Cancelled {{order.id}}'],
            ['Your Order is Cancelled {{order.id}}']
        );

        $this->updateTemplate(
            'shipping_statuses',
            'Pending',
            'Delivery update for order #{{order.id}}',
            'Your order #{{order.id}} delivery is pending.',
            ['Shipping Status Updated {{order.id}}'],
            ['Shipping Status Updated to Pending']
        );

        $this->updateTemplate(
            'shipping_statuses',
            'Dispatched',
            'Order #{{order.id}} has been dispatched',
            "Your order #{{order.id}} has been dispatched.\n\nManidvipa Flowers",
            ['Dispatched'],
            ["Hello ,\r\n\r\nWe are pleased to confirm your order is now dispatched.\r\n\r\nOur Customer Service team is available if you have any questions about your order.\r\n\r\nIf you have any questions about your order or any other matter, please feel free to contact us at sales@manidvipaflowers.com or call us on: 666 888 0000.\r\n\r\n&nbsp;\r\n\r\nManidvipa Flowers"]
        );

        $this->updateTemplate(
            'shipping_statuses',
            'Delivered',
            'Order #{{order.id}} has been delivered',
            'Your order #{{order.id}} has been delivered.',
            ['Delivered'],
            ['Shipping Status Updated &nbsp;to Delivered']
        );

        $this->updateTemplate(
            'shipping_statuses',
            'Cancelled',
            'Order #{{order.id}} delivery is cancelled',
            'Your order #{{order.id}} delivery is cancelled.',
            ['Cancelled'],
            ['']
        );

        $this->updateTemplate(
            'payment_statuses',
            'Checkout',
            'Payment checkout for order #{{order.id}}',
            'Payment for order #{{order.id}} is in checkout.',
            ['Checkout'],
            ['Payment Status in Checkout {{order.id}}']
        );

        $this->updateTemplate(
            'payment_statuses',
            'Pending',
            'Payment pending for order #{{order.id}}',
            'Payment for order #{{order.id}} is pending.',
            ['Pending'],
            ['Payment Status in Pending {{order.id}}']
        );

        $this->updateTemplate(
            'payment_statuses',
            'Completed',
            'Payment completed for order #{{order.id}}',
            'Payment for order #{{order.id}} is completed.',
            ['Completed'],
            ['Completed']
        );

        $this->updateTemplate(
            'payment_statuses',
            'Cancelled',
            'Payment cancelled for order #{{order.id}}',
            'Payment for order #{{order.id}} is cancelled.',
            ['Cancelled'],
            ['Payment Status in Cancelled {{order.id}}']
        );

        $this->updateTemplate(
            'payment_statuses',
            'Refunded',
            'Payment refunded for order #{{order.id}}',
            'Payment for order #{{order.id}} is refunded.',
            ['Refunded'],
            ['Payment Status in Refunded {{order.id}}']
        );
    }

    public function down(): void
    {
        // Keep the normalized defaults rather than reintroducing legacy wording.
    }

    private function updateTemplate(
        string $table,
        string $name,
        string $subject,
        string $bodyHtml,
        array $legacySubjects,
        array $legacyBodies
    ): void {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columns = Schema::getColumnListing($table);
        foreach (['name', 'subject', 'body_html'] as $column) {
            if (! in_array($column, $columns, true)) {
                return;
            }
        }

        $row = DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if (! $row) {
            return;
        }

        $updates = [];
        $legacyMarkers = ['Sri Sri Parinaya', '666 888 0000'];

        if (in_array((string) $row->subject, $legacySubjects, true) || $this->containsLegacyMarker((string) $row->subject, $legacyMarkers)) {
            $updates['subject'] = $subject;
        }

        if (in_array((string) $row->body_html, $legacyBodies, true) || $this->containsLegacyMarker((string) $row->body_html, $legacyMarkers)) {
            $updates['body_html'] = $bodyHtml;
        }

        if (! $updates) {
            return;
        }

        if (in_array('updated_at', $columns, true)) {
            $updates['updated_at'] = now();
        }

        DB::table($table)->where('id', $row->id)->update($updates);
    }

    private function containsLegacyMarker(string $value, array $markers): bool
    {
        foreach ($markers as $marker) {
            if (strpos($value, $marker) !== false) {
                return true;
            }
        }

        return false;
    }
};
