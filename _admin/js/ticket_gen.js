/*global $*/
/*exported genTickets*/
function generationDone(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Failed to generate tickets');
    return;
  }
  let data = jqXHR.responseJSON;
  var str = 'Created '+data.passed+' tickets\n';
  if(data.failed > 0) {
    str += 'Failed to create '+data.failed+' tickets';
  }
  alert(str);
  location.reload();
}

function genTickets() {
  var totalCount = 0;
  var elements = $('#additional [type="number"]');
  var obj = $('#gen_form').serializeObject();
  obj.types = {};
  for(let element of elements) {
    totalCount += 1*$(element).val();
    obj.types[element.id] = 1*$(element).val();
  }
  if(totalCount === 0) {
    alert('No additional tickets created!');
    return false;
  }
  $.ajax({
    url: '../api/v1/tickets/Actions/GenerateTickets',
    contentType: 'application/json',
    type: 'post',
    data: JSON.stringify(obj),
    dataType: 'json',
    processData: false,
    complete: generationDone});
  return false;
}

function gotTicketType(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to get ticket count for type '+this+'!');
    return;
  }
  var field = $('#'+this+'Current');
  field.html(jqXHR.responseJSON['@odata.count']);
}

function gotTicketTypes(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to get ticket types!');
    return;
  }
  var current = $('#current tbody');
  var additional = $('#additional tbody');
  for(let type of jqXHR.responseJSON) {
    current.append('<tr><td>'+type.description+'</td><td id="'+type.typeCode+'Current"></td></tr>');
    additional.append('<tr><td>'+type.description+'</td><td><input type="number" id="'+type.typeCode+'" value="0"/></td></tr>');
    $.ajax({
      url: '../api/v1/tickets?$filter=year%20eq%20current%20and%20type%20eq%20%27'+type.typeCode+'%27&$count=true&$select=@odata.count',
      type: 'get',
      context: type.typeCode,
      complete: gotTicketType});
  }
}

function initPage() {
  $.ajax({
    url: '../api/v1/tickets/types',
    type: 'get',
    complete: gotTicketTypes});
}

$(initPage);
