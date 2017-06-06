var ticketSystem = new TicketSystem('../api/v1');

function gotRequestCount(count, err) {
    if(err === null) {
        $('#requestCount').html(count);
    }
}

function gotRequestedTicketCount(count, err) {
    if(err === null) {
        $('#requestedTicketCount').html(count);
    }
}

function gotSoldTicketCount(count, err) {
    if(err === null) {
        $('#soldTicketCount').html(count);
    }
}

function gotUnsoldTicketCount(count, err) {
    if(err === null) {
        $('#unsoldCount').html(count);
    }
}

function gotUsedTicketCount(count, err) {
    if(err === null) {
        $('#usedCount').html(count);
    }
}

function init_index()
{
    ticketSystem.getTicketRequestCount(gotRequestCount);
    ticketSystem.getRequestedTicketCount(gotRequestedTicketCount);
    ticketSystem.getSoldTicketCount(gotSoldTicketCount);
    ticketSystem.getUnsoldTicketCount(gotUnsoldTicketCount);
    ticketSystem.getUsedTicketCount(gotUsedTicketCount);
}

$(init_index);
