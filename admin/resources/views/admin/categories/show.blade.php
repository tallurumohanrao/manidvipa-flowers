@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/4.0.1/min/dropzone.min.css" rel="stylesheet">
@endpush
@extends('admin.layouts.app')
@section('content')
<div class="container mt-2">
    <div class="row">
        <div class="col-md-12">
            <form action="{{ route('admin.projects.images',$project) }}" method="post" enctype="multipart/form-data" id="image-upload" class="dropzone">
                @csrf
            </form>
        </div>
    </div>
</div>

<div class="container mt-2">
    {{--<div class="row">
        @foreach($project->images as $image)
        <div class="col-md-3">
            {{ Html::image(asset('storage/projects/'. @$image->image ), @$image->image , array('title' => @$project->title ,'class' => 'img-fluid')) }}
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
            <tbody id="tablecontents" data-url="{{ route('admin.project.images.update.sort') }}">
            @foreach($project->images as $image)
                <tr id="row-{{ $image->id }}" class="row1" data-id="{{ $image->id }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ html()->img(asset('storage/projects/'. @$image->image ))->attributes(['title' => @$image->image ,'width' => '70px']) }}</td>
                    <td>
                        <label class="switch">
                        {{ html()->checkbox('status', $image->status, null)->class('status')->id('status_'.$image->id)->attributes(['data-id'=>$image->id,'data-url'=>route('admin.project.images.update.status',$image)]) }}
                        <span class="slider round"></span>
                        </label>
                    </td>
                    <td><a href="javascript:;" class="delete" data-id="{{ $image->id }}" data-url="{{ route('admin.projects.images.destroy',$image) }}"><i class="fas fa-trash text-danger p-1"></i></a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
@stop
@push('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/min/dropzone.min.js"></script>

<script type="text/javascript">
    Dropzone.options.imageUpload = {
       // maxFilesize         :       1,
        //acceptedFiles: ".jpeg,.jpg,.png,.gif"
    };
</script>
@endpush
