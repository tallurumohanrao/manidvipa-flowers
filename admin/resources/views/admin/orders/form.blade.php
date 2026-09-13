<div class="form-group row">
    {!! Form::label('title', 'Title', ['class' => 'col-sm-3 col-form-label']) !!}  
    <div class="col-sm-9">
    {!! Form::text('title', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>
</div>

<div class="form-group row">
    {!! Form::label('alt', 'Alt', ['class' => 'col-sm-3 col-form-label']) !!}  
    <div class="col-sm-9">
    {!! Form::text('alt', null ,['class' => 'form-control ', 'placeholder' => '', 'autocomplete' => 'off']) !!}
    </div>
</div>

<div class="form-group row">
    {!! Form::label('image', 'Image', ['class' => 'col-sm-3 col-form-label']) !!}  
    <div class="col-sm-9">
    {!! Form::file('image', $attributes = array()) !!}
    {!! Form::hidden('old_image', @$row->image) !!}
    @if(@$row->image && File::exists('storage/banners/'. @$row->image))
    {{ Html::image(asset('storage/banners/'. @$row->image ), null , array('title' => @$row->image ,'width' => '180px')) }}
    @endif
    </div>
</div>
<div class="form-group row">
    {!! Form::label('description', 'Banner Text', ['class' => 'col-sm-3 col-form-label']) !!}  
    <div class="col-sm-9">
        {!! Form::textarea('description', null ,['id'=>'description', 'class' => 'editor form-control']) !!}
    <span class="text-danger">{{ $errors->first('description') }}</span>
    </div>
</div>

<div class="form-group row">
    {!! Form::label('status', 'Status', ['class' => 'col-sm-3 col-form-label']) !!}
    <div class="col-sm-2">
    {!! Form::select('status', array('1' => 'Enable', '0' => 'Disable'), NULL, [ 'class' => 'form-control', 'autocomplete' => 'off' ]); !!}
    </div>
</div>
