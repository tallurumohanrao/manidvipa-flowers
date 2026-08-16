@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
        <div class="row">
            <div class="col-md-12"><h2>Product Reviews - {{ $product->title }}</h2></div>
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
                    <td>{!! $review->comment !!}</td>
                    <td>
                        <label class="switch">
                        {{ html()->checkbox('status', $review->status, null)->class('status')->id('status_'.$review->id)->attributes(['data-id'=>$review->id,'data-url'=>route('admin.products.review.update.status',['id'=>$review->id])]) }}
                        <span class="slider round"></span>
                        </label>
                    </td>
                    <td>{{$review->created_at}}</td>
                    <td>
                        <div class="btn-group">
                            <a href="javascript:;" class="delete btn btn-danger" data-id="{{ $review->id }}" data-url="{{ route('admin.products.reviews.destroy',['id'=>$review->id]) }}"><i class="fas fa-trash"></i></a>
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
