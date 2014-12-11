function short_hash(data, type, row, meta)
{
    return data.substring(0,7);
}

function table_searched()
{
    var dt_api = $('#tickets').DataTable();
    if(dt_api.rows({'search':'applied'})[0].length == 0)
    {
        alert("TODO: Search backend for ticket ID because not already on client");
    }
}

function init_page()
{
    $('#tickets').dataTable({
        "ajax": '/tickets/ajax/tickets.php?all=1',
        columns: [
            {'data': 'hash', 'render':short_hash},
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'}
        ]
    });

    $('#tickets').on('search.dt', table_searched);
}

$(init_page)
