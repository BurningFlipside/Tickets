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
      let reminder = false
      for(let request of data) {
        if(request.pendingInfo.status === 'pending') {
          globalIssue = true;
          if(reminder) {
	    break;
	  }
        } else if(request.pendingInfo.status === 'issued') {
	  reminder = true;
	  if(globalIssue) {
	    break;
	  }
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
      if(reminder) {
        let reminderButton = document.createElement('button');
        reminderButton.classList.add('btn', 'btn-primary');
        reminderButton.innerHTML = 'Send Reminder Email';
        let globalIssueDiv = document.getElementById('globalIssuePlaceholder');
        globalIssueDiv.appendChild(reminderButton);
        reminderButton.addEventListener('click', () => {
          getEmailContent();
        });
      }
    });
  });
}

function getEmailContent() {
  let modal = document.createElement("div");
  modal.classList.add("modal", "fade");
  modal.setAttribute("tabindex", "-1");
  modal.setAttribute("role", "dialog");
  let innerDiv = document.createElement("div");
  innerDiv.classList.add("modal-dialog","modal-xl");
  modal.appendChild(innerDiv);
  let contentDiv = document.createElement("div");
  contentDiv.classList.add("modal-content");
  innerDiv.appendChild(contentDiv);
  let headerDiv = document.createElement("div");
  headerDiv.classList.add("modal-header");
  headerDiv.innerHTML = '<h5 class="modal-title">Reminder Email Content</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
  contentDiv.appendChild(headerDiv);
  let bodyDiv = document.createElement("div");
  bodyDiv.classList.add("modal-body");
  let row = document.createElement("div");
  row.classList.add("row", "mb-3");
  let subjectLabel = document.createElement("label");
  subjectLabel.setAttribute("for", "subject");
  subjectLabel.classList.add("form-label", "col-sm-2");
  subjectLabel.innerHTML = "Subject:";
  row.appendChild(subjectLabel);
  let inputDiv = document.createElement("div");
  inputDiv.classList.add("col-sm-10");
  let subjectInput = document.createElement("input");
  subjectInput.setAttribute("type", "text");
  subjectInput.setAttribute("id", "subject");
  subjectInput.classList.add("form-control");
  subjectInput.setAttribute("placeholder", "Subject");
  inputDiv.appendChild(subjectInput);
  row.appendChild(inputDiv);
  bodyDiv.appendChild(row);
  row = document.createElement("div");
  row.classList.add("row", "mb-3");
  let bodyLabel = document.createElement("label");
  bodyLabel.setAttribute("for", "body");
  bodyLabel.classList.add("form-label", "col-sm-2");
  bodyLabel.innerHTML = "Body:";
  row.appendChild(bodyLabel);
  let bodyInput = document.createElement("textarea");
  bodyInput.setAttribute("id", "body");
  bodyInput.classList.add("form-control");
  bodyInput.setAttribute("rows", "10");
  bodyInput.setAttribute("placeholder", "Body");
  row.appendChild(bodyInput);
  bodyDiv.appendChild(row);
  row = document.createElement("div");
  row.classList.add("row", "mb-3");
  row.innerHTML = "You can use the following placeholders in the body of the email:<br/><ol>";
  row.innerHTML += "<li>${first_name} - The first name of the recipient</li>";
  row.innerHTML += "<li>${last_name}</strong> - The last name of the recipient</li>";
  row.innerHTML += "<li>${link}</strong> - The link to the payment page</li></ol>";
  bodyDiv.appendChild(row);
  contentDiv.appendChild(bodyDiv);
  let footerDiv = document.createElement("div");
  footerDiv.classList.add("modal-footer");
  let closeButton = document.createElement("button");
  closeButton.setAttribute("type", "button");
  closeButton.classList.add("btn", "btn-secondary");
  closeButton.setAttribute("data-bs-dismiss", "modal");
  closeButton.innerHTML = "Close";
  footerDiv.appendChild(closeButton);
  let sendButton = document.createElement("button");
  sendButton.setAttribute("type", "button");
  sendButton.classList.add("btn", "btn-primary");
  sendButton.innerHTML = "Send";
  footerDiv.appendChild(sendButton);
  contentDiv.appendChild(footerDiv);
  document.body.appendChild(modal);
  makeWYSIWYG(bodyInput, sendButton);
  let bsModal = new bootstrap.Modal(modal);
  bsModal.show();
}

function makeWYSIWYG(elem, sendButton) {
  const {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Font,
    Paragraph,
    SourceEditing,
    Style,
    GeneralHtmlSupport,
    Link,
  } = CKEDITOR;
  ClassicEditor.create(elem, {
    licenseKey: 'GPL',
    plugins: [Essentials, Bold, Italic, Font, Paragraph, SourceEditing, GeneralHtmlSupport, Style, Link],
    toolbar: ['undo', 'redo', '|', 'bold', 'italic', '|', 'sourceEditing', '|', 'style', 'fontColor', 'fontSize', 'fontFamily', '|', 'link'],
    htmlSupport: {
      allow: [
        {
          name: /.*/,
          attributes: true,
          classes: true,
          styles: true
        }
      ]
    }
  }).then((editor) => {
    sendButton.addEventListener("click", () => {
      fetch('../api/v1/requests/creditCardRequests/Actions/Requests.SendCCReminder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          subject: document.getElementById('subject').value,
          body: editor.getData()
        })
      }).then((response) => {
        if(!response.ok) {
          console.error('Error sending reminder email', response);
          return;
        }
        response.json().then((data) => {
          console.log('Reminder email sent', data);
        });
      }).catch((error) => {
        console.error('Error sending reminder email', error);
      });
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

function ticketCountFormatter(cell) {
  let data = cell.getRow().getData();
  let tickets = data.tickets;
  return tickets.length;
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
      {title: 'Ticket Count', field: 'tickets', formatter: ticketCountFormatter},
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
      let ticketCount = data.reduce((acc, row) => {
        return acc + row.tickets.length;
      }, 0);
      footerText.innerText = 'Showing '+data.length+' requests with '+ticketCount+' tickets';
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
  let style = document.createElement('style');
  style.textContent = '.ck-editor__editable_inline:not(.ck-comment__input *) {height: 300px; overflow-y: auto;}';
  document.head.appendChild(style);
  AddSearchToTable(document.getElementById('requests'));
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
