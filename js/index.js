var out_of_window = false;
var test_mode = false;

function tableDrawComplete()
{
    if($("#ticketList").DataTable().data().length != 0)
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
    $('#long_id_words').html(data.data);
}

function show_long_id(hash)
{
    $('#long_id').html(hash);
    $('#long_id_words').html('');
    $.ajax({
        url: '/tickets/ajax/tickets.php?hash_to_words='+hash,
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
    for(var i = 0; i < json.data.length; i++)
    {
        if(json.data[i].hash == hash)
        {
            ticket = json.data[i];
        }
    }
    return ticket;
}

function view_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket == null)
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
        location.reload();
    }
}

function save_ticket()
{
    $.ajax({
        url: '/tickets/ajax/tickets.php',
        type: 'post',
        data: 'hash='+$('#show_short_code').data('hash')+'&first='+$('#edit_first_name').val()+'&last='+$('#edit_last_name').val(),
        dataType: 'json',
        success: save_ticket_done});
    $('#ticket_edit_modal').modal('hide');
}

function edit_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket == null)
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
    if(data.pdf != undefined)
    {
        var win = window.open(data.pdf, '_blank');
        if(win == undefined)
        {
            alert('Popups are blocked! Please enable popups.');
        }
    }
}

function download_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket == null)
    {
        alert('Cannot find ticket');
        return;
    }
    $.ajax({
        url: '/tickets/ajax/tickets.php',
        type: 'post',
        data: 'hash='+ticket.hash+'&year='+ticket.year+'&pdf=1',
        dataType: 'json',
        success: download_ticket_done});
}

function transfer_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket == null)
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
    var view_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'View Ticket Code', for: data, onclick: 'view_ticket(this)'};
    var edit_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Edit Ticket<br/>Use this option to keep the ticket<br/>on your account but<br/>change the legal name.', 'data-html': true, for: data, onclick: 'edit_ticket(this)'};
    var pdf_options =  {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Download PDF', for: data, onclick: 'download_ticket(this)'};
    var transfer_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Transfer Ticket<br/>Use this option to send<br/>the ticket to someone else', 'data-html': true, for: data, onclick: 'transfer_ticket(this)'};
    if(browser_supports_font_face())
    {
        if($(window).width() < 768)
        {
            view_options.type = 'button';
            var button = $('<button/>', view_options);
            var glyph = $('<span/>', {class: 'glyphicon glyphicon-search'});
            glyph.appendTo(button);
            res += button.prop('outerHTML');
        }
        edit_options.type = 'button';
        var button = $('<button/>', edit_options);
        var glyph = $('<span/>', {class: 'glyphicon glyphicon-pencil'});
        glyph.appendTo(button);
        res += button.prop('outerHTML');

        pdf_options.type = 'button';
        button = $('<button/>', pdf_options);
        glyph = $('<span/>', {class: 'glyphicon glyphicon-cloud-download'});
        glyph.appendTo(button);
        res += button.prop('outerHTML');

        transfer_options.type = 'button';
        button = $('<button/>', transfer_options);
        glyph = $('<span/>', {class: 'glyphicon glyphicon-send'});
        glyph.appendTo(button);
        res += button.prop('outerHTML');
    }
    else
    {
        if($(window).width() < 768)
        {
            var link = $('<a/>', view_options);
            link.append("View");
            res += link.prop('outerHTML');
            res += '|';
        }
        var link = $('<a/>', edit_options);
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
        "ajax": '/tickets/ajax/tickets.php',
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
    if(data.pdf != undefined)
    {
        var win = window.open(data.pdf, '_blank');
        if(win == undefined)
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
    var edit_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Edit Request', for: id+'_'+year, onclick: 'edit_request(this)'};
    var mail_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Resend Request Email', for: id+'_'+year, onclick: 'email_request(this)'};
    var pdf_options =  {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Download PDF', for: id+'_'+year, onclick: 'download_request(this)'};
    if(browser_supports_font_face())
    {
        edit_options.type = 'button';
        var button = $('<button/>', edit_options);
        var glyph = $('<span/>', {class: 'glyphicon glyphicon-pencil'});
        glyph.appendTo(button);
        button.appendTo(cell);

        mail_options.type = 'button';
        button = $('<button/>', mail_options);
        glyph = $('<span/>', {class: 'glyphicon glyphicon-envelope'});
        glyph.appendTo(button);
        button.appendTo(cell);

        pdf_options.type = 'button';
        button = $('<button/>', pdf_options);
        glyph = $('<span/>', {class: 'glyphicon glyphicon-cloud-download'});
        glyph.appendTo(button);
        button.appendTo(cell);
    }
    else
    {
        var link = $('<a/>', edit_options);
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

function add_request_to_table(tbody, request)
{
    var row = $('<tr/>');
    var cell = $('<td/>');
    cell.html(request.request_id);
    cell.appendTo(row);
    cell = $('<td/>');
    cell.html(request.year);
    cell.appendTo(row);
    cell = $('<td/>');
    if(request.tickets != null)
    {
        cell.html(request.tickets.length);
    }
    else
    {
        cell.html(0);
    }
    cell.appendTo(row);
    cell = $('<td/>');
    if(!out_of_window || test_mode)
    {
        var total = 0;
        if(request.tickets != null)
        {
            for(i = 0; i < request.tickets.length; i++)
            {
                total += (request.tickets[i].type.cost)*1;
            }
        }
        if(request.donations != null)
        {
            for(i = 0; i < request.donations.length; i++)
            {
                if(request.donations[i].amount !== undefined)
                {
                    total += (request.donations[i].amount)*1;
                }
            }
        }
        cell.html('$'+total);
    }
    else
    {
        cell.attr('data-original-title', request.status.description);
        cell.attr('data-container', 'body');
        cell.attr('data-toggle', 'tooltip');
        cell.attr('data-placement', 'top');
        cell.html(request.status.name);
    }
    cell.appendTo(row);
    if(!out_of_window || test_mode)
    {
        add_buttons_to_row(row, request.request_id, request.year);
    }
    else
    {
        cell = $('<td/>');
        cell.appendTo(row);
    }
    row.appendTo(tbody);
    $('[data-original-title]').tooltip();
}

function get_requests_done(data)
{
    if(data.error)
    {
        if(data.error.startsWith("Access Denied"))
        {
        }
        else
        {
            alert('Error obtaining request data: '+data.error);
            console.log(data);
        }
    }
    else
    {
        if(data.requests == undefined || data.requests == null)
        {
            $('#request_set').empty();
            $('#request_set').append("You do not currently have a ticket request.<br/>");
            $('#request_set').append('<a href="/tickets/request.php">Create a Ticket Request</a>');
        }
        else
        {
            var tbody = $('#requestList tbody');
            for(i = 0; i < data.requests.length; i++)
            {
                add_request_to_table(tbody, data.requests[i]);
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
        if($('#request_set').length > 0)
        {
            $('#request_set').show();
        }
    }
}

function init_request()
{
    $.ajax({
        url: '/tickets/ajax/request.php?full',
        type: 'get',
        dataType: 'json',
        success: get_requests_done});
}

function get_window_done(data)
{
    if(data.window != undefined)
    {
        var my_window = data.window;
        var now = new Date(Date.now());
        var start = new Date(my_window.request_start_date+" GMT-0600");
        var end = new Date(my_window.request_stop_date+" GMT-0600");
        var mail_start = new Date(my_window.mail_start_date+" GMT-0600");
        end.setHours(23);
        end.setMinutes(59);
        if(now < start || now > end)
        {
            var alert_div = $('<div/>', {class: 'alert alert-info', role: 'alert'});
            $('<strong/>').html('Notice: ').appendTo(alert_div);
            alert_div.append('The request window is currently closed.');
            if(my_window.test_mode == '1')
            {
                alert_div.append(' But test mode is enabled. Any requests created will be deleted before ticketing starts!');
                $('#request_set').prepend(alert_div);
                test_mode = true;
            }
            else
            {
                $('#request_set').replaceWith(alert_div);
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
            var alert_div = $('<div/>', {class: 'alert alert-warning', role: 'alert'});
            $('<strong/>').html('Notice: ').appendTo(alert_div);
            if(days == 1)
            {
                alert_div.append('The mail in window is currently open! You have '+days+' day left to mail your request!');
            }
            else if(days == 0)
            {
                alert_div.append('The mail in window is currently open! Today is the last day to mail your request!');
            }
            else
            {
                alert_div.append('The mail in window is currently open! You have '+days+' days left to mail your request!');
            }
            $('#request_set').prepend(alert_div);
        }
    }
}

function init_window()
{
    $.ajax({
        url: '/tickets/ajax/window.php',
        type: 'get',
        dataType: 'json',
        success: get_window_done});
}

function init_index()
{
    init_window();
    init_request();
    init_table();
}

$(init_index);
