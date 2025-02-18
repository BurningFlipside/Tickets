/* global $, create_modal, bootbox */
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
  console.log(control);
  var jq = $(control);
  var varValue = '';
  var varName = '';
  if(jq.is('button')) {
    varName = jq.attr('for');
    varValue = $('#'+varName).val(); 
  } else {
    varName = jq.attr('name');
    varValue = jq.val();
    console.log('Name: '+varName+' Value: '+varValue);
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
  for(let variable of vars) {
    addRowToTable(tbody, variable.name, variable.value);
  }
  //Add empty row for adding
  addRowToTable(tbody, '_blank', '');
  $('[id^=delete_]').on('click', deleteVar);
  $('[id^=change_]').on('click', changeVar);
}

function populateKnownForm(vars) {
  for(let variable of vars) {
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

function verifySpreadsheet(spreadsheetID) {
  fetch('../api/v1/google/spreadsheets/'+spreadsheetID+'/isProblematicPersonsFormat').then((response) => {
    if(response.status === 200) {
      let problematicSpreadsheetID = document.getElementById('problematicSpreadsheetID');
      problematicSpreadsheetID.value = spreadsheetID;
      knownChange(problematicSpreadsheetID);
      return;
    }
    if(response.status === 404) {
      alert('Spreadsheet is not present!');
      return;
    }
    if(response.status === 400) {
      bootbox.confirm('Spreadsheet does not have the correct format. Would you like to make this sheet the correct format? <b>NOTE: All data in the spreadsheet will be lost!', (result) => {
        if(result === true) {
          fetch('../api/v1/google/spreadsheets/'+spreadsheetID+'/Actions.MakeProblematicPersonSpreadsheet', {method: 'POST'}).then((actionResponse) => {
            if(actionResponse.status === 200) {
              let problematicSpreadsheetID = document.getElementById('problematicSpreadsheetID');
              problematicSpreadsheetID.value = spreadsheetID;
              knownChange(problematicSpreadsheetID);
              return;
            }
            alert('Failed to make the spreadsheet the correct format!');
          });
        }
      });
      return;
    }
  });
}

function lookupSpreadsheet() {
  fetch('../api/v1/google/spreadsheets').then((response) => {
    if(response.status !== 200) {
      console.log(response);
      alert('Failed to get spreadsheets!');
      return;
    }
    return response.json();
  }).then((data) => {
    let message = document.createElement('div');
    let info = document.createElement('div');
    info.classList.add('alert', 'alert-info');
    info.textContent = 'Spreadsheets must be shared with the service account "ticketsystemservice@burning-flipside-ticket-system.iam.gserviceaccount.com" otherwise it will not show up in the list.';
    message.appendChild(info);
    let text = document.createElement('p');
    text.textContent = 'Select the spreadsheet that contains the Known Problematic Persons list.';
    message.appendChild(text);
    let select = document.createElement('select');
    select.classList.add('form-control');
    select.id = 'spreadsheetID';
    for(let sheet of data) {
      let option = document.createElement('option');
      option.value = sheet.id;
      option.textContent = sheet.name;
      select.appendChild(option);
    }
    message.appendChild(select);
    bootbox.dialog({
      title: 'Known Problematic Persons Spreadsheet Lookup',
      message: message,
      buttons: {
        cancel: {
          label: 'Cancel',
          className: 'btn-default',
        },
        confirm: {
          label: 'Link',
          className: 'btn-primary',
          callback: () => {
            verifySpreadsheet(select.value);
          },
        }
      }
    });
  });
}

function initVars() {
  $('#tabs a:first').tab('show');
  $.ajax({
    url: '../api/v1/globals/vars',
    type: 'get',
    dataType: 'json',
    complete: variablesDone});
  let problematicLookupButton = document.getElementById('knownProblematicSpreadsheetID');
  if(problematicLookupButton) {
    problematicLookupButton.addEventListener('click', lookupSpreadsheet);
  }
}

window.addEventListener('load', initVars);
