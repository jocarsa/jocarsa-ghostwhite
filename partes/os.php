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
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-os", dataOS, chartPreferences['os'] || 'bar');
                });
            </script>
