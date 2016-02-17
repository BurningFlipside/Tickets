function request_ajax_done(jqXHR)
{
    if(jqXHR.responseJSON === undefined)
    {
        $('#modal').modal('hide');
        alert("Unable to locate request id "+$('#save_btn').data('id'));
        $('#request_id').focus();
        return;
    }
    var data = jqXHR.responseJSON;
    console.log(data);
    $('#modal_title').html('Request #'+data.request_id);
    $('#given_name').val(data.givenName);
    $('#last_name').val(data.sn);
    $('#total_due').val('$'+data.total_due);
    $('#comments').val(data.comments);
    $('#status').val(data.private_status);
    if(data.total_received === 0 || data.total_received === '0')
    {
        $('#total_received').val('');
    }
    else
    {
        $('#total_received').val(data.total_received);
    }
    setTimeout(function(){$("#total_received").focus();}, 300);
    $('#bucket').val(data.bucket);
    $('#request_id_hidden').val(data.request_id);
    $('#save_btn').data('id', data.id);
}

function lookup_request(control)
{
    var id = $(control).val();
    lookup_request_by_id(id);
    $(control).val('');
}

function lookup_request_by_id(id)
{
    $('#request_select').modal('hide');
    $('#modal').modal('show');
    $.ajax({
            url: '../api/v1/requests/'+id+'/current/Actions/Requests.GetBucket',
            type: 'POST',
            dataType: 'json',
            complete: request_ajax_done});
}

function lookup_ajax_done(jqXHR)
{
    if(jqXHR.status != 200 || jqXHR.responseJSON === undefined || jqXHR.responseJSON.length === 0)
    {
        alert('No request found!');
    }
    var data = jqXHR.responseJSON;
    if(data.length == 1)
    {
        $('#request_id').val(data[0].request_id);
        lookup_request($('#request_id'));
    }
    else
    {
        $('#request_select').modal('show');
        var table = $('#request_table').DataTable();
        table.clear();
        for(i = 0; i < data.length; i++)
        {
            var row = [
                '<a href="#" onclick="lookup_request_by_id(\''+data[i].request_id+'\');">'+data[i].request_id+'</a>',
                data[i].givenName+' '+data[i].sn,
                data[i].mail
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
    if(type === '*')
    {
        $.ajax({
            url: '../api/v1/requests',
            data: '$search='+value+'&$filter=year eq current',
            type: 'get',
            dataType: 'json',
            complete: lookup_ajax_done});
    }
    else
    {
        $.ajax({
            url: '../api/v1/requests',
            data: '$filter=contains('+type+','+value+') and year eq current',
            type: 'get',
            dataType: 'json',
            complete: lookup_ajax_done});
    }
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
