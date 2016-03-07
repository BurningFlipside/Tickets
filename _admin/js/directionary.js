var disc;

function drawTable(disc)
{
    var table = $('#discretionary tbody');
    for(var mail in disc)
    {
        var row = $('<tr>');
        var count = Object.keys(disc[mail]).length;
        if(disc[mail].Name !== undefined)
        {
            count--;
            row.append('<td rowspan="'+count+'">'+disc[mail].Name+'</td>');
            delete disc[mail].Name;
        }
        else
        {
            row.append('<td rowspan="'+count+'">'+mail+'</td>');
        }
        for(var type in disc[mail])
        {
            row.append('<td>'+type+'</td><td>'+disc[mail][type]+'</td>');
            table.append(row);
            row = $('<tr>');
        }
    }
}

function gotUsers()
{
    console.log(arguments);
    disc[arguments[0][0].mail]['Name'] = arguments[0][0].displayName;
    drawTable(disc);
}

function gotDiscretionaryTickets(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to obtain tickets!');
        return;
    }
    var data = jqXHR.responseJSON;
    disc = {};
    var calls = [];
    for(var i = 0; i < data.length; i++)
    {
        if(disc[data[i].email] === undefined)
        {
            disc[data[i].email] = {};
            calls.push(
                $.ajax({
                url: 'https://profiles.burningflipside.com/api/v1/users?$filter=mail eq '+data[i].email,
                type: 'get',
                dataType: 'json',
                xhrFields: {withCredentials: true},
            }));
        }
        if(disc[data[i].email][data[i].type] === undefined)
        {
            disc[data[i].email][data[i].type] = 0;
        }
        disc[data[i].email][data[i].type]++;
    }
    $.when.apply($, calls).done(gotUsers);
}

function gotTicketYear(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to obtain ticket year!');
        return;
    }
    $.ajax({
        url: '../api/v1/tickets?$filter=discretionary eq 1 and sold eq 0 and year eq '+jqXHR.responseJSON,
        type: 'get',
        dataType: 'json',
        complete: gotDiscretionaryTickets});
}

function initPage()
{
    $.ajax({
        url: '../api/v1/globals/vars/year',
        type: 'get',
        dataType: 'json',
        complete: gotTicketYear});
}

$(initPage);
