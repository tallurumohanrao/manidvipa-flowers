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

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('fail'))
            <div class="alert alert-danger">{{ session('fail') }}</div>
        @endif

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
        </div>

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
                                @foreach([25, 50, 100, 200] as $option)
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

            <div class="card shadow mb-4">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <div>
                        <strong>{{ $data->total() }} price rows found</strong>
                        <span class="text-muted small ml-2">Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }}</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="select-visible">Select visible</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="clear-selected">Clear selected</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i> Save Price Updates
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive daily-price-table-wrap">
                        <table class="table table-bordered table-hover mb-0 daily-price-table">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 42px;">
                                        <input type="checkbox" id="select-all-prices">
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
                                            <input type="checkbox" class="price-row-check" name="selected_weights[]" value="{{ $row->weight_id }}">
                                        </td>
                                        <td class="align-middle">
                                            @if($row->image_name)
                                                <img src="{{ asset('storage/products/'.$row->image_name) }}" alt="{{ $row->product_title }}" class="daily-price-image">
                                            @else
                                                <div class="daily-price-placeholder">
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
                                                <input type="number" step="0.01" min="0" class="form-control price-input row-sell-price" name="prices[{{ $row->weight_id }}][sell_price]" value="{{ number_format((float) $row->sell_price, 2, '.', '') }}" data-current="{{ number_format((float) $row->sell_price, 2, '.', '') }}" data-row="{{ $row->weight_id }}">
                                            </div>
                                            <small class="text-muted current-sell-price">Current ₹{{ number_format((float) $row->sell_price, 2) }}</small>
                                        </td>
                                        <td class="align-middle">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">₹</span>
                                                </div>
                                                <input type="number" step="0.01" min="0" class="form-control price-input row-list-price" name="prices[{{ $row->weight_id }}][list_price]" value="{{ number_format((float) $row->list_price, 2, '.', '') }}" data-current="{{ number_format((float) $row->list_price, 2, '.', '') }}" data-row="{{ $row->weight_id }}">
                                            </div>
                                            <small class="text-muted current-list-price">Current ₹{{ number_format((float) $row->list_price, 2) }}</small>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge badge-{{ (int) $row->weight_status === 1 ? 'success' : 'secondary' }}">
                                                {{ (int) $row->weight_status === 1 ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <button type="button" class="btn btn-sm btn-success ajax-price-save mb-1" data-row="{{ $row->weight_id }}" data-url="{{ route('admin.dailyprices.weights.update', ['id' => $row->weight_id]) }}">
                                                Save
                                            </button>
                                            <a href="{{ route('admin.products.weights', ['id' => $row->product_id]) }}" target="_blank" class="btn btn-sm btn-outline-primary mb-1">
                                                Weights
                                            </a>
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
                        <div class="col-md-5">
                            <div class="dataTables_info">Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }} of {{ $data->total() }} price rows</div>
                        </div>
                        <div class="col-md-7">
                            <div class="float-right">{{ $data->links() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <strong>Recent Price Updates</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
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
                                        @if($log->update_source !== 'rollback')
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
    }

    .daily-price-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8f9fc;
    }

    .daily-price-image,
    .daily-price-placeholder {
        width: 56px;
        height: 56px;
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
</style>
@endpush

@push('script')
<script>
    (function () {
        const selectAll = document.getElementById('select-all-prices');
        const selectVisible = document.getElementById('select-visible');
        const clearSelected = document.getElementById('clear-selected');
        const checks = () => Array.from(document.querySelectorAll('.price-row-check'));
        const priceInputs = Array.from(document.querySelectorAll('.price-input'));
        const ajaxButtons = Array.from(document.querySelectorAll('.ajax-price-save'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function setChecks(value) {
            checks().forEach((input) => {
                input.checked = value;
            });
            if (selectAll) {
                selectAll.checked = value;
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
