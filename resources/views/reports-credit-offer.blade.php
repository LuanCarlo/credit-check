<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Relatórios Simulação de Crédito</title>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">

    

        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {

            $.ajax({
                url: 'http://127.0.0.1:8000/api/getCreditValuesByMonth',
                method: 'GET',
                data: { },
                success: function(response) {
                    
                    if (response) {

                        const jsonObject = JSON.parse(response);
                        intituitions = jsonObject.record;

                        var chartData = [['Mês', 'Valor']];

                        intituitions.forEach(function(item) {
                            chartData.push([item.month, parseFloat(item.total_value)]);
                        });
            
                        var data = google.visualization.arrayToDataTable(chartData);

                        var options = {
                            title: 'Relatório de Valores por mês',
                            curveType: 'function',
                            legend: { position: 'bottom' }
                        };

                        var chartValueByMounth = new google.visualization.LineChart(document.getElementById('value_by_month'));
                        chartValueByMounth.draw(data, options);
                    }
                }
            });

            $.ajax({
                url: 'http://127.0.0.1:8000/api/creditValuesByModality',
                method: 'GET',
                data: { },
                success: function(response) {
                    
                    if (response) {

                        const jsonObject = JSON.parse(response);
                        intituitions = jsonObject.record;

                        var chartData = [['Modalidade', 'Valor']];

                        intituitions.forEach(function(item) {
                            chartData.push([item.modality, parseFloat(item.total_value)]);
                        });
            
                        var data = google.visualization.arrayToDataTable(chartData);

                        var options2 = {
                            title: 'Valor por modalidade',
                            hAxis: {title: 'Modalidade',  titleTextStyle: {color: '#333'}},
                            vAxis: {minValue: 0}
                        };

                        var chartValueByModality = new google.visualization.ColumnChart(document.getElementById('value_by_modality'));
                        chartValueByModality.draw(data, options2);
                    }
                }
            });

            $.ajax({
                url: 'http://127.0.0.1:8000/api/creditSimulationsByInstituition',
                method: 'GET',
                data: { },
                success: function(response) {
                    
                    if (response) {

                        const jsonObject = JSON.parse(response);
                        intituitions = jsonObject.record;

                        var chartData = [['Instituição', 'Qtd']];

                        intituitions.forEach(function(item) {
                            chartData.push([item.instituition, parseFloat(item.total_simulations)]);
                        });
            
                        var data = google.visualization.arrayToDataTable(chartData);

                        var options3 = {
                            title: 'Quantidade por Instituição',
                            hAxis: {title: 'Instituição',  titleTextStyle: {color: '#333'}},
                            vAxis: {minValue: 0}
                        };

                        var qtdByInstituicao = new google.visualization.PieChart(document.getElementById('qtd_by_instituicao'));
                        qtdByInstituicao.draw(data, options3);
                    }
                }
            });
        }
    </script>
    
</head>
<body>


<div class="container justify-content-center align-items-center full-height">

    <h1>Relatórios Graficos</h1>

    <div class="container-fluid d-flex justify-content-center align-items-center full-height">


        <div id="value_by_month" style="width: 900px; height: 500px"></div>

        <div id="value_by_modality" style="width: 900px; height: 500px"></div>
    </div>
    <div class="">
        <div id="qtd_by_instituicao" style="width: 900px; height: 500px"></div>
    <div>
</div>
    
</body>
</html>