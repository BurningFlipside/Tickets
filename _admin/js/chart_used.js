var ticketSystem = new TicketSystem('../api/v1');
var usedCount = 0;

function gotUnusedTicketCount(count, err) {
    var data = {
        datasets: [{
            data: [count, usedCount],
            backgroundColor: ["#d53e4f", "#66c2a5"]
        }],
        labels: ['Unused', 'Used'],
    };
    var ctx = $("#used_chart").get(0).getContext("2d");
    new Chart(ctx, {type: 'doughnut', data: data});
}

function gotUsedTicketCount(count, err) {
    usedCount = count;
    ticketSystem.getUnusedTicketCount(gotUnusedTicketCount);
}

function initPage() {
    ticketSystem.getUsedTicketCount(gotUsedTicketCount);
}

$(initPage);
