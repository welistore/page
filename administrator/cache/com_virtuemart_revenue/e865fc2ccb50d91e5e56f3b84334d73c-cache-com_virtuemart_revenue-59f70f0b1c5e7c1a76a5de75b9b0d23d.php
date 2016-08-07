<?php die("Access Denied"); ?>#x#a:2:{s:6:"output";s:0:"";s:6:"result";a:2:{s:6:"report";a:0:{}s:2:"js";s:1419:"
  google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Day', 'Orders', 'Total Items sold', 'Revenue net'], ['2016-01-11', 0,0,0], ['2016-01-12', 0,0,0], ['2016-01-13', 0,0,0], ['2016-01-14', 0,0,0], ['2016-01-15', 0,0,0], ['2016-01-16', 0,0,0], ['2016-01-17', 0,0,0], ['2016-01-18', 0,0,0], ['2016-01-19', 0,0,0], ['2016-01-20', 0,0,0], ['2016-01-21', 0,0,0], ['2016-01-22', 0,0,0], ['2016-01-23', 0,0,0], ['2016-01-24', 0,0,0], ['2016-01-25', 0,0,0], ['2016-01-26', 0,0,0], ['2016-01-27', 0,0,0], ['2016-01-28', 0,0,0], ['2016-01-29', 0,0,0], ['2016-01-30', 0,0,0], ['2016-01-31', 0,0,0], ['2016-02-01', 0,0,0], ['2016-02-02', 0,0,0], ['2016-02-03', 0,0,0], ['2016-02-04', 0,0,0], ['2016-02-05', 0,0,0], ['2016-02-06', 0,0,0], ['2016-02-07', 0,0,0], ['2016-02-08', 0,0,0]  ]);
        var options = {
          title: 'Report for the period from Monday, 11 January 2016 to Tuesday, 09 February 2016',
            series: {0: {targetAxisIndex:0},
                   1:{targetAxisIndex:0},
                   2:{targetAxisIndex:1},
                  },
                  colors: ["#00A1DF", "#A4CA37","#E66A0A"],
        };

        var chart = new google.visualization.LineChart(document.getElementById('vm_stats_chart'));

        chart.draw(data, options);
      }
";}}