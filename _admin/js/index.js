function populate_request_count(data)
{
    if(data.count != undefined)
    {
        $('#request_count').html(data.count);
    }
}

function populate_ticket_count(data)
{
    var ctx = document.getElementById("tickets").getContext("2d");
    if(data.sold == undefined || data.unsold == undefined)
    {
        ctx.font="12px Georgia";
        ctx.fillText("Error obtaining ticket data", 10, 50);
        return;
    }
    var data = [
        {
            value: data.unsold,
            color: "#F7464A",
            highligh: "#FF5A5E",
            label: "Unsold"
        },
        {
            value: data.sold,
            color: "#46BFBD",
            highligh: "#5AD3D1",
            label: "Sold"
        }
    ];
    Chart.defaults.global.animation = false;
    Chart.defaults.global.responsive = false;
    var chart = new Chart(ctx).Doughnut(data);
}

function init_index()
{
    $.ajax({
            url: '/tickets/ajax/request.php?count',
            type: 'get',
            dataType: 'json',
            success: populate_request_count});
    $.ajax({
            url: '/tickets/ajax/tickets.php?sold',
            type: 'get',
            dataType: 'json',
            success: populate_ticket_count});
    if(window.screen.availWidth < 400)
    {
        $("#tickets").css('width', '150');
        $("#tickets").css('height', '150');
        $("#requests").css('width', '150');
        $("#requests").css('height', '150');
    }
}

$(init_index);
