@if($isSystem ?? false)
<div class="alert alert-info">
    This is a built-in homepage section. You can change its order and visibility here; its content remains part of the storefront design.
</div>
{{ html()->hidden('section_type', data_get($row, 'section_type')) }}
<div class="row">
    <div class="col-md-8">
        <label class="col-form-label">Section</label>
        <div class="form-control-plaintext font-weight-bold">{{ data_get($row, 'title') }}</div>
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="priority">Priority</label>
        {{ html()->number('priority')->class('form-control')->attributes(['min' => 0])->value(old('priority', data_get($row, 'priority', 0)))->required() }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status', ['1' => 'Show', '0' => 'Hide'], data_get($row, 'status', 1))->id('status')->class('form-control')->required() !!}
    </div>
</div>
@else
<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="title">Section Title</label>
        {{ html()->text('title')->class('form-control')->placeholder('Bouquets & Gifting')->required() }}
    </div>
    <div class="col-md-6">
        <label class="col-form-label" for="subtitle">Subtitle</label>
        {{ html()->text('subtitle')->class('form-control')->placeholder('Fresh arrangements for every occasion') }}
    </div>
    <div class="col-md-4">
        <label class="col-form-label" for="section_type">Section Type</label>
        {!! html()->select('section_type', ['category_products' => 'Products from a category', 'featured_products' => 'Selected featured products'], data_get($row, 'section_type', 'category_products'))->id('section_type')->class('form-control')->required() !!}
    </div>
    <div class="col-md-8" id="category-section-field">
        <label class="col-form-label" for="category_id">Product Category</label>
        {!! html()->select('category_id', $categories, data_get($row, 'category_id'))->id('category_id')->class('form-control')->placeholder('-- Select category --')->required() !!}
        <small class="form-text text-muted">The section automatically displays active products assigned to this category.</small>
    </div>
    <div class="col-md-12 d-none" id="featured-section-field">
        <label class="col-form-label" for="product_ids">Products</label>
        @php
            $selectedProducts = old('product_ids', json_decode((string) data_get($row, 'product_ids', '[]'), true) ?: []);
        @endphp
        <select name="product_ids[]" id="product_ids" class="form-control" multiple size="7">
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(in_array($product->id, $selectedProducts))>{{ $product->title }}{{ $product->sku ? ' — '.$product->sku : '' }}</option>
            @endforeach
        </select>
        <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to choose multiple products. Their order follows the selection priority below.</small>
    </div>
    <div class="col-md-4">
        <label class="col-form-label" for="button_text">Button Text</label>
        {{ html()->text('button_text')->class('form-control')->placeholder('Explore Bouquets') }}
    </div>
    <div class="col-md-8">
        <label class="col-form-label" for="button_url">Button URL</label>
        {{ html()->text('button_url')->class('form-control')->placeholder('/bouquets-gifting') }}
        <small class="form-text text-muted">Use a clean internal URL such as /bouquets-gifting.</small>
    </div>
    <div class="col-md-4">
        <label class="col-form-label" for="max_items">Products to Show</label>
        {{ html()->number('max_items')->class('form-control')->attributes(['min' => 1, 'max' => 12])->value(old('max_items', data_get($row, 'max_items', 6)))->required() }}
    </div>
    <div class="col-md-4">
        <label class="col-form-label" for="priority">Priority</label>
        {{ html()->number('priority')->class('form-control')->attributes(['min' => 0])->value(old('priority', data_get($row, 'priority', 0)))->required() }}
        <small class="form-text text-muted">Lower numbers appear first.</small>
    </div>
    <div class="col-md-4">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status', ['1' => 'Enable', '0' => 'Disable'], data_get($row, 'status', 1))->id('status')->class('form-control')->required() !!}
    </div>
</div>
@endif

@push('script')
<script>
    (function () {
        function toggleHomepageSectionSource() {
            var type = document.getElementById('section_type');
            var category = document.getElementById('category-section-field');
            var featured = document.getElementById('featured-section-field');
            if (!type || !category || !featured) return;
            var isFeatured = type.value === 'featured_products';
            category.classList.toggle('d-none', isFeatured);
            featured.classList.toggle('d-none', !isFeatured);
        }
        document.addEventListener('DOMContentLoaded', function () {
            var type = document.getElementById('section_type');
            if (type) type.addEventListener('change', toggleHomepageSectionSource);
            toggleHomepageSectionSource();
        });
    }());
</script>
@endpush
