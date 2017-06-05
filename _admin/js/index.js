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

function gotSoldTicketCount(jqXHR)
{
    if(jqXHR.status === 200)
    {
        $('#soldTicketCount').html(jqXHR.responseJSON['@odata.count']);
    }
}

function gotUnsoldTicketCount(jqXHR)
{
    if(jqXHR.status === 200)
    {
        $('#unsoldCount').html(jqXHR.responseJSON['@odata.count']);
    }
}

function gotUsedTicketCount(jqXHR)
{
    if(jqXHR.status === 200)
    {
        $('#usedCount').html(jqXHR.responseJSON['@odata.count']);
    }
}

function init_index()
{
    $.ajax({
        url: '../api/v1/requests?$filter=year eq current&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: gotRequestCount});
    $.ajax({
        url: '../api/v1/requests_w_tickets?$filter=year eq current&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: gotRequestedTicketCount});
    $.ajax({
        url: '../api/v1/tickets?$filter=year eq current and sold eq 1&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: gotSoldTicketCount});
    $.ajax({
        url: '../api/v1/tickets?$filter=year eq current and sold eq 0&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: gotUnsoldTicketCount});
    $.ajax({
        url: '../api/v1/tickets?$filter=year eq current and used eq 1&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: gotUsedTicketCount});
}

$(init_index);
