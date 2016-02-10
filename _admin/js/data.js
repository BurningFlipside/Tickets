function request_ajax_done(data)
{
    if(data.requests === undefined)
    {
        $('#modal').modal('hide');
        alert("Unable to locate request id "+$('#save_btn').data('id'));
        $('#request_id').focus();
        return;
    }
    $('#modal_title').html('Request #'+data.requests[0].request_id);
    $('#given_name').val(data.requests[0].givenName);
    $('#last_name').val(data.requests[0].sn);
    $('#total_due').val('$'+data.requests[0].total_due);
    $('#comments').val(data.requests[0].comments);
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
    $('#save_btn').data('id', data.requests[0].id);
}

function lookup_request(control)
{
    var id = $(control).val();
    $('#modal').modal('show');
    $.ajax({
            url: '/tickets/ajax/request.php',
            data: 'request_id='+id+'&genbucket=1',
            type: 'get',
            dataType: 'json',
            success: request_ajax_done});
    $(control).val('');
}

function lookup_request_by_id(id)
{
    $('#request_select').modal('hide');
    $('#modal').modal('show');
    $.ajax({
            url: '/tickets/ajax/request.php',
            data: 'request_id='+id+'&genbucket=1',
            type: 'get',
            dataType: 'json',
            success: request_ajax_done});
}

function lookup_ajax_done(data)
{
    if(data.requests.length == 1)
    {
        $('#request_id').val(data.requests[0].request_id);
        lookup_request($('#request_id'));
    }
    else
    {
        $('#request_select').modal('show');
        var table = $('#request_table').DataTable();
        table.clear();
        for(i = 0; i < data.requests.length; i++)
        {
            var row = [
                '<a href="#" onclick="lookup_request_by_id(\''+data.requests[i].request_id+'\');">'+data.requests[i].request_id+'</a>',
                data.requests[i].givenName+' '+data.requests[i].sn,
                data.requests[i].mail
            ];
            table.row.add(row);
        }
        table.draw();
    }
}

function lookup_request_by_value(control)
{
    var type  = $('#type').data('type');
    var value = $(control).val();
    $.ajax({
            url: '/tickets/ajax/request.php',
            data: 'type='+type+'&value='+value,
            type: 'get',
            dataType: 'json',
            success: lookup_ajax_done});
    $(control).val('');
}

function change_menu(value, text)
{
    $('#type').data('type', value);
    $('#type').html(text+'  <span class="caret"></span>');
}

function restore_focus()
{
    $('#request_id').focus();
}

function status_ajax_done(jqXHR)
{
    if(jqXHR.responseJSON === undefined)
    {
        alert('Unable to obtain status data!');
        return;
    }
    var data = jqXHR.responseJSON;
    for(i = 0; i < data.length; i++)
    {
        $('#status').append('<option value="'+data[i].status_id+'">'+data[i].name+'</option>');
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
    if($('#total_received').val() == '')
    {
        alert('Need Total Received!');
        return;
    }
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
        url: '../api/v1/globals/statuses',
        dataType: 'json',
        complete: status_ajax_done});
    $('#request_table').dataTable();
}

$(init_page);
