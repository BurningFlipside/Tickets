function change_year(control)
{
    var data = 'filter=year eq '+$(control).val()+'&fmt=data-table';
    var table = $('#requests').DataTable();
    table.ajax.url('../api/v1/requests?'+data).load();
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
    $('td.details-control').html('<span class="glyphicon glyphicon-plus"></span>');
}

function show_tickets(data)
{
    var ret = '<table class="table">';
    ret += '<thead>';
    ret += '<th>Ticket</th>';
    ret += '<th>First Name</th>';
    ret += '<th>Last Name</th>';
    ret += '<th>Type</th>';
    ret += '</thead>';
    ret += '<tbody>';
    if(data.tickets !== undefined)
    {
        for(i = 0; i < data.tickets.length; i++)
        {
            ret += '<tr><td>'+(i*1 + 1)+'</td><td>'+data.tickets[i].first+'</td><td>'+data.tickets[i].last+'</td><td>'+data.tickets[i].type.typeCode+'</td></tr>';
        }
    }
    ret += '</tbody>';
    ret += '</table>';
    return ret;
}

function details_clicked()
{
    var tr = $(this).closest('tr');
    var row = $('#requests').DataTable().row(tr);
    if(row.child.isShown())
    {
        row.child.hide();
        $(this).html('<span class="glyphicon glyphicon-plus"></span>');
        tr.removeClass('shown');
    }
    else
    {
        row.child(show_tickets(row.data())).show();
        $(this).html('<span class="glyphicon glyphicon-minus"></span>');
        tr.addClass('shown');
    }
}

function save_request_done(data)
{
    $('#modal').modal('hide');
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

function save_request(control)
{
    $.ajax({
        url: '/tickets/ajax/request.php',
        data: $('#request_edit_form').serialize()+"&dataentry=1",
        dataType: 'json',
        type: 'post',
        success: save_request_done});
}

function row_clicked()
{
    var tr = $(this).closest('tr');
    var row = $('#requests').DataTable().row(tr);
    var data = row.data();
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
        $('<td/>').html('<input type="text" id="ticket_type_'+i+'" name="ticket_type_'+i+'" class="form-control" value="'+data.tickets[i].type.typeCode+'"/>').appendTo(new_row);
        new_row.appendTo($('#ticket_table tbody'));
    }
    $('#donation_table tbody').empty();
    if(data.donations !== undefined)
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
    console.log(data);
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
}

function status_ajax_done(data)
{
    for(i = 0; i < data.length; i++)
    {
        $('#status').append('<option value="'+data[i].status_id+'">'+data[i].name+'</option>');
    }
}

function init_page()
{
    data = 'filter=year eq '+$('#year').val()+'&fmt=data-table';
    $('#requests').dataTable({
        columns: [
            {'class': 'details-control', 'orderable': false, 'data': null, 'defaultContent': ''},
            {'data': 'request_id'},
            {'data': 'givenName'},
            {'data': 'sn'},
            {'data': total_due},
            {'data': child_data, 'visible': false}
        ],
        'order': [[1, 'asc']],
        'ajax': '../api/v1/requests?'+data
    });
    $('#requests').on('draw.dt', draw_done);
    $('#requests tbody').on('click', 'td.details-control', details_clicked);
    $('#requests tbody').on('click', 'td:not(.details-control)', row_clicked);
    $.ajax({
        url: '../api/v1/globals/statuses',
        dataType: 'json',
        success: status_ajax_done});
}

$(init_page);
