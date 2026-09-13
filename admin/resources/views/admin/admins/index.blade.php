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
                        {{ html()->text('name',request('name'))->class('form-control')->placeholder('Name') }}
                        </li>
                        <li class="list-inline-item">
                        {{ html()->text('email',request('email'))->class('form-control')->placeholder('Email') }}
                        </li>
                        <li class="list-inline-item">
                        {!! html()->select('role', $roles)->value(request('role'))->id('role')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
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
                        <table class="table table-bordered  table-hover">
                            <thead>
                                <tr role="row">
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Avatar</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($data as $row)
                            <tr id="row-{{ $row->id }}">
                                <td>{{$row->id}}</td>
                                <td>{{$row->name}}</td>
                                <td>{{$row->email}}</td>
                                <td>
                                    @if(@$row->image && File::exists('storage/'.$module.'/'. @$row->image ))
                                    {{ html()->img(asset('storage/'.$module.'/'. @$row->image ), null)->attributes(array('title' => @$row->name ,'width' => '42px','class'=>'img-radius')) }}
                                   @endif
                                </td>
                                <td>{{ collect($row->roles->pluck('name'))->join(',') }}</td>
                                <td>
                                    @can($module.'_edit')
                                    <label class="switch">
                                    {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['aria-label'=>'Toggle status for '.$row->name, 'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',$row)]) }}
                                    <span class="slider round"></span>
                                    </label>
                                    @else <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? 'Enabled' : 'Disabled' }}</span> @endcan
                                </td>
                                <td>{{$row->created_at}}</td>
                                <td>
                                    @can($module.'_edit')
                                    <a href="{{ route('admin.'.$module.'.edit',$row) }}" title="Edit admin" aria-label="Edit admin"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                    @endcan
                                    @can($module.'_delete')
                                    <a href="javascript:;" class="delete" title="Delete admin" aria-label="Delete admin" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',$row) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
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
@stop
