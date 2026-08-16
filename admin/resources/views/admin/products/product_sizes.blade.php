@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.sizes',['id'=>$product->id]) }}">Add/Remove Product Sizes  - {{ $product->title }}</a>
        </h4>
		<hr>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
                    {{ html()->form('POST')->route('admin.'.$module.'.sizesstore',['id'=>$product->id])->class('form-horizontal')->id('form')->open() }}
            			<table class="table table-bordered table-hover">
                            <thead>
                                <tr role="row">
                                    {{-- <th>
                                        <div class="custom-control custom-checkbox">
                                            {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th> --}}
                                    <th scope="col">S.No</th>
                                    <th scope="col">Size</th>
                                    <th scope="col">Sell Price</th>
                                    <th scope="col">List Price</th>
                                    <th scope="col">Cost Price</th>
                                    <th scope="col">Status</th>
                                    {{-- <th scope="col">Created At</th> --}}
                                    <th><a href="javascript:;" onclick="addSizes()"><i class="fa fa-plus-circle"></i></a></th>
                                </tr>
                            </thead>

                            <tbody id="tablecontents">
                            @foreach($sizes as $row)
                                <tr id="row-{{ $loop->iteration }}">
                                    {{-- <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td> --}}
                                    <td>
                                        {{ $loop->iteration }}
                                        {{ html()->hidden('Size['.$loop->index.'][id]', $row->id) }}
                                    </td>
                                    <td>
                                        {!! html()->select('Size['.$loop->index.'][name]',$selectboxsizes,$row->name)->class('form-control')->placeholder('-- Select --','')->required() !!}
                                    </td>
                                    <td>
                                        {{ html()->text('Size['.$loop->index.'][sell_price]', $row->sell_price)->class('form-control')->required() }}
                                    </td>
                                    <td>
                                        {{ html()->text('Size['.$loop->index.'][list_price]', $row->list_price)->class('form-control') }}
                                    </td>
                                    <td>
                                        {{ html()->text('Size['.$loop->index.'][cost_price]', $row->cost_price)->class('form-control') }}
                                    </td>
                                    <td>
                                        {!! html()->select('Size['.$loop->index.'][status]',[''=>'-- Select --','1'=>'Enable','0'=>'Disable'],$row->status)->class('form-control') !!}
                                        {{-- <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.size.update.status',['id'=>$row->id])]) }}
                                        <span class="slider round"></span>
                                        </label> --}}
                                    </td>
                                    {{-- <td>{{$row->created_at}}</td> --}}
                                    <td>
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete btn btn-danger" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.size.destroy',['id'=>$row->id]) }}"><i class="fa fa-trash"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Save</button>
                    {{ html()->form()->close() }}
                    </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</section>
<script>
    var row_no ={{ $sizes->count() + 1 }};
</script>
@endsection
@push('script')
<script>
function addSizes()
{
    row = '<tr id="row-'+ row_no +'">';
    // row += '<td></td>';
    row += '<td>'+row_no+'<input name="Size[' + row_no + '][id]" type="hidden" value=""></td>';
    row += '<td><select class="select2 form-control" autocomplete="off" name="Size[' + row_no + '][name]">{!! $shtml !!}</select></td>';
    row += '<td><input name="Size[' + row_no + '][sell_price]" class="form-control" type="text"></td>';
    row += '<td><input name="Size[' + row_no + '][list_price]" class="form-control" type="text"></td>';
    row += '<td><input name="Size[' + row_no + '][cost_price]" class="form-control" type="text"></td>';
    row += '<td><select class="form-control" autocomplete="off" name="Size[' + row_no + '][status]"><option value="1">Enable</option><option value="2">Disable</option></select></td>';
    row += '<td> <a onclick="$(\'#row-' + row_no + '\').remove();"  class="btn btn-danger" ><i class="fa fa-trash"></i></a> </td>';
    row += '</tr>';
    $('#tablecontents').append(row);
    row_no++;
}
</script>
@endpush
