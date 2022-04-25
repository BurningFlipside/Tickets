function TicketSystem(apiRoot) {
    this.apiRoot = apiRoot;
}

function TicketRequest(data, ticketSystem) {
    this.ticketSystem = ticketSystem;
    for(var propName in data) {
        this[propName] = data[propName];
    }
}

function Ticket(data, ticketSystem) {
    this.ticketSystem = ticketSystem;
    for(var propName in data) {
        this[propName] = data[propName];
    }
}

TicketSystem.prototype.getCurrentYear = function(callback) {
    var obj = {
        callback: callback,
        parser: integerify
    };
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/globals/vars/year',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getAllYears = function(callback) {
    var obj = {
        callback: callback
    };
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/globals/years',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getWindow = function(callback) {
    var obj = {
        callback: callback,
        parser: ticketSystemParseWindowResults
    };
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/globals/window',
        type: 'GET',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getRequests = function(callback, filter) {
    var obj = {
        callback: callback,
        objectType: TicketRequest,
        ticketSystem: this
    };
    var url = this.apiRoot+'/request';
    if(filter !== undefined) {
        url+='?$filter='+filter;
    }
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        complete: parseResults});    
}

TicketSystem.prototype.getRequest = function(callback, requestId, year) {
    var obj = {
        callback: callback,
        parser: unarray,
        objectType: TicketRequest,
        ticketSystem: this
    };
    if(requestId === undefined || requestId === null) {
        requestId = 'me';
    }
    if(year === undefined || year === null) {
        year = 'current';
    }
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests/'+requestId+'/'+year,
        type: 'GET',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getRequestAndAssignBucket = function(callback, requestId, year) {
    var obj = {
        callback: callback,
        objectType: TicketRequest,
        ticketSystem: this
    };
    if(requestId === undefined || requestId === null) {
        requestId = 'me';
    }
    if(year === undefined || year === null) {
        year = 'current';
    }
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests/'+requestId+'/'+year+'/Actions/Requests.GetBucket',
        contentType: 'application/json',
        type: 'POST',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.searchRequest = function(value, callback, year) {
    var obj = {
        callback: callback,
        objectType: TicketRequest,
        ticketSystem: this
    };
    if(year === undefined) {
        year = 'current';
    }
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests?$search='+value+'&$filter=year eq '+year,
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getRequestDataTableUri = function(filter, select) {
    var ret = this.apiRoot+'/requests?fmt=data-table';
    if(filter !== undefined) {
        ret+='&$filter='+filter;
    }
    if(select !== undefined) {
        if(Array.isArray(select)) {
            select = select.join();
        }
        ret+='&$select='+select;
    }
    return ret;
}

TicketSystem.prototype.getRequestedTicketsDataTableUri = function(filter, select) {
    var ret = this.apiRoot+'/requests_w_tickets?fmt=data-table';
    if(filter !== undefined) {
        ret+='&$filter='+filter;
    }
    if(select !== undefined) {
        if(Array.isArray(select)) {
            select = select.join();
        }
        ret+='&$select='+select;
    }
    return ret;
}

TicketSystem.prototype.getProblemRequestDataTableUri = function(view) {
    var ret = this.apiRoot+'/requests/problems';
    if(view !== undefined) {
        ret+='/'+view;
    }
    return ret+'?fmt=data-table';
}

TicketSystem.prototype.getTicketRequestIdForCurrentUser = function(callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests/Actions/Requests.GetRequestID',
        contentType: 'application/json',
        type: 'POST',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getTicketRequestCount = function(callback) {
    var obj = {
        callback: callback,
        parser: getODataCount};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests?$filter=year eq current&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getRequestedTicketCount = function(callback) {
    var obj = {
        callback: callback,
        parser: getODataCount};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests_w_tickets?$filter=year eq current&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getSoldTicketCount = function(callback) {
    var obj = {
        callback: callback,
        parser: getODataCount};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/tickets?$filter=year eq current and sold eq 1&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getUnsoldTicketCount = function(callback) {
    var obj = {
        callback: callback,
        parser: getODataCount};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/tickets?$filter=year eq current and sold eq 0&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getUsedTicketCount = function(callback, year) {
    if(year === undefined) {
        year = 'current';
    }
    var obj = {
        callback: callback,
        parser: getODataCount};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/tickets?$filter=year eq '+year+' and used eq 1&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getUnusedTicketCount = function(callback, year) {
    if(year === undefined) {
        year = 'current';
    }
    var obj = {
        callback: callback,
        parser: getODataCount};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/tickets?$filter=year eq '+year+' and used eq 0 and sold eq 1&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getTicketRequestCountsByStatus = function(callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/request/countsByStatus',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getRequestedTicketCountsByType = function(callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests_w_tickets/types',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.createRequest = function(request, callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/request',
        contentType: 'application/json',
        data: JSON.stringify(request),
        type: 'POST',
        dataType: 'json',
        processData: false,
        complete: parseResults});
}

TicketSystem.prototype.updateRequest = function(request, callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    var uri = this.apiRoot+'/requests/'+request.request_id;
    if(request.year !== undefined) {
        uri += '/'+request.year;
    }
    $.ajax({
        url: uri,
        contentType: 'application/json',
        data: JSON.stringify(request),
        processData: false,
        dataType: 'json',
        type: 'patch',
        complete: parseResults});
}

TicketSystem.prototype.bulkSetCritvols = function(critVols, callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests/Actions/SetCritVols',
        type: 'POST',
        data: critVols,
        processData: false,
        complete: parseResults});
}

TicketSystem.prototype.getDonationsAmount = function(callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests/donations',
        type: 'GET',
        processData: false,
        complete: parseResults});
}

TicketSystem.prototype.getTickets = function(callback, filter) {
    var obj = {
        callback: callback,
        objectType: Ticket,
        ticketSystem: this
    };
    var url = this.apiRoot+'/tickets';
    if(filter !== undefined) {
        url+='?$filter='+filter;
    }
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.searchTickets = function(value, callback, year) {
    var obj = {
        callback: callback,
        objectType: Ticket,
        ticketSystem: this
    };
    if(year === undefined) {
        year = 'current';
    }
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/tickets?$search='+value+'&$filter=year eq '+year,
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.getEarlyEntryWindows = function(callback) {
    var obj = {
        callback: callback,
        ticketSystem: this
    };
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/earlyEntry',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketRequest.prototype.getPdfUri = function() {
    return this.ticketSystem.apiRoot+'/request/'+this.request_id+'/'+this.year+'/pdf';
}

TicketRequest.prototype.sendEmail = function(callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.ticketSystem.apiRoot+'/request/'+this.request_id+'/'+this.year+'/Actions/Requests.SendEmail',
        type: 'post',
        dataType: 'json',
        complete: parseResults});
}

function getDateInCentralTime(date) {
    var ret = new Date(date+" GMT-0600");
    //You can't replace this with <
    if(!(ret.getYear() > 2000)) {
        ret = new Date(date+"T06:00:00.000Z");
    }
    return ret;
}

function ticketSystemParseWindowResults(myWindow, callback) {
    myWindow.request_start_date = getDateInCentralTime(myWindow.request_start_date);
    myWindow.request_stop_date = getDateInCentralTime(myWindow.request_stop_date);
    myWindow.request_stop_date.setHours(23);
    myWindow.request_stop_date.setMinutes(59);
    myWindow.mail_start_date = getDateInCentralTime(myWindow.mail_start_date);
    myWindow.current = getDateInCentralTime(myWindow.current);
    myWindow.year = myWindow.year*1;
    if(myWindow.test_mode === '') {
        myWindow.test_mode = false;
    }
    callback(myWindow, null);
}

function unarray(data, callback) {
    if(Array.isArray(data)) {
        if(data.length === 0) {
            callback(null, null);
        }
        else if(data.length === 1) {
            callback(data[0], null);
        }
        else {
            console.log('Response array is longer than 1!');
            console.log(data);
            throw 'Array longer than 1!';
        }
        return;
    }
    throw 'Not an array!';
}

function integerify(data, callback) {
    callback(data*1, null);
}

function getODataCount(data, callback) {
    if(data['@odata.count'] !== undefined) {
        callback(data['@odata.count'], null);
    }
    else {
        console.log(data);
        throw '@odata.count not present in response!';
    }
}

function ticketSystemGenericResults(jqXHR) {
    if(this.callback === undefined) {
        return;
    }
    if(jqXHR.status === 200) {
        if(this.objectType !== undefined) {
            if(Array.isArray(jqXHR.responseJSON)) {
                for(var i = 0; i < jqXHR.responseJSON.length; i++) {
                    jqXHR.responseJSON[i] = new this.objectType(jqXHR.responseJSON[i], this.ticketSystem);
                }
            }
        }
        if(this.parser === undefined) {
            this.callback(jqXHR.responseJSON, null);
        }
        else {
            this.parser(jqXHR.responseJSON, this.callback);
        }
    }
    else {
        var err = {
            httpStatus: jqXHR.status,
            jsonResp: jqXHR.responseJSON,
            textResp: jqXHR.responseText
        };
        this.callback(null, err);
    }
}
