<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Artisan,Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{

    public function index()
    {
        $today = now()->toDateString();
        $ordersTable = Schema::hasTable('orders');
        $cancelledOrderStatusId = $this->statusId('order_statuses', 'Cancelled', 5);
        $completedOrderStatusId = $this->statusId('order_statuses', 'Completed', 4);
        $checkoutOrderStatusId = $this->statusId('order_statuses', 'Checkout', 1);
        $pendingOrderStatusId = $this->statusId('order_statuses', 'Pending', 2);

        $stats = [
            'products' => Schema::hasTable('products') ? DB::table('products')->count() : 0,
            'active_products' => Schema::hasTable('products') ? DB::table('products')->where('status', 1)->count() : 0,
            'orders' => $ordersTable ? DB::table('orders')->count() : 0,
            'customers' => Schema::hasTable('users') ? DB::table('users')->count() : 0,
            'today_orders' => 0,
            'today_sales' => 0,
            'today_deliveries' => 0,
            'low_stock' => $this->lowStockCount(),
            'out_of_stock' => $this->outOfStockCount(),
            'pending_payments' => 0,
            'missing_details' => 0,
            'new_subscription_enquiries' => 0,
        ];

        if ($ordersTable) {
            $stats['today_orders'] = DB::table('orders')->whereDate('created_at', $today)->count();
            $stats['today_sales'] = (float) DB::table('orders')
                ->whereDate('created_at', $today)
                ->whereNotIn('order_status_id', array_values(array_unique([$checkoutOrderStatusId, $cancelledOrderStatusId])))
                ->sum('amount');

            if (Schema::hasColumn('orders', 'serve_date')) {
                $stats['today_deliveries'] = DB::table('orders')
                    ->whereDate('serve_date', $today)
                    ->whereNotIn('order_status_id', $this->terminalOrderStatuses($completedOrderStatusId, $cancelledOrderStatusId))
                    ->count();
            }

            $stats['missing_details'] = $this->missingOrderDetailsCount($completedOrderStatusId, $cancelledOrderStatusId);
            $stats['pending_payments'] = $this->pendingPaymentCount($completedOrderStatusId, $cancelledOrderStatusId);
        }

        if (Schema::hasTable('subscription_enquiries')) {
            $stats['new_subscription_enquiries'] = DB::table('subscription_enquiries')
                ->whereRaw('LOWER(status) = ?', ['new'])
                ->count();
        }

        $workflow = [
            'new' => $ordersTable ? $this->workflowCount('new', $checkoutOrderStatusId, $pendingOrderStatusId, $completedOrderStatusId, $cancelledOrderStatusId) : 0,
            'packing' => $ordersTable ? $this->workflowCount('packing', $checkoutOrderStatusId, $pendingOrderStatusId, $completedOrderStatusId, $cancelledOrderStatusId) : 0,
            'ready_for_dispatch' => $ordersTable ? $this->workflowCount('ready_for_dispatch', $checkoutOrderStatusId, $pendingOrderStatusId, $completedOrderStatusId, $cancelledOrderStatusId) : 0,
            'delivery' => $ordersTable ? $this->workflowCount('delivery', $checkoutOrderStatusId, $pendingOrderStatusId, $completedOrderStatusId, $cancelledOrderStatusId) : 0,
        ];

        $recentOrders = $ordersTable ? $this->recentOrders() : collect();
        $todayDeliveries = $ordersTable && Schema::hasColumn('orders', 'serve_date')
            ? $this->todayDeliveries($today, $completedOrderStatusId, $cancelledOrderStatusId)
            : collect();
        $stockAlerts = $this->stockAlerts();
        $priceUpdate = $this->priceUpdateSummary();

        return view('admin.home', compact('stats', 'workflow', 'recentOrders', 'todayDeliveries', 'stockAlerts', 'priceUpdate'));
    }

    private function lowStockCount(): int
    {
        $inventoryCount = Schema::hasTable('product_inventory_pools')
            ? DB::table('product_inventory_pools')->where('track_stock', 1)->where('status', 1)->where('qty', '<=', 5)->count()
            : 0;
        $legacyCount = Schema::hasTable('product_weights')
            ? DB::table('product_weights')->where('stock', 1)
                ->when(Schema::hasColumn('product_weights', 'inventory_pool_id'), fn ($query) => $query->whereNull('inventory_pool_id'))
                ->where('qty', '<=', 5)->count()
            : 0;

        return $inventoryCount + $legacyCount;
    }

    private function outOfStockCount(): int
    {
        $inventoryCount = Schema::hasTable('product_inventory_pools')
            ? DB::table('product_inventory_pools')->where('track_stock', 1)->where('status', 1)->where('qty', '<=', 0)->count()
            : 0;
        $legacyCount = Schema::hasTable('product_weights')
            ? DB::table('product_weights')->where('stock', 1)
                ->when(Schema::hasColumn('product_weights', 'inventory_pool_id'), fn ($query) => $query->whereNull('inventory_pool_id'))
                ->where('qty', '<=', 0)->count()
            : 0;

        return $inventoryCount + $legacyCount;
    }

    private function statusId(string $table, string $name, int $fallback): int
    {
        if (! Schema::hasTable($table)) {
            return $fallback;
        }

        return (int) (DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id') ?: $fallback);
    }

    private function terminalOrderStatuses(int $completed, int $cancelled): array
    {
        return array_values(array_unique(array_filter([$completed, $cancelled], fn ($id) => $id > 0)));
    }

    private function workflowCount(string $queue, int $checkout, int $pending, int $completed, int $cancelled): int
    {
        if (! Schema::hasTable('orders')) {
            return 0;
        }

        $query = DB::table('orders as o');
        $terminal = $this->terminalOrderStatuses($completed, $cancelled);

        if ($queue === 'new') {
            return $query->whereIn('o.order_status_id', [$checkout, $pending])->count();
        }

        if ($queue === 'packing') {
            if (! Schema::hasColumn('orders', 'packing_status')) {
                return 0;
            }

            return $query->whereNotIn('o.order_status_id', $terminal)
                ->whereIn('o.packing_status', ['not_started', 'packing_started', 'packed'])
                ->count();
        }

        if (! Schema::hasTable('order_shippings') || ! Schema::hasColumn('orders', 'packing_status')) {
            return 0;
        }

        $query->leftJoin('order_shippings as os', 'os.order_id', '=', 'o.id');
        $delivered = $this->statusId('shipping_statuses', 'Delivered', 3);
        $shippingCancelled = $this->statusId('shipping_statuses', 'Cancelled', 4);

        if ($queue === 'ready_for_dispatch') {
            return $query->where('o.packing_status', 'ready_for_dispatch')
                ->whereNotIn('os.shipping_status_id', [$delivered, $shippingCancelled])
                ->count();
        }

        $dispatched = $this->statusId('shipping_statuses', 'Dispatched', 2);
        $outForDelivery = $this->statusId('shipping_statuses', 'Out for Delivery', 0);

        return $queue === 'delivery'
            ? $query->whereIn('os.shipping_status_id', array_values(array_filter([$dispatched, $outForDelivery])))
                ->count()
            : 0;
    }

    private function pendingPaymentCount(int $completed, int $cancelled): int
    {
        if (! Schema::hasTable('order_payments') || ! Schema::hasTable('orders')) {
            return 0;
        }

        return DB::table('order_payments as op')
            ->leftJoin('orders as o', 'o.id', '=', 'op.order_id')
            ->whereRaw('LOWER(op.payment_status) IN (?, ?)', ['pending', 'failed'])
            ->whereNotIn('o.order_status_id', $this->terminalOrderStatuses($completed, $cancelled))
            ->count();
    }

    private function missingOrderDetailsCount(int $completed, int $cancelled): int
    {
        if (! Schema::hasTable('orders')) {
            return 0;
        }

        $query = DB::table('orders as o')
            ->whereNotIn('o.order_status_id', $this->terminalOrderStatuses($completed, $cancelled));

        $query->where(function ($details) {
            if (Schema::hasColumn('orders', 'contact_number')) {
                $details->whereNull('o.contact_number')->orWhere('o.contact_number', '');
            }

            if (Schema::hasTable('order_shipping_addresses')) {
                $details->orWhereNotExists(function ($address) {
                    $address->select(DB::raw(1))
                        ->from('order_shipping_addresses as osa')
                        ->whereColumn('osa.order_id', 'o.id');
                });
            }
        });

        return $query->count();
    }

    private function recentOrders()
    {
        $query = DB::table('orders as o')->select('o.id', 'o.name', 'o.amount', 'o.created_at');

        if (Schema::hasTable('order_statuses')) {
            $query->leftJoin('order_statuses as os', 'os.id', '=', 'o.order_status_id')->addSelect('os.name as order_status');
        }
        if (Schema::hasTable('order_payments')) {
            $query->leftJoin('order_payments as op', 'op.order_id', '=', 'o.id')->addSelect('op.payment_status');
        }
        if (Schema::hasTable('order_shippings') && Schema::hasTable('shipping_statuses')) {
            $query->leftJoin('order_shippings as osh', 'osh.order_id', '=', 'o.id')
                ->leftJoin('shipping_statuses as ss', 'ss.id', '=', 'osh.shipping_status_id')
                ->addSelect('ss.name as shipping_status');
        }

        if (Schema::hasColumn('orders', 'source')) {
            $query->addSelect('o.source');
        }

        return $query->orderByDesc('o.id')->limit(6)->get();
    }

    private function todayDeliveries(string $today, int $completed, int $cancelled)
    {
        if (! Schema::hasTable('orders')) {
            return collect();
        }

        $query = DB::table('orders as o')->select('o.id', 'o.name', 'o.serve_date');
        $query->whereDate('o.serve_date', $today)
            ->whereNotIn('o.order_status_id', $this->terminalOrderStatuses($completed, $cancelled));

        if (Schema::hasColumn('orders', 'packing_status')) {
            $query->addSelect('o.packing_status');
        }
        if (Schema::hasTable('order_shippings') && Schema::hasTable('shipping_statuses')) {
            $query->leftJoin('order_shippings as osh', 'osh.order_id', '=', 'o.id')
                ->leftJoin('shipping_statuses as ss', 'ss.id', '=', 'osh.shipping_status_id')
                ->addSelect('ss.name as shipping_status');
        }
        if (Schema::hasColumn('orders', 'delivery_admin_id')) {
            $query->leftJoin('admins as da', 'da.id', '=', 'o.delivery_admin_id')->addSelect('da.name as delivery_admin_name');
        }

        return $query->orderBy('o.id')->limit(8)->get();
    }

    private function stockAlerts()
    {
        if (Schema::hasTable('product_inventory_pools') && Schema::hasTable('products') && Schema::hasTable('measurement_units')) {
            return DB::table('product_inventory_pools as pip')
                ->join('products as p', 'p.id', '=', 'pip.product_id')
                ->leftJoin('measurement_units as mu', 'mu.id', '=', 'pip.unit_id')
                ->where('pip.track_stock', 1)
                ->where('pip.status', 1)
                ->where('pip.qty', '<=', 5)
                ->select('p.id as product_id', 'p.title', 'pip.qty', 'mu.singular_name', 'mu.plural_name')
                ->orderBy('pip.qty')
                ->limit(8)
                ->get();
        }

        if (! Schema::hasTable('product_weights') || ! Schema::hasTable('products')) {
            return collect();
        }

        return DB::table('product_weights as pw')
            ->join('products as p', 'p.id', '=', 'pw.product_id')
            ->where('pw.stock', 1)
            ->where('pw.qty', '<=', 5)
            ->when(Schema::hasColumn('product_weights', 'inventory_pool_id'), fn ($query) => $query->whereNull('pw.inventory_pool_id'))
            ->select('p.id as product_id', 'p.title', 'pw.qty', 'pw.name as singular_name', 'pw.name as plural_name')
            ->orderBy('pw.qty')
            ->limit(8)
            ->get();
    }

    private function priceUpdateSummary(): array
    {
        if (! Schema::hasTable('price_update_logs')) {
            return ['updated' => false, 'at' => null];
        }

        $latest = DB::table('price_update_logs')->orderByDesc('id')->first();
        $timestamp = $latest?->created_at ?: $latest?->updated_at;

        return [
            'updated' => $timestamp ? date('Y-m-d', strtotime($timestamp)) === now()->toDateString() : false,
            'at' => $timestamp,
        ];
    }

    public function clear(){
        abort_if(Gate::denies('settings_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Artisan::call('route:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        //create cache
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        return back()->with('success','All cache cleared.');
    }

    public function down(){
        abort_if(Gate::denies('settings_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Artisan::call('down');
        return back()->with('success','Site is under maintenance.');
    }

    public function up(){
        abort_if(Gate::denies('settings_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Artisan::call('up');
        return back()->with('success','Site is on live.');
    }

    public function media(string $path)
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_if($path === '' || Str::contains($path, ['..', "\0"]), Response::HTTP_NOT_FOUND);

        $fullPath = storage_path('app/public/'.$path);

        abort_unless(File::isFile($fullPath), Response::HTTP_NOT_FOUND);

        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function upload(Request $request)
    {
        abort_if(!Gate::any([
            'banners_create', 'banners_edit',
            'categories_create', 'categories_edit',
            'pages_create', 'pages_edit',
            'posts_create', 'posts_edit',
            'contentblocks_create', 'contentblocks_edit',
            'faqs_create', 'faqs_edit',
            'orderstatuses_create', 'orderstatuses_edit',
            'paymentstatuses_create', 'paymentstatuses_edit',
            'products_create', 'products_edit',
            'projects_create', 'projects_edit',
            'services_create', 'services_edit',
            'shippingstatuses_create', 'shippingstatuses_edit',
            'subscriptionplans_create', 'subscriptionplans_edit',
            'testimonials_create', 'testimonials_edit',
            'theaters_create', 'theaters_edit',
        ]), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $request->validate([
            'upload' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,bmp,webp,pdf', 'max:8192'],
        ]);

        if($request->hasFile('upload')) {
            //get filename with extension
            $filenamewithextension = $request->file('upload')->getClientOriginalName();

            //get filename without extension
            $filename = \Illuminate\Support\Str::slug(pathinfo($filenamewithextension, PATHINFO_FILENAME)) ?: 'upload';

            //get file extension
            $extension = $request->file('upload')->getClientOriginalExtension();
            $file = $request->file('upload');
            //filename to store
            $filenametostore = $filename.'_'.time().'.'.$extension;

            //Upload File
            Storage::disk('public')->makeDirectory('ckeditor');
            if(in_array($extension,['JPG','jpg','jpeg','JPEG','PNG','png','GIF','gif','BMP','bmp','WebP','webp','WEBP'])){
                Image::make($file->getRealPath())->save(storage_path('app/public/ckeditor/'.$filenametostore), 60);
            }else{
                $request->file('upload')->storeAs('public/ckeditor', $filenametostore);
            }

            $CKEditorFuncNum = $request->input('CKEditorFuncNum');
            $url = '/storage/ckeditor/'.$filenametostore;
            $msg = 'Uploaded successfully.';
            $re = "<script>window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '$url', '$msg')</script>";

            if($request->expectsJson() || $request->ajax() || !$CKEditorFuncNum){
                return response()->json([
                    'uploaded' => 1,
                    'fileName' => $filenametostore,
                    'url' => $url,
                    'message' => $msg,
                ]);
            }

            return response($re)->header('Content-Type', 'text/html; charset=utf-8');
        }
    }

}
