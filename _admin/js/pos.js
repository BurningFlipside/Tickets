/*global $, add_notification, getParameterByName, bootbox*/
/*exported finalPost, nextTab, prevTab */
var selectedPool = 0;
var tickets = null;

function tabChanged(e) {
  var tabIndex = $(e.target).parent().index();
  if(tabIndex === 0) {
    $('.previous').attr('class', 'previous disabled');
  } else {
    $('.previous').attr('class', 'previous');
  }
  var lastIndex = $(e.target).parent().siblings().last().index();
  if(tabIndex >= lastIndex) {
    $('.next').html('<a class="page-link" href="#" onclick="finalPost(event)">Sell</a>');
  } else {
    $('.next').html('<a class="page-link" href="#" onclick="nextTab(event)">Next <span aria-hidden="true">&rarr;</span></a>');
  }
}

function validateCurrent() {
  var tab = $('div.tab-pane.active');
  switch(tab.attr('id')) {
    case 'tab0':
      var qtyControls = $('[name^=Qty]');
      var cost = 0;
      var found = false;
      let posType = $('#posType').val();
      for(let qtyControl of qtyControls) {
        var control = $(qtyControl);
        var qty = control.val();
        if(qty.length > 0) {
          qty = parseInt(qty, 10);
          found = true;
          if(posType === 'square') {
	    cost += qty*parseInt(control.data('squarecost'),10);
          } else {
            cost += qty*parseInt(control.data('cost'),10);
	  }
          if(qty > parseInt(control.data('max'),10)) {
            alert('Not enough tickets to fullfil request!');
            return false;
          }
        }
      }
      if(!found) {
        alert('No tickets ordered!');
      }
      $('#total').val('$'+cost);
      return found;
    case 'tab1':
      var email = $('#email').val();
      if(email.length === 0) {
        alert('Email is required!');
        return false;
      }
      $('#confirm_email').val(email);
      return true;
    default:
      return true;
  }
}

function finalPostDone(jqXHR) {
  $('.next').attr('disabled', false);
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Failed to sell ticket! '+jqXHR.status);
    Sentry.configureScope(function(scope) {
  	  scope.setExtra("server_data", jqXHR);
      Sentry.captureException(new Error('Failed to sell ticket!'));
	  });
    return;
  }
  let data = jqXHR.responseJSON;
  if(data === true) {
    location.reload();
  } else {
    alert(data);
    console.log(data);
  }
}

function didSquareSale(jqXHR) {
  $('.next').attr('disabled', false);
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Failed to sell ticket! '+jqXHR.status);
    Sentry.configureScope(function(scope) {
  	  scope.setExtra("server_data", jqXHR);
      Sentry.captureException(new Error('Failed to sell ticket!'));
	  });
    return;
  }
  let data = jqXHR.responseJSON;
  if(data.uri) {
    let control = $('#poswizard');
    control.empty();
    add_notification(control, 'Ticket(s) were sold successfully via Square! An email has been sent to the purchaser to complete the sale. Or you can click <a class="alert-link" href="'+data.uri+'">here</a> or let them scan the QR code below.');
    control.append('<div id="qrCode"></div>');
    new QRCode(document.getElementById("qrCode"), data.uri);
    return;
  }
  alert(data);
  console.log(data);
}

function finalPost() {
  if(validateCurrent()) {
    $('.next').attr('disabled', true);
    var id = getParameterByName('id');
    var obj = {};
    obj.pool = selectedPool;
    obj.email = $('#email').val();
    obj.tickets = {};
    var qtys = $('[name^=Qty]');
    for(let qty of qtys) {
      var control = $(qty);
      var name = control.attr('name').substr(3);
      obj.tickets[`${name}`] = control.val()*1;
    }
    var message = $('#message').val();
    if(message !== undefined && message.trim().length > 0) {
      obj.message = $('#message').val();
    }
    var firstName = $('#firstName').val();
    if(firstName !== undefined && firstName.trim().length > 0) {
      obj.firstName = firstName;
    }
    var lastName = $('#lastName').val();
    if(lastName !== undefined && lastName.trim().length > 0) {
      obj.lastName = lastName;
    }
    let posType = $('#posType').val();
    obj.posType = posType;
    let completeFunc = finalPostDone;
    if(obj.posType === 'square') {
      completeFunc = didSquareSale;
    }
    fetch('../api/v1/google/problematicActors/Actions/Test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        email: obj.email,
        first: obj.firstName,
        last: obj.lastName
      })
    }).then((response) => {
      if(response.status === 200) {
        submit(id, obj, completeFunc);
        return;
      }
      if(response.status === 451) {
        alert('This sale is being blocked because the purchaser is on the problematic actors list. Please check the list.');
        return;
      }
      if(response.status === 409) {
        bootbox.confirm('<i class="fas fa-exclamation-triangle" style="color: red;"></i> There is someone with the same name on the problematic actors list. Please check before continuing. Are you sure you want to continue?', (result) => {
          if(result === true) {
            submit(id, obj, completeFunc);
          }
        });
        return;
      }
      console.error('Unknown response from verification, let it go through.', response);
      submit(id, obj, completeFunc);
    });
  }
}

function submit(id, obj, completeFunc) {
  var dataStr = JSON.stringify(obj);
  if(id !== null) {
    $.ajax({
      url: '/tickets/api/v1/tickets/'+id+'/Actions/Ticket.Sell',
      contentType: 'application/json',
      type: 'POST',
      dataType: 'json',
      processData: false,
      data: dataStr,
      complete: completeFunc});
    return;
  }
  $.ajax({
    url: '/tickets/api/v1/ticket/pos/sell',
    contentType: 'application/json',
    type: 'POST',
    dataType: 'json',
    processData: false,
    data: dataStr,
    complete: completeFunc});
}

function prevTab() {
  $('li.nav-item .active').parent().prevAll(":not('.disabled')").first().find('a').tab('show');
}

function nextTab() {
  if(validateCurrent()) {
    $('li.nav-item .active').parent().next().find('a').tab('show');
  }
}

function getTicketTypesDone(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to get ticket pools!');
    return;
  }
  var data = jqXHR.responseJSON;
  var tbody = $('#ticket_select tbody');
  var id = getParameterByName('id');
  if(tbody.length === 0) {
    return;
  }
  for(let type of data) {
    tbody.append('<tr><td><input class="form-control" type="number" name="Qty'+type.typeCode+'" data-type="'+type.typeCode+'" data-cost="'+type.cost+'" data-squarecost="'+type.squareCost+'" data-max="0" disabled/></td><td>'+type.description+'</td></tr>');
  }
  if(id === null) {
    $.ajax({
      url: '/tickets/api/v1/tickets/pos?$filter=sold eq 0 and transferInProgress eq 0',
      type: 'GET',
      dataType: 'json',
      complete: getTicketsDone
    });
  } else {
    add_notification($('#poswizard'), 
        'You have used a link which will see a single specific discretionary ticket. If you did not mean to do this click <a href="pos.php" class="alert-link">here</a>.');
    $.ajax({
      url: '/tickets/api/v1/ticket/'+id,
      type: 'GET',
      dataType: 'json',
      success: getTicketDone
    });
  }
}

function updateControl(index, element) {
  if(tickets === null) {
    return;
  }
  var control = $(element);
  var type = control.data('type');
  if(tickets[`${selectedPool}`] === undefined || tickets[`${selectedPool}`][`${type}`] === undefined) {
    control.attr('disabled', true);
    control.attr('max', 0);
    control.attr('min', 0);
  } else {
    control.removeAttr('disabled');
    control.attr('max', tickets[`${selectedPool}`][`${type}`].length);
    control.attr('min', 0);
    control.data('max', tickets[`${selectedPool}`][`${type}`].length);
    control.attr('data-max', tickets[`${selectedPool}`][`${type}`].length);
  }
}

function poolChanged(control) {
  selectedPool = $(control).val()*1;
  var inputs = $('[name^=Qty]');
  inputs.each(updateControl);
}

function getTicketsDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain tickets! '+jqXHR.status);
    return;
  }
  var data = jqXHR.responseJSON;
  if(data.length === 0 || data === false) {
    let control = $('#poswizard');
    control.empty();
    add_notification(control, 'You have no more tickets to sell!');
    return;
  }
  for(let ticket of data) {
    let control = $('[name=Qty'+ticket.type+']');
    if(control.length > 0) {
      control.removeAttr('disabled');
      let max = parseInt(control.data('max'),10)+1;
      control.data('max', max);
      control.attr('data-max', max);
    }
    if(tickets === null) {
      tickets = [];
    }
    let poolId = ticket.pool_id*1;
    let ticketType = ticket.type;
    if(tickets[`${poolId}`] === undefined) {
      tickets[`${poolId}`] = {};
    }
    if(tickets[`${poolId}`][`${ticketType}`] === undefined) {
      tickets[`${poolId}`][`${ticketType}`] = [];
    }
    tickets[`${poolId}`][`${ticketType}`].push(ticket);
  }
  if(tickets !== null) {
    var options = $('#pool option');
    for(let option of options) {
      if(tickets[option.value*1] === undefined) {
        $(option).prop('disabled', true);
      }
    }
    poolChanged($('#pool')[0]);
  }
}

function getTicketDone(data) {
  if(data === false) {
    let wizard = $('#poswizard');
    wizard.empty();
    add_notification(wizard, 'Ticket does not exist!');
    return;
  }
  let control = $('[name=Qty'+data.type+']');
  if(control.length > 0) {
    //control.removeAttr('disabled');
    var max = parseInt(control.data('max'),10)+1;
    control.data('max', max);
    control.attr('data-max', max);
    control.attr('max', 1);
    control.attr('min', 1);
    control.val(1);
  }
  selectedPool = data.pool_id;
  $('#pool').val(data.pool_id);
  $('#pool').prop('disabled', true);
}

function getTicketTypes() {
  $.ajax({
    url: '/tickets/api/v1/ticket/types',
    type: 'GET',
    dataType: 'json',
    complete: getTicketTypesDone
  });
  $('.previous').attr('class', 'previous disabled');
  $('a[data-toggle="tab"]').on('shown.bs.tab', tabChanged);
}

function getPoolsDone(jqXHR) {
  if(jqXHR.status === 401) {
    //Don't need extra popups when they aren't logged in
    return;
  }
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to get ticket pools!');
    return;
  }
  var poolSelect = $('#pool');
  for(let pool of jqXHR.responseJSON) {
    poolSelect.append('<option value="'+pool.pool_id+'">'+pool.pool_name+'</option>');
  }
}

function getPools() {
  $.ajax({
    url: '../api/v1/pools/me',
    type: 'GET',
    dataType: 'json',
    complete: getPoolsDone
  });
}

function gotPaymentTypes(jqXHR) {
  if(jqXHR.status === 401) {
    //Don't need extra popups when they aren't logged in
    return;
  }
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to get payment types!');
    return;
  }
  let posTypesSelect = $('#posType');
  for(let type in jqXHR.responseJSON) {
    
    posTypesSelect.append('<option value="'+type+'">'+jqXHR.responseJSON[`${type}`]+'</option>');
  }
}

function getPaymentTypes() {
  $.ajax({
    url: '../api/v1/globals/posTypes',
    type: 'GET',
    dataType: 'json',
    complete: gotPaymentTypes
  });
}

function initPosPage() {
  getPools();
  getPaymentTypes();
  getTicketTypes();
}

$(initPosPage);
