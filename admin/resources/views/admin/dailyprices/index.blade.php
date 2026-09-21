@extends('admin.layouts.app')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="heading mb-1">Daily Price Update</h4>
                <p class="text-muted mb-0">Update flower prices quickly without opening each product one by one.</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-box-open mr-1"></i> Products
            </a>
        </div>

        <div class="card shadow mb-4 border-left-success">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <div>
                    <strong>Simple Bulk Price Update</strong>
                    <div class="text-muted small">Recommended daily flow: download current prices, edit only price columns, upload back.</div>
                </div>
                <a href="{{ route('admin.dailyprices.export', request()->query()) }}" class="btn btn-success">
                    <i class="fas fa-file-download mr-1"></i> Download CSV Template
                </a>
            </div>
            @can('dailyprices_edit')
            <div class="card-body">
                <form method="POST" action="{{ route('admin.dailyprices.import') }}" enctype="multipart/form-data">
                    @csrf
                    @foreach(['search', 'parent_category_id', 'category_id', 'weight_name', 'product_status', 'weight_status', 'per_page'] as $filterKey)
                        @if(request()->filled($filterKey))
                            <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                        @endif
                    @endforeach
                    <div class="row align-items-end">
                        <div class="col-md-5 mb-3">
                            <label class="small font-weight-bold">Upload updated price file</label>
                            <input type="file" name="price_file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
                            <small class="text-muted">Supports CSV, TXT, XLSX, XLS. Keep these columns: product_weight_id, sell_price, list_price.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="custom-control custom-checkbox mt-4">
                                <input type="checkbox" name="auto_calculate_loose_file" value="1" class="custom-control-input" id="auto-calculate-loose-file">
                                <label class="custom-control-label" for="auto-calculate-loose-file">
                                    Auto-calculate 100g / 250g / 500g from uploaded 1KG rows
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-upload mr-1"></i> Import Prices
                            </button>
                        </div>
                    </div>
                </form>
                <div class="alert alert-light border mb-0">
                    <strong>Simple rule:</strong> do not change product_weight_id. Update only <code>sell_price</code> and <code>list_price</code>, then upload the same file.
                </div>
            </div>
            @else
                <div class="card-body text-muted">You have read-only access. Ask an administrator for Daily Price Edit access to import or change prices.</div>
            @endcan
        </div>

        @can('dailyprices_edit')
        <div class="card shadow mb-4 border-left-primary voice-price-card">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <div>
                    <strong>Voice Price Update</strong>
                    <div class="text-muted small">Speak or type a command, preview the result, then confirm before saving.</div>
                </div>
                <span class="badge badge-primary">Preview required</span>
            </div>
            <div class="card-body">
                <div class="row align-items-start">
                    <div class="col-lg-7 mb-3">
                        <label class="small font-weight-bold" for="voice-price-command">Price command</label>
                        <textarea id="voice-price-command" class="form-control voice-command-input" rows="3" placeholder="Example: Increase all product prices by 10 percent"></textarea>
                        <div class="voice-command-examples mt-2">
                            <span>Try:</span>
                            <button type="button" class="btn btn-link btn-sm p-0 voice-example" data-command="Increase all product prices by 10 percent">Increase all by 10%</button>
                            <button type="button" class="btn btn-link btn-sm p-0 voice-example" data-command="Set roses to 499 rupees">Set roses to 499</button>
                            <button type="button" class="btn btn-link btn-sm p-0 voice-example" data-command="Reduce category bouquet prices by 5 percent">Reduce bouquet category by 5%</button>
                            <button type="button" class="btn btn-link btn-sm p-0 voice-example" data-command="Update jasmine price to 250">Update jasmine to 250</button>
                        </div>
                    </div>
                    <div class="col-lg-5 mb-3">
                        <label class="small font-weight-bold d-block">Controls</label>
                        <div class="btn-group flex-wrap mb-2" role="group" aria-label="Voice price controls">
                            <button type="button" class="btn btn-danger" id="voice-price-start">
                                <i class="fas fa-microphone mr-1"></i> Speak
                            </button>
                            <button type="button" class="btn btn-primary" id="voice-price-preview">
                                <i class="fas fa-eye mr-1"></i> Preview
                            </button>
                            <button type="button" class="btn btn-success" id="voice-price-apply" disabled>
                                <i class="fas fa-save mr-1"></i> Save Price Changes
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="voice-price-clear">Clear</button>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="voice-apply-list-price">
                            <label class="custom-control-label" for="voice-apply-list-price">Apply the same change to list price also</label>
                        </div>
                        <div class="small mt-2 text-muted">Speaking creates the preview automatically. Check the affected rows, then click Save Price Changes. Only active products and active weights are included.</div>
                    </div>
                </div>

                <div id="voice-price-status" class="alert d-none mb-3" role="status"></div>

                <div id="voice-price-preview-panel" class="voice-preview-panel d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <strong>Preview</strong>
                            <div class="text-muted small" id="voice-price-summary"></div>
                        </div>
                        <span class="badge badge-success">Ready to save</span>
                    </div>
                    <div class="table-responsive voice-preview-table-wrap">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Weight</th>
                                    <th>Sell Price</th>
                                    <th>List Price</th>
                                </tr>
                            </thead>
                            <tbody id="voice-price-preview-rows"></tbody>
                        </table>
                    </div>
                    <div class="small text-muted mt-2" id="voice-price-preview-note"></div>
                </div>
            </div>
        </div>
        @endcan

        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <strong>Filters</strong>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.dailyprices.index') }}">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="small font-weight-bold">Search Product / SKU / Weight</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Example: roses, chamanthi, 1 KG">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold">Parent Category</label>
                            <select name="parent_category_id" class="form-control">
                                <option value="">All parent categories</option>
                                @foreach($parentCategories as $category)
                                    <option value="{{ $category->id }}" @selected((string) request('parent_category_id') === (string) $category->id)>
                                        {{ $category->title ?: $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold">Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">All categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                        {{ $category->parent_id ? '— ' : '' }}{{ $category->title ?: $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold">Weight</label>
                            <select name="weight_name" class="form-control">
                                <option value="">All weights</option>
                                @foreach($weightOptions as $weightName)
                                    <option value="{{ $weightName }}" @selected(request('weight_name') === $weightName)>
                                        {{ $weightName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 mb-3">
                            <label class="small font-weight-bold">Product</label>
                            <select name="product_status" class="form-control">
                                <option value="">All</option>
                                <option value="1" @selected(request('product_status') === '1')>Active</option>
                                <option value="0" @selected(request('product_status') === '0')>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-3">
                            <label class="small font-weight-bold">Weight</label>
                            <select name="weight_status" class="form-control">
                                <option value="">All</option>
                                <option value="1" @selected(request('weight_status') === '1')>Active</option>
                                <option value="0" @selected(request('weight_status') === '0')>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-3">
                            <label class="small font-weight-bold">Show</label>
                            <select name="per_page" class="form-control">
                                @foreach([10, 25, 50, 100] as $option)
                                    <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="d-flex">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search mr-1"></i> Search
                        </button>
                        <a href="{{ route('admin.dailyprices.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.dailyprices.update') }}" id="daily-price-form">
            @csrf
            @foreach(['search', 'parent_category_id', 'category_id', 'weight_name', 'product_status', 'weight_status', 'per_page'] as $filterKey)
                @if(request()->filled($filterKey))
                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                @endif
            @endforeach

            @can('dailyprices_edit')
            <div class="card shadow mb-4">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <div>
                        <strong>Advanced Bulk Actions</strong>
                        <span class="text-muted small ml-2">Optional. Use only when you want formula-based changes on checked rows.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="collapse" data-target="#advanced-bulk-actions">
                        Show / Hide
                    </button>
                </div>
                <div class="card-body collapse" id="advanced-bulk-actions">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="small font-weight-bold">Selected Rows Action</label>
                            <select name="bulk_action" class="form-control" id="bulk-action">
                                <option value="none">No bulk action - save manual edits</option>
                                <option value="set_sell_price">Set selling price to ₹ value</option>
                                <option value="increase_percent">Increase selling price by %</option>
                                <option value="decrease_percent">Decrease selling price by %</option>
                                <option value="increase_fixed">Increase selling price by ₹</option>
                                <option value="decrease_fixed">Decrease selling price by ₹</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="small font-weight-bold">Bulk Value</label>
                            <input type="number" step="0.01" min="0" name="bulk_value" class="form-control" placeholder="Example: 10">
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="custom-control custom-checkbox mt-4">
                                <input type="checkbox" name="bulk_apply_list_price" value="1" class="custom-control-input" id="bulk-apply-list-price">
                                <label class="custom-control-label" for="bulk-apply-list-price">Apply same bulk change to list price also</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="custom-control custom-checkbox mt-4">
                                <input type="checkbox" name="auto_calculate_loose" value="1" class="custom-control-input" id="auto-calculate-loose">
                                <label class="custom-control-label" for="auto-calculate-loose">
                                    Auto-calculate 100g / 250g / 500g when 1KG price is edited
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">Loose flower auto prices are rounded to nearest ₹5. Premium bunch/stem prices remain manual.</small>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            <div class="card shadow mb-4">
                <div class="card-header bg-white daily-price-results-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:nowrap;gap:1rem;">
                    <div class="daily-price-results-summary" style="display:flex;align-items:center;flex:0 0 auto;gap:.5rem;white-space:nowrap;">
                        <label for="daily-price-page-size" class="mb-0 text-muted small">Rows</label>
                        <select id="daily-price-page-size" class="custom-select custom-select-sm daily-price-page-size" style="width:68px;min-width:68px;flex:0 0 68px;" aria-label="Rows per page">
                            @foreach([10, 25, 50, 100] as $option)
                                <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="daily-price-results-right" style="display:flex;align-items:center;justify-content:flex-end;flex:0 0 auto;gap:1rem;">
                        <div class="daily-price-results-search" style="margin-left:0;flex:0 0 260px;width:260px;min-width:220px;">
                            <input type="search" id="daily-price-header-search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Search products" aria-label="Search products">
                        </div>
                        <div class="daily-price-results-actions" style="display:flex;align-items:center;flex:0 0 auto;gap:.5rem;white-space:nowrap;">
                            @can('dailyprices_edit')
                                <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="select-visible">Select visible</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="clear-selected">Clear selected</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save mr-1"></i> Save Price Updates
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive daily-price-table-wrap">
                        <table class="table table-bordered table-hover mb-0 daily-price-table" data-admin-table="off" style="table-layout:fixed!important;min-width:1100px;width:100%!important;">
                            <colgroup>
                                <col style="width:42px;">
                                <col style="width:72px;">
                                <col style="width:220px;">
                                <col style="width:170px;">
                                <col style="width:120px;">
                                <col style="width:145px;">
                                <col style="width:145px;">
                                <col style="width:90px;">
                                <col style="width:150px;">
                            </colgroup>
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 42px;">
                                        <input type="checkbox" id="select-all-prices" @cannot('dailyprices_edit') disabled @endcannot aria-label="Select all visible price rows">
                                    </th>
                                    <th style="width: 72px;">Image</th>
                                    <th>Product</th>
                                    <th>Categories</th>
                                    <th style="width: 130px;">Weight</th>
                                    <th style="width: 145px;">Sell Price</th>
                                    <th style="width: 145px;">List Price</th>
                                    <th style="width: 90px;">Status</th>
                                    <th style="width: 150px;">Quick Save</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $row)
                                    <tr data-price-row="{{ $row->weight_id }}">
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="price-row-check" name="selected_weights[]" value="{{ $row->weight_id }}" @cannot('dailyprices_edit') disabled @endcannot aria-label="Select {{ $row->product_title }} {{ $row->weight_name }}">
                                        </td>
                                        <td class="align-middle">
                                            @if($row->image_name)
                                                <img src="{{ asset('storage/products/'.$row->image_name) }}" alt="{{ $row->product_title }}" class="daily-price-image" style="display:block;width:56px!important;height:56px!important;max-width:56px!important;min-width:56px;object-fit:cover;">
                                            @else
                                                <div class="daily-price-placeholder" style="width:56px!important;height:56px!important;max-width:56px!important;min-width:56px;">
                                                    <i class="fas fa-seedling"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <strong>{{ $row->product_title }}</strong>
                                            @if($row->sku)
                                                <div class="text-muted small">SKU: {{ $row->sku }}</div>
                                            @endif
                                            <div class="small">
                                                <span class="badge badge-{{ (int) $row->product_status === 1 ? 'success' : 'secondary' }}">
                                                    {{ (int) $row->product_status === 1 ? 'Product Active' : 'Product Inactive' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="align-middle text-muted small">
                                            {{ $row->category_titles ?: 'No category mapped' }}
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge badge-light border">{{ $row->weight_name }}</span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">₹</span>
                                                </div>
                                                <input type="number" step="0.01" min="0" class="form-control price-input row-sell-price" name="prices[{{ $row->weight_id }}][sell_price]" value="{{ number_format((float) $row->sell_price, 2, '.', '') }}" data-current="{{ number_format((float) $row->sell_price, 2, '.', '') }}" data-row="{{ $row->weight_id }}" @cannot('dailyprices_edit') readonly @endcannot>
                                            </div>
                                            <small class="text-muted current-sell-price">Current ₹{{ number_format((float) $row->sell_price, 2) }}</small>
                                        </td>
                                        <td class="align-middle">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">₹</span>
                                                </div>
                                                <input type="number" step="0.01" min="0" class="form-control price-input row-list-price" name="prices[{{ $row->weight_id }}][list_price]" value="{{ number_format((float) $row->list_price, 2, '.', '') }}" data-current="{{ number_format((float) $row->list_price, 2, '.', '') }}" data-row="{{ $row->weight_id }}" @cannot('dailyprices_edit') readonly @endcannot>
                                            </div>
                                            <small class="text-muted current-list-price">Current ₹{{ number_format((float) $row->list_price, 2) }}</small>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge badge-{{ (int) $row->weight_status === 1 ? 'success' : 'secondary' }}">
                                                {{ (int) $row->weight_status === 1 ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            @can('dailyprices_edit')
                                                <button type="button" class="btn btn-sm btn-success ajax-price-save mb-1" data-row="{{ $row->weight_id }}" data-url="{{ route('admin.dailyprices.weights.update', ['id' => $row->weight_id]) }}">Save</button>
                                            @endcan
                                            @can('products_edit')
                                                <a href="{{ route('admin.products.weights', ['id' => $row->product_id]) }}" class="btn btn-sm btn-outline-primary mb-1">Weights</a>
                                            @endcan
                                            <div class="small ajax-save-status text-muted" data-row-status="{{ $row->weight_id }}"></div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            No product weight prices found for the selected filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="row align-items-center">
                        <div class="col-12">
                            <div class="float-right">{{ $data->links() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card shadow mb-4 daily-price-log-card">
            <div class="card-header bg-white">
                <strong>Recent Price Updates</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 daily-price-log-table">
                        <colgroup>
                            <col style="width:15%;">
                            <col style="width:25%;">
                            <col style="width:11%;">
                            <col style="width:16%;">
                            <col style="width:16%;">
                            <col style="width:9%;">
                            <col style="width:8%;">
                        </colgroup>
                        <thead class="thead-light">
                            <tr>
                                <th>Updated At</th>
                                <th>Product</th>
                                <th>Weight</th>
                                <th>Sell Price</th>
                                <th>List Price</th>
                                <th>Source</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at }}</td>
                                    <td>{{ $log->product_title }}</td>
                                    <td>{{ $log->weight_name }}</td>
                                    <td>₹{{ number_format((float) $log->old_sell_price, 2) }} → ₹{{ number_format((float) $log->new_sell_price, 2) }}</td>
                                    <td>₹{{ number_format((float) $log->old_list_price, 2) }} → ₹{{ number_format((float) $log->new_list_price, 2) }}</td>
                                    <td><span class="badge badge-info">{{ $log->update_source }}</span></td>
                                    <td>
                                        @if($log->update_source !== 'rollback' && Gate::allows('dailyprices_edit'))
                                            <form method="POST" action="{{ route('admin.dailyprices.rollback', ['id' => $log->id]) }}" onsubmit="return confirm('Rollback this price update?');">
                                                @csrf
                                                @foreach(['search', 'parent_category_id', 'category_id', 'weight_name', 'product_status', 'weight_status', 'per_page'] as $filterKey)
                                                    @if(request()->filled($filterKey))
                                                        <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                                                    @endif
                                                @endforeach
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Rollback</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Rollback log</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No price updates logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .daily-price-table-wrap {
        max-height: 68vh;
        overflow-x: auto;
        overflow-y: auto;
    }

    .daily-price-results-header {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: .75rem 1rem;
        justify-content: space-between !important;
    }

    .daily-price-results-summary,
    .daily-price-results-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .daily-price-results-summary {
        order: 1;
    }

    .daily-price-results-search {
        margin-left: 0 !important;
        order: 2;
    }

    .daily-price-results-actions {
        order: 3;
    }

    .daily-price-results-right {
        margin-left: auto;
    }

    .daily-price-results-summary strong {
        margin-left: .35rem;
    }

    .daily-price-page-size {
        min-width: 68px;
        width: 68px;
    }

    .daily-price-results-search {
        min-width: 220px;
        width: 260px;
    }

    .daily-price-table {
        table-layout: fixed !important;
        min-width: 1100px;
        width: 100% !important;
    }

    .daily-price-table th,
    .daily-price-table td {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .daily-price-table th:nth-child(1),
    .daily-price-table td:nth-child(1) { width: 42px; }
    .daily-price-table th:nth-child(2),
    .daily-price-table td:nth-child(2) { width: 72px; }
    .daily-price-table th:nth-child(3),
    .daily-price-table td:nth-child(3) { width: 220px; }
    .daily-price-table th:nth-child(4),
    .daily-price-table td:nth-child(4) { width: 170px; }
    .daily-price-table th:nth-child(5),
    .daily-price-table td:nth-child(5) { width: 120px; }
    .daily-price-table th:nth-child(6),
    .daily-price-table td:nth-child(6),
    .daily-price-table th:nth-child(7),
    .daily-price-table td:nth-child(7) { width: 145px; }
    .daily-price-table th:nth-child(8),
    .daily-price-table td:nth-child(8) { width: 90px; }
    .daily-price-table th:nth-child(9),
    .daily-price-table td:nth-child(9) { width: 150px; }

    .daily-price-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8f9fc;
    }

    .daily-price-log-table {
        table-layout: fixed;
        width: 100%;
    }

    .daily-price-log-card .admin-table-toolbar {
        align-items: center;
        border-bottom: 1px solid #e3e6f0;
        margin: 0;
        min-height: 58px;
        padding: .75rem 1rem;
    }

    .daily-price-log-card .admin-table-options {
        margin-right: 0;
    }

    .daily-price-log-table th,
    .daily-price-log-table td {
        overflow-wrap: anywhere;
        word-break: break-word;
        vertical-align: middle;
    }

    .daily-price-image,
    .daily-price-placeholder {
        display: block;
        width: 56px !important;
        min-width: 56px;
        max-width: 56px !important;
        height: 56px !important;
        min-height: 56px;
        max-height: 56px !important;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #eadfe4;
        background: #fff8f3;
    }

    .daily-price-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9b1237;
    }

    .price-input.is-edited {
        border-color: #1cc88a;
        box-shadow: 0 0 0 0.1rem rgba(28, 200, 138, 0.2);
        font-weight: 700;
    }

    .voice-price-card {
        border-top: 1px solid #e3e6f0;
    }

    .voice-command-input {
        min-height: 92px;
        font-size: 15px;
    }

    .voice-command-examples {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 14px;
        align-items: center;
    }

    .voice-command-examples span {
        color: #6e7891;
        font-size: 13px;
    }

    .voice-preview-panel {
        border: 1px solid #d8e3ff;
        border-radius: 8px;
        background: #f8fbff;
        padding: 14px;
    }

    .voice-preview-table-wrap {
        max-height: 320px;
        overflow: auto;
        background: #fff;
    }

    .voice-preview-price-change {
        font-weight: 700;
        color: #1b8f5a;
    }

    @media (max-width: 991.98px) {
        .daily-price-results-header {
            flex-wrap: wrap !important;
        }

        .daily-price-results-right {
            flex: 1 1 100% !important;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-left: 0;
        }

        .daily-price-results-search {
            flex: 1 1 100% !important;
            margin-left: 0 !important;
            min-width: 100% !important;
            width: 100% !important;
        }
    }
</style>
@endpush

@push('script')
<script>
    (function () {
        const selectAll = document.getElementById('select-all-prices');
        const selectVisible = document.getElementById('select-visible');
        const clearSelected = document.getElementById('clear-selected');
        const pageSizeSelect = document.getElementById('daily-price-page-size');
        const headerSearch = document.getElementById('daily-price-header-search');
        const checks = () => Array.from(document.querySelectorAll('.price-row-check'));
        const priceInputs = Array.from(document.querySelectorAll('.price-input'));
        const ajaxButtons = Array.from(document.querySelectorAll('.ajax-price-save'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const voiceCommandInput = document.getElementById('voice-price-command');
        const voiceStartButton = document.getElementById('voice-price-start');
        const voicePreviewButton = document.getElementById('voice-price-preview');
        const voiceApplyButton = document.getElementById('voice-price-apply');
        const voiceClearButton = document.getElementById('voice-price-clear');
        const voiceApplyListPrice = document.getElementById('voice-apply-list-price');
        const voiceStatus = document.getElementById('voice-price-status');
        const voicePreviewPanel = document.getElementById('voice-price-preview-panel');
        const voiceSummary = document.getElementById('voice-price-summary');
        const voicePreviewRows = document.getElementById('voice-price-preview-rows');
        const voicePreviewNote = document.getElementById('voice-price-preview-note');
        const voicePreviewUrl = @json(route('admin.dailyprices.voice.preview'));
        const voiceApplyUrl = @json(route('admin.dailyprices.voice.apply'));
        let lastVoicePreview = null;

        function applyDailyPriceHeaderFilters() {
            const url = new URL(window.location.href);
            const search = headerSearch?.value.trim() || '';
            const pageSize = pageSizeSelect?.value || '10';

            if (search) {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }

            url.searchParams.set('per_page', pageSize);
            url.searchParams.delete('page');
            window.location.assign(url.toString());
        }

        function setChecks(value) {
            checks().forEach((input) => {
                input.checked = value;
            });
            if (selectAll) {
                selectAll.checked = value;
            }
        }

        function setVoiceStatus(message, type = 'info') {
            if (!voiceStatus) {
                return;
            }

            voiceStatus.textContent = message;
            voiceStatus.className = `alert alert-${type} mb-3`;
            voiceStatus.classList.toggle('d-none', !message);
        }

        function resetVoicePreview() {
            lastVoicePreview = null;
            if (voiceApplyButton) {
                voiceApplyButton.disabled = true;
                voiceApplyButton.innerHTML = '<i class="fas fa-save mr-1"></i> Save Price Changes';
            }
            if (voicePreviewPanel) {
                voicePreviewPanel.classList.add('d-none');
            }
            if (voiceSummary) {
                voiceSummary.textContent = '';
            }
            if (voicePreviewRows) {
                voicePreviewRows.innerHTML = '';
            }
            if (voicePreviewNote) {
                voicePreviewNote.textContent = '';
            }
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));
        }

        function renderVoicePreview(payload) {
            resetVoicePreview();
            lastVoicePreview = payload;

            if (voiceSummary) {
                voiceSummary.textContent = payload.summary || payload.message || '';
            }

            const rows = Array.isArray(payload.rows) ? payload.rows : [];
            const visibleRows = rows.slice(0, 40);

            if (voicePreviewRows) {
                voicePreviewRows.innerHTML = visibleRows.map((row) => `
                    <tr>
                        <td>
                            <strong>${escapeHtml(row.product_title)}</strong>
                            <div class="text-muted small">${escapeHtml(row.category_titles)}</div>
                        </td>
                        <td>${escapeHtml(row.weight_name)}</td>
                        <td>
                            ${escapeHtml(row.display_old_sell_price)}
                            <span class="mx-1">to</span>
                            <span class="voice-preview-price-change">${escapeHtml(row.display_new_sell_price)}</span>
                        </td>
                        <td>
                            ${escapeHtml(row.display_old_list_price)}
                            <span class="mx-1">to</span>
                            <span class="voice-preview-price-change">${escapeHtml(row.display_new_list_price)}</span>
                        </td>
                    </tr>
                `).join('');
            }

            if (voicePreviewNote) {
                voicePreviewNote.textContent = rows.length > visibleRows.length
                    ? `Showing first ${visibleRows.length} of ${rows.length} affected rows. Confirm updates all previewed rows.`
                    : `${rows.length} affected row${rows.length === 1 ? '' : 's'} will be updated after confirmation.`;
            }

            if (voicePreviewPanel) {
                voicePreviewPanel.classList.remove('d-none');
            }
            if (voiceApplyButton) {
                voiceApplyButton.disabled = rows.length === 0;
            }
        }

        async function postVoiceCommand(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const responseText = await response.text();
            let data = {};

            try {
                data = responseText ? JSON.parse(responseText) : {};
            } catch (error) {
                if (response.status === 419) {
                    throw new Error('Your admin session expired. Refresh the page, sign in again, and retry.');
                }

                throw new Error('The server returned an invalid response. Refresh the page and retry.');
            }

            if (response.status === 419) {
                throw new Error('Your admin session expired. Refresh the page, sign in again, and retry.');
            }

            if (response.status === 401 || response.status === 403) {
                throw new Error('You do not have permission to update daily prices. Ask a Super Admin for Daily Price Edit access.');
            }

            if (!response.ok || !data.success) {
                const examples = Array.isArray(data.examples) && data.examples.length
                    ? ` Examples: ${data.examples.join(' | ')}`
                    : '';
                throw new Error((data.message || 'Unable to process the command.') + examples);
            }

            return data;
        }

        async function previewVoiceCommand() {
            if (!voiceCommandInput || !voicePreviewButton) {
                return;
            }

            const command = voiceCommandInput.value.trim();
            if (!command) {
                setVoiceStatus('Type or speak a price command first.', 'warning');
                resetVoicePreview();
                return;
            }

            voicePreviewButton.disabled = true;
            voicePreviewButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Previewing';
            setVoiceStatus('Checking matching products and calculating new prices...', 'info');

            try {
                const payload = await postVoiceCommand(voicePreviewUrl, {
                    command,
                    apply_list_price: voiceApplyListPrice?.checked ? 1 : 0,
                });

                renderVoicePreview(payload);
                setVoiceStatus(payload.message || 'Preview ready.', 'success');
            } catch (error) {
                resetVoicePreview();
                setVoiceStatus(error.message || 'Unable to preview command.', 'danger');
            } finally {
                voicePreviewButton.disabled = false;
                voicePreviewButton.innerHTML = '<i class="fas fa-eye mr-1"></i> Preview';
            }
        }

        async function applyVoiceCommand() {
            if (!lastVoicePreview || !Array.isArray(lastVoicePreview.rows) || lastVoicePreview.rows.length === 0) {
                setVoiceStatus('Preview the command first.', 'warning');
                return;
            }

            if (!window.confirm(`Update ${lastVoicePreview.rows.length} price row${lastVoicePreview.rows.length === 1 ? '' : 's'} now?`)) {
                return;
            }

            voiceApplyButton.disabled = true;
            voiceApplyButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving';
            setVoiceStatus('Updating prices...', 'info');

            try {
                const payload = await postVoiceCommand(voiceApplyUrl, {
                    command: voiceCommandInput.value.trim(),
                    apply_list_price: voiceApplyListPrice?.checked ? 1 : 0,
                    weight_ids: lastVoicePreview.rows.map((row) => row.weight_id),
                });

                setVoiceStatus(payload.message || 'Prices updated.', 'success');
                setTimeout(() => window.location.reload(), 900);
            } catch (error) {
                setVoiceStatus(error.message || 'Unable to update prices.', 'danger');
                voiceApplyButton.disabled = false;
                voiceApplyButton.innerHTML = '<i class="fas fa-save mr-1"></i> Save Price Changes';
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                setChecks(this.checked);
            });
        }

        if (selectVisible) {
            selectVisible.addEventListener('click', function () {
                setChecks(true);
            });
        }

        if (clearSelected) {
            clearSelected.addEventListener('click', function () {
                setChecks(false);
            });
        }

        if (pageSizeSelect) {
            pageSizeSelect.addEventListener('change', applyDailyPriceHeaderFilters);
        }

        if (headerSearch) {
            headerSearch.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyDailyPriceHeaderFilters();
                }
            });
            headerSearch.addEventListener('change', applyDailyPriceHeaderFilters);
        }

        document.querySelectorAll('.voice-example').forEach((button) => {
            button.addEventListener('click', function () {
                if (voiceCommandInput) {
                    voiceCommandInput.value = this.dataset.command || '';
                    resetVoicePreview();
                    setVoiceStatus('', 'info');
                    voiceCommandInput.focus();
                }
            });
        });

        if (voiceClearButton) {
            voiceClearButton.addEventListener('click', function () {
                if (voiceCommandInput) {
                    voiceCommandInput.value = '';
                }
                resetVoicePreview();
                setVoiceStatus('', 'info');
            });
        }

        if (voiceApplyListPrice) {
            voiceApplyListPrice.addEventListener('change', function () {
                resetVoicePreview();
                if (voiceCommandInput?.value.trim()) {
                    setVoiceStatus('List-price option changed. Preview the command again before saving.', 'info');
                }
            });
        }

        if (voiceCommandInput) {
            voiceCommandInput.addEventListener('input', function () {
                resetVoicePreview();
                setVoiceStatus(
                    this.value.trim() ? 'Command changed. Preview it before saving.' : '',
                    'info'
                );
            });
        }

        if (voicePreviewButton) {
            voicePreviewButton.addEventListener('click', previewVoiceCommand);
        }

        if (voiceApplyButton) {
            voiceApplyButton.addEventListener('click', applyVoiceCommand);
        }

        if (voiceStartButton) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                voiceStartButton.disabled = true;
                voiceStartButton.title = 'Voice input is not supported in this browser. Type the command manually.';
            } else {
                const recognition = new SpeechRecognition();
                recognition.lang = 'en-IN';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;

                voiceStartButton.addEventListener('click', function () {
                    resetVoicePreview();
                    setVoiceStatus('Listening... speak the price command now.', 'info');
                    voiceStartButton.disabled = true;
                    voiceStartButton.innerHTML = '<i class="fas fa-microphone-alt mr-1"></i> Listening';
                    try {
                        recognition.start();
                    } catch (error) {
                        voiceStartButton.disabled = false;
                        voiceStartButton.innerHTML = '<i class="fas fa-microphone mr-1"></i> Speak';
                        setVoiceStatus('Microphone is already active. Wait a moment and try again.', 'warning');
                    }
                });

                recognition.onresult = async function (event) {
                    const transcript = event.results?.[0]?.[0]?.transcript || '';
                    if (voiceCommandInput) {
                        voiceCommandInput.value = transcript;
                        voiceCommandInput.focus();
                    }
                    setVoiceStatus('Voice captured. Creating the price preview...', 'info');
                    await previewVoiceCommand();
                };

                recognition.onerror = function (event) {
                    const message = event.error === 'not-allowed'
                        ? 'Microphone permission was blocked. Allow microphone access in the browser, then try again.'
                        : (event.error ? `Voice input failed: ${event.error}` : 'Voice input failed. Type the command manually.');
                    setVoiceStatus(message, 'warning');
                };

                recognition.onend = function () {
                    voiceStartButton.disabled = false;
                    voiceStartButton.innerHTML = '<i class="fas fa-microphone mr-1"></i> Speak';
                };
            }
        }

        priceInputs.forEach((input) => {
            input.addEventListener('input', function () {
                const current = Number(this.dataset.current || 0);
                const next = Number(this.value || 0);

                if (current !== next) {
                    this.classList.add('is-edited');
                } else {
                    this.classList.remove('is-edited');
                }
            });
        });

        ajaxButtons.forEach((button) => {
            button.addEventListener('click', async function () {
                const rowId = this.dataset.row;
                const row = document.querySelector(`tr[data-price-row="${rowId}"]`);
                const status = document.querySelector(`[data-row-status="${rowId}"]`);
                const sellInput = row?.querySelector('.row-sell-price');
                const listInput = row?.querySelector('.row-list-price');

                if (!row || !sellInput || !listInput) {
                    return;
                }

                this.disabled = true;
                const originalText = this.textContent;
                this.textContent = 'Saving...';
                if (status) {
                    status.textContent = 'Saving...';
                    status.className = 'small ajax-save-status text-muted';
                }

                try {
                    const response = await fetch(this.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            sell_price: sellInput.value,
                            list_price: listInput.value,
                        }),
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Unable to save price.');
                    }

                    sellInput.dataset.current = payload.sell_price;
                    listInput.dataset.current = payload.list_price;
                    sellInput.value = payload.sell_price;
                    listInput.value = payload.list_price;
                    sellInput.classList.remove('is-edited');
                    listInput.classList.remove('is-edited');

                    const sellCurrent = row.querySelector('.current-sell-price');
                    const listCurrent = row.querySelector('.current-list-price');

                    if (sellCurrent) {
                        sellCurrent.textContent = `Current ${payload.display_sell_price || ('₹' + payload.sell_price)}`;
                    }

                    if (listCurrent) {
                        listCurrent.textContent = `Current ${payload.display_list_price || ('₹' + payload.list_price)}`;
                    }

                    if (status) {
                        status.textContent = payload.message || 'Saved.';
                        status.className = 'small ajax-save-status text-success';
                    }
                } catch (error) {
                    if (status) {
                        status.textContent = error.message || 'Save failed.';
                        status.className = 'small ajax-save-status text-danger';
                    }
                } finally {
                    this.disabled = false;
                    this.textContent = originalText;
                }
            });
        });
    })();
</script>
@endpush
