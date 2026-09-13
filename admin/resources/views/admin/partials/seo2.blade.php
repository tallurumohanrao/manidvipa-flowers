<h5 class="text-capitalize pt-5">SEO</h5><hr>
<div class="row">
    <div class="col-md-12">
        {!! Form::label('url', 'URL*', ['class' => 'col-form-label']) !!}
        {!! Form::text('url', $url ?? null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off','readonly'=>true]) !!}
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        {!! Form::label('page_title', 'Page Title*', ['class' => 'col-form-label']) !!}
        {!! Form::text('page_title', $seo->page_title ?? null ,['rows'=>3,'class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>
    
</div>
<div class="row">
    <div class="col-md-12">
        {!! Form::label('meta_keywords', 'Meta Keywords*', ['class' => 'col-form-label']) !!}
        {!! Form::textarea('meta_keywords', $seo->meta_keywords ?? null ,['rows'=>3,'class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        {!! Form::label('meta_description', 'Meta Description*', ['class' => 'col-form-label']) !!}
        {!! Form::textarea('meta_description', $seo->meta_description ?? null ,['rows'=>3,'class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        {!! Form::label('schema_markup', 'Schema JSON-LD', ['class' => 'col-form-label']) !!}
        {!! Form::textarea('schema_markup', $seo->schema_markup ?? null ,['rows'=>8,'class' => 'form-control ', 'placeholder' => '{ "@context": "https://schema.org", "@type": "WebPage", "name": "Page name" }', 'autocomplete' => 'off']) !!}
        <small class="text-muted">
            Paste valid JSON-LD only. You can also paste the full &lt;script type="application/ld+json"&gt;...&lt;/script&gt; code; only the JSON will be saved.
        </small>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        {!! Form::label('robots', 'Robots*', ['class' => 'col-form-label']) !!}
        {!! Form::text('robots', $seo->robots ?? null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>
</div>
