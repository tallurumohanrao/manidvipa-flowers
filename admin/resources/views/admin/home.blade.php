@extends('admin.layouts.app')

@section('content')
@php
    $sourceLabels = [
        'whatsapp' => 'WhatsApp',
        'website' => 'Website',
        'phone' => 'Phone',
        'manual' => 'Manual',
        'unknown' => 'Unknown',
    ];
    $packingLabels = [
        'not_started' => 'Not started',
        'packing_started' => 'Packing',
        'packed' => 'Packed',
        'ready_for_dispatch' => 'Ready for dispatch',
    ];
@endphp
<section class="content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-gray-800">Store Dashboard</h1>
                <p class="text-muted mb-0">Today’s orders, delivery work, stock and store health.</p>
            </div>
            <div class="d-flex align-items-center mt-3 mt-sm-0">
                <span class="small text-muted mr-3">{{ now()->format('d M Y, h:i A') }}</span>
                <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-primary" aria-label="Refresh dashboard">
                    <i class="fas fa-sync-alt mr-1" aria-hidden="true"></i> Refresh
                </a>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-left-{{ $priceUpdate['updated'] ? 'success' : 'warning' }}">
            <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <strong class="{{ $priceUpdate['updated'] ? 'text-success' : 'text-warning' }}">
                        <i class="fas fa-{{ $priceUpdate['updated'] ? 'check-circle' : 'exclamation-triangle' }} mr-1" aria-hidden="true"></i>
                        {{ $priceUpdate['updated'] ? 'Prices updated today' : 'Prices not updated today' }}
                    </strong>
                    @if($priceUpdate['at'])
                        <span class="small text-muted ml-2">Last updated {{ date('d M Y, h:i A', strtotime($priceUpdate['at'])) }}</span>
                    @else
                        <span class="small text-muted ml-2">No price update has been recorded yet.</span>
                    @endif
                </div>
                @can('dailyprices_view')
                    <a href="{{ route('admin.dailyprices.index') }}" class="btn btn-sm btn-outline-secondary mt-2 mt-md-0">Open daily prices</a>
                @endcan
            </div>
        </div>

        <div class="row">
            @can('orders_view')
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="{{ route('admin.orders.index', ['workflowQueue' => 'new']) }}" class="card border-left-primary shadow-sm h-100 py-2 text-decoration-none">
                    <div class="card-body"><div class="row no-gutters align-items-center">
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Orders today</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['today_orders']) }}</div><div class="small text-muted mt-1">{{ number_format($workflow['new']) }} new or pending</div></div>
                        <div class="col-auto"><i class="fas fa-shopping-bag fa-2x text-gray-300" aria-hidden="true"></i></div>
                    </div></div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="{{ route('admin.orders.index') }}" class="card border-left-success shadow-sm h-100 py-2 text-decoration-none">
                    <div class="card-body"><div class="row no-gutters align-items-center">
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sales today</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ currency($stats['today_sales']) }}</div><div class="small text-muted mt-1">{{ number_format($stats['orders']) }} orders all time</div></div>
                        <div class="col-auto"><i class="fas fa-rupee-sign fa-2x text-gray-300" aria-hidden="true"></i></div>
                    </div></div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="{{ route('admin.orders.index', ['deliveryDate' => now()->format('Y-m-d')]) }}" class="card border-left-info shadow-sm h-100 py-2 text-decoration-none">
                    <div class="card-body"><div class="row no-gutters align-items-center">
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Deliveries today</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['today_deliveries']) }}</div><div class="small text-muted mt-1">Check the delivery schedule below</div></div>
                        <div class="col-auto"><i class="fas fa-truck fa-2x text-gray-300" aria-hidden="true"></i></div>
                    </div></div>
                </a>
            </div>
            @endcan

            @can('products_view')
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="{{ route('admin.products.index') }}" class="card border-left-warning shadow-sm h-100 py-2 text-decoration-none">
                    <div class="card-body"><div class="row no-gutters align-items-center">
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Stock attention</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['out_of_stock']) }} out</div><div class="small text-muted mt-1">{{ number_format($stats['low_stock']) }} low stock options</div></div>
                        <div class="col-auto"><i class="fas fa-boxes fa-2x text-gray-300" aria-hidden="true"></i></div>
                    </div></div>
                </a>
            </div>
            @endcan
        </div>

        @can('orders_view')
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <strong>Order work queue</strong>
                        <a href="{{ route('admin.orders.index') }}">Open orders</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach([
                                ['key' => 'new', 'label' => 'New orders', 'icon' => 'inbox', 'color' => 'primary'],
                                ['key' => 'packing', 'label' => 'Packing queue', 'icon' => 'box', 'color' => 'warning'],
                                ['key' => 'ready_for_dispatch', 'label' => 'Ready for dispatch', 'icon' => 'clipboard-check', 'color' => 'info'],
                                ['key' => 'delivery', 'label' => 'Out for delivery', 'icon' => 'truck', 'color' => 'success'],
                            ] as $queue)
                                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                                    <a href="{{ route('admin.orders.index', ['workflowQueue' => $queue['key']]) }}" class="d-flex align-items-center border rounded p-3 h-100 text-decoration-none">
                                        <span class="text-{{ $queue['color'] }} mr-3"><i class="fas fa-{{ $queue['icon'] }} fa-lg" aria-hidden="true"></i></span>
                                        <span><strong class="d-block text-gray-800">{{ $queue['label'] }}</strong><span class="h5 mb-0 text-{{ $queue['color'] }}">{{ number_format($workflow[$queue['key']]) }}</span></span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <div class="row">
            @can('orders_view')
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Recent orders</strong><a href="{{ route('admin.orders.index') }}">View all</a></div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
                        <thead class="thead-light"><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th><th>Source</th><th>Created</th></tr></thead>
                        <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td><a href="{{ route('admin.orders.show', ['order' => $order->id]) }}">#{{ $order->id }}</a></td>
                                <td>{{ $order->name ?: 'Guest' }}</td>
                                <td>{{ currency($order->amount) }}</td>
                                <td><span class="badge badge-light">{{ $order->order_status ?? 'Not set' }}</span>@if(!empty($order->payment_status) && strtolower($order->payment_status) !== 'paid') <span class="badge badge-warning ml-1">{{ $order->payment_status }}</span>@endif</td>
                                <td>{{ $sourceLabels[$order->source ?? 'unknown'] ?? ucfirst($order->source ?? 'Unknown') }}</td>
                                <td>{{ $order->created_at ? date('d M, h:i A', strtotime($order->created_at)) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div></div>
                </div>
            </div>
            @endcan

            @can('orders_view')
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Needs attention</strong></div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.orders.index', ['paymentStatus' => 'Pending']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">Pending payments <span class="badge badge-warning badge-pill">{{ number_format($stats['pending_payments']) }}</span></a>
                        <a href="{{ route('admin.orders.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">Missing contact or address <span class="badge badge-danger badge-pill">{{ number_format($stats['missing_details']) }}</span></a>
                        @can('subscriptionenquiries_view')
                            <a href="{{ route('admin.subscriptionenquiries.index', ['status' => 'New']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">New subscription enquiries <span class="badge badge-primary badge-pill">{{ number_format($stats['new_subscription_enquiries']) }}</span></a>
                        @endcan
                    </div>
                </div>
            </div>
            @endcan
        </div>

        <div class="row">
            @can('orders_view')
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Today’s delivery schedule</strong><a href="{{ route('admin.orders.index', ['deliveryDate' => now()->format('Y-m-d')]) }}">View all</a></div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0">
                        <thead class="thead-light"><tr><th>Order</th><th>Customer</th><th>Packing</th><th>Delivery</th><th>Assigned to</th></tr></thead>
                        <tbody>
                        @forelse($todayDeliveries as $delivery)
                            <tr><td><a href="{{ route('admin.orders.show', ['order' => $delivery->id]) }}">#{{ $delivery->id }}</a></td><td>{{ $delivery->name ?: 'Guest' }}</td><td>{{ $packingLabels[$delivery->packing_status ?? 'not_started'] ?? 'Not started' }}</td><td>{{ $delivery->shipping_status ?? 'Pending' }}</td><td>{{ $delivery->delivery_admin_name ?? 'Unassigned' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No open deliveries scheduled for today.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div></div>
                </div>
            </div>
            @endcan

            @can('products_view')
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Low stock options</strong><a href="{{ route('admin.products.index') }}">Manage stock</a></div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0">
                        <thead class="thead-light"><tr><th>Product</th><th>Available</th></tr></thead>
                        <tbody>
                        @forelse($stockAlerts as $stock)
                            @php $qty = (float) $stock->qty; $unit = $qty === 1.0 ? ($stock->singular_name ?: 'unit') : ($stock->plural_name ?: 'units'); @endphp
                            <tr><td>{{ $stock->title }}</td><td class="{{ $qty <= 0 ? 'text-danger font-weight-bold' : 'text-warning font-weight-bold' }}">{{ rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') }} {{ $unit }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">No low-stock options.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div></div>
                </div>
            </div>
            @endcan
        </div>

        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><strong>Quick actions</strong></div>
                    <div class="card-body d-flex flex-wrap">
                        @can('products_create')<a href="{{ route('admin.products.create') }}" class="btn btn-outline-primary mr-2 mb-2"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Add product</a>@endcan
                        @can('dailyprices_view')<a href="{{ route('admin.dailyprices.index') }}" class="btn btn-outline-primary mr-2 mb-2"><i class="fas fa-tags mr-1" aria-hidden="true"></i>Update prices</a>@endcan
                        @can('categories_view')<a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary mr-2 mb-2"><i class="fas fa-sitemap mr-1" aria-hidden="true"></i>Manage categories</a>@endcan
                        @can('featuredproducts_view')<a href="{{ route('admin.featuredproducts.index') }}" class="btn btn-outline-primary mr-2 mb-2"><i class="fas fa-star mr-1" aria-hidden="true"></i>Featured products</a>@endcan
                        @can('subscriptionenquiries_view')<a href="{{ route('admin.subscriptionenquiries.index') }}" class="btn btn-outline-primary mr-2 mb-2"><i class="fas fa-envelope-open-text mr-1" aria-hidden="true"></i>Subscription enquiries</a>@endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
