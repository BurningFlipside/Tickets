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
    var crit_data = [
        {
            value: (data.data.total_request_count - (data.data.protected_request_count + data.data.crit_request_count))*1,
            color: "#F7464A",
            highlight: "#FF5A5E",
            label: "Normal Requests"
        },
        {
            value: data.data.protected_request_count*1,
            color: "#46BFBD",
            highlight: "#5AD3D1",
            label: "Protected Requests"
        },
        {
            value: data.data.crit_request_count*1,
            color: "#FDB45C",
            highlight: "#FFC870",
            label: "Critical Volunteer Requests"
        }
    ];

    var ctx = $("#crits_chart").get(0).getContext("2d");
    new Chart(ctx).Doughnut(crit_data);

    var count_data = [];
    for(var propname in data.data.ticket_counts)
    {
        count_data.push({value: data.data.ticket_counts[propname], label: propname+' ticket(s) per request', color: get_color_by_index(propname), highligh: get_highlight_by_index(propname)});
    }

    var ctx = $("#ticket_count_chart").get(0).getContext("2d");
    new Chart(ctx).Doughnut(count_data);
}

function init_page()
{
    $.ajax({
        url: '/tickets/ajax/tickets.php',
        data: 'requested_type=all',
        type: 'get',
        dataType: 'json',
        success: tickets_done});
    $.ajax({
        url: '/tickets/ajax/request.php',
        data: 'meta=all',
        type: 'get',
        dataType: 'json',
        success: requests_done});
}

$(init_page);
