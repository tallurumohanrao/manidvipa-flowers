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

    <div class="col-md-12">
        <label class="col-form-label" for="schema_markup">Schema JSON-LD</label>
        {{ html()->textarea('schema_markup')->rows(8)->class('form-control')->placeholder('{ "@context": "https://schema.org", "@type": "WebPage", "name": "Page name" }') }}
        <small class="text-muted">
            Paste valid JSON-LD only. You can also paste the full &lt;script type="application/ld+json"&gt;...&lt;/script&gt; code; only the JSON will be saved.
        </small>
        @error('schema_markup')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="robots">Robots</label>
        {{ html()->textarea('robots')->class('form-control') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="status">Status*</label>
        {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>

</div>
