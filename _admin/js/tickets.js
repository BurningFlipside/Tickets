var ticket_data = null;
var ticketTypes = null;
var earlyEntry = null;

function short_hash(data, type, row, meta)
{
    var short_hash = data.substring(0,7);
    return '<a style="cursor: pointer;" onclick="view_ticket(\''+data+'\');">'+short_hash+'</a>';
}

function renderTicketType(data, type, row, meta)
{
    if(ticketTypes === null || ticketTypes[data] === undefined)
    {
        return data;
    }
    else
    {
        return ticketTypes[data];
    }
}

function get_ticket_by_selected()
{
    if(ticket_data.selected == -1)
    {
        return ticket_data.current;
    }
    return ticket_data.history[ticket_data.selected];
}

function show_ticket_from_data(data)
{
    var read_only = true;
    if(data.selected == -1)
    {
        ticket = data.current;
        $('#right_arrow').hide();
        if(data.history !== undefined && data.history.length > 0)
        {
            $('#left_arrow').show();
        }
        else
        {
            $('#left_arrow').hide();
        }
        read_only = false;
        $('#saveticket').removeAttr('disabled');
    }
    else
    {
        ticket = data.history[data.selected];
        if(data.selected == (data.history.length - 1))
        {
            $('#left_arrow').hide();
        }
        else
        {
            $('#left_arrow').show();
        }
        $('#right_arrow').show();
        $('#saveticket').attr('disabled', 'true');
    }
    $('#hash').val(ticket.hash);
    $('#year').val(ticket.year);
    $('#firstName').val(ticket.firstName);
    $('#lastName').val(ticket.lastName);
    $('#email').val(ticket.email);
    $('#request_id').val(ticket.request_id);
    $('#type').val(ticket.type);
    $('#guardian_first').val(ticket.guardian_first);
    $('#guardian_last').val(ticket.guardian_last);
    $('#earlyEntryWindow').val(ticket.earlyEntryWindow);
    if(ticket.sold == 1)
    {
        $('#sold').prop('checked', true);
    }
    else
    {
        $('#sold').prop('checked', false);
    }
    if(ticket.used == 1)
    {
        $('#used').prop('checked', true);
    }
    else
    {
        $('#used').prop('checked', false);
    }
    if(ticket.void == 1)
    {
        $('#void').prop('checked', true);
    }
    else
    {
        $('#void').prop('checked', false);
    }
    $('#comments').val(ticket.comments);
    if(read_only)
    {
        $('#firstName').prop('disabled', true);
        $('#lastName').prop('disabled', true);
        $('#email').prop('disabled', true);
        $('#request_id').prop('disabled', true);
        $('#type').prop('disabled', true);
        $('#guardian_first').prop('disabled', true);
        $('#guardian_last').prop('disabled', true);
        $('#sold').prop('disabled', true);
        $('#used').prop('disabled', true);
        $('#void').prop('disabled', true);
        $('#comments').prop('disabled', true);
    }
    else
    {
        $('#firstName').prop('disabled', false);
        $('#lastName').prop('disabled', false);
        $('#email').prop('disabled', false);
        $('#request_id').prop('disabled', false);
        $('#type').prop('disabled', false);
        $('#guardian_first').prop('disabled', false);
        $('#guardian_last').prop('disabled', false);
        $('#sold').prop('disabled', false);
        $('#used').prop('disabled', false);
        $('#void').prop('disabled', false);
        $('#comments').prop('disabled', false);
    }
    $('#ticket_modal').modal('show');
    console.log(data);
}

function ticket_data_done(data)
{
    var ticket = null;
    if(data.selected === undefined)
    {
        alert('Unable to retrieve ticket history data');
        console.log(data);
        return;
    }
    ticket_data = data;
    show_ticket_from_data(data);
}

function view_ticket(hash)
{
    $.ajax({
        url: '../api/v1/tickets/'+hash+'?with_history=1',
        type: 'get',
        dataType: 'json',
        success: ticket_data_done}); 
}

function prev_ticket()
{
    ticket_data.selected++;
    show_ticket_from_data(ticket_data);
}

function next_ticket()
{
    ticket_data.selected--;
    show_ticket_from_data(ticket_data);
}

function set_if_value_different(ticket, obj, inputname, fieldname)
{
    if(fieldname === undefined)
    {
        fieldname = inputname;
    }
    var input = $('#'+inputname);
    if(input.attr('type') === 'checkbox')
    {
         if(input.is(':checked'))
         {
             if(ticket[fieldname] == 0)
             {
                 obj[fieldname] = 1;
             }
         }
         else if(ticket[fieldname] == 1)
         {
             obj[fieldname] = 0;
         }
    }
    else
    {
        var val = $('#'+inputname).val();
        if(val != ticket[fieldname])
        {
            obj[fieldname] = val;
        }
    }
}

function save_ticket_done(jqXHR)
{
    if(jqXHR.status != 200)
    {
        alert("Unable to save ticket!");
    }
    else
    {
        $('#ticket_modal').modal('hide');
        yearChanged();
    }
}

function noChangeDone(jqXHR)
{
    if(jqXHR.status != 200)
    {
        alert("Error!");
    }
    else
    {
        $('#ticket_modal').modal('hide');
    }
}

function save_ticket()
{
    var ticket = get_ticket_by_selected();
    var obj = {};
    set_if_value_different(ticket, obj, 'email');
    set_if_value_different(ticket, obj, 'firstName');
    set_if_value_different(ticket, obj, 'lastName');
    set_if_value_different(ticket, obj, 'request_id');
    set_if_value_different(ticket, obj, 'type');
    set_if_value_different(ticket, obj, 'guardian_first');
    set_if_value_different(ticket, obj, 'guardian_last');
    set_if_value_different(ticket, obj, 'sold');
    set_if_value_different(ticket, obj, 'used');
    set_if_value_different(ticket, obj, 'void');
    set_if_value_different(ticket, obj, 'earlyEntryWindow');
    set_if_value_different(ticket, obj, 'comments');
    if(Object.keys(obj).length > 0)
    {
        $.ajax({
            url: '../api/v1/tickets/'+ticket.hash,
            contentType: 'application/json',
            data: JSON.stringify(obj),
            type: 'patch',
            dataType: 'json',
            complete: save_ticket_done});
    }
    else
    {
        $('#ticket_modal').modal('hide');
    }
}

function resendTicketEmail()
{
    var ticket = get_ticket_by_selected();
    $.ajax({
        url: '../api/v1/tickets/'+ticket.hash+'/Actions/Ticket.SendEmail',
        contentType: 'application/json',
        type: 'post',
        dataType: 'json',
        complete: noChangeDone}); 
}

function spinHash()
{
    var ticket = get_ticket_by_selected();
    $.ajax({
        url: '../api/v1/tickets/'+ticket.hash+'/Actions/Ticket.SpinHash',
        contentType: 'application/json',
        type: 'post',
        dataType: 'json',
        complete: save_ticket_done});
}

function backend_search_done(data)
{
    var tickets = data;
    var history = false;
    if(data.old_tickets !== undefined)
    {
        tickets = data.old_tickets;
        history = true;
    }
    view_ticket(tickets[0].hash);
}

function table_searched()
{
    var dt_api = $('#tickets').DataTable();
    if(dt_api.search() === '')
    {
        return;
    }
    if(dt_api.rows({'search':'applied'})[0].length == 0)
    {
        $.ajax({
            url: '../api/v1/tickets/search/'+dt_api.search(),
            type: 'get',
            dataType: 'json',
            success: backend_search_done
        });
    }
}

function requeryTable()
{
    var year = $('#ticket_year').val();
    var sold = $('#ticketSold').val();
    var assigned = $('#ticketAssigned').val();
    var used = $('#ticketUsed').val();
    var voidVal = $('#ticketVoid').val();
    var disc = $('#discretionaryUser').val();
    var ee = $('#earlyEntry').val();
    var pool = $('#ticketPool').val();
    var filter = 'year eq '+year;
    if(year === '*') {
      filter = 'year ne 999999';
    }
    if(sold !== '*')
    {
        filter+=' and sold eq '+sold;
    }
    if(assigned !== '*')
    {
        filter+=' and assigned eq '+assigned;
    }
    if(used !== '*')
    {
        filter+=' and used eq '+used;
    }
    if(disc !== '')
    {
        filter+=' and discretionaryOrig eq \''+disc+'\'';
    }
    if(voidVal !== '*')
    {
        filter+=' and void eq '+voidVal;
    }
    if(ee !== '*')
    {
        filter+=' and earlyEntryWindow eq '+ee;
    }
    if(pool !== '*')
    {
        filter+=' and pool_id eq '+pool;
    }
    $('#tickets').DataTable().ajax.url('../api/v1/tickets?filter='+filter+'&fmt=data-table').load();
}

function soldChanged()
{
    requeryTable();
}

function assignedChanged()
{
    requeryTable();
}

function usedChanged()
{
    requeryTable();
}

function yearChanged(e)
{
    requeryTable();
}

function discretionaryChanged(e)
{
    requeryTable();
}

function gotTicketYears(jqXHR)
{
    if(jqXHR.status !== 200)
    {
        alert('Unable to obtain valid ticket years!');
        console.log(jqXHR);
        return;
    }
    jqXHR.responseJSON.sort().reverse();
    for(var i = 0; i < jqXHR.responseJSON.length; i++)
    {
        var opt = $('<option/>').attr('value', jqXHR.responseJSON[i]).text(jqXHR.responseJSON[i]);
        if(i === 0) {
          opt.attr('selected', true);
        }
        $('#ticket_year').append(opt);
    }
    $('#ticket_year').on('change', yearChanged);
    var e = {};
    e.currentTarget = {};
    e.currentTarget.value = $('#ticket_year').val();
    yearChanged(e);
}

function gotTicketTypes(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        console.log(jqXHR);
        return;
    }
    var data = jqXHR.responseJSON;
    var options = '';
    ticketTypes = {};
    for(i = 0; i < data.length; i++)
    {
        options+='<option value="'+data[i].typeCode+'">'+data[i].description+'</option>';
        ticketTypes[data[i].typeCode] = data[i].description;
    }
    $('#type').replaceWith('<select id="type" name="type" class="form-control">'+options+'</select>');
}

function gotEarlyEntry(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        console.log(jqXHR);
        return;
    }
    var data = jqXHR.responseJSON;
    var options = '';
    earlyEntry = {};
    for(i = 0; i < data.length; i++)
    {
        options+='<option value="'+data[i].earlyEntrySetting+'">'+data[i].earlyEntryDescription+'</option>';
        earlyEntry[data[i].earlyEntrySetting] = data[i].earlyEntryDescription;
    }
    $('#earlyEntryWindow').replaceWith('<select id="earlyEntryWindow" name="earlyEntryWindow" class="form-control">'+options+'</select>');
    $('#earlyEntry :first-child').after(options);
}

function gotPools(jqXHR) {
  if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined) {
    console.log(jqXHR);
    return;
  }
  var data = jqXHR.responseJSON;
  var options = '';
  for(var i = 0; i < data.length; i++) {
    options+='<option value="'+data[i].pool_id+'">'+data[i].pool_name+'</option>';
  }
  $('#ticketPool :first-child').after(options);
}

function init_page()
{
    var sold = getParameterByName('sold');
    if(sold !== null)
    {
        $('#ticketSold').val(sold);
    }
    var used = getParameterByName('used');
    if(used !== null)
    {
        $('#ticketUsed').val(used);
    }
    var discretionaryUser = getParameterByName('discretionaryUser');
    if(discretionaryUser !== null)
    {
        $('#discretionaryUser').val(discretionaryUser);
    }

    $('#tickets').dataTable({
        columns: [
            {'data': 'hash', 'render':short_hash},
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'email'},
            {'data': 'type', 'render': renderTicketType}
        ]
    });
    $.ajax({
        url: '../api/v1/globals/years',
        type: 'get',
        dataType: 'json',
        complete: gotTicketYears});
    $.ajax({
        url: '../api/v1/globals/ticket_types',
        type: 'get',
        dataType: 'json',
        complete: gotTicketTypes});
    $.ajax({
        url: '../api/v1/earlyEntry',
        type: 'get',
        dataType: 'json',
        complete: gotEarlyEntry});
    $.ajax({
      url: '../api/v1/pools',
      method: 'get',
      complete: gotPools
    });

    $('#tickets').on('search.dt', table_searched);
    $('#ticketSold').on('change', soldChanged);
    $('#ticketAssigned').on('change', assignedChanged);
    $('#ticketUsed').on('change', usedChanged);
    $('#discretionaryUser').on('change', discretionaryChanged);
    $('#ticketVoid').on('change', usedChanged);
    $('#earlyEntry').on('change', usedChanged);
    $('#ticketPool').on('change', usedChanged);
}

$(init_page)
