<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="url">URL</label>
        {{ html()->text('url')->class('form-control')->required() }}
    </div>

    {{--<div class="col-md-6">
        {!! Form::label('alias', 'Alias*', ['class' => 'col-form-label']) !!}
        {!! Form::text('alias', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>--}}

    <div class="col-md-6">
        <label class="col-form-label" for="page_title">Page Title*</label>
        {{ html()->text('page_title')->class('form-control')->required() }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="meta_keywords">Meta Keywords*</label>
        {{ html()->textarea('meta_keywords')->class('form-control') }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="meta_description">Meta Description*</label>
        {{ html()->textarea('meta_description')->class('form-control') }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="robots">Robots</label>
        {{ html()->textarea('robots')->class('form-control') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="status">Status*</label>
        {!! html()->select('status',array('1' => 'Enable', '2' => 'Disable'))->id('status')->class('form-control') !!}
    </div>

</div>
