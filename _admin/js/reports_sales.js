/*global $, TicketSystem*/
let ticketSystem = new TicketSystem('../api/v1');

function getTicketCost(ticket, costData) {
  if(ticket.request_id !== null && ticket.request_id !== '') {
    switch(ticket.type) {
      case 'A':
        return costData.adultPrice;
      case 'T':
        return costData.teenPrice;
      case 'K':
        return costData.kidPrice;
      case 'C':
        return costData.childPrice;
    }
  } else {
    switch(ticket.type) {
      case 'A':
        return costData.adultPriceSecondarySale;
      case 'T':
        return costData.teenPriceSecondaySale;
      case 'K':
        return costData.kidPriceSecondaySale;
      case 'C':
        return costData.childPriceSecondaySale;
    }
  }
}

function getNetFromGross(gross, ticket, costData) {
  if(ticket.request_id !== null && costData.mainSaleTaxExcempt === 1) {
    //Main sale is tax exempt
    return gross;
  } else if(ticket.request_id !== null) {
    //Only sales tax
    return gross - (gross * 0.0825);
  }
  let myNet = gross;
  if(costData.secondaryFlatFee !== null) {
    myNet -= costData.secondaryFlatFee;
  }
  if(costData.secondaryPercentFee !== null) {
    myNet -= (gross * costData.secondaryPercentFee);
  }
  //Sales tax
  myNet -= (myNet * 0.0825);
  return myNet;
}

function gotTickets(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain costs!');
    return;
  }
  let formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  });
  let data = jqXHR.responseJSON;
  let gross = 0.0;
  let net = 0.0;
  for(let ticket of data) {
    let myGross = getTicketCost(ticket, this.cost);
    gross += myGross;
    let myNet = getNetFromGross(myGross, ticket, this.cost);
    net += myNet;
  }
  let grossCell = $('#salesOverTimeTable tbody tr:nth-child(1) td:nth-child('+this.col+')');
  grossCell.append(formatter.format(gross));
  let netCell = $('#salesOverTimeTable tbody tr:nth-child(2) td:nth-child('+this.col+')');
  netCell.append(formatter.format(net));
}

function gotAllYears(years, err) {
  if(err && err.httpStatus === 401) {
    return;
  } else if(err && err.httpStatus !== 200) {
    alert('Failed to get ticket system years!');
  }
  years.sort();
  let thead = $('#salesOverTimeTable thead tr');
  let rows = $('#salesOverTimeTable tbody tr');
  thead.append('<th></th>');
  let i = 0;
  for(let year of years) {
    thead.append('<th>'+year+'</th>');
    for(let j = 0; j < rows.length; j++) {
      rows[j].innerHTML += '<td></td>'; // eslint-disable-line security/detect-object-injection
    }
    let obj = { year: year, col: i+2, cost: this[year]};
    $.ajax({url: '../api/v1/tickets?$filter=sold eq 1 and year eq '+year, complete: gotTickets.bind(obj)});
    i++;
  }
}

function gotCosts(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain costs!');
    return;
  }
  let data = jqXHR.responseJSON;
  let obj = {};
  for(let cost of data) {
    obj[cost.year] = cost;
  }
  ticketSystem.getAllYears(gotAllYears.bind(obj));
}

function createChartData(data) {
  let sorted = data.sort((a, b) => {
    return a.date - b.date;
  });
  // How long between the first and last date?
  let first = sorted[0].date;
  let last = sorted[sorted.length - 1].date;
  let diff = last - first;
  let days = diff / (1000 * 60 * 60 * 24);
  let labels = [];
  let requests = [];
  let pos = [];
  let values = [];
  let tmpMap = {};
  if(days < 3) {
    // Use hours for the labels
    for(let entry of sorted) {
      let rounded = entry.date.setMinutes(0, 0, 0);
      let key = new Intl.DateTimeFormat("en-US", {hour12: true, dateStyle: 'short', timeStyle: 'short'}).format(rounded);
      if(tmpMap[key] === undefined) {
        tmpMap[key] = {requests: 0, pos: 0, total: 0};
      }
      switch(entry.saleType) {
        case 'POS':
          tmpMap[key].pos += entry.amount;
          break;
        case 'Request':
          tmpMap[key].requests += entry.amount;
          break;
        default:
          console.error('Unknown sale type: '+entry.saleType);
      }
      tmpMap[key].total += entry.amount;
    }
    for(let key in tmpMap) {
      labels.push(key);
      requests.push(tmpMap[key].requests);
      pos.push(tmpMap[key].pos);
      values.push(tmpMap[key].total);
    }
  } else if(days < 30) {
    // Use days for the labels
  } else {
    // Use months for the labels
  }
  return {labels: labels, requests: requests, pos: pos, total: values};
}

function createCreditCardLineChart(data) {
  data.forEach(entry => {
    entry.date = new Date(entry.soldOn+' UTC');
  });
  let chartData = createChartData(data);
  const config = {
    plugins: {
      colors: {
        enabled: false
      }
    },
    type: 'line',
    data: {
      labels: chartData.labels,
      datasets: [{
        label: 'Credit Card Sales from Requests',
        data: chartData.requests,
        fill: false,
      },
      {
        label: 'Credit Card Sales CritVol/Discretionary',
        data: chartData.pos,
        fill: false,
      }],
    },
    options: {
      responsive: true,
      title: {
        display: true,
        text: 'Credit Card Sales Over Time',
      },
      scales: {
        y: {
          title: {
            display: true,
            text: '$'
          }
        },
      },
      plugins: {
        tooltip: {
          callbacks: {
            label: (context) => {
              let label = context.dataset.label || '';
              let formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
              });
              if(label) {
                label += ': ';
              }
              if(context.parsed.y !== null) {
                label += formatter.format(context.parsed.y);
              }
              return label;
            },
          }
        }
      }
    }
  };
  new Chart(document.getElementById('ccSales'), config);
}

function initPage() {
  $.ajax({url: '../api/v1/globals/costs', complete: gotCosts});
  fetch('../api/v1/completeSales').then((response) => {
    if(response.status !== 200) {
      console.log(response);
      alert('Failed to get sales!');
      return;
    }
    return response.json();
  }).then((data) => {
    createCreditCardLineChart(data);
  });
}

window.addEventListener('load', initPage);

