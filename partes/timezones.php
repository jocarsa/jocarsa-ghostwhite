<div class="chart-section">
                <h3>Zonas Horarias</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-timezones', dataTimezones, 'bar'); updateChartPreference('timezones', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-timezones', dataTimezones, 'line'); updateChartPreference('timezones', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-timezones', dataTimezones, 'pie'); updateChartPreference('timezones', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-timezones', dataTimezones, 'horizontal'); updateChartPreference('timezones', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-timezones', dataTimezones, 'table'); updateChartPreference('timezones', 'table');">Tabla</button>

                </div>
                <div id="chart-timezones" class="chart-container"></div>
            </div>
            <script>
                var dataTimezones = <?php echo json_encode($dataTimezones); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-timezones", dataTimezones, chartPreferences['timezones'] || 'bar');
                });
            </script>
