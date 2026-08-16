$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$('.datetimepicker').datetimepicker({
	format:'Y-m-d H:i:s',
	step:30
});
$('.datepicker').datetimepicker({
    timepicker:false,
	format:'Y-m-d'
});

$(".openNav").click(function(){
    $('#mySidenav').css("display", "block");
})
$(".closeNav").click(function(){
    $('#mySidenav').css("display", "none");
})

$(".custom-file-input").on("change", function() {
    var fileName = $(this).val().split("\\").pop();
    $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});

$("#rolesAll").click(function(){ 
    $("input[type=checkbox]").prop('checked', $(this).prop('checked'));

});

$(document).ready(function() {

    $('.select-all').click(function () {
        let $select2 = $(this).parent().siblings('.select2');
        $select2.find('option').prop('selected', 'selected');
        $select2.trigger('change');
    });
    $('.deselect-all').click(function () {
        let $select2 = $(this).parent().siblings('.select2');
        $select2.find('option').prop('selected', '');
        $select2.trigger('change');
    });
    
    var $selectAll = $('#selectAll'); // main checkbox inside table thead
    var $table = $('.tablegrid'); // table selector 
    var $tdCheckbox = $table.find('tbody .sub_chk input:checkbox'); // checboxes inside table body
    var tdCheckboxChecked = 0; // checked checboxes
    
    // Select or deselect all checkboxes depending on main checkbox change
    $selectAll.on('click', function () {
        $tdCheckbox.prop('checked', this.checked);
    });
    
    // Toggle main checkbox state to checked when all checkboxes inside tbody tag is checked
    $tdCheckbox.on('change', function(e){
        tdCheckboxChecked = $table.find('tbody .sub_chk input:checkbox:checked').length; // Get count of checkboxes that is checked
        // if all checkboxes are checked, then set property of main checkbox to "true", else set to "false"
        $selectAll.prop('checked', (tdCheckboxChecked === $tdCheckbox.length));
    })
    //  shift check
    var $chkboxes = $('.sub_chk');
    var lastChecked = null;

    $chkboxes.click(function(e) {
        if (!lastChecked) {
            lastChecked = this;
            return;
        }

        if (e.shiftKey) {
            var start = $chkboxes.index(this);
            var end = $chkboxes.index(lastChecked);

            $chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop('checked', lastChecked.checked);
        }

        lastChecked = this;
    });
    //  shift check
});
    
$('#delete_all').on('click', function(e) {
    var checked = [];
   $.each($("input[name='id[]']:checked"), function(){
       checked.push($(this).val());
   });
   var joined = checked.join(", ");
   if(joined.length <=0)
   {
       return false;
   }
   swal.fire({
       title: "Are you sure?",
       icon: "warning",
       showCancelButton: true,
       focusConfirm: false,
       confirmButtonColor: '#d33',
       confirmButtonText:'Yes, delete it!',
       cancelButtonText:'Cancel',
       })
       .then((willDelete) => {
           if (willDelete.value) {
               $.ajax({
                   url: $(this).data('url'),
                   type: 'DELETE',
                   data: { 'ids' : joined },
                   success: function (data) {
                       if (data.success === true) {
                               $("input[name='id[]']:checked").each(function() {
                                   $(this).parents("tr").remove();
                               });
                               Message.add(data.message, {type: 'success'});
                           } else if (data.success === false) {
                               Message.add(data.message, {type: 'error'});
                       } else {
                           Message.add(data.message, {type: 'error'});
                       }
                   },
                   error: function (data) { 
                        Message.add(data.responseJSON.message, {type: 'error',sticky: true});
                   }
               });
           }
       });
})
$('.delete').on('click', function(e) {
    var ID = parseInt($(this).data('id'));
   if(!$.isNumeric(ID))
   {  
       return false;
   }
   swal.fire({
       title: "Are you sure?",
       icon: "warning",
       showCancelButton: true,
       focusConfirm: false,
       confirmButtonColor: '#d33',
       confirmButtonText:'Yes, delete it!',
       cancelButtonText:'Cancel',
       })
       .then((willDelete) => {
           if (willDelete.value) {
               $.ajax({
                   url: $(this).data('url'),
                   type: 'DELETE',
                   data: { 'id' : ID },
                   success: function (data) {
                       if (data.success === true) {
                               $("#row-"+ID).remove();
                               Message.add(data.message, {type: 'success'});
                           } else if (data.success === false) {
                               Message.add(data.message, {type: 'error'});
                       } else {
                           Message.add(data.message, {type: 'error'});
                       }
                   },
                   error: function (data) {
                        Message.add(data.responseJSON.message, {type: 'error',sticky: true});
                   }
               });
           }
       });
})

$(".status").click(function(){
    var id = $(this).data('id');
    var status = $( this ).is( ":checked" )===true?1:2;
    $.ajax({
        url: $(this).data('url'),
        type: 'PATCH',
        data: { "status": status },
        success: function (data) {
            if (data.status==='success') {
                Message.add(data.message, {type: 'success'});
            } else{
                $('#status_'+id).prop("checked", function () { return (!this.checked); } );
                Message.add(data.message, {type: 'error'});
            } 
        },
        error: function(data) {
            $('#status_'+id).prop("checked", function () { return (!this.checked); } );
            Message.add(data.responseJSON.message, {type: 'error',sticky: true});
        },
    });
});
$(".sendtohome").click(function(){
    var id = $(this).data('id');
    var status = $( this ).is( ":checked" )===true?1:2;
    $.ajax({
        url: $(this).data('url'),
        type: 'PATCH',
        data: { "status": status },
        success: function (data) {
            if (data.status==='success') {
                Message.add(data.message, {type: 'success'});
            } else{
                $('#sendtohome_'+id).prop("checked", function () { return (!this.checked); } );
                Message.add(data.message, {type: 'error'});
            } 
        },
        error: function(data) {
            $('#sendtohome_'+id).prop("checked", function () { return (!this.checked); } );
            Message.add(data.responseJSON.message, {type: 'error',sticky: true});
        },
    });
});

$(".select2").select2();

if(assetcategory == 11){
    $(".gold").addClass('d-none');
    $(".gold input").attr('disabled',true);
    $(".gold select").attr('disabled',true);
    $(".gold textarea").attr('disabled',true);
}else{
    $(".gold").removeClass('d-none');
    $(".gold input").attr('disabled',false);
    $(".gold select").attr('disabled',false);
    $(".gold textarea").attr('disabled',false);
}

$("#custom_seo").change(function(){
    if($('#custom_seo').is(':checked')){
        $("#page_title").attr('readonly',false);
    }else{
        $("#page_title").attr('readonly',true);
    }
});
$("#asset_category_id").change(function(){
    if(this.value == 11){
        $(".gold").addClass('d-none');
        $(".gold input").attr('disabled',true);
        $(".gold select").attr('disabled',true);
        $(".gold textarea").attr('disabled',true);
    }else{
        $(".gold").removeClass('d-none');
        $(".gold input").attr('disabled',false);
        $(".gold select").attr('disabled',false);
        $(".gold textarea").attr('disabled',false);
    }
});
