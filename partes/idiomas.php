<div class="chart-section">
    <h3>Idiomas</h3>
    <div class="chart-controls">
        <button onclick="redrawChart('chart-languages', dataLanguages, 'bar'); updateChartPreference('languages', 'bar');">Barra</button>
        <button onclick="redrawChart('chart-languages', dataLanguages, 'line'); updateChartPreference('languages', 'line');">Línea</button>
        <button onclick="redrawChart('chart-languages', dataLanguages, 'pie'); updateChartPreference('languages', 'pie');">Pastel</button>
        <button onclick="redrawChart('chart-languages', dataLanguages, 'horizontal'); updateChartPreference('languages', 'horizontal');">Horizontal</button>
        <button onclick="redrawChart('chart-languages', dataLanguages, 'table'); updateChartPreference('languages', 'table');">Tabla</button>
    </div>
    <div id="chart-languages" class="chart-container"></div>
</div>
<script>
    var dataLanguages = <?php echo json_encode($dataLanguages); ?>;
    (function(){
        function renderChart(){
            redrawChart("chart-languages", dataLanguages, chartPreferences['languages'] || 'bar');
        }
        if(document.readyState === "complete" || document.readyState === "interactive"){
            renderChart();
        } else {
            window.addEventListener("DOMContentLoaded", renderChart);
        }
    })();
</script>

