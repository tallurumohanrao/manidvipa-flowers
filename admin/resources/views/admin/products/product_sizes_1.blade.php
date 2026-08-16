@extends('admin.layouts.app')
@section('content')
<div class="container mt-2">
    <div class="row">
        <div class="col-md-12">Add/Edit Product Sizes - {{ $product->title }}</div>
    </div>
    <div class="row">
        <div class="col-md-12">
            {{ html()->form('POST')->route('admin.'.$module.'.sizesstore', ['id'=>$product->id])->open() }}
            <label class="col-form-label" for="sizes">Select Sizes</label>
            {!! html()->multiselect('Sizes[]',$selectboxsizes,$selected)->id('sizes')->class('select2 form-control') !!}
            <button type="submit" class="btn btn-primary">Submit</button>
            {{ html()->form()->close() }}
        </div>
    </div>
</div>

<div class="container mt-2">
    <div class="row">
        <div class="card-body p-0">
            <div class="list-master-table table-responsive">
                <table class="table table-centered table-nowrap data-table table-hover m-0 tablegrid">
                    <thead class="table-head table-filters">
                        <tr>
                            <th scope="col">S.No</th>
                            <th scope="col">Size</th>
                            <th scope="col">Sell Price</th>
                            <th scope="col">List Price</th>
                            <th scope="col">Cost Price</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tablecontents">
                    @foreach($sizes as $size)
                        <tr id="row-{{ $size->id }}" class="row1" data-id="{{ $size->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $size->name }}</td>
                            <td>{{ $size->sell_price }}</td>
                            <td>{{ $size->list_price }}</td>
                            <td>{{ $size->cost_price }}</td>
                            <td>
                                <label class="switch">
                                {{ html()->checkbox('status', $size->status, null)->class('status')->id('status_'.$size->id)->attributes(['data-id'=>$size->id,'data-url'=>route('admin.products.images.update.status',['id'=>$size->id])]) }}
                                <span class="slider round"></span>
                                </label>
                            </td>
                            <td><a href="javascript:;" class="delete" data-id="{{ $size->id }}" data-url="{{ route('admin.products.images.destroy',['id'=>$size->id]) }}"><i class="fas fa-trash text-danger p-1"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
