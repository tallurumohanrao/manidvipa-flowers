@extends('admin.layouts.app')
@push('styles')
<style>
    .category-guide-card {
        border: 1px solid #ead8dd;
        border-radius: 12px;
        background: #fffaf7;
        padding: 14px 16px;
        height: 100%;
    }

    .category-guide-card .guide-title {
        color: #8f1235;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .02em;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .category-guide-card .guide-text {
        color: #5f4a4f;
        font-size: 13px;
        margin-bottom: 0;
    }

    .category-tree-table th {
        white-space: nowrap;
        color: #4c3038;
        font-size: 13px;
    }

    .category-tree-table td {
        vertical-align: middle;
    }

    .category-parent-row {
        background: #fff9f1;
    }

    .category-child-row {
        background: #ffffff;
    }

    .category-structure {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 260px;
    }

    .category-branch {
        color: #b0133d;
        font-size: 20px;
        line-height: 1.2;
        margin-left: 18px;
    }

    .category-title {
        color: #24161a;
        font-weight: 800;
        font-size: 15px;
        line-height: 1.25;
    }

    .category-parent-title {
        color: #84646c;
        display: block;
        font-size: 12px;
        margin-top: 2px;
    }

    .category-badge {
        border-radius: 999px;
        display: inline-block;
        font-size: 11px;
        font-weight: 800;
        margin-left: 6px;
        padding: 4px 8px;
        text-transform: uppercase;
    }

    .category-badge-main {
        background: #8f1235;
        color: #ffffff;
    }

    .category-badge-child {
        background: #fff0d9;
        color: #9b4a00;
    }

    .category-badge-home {
        background: #e7f6ee;
        color: #10763a;
    }

    .category-badge-muted {
        background: #f1eef0;
        color: #76626a;
    }

    .category-image {
        border-radius: 10px;
        height: 58px;
        object-fit: cover;
        width: 72px;
    }
</style>
@endpush
@section('content')
<section class="content">
    <div class="container-fluid">
		<div class="card shadow mb-4">
            <div class="card-body">
                @include('admin.includes.index')
                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <div class="category-guide-card">
                            <div class="guide-title">Main categories</div>
                            <p class="guide-text">No parent. Use these for Home and main menu pages like Puja Flowers, Premium Flowers, Garlands.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="category-guide-card">
                            <div class="guide-title">Child categories</div>
                            <p class="guide-text">Assigned under a main category. Example: Chamanthi, Banthi and Kanakambaram under Puja Flowers.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="category-guide-card">
                            <div class="guide-title">Frontend result</div>
                            <p class="guide-text">Home shows only main categories marked "Show on Home". Parent pages include products from their child categories.</p>
                        </div>
                    </div>
                </div>
                {{ html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open() }}
                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        @include('admin.includes.items')
                        <ul class="list-inline">
                            <li class="list-inline-item">
                            {{ html()->text('title',request('title'))->class('form-control form-control-sm')->placeholder('Search category') }}
                            </li>
                            <li class="list-inline-item">
                                {!! html()->select('category_type', array('' => 'All Category Types', 'main' => 'Main Categories', 'child' => 'Child Categories', 'home' => 'Shown on Home'))->value(request('category_type'))->id('category_type')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
                            </li>
                            <li class="list-inline-item">
                                {!! html()->select('status', array('' => 'Status', '1' => 'Enable', '0' => 'Disable'))->value(request('status'))->id('status')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->button('Search','submit')->class('btn btn-primary form-control form-control-sm') !!}
                            </li>
                        </ul>
                    </div>
                </div>
                {{ html()->form()->close() }}
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
                        <table class="table table-bordered table-hover tablegrid category-tree-table">
                            <thead>
                                <tr role="row">
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>No.</th>
                                    <th>Category Structure</th>
                                    <th>Products Page Use</th>
                                    <th>Children</th>
                                    <th>Image</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($data as $row)
                                @php
                                    $isChildCategory = !empty($row->parent_id);
                                @endphp
                                <tr id="row-{{ $row->id }}" class="{{ $isChildCategory ? 'category-child-row' : 'category-parent-row' }}">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                        <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td>
                                    <td>{{ $data->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="category-structure">
                                            @if($isChildCategory)
                                                <span class="category-branch">&rdsh;</span>
                                            @endif
                                            <div>
                                                <span class="category-title">{{ $row->title }}</span>
                                                @if($isChildCategory)
                                                    <span class="category-badge category-badge-child">Child</span>
                                                    <small class="category-parent-title">Under: {{ $row->parent_title ?: 'Parent missing' }}</small>
                                                @else
                                                    <span class="category-badge category-badge-main">Main</span>
                                                    @if($row->home_category)
                                                        <span class="category-badge category-badge-home">Home</span>
                                                    @endif
                                                    <small class="category-parent-title">Parent category for frontend pages</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($isChildCategory)
                                            <span class="category-badge category-badge-muted">Inside {{ $row->parent_title ?: 'parent page' }}</span>
                                        @elseif($row->home_category)
                                            <span class="category-badge category-badge-home">Shown on Home</span>
                                        @else
                                            <span class="category-badge category-badge-muted">Main page only</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$isChildCategory)
                                            <strong>{{ $row->children_count }}</strong>
                                            <small class="text-muted d-block">child categories</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(@$row->image)
                                            {{ html()->img(asset('storage/'.$module.'/'. @$row->image ), null)->attributes(array('title' => @$row->title ,'class' => 'category-image')) }}
                                        @else
                                            <span class="text-muted">No image</span>
                                        @endif
                                    </td>
                                    <td>
                                        <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',['id'=>$row->id])]) }}
                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit',['category'=>$row->id]) }}"><i class="fas fa-edit p-1"></i></a>
                                        @endcan
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',['category'=>$row->id]) }}"><i class="fas fa-trash text-danger p-1"></i></a>
                                        @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class="row">
                	<div class="col-sm-12 col-md-5">
                		<div class="dataTables_info" id="dataTable_info" role="status" aria-live="polite">Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} entries</div>
                	</div>
                	<div class="col-sm-12 col-md-7">
                		{{ $data->links() }}
                	</div>
                </div>
            </div>
        </div>
	</div>
</section>
@endsection
