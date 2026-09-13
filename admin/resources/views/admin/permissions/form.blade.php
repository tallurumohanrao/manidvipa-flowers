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
        <label class="col-form-label" for="route_name">Route Name</label>
        {{ html()->text('route_name')->class('form-control')->placeholder('Example: products')->required() }}
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
        {{ html()->text('icon_class')->class('form-control')->placeholder('Example: fas fa-box-open') }}
    </div>

    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        {!! html()->select('status',array(''=>'-- Select --','1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control')->required() !!}
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="menu_status">Show in Menu</label>
        {!! html()->select('menu_status',array(''=>'-- Select --','1' => 'Yes', '0' => 'No'))->id('menu_status')->class('form-control')->required() !!}
    </div>
</div>
