<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="name">Name</label>
        {{ html()->text('name')->class('form-control') }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="subject">Subject</label>
        {{ html()->text('subject')->class('form-control') }}
    </div>

    <div class="col-md-12">
        <label class="col-form-label" for="body_html">HTML Message</label>
        {{ html()->textarea('body_html')->class('editor form-control') }}
    </div>
</div>
