/*global bootstrap, Tabulator*/
/*exported changeMenu, lookupRequestByValue, saveRequest*/

function requestAjaxDone(data) {
  document.getElementById('modal_title').innerHTML = 'Request #'+data.request_id;
  document.getElementById('given_name').value = data.givenName;
  document.getElementById('last_name').value = data.sn;
  document.getElementById('total_due').value = '$'+data.total_due;
  document.getElementById('comments').value = data.comments;
  document.getElementById('status').value = data.private_status;
  let totalReceived = document.getElementById('total_received');
  if(data.total_received === 0 || data.total_received === '0') {
    totalReceived.value = '';
  } else {
    totalReceived.value = '$'+data.total_received;
  }
  setTimeout(function() {
    totalReceived.focus();
  }, 300);
  document.getElementById('bucket').value = data.bucket;
  document.getElementById('request_id_hidden').value = data.request_id;
  document.getElementById('save_btn').dataset.id = data.id;
}

function lookupRequest(control) {
  let id = control.value;
  lookupRequestById(id);
  control.value = '';
}

function lookupRequestById(id) {
  let modalElem = document.getElementById('request_select');
  let modal = bootstrap.Modal.getOrCreateInstance(modalElem);
  modal.hide();
  modalElem = document.getElementById('modal');
  modal = bootstrap.Modal.getOrCreateInstance(modalElem);
  modal.show();
  fetch('../api/v1/requests/'+id+'/current/Actions/Requests.GetBucket').then(response => {
    if(!response.ok) {
      alert('Request not found!');
      return;
    }
    response.json().then(data => {
      requestAjaxDone(data);
    });
  });
}

function lookupAjaxDone(data) {
  if(data.length === 1) {
    let requestID = document.getElementById('request_id');
    requestID.value = data[0].request_id;
    lookupRequest(requestID);
  } else {
    let modalElem = document.getElementById('request_select');
    let modal = bootstrap.Modal.getOrCreateInstance(modalElem);
    modal.show();
    let table = Tabulator.findTable('#request_table')[0];
    table.clearData();
    table.updateOrAddData(data);
    if(data.length <= 10) {
      document.getElementsByClassName('tabulator-footer')[0].style.display = 'none';
    } else {
      document.getElementsByClassName('tabulator-footer')[0].style.display = 'block';
    }
  }
}

function lookupRequestByValue(control) {
  let type = document.getElementById('type').dataset.type;
  let value = control.value;
  if(type === '*') {
    fetch('../api/v1/requests?$search='+value+'&$filter=year eq current').then(response => {
      if(!response.ok) {
        alert('Search failed!');
        return;
      }
      response.json().then(data => {
        lookupAjaxDone(data);
      });
    });
  } else {
    fetch('../api/v1/requests?$filter=contains('+type+','+value+') and year eq current').then(response => {
      if(!response.ok) {
        alert('Search failed!');
        return;
      }
      response.json().then(data => {
        lookupAjaxDone(data);
      });
    });
  }
  control.value = '';
}

function changeMenu(value, text) {
  let type = document.getElementById('type');
  type.dataset.type = value;
  type.innerHTML = text+'  <span class="caret"></span>';
}

function restoreFocus() {
  document.getElementById('request_id').focus();
}

function saveRequest() {
  if(document.getElementById('total_received').value === '') {
    alert('Need Total Received!');
    return;
  }
  let form = document.getElementById('req_form');
  let obj = Object.fromEntries(new FormData(form));
  if(obj.total_due !== undefined && obj.total_due[0] === '$') {
    obj.total_due = obj.total_due.substring(1); // eslint-disable-line camelcase
  }
  if(obj.total_received !== undefined && obj.total_received[0] === '$') {
    obj.total_received = obj.total_received.substring(1); // eslint-disable-line camelcase
  }
  obj.request_id = obj.id; // eslint-disable-line camelcase
  delete obj.id;
  obj.year = 'current';
  fetch('../api/v1/requests/'+obj.request_id, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(obj)
  }).then(response => {
    if(!response.ok) {
      alert('Unable to save request!');
    } else {
      let modalElem = document.getElementById('modal');
      let modal = bootstrap.Modal.getOrCreateInstance(modalElem);
      modal.hide();
      document.getElementById('request_id').focus();
    }
  });
}

function initPage() {
  document.getElementById('modal').addEventListener('hidden.bs.modal', restoreFocus);
  fetch('../api/v1/globals/statuses').then(response => {
    if(!response.ok) {
      return;
    }
    response.json().then(data => {
      let statusSelect = document.getElementById('status');
      for(let status of data) {
        statusSelect.add(new Option(status.name, status.status_id));
      }
    });
  });
  new Tabulator('#request_table', {
    layout: 'fitColumns',
    pagination: 'local',
    paginationSize: 10,
    columns: [
      {title: 'Request ID', field: 'request_id', formatter: 'link', formatterParams: {url: (cell) => {
        let data = cell.getData();
        return 'javascript:lookupRequestById('+data.request_id+')';
      }, target: '_self'}},
      {title: 'Name', field: 'name', formatter: (cell) => {
        let data = cell.getRow().getData();
        return data.givenName+' '+data.sn;
      }},
      {title: 'Email', field: 'mail'}
    ]
  });
}

window.onload = initPage;
