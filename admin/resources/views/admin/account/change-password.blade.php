{{ html()->form('POST')->route('admin.changepassword')->open() }}
<div class="row">
    <div class="form-boarder">
        <div class="col-md-12 col-lg-12 col-sm-12 col-xs-12">
            <div class="form-row">
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label class="col-form-label" for="current_password">Current Password</label>
                    {{ html()->password('current_password')->class('form-control')->required() }}
                    @if($errors->changePasswordForm->has('current_password'))
                    <span class="text-danger">{{ $errors->changePasswordForm->first('current_password') }}</span>
                    @endif
                </div>

                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label for="new_password" class="control-label">New Password</label>
                    {{ html()->password('new_password')->class('form-control')->required() }}
                    @if($errors->changePasswordForm->has('new_password'))
                    <span class="text-danger">{{ $errors->changePasswordForm->first('new_password') }}</span>
                    @endif
                </div>

                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label for="new_password_confirmation" class="control-label">Confirm New Password</label>
                    {{ html()->password('new_password_confirmation')->class('form-control')->required() }}
                    @if($errors->changePasswordForm->has('new_password_confirmation'))
                    <span class="text-danger">{{ $errors->changePasswordForm->first('new_password_confirmation') }}</span>
                    @endif
                </div>
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    {!! html()->button('Change','submit')->class('btn btn-primary')->id('save') !!}
                </div>
            </div>
        </div>
	</div>
</div>
{{ html()->form()->close() }}
