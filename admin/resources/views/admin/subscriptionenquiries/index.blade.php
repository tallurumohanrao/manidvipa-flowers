@extends('admin.layouts.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">Subscription Enquiries</a>
        </h4>
        <hr>

        <ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
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
                    <div class="col-md-4">
                        {{ html()->text('q', request('q'))->class('form-control')->placeholder('Search name, phone, organization or location') }}
                    </div>
                    <div class="col-md-3">
                        {{ html()->text('business_type', request('business_type'))->class('form-control')->placeholder('Business type') }}
                    </div>
                    <div class="col-md-2">
                        {!! html()->select('status', $enquiryStatuses)->value(request('status'))->class('form-control')->placeholder('-- Status --') !!}
                    </div>
                    <div class="col-md-3">
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
                                <th>Customer</th>
                                <th>Organization</th>
                                <th>Plan</th>
                                <th>Business Type</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Created At</th>
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
                                        <strong>{{ $row->name }}</strong>
                                        <div><a href="tel:{{ $row->phone }}">{{ $row->phone }}</a></div>
                                        @if($row->email)
                                            <div><a href="mailto:{{ $row->email }}">{{ $row->email }}</a></div>
                                        @endif
                                    </td>
                                    <td>{{ $row->organization_name ?: '-' }}</td>
                                    <td>{{ $row->plan_title ?: optional($row->plan)->title ?: 'Custom Plan' }}</td>
                                    <td>{{ $row->business_type ?: '-' }}</td>
                                    <td>{{ $row->location ?: '-' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $row->status === 'Converted' ? 'success' : ($row->status === 'Closed' ? 'secondary' : 'warning') }}">
                                            {{ $row->status }}
                                        </span>
                                    </td>
                                    <td>{{ $row->created_at }}</td>
                                    <td>
                                        <a href="{{ route('admin.'.$module.'.show', $row) }}" title="View enquiry" aria-label="View enquiry"><i class="fas fa-eye p-1" aria-hidden="true"></i></a>
                                        @can($module.'_edit')
                                            <a href="{{ route('admin.'.$module.'.edit', $row) }}" title="Edit enquiry" aria-label="Edit enquiry"><i class="fas fa-edit p-1" aria-hidden="true"></i></a>
                                        @endcan
                                        @can($module.'_delete')
                                            <a href="javascript:;" class="delete" title="Delete enquiry" aria-label="Delete enquiry" data-id="{{ $row->id }}" data-url="{{ route('admin.'.$module.'.destroy', $row) }}"><i class="fas fa-trash text-danger p-1" aria-hidden="true"></i></a>
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
