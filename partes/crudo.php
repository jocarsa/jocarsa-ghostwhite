<h3>Datos Sin Procesar de Analítica</h3>
            <?php
              $q = "SELECT * FROM logs WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
              if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
              $q .= " ORDER BY id DESC";
              $stmtRaw = $db->prepare($q);
              $stmtRaw->bindValue(':startDate', $startDate, SQLITE3_TEXT);
              $stmtRaw->bindValue(':endDate', $endDate, SQLITE3_TEXT);
              if ($filterUser !== '') { $stmtRaw->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
              $resRaw = $stmtRaw->execute();
            ?>
            <table>
              <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Agente de Usuario</th>
                <th>Pantalla (WxH)</th>
                <th>Ventana Gráfica (WxH)</th>
                <th>Idioma</th>
                <th>Idiomas</th>
                <th>Desfase de Zona Horaria</th>
                <th>Plataforma</th>
                <th>Conexión</th>
                <th>Profundidad de Color</th>
                <th>URL</th>
                <th>Referente</th>
                <th>Marca de Tiempo</th>
                <th>IP</th>
              </tr>
              <?php while ($row = $resRaw->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['id']); ?></td>
                  <td><?php echo htmlspecialchars($row['user']); ?></td>
                  <td><?php echo htmlspecialchars($row['user_agent']); ?></td>
                  <td><?php echo htmlspecialchars($row['screen_width']) . " x " . htmlspecialchars($row['screen_height']); ?></td>
                  <td><?php echo htmlspecialchars($row['viewport_width']) . " x " . htmlspecialchars($row['viewport_height']); ?></td>
                  <td><?php echo htmlspecialchars($row['language']); ?></td>
                  <td><?php echo htmlspecialchars($row['languages']); ?></td>
                  <td><?php echo htmlspecialchars($row['timezone_offset']); ?></td>
                  <td><?php echo htmlspecialchars($row['platform']); ?></td>
                  <td><?php echo htmlspecialchars($row['connection_type']); ?></td>
                  <td><?php echo htmlspecialchars($row['screen_color_depth']); ?></td>
                  <td><?php echo htmlspecialchars($row['url']); ?></td>
                  <td><?php echo htmlspecialchars($row['referrer']); ?></td>
                  <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                  <td><?php echo htmlspecialchars($row['ip']); ?></td>
                </tr>
              <?php endwhile; ?>
            </table>
