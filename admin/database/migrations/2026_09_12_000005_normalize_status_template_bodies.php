<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateBody('order_statuses', 'Checkout', 'Your order #{{order.id}} is in checkout.', [
            'Your Order is in Checkout {{order.id}}',
        ]);
        $this->updateBody('order_statuses', 'Pending', 'Your order #{{order.id}} is pending.', [
            'Your Order is in Pending {{order.id}}',
        ]);
        $this->updateBody('order_statuses', 'Accepted', 'Your order #{{order.id}} is accepted.', [
            'Your Order is&nbsp;Accepted {{order.id}}',
        ]);
        $this->updateBody('order_statuses', 'Completed', 'Your order #{{order.id}} has been completed successfully.', [
            'Your Order has been placed successfully. Order Id #{{order.id}}.',
        ]);
        $this->updateBody('order_statuses', 'Cancelled', 'Your order #{{order.id}} is cancelled.', [
            'Your Order is Cancelled {{order.id}}',
        ]);

        $this->updateBody('shipping_statuses', 'Pending', 'Your order #{{order.id}} delivery is pending.', [
            'Shipping Status Updated to Pending',
        ]);
        $this->updateBody('shipping_statuses', 'Delivered', 'Your order #{{order.id}} has been delivered.', [
            'Shipping Status Updated &nbsp;to Delivered',
        ]);

        $this->updateBody('payment_statuses', 'Checkout', 'Payment for order #{{order.id}} is in checkout.', [
            'Payment Status in Checkout {{order.id}}',
        ]);
        $this->updateBody('payment_statuses', 'Pending', 'Payment for order #{{order.id}} is pending.', [
            'Payment Status in Pending {{order.id}}',
        ]);
        $this->updateBody('payment_statuses', 'Completed', 'Payment for order #{{order.id}} is completed.', [
            'Completed',
        ]);
        $this->updateBody('payment_statuses', 'Cancelled', 'Payment for order #{{order.id}} is cancelled.', [
            'Payment Status in Cancelled {{order.id}}',
        ]);
        $this->updateBody('payment_statuses', 'Refunded', 'Payment for order #{{order.id}} is refunded.', [
            'Payment Status in Refunded {{order.id}}',
        ]);
    }

    public function down(): void
    {
        // Keep the normalized defaults rather than reintroducing legacy wording.
    }

    private function updateBody(string $table, string $name, string $bodyHtml, array $legacyBodies): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columns = Schema::getColumnListing($table);
        foreach (['name', 'body_html'] as $column) {
            if (! in_array($column, $columns, true)) {
                return;
            }
        }

        $row = DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if (! $row) {
            return;
        }

        $currentBody = $this->normalizeText((string) $row->body_html);
        $matchesLegacy = false;
        foreach ($legacyBodies as $legacyBody) {
            if ($currentBody === $this->normalizeText($legacyBody)) {
                $matchesLegacy = true;
                break;
            }
        }

        if (! $matchesLegacy) {
            return;
        }

        $updates = ['body_html' => $bodyHtml];
        if (in_array('updated_at', $columns, true)) {
            $updates['updated_at'] = now();
        }

        DB::table($table)->where('id', $row->id)->update($updates);
    }

    private function normalizeText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\xC2\xA0", ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }
};
