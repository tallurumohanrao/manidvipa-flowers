<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="group_name">Group Name</label>
        {{ html()->text('group_name')->class('form-control')->required() }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="module">Module</label>
        {{ html()->text('module')->class('form-control')->required() }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="group_sort_order">Group Sort Order</label>
        {{ html()->text('group_sort_order')->class('form-control')->required() }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="module_sort_order">Module Sort Order</label>
        {{ html()->text('module_sort_order')->class('form-control')->required() }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="icon_class">Icon Class</label>
        {{ html()->text('icon_class')->class('form-control')->required() }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status',array(''=>'-- Select --','1' => 'Enable', '2' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
