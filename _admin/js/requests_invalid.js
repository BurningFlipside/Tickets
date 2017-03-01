function init_table()
{
    $(this).dataTable({
        'ajax': '../api/v1/requests?fmt=data-table&filter=year eq current and private_status eq 3',
        'columns': [
            {'data': 'request_id'},
            {'data': 'private_status'},
            {'data': 'total_due'},
            {'data': 'total_received'},
            {'data': 'comments'},
            {'data': 'crit_vol'}
        ]
    });
}

function expand_table()
{
    $(this).DataTable().page.len(-1);
    $(this).DataTable().draw();
}

function before_print()
{
    $('table').each(expand_table);
}

function after_print()
{
    console.log('after');
}

function on_print_change(mql)
{
    if(mql.matches)
    {
        before_print();
    }
    else
    {
        after_print();
    }
}

function exportCSV()
{
    window.location = '../api/v1/requests?fmt=csv&filter=year eq current and private_status eq 3';
}

function init_page()
{
    $('table').each(init_table);
    if(window.matchMedia !== undefined)
    {
        //WebKit implementation
        var mediaQueryList = window.matchMedia('print');
        mediaQueryList.addListener(on_print_change);
    }
    //IE & Firefox implementation
    window.onbeforeprint = before_print;
    window.onafterprint = after_print;
}

$(init_page);
