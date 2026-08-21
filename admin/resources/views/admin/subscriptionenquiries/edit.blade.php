@extends('admin.layouts.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="{{ route('admin.'.$module.'.show', $row) }}" class="btn btn-secondary">View</a>
                    <a href="{{ route('admin.'.$module.'.index') }}" class="btn btn-danger">Cancel</a>
                </div>
            </li>
        </ul>
        <hr>

        <div class="card shadow mb-4">
            <div class="card-body">
                <h5>Update Subscription Enquiry</h5>
                <hr>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $row->name }}</p>
                        <p><strong>Phone:</strong> <a href="tel:{{ $row->phone }}">{{ $row->phone }}</a></p>
                        <p><strong>Organization:</strong> {{ $row->organization_name ?: '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Plan:</strong> {{ $row->plan_title ?: optional($row->plan)->title ?: 'Custom Plan' }}</p>
                        <p><strong>Business Type:</strong> {{ $row->business_type ?: '-' }}</p>
                        <p><strong>Location:</strong> {{ $row->location ?: '-' }}</p>
                    </div>
                </div>

                {{ html()->model($row)->form('PATCH')->route('admin.'.$module.'.update', $row)->open() }}
                <div class="row">
                    <div class="col-md-4">
                        <label class="col-form-label" for="status">Follow-up Status</label>
                        {!! html()->select('status', $enquiryStatuses)->id('status')->class('form-control') !!}
                    </div>
                    <div class="col-md-12">
                        <label class="col-form-label" for="admin_notes">Admin Notes</label>
                        {{ html()->textarea('admin_notes')->class('form-control')->rows(6)->placeholder('Add follow-up notes, quote details or next action.') }}
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Status</button>
                </div>
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</section>
@endsection
