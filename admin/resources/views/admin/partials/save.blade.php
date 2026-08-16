</br>
{!! html()->button('Save','submit')->name('FormButton')->value('SAVE')->class('btn btn-outline-primary btn-save')->id('save') !!}
{!! html()->button('Save & Stay Here','submit')->name('FormButton')->value('SAVEEDIT')->class('btn btn-outline-primary btn-save')->id('saveedit') !!}
{!! html()->button('Save & New','submit')->name('FormButton')->value('SAVENEW')->class('btn btn-outline-primary btn-save')->id('savenew') !!}
{{-- ['name'=>'FormButton','value'=>'SAVE','type'=>'submit','class'=>'btn btn-outline-primary btn-save','id'=>'SAVE']
 html()->button('Save & Stay Here',['name'=>'FormButton','value'=>'SAVEEDIT','type'=>'submit','class'=>'btn btn-outline-primary btn-save','id'=>'SAVEEDIT']) !!}
{!! html()->button('Save & New',['name'=>'FormButton','value'=>'SAVENEW','type'=>'submit','class'=>'btn btn-outline-primary btn-save','id'=>'SAVENEW']) --}}
