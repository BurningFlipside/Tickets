/* global $, browser_supports_input_type */
/* exported claimTicket, transfer */
function changeNameDone(jqXHR) {
  var data = jqXHR.responseJSON;
  if(data !== undefined && data.error !== undefined) {
    alert(data.error);
    return;
  }
  window.location = '/tickets/index.php';
}

function transferDone(data) {
  if(data.error !== undefined) {
    alert(data.error);
    return;
  }
  window.location = '/tickets/index.php?show_transfer_info=1';
}

function transfer() {
  var obj = {};
  obj.email = $('#email').val();
  $.ajax({
    url: 'api/v1/tickets/'+encodeURIComponent($('#hash').val())+'/Actions/Ticket.Transfer',
    contentType: 'application/json',
    type: 'post',
    data: JSON.stringify(obj),
    processData: false,
    dataType: 'json',
    success: transferDone});
}

function claimTicket() {
  var obj = {};
  obj.firstName = $('#firstName').val();
  obj.lastName  = $('#lastName').val();
  $.ajax({
    url: 'api/v1/tickets/'+encodeURIComponent($('#hash').val())+'/Actions/Ticket.Claim',
    contentType: 'application/json',
    type: 'post',
    data: JSON.stringify(obj),
    processData: false,
    dataType: 'json',
    complete: changeNameDone});
}

function initPage() {
  $('[title]').tooltip();
  if(browser_supports_input_type('email')) {
    $('#email').attr({type: 'email'});
  }
}

$(initPage);
