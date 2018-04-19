var ticketSystem = new TicketSystem('api/v1');

var out_of_window = false;
var test_mode = false;
var ticket_year = false;
var basic_button_options = {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', 'data-html': true};

function tableDrawComplete()
{
    $("#ticket_set").show();
    if($("#ticketList").DataTable().data().length !== 0)
    {
        //Table contains nothing, just return
        return;
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

function findTicketInTableByHash(table, hash)
{
    var json = table.DataTable().ajax.json();
    var i;
    for(i = 0; i < json.data.length; i++)
    {
        if(json.data[i].hash === hash)
        {
            return json.data[i];
        }
    }
    return null;
}

function get_ticket_data_by_hash(hash)
{
    var ticket = findTicketInTableByHash($("#ticketList"), hash);
    if(ticket === null)
    {
        ticket = findTicketInTableByHash($("#discretionary"), hash);
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
        location.reload();
    }
}

function save_ticket()
{
    $.ajax({
        url: 'api/v1/tickets/'+$('#show_short_code').data('hash'),
        type: 'patch',
        data: '{"firstName":"'+$('#edit_first_name').val()+'","lastName":"'+$('#edit_last_name').val()+'"}',
        contentType: 'application/json',
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
    window.location.assign('transfer.php?id='+ticket.hash);
}

function short_hash(data, type, row, meta)
{
    return '<a href="#" onclick="show_long_id(\''+data+'\')">'+data.substring(0,8)+'</a>';
}

function getOuterHTML(button)
{
    if(button.prop('outerHTML') === undefined)
    {
        return new XMLSerializer().serializeToString(button[0]);
    }
    return button.prop('outerHTML');
}

function makeGlyphButton(options, glyphClass, onClick)
{
    options.type = 'button';
    var button = $('<button/>', options);
    var glyph = $('<span/>', {'class': glyphClass});
    button.on('click', onClick);
    glyph.appendTo(button);
    return button;
}

function makeGlyphLink(options, glyphClass, ref)
{
    var link = $('<a/>', options);
    var glyph = $('<span/>', {'class': glyphClass});
    if(ref !== undefined) {
        link.attr('href', ref);
    }
    glyph.appendTo(link);
    return getOuterHTML(link);
}

function makeTextLink(options, linkText)
{
    var link = $('<a/>', options);
    link.append(linkText);
    return getOuterHTML(link);
}

function createButtonOptions(title, onClick, forData)
{
    var ret = JSON.parse(JSON.stringify(basic_button_options));
    ret.title   = title;
    if(forData !== undefined) {
        ret['for']  = forData;
    }
    if(onClick !== undefined) {
        ret.onclick = onClick;
    }
    return ret;
}

function createLinkOptions(title, forData, href, target)
{
    var ret = basic_button_options;
    ret.title   = title;
    ret['for']  = forData;
    ret.href    = href;
    if(target !== undefined)
    {
        ret.target = target;
    }
    return ret;
}

function getViewButton(data)
{
    var view_options = createButtonOptions('View Ticket Code', 'view_ticket(this)', data);
    return makeGlyphButton(view_options, 'fa fa-search');
}

function getEditButton(data)
{
    var edit_options = createButtonOptions('Edit Ticket<br/>Use this option to keep the ticket<br/>on your account but<br/>change the legal name.', 'edit_ticket(this)', data);
    return makeGlyphButton(edit_options, 'fa fa-pencil');
}

function getPDFButton(data)
{
    var pdf_options = createLinkOptions('Download PDF', data, 'api/v1/tickets/'+data+'/pdf', '_blank');
    return makeGlyphLink(pdf_options, 'fa fa-download');
}

function getTransferButton(data)
{
    var transfer_options = createButtonOptions('Transfer Ticket<br/>Use this option to send<br/>the ticket to someone else', 'transfer_ticket(this)', data);
    return makeGlyphButton(transfer_options, 'fa fa-send');
}

function make_actions(data, type, row, meta)
{
    var res = '';
    if($(window).width() < 768)
    {
        res += getOuterHTML(getViewButton(data));
    }
    res += getOuterHTML(getEditButton(data));
    res += getPDFButton(data);
    res += getOuterHTML(getTransferButton(data));
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

function add_buttons_to_row(row, request)
{
    var cell = $('<td/>', {style: 'white-space: nowrap;'});
    var edit_options = createButtonOptions('Edit Request');
    var mail_options = createButtonOptions('Resend Request Email');
    var pdf_options = createButtonOptions('Download Request PDF');
    var html = makeGlyphLink(edit_options, 'fa fa-pencil', 'request.php?request_id='+request.request_id+'&year='+request.year);
    cell.append(html);

    html = makeGlyphButton(mail_options, 'fa fa-envelope', request.sendEmail.bind(request));
    cell.append(html);

    html = makeGlyphLink(pdf_options, 'fa fa-download', request.getPdfUri());
    cell.append(html);
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
    window.location.assign('copy_request.php?id='+request.request_id+'&year='+request.year);
}

function add_old_request_to_table(tbody, request)
{
    var container = tbody.find('tr#old_requests');
    if(container.length === 0)
    {
        tbody.prepend('<tr id="old_requests" style="cursor: pointer;"><td colspan="5"><span class="fa fa-chevron-right"></span> Old Requests</td></tr>');
        container = tbody.find('tr#old_requests');
        container.on('click', toggle_hidden_requests);
    }
    var row = $('<tr class="old_request" style="display: none;">');
    row.append('<td/>');
    row.append('<td>'+request.year+'</td>');
    if(request.tickets === null) {
        row.append('<td>0</td>');
    }
    else {
        row.append('<td>'+request.tickets.length+'</td>');
    }
    row.append('<td>$'+request.total_due+'</td>');
    var cell = $('<td>');
    //var button = $('<button class="btn btn-link btn-sm" data-toggle="tooltip" data-placement="top" title="Copy Old Request"><span class="fa fa-clipboard"></span></button>');
    //button.data('request', request);
    //button.on('click', copy_request);
    //cell.append(button);
    row.append(cell);
    container.after(row);
}

function add_request_to_table(tbody, request, old_request_only)
{
    if(request.year !== ticket_year)
    {
        add_old_request_to_table(tbody, request);
        return;
    }
    old_request_only.value = false;
    var row = $('<tr/>');
    row.append('<td>'+request.request_id+'</td>');
    row.append('<td>'+request.year+'</td>');
    if(request.tickets === null)
    {
        request.tickets = [];
    }
    row.append('<td>'+request.tickets.length+'</td>');
    if(!out_of_window || test_mode)
    {
        row.append('<td>$'+request.total_due+'</td>');
        add_buttons_to_row(row, request);
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
        row.append('<td></td>');
    }
    row.appendTo(tbody);
    $('[data-original-title]').tooltip();
}

function processRequests(requests) {
    var tbody = $('#requestList tbody');
    var old_request_only = {};
    old_request_only.value = true;
    for(var i = 0; i < requests.length; i++)
    {
        add_request_to_table(tbody, requests[i], old_request_only);
    }
    if(out_of_window === false)
    {
        tbody.append('<tr><td></td><td colspan="4" style="text-align: center;"><a href="request.php"><span class="fa fa-plus-square"></span> Create a new request</a></td></tr>');
        $('#fallback').hide();
    }
    else
    {
        tbody.append('<tr><td colspan="5" style="text-align: center;"></td></tr>');
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

function getRequestsDone(requests, err) {
    if(err !== null) {
        alert('Error obtaining request!');
        return;
    }
    if(requests === undefined || requests.length === 0) {
        if(out_of_window) {
            $('#requestList').empty();
        }
        else {
            $('#request_set').empty();
            $('#request_set').append("You do not currently have a current or previous ticket request.<br/>");
            $('#request_set').append('<a href="/tickets/request.php">Create a Ticket Request</a>');
        }
    }
    else {
        processRequests(requests);
    }
    if($('#request_set').length > 0) {
        $('#request_set').show();
    }
}

function processOutOfWindow(now, start, end, my_window)
{
    if(now < start || now > end)
    {
        var message = 'The request window is currently closed. No new ticket requests are accepted at this time.';
        if(my_window.test_mode === '1')
        {
            message += ' But test mode is enabled. Any requests created will be deleted before ticketing starts!';
            test_mode = true;
        }
        else
        {
            $('[href="request.php"]').hide();
        }
        //add_notification($('#request_set'), message);
        out_of_window = true;
        if(!test_mode)
        {
            $('#requestList th:nth-child(4)').html("Request Status");
        }
    }
}

function processMailInWindow(now, mail_start, end)
{
    if(now > mail_start && now < end)
    {
        var days = Math.floor(end/(1000*60*60*24) - now/(1000*60*60*24));
        var message = 'The mail in window is currently open! ';
        if(days === 1)
        {
            message += 'You have 1 day left to mail your request!';
        }
        else if(days === 0)
        {
            message += 'Today is the last day to mail your request!';
        }
        else
        {
            message += 'You have '+days+' days left to mail your request!';
        }
        add_notification($('#request_set'), message, NOTIFICATION_WARNING);
    }
}

function getWindowDone(data, err) {
    if(err !== null) {
        if(err.jsonResp !== undefined && err.jsonResp.code !== undefined) {
            switch(err.jsonResp.code) {
                case 5:
                    //Not logged in... just silently fail the whole script right here
                    return;
                default:
                    alert(err.jsonResp.message);
                    break;
            }
        }
        return;
    }
    var now = new Date(Date.now());
    if(data.current < now) {
        now = data.current;
    }
    ticket_year = data.year;
    processOutOfWindow(now, data.request_start_date, data.request_stop_date, data);
    processMailInWindow(now, data.mail_start_date, data.request_stop_date);
    ticketSystem.getRequests(getRequestsDone);
    init_table();
}

function panel_heading_click(e)
{
    var panel = $(this);
    var panelBody = panel.parents('.panel').find('.panel-body');
    var glyph = panel.find('i');
    if(panel.hasClass('panel-collapsed'))
    {
        panelBody.slideDown();
        panel.removeClass('panel-collapsed');
        glpyh.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
    else
    {
        panelBody.slideUp();
        panel.addClass('panel-collapsed');
        glyph.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    }
}

function initIndex() {
    ticketSystem.getWindow(getWindowDone);
    $('.panel-heading span.clickable').on("click", panel_heading_click);
    if(getParameterByName('show_transfer_info') === '1') {
        var body = $('#content');
        add_notification(body, 'You have successfully sent an email with the ticket information. The ticket will be fully transfered when the receipient logs in and claims the ticket', NOTIFICATION_SUCCESS);
    }
}

$(initIndex);
