@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
        <h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">{{ $module }}</a>
        </h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                @can($module.'_delete')
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                @endcan
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
                {{ html()->form('GET')->route('admin.'.$module.'.index',['type'=>request('type')])->id('search')->open() }}
                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        @include('admin.includes.items')
                    <ul class="list-inline d-flex">
                        {{--<li class="list-inline-item">
                            {!! Form::select('property_id', $property_categories, request('asset_category_id'), [ 'id' => 'asset_category_id','class' => 'form-control', 'autocomplete' => 'off' ]) !!}
                        </li>--}}
                        <li class="list-inline-item">
                        {{ html()->hidden('type',request('type')) }}
                        {{ html()->text('email',request('email'))->class('form-control')->placeholder('Email') }}
                        <li class="list-inline-item">
                        {{ html()->text('mobile',request('mobile'))->class('form-control')->placeholder('Mobile') }}
                        </li>
                        <li class="list-inline-item">
                        {!! html()->button('Search','submit')->class('btn btn-primary') !!}
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
                                    <th scope="col">ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Mobile</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Products</th>
                                    <th scope="col">Message</th>
                                    <th scope="col">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                        {!! html()->checkbox('id[]')->value($row->id)->id($row->id)->class('custom-control-input') !!}
                                        <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->mobile }}</td>
                                    <td>{{ $row->email }}</td>
                                    <td>{{ $row->subject }}</td>
                                    <td>{{ $row->message }}</td>
                                    <td>{{ $row->created_at }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class="row">
                	<div class="col-sm-12 col-md-5">
                		<p>Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} entries</p>
                	</div>
                	<div class="col-sm-12 col-md-7">
                        {{ $data->onEachSide(config('onEachSide'))->links() }}
                	</div>
                </div>
            </div>
        </div>
	</div>
</section>
@endsection
