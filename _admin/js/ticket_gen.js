function generation_done(data)
{
    var str = 'Created '+data.passed+' tickets\n';
    if(data.failed > 0)
    {
        str += 'Failed to create '+data.failed+' tickets';
    }
    alert(str);
    location.reload();
}

function gen_tickets()
{
    var total_count = 0;
    var elements = $('#additional [type="number"]');
    var obj = $('#gen_form').serializeObject();
    obj.types = {};
    for(var i = 0; i < elements.length; i++)
    {
        total_count += 1*$(elements[i]).val();
        obj.types[elements[i].id] = 1*$(elements[i]).val();
    }
    if(total_count == 0)
    {
        alert("No additional tickets created!");
        return false;
    }
    $.ajax({
        url: '../api/v1/tickets/Actions/GenerateTickets',
        type: 'post',
        data: JSON.stringify(obj),
        dataType: 'json',
        processData: false,
        success: generation_done});
    return false
}

function gotTicketType(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        alert('Unable to get ticket count for type '+this+'!');
        return;
    }
    var field = $('#'+this+'Current');
    field.html(jqXHR.responseJSON['@odata.count']);
}

function gotTicketTypes(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        alert('Unable to get ticket types!');
        return;
    }
    var current = $('#current tbody');
    var additional = $('#additional tbody');
    for(var i = 0; i < jqXHR.responseJSON.length; i++)
    {
        current.append('<tr><td>'+jqXHR.responseJSON[i].description+'</td><td id="'+jqXHR.responseJSON[i].typeCode+'Current"></td></tr>');
        additional.append('<tr><td>'+jqXHR.responseJSON[i].description+'</td><td><input type="number" id="'+jqXHR.responseJSON[i].typeCode+'" value="0"/></td></tr>');
        $.ajax({
            url: '../api/v1/tickets?$filter=year%20eq%202016%20and%20type%20eq%20%27'+jqXHR.responseJSON[i].typeCode+'%27&$count=true&$select=@odata.count',
            type: 'get',
            context: jqXHR.responseJSON[i].typeCode,
            complete: gotTicketType
        });
    }
}

function initPage()
{
    $.ajax({
       url: '../api/v1/tickets/types',
       type: 'get',
       complete: gotTicketTypes
    });
}

$(initPage);
