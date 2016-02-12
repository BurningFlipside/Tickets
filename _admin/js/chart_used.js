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

function used_done(data)
{
    var crit_data = [
        {
            value: data.unused*1,
            color: "#F7464A",
            highlight: "#FF5A5E",
            label: "Unused"
        },
        {
            value: data.used*1,
            color: "#46BFBD",
            highlight: "#5AD3D1",
            label: "Used"
        }
    ];

    var ctx = $("#used_chart").get(0).getContext("2d");
    new Chart(ctx).Doughnut(crit_data);
}

function init_page()
{
    $.ajax({
        url: '../api/v1/tickets',
        data: '$filter=used eq 1',
        type: 'get',
        dataType: 'json',
        success: used_done});
}

$(init_page);
