function tableDrawComplete()
{
    if($("#ticketList").DataTable().data().length != 0)
    {
        $("#ticket_set").show();
    }
}

function init_table()
{
    $('#ticketList').dataTable({
        "ajax": '/tickets/ajax/tickets.php'
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
            total += (request.donations[i].amount)*1;
        }
    }
    cell.html('$'+total);
    cell.appendTo(row);
    add_buttons_to_row(row, request.request_id, request.year);
    row.appendTo(tbody);
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
        var now = Date.now();
        var start = new Date(my_window.request_start_date);
        var end = new Date(my_window.request_stop_date);
        var mail_start = new Date(my_window.mail_start_date);
        if(now < start || now > end)
        {
            var alert_div = $('<div/>', {class: 'alert alert-info', role: 'alert'});
            $('<strong/>').html('Notice: ').appendTo(alert_div);
            alert_div.append('The request window is currently closed.');
            if(my_window.test_mode == '1')
            {
                alert_div.append(' But test mode is enabled. Any requests created will be deleted before ticketing starts!');
                $('#request_set').prepend(alert_div);
            }
            else
            {
                $('#request_set').replaceWith(alert_div);
            }
        }
        if(now > mail_start && now < end)
        {
            var days = Math.floor(end/(1000*60*60*24) - now/(1000*60*60*24));
            var alert_div = $('<div/>', {class: 'alert alert-warning', role: 'alert'});
            $('<strong/>').html('Notice: ').appendTo(alert_div);
            alert_div.append('The mail in window is currently open! You have '+days+' days left to mail your request!');
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
