var ticketSystem = new TicketSystem('../api/v1');

function ticketsDone(data, err) {
    if(err !== null) {
        console.log(err);
        alert('Unable to obtain requested ticket counts!');
        return;
    }
    var label_array = [];
    var data_array = [];
    var tbody = $('#requestTypesTable tbody');
    for(i = 0; data[i] !== undefined; i++)
    {
        var row = $('<tr>');
        row.append('<td>'+data[i].description+'</td>');
        row.append('<td>'+data[i].count+'</td>');
        row.append('<td>'+data[i].receivedCount+'</td>');
        tbody.append(row);
    }
}

function get_color_by_index(index)
{
    var colors = [
        "#d53e4f",
        "#f46d43",
        "#fdae61",
        "#fee08b",
        "#e6f598",
        "#abdda4",
        "#66c2a5",
        "#3288bd"
    ];
    return colors[index];
}

function get_highlight_by_index(index)
{
    var highlight = [
        "#d73027",
        "#f46d43",
        "#fdae61",
        "#fee08b",
        "#d9ef8b",
        "#a6d96a",
        "#66bd63",
        "#1a9850"
    ];
    return highlight[index];
}

function requests_done(data)
{
    var count_data = [];
    for(var propname in data.data.ticket_counts)
    {
        count_data.push({value: data.data.ticket_counts[propname], label: propname+' ticket(s) per request', color: get_color_by_index(propname), highligh: get_highlight_by_index(propname)});
    }

    var ctx = $("#ticket_count_chart").get(0).getContext("2d");
    new Chart(ctx).Doughnut(count_data);
}

function gotRequestCounts(data, err) {
    if(err !== null) {
        console.log(err);
        alert('Unable to obtain request counts!');
        return;
    }
    var totalCount = 0;
    for(var i = 0; i < data.length; i++) {
        if(data[i].all === true) {
            totalCount = data[i].count;
            data.splice(i, 1);
        }
    }
    $('#requestCount').html(totalCount);
    var receivedCount = 0;
    for(var i = 0; i < data.length; i++) {
        if(data[i].private_status === 3 || data[i].private_status === 6) {
            receivedCount += data[i].count;
        }
    }
    var receivedText = receivedCount+' ';
    if(totalCount !== 0) {
        var percent = ((receivedCount/totalCount)*100).toFixed(2);
        receivedText+='- '+percent+'%';
    }
    $('#receivedRequestCount').html(receivedText);
    var problemCount = 0;
    for(var i = 0; i < data.length; i++) {
        if(data[i].private_status === 2) {
            problemCount += data[i].count;
        }
    }
    var problemText = problemCount+' ';
    if(totalCount !== 0) {
        var percent = ((problemCount/totalCount)*100).toFixed(2);
        problemText+='- '+percent+'%';
    }
    $('#problemRequestCount').html(problemText);
    var rejectedCount = 0;
    for(var i = 0; i < data.length; i++) {
        if(data[i].private_status === 3 || data[i].private_status === 4) {
            rejectedCount += data[i].count;
        }
    }
    var rejectedText = rejectedCount+' ';
    if(totalCount !== 0) {
        var percent = ((rejectedCount/totalCount)*100).toFixed(2);
        rejectedText+='- '+percent+'%';
    }
    $('#rejectedRequestCount').html(rejectedText);
}

function init_page()
{
    ticketSystem.getTicketRequestCountsByStatus(gotRequestCounts);
    ticketSystem.getRequestedTicketCountsByType(ticketsDone);
}

$(init_page);
