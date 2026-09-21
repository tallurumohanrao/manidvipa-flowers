@extends('admin.layouts.app')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="heading mb-0">Homepage Sections</h4>
            <div class="btn-group">
                @can($module.'_edit')
                    <button type="button" class="btn btn-outline-primary" id="save-home-section-order" data-url="{{ route('admin.home_sections.reorder') }}">Save order</button>
                @endcan
                @can($module.'_create')
                    <a href="{{ route('admin.home_sections.create') }}" class="btn btn-primary">Create</a>
                @endcan
                @can($module.'_delete')
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.home_sections.massdestroy') }}" role="button">Delete</a>
                @endcan
            </div>
        </div>
        @can($module.'_edit')
            <div id="home-section-order-feedback" class="alert d-none" role="status"></div>
        @endcan

        <div class="alert alert-info">
            Change the priority to rearrange homepage sections. Built-in sections control the original homepage blocks; product sections can be created from a category or selected products. Lower priority numbers appear first.
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table data-admin-table="drag" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <div class="custom-control custom-checkbox">
                                        {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                        <label class="custom-control-label" for="selectAll"></label>
                                    </div>
                                </th>
                                <th>Section</th>
                                <th>Category</th>
                                <th>Items</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Updated At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                                <tr id="row-{{ $row->id }}" draggable="true" data-section-id="{{ $row->id }}" class="homepage-section-row">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id('section_'.$row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="section_{{ $row->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        @can($module.'_edit')
                                            <span class="homepage-section-drag-handle" title="Drag to rearrange" aria-label="Drag to rearrange"><i class="fas fa-grip-vertical" aria-hidden="true"></i></span>
                                        @endcan
                                        <strong>{{ $row->title }}</strong>
                                        @if($row->is_system)
                                            <span class="badge badge-info ml-1">Built-in</span>
                                        @endif
                                        @if($row->subtitle)
                                            <div class="text-muted small">{{ $row->subtitle }}</div>
                                        @endif
                                        @if($row->button_text)
                                            <span class="badge badge-light mt-1">{{ $row->button_text }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $row->is_system ? 'Homepage content' : ($row->category_title ?: 'Category removed') }}
                                        @if($row->category_slug)
                                            <div class="text-muted small">/{{ $row->category_slug }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $row->max_items }}</td>
                                    <td>{{ $row->priority }}</td>
                                    <td>
                                        @can($module.'_edit')
                                            <label class="switch" title="Toggle section visibility">
                                                {!! html()->checkbox('status', (bool) $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['aria-label' => 'Toggle homepage section status', 'data-id' => $row->id, 'data-url' => route('admin.home_sections.update.status', $row->id)]) !!}
                                                <span class="slider round"></span>
                                            </label>
                                        @else
                                            <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? 'Enabled' : 'Disabled' }}</span>
                                        @endcan
                                    </td>
                                    <td>{{ $row->updated_at }}</td>
                                    <td>
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.home_sections.edit', $row->id) }}" title="Edit homepage section" aria-label="Edit homepage section"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                        @endcan
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete" title="Delete homepage section" aria-label="Delete homepage section" data-id="{{ $row->id }}" data-url="{{ route('admin.home_sections.destroy', $row->id) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">No homepage sections created yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-5">
                        <p>Showing {{ $data->firstItem() ?: 0 }} to {{ $data->lastItem() ?: 0 }} of {{ $data->total() }} entries</p>
                    </div>
                    <div class="col-sm-12 col-md-7">{{ $data->onEachSide(config('onEachSide'))->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('script')
<script>
    (function () {
        var tableBody = document.querySelector('.homepage-section-row')?.closest('tbody');
        var saveButton = document.getElementById('save-home-section-order');
        if (!tableBody || !saveButton) return;

        var draggedRow = null;
        tableBody.querySelectorAll('.homepage-section-row').forEach(function (row) {
            row.addEventListener('dragstart', function (event) {
                draggedRow = row;
                row.classList.add('table-primary');
                event.dataTransfer.effectAllowed = 'move';
            });
            row.addEventListener('dragend', function () {
                row.classList.remove('table-primary');
                draggedRow = null;
            });
            row.addEventListener('dragover', function (event) {
                event.preventDefault();
                if (!draggedRow || draggedRow === row) return;
                var rect = row.getBoundingClientRect();
                tableBody.insertBefore(draggedRow, event.clientY < rect.top + rect.height / 2 ? row : row.nextSibling);
                saveButton.classList.remove('btn-outline-primary');
                saveButton.classList.add('btn-primary');
            });
        });

        saveButton.addEventListener('click', function () {
            var ids = Array.from(tableBody.querySelectorAll('.homepage-section-row')).map(function (row) {
                return Number(row.dataset.sectionId);
            });
            saveButton.disabled = true;
            fetch(saveButton.dataset.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    order: ids,
                    _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                })
            }).then(async function (response) {
                var payload = await response.json().catch(function () { return {}; });
                if (!response.ok) {
                    var validationMessage = payload.message || (payload.errors && Object.values(payload.errors).flat().join(' '));
                    throw new Error(validationMessage || ('Unable to save order (HTTP ' + response.status + ')'));
                }
                return payload;
            }).then(function (result) {
                saveButton.textContent = result.message || 'Order saved';
                saveButton.classList.remove('btn-primary');
                saveButton.classList.add('btn-success');
                var feedback = document.getElementById('home-section-order-feedback');
                if (feedback) {
                    feedback.className = 'alert alert-success';
                    feedback.textContent = result.message || 'Homepage section order saved successfully.';
                }
            }).catch(function (error) {
                saveButton.textContent = error.message || 'Save order failed';
                saveButton.classList.remove('btn-primary');
                saveButton.classList.add('btn-danger');
                var feedback = document.getElementById('home-section-order-feedback');
                if (feedback) {
                    feedback.className = 'alert alert-danger';
                    feedback.textContent = error.message || 'The server rejected the new order.';
                }
            }).finally(function () {
                saveButton.disabled = false;
            });
        });
    }());
</script>
<style>
    .homepage-section-row { cursor: grab; }
    .homepage-section-row:active { cursor: grabbing; }
    .homepage-section-drag-handle { color: #6c757d; margin-right: .5rem; }
</style>
@endpush
