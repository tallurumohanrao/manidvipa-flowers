@extends('admin.layouts.app')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="heading mb-0">Create Homepage Section</h4>
            <a href="{{ route('admin.home_sections.index') }}" class="btn btn-secondary">Back</a>
        </div>
        <div class="card shadow mb-4">
            <div class="card-body">
                {{ html()->form('POST')->route('admin.home_sections.store')->id('form')->open() }}
                    @include('admin.home_sections.form')
                    @include('admin.partials.save')
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</section>
@endsection
