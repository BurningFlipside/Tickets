var id;
var pools;

function opDone(jqXHR)
{
    if(jqXHR.status === 200)
    {
        alert('Success!');
        $('#pools tbody').empty();
        initTable();
    }
    else
    {
        alert('Error!');
        console.log(jqXHR);
    }
}

function deletePool(really)
{
    if(really)
    {
        $.ajax({
            url: '../api/v1/pools/'+id,
            method: 'delete',
            complete: opDone
        });
    }
}

function deletePoolDialog(_id)
{
    id = _id;
    bootbox.confirm("Are you sure you want to delete this pool?", deletePool);
}

function editDialogPool(_id)
{
    id = _id;
    $('#_id').html(_id);
    $('#pool_name').val(pools[_id].pool_name);
    $('#group_name').val(pools[_id].group_name);
    $('#editModal').modal('show');
}

function gotPools(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        alert('Unable to obtain pools!');
        return;
    }
    var data = jqXHR.responseJSON;
    pools = [];
    var tbody = $('#pools tbody');
    for(var i = 0; i < data.length; i++)
    {
        var myID = data[i].pool_id;
        pools[myID] = data[i];
        tbody.append('<tr><td><button class="btn btn-link" onclick="deletePoolDialog('+myID+')" title="Delete Pool"><i class="fa fa-times"></i></button>'+
                     '<button class="btn btn-link" onclick="editDialogPool('+myID+')" title="Edit Pool"><i class="fa fa-pencil"></i></button>'+
                     '<button class="btn btn-link" onclick="poolStats('+myID+')" title="Pool Stats"><i class="fa fa-bar-chart"></i></button>'+
                     '</td><td>'+myID+'</td><td>'+data[i].pool_name+'</td><td>'+data[i].group_name+'</td></tr>');
    }
    tbody.append('<tr><td><button class="btn btn-link" onclick="newPool()" title="Add Pool"><i class="fa fa-plus"></i></button></td><td colspan=3"></td></tr>');
}

function gotGroups(jqXHR)
{
    if(jqXHR.status !== 200 || jqXHR.responseJSON === undefined)
    {
        return;
    }
    var names = jqXHR.responseJSON;
    for(var i = 0; i < names.length; i++)
    {
        names[i] = names[i].cn;
    }
    var group_names = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.whitespace,
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        local: names});
    $('#group_name').typeahead(null, {name: 'group_name', source: group_names});
}

function initTable()
{
    $.ajax({
        url: '../api/v1/pools',
        method: 'get',
        complete: gotPools
    });
}

function initPage()
{
    $.ajax({
        url: 'https://profiles.burningflipside.com/api/v1/groups?$select=cn',
        type: 'get',
        dataType: 'json',
        xhrFields: {withCredentials: true},
        complete: gotGroups});
    $("#editModal").modal({"show":false});
    initTable();
}

$(initPage);
