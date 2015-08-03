var out_of_window = false;
var test_mode = false;
var ticket_year = false;

function tableDrawComplete()
{
    if($("#ticketList").DataTable().data().length !== 0)
    {
        $("#ticket_set").show();
    }
    if($(window).width() < 768)
    {
        $('#ticketList th:nth-child(3)').hide();
        $('#ticketList td:nth-child(3)').hide();
        $('#ticketList th:nth-child(4)').hide();
        $('#ticketList td:nth-child(4)').hide();
    }
}

function get_words_done(data)
{
    $('#long_id_words').html(data.hash_words);
}

function show_long_id(hash)
{
    $('#long_id').html(hash);
    $('#long_id_words').html('');
    $.ajax({
        url: 'api/v1/tickets/'+hash+'?select=hash_words',
        type: 'get',
        dataType: 'json',
        success: get_words_done});
    $('#ticket_view_modal').modal('hide');
    $('#ticket_id_modal').modal('show');
}

function get_ticket_data_by_hash(hash)
{
    var json = $("#ticketList").DataTable().ajax.json();
    var ticket = null;
    var i;
    for(i = 0; i < json.data.length; i++)
    {
        if(json.data[i].hash == hash)
        {
            ticket = json.data[i];
        }
    }
    if(ticket === null)
    {
        json = $('#discretionary').DataTable().ajax.json();
        for(i = 0; i < json.data.length; i++)
        {
            if(json.data[i].hash == hash)
            {
                ticket = json.data[i];
            }
        }
    }
    return ticket;
}

function view_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket === null)
    {
        alert('Cannot find ticket');
        return;
    }
    $('[title]').tooltip('hide');
    $('#view_first_name').html(ticket.firstName);
    $('#view_last_name').html(ticket.lastName);
    $('#view_type').html(ticket.type);
    $('#view_short_code').html(ticket.hash.substring(0,7)).attr('onclick', 'show_long_id(\''+ticket.hash+'\')');
    $('#ticket_view_modal').modal('show');
}

function save_ticket_done(data)
{
    if(data.error !== undefined)
    {
        alert(data.error);
        return;
    }
    else
    {
        console.log(data);
        //location.reload();
    }
}

function save_ticket()
{
    $.ajax({
        url: 'api/v1/tickets/'+$('#show_short_code').data('hash'),
        type: 'patch',
        data: '{"firstName":"'+$('#edit_first_name').val()+'","lastName":"'+$('#edit_last_name').val()+'"}',
        processData: false,
        dataType: 'json',
        success: save_ticket_done});
    $('#ticket_edit_modal').modal('hide');
}

function edit_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket === null)
    {
        alert('Cannot find ticket');
        return;
    }
    $('[title]').tooltip('hide');
    $('#edit_first_name').val(ticket.firstName);
    $('#edit_last_name').val(ticket.lastName);
    $('#show_short_code').val(ticket.hash.substring(0,8)).data('hash', id);
    $('#ticket_edit_modal').modal('show');
}

function download_ticket_done(data)
{
    if(data.pdf !== undefined)
    {
        var win = window.open(data.pdf, '_blank');
        if(win === undefined)
        {
            alert('Popups are blocked! Please enable popups.');
        }
    }
}

function download_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var win = window.open('api/v1/tickets/'+id+'/pdf', '_blank');
    
}

function transfer_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket === null)
    {
        alert('Cannot find ticket');
        return;
    }
    window.location = 'transfer.php?id='+ticket.hash;
}

function short_hash(data, type, row, meta)
{
    return '<a href="#" onclick="show_long_id(\''+data+'\')">'+data.substring(0,8)+'</a>';
}

function make_actions(data, type, row, meta)
{
    var res = '';
    var view_options = {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'View Ticket Code', 'for': data, onclick: 'view_ticket(this)'};
    var edit_options = {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Edit Ticket<br/>Use this option to keep the ticket<br/>on your account but<br/>change the legal name.', 'data-html': true, 'for': data, onclick: 'edit_ticket(this)'};
    var pdf_options =  {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Download PDF', 'for': data, href: 'api/v1/tickets/'+data+'/pdf', target: '_blank'};
    var transfer_options = {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Transfer Ticket<br/>Use this option to send<br/>the ticket to someone else', 'data-html': true, 'for': data, onclick: 'transfer_ticket(this)'};
    var link;
    if(browser_supports_font_face())
    {
        var button;
        var glyph;
        if($(window).width() < 768)
        {
            view_options.type = 'button';
            button = $('<button/>', view_options);
            glyph = $('<span/>', {'class': 'glyphicon glyphicon-search'});
            glyph.appendTo(button);
            if(button.prop('outerHTML') === undefined)
            {
                res += new XMLSerializer().serializeToString(button[0]);
            }
            else
            {
                res += button.prop('outerHTML');
            }
        }
        edit_options.type = 'button';
        button = $('<button/>', edit_options);
        glyph = $('<span/>', {'class': 'glyphicon glyphicon-pencil'});
        glyph.appendTo(button);
        if(button.prop('outerHTML') === undefined)
        {
            res += new XMLSerializer().serializeToString(button[0]);
        }
        else
        {
            res += button.prop('outerHTML');
        }

	link = $('<a/>', pdf_options);
        glyph = $('<span/>', {'class': 'glyphicon glyphicon-cloud-download'});
        glyph.appendTo(link);
        if(link.prop('outerHTML') === undefined)
        {
            res += new XMLSerializer().serializeToString(link[0]);
        }
        else
        {
            res += link.prop('outerHTML');
        }

        transfer_options.type = 'button';
        button = $('<button/>', transfer_options);
        glyph = $('<span/>', {'class': 'glyphicon glyphicon-send'});
        glyph.appendTo(button);
        if(button.prop('outerHTML') === undefined)
        {
            res += new XMLSerializer().serializeToString(button[0]);
        }
        else
        {
            res += button.prop('outerHTML');
        }
    }
    else
    {
        if($(window).width() < 768)
        {
            link = $('<a/>', view_options);
            link.append("View");
            res += link.prop('outerHTML');
            res += '|';
        }
        link = $('<a/>', edit_options);
        link.append("Edit");
        res += link.prop('outerHTML');
        res += '|';

        link = $('<a/>', pdf_options);
        link.append("Download");
        res += link.prop('outerHTML');
        res += '|';

        link = $('<a/>', transfer_options);
        link.append("Transfer");
        res += link.prop('outerHTML');
    }
    return res;
}

function init_table()
{
    $('#ticketList').dataTable({
        "ajax": 'api/v1/ticket?fmt=data-table',
        columns: [
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'},
            {'data': 'hash', 'render': short_hash},
            {'data': 'hash', 'render': make_actions, 'class': 'action-buttons', 'orderable': false}
        ],
        paging: false,
        info: false,
        searching: false
    });

    $("#ticketList").on('draw.dt', tableDrawComplete);
}

function edit_request(control)
{
    var jq = $(control);
    var tmp = jq.attr('for');
    var ids = tmp.split('_');
    window.location = 'request.php?request_id='+ids[0]+'&year='+ids[1];
}

function email_request_done(data)
{
    console.log(data);
}

function email_request(control)
{
    var jq = $(control);
    var tmp = jq.attr('for');
    var ids = tmp.split('_');
    $.ajax({
        url: '/tickets/ajax/request.php',
        type: 'post',
        data: 'request_id='+ids[0]+'&year='+ids[1]+'&email=1',
        dataType: 'json',
        success: email_request_done});
}

function download_request_done(data)
{
    if(data.pdf !== undefined)
    {
        var win = window.open(data.pdf, '_blank');
        if(win === undefined)
        {
            alert('Popups are blocked! Please enable popups.');
        }
    }
    console.log(data);
}

function download_request(control)
{
    var jq = $(control);
    var tmp = jq.attr('for');
    var ids = tmp.split('_');
    $.ajax({
        url: '/tickets/ajax/request.php',
        type: 'post',
        data: 'request_id='+ids[0]+'&year='+ids[1]+'&pdf=1',
        dataType: 'json',
        success: download_request_done});
}

function add_buttons_to_row(row, id, year)
{
    var cell = $('<td/>', {style: 'white-space: nowrap;'});
    var edit_options = {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Edit Request', 'for': id+'_'+year, onclick: 'edit_request(this)'};
    var mail_options = {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Resend Request Email', 'for': id+'_'+year, onclick: 'email_request(this)'};
    var pdf_options =  {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Download PDF', 'for': id+'_'+year, onclick: 'download_request(this)'};
    var button;
    var link;
    var glyph;
    if(browser_supports_font_face())
    {
        edit_options.type = 'button';
        button = $('<button/>', edit_options);
        glyph = $('<span/>', {'class': 'glyphicon glyphicon-pencil'});
        glyph.appendTo(button);
        button.appendTo(cell);

        mail_options.type = 'button';
        button = $('<button/>', mail_options);
        glyph = $('<span/>', {'class': 'glyphicon glyphicon-envelope'});
        glyph.appendTo(button);
        button.appendTo(cell);

        pdf_options.type = 'button';
        button = $('<button/>', pdf_options);
        glyph = $('<span/>', {'class': 'glyphicon glyphicon-cloud-download'});
        glyph.appendTo(button);
        button.appendTo(cell);
    }
    else
    {
        link = $('<a/>', edit_options);
        link.append("Edit");
        link.appendTo(cell);
        cell.append("|");
        link = $('<a/>', mail_options);
        link.append("Resend");
        link.appendTo(cell);
        cell.append("|");
        link = $('<a/>', pdf_options);
        link.append("Download");
        link.appendTo(cell);
    }
    cell.appendTo(row);
}

function toggle_hidden_requests(e)
{
    var rows = $('tr.old_request');
    if(rows.is(':visible'))
    {
        rows.hide();
    }
    else
    {
        rows.show();
    }
}

function copy_request(e)
{
    var request = $(e.currentTarget).data('request');
    location = 'copy_request.php?id='+request.request_id+'&year='+request.year;
}

function add_old_request_to_table(tbody, request)
{
    var container = tbody.find('tr#old_requests');
    if(container.length === 0)
    {
        tbody.prepend('<tr id="old_requests" style="cursor: pointer;"><td colspan="5"><span class="glyphicon glyphicon-chevron-right"></span> Old Requests</td></tr>');
        container = tbody.find('tr#old_requests');
        container.on('click', toggle_hidden_requests);
    }
    var row = $('<tr class="old_request" style="display: none;">');
    row.append('<td/>');
    row.append('<td>'+request.year+'</td>');
    row.append('<td>'+request.tickets.length+'</td>');
    row.append('<td>$'+request.total_due+'</td>');
    var cell = $('<td>');
    var button = $('<button class="btn btn-link btn-sm" data-toggle="tooltip" data-placement="top" title="Copy Old Request"><span class="glyphicon glyphicon-copy"></span></button>');
    button.data('request', request);
    button.on('click', copy_request);
    cell.append(button);
    row.append(cell);
    container.after(row);
}

function add_request_to_table(tbody, request)
{
    if(request.year != ticket_year)
    {
        add_old_request_to_table(tbody, request);
        return;
    }
    var row = $('<tr/>');
    row.append('<td>'+request.request_id+'</td>');
    row.append('<td>'+request.year+'</td>');
    if(request.tickets !== null)
    {
        row.append('<td>'+request.tickets.length+'</td>');
    }
    else
    {
        row.append('<td>0</td>');
    }
    if(!out_of_window || test_mode)
    {
        row.append('<td>$'+request.total_due+'</td>');
    }
    else
    {
        var cell = $('<td/>');
        cell.attr('data-original-title', request.status.description);
        cell.attr('data-container', 'body');
        cell.attr('data-toggle', 'tooltip');
        cell.attr('data-placement', 'top');
        cell.html(request.status.name);
        cell.appendTo(row);
    }
    if(!out_of_window || test_mode)
    {
        add_buttons_to_row(row, request.request_id, request.year);
    }
    else
    {
        row.append('<td></td>');
    }
    row.appendTo(tbody);
    $('[data-original-title]').tooltip();
}

function process_requests(requests)
{
    var tbody = $('#requestList tbody');
    for(var i = 0; i < requests.length; i++)
    {
        add_request_to_table(tbody, requests[i]);
    }
    if($('#requestList tbody tr:visible:not("#old_requests")').length === 0)
    {
        tbody.append('<tr><td colspan="5" style="text-align: center;"><a href="request.php"><span class="glyphicon glyphicon-new-window"></span> Create a new request</a></td></tr>');
    }
    if($('[title]').length > 0)
    {
        $('[title]').tooltip();
    }
    if($(window).width() < 768)
    {
        $('#requestList th:nth-child(1)').hide();
        $('#requestList td:nth-child(1)').hide();
    }
}

function get_requests_done(jqXHR)
{
    if(jqXHR.status === 200)
    {
        if(jqXHR.responseJSON === undefined || jqXHR.responseJSON.length === 0)
        {
            if(out_of_window)
            {
                $('#requestList').empty();
            }
            else
            {
                $('#request_set').empty();
                $('#request_set').append("You do not currently have a current or previous ticket request.<br/>");
                $('#request_set').append('<a href="/tickets/request.php">Create a Ticket Request</a>');
            }
        }
        else
        {
            process_requests(jqXHR.responseJSON);
        }
        if($('#request_set').length > 0)
        {
            $('#request_set').show();
        }
    }
    else
    {
        console.log(jqXHR);
        alert('Error obtaining request!');
    }
}

function init_request()
{
    $.ajax({
        url: 'api/v1/request',
        type: 'get',
        dataType: 'json',
        complete: get_requests_done});
}

function get_window_done(data)
{
    var my_window = data;
    var now = new Date(Date.now());
    var start = new Date(my_window.request_start_date+" GMT-0600");
    var end = new Date(my_window.request_stop_date+" GMT-0600");
    var mail_start = new Date(my_window.mail_start_date+" GMT-0600");
    var server_now = new Date(my_window.current+" GMT-0600");
    //You can't replace this with < 
    if(!(start.getYear() > 2000))
    {
        start = new Date(my_window.request_start_date+"T06:00:00.000Z");
        end   = new Date(my_window.request_stop_date+"T06:00:00.000Z");
        mail_start = new Date(my_window.mail_start_date+"T06:00:00.000Z");
        server_now = new Date(my_window.current+"T06:00:00.000Z");
    }
    end.setHours(23);
    end.setMinutes(59);
    ticket_year = data.year;
    if(server_now < now)
    {
        now = server_now;
    }
    var alert_div;
    if(now < start || now > end)
    {
        alert_div = $('<div/>', {'class': 'alert alert-info', role: 'alert'});
        $('<strong/>').html('Notice: ').appendTo(alert_div);
        alert_div.append('The request window is currently closed. No new ticket requests are accepted at this time.');
        if(my_window.test_mode == '1')
        {
            alert_div.append(' But test mode is enabled. Any requests created will be deleted before ticketing starts!');
            $('#request_set').prepend(alert_div);
            test_mode = true;
        }
        else
        {
            $('#request_set').prepend(alert_div);
        }
        out_of_window = true;
        if(!test_mode)
        {
            $('#requestList th:nth-child(4)').html("Request Status");
        }
    }
    if(now > mail_start && now < end)
    {
        var days = Math.floor(end/(1000*60*60*24) - now/(1000*60*60*24));
        alert_div = $('<div/>', {'class': 'alert alert-warning', role: 'alert'});
        $('<strong/>').html('Notice: ').appendTo(alert_div);
        if(days === 1)
        {
            alert_div.append('The mail in window is currently open! You have '+days+' day left to mail your request!');
        }
        else if(days === 0)
        {
            alert_div.append('The mail in window is currently open! Today is the last day to mail your request!');
        }
        else
        {
            alert_div.append('The mail in window is currently open! You have '+days+' days left to mail your request!');
        }
        $('#request_set').prepend(alert_div);
    }
    init_request();
}

function init_window()
{
    $.ajax({
        url: 'api/v1/globals/window',
        type: 'GET',
        dataType: 'json',
        success: get_window_done});
}

function panel_heading_click(e)
{
    if($(this).hasClass('panel-collapsed'))
    {
        $(this).parents('.panel').find('.panel-body').slideDown();
        $(this).removeClass('panel-collapsed');
        $(this).find('i').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
    }
    else
    {
        $(this).parents('.panel').find('.panel-body').slideUp();
        $(this).addClass('panel-collapsed');
        $(this).find('i').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
    }
}

function init_index()
{
    $('.panel-heading span.clickable').on("click", panel_heading_click);
    init_window();
    init_table();
}

$(init_index);
