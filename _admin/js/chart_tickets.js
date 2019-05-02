var ticketSystem = new TicketSystem('../api/v1');

var chart = null;
var chartData = {
  type: 'doughnut',
  data: {
    datasets: [{
      data: [],
      backgroundColor: ["#d53e4f", "#f46d43", "#fdae61", "#66c2a5"]
    }],
    labels: []
  }
};

function gotTicketType(jqXHR){
  if(jqXHR.status !== 200) {
    alert('Unable to get ticket type!');
    return;
  }
  if(chart == null) {
    var ctx = $("#ticket_type_chart").get(0).getContext("2d");
    chart = new Chart(ctx, chartData);
  }
  chartData.data.labels.push(this.label);
  chartData.data.datasets[0].data.push(jqXHR.responseJSON['@odata.count']);
  console.log(chartData);
  //chart.update(chartData);
}

function gotTicketTypes(jqXHR){
  if(jqXHR.status !== 200) {
    alert('Unable to get ticket types!');
    return;
  }
  for(var i = 0; i < jqXHR.responseJSON.length; i++)
  {
    var obj = {label: jqXHR.responseJSON[i].description, type: jqXHR.responseJSON[i].typeCode};
    $.ajax({
      url: '../api/v1/tickets?$filter=year%20eq%20current%20and%20type%20eq%20%27'+jqXHR.responseJSON[i].typeCode+'%27&$count=true&$select=@odata.count',
      type: 'get',
      context: obj,
      complete: gotTicketType
    });
  }
}

function gotTickets(jqXHR) {
  if(jqXHR.status !== 200) {
    return;
  }
  var tickets = jqXHR.responseJSON;
  var total = $('#ticketsSold tbody tr:nth-child(6) td:nth-child('+this.col+')');
  total.append(tickets.length);
  var orig = 0;
  var crit = 0;
  var secondary = 0;
  var discretionary = 0;
  var other = 0;
  for(var i = 0; i < tickets.length; i++) {
    var ticket = tickets[i];
    if(ticket.discretionary === '1') {
      discretionary++;
    }
    else if(ticket.pool_id === '-1') {
      orig++;
    }
    else if(ticket.pool_id === '1') {
      secondary++;
    }
    else if(ticket.pool_id === '3') {
      crit++;
    }
    else {
      other++;
    }
  }
  var cell = $('#ticketsSold tbody tr:nth-child(1) td:nth-child('+this.col+')');
  cell.append(orig);
  cell = $('#ticketsSold tbody tr:nth-child(2) td:nth-child('+this.col+')');
  cell.append(crit);
  cell = $('#ticketsSold tbody tr:nth-child(3) td:nth-child('+this.col+')');
  cell.append(secondary);
  cell = $('#ticketsSold tbody tr:nth-child(4) td:nth-child('+this.col+')');
  cell.append(discretionary);
  cell = $('#ticketsSold tbody tr:nth-child(5) td:nth-child('+this.col+')');
  cell.append(other);
}

function gotAllYears(years, err) {
  years.sort();
  var thead = $('#ticketsSold thead tr');
  var rows = $('#ticketsSold tbody tr');
  thead.append('<th></th>');
  for(var i = 0; i < years.length; i++) {
    if(years[i] === 0) {
      continue;
    }
    thead.append('<th>'+years[i]+'</th>');
    for(var j = 0; j < rows.length; j++) {
      rows[j].innerHTML += '<td></td>';
    }
    var obj = { year: years[i], col: i+1};
    $.ajax({
      url: '../api/v1/tickets?$filter=year eq '+years[i]+' and sold eq 1&$select=pool_id,discretionary',
      type: 'GET',
      context: obj,
      complete: gotTickets
    });
  }
}

function initPage(){
  $.ajax({
    url: '../api/v1/tickets/types',
    type: 'get',
    complete: gotTicketTypes
  });
  ticketSystem.getAllYears(gotAllYears);
}

$(initPage);
