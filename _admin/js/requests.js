var ticketSystem = new TicketSystem('../api/v1');

function requeryTable()
{
    var year = $('#year').val();
    var status = $('#statusFilter').val();
    var filter = 'year eq '+year;
    if(status !== '*')
    {
        filter+=' and private_status eq '+status;
    }
    var table = $('#requests').DataTable();
    table.ajax.url(ticketSystem.getRequestDataTableUri(filter)).load();
}

function change_year(control)
{
    requeryTable();
}

function changeStatusFilter(control)
{
   requeryTable();
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

function draw_done()
{
    $('td.details-control').html('<span class="fa fa-plus"></span>');
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

function request_donations_loaded(data)
{
    var tbody = $('#donation_table tbody');
    for(var i = 0; i < data.length; i++)
    {
        var new_row = $('<tr/>');
        $('<td/>').html(data[i].type).appendTo(new_row);
        $('<td/>').html('<input type="text" id="donation_amount_'+data[i].type+'" name="donation_amount_'+data[i].type+'" class="form-control" value="'+data[i].amount+'"/>').appendTo(new_row);
        new_row.appendTo(tbody);
    }
    this.donations = data;
}

function saveRequestDone(data, err) {
    if(err !== null) {
        if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
            alert(err.jsonResp.message);
        }
        else {
            console.log(err);
            alert('Unable to update request!');
        }
        return;
    }
    $('#modal').modal('hide');
    change_year($('#year'));
}

function save_request(control)
{
    var obj = {};
    var a = $('#request_edit_form').serializeArray();
    for(var i = 0; i < a.length; i++)
    {
        var name = a[i].name;
        var split = name.split('_');
        if(split[0] == 'ticket')
        {
            var child_name = split[1];
            if(obj['tickets'] === undefined)
            {
                obj['tickets'] = [];
            }
            if(obj['tickets'].length === 0 || obj['tickets'][obj['tickets'].length-1][child_name] !== undefined)
            {
                 obj['tickets'][obj['tickets'].length] = {};
            }
            obj['tickets'][obj['tickets'].length-1][child_name] = a[i].value;
        }
        else if(split[0] == 'donation')
        {
            if(obj['donations'] === undefined)
            {
                obj['donations'] = {};
            }
            if(obj['donations'][split[2]] === undefined)
            {
                obj['donations'][split[2]] = {};
            }
            obj['donations'][split[2]][split[1]] = a[i].value;
        }
        else
        {
            if(a[i].value === 'on')
            {
                a[i].value = 1;
            }
            if(name === 'critvol')
            {
                name = 'crit_vol';
            }
            obj[name] = a[i].value;
        }
    }
    obj.minor_confirm = true;
    ticketSystem.updateRequest(obj, saveRequestDone); 
}

function edit_request(control)
{
    window.location = '../request.php?request_id='+$('#request_id').val();
}

function getPDF(control)
{
    var request = $('#modal').data('request');
    window.location = request.getPdfUri();
}

function getCSV()
{
    var uri = $('#requests').DataTable().ajax.url();
    uri = uri.replace('fmt=data-table', '$format=csv2');
    window.location = uri;
}

function row_clicked()
{
    var tr = $(this).closest('tr');
    var row = $('#requests').DataTable().row(tr);
    var data = new TicketRequest(row.data(), ticketSystem);
    $('#modal').modal();
    $('#modal_title').html('Request #'+data.request_id);
    $('#request_id').val(data.request_id);
    $('#givenName').val(data.givenName);
    $('#sn').val(data.sn);
    $('#mail').val(data.mail);
    $('#c').val(data.c);
    $('#mobile').val(data.mobile);
    $('#street').val(data.street);
    $('#zip').val(data.zip);
    $('#l').val(data.l);
    $('#st').val(data.st);
    $('#ticket_table tbody').empty();
    for(i = 0; i < data.tickets.length; i++)
    {
        var new_row = $('<tr/>');
        $('<td/>').html('<input type="text" id="ticket_first_'+i+'" name="ticket_first_'+i+'" class="form-control" value="'+data.tickets[i].first+'"/>').appendTo(new_row);
        $('<td/>').html('<input type="text" id="ticket_last_'+i+'" name="ticket_last_'+i+'" class="form-control" value="'+data.tickets[i].last+'"/>').appendTo(new_row);
        $('<td/>').html('<input type="text" id="ticket_type_'+i+'" name="ticket_type_'+i+'" class="form-control" value="'+data.tickets[i].type+'"/>').appendTo(new_row);
        new_row.appendTo($('#ticket_table tbody'));
    }
    $('#donation_table tbody').empty();
    if(data.donations !== null)
    {
        for(i = 0; i < data.donations.length; i++)
        {
            var new_row = $('<tr/>');
            $('<td/>').html(data.donations[i].type.entityName).appendTo(new_row);
            $('<td/>').html('<input type="text" id="ticket_type_'+data.donations[i].type.entityName+'" name="ticket_type_'+data.donations[i].type.entityName+'" class="form-control" value="'+data.donations[i].amount+'"/>').appendTo(new_row);
            new_row.appendTo($('#donation_table tbody'));
        }
    }
    $('#total_due').val('$'+data.total_due);
    $('#status').val(data.private_status);
    $('#total_received').val(data.total_received);
    $('#comments').val(data.comments);
    $('#bucket').val(data.bucket);
    if(data.envelopeArt != '0')
    {
        $('#envelopeArt').prop('checked', true);
    }
    else
    {
        $('#envelopeArt').prop('checked', false);
    }

    if(data.crit_vol != '0')
    {
        $('#critvol').prop('checked', true);
    }
    else
    {
        $('#critvol').prop('checked', false);
    }
    if(data.protected != '0')
    {
        $('#protected').prop('checked', true);
    }
    else
    {
        $('#protected').prop('checked', false);
    }
    $('#modal').data('request', data);
}

function status_ajax_done(data)
{
    for(i = 0; i < data.length; i++)
    {
        $('#status').append('<option value="'+data[i].status_id+'">'+data[i].name+'</option>');
        $('#statusFilter').append('<option value="'+data[i].status_id+'">'+data[i].name+'</option>');
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

function init_page()
{
    $.ajax({
        url: '../api/v1/globals/years',
        type: 'get',
        dataType: 'json',
        complete: gotTicketYears});
    $('#requests').on('draw.dt', draw_done);
    $('#requests').dataTable({
        'columns': [ 
            {'data': 'request_id'},
            {'data': 'givenName'},
            {'data': 'sn'},
            {'data': 'mail'},
            {'data': 'total_due'}
        ]
    });
    $('#requests tbody').on('click', 'td:not(.details-control)', row_clicked);
    $.ajax({
        url: '../api/v1/globals/statuses',
        dataType: 'json',
        success: status_ajax_done});
}

$(init_page);
