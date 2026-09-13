<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="city">City</label>
    <div class="col-sm-2">
    {!! html()->select('city',array(''=>'-- City --','hyderabad' => 'Hyderabad', 'vizag' => 'Vizag'))->id('city')->class('form-control')->required() !!}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="name">Name</label>
    <div class="col-sm-9">
    {{ html()->text('name')->class('form-control')->placeholder('Name')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="price1">Price 1</label>
    <div class="col-sm-9">
    {{ html()->text('price1')->class('form-control')->placeholder('Price 1')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="price1">Price 1 Max. People</label>
    <div class="col-sm-9">
    {{ html()->text('price1_max_people')->class('form-control')->placeholder('Price 1 Max. People')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="max_people">Max. People</label>
    <div class="col-sm-9">
    {{ html()->text('max_people')->class('form-control')->placeholder('Max. People')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="price1">Price 2</label>
    <div class="col-sm-9">
    {{ html()->text('price2')->class('form-control')->placeholder('Price 2')->required() }}
    </div>
</div>


<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="image">Image</label>
    <div class="col-sm-9">
    {!! html()->file('image') !!}
    {!! html()->hidden('old_image', @$row->image) !!}
    @if(@$row->image && File::exists('storage/theaters/'. @$row->image))
    {{ html()->img(asset('storage/theaters/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '180px']) }}
    @endif
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="price_description">Price Description</label>
    <div class="col-sm-9">
        {!! html()->textarea('price_description')->class('form-control') !!}
    <span class="text-danger">{{ $errors->first('price_description') }}</span>
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="description">Description</label>
    <div class="col-sm-9">
        {!! html()->textarea('description')->class('form-control editor') !!}
    <span class="text-danger">{{ $errors->first('description') }}</span>
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="status">Status</label>
    <div class="col-sm-2">
    {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>

@if($row)
<div class="row">
	<div class="col-sm-12">
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
            <thead>
                <tr role="row">
                    <th>S.No.</th>
                    <th>Timings</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th><a href="javascript:;" onclick="addSlots()" title="Add slot" aria-label="Add slot"><i class="fa fa-plus-circle" aria-hidden="true"></i></a></th>
                </tr>
            </thead>

            <tbody id="slottablecontents">
            @foreach($slots as $slot)
                <tr id="slotrow-{{ $slot->id }}">
                    <td>
                        {{ $loop->iteration }}
                        {!! html()->hidden('Slot['.$loop->index.'][id]', $slot->id ) !!}
                    </td>
                    <td>{!! html()->text('Slot['.$loop->index.'][timings]', $slot->timings)->class('form-control') !!}</td>
                    <td>
                        <label class="switch">
                        {{ html()->checkbox('Slot['.$loop->index.'][status]', $slot->status ==1?true:false)->id('gallery_'.$slot->id)->class('status')->id('status_'.$slot->id)->attributes(['data-url'=>route('admin.'.$module.'.update.slots.status',['id'=>$slot->id])]) }}
                        <span class="slider round"></span>
                        </label>
                    </td>
                    <td>{{$slot->created_at}}</td>
                    <td>
                        @can($module.'_delete')
                            <a href="javascript:;" class="deleteslot btn btn-danger" title="Delete slot" aria-label="Delete slot" data-id="{{ $slot->id }}" data-url="{{ route('admin.'.$module.'.slots.destroy',['id'=>$slot->id]) }}"><i class="fa fa-trash" aria-hidden="true"></i></a>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    </div>
</div>

<div class="row">
	<div class="col-sm-12">
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
            <thead>
                <tr role="row">
                    <th>S.No.</th>
                    <th>Upload</th>
                    <th>Image</th>
                    {{--<th>Is Before & After?</th>--}}
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th><a href="javascript:;" onclick="addGallery()" title="Add gallery image" aria-label="Add gallery image"><i class="fa fa-plus-circle" aria-hidden="true"></i></a></th>
                </tr>
            </thead>

            <tbody id="tablecontents">
            @foreach($images as $image)
                <tr id="row-{{ $image->id }}">
                    <td>
                        {{ $loop->iteration }}
                        {!! html()->hidden('Gallery['.$loop->index.'][id]', $image->id ) !!}
                        {!! html()->hidden('Gallery['.$loop->index.'][old_file]', $image->image ) !!}
                    </td>

                    <td>{!! html()->file('Gallery['.$loop->index.'][image]') !!}</td>
                    <td>{{ html()->img(asset('storage/'.$module.'/'. @$image->image ), null)->attributes(array('title' => @$image->image ,'width' => '70px')) }}</td>
                    {{--<td>
                        <label class="switch">
                        {{ Form::checkbox('type', null, $image->type ==1?true:false,['class'=>'status','id'=>'type_'.$image->id,'data-id'=>$image->id,'data-url'=>route('admin.'.$module.'.isbeforafter.status',$image)]) }}
                        <span class="slider round"></span>
                        </label>
                    </td>--}}
                    <td>
                        {!! html()->text('Gallery['.$loop->index.'][priority]', $image->priority)->class('form-control') !!}
                    </td>
                    <td>
                        <label class="switch">
                        {{ html()->checkbox('Gallery['.$loop->index.'][status]', $image->status ==1?true:false)->id('gallery_'.$image->id)->class('status')->id('status_'.$image->id)->attributes(['data-url'=>route('admin.'.$module.'.update.gallery.status',['id'=>$image->id])]) }}
                        <span class="slider round"></span>
                        </label>
                    </td>
                    <td>{{$image->created_at}}</td>
                    <td>
                        @can($module.'_delete')
                            <a href="javascript:;" class="delete btn btn-danger" title="Delete gallery image" aria-label="Delete gallery image" data-id="{{ $image->id }}" data-url="{{ route('admin.'.$module.'.gallery.destroy',['id'=>$image->id]) }}"><i class="fa fa-trash" aria-hidden="true"></i></a>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    </div>
</div>
@endif
