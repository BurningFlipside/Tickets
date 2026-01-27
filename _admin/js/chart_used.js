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
  let promises = [];
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
    promises.push(fetch('../api/v1/tickets?$filter=used eq 1 and year eq '+year).then((response) => {
      if(!response.ok) {
        return;
      }
      return response.json();
    }));
  }
  Promise.allSettled(promises).then((results) => {
    constructTicketsPastGateGraph(results);
  });
}

const offsetMap = {
  2015: -4,
  2016: -4,
  2017: -9,
  2018: -4,
  2019: -5,
  2022: -6
};
const dateFormatter = new Intl.DateTimeFormat('en-US', {weekday: 'long'})

function getKeyFromDate(date) {
  let hours = date.getHours();
  let offset = 'AM';
  if(hours >= 12) {
    hours -= 12;
    offset = 'PM';
  }
  if(hours === 0) {
    hours = 12;
  }
  return dateFormatter.format(date) + ' ' + hours + ' ' + offset;
}

function fixupData(data) {
  for(let ticket of data) {
    ticket.usedDT = new Date(ticket.used_dt+' UTC');
    if(isNaN(ticket.usedDT)) {
      continue;
    }
    if(offsetMap[ticket.year] != undefined) {
      ticket.usedDT.setHours(ticket.usedDT.getHours() + offsetMap[ticket.year]);
    }
    // Now round to the nearest hour
    ticket.usedDT.setMinutes(0, 0, 0);
  }
  data.sort((a, b) => a.usedDT - b.usedDT);
  return data;
}

function getDataSetForYear(data, earliest, latest) {
  if(data.length === 0) {
    return null;
  }
  // First let's get the data into a format we can use
  let ret = {
    labels: [],
    dataset: {
      label: data[0].year,
      data: [],
      fill: false
    },
  };
  let yearData = {};
  let start = new Date(earliest);
  let end = new Date(latest);
  end.setHours(end.getHours() + 2);
  for(let i = start; i <= end; i.setHours(i.getHours() + 1)) {
    let key = getKeyFromDate(i);
    yearData[key] = {count: 0, sortDT: i, key: key};
  }
  for(let ticket of data) {
    if(isNaN(ticket.usedDT)) {
      continue;
    }
    let key = getKeyFromDate(ticket.usedDT);
    if(yearData[key] === undefined) {
      continue
    }
    yearData[key].count++;
  }
  let vals = Object.values(yearData);
  vals.sort((a, b) => a.sortDT - b.sortDT);
  for(let entry of vals) {
    ret.labels.push(entry.key);
    ret.dataset.data.push(entry.count);
  }
  return ret;
}

function isSubset(mainArray, subsetArray) {
  return subsetArray.every(element => mainArray.includes(element));
}

function constructTicketsPastGateGraph(results) {
  // Let's just do the first one for right now...
  let labels = [];
  let datasets = [];
  let allYears = [];
  let earliest = null;
  let latest = null;
  for(let result of results) {
    if(result.status !== 'fulfilled') {
      console.error(result.reason);
      return;
    }
    if(result.value.length === 0) {
      continue;
    }
    let data = fixupData(result.value);
    allYears.push(data);
    // Normalize the data by year to get the range we need...
    if(earliest == null) {
      earliest = data[0].usedDT;
      latest = data[data.length - 1].usedDT;
      continue;
    }
    // Normalize the data by year to get the range we need...
    let tmpStart = new Date(data[0].usedDT);
    tmpStart.setFullYear(earliest.getFullYear(), earliest.getMonth(), earliest.getDate());
    if(tmpStart < earliest) {
      earliest = tmpStart;
    }
    let tmpEnd = new Date(data[data.length - 1].usedDT);
    tmpEnd.setFullYear(latest.getFullYear(), latest.getMonth(), latest.getDate());
    if(tmpEnd > latest) {
      latest = tmpEnd;
    }
  }
  for(let data of allYears) {
    let yearData = getDataSetForYear(data, earliest, latest);
    if(yearData === null) {
      continue;
    }
    if(labels.length === 0) {
      labels = yearData.labels;
    } else {
      // TODO merge these and push values as appropriate
      if(!isSubset(labels, yearData.labels)) {
        let diff = yearData.labels.filter(x => !labels.includes(x));
        console.error('Labels do not match!', diff);
      }
    }
    datasets.push(yearData.dataset);
  }
  const config = {
    plugins: {
      colors: {
        enabled: true
      }
    },
    type: 'line',
    data: {
      labels: labels,
      datasets: datasets,
    },
    options: {
      responsive: true,
      title: {
        display: true,
        text: 'Time of Entry',
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
  new Chart(document.getElementById('timePastGate'), config);
}

function initPage() {
  ticketSystem.getUsedTicketCount(gotUsedTicketCount);
  ticketSystem.getAllYears(gotAllYears);
}

$(initPage);
