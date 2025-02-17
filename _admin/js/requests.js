/*global bootstrap, Tabulator*/
/*exported changeStatusFilter, editRequest, getCSV, getPDF, saveRequest, excelLoaded*/

function recreateTable() {
  let year = document.getElementById('year').value;
  let status = document.getElementById('statusFilter').value;
  let filter = '';
  if(year !== '*') {
    filter = 'year eq '+year;
  } else {
    filter = 'year ne 999999';
  }
  if(status !== '*') {
    filter+=' and private_status eq '+status;
  }
  let tables = Tabulator.findTable('#requests');
  if(tables === false) {
    return;
  }
  let table = tables[0];
  table.setData('../api/v1/requests?$filter='+filter);
}

function changeYear() {
  recreateTable();
}

function changeStatusFilter() {
  recreateTable();
}

function saveRequest() {
  let obj = {};
  for(let element of document.forms.request_edit_form.elements) {
    if(element.type === 'checkbox') {
      obj[element.name] = element.checked;
      continue;
    }
    if(element.name.startsWith('ticket_')) {
      let name = element.name.split('_')[1];
      if(obj['tickets'] === undefined) {
        obj['tickets'] = [];
      }
      if(obj['tickets'].length === 0 || obj['tickets'][obj['tickets'].length-1][`${name}`] !== undefined) {
        obj['tickets'][obj['tickets'].length] = {};
      }
      obj['tickets'][obj['tickets'].length-1][`${name}`] = element.value;
      continue;
    }
    if(element.name.startsWith('donation_')) {
      let split = element.name.split('_');
      if(obj['donations'] === undefined) {
        obj['donations'] = {};
      }
      if(obj['donations'][split[2]] === undefined) {
        obj['donations'][split[2]] = {};
      }
      obj['donations'][split[2]][split[1]] = element.value;
      continue;
    }
    obj[element.name] = element.value;
  }
  let id = obj['request_id'];
  delete obj['request_id'];
  obj.crit_vol = obj.critvol; // eslint-disable-line camelcase
  delete obj.critvol;
  let year = document.getElementById('year').value;
  obj.minor_confirm = true; // eslint-disable-line camelcase
  fetch('../api/v1/requests/'+id+'/'+year, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(obj)
  }).then((response) => {
    if(!response.ok) {
      response.json().then((data) => {
        if(data.message !== undefined) {
          alert(data.message);
          return;
        }
        alert('Unable to update request!');
      });
    } else {
      let modal = bootstrap.Modal.getInstance(document.getElementById('modal'));
      modal.hide();
      changeYear(document.getElementById('year'));
    }
  }).catch((err) => {
    alert('Unable to update request!');
    console.log(err);
  });
}

function editRequest() {
  window.location = '../request.php?request_id='+document.getElementById('request_id').value;
}

function getPDF() {
  let requestId = document.getElementById('request_id').value;
  let year = document.getElementById('year').value;
  window.location = '../api/v1/requests/'+requestId+'/'+year+'/pdf';
}

function getCSV() {
  let tables = Tabulator.findTable('#requests');
  if(tables === false) {
    return;
  }
  let table = tables[0];
  table.download('csv', 'requests.csv', {bom:true});
}

function rowClicked(e, row) {
  let data = row.getData();
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modal'));
  let title = document.getElementById('modal_title');
  title.innerHTML = 'Request #'+data.request_id;
  document.getElementById('request_id').value = data.request_id;
  document.getElementById('givenName').value = data.givenName;
  document.getElementById('sn').value = data.sn;
  document.getElementById('mail').value = data.mail;
  document.getElementById('year').value = data.year;
  document.getElementById('mobile').value = data.mobile;
  document.getElementById('street').value = data.street;
  document.getElementById('zip').value = data.zip;
  document.getElementById('l').value = data.l;
  document.getElementById('st').value = data.st;
  let table = document.getElementById('ticket_table');
  let tbody = table.tBodies[0];
  tbody.innerHTML = '';
  for(let ticket of data.tickets) {
    let newRow = tbody.insertRow();
    let cell = newRow.insertCell();
    let input = document.createElement('input');
    input.className = 'form-control';
    input.type = 'text';
    input.name = 'ticket_first';
    input.value = ticket.first;
    cell.appendChild(input);
    cell = newRow.insertCell();
    input = document.createElement('input');
    input.className = 'form-control';
    input.type = 'text';
    input.name = 'ticket_last';
    input.value = ticket.last;
    cell.appendChild(input);
    cell = newRow.insertCell();
    input = document.createElement('input');
    input.className = 'form-control';
    input.type = 'text';
    input.name = 'ticket_type';
    input.value = ticket.type;
    cell.appendChild(input);
  }
  table = document.getElementById('donation_table');
  tbody = table.tBodies[0];
  tbody.innerHTML = '';
  if(data.donations !== null) {
    for(let donationName in data.donations) {
      if(!data.donations[`${donationName}`].amount) {
        continue;
      }
      row = tbody.insertRow();
      let cell = row.insertCell();
      cell.innerHTML = donationName;
      cell = row.insertCell();
      let input = document.createElement('input');
      input.className = 'form-control';
      input.type = 'text';
      input.name = 'donation_amount_'+donationName;
      input.value = data.donations[`${donationName}`].amount;
      cell.appendChild(input);
    }
  }
  document.getElementById('total_due').value = data.total_due;
  document.getElementById('status').value = data.private_status;
  document.getElementById('total_received').value = data.total_received;
  document.getElementById('comments').value = data.comments;
  document.getElementById('bucket').value = data.bucket;
  document.getElementById('paymentMethod').value = data.paymentMethod;
  let envelopeArt = document.getElementById('envelopeArt');
  if(data.envelopeArt) {
    envelopeArt.checked = true;
  } else {
    envelopeArt.checked = false;
  }
  let critVol = document.getElementById('critvol');
  if(data.crit_vol) {
    critVol.checked = true;
  } else {
    critVol.checked = false;
  }
  let protected = document.getElementById('protected');
  if(data.protected) {
    protected.checked = true;
  } else {
    protected.checked = false;
  }
  modal.show();
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

function initPage() {
  let table = new Tabulator('#requests', {
    columns: [
      {title: 'Request ID', field: 'request_id'},
      {title: 'First Name', field: 'givenName'},
      {title: 'Last Name', field: 'sn'},
      {title: 'Email', field: 'mail'},
      {title: 'Total Due', field: 'total_due'},
      {title: 'Total Received', field: 'total_received'},
      {title: 'Status', field: 'private_status'},
      {title: 'City', field: 'l'},
      {title: 'State', field: 'st'},
      {title: 'Year', field: 'year'},
      {title: 'Envelope Art', field: 'envelopeArt', formatter: 'tickCross'},
      {title: 'Crit Vol', field: 'crit_vol', formatter: 'tickCross'},
      {title: 'Protected', field: 'protected', formatter: 'tickCross'},
      {title: 'Payment Method', field: 'paymentMethod'}
    ],
    pagination: 'local',
    paginationSize: 10,
    initialSort: [
      {column: 'request_id', dir: 'asc'}
    ]
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
      changeYear(yearSelect);
    });
  });
  fetch('../api/v1/globals/statuses').then((response) => {
    if(!response.ok) {
      return;
    }
    response.json().then((data) => {
      let statusSelect = document.getElementById('statusFilter');
      for(let status of data) {
        statusSelect.add(new Option(status.name, status.status_id));
      }
    });
  });
  table.on('rowClick', rowClicked);
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
    table.setFilter((data) => {
      let searchValue = search.value.toLowerCase();
      for(let key in data) {
        if(data[`${key}`] === null) {
          continue;
        }
        if(data[`${key}`].toString().toLowerCase().includes(searchValue)) {
          return true;
        }
      }
      return false;
    });
  });
}

window.onload = initPage;
