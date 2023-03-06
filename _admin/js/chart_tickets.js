/*global $, Chart, TicketSystem*/
var ticketSystem = new TicketSystem('../api/v1');

var chart = null;
var chartData = {
  type: 'doughnut',
  data: {
    datasets: [{
      data: [],
      backgroundColor: ['#d53e4f', '#f46d43', '#fdae61', '#66c2a5']
    }],
    labels: []
  }
};

var chart2 = null;
var chart2Data = {
  type: 'bar',
  data: {
    labels: [],
    datasets: [
      {
        label: 'Original Sale',
        data: [],
        backgroundColor: '#d53e4f'
      },
      {
        label: 'Critical Volunteer',
        data: [],
        backgroundColor: '#f46d43'
      },
      {
        label: 'Secondary Sale',
        data: [],
        backgroundColor: '#fdae61'
      },
      {
        label: 'Discretionary',
        data: [],
        backgroundColor: '#66c2a5'
      },
      {
        label: 'Other',
        data: []
      }]
  },
  options: {
    scales: {
      xAxes: [{
        stacked: true
      }],
      yAxes: [{
        stacked: true
      }]
    },
    tooltips: {
      mode: 'index'
    }
  }
};

function gotTicketType(jqXHR){
  if(jqXHR.status !== 200) {
    alert('Unable to get ticket type!');
    return;
  }
  if(chart === null) {
    var ctx = $('#ticket_type_chart').get(0).getContext('2d');
    chart = new Chart(ctx, chartData);
  }
  chartData.data.labels.push(this.label);
  chartData.data.datasets[0].data.push(jqXHR.responseJSON['@odata.count']);
  //chart.update(chartData);
}

function gotTicketTypes(jqXHR){
  if(jqXHR.status !== 200) {
    alert('Unable to get ticket types!');
    return;
  }
  for(let type of jqXHR.responseJSON) {
    var obj = {label: type.description, type: type.typeCode};
    $.ajax({
      url: '../api/v1/tickets?$filter=year%20eq%20current%20and%20type%20eq%20%27'+type.typeCode+'%27&$count=true&$select=@odata.count',
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
  for(let ticket of tickets) {
    if(ticket.discretionary === 1) {
      discretionary++;
    } else if(ticket.pool_id === -1) {
      orig++;
    } else if(ticket.pool_id === 1) {
      secondary++;
    } else if(ticket.pool_id === 3) {
      crit++;
    } else {
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
  if(chart2 === null) {
    var ctx = $('#ticket_sold_chart').get(0).getContext('2d');
    chart2 = new Chart(ctx, chart2Data);
  }
  chart2Data.data.datasets[0].data[this.col-2] = orig;
  chart2Data.data.datasets[1].data[this.col-2] = crit;
  chart2Data.data.datasets[2].data[this.col-2] = secondary;
  chart2Data.data.datasets[3].data[this.col-2] = discretionary;
  chart2Data.data.datasets[4].data[this.col-2] = other;
  chart2.update(chart2Data);
}

function gotAllYears(years) {
  years.sort();
  var thead = $('#ticketsSold thead tr');
  var rows = $('#ticketsSold tbody tr');
  thead.append('<th></th>');
  let i = 0;
  for(let year of years) {
    if(year === 0) {
      continue;
    }
    thead.append('<th>'+year+'</th>');
    for(var j = 0; j < rows.length; j++) {
      rows[j].innerHTML += '<td></td>'; // eslint-disable-line security/detect-object-injection
    }
    chart2Data.data.labels.push(''+year);
    chart2Data.data.datasets[0].data.push(0);
    chart2Data.data.datasets[1].data.push(0);
    chart2Data.data.datasets[2].data.push(0);
    chart2Data.data.datasets[3].data.push(0);
    chart2Data.data.datasets[4].data.push(0);
    var obj = { year: year, col: i+2};
    $.ajax({
      url: '../api/v1/tickets?$filter=year eq '+year+' and sold eq 1&$select=pool_id,discretionary',
      type: 'GET',
      context: obj,
      complete: gotTickets
    });
    i++;
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
