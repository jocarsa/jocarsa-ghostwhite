<div class="chart-section">
    <h3>Calendario de Diagrama de Semanas (Heatmap)</h3>
    <?php
    // Query daily visit counts applying the current accounts restrictions.
    $visitsPerDay = [];
    $qHeat = "SELECT date(timestamp) as day, COUNT(*) as visits FROM logs WHERE 1 $accountsClause GROUP BY day";
    $resHeat = $db->query($qHeat);
    while ($row = $resHeat->fetchArray(SQLITE3_ASSOC)) {
        $visitsPerDay[$row['day']] = $row['visits'];
    }
    
    // For this heatmap, we show data for the current year.
    $year = date('Y');
    $startDate = new DateTime("$year-01-01");
    $endDate = new DateTime("$year-12-31");
    
    // Build a grid based on ISO weeks.
    // Iterate day by day to build an array keyed by week number and day-of-week (1 = Monday, 7 = Sunday).
    $heatmap = [];
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        $week = $currentDate->format("W"); // ISO week number
        $dayOfWeek = $currentDate->format("N"); // 1 (Monday) to 7 (Sunday)
        $dateStr = $currentDate->format("Y-m-d");
        $visits = isset($visitsPerDay[$dateStr]) ? $visitsPerDay[$dateStr] : 0;
        $heatmap[$week][$dayOfWeek] = ['date' => $dateStr, 'visits' => $visits];
        $currentDate->modify('+1 day');
    }
    
    // Determine the maximum number of visits in a day (for color scaling).
    $maxVisits = 0;
    foreach ($visitsPerDay as $count) {
        if ($count > $maxVisits) {
            $maxVisits = $count;
        }
    }
    
    // Helper function: Returns a background color based on the visit count.
    function getHeatmapColor($visits, $maxVisits) {
        if ($visits == 0) {
            return "#ebedf0";
        }
        $ratio = $visits / ($maxVisits ? $maxVisits : 1);
        if ($ratio < 0.25) {
            return "#c6e48b";
        } elseif ($ratio < 0.5) {
            return "#7bc96f";
        } elseif ($ratio < 0.75) {
            return "#239a3b";
        } else {
            return "#196127";
        }
    }
    
    // Get sorted week numbers.
    $weeks = array_keys($heatmap);
    sort($weeks);
    
    // Build an array of month names for each week.
    $monthHeaders = [];
    foreach ($weeks as $wk) {
        $dt = new DateTime();
        $dt->setISODate($year, intval($wk));
        $monthHeaders[] = $dt->format("M");
    }
    
    // Group consecutive weeks that have the same month.
    $groups = [];
    $prev = null;
    $count = 0;
    foreach ($monthHeaders as $i => $month) {
        if ($month === $prev) {
            $count++;
        } else {
            if ($prev !== null) {
                $groups[] = ['month' => $prev, 'colspan' => $count];
            }
            $prev = $month;
            $count = 1;
        }
    }
    if ($prev !== null) {
        $groups[] = ['month' => $prev, 'colspan' => $count];
    }
    ?>
    <!-- Display the Year -->
    <div class="heatmap-year">
        <strong>Año <?php echo $year; ?></strong>
    </div>
    <div class="heatmap-container">
        <table class="heatmap-table">
            <!-- Month Header Row -->
            <tr>
                <th></th>
                <?php
                foreach ($groups as $group) {
                    echo "<th colspan='" . $group['colspan'] . "'>" . $group['month'] . "</th>";
                }
                ?>
            </tr>
            <!-- Week Numbers Header Row -->
            <tr>
                <th></th>
                <?php foreach ($weeks as $wk) { echo "<th>$wk</th>"; } ?>
            </tr>
            <?php
            // Days of week: 1 = Monday, …, 7 = Sunday.
            $dayNames = [1 => "Lun", 2 => "Mar", 3 => "Mié", 4 => "Jue", 5 => "Vie", 6 => "Sáb", 7 => "Dom"];
            for ($day = 1; $day <= 7; $day++) {
                echo "<tr>";
                // Row header: abbreviated day name.
                echo "<th>" . $dayNames[$day] . "</th>";
                foreach ($weeks as $wk) {
                    if (isset($heatmap[$wk][$day])) {
                        $cell = $heatmap[$wk][$day];
                        $color = getHeatmapColor($cell['visits'], $maxVisits);
                        $tooltip = $cell['date'] . ": " . $cell['visits'] . " visitas";
                        echo "<td style='background-color: $color;' title='$tooltip'></td>";
                    } else {
                        echo "<td></td>";
                    }
                }
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</div>
