function tickets_done(data)
{
    var label_array = [];
    var data_array = [];
    for(i = 0; data[i] !== undefined; i++)
    {
        label_array.push(data[i].description);
        data_array.push(data[i].count);
    }
    var ctx = $("#request_type_chart").get(0).getContext("2d");
    var chart_data = {
        labels: label_array,
        datasets: [
            {
                fillColor: "rgba(151,187,205,0.5)",
                strokeColor: "rgba(151,187,205,0.8)",
                highlightFill: "rgba(151,187,205,0.75)",
                highlightStroke: "rgba(151,187,205,1)",
                data: data_array
            }
        ]
    };
    new Chart(ctx).Bar(chart_data);
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
    var normal_count = 0;
    var protected_count = 0;
    var critvol_count = 0;
    for(i = 0; i < data.length; i++)
    {
        if(data[i].crit_vol === '0' && data[i].protected === '0')
        {
            normal_count = (data[i].count)*1;
        }
        else if(data[i].crit_vol === '1')
        {
            critvol_count+= (data[i].count)*1;
        }
        else if(data[i].protected === '1')
        {
            protected_count+= (data[i].count)*1;
        }
    }
    var crit_data = [
        {
            value: normal_count*1,
            color: "#F7464A",
            highlight: "#FF5A5E",
            label: "Normal Requests"
        },
        {
            value: protected_count*1,
            color: "#46BFBD",
            highlight: "#5AD3D1",
            label: "Protected Requests"
        },
        {
            value: critvol_count*1,
            color: "#FDB45C",
            highlight: "#FFC870",
            label: "Critical Volunteer Requests"
        }
    ];
    console.log(crit_data);

    var ctx = $("#crits_chart").get(0).getContext("2d");
    new Chart(ctx).Doughnut(crit_data);
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
