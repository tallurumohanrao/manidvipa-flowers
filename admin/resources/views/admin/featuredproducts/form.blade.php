<div class="form-group row">
    {!! Form::label('image', 'Image', ['class' => 'col-sm-3 col-form-label']) !!}  
    <div class="col-sm-9">
    {!! Form::file('image', $attributes = array()) !!}
    {!! Form::hidden('old_image', @$row->image) !!}
    @if(@$row->image && File::exists('storage/clients/'. @$row->image))
    {{ Html::image(asset('storage/clients/'. @$row->image ), null , array('title' => @$row->image ,'width' => '180px')) }}
    @endif
    </div>
</div>
<div class="form-group row">
    {!! Form::label('status', 'Status', ['class' => 'col-sm-3 col-form-label']) !!}
    <div class="col-sm-2">
    {!! Form::select('status', array('1' => 'Enable', '2' => 'Disable'), NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]); !!}
    </div>
</div>
