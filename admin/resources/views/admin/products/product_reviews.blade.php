@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
        <div class="row">
            <div class="col-md-12 d-flex align-items-center justify-content-between">
                <h2>Product reviews — {{ $product->title }}</h2>
                <div class="btn-group">
                    <a href="{{ route('admin.products.edit', ['product' => $product->id]) }}" class="btn btn-outline-primary">Edit product</a>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">All products</a>
                </div>
            </div>
        </div>
        <div class="row">
            <table class="table table-bordered  table-hover tablegrid">
            <thead>
                <tr role="row">
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Review</th>
                    <th>Publish</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            @foreach($review_ratings as $review)
                <tr id="row-{{ $review->id }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{$review->name}}</td>
                    <td>{{$review->email}}</td>
                    <td>{{ $review->comment }}</td>
                    <td>
                        @can($module.'_edit')
                            <label class="switch">
                            {{ html()->checkbox('status', $review->status, null)->class('status')->id('status_'.$review->id)->attributes(['aria-label'=>'Toggle review status', 'data-id'=>$review->id,'data-url'=>route('admin.products.review.update.status',['id'=>$review->id])]) }}
                            <span class="slider round"></span>
                            </label>
                        @else
                            <span class="badge badge-{{ $review->status ? 'success' : 'secondary' }}">{{ $review->status ? 'Published' : 'Hidden' }}</span>
                        @endcan
                    </td>
                    <td>{{$review->created_at}}</td>
                    <td>
                        <div class="btn-group">
                            @can($module.'_delete')
                                <a href="javascript:;" class="delete btn btn-danger" title="Delete review" aria-label="Delete review" data-id="{{ $review->id }}" data-url="{{ route('admin.products.reviews.destroy',['id'=>$review->id]) }}"><i class="fas fa-trash" aria-hidden="true"></i></a>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
