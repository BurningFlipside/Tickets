var history_data = null;
var earlyEntry;
var scanner;
var myCameras;

function finish_processing_ticket(data)
{
    $('#process_ticket_modal').modal('hide');
    console.log(data);
}

function error_processing_ticket(jqXHR)
{
    console.log(jqXHR);
}

function process_ticket()
{
    var hash = $('#hash').val();
    var data = {};
    data.firstName = $('#firstName').val();
    data.lastName  = $('#lastName').val();
    if($('#void:checked').length === 0)
    {
        data['void'] = 0;
    }
    else
    {
        data['void'] = 1;
    }
    if($('#used:checked').length === 0)
    {
        data.used = 0;
    }
    else
    {
        data.used = 1;
        var date = new Date();
        data.used_dt = date.toISOString().slice(0,19).replace('T', ' ');
    }
    if($('#guardian_first').val().length > 0)
    {
        data.guardian_first = $('#guardian_first').val();
    }
    if($('#guardian_last').val().length > 0)
    {
        data.guardian_first = $('#guardian_last').val();
    }
    data.physical_ticket_id = $('#physical_ticket_id').val();
    data.comments = $('#comments').val();
    data = JSON.stringify(data);
    $.ajax({
        url:  '../api/v1/tickets/'+hash,
        contentType: 'application/json',
        type: 'patch',
        dataType: 'json',
        data: data,
        processData: false,
        success: finish_processing_ticket,
        error: error_processing_ticket
    });
}

function found_ticket(data)
{
    if(data[0] !== undefined) {
       data = data[0];
    }
    $('#ticket_history_modal').modal('hide');
    $('#search_ticket_modal').modal('hide');
    console.log(data);
    $('#process_ticket_modal .modal-body .alert').remove();
    if(data.used !== '0')
    {
        add_notification($('#process_ticket_modal .modal-body'), 'Ticket is already used!', NOTIFICATION_FAILED, false);
    }
    if(data['void'] !== '0')
    {
        add_notification($('#process_ticket_modal .modal-body'), 'Ticket is void!', NOTIFICATION_FAILED, false);
        $('#void').attr('checked', true);
    }
    else
    {
        $('#void').removeAttr('checked');
    }
    if(data['earlyEntryWindow']*1 < earlyEntry)
    {
        add_notification($('#process_ticket_modal .modal-body'), 'Ticket is not valid for current early entry status!', NOTIFICATION_FAILED, false);
    }
    $('#used').attr('checked', true);
    $('#hash').val(data.hash);
    $('#type').val(data.type);
    $('#firstName').val(data.firstName);
    $('#lastName').val(data.lastName);
    if((data.guardian_first === null && data.guardian_last === null) ||
       (data.guardian_first === '' && data.guardian_last === ''))
    {
        $('#guardian_first').val('');
        $('#guardian_last').val('');
        $('#minor_block').attr('hidden', 'true');
    }
    else
    {
        $('#guardian_first').val(data.guardian_first);
        $('#guardian_last').val(data.guardian_last);
        $('#minor_block').removeAttr('hidden');
    }
    $('#physical_ticket_id').val(data.physical_ticket_id);
    $('#comments').val(data.comments);
    $('#process_ticket_modal').modal('show');
}

function process_history_ticket()
{
    if(history_data.selected == -1)
    {
        found_ticket(history_data.current);
    }
    else
    {
        alert('Cannot process an old ticket');
    }
}

function show_history_from_data(data)
{
    var read_only = true;
    var ticket;
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
    }
    $('#history_hash').val(ticket.hash);
    $('#history_firstName').val(ticket.firstName);
    $('#history_lastName').val(ticket.lastName);
    $('#history_email').val(ticket.email);
    $('#history_request_id').val(ticket.request_id);
    $('#history_type').val(ticket.type);
    $('#history_guardian_first').val(ticket.guardian_first);
    $('#history_guardian_last').val(ticket.guardian_last);
    $('#history_sold').val(ticket.sold);
    $('#history_used').val(ticket.used);
    $('#history_void').val(ticket['void']);
    $('#history_physical_ticket_id').val(ticket.physical_ticket_id);
    $('#history_comments').val(ticket.comments);
    if(read_only)
    {
        $('#history_firstName').prop('disabled', true);
        $('#history_lastName').prop('disabled', true);
        $('#history_email').prop('disabled', true);
        $('#history_request_id').prop('disabled', true);
        $('#history_type').prop('disabled', true);
        $('#history_guardian_first').prop('disabled', true);
        $('#history_guardian_last').prop('disabled', true);
        $('#history_sold').prop('disabled', true);
        $('#history_used').prop('disabled', true);
        $('#history_void').prop('disabled', true);
        $('#history_physical_ticket_id').prop('disabled', true);
        $('#history_comments').prop('disabled', true);
        $('#process_history').prop('disabled', true);
    }
    else
    {
        $('#history_firstName').prop('disabled', false);
        $('#history_lastName').prop('disabled', false);
        $('#history_email').prop('disabled', false);
        $('#history_request_id').prop('disabled', false);
        $('#history_type').prop('disabled', false);
        $('#history_guardian_first').prop('disabled', false);
        $('#history_guardian_last').prop('disabled', false);
        $('#history_sold').prop('disabled', false);
        $('#history_used').prop('disabled', false);
        $('#history_void').prop('disabled', false);
        $('#history_physical_ticket_id').prop('disabled', false);
        $('#history_comments').prop('disabled', false);
        $('#process_history').prop('disabled', false);
    }
    $('#ticket_history_modal').modal('show');
}

function found_history(data)
{
    history_data = data;
    show_history_from_data(data);
}

function prev_ticket()
{
    history_data.selected++;
    show_history_from_data(history_data);
}

function next_ticket()
{
    history_data.selected--;
    show_history_from_data(history_data);
}

function search_done(data)
{
    if(data.length === undefined || data.length === 0)
    {
        search_failed();
        return;
    }
    var table = $('#search_ticket_table').DataTable();
    table.clear();
    for(var i = 0; i < data.length; i++)
    {
        table.row.add(data[i]);
    }
    console.log(data);
    table.draw();
    $('#search_ticket_modal').modal('show');
}

function history_search_done(data)
{
    if(data.length === undefined || data.length === 0)
    {
        search_failed();
        return;
    }
    var table = $('#history_ticket_table').DataTable();
    table.clear();
    for(var i = 0; i < data.length; i++)
    {
        table.row.add(data[i]);
    }
    table.draw();
    $('#history_ticket_modal').modal('show');
}

function search_failed(jqXHR)
{
  alert('Unable to locate ticket!');
}

function process_mag_stripe(stripe_value)
{
    var card = {};
    if(stripe_value[0] !== '%')
    {
        return false;
    }
    if(stripe_value[1] === 'B' || stripe_value[1] === 'b')
    {
        //This appears to be a credit card
        stipe_value = stripe_value.replace('%B', '');
        stripe_value = stripe_value.replace('%b', '');
        var arr = stripe_value.split('^');
        
        card.type          = 'cc';
	card.cc_number     = arr[0];
        card.expires       = {};
        card.expires.month = arr[2].substring(2,4);
        card.expires.year  = arr[2].substring(0,2);

        var nameArr = arr[1].split('/');
        card.first  = nameArr[1];
        card.last   = nameArr[0];

        var first = card.first.split(' ');
        if(card.length > 1)
        {
            card.first = first[0];
            card.initial = first[1];
        }
    }
    else
    {
        //This appears to be a drivers license
        var parts = stripe_value.split('^');
        card.type  = 'dl';
        card.state = parts[0].substring(1,3);
        card.city  = parts[0].substring(3);
        if(parts.length >= 2)
        {
             var names = parts[1].split('$');
             card.first = names[1];
             card.last  = names[0];
             if(parts.length >= 3)
             {
                 card.address = parts[2];
                 if(parts.length >= 4)
                 {
                     var subparts = parts[3].split('=');
                     card.iin = subparts[0].substring(2, 5);
                     card.dl_num = subparts[0].substring(8);
                     if(subparts.length >= 2)
                     {
                         card.expires       = {};
                         card.expires.month = subparts[1].substring(2,4);
                         card.expires.year  = subparts[1].substring(0,2);
                         card.birth         = {};
                         card.birth.year    = subparts[1].substring(4,8);
                         card.birth.month   = subparts[1].substring(8,10);
                         card.birth.day     = subparts[1].substring(10,12);
                         console.log(subparts);
                     }
                 }
             }
        }
    }
    console.log(card);
    return card;
}

function filter_from_mag_stripe(stripe_value)
{
    if(stripe_value[0] !== '%')
    {
        return false;
    }
    var card = process_mag_stripe(stripe_value);
    if(card.first !== undefined && card.last !== undefined)
    {
        return 'filter=year eq current and '+
                 'substringof(firstName,\''+card.first+'\') and '+
                 'substringof(lastName,\''+card.last+'\')';
    }
    else if(stripe_value.indexOf('%TX') === 0)
    {
        //This appears to be a TX drivers license
        var parts = stripe_value.split('^');
        if(parts.length > 2)
        {
            var names = parts[1].split('$');
            return 'filter=year eq current and '+
                 'substringof(firstName,\''+names[1]+'\') and '+
                 'substringof(lastName,\''+names[0]+'\')';
        }
    }
    return false;
}

function really_search(jqXHR)
{
    var filter = false;
    if(jqXHR.status === 401)
    {
        location.reload();
    }
    if(this.indexOf('%') === 0)
    {
        filter = filter_from_mag_stripe(this);
    }
    else if(this.indexOf(' ') > -1)
    {
        var names = this.split(' ');
        filter = 'filter=year eq current and '+
                 'substringof(firstName,\''+names[0]+'\') and '+
                 'substringof(lastName,\''+names[1]+'\')';
    }
    else
    {
        filter = 'filter=year eq current and '+
                 '(substringof(firstName,\''+this+'\') or '+
                 'substringof(lastName,\''+this+'\') or '+
                 'substringof(hash,\''+this+'\') or '+
                 'substringof(email,\''+this+'\') or '+
                 'substringof(request_id,\''+this+'\'))';
    }
    $.ajax({
        url:  '../api/v1/tickets',
        data: filter,
        type: 'get',
        dataType: 'json',
        success: search_done,
        error: search_failed
    });
}

function really_search_history(jqXHR)
{
    var filter = false;
    console.log(jqXHR);
    if(this.indexOf('%') === 0)
    {
        filter = filter_from_mag_stripe(this);
    }
    else if(this.indexOf(' ') > -1)
    {
        var names = this.split(' ');
        filter = 'filter=year eq current and '+
                 'substringof(firstName,\''+names[0]+'\') and '+
                 'substringof(lastName,\''+names[1]+'\')';
    }
    else
    {
        filter = 'filter=year eq current and '+
                 '(substringof(firstName,\''+this+'\') or '+
                 'substringof(lastName,\''+this+'\') or '+
                 'substringof(hash,\''+this+'\') or '+
                 'substringof(email,\''+this+'\') or '+
                 'substringof(request_id,\''+this+'\'))';
    }
    $.ajax({
        url:  '../api/v1/tickets_history',
        data: filter,
        type: 'get',
        dataType: 'json',
        success: history_search_done,
        error: search_failed
    });
}

function get_ticket(hash)
{
    if(hash.indexOf('%') === 0)
    {
        really_search.call(hash);
        return;
    }
    var pos = hash.indexOf('transfer.php?id=');
    if(pos != -1)
    {
        pos+=16;
        hash = hash.substring(pos);
    }
    if(hash.length === 16)
    {
        hash = hash.substring(0, 8)+'%25'+hash.substring(8);
        $.ajax({
            url:  '../api/v1/tickets/?$filter=contains(hash,\''+hash+'\')',
            type: 'get',
            dataType: 'json',
            context: hash,
            success: found_ticket,
            error: really_search
        });    
    }
    else
    {
        $.ajax({
            url:  '../api/v1/tickets/'+hash,
            type: 'get',
            dataType: 'json',
            context: hash,
            success: found_ticket,
            error: really_search
        });
    }
}

function get_history(hash)
{
    $('#history_ticket_modal').modal('hide');
    if(hash.indexOf('%') === 0)
    {
        really_search_history.call(hash);
        return;
    }
    $.ajax({
        url:  '../api/v1/tickets/'+hash+'?with_history=1',
        type: 'get',
        dataType: 'json',
        context: hash,
        success: found_history,
        error: really_search_history
    });
}

function ticket_clicked()
{
    var table = $('#search_ticket_table').DataTable();
    var tr = $(this).closest('tr');
    var row = table.row(tr);
    if(tr.children('th').length > 0)
    {
        return;
    }
    found_ticket(row.data());
}

function history_clicked()
{
    var table = $('#history_ticket_table').DataTable();
    var tr = $(this).closest('tr');
    var row = table.row(tr);
    if(tr.children('th').length > 0)
    {
        return;
    }
    get_history(row.data().hash);
}

function ticket_search(evt)
{
    if(evt.which !== 13) return;
    var value = $(this).val();
    //Try this as a ticket
    get_ticket(value);
}

function history_search(evt)
{
    if(evt.which !== 13) return;
    var value = $(this).val();
    //Try this as a ticket
    get_history(value);
}

function focus_on_ticket_id()
{
    $('#physical_ticket_id').focus();
}

function focus_on_search()
{
    $('#ticket_search').val('');
    $('#ticket_search').focus();
}

function revert_screen()
{
    $('.navbar').show();
    $('#page-wrapper').css('margin', '0 0 0 250px').css('width', '').css('height', '');
    $('#screen').html('<span class="fa fa-arrows-alt"></span>').attr('title', 'fullscreen').unbind('click', revert_screen).click(fullscreen);
}

function fullscreen()
{
    $('.navbar').hide();
    $('#page-wrapper').css('width', '100%').css('height', '100%').css('margin', '0');
    $('#screen').html('<span class="fa fa-compress"></span>').attr('title', 'revert').unbind('click', fullscreen).click(revert_screen);
}

function gotEarlyEntry(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to obtain ticket current EE status!');
        return;
    }
    earlyEntry = jqXHR.responseJSON*1;
}

function error(err) {
    alert('Error: '+err);
}

function selectCamera() {
    var videoSource = $('#videoSource :selected')[0].value;
    for(var i = 0; i < myCameras.length; i++) {
        if(myCameras[i].id == videoSource) {
            scanner.start(myCameras[i]);
        }
    }
}

function gotCameras(cameras) {
    var haveVideo = false;
    myCameras = cameras;
    for(var i = 0; i < cameras.length; i++) {
        var option = document.createElement('option');
	option.text = cameras[i].name;
	option.value = cameras[i].id;
	$('#videoSource').append(option);
	haveVideo = true;
	if(cameras[i].name === null) {
            option.text = 'Camera '+i;
            if(i === 1) {
                //The back camera is almost always the second
                option.selected = true;
            }
            continue;
	}
	if(cameras[i].name.indexOf('Rear') !== -1 || cameras[i].name.indexOf('rear') !== -1 ||
	   cameras[i].name.indexOf('back') !== -1) {
            option.selected = true;
        }
    }
    if(haveVideo) {
        $('#videoSource').on('change', selectCamera);
        selectCamera();
    }
    else {
        $('#ticketCodeScan').hide();
    }
}

function enumError(err) {
    $('#ticketCodeScan').hide();
    console.log(err);
}

function codeScanned(content) {
    console.log(content);
    $('#qrcodeScan').modal('hide');
    get_ticket(content);
}

function init_gate_page()
{
    $.ajax({
        url: '../api/v1/globals/vars/currentEarlyEntry',
        type: 'get',
        dataType: 'json',
        complete: gotEarlyEntry});
    $('#ticket_search').keypress(ticket_search);
    $('#history_search').keypress(history_search);
    $('#process_ticket_modal').on('shown.bs.modal', focus_on_ticket_id);
    $('#process_ticket_modal').on('hidden.bs.modal', focus_on_search);
    $('#search_ticket_table').dataTable({
        'columns': [
            {'data': 'hash'},
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'}
        ]
    });
    $('#history_ticket_table').dataTable({
        'columns': [
            {'data': 'hash'},
            {'data': 'firstName'},
            {'data': 'lastName'},
            {'data': 'type'}
        ]
    });
    $('#search_ticket_table').on('click', 'tr', ticket_clicked);
    $('#history_ticket_table').on('click', 'tr', history_clicked);
    if(navigator.getUserMedia !== undefined) {
        Instascan.Camera.getCameras().then(gotCameras).catch(enumError);
        scanner = new Instascan.Scanner({video: document.getElementById('v'), mirror: false});
        scanner.addListener('scan', codeScanned);
    }
    else {
        enumError(null);
    }
}

$(init_gate_page);


