/* global Tabulator, bootbox */
function gotData(results) {
  let pendingSales = results.shift();
  let pools = results.shift();
  let poolObj = pools.value.reduce((acc, pool) => {
    acc[pool.pool_id] = pool;
    return acc;
  }, {});
  let tables = Tabulator.findTable('#sales');
  if(tables === false) {
    return;
  }
  let table = tables[0];
  let ticketCount = 0;
  for(let sale of pendingSales.value) {
    for(let ticket of sale.tickets) {
      if(poolObj[ticket.pool_id] !== undefined) {
        ticket.pool = poolObj[ticket.pool_id].pool_name;
      }
    }
    ticketCount += sale.tickets.length;
    if(!sale.squareLink) {
      if(!sale.squareInformation) {
        sale.squareLink = '<i style="color: red;">Cannot locate square link!</i>';
      } else {
        sale.squareLink = sale.squareInformation.url;
      }
    }
    table.addData(sale);
  }
  document.getElementById('alert').innerHTML = '<div class="alert alert-info" role="alert">There are '+pendingSales.value.length+' pending sales with '+ticketCount+' tickets.</div>';
}

function renderSeller(cell) {
  let data = cell.getData();
  if(data.requestID) {
    return 'Request ID: '+data.requestID;
  }
  if(data.tickets[0].discretionaryOrig === null) {
    return data.tickets[0].pool;
  }
  return data.tickets[0].discretionaryOrig;
}

function renderPurchaser(cell) {
  let data = cell.getData();
  return data.firstName + ' ' + data.lastName + ' ('+data.purchaserEmail+')';
}

function cancelDone(response) {
  if(response.status !== 200) {
    console.log(response);
    alert('Failed to cancel the request!');
    return;
  }
  location.reload();
}

function doCancel(target) {
  bootbox.confirm('Are you sure you want to cancel this transaction?', (result) => {
    if(result === true) {
      fetch('../api/v1/pendingSales/'+target.dataset.id, {method: 'DELETE'}).then((response) => {
        cancelDone(response);
      });
    }
  });
}

function cancelSale(cell) {
  let value = cell.getValue();
  let button = document.createElement('a');
  button.classList.add('btn', 'btn-danger');
  button.dataset.id = value;
  button.innerHTML = '<i class="fas fa-ban"></i>';
  button.addEventListener('click', () => {
    doCancel(button);
  });
  return button;
}

function initPage() {
  let table = new Tabulator('#sales', {
    layout: 'fitColumns',
    columns: [
      {title: 'Cancel', formatter: cancelSale, maxWidth: 150, field: 'purchaseId'},
      {title: 'Seller/Pool', formatter: renderSeller},
      {title: 'Purchaser', formatter: renderPurchaser},
      {title: 'Ticket Count', field: 'tickets.length', maxWidth: 150},
      {title: 'Square Link', field: 'squareLink', formatter:'html'},
    ],
  });
  let promises = [
    fetch('../api/v1/pendingSales?$expand=tickets').then((response) => response.json()),
    fetch('../api/v1/pools').then((response) => response.json()),
  ];
  Promise.allSettled(promises).then(gotData);
}

window.addEventListener('load', initPage);