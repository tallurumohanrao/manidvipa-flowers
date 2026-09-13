<div class="row pt-2">
    <div class="col-md-12">
        <h5 class="text-capitalize">SEO</h5><hr>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <label for="url" class="col-form-label">URL*</label>
        {{ html()->hidden('seo[old_url]',$seoOldUrl ?? ($seo->url ?? null))->class('form-control') }}
        {{ html()->text('seo[url]',$seoUrl ?? ($seo->url ?? null))->class('form-control')->required() }}
    </div>
    <div class="col-md-6">
        <label for="page_title" class="col-form-label">Page Title</label>
        {{ html()->textarea('seo[page_title]',$seo->page_title ?? null)->rows(3)->class('form-control') }}
    </div>
    <div class="col-md-6">
        <label for="meta_keywords" class="col-form-label">Meta Keywords</label>
        {{ html()->textarea('seo[meta_keywords]',$seo->meta_keywords ?? null)->rows(3)->class('form-control') }}
    </div>
    <div class="col-md-6">
        <label for="meta_description" class="col-form-label">Meta Description</label>
        {{ html()->textarea('seo[meta_description]',$seo->meta_description ?? null)->rows(3)->class('form-control') }}
    </div>
    <div class="col-md-12">
        <label for="schema_markup" class="col-form-label">Schema JSON-LD</label>
        {{ html()->textarea('seo[schema_markup]',$seo->schema_markup ?? null)->rows(8)->class('form-control')->placeholder('{ "@context": "https://schema.org", "@type": "Product", "name": "Product name" }') }}
        <small class="text-muted">
            Paste valid JSON-LD only. You can also paste the full &lt;script type="application/ld+json"&gt;...&lt;/script&gt; code; only the JSON will be saved.
        </small>
        @error('seo.schema_markup')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label for="robots" class="col-form-label">Robots</label>
        {{ html()->text('seo[robots]',$seo->robots ?? null)->class('form-control') }}
    </div>
</div>
