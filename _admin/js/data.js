function request_ajax_done(data)
{
    if(data.requests === undefined)
    {
        $('#modal').modal('hide');
        alert("Unable to locate request id "+$('#save_btn').data('id'));
        $('#request_id').focus();
        return;
    }
    $('#total_due').val('$'+data.requests[0].total_due);
    $('#total_received').val('').focus();
    $('#bucket').val(data.requests[0].bucket);
    console.log(data);
}

function lookup_request(control)
{
    var id = $(control).val();
    $('#modal_title').html('Request #'+id);
    $('#modal').modal('show');
    $.ajax({
            url: '/tickets/ajax/request.php',
            data: 'request_id='+id+'&genbucket=1',
            type: 'get',
            dataType: 'json',
            success: request_ajax_done});
    $('#save_btn').data('id', id);
    $(control).val('');
}

function restore_focus()
{
    $('#request_id').focus();
}

function init_page()
{
    $('#modal').modal({show: false});
    $('#modal').on('hidden.bs.modal', restore_focus);
}

$(init_page);
