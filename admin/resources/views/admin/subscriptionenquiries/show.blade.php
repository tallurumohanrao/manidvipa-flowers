@extends('admin.layouts.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-danger">Back</a>
                    @can($module.'_edit')
                        <a href="{{ route('admin.'.$module.'.edit', $row) }}" class="btn btn-primary">Update Status</a>
                    @endcan
                </div>
            </li>
        </ul>
        <hr>

        <div class="card shadow mb-4">
            <div class="card-body">
                <h5>Subscription Enquiry #{{ $row->id }}</h5>
                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr><th>Name</th><td>{{ $row->name }}</td></tr>
                            <tr><th>Phone</th><td><a href="tel:{{ $row->phone }}">{{ $row->phone }}</a></td></tr>
                            <tr>
                                <th>Email</th>
                                <td>
                                    @if($row->email)
                                        <a href="mailto:{{ $row->email }}">{{ $row->email }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr><th>Organization</th><td>{{ $row->organization_name ?: '-' }}</td></tr>
                            <tr><th>Business Type</th><td>{{ $row->business_type ?: '-' }}</td></tr>
                            <tr><th>Location</th><td>{{ $row->location ?: '-' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr><th>Plan</th><td>{{ $row->plan_title ?: optional($row->plan)->title ?: 'Custom Plan' }}</td></tr>
                            <tr><th>Preferred Time</th><td>{{ $row->preferred_delivery_time ?: '-' }}</td></tr>
                            <tr><th>Estimated Quantity</th><td>{{ $row->estimated_quantity ?: '-' }}</td></tr>
                            <tr><th>Status</th><td><span class="badge badge-warning">{{ $row->status }}</span></td></tr>
                            <tr><th>Source</th><td>{{ $row->source }}</td></tr>
                            <tr><th>Created At</th><td>{{ $row->created_at }}</td></tr>
                        </table>
                    </div>
                </div>

                <h6>Customer Message</h6>
                <div class="border rounded p-3 mb-3 bg-light">
                    {!! nl2br(e($row->message ?: '-')) !!}
                </div>

                <h6>Admin Notes</h6>
                <div class="border rounded p-3 bg-light">
                    {!! nl2br(e($row->admin_notes ?: '-')) !!}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
