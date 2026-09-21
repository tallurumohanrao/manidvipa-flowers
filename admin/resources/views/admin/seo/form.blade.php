<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="url">Public Page URL*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">https://www.manidvipaflowers.com</span>
            </div>
            {{ html()->text('url')->class('form-control')->placeholder('/puja-flowers/chamanthi')->required() }}
        </div>
        <small class="form-text text-muted">Fully editable. Paste a complete URL or enter only the path; it will be saved as a clean path.</small>
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="alias">System Page Path*</label>
        @if(isset($seo) && $seo->exists)
            {{ html()->text('alias')->class('form-control')->attribute('readonly', 'readonly')->required() }}
        @else
            {{ html()->text('alias')->class('form-control')->placeholder('/about')->required() }}
        @endif
        <small class="form-text text-muted">The existing page that opens at the public URL. Keep this unchanged when you only rename the URL.</small>
    </div>

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
