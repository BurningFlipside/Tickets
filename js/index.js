/* global addNotification, bootbox, bootstrap, NOTIFICATION_SUCCESS, NOTIFICATION_WARNING, Tabulator,  */
/* exported copy_request, downloadTicket, editTicket, showLongId, transferTicket, viewTicket, saveTicket */
var outOfWindow = false;
var testMode = false;
var ticketYear = false;
const basicButtonOptions = {'class': 'btn btn-link btn-sm', 'data-toggle': 'tooltip', 'data-placement': 'top', 'data-html': true};

function tableDrawComplete() {
  document.querySelector('#ticketList .tabulator-tableholder').style.overflow = 'hidden';
  if(window.innerWidth < 768) {
    let tables = Tabulator.findTable('#ticketList');
    if(tables === null) {
      return;
    }
    let table = tables[0];
    table.hideColumn('type');
    table.hideColumn('short_ticket_code');
  }
}

function showLongId(hash) {
  document.getElementById('long_id').innerText = hash;
  let longIdWords = document.getElementById('long_id_words');
  longIdWords.innerText = '';
  fetch('api/v1/tickets/'+hash+'?select=hash_words').then((response) => {
    if(response.ok) {
      response.json().then((data) => {
        longIdWords.innerText = data.hash_words;
      });
    }
  });
  let viewModalElement = document.getElementById('ticket_view_modal');
  let idModalElement = document.getElementById('ticket_id_modal');
  let viewModal = bootstrap.Modal.getOrCreateInstance(viewModalElement);
  let idModal = bootstrap.Modal.getOrCreateInstance(idModalElement);
  viewModal.hide();
  idModal.show();
}

function findTicketInTableByHash(tableID, hash) {
  let tables = Tabulator.findTable(tableID);
  if(tables === false) {
    return null;
  }
  let table = tables[0];
  let data = table.searchData('hash', '=', hash);
  if(data.length === 0) {
    return null;
  }
  return data[0];
}

function getTicketDataByHash(hash) {
  var ticket = findTicketInTableByHash('#ticketList', hash);
  if(ticket === null) {
    ticket = findTicketInTableByHash('#discretionary', hash);
  }
  return ticket;
}

function viewTicket(control) {
  let id = control.getAttribute('for');
  let ticket = getTicketDataByHash(id);
  if(ticket === null) {
    alert('Cannot find ticket');
    return;
  }
  document.getElementById('view_first_name').innerText = ticket.firstName;
  document.getElementById('view_last_name').innerText = ticket.lastName;
  document.getElementById('view_type').innerText = ticket.type;
  let shortCode = document.getElementById('view_short_code');
  shortCode.innerText = ticket.hash.substring(0,8);
  shortCode.setAttribute('onclick', 'showLongId(\''+ticket.hash+'\')');
  let ticketViewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('ticket_view_modal'));
  ticketViewModal.show();
}

function saveTicket() {
  let hash = document.getElementById('show_short_code').dataset.hash;
  let first = document.getElementById('edit_first_name').value;
  let last = document.getElementById('edit_last_name').value;
  let data = {'firstName': first, 'lastName': last};
  fetch('api/v1/tickets/'+hash, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  }).then((response) => {
    if(!response.ok) {
      alert('Failed to save ticket!');
      return;
    }
    location.reload();
  });
  let ticketEditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('ticket_edit_modal'));
  ticketEditModal.hide();
}

function editTicket(control) {
  let id = control.getAttribute('for');
  let ticket = getTicketDataByHash(id);
  if(ticket === null) {
    alert('Cannot find ticket');
    return;
  }
  document.getElementById('edit_first_name').value = ticket.firstName;
  document.getElementById('edit_last_name').value = ticket.lastName;
  let shortCode = document.getElementById('show_short_code');
  shortCode.value = ticket.hash.substring(0,8);
  shortCode.dataset.hash = id;
  let ticketEditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('ticket_edit_modal'));
  ticketEditModal.show();
}

function downloadTicket(control) {
  let id = control.getAttribute('for');
  window.open('api/v1/tickets/'+id+'/pdf', '_blank');  // eslint-disable-line security/detect-non-literal-fs-filename
}

function transferTicket(control) {
  let id = control.getAttribute('for');
  let ticket = getTicketDataByHash(id);
  if(ticket === null) {
    alert('Cannot find ticket');
    return;
  }
  window.location.assign('transfer.php?id='+ticket.hash);
}

function shortHash(cell) {
  let hash = cell.getValue();
  return '<a href="#" onclick="showLongId(\''+hash+'\')">'+hash.substring(0,8)+'</a>';
}

function getOuterHTML(button) {
  if(button.prop('outerHTML') === undefined) {
    return new XMLSerializer().serializeToString(button[0]);
  }
  return button.prop('outerHTML');
}

function makeGlyphButton(options, glyphClass, onClick) {
  let button = document.createElement('button');
  for(const propName in options) {
    button.setAttribute(propName, options[`${propName}`]);
  }
  button.setAttribute('type', 'button');
  button.classList.add('btn');
  button.classList.add('btn-link');
  let glyph = document.createElement('span');
  glyph.className = glyphClass;
  button.appendChild(glyph);
  button.addEventListener('click', onClick);
  //TODO unsure why I need to do this, something is wrong...
  window.setTimeout(() => {
    let temp = document.querySelector('[title="'+options.title+'"]');
    temp.addEventListener('click', onClick);
  }, 100);
  return button;
}

function makeGlyphLink(options, glyphClass, ref) {
  let link = document.createElement('a');
  for(const propName in options) {
    link.setAttribute(propName, options[`${propName}`]);
  }
  let glyph = document.createElement('span');
  glyph.className = glyphClass;
  if(ref !== undefined) {
    link.href = ref;
  }
  link.appendChild(glyph);
  return link;
}

function createButtonOptions(title, onClick, forData) {
  var ret = JSON.parse(JSON.stringify(basicButtonOptions));
  ret.title   = title;
  if(forData !== undefined) {
    ret['for']  = forData;
  }
  if(onClick !== undefined) {
    ret.onclick = onClick;
  }
  return ret;
}

function createLinkOptions(title, forData, href, target) {
  var ret = basicButtonOptions;
  ret.title   = title;
  ret['for']  = forData;
  ret.href    = href;
  if(target !== undefined) {
    ret.target = target;
  }
  return ret;
}

function getViewButton(data) {
  var viewOptions = createButtonOptions('View Ticket Code', 'viewTicket(this)', data);
  return makeGlyphButton(viewOptions, 'fa fa-search');
}

function getEditButton(data) {
  var editOptions = createButtonOptions('Edit Ticket<br/>Use this option to keep the ticket<br/>on your account but<br/>change the legal name.', 'editTicket(this)', data);
  return makeGlyphButton(editOptions, 'fa fa-pencil-alt');
}

function getPDFButton(data) {
  var pdfOptions = createLinkOptions('Download PDF', data, 'api/v1/tickets/'+data+'/pdf', '_blank');
  return makeGlyphLink(pdfOptions, 'fa fa-download');
}

function getTransferButton(data) {
  var transferOptions = createButtonOptions('Transfer Ticket<br/>Use this option to send<br/>the ticket to someone else', 'transferTicket(this)', data);
  return makeGlyphButton(transferOptions, 'fa fa-envelope');
}

function makeActions(cell) {
  let hash = cell.getValue();
  let retElem = document.createElement('span');
  retElem.style.whiteSpace = 'nowrap';
  if(window.innerWidth < 768) {
    retElem.appendChild(getViewButton(hash));
  }
  retElem.appendChild(getEditButton(hash));
  retElem.appendChild(getPDFButton(hash));
  retElem.appendChild(getTransferButton(hash));
  return retElem;
}

function initTable() {
  let table = new Tabulator('#ticketList', {
    layout: 'fitColumns',
    columns: [
      { title: 'First Name', field: 'firstName' },
      { title: 'Last Name', field: 'lastName' },
      { title: 'Type', field: 'type' },
      { title: 'Short Ticket Code', field: 'hash', formatter: shortHash},
      { title: 'Actions', field: 'hash', formatter: makeActions, headerSort: false }
    ]
  });
  fetch('api/v1/ticket').then(response => {
    if(response.ok) {
      response.json().then(data => {
        document.getElementById('ticket_set').style.removeProperty('display');
        if(data.length === 0) {
          let ticketList = document.getElementById('ticketList');
          if(!ticketList) {
            return;
          }
          ticketList.outerHTML = 'You do not have any tickets at this time!<br/>';
          return;
        }
        console.log(data);
        table.setData(data);
      });
    }
  });
  table.on('dataProcessed', tableDrawComplete);
}

function addButtonsToRow(row, request) {
  let cell = row.insertCell();
  cell.style.whiteSpace = 'nowrap';
  let editOptions = createButtonOptions('Edit Request');
  let mailOptions = createButtonOptions('Resend Request Email');
  let pdfOptions = createButtonOptions('Download Request PDF');
  let html = makeGlyphLink(editOptions, 'fa fa-pencil-alt', 'request.php?request_id='+request.request_id+'&year='+request.year);
  cell.appendChild(html);

  html = makeGlyphButton(mailOptions, 'fa fa-envelope', function(){
    fetch('api/v1/request/'+request.request_id+'/'+request.year+'/Actions/Requests.SendEmail', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    }).then(function(response) {
      if(!response.ok) {
        bootbox.alert('Unable to send email!');
        return;
      }
      bootbox.alert('Email sent!');
    });
  });
  cell.appendChild(html);

  html = makeGlyphLink(pdfOptions, 'fa fa-download', 'api/v1/request/'+request.request_id+'/'+request.year+'/pdf');
  cell.appendChild(html);
}

function toggleHiddenRequests() {
  let rows = document.querySelectorAll('tr.old_request');
  for(let row of rows) {
    if(row.style.display === 'none') {
      row.style.display = 'table-row';
    } else {
      row.style.display = 'none';
    }
  }
  let toggle = document.querySelector('#old_requests span');
  if(toggle.classList.contains('fa-chevron-right')) {
    toggle.classList.remove('fa-chevron-right');
    toggle.classList.add('fa-chevron-down');
  } else {
    toggle.classList.remove('fa-chevron-down');
    toggle.classList.add('fa-chevron-right');
  }
}

function addOldRequestToTable(tbody, request) {
  let container = tbody.querySelector('tr#old_requests');
  if(container === null) {
    let row = tbody.insertRow();
    row.id = 'old_requests';
    row.style.cursor = 'pointer';
    let cell = row.insertCell();
    cell.colSpan = 5;
    cell.innerHTML = '<span class="fa fa-chevron-right"></span> Old Requests';
    row.addEventListener('click', toggleHiddenRequests);
    row.setAttribute('onclick', 'toggleHiddenRequests()');
  }
  let row = tbody.insertRow();
  row.className = 'old_request';
  row.style.display = 'none';
  row.insertCell();
  row.insertCell().innerText = request.year;
  if(request.tickets === null) {
    request.tickets = [];
  }
  row.insertCell().innerText = request.tickets.length;
  row.insertCell().innerText = '$'+request.total_due;
}

function addRequestToTable(tbody, request, oldRequestOnly) {
  if(request.year !== ticketYear) {
    addOldRequestToTable(tbody, request);
    return;
  }
  oldRequestOnly.value = false;
  let row = tbody.insertRow();
  row.insertCell().innerText = request.request_id;
  row.insertCell().innerText = request.year;
  if(request.tickets === null) {
    request.tickets = [];
  }
  row.insertCell().innerText = request.tickets.length;
  if(!outOfWindow || testMode) {
    row.insertCell().innerText = '$'+request.total_due;
    addButtonsToRow(row, request);
  } else {
    let cell = row.insertCell();
    let div = document.createElement('div');
    div.setAttribute('data-bs-title', request.status.description);
    div.setAttribute('data-bs-container', 'body');
    div.setAttribute('data-bs-toggle', 'tooltip');
    div.setAttribute('data-bs-placement', 'top');
    div.setAttribute('title', request.status.description);
    div.innerText = request.status.name;
    cell.appendChild(div);
    row.insertCell();
  }
}

function processRequests(requests) {
  let table = document.getElementById('requestList');
  let tbody = table.tBodies[0];
  let oldRequestOnly = {};
  oldRequestOnly.value = true;
  for(let request of requests) {
    addRequestToTable(tbody, request, oldRequestOnly);
  }
  if(outOfWindow === false) {
    tbody.innerHTML += '<tr><td></td><td colspan="4" style="text-align: center;"><a href="request.php"><span class="fa fa-plus-square"></span> Create a new request</a></td></tr>';
    document.getElementById('fallback').style.display = 'none';
  } else {
    tbody.innerHTML += '<tr><td colspan="5" style="text-align: center;"></td></tr>';
  }
  if(window.innerWidth < 768) {
    for(let row of table.rows) {
      row.cells[0].style.display = 'none';
    }
  }
}

function getRequestsDone(requests) {
  document.getElementById('request_set').style.removeProperty('display');
  if(requests === undefined || requests.length === 0) {
    if(outOfWindow) {
      document.getElementById('requestList').innerHTML = '';
    } else {
      let requestSet = document.getElementById('request_set');
      requestSet.innerHTML = 'You do not currently have a current or previous ticket request.<br/><a href="/tickets/request.php">Create a Ticket Request</a>';
    }
  } else {
    processRequests(requests);
  }
}

function processOutOfWindow(now, start, end, myWindow) {
  if(now < start) {
    let message = 'The request window is not open yet. It starts on '+start.toDateString();
    if(myWindow.test_mode !== true) {
      let links = document.querySelectorAll('[href="request.php"]');
      for(let link of links) {
        link.style.display = 'none';
      }
    }
    addNotification(document.getElementById('content'), message);
    outOfWindow = true;
    return;
  }
  if(now < start || now > end) {
    let message = 'The request window is currently closed. No new ticket requests are accepted at this time.';
    if(myWindow.test_mode === true) {
      message += ' But test mode is enabled. Any requests created will be deleted before ticketing starts!';
      testMode = true;
    } else {
      let links = document.querySelectorAll('[href="request.php"]');
      for(let link of links) {
        link.style.display = 'none';
      }
    }
    let div = addNotification(document.getElementById('request_set'), message);
    let after = document.createElement('div');
    after.classList.add('w-100');
    div.after(after);
    let before = document.createElement('div');
    before.classList.add('col-sm-1');
    div.before(before);
    outOfWindow = true;
    if(!testMode) {
      let table = document.getElementById('requestList');
      table.tHead.rows[0].cells[3].innerText = 'Request Status';
    }
    new bootstrap.Collapse('#request', {
      hide: true
    });
  }
}

function processMailInWindow(now, mailStart, end) {
  if(now > mailStart && now < end) {
    let days = Math.floor(end/(1000*60*60*24) - now/(1000*60*60*24));
    let message = 'The mail in window is currently open! ';
    if(days === 1) {
      message += 'You have 1 day left to mail your request!';
    } else if(days === 0) {
      message += 'Today is the last day to mail your request!';
    } else {
      message += 'You have '+days+' days left to mail your request!';
    }
    addNotification(document.getElementById('request_set'), message, NOTIFICATION_WARNING);
  }
}

function getWindowDone(data) {
  let now = new Date(Date.now());
  if(data.current < now) {
    now = data.current;
  }
  ticketYear = data.year;
  let stopDate =  new Date(data.request_stop_date);
  stopDate.setHours(23, 59, 59);
  processOutOfWindow(now, new Date(data.request_start_date), stopDate, data);
  processMailInWindow(now, new Date(data.mail_start_date), stopDate);
  fetch('api/v1/requests').then(response => {
    if(response.ok) {
      response.json().then(requests => {
        getRequestsDone(requests);
      });
    }
  });
  initTable();
}

function collapseCard(e) {
  let icon = e.target.parentElement.querySelector('.fa-chevron-up');
  icon.classList.remove('fa-chevron-up');
  icon.classList.add('fa-chevron-down');
}

function expandCard(e) {
  let icon = e.target.parentElement.querySelector('.fa-chevron-down');
  icon.classList.remove('fa-chevron-down');
  icon.classList.add('fa-chevron-up');
}

function initIndex() {
  fetch('api/v1/globals/window').then((response => {
    if(response.ok) {
      response.json().then((data) => {
        getWindowDone(data);
      });
    }
  }));
  let cards = document.querySelectorAll('.card .collapse');
  for(let card of cards) {
    card.addEventListener('hide.bs.collapse', collapseCard);
    card.addEventListener('show.bs.collapse', expandCard);
  }
  const urlParams = new URLSearchParams(window.location.search);
  if(urlParams.get('show_transfer_info') === '1') {
    let body = document.getElementById('content');
    addNotification(body, 'You have successfully sent an email with the ticket information. The ticket will be fully transferred when the recipient logs in and claims the ticket', NOTIFICATION_SUCCESS);
  }
  fetch('/tickets/api/v1/earlyEntry/passes').then(function(response) {
    if(!response.ok) {
      return;
    }
    response.json().then(function(data) {
      if(data === false || data.length === 0) {
        return;
      }
      let div = document.getElementById('eePasses');
      let message = 'You have been granted early entry to the event. Please print out the following pass and bring it with you to the event. Or reassign it to a camp mate (they need to be on the early entry list!)';
      let spacer = document.createElement('div');
      spacer.classList.add('col-sm-1');
      div.append(spacer);
      addNotification(div, message, NOTIFICATION_WARNING);
      let table = document.createElement('table');
      table.classList.add('table');
      let row = table.insertRow();
      let cell = row.insertCell();
      cell.outerHTML = '<th>Pass Code</th>';
      cell = row.insertCell();
      cell.outerHTML = '<th>Actions</th>';
      let tbody = table.createTBody();
      for(let pass of data) {
        row = tbody.insertRow();
        cell = row.insertCell();
        cell.innerText = pass.id;
        cell = row.insertCell();
        let button = document.createElement('button');
        button.classList.add('btn');
        button.classList.add('btn-link');
        button.setAttribute('title', 'Assign Pass');
        button.innerHTML = '<i class="fa fa-envelope"></i>';
        button.addEventListener('click', () => {
          bootbox.prompt({
            title: 'Assign Pass',
            message: 'Please enter the email address of the person you want to assign the pass to',
            buttons: {
              cancel: {
                label: 'Cancel',
                className: 'btn btn-secondary'
              },
              confirm: {
                label: 'Assign',
                className: 'btn btn-primary',
              }
            },
            callback: function(email) {
              if(email === null) {
                return;
              }
              let obj = {'assignedTo': email};
              obj.ticketGroups = [{'Count': 1, 'Type': 'early_entry'}];
              fetch('/tickets/api/v1/earlyEntry/passes/'+pass.id+'/Actions/Reassign', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify(obj)
              }).then(function(postResponse) {
                if(!postResponse.ok) {
                  alert('Failed to assign pass!');
                  return;
                }
                location.reload();
              });
            }
          });
        });
        cell.append(button);
        button = document.createElement('button');
        button.classList.add('btn');
        button.classList.add('btn-link');
        button.setAttribute('title', 'Download Pass');
        button.innerHTML = '<i class="fa fa-file-pdf"></i>';
        button.addEventListener('click', () => {
          // eslint-disable-next-line security/detect-non-literal-fs-filename
          window.open('/tickets/api/v1/earlyEntry/passes/'+pass.id+'/pdf', '_blank');
        });
        cell.append(button);
        row.append(cell);
        tbody.append(row);
      }
      table.append(tbody);
      div.append(table);
    });
  });
  window.setTimeout(() => {
    let tooltips = document.querySelectorAll('[data-bs-title]');
    for(let tooltip of tooltips){ 
      new bootstrap.Tooltip(tooltip);
    }
  }, 1000);
}

window.onload = initIndex;
