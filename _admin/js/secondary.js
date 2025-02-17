/*global addNotification, bootstrap, Tabulator*/
/*exported getCSV, getPDF, saveRequest*/
function changeYear(control) {
  let year = control.value;
  let data = 'filter=year eq '+year;
  let table = new Tabulator('#requests', {
    ajaxURL: '../api/v1/secondary/requests?'+data,
    layout: 'fitColumns',
    pagination: 'local',
    paginationSize: 10,
    columns: [
      {title: 'Request ID', field: 'request_id'},
      {title: 'Email', field: 'mail'},
      {title: 'First Name', field: 'givenName'},
      {title: 'Last Name', field: 'sn'},
      {title: 'Total Due', field: 'total_due', formatter: 'money', formatterParams: {symbol: '$'}},
    ],
  });
  table.on('tableBuilt', () => {
    table.setData();
  });
  table.on('dataLoaded', (requestData) => {
    let totalTickets = 0;
    for(let request of requestData) {
      let tickets = JSON.parse(request.valid_tickets);
      totalTickets += tickets.length;
    }
    let container = document.getElementById('requests');
    container = container.parentElement;
    if(container.querySelector('.alert') !== null) {
      container.querySelector('.alert').remove();
    }
    addNotification(container, 'There are currently requests for '+totalTickets+' tickets.');
  });
  table.on('rowClick', (e, row) => {
    rowClicked(row);
  });
  let tableElem = document.getElementById('requests');
  let parent = tableElem.parentElement;
  let node = document.createElement('div');
  node.style.float = 'right';
  node.style.textAlign = 'right';
  node.innerText = 'Search:';
  let search = document.createElement('input');
  search.type = 'text';
  search.style.border = '1px solid #aaa';
  search.style.borderRadius = '5px';
  search.style.padding = '2px';
  node.appendChild(search);
  parent.insertBefore(node, tableElem);
  search.addEventListener('input', () => {
    table.clearFilter();
    if(search.value === '') {
      return;
    }
    table.setFilter((filterData) => {
      let searchValue = search.value.toLowerCase();
      for(let key in filterData) {
        if(filterData[`${key}`] === null) {
          continue;
        }
        if(filterData[`${key}`].toString().toLowerCase().includes(searchValue)) {
          return true;
        }
      }
      return false;
    });
  });
}

function ticketRequestDone(data) {
  if(data.error !== undefined) {
    alert(data.error);
    console.log(data);
  } else {
    changeYear(document.getElementById('year'));
  }
}

function ticketRequest(requestId) {
  fetch('../api/v1/secondary/requests/'+requestId+'/current/Actions/Ticket').then((response) => {
    if(!response.ok) {
      alert('Failed to create ticket request.');
      return;
    }
    response.json().then(ticketRequestDone);
  });
}

function saveRequest() {
  let form = document.getElementById('request_edit_form');
  let obj = Object.fromEntries(new FormData(form));
  obj.total_due = obj.total_due.substring(1); // eslint-disable-line camelcase
  let requestId = obj.request_id;
  fetch('../api/v1/secondary/requests/'+requestId+'/current', {
    method: 'PATCH',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(obj),
  }).then((response) => {
    if(!response.ok) {
      alert('Failed to save request.');
      return;
    }
    let modalElement = document.getElementById('modal');
    let modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    modal.hide();
    ticketRequest(requestId);
  });
}

function getPDF() {
  let year = document.getElementById('year').value;
  let requestID = document.getElementById('request_id').value;
  window.location = '../api/v1/secondary/'+requestID+'/'+year+'/pdf';
}

function getCSV() {
  let tabulator = Tabulator.findTable('#requests')[0];
  tabulator.download('csv', 'requests.csv');
}

function rowClicked(row) {
  var data = row.getData();
  let ticketButton = document.getElementById('ticketButton');
  ticketButton.disabled = true;
  let modalElement = document.getElementById('modal');
  let modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  document.getElementById('modal_title').innerHTML = 'Request #'+data.request_id.substring(0,16)+'...';
  document.getElementById('request_id').value = data.request_id;
  document.getElementById('givenName').value = data.givenName;
  document.getElementById('sn').value = data.sn;
  document.getElementById('mail').value = data.mail;
  document.getElementById('c').value = data.c;
  document.getElementById('street').value = data.street;
  document.getElementById('zip').value = data.zip;
  document.getElementById('l').value = data.l;
  document.getElementById('st').value = data.st;
  let ticketTable = document.getElementById('ticket_table');
  let tbody = ticketTable.tBodies[0];
  tbody.innerHTML = '';
  if(typeof(data.valid_tickets) === 'string') {
    data.valid_tickets = JSON.parse(data.valid_tickets); // eslint-disable-line camelcase
  }
  for(let ticket of data.valid_tickets) {
    let newRow = tbody.insertRow();
    let type = ticket.substring(0, 1);
    let id = ticket;
    let cell = newRow.insertCell();
    cell.innerHTML = '<input type="text" id="ticket_first_'+id+'" name="ticket_first_'+id+'" class="form-control" value="'+data['ticket_first_'+id]+'"/>';
    cell = newRow.insertCell();
    cell.innerHTML = '<input type="text" id="ticket_last_'+id+'" name="ticket_last_'+id+'" class="form-control" value="'+data['ticket_last_'+id]+'"/>';
    cell = newRow.insertCell();
    cell.innerText = type;
  }
  document.getElementById('total_due').value = '$'+data.total_due;
  document.getElementById('total_received').value = data.total_received;
  if(data.total_due === data.total_received) {
    ticketButton.disabled = false;
  }
  modal.show();
}

function totalChanged(e) {
  let due = document.getElementById('total_due').value.substring(1);
  let received = e.target.value;
  if(due === received || (received[0] === '$' && due === received.substring(1))) {
    document.getElementById('ticketButton').disabled = false;
  }
}

function initPage() {
  fetch('../api/v1/globals/years').then(response => response.json()).then((data) => {
    data.sort().reverse();
    let yearSelect = document.getElementById('year');
    for(let year of data) {
      if(yearSelect.options.length === 0) {
        yearSelect.add(new Option(year, year, true, true));
      } else {
        yearSelect.add(new Option(year, year));
      }
    }
    changeYear(yearSelect);
  });
  document.getElementById('total_received').addEventListener('change', totalChanged);
}

window.onload = initPage;
