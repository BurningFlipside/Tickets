/*global $, Chart, TicketSystem*/
var ticketSystem = new TicketSystem('../api/v1');
var usedCount = 0;

function gotUnusedTicketCount(count) {
  var data = {
    datasets: [{
      data: [count, usedCount],
      backgroundColor: ['#d53e4f', '#66c2a5']
    }],
    labels: ['Unused', 'Used'],
  };
  var ctx = $('#used_chart').get(0).getContext('2d');
  new Chart(ctx, {type: 'doughnut', data: data});
}

function gotUsedTicketCount(count) {
  usedCount = count;
  ticketSystem.getUnusedTicketCount(gotUnusedTicketCount);
}

function gotUnusedForYear(count) {
  var total = $('#ticketsUsed tbody tr:nth-child(2) td:nth-child('+this.col+')');
  total.append(count);
  if(this.unused !== 0) {
    total = $('#ticketsUsed tbody tr:nth-child(3) td:nth-child('+this.col+')');
    total.append(((count/(this.unused*1.0))*100).toFixed(2)+'%');
  }
}

function gotUsedForYear(count) {
  var total = $('#ticketsUsed tbody tr:nth-child(1) td:nth-child('+this.col+')');
  total.append(count);
  this.unused = count;
  ticketSystem.getUnusedTicketCount(gotUnusedForYear.bind(this), this.year);
}

function gotAllYears(years) {
  years.sort();
  var thead = $('#ticketsUsed thead tr');
  var rows = $('#ticketsUsed tbody tr');
  thead.append('<th></th>');
  let i = 0;
  for(let year of years) {
    if(year === 0 || year === '0') {
      continue;
    }
    thead.append('<th>'+year+'</th>');
    for(var j = 0; j < rows.length; j++) {
      rows[j].innerHTML += '<td></td>'; // eslint-disable-line security/detect-object-injection
    }
    var obj = { year: year, col: i+2};
    ticketSystem.getUsedTicketCount(gotUsedForYear.bind(obj), year);
    i++;
  }
}

function initPage() {
  ticketSystem.getUsedTicketCount(gotUsedTicketCount);
  ticketSystem.getAllYears(gotAllYears);
}

$(initPage);
