<div class="chart-section">
                <h3>Resoluciones de Pantalla</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'bar'); updateChartPreference('resolutions', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'line'); updateChartPreference('resolutions', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'pie'); updateChartPreference('resolutions', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'horizontal'); updateChartPreference('resolutions', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'table'); updateChartPreference('resolutions', 'table');">Tabla</button>

                </div>
                <div id="chart-resolutions" class="chart-container"></div>
            </div>
            <script>
                var dataResolutions = <?php echo json_encode($dataResolutions); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-resolutions", dataResolutions, chartPreferences['resolutions'] || 'bar');
                });
            </script>
