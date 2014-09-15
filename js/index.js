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

function get_requests_done(data)
{
    if(data.error)
    {
        alert('Login failed: '+data.error);
        console.log(data.error);
    }
    else
    {
        if(data.request == undefined || data.request == null)
        {
            //TODO - Disable this link if reg window is closed
            $('#request_set').append("You do not currently have a ticket request.<br/>");
            $('#request_set').append('<a href="/tickets/request.php">Create a Ticket Request</a>');
        }
        else
        {
            console.log(data);
        }
    }
}

function init_request()
{
    $.ajax({
        url: '/tickets/ajax/request.php',
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
