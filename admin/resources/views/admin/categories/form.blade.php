<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="title">Title</label>
        {{ html()->text('title')->class('form-control') }}
    </div>
    <div class="col-md-6">
        <label class="col-form-label" for="image">Image</label>
        <div class="row">
            <div class="col-md-9">
            {!! html()->file('image') !!}
            {!! html()->hidden('old_image', @$row->image) !!}
            </div>
            <div class="col-md-3">
                @if(@$row->image && File::exists('storage/'.$module.'/'. @$row->image))
                {{ html()->img(asset('storage/'.$module.'/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '100%']) }}
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
    {{-- <div class="col-md-12">
        <label class="col-form-label" for="description">Description</label>
        {{ html()->textarea('description')->class('form-control editor') }}
    </div> --}}
</div>
