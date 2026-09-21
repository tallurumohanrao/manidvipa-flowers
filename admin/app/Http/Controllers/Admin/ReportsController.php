<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ReportsController extends Controller
{
    private const REPORTS = ['sales', 'orders', 'inventory', 'products', 'customers'];

    public function index()
    {
        $this->authorizeReports();

        return view('admin.reports.index', [
            'report' => 'overview',
            'reportMeta' => $this->reportMeta(),
        ]);
    }

    public function sales(Request $request)
    {
        $this->authorizeReports();
        [$from, $to] = $this->dateRange($request);
        $cancelled = $this->statusId('order_statuses', 'Cancelled', 5);
        $rows = $this->orderRows($from, $to, true, $cancelled)
            ->orderByDesc('o.id')
            ->paginate(25)
            ->withQueryString();
        $base = $this->ordersInRange($from, $to);
        $active = (clone $base)->where('o.order_status_id', '<>', $cancelled);
        $summary = [
            'orders' => (clone $active)->count('o.id'),
            'sales' => (float) (clone $active)->sum('o.amount'),
            'average' => (float) ((clone $active)->count('o.id') ? (clone $active)->sum('o.amount') / (clone $active)->count('o.id') : 0),
            'cancelled' => (clone $base)->where('o.order_status_id', $cancelled)->count('o.id'),
        ];

        return $this->reportView('sales', $request, compact('from', 'to', 'rows', 'summary'));
    }

    public function orders(Request $request)
    {
        $this->authorizeReports();
        [$from, $to] = $this->dateRange($request);
        $cancelled = $this->statusId('order_statuses', 'Cancelled', 5);
        $completed = $this->statusId('order_statuses', 'Completed', 4);
        $checkout = $this->statusId('order_statuses', 'Checkout', 1);
        $pending = $this->statusId('order_statuses', 'Pending', 2);
        $rows = $this->orderRows($from, $to, false, $cancelled)->orderByDesc('o.id')->paginate(25)->withQueryString();
        $base = $this->ordersInRange($from, $to);
        $summary = [
            'total' => (clone $base)->count('o.id'),
            'new' => (clone $base)->whereIn('o.order_status_id', [$checkout, $pending])->count('o.id'),
            'packing' => (clone $base)->when(Schema::hasColumn('orders', 'packing_status'), fn ($query) => $query->whereNotIn('o.order_status_id', [$completed, $cancelled])->whereIn('o.packing_status', ['not_started', 'packing_started', 'packed']))->count('o.id'),
            'ready' => (clone $base)->when(Schema::hasColumn('orders', 'packing_status'), fn ($query) => $query->where('o.packing_status', 'ready_for_dispatch'))->count('o.id'),
            'delivered' => $this->deliveredCount($from, $to),
            'cancelled' => (clone $base)->where('o.order_status_id', $cancelled)->count('o.id'),
        ];
        $statusBreakdown = Schema::hasTable('order_statuses')
            ? (clone $base)->leftJoin('order_statuses as os', 'os.id', '=', 'o.order_status_id')->select('os.name')->selectRaw('COUNT(o.id) as total')->groupBy('os.name')->orderByDesc('total')->get()
            : collect();

        return $this->reportView('orders', $request, compact('from', 'to', 'rows', 'summary', 'statusBreakdown'));
    }

    public function inventory(Request $request)
    {
        $this->authorizeReports();
        [$from, $to] = $this->dateRange($request);
        $scope = $request->input('scope', 'all');
        $rows = $this->inventoryRows($request, $scope)->paginate(25)->withQueryString();
        $base = $this->inventoryRows($request, 'all');
        $summary = [
            'options' => (clone $base)->count('pip.id'),
            'low' => (clone $base)->where('pip.qty', '>', 0)->where('pip.qty', '<=', 5)->count('pip.id'),
            'out' => (clone $base)->where('pip.qty', '<=', 0)->count('pip.id'),
            'tracked' => (clone $base)->where('pip.track_stock', 1)->count('pip.id'),
        ];

        return $this->reportView('inventory', $request, compact('from', 'to', 'rows', 'summary', 'scope'));
    }

    public function products(Request $request)
    {
        $this->authorizeReports();
        [$from, $to] = $this->dateRange($request);
        $cancelled = $this->statusId('order_statuses', 'Cancelled', 5);
        $base = $this->orderProductRows($from, $to, $cancelled);
        $rows = (clone $base)
            ->select('op.product_id', 'op.product_title', 'op.sku')
            ->selectRaw('SUM(op.quantity) as units_sold, SUM(op.amount) as sales')
            ->groupBy('op.product_id', 'op.product_title', 'op.sku')
            ->orderByDesc('sales')
            ->paginate(25)
            ->withQueryString();
        $summary = [
            'products' => (clone $base)->distinct('op.product_id')->count('op.product_id'),
            'units' => (float) (clone $base)->sum('op.quantity'),
            'sales' => (float) (clone $base)->sum('op.amount'),
            'orders' => (clone $base)->distinct('op.order_id')->count('op.order_id'),
        ];

        return $this->reportView('products', $request, compact('from', 'to', 'rows', 'summary'));
    }

    public function customers(Request $request)
    {
        $this->authorizeReports();
        [$from, $to] = $this->dateRange($request);
        $cancelled = $this->statusId('order_statuses', 'Cancelled', 5);
        $rows = $this->customerRows($from, $to, $cancelled)->orderByDesc('order_total')->paginate(25)->withQueryString();
        $summary = [
            'customers' => Schema::hasTable('users') ? DB::table('users')->count() : 0,
            'new' => Schema::hasTable('users') ? DB::table('users')->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count() : 0,
            'active' => Schema::hasTable('orders') ? DB::table('orders')->whereNotNull('user_id')->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->where('order_status_id', '<>', $cancelled)->distinct()->count('user_id') : 0,
            'sales' => Schema::hasTable('orders') ? (float) DB::table('orders')->whereNotNull('user_id')->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->where('order_status_id', '<>', $cancelled)->sum('amount') : 0,
        ];

        return $this->reportView('customers', $request, compact('from', 'to', 'rows', 'summary'));
    }

    public function export(Request $request, string $report)
    {
        $this->authorizeReports();
        abort_unless(in_array($report, self::REPORTS, true), Response::HTTP_NOT_FOUND);
        [$from, $to] = $this->dateRange($request);

        $rows = match ($report) {
            'sales' => $this->orderRows($from, $to, true, $this->statusId('order_statuses', 'Cancelled', 5))->orderByDesc('o.id')->get(),
            'orders' => $this->orderRows($from, $to, false, $this->statusId('order_statuses', 'Cancelled', 5))->orderByDesc('o.id')->get(),
            'inventory' => $this->inventoryRows($request, $request->input('scope', 'all'))->get(),
            'products' => $this->orderProductRows($from, $to, $this->statusId('order_statuses', 'Cancelled', 5))->select('op.product_id', 'op.product_title', 'op.sku')->selectRaw('SUM(op.quantity) as units_sold, SUM(op.amount) as sales')->groupBy('op.product_id', 'op.product_title', 'op.sku')->orderByDesc('sales')->get(),
            'customers' => $this->customerRows($from, $to, $this->statusId('order_statuses', 'Cancelled', 5))->orderByDesc('order_total')->get(),
        };

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) {
                fputcsv($output, array_keys((array) $rows->first()));
                foreach ($rows as $row) {
                    fputcsv($output, (array) $row);
                }
            }
            fclose($output);
        }, 'manidvipa-'.$report.'-report-'.$from.'-to-'.$to.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function reportView(string $report, Request $request, array $data)
    {
        return view('admin.reports.index', array_merge($data, [
            'report' => $report,
            'reportMeta' => $this->reportMeta(),
        ]));
    }

    private function reportMeta(): array
    {
        return [
            'overview' => ['title' => 'Reports', 'description' => 'Review sales, orders, stock, products and customers from one place.'],
            'sales' => ['title' => 'Sales report', 'description' => 'Order value and revenue for the selected period.'],
            'orders' => ['title' => 'Order workflow report', 'description' => 'Track order status, packing progress and delivery work.'],
            'inventory' => ['title' => 'Inventory report', 'description' => 'Review physical stock by product and selling unit.'],
            'products' => ['title' => 'Product performance', 'description' => 'See which products and options are selling in the selected period.'],
            'customers' => ['title' => 'Customer report', 'description' => 'Review customer activity and value for the selected period.'],
        ];
    }

    private function dateRange(Request $request): array
    {
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : now();
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : $to->copy()->subDays(29);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    private function ordersInRange(string $from, string $to)
    {
        return DB::table('orders as o')
            ->whereDate('o.created_at', '>=', $from)
            ->whereDate('o.created_at', '<=', $to);
    }

    private function orderRows(string $from, string $to, bool $excludeCancelled, int $cancelled)
    {
        $query = $this->ordersInRange($from, $to)
            ->select('o.id', 'o.name', 'o.email', 'o.amount', 'o.sub_total', 'o.created_at', 'o.serve_date')
            ->addSelect(DB::raw("COALESCE(o.source, 'unknown') as source"));

        if ($excludeCancelled) {
            $query->where('o.order_status_id', '<>', $cancelled);
        }
        if (Schema::hasTable('order_statuses')) {
            $query->leftJoin('order_statuses as os', 'os.id', '=', 'o.order_status_id')->addSelect('os.name as order_status');
        }
        if (Schema::hasTable('order_payments')) {
            $query->leftJoin('order_payments as op', 'op.order_id', '=', 'o.id')->addSelect('op.payment_status', 'op.payment_method');
        }
        if (Schema::hasTable('order_shippings')) {
            $query->leftJoin('order_shippings as osh', 'osh.order_id', '=', 'o.id')->addSelect('osh.amount as shipping_amount');
            if (Schema::hasTable('shipping_statuses')) {
                $query->leftJoin('shipping_statuses as ss', 'ss.id', '=', 'osh.shipping_status_id')->addSelect('ss.name as shipping_status');
            }
        }
        if (Schema::hasColumn('orders', 'packing_status')) {
            $query->addSelect('o.packing_status');
        }
        if (Schema::hasColumn('orders', 'accepted_by_admin_id')) {
            $query->leftJoin('admins as accepted_admin', 'accepted_admin.id', '=', 'o.accepted_by_admin_id')->addSelect('accepted_admin.name as order_manager');
        }
        if (Schema::hasColumn('orders', 'packing_admin_id')) {
            $query->leftJoin('admins as packing_admin', 'packing_admin.id', '=', 'o.packing_admin_id')->addSelect('packing_admin.name as packing_person');
        }
        if (Schema::hasColumn('orders', 'delivery_admin_id')) {
            $query->leftJoin('admins as delivery_admin', 'delivery_admin.id', '=', 'o.delivery_admin_id')->addSelect('delivery_admin.name as delivery_person');
        }

        return $query;
    }

    private function deliveredCount(string $from, string $to): int
    {
        if (! Schema::hasTable('order_shippings')) {
            return 0;
        }

        $delivered = $this->statusId('shipping_statuses', 'Delivered', 3);

        return $this->ordersInRange($from, $to)
            ->join('order_shippings as os', 'os.order_id', '=', 'o.id')
            ->where('os.shipping_status_id', $delivered)
            ->count('o.id');
    }

    private function inventoryRows(Request $request, string $scope)
    {
        $query = DB::table('product_inventory_pools as pip')
            ->join('products as p', 'p.id', '=', 'pip.product_id')
            ->leftJoin('measurement_units as mu', 'mu.id', '=', 'pip.unit_id')
            ->select('pip.id', 'pip.product_id', 'p.title', 'p.sku', 'pip.qty', 'pip.track_stock', 'pip.status', 'mu.singular_name', 'mu.plural_name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($filter) use ($search) {
                $filter->where('p.title', 'like', '%'.$search.'%')->orWhere('p.sku', 'like', '%'.$search.'%');
            });
        }
        if ($scope === 'low') {
            $query->where('pip.qty', '>', 0)->where('pip.qty', '<=', 5);
        } elseif ($scope === 'out') {
            $query->where('pip.qty', '<=', 0);
        }

        return $query->orderBy('pip.qty')->orderBy('p.title');
    }

    private function orderProductRows(string $from, string $to, int $cancelled)
    {
        return DB::table('order_products as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->whereDate('o.created_at', '>=', $from)
            ->whereDate('o.created_at', '<=', $to)
            ->where('o.order_status_id', '<>', $cancelled);
    }

    private function customerRows(string $from, string $to, int $cancelled)
    {
        $mobileSelect = Schema::hasColumn('users', 'mobile') ? 'u.mobile' : DB::raw("'' as mobile");

        return DB::table('users as u')
            ->leftJoin('orders as o', function ($join) use ($from, $to, $cancelled) {
                $join->on('o.user_id', '=', 'u.id')
                    ->whereDate('o.created_at', '>=', $from)
                    ->whereDate('o.created_at', '<=', $to)
                    ->where('o.order_status_id', '<>', $cancelled);
            })
            ->select('u.id', 'u.name', 'u.email', $mobileSelect)
            ->selectRaw('COUNT(o.id) as order_count, COALESCE(SUM(o.amount), 0) as order_total')
            ->groupBy('u.id', 'u.name', 'u.email', 'u.mobile')
            ->where('u.status', 1);
    }

    private function statusId(string $table, string $name, int $fallback): int
    {
        if (! Schema::hasTable($table)) {
            return $fallback;
        }

        return (int) (DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id') ?: $fallback);
    }

    private function authorizeReports(): void
    {
        abort_if(Gate::denies('reports_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
    }
}
