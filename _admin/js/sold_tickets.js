function short_hash(data, type, row, meta)
{
    return data.substring(0,7);
}

function init_page()
{
    $('#tickets').dataTable({
        "ajax": '/tickets/ajax/tickets.php?all=1&meta=sold',
        columns: [
            {'data': 'hash', 'render':short_hash},
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'}
        ]
    });
}

$(init_page)
