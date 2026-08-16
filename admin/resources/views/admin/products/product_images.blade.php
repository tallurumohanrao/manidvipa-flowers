@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/4.0.1/min/dropzone.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/min/dropzone.min.js"></script>
@endpush
@extends('admin.layouts.app')
@section('content')
<div class="container mt-2">
    <div class="row">
        <div class="col-md-12">
        Add/Remove Product Images - {{ $product->title }}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <form action="{{ route('admin.products.imagesstore',['id'=>$id]) }}" method="post" enctype="multipart/form-data" id="image-upload" class="dropzone">
                @csrf
            </form>
        </div>
    </div>
</div>

<div class="container mt-2">
    <div class="row">
        <div class="card-body p-0">
            <div class="list-master-table table-responsive">
                <a href="javascript:location.reload();" class="btn btn-primary">Reload</a>
                <table class="table table-centered table-nowrap data-table table-hover m-0 tablegrid">
                    <thead class="table-head table-filters">
                        <tr>
                            <th scope="col">S.No</th>
                            <th scope="col">Image</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tablecontents" class="row_position">
                    @foreach($images as $image)
                        <tr id="row-{{ $image->id }}" class="row1" data-id="{{ $image->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ html()->img(asset('storage/'.$module.'/100X100/'. @$image->name ), null)->attributes(array('title' => @$image->name ,'width' => '70px')) }}</td>
                            <td>
                                <label class="switch">
                                {{ html()->checkbox('status', $image->status, null)->class('status')->id('status_'.$image->id)->attributes(['data-id'=>$image->id,'data-url'=>route('admin.products.images.update.status',['id'=>$image->id])]) }}
                                <span class="slider round"></span>
                                </label>
                            </td>
                            <td><a href="javascript:;" class="delete" data-id="{{ $image->id }}" data-url="{{ route('admin.products.images.destroy',['id'=>$image->id]) }}"><i class="fas fa-trash text-danger p-1"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@stop

@push('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript">
Dropzone.options.imageUpload = {
   // maxFilesize         :       1,
    //acceptedFiles: ".jpeg,.jpg,.png,.gif"
};
$( ".row_position" ).sortable({
    delay: 150,
    stop: function() {
        var selectedData = new Array();
        $('.row_position>tr').each(function() {
            selectedData.push($(this).attr("data-id"));
        });
        updateOrder(selectedData);
    }
});


function updateOrder(data) {
    $.ajax({
        url:"{{ route('admin.product.images.update.sort') }}",
        type:'PATCH',
        headers: {
            'method':'POST',
        },
        data:{position:data},
        success:function(){
            Message.add('Updated successfully.', {type: 'success'});
            location.reload(true);
        }
    })
}
</script>
@endpush
