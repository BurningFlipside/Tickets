function tickets_done(data)
{
    var label_array = [];
    var data_array = [];
    console.log(data);
    for(i = 0; data[i] !== undefined; i++)
    {
        label_array.push(data[i].description);
        data_array.push(data[i].count);
    }
    console.log(label_array);
    console.log(data_array);
    var ctx = $("#ticket_type_chart").get(0).getContext("2d");
    var chart_data = {
        type: 'pie',
        labels: label_array,
        datasets: [
            {
                fillColor: "rgba(151,187,205,0.5)",
                strokeColor: "rgba(151,187,205,0.8)",
                highlightFill: "rgba(151,187,205,0.75)",
                highlightStroke: "rgba(151,187,205,1)",
                data: data_array
            }
        ]
    };
    new Chart(ctx, chart_data);
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

var chart = null;
var chartData = {
  type: 'pie',
  labels: [],
  datasets: [
    {
      data: []
    }
  ]
};

function gotTicketType(jqXHR){
  if(jqXHR.status !== 200) {
    alert('Unable to get ticket type!');
    return;
  }
  if(chart == null) {
    var ctx = $("#ticket_type_chart").get(0).getContext("2d");
    chart = new Chart(ctx, chartData);
  }
  chartData.labels.push(this.label);
  chartData.datasets[0].data.push(jqXHR.responseJSON['@odata.count']);
  console.log(chartData);
  chart.update(chartData);
}

function gotTicketTypes(jqXHR){
  if(jqXHR.status !== 200) {
    alert('Unable to get ticket types!');
    return;
  }
  var data = [];
  for(var i = 0; i < jqXHR.responseJSON.length; i++)
  {
    var obj = {label: jqXHR.responseJSON[i].description, type: jqXHR.responseJSON[i].typeCode};
    $.ajax({
      url: '../api/v1/tickets?$filter=year%20eq%20current%20and%20type%20eq%20%27'+jqXHR.responseJSON[i].typeCode+'%27&$count=true&$select=@odata.count',
      type: 'get',
      context: obj,
      complete: gotTicketType
    });
  }
}

function initPage(){
  $.ajax({
    url: '../api/v1/tickets/types',
    type: 'get',
    complete: gotTicketTypes
  });
}

$(initPage);
