<ul class="list-inline mb-3 text-right">
    <li class="list-inline-item float-left">
        <h4 class="heading text-capitalize">
            <a href="{{ route('admin.'.$module.'.index') }}">{{ $module }}</a>
        </h4>
    </li>
    <li class="list-inline-item">
        <div class="btn-group">
        <a href="{{ route('admin.'.$module.'.create') }}" class="btn btn-primary">Create</a>
          <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="{{ route('admin.'.$module.'.massdestroy') }}" role="button">Delete</a>
        </div>
    </li>
</ul><hr>
