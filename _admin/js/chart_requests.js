function tickets_done(data)
{
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

function crit_vols_done(data)
{
    var total = 0;
    var normal_count = 0;
    var protected_count = 0;
    var critvol_count = 0;
    var both_count = 0;
    for(i = 0; i < data.length; i++)
    {
        total+= (data[i].count)*1;
        if(data[i].crit_vol === '0' && data[i].protected === '0')
        {
            normal_count = (data[i].count)*1;
        }
        else if(data[i].crit_vol === '1' && data[i].protected === '0')
        {
            critvol_count = (data[i].count)*1;
        }
        else if(data[i].crit_vol === '0' && data[i].protected === '1')
        {
            protected_count = (data[i].count)*1;
        }
        else
        {
            both_count = (data[i].count)*1;
        }
    }
    var rows = $('#critVolTable tbody tr');
    var row1 = rows.first();
    var row2 = rows.last();
    row1.append('<td>'+normal_count+'</td><td>'+critvol_count+'</td><td>'+protected_count+'</td><td>'+both_count+'</td>');
    normal_count = ((normal_count/total)*100).toFixed(2);
    critvol_count = ((critvol_count/total)*100).toFixed(2);
    protected_count = ((protected_count/total)*100).toFixed(2);
    both_count = ((both_count/total)*100).toFixed(2);
    row2.append('<td>'+normal_count+'%</td><td>'+critvol_count+'%</td><td>'+protected_count+'%</td><td>'+both_count+'%</td>');
}

function gotRequestCounts(total, received, problem, rejected)
{
    var totalCount = 0;
    if(total[1] === 'success')
    {
        totalCount = total[0]['@odata.count'];
        $('#requestCount').html(totalCount);
    }
    if(received[1] === 'success')
    {
        var receivedCount = received[0]['@odata.count'];
        var receivedText = receivedCount+' ';
        if(totalCount !== 0)
        {
            var percent = ((receivedCount/totalCount)*100).toFixed(2);
            receivedText+='- '+percent+'%';
        }
        $('#receivedRequestCount').html(receivedText);
    }
    if(problem[1] === 'success')
    {
        var problemCount = problem[0]['@odata.count'];
        var problemText = problemCount+' ';
        if(totalCount !== 0)
        {
            var percent = ((problemCount/totalCount)*100).toFixed(2);
            problemText+='- '+percent+'%';
        }
        $('#problemRequestCount').html(problemText);
    }
    if(rejected[1] === 'success')
    {
        var rejectedCount = rejected[0]['@odata.count'];
        var rejectedText = rejectedCount+' ';
        if(totalCount !== 0)
        {
            var percent = ((rejectedCount/totalCount)*100).toFixed(2);
            rejectedText+='- '+percent+'%';
        }
        $('#rejectedRequestCount').html(rejectedText);
    }
}

function yearDone(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to obtain current ticket request year!');
        return;
    }
    var year = jqXHR.responseJSON;
    $.when(
    $.ajax({
        url: '../api/v1/requests?$filter=year eq '+year+'&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json'}),
    $.ajax({
        url: '../api/v1/requests?$filter=year eq '+year+' and private_status eq 1&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json'}),
    $.ajax({
        url: '../api/v1/requests?$filter=year eq '+year+' and private_status eq 2&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json'}),
    $.ajax({
        url: '../api/v1/requests?$filter=year eq '+year+' and private_status eq 3&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json'})
    ).then(gotRequestCounts);
}

function init_page()
{
    $.ajax({
        url: '../api/v1/globals/vars/year',
        type: 'get',
        dataType: 'json',
        complete: yearDone});
    $.ajax({
        url: '../api/v1/requests_w_tickets/types',
        type: 'get',
        dataType: 'json',
        success: tickets_done});
    $.ajax({
        url: '../api/v1/requests/crit_vols',
        type: 'get',
        dataType: 'json',
        success: crit_vols_done});
}

$(init_page);
