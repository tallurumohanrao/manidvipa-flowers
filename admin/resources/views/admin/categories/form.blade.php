<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="title">Title</label>
        {{ html()->text('title')->class('form-control') }}
    </div>
    <div class="col-md-6">
        <label class="col-form-label" for="url">Public Category URL*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">https://www.manidvipaflowers.com</span>
            </div>
            {{ html()->text('url', $pageUrl ?? null)->class('form-control')->placeholder('/puja-flowers/chamanthi')->required() }}
        </div>
        <small class="form-text text-muted">Fully editable. The old URL redirects automatically after a change.</small>
    </div>
    <div class="col-md-4">
        <label class="col-form-label" for="parent_id">Parent Category</label>
        {!! html()->select('parent_id', $parentCategories ?? [], @$row->parent_id)->placeholder('-- Main Category --')->id('parent_id')->class('form-control') !!}
        <small class="form-text text-muted">Leave empty for main categories like Puja Flowers, Premium Flowers.</small>
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="home_category">Show on Home</label>
        {!! html()->select('home_category', array('1' => 'Yes', '0' => 'No'), @$row->home_category ?? 0)->id('home_category')->class('form-control') !!}
    </div>
    <div class="col-md-6">
        <label class="col-form-label" for="image">Image</label>
        <div class="row">
            <div class="col-md-9">
            {!! html()->file('image') !!}
            {!! html()->hidden('old_image', @$row->image) !!}
            </div>
            <div class="col-md-3">
                @php
                    $categoryImagePath = @$row->image ? $module.'/'.@$row->image : null;
                    $categoryImagePublic = $categoryImagePath && File::exists(public_path('storage/'.$categoryImagePath));
                    $categoryImageStored = $categoryImagePath && File::exists(storage_path('app/public/'.$categoryImagePath));
                    $categoryImageUrl = $categoryImagePublic
                        ? asset('storage/'.$categoryImagePath)
                        : ($categoryImageStored ? route('admin.media.show', ['path' => $categoryImagePath]) : null);
                @endphp
                @if($categoryImageUrl)
                {{ html()->img($categoryImageUrl, @$row->title)->attributes(['title' => @$row->image ,'width' => '100%']) }}
                @elseif(@$row->image)
                <small class="text-danger">Missing image file: {{ @$row->image }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="priority">Priority</label>
        {{ html()->text('priority')->class('form-control') }}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
    <div class="col-md-12">
        <label class="col-form-label" for="short_description">Short Description</label>
        {{ html()->textarea('short_description')->id('short_description')->class('form-control editor')->rows(5) }}
    </div>
    <div class="col-md-12">
        <label class="col-form-label" for="description">Description</label>
        {{ html()->textarea('description')->class('form-control editor') }}
    </div>
</div>
