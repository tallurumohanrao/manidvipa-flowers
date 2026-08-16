
{{ html()->model($data)->form('PATCH')->route('admin.account.update', $data)->class('')->id('form')->attributes(['enctype'=>'multipart/form-data'])->open() }}
<div class="col-md-12 mb-3">
    <label for="name">Name</label>
    <div class="form-floating">
    {{ html()->text('name')->class('form-control')->placeholder('Name')->required() }}
    <span class="text-danger">{{ $errors->accountForm->first('name') }}</span>
    </div>
</div>

<div class="col-md-12 mb-3">
    <div class="row">
        <div class="col-md-6">
            <label class="control-label w-100" for="image">Avatar</label>
            {!! html()->file('image') !!}
            {!! html()->hidden('old_image', @$data->image) !!}
            <span class="text-danger">{{ $errors->accountForm->first('image') }}</span>
        </div>
        <div class="col-md-6">
            @if(@$data->image && File::exists(public_path('storage/admins/'.@$data->image)))
            <div class="image float-right">
                {{ html()->img(asset('storage/admins/'. @$data->image ),$data->image)->attributes(['title' => @$data->image ,'class' => 'w-50']) }}
            </div>
            @endif
        </div>
    </div>
</div>

<div class="col-md-12 mb-3">
    <label for="email">Email</label>
    <div class="form-floating">
        {{ html()->email('email')->class('form-control')->placeholder('Email')->required() }}
        <span class="text-danger">{{ $errors->accountForm->first('email') }}</span>
    </div>
</div>
{!! html()->button('Save','submit')->name('FormButton')->value('Save')->class('btn btn-primary')->id('save') !!}
{{ html()->form()->close() }}

