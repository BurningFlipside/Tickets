var ticketSystem = new TicketSystem('../api/v1');

function change_year(control)
{
    var table = $('#tickets').DataTable();
    table.ajax.url(ticketSystem.getRequestedTicketsDataTableUri('year eq '+$(control).val(), 'request_id,first,last,type')).load();
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
    $('#tickets').dataTable({
        columns: [
            {'data': 'request_id'},
            {'data': 'first'},
            {'data': 'last'},
            {'data': 'type'}
        ]
    });
}

$(init_page);
