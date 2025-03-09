<div class="chart-section">
                <h3>IPs</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-ips', dataIps, 'bar'); updateChartPreference('ips', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-ips', dataIps, 'line'); updateChartPreference('ips', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-ips', dataIps, 'pie'); updateChartPreference('ips', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-ips', dataIps, 'horizontal'); updateChartPreference('ips', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-ips', dataIps, 'table'); updateChartPreference('ips', 'table');">Tabla</button>

                </div>
                <div id="chart-ips" class="chart-container"></div>
            </div>
            <script>
                var dataIps = <?php echo json_encode($dataIps); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-ips", dataIps, chartPreferences['ips'] || 'bar');
                });
            </script>
