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

function gotUnusedForYear(count, err) {
    var total = $('#ticketsUsed tbody tr:nth-child(2) td:nth-child('+this.col+')');
    total.append(count);
    if(this.unused != 0) {
      total = $('#ticketsUsed tbody tr:nth-child(3) td:nth-child('+this.col+')');
      total.append(((count/(this.unused*1.0))*100).toFixed(2)+'%');
    }
}

function gotUsedForYear(count, err) {
    var total = $('#ticketsUsed tbody tr:nth-child(1) td:nth-child('+this.col+')');
    total.append(count);
    this.unused = count;
    ticketSystem.getUnusedTicketCount(gotUnusedForYear.bind(this), this.year);
}

function gotAllYears(years, err) {
    years.sort();
    var thead = $('#ticketsUsed thead tr');
    var rows = $('#ticketsUsed tbody tr');
    thead.append('<th></th>');
    for(var i = 0; i < years.length; i++) {
      if(years[i] === 0 || years[i] === '0') {
        continue;
      }
      thead.append('<th>'+years[i]+'</th>');
      for(var j = 0; j < rows.length; j++) {
        rows[j].innerHTML += '<td></td>';
      }
      var obj = { year: years[i], col: i+1};
      ticketSystem.getUsedTicketCount(gotUsedForYear.bind(obj), years[i]);
    }
}

function initPage() {
    ticketSystem.getUsedTicketCount(gotUsedTicketCount);
    ticketSystem.getAllYears(gotAllYears);
}

$(initPage);
