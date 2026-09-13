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
                @can($module.'_create')
                <a href="{{ route('admin.'.$module.'.create') }}" class="btn btn-primary">Create</a>
                @endcan
                @can($module.'_delete')
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                @endcan
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
                {{ html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open() }}
                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        @include('admin.includes.items')
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                {{ html()->text('question',request('question'))->class('form-control')->placeholder('Question')->required() }}
                            </li>
                            <li class="list-inline-item">
                                {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->value(request('status'))->id('status')->class('form-control') !!}
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
                                        <th>ID</th>
                                        <th>Question</th>
                                        <th>Answer</th>
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
                                        <td>{{$row->id}}</td>
                                        <td>{!! $row->question !!}</td>
                                        <td>{!! $row->answer !!}</td>
                                        <td>
                                            @can($module.'_edit')
                                            <label class="switch">
                                            {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['aria-label'=>'Toggle FAQ status', 'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',$row)]) }}
                                            <span class="slider round"></span>
                                            </label>
                                            @else <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? 'Enabled' : 'Disabled' }}</span> @endcan
                                        </td>
                                        <td>{{$row->created_at}}</td>
                                        <td>
                                            @can($module.'_edit')
                                                <a href="{{ route('admin.'.$module.'.edit',$row) }}" title="Edit FAQ" aria-label="Edit FAQ"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                            @endcan
                                            @can($module.'_delete')
                                                <a href="javascript:;" class="delete" title="Delete FAQ" aria-label="Delete FAQ" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',$row) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
                                            @endcan
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
