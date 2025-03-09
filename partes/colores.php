<div class="chart-section">
    <h3>Profundidad de Color</h3>
    <div class="chart-controls">
        <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'bar'); updateChartPreference('color_depth', 'bar');">Barra</button>
        <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'line'); updateChartPreference('color_depth', 'line');">Línea</button>
        <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'pie'); updateChartPreference('color_depth', 'pie');">Pastel</button>
        <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'horizontal'); updateChartPreference('color_depth', 'horizontal');">Horizontal</button>
        <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'table'); updateChartPreference('color_depth', 'table');">Tabla</button>
    </div>
    <div id="chart-color-depth" class="chart-container"></div>
</div>
<script>
    var dataColorDepth = <?php echo json_encode($dataColorDepth); ?>;
    (function(){
        function renderChart() {
            redrawChart("chart-color-depth", dataColorDepth, chartPreferences['color_depth'] || 'bar');
        }
        if(document.readyState === "complete" || document.readyState === "interactive"){
            renderChart();
        } else {
            window.addEventListener("DOMContentLoaded", renderChart);
        }
    })();
</script>

