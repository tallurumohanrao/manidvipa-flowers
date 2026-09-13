<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="question">Question</label>
    <div class="col-sm-9">
    {{ html()->text('question')->class('form-control')->placeholder('Question')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="answer">Answer</label>
    <div class="col-sm-9">
    {{ html()->textarea('answer')->class('editor form-control')->placeholder('Answer')->id('answer')->required() }}
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="status">Status</label>
    <div class="col-sm-2">
    {!! html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control') !!}
    </div>
</div>
