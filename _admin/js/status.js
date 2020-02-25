function actionComplete(jqXHR) {
  console.log(jqXHR);
}

function changePrivate(elem, newStatus) {
  let oldStatus = $(elem).closest('tr').data('status');
  let obj = {old: oldStatus, 'new': newStatus};
  $.ajax({
    url: '../api/v1/requests/Actions/ChangePrivateStatus',
    contentType: 'application/json',
    data: JSON.stringify(obj),
    type: 'POST',
    dataType: 'json',
    complete: actionComplete
  });
}

function gotStatusCounts(jqXHR) {
  if(jqXHR.status !== 200) {
    return;
  }
  let data = jqXHR.responseJSON;
  let tbody = $('#statues tbody');
  for(let i = 0; i < data.length; i++) {
    if(data[i].private_status === undefined) {
      continue;
    }
    let buttons = '<button type="button" class="btn btn-primary" onClick="makePublic(this);">Make Public</button>';
    switch(data[i].private_status) {
      case 4:
        buttons += ' <button type="button" class="btn btn-success" onClick="changePrivate(this, 1);">Move to Recieved</button>';
    }
    tbody.append('<tr data-status="'+data[i].private_status+'"><td>'+data[i].extended_status.name+'</td><td>'+data[i].count+'</td><td>'+buttons+'</td></tr>');
  }
}

function initPage() {
  $.ajax({
    url: '../api/v1/requests/countsByStatus',
    complete: gotStatusCounts
  });
}

$(initPage);
