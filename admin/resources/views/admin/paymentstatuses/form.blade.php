<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="name">Name</label>
        {{ html()->text('name')->class('form-control') }}
    </div>

    <div class="col-md-6">
        <label class="col-form-label" for="subject">Subject Template</label>
        {{ html()->text('subject')->class('form-control') }}
        <small class="form-text text-muted">Leave <code>@verbatim{{order.id}}@endverbatim</code> in the message where the real Order No should appear. Do not type one fixed order number here. Example: for order 125, it becomes <strong>#125</strong>.</small>
    </div>

    <div class="col-md-12">
        <label class="col-form-label" for="body_html">HTML Message Template</label>
        {{ html()->textarea('body_html')->class('editor form-control') }}
        <small class="form-text text-muted">Example saved text: <code>Your order #@verbatim{{order.id}}@endverbatim payment is pending.</code> Customer sees: <strong>Your order #125 payment is pending.</strong></small>
    </div>
</div>
