<?php
    // (Server-side PHP code remains unchanged)
?>
<h2>Robots vs Humanos</h2>
<p><strong>Total Robots:</strong> <?php echo $botsOverall; ?></p>
<p><strong>Total Humanos:</strong> <?php echo $humansOverall; ?></p>

<!-- Total Aggregated Chart -->
<h3>Total Robots vs Humanos</h3>
<div class="chart-section">
  <div class="chart-controls">
      <button onclick="redrawChart('chart-total', dataTotal, 'bar'); updateChartPreference('total', 'bar');">Barra</button>
      <button onclick="redrawChart('chart-total', dataTotal, 'line'); updateChartPreference('total', 'line');">Línea</button>
      <button onclick="redrawChart('chart-total', dataTotal, 'pie'); updateChartPreference('total', 'pie');">Pastel</button>
      <button onclick="redrawChart('chart-total', dataTotal, 'horizontal'); updateChartPreference('total', 'horizontal');">Horizontal</button>
      <button onclick="redrawChart('chart-total', dataTotal, 'table'); updateChartPreference('total', 'table');">Tabla</button>
  </div>
  <div id="chart-total" class="chart-container"></div>
</div>

<!-- Daily Breakdown Charts -->
<h3>Desglose Diario</h3>
<div class="chart-section">
  <h4>Robots Diarios</h4>
  <div class="chart-controls">
      <button onclick="redrawChart('chart-robots', dataRobots, 'bar'); updateChartPreference('robots', 'bar');">Barra</button>
      <button onclick="redrawChart('chart-robots', dataRobots, 'line'); updateChartPreference('robots', 'line');">Línea</button>
      <button onclick="redrawChart('chart-robots', dataRobots, 'pie'); updateChartPreference('robots', 'pie');">Pastel</button>
      <button onclick="redrawChart('chart-robots', dataRobots, 'horizontal'); updateChartPreference('robots', 'horizontal');">Horizontal</button>
      <button onclick="redrawChart('chart-robots', dataRobots, 'table'); updateChartPreference('robots', 'table');">Tabla</button>
  </div>
  <div id="chart-robots" class="chart-container"></div>
</div>

<div class="chart-section">
  <h4>Humanos Diarios</h4>
  <div class="chart-controls">
      <button onclick="redrawChart('chart-humans', dataHumans, 'bar'); updateChartPreference('humans', 'bar');">Barra</button>
      <button onclick="redrawChart('chart-humans', dataHumans, 'line'); updateChartPreference('humans', 'line');">Línea</button>
      <button onclick="redrawChart('chart-humans', dataHumans, 'pie'); updateChartPreference('humans', 'pie');">Pastel</button>
      <button onclick="redrawChart('chart-humans', dataHumans, 'horizontal'); updateChartPreference('humans', 'horizontal');">Horizontal</button>
      <button onclick="redrawChart('chart-humans', dataHumans, 'table'); updateChartPreference('humans', 'table');">Tabla</button>
  </div>
  <div id="chart-humans" class="chart-container"></div>
</div>

<script>
      var dataTotal = [
          { label: "Robots", visits: <?php echo $botsOverall; ?> },
          { label: "Humanos", visits: <?php echo $humansOverall; ?> }
      ];
      var dataRobots = [];
      var dataHumans = [];
      <?php foreach($dailyData as $d): ?>
         dataRobots.push({ label: "<?php echo $d['label']; ?>", visits: <?php echo $d['bot_visits']; ?> });
         dataHumans.push({ label: "<?php echo $d['label']; ?>", visits: <?php echo $d['human_visits']; ?> });
      <?php endforeach; ?>
      (function(){
          function renderCharts(){
              redrawChart("chart-total", dataTotal, chartPreferences['total'] || 'pie');
              redrawChart("chart-robots", dataRobots, chartPreferences['robots'] || 'bar');
              redrawChart("chart-humans", dataHumans, chartPreferences['humans'] || 'bar');
          }
          if(document.readyState === "complete" || document.readyState === "interactive"){
              renderCharts();
          } else {
              window.addEventListener("DOMContentLoaded", renderCharts);
          }
      })();
</script>

