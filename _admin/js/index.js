function gotRequestCount(jqXHR)
{
    if(jqXHR.status === 200)
    {
        $('#requestCount').html(jqXHR.responseJSON['@odata.count']);
    }
}

function gotRequestedTicketCount(jqXHR)
{
    if(jqXHR.status === 200)
    {
        $('#requestedTicketCount').html(jqXHR.responseJSON['@odata.count']);
    }
}

function init_index()
{
    $.ajax({
        url: '../api/v1/requests?$filter=year eq 2016&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: gotRequestCount});
    $.ajax({
        url: '../api/v1/requests_w_tickets?$filter=year eq 2016&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: gotRequestedTicketCount});
}

$(init_index);
