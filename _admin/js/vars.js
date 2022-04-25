/* global $, create_modal */
/* exported knownChange */
function addRowToTable(tbody, name, value) {
  var row = $('<tr/>');
  var cell = $('<td/>');
  if(name !== '_blank') {
    cell.html('<button type="button" class="btn btn-link btn-sm" id="delete_'+name+'"><span class="fa fa-times"></span></button>');
  }
  cell.appendTo(row);
  cell = $('<td/>');
  if(name === '_blank') {
    cell.html('<input type="text" id="name__blank" value="" placeholder="Variable Name"/>');
  } else {
    cell.html(name);
  }
  cell.appendTo(row);
  cell = $('<td/>');
  cell.html('<div class="input-group"><input class="form-control" type="text" id="text_'+name+'" value="'+value+'"/><span class="input-group-btn"><button type="button" class="btn btn-default" id="change_'+name+'"><span class="fa fa-check"></span></button></span></div>');
  cell.appendTo(row);
  row.appendTo(tbody);
}

function variableSetDone(jqXHR) {
  if(jqXHR.status === 200) {
    $('#raw tbody').empty();
    initVars();
  } else {
    console.log(jqXHR);
    alert('Unable to set variable');
  }
}

function deleteVar() {
  var button = $(this);
  var varName = button.attr('id').substr(7);
  $.ajax({
    url: '../api/v1/globals/vars/'+varName,
    type: 'delete',
    dataType: 'json',
    complete: variableSetDone});
}

function changeVar() {
  var button = $(this);
  var varName = button.attr('id').substr(7);
  var varValue = $('#text_'+varName).val();
  var method = 'patch';
  if(varName === '_blank') {
    varName = $('#name__blank').val();
    if(varName.length < 1) {
      alert('Variable name must be at least one character long');
      return;
    }
    method = 'post';
  }
  $.ajax({
    url: '../api/v1/globals/vars/'+varName,
    contentType: 'application/json',
    data: JSON.stringify(varValue),
    processData: false,
    type: method,
    dataType: 'json',
    complete: variableSetDone});
}

function unsetTestMode() {
  $.ajax({
    url: '../api/v1/globals/vars/test_mode',
    contentType: 'application/json',
    data: JSON.stringify(0),
    processData: false,
    type: 'patch',
    dataType: 'json',
    complete: variableSetDone});
}

function knownChange(control) {
  var jq = $(control);
  var varValue = '';
  var varName = '';
  if(jq.is('button')) {
    varName = jq.attr('for');
    varValue = $('#'+varName).val(); 
  } else {
    varName = jq.attr('name');
    varValue = jq.val();
  }
  if(varName === 'test_mode' && varValue === '0') {
    var html = '<strong>Warning!</strong> Unsetting Test mode will delete all test entries are you sure you want to continue?';
    var modal = create_modal('Test Mode', html, [{text:'Yes', method: unsetTestMode, close: true}, {text:'No', close: true}]);
    modal.modal();
    return;
  }
  $.ajax({
    url: '../api/v1/globals/vars/'+varName,
    contentType: 'application/json',
    data: JSON.stringify(varValue),
    processData: false,
    type: 'patch',
    dataType: 'json',
    complete: variableSetDone});
}

function populateRawTable(vars) {
  var tbody = $('#raw tbody');
  for(let variable in vars) {
    addRowToTable(tbody, variable.name, variable.value);
  }
  //Add empty row for adding
  addRowToTable(tbody, '_blank', '');
  $('[id^=delete_]').on('click', deleteVar);
  $('[id^=change_]').on('click', changeVar);
}

function populateKnownForm(vars) {
  for(let variable in vars) {
    let control = $('#'+variable.name);
    if(control.length > 0) {
      control.val(variable.value);
    }
  }
}

function variablesDone(jqXHR) {
  if(jqXHR.status !== 200)   {
    alert('Error obtaining variables!');
    return;
  }
  populateRawTable(jqXHR.responseJSON);
  populateKnownForm(jqXHR.responseJSON);
}

function initVars() {
  $('#tabs a:first').tab('show');
  $.ajax({
    url: '../api/v1/globals/vars',
    type: 'get',
    dataType: 'json',
    complete: variablesDone});
}

$(initVars);
