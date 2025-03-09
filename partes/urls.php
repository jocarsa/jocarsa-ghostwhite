<div class="chart-section">
                <h3>URLs Visitadas</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-urls', dataUrls, 'bar'); updateChartPreference('urls', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-urls', dataUrls, 'line'); updateChartPreference('urls', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-urls', dataUrls, 'pie'); updateChartPreference('urls', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-urls', dataUrls, 'horizontal'); updateChartPreference('urls', 'horizontal');">Horizontal</button>
               	    <button onclick="redrawChart('chart-urls', dataUrls, 'table'); updateChartPreference('urls', 'table');">Tabla</button>

                </div>
                <div id="chart-urls" class="chart-container"></div>
            </div>
            <script>
                var dataUrls = <?php echo json_encode($dataUrls); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-urls", dataUrls, chartPreferences['urls'] || 'bar');
                });
            </script>
