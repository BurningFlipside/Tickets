/* global bootstrap, Tabulator*/
/* exported nextTicket, prevTicket, resendTicketEmail, saveTicket, spinHash */
let ticketData = null;

function renderShortHash(cell) {
  let data = cell.getValue();
  let shortHash = data.substring(0,8);
  return '<a href="#" style="cursor: pointer;" onclick="viewTicket(\''+data+'\');">'+shortHash+'</a>';
}

function renderTicketType(cell) {
  let data = cell.getValue();
  let typeSelect = document.getElementById('type');
  for(let option of typeSelect.options) {
    if(option.value === data) {
      return option.text;
    }
  }
  return data;
}

function getTicketBySelected() {
  if(ticketData.selected === -1) {
    return ticketData.current;
  }
  return ticketData.history[ticketData.selected];
}

function showTicketFromData(data) {
  let readOnly = true;
  let ticket = null;
  let leftArrow = document.getElementById('left_arrow');
  let rightArrow = document.getElementById('right_arrow');
  let saveTicketButton = document.getElementById('saveticket');
  if(data.selected === -1) {
    ticket = data.current;
    rightArrow.disabled = true;
    if(data.history !== undefined && data.history.length > 0) {
      leftArrow.disabled = false;
    } else {
      leftArrow.disabled = true;
    }
    readOnly = false;
    saveTicketButton.disabled = false;
  } else {
    ticket = data.history[data.selected];
    if(data.selected === (data.history.length - 1)) {
      leftArrow.disabled = true;
    } else {
      leftArrow.disabled = false;
    }
    rightArrow.disabled = false;
    saveTicketButton.disabled = true;
  }
  document.getElementById('hash').value = ticket.hash;
  document.getElementById('year').value = ticket.year;
  let firstName = document.getElementById('firstName');
  firstName.value = ticket.firstName;
  let lastName = document.getElementById('lastName');
  lastName.value = ticket.lastName;
  let email = document.getElementById('email');
  email.value = ticket.email;
  let requestID = document.getElementById('request_id');
  requestID.value = ticket.request_id;
  let typeSelect = document.getElementById('type');
  typeSelect.value = ticket.type;
  let guardianFirst = document.getElementById('guardian_first');
  let guardianLast = document.getElementById('guardian_last');
  guardianFirst.value = ticket.guardian_first;
  guardianLast.value = ticket.guardian_last;
  let eeWindow = document.getElementById('earlyEntryWindow');
  eeWindow.value = ticket.earlyEntryWindow;
  let sold = document.getElementById('sold');
  if(ticket.sold === 1 || ticket.sold === '1') {
    sold.checked = true;
  } else {
    sold.checked = false;
  }
  let used = document.getElementById('used');
  if(ticket.used === 1 || ticket.used === '1') {
    used.checked = true;
  } else {
    used.checked = false;
  }
  let voidValue = document.getElementById('void');
  if(ticket.void === 1 || ticket.void === '1') {
    voidValue.checked = true;
  } else {
    voidValue.checked = false;
  }
  let comments = document.getElementById('comments');
  comments.value = ticket.comments;
  if(readOnly) {
    firstName.disabled = true;
    lastName.disabled = true;
    email.disabled = true;
    requestID.disabled = true;
    typeSelect.disabled = true;
    guardianFirst.disabled = true;
    guardianLast.disabled = true;
    eeWindow.disabled = true;
    sold.disabled = true;
    used.disabled = true;
    voidValue.disabled = true;
    comments.disabled = true;
  } else {
    firstName.disabled = false;
    lastName.disabled = false;
    email.disabled = false;
    requestID.disabled = false;
    typeSelect.disabled = false;
    if(ticket.type === 'A') {
      guardianFirst.disabled = true;
      guardianLast.disabled = true;
    } else {
      // All other ticket types are minors and so we need guardian info
      guardianFirst.disabled = false;
      guardianLast.disabled = false;
    }
    eeWindow.disabled = false;
    sold.disabled = false;
    used.disabled = false;
    voidValue.disabled = false;
    comments.disabled = false;
  }
  let modalElement = document.getElementById('ticket_modal');
  let modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  modal.show();
}

function viewTicket(hash) {
  fetch('../api/v1/tickets/'+hash+'?with_history=1').then(response => response.json()).then((data) => {
    ticketData = data;
    showTicketFromData(data);
  });
}

function prevTicket() {
  ticketData.selected++;
  showTicketFromData(ticketData);
}

function nextTicket() {
  ticketData.selected--;
  showTicketFromData(ticketData);
}

function setIfValueDifferent(ticket, obj, inputName, fieldName) {
  if(fieldName === undefined) {
    fieldName = inputName;
  }
  let input = document.getElementById(inputName);
  if(input.type === 'checkbox') {
    if(input.checked) {
      if(ticket[`${fieldName}`] === 0 || ticket[`${fieldName}`] === '0') {
        obj[`${fieldName}`] = 1;
      }
    } else if(ticket[`${fieldName}`] === 1 || ticket[`${fieldName}`] === '1') {
      obj[`${fieldName}`] = 0;
    }
    return;
  }
  let val = input.value;
  if(val !== ticket[`${fieldName}`]) {
    obj[`${fieldName}`] = val;
  }
}

function saveTicket() {
  var ticket = getTicketBySelected();
  var obj = {};
  setIfValueDifferent(ticket, obj, 'email');
  setIfValueDifferent(ticket, obj, 'firstName');
  setIfValueDifferent(ticket, obj, 'lastName');
  setIfValueDifferent(ticket, obj, 'request_id');
  setIfValueDifferent(ticket, obj, 'type');
  setIfValueDifferent(ticket, obj, 'guardian_first');
  setIfValueDifferent(ticket, obj, 'guardian_last');
  setIfValueDifferent(ticket, obj, 'sold');
  setIfValueDifferent(ticket, obj, 'used');
  setIfValueDifferent(ticket, obj, 'void');
  setIfValueDifferent(ticket, obj, 'earlyEntryWindow');
  setIfValueDifferent(ticket, obj, 'comments');
  if(Object.keys(obj).length > 0) {
    fetch('../api/v1/tickets/'+ticket.hash, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(obj)
    }).then((response) => {
      if(!response.ok) {
        alert('Unable to save ticket!');
        return;
      }
      let modalElement = document.getElementById('ticket_modal');
      let modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      modal.hide();
    });
  }
  let modalElement = document.getElementById('ticket_modal');
  let modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  modal.hide();
}

function resendTicketEmail() {
  let ticket = getTicketBySelected();
  fetch('../api/v1/tickets/'+ticket.hash+'/Actions/Ticket.SendEmail', {
    method: 'POST'
  }).then((response) => {
    if(response.status !== 200) {
      alert('Unable to resend email!');
      return;
    }
    let modalElement = document.getElementById('ticket_modal');
    let modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    modal.hide();
  });
}

function spinHash() {
  let ticket = getTicketBySelected();
  fetch('../api/v1/tickets/'+ticket.hash+'/Actions/Ticket.SpinHash', {
    method: 'POST'
  }).then((response) => {
    if(response.status !== 200) {
      alert('Unable to spin hash!');
      return;
    }
    let modalElement = document.getElementById('ticket_modal');
    let modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    modal.hide();
    requeryTable();
  });
}

function tableSearched(search) {
  if(search.value === '') {
    return;
  }
  fetch('../api/v1/tickets/search/'+search.value).then(response => response.json()).then((data) => {
    if(data.length === 0) {
      return;
    }
    if(data.length === 1) {
      viewTicket(data[0].hash);
      return;
    }
  });
}

function requeryTable() {
  let year = document.getElementById('ticket_year').value;
  let sold = document.getElementById('ticketSold').value;
  let assigned = document.getElementById('ticketAssigned').value;
  let used = document.getElementById('ticketUsed').value;
  let voidVal = document.getElementById('ticketVoid').value;
  let disc = document.getElementById('discretionaryUser').value;
  let ee = document.getElementById('earlyEntry').value;
  let pool = document.getElementById('ticketPool').value;
  let filter = 'year eq '+year;
  if(year === '*') {
    filter = 'year ne 999999';
  }
  if(sold !== '*') {
    filter+=' and sold eq '+sold;
  }
  if(assigned !== '*') {
    filter+=' and assigned eq '+assigned;
  }
  if(used !== '*') {
    filter+=' and used eq '+used;
  }
  if(disc !== '') {
    filter+=' and discretionaryOrig eq \''+disc+'\' and discretionary eq 1';
  }
  if(voidVal !== '*') {
    filter+=' and void eq '+voidVal;
  }
  if(ee !== '*') {
    filter+=' and earlyEntryWindow eq '+ee;
  }
  if(pool !== '*') {
    filter+=' and pool_id eq '+pool;
  }
  let table = Tabulator.findTable('#tickets')[0];
  table.setData('../api/v1/tickets?filter='+filter);
}

function initPage() {
  const urlParams = new URLSearchParams(window.location.search);
  if(urlParams.get('sold') !== null) {
    document.getElementById('ticketSold').value = urlParams.get('sold');
  }
  if(urlParams.get('used') !== null) {
    document.getElementById('ticketUsed').value = urlParams.get('used');
  }
  if(urlParams.get('discretionaryUser') !== null) {
    document.getElementById('discretionaryUser').value = urlParams.get('discretionaryUser');
  }
  let table = new Tabulator('#tickets', {
    layout: 'fitColumns',
    pagination: 'local',
    paginationSize: 10,
    paginationSizeSelector: [10, 20, 50, 100],
    columns: [
      {'title': 'Short Code', 'field': 'hash', 'formatter': renderShortHash},
      {'title': 'First Name', 'field': 'firstName'},
      {'title': 'Last Name', 'field': 'lastName'},
      {'title': 'Email', 'field': 'email'},
      {'title': 'Type', 'field': 'type', 'formatter': renderTicketType}
    ],
  });
  fetch('../api/v1/globals/years').then(response => response.json()).then(data => {
    data.sort().reverse();
    let yearSelect = document.getElementById('ticket_year');
    for(let year of data) {
      if(yearSelect.options.length === 1) {
        yearSelect.add(new Option(year, year, true, true));
      } else {
        yearSelect.add(new Option(year, year));
      }
    }
    yearSelect.addEventListener('change', requeryTable);
    requeryTable();
  });
  fetch('../api/v1/globals/ticket_types').then(response => response.json()).then(data => {
    let typeSelect = document.getElementById('type');
    typeSelect.outerHTML = '<select id="type" name="type" class="form-control"></select>';
    typeSelect = document.getElementById('type');
    for(let type of data) {
      if(typeSelect.options.length === 0) {
        typeSelect.add(new Option(type.description, type.typeCode, true, true));
      } else {
        typeSelect.add(new Option(type.description, type.typeCode));
      }
    }
  });
  fetch('../api/v1/earlyEntry').then(response => response.json()).then(data => {
    let eeSelect = document.getElementById('earlyEntryWindow');
    let eeSelect2 = document.getElementById('earlyEntry');
    eeSelect.outerHTML = '<select id="earlyEntryWindow" name="earlyEntryWindow" class="form-control"></select>';
    eeSelect = document.getElementById('earlyEntryWindow');
    for(let ee of data) {
      if(eeSelect.options.length === 0) {
        let option = new Option(ee.earlyEntryDescription, ee.earlyEntrySetting, true, true);
        eeSelect.add(option);
        option = new Option(ee.earlyEntryDescription, ee.earlyEntrySetting);
        eeSelect2.add(option);
      } else {
        let option = new Option(ee.earlyEntryDescription, ee.earlyEntrySetting);
        eeSelect.add(option);
        option = new Option(ee.earlyEntryDescription, ee.earlyEntrySetting);
        eeSelect2.add(option);
      }
      eeSelect2.value = '*';
    }
  });
  fetch('../api/v1/pools').then(response => response.json()).then(data => {
    let poolSelect = document.getElementById('ticketPool');
    for(let pool of data) {
      poolSelect.add(new Option(pool.pool_name, pool.pool_id));
    }
  });

  document.getElementById('ticketSold').addEventListener('click', requeryTable);
  document.getElementById('ticketAssigned').addEventListener('click', requeryTable);
  document.getElementById('ticketUsed').addEventListener('click', requeryTable);
  document.getElementById('discretionaryUser').addEventListener('click', requeryTable);
  document.getElementById('ticketVoid').addEventListener('click', requeryTable);
  document.getElementById('earlyEntry').addEventListener('click', requeryTable);
  document.getElementById('ticketPool').addEventListener('click', requeryTable);

  if(urlParams.get('hash') !== null) {
    viewTicket(urlParams.get('hash'));
  }
  table.on('dataLoaded', (data) => {
    let tabulatorFooter = document.getElementsByClassName('tabulator-footer')[0];
    if(data.length <= 10) {
      tabulatorFooter.style.display = 'none';
    } else {
      tabulatorFooter.style.display = 'block';
      let footerText = document.createElement('div');
      footerText.style.float = 'left';
      footerText.innerText = 'Showing '+data.length+' tickets';
      tabulatorFooter.firstChild.prepend(footerText);
    }
  });
  let tableElem = document.getElementById('tickets');
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
  table.on('dataFiltered', (filters, rows) => {
    if(rows.length === 0) {
      tableSearched(search);
    }
  });
}

window.onload = initPage;
