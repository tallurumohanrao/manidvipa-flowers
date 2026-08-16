@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">
                @if(request('booking_type') == 1)
                Offline
                @endif
                {{ $module }}</a>
        </h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                @can($module.'_create')
                    @if(request('booking_type') == 1)
                    <a href="{{ route('admin.'.$module.'.create',['booking_type'=>request('booking_type')]) }}" class="btn btn-primary">Create</a>
                    @endif
                @endcan
                @can($module.'_delete1')
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                @endcan
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body" style="overflow-x:scroll;">
                {{ html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open() }}
                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        @include('admin.includes.items')
                        <ul class="list-inline">
                            <li class="list-inline-item">
                            {{ html()->hidden('booking_type',request('booking_type')) }}
                            {{ html()->text('name',request('name'))->class('form-control-sm')->placeholder('Name') }}
                            </li>
                            <li class="list-inline-item">
                            {{ html()->date('booking_date',request('booking_date'))->class('form-control-sm')->placeholder('Booking Date') }}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->select('theater', $theaters)->value(request('theater'))->class('custom-select custom-select-sm form-control form-control-sm w-80')->placeholder('-- Theater --') !!}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->select('booking_status', bookingstatuses())->value(request('booking_status'))->class('custom-select custom-select-sm form-control form-control-sm w-80')->placeholder('-- Booking Status --') !!}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->select('payment_status', array_merge(paymentstatuses(),['captured'=>'Captured','authorized'=>'Authorized','failed'=>'Failed']))->value(request('payment_status'))->class('custom-select custom-select-sm form-control form-control-sm w-80')->placeholder('-- Payment Status --') !!}
                            </li>
                            <li class="list-inline-item">
                            {!! html()->button('Search','submit')->class('btn btn-primary form-control form-control-sm') !!}
                            </li>
                        </ul>
                    </div>
                </div>
                {{ html()->form()->close() }}
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
            			<table class="table table-bordered  table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Whatsapp Number</th>
                                    <th>Booking Date</th>
                                    <th>No of Persons</th>
                                    <th>Payment Status</th>
                                    <th>Booking Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            @foreach($data as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>{{ $row->id }}</td>
                                    <td>{{$row->name}}</td>
                                    <td>{{$row->email}}</td>
                                    <td>{{$row->whatsapp_number}}</td>
                                    <td>{{$row->booking_date}}</td>
                                    <td>{{$row->no_of_persons}}</td>
                                    <td>{{$row->payment_status}}</td>
                                    <td>@if($row->booking_status_id) {{ bookingStatuses()[$row->booking_status_id]}} @endif</td>
                                    <td>{{$row->created_at}}</td>
                                    <td>
                                    @can($module.'_edit')
                                        <a href="{{ route('admin.'.$module.'.show',['booking'=>$row->id,'booking_type'=>request('booking_type')]) }}"><i class="fas fa-eye p-1"></i></a>
                                        <a href="{{ route('admin.'.$module.'.print',['booking'=>$row->id,'booking_type'=>request('booking_type')]) }}"><i class="fas fa-print p-1"></i></a>
                                    @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class="row">
            		<div class="col-sm-12">
            		    {{ $data->links() }}
                    </div>
                </div>
            </div>
        </div>
	</div>
</section>
@endsection
