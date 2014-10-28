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
    $('#status').val(data.requests[0].private_status);
    if(data.requests[0].total_received == 0)
    {
        $('#total_received').val('').focus();
    }
    else
    {
        $('#total_received').val(data.requests[0].total_received).focus();
    }
    $('#bucket').val(data.requests[0].bucket);
    $('#request_id_hidden').val(data.requests[0].request_id);
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

function status_ajax_done(data)
{
    for(i = 0; i < data.data.length; i++)
    {
        $('#status').append('<option value="'+data.data[i].status_id+'">'+data.data[i].name+'</option>');
    }
}

function save_request_done(data)
{
    $('#modal').modal('hide');
    $('#request_id').focus();
    if(data.error !== undefined)
    {
        alert(data.error);
        console.log(data);
    }
}

function save_request()
{
    $.ajax({
        url: '/tickets/ajax/request.php',
        data: $('#req_form').serialize(),
        dataType: 'json',
        type: 'post',
        success: save_request_done}); 
}

function init_page()
{
    $('#modal').modal({show: false});
    $('#modal').on('hidden.bs.modal', restore_focus);
    $.ajax({
        url: 'ajax/status.php',
        dataType: 'json',
        success: status_ajax_done});
}

$(init_page);
