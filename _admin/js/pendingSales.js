function gotData(results) {
  let pendingSales = results.shift();
  let pools = results.shift();
  let poolObj = {};
  for(let i = 0; i < pools.value.length; i++) {
    let pool = pools.value[i];
    poolObj[pool.pool_id] = pool;
  }
  for(let i = 0; i < pendingSales.value.length; i++) {
    let sale = pendingSales.value[i];
    for(let j = 0; j < sale.tickets.length; j++) {
      if(poolObj[sale.tickets[j].pool_id] !== undefined) {
        sale.tickets[j].pool = poolObj[sale.tickets[j].pool_id].pool_name;
      }
    }
  }
  $('#sales').DataTable().rows.add(pendingSales.value).draw();
}

function renderSeller(value, type, data) {
  if(data.tickets[0].discretionaryOrig === null) {
    return data.tickets[0].pool;
  }
  return data.tickets[0].discretionaryOrig;
}

function renderPurchaser(value, type, data) {
  return data.firstName + ' ' + data.lastName + ' ('+data.purchaserEmail+')';
}

function cancelDone(jqXHR) {
  if(jqXHR.status !== 200) {
    console.log(jqXHR);
    alert('Failed to cancel the request!');
  }
  location.reload();
}

function doCancel(target) {
  bootbox.confirm('Are you sure you want to cancel this transaction?', (result) => {
    if(result === true) {
      $.ajax({
        url: '../api/v1/pendingSales/'+target.dataset.id,
        method: 'DELETE',
        complete: cancelDone,
      });
    }
  });
}

function cancelSale(value) {
  return '<a class="btn btn-danger" data-id="'+value+'" onclick="doCancel(this);"><i class="fas fa-ban"></i></a>';
}

function initPage() {
  $('#sales').dataTable({
    columns: [
      {data: 'purchaseId', 'render': cancelSale},
      {'render': renderSeller},
      {'render': renderPurchaser},
      {'data': 'tickets.length'},
      {'data': 'squareInformation.url',}
    ]
  });
  let promises = [
    $.ajax({url: '../api/v1/pendingSales?$expand=tickets'}),
    $.ajax({url: '../api/v1/pools'}),
  ];
  Promise.allSettled(promises).then(gotData);
}

$(initPage);