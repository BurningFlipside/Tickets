/* global $, create_modal, bootstrap */
function doCostCalc(label) {
  let calcField = document.getElementById('cost_calc_'+label);
  let costField = document.getElementById('cost_'+label);
  // Square fees are 3.3% + 30 cents
  // Sales tax is 8.25%
  calcField.innerHTML = '$'+(costField.value * 1.033 * 1.0825 + 0.30).toFixed(2);
}

function addTicketType(ticketType) {
  let nav = document.getElementById('ticket_type_nav');
  let navItem = document.createElement('li');
  navItem.setAttribute('class', 'nav-item');
  let link = document.createElement('a');
  Object.assign(link, {'className': 'nav-link', 'href': '#'+ticketType.typeCode, 'role': 'tab'});
  link.setAttribute('data-bs-toggle', 'tab');
  link.innerHTML = ticketType.description;
  navItem.appendChild(link);
  if(ticketType.typeCode !== 'NEW') {
    let button = document.createElement('button');
    Object.assign(button, {'className': 'btn btn-link', 'id': 'delete_'+ticketType.typeCode, 'title': 'Delete Ticket Type'});
    button.dataset.bsToggle = 'tooltip';
    button.dataset.placement = 'top';
    button.addEventListener('click', deleteTicketType);
    let span = document.createElement('span');
    span.setAttribute('aria-hidden', 'true');
    span.innerHTML = '&times;';
    button.appendChild(span);
    span = document.createElement('span');
    span.setAttribute('class', 'sr-only');
    span.innerHTML = 'Delete';
    button.appendChild(span);
    link.innerHTML += '&nbsp;';
    link.appendChild(button);
  }
  nav.appendChild(navItem);

  let content = $('#ticket_type_content');
  var contentItem = $('<div/>', {'class':'tab-pane fade', id: ticketType.typeCode});
  var form = $('<form/>', {'class':'form-horizontal', 'role':'form'});
  var div = $('<div/>', {'class':'row'});
  var label = $('<label/>', {'for': 'type_code_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Type Code');
  var input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'type_code_'+ticketType.typeCode, 'required': 'true'});
  if(ticketType.typeCode !== 'NEW') {
    input.attr('value', ticketType.typeCode);
  }
  label.appendTo(div);
  var innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);

  label = $('<label/>', {'for': 'desc_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Description');
  input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'desc_'+ticketType.typeCode, 'required': 'true'});
  if(ticketType.typeCode !== 'NEW') {
    input.attr('value', ticketType.description);
  }
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);

  label = $('<label/>', {'for': 'cost_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Cost ($)');
  input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'cost_'+ticketType.typeCode, 'value': ticketType.cost, 'required': 'true'});
  input.change(function () {
    doCostCalc(ticketType.typeCode);
  });
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-6'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-4', id: 'cost_calc_'+ticketType.typeCode}).appendTo(div);
  innerDiv.appendTo(div);

  label = $('<label/>', {'for': 'credit_cost_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Credit Cost ($)');
  input = $('<input/>', {'type': 'text', 'class': 'form-control', 'id': 'credit_cost_'+ticketType.typeCode, 'value': ticketType.squareCost, 'required': 'true'});
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);
   
  label = $('<label/>', {'for': 'max_per_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Max of this type per request');
  input = $('<input/>', {'type': 'number', 'class': 'form-control', 'id': 'max_per_'+ticketType.typeCode, 'value': ticketType.max_per_request, 'required': 'true'});
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div);

  div.append('<div class="clearfix visible-sm visible-md visible-lg"></div>');

  label = $('<label/>', {'for': 'minor_'+ticketType.typeCode, 'class': 'col-sm-2 control-label'}).html('Is this request type a minor?');
  input = $('<input/>', {'type': 'checkbox', 'class': 'form-check-input', 'id': 'minor_'+ticketType.typeCode, 'data-on-text': 'Yes', 'data-off-text': 'No'});
  if(ticketType.is_minor === '1' || ticketType.is_minor === 1) {
    input.attr('checked', true);
  }
  label.appendTo(div);
  innerDiv = $('<div/>', {'class': 'col-sm-10'}).appendTo(div);
  input.appendTo(innerDiv);
  innerDiv.appendTo(div); 

  div.appendTo(form);

  div = $('<div/>', {'class':'row'});
  let button = $('<button/>', {'class': 'btn btn-primary', id: 'commit_'+ticketType.typeCode, 'title': 'Change Ticket Type', 'data-toggle': 'tooltip', 'data-placement': 'top'});
  button.html('Commit Changes');
  button.appendTo(div);
  div.appendTo(form);

  form.appendTo(contentItem);
  contentItem.appendTo(content);
  doCostCalc(ticketType.typeCode);
}

function tabShown(e) {
  console.log(e);
  $('[id^=delete_]').hide();
  var tab;
  if(e === undefined) {
    tab = $('#ticket_type_nav .active');
  } else {
    tab = $(e.currentTarget);
  }
  tab.find('[id^=delete_]').show();
}

function actionFailed(jqXHR) {
  console.log(jqXHR);
}

function reallyDelete(e) {
  var button = $(e.currentTarget);
  var id = button.data('data');
  $.ajax({
    url: '../api/v1/globals/ticket_types/'+id,
    type: 'DELETE',
    dataType: 'json',
    success: initTicketType,
    error: actionFailed});
}

function deleteTicketType(e) {
  var button = $(e.currentTarget);
  var id = button.attr('id').split('_');
  var type = id[1];
  var desc = $('#desc_'+type).val();

  var yes = {'close': true, 'text': 'Yes', 'method': reallyDelete, 'data': type};
  var no = {'close': true, 'text': 'No'};
  var buttons = [yes, no];
  var modal = create_modal('Are you sure?', 'Are you sure you want to delete the '+desc+' ticket type? This operation cannot be undone.', buttons);
  modal.appendTo(document.body);
  modal.modal();
  e.preventDefault();
}

function commitTicketType(e) {
  var button = $(e.currentTarget);
  var id = button.attr('id').split('_');
  var type = id[1];
  var controls = $('[id$=_'+type+'].form-control');
  var data = {};
  for(let _control of controls) {
    var control = $(_control);
    var controlName = control.attr('id');
    controlName = controlName.substring(0, controlName.lastIndexOf('_'));
    if(controlName === 'type_code') {
      controlName = 'typeCode';
    } else if(controlName === 'desc') {
      controlName = 'description';
    } else if(controlName === 'credit_cost') {
      controlName = 'squareCost';
    } else if(controlName === 'max_per') {
      controlName = 'max_per_request';
    }
    if(control[0].type === 'checkbox') {
      data[`${controlName}`] = control[0].checked;
    } else {
      data[`${controlName}`] = control.val();
    }
  }
  e.preventDefault();
  $.ajax({
    url: '../api/v1/globals/ticket_types/'+type,
    contentType: 'application/json',
    data: JSON.stringify(data),
    type: 'PATCH',
    processData: false,
    dataType: 'json',
    success: initTicketType,
    error: actionFailed});
}

function ticketTypesDone(data) {
  for(let type of data) {
    addTicketType(type);
  }
  let newType = {
    typeCode: 'NEW',
    description: 'New Type',
    cost: '',
    max_per_request: '', // eslint-disable-line camelcase
    is_minor: '' // eslint-disable-line camelcase
  };
  addTicketType(newType);
  let ticketTypeNav = document.getElementById('ticket_type_nav');
  let ticketTypeContent = document.getElementById('ticket_type_content');
  ticketTypeNav.firstChild.firstChild.setAttribute('class', 'nav-link active');
  ticketTypeContent.firstChild.setAttribute('class', 'tab-pane fade show active');
  document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', tabShown);
  });
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
  tabShown();
  $('[id^=commit_]').on('click', commitTicketType);
}

function initTicketType() {
  document.getElementById('ticket_type_nav').innerHTML = '';
  fetch('../api/v1/globals/ticket_types').then(response => {
    if(!response.ok) {
      alert('Ticket Types not found!');
      return;
    }
    response.json().then(data => {
      ticketTypesDone(data);
    });
  });
}

window.onload = initTicketType;
