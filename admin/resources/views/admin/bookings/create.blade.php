@push('styles')
<link rel="stylesheet" href="{{ asset('assets/admin/css/jquery.multiselect.css') }}" />
@endpush
@extends('admin.layouts.app')
@section('content')
<section class="content">
	<div class="container-fluid">
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="{{ route('admin.'.$module.'.index',['booking_type'=>request('booking_type')]) }}" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="text-capitalize">Create @if(request('booking_type') == 1)
                    Offline
                    @endif {{ Str::singular($module) }}<span class="float-right" id="priceHtml"></span></h5>
                <hr>
                {{ html()->form('POST')->route('admin.'.$module.'.store')->class('form-horizontal')->id('bookingForm')->attributes(['enctype'=>'multipart/form-data'])->open() }}
                @include('admin.'.$module.'.form',['decorations'=>$decorations])
                <div class="form-group row">
                {!! html()->button('Save','submit')->name('FormButton')->value('SAVE')->class('btn btn-outline-primary btn-save text-center')->id('bookingSubmit') !!}
                </div>
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</section>
@stop
@push('script')
<script src="{{ asset('assets/admin/js/jquery.multiselect.js') }}"></script>
<script>
$('#addons').multiselect();
$("#booking_date").change(function(){
    var val = this.value;
    theaters(val);
});

function theaters(val){
    event.preventDefault();
    var city = $("#city").val();
    $.ajax({
        method: "GET",
        data: { booking_date : val, city:city},
        dataType: 'json',
        url: "{{ route('admin.theaters.ajax') }}",
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(data, textStatus, jqXHR) {
            if(data.status=== 'success'){
                var option = '<option value="">-- Select Theater --</option>';
                $.each(data.data,function(index,value){
                    option += '<option value="'+value.id+'">'+ value.name +'('+value.slotsCount+' slots available.)</option>';
                });
                $("#theater_id").html(option);
            }else if(data.status=== 'fail'){
                Message.add(data.message, {type: 'error',sticky: false});
            }
        },
        error: function(data) {
            var errors = $.parseJSON(data.responseText);
            var html = '<li>'+errors.message +'</li>';
            $.each(errors.errors, function (key, val) {
                html += '<li>'+ val +'</li>';
            });
            Message.add(html, {type: 'error',sticky: false});
        }
    });
}

$("#theater_id").change(function(){
    var theater_id = this.value;
    slots(theater_id);
});

function slots(theater_id){
    var booking_date = $("#booking_date").val();
    event.preventDefault();
    $.ajax({
        method: "GET",
        data: { booking_date : booking_date, theater_id:theater_id },
        dataType: 'json',
        url: "{{ route('admin.slots.ajax') }}",
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(data, textStatus, jqXHR) {
            if(data.status=== 'success'){
                if (data.slots.length === 0) {
                    Message.add('Slots not available.', {type: 'error',sticky: false});
                }
                var option = '<option value="">-- Select Slot --</option>';
                $.each(data.slots,function(index,value){
                    option += '<option value="'+value.id+'">'+ value.timings +' slots available.)</option>';
                });
                $("#slot_id").html(option);
                $("#price1_max_people").val(data.theater.price1_max_people);
                $("#price1").val(data.theater.price1);
                $("#price2").val(data.theater.price2);

                var selectList = $('#no_of_persons');
                var option = '<option value="">-- Select no. of persons --</option>';
                for(var i = 1; i < data.theater.max_people; i++) {
                    option += '<option value='+i+'>'+i+'</option>';
                    selectList.html(option);
                    // option = document.createElement('option');
                    // option.text = [i]
                    // option.value = [i]
                    // selectList.appendChild(option);
                }
            }else if(data.status=== 'fail'){
                Message.add(data.message, {type: 'error',sticky: false});
            }
        },
        error: function(data) {
            var errors = $.parseJSON(data.responseText);
            var html = '<li>'+errors.message +'</li>';
            $.each(errors.errors, function (key, val) {
                html += '<li>'+ val +'</li>';
            });
            Message.add(html, {type: 'error',sticky: false});
        }
    });
}

$('#no_of_persons').change(function() {
    var price1_max_people = $("#price1_max_people").val();
    var price1 = $("#price1").val();
    var price2 = $("#price2").val();
    var slot_val = $("#slot_id").val();
    if(slot_val == ''){
        Message.add('Please select slot first.', {type: 'error'});
        $('#no_of_persons').val('');
        return false;
    }
    var val = this.value;
    if(val > price1_max_people){
        var gp = val - price1_max_people;
        var price = parseFloat(price1) + (parseFloat(price2) * gp);
        $("#amount").val(price);
        resultamount();
        // var priceText = '₹' + price.toLocaleString('en-IN') + '/-';
        // $("#priceHtml").text(priceText);
    }else{
        var price = parseFloat(price1);
        $("#amount").val(price);
        resultamount();
        // var priceText = '₹' + price.toLocaleString('en-IN') + '/-';
        // $("#priceHtml").text(priceText);
    }
});


$("#decorations").change(function(){
    var decoration = this.value;
    var split = decoration.split('||');
    var splitamount = parseFloat(split[1]);
    $("#decoration_amount").val(splitamount);
    resultamount();
});

$("#cakes").change(function(){
    var cake = this.value;
    var split = cake.split('||');
    var splitamount = parseFloat(split[1]);
    $("#cake_amount").val(splitamount);
    resultamount();
});

$("#addons").change(function(){
    var addons_amount = 0;
    $('#addons :selected').each(function(i, sel){
        var sel_val = $(sel).val();
        var split = sel_val.split('||');
        var splitamount = parseFloat(split[1]);
            addons_amount += parseFloat(splitamount);
    });
    // var addon = this.value;
    // var split = addon.split('||');
    // var splitamount = parseFloat(split[1]);
    $("#addons_amount").val(addons_amount);
    resultamount();
});

function resultamount(){
    var amount = parseFloat($("#amount").val());
    var decoration_amount = parseFloat($("#decoration_amount").val());
    var cake_amount = parseFloat($("#cake_amount").val());
    var addons_amount = parseFloat($("#addons_amount").val());
    var price =0;
    if(!isNaN(amount)){
        price += parseFloat(amount);
    }
    if(!isNaN(decoration_amount)){
        price += parseFloat(decoration_amount);
    }
    if(!isNaN(cake_amount)){
        price += parseFloat(cake_amount);
    }
    if(!isNaN(addons_amount)){
        price += parseFloat(addons_amount);
    }
    var priceText = '₹' + price.toLocaleString('en-IN') + '/-';
    $("#total_amount").val(price);
    $("#priceHtml").text(priceText);
}

$("#bookingForm").validate({
    rules: {
        name:{
            required:true
        },
        email:{
            required:true
        },
        whatsapp_number:{
            required:true
        },
        city:{
            required:true
        },
        booking_date:{
            required:true,
        },
        theater_id:{
            required:true
        },
        slot_id:{
            required:true,
        },
        booking_status_id:{
            required:true,
        },
        no_of_persons:{
            required:true,
        }
    },
    messages:{
        name:{
            required:"Name is required.",
        },
        email:{
            required:"Email is required.",
        },
        whatsapp_number:{
            required:"Whatsapp number is required.",
        },
        city:{
            required:"City is required.",
        },
        booking_date:{
            required:"Booking date is required.",
        },
        theater_id:{
            required:"Theater is required.",
        },
        slot_id:{
            required:"Slot is required.",
        },
        no_of_persons:{
            required:"No .of persons is required.",
        },
        booking_status_id:{
            required:"Booking status is required.",
        }
    },
    submitHandler: function() {
        event.preventDefault();
        const form = $('#bookingForm')[0];
        const formData = new FormData(form);
        $.ajax({
            method: "POST",
            data: formData,
            dataType: 'json',
            url: $("#bookingForm").attr('action'),
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            processData: false, //add this
            contentType: false, //and this
            beforeSend:function(){
                $("#bookingSubmit").text('Processing...');
                $("#bookingSubmit").prop('disabled',true);
            },
            success: function(data, textStatus, jqXHR) {
                if(data.status=== 'success'){
                    form.reset();
                    $("#priceHtml").text('');
                    Message.add(data.message, {type: 'success',sticky: false});
                }else if(data.status=== 'fail'){
                    Message.add(data.message, {type: 'error',sticky: false});
                }
            },
            error: function(data) {
                var errors = $.parseJSON(data.responseText);
                var html = '<li>'+errors.message +'</li>';
                $.each(errors.errors, function (key, val) {
                    html += '<li>'+ val +'</li>';
                });
                Message.add(html, {type: 'error',sticky: false});
            },
            complete: function() {
                $("#bookingSubmit").text('Submit');
                $("#bookingSubmit").prop('disabled',false);
            }
        });
    }
});
</script>
@endpush
