function ticketsDone(data) {
  let table = document.getElementById('requestTypesTable');
  let tableBody = table.tBodies[0];
  let totalTotal = 0;
  let totalReceived = 0;
  for(let type of data) {
    let row = tableBody.insertRow();
    row.insertCell().innerHTML = type.description;
    row.insertCell().innerHTML = type.count;
    row.insertCell().innerHTML = type.receivedCount;
    totalTotal += type.count;
    totalReceived += type.receivedCount;
  }
  let footer = table.createTFoot();
  let row = footer.insertRow();
  row.insertCell().innerHTML = '<b>Total</b>';
  row.insertCell().innerHTML = '<b>'+totalTotal+'</b>';
  row.insertCell().innerHTML = '<b>'+totalReceived+'</b>';
}

function gotRequestCounts(data) {
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
  document.getElementById('requestCount').innerHTML = totalCount;
  let receivedText = receivedCount+' ';
  if(totalCount !== 0) {
    let percent = ((receivedCount/totalCount)*100).toFixed(2);
    receivedText+='- '+percent+'%';
  }
  document.getElementById('receivedRequestCount').innerHTML = receivedText;
  let problemText = problemCount+' ';
  if(totalCount !== 0) {
    let percent = ((problemCount/totalCount)*100).toFixed(2);
    problemText+='- '+percent+'%';
  }
  document.getElementById('problemRequestCount').innerHTML = problemText;
  let rejectedText = rejectedCount+' ';
  if(totalCount !== 0) {
    let percent = ((rejectedCount/totalCount)*100).toFixed(2);
    rejectedText+='- '+percent+'%';
  }
  document.getElementById('rejectedRequestCount').innerHTML = rejectedText;
  fetch('../api/v1/request?$filter=year eq current and paymentMethod eq "cc"&$count=true').then((response) => {
    if(response.httpStatus === 401) {
      // Not logged in, just silently return
      return;
    }
    if(!response.ok) {
      alert('Failed to get Ticket Request Counts!');
      return;
    }
    response.json().then((data) => {
      gotCCRequests(data, totalCount, receivedCount);
    });
  });
}

function gotCCRequests(data, totalCount, receivedCount) {
  let field = document.getElementById('ccRequestCount');
  let ccText = data['@odata.count'];
  if(totalCount !== 0) {
    let percent = ((data['@odata.count']/totalCount)*100).toFixed(2);
    ccText+='- '+percent+'%';
  }
  field.innerHTML = ccText;
  field = document.getElementById('receivedPlusCCRequestCount');
  let plusText = data['@odata.count']+receivedCount;
  if(totalCount !== 0) {
    let percent = ((plusText/totalCount)*100).toFixed(2);
    plusText+=' - '+percent+'%';
  }
  field.innerHTML = plusText;
}

function gotRequests(requests, year, col) {
  if(requests === null) {
    return;
  }
  let table = document.getElementById('requestOverTimeTable');
  // eslint-disable-next-line security/detect-object-injection
  table.rows[1].cells[col].innerHTML = requests.length;
  let received = 0;
  let notReceived = 0;
  let problem = 0;
  let reject = 0;
  let requestedTickets = 0;
  let donationAmount = 0;
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
    if(request.donations) {
      for(const [key, donation] of Object.entries(request.donations)) {
        if(donation.amount) {
          donationAmount += donation.amount*1;
	}
      }
    }
  }
  let donationSpan = document.getElementById('pledgedDonations');
  if(donationSpan) {
    donationYear = donationSpan.dataset.year;
    if(donationYear === undefined || donationYear*1 < year*1) {
      donationSpan.dataset.year = year;
      let formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      });
      let val = formatter.format(donationAmount);
      donationSpan.innerHTML = val;
    }
  }
  console.log(year, donationAmount);
  // eslint-disable-next-line security/detect-object-injection
  table.rows[2].cells[col].innerHTML = received;
  // eslint-disable-next-line security/detect-object-injection
  table.rows[3].cells[col].innerHTML = notReceived;
  // eslint-disable-next-line security/detect-object-injection
  table.rows[4].cells[col].innerHTML = problem;
  // eslint-disable-next-line security/detect-object-injection
  table.rows[5].cells[col].innerHTML = reject;
  let text = 'N/A';
  if(requestedTickets !== 0) {
    text = ''+requestedTickets;
  }
  // eslint-disable-next-line security/detect-object-injection
  table.rows[6].cells[col].innerHTML = text;
  // eslint-disable-next-line security/detect-object-injection
  table.rows[7].cells[col].innerHTML = (requestedTickets/requests.length).toFixed(2);
}

function gotAllYears(years) {
  years.sort();
  let table = document.getElementById('requestOverTimeTable');
  let header = table.rows[0];
  header.insertCell().outerHTML = '<th></th>';
  let i = 0;
  for(let year of years) {
    header.insertCell().outerHTML = '<th>'+year+'</th>';
    for(let j = 1; j < table.rows.length; j++) {
      // eslint-disable-next-line security/detect-object-injection
      table.rows[j].insertCell();
    }
    let col = i+1;
    fetch('../api/v1/requests?$filter=year eq '+year).then((response) => {
      if(!response.ok) {
        alert('Failed to get requests for '+year);
        return;
      }
      response.json().then((data) => {
        gotRequests(data, year, col);
      });
    });
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
  document.getElementById('receivedDonations').innerHTML = val;
}

function gotMoney(data) {
  let formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  });
  let val = formatter.format(data.total);
  document.getElementById('receivedMoney').innerHTML = val;
  let val2 = formatter.format(data.moneyOrders);
  document.getElementById('receivedMoneyOrders').innerHTML = val2;
  let val3 = formatter.format(data.creditCards);
  document.getElementById('receivedCCOrders').innerHTML = val3;
}

function initPage() {
  fetch('../api/v1/request/countsByStatus').then((response) => {
    if(response.httpStatus === 401) {
      // Not logged in, just silently return
      return;
    }
    if(!response.ok) {
      alert('Failed to get Ticket Request Counts!');
      return;
    }
    response.json().then((data) => {
      gotRequestCounts(data);
    });
  });
  fetch('../api/v1/requests_w_tickets/types').then((response) => {
    if(response.httpStatus === 401) {
      // Not logged in, just silently return
      return;
    }
    if(!response.ok) {
      alert('Failed to get Requests with tickets!');
      return;
    }
    response.json().then((data) => {
      ticketsDone(data);
    });
  });
  fetch('../api/v1/globals/years').then((response) => {
    if(response.httpStatus === 401) {
      // Not logged in, just silently return
      return;
    }
    if(!response.ok) {
      alert('Failed to get ticket system years!');
      return;
    }
    response.json().then((data) => {
      gotAllYears(data);
    });
  });
  fetch('../api/v1/requests/donations').then((response) => {
    if(response.httpStatus === 401) {
      // Not logged in, just silently return
      return;
    }
    if(!response.ok) {
      alert('Failed to get donation amounts!');
      return;
    }
    response.json().then((data) => {
      gotDonationAmount(data);
    });
  });
  fetch('../api/v1/requests/moneyReceived').then((response) => {
    if(response.httpStatus === 401) {
      // Not logged in, just silently return
      return;
    }
    if(!response.ok) {
      alert('Failed to get money received!');
      return;
    }
    response.json().then((data) => {
      gotMoney(data);
    });
  });
}

window.onload = initPage;
