<?php
// This file displays a grid of "Visitas Por Día" charts for each account
// assigned to the logged-in user.

if (empty($assignedAccounts)) {
    echo "<p>No hay cuentas asignadas para este usuario.</p>";
} else {
    echo '<div class="chart-grid">';
    foreach ($assignedAccounts as $account):
        // Query daily visits for this account.
        $dataDashboard = [];
        $stmt = $db->prepare("SELECT date(timestamp) as label, count(*) as visits 
                              FROM logs 
                              WHERE user = :account 
                                AND date(timestamp) BETWEEN :startDate AND :endDate 
                              GROUP BY label 
                              ORDER BY label");
        $stmt->bindValue(':account', $account, SQLITE3_TEXT);
        $stmt->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmt->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        $res = $stmt->execute();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $dataDashboard[] = $row;
        }
        // Create a unique chart container ID by replacing non-alphanumeric characters with an underscore.
        $chartId = "chart_dashboard_" . preg_replace("/[^a-zA-Z0-9_]/", "", $account);
?>
<div class="chart-section">
    <h3>Visitas Por Día - <?php echo htmlspecialchars($account); ?></h3>
    <!-- Chart type controls -->
    <div class="chart-controls">
      <button onclick="redrawChart('<?php echo $chartId; ?>', window.dashboardData['<?php echo $chartId; ?>'], 'bar'); updateChartPreference('dashboard_<?php echo $chartId; ?>', 'bar');">Barra</button>
      <button onclick="redrawChart('<?php echo $chartId; ?>', window.dashboardData['<?php echo $chartId; ?>'], 'line'); updateChartPreference('dashboard_<?php echo $chartId; ?>', 'line');">Línea</button>
      <button onclick="redrawChart('<?php echo $chartId; ?>', window.dashboardData['<?php echo $chartId; ?>'], 'pie'); updateChartPreference('dashboard_<?php echo $chartId; ?>', 'pie');">Pastel</button>
      <button onclick="redrawChart('<?php echo $chartId; ?>', window.dashboardData['<?php echo $chartId; ?>'], 'horizontal'); updateChartPreference('dashboard_<?php echo $chartId; ?>', 'horizontal');">Horizontal</button>
      <button onclick="redrawChart('<?php echo $chartId; ?>', window.dashboardData['<?php echo $chartId; ?>'], 'table'); updateChartPreference('dashboard_<?php echo $chartId; ?>', 'table');">Tabla</button>
    </div>
    <div id="<?php echo $chartId; ?>" class="chart-container"></div>
    <script>
      (function(){
          // Save the JSON data in a global object so it can be referenced later.
          window.dashboardData = window.dashboardData || {};
          window.dashboardData["<?php echo $chartId; ?>"] = <?php echo json_encode($dataDashboard); ?>;
          // Function to render the chart.
          var renderChart = function(){
              redrawChart("<?php echo $chartId; ?>", window.dashboardData["<?php echo $chartId; ?>"], chartPreferences['dashboard_<?php echo $chartId; ?>'] || 'bar');
          };
          // If the document is still loading, wait for DOMContentLoaded.
          if (document.readyState === "loading") {
              document.addEventListener("DOMContentLoaded", renderChart);
          } else {
              renderChart();
          }
      })();
    </script>
</div>
<?php
    endforeach;
    echo '</div>';
}
?>

