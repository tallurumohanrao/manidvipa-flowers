@extends('admin.layouts.app')
@section('styles')
{{ Html::style('assets/admin/css/bootstrap.min.css') }}
<link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/4.0.1/min/dropzone.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/min/dropzone.min.js"></script>
@stop
@section('content')
<div class="container mt-2">
    <div class="row">
        <div class="col-md-12">
            <form action="{{ route('admin.products.images',$product) }}" method="post" enctype="multipart/form-data" id="image-upload" class="dropzone">
                @csrf
            </form>
        </div>
    </div>
</div>
   
<script type="text/javascript">
        Dropzone.options.imageUpload = {
           // maxFilesize         :       1,
            //acceptedFiles: ".jpeg,.jpg,.png,.gif"
        };
</script>
<div class="container mt-2">
    {{--<div class="row">
        @foreach($product->images as $image)
        <div class="col-md-3">
            {{ Html::image(asset('storage/products/'. @$image->image ), @$image->image , array('title' => @$product->title ,'class' => 'img-fluid')) }}
        </div>
            @endforeach
    </div>--}}
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
            <tbody id="tablecontents" data-url="{{ route('admin.product.images.update.sort') }}">
            @foreach($product->images as $image)
                <tr id="row-{{ $image->id }}" class="row1" data-id="{{ $image->id }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ Html::image(asset('storage/products/'. @$image->image ), null , array('title' => @$image->image ,'width' => '70px')) }}</td>
                    <td>
                        <label class="switch">
                        {{ Form::checkbox('status', null, $image->status,['class'=>'status','id'=>'status_'.$image->id,'data-id'=>$image->id,'data-url'=>route('admin.product.images.update.status',$image)]) }}
                        <span class="slider round"></span>
                        </label>
                    </td>
                    <td><a href="javascript:;" class="delete" data-id="{{ $image->id }}" data-url="{{ route('admin.products.images.destroy',$image) }}"><i class="fas fa-trash text-danger p-1"></i></a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection