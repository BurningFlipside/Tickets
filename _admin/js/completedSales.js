/* global Tabulator, bootbox */
function gotData(results) {
  let completeSales = results.shift();
  let pools = results.shift();
  if(completeSales.status === 'rejected' || pools.status === 'rejected') {
    return;
  }
  let poolObj = pools.value.reduce((acc, pool) => {
    acc[pool.pool_id] = pool;
    return acc;
  }, {});
  let tables = Tabulator.findTable('#sales');
  if(tables === false) {
    return;
  }
  let table = tables[0];
  let zeroAmount = false;
  for(let sale of completeSales.value) {
    for(let ticket of sale.tickets) {
      if(poolObj[ticket.pool_id] !== undefined) {
        ticket.pool = poolObj[ticket.pool_id].pool_name;
      }
    }
    if(sale.amount === 0) {
      zeroAmount = true;
    }
    table.addData(sale);
  }
  if(zeroAmount) {
    let alertDiv = document.getElementById('alert');
    let alert = document.createElement('div');
    alert.classList.add('alert', 'alert-info');
    alert.setAttribute('role', 'alert');
    alert.innerText = 'There are one or more sales without amount values. Click ';
    let link = document.createElement('a');
    link.href = '#';
    link.classList.add('alert-link');
    link.innerText = 'here to update the values from Square (may take a few seconds).';
    alert.appendChild(link);
    alertDiv.appendChild(alert);
    link.addEventListener('click', () => {
      fetch('../api/v1/completeSales/square/Actions/UpdateZeroAmounts', {
        method: 'POST',
      }).then((response) => {
        if(response.ok) {
          location.reload();
        } else {
          alert('Failed to update zero amounts!');
        }
      });
    });
  }
}

function renderSeller(cell) {
  let data = cell.getData();
  if(data.requestId) {
    return 'Request ID: '+data.requestId;
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

function showPaymentDetails(tenderId) {
  fetch('../api/v1/completeSales/square/purchase/'+tenderId).then((response) => response.json()).then((data) => {
    let message = document.createElement('div');
    let formatter = new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    });
    let payment = data.payment;
    message.innerHTML = 'Amount: '+formatter.format(payment.amount_money.amount/100)+'<br>';
    message.innerHTML += 'Processing Fees:';
    let feeList = document.createElement('ul');
    for(let fee of payment.processing_fee) {
      let item = document.createElement('li');
      item.innerHTML = formatter.format(fee.amount_money.amount/100);
      feeList.appendChild(item);
    }
    message.appendChild(feeList);
    message.innerHTML += 'Status: ';
    switch(payment.status) {
      case 'COMPLETED':
        message.innerHTML += '<span style="color: green;">Completed</span>';
        break;
      case 'PENDING':
        message.innerHTML += '<span style="color: orange;">Pending</span>';
        break;
      case 'APPROVED':
        message.innerHTML += '<span style="color: orange;">Approved</span>';
        break;
      default:
        message.innerHTML += '<span style="color: red;">'+payment.status+'</span>';
    }
    message.innerHTML += '<br>Payment Risk: ';
    switch(payment.risk_evaluation.risk_level) {
      case 'PENDING':
        message.innerHTML += '<span style="color: grey;">Pending</span>';
        break;
      case 'NORMAL':
        message.innerHTML += '<span style="color: green;">Normal</span>';
        break;
      case 'MODERATE':
        message.innerHTML += '<span style="color: orange;">Moderate</span>';
        break;
      case 'HIGH':
        message.innerHTML += '<span style="color: red;">High</span>';
        break;
      default:
        message.innerHTML += '<span style="color: red;">'+payment.risk_evaluation.risk_level+'</span>';
    }
    message.innerHTML += '<br>Buyer Email:'+payment.buyer_email_address;
    message.innerHTML += '<br><a href="'+payment.receipt_url+'" target="_blank" rel="noopener noreferrer">View Receipt on Square <i class="fas fa-external-link-alt"></i></a>';
    bootbox.dialog({
      title: 'Square Payment Details',
      message: message,
    });
  });
}

function renderOrderId(cell) {
  let formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  });
  let orderId = cell.getValue();
  let link = document.createElement('a');
  link.href = '#';
  link.innerText = orderId;
  link.addEventListener('click', () => {
    fetch('../api/v1/completeSales/square/order/'+orderId).then((response) => response.json()).then((data) => {
      let message = document.createElement('div');
      let orderLink = document.createElement('a');
      orderLink.href = 'https://app.squareup.com/dashboard/orders/overview/'+orderId;
      orderLink.target = '_blank';
      orderLink.rel = 'noopener noreferrer';
      orderLink.innerHTML = 'View on Square <i class="fas fa-external-link-alt"></i>';
      message.appendChild(orderLink);
      let order = data.order;
      message.innerHTML += '<br>Order ID: '+order.id;
      message.innerHTML += '<br>Items:';
      message.innerHTML += '<ul>';
      for(let item of order.line_items) {
        message.innerHTML += '<li>'+item.quantity+' '+item.name+' @ '+formatter.format(item.base_price_money.amount/100)+'</li>';
      }
      message.innerHTML += '</ul>';
      message.innerHTML += 'Total: '+formatter.format(order.total_money.amount/100);
      message.innerHTML += '<br>Payments:';
      let paymentList = document.createElement('ul');
      for(let tender of order.tenders) {
        let money = formatter.format(tender.amount_money.amount/100);
        let item = document.createElement('li');
        let paymentLink = document.createElement('a');
        paymentLink.href = '#';
        switch(tender.type) {
          case 'CARD':
            switch(tender.card_details.card.card_brand) {
              case 'VISA':
                paymentLink.innerHTML = money+' - <i class="fab fa-cc-visa"></i> '+tender.card_details.card.last_4;
                break;
              case 'MASTERCARD':
                paymentLink.innerHTML = money+' - <i class="fab fa-cc-mastercard"></i> '+tender.card_details.card.last_4;
                break;
              case 'DISCOVER':
                paymentLink.innerHTML = money+' - <i class="fab fa-cc-discover"></i> '+tender.card_details.card.last_4;
                break;
              default:
                paymentLink.innerHTML = money+' - '+tender.card_details.card.card_brand+' '+tender.card_details.card.last_4;
                break;
            }
            break;
          default:
            paymentLink.innerHTML = money+' ('+tender.type+')';
            break;
        }
        item.appendChild(paymentLink);
        paymentLink.addEventListener('click', () => {
          showPaymentDetails(tender.id);
        });
        paymentList.appendChild(item);
      }
      message.appendChild(paymentList);
      bootbox.dialog({
        title: 'Square Order Details',
        message: message,
      });
    });
  });
  return link;
}

function initPage() {
  new Tabulator('#sales', {
    layout: 'fitColumns',
    columns: [
      {title: 'Seller/Pool', formatter: renderSeller},
      {title: 'Purchaser', formatter: renderPurchaser},
      {title: 'Ticket Count', field: 'tickets.length', maxWidth: 150},
      {title: 'Amount', field: 'amount', formatter:'money', formatterParams: {symbol: '$'}},
      {title: 'Square Order ID', field: 'orders', formatter: renderOrderId},
    ],
  });
  let promises = [
    fetch('../api/v1/completeSales?$expand=tickets').then((response) => response.json()),
    fetch('../api/v1/pools').then((response) => response.json()),
  ];
  Promise.allSettled(promises).then(gotData);
}

window.addEventListener('load', initPage);