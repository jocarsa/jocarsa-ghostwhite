<?php
    // Define a condition for bot user agents.
    // Adjust keywords as needed.
    $botCondition = "user_agent LIKE '%bot%' OR user_agent LIKE '%spider%' OR user_agent LIKE '%crawl%' OR user_agent LIKE '%slurp%'";

    // Get overall counts.
    $queryBots = "SELECT COUNT(*) as count FROM logs WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause . " AND ($botCondition)";
    $stmtBots = $db->prepare($queryBots);
    $stmtBots->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmtBots->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    $resultBots = $stmtBots->execute();
    $botsOverall = $resultBots->fetchArray(SQLITE3_ASSOC)['count'];

    $queryTotal = "SELECT COUNT(*) as count FROM logs WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
    $stmtTotal = $db->prepare($queryTotal);
    $stmtTotal->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmtTotal->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    $resultTotal = $stmtTotal->execute();
    $totalOverall = $resultTotal->fetchArray(SQLITE3_ASSOC)['count'];

    $humansOverall = $totalOverall - $botsOverall;

    // Daily breakdown: one row per day with counts for bots and humans.
    $dailyData = [];
    $qDaily = "SELECT date(timestamp) as label,
         SUM(CASE WHEN ($botCondition) THEN 1 ELSE 0 END) as bot_visits,
         SUM(CASE WHEN NOT ($botCondition) THEN 1 ELSE 0 END) as human_visits
         FROM logs
         WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause . "
         GROUP BY label
         ORDER BY label";
    $stmtDaily = $db->prepare($qDaily);
    $stmtDaily->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmtDaily->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    $resDaily = $stmtDaily->execute();
    while ($row = $resDaily->fetchArray(SQLITE3_ASSOC)) {
        $dailyData[] = $row;
    }
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
    <!-- Chart for daily robot visits -->
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

    <!-- Chart for daily human visits -->
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
      // Prepare arrays for the aggregated and daily data.
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

      document.addEventListener("DOMContentLoaded", function(){
          // Render the overall total chart using the user's saved preference (default to 'pie').
          redrawChart("chart-total", dataTotal, chartPreferences['total'] || 'pie');
          // Render the daily breakdown charts.
          redrawChart("chart-robots", dataRobots, chartPreferences['robots'] || 'bar');
          redrawChart("chart-humans", dataHumans, chartPreferences['humans'] || 'bar');
      });
    </script>
