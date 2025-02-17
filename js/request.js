/* global $, bootstrap, TicketSystem, add_notification, browser_supports_cors, getParameterByName */
/* exported deleteTicket, minorAffirmClicked */
var ticketSystem;

var ticketConstraints = null;
var tableRow = 0;

function constraintsAjaxDone(data) {
  ticketConstraints = data;
  let tableRowCount = document.getElementById('ticket_table').rows.length;
  // Ignore the header and footer rows
  tableRowCount -= 2;
  if(tableRowCount > 0) {
    let types = document.querySelectorAll('[id^=ticket_type_]');
    for(let type of types) {
      populateDropdown(type, null, type.dataset.temp);
    }
    for(let i = 0; i < tableRowCount; i++) {
      ticketTypeChanged(i);
    }
  }
  let contentDiv = document.getElementById('content');
  for(let ticketType of data.ticket_types) {
    let hiddenCurrentCostBox = document.createElement('input');
    hiddenCurrentCostBox.type = 'hidden';
    hiddenCurrentCostBox.id = 'ticketCost'+ticketType.typeCode;
    hiddenCurrentCostBox.value = ticketType.cost;
    let hiddenCashCostBox = document.createElement('input');
    hiddenCashCostBox.type = 'hidden';
    hiddenCashCostBox.id = 'ticketCashCost'+ticketType.typeCode;
    hiddenCashCostBox.value = ticketType.cost;
    let hiddenCreditCostBox = document.createElement('input');
    hiddenCreditCostBox.type = 'hidden';
    hiddenCreditCostBox.id = 'ticketCreditCost'+ticketType.typeCode;
    hiddenCreditCostBox.value = ticketType.squareCost;
    contentDiv.appendChild(hiddenCurrentCostBox);
    contentDiv.appendChild(hiddenCashCostBox);
    contentDiv.appendChild(hiddenCreditCostBox);
  }
  let hiddenMaxTotalTicketsBox = document.createElement('input');
  hiddenMaxTotalTicketsBox.type = 'hidden';
  hiddenMaxTotalTicketsBox.id = 'maxTotalTickets';
  hiddenMaxTotalTicketsBox.value = data.max_tickets_per_request;
  contentDiv.appendChild(hiddenMaxTotalTicketsBox);
}

function populateDropdown(dropdown, cost, type) {
  if(cost !== null) {
    cost.value = ' ';
  }
  dropdown.innerHTML = '';
  let option = document.createElement('option');
  option.value = ' ';
  option.text = ' ';
  dropdown.appendChild(option);
  if(ticketConstraints === null) {
    return;
  }
  for(let ticketType of ticketConstraints.ticket_types) {
    option = document.createElement('option');
    option.value = ticketType.typeCode;
    option.text = ticketType.description;
    if(ticketType.typeCode === type) {
      option.selected = true;
    }
    dropdown.appendChild(option);
  }
  ticketTypeChanged(dropdown);
}

function floatValue(i) {
  if(typeof i === 'string') {
    return i.replace(/[\$,]/g, '')*1;
  }
  return i;
}

function calculateTicketSubtotal() {
  let total = 0;
  let costElems = document.querySelectorAll('[name=ticket_cost]');
  for(let costElem of costElems) {
    total += floatValue(costElem.value);
  }
  document.getElementById('ticket_subtotal').innerHTML = '$'+total;
}

function addRowToTable(tbody, firstName, lastName, type, rowId) {
  let row = document.createElement('tr');
  let cell = document.createElement('td');
  cell.id = 'delete_cell';
  if(rowId !== 0) {
    let button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-link btn-sm';
    button.id = 'delete_' + rowId;
    button.onclick = deleteTicket;
    let span = document.createElement('span');
    span.className = 'fa fa-times';
    button.appendChild(span);
    cell.appendChild(button);
  }
  row.appendChild(cell);

  cell = document.createElement('td');
  let first = document.createElement('input');
  first.type = 'text';
  first.id = 'ticket_first';
  first.name = 'ticket_first';
  first.required = true;
  first.value = firstName;
  first.className = 'form-control';
  cell.appendChild(first);
  row.appendChild(cell);

  cell = document.createElement('td');
  let last = document.createElement('input');
  last.type = 'text';
  last.id = 'ticket_last';
  last.name = 'ticket_last';
  last.required = true;
  last.value = lastName;
  last.className = 'form-control';
  cell.appendChild(last);
  row.appendChild(cell);

  let cost = document.createElement('input');

  cell = document.createElement('td');
  let age = document.createElement('select');
  age.id = 'ticket_type';
  age.name = 'ticket_type';
  age.className = 'form-control';
  age.required = true;
  age.onchange = function() { 
    ticketTypeChanged(this); 
  };
  if(ticketConstraints !== null) {
    populateDropdown(age, cost, type);
  } else {
    age.dataset.temp = type;
  }
  cell.appendChild(age);
  row.appendChild(cell);

  let cell2 = document.createElement('td');
  cost.type = 'text';
  cost.id = 'ticket_cost';
  cost.name = 'ticket_cost';
  cost.readOnly = true;
  cost.className = 'form-control';
  cell2.appendChild(cost);
  row.appendChild(cell2);

  tbody.appendChild(row);
  calculateTicketSubtotal();
}

function requestAjaxDone(data) {
  document.getElementById('givenName').value = data.givenName;
  document.getElementById('sn').value = data.sn;
  let mail = document.getElementById('mail');
  mail.value = data.mail;
  mail.disabled = true;
  document.getElementById('street').value = data.postalAddress;
  document.getElementById('zip').value = data.postalCode;
  document.getElementById('l').value = data.l;
  document.getElementById('st').value = data.st;
  document.getElementById('mobile').value = data.mobile;
  let c = document.getElementById('c');
  if(data.c === undefined || data.c.length <= 0) {
    c.value = 'US';
  } else {
    c.value = data.c;
  }
  if(data.postalAddress === null || data.postalAddress.length === 0 || 
     data.postalCode === null || data.postalCode.length === 0 || 
     data.l === null || data.l.length === 0 || 
     data.st === null || data.st.length === 0 || 
     data.mobile === null || data.mobile.length === 0) {
    add_notification($('#request_set'), 'If you had filled out your profile this data would all be populated.');
  }

  let tbody = document.querySelector('#ticket_table tbody');
  addRowToTable(tbody, data.givenName, data.sn, 'A', tableRow++);
}

function ticketTypeChanged(dropdown) {
  let dropdownValue = dropdown.value;
  if(ticketConstraints !== null) {
    let count = 0;
    let types = document.querySelectorAll('[id^=ticket_type]');
    for(let type of types) {
      if(type.value === dropdownValue) {
        count++;
      }
    }
    let hiddenCostElem = document.getElementById('ticketCost'+dropdownValue);
    if (hiddenCostElem === null) {
      return;
    }
    let hiddenCost = hiddenCostElem.value;
    let row = dropdown.closest('tr');
    if(row !== null) {
      row.querySelector('[name="ticket_cost"]').value = '$' + hiddenCost;
    }
    var ticketTypes = ticketConstraints.ticket_types;
    for(let ticketType of ticketTypes) {
      if(ticketType.typeCode === dropdownValue) {
        if(count > ticketType.max_per_request) {
          alert('You are only allowed to have '+ticketType.max_per_request+' '+
                ticketType.description+' tickets per request');
        }
      }
    }
  }
  calculateTicketSubtotal();
}

function addNewTicket() {
  let maxTotalTicketsElem = document.getElementById('maxTotalTickets');
  if(maxTotalTicketsElem !== null) {
    let maxTotalTickets = maxTotalTicketsElem.value;
    let rowCount = document.getElementById('ticket_table').rows.length;
    let addNewTicketButton = document.getElementById('add_new_ticket');
    // Ignore the header and footer rows
    rowCount -= 2;
    if(rowCount >= maxTotalTickets) {
      addNewTicketButton.disabled = true;
      let newTicketTooltip = document.getElementById('new_ticket_tooltip');
      newTicketTooltip.dataset.toggle = 'tooltip';
      newTicketTooltip.dataset.placement = 'left';
      newTicketTooltip.setAttribute('title', 'You can have a maximum of '+maxTotalTickets+' tickets per request');
      new bootstrap.Tooltip(newTicketTooltip);
      return;
    }
    addNewTicketButton.disabled = false;
  }
  let tbody = document.querySelector('#ticket_table tbody');
  if(tbody.rows.length > 1) {
    let button = document.getElementById('delete_'+(tbody.rows.length-1));
    button.disabled = true;
    let cell = button.parentNode;
    cell.dataset.toggle = 'tooltip';
    cell.dataset.placement = 'left';
    cell.dataset.container = 'body';
    cell.setAttribute('title', 'You must delete the last ticket before adding another');
    new bootstrap.Tooltip(cell);
  }
  addRowToTable(tbody, '', '', ' ', tableRow++);
}

function deleteTicket(e) {
  let row = e.target.closest('tr');
  let table = row.closest('table');
  let tbody = table.querySelector('tbody');
  tbody.removeChild(row);
  tableRow--;
  let button = document.getElementById('delete_'+(tbody.rows.length-1));
  if(button !== null) {
    button.disabled = false;
    let cell = button.parentNode;
    cell.dataset.toggle = 'tooltip';
    cell.dataset.placement = 'left';
    cell.dataset.container = 'body';
    cell.setAttribute('title', 'Delete this ticket');
    new bootstrap.Tooltip(cell);
  }
  calculateTicketSubtotal();
  let maxTotalTicketsElem = document.getElementById('maxTotalTickets');
  if(maxTotalTicketsElem !== null) {
    let maxTotalTickets = maxTotalTicketsElem.value;
    let rowCount = document.getElementById('ticket_table').rows.length;
    let addNewTicketButton = document.getElementById('add_new_ticket');
    // Ignore the header and footer rows
    rowCount -= 2;
    if(rowCount < maxTotalTickets) {
      addNewTicketButton.disabled = false;
    }
  }
}

function donationAmountChanged(e) {
  let elem = e.target !== undefined ? e.target : e;
  let id = elem.id;
  let textId = id+'_text';
  let textElem = document.getElementById(textId);
  if(elem.value === 'other') {
    if(textElem === null) {
      let box = document.createElement('input');
      box.name = id;
      box.id = textId;
      box.className = 'form-control';
      box.placeholder = 'Donation ($)';
      box.type = 'number';
      elem.parentNode.appendChild(box);
    }
  } else {
    let boxes = document.getElementById(textId);
    if(boxes !== null) {
      boxes.style.display = 'none';
    }
  }
}

function showDonationInfoTooltip() {
  var span = $(this);
  var donation = span.data('donation');
  var tooltip = '';
  tooltip += '<p align="left">';
  tooltip += 'Checking this box will provide '+donation.entityName+' with the following:<br/>';
  tooltip += '<address>';
  tooltip += $('#givenName').val()+' '+$('#sn').val()+'<br/>';
  tooltip += $('#street').val()+'<br/>';
  tooltip += $('#l').val()+', '+$('#st').val()+' '+$('#zip').val()+'<br/>';
  if($('#c').val() !== 'US') {
    tooltip += $('#c option:selected').text()+'<br/>';
  }
  tooltip += '</address>';
  tooltip += 'Email: '+$('#mail').val()+'<br/>';
  var amount = $('#donation_amount_'+donation.entityName).val();
  if(amount === 'other') {
    amount = $('#donation_amount_'+donation.entityName+'_text').val();
  }
  tooltip += 'Donation Amount: $'+amount;
  tooltip += '</p>';
  span.attr('data-original-title', tooltip);
}

function addDiscloseCheckboxToCell(cell, donation) {
  var id = 'donation_disclose_'+donation.entityName;
  var span = $('<span/>', {'data-toggle': 'tooltip', 'data-placement': 'bottom', 'title': 'Filler...'});
  var checkbox = $('<input/>', {type: 'checkbox', id: id, name: id});
  var label = $('<label/>', {for: id}).html('&nbsp;Allow '+donation.entityName+' to see my contact details');
  checkbox.appendTo(span);
  label.appendTo(span);
  span.appendTo(cell);
  span.tooltip({html:true});
  span.data('donation', donation);
  span.on('show.bs.tooltip', showDonationInfoTooltip);
}

function addDonationTypeToTable(table, donation) {
  var row = $('<tr/>');
  var cell = $('<td/>');
  cell.append(donation.entityName);
  if(donation.thirdParty || donation.url) {
    cell.append('<br/>');
    if(donation.thirdParty) {
      cell.append('<I>Not Affiliated with Catalyst Collective</I> ');
    }
    if(donation.url) {
      cell.append('<a href="'+donation.url+'" target="_new">More Info</a>');
    }
  }
  cell.appendTo(row);
  cell = $('<td/>');
  if(donation.thirdParty && $(window).width() >= 768) {
    addDiscloseCheckboxToCell(cell, donation);
  }
  cell.appendTo(row);
  cell = $('<td/>', {style: 'vertical-align:middle; horizontal-align:left'});
  var id = 'donation_amount_'+donation.entityName;
  id = id.replace(/ /g,'_');
  var dropdown = $('<select />', {id: id, name: id, 'class':'form-control'});
  $('<option/>', {value: '0', text: '$0'}).appendTo(dropdown);
  $('<option/>', {value: '5', text: '$5'}).appendTo(dropdown);
  $('<option/>', {value: '10', text: '$10'}).appendTo(dropdown);
  $('<option/>', {value: '25', text: '$25'}).appendTo(dropdown);
  $('<option/>', {value: '50', text: '$50'}).appendTo(dropdown);
  $('<option/>', {value: 'other', text: 'Other...'}).appendTo(dropdown);
  dropdown.appendTo(cell); 
  cell.appendTo(row);
  row.appendTo(table);
  dropdown.on('change', donationAmountChanged);
  if(donation.thirdParty && $(window).width() < 768) {
    row = $('<tr/>');
    cell = $('<td/>', {colspan: '3'});
    addDiscloseCheckboxToCell(cell, donation);
    cell.appendTo(row);
    row.appendTo(table);
  }
}

function donationsAjaxDone(data) {
  let div = $('#donations');
  if(data.length > 0) {
    let table = $('<table/>', {width: '100%'});
    for(let donation of data) {
      addDonationTypeToTable(table, donation);
    }
    table.appendTo(div);
  } else {
    div.hide();
  }
}

function getTicketCount(type) {
  var values = $('[value="'+type+'"]').filter(':selected');
  return values.length;
}

function requestSubmitDone(data, err) {
  if(err !== null) {
    if(err.jsonResp !== undefined && err.jsonResp.message !== undefined) {
      alert(err.jsonResp.message);
    } else {
      alert('Unable to submit request!');
      console.log(err);
    }
    return;
  }
  if(data.need_minor_confirm !== undefined && (data.need_minor_confirm === '1' || data.need_minor_confirm === true)) {
    $('#minor_dialog').modal('show');
    $('[title]').tooltip('hide');
  } else {
    window.location = 'index.php';
  }
}

function fixupDonationForm() {
  if($(this).val() === 'other') {
    $(this).attr('disabled', 'true');
    var id = $(this).id;
    $(this).removeAttr('name');
    $('#'+id+'_text').attr('name', id);
  }
}

function revertDonationForm() {
  if($(this).val() === 'other') {
    $(this).removeAttr('disabled');
    var id = $(this).id;
    $(this).attr('name', id);
    $('#'+id+'_text').attr('name', id+'_text');
  }
}

function getPosition(string, subString, index) {
  return string.split(subString, index).join(subString).length;
}

function requestDataSubmitted(e) {
  e.preventDefault();
  const form = document.getElementById('request');
  let formData = new FormData(form);
  let data = {};
  for(let pair of formData.entries()) {
    if(pair[0] === 'paymentMethod') {
      if(document.getElementById('paymentTraditional').checked) {
        data['paymentMethod'] = 'traditional';
      }
      if(document.getElementById('paymentCC').checked) {
        data['paymentMethod'] = 'cc';
      }
      continue;
    } else if(pair[0].startsWith('ticket_')) {
      continue;
    } else if(pair[0].startsWith('donation_')) {
      continue;
    }
    data[pair[0]] = pair[1];
  }
  data['tickets'] = [];
  let table = document.getElementById('ticket_table');
  let tbody = table.querySelector('tbody');
  for(let i = 0; i < tbody.rows.length; i++) {
    const row = tbody.rows[i];
    const ticket = {};
    for(let j = 0; j < row.cells.length; j++) {
      const cell = row.cells[j];
      const input = cell.querySelector('input');
      if(input !== null) {
        let split = input.name.split('_');
        ticket[split[1]] = input.value;
      } else {
        const select = cell.querySelector('select');
        if(select !== null) {
          let split = select.name.split('_');
          ticket[split[1]] = select.value;
        }
      }
    }
    data['tickets'].push(ticket);
  }
  const donationTypes = document.getElementById('donations');
  table = donationTypes.querySelector('table');
  data['donations'] = {};
  for(let i = 0; i < table.rows.length; i++) {
    const row = table.rows[i];
    const donation = {};
    let name = '';
    for(let j = 0; j < row.cells.length; j++) {
      const cell = row.cells[j];
      const input = cell.querySelector('input');
      if(input !== null) {
        let split = input.name.split('_');
        split.shift();
        let dataName = split.shift();
        name = split.join(' ');
        if(input.type === 'checkbox') {
          donation[dataName] = input.checked ? true : false;
        } else {
          donation[dataName] = input.value;
        }
      } else {
        const select = cell.querySelector('select');
        if(select !== null) {
          let split = select.name.split('_');
          split.shift();
          let dataName = split.shift();
          name = split.join(' ');
          donation[dataName] = select.value;
        }
      }
    }
    if(donation.amount === '0') {
      continue;
    }
    data['donations'][name] = donation;
  }
  if(Object.keys(data.donations).length === 0) {
    delete data.donations;
  }
  data['request_id'] = document.getElementById('request_id').value;
  data['mail'] = document.getElementById('mail').value;
  console.log(data);
  fetch('api/v1/requests', {
    method: 'POST',
    body: JSON.stringify(data),
    headers: {
      'Content-Type': 'application/json'
    }
  }).then(response => {
    if(response.ok) {
      return response.json();
    }
    throw {jsonResp: response.json(), response: response};
  }).then(respData => requestSubmitDone(respData, null)).catch(err => requestSubmitDone(null, err));
  return false;
}

function resubmitForm(e) {
  var form = $('#request');
  $('<input/>', {type: 'hidden', name: 'minor_confirm', value: '1'}).appendTo(form);
  requestDataSubmitted(e); 
}

function minorAffirmClicked() {
  $('#minor_dialog_continue').removeAttr('disabled');
  $('#minor_dialog_continue').on('click', resubmitForm);
}

function currentRequestDone(request) {
  if(request === null) {
    ticketSystem.getTicketRequestIdForCurrentUser(requestIdDone);
  } else {
    let tbody = document.querySelector('#ticket_table tbody');
    for(var propertyName in request) {
      switch(propertyName) {
        case 'tickets':
          for(let ticket of request.tickets) {
            addRowToTable(tbody, ticket.first, ticket.last, ticket.type, tableRow++);
          }
          break;
        case 'donations':
          if(request[`${propertyName}`] === null) {
            continue;
          }
          for(let donationName in request.donations) {
            var id = 'donation_amount_'+donationName.replace(/ /g,'_');
            var dropdown = $('#'+id);
            let donation = request.donations[donationName];
            let amount = donation.amount;
            dropdown.val(amount);
            if(dropdown.val() === null || dropdown.val() === undefined) {
              dropdown.val('other');
              var box = $('<input/>', {name: id, id: id+'_text', type: 'text', value: amount});
              box.appendTo(dropdown.parent());
            }
            if(donation.disclose !== undefined && donation.disclose === '1') {
              $('#donation_disclose_'+donationName).prop('checked', true);
            }
          }
          break;
        default:
          $('#'+propertyName).val(request[`${propertyName}`]);
          break;
      }
    }
  }
}

function requestIdDone(data, err) {
  if(err !== null) {
    alert('Unable to obtain request ID!');
    return; 
  }
  $('#request_id').val(data);
  if(browser_supports_cors()) {
    $.ajax({
      url: window.profilesUrl+'/api/v1/users/me',
      type: 'get',
      dataType: 'json',
      xhrFields: {withCredentials: true},
      success: requestAjaxDone});
  } else {
    add_notification($('#request_set'), 'Your browser is out of date. Due to this some data may be missing from your request. Please make sure it is complete');
  }
}

function initRequest() {
  let requestId  = getParameterByName('request_id');
  let year       = getParameterByName('year');
  ticketSystem.getRequest(currentRequestDone, requestId, year);
  let request = document.getElementById('request').dataset.request;
  document.getElementById('add_new_ticket').addEventListener('click', addNewTicket);
  if(request !== undefined) {
    let tbody = document.querySelector('#ticket_table tbody');
    for(let ticket of request.tickets) {
      addRowToTable(tbody, ticket.first, ticket.last, ticket.type.typeCode, tableRow++);
    }
    for(let donation of request.donations) {
      let id = 'donation_amount_'+donation.type;
      let dropdown = document.getElementById(id);
      dropdown.value = donation.amount;
      if(dropdown.value === null) {
        dropdown.value = 'other';
        let box = document.createElement('input');
        box.name = id;
        box.id = id + '_text';
        box.type = 'text';
        box.value = donation.amount;
        dropdown.parentNode.appendChild(box);
      }
      if(donation.disclose !== undefined && donation.disclose === '1') {
        document.getElementById('donation_disclose_' + donation.type.entityName).checked = true;
      }
    }
  }
  document.getElementById('request').addEventListener('submit', requestDataSubmitted);
}

function findElementInPreviousCell(currentElement) {
  // Get the row containing the current cell
  const currentCell = currentElement.closest('td');

  // Get the previous cell in the row
  const previousCell = currentCell.previousElementSibling;

  // Check if the previous cell exists
  if (previousCell) {
    // Find the element within the previous cell
    return previousCell.querySelector('select');
  }
  console.log('No previous cell found.');
}

function showTraditionalPayment() {
  document.getElementById('creditPaymentDetails').setAttribute('hidden', 'true');
  document.getElementById('ticket_table').removeAttribute('hidden');
  document.getElementById('traditionalPaymentDetails').removeAttribute('hidden');
  document.getElementById('submit').removeAttribute('hidden');
  document.querySelectorAll("[id^='ticketCost']").forEach((element) => {
    let type = element.id.replace('ticketCost', '');
    element.value = document.getElementById('ticketCashCost'+type).value;
  });
  document.querySelectorAll("[name='ticket_cost']").forEach((element) => {
    let typeSelect = findElementInPreviousCell(element);
    let type = typeSelect.value;
    let costElem = document.getElementById('ticketCost'+type);
    if(costElem !== null) {
      element.value = '$'+costElem.value;
    }
  });
  calculateTicketSubtotal();
}

function showCreditPayment() {
  document.getElementById('traditionalPaymentDetails').setAttribute('hidden', 'true');
  document.getElementById('ticket_table').removeAttribute('hidden');
  document.getElementById('creditPaymentDetails').removeAttribute('hidden');
  document.getElementById('submit').removeAttribute('hidden');
  document.querySelectorAll("[id^='ticketCost']").forEach((element) => {
    let type = element.id.replace('ticketCost', '');
    element.value = document.getElementById('ticketCreditCost'+type).value;
  });
  document.querySelectorAll("[name='ticket_cost']").forEach((element) => {
    let typeSelect = findElementInPreviousCell(element);
    let type = typeSelect.value;
    let costElem = document.getElementById('ticketCost'+type);
    if(costElem !== null) {
      element.value = '$'+costElem.value;
    }
  });
  calculateTicketSubtotal();
}

async function fetchMultipleAPIs(urls) {
  try {
    const responses = await Promise.all(urls.map(url => fetch(url)));
    const data = await Promise.all(responses.map(response => response.json()));
    return data;
  } catch (error) {
    console.error('Error fetching data:', error);
    throw error; // Optionally rethrow the error for further handling
  }
}

function startPopulateForm() {
  let promise = fetchMultipleAPIs(['api/v1/globals/constraints', 'api/v1/globals/donation_types']);
  promise.then((data) => {
    constraintsAjaxDone(data[0]);
    donationsAjaxDone(data[1]);
    initRequest();
  }).catch((error) => {
    console.error(error);
  });
}

function initInThread() {
  if(window.TicketSystem === undefined) {
    setTimeout(initInThread, 10);
    return;
  }
  ticketSystem = new TicketSystem('api/v1');
  if(document.getElementById('request_id') !== null) {
    setTimeout(startPopulateForm, 0);
    const toolTipList = document.querySelectorAll('[title]');
    toolTipList.forEach((toolTip) => {
      new bootstrap.Tooltip(toolTip);
    });
  } else {
    // Retry forever until the page is painted
    setTimeout(initInThread, 10);
    return;
  }
  let checkbox = document.getElementById('paymentTraditional');
  if(checkbox === null) {
    // Retry forever until the page is painted
    setTimeout(initInThread, 10);
    return;
  }
  checkbox.addEventListener('change', showTraditionalPayment);
  checkbox = document.getElementById('paymentCC');
  checkbox.addEventListener('change', showCreditPayment);
}

window.onload = initInThread;
