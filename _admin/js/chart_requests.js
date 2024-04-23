/*global $, TicketSystem*/
var ticketSystem = new TicketSystem('../api/v1');

function ticketsDone(data, err) {
  if(err !== null) {
    alert('Unable to obtain requested ticket counts!');
    return;
  }
  var tbody = $('#requestTypesTable tbody');
  for(let type of data) {
    let row = $('<tr>');
    row.append('<td>'+type.description+'</td>');
    row.append('<td>'+type.count+'</td>');
    row.append('<td>'+type.receivedCount+'</td>');
    tbody.append(row);
  }
}

function gotRequestCounts(data, err) {
  if(err !== null) {
    alert('Unable to obtain request counts!');
    return;
  }
  let i = 0;
  let totalCount = 0;
  let receivedCount = 0;
  let problemCount = 0;
  let rejectedCount = 0;
  for(let request of data) {
    if(request.all === true) {
      totalCount = request.count;
      data.splice(i, 1);
    }
    if(request.private_status === 1 || request.private_status === 6) {
      receivedCount += request.count;
    }
    if(request.private_status === 2) {
      problemCount += request.count;
    }
    if(request.private_status === 3 || request.private_status === 4) {
      rejectedCount += request.count;
    }
    i++;
  }
  $('#requestCount').html(totalCount);
  let receivedText = receivedCount+' ';
  if(totalCount !== 0) {
    let percent = ((receivedCount/totalCount)*100).toFixed(2);
    receivedText+='- '+percent+'%';
  }
  $('#receivedRequestCount').html(receivedText);
  let problemText = problemCount+' ';
  if(totalCount !== 0) {
    let percent = ((problemCount/totalCount)*100).toFixed(2);
    problemText+='- '+percent+'%';
  }
  $('#problemRequestCount').html(problemText);
  let rejectedText = rejectedCount+' ';
  if(totalCount !== 0) {
    let percent = ((rejectedCount/totalCount)*100).toFixed(2);
    rejectedText+='- '+percent+'%';
  }
  $('#rejectedRequestCount').html(rejectedText);
}

function gotRequests(requests) {
  if(requests === null) {
    return;
  }
  var total = $('#requestOverTimeTable tbody tr:nth-child(1) td:nth-child('+this.col+')');
  total.append(requests.length);
  var received = 0;
  var notReceived = 0;
  var problem = 0;
  var reject = 0;
  let requestedTickets = 0;
  for(let request of requests) {
    switch(request.private_status) {
      case 0:
        notReceived++;
        break;
      case 1:
      case 6:
        received++;
        break;
      case 2:
        problem++;
        break;
      case 3:
      case 4:
        reject++;
    }
    if(request.tickets !== null) {
      requestedTickets += request.tickets.length;
    }
  }
  var cell = $('#requestOverTimeTable tbody tr:nth-child(2) td:nth-child('+this.col+')');
  cell.append(received);
  cell = $('#requestOverTimeTable tbody tr:nth-child(3) td:nth-child('+this.col+')');
  cell.append(notReceived);
  cell = $('#requestOverTimeTable tbody tr:nth-child(4) td:nth-child('+this.col+')');
  cell.append(problem);
  cell = $('#requestOverTimeTable tbody tr:nth-child(5) td:nth-child('+this.col+')');
  cell.append(reject);
  cell = $('#requestOverTimeTable tbody tr:nth-child(6) td:nth-child('+this.col+')');
  if(requestedTickets === 0) {
    cell.append('N/A');
  } else {
    cell.append(requestedTickets);
  }
  cell = $('#requestOverTimeTable tbody tr:nth-child(7) td:nth-child('+this.col+')');
  cell.append((requestedTickets/requests.length).toFixed(2));
}

function gotAllYears(years, err) {
  if(err && err.httpStatus === 401) {
    return;
  } else if(err && err.httpStatus !== 200) {
    alert('Failed to get ticket system years!');
  }
  years.sort();
  var thead = $('#requestOverTimeTable thead tr');
  var rows = $('#requestOverTimeTable tbody tr');
  thead.append('<th></th>');
  let i = 0;
  for(let year of years) {
    thead.append('<th>'+year+'</th>');
    for(var j = 0; j < rows.length; j++) {
      rows[j].innerHTML += '<td></td>'; // eslint-disable-line security/detect-object-injection
    }
    var obj = { year: year, col: i+2};
    var gotRequestsCall = gotRequests.bind(obj);
    ticketSystem.getRequests(gotRequestsCall, 'year eq '+year);
    i++;
  }
}

function gotDonationAmount(donations) {
  if(donations === null) {
    donations = 0;
  }
  let formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  });
  let val = formatter.format(donations);
  $('#receivedDonations').html(val);
}

function gotMoney(jqXHR) {
  if(jqXHR.status !== 200) {
    return;
  }
  let formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  });
  let val = formatter.format(jqXHR.responseJSON);
  $('#receivedMoney').html(val);
}

function initPage() {
  ticketSystem.getTicketRequestCountsByStatus(gotRequestCounts);
  ticketSystem.getRequestedTicketCountsByType(ticketsDone);
  ticketSystem.getAllYears(gotAllYears);
  ticketSystem.getDonationsAmount(gotDonationAmount);
  $.ajax({url: '../api/v1/requests/moneyReceived', complete: gotMoney});
}

$(initPage);
