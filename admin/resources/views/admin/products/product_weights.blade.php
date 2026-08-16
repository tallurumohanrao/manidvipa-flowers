@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.weights',['id'=>$product->id]) }}">Add/Remove Product Weights  - {{ $product->title }}</a>
        </h4>
		<hr>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
                    {{ html()->form('POST')->route('admin.'.$module.'.weightsstore',['id'=>$product->id])->class('form-horizontal')->id('form')->open() }}
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
                                    <th scope="col">Weight</th>
                                    <th scope="col">Sell Price</th>
                                    <th scope="col">List Price</th>
                                    <th scope="col">Cost Price</th>
                                    {{--<th scope="col">GST %</th>
                                    <th scope="col">Enable Vat</th>--}}
                                    <th scope="col">Stock Qty</th>
                                    <th scope="col">Enable Stock</th>
                                    <th scope="col">Status</th>
                                    {{-- <th scope="col">Created At</th> --}}
                                    <th><a href="javascript:;" onclick="addWeights()"><i class="fa fa-plus-circle"></i></a></th>
                                </tr>
                            </thead>

                            <tbody id="tablecontents">
                            @foreach($weights as $row)
                                <tr id="row-{{ $row->id }}">
                                    {{-- <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td> --}}
                                    <td>
                                        {{ $loop->iteration }}
                                        {{ html()->hidden('Weight['.$loop->index.'][id]', $row->id) }}
                                    </td>
                                    <td>
                                        {!! html()->select('Weight['.$loop->index.'][name]',$selectboxweights,$row->name)->class('form-control')->placeholder('-- Select --','')->required() !!}
                                    </td>
                                    <td>
                                        {{ html()->text('Weight['.$loop->index.'][sell_price]', $row->sell_price)->class('form-control')->required() }}
                                    </td>
                                    <td>
                                        {{ html()->text('Weight['.$loop->index.'][list_price]', $row->list_price)->class('form-control')->required() }}
                                    </td>
                                    <td>
                                        {{ html()->text('Weight['.$loop->index.'][cost_price]', $row->cost_price)->class('form-control') }}
                                    </td>
                                    <td>{{ html()->text('Weight['.$loop->index.'][qty]', $row->qty)->class('form-control') }}</td>
                                    {{--<td>{{ html()->text('Weight['.$loop->index.'][vat_price]', $row->vat_price )->class('form-control') }}</td>
                                    <td>
                                    {{ html()->checkbox('Weight['.$loop->index.'][vat_enable]', 1, $row->vat_enable == 1)->class('form-control') }}
                                    </td>--}}
                                    <td>
                                    {{ html()->checkbox('Weight['.$loop->index.'][stock]', $row->stock == 1, 1)->class('form-control') }}
                                    </td>
                                    <td>
                                        {!! html()->select('Weight['.$loop->index.'][status]',[''=>'-- Select --','1'=>'Enable','0'=>'Disable'],$row->status)->class('form-control')->required() !!}
                                        {{-- <label class="switch">
                                        {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.weight.update.status',['id'=>$row->id])]) }}
                                        <span class="slider round"></span>
                                        </label> --}}
                                    </td>
                                    {{-- <td>{{$row->created_at}}</td> --}}
                                    <td>
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete btn btn-danger" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.weight.destroy',['id'=>$row->id]) }}"><i class="fa fa-trash"></i></a>
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
    var row_no ={{ $weights->count() + 1 }};
</script>
@endsection
@push('script')
<script>
function addWeights()
{
    row = '<tr id="row-'+ row_no +'">';
    // row += '<td></td>';
    row += '<td>'+row_no+'<input name="Weight[' + row_no + '][id]" type="hidden" value=""></td>';
    row += '<td><select class="select2 form-control" autocomplete="off" name="Weight[' + row_no + '][name]" required>{!! $shtml !!}</select></td>';
    row += '<td><input name="Weight[' + row_no + '][sell_price]" class="form-control" type="text" required></td>';
    row += '<td><input name="Weight[' + row_no + '][list_price]" class="form-control" type="text" required></td>';
    row += '<td><input name="Weight[' + row_no + '][cost_price]" class="form-control" type="text"></td>';
    row += '<td><input class="form-control" autocomplete="off" name="Weight[' + row_no + '][qty]" type="number" step="any"></td>';
    //row += '<td><input class="form-control" autocomplete="off" name="Weight[' + row_no + '][vat_price]" type="number" step="any"></td>';
    //row += '<td><input autocomplete="off" name="Weight[' + row_no + '][vat_enable]" type="checkbox" value="1"></td>';
    row += '<td><input autocomplete="off" name="Weight[' + row_no + '][stock]" class="form-control" type="checkbox" value="1" checked></td>';
    row += '<td><select class="form-control" autocomplete="off" name="Weight[' + row_no + '][status]"><option value="1">Enable</option><option value="2">Disable</option></select></td>';
    row += '<td> <a onclick="$(\'#row-' + row_no + '\').remove();"  class="btn btn-danger" ><i class="fa fa-trash"></i></a> </td>';
    row += '</tr>';
    $('#tablecontents').append(row);
    row_no++;
}
</script>
@endpush
