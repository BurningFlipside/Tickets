var ticket_data = null;

function short_hash(data, type, row, meta)
{
    var short_hash = data.substring(0,7);
    return '<a style="cursor: pointer;" onclick="view_ticket(\''+data+'\');">'+short_hash+'</a>';
}

function show_ticket_from_data(data)
{
    var read_only = true;
    if(data.selected == -1)
    {
        ticket = data.current;
        $('#right_arrow').hide();
        if(data.history !== undefined && data.history.length > 0)
        {
            $('#left_arrow').show();
        }
        else
        {
            $('#left_arrow').hide();
        }
        read_only = false;
    }
    else
    {
        ticket = data.history[data.selected];
        if(data.selected == (data.history.length - 1))
        {
            $('#left_arrow').hide();
        }
        else
        {
            $('#left_arrow').show();
        }
        $('#right_arrow').show();
    }
    $('#hash').val(ticket.hash);
    $('#year').val(ticket.year);
    $('#firstName').val(ticket.firstName);
    $('#lastName').val(ticket.lastName);
    $('#email').val(ticket.email);
    $('#request_id').val(ticket.request_id);
    $('#type').val(ticket.type);
    $('#guardian_first').val(ticket.guardian_first);
    $('#guardian_last').val(ticket.guardian_last);
    $('#sold').val(ticket.sold);
    $('#used').val(ticket.used);
    $('#void').val(ticket.void);
    if(read_only)
    {
        $('#firstName').prop('disabled', true);
        $('#lastName').prop('disabled', true);
        $('#email').prop('disabled', true);
        $('#request_id').prop('disabled', true);
        $('#type').prop('disabled', true);
        $('#guardian_first').prop('disabled', true);
        $('#guardian_last').prop('disabled', true);
        $('#sold').prop('disabled', true);
        $('#used').prop('disabled', true);
        $('#void').prop('disabled', true);
    }
    else
    {
        $('#firstName').prop('disabled', false);
        $('#lastName').prop('disabled', false);
        $('#email').prop('disabled', false);
        $('#request_id').prop('disabled', false);
        $('#type').prop('disabled', false);
        $('#guardian_first').prop('disabled', false);
        $('#guardian_last').prop('disabled', false);
        $('#sold').prop('disabled', false);
        $('#used').prop('disabled', false);
        $('#void').prop('disabled', false);
    }
    $('#ticket_modal').modal('show');
    console.log(data);
}

function ticket_data_done(data)
{
    var ticket = null;
    if(data.data.selected === undefined)
    {
        alert(data.error);
        console.log(data);
        return;
    }
    ticket_data = data.data;
    show_ticket_from_data(data.data);
}

function view_ticket(hash)
{
    $.ajax({
        url: '/tickets/ajax/tickets.php',
        data: 'hash='+hash+'&with_history=1',
        type: 'get',
        dataType: 'json',
        success: ticket_data_done}); 
}

function prev_ticket()
{
    ticket_data.selected++;
    show_ticket_from_data(ticket_data);
}

function next_ticket()
{
    ticket_data.selected--;
    show_ticket_from_data(ticket_data);
}

function table_searched()
{
    var dt_api = $('#tickets').DataTable();
    if(dt_api.rows({'search':'applied'})[0].length == 0)
    {
        alert("TODO: Search backend for ticket ID because not already on client");
    }
}

function init_page()
{
    $('#tickets').dataTable({
        "ajax": '/tickets/ajax/tickets.php?all=1',
        columns: [
            {'data': 'hash', 'render':short_hash},
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'}
        ]
    });

    $('#tickets').on('search.dt', table_searched);
}

$(init_page)
