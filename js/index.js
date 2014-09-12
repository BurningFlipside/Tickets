function tableDrawComplete()
{
    if($("#ticketList").DataTable().data().length == 0)
    {
        $("#ticket_set").hide();
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

function init_index()
{
    init_request();
    init_table();
}

$(init_index);
