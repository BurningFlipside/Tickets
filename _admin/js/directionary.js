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
            row.append('<td>'+type+'</td><td>'+disc[mail][type]['unsold']+'</td><td>'+disc[mail][type]['sold']+'</td>');
            table.append(row);
            row = $('<tr>');
        }
    }
}

function gotUsers()
{
    console.log(arguments);
    for(var i = 0; i < arguments.length; i++)
    {
        if(arguments[i].length === 0 || arguments[i][0].length === 0) continue;
        var user = arguments[i][0][0];
        disc[user.mail]['Name'] = user.displayName;
    }
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
        var email = data[i].discretionaryOrig;
        if(disc[email] === undefined)
        {
            disc[email] = {};
            calls.push(
                $.ajax({
                url: window.profilesUrl+'/api/v1/users?$filter=mail eq '+email,
                type: 'get',
                dataType: 'json',
                xhrFields: {withCredentials: true},
            }));
        }
        if(disc[email][data[i].type] === undefined)
        {
            disc[email][data[i].type] = {};
            disc[email][data[i].type]['sold'] = 0;
            disc[email][data[i].type]['unsold'] = 0;
        }
        if(data[i].sold == 0)
        {
            disc[email][data[i].type]['unsold']++;
        }
        else
        {
            disc[email][data[i].type]['sold']++;
        }
    }
    $.when.apply($, calls).done(gotUsers);
}

function getCSV()
{
    window.location = '../api/v1/tickets?$format=csv&$filter=discretionary eq 1 and year eq current';
}

function gotGroups(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to obtain groups!');
        return;
    }
    var data = jqXHR.responseJSON;
    for(var i = 0; i < data.length; i++)
    {
        $('#group').append('<option value="'+data[i].cn+'">'+data[i].cn+'</option>');
    }
}

function initPage()
{
    $.ajax({
        url: '../api/v1/tickets?$filter=discretionary eq 1 and year eq current',
        type: 'get',
        dataType: 'json',
        complete: gotDiscretionaryTickets});
    $.ajax({
        url: window.profilesUrl+'/api/v1/groups?$select=cn',
        type: 'get',
        dataType: 'json',
        xhrFields: {withCredentials: true},
        complete: gotGroups});
}

$(initPage);
