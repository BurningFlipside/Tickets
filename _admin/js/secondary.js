function change_year(control)
{
    var data = 'filter=year eq '+$(control).val()+'&fmt=data-table';
    var table = $('#requests').DataTable();
    table.ajax.url('../api/v1/secondary/requests?'+data).load();
}

function total_due(row, type, val, meta)
{
    return '$'+row.total_due;
}

function child_data(row, type, val, meta)
{
    var res = '';
    if(row.tickets !== undefined && row.tickets !== null)
    {
        for(i = 0; i < row.tickets.length; i++)
        {
            res += row.tickets[i].first+' '+row.tickets[i].last+' ';
        }
    }
    return res;
}

function request_loaded(data)
{
    for(var i = 0; i < data.length; i++)
    {
        var tbody = $('#'+data[i].request_id);
        tbody.append('<tr><td>'+(i*1 + 1)+'</td><td>'+data[i].first+'</td><td>'+data[i].last+'</td><td>'+data[i].type+'</td></tr>');
    }
    this.tickets = data;
}

function request_tickets_loaded(data)
{
    for(var i = 0; i < data.length; i++)
    {
        var new_row = $('<tr/>');
        $('<td/>').html('<input type="text" id="ticket_first_'+i+'" name="ticket_first_'+i+'" class="form-control" value="'+data[i].first+'"/>').appendTo(new_row);
        $('<td/>').html('<input type="text" id="ticket_last_'+i+'" name="ticket_last_'+i+'" class="form-control" value="'+data[i].last+'"/>').appendTo(new_row);
        $('<td/>').html('<input type="text" id="ticket_type_'+i+'" name="ticket_type_'+i+'" class="form-control" value="'+data[i].type+'"/>').appendTo(new_row);
        new_row.appendTo($('#ticket_table tbody'));
    }
    this.tickets = data;
}

function ticketRequestDone(data)
{
    if(data.error !== undefined)
    {
        alert(data.error);
        console.log(data);
    }
    else
    {
        change_year($('#year'));
    }
}

function saveRequestDone(data)
{
    $('#modal').modal('hide');
    $.ajax({
        url: '../api/v1/secondary/requests/'+$('#request_id').val()+'/current/Actions/Ticket',
        processData: false,
        dataType: 'json',
        type: 'post',
        success: ticketRequestDone});
}

function saveRequest(control)
{
    var obj = $('#request_edit_form').serializeObject();
    obj.total_due = obj.total_due.substring(1);
    $.ajax({
        url: '../api/v1/secondary/requests/'+$('#request_id').val()+'/current',
        contentType: 'application/json',
        data: JSON.stringify(obj),
        processData: false,
        dataType: 'json',
        type: 'patch',
        success: saveRequestDone});
}

function getPDF(control)
{
    var year = $('#year').val();
    window.location = '../api/v1/secondary/'+$('#request_id').val()+'/'+year+'/pdf';
}

function getCSV()
{
    var year = $('#year').val();
    window.location = '../api/v1/secondary/requests?$format=csv&$filter=year eq '+year;
}

function rowClicked()
{
    var tr = $(this).closest('tr');
    var row = $('#requests').DataTable().row(tr);
    var data = row.data();
    $('#ticketButton').prop('disabled', true);
    $('#modal').modal();
    $('#modal_title').html('Request #'+data.request_id);
    $('#request_id').val(data.request_id);
    $('#givenName').val(data.givenName);
    $('#sn').val(data.sn);
    $('#mail').val(data.mail);
    $('#c').val(data.c);
    $('#street').val(data.street);
    $('#zip').val(data.zip);
    $('#l').val(data.l);
    $('#st').val(data.st);
    $('#ticket_table tbody').empty();
    if(typeof(data.valid_tickets) === 'string')
    {
        data.valid_tickets = JSON.parse(data.valid_tickets);
    }
    for(let i = 0; i < data.valid_tickets.length; i++)
    {
        var new_row = $('<tr/>');
        var type = data.valid_tickets[i].substring(0, 1);
        var id = data.valid_tickets[i];
        $('<td/>').html('<input type="text" id="ticket_first_'+id+'" name="ticket_first_'+id+'" class="form-control" value="'+data['ticket_first_'+id]+'"/>').appendTo(new_row);
        $('<td/>').html('<input type="text" id="ticket_last_'+id+'" name="ticket_last_'+id+'" class="form-control" value="'+data['ticket_last_'+id]+'"/>').appendTo(new_row);
        $('<td/>').html(type).appendTo(new_row);
        new_row.appendTo($('#ticket_table tbody'));
    }
    for(i = 0; i < data.tickets.length; i++)
    {
        var new_row = $('<tr/>');
        $('<td/>').html('<input type="text" id="ticket_first_'+i+'" name="ticket_first_'+i+'" class="form-control" value="'+data.tickets[i].first+'"/>').appendTo(new_row);
        $('<td/>').html('<input type="text" id="ticket_last_'+i+'" name="ticket_last_'+i+'" class="form-control" value="'+data.tickets[i].last+'"/>').appendTo(new_row);
        $('<td/>').html('<input type="text" id="ticket_type_'+i+'" name="ticket_type_'+i+'" class="form-control" value="'+data.tickets[i].type+'"/>').appendTo(new_row);
        new_row.appendTo($('#ticket_table tbody'));
    }
    $('#total_due').val('$'+data.total_due);
    $('#total_received').val(data.total_received);
}

function totalChanged()
{
    var due = $('#total_due').val().substring(1);
    var received = $('#total_received').val();
    if(due === received || (received[0] === '$' && due === received.substring(1)))
    {
        $('#ticketButton').prop('disabled', false);
    }
}

function gotTicketYears(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        alert('Unable to obtain valid ticket years!');
        console.log(jqXHR);
        return;
    }
    jqXHR.responseJSON.sort().reverse();
    for(var i = 0; i < jqXHR.responseJSON.length; i++)
    {
        $('#year').append($('<option/>').attr('value', jqXHR.responseJSON[i]).text(jqXHR.responseJSON[i]));
    }
    change_year($('#year'));
}

function initPage()
{
    $.ajax({
        url: '../api/v1/globals/years',
        type: 'get',
        dataType: 'json',
        complete: gotTicketYears});
    $('#requests').dataTable({
        'columns': [ 
            {'data': 'request_id'},
            {'data': 'givenName'},
            {'data': 'sn'},
            {'data': 'total_due'}
        ]
    });
    $('#requests tbody').on('click', 'td', rowClicked);
    $('#total_received').on('change', totalChanged);
}

$(initPage);
