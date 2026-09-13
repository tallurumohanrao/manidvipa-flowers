<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="name">Name</label>
        {{ html()->text('name')->class('form-control')->placeholder('Name')->required() }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="designation">Designation</label>
        {{ html()->text('designation')->class('form-control')->placeholder('Designation')->required() }}
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
                {{ html()->img(asset('storage/'.$module.'/100X100/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '100%']) }}
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
    <label class="col-form-label" for="description">Description</label>
        {{ html()->textarea('description')->class('form-control')->placeholder('Description')->id('description')->required() }}
    </div>


    <div class="col-md-2">
    <label class="col-form-label" for="status">Status</label>
    {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
