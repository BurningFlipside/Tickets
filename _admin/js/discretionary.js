/*global $*/
/*exported assignTickets, getCSV*/
var disc;

function drawTable() {
  var table = $('#discretionary tbody');
  for(var mail in disc) {
    var row = $('<tr>');
    var count = Object.keys(disc[`${mail}`]).length;
    if(disc[`${mail}`].Name !== undefined) {
      count--;
      row.append('<td rowspan="'+count+'">'+disc[`${mail}`].Name+'</td>');
      delete disc[`${mail}`].Name;
    } else {
      row.append('<td rowspan="'+count+'">'+mail+'</td>');
    }
    for(var type in disc[`${mail}`]) {
      row.append('<td>'+type+'</td><td>'+disc[`${mail}`][`${type}`]['unsold']+'</td><td>'+disc[`${mail}`][`${type}`]['sold']+'</td>');
      table.append(row);
      row = $('<tr>');
    }
  }
}

function gotUsers() {
  for(let arg of arguments) {
    if(Array.isArray(arg)) {
      let user = arg[0];
      if(Array.isArray(user)) {
        user = user[0];
      }
      if(user === false) {
        //API call failed... skip!
        continue;
      }
      disc[user.mail]['Name'] = user.displayName;
    }
  }
  drawTable();
}

function gotDiscretionaryTickets(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to obtain tickets!');
    return;
  }
  var data = jqXHR.responseJSON;
  disc = {};
  var calls = [];
  for(let ticket of data) {
    let email = ticket.discretionaryOrig;
    if(disc[`${email}`] === undefined) {
      disc[`${email}`] = {};
      calls.push(
        $.ajax({
          url: window.profilesUrl+'/api/v1/users?$filter=mail eq \''+email+'\'',
          type: 'get',
          dataType: 'json',
          xhrFields: {withCredentials: true},
        }));
    }
    if(disc[`${email}`][ticket.type] === undefined) {
      disc[`${email}`][ticket.type] = {};
      disc[`${email}`][ticket.type]['sold'] = 0;
      disc[`${email}`][ticket.type]['unsold'] = 0;
    }
    if(ticket.sold === 0) {
      disc[`${email}`][ticket.type]['unsold']++;
    } else {
      disc[`${email}`][ticket.type]['sold']++;
    }
  }
  $.when.apply($, calls).done(gotUsers);
}

function getCSV() {
  window.location = '../api/v1/tickets?$format=csv&$filter=discretionary eq 1 and year eq current';
}

function gotGroups(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to obtain groups!');
    return;
  }
  var data = jqXHR.responseJSON;
  for(let group of data) {
    $('#group').append('<option value="'+group.cn+'">'+group.cn+'</option>');
  }
}

function assignedTickets(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    console.log(jqXHR);
    alert('Unable to assign tickets!');
    return;
  }
  location.reload();
}

function assignTickets() {
  let groupName = $('#group').val();
  let count = $('#count').val();
  let obj = {'ticketGroups': [{'Group':groupName}]};
  obj.ticketGroups[0].Count = parseInt(count);
  if(isNaN(obj.ticketGroups[0].Count)) {
    alert('Count not specified!');
    return;
  }
  $.ajax({
    url: '../api/v1/tickets/discretionary',
    method: 'POST',
    data: JSON.stringify(obj),
    contentType: 'application/json',
    complete: assignedTickets
  });
}

function initPage() {
  $.ajax({
    url: '../api/v1/tickets?$filter=discretionary eq 1 and year eq current',
    type: 'get',
    dataType: 'json',
    complete: gotDiscretionaryTickets});
  if(window.profilesUrl === undefined) {
    window.profilesUrl = 'https://profiles.burningflipside.com';
  }
  $.ajax({
    url: window.profilesUrl+'/api/v1/groups?$select=cn',
    type: 'get',
    dataType: 'json',
    xhrFields: {withCredentials: true},
    complete: gotGroups});
}

$(initPage);
