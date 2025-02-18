/*global $, Bloodhound, bootbox*/
/*exported createPool, deletePoolDialog, editDialogPool, newPool, poolStats, updatePool*/
var id;
var pools;

function setIfValueDifferent(newObj, origObj, inputName, fieldName) {
  if(fieldName === undefined) {
    fieldName = inputName;
  }
  var input = $('#'+inputName);
  if(input.attr('type') === 'checkbox') {
    if(input.is(':checked')) {
      if(origObj[`${fieldName}`] === 0) {
        newObj[`${fieldName}`] = 1;
      }
    } else if(origObj[`${fieldName}`] === 1) {
      newObj[`${fieldName}`] = 0;
    }
  } else {
    var val = $('#'+inputName).val();
    if(val !== origObj[`${fieldName}`]) {
      newObj[`${fieldName}`] = val;
    }
  }
}

function opDone(jqXHR) {
  if(jqXHR.status === 200) {
    alert('Success!');
    $('#pools tbody').empty();
    initTable();
  } else {
    alert('Error!');
    console.log(jqXHR);
  }
}

function deletePool(really) {
  if(really) {
    $.ajax({
      url: '../api/v1/pools/'+id,
      method: 'delete',
      complete: opDone
    });
  }
}

function updatePool() {
  var obj = {};
  setIfValueDifferent(obj, pools[`${id}`], 'pool_name');
  setIfValueDifferent(obj, pools[`${id}`], 'group_name');
  $('#editModal').modal('hide');
  if(Object.keys(obj).length === 0) {
    alert('No changes to save!');
    return;
  }
  $.ajax({
    url: '../api/v1/pools/'+id,
    contentType: 'application/json',
    method: 'patch',
    data: JSON.stringify(obj),
    processData: false,
    complete: opDone
  });
}

function createPool() {
  var obj = {};
  obj['pool_name'] = $('#pool_name_new').val();
  obj['group_name'] = $('#group_name_new').val();
  $('#newModal').modal('hide');
  $.ajax({
    url: '../api/v1/pools',
    contentType: 'application/json',
    method: 'post',
    data: JSON.stringify(obj),
    processData: false,
    complete: opDone
  });
}

function doTicketAssignment(poolId, assignTypes) {
  $.ajax({
    url: '../api/v1/pools/'+poolId+'/Actions/Pool.Assign',
    contentType: 'application/json',
    method: 'post',
    data: JSON.stringify(assignTypes),
    processData: false,
    complete: opDone
  });
}

function gotUnassignedTickets(jqXHR) {
  let poolId = this;
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to obtain unassigned tickets!');
    return;
  }
  let types = {};
  for(let ticket of jqXHR.responseJSON) {
    let type = ticket.type;
    if(types[type] === undefined) {
      types[type] = 1;
      continue;
    }
    types[type]++;
  }
  let msg = 'Available Tickets:<br/>';
  for(let type in types) {
    msg+= '&nbsp;&nbsp;'+type+': '+types[type]+'<input class="form-control" type="number" name="'+type+'" id="'+type+'" min="0" max="'+types[type]+'"><br/>';
  }
  bootbox.dialog({
    title: 'Available tickets for Pool #'+this,
    message: msg,
    buttons: {
      cancel: {
        label: 'Cancel',
        className: 'btn-danger'
      },
      ok: {
        label: 'Assign',
        className: 'btn-info',
        callback: function() {
          let assignTypes = {};
          for(let type in types) {
            let val = $('#'+type).val();
            if(val > 0) {
              assignTypes[type]=val;
            }
          }
          doTicketAssignment(poolId, assignTypes);
        }
      }
    }
  });
}

function poolAssign(poolId) {
  $.ajax({
    url: '../api/v1/tickets?$filter=year eq current and pool_id eq -1 and assigned eq 0 and transferInProgress eq 0 and sold eq 0',
    method: 'get',
    context: poolId,
    complete: gotUnassignedTickets
  });
}

function gotPoolTickets(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to obtain pools!');
    return;
  }
  var msg = '';
  var soldCount = 0;
  var unsoldCount = 0;
  let unsoldType = {};
  let soldType = {};
  var data = jqXHR.responseJSON;
  for(let ticket of data) {
    if(ticket.sold === 1) {
      soldCount++;
      if(soldType[ticket.type] === undefined) {
        soldType[ticket.type] = 1;
      } else {
        soldType[ticket.type]++;
      }
    } else {
      unsoldCount++;
      if(unsoldType[ticket.type] === undefined) {
        unsoldType[ticket.type] = 1;
      } else {
        unsoldType[ticket.type]++;
      }
    }
  }
  msg+= 'Pool Name: '+pools[this].pool_name+'<br/>';
  msg+= 'Pool Owning Group: '+pools[this].group_name+'<br/>';
  msg+= 'Total Sold Count: '+soldCount+'<br/>';
  for(const type in soldType) {
    msg+= '&nbsp;&nbsp;&nbsp;&nbsp;Sold '+type+': '+soldType[type]+'<br/>';
  }
  msg+= 'Total Unsold Count: '+unsoldCount+'<br/>';
  for(const type in unsoldType) {
    msg+= '&nbsp;&nbsp;&nbsp;&nbsp;Unsold '+type+': '+unsoldType[type]+'<br/>';
  }

  bootbox.dialog({
    title: 'Pool Statistics for Pool #'+this,
    message: msg
  });
}

function poolStats(_id) {
  $.ajax({
    url: '../api/v1/tickets?$filter=year eq current and pool_id eq '+_id,
    method: 'get',
    context: _id,
    complete: gotPoolTickets
  }); 
}

function deletePoolDialog(_id) {
  id = _id;
  bootbox.confirm('Are you sure you want to delete this pool?', deletePool);
}

function editDialogPool(_id) {
  id = _id;
  $('#_id').html(_id);
  $('#pool_name').val(pools[`${_id}`].pool_name);
  $('#group_name').val(pools[`${_id}`].group_name);
  $('#editModal').modal('show');
}

function newPool() {
  $('#newModal').modal('show');
}

function gotPools(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    alert('Unable to obtain pools!');
    return;
  }
  var data = jqXHR.responseJSON;
  pools = [];
  var tbody = $('#pools tbody');
  for(let pool of data) {
    var myID = pool.pool_id;
    pools[`${myID}`] = pool;
    tbody.append('<tr><td><button class="btn btn-link" onclick="deletePoolDialog('+myID+')" title="Delete Pool"><i class="fa fa-times"></i></button>'+
                 '<button class="btn btn-link" onclick="editDialogPool('+myID+')" title="Edit Pool"><i class="fa fa-pencil-alt"></i></button>'+
                 '<button class="btn btn-link" onclick="poolStats('+myID+')" title="Pool Stats"><i class="fa fa-chart-bar"></i></button>'+
	         '<button class="btn btn-link" onclick="poolAssign('+myID+')" title="Assign Tickets to Pool"><i class="fa fa-plus-square"></i></button>'+
                 '</td><td>'+myID+'</td><td>'+pool.pool_name+'</td><td>'+pool.group_name+'</td></tr>');
  }
  tbody.append('<tr><td><button class="btn btn-link" onclick="newPool()" title="Add Pool"><i class="fa fa-plus"></i></button></td><td colspan=3"></td></tr>');
}

function gotGroups(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    return;
  }
  var names = jqXHR.responseJSON;
  for(var i = 0; i < names.length; i++) {
    names[`${i}`] = names[`${i}`].cn;
  }
  var groupNames = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.whitespace,
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    local: names});
  $('#group_name').typeahead(null, {name: 'group_name', source: groupNames});
  $('#group_name_new').typeahead(null, {name: 'group_name', source: groupNames});
}

function initTable() {
  $.ajax({
    url: '../api/v1/pools',
    method: 'get',
    complete: gotPools
  });
}

function initPage() {
  if(window.profilesUrl === undefined) {
    window.profilesUrl = 'https://profiles.burningflipside.com';
  }
  $.ajax({
    url: window.profilesUrl+'/api/v1/groups?$select=cn',
    type: 'get',
    dataType: 'json',
    xhrFields: {withCredentials: true},
    complete: gotGroups});
  $('#editModal').modal({'show':false});
  initTable();
}

$(initPage);
// vim: set tabstop=2 shiftwidth=2 expandtab:
