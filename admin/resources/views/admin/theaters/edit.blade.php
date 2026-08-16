@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <h5>Edit Theater</h5>
                <hr>
                {{ html()->model($row)->form('PATCH')->route('admin.'.$module.'.update', $row)->class('')->id('form')->attributes(['enctype'=>'multipart/form-data'])->open() }}
                @include('admin.'.$module.'.form')
                @include('admin.partials.save')
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</section>
<script>
    var slotrow_no ={{ $slots->count() + 1 }};
    var row_no ={{ $images->count() + 1 }};
</script>
@endsection

@push('script')
<script>
function addSlots()
{
    slotrow = '<tr id="slotrow-'+ slotrow_no +'">';
    slotrow += '<td>'+slotrow_no+'<input name="Slot[' + slotrow_no + '][id]" type="hidden"></td></td>';
    slotrow += '<td><input name="Slot[' + slotrow_no + '][timings]" class="form-control" type="text"></td>';
    slotrow += '<td><select class="form-control" autocomplete="off" name="Slot[' + slotrow_no + '][status]"><option value="1">Enable</option><option value="2">Disable</option></select></td>';
    slotrow += '<td></td>';
    slotrow += '<td> <a onclick="$(\'#slotrow-' + slotrow_no + '\').remove();"  class="btn btn-danger" ><i class="fa fa-trash"></i></a> </td>';
    slotrow += '</tr>';
    $('#slottablecontents').append(slotrow);
    slotrow_no++;
}
function addGallery()
{
    row = '<tr id="row-'+ row_no +'">';
    row += '<td>'+row_no+'</td>';
    row += '<td><input name="Gallery[' + row_no + '][id]" type="hidden"></td>';
    row += '<td><input name="Gallery[' + row_no + '][image]" type="file" required></td>';
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
