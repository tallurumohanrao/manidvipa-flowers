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
                <a class="btn btn-primary" href="javascript:document.getElementById('FormButton').click();"><i class="fa fa-save mr-1"></i>Save</a>
                @endcan
                @can($module.'_delete')
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                @endcan
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
                    {{ html()->form('POST')->route('admin.'.$module.'.store')->class('search')->id('search')->attributes(['enctype'=>'multipart/form-data'])->open() }}
            			<table class="table table-bordered table-hover">
                            <thead>
                                <tr role="row">
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>S.No.</th>
                                    <th>Upload</th>
                                    <th>Image</th>
                                    {{--<th>Is Before & After?</th>--}}
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th><a href="javascript:;" onclick="addGallery()"><i class="fa fa-plus-circle"></i></a></th>
                                </tr>
                            </thead>

                            <tbody id="tablecontents">
                            @foreach($data as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $loop->iteration }}
                                        {!! html()->hidden('Gallery['.$loop->index.'][id]', $row->id ) !!}
                                        {!! html()->hidden('Gallery['.$loop->index.'][old_file]', $row->image ) !!}
                                    </td>

                                    <td>{!! html()->file('Gallery['.$loop->index.'][image]') !!}</td>
                                    <td>{{ html()->img(asset('storage/'.$module.'/'. @$row->image ), null)->attributes(array('title' => @$row->image ,'width' => '70px')) }}</td>
                                    {{--<td>
                                        <label class="switch">
                                        {{ Form::checkbox('type', null, $row->type ==1?true:false,['class'=>'status','id'=>'type_'.$row->id,'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.isbeforafter.status',$row)]) }}
                                        <span class="slider round"></span>
                                        </label>
                                    </td>--}}
                                    <td>
                                        {!! html()->text('Gallery['.$loop->index.'][priority]', $row->priority)->class('form-control') !!}
                                    </td>
                                    <td>
                                        <label class="switch">
                                        {!! html()->checkbox('status', $row->status ==1?true:false)->id($row->id)->class('status')->id('status_'.$row->id)->attributes(['data-url'=>route('admin.'.$module.'.update.status',$row)]) !!}
                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete btn btn-danger" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy',$row) }}"><i class="fa fa-trash"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        {!! html()->submit('FormButton','SAVE')->class('btn btn-primary d-none')->id('FormButton') !!}
                    {{ html()->form()->close() }}
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
<script>
    var row_no ={{ $data->count() + 1 }};
</script>
@endsection

@push('script')
<script>
function addGallery()
{
    row = '<tr id="row-'+ row_no +'">';
    row += '<td></td>';
    row += '<td>'+row_no+'</td>';
    row += '<td><input name="Gallery[' + row_no + '][image]" type="file" required></td>';
    row += '<td></td>';
    //row += '<td><select class="form-control" autocomplete="off" name="Gallery[' + row_no + '][type]"><option value="1">Yes</option><option value="0">No</option></select></td>';
    row += '<td><input name="Gallery[' + row_no + '][priority]" class="form-control" type="text"></td>';
    row += '<td><select class="form-control" autocomplete="off" name="Gallery[' + row_no + '][status]"><option value="1">Enable</option><option value="2">Disable</option></select></td>';
    row += '<td></td>';
    row += '<td> <a onclick="$(\'#row-' + row_no + '\').remove();"  class="btn btn-danger" ><i class="fa fa-trash"></i></a> </td>';
    row += '</tr>';
    $('#tablecontents').prepend(row);
    row_no++;
}
</script>
@endpush
