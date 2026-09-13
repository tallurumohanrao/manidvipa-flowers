@extends('admin.layouts.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">Subscription Plans</a>
        </h4>
        <hr>

        <ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    @can($module.'_create')
                        <a href="{{ route('admin.'.$module.'.create') }}" class="btn btn-primary">Create</a>
                    @endcan
                    @can($module.'_delete')
                        <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
                    @endcan
                </div>
            </li>
        </ul>

        <div class="card shadow mb-4">
            <div class="card-body">
                {{ html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open() }}
                <div class="row mb-3">
                    <div class="col-md-3">
                        {{ html()->text('q', request('q'))->class('form-control')->placeholder('Search title, flowers or description') }}
                    </div>
                    <div class="col-md-2">
                        {!! html()->select('business_type', $businessTypes)->value(request('business_type'))->class('form-control')->placeholder('-- Business Type --') !!}
                    </div>
                    <div class="col-md-3">
                        {!! html()->select('subscription_type', $subscriptionTypes)->value(request('subscription_type'))->class('form-control')->placeholder('-- Package Type --') !!}
                    </div>
                    <div class="col-md-2">
                        {!! html()->select('status', ['1' => 'Enabled', '0' => 'Disabled'])->value(request('status'))->class('form-control')->placeholder('-- Status --') !!}
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
                {{ html()->form()->close() }}

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <div class="custom-control custom-checkbox">
                                        {!! html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input') !!}
                                        <label class="custom-control-label" for="selectAll"></label>
                                    </div>
                                </th>
                                <th>S.No</th>
                                <th>Image</th>
                                <th>Plan</th>
                                <th>Segment / Package</th>
                                <th>What Customer Gets</th>
                                <th>Price</th>
                                <th>Refresh / Flowers</th>
                                <th>Home</th>
                                <th>Sort</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            {!! html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input') !!}
                                            <label class="custom-control-label" for="{{ $row->id }}"></label>
                                        </div>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($row->image && File::exists('storage/'.$module.'/'. $row->image))
                                            {{ html()->img(asset('storage/'.$module.'/'. $row->image), null)->attributes(['title' => $row->title, 'width' => '80px']) }}
                                        @else
                                            <span class="badge badge-light">No image</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $row->title }}</strong>
                                        <div class="text-muted small">{{ $row->short_description }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $row->business_type }}</strong>
                                        <div class="mt-1">
                                            <span class="badge badge-info">{{ $row->subscription_type ?: 'Premium Arrangements' }}</span>
                                        </div>
                                        @if($row->flower_grade)
                                            <div class="text-muted small mt-1">Grade: {{ $row->flower_grade }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->included_arrangement_count)
                                            <strong>{{ $row->included_arrangement_count }}</strong>
                                        @elseif($row->included_quantity_text)
                                            <strong>{{ $row->included_quantity_text }}</strong>
                                        @else
                                            <span class="text-muted">Not configured</span>
                                        @endif
                                        @if($row->included_arrangement_count && $row->included_quantity_text)
                                            <div class="text-muted small">{{ $row->included_quantity_text }}</div>
                                        @endif
                                        @if($row->arrangement_size)
                                            <div class="text-muted small">Size: {{ $row->arrangement_size }}</div>
                                        @endif
                                        @if($row->minimum_commitment)
                                            <div class="text-muted small">Commitment: {{ $row->minimum_commitment }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{!! $row->starting_price > 0 ? '&#8377;'.number_format($row->starting_price, 0) : 'Custom Quote' !!}</strong>
                                        <span>{{ $row->price_suffix }}</span>
                                        <div class="text-muted small">{{ $row->billing_cycle }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $row->refresh_frequency ?: $row->delivery_frequency }}</strong>
                                        @if($row->flower_examples)
                                            <div class="text-muted small">
                                                {{ \Illuminate\Support\Str::limit(str_replace(["\r\n", "\r", "\n"], ', ', $row->flower_examples), 90) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->is_featured)
                                            <span class="badge badge-success">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->sort_order }}</td>
                                    <td>
                                        @can($module.'_edit')
                                        <label class="switch">
                                            {{ html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['aria-label'=>'Toggle status for '.$row->title, 'data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status', ['id' => $row->id])]) }}
                                            <span class="slider round"></span>
                                        </label>
                                        @else <span class="badge badge-{{ $row->status ? 'success' : 'secondary' }}">{{ $row->status ? 'Enabled' : 'Disabled' }}</span> @endcan
                                    </td>
                                    <td>
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit', $row) }}" title="Edit subscription plan" aria-label="Edit subscription plan"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                        @endcan
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete" title="Delete subscription plan" aria-label="Delete subscription plan" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy', $row) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-sm-12 col-md-5">
                        <p>Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} entries</p>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        {{ $data->onEachSide(config('onEachSide'))->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
