function sell_ticket(control)
{
    var jq = $(control);
    var id = jq.attr('for');
    var ticket = get_ticket_data_by_hash(id);
    if(ticket == null)
    {
        alert('Cannot find ticket');
        return;
    }
    window.location = '_admin/pos.php?id='+ticket.hash;
}

function make_d_action(data, type, row, meta)
{
    var res = '';
    var view_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'View Ticket Code', for: data, onclick: 'view_ticket(this)'};
    var pdf_options =  {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Download PDF', for: data, onclick: 'download_ticket(this)'};
    var sell_options = {class: 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', title: 'Sell Ticket<br/>Use this option to sell<br/>the ticket to someone else', 'data-html': true, for: data, onclick: 'sell_ticket(this)'};
    if(browser_supports_font_face())
    {
        if($(window).width() < 768)
        {
            view_options.type = 'button';
            var button = $('<button/>', view_options);
            var glyph = $('<span/>', {class: 'glyphicon glyphicon-search'});
            glyph.appendTo(button);
            res += button.prop('outerHTML');
        }
        pdf_options.type = 'button';
        button = $('<button/>', pdf_options);
        glyph = $('<span/>', {class: 'glyphicon glyphicon-cloud-download'});
        glyph.appendTo(button);
        res += button.prop('outerHTML');

        var rand = Math.floor(Math.random() * 3);

        sell_options.type = 'button';
        button = $('<button/>', sell_options);
        switch(rand)
        {
            case 0:
                glyph = $('<span/>', {class: 'glyphicon glyphicon-usd'});
                break;
            case 1:
                glyph = $('<span/>', {class: 'glyphicon glyphicon-euro'});
                break;
            case 2:
                glyph = $('<span/>', {class: 'glyphicon glyphicon-yen'});
                break;
        }
        glyph.appendTo(button);
        res += button.prop('outerHTML');
    }
    else
    {
        if($(window).width() < 768)
        {
            var link = $('<a/>', view_options);
            link.append("View");
            res += link.prop('outerHTML');
            res += '|';
        }
        link = $('<a/>', pdf_options);
        link.append("Download");
        res += link.prop('outerHTML');
        res += '|';

        link = $('<a/>', transfer_options);
        link.append("Sell");
        res += link.prop('outerHTML');
    }
    return res;
}

function init_d_table()
{
    $('#discretionary').dataTable({
        "ajax": '/tickets/api/v1/ticket/discretionary?fmt=data-table',
        columns: [
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'},
            {'data': 'hash', 'render': short_hash},
            {'data': 'hash', 'render': make_d_action, 'class': 'action-buttons', 'orderable': false}
        ],
        paging: false,
        info: false,
        searching: false
    });

    $("#ticketList").on('draw.dt', tableDrawComplete);
}

function init_discretionary()
{
    init_d_table();
}

$(init_discretionary);
