@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
        <h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">Order Statuses</a>
        </h4>
		<hr>
        <div class="alert alert-info small">
            This is a master setup page for notification messages. A real order number is available only on an actual order, so this page shows a sample preview with order #125.
        </div>
        @php
            $plainTemplateText = static function ($value) {
                $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = str_replace("\xC2\xA0", ' ', $text);
                return trim(preg_replace('/\s+/u', ' ', $text));
            };
            $formatTemplateText = static function ($value) {
                $value = e($value ?? '');
                return preg_replace_callback('/\{\{([A-Za-z0-9_.]+)\}\}/', static function ($matches) {
                    $labels = [
                        'order.id' => 'Order No',
                    ];
                    $label = $labels[$matches[1]] ?? ucwords(str_replace(['.', '_'], ' ', $matches[1]));
                    return '<span class="badge badge-info status-template-token" title="Real order number will appear here">'.e($label).'</span>';
                }, $value);
            };
            $previewTemplateText = static function ($value) use ($plainTemplateText) {
                $text = $plainTemplateText($value);
                return preg_replace('/\{\{[A-Za-z0-9_.]+\}\}/', '#125', $text);
            };
        @endphp
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                    @can($module.'_create')
                    <a href="{{ route('admin.'.$module.'.create') }}" class="btn btn-primary">Create</a>
                    @endcan
                  {{--<a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>--}}
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
            			<table class="table table-bordered  table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th>
                                        @can($module.'_delete')
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                        @endcan
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Subject Template</th>
                                    <th>Message Template</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($data as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>
                                        @can($module.'_delete')
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                        @endcan
                                    </td>
                                    <td>{{$row->id}}</td>
                                    <td>{{$row->name}}</td>
                                    <td>
                                        {!! $formatTemplateText($row->subject) !!}
                                        <div class="text-muted small mt-1">Example: {{ $previewTemplateText($row->subject) }}</div>
                                    </td>
                                    <td>
                                        {!! $formatTemplateText(\Illuminate\Support\Str::limit($plainTemplateText($row->body_html), 120)) !!}
                                        <div class="text-muted small mt-1">Example: {{ \Illuminate\Support\Str::limit($previewTemplateText($row->body_html), 120) }}</div>
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        <div class="btn-group">
                                            @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit',['orderstatus'=>$row->id]) }}" title="Edit order status" aria-label="Edit order status"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                            @endcan
                                            {{--<a href="javascript:;" class="delete btn btn-danger" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',$row) }}"><i class="ti-trash"></i></a>--}}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</section>
@endsection
