<div class="chart-section">
    <h3>Navegadores</h3>
    <div class="chart-controls">
        <button onclick="redrawChart('chart-browsers', dataBrowsers, 'bar'); updateChartPreference('browsers', 'bar');">Barra</button>
        <button onclick="redrawChart('chart-browsers', dataBrowsers, 'line'); updateChartPreference('browsers', 'line');">Línea</button>
        <button onclick="redrawChart('chart-browsers', dataBrowsers, 'pie'); updateChartPreference('browsers', 'pie');">Pastel</button>
        <button onclick="redrawChart('chart-browsers', dataBrowsers, 'horizontal'); updateChartPreference('browsers', 'horizontal');">Horizontal</button>
        <button onclick="redrawChart('chart-browsers', dataBrowsers, 'table'); updateChartPreference('browsers', 'table');">Tabla</button>
    </div>
    <div id="chart-browsers" class="chart-container"></div>
</div>
<script>
    var dataBrowsers = <?php echo json_encode($dataBrowsers); ?>;
    (function(){
        function renderChart(){
            redrawChart("chart-browsers", dataBrowsers, chartPreferences['browsers'] || 'bar');
        }
        if(document.readyState === "complete" || document.readyState === "interactive"){
            renderChart();
        } else {
            window.addEventListener("DOMContentLoaded", renderChart);
        }
    })();
</script>

