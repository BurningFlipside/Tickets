function resize()
{
    var topOffset = 50;
    var width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
    if(width < 768)
    {
        $('div.navbar-collapse').addClass('collapse');
        topOffset = 100; // 2-row-menu
    }
    else
    {
        $('div.navbar-collapse').removeClass('collapse');
    }

    var height = (this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height;
    height = height - topOffset;
    if(height < 1)
    {
         height = 1;
    }
    if(height > topOffset)
    {
        $("#page-wrapper").css("min-height", (height) + "px");
    }
}

function make_active_link(index, element)
{
    var href = $(element).attr('href');
    var pathArray = window.location.pathname.split( '/' );
    if(href.toUpperCase() == pathArray[3].toUpperCase())
    {
        $(element).attr('class', 'active');
    }
}

function init_side_menu()
{
    $('#side-menu').metisMenu();
    $(window).bind('load resize', resize);
    $('.sidebar a').each(make_active_link);
}

$(init_side_menu);
