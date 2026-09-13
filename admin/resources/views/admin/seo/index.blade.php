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
                    <a href="{{ route('admin.'.$module.'.create') }}" class="btn btn-primary">Create SEO entry</a>
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
            	<div class="row mb-3">
            		<div class="col-sm-12 d-flex align-items-center justify-content-between">
            			<ul class="list-inline">
            				<li class="list-inline-item">Show</li>
            				<li class="list-inline-item">
                                {!! html()->select('perPage',pageNumbers())->value(request('perPage'))->id('perPage')->class('custom-select custom-select-sm form-control form-control-sm w-80')->attributes(['onchange'=>'$("#search").submit();']) !!}
                            </li>
                			<li class="list-inline-item">entries</li>
            			</ul>

            			<ul class="list-inline">
            				<li class="list-inline-item">
                                {{ html()->text('url',request('url'))->class('form-control form-control-sm')->placeholder('URL') }}
                            </li>
            				{{--<li class="list-inline-item">
                                {!! Form::text('alias', request()->get('alias') ,['class' => 'form-control form-control-sm', 'placeholder' => 'Alias', 'autocomplete' => 'off', 'aria-controls'=>'dataTable']) !!}
                            </li>--}}
                            <li class="list-inline-item">
                            {!! html()->select('status', array('' => 'Status', '1' => 'Enable', '0' => 'Disable'))->value(request('status'))->id('status')->class('custom-select custom-select-sm form-control form-control-sm w-80') !!}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->button('Search','submit')->class('form-control form-control-sm') !!}
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
                                    <th>S.No.</th>
                                    <th>URL</th>
                                    {{--<th>Alias</th>--}}
                                    <th>Page Title</th>
                                    <th>Meta Keywords</th>
                                    <th>Meta Description</th>
                                    <th>Schema</th>
                                    <th>Robots</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($data as $seo)
                                <tr id="row-{{ $seo->id }}">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                        {!! html()->checkbox('id[]')->value($seo->id)->id($seo->id)->class('custom-control-input') !!}
                                        <label class="custom-control-label" for="{{ $seo->id }}"></label>
                                        </div>
                                    </td>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$seo->url}}</td>
                                    {{--<td>{{$seo->alias}}</td>--}}
                                    <td>{{$seo->page_title}}</td>
                                    <td>{{$seo->meta_keywords}}</td>
                                    <td>{{$seo->meta_description}}</td>
                                    <td>
                                        @if(!empty($seo->schema_markup))
                                            <span class="badge badge-success">Added</span>
                                        @else
                                            <span class="badge badge-secondary">Empty</span>
                                        @endif
                                    </td>
                                    <td>{{$seo->robots }}</td>
                                    <td>
                                        @can($module.'_edit')
                                        <label class="switch">
                                        {{ html()->checkbox('status', $seo->status, null)->class('status')->id('status_'.$seo->id)->attributes(['aria-label'=>'Toggle SEO status', 'data-id'=>$seo->id,'data-url'=>route('admin.'.$module.'.update.status',$seo)]) }}
                                        <span class="slider round"></span>
                                        </label>
                                        @else <span class="badge badge-{{ $seo->status ? 'success' : 'secondary' }}">{{ $seo->status ? 'Enabled' : 'Disabled' }}</span> @endcan
                                    </td>
                                    <td>{{$seo->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.'.$module.'.edit',$seo) }}" title="Edit SEO page" aria-label="Edit SEO page"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                            <a href="javascript:;" class="delete" title="Delete SEO page" aria-label="Delete SEO page" data-id="{{ $seo->id }}" data-url="{{ route('admin.'.$module.'.destroy',$seo) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
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
                    {{ $data->onEachSide(config('onEachSide'))->links() }}
                	</div>
                </div>
            </div>
        </div>
	</div>
</section>
@endsection
