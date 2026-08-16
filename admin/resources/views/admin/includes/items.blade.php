<ul class="list-inline d-flex">
    <li class="list-inline-item">
        Show
    </li>
    <li class="list-inline-item"  style="width:60px">
    {!! html()->select('per_page', pageNumbers())->class('custom-select custom-select-sm form-control form-control-sm w-80')->attributes(['onchange'=>'$("#search").submit()']) !!}
    </li>
</ul>
