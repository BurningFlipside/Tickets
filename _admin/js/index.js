function gotRequestCount(jqXHR)
{
    if(jqXHR.status === 200)
    {
        $('#requestCount').html(jqXHR.responseJSON['@odata.count']);
    }
}

function init_index()
{
    $.ajax({
        url: '../api/v1/requests?$filter=year%20eq%202016&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: gotRequestCount});
}

$(init_index);
