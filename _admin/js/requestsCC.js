/* global Tabulator, bootbox */
/* exported excelLoaded, getCSV */
function changeYear(yearSelect, table) {
  let year = yearSelect.options[yearSelect.selectedIndex].value;
  fetch('../api/v1/requests/creditCardRequests/'+year).then((response) => {
    if(!response.ok) {
      return;
    }
    response.json().then((data) => {
      table.setData(data);
      let globalIssue = false;
      for(let request of data) {
        if(request.pendingInfo.status === 'pending') {
          globalIssue = true;
          break;
        }
      }
      if(globalIssue) {
        let globalIssueButton = document.createElement('button');
        globalIssueButton.classList.add('btn', 'btn-primary');
        globalIssueButton.innerHTML = 'Issue all links';
        let globalIssueDiv = document.getElementById('globalIssuePlaceholder');
        globalIssueDiv.appendChild(globalIssueButton);
        globalIssueButton.addEventListener('click', () => {
          sendGlobalIssueRequest();
        });
      }
    });
  });
}

function sendGlobalIssueRequestImpl(data, dialog, origCount) {
  if(!origCount) {
    origCount = data.length;
  }
  if(!dialog) {
    let div = document.createElement('div');
    div.classList.add('text-center');
    let spinner = document.createElement('i');
    spinner.classList.add('fas', 'fa-spin', 'fa-spinner');
    div.appendChild(spinner);
    div.innerHTML += ' Issuing links... Please wait...';
    let progressDiv = document.createElement('div');
    progressDiv.classList.add('progress');
    let progressBar = document.createElement('div');
    progressBar.classList.add('progress-bar', 'progress-bar-striped', 'progress-bar-animated');
    progressBar.setAttribute('id', 'progressBar');
    progressBar.setAttribute('role', 'progressbar');
    progressBar.setAttribute('aria-valuenow', '0');
    progressBar.setAttribute('aria-valuemin', '0');
    progressBar.setAttribute('aria-valuemax', data.length);
    progressBar.style.width = '0%';
    progressDiv.appendChild(progressBar);
    div.appendChild(progressDiv);
    dialog = bootbox.dialog({ 
      message: div, 
      closeButton: false 
    });
  }
  let request = data.pop();
  if(request === undefined) {
    dialog.modal('hide');
    bootbox.alert('All links have been issued!');
    location.reload();
    return;
  }
  let options = getFetchOptionsForRequestId(request.request_id);
  fetch(options).then((response) => {
    if(!response.ok) {
      console.error('Error sending reissue request '+data.request_id, response);
      return;
    }
    response.json().then((jsonData) => {
      console.log('Reissue request sent '+data.request_id, jsonData);
      let progressBar = document.getElementById('progressBar');
      if(progressBar) {
        progressBar.setAttribute('aria-valuenow', origCount - data.length);
        progressBar.style.width = ((origCount - data.length) / origCount) * 100 + '%';
      }
      let tables = Tabulator.findTable('#requests');
      if(tables === false) {
        return;
      }
      let table = tables[0];
      // eslint-disable-next-line camelcase
      table.updateData([{request_id: data.request_id, pendingInfo: {status: 'issued', squareLink: jsonData}}]);
      sendGlobalIssueRequestImpl(data, dialog, origCount);
      return;
    });
  }).catch((error) => {
    console.error('Error sending reissue request '+data.request_id, error);
  });
}

function sendGlobalIssueRequest() {
  let tables = Tabulator.findTable('#requests');
  if(tables === false) {
    return;
  }
  let table = tables[0];
  let data = table.getData();
  data = data.filter((request) => {
    return request.pendingInfo.status === 'pending';
  });
  if(data.length === 0) {
    return;
  }
  if(data.length > 10) {
    bootbox.confirm('Are you sure you want to issue links for all '+data.length+' requests? This may take a while...', (result) => {
      if(result === true) {
        sendGlobalIssueRequestImpl(data);
      }
    });
  } else {
    sendGlobalIssueRequestImpl(data);
  }
}

function sendReissueRequest(requestId) {
  fetch(getFetchOptionsForRequestId(requestId)).then((response) => {
    if(!response.ok) {
      console.error('Error sending reissue request '+requestId, response);
      return;
    }
    response.json().then((data) => {
      console.log('Reissue request sent '+requestId, data);
      let tables = Tabulator.findTable('#requests');
      if(tables === false) {
        return;
      }
      let table = tables[0];
      // eslint-disable-next-line camelcase
      table.updateData([{request_id: requestId, pendingInfo: {status: 'issued', squareLink: data}}]);
    });
  }).catch((error) => {
    console.error('Error sending reissue request '+requestId, error);
  });
}

function getFetchOptionsForRequestId(requestId) {
  return new Request('../api/v1/requests/'+requestId+'/current/Actions/Requests.IssuePaymentLink', {
    method: 'POST',
  });
}

function linkStatusFormatter(cell) {
  let data = cell.getRow().getData();
  let currentVal = cell.getValue();
  switch(currentVal) {
    case 'sold':
      return '<span class="badge bg-success">Sold</span>';
    case 'pending': {
      let parentSpan = document.createElement('span');
      let badge = document.createElement('span');
      badge.classList.add('badge', 'bg-warning');
      badge.innerHTML = 'Pending';
      parentSpan.appendChild(badge);
      let button = document.createElement('button');
      button.classList.add('btn', 'btn-outline-warning');
      button.innerHTML = '<i class="fas fa-sync"></i>';
      button.setAttribute('title', 'Reissue Link');
      button.addEventListener('click', () => {
        sendReissueRequest(data.request_id);
      });
      parentSpan.appendChild(button);
      return parentSpan;
    }
    case 'issued':
      return '<span class="badge bg-info">Issued</span>';
    default:
      return currentVal;
  }
}

function initPage() {
  let table = new Tabulator('#requests', {
    index: 'request_id',
    columns: [
      {title: 'Request ID', field: 'request_id'},
      {title: 'First Name', field: 'givenName'},
      {title: 'Last Name', field: 'sn'},
      {title: 'Email', field: 'mail'},
      {title: 'Total Due', field: 'total_due', formatter: 'money', formatterParams: {symbol: '$'}},
      {title: 'Link Status', field: 'pendingInfo.status', formatter: linkStatusFormatter},
      {title: 'Link', field: 'pendingInfo.squareLink'},
    ],
    pagination: 'local',
    paginationSize: 10,
    initialSort: [
      {column: 'request_id', dir: 'asc'}
    ]
  });
  let footerText = document.createElement('div');
  table.on('dataLoaded', (data) => {
    let tabulatorFooter = document.getElementsByClassName('tabulator-footer')[0];
    if(data.length <= 10) {
      tabulatorFooter.style.display = 'none';
    } else {
      tabulatorFooter.style.display = 'block';
      footerText.style.float = 'left';
      footerText.innerText = 'Showing '+data.length+' requests';
      tabulatorFooter.firstChild.prepend(footerText);
    }
  });
  table.on('dataFiltered', function(filters, rows){
    let data = table.getData();
    if(data.length !== rows.length) {
      footerText.innerText = 'Showing '+rows.length+' of '+data.length+' requests';
    }
  });
  fetch('../api/v1/globals/years').then((response) => {
    if(!response.ok) {
      return;
    }
    response.json().then((data) => {
      data.sort().reverse();
      let yearSelect = document.getElementById('year');
      for(let year of data) {
        if(yearSelect.options.length === 1) {
          yearSelect.add(new Option(year, year, true, true));
        } else {
          yearSelect.add(new Option(year, year));
        }
      }
      changeYear(yearSelect, table);
      yearSelect.addEventListener('change', () => {
        changeYear(yearSelect, table);
      });
    });
  });
  let statusFilter = document.getElementById('statusFilter');
  statusFilter.addEventListener('change', () => {
    let status = statusFilter.options[statusFilter.selectedIndex].value;
    table.setFilter('pendingInfo.status', '=', status);
  });
}

function excelLoaded() {
  let csv = document.getElementById('csv');
  let excel = document.createElement('button');
  excel.className = 'btn btn-link btn-sm';
  excel.innerHTML = '<i class="fa fa-file-excel"></i>';
  excel.addEventListener('click', () => {
    let tables = Tabulator.findTable('#requests');
    if(tables === false) {
      return;
    }
    let table = tables[0];
    table.download('xlsx', 'requests.xlsx', {sheetName:'Requests'});
  });
  csv.parentNode.append(excel);
}

function getCSV() {
  let tables = Tabulator.findTable('#requests');
  if(tables === false) {
    return;
  }
  let table = tables[0];
  table.download('csv', 'requests.csv', {bom:true});
}

window.addEventListener('load', function() {
  initPage();
});