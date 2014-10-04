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

function init_page()
{
    $.ajax({
        url: '/tickets/ajax/tickets.php',
        data: 'requested_type=all',
        type: 'get',
        dataType: 'json',
        success: tickets_done});
}

$(init_page);
