function generation_done(data)
{
    console.log(data);
}

function gen_tickets()
{
    var total_count = 0;
    var elements = $('#gen_form [type="text"]');
    for(var i = 0; i < elements.length; i++)
    {
        total_count += 1*$(elements[i]).val();
    }
    if(total_count == 0)
    {
        alert("No additional tickets created!");
        return false;
    }
    $.ajax({
        url: 'ajax/tickets.php',
        type: 'post',
        data: $('#gen_form').serialize(),
        dataType: 'json',
        success: generation_done});
    return false
}
