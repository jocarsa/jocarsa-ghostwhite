<div class="chart-section">
    <h3>Visitas Por Día</h3>
    <div class="chart-controls">
        <button onclick="redrawChart('chart-day', dataDay, 'bar'); updateChartPreference('day', 'bar');">Barra</button>
        <button onclick="redrawChart('chart-day', dataDay, 'line'); updateChartPreference('day', 'line');">Línea</button>
        <button onclick="redrawChart('chart-day', dataDay, 'pie'); updateChartPreference('day', 'pie');">Pastel</button>
        <button onclick="redrawChart('chart-day', dataDay, 'horizontal'); updateChartPreference('day', 'horizontal');">Horizontal</button>
        <button onclick="redrawChart('chart-day', dataDay, 'table'); updateChartPreference('day', 'table');">Tabla</button>
    </div>
    <div id="chart-day" class="chart-container"></div>
</div>
<div class="chart-section">
    <h3>Visitas Por Semana</h3>
    <div class="chart-controls">
        <button onclick="redrawChart('chart-week', dataWeek, 'bar'); updateChartPreference('week', 'bar');">Barra</button>
        <button onclick="redrawChart('chart-week', dataWeek, 'line'); updateChartPreference('week', 'line');">Línea</button>
        <button onclick="redrawChart('chart-week', dataWeek, 'pie'); updateChartPreference('week', 'pie');">Pastel</button>
        <button onclick="redrawChart('chart-week', dataWeek, 'horizontal'); updateChartPreference('week', 'horizontal');">Horizontal</button>
        <button onclick="redrawChart('chart-week', dataWeek, 'table'); updateChartPreference('week', 'table');">Tabla</button>
    </div>
    <div id="chart-week" class="chart-container"></div>
</div>
<div class="chart-section">
    <h3>Visitas Por Mes</h3>
    <div class="chart-controls">
        <button onclick="redrawChart('chart-month', dataMonth, 'bar'); updateChartPreference('month', 'bar');">Barra</button>
        <button onclick="redrawChart('chart-month', dataMonth, 'line'); updateChartPreference('month', 'line');">Línea</button>
        <button onclick="redrawChart('chart-month', dataMonth, 'pie'); updateChartPreference('month', 'pie');">Pastel</button>
        <button onclick="redrawChart('chart-month', dataMonth, 'horizontal'); updateChartPreference('month', 'horizontal');">Horizontal</button>
        <button onclick="redrawChart('chart-month', dataMonth, 'table'); updateChartPreference('month', 'table');">Tabla</button>
    </div>
    <div id="chart-month" class="chart-container"></div>
</div>
<div class="chart-section">
    <h3>Visitas Por Hora (Últimas 24 Horas)</h3>
    <div class="chart-controls">
        <button onclick="redrawChart('chart-hour', dataHour, 'bar'); updateChartPreference('hour', 'bar');">Barra</button>
        <button onclick="redrawChart('chart-hour', dataHour, 'line'); updateChartPreference('hour', 'line');">Línea</button>
        <button onclick="redrawChart('chart-hour', dataHour, 'pie'); updateChartPreference('hour', 'pie');">Pastel</button>
        <button onclick="redrawChart('chart-hour', dataHour, 'horizontal'); updateChartPreference('hour', 'horizontal');">Horizontal</button>
        <button onclick="redrawChart('chart-hour', dataHour, 'table'); updateChartPreference('hour', 'table');">Tabla</button>
    </div>
    <div id="chart-hour" class="chart-container"></div>
</div>
<script>
    var dataDay = <?php echo json_encode($dataDay); ?>;
    var dataWeek = <?php echo json_encode($dataWeek); ?>;
    var dataMonth = <?php echo json_encode($dataMonth); ?>;
    var dataHour = <?php echo json_encode($dataHour); ?>;
    (function(){
        function renderCharts(){
            redrawChart("chart-day", dataDay, chartPreferences['day'] || 'bar');
            redrawChart("chart-week", dataWeek, chartPreferences['week'] || 'bar');
            redrawChart("chart-month", dataMonth, chartPreferences['month'] || 'bar');
            redrawChart("chart-hour", dataHour, chartPreferences['hour'] || 'bar');
        }
        if(document.readyState === "complete" || document.readyState === "interactive"){
            renderCharts();
        } else {
            window.addEventListener("DOMContentLoaded", renderCharts);
        }
    })();
</script>

