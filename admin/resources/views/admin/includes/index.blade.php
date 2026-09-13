<ul class="list-inline mb-3 text-right">
    <li class="list-inline-item float-left">
        <h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">{{ $module }}</a>
        </h4>
    </li>
    <li class="list-inline-item">
        <div class="btn-group">
        @can($module.'_create')
            @if(Route::has('admin.'.$module.'.create'))
                <a href="{{ route('admin.'.$module.'.create') }}" class="btn btn-primary">Create</a>
            @endif
        @endcan
        @can($module.'_delete')
            @if(Route::has('admin.'.$module.'.massdestroy'))
                <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
            @endif
        @endcan
        </div>
    </li>
</ul><hr>
