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
        // Create a unique chart container ID (remove any non-alphanumeric characters)
        $chartId = "chart-dashboard-" . preg_replace("/[^a-zA-Z0-9_-]/", "", $account);
?>
<div class="chart-section">
    <h3>Visitas Por Día - <?php echo htmlspecialchars($account); ?></h3>
    <div id="<?php echo $chartId; ?>" class="chart-container"></div>
    <script>
      // Pass the PHP data to JavaScript.
      var dataDashboard_<?php echo $chartId; ?> = <?php echo json_encode($dataDashboard); ?>;
      // Render the chart once the DOM is loaded.
      document.addEventListener("DOMContentLoaded", function(){
          // Use a unique key (e.g., 'dashboard_<?php echo $chartId; ?>') for saving the chart preference.
          redrawChart("<?php echo $chartId; ?>", dataDashboard_<?php echo $chartId; ?>, chartPreferences['dashboard_<?php echo $chartId; ?>'] || 'bar');
      });
    </script>
</div>
<?php
    endforeach;
    echo '</div>';
}
?>

