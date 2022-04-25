var ticketSystem = new TicketSystem('../api/v1');

function ticketsDone(data, err) {
    if(err !== null) {
        console.log(err);
        alert('Unable to obtain requested ticket counts!');
        return;
    }
    var label_array = [];
    var data_array = [];
    var tbody = $('#requestTypesTable tbody');
    for(i = 0; data[i] !== undefined; i++)
    {
        var row = $('<tr>');
        row.append('<td>'+data[i].description+'</td>');
        row.append('<td>'+data[i].count+'</td>');
        row.append('<td>'+data[i].receivedCount+'</td>');
        tbody.append(row);
    }
}

function get_color_by_index(index)
{
    var colors = [
        "#d53e4f",
        "#f46d43",
        "#fdae61",
        "#fee08b",
        "#e6f598",
        "#abdda4",
        "#66c2a5",
        "#3288bd"
    ];
    return colors[index];
}

function get_highlight_by_index(index)
{
    var highlight = [
        "#d73027",
        "#f46d43",
        "#fdae61",
        "#fee08b",
        "#d9ef8b",
        "#a6d96a",
        "#66bd63",
        "#1a9850"
    ];
    return highlight[index];
}

function requests_done(data)
{
    var count_data = [];
    for(var propname in data.data.ticket_counts)
    {
        count_data.push({value: data.data.ticket_counts[propname], label: propname+' ticket(s) per request', color: get_color_by_index(propname), highligh: get_highlight_by_index(propname)});
    }

    var ctx = $("#ticket_count_chart").get(0).getContext("2d");
    new Chart(ctx).Doughnut(count_data);
}

function gotRequestCounts(data, err) {
    if(err !== null) {
        console.log(err);
        alert('Unable to obtain request counts!');
        return;
    }
    var totalCount = 0;
    for(var i = 0; i < data.length; i++) {
        if(data[i].all === true) {
            totalCount = data[i].count;
            data.splice(i, 1);
        }
    }
    $('#requestCount').html(totalCount);
    var receivedCount = 0;
    for(var i = 0; i < data.length; i++) {
        if(data[i].private_status === 1 || data[i].private_status === 6) {
            receivedCount += data[i].count;
        }
    }
    var receivedText = receivedCount+' ';
    if(totalCount !== 0) {
        var percent = ((receivedCount/totalCount)*100).toFixed(2);
        receivedText+='- '+percent+'%';
    }
    $('#receivedRequestCount').html(receivedText);
    var problemCount = 0;
    for(var i = 0; i < data.length; i++) {
        if(data[i].private_status === 2) {
            problemCount += data[i].count;
        }
    }
    var problemText = problemCount+' ';
    if(totalCount !== 0) {
        var percent = ((problemCount/totalCount)*100).toFixed(2);
        problemText+='- '+percent+'%';
    }
    $('#problemRequestCount').html(problemText);
    var rejectedCount = 0;
    for(var i = 0; i < data.length; i++) {
        if(data[i].private_status === 3 || data[i].private_status === 4) {
            rejectedCount += data[i].count;
        }
    }
    var rejectedText = rejectedCount+' ';
    if(totalCount !== 0) {
        var percent = ((rejectedCount/totalCount)*100).toFixed(2);
        rejectedText+='- '+percent+'%';
    }
    $('#rejectedRequestCount').html(rejectedText);
}

function gotRequests(requests, err) {
    if(requests === null) {
        return;
    }
    var total = $('#requestOverTimeTable tbody tr:nth-child(1) td:nth-child('+this.col+')');
    total.append(requests.length);
    var received = 0;
    var notReceived = 0;
    var problem = 0;
    var reject = 0;
    for(var i = 0; i < requests.length; i++) {
        switch(requests[i].private_status) {
            case 0:
                notReceived++;
                break;
            case 1:
            case 6:
                received++;
                break;
            case 2:
                problem++;
                break;
            case 3:
            case 4:
                reject++;
        }
    }
    var cell = $('#requestOverTimeTable tbody tr:nth-child(2) td:nth-child('+this.col+')');
    cell.append(received);
    cell = $('#requestOverTimeTable tbody tr:nth-child(3) td:nth-child('+this.col+')');
    cell.append(notReceived);
    cell = $('#requestOverTimeTable tbody tr:nth-child(4) td:nth-child('+this.col+')');
    cell.append(problem);
    cell = $('#requestOverTimeTable tbody tr:nth-child(5) td:nth-child('+this.col+')');
    cell.append(reject);
}

function gotAllYears(years, err) {
    years.sort();
    var thead = $('#requestOverTimeTable thead tr');
    var rows = $('#requestOverTimeTable tbody tr');
    thead.append('<th></th>');
    for(var i = 0; i < years.length; i++) {
        thead.append('<th>'+years[i]+'</th>');
        for(var j = 0; j < rows.length; j++) {
            rows[j].innerHTML += '<td></td>';
        }
        var obj = { year: years[i], col: i+2};
        var gotRequestsCall = gotRequests.bind(obj);
        ticketSystem.getRequests(gotRequestsCall, 'year eq '+years[i]);
    }
}

function gotDonationAmount(donations, err) {
  if(donations === null) {
    donations = 0;
  }
  let formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  });
  let val = formatter.format(donations);
  $('#receivedDonations').html(val);
}

function gotMoney(jqXHR) {
  if(jqXHR.status !== 200) {
    return;
  }
  let formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  });
  let val = formatter.format(jqXHR.responseJSON);
  $('#receivedMoney').html(val);
}

function init_page()
{
    ticketSystem.getTicketRequestCountsByStatus(gotRequestCounts);
    ticketSystem.getRequestedTicketCountsByType(ticketsDone);
    ticketSystem.getAllYears(gotAllYears);
    ticketSystem.getDonationsAmount(gotDonationAmount);
    $.ajax({url: '../api/v1/requests/moneyReceived', complete: gotMoney});
}

$(init_page);
