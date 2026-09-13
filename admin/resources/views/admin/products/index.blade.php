@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<div class="card shadow mb-4">
            <div class="card-body">
                @include('admin.includes.index')
                {{ html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open() }}
            <div class="row">
                <div class="col-md-12 d-flex align-items-center justify-content-between">
                    @include('admin.includes.items')
                    <ul class="list-inline">
                        <li class="list-inline-item">
                        {{ html()->text('title',request('title'))->class('form-control')->placeholder('Title') }}
                        </li>
                        <li class="list-inline-item">
                        {{ html()->text('sku',request('sku'))->class('form-control')->placeholder('SKU') }}
                        </li>
                        <li class="list-inline-item">
                        {!! html()->select('category', $categories)->placeholder('-- Category --')->value(request('category'))->id('category')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
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
            			<table class="table table-bordered  table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Categories</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                        <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->title }}</td>
                                    <td>{{ $row->sku }}</td>
                                    <td>{{ $row->display_quantity ?? '-' }}</td>
                                    <td>{{ $row->category_titles ?: '-' }}</td>
                                    <td>
                                        @can($module.'_edit')
                                        <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['aria-label'=>'Toggle status for '.$row->title, 'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',['id'=>$row->id])]) }}
                                        <span class="slider round"></span>
                                        </label>
                                        @else
                                            <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? 'Enabled' : 'Disabled' }}</span>
                                        @endcan
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit',['product'=>$row->id]) }}" title="Edit {{ $row->title }}" aria-label="Edit {{ $row->title }}"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                            <a href="{{ route('admin.'.$module.'.images',['id'=>$row->id]) }}" title="Manage images for {{ $row->title }}" aria-label="Manage images for {{ $row->title }}"><i class="fas fa-image p-1" aria-hidden="true"></i></a>
                                            <a href="{{ route('admin.'.$module.'.weights',['id'=>$row->id]) }}" title="Manage weights for {{ $row->title }}" aria-label="Manage weights for {{ $row->title }}"><i class="fas fa-balance-scale p-1" aria-hidden="true"></i></a>
                                        @endcan
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete" title="Delete {{ $row->title }}" aria-label="Delete {{ $row->title }}" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',['product'=>$row->id]) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
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
