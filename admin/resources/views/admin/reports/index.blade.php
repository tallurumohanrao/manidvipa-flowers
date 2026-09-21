@extends('admin.layouts.app')

@section('content')
@php
    $links = [
        'sales' => ['label' => 'Sales', 'icon' => 'rupee-sign'],
        'orders' => ['label' => 'Order workflow', 'icon' => 'route'],
        'inventory' => ['label' => 'Inventory', 'icon' => 'boxes'],
        'products' => ['label' => 'Products', 'icon' => 'chart-bar'],
        'customers' => ['label' => 'Customers', 'icon' => 'users'],
    ];
    $summaryCards = [
        'sales' => [
            ['label' => 'Orders', 'value' => number_format($summary['orders'] ?? 0), 'color' => 'primary'],
            ['label' => 'Sales', 'value' => currency($summary['sales'] ?? 0), 'color' => 'success'],
            ['label' => 'Average order', 'value' => currency($summary['average'] ?? 0), 'color' => 'info'],
            ['label' => 'Cancelled', 'value' => number_format($summary['cancelled'] ?? 0), 'color' => 'danger'],
        ],
        'orders' => [
            ['label' => 'Total orders', 'value' => number_format($summary['total'] ?? 0), 'color' => 'primary'],
            ['label' => 'New or pending', 'value' => number_format($summary['new'] ?? 0), 'color' => 'warning'],
            ['label' => 'Ready for dispatch', 'value' => number_format($summary['ready'] ?? 0), 'color' => 'info'],
            ['label' => 'Delivered', 'value' => number_format($summary['delivered'] ?? 0), 'color' => 'success'],
        ],
        'inventory' => [
            ['label' => 'Stock options', 'value' => number_format($summary['options'] ?? 0), 'color' => 'primary'],
            ['label' => 'Tracked', 'value' => number_format($summary['tracked'] ?? 0), 'color' => 'info'],
            ['label' => 'Low stock', 'value' => number_format($summary['low'] ?? 0), 'color' => 'warning'],
            ['label' => 'Out of stock', 'value' => number_format($summary['out'] ?? 0), 'color' => 'danger'],
        ],
        'products' => [
            ['label' => 'Products sold', 'value' => number_format($summary['products'] ?? 0), 'color' => 'primary'],
            ['label' => 'Units sold', 'value' => number_format($summary['units'] ?? 0, 0), 'color' => 'info'],
            ['label' => 'Sales', 'value' => currency($summary['sales'] ?? 0), 'color' => 'success'],
            ['label' => 'Orders', 'value' => number_format($summary['orders'] ?? 0), 'color' => 'warning'],
        ],
        'customers' => [
            ['label' => 'Customers', 'value' => number_format($summary['customers'] ?? 0), 'color' => 'primary'],
            ['label' => 'New in period', 'value' => number_format($summary['new'] ?? 0), 'color' => 'info'],
            ['label' => 'Active in period', 'value' => number_format($summary['active'] ?? 0), 'color' => 'success'],
            ['label' => 'Customer sales', 'value' => currency($summary['sales'] ?? 0), 'color' => 'warning'],
        ],
    ];
@endphp
<section class="content">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-gray-800">{{ $reportMeta[$report]['title'] }}</h1>
                <p class="text-muted mb-0">{{ $reportMeta[$report]['description'] }}</p>
            </div>
            @if($report !== 'overview')
                <a href="{{ route('admin.reports.export', array_merge(['report' => $report], request()->query())) }}" class="btn btn-outline-success mt-3 mt-md-0"><i class="fas fa-file-csv mr-1" aria-hidden="true"></i> Export CSV</a>
            @endif
        </div>

        <div class="d-flex flex-wrap mb-4">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm {{ $report === 'overview' ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-2"><i class="fas fa-home mr-1" aria-hidden="true"></i> Reports home</a>
            @foreach($links as $key => $link)
                <a href="{{ route('admin.reports.'.$key, $key === 'inventory' ? [] : ['from' => request('from'), 'to' => request('to')]) }}" class="btn btn-sm {{ $report === $key ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-2"><i class="fas fa-{{ $link['icon'] }} mr-1" aria-hidden="true"></i>{{ $link['label'] }}</a>
            @endforeach
        </div>

        @if($report === 'overview')
            <div class="row">
                @foreach($links as $key => $link)
                    <div class="col-xl-4 col-md-6 mb-4">
                        <a href="{{ route('admin.reports.'.$key) }}" class="card shadow-sm h-100 py-3 text-decoration-none border-left-primary">
                            <div class="card-body">
                                <i class="fas fa-{{ $link['icon'] }} fa-2x text-primary mb-3" aria-hidden="true"></i>
                                <h5 class="text-gray-800">{{ $link['label'] }} report</h5>
                                <p class="text-muted mb-0">{{ $reportMeta[$key]['description'] }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
            <div class="alert alert-info shadow-sm"><strong>How to use reports:</strong> select a date range, review the totals, then open the related order, product, or stock page to take action. Reports use the same order and inventory records as the admin workflow.</div>
        @else
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        @if($report !== 'inventory')
                            <div class="col-md-3 mb-3 mb-md-0"><label for="report-from" class="small font-weight-bold">From</label><input id="report-from" type="date" name="from" value="{{ $from }}" class="form-control"></div>
                            <div class="col-md-3 mb-3 mb-md-0"><label for="report-to" class="small font-weight-bold">To</label><input id="report-to" type="date" name="to" value="{{ $to }}" class="form-control"></div>
                        @endif
                        @if($report === 'inventory')
                            <div class="col-md-4 mb-3 mb-md-0"><label for="inventory-search" class="small font-weight-bold">Product or SKU</label><input id="inventory-search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search inventory"></div>
                            <div class="col-md-3 mb-3 mb-md-0"><label for="inventory-scope" class="small font-weight-bold">Show</label><select id="inventory-scope" name="scope" class="custom-select"><option value="all" @selected($scope === 'all')>All stock</option><option value="low" @selected($scope === 'low')>Low stock</option><option value="out" @selected($scope === 'out')>Out of stock</option></select></div>
                        @endif
                        <div class="col-md-auto mb-0"><button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1" aria-hidden="true"></i> Apply</button> <a href="{{ route('admin.reports.'.$report) }}" class="btn btn-outline-secondary">Reset</a></div>
                    </form>
                </div>
            </div>

            <div class="row">
                @foreach($summaryCards[$report] as $card)
                    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-{{ $card['color'] }} shadow-sm h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-{{ $card['color'] }} text-uppercase mb-1">{{ $card['label'] }}</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ $card['value'] }}</div></div></div></div>
                @endforeach
            </div>

            @if($report === 'orders' && $statusBreakdown->isNotEmpty())
                <div class="card shadow-sm mb-4"><div class="card-header bg-white"><strong>Status breakdown</strong></div><div class="card-body"><div class="row">
                    @foreach($statusBreakdown as $status)
                        <div class="col-md-3 col-sm-6 mb-2"><span class="text-muted">{{ $status->name ?: 'Not set' }}</span><strong class="float-right">{{ number_format($status->total) }}</strong></div>
                    @endforeach
                </div></div></div>
            @endif

            @if($report === 'sales')
                <div class="card shadow-sm"><div class="card-header bg-white"><strong>Sales transactions</strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="thead-light"><tr><th>Order</th><th>Date</th><th>Customer</th><th>Source</th><th>Payment</th><th>Status</th><th class="text-right">Amount</th></tr></thead><tbody>
                    @forelse($rows as $row)<tr><td><a href="{{ route('admin.orders.show', ['order' => $row->id]) }}">#{{ $row->id }}</a></td><td>{{ $row->created_at ? date('d M Y', strtotime($row->created_at)) : '—' }}</td><td>{{ $row->name ?: 'Guest' }}</td><td>{{ ucfirst($row->source ?: 'unknown') }}</td><td>{{ $row->payment_status ?: '—' }}</td><td>{{ $row->order_status ?: '—' }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No sales found for this period.</td></tr>@endforelse
                </tbody></table></div></div></div>
            @elseif($report === 'orders')
                <div class="card shadow-sm"><div class="card-header bg-white"><strong>Order workflow</strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="thead-light"><tr><th>Order</th><th>Customer</th><th>Order status</th><th>Packing</th><th>Shipping</th><th>Delivery person</th><th>Date</th></tr></thead><tbody>
                    @forelse($rows as $row)<tr><td><a href="{{ route('admin.orders.show', ['order' => $row->id]) }}">#{{ $row->id }}</a></td><td>{{ $row->name ?: 'Guest' }}</td><td>{{ $row->order_status ?: '—' }}</td><td>{{ str_replace('_', ' ', $row->packing_status ?? 'not started') }}</td><td>{{ $row->shipping_status ?: '—' }}</td><td>{{ $row->delivery_person ?: 'Unassigned' }}</td><td>{{ $row->created_at ? date('d M Y', strtotime($row->created_at)) : '—' }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No orders found for this period.</td></tr>@endforelse
                </tbody></table></div></div></div>
            @elseif($report === 'inventory')
                <div class="card shadow-sm"><div class="card-header bg-white"><strong>Inventory by selling unit</strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="thead-light"><tr><th>Product</th><th>SKU</th><th>Unit</th><th>Available</th><th>Stock tracking</th><th>Status</th></tr></thead><tbody>
                    @forelse($rows as $row) @php $qty = (float) $row->qty; $unit = $qty === 1.0 ? ($row->singular_name ?: 'unit') : ($row->plural_name ?: 'units'); @endphp<tr><td>{{ $row->title }}</td><td>{{ $row->sku }}</td><td>{{ $unit }}</td><td class="{{ $qty <= 0 ? 'text-danger font-weight-bold' : ($qty <= 5 ? 'text-warning font-weight-bold' : '') }}">{{ rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') }}</td><td>{{ $row->track_stock ? 'Tracked' : 'Not tracked' }}</td><td>{{ $row->status ? 'Active' : 'Disabled' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">No inventory records found.</td></tr>@endforelse
                </tbody></table></div></div></div>
            @elseif($report === 'products')
                <div class="card shadow-sm"><div class="card-header bg-white"><strong>Best-selling products</strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="thead-light"><tr><th>Product</th><th>SKU</th><th class="text-right">Units sold</th><th class="text-right">Sales</th></tr></thead><tbody>
                    @forelse($rows as $row)<tr><td>{{ $row->product_title ?: 'Unknown product' }}</td><td>{{ $row->sku ?: '—' }}</td><td class="text-right">{{ number_format($row->units_sold) }}</td><td class="text-right">{{ currency($row->sales) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">No product sales found for this period.</td></tr>@endforelse
                </tbody></table></div></div></div>
            @elseif($report === 'customers')
                <div class="card shadow-sm"><div class="card-header bg-white"><strong>Customer activity</strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="thead-light"><tr><th>Customer</th><th>Email</th><th>Mobile</th><th class="text-right">Orders</th><th class="text-right">Order value</th></tr></thead><tbody>
                    @forelse($rows as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->email }}</td><td>{{ $row->mobile ?: '—' }}</td><td class="text-right">{{ number_format($row->order_count) }}</td><td class="text-right">{{ currency($row->order_total) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No customers found.</td></tr>@endforelse
                </tbody></table></div></div></div>
            @endif

            @if(isset($rows))
                <div class="d-flex justify-content-end mt-3">{{ $rows->links() }}</div>
            @endif
        @endif
    </div>
</section>
@endsection
