function short_hash(data, type, row, meta)
{
    return data.substring(0,8);
}

function init_page()
{
    $('#tickets').dataTable({
        "ajax": '../api/v1/tickets?filter=sold eq 1 and year eq 2016',
        columns: [
            {'data': 'hash', 'render':short_hash},
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'}
        ]
    });
}

$(init_page)
