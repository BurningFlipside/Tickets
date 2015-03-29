function found_ticket(data)
{
    $('#search_ticket_modal').modal('hide');
    console.log(data);
    if(data.used !== '0')
    {
        add_notification($('#process_ticket_modal .modal-body'), 'Ticket is already used!', NOTIFICATION_FAILED, false);
        $('#used').attr('checked', true);
    }
    else
    {
        $('#used').removeAttr('checked');
    }
    if(data.void !== '0')
    {
        add_notification($('#process_ticket_modal .modal-body'), 'Ticket is void!', NOTIFICATION_FAILED, false);
        $('#void').attr('checked', true);
    }
    else
    {
        $('#void').removeAttr('checked');
    }
    $('#hash').val(data.hash);
    $('#firstName').val(data.firstName);
    $('#lastName').val(data.lastName);
    if((data.guardian_first === null && data.guardian_last === null) ||
       (data.guardian_first === '' && data.guardian_last === ''))
    {
        $('#guadian_first').val('');
        $('#guadian_last').val('');
        $('#minor_block').attr('hidden', 'true');
    }
    else
    {
        $('#guadian_first').val(data.guardian_first);
        $('#guadian_last').val(data.guardian_last);
        $('#minor_block').removeAttr('hidden');
    }
    $('#physical_ticket_id').val(data.physical_ticket_id);
    $('#comments').val(data.comments);
    $('#process_ticket_modal').modal('show');
}

function search_done(data)
{
    if(data.length === undefined || data.length === 0)
    {
        search_failed();
        return;
    }
    var table = $('#search_ticket_table').DataTable();
    table.clear();
    for(i = 0; i < data.length; i++)
    {
        table.row.add(data[i]);
    }
    console.log(data);
    table.draw();
    $('#search_ticket_modal').modal('show');
}

function search_failed(jqXHR)
{
    alert('Unable to locate ticket!');
}

function really_search(jqXHR)
{
    var filter = 'filter='+
                 'substringof(firstName,\''+this+'\') or '+
                 'substringof(lastName,\''+this+'\') or '+
                 'substringof(hash,\''+this+'\') or '+
                 'substringof(email,\''+this+'\') or '+
                 'substringof(request_id,\''+this+'\')';
    $.ajax({
        url:  '../api/v1/tickets',
        data: filter,
        type: 'get',
        dataType: 'json',
        success: search_done,
        error: search_failed
    });
}

function get_ticket(hash)
{
    $.ajax({
        url:  '../api/v1/tickets/'+hash,
        type: 'get',
        dataType: 'json',
        context: hash,
        success: found_ticket,
        error: really_search
    });
}

function ticket_clicked()
{
    var table = $('#search_ticket_table').DataTable();
    var tr = $(this).closest('tr');
    var row = table.row(tr);
    found_ticket(row.data());
}

function ticket_search(evt)
{
    if(evt.which !== 13) return;
    var value = $(this).val();
    //Try this as a ticket
    get_ticket(value);
}

function focus_on_ticket_id()
{
    $('#physical_ticket_id').focus();
}

function focus_on_search()
{
    $('#ticket_search').val('');
    $('#ticket_search').focus();
}

function revert_screen()
{
    $('.navbar').show();
    $('#page-wrapper').css('margin', '0 0 0 250px').css('width', '').css('height', '');
    $('#screen').html('<span class="glyphicon glyphicon-fullscreen"></span>').attr('title', 'fullscreen').click(fullscreen);
}

function fullscreen()
{
    $('.navbar').hide();
    $('#page-wrapper').css('width', '100%').css('height', '100%').css('margin', '0');
    $('#screen').html('<span class="glyphicon glyphicon-resize-small"></span>').attr('title', 'revert').click(revert_screen);
}

function init_gate_page()
{
    $('#ticket_search').keypress(ticket_search);
    $('#process_ticket_modal').on('shown.bs.modal', focus_on_ticket_id);
    $('#process_ticket_modal').on('hidden.bs.modal', focus_on_search);
    $('#search_ticket_table').dataTable({
        'columns': [
            {'data': 'hash'},
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'}
        ]
    });
    $('#search_ticket_table').on('click', 'tr', ticket_clicked);
}

$(init_gate_page);
