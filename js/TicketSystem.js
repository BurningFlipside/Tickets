function TicketSystem(apiRoot) {
    this.apiRoot = apiRoot;
}

function TicketRequest(data, ticketSystem) {
    this.ticketSystem = ticketSystem;
    for(var propName in data) {
        this[propName] = data[propName];
    }
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

TicketSystem.prototype.getRequests = function(callback) {
    var obj = {
        callback: callback,
        objectType: TicketRequest,
        ticketSystem: this
    };
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/request',
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

TicketSystem.prototype.getRequestDataTableUri = function(filter) {
    var ret = this.apiRoot+'/requests?fmt=data-table'
    if(filter !== undefined) {
        ret+='&filter='+filter;
    }
    return ret;
}

TicketSystem.prototype.getTicketRequestIdForCurrentUser = function(callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests/Actions/Requests.GetRequestID',
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

TicketSystem.prototype.getUsedTicketCount = function(callback) {
    var obj = {
        callback: callback,
        parser: getODataCount};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/tickets?$filter=year eq current and used eq 1&$count=true&$select=@odata.count',
        type: 'get',
        dataType: 'json',
        complete: parseResults});
}

TicketSystem.prototype.createRequest = function(request, callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/request',
        data: JSON.stringify(request),
        type: 'POST',
        dataType: 'json',
        processData: false,
        complete: parseResults});
}

TicketSystem.prototype.updateRequest = function(request, callback) {
    var obj = {callback: callback};
    var parseResults = ticketSystemGenericResults.bind(obj);
    $.ajax({
        url: this.apiRoot+'/requests/'+request.request_id+'/'+request.year,
        data: JSON.stringify(obj),
        processData: false,
        dataType: 'json',
        type: 'patch',
        success: parseResults});
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
        complete: email_request_done});
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
