@extends('admin.layouts.app')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-gray-800">Store Dashboard</h1>
                <p class="text-muted mb-0">Products, orders, stock and customer activity at a glance.</p>
            </div>
            @can('dailyprices_view')
                <a href="{{ route('admin.dailyprices.index') }}" class="btn btn-primary">
                    <i class="fas fa-tags mr-1" aria-hidden="true"></i> Update Today’s Prices
                </a>
            @endcan
        </div>

        <div class="row">
            @can('products_view')
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="{{ route('admin.products.index') }}" class="card border-left-primary shadow h-100 py-2 text-decoration-none">
                    <div class="card-body"><div class="row no-gutters align-items-center">
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Products</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['active_products']) }} active / {{ number_format($stats['products']) }}</div></div>
                        <div class="col-auto"><i class="fas fa-box-open fa-2x text-gray-300" aria-hidden="true"></i></div>
                    </div></div>
                </a>
            </div>
            @endcan

            @can('orders_view')
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="{{ route('admin.orders.index') }}" class="card border-left-success shadow h-100 py-2 text-decoration-none">
                    <div class="card-body"><div class="row no-gutters align-items-center">
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Orders</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['orders']) }}</div></div>
                        <div class="col-auto"><i class="fas fa-shopping-bag fa-2x text-gray-300" aria-hidden="true"></i></div>
                    </div></div>
                </a>
            </div>
            @endcan

            @can('products_edit')
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="card border-left-warning shadow h-100 py-2 text-decoration-none">
                    <div class="card-body"><div class="row no-gutters align-items-center">
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low-stock weights</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['low_stock']) }}</div></div>
                        <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-gray-300" aria-hidden="true"></i></div>
                    </div></div>
                </a>
            </div>
            @endcan

            @can('users_view')
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="{{ route('admin.users.index') }}" class="card border-left-info shadow h-100 py-2 text-decoration-none">
                    <div class="card-body"><div class="row no-gutters align-items-center">
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Customers</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['customers']) }}</div></div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300" aria-hidden="true"></i></div>
                    </div></div>
                </a>
            </div>
            @endcan
        </div>

        <div class="row">
            @can('orders_view')
            <div class="col-lg-8 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Recent Orders</strong><a href="{{ route('admin.orders.index') }}">View all</a></div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
                        <thead class="thead-light"><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Created</th></tr></thead>
                        <tbody>
                        @forelse($recentOrders as $order)
                            <tr><td><a href="{{ route('admin.orders.show', ['order' => $order->id]) }}">#{{ $order->id }}</a></td><td>{{ $order->name ?: 'Guest' }}</td><td>{{ currency($order->amount) }}</td><td>{{ $order->created_at ? date('d M Y, h:i A', strtotime($order->created_at)) : '—' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No orders yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div></div>
                </div>
            </div>
            @endcan

            <div class="col-lg-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-white"><strong>Quick Actions</strong></div>
                    <div class="card-body d-flex flex-column">
                        @can('products_create')<a href="{{ route('admin.products.create') }}" class="btn btn-outline-primary text-left mb-2"><i class="fas fa-plus mr-2" aria-hidden="true"></i>Add product</a>@endcan
                        @can('categories_view')<a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary text-left mb-2"><i class="fas fa-sitemap mr-2" aria-hidden="true"></i>Manage categories</a>@endcan
                        @can('featuredproducts_view')<a href="{{ route('admin.featuredproducts.index') }}" class="btn btn-outline-primary text-left mb-2"><i class="fas fa-star mr-2" aria-hidden="true"></i>Featured products</a>@endcan
                        @can('subscriptionenquiries_view')<a href="{{ route('admin.subscriptionenquiries.index') }}" class="btn btn-outline-primary text-left"><i class="fas fa-envelope-open-text mr-2" aria-hidden="true"></i>Subscription enquiries <span class="badge badge-primary float-right">{{ $stats['subscription_enquiries'] }}</span></a>@endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
