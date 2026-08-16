<div class="pb-4">
    <div class="row g-12">
        <div class="col-md-12">
            <fieldset>
                <div class="row g-3 mb-3">
                    <div class="col-md-12 d-none">
                        <div class="form-floating">
                            <label for="slug">URL*</label>
                            {{ html()->text('slug')->class('form-control')->placeholder('URL') }}
                            <span class="text-danger">{{ $errors->first('slug') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <label for="title">Title*</label>
                            {{ html()->text('title')->class('form-control')->placeholder('Title')->required() }}
                            <span class="text-danger">{{ $errors->first('title') }}</span>
                        </div>
                    </div>
                    {{--<div class="col-md-6">
                        <div class="form-floating">
                            {!! Form::label('category', 'Category*') !!}
                            {!! Form::text('category', null ,['class' => 'form-control', 'placeholder' => 'Category', 'autocomplete' => 'off']) !!}
                            <span class="text-danger">{{ $errors->first('category') }}</span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="banner">Banner</label>
                        <div class="form-floating">
                        {!! html()->file('banner') !!}
                        {{ html()->hidden('old_banner', @$row->banner) }}
                        </div>
                        <span class="text-danger">{{ $errors->first('banner') }}</span>
                    </div>
                    <div class="col-md-3">
                    @if(@$row->banner && File::exists('storage/posts/'. @$row->image))
                        {{ html()->img(asset('storage/'.$module.'/'. @$row->banner ))->attributes(['title' => @$row->banner ,'width' => '100%']) }}
                    @endif
                    </div>--}}


                    <div class="col-md-6">
                        <div class="form-floating">
                            <label for="short_description">Short Description</label>
                            {{ html()->textarea('short_description')->class('form-control')->rows(2)->placeholder('Short Description') }}
                            <span class="text-danger">{{ $errors->first('short_description') }}</span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="image">Image</label>
                        <div class="form-floating">
                        {!! html()->file('image') !!}
                        {{ html()->hidden('old_image', @$row->image) }}
                        </div>
                        <span class="text-danger">{{ $errors->first('image') }}</span>
                    </div>
                    <div class="col-md-3">
                    @if(@$row->image && File::exists('storage/posts/'. @$row->image))
                        {{ html()->img(asset('storage/'.$module.'/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '100%']) }}
                    @endif
                    </div>
                    <div class="col-md-12">
                        <label for="description">Description*</label>
                        <div class="form-floating">
                            {{ html()->textarea('description')->class('form-control editor') }}
                            <span class="text-danger">{{ $errors->first('description') }}</span>
                        </div>
                    </div>

                    {{--<div class="col-md-6">
                        <div class="form-floating">
                            <label for="twitter">Twitter</label>
                            {{ html()->text('twitter')->class('form-control')->placeholder('Twitter') }}
                            <span class="text-danger">{{ $errors->first('twitter') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <label for="linkedin">Linked In</label>
                            {{ html()->text('linkedin')->class('form-control')->placeholder('Linked In') }}
                            <span class="text-danger">{{ $errors->first('linkedin') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <label for="facebook">Facebook</label>
                            {{ html()->text('facebook')->class('form-control')->placeholder('Facebook') }}
                            <span class="text-danger">{{ $errors->first('facebook') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <label for="skype">Skype</label>
                            {{ html()->text('skype')->class('form-control')->placeholder('Skype') }}
                            <span class="text-danger">{{ $errors->first('skype') }}</span>
                        </div>
                    </div>--}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <label for="status">Status*</label>
                            {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
                            <span class="text-danger">{{ $errors->first('status') }}</span>
                        </div>
                    </div>
                    {{--<div class="col-md-3">
                        <div class="form-floating">
                            {!! Form::text('priority', null ,['class' => 'form-control', 'placeholder' => 'Priority', 'autocomplete' => 'off']) !!}
                            {!! Form::label('priority', 'Priority') !!}
                            <div id="error_name" class="invalid-feedback">{{ $errors->first('priority') }}</div>
                        </div>
                    </div>--}}
                </div>
            </fieldset>
        </div>
    </div>
</div>

