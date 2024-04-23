/*global $, TicketSystem*/
let ticketSystem = new TicketSystem('../api/v1');

function getTicketCost(ticket, costData) {
  if(ticket.request_id !== null && ticket.request_id !== '') {
    switch(ticket.type) {
      case 'A':
        return costData.adultPrice;
      case 'T':
        return costData.teenPrice;
      case 'K':
        return costData.kidPrice;
      case 'C':
        return costData.childPrice;
    }
  } else {
    switch(ticket.type) {
      case 'A':
        return costData.adultPriceSecondarySale;
      case 'T':
        return costData.teenPriceSecondaySale;
      case 'K':
        return costData.kidPriceSecondaySale;
      case 'C':
        return costData.childPriceSecondaySale;
    }
  }
}

function getNetFromGross(gross, ticket, costData) {
  if(ticket.request_id !== null && costData.mainSaleTaxExcempt === 1) {
    //Main sale is tax exempt
    return gross;
  } else if(ticket.request_id !== null) {
    //Only sales tax
    return gross - (gross * 0.0825);
  }
  let myNet = gross;
  if(costData.secondaryFlatFee !== null) {
    myNet -= costData.secondaryFlatFee;
  }
  if(costData.secondaryPercentFee !== null) {
    myNet -= (gross * costData.secondaryPercentFee);
  }
  //Sales tax
  myNet -= (myNet * 0.0825);
  return myNet;
}

function gotTickets(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain costs!');
    return;
  }
  let data = jqXHR.responseJSON;
  let gross = 0.0;
  let net = 0.0;
  for(let ticket of data) {
    let myGross = getTicketCost(ticket, this.cost);
    gross += myGross;
    let myNet = getNetFromGross(myGross, ticket, this.cost);
    net += myNet;
  }
  let grossCell = $('#salesOverTimeTable tbody tr:nth-child(1) td:nth-child('+this.col+')');
  grossCell.append('$'+gross.toFixed(2));
  let netCell = $('#salesOverTimeTable tbody tr:nth-child(2) td:nth-child('+this.col+')');
  netCell.append('$'+net.toFixed(2));
}

function gotAllYears(years, err) {
  if(err && err.httpStatus === 401) {
    return;
  } else if(err && err.httpStatus !== 200) {
    alert('Failed to get ticket system years!');
  }
  years.sort();
  let thead = $('#salesOverTimeTable thead tr');
  let rows = $('#salesOverTimeTable tbody tr');
  thead.append('<th></th>');
  let i = 0;
  for(let year of years) {
    thead.append('<th>'+year+'</th>');
    for(let j = 0; j < rows.length; j++) {
      rows[j].innerHTML += '<td></td>'; // eslint-disable-line security/detect-object-injection
    }
    let obj = { year: year, col: i+2, cost: this[year]};
    $.ajax({url: '../api/v1/tickets?$filter=sold eq 1 and year eq '+year, complete: gotTickets.bind(obj)});
    i++;
  }
}

function gotCosts(jqXHR) {
  if(jqXHR.status !== 200) {
    alert('Unable to obtain costs!');
    return;
  }
  let data = jqXHR.responseJSON;
  let obj = {};
  for(let cost of data) {
    obj[cost.year] = cost;
  }
  ticketSystem.getAllYears(gotAllYears.bind(obj));

}

function initPage() {
  $.ajax({url: '../api/v1/globals/costs', complete: gotCosts});
}

$(initPage);
