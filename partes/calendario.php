 <div class="chart-section calendar-container">
				  <h3>Calendario Mensual de Visitas</h3>
				  <?php
				  // Query the first and last visit dates based on the current accounts clause.
				  $q = "SELECT MIN(date(timestamp)) as first_date, MAX(date(timestamp)) as last_date FROM logs WHERE 1 $accountsClause";
				  $stmtMinMax = $db->query($q);
				  $minmax = $stmtMinMax->fetchArray(SQLITE3_ASSOC);
				  $first_date = $minmax['first_date'];
				  $last_date = $minmax['last_date'];

				  // Build an associative array with visits per day.
				  $visitsPerDay = [];
				  $q2 = "SELECT date(timestamp) as day, COUNT(*) as visits FROM logs WHERE 1 $accountsClause GROUP BY day";
				  $result2 = $db->query($q2);
				  while ($row = $result2->fetchArray(SQLITE3_ASSOC)) {
				      $visitsPerDay[$row['day']] = $row['visits'];
				  }

				  if (!$first_date || !$last_date) {
				      echo "<p>No hay registros de visitas.</p>";
				  } else {
				      // Initialize DateTime objects.
				      $start = new DateTime($first_date);
				      // Start at the first day of the month for the earliest access.
				      $start->modify('first day of this month');
				      $end = new DateTime($last_date);
				      // End after the last month (to include the complete final month).
				      $end->modify('first day of next month');

				      // Loop month by month.
				      $current = clone $start;
				      while ($current < $end) {
				          $year = $current->format('Y');
				          $monthName = $current->format('F');
				          $daysInMonth = $current->format('t');
				          // Determine the day of week the first day falls on (1=Monday, 7=Sunday).
				          $firstDayOfWeek = (int)$current->format('N');

				          echo "<h4>$monthName $year</h4>";
				          echo "<table border='1' cellpadding='5' cellspacing='0'  class='calendar-table'>";
				          // Table header with abbreviated day names.
				          echo "<tr>";
				          $daysOfWeek = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
				          foreach ($daysOfWeek as $dayName) {
				              echo "<th>$dayName</th>";
				          }
				          echo "</tr><tr>";

				          // Fill in empty cells until the first day of the month.
				          for ($i = 1; $i < $firstDayOfWeek; $i++) {
				              echo "<td>&nbsp;</td>";
				          }

				          // Print each day of the month.
				          for ($day = 1; $day <= $daysInMonth; $day++) {
				              // Build a date string in Y-m-d format.
				              $currentDateStr = $current->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
				              echo "<td>";
				              echo "<strong>$day</strong><br>";
				              if (isset($visitsPerDay[$currentDateStr])) {
				                  echo $visitsPerDay[$currentDateStr] . " visitas";
				              }
				              echo "</td>";

				              // Determine current cell index (starting from 1).
				              $cellIndex = $firstDayOfWeek + $day - 1;
				              // If the cell is the last in a week, end the row.
				              if ($cellIndex % 7 == 0 && $day != $daysInMonth) {
				                  echo "</tr><tr>";
				              }
				          }

				          // Fill remaining cells in the last row if needed.
				          $lastCellIndex = $firstDayOfWeek + $daysInMonth - 1;
				          $remaining = 7 - ($lastCellIndex % 7);
				          if ($remaining < 7) {
				              for ($i = 0; $i < $remaining; $i++) {
				                  echo "<td>&nbsp;</td>";
				              }
				          }
				          echo "</tr>";
				          echo "</table><br>";

				          // Move to the next month.
				          $current->modify('first day of next month');
				      }
				  }
				  ?>
			 </div>
