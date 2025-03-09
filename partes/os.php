<div class="chart-section">
    <h3>Sistemas Operativos</h3>
    <div class="chart-controls">
        <button onclick="redrawChart('chart-os', dataOS, 'bar'); updateChartPreference('os', 'bar');">Barra</button>
        <button onclick="redrawChart('chart-os', dataOS, 'line'); updateChartPreference('os', 'line');">Línea</button>
        <button onclick="redrawChart('chart-os', dataOS, 'pie'); updateChartPreference('os', 'pie');">Pastel</button>
        <button onclick="redrawChart('chart-os', dataOS, 'horizontal'); updateChartPreference('os', 'horizontal');">Horizontal</button>
        <button onclick="redrawChart('chart-os', dataOS, 'table'); updateChartPreference('os', 'table');">Tabla</button>
    </div>
    <div id="chart-os" class="chart-container"></div>
</div>
<script>
    var dataOS = <?php echo json_encode($dataOS); ?>;
    (function(){
        function renderChart() {
            console.log('Rendering OS chart using preference:', chartPreferences['os'] || 'bar');
            redrawChart("chart-os", dataOS, chartPreferences['os'] || 'bar');
        }
        if(document.readyState === "complete" || document.readyState === "interactive"){
            console.log('Document ready: calling renderChart() for OS chart immediately.');
            renderChart();
        } else {
            console.log('Document not ready: waiting for DOMContentLoaded event for OS chart.');
            window.addEventListener("DOMContentLoaded", function(){
                console.log('DOMContentLoaded event fired: rendering OS chart.');
                renderChart();
            });
        }
    })();
</script>

